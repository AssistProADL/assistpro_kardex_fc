<?php
declare(strict_types=1);
/* =========================================================================
   AssistPro SFA – Órdenes de Compra (Listado + Export CSV + Importador)
   ========================================================================= */
require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ---------------------------- Helpers ---------------------------------- */
function J($a){ header('Content-Type: application/json; charset=utf-8'); echo json_encode($a); exit; }
function P($k,$d=null){ return $_REQUEST[$k] ?? $d; }
function ddmmyyyy(?string $ymd): string { if(!$ymd) return ''; $t=strtotime($ymd); return $t?date('d/m/Y',$t):''; }
function ymd(?string $dmy): ?string {
  if(!$dmy) return null;
  $dmy = trim($dmy);
  if ($dmy === '') return null;
  if (preg_match('~^(\d{2})/(\d{2})/(\d{4})$~', $dmy, $m)) return "{$m[3]}-{$m[2]}-{$m[1]}";
  if (preg_match('~^\d{4}-\d{2}-\d{2}$~', $dmy)) return $dmy;
  return null;
}
function is_num($v){ return is_numeric($v) && (string)(int)$v === (string)$v; }
function n($v){ return $v===''? null : $v; }
function money($v){ return number_format((float)$v,2); }

/** Resuelve un ID en un catálogo genérico por código o ID numérico */
function resolve_catalog_id(PDO $pdo, string $table, string $field, $value, string $return='id'): ?int {
  if ($value===null || $value==='') return null;
  if (is_num($value)) return (int)$value;
  $sql = "SELECT $return FROM $table WHERE $field = ? LIMIT 1";
  $st=$pdo->prepare($sql); $st->execute([trim((string)$value)]);
  $id=$st->fetchColumn();
  return $id? (int)$id : null;
}
function resolve_uom_id(PDO $pdo, $uom, ?int $producto_id): int {
  if (is_num($uom)) return (int)$uom;
  if (is_string($uom) && $uom!=='') {
    $st=$pdo->prepare("SELECT id FROM c_uom WHERE clave=? OR nombre=? LIMIT 1");
    $st->execute([$uom,$uom]);
    $id=$st->fetchColumn(); if($id) return (int)$id;
  }
  if ($producto_id){
    $st=$pdo->prepare("SELECT uom_base FROM c_producto WHERE id=?");
    $st->execute([$producto_id]);
    $id=$st->fetchColumn(); if($id) return (int)$id;
  }
  return 1; // fallback: unidad
}

/* ------------------------- Detectar columnas c_almacenp ----------------- */

$almCols = $pdo->query("SHOW COLUMNS FROM c_almacenp")->fetchAll(PDO::FETCH_ASSOC);

$almIdCol   = 'id';
$almCodeCol = 'clave';
$almNameCol = 'nombre';

if ($almCols) {
  // ID = primera columna por defecto
  $almIdCol = $almCols[0]['Field'] ?? 'id';

  // Candidatos de código/clave
  $codeCandidates = [
    'clave','Clave','CLAVE',
    'clave_almacen','Clave_Almacen','CLAVE_ALMACEN',
    'codigo','Codigo','CODIGO',
    'cve_almacen','Cve_almacen','CVE_ALMACEN'
  ];
  foreach ($almCols as $c) {
    $f = $c['Field'];
    if (in_array($f, $codeCandidates, true)) {
      $almCodeCol = $f;
      break;
    }
  }
  if (!in_array($almCodeCol, array_column($almCols,'Field'), true)) {
    $almCodeCol = $almIdCol;
  }

  // Candidatos de nombre/descripcion
  $nameCandidates = [
    'descripcion','Descripcion','DESCRIPCION',
    'almacen','Almacen','ALMACEN',
    'nombre','Nombre','NOMBRE'
  ];
  foreach ($almCols as $c) {
    $f = $c['Field'];
    if (in_array($f, $nameCandidates, true)) {
      $almNameCol = $f;
      break;
    }
  }
  if (!in_array($almNameCol, array_column($almCols,'Field'), true)) {
    if (isset($almCols[1])) {
      $almNameCol = $almCols[1]['Field'];
    } else {
      $almNameCol = $almIdCol;
    }
  }
}

/* ------------------------- Carga de catálogos -------------------------- */

// Proveedores: ya tenemos estructura con ID_Proveedor y Nombre
$proveedores = $pdo->query("
  SELECT ID_Proveedor AS id, Nombre
  FROM c_proveedores
  WHERE Activo = 1
  ORDER BY Nombre
")->fetchAll(PDO::FETCH_ASSOC);

// Almacenes: dinámico según columnas detectadas
$almacenes = $pdo->query("
  SELECT 
    {$almIdCol}   AS id,
    {$almCodeCol} AS clave,
    {$almNameCol} AS nombre
  FROM c_almacenp
  ORDER BY {$almCodeCol}
")->fetchAll(PDO::FETCH_ASSOC);

// Monedas: asumimos estructura id/clave/nombre
$monedas = $pdo->query("
  SELECT id, clave, nombre
  FROM c_moneda
  ORDER BY clave
")->fetchAll(PDO::FETCH_ASSOC);

/* ------------------------- Filtros de búsqueda ------------------------- */

$folio       = P('folio');
$proveedorId = P('proveedor_id');
$almacenId   = P('almacen_id');
$estatus     = P('estatus');
$fdesde_in   = P('fecha_desde');
$fhasta_in   = P('fecha_hasta');
$export      = P('export'); // csv

$fdesde = ymd($fdesde_in);
$fhasta = ymd($fhasta_in);

$where = [];
$params = [];

if ($folio)       { $where[]="h.folio LIKE :folio";            $params[':folio']="%$folio%"; }
if ($proveedorId) { $where[]="h.proveedor_id = :proveedor_id"; $params[':proveedor_id']=(int)$proveedorId; }
if ($almacenId)   { $where[]="h.almacen_id   = :almacen_id";   $params[':almacen_id']  =(int)$almacenId; }
if ($estatus)     { $where[]="h.estatus      = :estatus";      $params[':estatus']     =$estatus; }
if ($fdesde)      { $where[]="DATE(h.fecha) >= :fdesde";       $params[':fdesde']      =$fdesde; }
if ($fhasta)      { $where[]="DATE(h.fecha) <= :fhasta";       $params[':fhasta']      =$fhasta; }

$whereSql = $where ? 'WHERE '.implode(' AND ',$where) : '';

/* ------------------------- Consulta principal -------------------------- */

$sql = "
SELECT
  h.id,
  h.folio,
  h.fecha,
  h.estatus,
  h.proveedor_id,
  p.Nombre AS proveedor_nombre,
  h.almacen_id,
  a.{$almCodeCol}  AS almacen_clave,
  a.{$almNameCol}  AS almacen_nombre,
  h.moneda_id,
  m.clave AS moneda_clave,
  COALESCE(SUM(d.cantidad),0) AS total_cantidad,
  COALESCE(SUM(d.cantidad * d.precio),0) AS total_monto
FROM t_ordencompra h
LEFT JOIN td_ordencompra d   ON d.orden_id   = h.id
LEFT JOIN c_proveedores  p   ON p.ID_Proveedor = h.proveedor_id
LEFT JOIN c_almacenp     a   ON a.{$almIdCol}  = h.almacen_id
LEFT JOIN c_moneda       m   ON m.id         = h.moneda_id
{$whereSql}
GROUP BY
  h.id, h.folio, h.fecha, h.estatus,
  h.proveedor_id, p.Nombre,
  h.almacen_id, a.{$almCodeCol}, a.{$almNameCol},
  h.moneda_id, m.clave
ORDER BY h.fecha DESC, h.folio DESC
LIMIT 500
";

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

/* -------------------------- Totales de cards --------------------------- */

$totalOrdenes  = count($rows);
$totalCantidad = 0;
$totalMonto    = 0;
foreach ($rows as $r){
  $totalCantidad += (float)$r['total_cantidad'];
  $totalMonto    += (float)$r['total_monto'];
}

/* -------------------------- Exportación CSV ---------------------------- */
if ($export === 'csv') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="ordenes_compra.csv"');
  $out = fopen('php://output','w');
  fputcsv($out, [
    'Folio','Fecha','Estatus','Proveedor','Almacén','Moneda',
    'Cantidad Total','Monto Total'
  ]);
  foreach ($rows as $r){
    fputcsv($out, [
      $r['folio'],
      ddmmyyyy($r['fecha']),
      $r['estatus'],
      $r['proveedor_nombre'],
      $r['almacen_clave'].' - '.$r['almacen_nombre'],
      $r['moneda_clave'],
      $r['total_cantidad'],
      $r['total_monto'],
    ]);
  }
  fclose($out);
  exit;
}

/* ============================== IMPORTADOR ============================= */
/*  Tu importador original se conserva, solo usando db_pdo().             */

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['accion']) && $_POST['accion']==='importar') {
  try {
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error']!==UPLOAD_ERR_OK) {
      throw new RuntimeException('Archivo no recibido correctamente');
    }

    $tmp = $_FILES['archivo']['tmp_name'];
    $nombre = $_FILES['archivo']['name'];

    $fh = fopen($tmp,'r');
    if (!$fh) throw new RuntimeException('No se pudo leer el archivo');

    $header = fgetcsv($fh);
    if (!$header) throw new RuntimeException('Archivo vacío o encabezado inválido');

    $pdo->beginTransaction();

    $rowNum = 1;
    $creadas = 0;
    $actualizadas = 0;

    while (($data = fgetcsv($fh)) !== false) {
      $rowNum++;
      $row = array_combine($header, $data);

      $folioImp      = trim((string)($row['Folio'] ?? ''));
      $fechaImp      = ymd($row['Fecha'] ?? '');
      $estatusImp    = $row['Estatus']   ?? 'ABIERTA';
      $provImp       = $row['Proveedor'] ?? '';
      $almImp        = $row['Almacen']   ?? '';
      $monImp        = $row['Moneda']    ?? '';
      $codProdImp    = $row['Producto']  ?? '';
      $cantImp       = (float)($row['Cantidad'] ?? 0);
      $precioImp     = (float)($row['Precio']   ?? 0);
      $uomImp        = $row['UOM']       ?? null;

      if ($folioImp==='') continue;

      // Resolver proveedor, almacén, moneda, producto, UOM según tu lógica
      $proveedor_id = resolve_catalog_id($pdo, 'c_proveedores', 'ID_Proveedor', $provImp, 'ID_Proveedor');
      if (!$proveedor_id) throw new RuntimeException("Proveedor inválido en fila $rowNum: $provImp");

      $almacen_id   = resolve_catalog_id($pdo, 'c_almacenp', $almCodeCol, $almImp, $almIdCol);
      if (!$almacen_id) throw new RuntimeException("Almacén inválido en fila $rowNum: $almImp");

      $moneda_id    = resolve_catalog_id($pdo, 'c_moneda', 'clave', $monImp, 'id');
      if (!$moneda_id) throw new RuntimeException("Moneda inválida en fila $rowNum: $monImp");

      // Producto
      $producto_id = null;
      if ($codProdImp!=='') {
        $stP = $pdo->prepare("SELECT id FROM c_producto WHERE codigo=? OR sku=? LIMIT 1");
        $stP->execute([$codProdImp,$codProdImp]);
        $producto_id = (int)$stP->fetchColumn();
        if (!$producto_id) throw new RuntimeException("Producto inválido en fila $rowNum: $codProdImp");
      }

      $uom_id = resolve_uom_id($pdo, $uomImp, $producto_id);

      // Ver si ya existe encabezado por folio
      $stH = $pdo->prepare("SELECT id FROM t_ordencompra WHERE folio=? LIMIT 1");
      $stH->execute([$folioImp]);
      $orden_id = $stH->fetchColumn();

      if ($orden_id) {
        // actualizar encabezado básico
        $stUp = $pdo->prepare("
          UPDATE t_ordencompra
          SET fecha=?, estatus=?, proveedor_id=?, almacen_id=?, moneda_id=?
          WHERE id=?
        ");
        $stUp->execute([$fechaImp,$estatusImp,$proveedor_id,$almacen_id,$moneda_id,$orden_id]);
        $actualizadas++;
      } else {
        $stIns = $pdo->prepare("
          INSERT INTO t_ordencompra (folio,fecha,estatus,proveedor_id,almacen_id,moneda_id)
          VALUES (?,?,?,?,?,?)
        ");
        $stIns->execute([$folioImp,$fechaImp,$estatusImp,$proveedor_id,$almacen_id,$moneda_id]);
        $orden_id = (int)$pdo->lastInsertId();
        $creadas++;
      }

      // Insertar detalle
      $stD = $pdo->prepare("
        INSERT INTO td_ordencompra (orden_id,producto_id,uom_id,cantidad,precio)
        VALUES (?,?,?,?,?)
      ");
      $stD->execute([$orden_id,$producto_id,$uom_id,$cantImp,$precioImp]);
    }

    fclose($fh);
    $pdo->commit();

    J([
      'ok' => true,
      'creadas' => $creadas,
      'actualizadas' => $actualizadas
    ]);

  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    J([
      'ok' => false,
      'error' => $e->getMessage()
    ]);
  }
}

/* ============================ VISTA HTML =============================== */
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title>Órdenes de Compra – AssistPro SFA</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="../assets/bootstrap.min.css" rel="stylesheet" />
  <link href="../assets/fontawesome.min.css" rel="stylesheet" />
  <style>
    body{background:#f4f6fb;font-size:12px;}
    .ap-card{background:#fff;border-radius:10px;box-shadow:0 1px 3px rgba(15,90,173,.15);padding:15px;margin-bottom:15px;}
    .ap-label{font-size:11px;color:#6c757d;margin-bottom:2px;}
    .ap-value{font-size:13px;font-weight:600;}
    .table-sm td,.table-sm th{padding:.25rem .4rem;font-size:11px;white-space:nowrap;}
    .table thead th{position:sticky;top:0;background:#0F5AAD;color:#fff;z-index:2;}
    .ap-scroll{max-height:480px;overflow:auto;}
    .badge{font-size:10px;}
    .form-control,.form-select{font-size:12px;}
    .btn-sm{font-size:11px;}
  </style>
</head>
<body>
<?php require_once __DIR__ . '/../_menu_global.php'; ?>

<div class="container-fluid mt-3">
  <div class="row mb-2">
    <div class="col">
      <h4 class="mb-0" style="color:#0F5AAD;">
        <i class="fa fa-file-invoice-dollar me-1"></i>
        Órdenes de Compra
      </h4>
      <small class="text-muted">Listado, filtros y carga masiva</small>
    </div>
  </div>

  <!-- Cards resumen -->
  <div class="row mb-2">
    <div class="col-md-4 col-sm-6 mb-2">
      <div class="ap-card">
        <div class="ap-label">Órdenes encontradas</div>
        <div class="ap-value">
          <i class="fa fa-list me-1"></i><?php echo $totalOrdenes; ?>
        </div>
      </div>
    </div>
    <div class="col-md-4 col-sm-6 mb-2">
      <div class="ap-card">
        <div class="ap-label">Cantidad total</div>
        <div class="ap-value">
          <i class="fa fa-cubes me-1"></i><?php echo money($totalCantidad); ?>
        </div>
      </div>
    </div>
    <div class="col-md-4 col-sm-6 mb-2">
      <div class="ap-card">
        <div class="ap-label">Monto total</div>
        <div class="ap-value">
          <i class="fa fa-dollar-sign me-1"></i><?php echo money($totalMonto); ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Filtros -->
  <div class="ap-card mb-3">
    <form method="get" class="row g-2">
      <div class="col-md-2 col-sm-6">
        <label class="ap-label">Folio</label>
        <input type="text" name="folio" class="form-control" value="<?php echo htmlspecialchars((string)$folio); ?>" />
      </div>
      <div class="col-md-2 col-sm-6">
        <label class="ap-label">Proveedor</label>
        <select name="proveedor_id" class="form-select">
          <option value="">Todos</option>
          <?php foreach($proveedores as $p): ?>
            <option value="<?php echo $p['id']; ?>" <?php echo $proveedorId==$p['id']?'selected':''; ?>>
              <?php echo htmlspecialchars($p['Nombre']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 col-sm-6">
        <label class="ap-label">Almacén</label>
        <select name="almacen_id" class="form-select">
          <option value="">Todos</option>
          <?php foreach($almacenes as $a): ?>
            <option value="<?php echo $a['id']; ?>" <?php echo $almacenId==$a['id']?'selected':''; ?>>
              <?php echo htmlspecialchars($a['clave'].' - '.$a['nombre']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 col-sm-6">
        <label class="ap-label">Estatus</label>
        <select name="estatus" class="form-select">
          <option value="">Todos</option>
          <option value="ABIERTA"   <?php echo $estatus==='ABIERTA'?'selected':''; ?>>Abierta</option>
          <option value="CERRADA"   <?php echo $estatus==='CERRADA'?'selected':''; ?>>Cerrada</option>
          <option value="CANCELADA" <?php echo $estatus==='CANCELADA'?'selected':''; ?>>Cancelada</option>
          <option value="SURTIDA"   <?php echo $estatus==='SURTIDA'?'selected':''; ?>>Surtida Parcial / Total</option>
        </select>
      </div>
      <div class="col-md-2 col-sm-6">
        <label class="ap-label">Fecha desde</label>
        <input type="text" name="fecha_desde" class="form-control" placeholder="dd/mm/aaaa"
               value="<?php echo htmlspecialchars($fdesde_in ?? ''); ?>" />
      </div>
      <div class="col-md-2 col-sm-6">
        <label class="ap-label">Fecha hasta</label>
        <input type="text" name="fecha_hasta" class="form-control" placeholder="dd/mm/aaaa"
               value="<?php echo htmlspecialchars($fhasta_in ?? ''); ?>" />
      </div>

      <div class="col-12 d-flex justify-content-end mt-2">
        <button type="submit" class="btn btn-sm btn-primary me-2">
          <i class="fa fa-search"></i> Buscar
        </button>
        <a href="orden_compra.php" class="btn btn-sm btn-outline-secondary">
          <i class="fa fa-eraser"></i> Limpiar
        </a>
        <a href="?<?php
          $qs = $_GET; $qs['export']='csv';
          echo htmlspecialchars(http_build_query($qs));
        ?>" class="btn btn-sm btn-outline-success ms-2">
          <i class="fa fa-file-csv"></i> Exportar CSV
        </a>
      </div>
    </form>
  </div>

  <!-- Grilla -->
  <div class="ap-card mb-3">
    <div class="ap-scroll">
      <table class="table table-sm table-hover table-bordered mb-0">
        <thead>
          <tr>
            <th>Folio</th>
            <th>Fecha</th>
            <th>Estatus</th>
            <th>Proveedor</th>
            <th>Almacén</th>
            <th>Moneda</th>
            <th>Cantidad Total</th>
            <th>Monto Total</th>
          </tr>
        </thead>
        <tbody>
        <?php if(!$rows): ?>
          <tr>
            <td colspan="8" class="text-center text-muted">
              No se encontraron órdenes con los criterios indicados.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach($rows as $r): ?>
            <tr>
              <td><?php echo htmlspecialchars($r['folio']); ?></td>
              <td><?php echo ddmmyyyy($r['fecha']); ?></td>
              <td><?php echo htmlspecialchars($r['estatus']); ?></td>
              <td><?php echo htmlspecialchars($r['proveedor_nombre']); ?></td>
              <td><?php echo htmlspecialchars($r['almacen_clave'].' - '.$r['almacen_nombre']); ?></td>
              <td><?php echo htmlspecialchars($r['moneda_clave']); ?></td>
              <td class="text-end"><?php echo money($r['total_cantidad']); ?></td>
              <td class="text-end"><?php echo money($r['total_monto']); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Importador -->
  <div class="ap-card">
    <h6 class="mb-2"><i class="fa fa-upload me-1"></i>Importar Órdenes de Compra (CSV)</h6>
    <form method="post" enctype="multipart/form-data" onsubmit="return importarOC(this);">
      <input type="hidden" name="accion" value="importar" />
      <div class="row g-2 align-items-end">
        <div class="col-md-4 col-sm-6">
          <label class="ap-label">Archivo CSV</label>
          <input type="file" name="archivo" class="form-control" accept=".csv" required />
        </div>
        <div class="col-md-3 col-sm-6">
          <button type="submit" class="btn btn-sm btn-primary">
            <i class="fa fa-file-import"></i> Importar
          </button>
        </div>
        <div class="col-md-5 col-sm-12">
          <small class="text-muted">
            Formato: Folio, Fecha, Estatus, Proveedor, Almacen, Moneda, Producto, Cantidad, Precio, UOM
          </small>
        </div>
      </div>
    </form>
    <div id="import_result" class="mt-2" style="font-size:11px;"></div>
  </div>
</div>

<?php require_once __DIR__ . '/../_menu_global_end.php'; ?>
<script>
async function importarOC(form){
  const div = document.getElementById('import_result');
  div.textContent = 'Procesando...';
  const fd = new FormData(form);
  const res = await fetch('orden_compra.php', {method:'POST', body:fd});
  const data = await res.json().catch(()=>null);
  if(!data){
    div.textContent = 'Error al procesar respuesta del servidor';
    return false;
  }
  if(data.ok){
    div.textContent = 'Importación completada. Órdenes creadas: '+data.creadas+
                      ', actualizadas: '+data.actualizadas;
    setTimeout(()=>location.reload(),1500);
  }else{
    div.textContent = 'Error: '+data.error;
  }
  return false;
}
</script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>

