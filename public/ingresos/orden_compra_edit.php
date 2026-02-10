<?php
// public/ingresos/orden_compra_edit.php
require_once __DIR__ . '/../../app/db.php';

$pdo = db_pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function upper($v): string { return strtoupper(trim((string)$v)); }

/**
 * Genera folio tipo OCNYYYYMMDD-001 / OCIYYYYMMDD-001
 * - Se basa en th_aduana.Pedimento
 * - Concurrencia: dentro de transacción, toma el último folio del día y suma +1
 */
function generarFolioOC(PDO $pdo, string $tipoOc, ?string $fechaISO = null): string {
    // Fuente única: SP + tabla c_folios
    $tipoOc = upper($tipoOc);
    if ($tipoOc === '') $tipoOc = 'OCN';

    $fechaISO = $fechaISO ?: date('Y-m-d');

    $startedTx = false;
    try {
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $startedTx = true;
        }

        $stmt = $pdo->prepare("
            CALL sp_next_folio_diario(
                :empresa_id,
                :modulo,
                :serie,
                :prefijo,
                :fecha_doc,
                :usuario,
                @p_folio,
                @p_consecutivo
            )
        ");

        $stmt->execute([
            ':empresa_id' => 1,
            ':modulo'     => 'OC',
            ':serie'      => '01',
            ':prefijo'    => $tipoOc,
            ':fecha_doc'  => $fechaISO,
            ':usuario'    => 'system',
        ]);

        // Importante: cerrar cursor para permitir siguientes queries en algunos drivers
        $stmt->closeCursor();

        $row = $pdo->query("SELECT @p_folio AS folio")->fetch(PDO::FETCH_ASSOC);
        $folio = (string)($row['folio'] ?? '');

        if ($folio === '') {
            throw new Exception('SP no devolvió folio');
        }

        if ($startedTx) {
            $pdo->commit();
        }

        return $folio;

    } catch (Throwable $e) {
        if ($startedTx && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * num_pedimento interno (legacy) - secuencia global numérica
 * - Evita MAX() FOR UPDATE (que en MySQL suele fallar).
 * - Toma el último num_pedimento existente y suma.
 */
function generarNumPedimentoGlobal(PDO $pdo): int {
    $st = $pdo->query("
        SELECT num_pedimento
        FROM th_aduana
        ORDER BY num_pedimento DESC
        LIMIT 1
        FOR UPDATE
    ");
    $max = (int)$st->fetchColumn();
    return $max + 1;
}

$idAduana   = isset($_GET['id_aduana']) ? (int)$_GET['id_aduana'] : 0;
$esEdicion  = $idAduana > 0;
$mensajeError = '';
$ok = isset($_GET['ok']) ? (int)$_GET['ok'] : 0;

/** =================== Catálogos =================== */
$proveedores = [];
$almacenes   = [];
$productos   = [];
$mapProd     = [];

try {
    $proveedores = $pdo->query("
        SELECT ID_Proveedor, Nombre
        FROM c_proveedores
        WHERE (Activo=1 OR Activo='1' OR Activo='S' OR Activo IS NULL)
        ORDER BY Nombre
    ")->fetchAll(PDO::FETCH_ASSOC);

// Helper: validar si existe una columna (para despliegue/guardado evolutivo sin romper)
function column_exists(PDO $pdo, string $table, string $column): bool {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable $e) { return false; }
}

$has_folio_oc   = column_exists($pdo, 'th_aduana', 'folio_oc');
$has_oc_erp     = column_exists($pdo, 'th_aduana', 'oc_erp');
$has_ped_ref    = column_exists($pdo, 'th_aduana', 'pedimento_ref');
$has_fecha_eta  = column_exists($pdo, 'th_aduana', 'fecha_eta'); // NUEVO: ETA (planeación)
$has_fecha_lleg = column_exists($pdo, 'th_aduana', 'fech_llegPed'); // fecha estimada/real recepción (si aplica)

// Proyectos (catálogo)
$proyectos = [];
try {
    $proyectos = $pdo->query("SELECT Cve_Proyecto, Des_Proyecto FROM c_proyecto ORDER BY Des_Proyecto")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $proyectos = []; }

} catch(Throwable $e){ $proveedores = []; }

try {
    $almacenes = $pdo->query("SELECT clave, nombre FROM c_almacenp ORDER BY clave")->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e){ $almacenes = []; }

try {
    // IMPORTANTE: antes estaba LIMIT 8000 y eso “ocultaba” productos.
    // Si c_articulo es enorme, lo correcto es migrarlo a un autocomplete por API.
    // Para el “OC referente” lo dejamos completo (puede tardar si hay muchísimos).
    $productos = $pdo->query("
        SELECT a.cve_articulo, a.des_articulo, a.unidadMedida
        FROM c_articulo a
        WHERE (a.Activo=1 OR a.Activo='1' OR a.Activo='S' OR a.Activo IS NULL)
        ORDER BY a.cve_articulo
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($productos as $p) {
        $cve = trim((string)$p['cve_articulo']);
        if ($cve === '') continue;
        $mapProd[$cve] = [
            'des' => (string)($p['des_articulo'] ?? ''),
            'um'  => (string)($p['unidadMedida'] ?? ''),
        ];
    }
} catch(Throwable $e){
    $productos = [];
    $mapProd = [];
}

/** =================== Load Encabezado/Detalle =================== */
$encabezado = [
    'ID_Aduana'      => $idAduana,
    'ID_Proveedor'   => 0,
    'Cve_Almac'      => '',
    'Pedimento'      => '',   // folio negocio (folio_oc)
    'Id_moneda'      => 1,    // 1 MXN, 2 USD
    'Tipo_Cambio'    => 1,
    'fech_pedimento' => date('Y-m-d'),
    'fecha_eta'      => null, // planeación compras
    'Proyecto'       => '',
    'oc_erp'         => '',
    'pedimento_ref'  => '',
    'tipo_oc'        => 'OCN', // OCN / OCI
    'ID_Protocolo'   => 'OCN', // compatibilidad legacy
    'num_pedimento'  => null,
    'Consec_protocolo'=> null,
];

$detalle = [];

if ($esEdicion) {
    $h = $pdo->prepare("SELECT * FROM th_aduana WHERE ID_Aduana=?");
    $h->execute([$idAduana]);
    $rowH = $h->fetch(PDO::FETCH_ASSOC);
    if ($rowH) {
        $encabezado = array_merge($encabezado, $rowH);
        if (empty($encabezado['tipo_oc']) && !empty($encabezado['ID_Protocolo'])) {
            $encabezado['tipo_oc'] = $encabezado['ID_Protocolo'];
        }
    }

    $d = $pdo->prepare("
        SELECT d.*, a.des_articulo, a.unidadMedida AS des_umed
        FROM td_aduana d
        LEFT JOIN c_articulo a ON a.cve_articulo = d.cve_articulo
        WHERE d.ID_Aduana=?
        ORDER BY d.Id_DetAduana
    ");
    $d->execute([$idAduana]);
    $detalle = $d->fetchAll(PDO::FETCH_ASSOC);
}

/** =================== POST Guardar =================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // === Generación de folio SOLO al guardar (POST) ===
    if (empty($_POST['folio_oc'])) {
        $folioOC = generarFolioOC($pdo, $_POST['tipo_oc'], ($_POST['fech_pedimento'] ?? date('Y-m-d')));
    } else {
        $folioOC = $_POST['folio_oc'];
    }



    // Rehidrata encabezado desde POST SIEMPRE
    $encabezado['ID_Proveedor']   = (int)($_POST['ID_Proveedor'] ?? 0);
    $encabezado['ProveedorNombre']= trim((string)($_POST['ProveedorNombre'] ?? '')); // blindaje
    $encabezado['Cve_Almac']      = upper($_POST['Cve_Almac'] ?? '');
        $encabezado['Pedimento']      = upper($_POST['Pedimento'] ?? '');    // puede ir vacío (autofolio)
    $encabezado['Id_moneda']      = (int)($_POST['Id_moneda'] ?? 1);
    $encabezado['Tipo_Cambio']    = (float)($_POST['Tipo_Cambio'] ?? 1);
    $encabezado['fech_pedimento'] = trim((string)($_POST['fech_pedimento'] ?? date('Y-m-d')));
    $encabezado['Proyecto']       = upper($_POST['Proyecto'] ?? '');
    $encabezado['fecha_eta']     = trim((string)($_POST['fecha_eta'] ?? ''));
    $encabezado['oc_erp']         = upper($_POST['oc_erp'] ?? '');
    $encabezado['pedimento_ref']  = upper($_POST['pedimento_ref'] ?? '');
    $encabezado['tipo_oc']        = upper($_POST['tipo_oc'] ?? 'OCN');

    // ===== Regla: OCN siempre MXN y TC=1 =====
    if ($encabezado['tipo_oc'] === 'OCN') {
        $encabezado['Id_moneda'] = 1;
        $encabezado['Tipo_Cambio'] = 1;
        $encabezado['pedimento_ref'] = '';
    }

    // Rehidrata detalle
    $postCve  = $_POST['cve_articulo'] ?? [];
    $postCant = $_POST['cantidad'] ?? [];
    $postCost = $_POST['costo'] ?? [];
    $postIVA  = $_POST['iva'] ?? [];
    $postLote = $_POST['Cve_Lote'] ?? [];
    $postCad  = $_POST['caducidad'] ?? [];

    $detalle = [];
    $n = max(count($postCve), count($postCant));
    for ($i=0; $i<$n; $i++) {
        $cve   = upper($postCve[$i] ?? '');
        $cant  = (float)($postCant[$i] ?? 0);
        $costo = (float)($postCost[$i] ?? 0);
        $iva   = (float)($postIVA[$i] ?? 16);
        $lote  = upper($postLote[$i] ?? '');
        $cad   = trim((string)($postCad[$i] ?? ''));

        if ($cve === '' && $cant == 0 && $costo == 0) continue;

        $detalle[] = [
            'cve_articulo' => $cve,
            'cantidad'     => $cant,
            'costo'        => $costo,
            'IVA'          => $iva,
            'Cve_Lote'     => $lote,
            'caducidad'    => $cad,
            'des_articulo' => $mapProd[$cve]['des'] ?? '',
            'des_umed'     => $mapProd[$cve]['um'] ?? '',
        ];
    }

    // Blindaje proveedor por nombre (si llega 0)
    if ($encabezado['ID_Proveedor'] <= 0 && $encabezado['ProveedorNombre'] !== '') {
        $stp = $pdo->prepare("SELECT ID_Proveedor FROM c_proveedores WHERE Nombre = ? LIMIT 1");
        $stp->execute([$encabezado['ProveedorNombre']]);
        $idProv = (int)$stp->fetchColumn();
        if ($idProv > 0) $encabezado['ID_Proveedor'] = $idProv;
    }

    // ===== Validación (protocolo ahora es OPCIONAL) =====
    if ($encabezado['ID_Proveedor'] <= 0) {
        $mensajeError = 'Proveedor es obligatorio.';
    } elseif ($encabezado['Cve_Almac'] === '') {
        $mensajeError = 'Almacén es obligatorio.';
    } elseif ($encabezado['tipo_oc'] === '') {
        $mensajeError = 'Tipo OC es obligatorio.';
    } elseif ($encabezado['tipo_oc'] === 'OCN' && ((int)$encabezado['Id_moneda'] !== 1 || (float)$encabezado['Tipo_Cambio'] != 1.0)) {
        $mensajeError = 'OCN debe ser MXN y Tipo de Cambio = 1.';
    } elseif (!$detalle) {
        $mensajeError = 'Debe capturar al menos 1 partida.';
    } else {
        try {
            $pdo->beginTransaction();

            // ===================== FOLIO OC AUTOMÁTICO (NUEVA REGLA) =====================
            // Formato: OCNYYYYMMDD-001 / OCIYYYYMMDD-001
            // Normalizado a MAYÚSCULAS.
            if (!$esEdicion) {

                // Si el usuario NO puso folio, lo generamos.
                if ($encabezado['Pedimento'] === '') {
                    $encabezado['Pedimento'] = generarFolioOC($pdo, $encabezado['tipo_oc'], ($encabezado['fech_pedimento'] ?? date('Y-m-d')));
                } else {
                    // Normaliza y valida formato mínimo (opcional)
                    $encabezado['Pedimento'] = upper($encabezado['Pedimento']);
                }

                // num_pedimento se captura en Recepción de Mercancía, aquí se deja NULL
                $encabezado['num_pedimento'] = null;
// folio_oc y consecutivo por protocolo (dependiente de tipo_oc)
                $encabezado['folio_oc'] = $encabezado['Pedimento'];
                $encabezado['Consec_protocolo'] = null;
                if (preg_match('/-(\d{1,6})$/', $encabezado['Pedimento'], $mm)) {
                    $encabezado['Consec_protocolo'] = (int)$mm[1];
                }

            }

            // ===================== INSERT / UPDATE =====================
            if (!$esEdicion) {

                // Status abierto por diseño (tu decisión): 'A'
                $insH = (
                function() use ($pdo, $has_fecha_eta){
                    $cols = "num_pedimento, fech_pedimento, Pedimento, status, ID_Proveedor, ID_Protocolo, folio_oc, Consec_protocolo, Cve_Almac, Activo, Proyecto, Tipo_Cambio, Id_moneda, oc_erp, pedimento_ref";
                    $vals = ":num_ped, :fech, :ped, 'A', :prov, :prot, :folio_oc, :consec, :alm, 1, :proy, :tc, :mon, :oc_erp, :ped_ref";
                    if($has_fecha_eta){
                        $cols .= ", fecha_eta";
                        $vals .= ", :fecha_eta";
                    }
                    $sql = "INSERT INTO th_aduana ($cols) VALUES ($vals)";
                    return $pdo->prepare($sql);
                }
            )();

                $paramsH = [
                    ':num_ped'  => $encabezado['num_pedimento'] === null ? null : (int)$encabezado['num_pedimento'],
                    ':fech'     => $encabezado['fech_pedimento'],
                    ':ped'      => $encabezado['Pedimento'],
                    ':prov'     => $encabezado['ID_Proveedor'],
                    ':prot'     => $encabezado['ID_Protocolo'],
                    ':folio_oc' => $encabezado['folio_oc'],
                    ':consec'   => $encabezado['Consec_protocolo'],
                    ':alm'      => $encabezado['Cve_Almac'],
                    ':proy'     => $encabezado['Proyecto'] ?: null,
                    ':tc'       => $encabezado['Tipo_Cambio'],
                    ':mon'      => $encabezado['Id_moneda'],
                    ':oc_erp'   => $encabezado['oc_erp'] ?: null,
                    ':ped_ref'  => $encabezado['pedimento_ref'] ?: null,
                ];
                if($has_fecha_eta){
                    $paramsH[':fecha_eta'] = ($encabezado['fecha_eta'] ?? '') ?: null;
                }
                $insH->execute($paramsH);
$idAduana  = (int)$pdo->lastInsertId();
                $esEdicion = true;
                $encabezado['ID_Aduana'] = $idAduana;

            } else {
                // En edición NO permitimos cambiar moneda/TC en este flujo (por consistencia).
                $updH = $pdo->prepare("
    UPDATE th_aduana
    SET
        fech_pedimento=:fech,
        Pedimento=:ped,
        status='A',
        ID_Proveedor=:prov,
        Cve_Almac=:alm,
        Activo=1,
        Proyecto=:proy,
        Tipo_Cambio=:tc,
        Id_moneda=:mon,
        ID_Protocolo=:prot,
        folio_oc=:folio_oc,
        Consec_protocolo=:consec,
        oc_erp=:oc_erp,
        pedimento_ref=:ped_ref
    WHERE ID_Aduana=:id
");
$updH->execute([
    ':fech'   => $encabezado['fech_pedimento'],
    ':ped'    => $encabezado['Pedimento'],
    ':prov'   => $encabezado['ID_Proveedor'],
    ':alm'    => $encabezado['Cve_Almac'],
    ':proy'   => $encabezado['Proyecto'],
    ':tc'     => $encabezado['Tipo_Cambio'],
    ':mon'    => $encabezado['Id_moneda'],
    ':prot'   => $encabezado['tipo_oc'],
    ':folio_oc'=> ($encabezado['Pedimento'] ?? null),
    ':consec' => (preg_match('/-(\d{1,6})$/', (string)$encabezado['Pedimento'], $mm2) ? (int)$mm2[1] : null),
    ':oc_erp' => ($encabezado['oc_erp']===''? null : $encabezado['oc_erp']),
    ':ped_ref'=> ($encabezado['pedimento_ref']===''? null : $encabezado['pedimento_ref']),
    ':id'     => $idAduana
]);
$pdo->prepare("DELETE FROM td_aduana WHERE ID_Aduana=?")->execute([$idAduana]);
            }

            $insD = $pdo->prepare("
                INSERT INTO td_aduana
                (ID_Aduana, cve_articulo, cantidad, Cve_Lote, caducidad, costo, IVA, Id_UniMed, Ref_Docto, Ingresado, num_orden)
                VALUES
                (:id, :art, :cant, :lote, :cad, :costo, :iva, :idum, :ref, 0, :orden)
            ");

            $ordenRow = 1;

            foreach ($detalle as $r) {
                $cve = upper($r['cve_articulo'] ?? '');
                if ($cve === '') continue;

                $insD->execute([
                    ':id'    => (int)$encabezado['ID_Aduana'],
                    ':art'   => $cve,
                    ':cant'  => (float)($r['cantidad'] ?? 0),
                    ':lote'  => upper($r['Cve_Lote'] ?? ''),
                    ':cad'   => (!empty($r['caducidad']) ? ($r['caducidad'].' 00:00:00') : null),
                    ':costo' => (float)(($r['costo'] ?? 0) * (1 + (((float)($r['IVA'] ?? 0))/100))),
                    ':iva'   => (float)($r['IVA'] ?? 16),
                    ':idum'  => null,
                    ':ref'   => (string)($encabezado['Pedimento'] ?? ''),
                    ':orden' => $ordenRow, // partida secuencial
                ]);

                $ordenRow++;
            }

            $pdo->commit();
            header("Location: orden_compra_edit.php?id_aduana=".(int)$encabezado['ID_Aduana']."&ok=1");
            exit;

        } catch(Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();

            $msg = $ex->getMessage();
            if (strpos($msg, 'SQLSTATE[23000]') !== false && strpos($msg, '1062') !== false) {
                $mensajeError = "Conflicto de folio: el consecutivo ya fue tomado por otra OC. Intenta guardar de nuevo (se recalcula automático).";
            } else {
                $mensajeError = $msg;
            }
        }
    }
}

require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
.ap-card { border:1px solid #d0d7e2; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.05); }
.ap-card-header { background:#0F5AAD; color:#fff; padding:10px 14px; display:flex; justify-content:space-between; align-items:center; }
.ap-title { font-size:14px; margin:0; font-weight:800; }
.ap-subtitle { font-size:11px; margin:0; opacity:.85; }
.ap-card-body { padding:10px 14px; }
.ap-label { font-size:11px; font-weight:700; margin-bottom:2px; color:#555; }
.form-control, .form-select { font-size:11px; height:28px; padding:3px 6px; }
.btn-ap-primary { background:#0F5AAD; border-color:#0F5AAD; color:#fff; border-radius:999px; font-size:11px; padding:4px 14px; }
.btn-ap-primary:hover { background:#0c4a8d; border-color:#0c4a8d; }
.table-partidas thead th { background:#f1f5f9; font-size:10px; font-weight:800; }
.table-partidas tbody td { font-size:10px; padding:2px 4px; vertical-align:middle; }
.btn-row { font-size:10px; padding:2px 6px; }
.ap-help { font-size:10px; color:#6b7280; }
</style>

<div class="container-fluid py-2">
  <div class="ap-card mb-2">
    <div class="ap-card-header">
      <div>
        <div class="ap-title"><?php echo $esEdicion ? 'Editar Orden de Compra' : 'Nueva Orden de Compra'; ?></div>
        <div class="ap-subtitle">th_aduana / td_aduana — OCN / OCI con partidas detalladas.</div>
      </div>
      <div><a href="orden_compra.php" class="btn btn-outline-light btn-sm">Regresar</a></div>
    </div>
  </div>

  <div class="ap-card">
    <div class="ap-card-body">

      <?php if ($ok === 1): ?>
        <div class="alert alert-success py-1" style="font-size:11px;">OC guardada correctamente.</div>
      <?php endif; ?>

      <?php if ($mensajeError !== ''): ?>
        <div class="alert alert-danger py-1" style="font-size:11px;">
          Error al guardar la OC: <?php echo e($mensajeError); ?>
        </div>
      <?php endif; ?>

      <form method="post" id="formOc" autocomplete="off">
        <input type="hidden" name="id_aduana" value="<?php echo (int)($encabezado['ID_Aduana'] ?? 0); ?>">

        <div class="row g-2 mb-2">
          <div class="col-md-3">
            <label class="ap-label">Proveedor *</label>
            <?php $idProvSel = (int)($encabezado['ID_Proveedor'] ?? 0); ?>
            <select name="ID_Proveedor" id="ID_Proveedor" class="form-select" <?php echo $esEdicion ? 'disabled' : ''; ?>>
              <option value="0">Seleccione...</option>
              <?php foreach ($proveedores as $p): ?>
                <option value="<?php echo (int)$p['ID_Proveedor']; ?>" <?php echo ($idProvSel === (int)$p['ID_Proveedor']) ? 'selected' : ''; ?>>
                  <?php echo e((string)$p['Nombre']); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <input type="hidden" name="ProveedorNombre" id="ProveedorNombre" value="">
            <?php if ($esEdicion): ?><input type="hidden" name="ID_Proveedor" value="<?php echo (int)$idProvSel; ?>"><?php endif; ?>
          </div>

          <div class="col-md-2">
            <label class="ap-label">Almacén *</label>
            
<?php
// ================= Almacén =================
// Valor seleccionado SOLO si es edición
$almSel = '';
if ($esEdicion && !empty($encabezado['Cve_Almac'])) {
    $almSel = (string)$encabezado['Cve_Almac'];
}
?>

<select name="Cve_Almac" class="form-select" <?php echo $esEdicion ? 'disabled' : ''; ?>>
    <option value="" selected>Seleccione...</option>

    <?php foreach ($almacenes as $a):
        $cve = (string)$a['clave'];
        $nom = (string)$a['nombre'];
        $sel = ($almSel !== '' && $almSel === $cve) ? 'selected' : '';
    ?>
        <option value="<?php echo e($cve); ?>" <?php echo $sel; ?>>
            <?php echo e($cve . ' - ' . $nom); ?>
        </option>
    <?php endforeach; ?>
</select>

            <?php if ($esEdicion): ?>"><?php endif; ?>
          </div>

          <div class="col-md-2">
            <label class="ap-label">Tipo OC</label>
            <?php $tipoSel=(string)($encabezado['tipo_oc'] ?? 'OCN'); ?>
            <select name="tipo_oc" id="tipo_oc" class="form-select" <?php echo $esEdicion?'disabled':''; ?>>
              <option value="OCN" <?php echo ($tipoSel==='OCN')?'selected':''; ?>>OCN</option>
              <option value="OCI" <?php echo ($tipoSel==='OCI')?'selected':''; ?>>OCI</option>
            </select>
            <?php if ($esEdicion): ?><input type="hidden" name="tipo_oc" value="<?php echo e($tipoSel); ?>"><?php endif; ?>
          </div>
          <div class="col-md-2">
            <label class="ap-label">Folio OC</label>
            <input type="text" name="Pedimento" id="Pedimento" class="form-control"
              placeholder="<?php echo $esEdicion ? '' : 'AUTO (OCNYYYYMMDD-001)'; ?>"
              value="<?php echo e((string)($encabezado['Pedimento'] ?? '')); ?>"
              <?php echo $esEdicion?'readonly':''; ?>>
            <div class="ap-help">Si lo dejas vacío, se genera automático: OCNYYYYMMDD-001.</div>
          </div>

          <div class="col-md-1">

            <label class="ap-label">Moneda</label>
            <?php $monSel=(int)($encabezado['Id_moneda'] ?? 1); ?>
            <select name="Id_moneda" id="Id_moneda" class="form-select" <?php echo $esEdicion?'disabled':''; ?>>
              <option value="1" <?php echo ($monSel===1)?'selected':''; ?>>MXN</option>
              <option value="2" <?php echo ($monSel===2)?'selected':''; ?>>USD</option>
            </select>
            <?php if ($esEdicion): ?><input type="hidden" name="Id_moneda" value="<?php echo (int)$monSel; ?>"><?php endif; ?>
          </div>
        </div>

        <div class="row g-2 mb-2">
          <div class="col-md-4">
            <label class="ap-label">OC ERP (opcional)</label>
            <input type="text" name="oc_erp" class="form-control" maxlength="60"
              value="<?php echo e((string)($encabezado['oc_erp'] ?? '')); ?>"
              placeholder="Ej: PO-12345 / OCN-ERP-0001">
            <div class="ap-help">Número de OC en ERP/cliente (alfanumérico).</div>
          </div>

          <div class="col-md-6">
            <label class="ap-label">Referencia / Factura (opcional)</label>
            <input type="text" name="pedimento_ref" class="form-control" maxlength="120"
              value="<?php echo e((string)($encabezado['pedimento_ref'] ?? '')); ?>"
              placeholder="Ej: FACT-8899 | BL-001 | CONSOL-ABC">
            <div class="ap-help">Texto libre para agrupar y rastrear.</div>
          </div>
        </div>

        <div class="col-md-4" id="box_num_pedimento" style="display:none;">
          <label class="form-label">Num. Pedimento</label>
          <input type="number" name="num_pedimento" id="num_pedimento" class="form-control"
            value="<?php echo e((string)($encabezado['num_pedimento'] ?? '')); ?>"
            placeholder="Captura pedimento (solo OCI)">
          <div class="ap-help">Visible únicamente para OCI (internacional).</div>
        </div>

        
        <div class="row g-2 mb-3">
          <div class="col-md-3">
            <label class="form-label">Tipo cambio</label>
            <input type="number" step="0.0001" name="Tipo_Cambio" class="form-control"
              value="<?php echo e((string)($encabezado['Tipo_Cambio'] ?? 1)); ?>">
            <div class="ap-help">OCN fuerza 1.0</div>
          </div>

          <div class="col-md-3">
            <label class="form-label">Fecha OC</label>
            <input type="date" name="fech_pedimento" class="form-control"
              value="<?php echo e(date('Y-m-d', strtotime($encabezado['fech_pedimento'] ?? date('Y-m-d')))); ?>">
          </div>

          <div class="col-md-3">
            <label class="form-label">Fecha ETA (estimada)</label>
            <input type="date" name="fecha_eta" id="fecha_eta" class="form-control"
              value="<?php echo e(($encabezado['fecha_eta'] ?? '') ? date('Y-m-d', strtotime($encabezado['fecha_eta'])) : ''); ?>"
              placeholder="ETA para planeación">
            <div class="ap-help">Planeación compras / indicador de cumplimiento.</div>
          </div>

          <div class="col-md-3">
            <label class="form-label">Proyecto</label>
            <?php if (!empty($proyectos)) { ?>
              <select name="Proyecto" class="form-select">
                <option value="">— Selecciona —</option>
                <?php foreach($proyectos as $p){ 
                    $val = (string)$p['Cve_Proyecto'];
                    $txt = (string)$p['Des_Proyecto'];
                    $sel = ((string)($encabezado['Proyecto'] ?? '') === $val) ? 'selected' : '';
                ?>
                  <option value="<?php echo e($val); ?>" <?php echo $sel; ?>>
                    <?php echo e($txt . ' (' . $val . ')'); ?>
                  </option>
                <?php } ?>
              </select>
            <?php } else { ?>
              <input type="text" name="Proyecto" class="form-control"
                value="<?php echo e((string)($encabezado['Proyecto'] ?? '')); ?>"
                placeholder="Ej. FC-Q1-2026">
            <?php } ?>
            <div class="ap-help">Sugerencia: usa un código corto (ej. 'FC-Q1-2026').</div>
          </div>
        </div>


        <hr class="my-2">

        <div class="d-flex justify-content-between align-items-center mb-1">
          <div class="ap-label mb-0">Partidas</div>
          <button type="button" class="btn btn-sm btn-ap-primary" id="btnAddRow">Agregar partida</button>
        </div>

        <datalist id="dlProductos">
          <?php foreach ($productos as $p):
            $cve = trim((string)$p['cve_articulo']); if ($cve==='') continue;
            $des = (string)($p['des_articulo'] ?? '');
          ?>
            <option value="<?php echo e($cve); ?>"><?php echo e($des); ?></option>
            <option value="<?php echo e($des); ?>"><?php echo e($cve); ?></option>
          <?php endforeach; ?>
        </datalist>

        <div class="table-responsive" style="max-height:420px;overflow-y:auto;">
          <table class="table table-bordered table-hover table-partidas" id="tablaPartidas">
            <thead>
              <tr>
                <th style="width:40px;"></th>
                <th style="width:220px;">Artículo (clave o descripción)</th>
                <th>Descripción</th>
                <th style="width:90px;">UOM</th>
                <th style="width:90px;" class="text-end">Cantidad</th>
                <th style="width:140px;" class="text-end">Costo Unit (sin IVA)</th>
                <th style="width:70px;" class="text-end">IVA %</th>
                <th style="width:120px;" class="text-end">Total</th>
                <th style="width:120px;">Lote</th>
                <th style="width:140px;">Caducidad</th>
              </tr>
            </thead>
            
            <tbody>
              <?php if (!$detalle): ?>
                <tr>
                  <td class="text-center"><button type="button" class="btn btn-outline-danger btn-xs btn-row btn-del">&times;</button></td>
                  <td><input list="dlProductos" type="text" name="cve_articulo[]" class="form-control inp-art" placeholder="Clave o descripción"></td>
                  <td><input type="text" class="form-control inp-des" value="" disabled></td>
                  <td><input type="text" class="form-control inp-um" value="" disabled></td>
                  <td><input type="number" step="0.0001" name="cantidad[]" class="form-control cantidad text-end" value=""></td>
                  <td><input type="number" step="0.0001" name="costo[]" class="form-control costo text-end" value=""></td>
                  <td><input type="number" step="0.01" name="iva[]" class="form-control iva text-end" value="16"></td>
                  <td><input type="text" class="form-control total text-end" value="0.00" readonly></td>
                  <td><input type="text" name="Cve_Lote[]" class="form-control" value=""></td>
                  <td><input type="date" name="caducidad[]" class="form-control" value=""></td>
                </tr>
              <?php else: ?>
                <?php foreach ($detalle as $row):
                  $cadIso = !empty($row['caducidad']) ? substr((string)$row['caducidad'],0,10) : '';
                ?>
                  <tr>
                    <td class="text-center"><button type="button" class="btn btn-outline-danger btn-xs btn-row btn-del">&times;</button></td>
                    <td><input list="dlProductos" type="text" name="cve_articulo[]" class="form-control inp-art" value="<?php echo e((string)$row['cve_articulo']); ?>"></td>
                    <td><input type="text" class="form-control inp-des" value="<?php echo e((string)($row['des_articulo'] ?? '')); ?>" disabled></td>
                    <td><input type="text" class="form-control inp-um" value="<?php echo e((string)($row['des_umed'] ?? '')); ?>" disabled></td>
                    <td><input type="number" step="0.0001" name="cantidad[]" class="form-control cantidad text-end" value="<?php echo e((string)($row['cantidad'] ?? '')); ?>"></td>
                    <td><input type="number" step="0.0001" name="costo[]" class="form-control costo text-end" value="<?php echo e((string)($row['costo'] ?? '')); ?>"></td>
                    <td><input type="number" step="0.01" name="iva[]" class="form-control iva text-end" value="<?php echo e((string)($row['IVA'] ?? '16')); ?>"></td>
                    <td><input type="text" class="form-control total text-end" value="0.00" readonly></td>
                    <td><input type="text" name="Cve_Lote[]" class="form-control" value="<?php echo e((string)($row['Cve_Lote'] ?? '')); ?>"></td>
                    <td><input type="date" name="caducidad[]" class="form-control" value="<?php echo e($cadIso); ?>"></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>

          </table>
        </div>

        <div class="d-flex justify-content-end mt-2">
          <div class="input-group" style="max-width:320px;">
            <span class="input-group-text" style="font-size:11px;"><strong>Total general</strong></span>
            <input type="text" id="total_general" class="form-control text-end" value="0.00" readonly>
          </div>
        </div>

        <div class="mt-3 d-flex justify-content-between">
          <div class="text-muted" style="font-size:10px;">
            Captura el <b>costo unitario sin IVA</b>. El <b>Total</b> se calcula: Cantidad × Costo × (1 + IVA%/100).<br>
            (Si no manejas costos, déjalo en 0.0000; los PDFs operativos pueden ocultarlo.)
          </div>
          <div><button type="submit" class="btn btn-ap-primary">Guardar OC</button></div>
        </div>

      </form>
    </div>
  </div>
</div>

<script>
const MAP_PROD = <?php echo json_encode($mapProd, JSON_UNESCAPED_UNICODE); ?>;

function norm(s){ return (s||'').toString().trim(); }
function up(s){ return (s||'').toString().trim().toUpperCase(); }

function tryResolveProduct(v){
  v = norm(v);
  if (!v) return {cve:'', des:'', um:''};

  const vv = up(v);
  if (MAP_PROD[vv]) return {cve:vv, des:MAP_PROD[vv].des||'', um:MAP_PROD[vv].um||''};

  // match por descripción exacta (case-insensitive)
  const keys = Object.keys(MAP_PROD);
  const vlow = v.toLowerCase();
  for (let i=0;i<keys.length;i++){
    const k=keys[i], des=(MAP_PROD[k].des||'').toString();
    if (des.toLowerCase() === vlow) return {cve:k, des:MAP_PROD[k].des||'', um:MAP_PROD[k].um||''};
  }
  return {cve:vv, des:'', um:''};
}

function refreshRow(row){
  const inpArt = row.querySelector('.inp-art');
  const inpDes = row.querySelector('.inp-des');
  const inpUm  = row.querySelector('.inp-um');
  const r = tryResolveProduct(inpArt.value);
  if (r.cve) inpArt.value = r.cve;
  if (inpDes) inpDes.value = r.des || '';
  if (inpUm)  inpUm.value  = r.um  || '';
}

document.addEventListener('input', (ev)=>{
  const el = ev.target;
  if (el && el.classList.contains('inp-art')) {
    const tr = el.closest('tr');
    if (tr) refreshRow(tr);
  }
});

document.getElementById('btnAddRow')?.addEventListener('click', ()=>{
  const tb = document.querySelector('#tablaPartidas tbody');
  if (!tb) return;
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td class="text-center"><button type="button" class="btn btn-outline-danger btn-xs btn-row btn-del">&times;</button></td>
    <td><input list="dlProductos" type="text" name="cve_articulo[]" class="form-control inp-art" placeholder="Clave o descripción"></td>
    <td><input type="text" class="form-control inp-des" value="" disabled></td>
    <td><input type="text" class="form-control inp-um" value="" disabled></td>
    <td><input type="number" step="0.0001" name="cantidad[]" class="form-control cantidad text-end"></td>
    <td><input type="number" step="0.0001" name="costo[]" class="form-control costo text-end"></td>
    <td><input type="number" step="0.01" name="iva[]" class="form-control iva text-end" value="16"></td>
            <td><input type="text" class="form-control total text-end" value="0.00" readonly></td>
            <td><input type="text" name="Cve_Lote[]" class="form-control"></td>
    <td><input type="date" name="caducidad[]" class="form-control"></td>
  `;
  tb.appendChild(tr);
});

document.addEventListener('click', (ev)=>{
  const btn = ev.target;
  if (btn && btn.classList.contains('btn-del')) {
    const tr = btn.closest('tr');
    const tb = tr?.parentElement;
    tr?.remove();
    // si quedó sin filas, agrega una
    if (tb && tb.querySelectorAll('tr').length === 0) {
      document.getElementById('btnAddRow')?.click();
    }
  }
});

// Regla UI: si tipo_oc = OCN => moneda MXN + TC=1 (bloqueado)
function enforceOCN(){
  const tipo = document.getElementById('tipo_oc')?.value || 'OCN';
  const mon = document.getElementById('Id_moneda');
  const tc  = document.getElementById('Tipo_Cambio');

  if (!mon || !tc) return;

  if (tipo === 'OCN') {
    // Nacional: MXN fijo, TC=1
    mon.value = '1';
    mon.setAttribute('disabled','disabled');
    tc.value = '1';
    tc.setAttribute('readonly','readonly');
  } else if (tipo === 'OCI') {
    // Importación: USD fijo, TC editable
    mon.value = '2';
    mon.setAttribute('disabled','disabled');
    tc.removeAttribute('readonly');
    if (!tc.value || parseFloat(tc.value) === 1) tc.value = '';
  } else {
    mon.removeAttribute('disabled');
    tc.removeAttribute('readonly');
  }

  // Normaliza folio a mayúsculas
  const ped = document.getElementById('Pedimento');
  if (ped && ped.value) ped.value = ped.value.toUpperCase();

  // Regla USD => IVA=0 en capturas
  const isUSD = (mon.value === '2');
  document.querySelectorAll('#tablaPartidas tbody tr').forEach(tr=>{
    const ivaInp = tr.querySelector('input.iva');
    if (!ivaInp) return;
    if (isUSD) {
      ivaInp.value = '0';
      ivaInp.setAttribute('readonly','readonly');
    } else {
      if (ivaInp.value === '' || ivaInp.value === '0') ivaInp.value = '16';
      ivaInp.removeAttribute('readonly');
    }
  });
}
document.getElementById('tipo_oc')?.addEventListener('change', enforceOCN);
enforceOCN();

// Normaliza a mayúsculas en submit (folio, proyecto, lotes ya se normalizan server-side)
document.getElementById('formOc')?.addEventListener('submit', ()=>{
  const ped = document.getElementById('Pedimento');
  if (ped && ped.value) ped.value = ped.value.toUpperCase();
});


// ===================== Costeo por API (fuente única de verdad) =====================
const COSTEO_API_URL = '/assistpro_kardex_fc/public/api/costeo/api_costeo_calcular.php';

let _costeoTimer = null;
function scheduleCosteo(){
  if (_costeoTimer) clearTimeout(_costeoTimer);
  _costeoTimer = setTimeout(runCosteo, 150);
}

async function runCosteo(){
  const monVal = document.getElementById('Id_moneda')?.value || '1';
  const moneda = (String(monVal) === '2') ? 'USD' : 'MXN';

  let totalGeneral = 0;

  const rows = document.querySelectorAll('#tablaPartidas tbody tr');
  for (const tr of rows){
    const qtyEl  = tr.querySelector('input.cantidad');
    const costEl = tr.querySelector('input.costo');
    const ivaEl  = tr.querySelector('input.iva');
    const outEl  = tr.querySelector('input.total');

    if(!qtyEl || !costEl || !outEl) continue;

    const cantidad = parseFloat(qtyEl.value || '0') || 0;
    const costo_unitario = parseFloat(costEl.value || '0') || 0;
    const iva_pct = (moneda === 'USD') ? 0 : (parseFloat(ivaEl?.value || '16') || 16);

    try{
      const resp = await fetch(COSTEO_API_URL, {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ moneda, iva_pct, cantidad, costo_unitario })
      });
      const data = await resp.json();

      let total = 0;
      if (data && typeof data.total !== 'undefined') total = parseFloat(data.total) || 0;
      else if (data?.summary?.total) total = parseFloat(data.summary.total) || 0;
      else if (Array.isArray(data?.lines) && data.lines[0]?.total) total = parseFloat(data.lines[0].total) || 0;

      outEl.value = (isFinite(total) ? total : 0).toFixed(2);
      totalGeneral += (isFinite(total) ? total : 0);

      if (moneda === 'USD' && ivaEl) ivaEl.value = '0';
    }catch(e){
      outEl.value = '0.00';
    }
  }

  const tg = document.getElementById('total_general');
  if (tg) tg.value = totalGeneral.toFixed(2);
}

// Delegación de eventos para N filas dinámicas
document.addEventListener('input', (ev)=>{
  const t = ev.target;
  if(!t) return;
  if (t.classList.contains('cantidad') || t.classList.contains('costo') || t.classList.contains('iva')){
    scheduleCosteo();
  }
});
document.getElementById('btnAddRow')?.addEventListener('click', ()=> setTimeout(runCosteo, 0));
document.addEventListener('click', (ev)=>{
  if (ev.target && ev.target.classList.contains('btn-del')) setTimeout(runCosteo, 0);
});

// Inicial
window.addEventListener('load', ()=>{ enforceOCN(); runCosteo(); });

</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';


?>


<script>
document.addEventListener("DOMContentLoaded", function () {
    const tipoOC = document.getElementById('tipo_oc');
    const moneda = document.getElementById('Id_moneda');
    const tipoCambio = document.querySelector('[name="Tipo_Cambio"]');

    if (!tipoOC || !moneda || !tipoCambio) return;

    function actualizarCampos() {
        const tipo = tipoOC.value;

        if (tipo === "OCN") {
            moneda.value = "1"; // MXN
            moneda.disabled = true;

            tipoCambio.value = "1";
            tipoCambio.readOnly = true;
        } else if (tipo === "OCI") {
            moneda.value = "2"; // USD
            moneda.disabled = true;

            tipoCambio.readOnly = false;
        } else {
            moneda.disabled = false;
            tipoCambio.readOnly = false;
        }
    }

    tipoOC.addEventListener("change", actualizarCampos);
    actualizarCampos();
});
</script>
