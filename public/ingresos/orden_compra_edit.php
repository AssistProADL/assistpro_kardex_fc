<?php
// public/ingresos/orden_compra_edit.php
require_once __DIR__ . '/../../app/db.php';

$pdo = db_pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$idAduana   = isset($_GET['id_aduana']) ? (int)$_GET['id_aduana'] : 0;
$esEdicion  = $idAduana > 0;
$mensajeError = '';
$ok = isset($_GET['ok']) ? (int)$_GET['ok'] : 0;

/** =================== Catálogos =================== */
$proveedores = [];
$almacenes   = [];
$protocolos  = [];
$productos   = [];
$mapProd     = [];

try {
    $proveedores = $pdo->query("
        SELECT ID_Proveedor, Nombre
        FROM c_proveedores
        WHERE (Activo=1 OR Activo='1' OR Activo='S' OR Activo IS NULL)
        ORDER BY Nombre
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e){ $proveedores = []; }

try {
    $almacenes = $pdo->query("SELECT clave, nombre FROM c_almacenp ORDER BY clave")->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e){ $almacenes = []; }

try {
    $protocolos = $pdo->query("SELECT ID_Protocolo, descripcion, FOLIO FROM t_protocolo WHERE Activo=1 ORDER BY ID_Protocolo")->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e){ $protocolos = []; }

try {
    $productos = $pdo->query("
        SELECT a.cve_articulo, a.des_articulo, a.unidadMedida
        FROM c_articulo a
        WHERE (a.Activo=1 OR a.Activo='1' OR a.Activo='S' OR a.Activo IS NULL)
        ORDER BY a.cve_articulo
        LIMIT 8000
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
    'ID_Protocolo'   => '',
    'Pedimento'      => '',
    'Id_moneda'      => 1,
    'Tipo_Cambio'    => 1,
    'fech_pedimento' => date('Y-m-d'),
    'Proyecto'       => '',
    'tipo_oc'        => 'OCN',
];

$detalle = [];

if ($esEdicion) {
    $h = $pdo->prepare("SELECT * FROM th_aduana WHERE ID_Aduana=?");
    $h->execute([$idAduana]);
    $rowH = $h->fetch(PDO::FETCH_ASSOC);
    if ($rowH) $encabezado = array_merge($encabezado, $rowH);

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

    // Rehidrata encabezado desde POST SIEMPRE
    $encabezado['ID_Proveedor']   = (int)($_POST['ID_Proveedor'] ?? 0);
    $encabezado['ProveedorNombre']= trim((string)($_POST['ProveedorNombre'] ?? '')); // blindaje
    $encabezado['Cve_Almac']      = trim((string)($_POST['Cve_Almac'] ?? ''));
    $encabezado['ID_Protocolo']   = trim((string)($_POST['ID_Protocolo'] ?? ''));
    $encabezado['Pedimento']      = trim((string)($_POST['Pedimento'] ?? '')); // puede ir vacío (autofolio)
    $encabezado['Id_moneda']      = (int)($_POST['Id_moneda'] ?? 1);
    $encabezado['Tipo_Cambio']    = (float)($_POST['Tipo_Cambio'] ?? 1);
    $encabezado['fech_pedimento'] = trim((string)($_POST['fech_pedimento'] ?? date('Y-m-d')));
    $encabezado['Proyecto']       = trim((string)($_POST['Proyecto'] ?? ''));
    $encabezado['tipo_oc']        = trim((string)($_POST['tipo_oc'] ?? 'OCN'));

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
        $cve   = trim((string)($postCve[$i] ?? ''));
        $cant  = (float)($postCant[$i] ?? 0);
        $costo = (float)($postCost[$i] ?? 0);
        $iva   = (float)($postIVA[$i] ?? 16);
        $lote  = trim((string)($postLote[$i] ?? ''));
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

    // ===== Validación (Folio OC NO bloquea en nueva) =====
    if ($encabezado['ID_Proveedor'] <= 0) {
        $mensajeError = 'Proveedor es obligatorio.';
    } elseif ($encabezado['Cve_Almac'] === '') {
        $mensajeError = 'Almacén es obligatorio.';
    } elseif (!$esEdicion && trim((string)$encabezado['ID_Protocolo']) === '' && trim((string)$encabezado['tipo_oc']) === '') {
        $mensajeError = 'Protocolo es obligatorio.';
    } elseif (!$detalle) {
        $mensajeError = 'Debe capturar al menos 1 partida.';
    } else {
        try {
            $pdo->beginTransaction();

            // ===================== FOLIO OC AUTOMÁTICO (ACORDADO) =====================
            // - Pedimento: por protocolo (OCN_000001)
            // - num_pedimento: GLOBAL (para respetar índice único en th_aduana.num_pedimento)
            if (!$esEdicion && trim((string)$encabezado['Pedimento']) === '') {

                // Si no mandan protocolo, usar tipo_oc como fallback
                if (trim((string)$encabezado['ID_Protocolo']) === '') {
                    $encabezado['ID_Protocolo'] = trim((string)($encabezado['tipo_oc'] ?? ''));
                }

                $prot = trim((string)$encabezado['ID_Protocolo']);
                if ($prot === '') {
                    throw new Exception('No se pudo generar Folio OC: falta Protocolo.');
                }

                // 1) Consecutivo por protocolo (negocio)
                $stF = $pdo->prepare("SELECT FOLIO FROM t_protocolo WHERE ID_Protocolo=? FOR UPDATE");
                $stF->execute([$prot]);
                $folioActual = (int)$stF->fetchColumn();

                $nuevoFolioProt = $folioActual + 1;

                $updF = $pdo->prepare("UPDATE t_protocolo SET FOLIO=? WHERE ID_Protocolo=?");
                $updF->execute([$nuevoFolioProt, $prot]);

                $encabezado['Pedimento'] = $prot . '_' . str_pad((string)$nuevoFolioProt, 6, '0', STR_PAD_LEFT);

                // 2) Consecutivo GLOBAL para num_pedimento (evita colisión OCN vs OCI)
                $stG = $pdo->query("SELECT COALESCE(MAX(num_pedimento),0) FROM th_aduana FOR UPDATE");
                $maxGlobal = (int)$stG->fetchColumn();
                $encabezado['num_pedimento'] = $maxGlobal + 1;
            }

            // ===================== INSERT / UPDATE =====================
            if (!$esEdicion) {
                $insH = $pdo->prepare("
                    INSERT INTO th_aduana
                    (num_pedimento, fech_pedimento, Pedimento, status, ID_Proveedor, ID_Protocolo, Cve_Almac, Activo, Proyecto, Tipo_Cambio, Id_moneda)
                    VALUES
                    (:num_ped, :fech, :ped, 'A', :prov, :prot, :alm, 1, :proy, :tc, :mon)
                ");

                // Si el usuario escribió Pedimento manualmente, también debemos asignar num_pedimento global
                if (!isset($encabezado['num_pedimento'])) {
                    $stG = $pdo->query("SELECT COALESCE(MAX(num_pedimento),0) FROM th_aduana FOR UPDATE");
                    $maxGlobal = (int)$stG->fetchColumn();
                    $encabezado['num_pedimento'] = $maxGlobal + 1;
                }

                $insH->execute([
                    ':num_ped' => (int)$encabezado['num_pedimento'],
                    ':fech'    => $encabezado['fech_pedimento'],
                    ':ped'     => $encabezado['Pedimento'],
                    ':prov'    => $encabezado['ID_Proveedor'],
                    ':prot'    => ($encabezado['ID_Protocolo'] === '' ? null : $encabezado['ID_Protocolo']),
                    ':alm'     => $encabezado['Cve_Almac'],
                    ':proy'    => $encabezado['Proyecto'],
                    ':tc'      => $encabezado['Tipo_Cambio'],
                    ':mon'     => $encabezado['Id_moneda'],
                ]);

                $idAduana  = (int)$pdo->lastInsertId();
                $esEdicion = true;
                $encabezado['ID_Aduana'] = $idAduana;

            } else {
                $updH = $pdo->prepare("
                    UPDATE th_aduana
                    SET Proyecto=:proy, Tipo_Cambio=:tc, Id_moneda=:mon
                    WHERE ID_Aduana=:id
                ");
                $updH->execute([
                    ':proy' => $encabezado['Proyecto'],
                    ':tc'   => $encabezado['Tipo_Cambio'],
                    ':mon'  => $encabezado['Id_moneda'],
                    ':id'   => $idAduana
                ]);

                $pdo->prepare("DELETE FROM td_aduana WHERE ID_Aduana=?")->execute([$idAduana]);
            }

            $insD = $pdo->prepare("
                INSERT INTO td_aduana
                (ID_Aduana, cve_articulo, cantidad, Cve_Lote, Ingresado, num_orden)
                VALUES
                (:id, :art, :cant, :lote, 0, :orden)
            ");

            $numOrden = $encabezado['Pedimento']; // folio negocio
            foreach ($detalle as $r) {
                $cve = trim((string)$r['cve_articulo']);
                if ($cve === '') continue;
                $insD->execute([
                    ':id'    => $encabezado['ID_Aduana'],
                    ':art'   => $cve,
                    ':cant'  => (float)$r['cantidad'],
                    ':lote'  => ($r['Cve_Lote'] ?? ''),
                    ':orden' => $numOrden,
                ]);
            }

            $pdo->commit();
            header("Location: orden_compra_edit.php?id_aduana=".(int)$encabezado['ID_Aduana']."&ok=1");
            exit;

        } catch(Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();

            $msg = $ex->getMessage();
            if (strpos($msg, 'SQLSTATE[23000]') !== false && strpos($msg, '1062') !== false) {
                $mensajeError = "Conflicto de folio interno: el consecutivo ya fue tomado por otra OC. Intenta guardar de nuevo (el sistema recalcula el folio).";
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
            <?php $almSel = (string)($encabezado['Cve_Almac'] ?? ''); ?>
            <select name="Cve_Almac" class="form-select" <?php echo $esEdicion ? 'disabled' : ''; ?>>
              <option value="">Seleccione...</option>
              <?php foreach ($almacenes as $a):
                $cve=(string)$a['clave']; $nom=(string)$a['nombre']; ?>
                <option value="<?php echo e($cve); ?>" <?php echo ($almSel===$cve)?'selected':''; ?>>
                  <?php echo e($cve.' - '.$nom); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if ($esEdicion): ?><input type="hidden" name="Cve_Almac" value="<?php echo e($almSel); ?>"><?php endif; ?>
          </div>

          <div class="col-md-2">
            <label class="ap-label">Tipo OC</label>
            <?php $tipoSel=(string)($encabezado['tipo_oc'] ?? 'OCN'); ?>
            <select name="tipo_oc" class="form-select" <?php echo $esEdicion?'disabled':''; ?>>
              <option value="OCN" <?php echo ($tipoSel==='OCN')?'selected':''; ?>>OCN</option>
              <option value="OCI" <?php echo ($tipoSel==='OCI')?'selected':''; ?>>OCI</option>
            </select>
            <?php if ($esEdicion): ?><input type="hidden" name="tipo_oc" value="<?php echo e($tipoSel); ?>"><?php endif; ?>
          </div>

          <div class="col-md-2">
            <label class="ap-label">Protocolo *</label>
            <?php $protSel=(string)($encabezado['ID_Protocolo'] ?? ''); ?>
            <select name="ID_Protocolo" class="form-select" <?php echo $esEdicion?'disabled':''; ?>>
              <option value="">Seleccione...</option>
              <?php foreach ($protocolos as $pr): ?>
                <option value="<?php echo e((string)$pr['ID_Protocolo']); ?>" <?php echo ($protSel===(string)$pr['ID_Protocolo'])?'selected':''; ?>>
                  <?php echo e((string)$pr['descripcion']); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if ($esEdicion): ?><input type="hidden" name="ID_Protocolo" value="<?php echo e($protSel); ?>"><?php endif; ?>
          </div>

          <div class="col-md-2">
            <label class="ap-label">Folio OC</label>
            <input type="text" name="Pedimento" class="form-control"
              placeholder="<?php echo $esEdicion ? '' : 'AUTO (se genera al guardar)'; ?>"
              value="<?php echo e((string)($encabezado['Pedimento'] ?? '')); ?>"
              <?php echo $esEdicion?'readonly':''; ?>>
            <div class="ap-help">Si lo dejas vacío, se asigna automático según Protocolo (t_protocolo.FOLIO).</div>
          </div>

          <div class="col-md-1">
            <label class="ap-label">Moneda</label>
            <?php $monSel=(int)($encabezado['Id_moneda'] ?? 1); ?>
            <select name="Id_moneda" class="form-select" <?php echo $esEdicion?'disabled':''; ?>>
              <option value="1" <?php echo ($monSel===1)?'selected':''; ?>>MXN</option>
              <option value="2" <?php echo ($monSel===2)?'selected':''; ?>>USD</option>
            </select>
            <?php if ($esEdicion): ?><input type="hidden" name="Id_moneda" value="<?php echo (int)$monSel; ?>"><?php endif; ?>
          </div>
        </div>

        <div class="row g-2 mb-3">
          <div class="col-md-2">
            <label class="ap-label">Tipo cambio</label>
            <input type="number" step="0.0001" name="Tipo_Cambio" class="form-control"
              value="<?php echo e((string)($encabezado['Tipo_Cambio'] ?? 1)); ?>"
              <?php echo $esEdicion?'readonly':''; ?>>
          </div>

          <div class="col-md-2">
            <label class="ap-label">Fecha OC</label>
            <input type="date" name="fech_pedimento" class="form-control"
              value="<?php echo e(substr((string)($encabezado['fech_pedimento'] ?? date('Y-m-d')),0,10)); ?>"
              <?php echo $esEdicion?'readonly':''; ?>>
          </div>

          <div class="col-md-4">
            <label class="ap-label">Proyecto</label>
            <input type="text" name="Proyecto" class="form-control"
              value="<?php echo e((string)($encabezado['Proyecto'] ?? '')); ?>"
              <?php echo $esEdicion?'readonly':''; ?>>
            <div class="ap-help">Sugerencia: usa un código corto (ej. “FC-Q1-2026”).</div>
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
                <th style="width:110px;" class="text-end">Costo (c/IVA)</th>
                <th style="width:70px;" class="text-end">IVA %</th>
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
                  <td><input type="number" step="0.0001" name="cantidad[]" class="form-control text-end"></td>
                  <td><input type="number" step="0.0001" name="costo[]" class="form-control text-end"></td>
                  <td><input type="number" step="0.01" name="iva[]" class="form-control text-end" value="16"></td>
                  <td><input type="text" name="Cve_Lote[]" class="form-control"></td>
                  <td><input type="date" name="caducidad[]" class="form-control"></td>
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
                    <td><input type="number" step="0.0001" name="cantidad[]" class="form-control text-end" value="<?php echo e((string)($row['cantidad'] ?? '')); ?>"></td>
                    <td><input type="number" step="0.0001" name="costo[]" class="form-control text-end" value="<?php echo e((string)($row['costo'] ?? '')); ?>"></td>
                    <td><input type="number" step="0.01" name="iva[]" class="form-control text-end" value="<?php echo e((string)($row['IVA'] ?? '16')); ?>"></td>
                    <td><input type="text" name="Cve_Lote[]" class="form-control" value="<?php echo e((string)($row['Cve_Lote'] ?? '')); ?>"></td>
                    <td><input type="date" name="caducidad[]" class="form-control" value="<?php echo e($cadIso); ?>"></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="mt-3 d-flex justify-content-between">
          <div class="text-muted" style="font-size:10px;">
            El costo capturado es precio total con IVA.<br>
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

function tryResolveProduct(v){
  v = norm(v);
  if (!v) return {cve:'', des:'', um:''};
  if (MAP_PROD[v]) return {cve:v, des:MAP_PROD[v].des||'', um:MAP_PROD[v].um||''};

  // match por descripción exacta (case-insensitive)
  const keys = Object.keys(MAP_PROD);
  for (let i=0;i<keys.length;i++){
    const k=keys[i], des=(MAP_PROD[k].des||'').toString();
    if (des.toLowerCase() === v.toLowerCase()) return {cve:k, des:MAP_PROD[k].des||'', um:MAP_PROD[k].um||''};
  }
  return {cve:v, des:'', um:''};
}

function bindRow(row){
  const inpArt=row.querySelector('.inp-art');
  const inpDes=row.querySelector('.inp-des');
  const inpUm =row.querySelector('.inp-um');

  const apply=()=>{
    const r=tryResolveProduct(inpArt.value);
    if (r.cve && MAP_PROD[r.cve]){
      inpArt.value=r.cve;
      inpDes.value=r.des||'';
      inpUm.value=r.um||'';
    } else {
      inpDes.value='';
      inpUm.value='';
    }
  };

  inpArt.addEventListener('change', apply);
  inpArt.addEventListener('blur', apply);

  row.querySelector('.btn-del').onclick=()=>{
    const tbody=row.parentElement;
    if (tbody.querySelectorAll('tr').length===1){
      row.querySelectorAll('input').forEach(inp=>{
        inp.value = (inp.name==='iva[]' ? '16' : '');
      });
      row.querySelectorAll('.inp-des,.inp-um').forEach(inp=>inp.value='');
    } else row.remove();
  };
}

document.addEventListener('DOMContentLoaded', ()=>{
  // Guardar texto del proveedor en hidden (blindaje)
  const selProv = document.getElementById('ID_Proveedor');
  const hidProv = document.getElementById('ProveedorNombre');
  if (selProv && hidProv){
    const setTxt=()=>{
      const opt = selProv.options[selProv.selectedIndex];
      hidProv.value = opt ? (opt.text || '') : '';
    };
    selProv.addEventListener('change', setTxt);
    setTxt();
  }

  const tbody=document.querySelector('#tablaPartidas tbody');
  tbody.querySelectorAll('tr').forEach(bindRow);

  document.getElementById('btnAddRow').addEventListener('click', ()=>{
    const rows=tbody.querySelectorAll('tr');
    const base=rows[rows.length-1];
    const clone=base.cloneNode(true);
    clone.querySelectorAll('input').forEach(inp=>{
      if (inp.classList.contains('inp-des') || inp.classList.contains('inp-um')) { inp.value=''; return; }
      if (inp.name==='iva[]') inp.value='16'; else inp.value='';
    });
    tbody.appendChild(clone);
    bindRow(clone);
  });
});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
