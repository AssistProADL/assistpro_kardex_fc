<?php
// public/pedidos/registro_pedidos.php

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$pdo = db_pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// =========================
// Helpers
// =========================
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function fetchAllSafe(PDO $pdo, string $sql, array $params = []): array {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function ymd_to_dmy(?string $ymd): string {
    $ymd = trim((string)$ymd);
    if ($ymd === '' || $ymd === '0000-00-00') return '';
    // soporta date o datetime
    $parts = preg_split('/\s+/', $ymd);
    $d = $parts[0] ?? '';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return $ymd;
    [$Y,$m,$d2] = explode('-', $d);
    return sprintf('%02d/%02d/%04d', (int)$d2, (int)$m, (int)$Y);
}

function dmy_to_ymd(?string $dmy): string {
    $dmy = trim((string)$dmy);
    if ($dmy === '') return '';
    // acepta dd/mm/aaaa o dd-mm-aaaa
    $dmy = str_replace('-', '/', $dmy);
    if (!preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $dmy)) return '';
    [$d,$m,$Y] = explode('/', $dmy);
    return sprintf('%04d-%02d-%02d', (int)$Y, (int)$m, (int)$d);
}

function split_rango(?string $rango): array {
    $rango = trim((string)$rango);
    if ($rango === '') return ['', ''];
    // formatos comunes: "08:00-12:00" | "08:00 a 12:00" | "08:00 12:00"
    $tmp = str_ireplace([' a ', ' A '], '-', $rango);
    $tmp = preg_replace('/\s+/', '', $tmp);
    if (strpos($tmp, '-') !== false) {
        [$a,$b] = array_pad(explode('-', $tmp, 2), 2, '');
        return [trim($a), trim($b)];
    }
    return [$rango, ''];
}

// =========================
// Catálogos para filtros
// =========================

// Empresas (si existe c_compania; si no, se muestra vacío sin romper)
$empresas = [];
try {
    $empresas = fetchAllSafe($pdo, "
        SELECT cve_cia, nombre
        FROM c_compania
        WHERE COALESCE(Activo,1)=1
        ORDER BY nombre
    ");
} catch (Throwable $e) {
    $empresas = [];
}

// Almacenes (c_almacenp)
$almacenes = [];
try {
    $almacenes = fetchAllSafe($pdo, "
        SELECT id, clave, nombre, cve_cia
        FROM c_almacenp
        WHERE COALESCE(Activo,1)=1
        ORDER BY nombre
    ");
} catch (Throwable $e) {
    $almacenes = [];
}

// =========================
// Filtros (GET) - UI en dd/mm/aaaa
// =========================
$empresa   = (int)($_GET['empresa'] ?? 0);
$almacenp  = (int)($_GET['almacen'] ?? 0);
$tipo      = trim((string)($_GET['tipo'] ?? ''));      // CLIENTE | RUTA
$status    = trim((string)($_GET['status'] ?? ''));    // A | C | etc
$desde_ui  = trim((string)($_GET['desde'] ?? ''));      // dd/mm/aaaa
$hasta_ui  = trim((string)($_GET['hasta'] ?? ''));      // dd/mm/aaaa
$q         = trim((string)($_GET['q'] ?? ''));

// Default últimos 30 días si vienen vacías
if ($desde_ui === '' && $hasta_ui === '') {
    $hasta_ui = date('d/m/Y');
    $desde_ui = date('d/m/Y', strtotime('-30 days'));
}

$desde = dmy_to_ymd($desde_ui);
$hasta = dmy_to_ymd($hasta_ui);

// si no parsea, no rompemos
if ($desde_ui !== '' && $desde === '') $desde_ui = '';
if ($hasta_ui !== '' && $hasta === '') $hasta_ui = '';

// =========================
// Query principal (Listado)
// =========================
$where = [];
$params = [];

$where[] = "COALESCE(h.Activo,1)=1";

if ($empresa > 0) {
    // Filtra por empresa a través del cliente (c_cliente.IdEmpresa)
    $where[] = "COALESCE(c.IdEmpresa,'') COLLATE utf8mb4_unicode_ci = CONVERT(:empresa USING utf8mb4) COLLATE utf8mb4_unicode_ci";
    $params[':empresa'] = (string)$empresa;
}

if ($almacenp > 0) {
    // h.cve_almac es varchar(20) y aquí se usa como id (por tu UI previa)
    $where[] = "TRIM(COALESCE(h.cve_almac,'')) COLLATE utf8mb4_unicode_ci = CONVERT(:alm USING utf8mb4) COLLATE utf8mb4_unicode_ci";
    $params[':alm'] = (string)$almacenp;
}

if ($tipo !== '') {
    $where[] = "TRIM(COALESCE(h.TipoPedido,'')) COLLATE utf8mb4_unicode_ci = CONVERT(:tipo USING utf8mb4) COLLATE utf8mb4_unicode_ci";
    $params[':tipo'] = $tipo;
}

if ($status !== '') {
    $where[] = "TRIM(COALESCE(h.status,'')) COLLATE utf8mb4_unicode_ci = CONVERT(:st USING utf8mb4) COLLATE utf8mb4_unicode_ci";
    $params[':st'] = $status;
}

if ($desde !== '') {
    $where[] = "h.Fec_Pedido >= :desde";
    $params[':desde'] = $desde;
}
if ($hasta !== '') {
    $where[] = "h.Fec_Pedido <= :hasta";
    $params[':hasta'] = $hasta;
}

if ($q !== '') {
    // Buscador BD (al presionar Filtrar)
    $where[] = "("
        . " CONVERT(COALESCE(h.Fol_folio,'') USING utf8mb4) COLLATE utf8mb4_unicode_ci LIKE :q"
        . " OR CONVERT(COALESCE(h.Cve_clte,'') USING utf8mb4) COLLATE utf8mb4_unicode_ci LIKE :q"
        . " OR CONVERT(COALESCE(c.RazonSocial,'') USING utf8mb4) COLLATE utf8mb4_unicode_ci LIKE :q"
        . " OR CONVERT(COALESCE(h.ruta,'') USING utf8mb4) COLLATE utf8mb4_unicode_ci LIKE :q"
        . ")";
    $params[':q'] = '%' . $q . '%';
}

$sqlListado = "
    SELECT
        h.id_pedido,
        h.Fol_folio,
        h.Fec_Pedido,
        h.Fec_Entrega,
        h.Cve_clte,
        h.TipoPedido,
        h.ruta,
        h.status,
        h.cve_almac,
        h.Cve_Usuario,
        h.Tot_Factura,
        h.rango_hora,
        c.RazonSocial AS cliente_nombre,
        ap.nombre AS almacen_nombre,
        COALESCE(d.partidas,0) AS partidas
    FROM th_pedido h
    LEFT JOIN c_cliente c
        ON c.Cve_Clte COLLATE utf8mb4_unicode_ci
         = h.Cve_clte COLLATE utf8mb4_unicode_ci
    LEFT JOIN c_almacenp ap
        ON CAST(ap.id AS CHAR) COLLATE utf8mb4_unicode_ci
         = h.cve_almac COLLATE utf8mb4_unicode_ci
    LEFT JOIN (
        SELECT Fol_folio, COUNT(*) AS partidas
        FROM td_pedido
        GROUP BY Fol_folio
    ) d
        ON d.Fol_folio COLLATE utf8mb4_unicode_ci
         = h.Fol_folio COLLATE utf8mb4_unicode_ci
    WHERE " . implode(" AND ", $where) . "
    ORDER BY h.id_pedido DESC
    LIMIT 400
";

$rows = fetchAllSafe($pdo, $sqlListado, $params);

// KPIs
$kpi_total = count($rows);
$kpi_partidas = 0;
$kpi_monto = 0.0;
foreach ($rows as $r) {
    $kpi_partidas += (int)($r['partidas'] ?? 0);
    $kpi_monto += (float)($r['Tot_Factura'] ?? 0);
}

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>AssistPro SFA — Registro de Pedidos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables -->
    <link href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@2.1.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/datatables.net-fixedheader-bs5@4.0.1/css/fixedHeader.bootstrap5.min.css" rel="stylesheet">

    <style>
        body { background:#f5f7fb; font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial; }

        /* AssistPro 10px */
        .ap-10, .ap-10 * { font-size:10px !important; }

        .ap-card{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 1px 2px rgba(0,0,0,.04); }
        .ap-title{ font-weight:800; color:#0F5AAD; margin:0; }
        .ap-subtitle{ font-size:10px !important; color:#6b7280; margin:0; }

        .ap-label{ font-size:10px !important; font-weight:800; color:#374151; margin-bottom:4px; }
        .btn-ap-primary{ background:#0F5AAD; border-color:#0F5AAD; color:#fff; border-radius:999px; }
        .btn-ap-primary:hover{ background:#0c4a8d; border-color:#0c4a8d; }

        .ap-kpi{ border:1px dashed #c7d2fe; background:#f8fafc; border-radius:12px; padding:10px 12px; }
        .ap-kpi .k{ font-size:10px !important; color:#6b7280; font-weight:800; }
        .ap-kpi .v{ font-size:14px !important; font-weight:900; color:#111827; }

        /* Grilla 1 línea, scroll */
        table.ap-table { width:100%; }
        .ap-table th,.ap-table td{
            font-size:10px !important;
            white-space:nowrap !important;
            vertical-align:middle;
            line-height:1.15 !important;
        }
        .ap-table thead th{ position:sticky; top:0; background:#f3f4f6; z-index:2; }

        .ap-badge{ font-size:10px !important; padding:.18rem .45rem; border-radius:999px; font-weight:800; }
        .ap-badge-a{ background:#dcfce7; color:#166534; }
        .ap-badge-c{ background:#fee2e2; color:#991b1b; }
        .ap-badge-x{ background:#e5e7eb; color:#374151; }

        /* Controles 10px */
        .form-control, .form-select, .btn { font-size:10px !important; }
        .btn-sm{ padding:.22rem .45rem; }

        /* DataTables compact */
        div.dataTables_wrapper div.dataTables_filter input { font-size:10px !important; }
        div.dataTables_wrapper div.dataTables_info,
        div.dataTables_wrapper div.dataTables_paginate { font-size:10px !important; }

        .ap-table-wrap{ max-height:68vh; overflow:auto; }
    </style>
</head>
<body>

<div class="container-fluid mt-3 ap-10">

    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h5 class="ap-title">Registro de Pedidos</h5>
            <p class="ap-subtitle">Gestión operativa con grilla inferior, filtros y PDFs (Cliente / Ruta)</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-ap-primary btn-sm" data-bs-toggle="modal" data-bs-target="#mdlTipoPedido">
                Nuevo Pedido
            </button>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-2 mb-2">
        <div class="col-md-3">
            <div class="ap-kpi">
                <div class="k">Pedidos listados</div>
                <div class="v"><?php echo h($kpi_total); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="ap-kpi">
                <div class="k">Partidas (total)</div>
                <div class="v"><?php echo h($kpi_partidas); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="ap-kpi">
                <div class="k">Monto (Tot_Factura)</div>
                <div class="v"><?php echo number_format($kpi_monto, 2); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="ap-kpi">
                <div class="k">Ventana de fechas</div>
                <div class="v">
                    <?php echo h($desde_ui); ?> → <?php echo h($hasta_ui); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="ap-card mb-2 p-3">
        <form class="row g-2 align-items-end" method="get" autocomplete="off">

            <div class="col-md-2 col-sm-4">
                <label class="ap-label">Empresa</label>
                <select class="form-select" name="empresa">
                    <option value="">Todas</option>
                    <?php foreach ($empresas as $e): ?>
                        <option value="<?php echo (int)$e['cve_cia']; ?>" <?php if ($empresa === (int)$e['cve_cia']) echo 'selected'; ?>>
                            <?php echo h($e['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 col-sm-4">
                <label class="ap-label">Almacén</label>
                <select class="form-select" name="almacen">
                    <option value="">Todos</option>
                    <?php foreach ($almacenes as $a): ?>
                        <option value="<?php echo (int)$a['id']; ?>" <?php if ($almacenp === (int)$a['id']) echo 'selected'; ?>>
                            <?php echo h(($a['clave'] ?? '—') . ' - ' . ($a['nombre'] ?? '')); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 col-sm-4">
                <label class="ap-label">Tipo Pedido</label>
                <select class="form-select" name="tipo">
                    <option value="">Todos</option>
                    <option value="CLIENTE" <?php if ($tipo==='CLIENTE') echo 'selected'; ?>>Pedido Cliente</option>
                    <option value="RUTA" <?php if ($tipo==='RUTA') echo 'selected'; ?>>Pedido Ruta</option>
                </select>
            </div>

            <div class="col-md-1 col-sm-3">
                <label class="ap-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">Todos</option>
                    <option value="A" <?php if ($status==='A') echo 'selected'; ?>>A</option>
                    <option value="C" <?php if ($status==='C') echo 'selected'; ?>>C</option>
                </select>
            </div>

            <div class="col-md-2 col-sm-4">
                <label class="ap-label">Desde (dd/mm/aaaa)</label>
                <input type="text" class="form-control" name="desde" id="f_desde"
                       placeholder="dd/mm/aaaa" value="<?php echo h($desde_ui); ?>">
            </div>

            <div class="col-md-2 col-sm-4">
                <label class="ap-label">Hasta (dd/mm/aaaa)</label>
                <input type="text" class="form-control" name="hasta" id="f_hasta"
                       placeholder="dd/mm/aaaa" value="<?php echo h($hasta_ui); ?>">
            </div>

            <div class="col-md-3 col-sm-6">
                <label class="ap-label">Buscar (BD + Grilla)</label>
                <input type="text" class="form-control" name="q" id="q_buscar"
                       placeholder="Folio, cliente, razón social, ruta…" value="<?php echo h($q); ?>">
            </div>

            <div class="col-md-2 col-sm-6 d-flex gap-2">
                <button class="btn btn-ap-primary w-100" type="submit">Filtrar</button>
                <a class="btn btn-outline-secondary w-100" href="registro_pedidos.php">Limpiar</a>
            </div>

        </form>
    </div>

    <!-- Grilla inferior -->
    <div class="ap-card p-0">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
            <div style="font-weight:900;color:#0F5AAD;">Pedidos generados</div>
            <div class="text-muted" style="font-size:10px;">Mostrando últimos 400 (optimización operativa)</div>
        </div>

        <div class="ap-table-wrap">
            <table class="table table-sm table-hover ap-table mb-0" id="tblPedidos">
                <thead>
                    <tr>
                        <th>Acciones</th>
                        <th>ID</th>
                        <th>Folio</th>
                        <th>Fecha Pedido</th>
                        <th>Fecha Entrega</th>
                        <th>Horario Entrega</th>
                        <th>Desde</th>
                        <th>Hasta</th>
                        <th>Tipo</th>
                        <th>Ruta</th>
                        <th>Cliente</th>
                        <th>Razón Social</th>
                        <th>Almacén</th>
                        <th>Partidas</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="17" class="text-center text-muted p-4">
                            Sin resultados con los filtros actuales.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <?php
                            $st = trim((string)($r['status'] ?? ''));
                            $badgeClass = $st === 'A' ? 'ap-badge ap-badge-a' : ($st === 'C' ? 'ap-badge ap-badge-c' : 'ap-badge ap-badge-x');

                            $tipoLabel = strtoupper(trim((string)($r['TipoPedido'] ?? '')));
                            if ($tipoLabel === 'CLIENTE') $tipoShow = 'Pedido Cliente';
                            elseif ($tipoLabel === 'RUTA') $tipoShow = 'Pedido Ruta';
                            else $tipoShow = ($r['TipoPedido'] ?? '');

                            $fecPed = ymd_to_dmy($r['Fec_Pedido'] ?? '');
                            $fecEnt = ymd_to_dmy($r['Fec_Entrega'] ?? '');

                            $hor = trim((string)($r['rango_hora'] ?? ''));
                            [$hor_desde, $hor_hasta] = split_rango($hor);
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex gap-1">
                                    <a class="btn btn-outline-primary btn-sm"
                                       href="pedido_edit.php?id_pedido=<?php echo (int)$r['id_pedido']; ?>">
                                        Ver / editar
                                    </a>

                                    <a class="btn btn-outline-secondary btn-sm"
                                       href="pedido_pdf.php?id_pedido=<?php echo (int)$r['id_pedido']; ?>"
                                       target="_blank">
                                        PDF
                                    </a>

                                    <a class="btn btn-outline-secondary btn-sm"
                                       href="pedido_pdf_sin_costos.php?id_pedido=<?php echo (int)$r['id_pedido']; ?>"
                                       target="_blank">
                                        PDF s/c
                                    </a>
                                </div>
                            </td>

                            <td><?php echo (int)$r['id_pedido']; ?></td>

                            <td>
                                <a href="pedido_edit.php?id_pedido=<?php echo (int)$r['id_pedido']; ?>">
                                    <?php echo h($r['Fol_folio'] ?? ''); ?>
                                </a>
                            </td>

                            <td><?php echo h($fecPed); ?></td>
                            <td><?php echo h($fecEnt); ?></td>

                            <td><?php echo h($hor); ?></td>
                            <td><?php echo h($hor_desde); ?></td>
                            <td><?php echo h($hor_hasta); ?></td>

                            <td><?php echo h($tipoShow); ?></td>
                            <td><?php echo h($r['ruta'] ?? ''); ?></td>

                            <td><?php echo h($r['Cve_clte'] ?? ''); ?></td>
                            <td><?php echo h($r['cliente_nombre'] ?? ''); ?></td>

                            <td><?php echo h($r['almacen_nombre'] ?? ''); ?></td>
                            <td class="text-end"><?php echo number_format((int)($r['partidas'] ?? 0)); ?></td>

                            <td><span class="<?php echo $badgeClass; ?>"><?php echo h($st); ?></span></td>

                            <td class="text-end"><?php echo number_format((float)($r['Tot_Factura'] ?? 0), 2); ?></td>
                            <td><?php echo h($r['Cve_Usuario'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Tipo Pedido -->
<div class="modal fade" id="mdlTipoPedido" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:14px;">
      <div class="modal-header">
        <h6 class="modal-title" style="font-weight:900;color:#0F5AAD;">Nuevo Pedido</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="text-muted" style="font-size:10px;">
            Selecciona el tipo de pedido y la ventana de entrega para habilitar el flujo operativo correcto.
        </div>

        <div class="mt-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="tipo_pedido" id="tp1" value="CLIENTE" checked>
              <label class="form-check-label" for="tp1">Pedido Cliente</label>
            </div>

            <div class="form-check mt-2">
              <input class="form-check-input" type="radio" name="tipo_pedido" id="tp2" value="RUTA">
              <label class="form-check-label" for="tp2">Pedido Ruta</label>
            </div>
        </div>

        <!-- NUEVO: Select horario entrega -->
        <div class="mt-3">
            <label class="ap-label">Horario Entrega (Desde - Hasta)</label>
            <select class="form-select" id="selRangoHora">
                <option value="">(sin horario)</option>
                <option value="08:00-12:00">08:00 - 12:00</option>
                <option value="12:00-16:00">12:00 - 16:00</option>
                <option value="16:00-20:00">16:00 - 20:00</option>
                <option value="08:00-16:00">08:00 - 16:00</option>
                <option value="09:00-18:00">09:00 - 18:00</option>
            </select>
            <div class="text-muted mt-1" style="font-size:10px;">
                Se enviará al formulario del pedido como <b>rango_hora</b>.
            </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-ap-primary" onclick="crearPedido()">Continuar</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net@2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@2.1.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-fixedheader@4.0.1/js/dataTables.fixedHeader.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-fixedheader-bs5@4.0.1/js/fixedHeader.bootstrap5.min.js"></script>

<script>
let dtPedidos = null;

function crearPedido(){
    const tipo = document.querySelector('input[name="tipo_pedido"]:checked')?.value || 'CLIENTE';
    const rango = document.getElementById('selRangoHora')?.value || '';

    const url = new URL(window.location.origin + window.location.pathname.replace('registro_pedidos.php','') + 'pedido_edit.php');
    url.searchParams.set('tipo', tipo);
    if (rango) url.searchParams.set('rango_hora', rango);

    window.location.href = url.toString();
}

function isValidDMY(v){
    if(!v) return true;
    return /^\d{1,2}\/\d{1,2}\/\d{4}$/.test(v.trim());
}

$(function(){
    // DataTable con 25 registros, scroll vertical/horizontal, 1 línea
    dtPedidos = new DataTable('#tblPedidos', {
        pageLength: 25,
        lengthChange: false,
        searching: true,
        ordering: true,
        info: true,
        scrollX: true,
        scrollY: '58vh',
        scrollCollapse: true,
        fixedHeader: true,
        language: {
            search: "Buscar en grilla:",
            info: "Mostrando _START_ a _END_ de _TOTAL_",
            infoEmpty: "Sin registros",
            zeroRecords: "No se encontraron coincidencias",
            paginate: { previous: "Anterior", next: "Siguiente" }
        }
    });

    // Buscador superior ahora también filtra la grilla en vivo (sin esperar Filtrar)
    const $q = $('#q_buscar');
    let t = null;
    $q.on('input', function(){
        clearTimeout(t);
        const val = this.value || '';
        t = setTimeout(() => {
            if (dtPedidos) dtPedidos.search(val).draw();
        }, 120);
    });

    // Validación ligera de fechas dd/mm/aaaa en UI (no rompe, sólo alerta)
    $('#f_desde,#f_hasta').on('blur', function(){
        const v = (this.value || '').trim();
        if(v && !isValidDMY(v)){
            alert('Formato de fecha inválido. Usa dd/mm/aaaa');
            this.focus();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
