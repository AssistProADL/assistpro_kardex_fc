<?php
declare(strict_types=1);
/* =========================================================================
   AssistPro – Órdenes de Compra (Listado + Export CSV + Importador)
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
  if ($dmy==='') return null;
  if (preg_match('~^(\d{2})/(\d{2})/(\d{4})$~', $dmy, $m)) return "{$m[3]}-{$m[2]}-{$m[1]}";
  if (preg_match('~^\d{4}-\d{2}-\d{2}$~', $dmy)) return $dmy;
  return null;
}
function is_num($v){ return is_numeric($v) && (string)(int)$v === (string)$v; }
function next_folio(PDO $pdo): string {
  $n = (int)$pdo->query("SELECT COALESCE(MAX(id),0)+1 FROM th_oc")->fetchColumn();
  return 'OC'.str_pad((string)$n,6,'0',STR_PAD_LEFT);
}
function id_by(PDO $pdo, string $table, string $field, $value, string $return='id'): ?int {
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
    $st->execute([$producto_id]); $code=$st->fetchColumn();
    if($code){
      $st=$pdo->prepare("SELECT id FROM c_uom WHERE clave=? OR nombre=? LIMIT 1");
      $st->execute([$code,$code]); $id=$st->fetchColumn();
      if($id) return (int)$id;
    }
  }
  return 1;
}

/* ------------------------ SQL dinámico (compat) ------------------------ */
$EMP_SEL = function_exists('db_table_exists') && db_table_exists('c_empresa')
  ? 'SELECT id, nombre FROM c_empresa'
  : ((function_exists('db_table_exists') && db_table_exists('c_compania'))
      ? 'SELECT empresa_id AS id, des_cia AS nombre FROM c_compania'
      : 'SELECT 1 AS id, "SIN EMPRESA" AS nombre LIMIT 0');

$ALM_SEL = (function_exists('db_table_exists') && db_table_exists('c_almacen'))
  ? 'SELECT id, nombre FROM c_almacen'
  : ((function_exists('db_table_exists') && db_table_exists('c_almacenp'))
      ? 'SELECT id, nombre FROM c_almacenp'
      : 'SELECT 1 AS id, "SIN ALMACEN" AS nombre LIMIT 0');

$PROV_JOIN = (function_exists('db_table_exists') && db_table_exists('c_proveedor'))
  ? 'LEFT JOIN c_proveedor pr ON pr.id=h.proveedor_id'
  : ((function_exists('db_table_exists') && db_table_exists('c_proveedores'))
      ? "LEFT JOIN (SELECT ID_Proveedor AS id,
                           cve_proveedor AS clave,
                           Nombre AS razon_social,
                           Nombre AS nombre_comercial
                    FROM c_proveedores) pr ON pr.id=h.proveedor_id"
      : 'LEFT JOIN (SELECT NULL AS id, NULL AS clave, NULL AS razon_social, NULL AS nombre_comercial) pr ON 1=0');

/* -------------------------- Acciones AJAX ------------------------------ */
$action = P('action');

if ($action === 'cats') {
  $cats = [
    'empresas'  => db_all("$EMP_SEL ORDER BY nombre"),
    'almacenes' => db_all("$ALM_SEL ORDER BY nombre"),
  ];
  J($cats);
}

if ($action === 'list') {
  $p = []; $w = "WHERE 1=1";
  if ($e = P('empresa_id'))  { $w .= " AND h.empresa_id = ?"; $p[]=$e; }
  if ($a = P('almacen_id'))  { $w .= " AND h.almacen_id = ?"; $p[]=$a; }
  if (($s = P('status','')) !== '') { $w .= " AND h.status = ?"; $p[]=$s; }
  if ($q = trim((string)P('q',''))) {
    $w .= " AND (h.folio LIKE ? OR pr.nombre_comercial LIKE ? OR pr.razon_social LIKE ?)";
    $like = "%$q%"; array_push($p,$like,$like,$like);
  }
  $sql = "SELECT h.id, h.folio, h.fecha_oc, h.fecha_compromiso, h.fecha_recepcion_prev,
                 h.status,
                 e.nombre AS empresa, a.nombre AS almacen,
                 COALESCE(pr.razon_social, pr.nombre_comercial, pr.clave) AS proveedor,
                 COALESCE(SUM(d.cantidad*d.precio_unit),0) AS subtotal,
                 COALESCE(SUM(JSON_EXTRACT(d.impuestos_json,'$.iva')),0) AS iva,
                 COALESCE(SUM(d.cantidad*d.precio_unit)+SUM(JSON_EXTRACT(d.impuestos_json,'$.iva')),0) AS total
          FROM th_oc h
          LEFT JOIN ($EMP_SEL) e ON e.id=h.empresa_id
          LEFT JOIN ($ALM_SEL) a ON a.id=h.almacen_id
          $PROV_JOIN
          LEFT JOIN td_oc d ON d.oc_id=h.id
          $w
          GROUP BY h.id
          ORDER BY h.id DESC
          LIMIT 2000";
  $st=$pdo->prepare($sql); $st->execute($p);
  $rows=$st->fetchAll(PDO::FETCH_ASSOC);
  foreach($rows as &$r){
    $r['fecha_oc'] = ddmmyyyy($r['fecha_oc']);
    $r['fecha_compromiso'] = ddmmyyyy($r['fecha_compromiso']);
    $r['fecha_recepcion_prev'] = ddmmyyyy($r['fecha_recepcion_prev']);
  }
  J(['data'=>$rows]);
}

if ($action === 'export_csv') {
  $p=[]; $w="WHERE 1=1";
  if ($e = P('empresa_id'))  { $w.=" AND h.empresa_id=?"; $p[]=$e; }
  if ($a = P('almacen_id'))  { $w.=" AND h.almacen_id=?"; $p[]=$a; }
  if (($s=P('status',''))!==''){ $w.=" AND h.status=?"; $p[]=$s; }
  if ($q=trim((string)P('q',''))){
    $w.=" AND (h.folio LIKE ? OR pr.nombre_comercial LIKE ? OR pr.razon_social LIKE ?)";
    $like="%$q%"; array_push($p,$like,$like,$like);
  }
  $sql="SELECT h.folio,
               DATE_FORMAT(h.fecha_oc,'%d/%m/%Y') AS fecha_oc,
               DATE_FORMAT(h.fecha_compromiso,'%d/%m/%Y') AS fecha_compromiso,
               DATE_FORMAT(h.fecha_recepcion_prev,'%d/%m/%Y') AS fecha_prevista,
               e.nombre AS empresa, a.nombre AS almacen,
               COALESCE(pr.razon_social,pr.nombre_comercial,pr.clave) AS proveedor,
               h.status,
               COALESCE(SUM(d.cantidad*d.precio_unit),0) AS subtotal,
               COALESCE(SUM(JSON_EXTRACT(d.impuestos_json,'$.iva')),0) AS iva,
               COALESCE(SUM(d.cantidad*d.precio_unit)+SUM(JSON_EXTRACT(d.impuestos_json,'$.iva')),0) AS total
        FROM th_oc h
        LEFT JOIN ($EMP_SEL) e ON e.id=h.empresa_id
        LEFT JOIN ($ALM_SEL) a ON a.id=h.almacen_id
        $PROV_JOIN
        LEFT JOIN td_oc d ON d.oc_id=h.id
        $w
        GROUP BY h.id
        ORDER BY h.id DESC";
  $st=$pdo->prepare($sql); $st->execute($p);
  header('Content-Type:text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=ordenes_compra.csv');
  $out=fopen('php://output','w');
  fputcsv($out,['Folio','Fecha OC','Fecha Compromiso','Fecha Prevista','Empresa','Almacén','Proveedor','Estatus','Subtotal','IVA','Total']);
  while($r=$st->fetch(PDO::FETCH_ASSOC)) fputcsv($out,$r);
  fclose($out); exit;
}

if ($action === 'layout_csv') {
  header('Content-Type:text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=layout_oc.csv');
  $out=fopen('php://output','w');
  fputcsv($out,[
    'folio','fecha_oc(dd/mm/aaaa)','fecha_compromiso(dd/mm/aaaa)','fecha_prevista(dd/mm/aaaa)',
    'empresa_id','almacen_id','proveedor_id','oc_tipo_id','moneda_id','pedido_id','comentarios',
    'producto_clave','uom_clave','cantidad','precio_neto'
  ]);
  fputcsv($out,[
    'OC000999','19/10/2025','21/10/2025','21/10/2025',
    '1','1','1','1','1','','OC demo import',
    'SKU-001','PZA','10','58.00'
  ]);
  fclose($out); exit;
}

if ($action === 'preview_csv' && $_SERVER['REQUEST_METHOD']==='POST') {
  if (empty($_FILES['file']['tmp_name'])) J(['ok'=>false,'msg'=>'Sube un CSV.']);
  $rows = [];
  if (($h=fopen($_FILES['file']['tmp_name'],'r'))!==false) {
    $i=0; $header=[];
    while(($r=fgetcsv($h))!==false){ $i++;
      if($i===1){ $header=$r; continue; }
      if(count(array_filter($r))===0) continue;
      $rows[] = array_combine($header,$r);
    }
    fclose($h);
  }
  $folios=[];
  foreach($rows as $r){ $folios[$r['folio']??''] = true; }
  J(['ok'=>true,'total'=>count($rows),'ocs'=>count($folios),'sample'=>array_slice($rows,0,10)]);
}

if ($action === 'do_import' && $_SERVER['REQUEST_METHOD']==='POST') {
  try{
    $pdo->beginTransaction();
    $rows = json_decode((string)file_get_contents('php://input'), true) ?: [];
    if(!is_array($rows) || !$rows) throw new RuntimeException('Sin datos a importar.');

    if (!function_exists('db_table_exists') || !db_table_exists('c_producto')) {
      throw new RuntimeException("Catálogo c_producto no existe en esta BD.");
    }
    if (!db_table_exists('c_uom')) {
      throw new RuntimeException("Catálogo c_uom no existe en esta BD.");
    }

    $mapHdr = []; // folio -> oc_id
    $has_c_octipo = function_exists('db_table_exists') && db_table_exists('c_oc_tipo');
    $has_c_moneda = function_exists('db_table_exists') && db_table_exists('c_moneda');

    foreach($rows as $r){
      $folio = trim((string)($r['folio']??'')); if($folio==='') $folio = next_folio($pdo);
      $fecha_oc         = ymd($r['fecha_oc(dd/mm/aaaa)'] ?? $r['fecha_oc'] ?? '');
      $fecha_compromiso = ymd($r['fecha_compromiso(dd/mm/aaaa)'] ?? $r['fecha_compromiso'] ?? '');
      $fecha_prevista   = ymd($r['fecha_prevista(dd/mm/aaaa)'] ?? $r['fecha_prevista'] ?? '');

      $empresa_id   = id_by($pdo, (db_table_exists('c_empresa')?'c_empresa':'c_compania'), (db_table_exists('c_empresa')?'id':'empresa_id'), $r['empresa_id']??null, (db_table_exists('c_empresa')?'id':'empresa_id'));
      $almacen_tbl  = db_table_exists('c_almacen') ? 'c_almacen' : 'c_almacenp';
      $almacen_id   = id_by($pdo,$almacen_tbl,'id',$r['almacen_id']??null);

      // Proveedor (acepta ambos catálogos)
      $proveedor_id = null;
      if (db_table_exists('c_proveedor')) {
        $proveedor_id = id_by($pdo,'c_proveedor','id',$r['proveedor_id']??null);
      } elseif (db_table_exists('c_proveedores')) {
        $proveedor_id = id_by($pdo,'c_proveedores','ID_Proveedor',$r['proveedor_id']??null,'ID_Proveedor');
      }

      $oc_tipo_id   = $has_c_octipo ? id_by($pdo,'c_oc_tipo','id',$r['oc_tipo_id']??null) : null;
      $moneda_id    = $has_c_moneda ? id_by($pdo,'c_moneda','id',$r['moneda_id']??null) : null;
      $pedido_id    = ($r['pedido_id']??'')!=='' ? (int)$r['pedido_id'] : null;
      $comentarios  = trim((string)($r['comentarios']??''));

      if(!isset($mapHdr[$folio])){
        $st=$pdo->prepare("
          INSERT INTO th_oc(
            empresa_id, proveedor_id, almacen_id, oc_tipo_id, moneda_id,
            folio, fecha_oc, fecha_compromiso, fecha_recepcion_prev,
            pedido_id, comentarios, status, usuario_id, created_at, updated_at
          )
          VALUES(?,?,?,?,?, ?,?,?,?, ?,?, 'ABIERTA', 1, NOW(), NOW())
        ");
        $st->execute([
          $empresa_id,$proveedor_id,$almacen_id,$oc_tipo_id,$moneda_id,
          $folio,$fecha_oc,$fecha_compromiso,$fecha_prevista,
          $pedido_id,$comentarios
        ]);
        $mapHdr[$folio] = (int)$pdo->lastInsertId();
      }

      $oc_id = $mapHdr[$folio];

      // Producto por clave (c_producto requerido)
      $prod_clave = trim((string)($r['producto_clave']??''));
      if($prod_clave==='') throw new RuntimeException("Falta producto_clave para folio $folio.");
      $st=$pdo->prepare("SELECT id FROM c_producto WHERE clave=?");
      $st->execute([$prod_clave]); $producto_id = (int)$st->fetchColumn();
      if(!$producto_id) throw new RuntimeException("Producto '$prod_clave' no existe en c_producto.");

      $uom_clave = trim((string)($r['uom_clave']??''));
      $uom_id = resolve_uom_id($pdo, $uom_clave, $producto_id);

      $cantidad = (float)($r['cantidad']??0);
      $precio   = (float)($r['precio_neto']??0);
      if($cantidad<=0) throw new RuntimeException("Cantidad inválida ($prod_clave).");

      $iva = round($precio*$cantidad*0.16, 2);
      $imp_json = json_encode(['iva'=>$iva], JSON_UNESCAPED_UNICODE);

      $st=$pdo->prepare("INSERT INTO td_oc(oc_id,producto_id,uom_id,cantidad,precio_unit,impuestos_json,comentarios,status_linea,usuario_id,created_at,updated_at)
                         VALUES(?,?,?,?,?,?,?,?,1,NOW(),NOW())");
      $st->execute([$oc_id,$producto_id,$uom_id,$cantidad,$precio,$imp_json,null,'ABIERTA']);
    }
    $pdo->commit();
    J(['ok'=>true]);
  }catch(Throwable $e){
    if($pdo->inTransaction()) $pdo->rollBack();
    J(['ok'=>false,'msg'=>$e->getMessage()]);
  }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Órdenes de Compra</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="../assets/bootstrap.min.css" rel="stylesheet">
<link href="../assets/fontawesome.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body{background:#f4f6fb;font-size:12px;}
table{font-size:10px}
.sticky-head thead th{position:sticky;top:0;background:#f8f9fa;z-index:2}
.actions .btn{padding:.15rem .35rem;font-size:.75rem}
.kpi-dot{display:inline-block;width:8px;height:8px;border-radius:50%}
.on-time{background:#28a745}.late{background:#dc3545}.pending{background:#ffc107}
</style>
</head>
<body>
<?php require_once __DIR__ . '/../bi/_menu_global.php'; ?>

<div class="container-fluid mt-2">
  <div class="row align-items-center mb-2">
    <div class="col">
      <h5 class="mb-0" style="color:#0F5AAD;"><i class="bi bi-file-earmark-text me-1"></i> Órdenes de Compra</h5>
      <small class="text-muted">Listado, filtros, acciones y carga masiva</small>
    </div>
    <div class="col-auto">
      <a class="btn btn-primary btn-sm" href="orden_compra_edit.php"><i class="bi bi-plus-circle"></i> Nueva OC</a>
      <a class="btn btn-outline-secondary btn-sm" href="orden_compra.php?action=export_csv"><i class="bi bi-download"></i> Exportar CSV</a>
    </div>
  </div>

  <div class="card shadow-sm mb-2">
    <div class="card-body py-2">
      <div class="row g-2 align-items-end">
        <div class="col-md-2">
          <label class="form-label">Empresa</label>
          <select id="f_empresa" class="form-select form-select-sm"><option value="">Todas</option></select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Almacén</label>
          <select id="f_almacen" class="form-select form-select-sm"><option value="">Todos</option></select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Estatus</label>
          <select id="f_status" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option>ABIERTA</option>
            <option>CERRADA</option>
            <option>CANCELADA</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Buscar</label>
          <input id="f_q" class="form-control form-control-sm" placeholder="Folio / Proveedor…">
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button class="btn btn-primary btn-sm" onclick="list()"><i class="bi bi-search"></i></button>
          <a class="btn btn-outline-secondary btn-sm" href="orden_compra.php?action=export_csv"><i class="bi bi-download"></i> CSV</a>
        </div>
      </div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle sticky-head">
      <thead class="table-light">
        <tr>
          <th style="width:140px">Acciones</th>
          <th>Folio</th><th>Fecha OC</th><th>Compromiso</th><th>Recepción Prev.</th>
          <th>Empresa</th><th>Almacén</th><th>Proveedor</th>
          <th class="text-end">Subtotal</th><th class="text-end">IVA</th><th class="text-end">Total</th>
        </tr>
      </thead>
      <tbody id="tb"></tbody>
    </table>
  </div>

  <div class="card mt-3">
    <div class="card-header py-2"><b>Importar OCs</b></div>
    <div class="card-body">
      <div class="alert alert-info py-2">
        Layout requerido:
        <div class="mt-1">
          <code>folio, fecha_oc(dd/mm/aaaa), fecha_compromiso(dd/mm/aaaa), fecha_prevista(dd/mm/aaaa),
empresa_id, almacen_id, proveedor_id, oc_tipo_id, moneda_id, pedido_id, comentarios,
producto_clave, uom_clave, cantidad, precio_neto</code>
        </div>
      </div>
      <div class="d-flex gap-2 mb-2">
        <a href="orden_compra.php?action=layout_csv" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-download"></i> Descargar layout
        </a>
        <label class="btn btn-outline-primary btn-sm mb-0">
          <input type="file" id="csvFile" accept=".csv" hidden>
          <i class="bi bi-filetype-csv"></i> Seleccionar archivo
        </label>
        <button class="btn btn-primary btn-sm" id="btnPreview"><i class="bi bi-eye"></i> Previsualizar</button>
        <button class="btn btn-success btn-sm" id="btnImport" disabled><i class="bi bi-check2-circle"></i> Importar</button>
      </div>
      <div id="impInfo" class="small text-muted mb-2">Selecciona un CSV para previsualizar…</div>
      <div class="table-responsive" style="max-height:50vh; overflow:auto;">
        <table class="table table-sm table-striped">
          <thead class="table-light" id="impHead"></thead>
          <tbody id="impBody"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
<script>
const $ = s => document.querySelector(s);

async function cats(){
  const r = await fetch('orden_compra.php?action=cats').then(x=>x.json());
  (r.empresas||[]).forEach(x=>$('#f_empresa').insertAdjacentHTML('beforeend', `<option value="${x.id}">${x.nombre}</option>`));
  (r.almacenes||[]).forEach(x=>$('#f_almacen').insertAdjacentHTML('beforeend', `<option value="${x.id}">${x.nombre}</option>`));
}
function actionsCell(row){
  // Acciones al lado izquierdo: Editar, PDF, Recibir, Cerrar, Cancelar
  return `
    <div class="btn-group btn-group-sm" role="group">
      <a class="btn btn-outline-primary" title="Editar" href="orden_compra_edit.php?id=${row.id}"><i class="bi bi-pencil-square"></i></a>
      <a class="btn btn-outline-secondary" title="PDF" target="_blank" href="oc_pdf.php?id=${row.id}"><i class="bi bi-filetype-pdf"></i></a>
      <a class="btn btn-outline-success" title="Recepción" href="oc_recibir.php?id=${row.id}"><i class="bi bi-box-seam"></i></a>
      <a class="btn btn-outline-warning" title="Cerrar" href="oc_cerrar.php?id=${row.id}"><i class="bi bi-check2-square"></i></a>
      <a class="btn btn-outline-danger"  title="Cancelar" href="oc_cancelar.php?id=${row.id}"><i class="bi bi-x-octagon"></i></a>
    </div>
  `;
}
function statusDot(row){
  const fc = row.fecha_compromiso;
  if(!fc) return '';
  const [d,m,y] = fc.split('/');
  const cmp = new Date(`${y}-${m}-${d}T00:00:00`);
  const today = new Date(); today.setHours(0,0,0,0);
  if(row.status === 'CERRADA'){ return ''; }
  return cmp < today ? ' <span class="kpi-dot late" title="Fuera de tiempo"></span>' : ' <span class="kpi-dot pending" title="En tiempo"></span>';
}
async function list(){
  const p = new URLSearchParams({
    action:'list',
    empresa_id: $('#f_empresa').value,
    almacen_id: $('#f_almacen').value,
    status: $('#f_status').value,
    q: $('#f_q')?.value||''
  });
  const r = await fetch('orden_compra.php?'+p.toString()).then(x=>x.json());
  const tb = $('#tb'); tb.innerHTML = '';
  (r.data||[]).forEach(row=>{
    tb.insertAdjacentHTML('beforeend', `
      <tr>
        <td>${actionsCell(row)}</td>
        <td>${row.folio||''}</td>
        <td>${row.fecha_oc||''}</td>
        <td>${row.fecha_compromiso||''}${statusDot(row)}</td>
        <td>${row.fecha_recepcion_prev||''}</td>
        <td>${row.empresa||''}</td>
        <td>${row.almacen||''}</td>
        <td>${row.proveedor||''}</td>
        <td class="text-end">${Number(row.subtotal||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</td>
        <td class="text-end">${Number(row.iva||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</td>
        <td class="text-end">${Number(row.total||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</td>
      </tr>
    `);
  });
}
cats().then(list);

// ---------- Importador ----------
const csvFile = document.getElementById('csvFile');
document.getElementById('btnPreview').onclick = async () => {
  if(!csvFile.files.length){ alert('Selecciona un CSV'); return; }
  const fd = new FormData(); fd.append('file', csvFile.files[0]);
  const r = await fetch('orden_compra.php?action=preview_csv', {method:'POST', body:fd}).then(x=>x.json());
  $('#impInfo').textContent = `Filas: ${r.total||0} | OCs distintas: ${r.ocs||0}`;
  const head = Object.keys((r.sample&&r.sample[0])||{
    folio:'', 'fecha_oc(dd/mm/aaaa)':'', 'fecha_compromiso(dd/mm/aaaa)':'','fecha_prevista(dd/mm/aaaa)':'',
    empresa_id:'', almacen_id:'', proveedor_id:'', oc_tipo_id:'', moneda_id:'', pedido_id:'', comentarios:'',
    producto_clave:'', uom_clave:'', cantidad:'', precio_neto:''
  });
  document.getElementById('impHead').innerHTML = '<tr>'+head.map(h=>`<th>${h}</th>`).join('')+'</tr>';
  document.getElementById('impBody').innerHTML = (r.sample||[]).map(row => '<tr>'+head.map(h=>`<td>${row[h]??''}</td>`).join('')+'</tr>').join('');
  document.getElementById('btnImport').disabled = !r.total;
};
document.getElementById('btnImport').onclick = async () => {
  if(!csvFile.files.length){ alert('Selecciona un CSV'); return; }
  const text = await csvFile.files[0].text();
  const lines = text.split(/\r?\n/).filter(x=>x.trim()!=='');
  const header = lines.shift().split(',');
  const rows = lines.map(line=>{
    const cols = line.split(',');
    const o={}; header.forEach((h,i)=>o[h]=cols[i]??''); return o;
  });
  const r = await fetch('orden_compra.php?action=do_import', {method:'POST', body: JSON.stringify(rows)}).then(x=>x.json());
  if(!r.ok){ alert(r.msg||'Error importando'); return; }
  alert('Importación exitosa');
  list();
};
</script>
</body>
</html>
