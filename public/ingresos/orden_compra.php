<?php
// public/ingresos/orden_compra.php

require_once __DIR__ . '/../../app/db.php';

try {
    $pdo = db_pdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    die('Error de conexión a base de datos: ' . htmlspecialchars($e->getMessage()));
}

/* ===========================
   AJAX: update status + registrar evento logístico (TR)
   =========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'oc_status_update') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $idAduana = (int)($_POST['id_aduana'] ?? 0);
        $nuevoStatus = strtoupper(trim((string)($_POST['status'] ?? '')));
        $comentario = trim((string)($_POST['comment'] ?? ''));
        $usuario = trim((string)($_POST['usuario'] ?? 'SYSTEM'));

        // Campos de Tracking (TR): opcionales (solo se usan si status=TR)
        $evento = strtoupper(trim((string)($_POST['evento'] ?? 'EMBARQUE')));
        $fecha_evento = trim((string)($_POST['fecha_evento'] ?? ''));
        $tracking_no = trim((string)($_POST['tracking_number'] ?? ''));
        $carrier = trim((string)($_POST['carrier'] ?? ''));
        $metodo_transporte = strtoupper(trim((string)($_POST['metodo_transporte'] ?? '')));

        if ($idAduana <= 0) throw new Exception('ID_Aduana inválido.');
        if (!in_array($nuevoStatus, ['A','C','TR'], true)) throw new Exception('Status inválido.');

        if ($usuario === '') $usuario = 'SYSTEM';

        // Normaliza fecha_evento
        if ($fecha_evento === '') {
            $fecha_evento = date('Y-m-d H:i:s');
        } else {
            // acepta yyyy-mm-dd hh:mm:ss o yyyy-mm-ddThh:mm
            $fecha_evento = str_replace('T', ' ', $fecha_evento);
            if (strlen($fecha_evento) === 16) $fecha_evento .= ':00';
        }

        $pdo->beginTransaction();

        // 1) Actualizar status en th_aduana
        $upd = $pdo->prepare("UPDATE th_aduana SET status = :st WHERE ID_Aduana = :id LIMIT 1");
        $upd->execute([':st' => $nuevoStatus, ':id' => $idAduana]);

        $id_lta = 0;

        // 2) Si es TR, registrar/append evento(s) y tracking
        if ($nuevoStatus === 'TR') {

            // 2.1) Obtener datos OC para lta_case
            $qOc = $pdo->prepare("
                SELECT
                    ID_Proveedor,
                    COALESCE(NULLIF(folio_mov,''), NULLIF(Pedimento,'')) AS num_oc
                FROM th_aduana
                WHERE ID_Aduana = :id
                LIMIT 1
            ");
            $qOc->execute([':id' => $idAduana]);
            $ocRow = $qOc->fetch(PDO::FETCH_ASSOC) ?: [];
            $idProveedor = (int)($ocRow['ID_Proveedor'] ?? 0);
            $numOC = (string)($ocRow['num_oc'] ?? '');

            // 2.2) Transporte -> enum de lta_case (AEREO/MARITIMO/TERRESTRE/MIXTO)
            $transporte = $metodo_transporte;
            if (!in_array($transporte, ['AEREO','MARITIMO','TERRESTRE','MIXTO'], true)) {
                $transporte = 'MIXTO';
            }

            // 2.3) Buscar o crear lta_case para esta OC (tipo OC + id_aduana)
            $qCase = $pdo->prepare("
                SELECT id_lta
                FROM lta_case
                WHERE tipo = 'OC'
                  AND id_aduana = :id_aduana
                ORDER BY id_lta DESC
                LIMIT 1
            ");
            $qCase->execute([':id_aduana' => $idAduana]);
            $id_lta = (int)($qCase->fetchColumn() ?: 0);

            if ($id_lta <= 0) {
                $insCase = $pdo->prepare("
                    INSERT INTO lta_case
                        (tipo, descripcion, id_proveedor, id_aduana, estado, transporte, fecha_inicio)
                    VALUES
                        ('OC', :desc, :id_prov, :id_aduana, 'TRANSITO', :transporte, CURDATE())
                ");
                $desc = trim('OC ' . $numOC);
                if ($desc === '') $desc = 'OC';
                $insCase->execute([
                    ':desc'       => $desc,
                    ':id_prov'    => ($idProveedor > 0 ? $idProveedor : null),
                    ':id_aduana'  => $idAduana,
                    ':transporte' => $transporte,
                ]);
                $id_lta = (int)$pdo->lastInsertId();
            } else {
                // Actualiza transporte si viene válido (sin tocar otros datos)
                $pdo->prepare("
                    UPDATE lta_case
                    SET transporte = :t, estado='TRANSITO'
                    WHERE id_lta = :id_lta
                    LIMIT 1
                ")->execute([':t' => $transporte, ':id_lta' => $id_lta]);
            }

            // 2.4) Validar evento enum
            if (!in_array($evento, ['EMBARQUE','INGRESO_ADUANA','SALIDA_ADUANA'], true)) {
                $evento = 'EMBARQUE';
            }

            // 2.5) Validación de secuencia: fecha_evento debe ser >= último evento (si existe)
            $qMax = $pdo->prepare("SELECT MAX(fecha_evento) FROM lta_event WHERE id_lta = :id_lta");
            $qMax->execute([':id_lta' => $id_lta]);
            $maxFecha = $qMax->fetchColumn();
            if (!empty($maxFecha)) {
                $tsNew = strtotime($fecha_evento);
                $tsMax = strtotime((string)$maxFecha);
                if ($tsNew === false || $tsMax === false) {
                    throw new Exception('Fecha de evento inválida.');
                }
                if ($tsNew < $tsMax) {
                    throw new Exception('La fecha del evento debe ser igual o mayor al último evento registrado.');
                }
            }

            // 2.6) Insertar (append) evento en lta_event
            $comentarioFinal = $comentario;
            $comentarioFinal = ($comentarioFinal !== '') ? ('['.$usuario.'] '.$comentarioFinal) : ('['.$usuario.']');

            $insEvt = $pdo->prepare("
                INSERT INTO lta_event (id_lta, evento, fecha_evento, fuente, comentario)
                VALUES (:id_lta, :evento, :fecha_evento, 'USUARIO', :comentario)
            ");
            $insEvt->execute([
                ':id_lta'       => $id_lta,
                ':evento'       => $evento,
                ':fecha_evento' => $fecha_evento,
                ':comentario'   => $comentarioFinal,
            ]);

            // 2.7) Insertar / actualizar tracking (no reemplaza eventos; sólo agrega tracking si es nuevo)
            if ($tracking_no !== '') {
                $qTrk = $pdo->prepare("
                    SELECT id_tracking
                    FROM lta_tracking
                    WHERE id_lta = :id_lta AND tracking_no = :trk
                    LIMIT 1
                ");
                $qTrk->execute([':id_lta' => $id_lta, ':trk' => $tracking_no]);
                $id_tracking = (int)($qTrk->fetchColumn() ?: 0);

                if ($id_tracking > 0) {
                    $pdo->prepare("
                        UPDATE lta_tracking
                        SET carrier = :carrier, activo = 1
                        WHERE id_tracking = :id_tracking
                        LIMIT 1
                    ")->execute([
                        ':carrier' => ($carrier !== '' ? $carrier : null),
                        ':id_tracking' => $id_tracking
                    ]);
                } else {
                    $insTrk = $pdo->prepare("
                        INSERT INTO lta_tracking (id_lta, tracking_no, carrier, activo)
                        VALUES (:id_lta, :trk, :carrier, 1)
                    ");
                    $insTrk->execute([
                        ':id_lta'  => $id_lta,
                        ':trk'     => $tracking_no,
                        ':carrier' => ($carrier !== '' ? $carrier : null),
                    ]);
                }
            }
        }

        $pdo->commit();

        echo json_encode([
            'ok' => true,
            'msg' => 'Guardado',
            'id_lta' => $id_lta
        ]);
        exit;

    } catch (Throwable $e) {
        if ($pdo && $pdo->inTransaction()) $pdo->rollBack();

        $msg = $e->getMessage();

        // Caso típico: UNIQUE (id_lta, evento) impide registrar el mismo evento más de una vez.
        if (strpos($msg, 'Duplicate entry') !== false && strpos($msg, 'id_lta_evento') !== false) {
            $msg = "Tu tabla lta_event tiene un índice UNIQUE que no permite repetir el mismo 'evento' por id_lta (key id_lta_evento). " .
                   "Para permitir múltiples escalas/eventos, elimina ese UNIQUE y deja un índice normal por id_lta. " .
                   "Ejemplo SQL: ALTER TABLE lta_event DROP INDEX id_lta_evento; (NO borres el índice id_lta).";
        }

        echo json_encode(['ok' => false, 'msg' => $msg]);
        exit;
    }
}

/* ===========================
   AJAX: snapshot TR (eventos + tracking)
   =========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'oc_tracking_snapshot') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $idAduana = (int)($_POST['id_aduana'] ?? 0);
        if ($idAduana <= 0) throw new Exception('ID_Aduana inválido.');

        // Localiza el caso LTA de la OC (si existe)
        $qCase = $pdo->prepare("
            SELECT id_lta
            FROM lta_case
            WHERE tipo='OC' AND id_aduana=:id
            ORDER BY id_lta DESC
            LIMIT 1
        ");
        $qCase->execute([':id' => $idAduana]);
        $id_lta = (int)($qCase->fetchColumn() ?: 0);

        if ($id_lta <= 0) {
            echo json_encode([
                'ok' => true,
                'id_lta' => 0,
                'events' => [],
                'tracking' => [],
            ]);
            exit;
        }

        $events = $pdo->prepare("
            SELECT id_event, evento, fecha_evento, fuente, comentario
            FROM lta_event
            WHERE id_lta = :id_lta
            ORDER BY fecha_evento ASC, id_event ASC
        ");
        $events->execute([':id_lta' => $id_lta]);
        $rowsE = $events->fetchAll(PDO::FETCH_ASSOC);

        $trks = $pdo->prepare("
            SELECT id_tracking, tracking_no, carrier, activo
            FROM lta_tracking
            WHERE id_lta = :id_lta
            ORDER BY activo DESC, id_tracking DESC
        ");
        $trks->execute([':id_lta' => $id_lta]);
        $rowsT = $trks->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'ok' => true,
            'id_lta' => $id_lta,
            'events' => $rowsE,
            'tracking' => $rowsT,
        ]);
        exit;

    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        exit;
    }
}

/**
 * Carga almacenes directamente desde th_aduana (almacenes que ya tienen OCs),
 * para no depender del API ni de otras tablas por ahora.
 *
 * Devuelve:
 *   [
 *     ['Clave_Almacen' => 'WH1', 'Descripcion' => 'WH1'],
 *     ...
 *   ]
 */
function cargarAlmacenes(PDO $pdo): array
{
    $almacenes = [];
    try {
        $rows = $pdo->query("
            SELECT DISTINCT Cve_Almac AS Clave_Almacen
            FROM th_aduana
            WHERE Cve_Almac IS NOT NULL AND Cve_Almac <> ''
            ORDER BY Cve_Almac
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $r) {
            $almacenes[] = [
                'Clave_Almacen' => $r['Clave_Almacen'],
                'Descripcion'   => $r['Clave_Almacen'],
            ];
        }
    } catch (Throwable $e) {
        $almacenes = [];
    }
    return $almacenes;
}

// ================== Catálogo de almacenes ==================
$almacenes = cargarAlmacenes($pdo);
$primerAlmacen = $almacenes[0]['Clave_Almacen'] ?? '';

// ================== Parámetros de filtro con defaults ==================
$hoy          = date('Y-m-d');
$desdeDefault = date('Y-m-d', strtotime('-7 days'));

$almacen   = $_GET['almacen']   ?? '';
$status    = $_GET['status']    ?? '';
$protocolo = $_GET['protocolo'] ?? '';
$desde     = $_GET['desde']     ?? '';
$hasta     = $_GET['hasta']     ?? '';

// ¿Es la primera carga (sin parámetros en la URL)?
$esPrimeraCarga = (count($_GET) === 0);

// Defaults visuales
if ($almacen === '' && $primerAlmacen !== '') {
    $almacen = $primerAlmacen;
}
if ($desde === '') $desde = $desdeDefault;
if ($hasta === '') $hasta = $hoy;
if ($status === '') $status = 'A';          // Abierta
if ($protocolo === '') $protocolo = 'OCN';  // Orden de Compra Nacional

// ================== Catálogo de protocolos (solo OCN / OCI) ==================
$protRows = [];
try {
    $protRows = $pdo->query("
        SELECT ID_Protocolo, descripcion
        FROM t_protocolo
        WHERE Activo = 1
          AND ID_Protocolo IN ('OCN','OCI')
        ORDER BY ID_Protocolo
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $protRows = [];
}

// ================== WHERE de la consulta ==================
$where  = [];
$params = [];

// Siempre filtramos por almacén si está seleccionado
if ($almacen !== '') {
    $where[]            = 'h.Cve_Almac = :almacen';
    $params[':almacen'] = $almacen;
}

// Solo aplicamos fecha / status / protocolo cuando el usuario ya dio "Buscar"
if (!$esPrimeraCarga) {
    if ($desde !== '') {
        $where[]           = 'DATE(COALESCE(h.fecha_mov, h.fech_pedimento)) >= :desde';
        $params[':desde']  = $desde;
    }
    if ($hasta !== '') {
        $where[]           = 'DATE(COALESCE(h.fecha_mov, h.fech_pedimento)) <= :hasta';
        $params[':hasta']  = $hasta;
    }
    if ($status !== '') {
        $where[]           = 'h.status = :status';
        $params[':status'] = $status;
    }
    if ($protocolo !== '') {
        $where[]              = "COALESCE(NULLIF(h.modulo,''), h.ID_Protocolo) = :protocolo";
        $params[':protocolo'] = $protocolo;
    }
}

$sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$ocs           = [];
$errorConsulta = '';

// ================== Consulta principal ==================
if ($almacen !== '' || !$esPrimeraCarga) {
    $sql = "
        SELECT
            h.ID_Aduana,
            COALESCE(NULLIF(h.folio_mov,''), NULLIF(h.Pedimento,'')) AS num_oc,
            COALESCE(NULLIF(h.modulo,''), h.ID_Protocolo) AS tipo_mov,
            h.Pedimento AS pedimento_legacy,
            h.num_pedimento,
            h.fech_pedimento,
            h.fech_llegPed,
            h.status,
            h.ID_Proveedor,
            h.ID_Protocolo,
            h.Cve_Almac,
            h.Tipo_Cambio,
            h.Id_moneda,
            h.recurso AS empresa_recurso,
            h.cve_cia,
            h.cve_usuario,
            h.fecha_mov,
            h.folio_mov,

            p.Nombre        AS proveedor,
            pr.descripcion  AS protocolo_desc,
            pr.ID_Protocolo AS protocolo_clave,
            COALESCE(d.partidas, 0) AS partidas
        FROM th_aduana h
        LEFT JOIN c_proveedores p
            ON p.ID_Proveedor = h.ID_Proveedor
        LEFT JOIN t_protocolo pr
            ON pr.ID_Protocolo = COALESCE(NULLIF(h.modulo,''), h.ID_Protocolo)
        LEFT JOIN (
            SELECT ID_Aduana, COUNT(*) AS partidas
            FROM td_aduana
            GROUP BY ID_Aduana
        ) d
            ON d.ID_Aduana = h.ID_Aduana
        $sqlWhere
        ORDER BY h.ID_Aduana DESC
        LIMIT 500
    ";

    try {
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $ocs = $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $errorConsulta = $e->getMessage();
        $ocs = [];
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>AssistPro SFA — Órdenes de Compra</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f5f7fb;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 10px;
        }
        .ap-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 90, 173, 0.08);
            border: 1px solid #eef1f7;
            padding: 8px 10px;
            margin-bottom: 8px;
        }
        .ap-title,
        .ap-subtitle,
        .ap-label,
        .form-control,
        .form-select,
        .btn,
        table,
        table th,
        table td {
            font-size: 10px !important;
        }
        .ap-title {
            font-weight: 700;
            color: #0F5AAD;
        }
        .ap-subtitle {
            color: #6c757d;
            margin-bottom: 0;
        }
        .ap-label {
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 2px;
        }
        .form-control,
        .form-select {
            border-radius: 8px;
            padding-top: 1px;
            padding-bottom: 1px;
            line-height: 1.2;
        }
        .form-control:focus,
        .form-select:focus {
            border-color: #0F5AAD;
            box-shadow: 0 0 0 0.12rem rgba(15, 90, 173, .25);
        }
        .btn-ap-primary {
            background-color: #0F5AAD;
            border-color: #0F5AAD;
            color: #fff;
            border-radius: 999px;
            padding-inline: 14px;
            padding-block: 3px;
            box-shadow: 0 4px 10px rgba(15, 90, 173, 0.4);
        }
        .btn-ap-primary:hover {
            background-color: #0c4a8d;
            border-color: #0c4a8d;
        }
        #tablaWrapper {
            max-height: 520px;
            overflow-y: auto;
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }
        table.table-sm th,
        table.table-sm td {
            white-space: nowrap;
            vertical-align: middle;
        }
        thead.sticky-header th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #f8fafc;
        }
        .badge-status {
            border-radius: 999px;
            padding: 2px 8px;
            text-decoration: none;
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/../bi/_menu_global.php'; ?>

<div class="container-fluid py-2">

    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <div class="ap-title mb-0">AssistPro SFA — Órdenes de Compra</div>
            <p class="ap-subtitle">Consulta y administración de Órdenes de Compra (th_aduana / td_aduana).</p>
        </div>
        <div class="d-flex gap-2">
            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                    Columnas
                </button>
                <ul class="dropdown-menu dropdown-menu-end p-2" style="font-size:10px;">
                    <li><label class="form-check-label">
                        <input class="form-check-input me-1 col-toggle" type="checkbox" data-col="folio" checked> Folio
                    </label></li>
                    <li><label class="form-check-label">
                        <input class="form-check-input me-1 col-toggle" type="checkbox" data-col="pedimento" checked> Pedimento
                    </label></li>
                    <li><label class="form-check-label">
                        <input class="form-check-input me-1 col-toggle" type="checkbox" data-col="fecha" checked> Fecha OC
                    </label></li>
                    <li><label class="form-check-label">
                        <input class="form-check-input me-1 col-toggle" type="checkbox" data-col="proveedor" checked> Proveedor
                    </label></li>
                    <li><label class="form-check-label">
                        <input class="form-check-input me-1 col-toggle" type="checkbox" data-col="protocolo" checked> Tipo OC
                    </label></li>
                    <li><label class="form-check-label">
                        <input class="form-check-input me-1 col-toggle" type="checkbox" data-col="almacen" checked> Almacén
                    </label></li>
                    <li><label class="form-check-label">
                        <input class="form-check-input me-1 col-toggle" type="checkbox" data-col="moneda" checked> Moneda
                    </label></li>
                    <li><label class="form-check-label">
                        <input class="form-check-input me-1 col-toggle" type="checkbox" data-col="partidas" checked> Partidas
                    </label></li>
                </ul>
            </div>

            <a href="orden_compra_edit.php" class="btn btn-ap-primary btn-sm">
                Nueva OC
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="ap-card mb-2">
        <form class="row g-2 align-items-end" method="get">
            <div class="col-md-2 col-sm-4">
                <label class="ap-label">Almacén</label>
                <select class="form-select" name="almacen">
                    <?php if (!$almacenes): ?>
                        <option value="">(Sin almacenes en th_aduana)</option>
                    <?php else: ?>
                        <?php foreach ($almacenes as $a): ?>
                            <option value="<?php echo htmlspecialchars($a['Clave_Almacen']); ?>"
                                <?php if ($almacen === $a['Clave_Almacen']) echo ' selected'; ?>>
                                <?php echo htmlspecialchars($a['Clave_Almacen'] . ' — ' . $a['Descripcion']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="col-md-2 col-sm-4">
                <label class="ap-label">Fecha desde</label>
                <input type="date" class="form-control" name="desde" value="<?php echo htmlspecialchars($desde); ?>">
            </div>
            <div class="col-md-2 col-sm-4">
                <label class="ap-label">Fecha hasta</label>
                <input type="date" class="form-control" name="hasta" value="<?php echo htmlspecialchars($hasta); ?>">
            </div>
            <div class="col-md-2 col-sm-4">
                <label class="ap-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">Todos</option>
                    <option value="A"<?php if ($status==='A') echo ' selected'; ?>>Abierta (A)</option>
                    <option value="C"<?php if ($status==='C') echo ' selected'; ?>>Cerrada (C)</option>
                    <option value="TR"<?php if ($status==='TR') echo ' selected'; ?>>Tracking (TR)</option>
                </select>
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="ap-label">Tipo OC</label>
                <select class="form-select" name="protocolo">
                    <?php if (!$protRows): ?>
                        <option value="">Sin tipos</option>
                    <?php else: ?>
                        <?php foreach ($protRows as $pr): ?>
                            <option value="<?php echo htmlspecialchars($pr['ID_Protocolo']); ?>"
                                <?php if ($protocolo === $pr['ID_Protocolo']) echo ' selected'; ?>>
                                <?php echo htmlspecialchars($pr['ID_Protocolo'] . ' - ' . $pr['descripcion']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-1 col-sm-6 text-end">
                <button type="submit" class="btn btn-ap-primary btn-sm w-100">
                    Buscar
                </button>
            </div>
        </form>
        <?php if (!empty($errorConsulta)): ?>
            <div class="text-danger mt-1" style="font-size:9px;">
                Error en la consulta: <?php echo htmlspecialchars($errorConsulta, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Grilla -->
    <div class="ap-card">
        <div id="tablaWrapper" class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" id="tablaOC">
                <thead class="sticky-header">
                    <tr>
                        <th style="width:120px;">Acciones</th>
                        <th>Empresa</th>
                        <th>Status</th>
                        <th class="col-pedimento">Num. OC</th>
                        <th class="col-fecha">Fecha OC</th>
                        <th class="col-proveedor">Proveedor</th>
                        <th class="col-protocolo">Tipo OC</th>
                        <th class="col-almacen">Almacén</th>
                        <th class="col-moneda">Moneda</th>
                        <th>Tipo Cambio</th>
                        <th class="col-partidas text-end" style="width:80px;">Partidas (td_aduana)</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($almacen === '' && !$almacenes): ?>
                    <tr>
                        <td colspan="12" class="text-center text-muted">
                            No hay almacenes disponibles en th_aduana.
                        </td>
                    </tr>
                <?php elseif (!$ocs): ?>
                    <tr>
                        <td colspan="12" class="text-center text-muted">
                            No hay órdenes de compra para los filtros seleccionados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ocs as $r): ?>
                        <?php
                            $monedaTxt = '';
                            if ((int)$r['Id_moneda'] === 1) {
                                $monedaTxt = 'MXN';
                            } elseif ((int)$r['Id_moneda'] === 2) {
                                $monedaTxt = 'USD';
                            }

                            // Badge por status (A/C/TR)
                            if (($r['status'] ?? '') === 'A') {
                                $statusBadge = 'success';
                                $statusText  = 'Abierta';
                            } elseif (($r['status'] ?? '') === 'C') {
                                $statusBadge = 'secondary';
                                $statusText  = 'Cerrada';
                            } else {
                                $statusBadge = 'warning';
                                $statusText  = 'Tracking';
                            }
                        ?>
                        <tr>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="orden_compra_edit.php?id_aduana=<?php echo (int)$r['ID_Aduana']; ?>"
                                       class="btn btn-outline-primary">
                                        Ver / editar
                                    </a>
                                    <a href="orden_compra_pdf.php?id_aduana=<?php echo (int)$r['ID_Aduana']; ?>"
                                       class="btn btn-outline-secondary" target="_blank">
                                        PDF OC
                                    </a>
                                    <a href="orden_compra_pdf_sin_costos.php?id_aduana=<?php echo (int)$r['ID_Aduana']; ?>"
                                       class="btn btn-outline-secondary" target="_blank">
                                        PDF recepción
                                    </a>
                                </div>
                            </td>
                            <td>
                                <?php
                                    $empresaVal = $r['cve_cia'] ?? ($r['empresa_recurso'] ?? '');
                                    echo htmlspecialchars((string)$empresaVal, ENT_QUOTES, 'UTF-8');
                                ?>
                            </td>
                            <td>
                                <a href="#"
                                   class="link-status badge badge-status bg-<?php echo $statusBadge; ?>"
                                   data-id="<?php echo (int)$r['ID_Aduana']; ?>"
                                   data-status="<?php echo htmlspecialchars((string)($r['status'] ?? 'A'), ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </td>
                            <td class="col-pedimento">
                                <a href="orden_compra_edit.php?id_aduana=<?php echo (int)$r['ID_Aduana']; ?>">
                                    <?php echo htmlspecialchars($r['num_oc'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </td>
                            <td class="col-fecha">
                                <?php
                                    echo htmlspecialchars(
                                        (!empty($r['fecha_mov']) ? substr($r['fecha_mov'], 0, 10) : (!empty($r['fech_pedimento']) ? substr($r['fech_pedimento'], 0, 10) : '')),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                ?>
                            </td>
                            <td class="col-proveedor">
                                <?php echo htmlspecialchars($r['proveedor'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td class="col-protocolo">
                                <?php
                                    $protTxt = trim(($r['protocolo_clave'] ?? '') . ' ' . ($r['protocolo_desc'] ?? ''));
                                    echo htmlspecialchars($protTxt, ENT_QUOTES, 'UTF-8');
                                ?>
                            </td>
                            <td class="col-almacen">
                                <?php echo htmlspecialchars($r['Cve_Almac'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td class="col-moneda">
                                <?php echo htmlspecialchars($monedaTxt, ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td class="text-end">
                                <?php echo number_format((float)$r['Tipo_Cambio'], 4); ?>
                            </td>
                            <td class="col-partidas text-end">
                                <?php echo (int)$r['partidas']; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars((string)($r['cve_usuario'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="text-muted mt-1" style="font-size:9px;">
            Consulta limitada a 500 registros. Primera carga: filtra solo por almacén. Después de &laquo;Buscar&raquo; aplica fechas, status y tipo OC.
        </div>
    </div>

</div>

<!-- Modal Actualizar status OC -->
<div class="modal fade" id="modalStatusOC" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:650px;">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">Actualizar status OC</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" id="modal_id_aduana">

        <div class="mb-2">
          <label class="form-label">Nuevo status</label>
          <select id="modal_status" class="form-select form-select-sm">
            <option value="A">Abierta (A)</option>
            <option value="C">Cerrada (C)</option>
            <option value="TR">Tracking (TR)</option>
          </select>
        </div>

        <div id="boxLTA" class="border rounded p-2 mb-2" style="display:none;">
          <div class="mb-2">
            <label class="form-label">Evento logístico</label>
            <select id="modal_evento_lta" class="form-select form-select-sm">
              <option value="EMBARQUE">EMBARQUE</option>
              <option value="INGRESO_ADUANA">INGRESO_ADUANA</option>
              <option value="SALIDA_ADUANA">SALIDA_ADUANA</option>
            </select>
          </div>

          <div class="mb-2">
            <label class="form-label">Fecha evento</label>
            <input type="datetime-local" id="modal_fecha_evento" class="form-control form-control-sm">
            <div class="text-muted" style="font-size:10px;">(Debe ser igual o mayor al último evento registrado)</div>
          </div>

          <div class="mb-2">
            <label class="form-label">Tracking number</label>
            <input type="text" id="modal_tracking_number" class="form-control form-control-sm" placeholder="Ej: TN123...">
          </div>

          <div class="mb-2">
            <label class="form-label">Método de transporte</label>
            <input type="text" id="modal_metodo_transporte" class="form-control form-control-sm" placeholder="AEREO / MARITIMO / TERRESTRE / MIXTO">
          </div>

          <div class="mb-2">
            <label class="form-label">Carrier</label>
            <input type="text" id="modal_carrier" class="form-control form-control-sm" placeholder="DHL / FedEx / Naviera / Aerolínea">
          </div>
        </div>

        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center">
            <label class="form-label mb-1">Historial logístico (snapshot)</label>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRefrescarSnap" style="font-size:10px;padding:2px 8px;">Refrescar</button>
          </div>

          <div class="table-responsive border rounded" style="max-height:180px;overflow:auto;">
            <table class="table table-sm mb-0" style="font-size:10px;">
              <thead class="table-light" style="position:sticky;top:0;z-index:1;">
                <tr>
                  <th style="width:110px;">Evento</th>
                  <th style="width:160px;">Fecha</th>
                  <th style="width:80px;">Fuente</th>
                  <th>Comentario</th>
                </tr>
              </thead>
              <tbody id="snapEventsBody">
                <tr><td colspan="4" class="text-muted">Sin eventos</td></tr>
              </tbody>
            </table>
          </div>

          <div class="mt-2">
            <label class="form-label mb-1">Tracking(s)</label>
            <div class="table-responsive border rounded" style="max-height:110px;overflow:auto;">
              <table class="table table-sm mb-0" style="font-size:10px;">
                <thead class="table-light" style="position:sticky;top:0;z-index:1;">
                  <tr>
                    <th>Tracking</th>
                    <th>Carrier</th>
                    <th class="text-center" style="width:60px;">Activo</th>
                  </tr>
                </thead>
                <tbody id="snapTrackingBody">
                  <tr><td colspan="3" class="text-muted">Sin tracking</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="mb-2">
          <label class="form-label">Comentario</label>
          <textarea id="modal_comment" class="form-control form-control-sm" rows="3" placeholder="Motivo / nota"></textarea>
        </div>

        <div class="text-danger" id="modal_error" style="font-size:10px;display:none;"></div>
        <div class="text-success" id="modal_ok" style="font-size:10px;display:none;"></div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-ap-primary btn-sm" id="btnGuardarStatus">Guardar</button>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Toggle columnas
document.querySelectorAll('.col-toggle').forEach(cb => {
  cb.addEventListener('change', () => {
    const col = cb.dataset.col;
    document.querySelectorAll('.col-' + col).forEach(td => {
      td.style.display = cb.checked ? '' : 'none';
    });
  });
});

(function(){
  const modalEl = document.getElementById('modalStatusOC');
  if (!modalEl) return;

  const modal = new bootstrap.Modal(modalEl);

  const inpId = document.getElementById('modal_id_aduana');
  const selSt = document.getElementById('modal_status');
  const txtCom = document.getElementById('modal_comment');

  const boxLTA = document.getElementById('boxLTA');
  const selEvento = document.getElementById('modal_evento_lta');
  const inpFecha  = document.getElementById('modal_fecha_evento');
  const inpTrk    = document.getElementById('modal_tracking_number');
  const inpMetodo = document.getElementById('modal_metodo_transporte');
  const inpCarrier= document.getElementById('modal_carrier');

  const outErr = document.getElementById('modal_error');
  const outOk  = document.getElementById('modal_ok');

  const btnSave = document.getElementById('btnGuardarStatus');
  const btnRef = document.getElementById('btnRefrescarSnap');
  const tbEvents = document.getElementById('snapEventsBody');
  const tbTrk = document.getElementById('snapTrackingBody');

  function setMsg(el, msg){
    if (!el) return;
    el.textContent = msg || '';
    el.style.display = msg ? '' : 'none';
  }

  function showLTABox(){
    if (!boxLTA) return;
    boxLTA.style.display = (selSt.value === 'TR') ? '' : 'none';
  }

  function nowDatetimeLocal(){
    const d = new Date();
    const pad = (n)=> String(n).padStart(2,'0');
    return d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate())+'T'+pad(d.getHours())+':'+pad(d.getMinutes());
  }

  async function fetchSnapshot(id){
    if (!tbEvents || !tbTrk) return;

    tbEvents.innerHTML = '<tr><td colspan="4" class="text-muted">Cargando...</td></tr>';
    tbTrk.innerHTML = '<tr><td colspan="3" class="text-muted">Cargando...</td></tr>';

    try{
      const fd = new URLSearchParams();
      fd.set('ajax', 'oc_tracking_snapshot');
      fd.set('id_aduana', id);

      const resp = await fetch(window.location.href, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
        body: fd.toString()
      });
      const data = await resp.json();

      if (!data || !data.ok) {
        tbEvents.innerHTML = '<tr><td colspan="4" class="text-danger">Error: '+ (data && data.msg ? data.msg : 'snapshot') +'</td></tr>';
        tbTrk.innerHTML = '<tr><td colspan="3" class="text-danger">Error</td></tr>';
        return;
      }

      const events = Array.isArray(data.events) ? data.events : [];
      const trks = Array.isArray(data.tracking) ? data.tracking : [];

      if (!events.length){
        tbEvents.innerHTML = '<tr><td colspan="4" class="text-muted">Sin eventos</td></tr>';
      } else {
        tbEvents.innerHTML = events.map(e => {
          const f = (e.fecha_evento || '');
          const ev = (e.evento || '');
          const fu = (e.fuente || '');
          const co = (e.comentario || '');
          return '<tr>'
            + '<td>'+ ev +'</td>'
            + '<td>'+ f +'</td>'
            + '<td>'+ fu +'</td>'
            + '<td style="white-space:normal;">'+ co +'</td>'
            + '</tr>';
        }).join('');
      }

      if (!trks.length){
        tbTrk.innerHTML = '<tr><td colspan="3" class="text-muted">Sin tracking</td></tr>';
      } else {
        tbTrk.innerHTML = trks.map(t => {
          const tr = (t.tracking_no || '');
          const ca = (t.carrier || '');
          const ac = (String(t.activo) === '1') ? 'Sí' : 'No';
          return '<tr>'
            + '<td>'+ tr +'</td>'
            + '<td>'+ ca +'</td>'
            + '<td class="text-center">'+ ac +'</td>'
            + '</tr>';
        }).join('');
      }
    } catch(err){
      tbEvents.innerHTML = '<tr><td colspan="4" class="text-danger">Error JS: '+ (err && err.message ? err.message : err) +'</td></tr>';
      tbTrk.innerHTML = '<tr><td colspan="3" class="text-danger">Error</td></tr>';
    }
  }

  selSt?.addEventListener('change', ()=>{
    showLTABox();
    if (selSt.value === 'TR' && inpFecha && !inpFecha.value) inpFecha.value = nowDatetimeLocal();
  });

  btnRef?.addEventListener('click', ()=>{
    const id = inpId?.value || '';
    if (id) fetchSnapshot(id);
  });

  document.addEventListener('click', function(e){
    const el = e.target.closest('.link-status');
    if (!el) return;
    e.preventDefault();

    setMsg(outErr, '');
    setMsg(outOk, '');

    const id = el.dataset.id || '';
    const st = (el.dataset.status || 'A').toUpperCase();

    inpId.value = id;
    selSt.value = ['A','C','TR'].includes(st) ? st : 'A';

    // defaults
    txtCom.value = '';
    if (selEvento) selEvento.value = 'EMBARQUE';
    if (inpFecha) inpFecha.value = nowDatetimeLocal();
    if (inpTrk) inpTrk.value = '';
    if (inpMetodo) inpMetodo.value = '';
    if (inpCarrier) inpCarrier.value = '';

    showLTABox();
    modal.show();

    if (id) fetchSnapshot(id);
  });

  btnSave?.addEventListener('click', async function(){
    setMsg(outErr, '');
    setMsg(outOk, '');

    const id = inpId.value || '';
    const st = selSt.value || 'A';

    if (!id) {
      setMsg(outErr, 'Falta ID.');
      return;
    }

    const fd = new URLSearchParams();
    fd.set('ajax', 'oc_status_update');
    fd.set('id_aduana', id);
    fd.set('status', st);
    fd.set('comment', txtCom.value || '');

    if (st === 'TR') {
      fd.set('evento', selEvento ? (selEvento.value || 'EMBARQUE') : 'EMBARQUE');
      // datetime-local -> yyyy-mm-dd hh:mm:ss
      if (inpFecha && inpFecha.value) fd.set('fecha_evento', inpFecha.value.replace('T',' ') + ':00');
      if (inpTrk && inpTrk.value) fd.set('tracking_number', inpTrk.value);
      if (inpMetodo && inpMetodo.value) fd.set('metodo_transporte', inpMetodo.value);
      if (inpCarrier && inpCarrier.value) fd.set('carrier', inpCarrier.value);
    }

    btnSave.disabled = true;

    try{
      const resp = await fetch(window.location.href, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
        body: fd.toString()
      });
      const data = await resp.json();

      if (!data || !data.ok) {
        setMsg(outErr, (data && data.msg) ? data.msg : 'Error al guardar');
        return;
      }

      setMsg(outOk, 'Guardado correctamente');

      // Actualiza badge sin recargar
      const badge = document.querySelector('.link-status[data-id="'+id+'"]');
      if (badge) {
        badge.dataset.status = st;
        badge.classList.remove('bg-success','bg-secondary','bg-warning');

        if (st === 'A') { badge.classList.add('bg-success'); badge.textContent = 'Abierta'; }
        else if (st === 'C') { badge.classList.add('bg-secondary'); badge.textContent = 'Cerrada'; }
        else { badge.classList.add('bg-warning'); badge.textContent = 'Tracking'; }
      }

      // Refrescar snapshot (para ir sumando eventos)
      await fetchSnapshot(id);

      // Si NO es TR, cerramos el modal; si es TR lo dejamos abierto para capturar más eventos
      if (st !== 'TR') {
        setTimeout(()=> modal.hide(), 400);
      } else {
        // limpia campos de captura para siguiente evento
        txtCom.value = '';
        if (inpTrk) inpTrk.value = '';
        if (inpCarrier) inpCarrier.value = '';
      }

    } catch(err){
      setMsg(outErr, 'Error JS/red: ' + (err && err.message ? err.message : err));
    } finally {
      btnSave.disabled = false;
    }
  });

})();
</script>

</body>
</html>
