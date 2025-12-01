<?php
// public/procesos/ingresos_admin.php
// Administración de Ingresos (OC, RL, CrossDocking) – UI AssistPro

if (isset($_GET['ajax'])) {
    require_once __DIR__ . '/../../app/db.php';
    header('Content-Type: application/json; charset=utf-8');
    $act = $_GET['ajax'] ?? '';

    try {
        $pdo = db_pdo();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'error' => 'Sin conexión a BD: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($act === 'empresas') {
        try {
            $rows = $pdo->query(
                "SELECT DISTINCT 
                        empresa_id AS id,
                        empresa_id,
                        CONCAT('Empresa ', empresa_id) AS nombre
                 FROM c_almacenp
                 WHERE empresa_id IS NOT NULL
                 ORDER BY empresa_id"
            )->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'error' => 'Error al cargar empresas: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if ($act === 'almacenes') {
        $empresaId = $_GET['empresa_id'] ?? '';
        $sql = "SELECT empresa_id, id, clave AS cve_almac, clave, nombre 
                FROM c_almacenp
                WHERE 1=1";
        $params = [];
        if ($empresaId !== '') {
            $sql .= " AND empresa_id = :emp";
            $params[':emp'] = $empresaId;
        }
        $sql .= " ORDER BY clave";

        try {
            $st = $pdo->prepare($sql);
            $st->execute($params);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'error' => 'Error al cargar almacenes: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if ($act === 'buscar') {
        $tipo       = $_GET['tipo']       ?? '';
        $empresaId  = $_GET['empresa_id'] ?? '';
        $almacenId  = $_GET['almacen_id'] ?? '';
        $q          = trim($_GET['q'] ?? '');
        $fini       = $_GET['fini'] ?? '';
        $ffin       = $_GET['ffin'] ?? '';

        $where  = [];
        $params = [];

        if ($tipo !== '') {
            $where[] = 'h.tipo = :tipo';
            $params[':tipo'] = $tipo;
        }
        if ($empresaId !== '') {
            $where[] = 'ap.empresa_id = :emp';
            $params[':emp'] = $empresaId;
        }
        if ($almacenId !== '') {
            // Puede llegar id o clave; se compara contra ambos + Cve_Almac del encabezado
            $where[] = '(ap.id = :alm OR ap.clave = :alm OR h.Cve_Almac = :alm)';
            $params[':alm'] = $almacenId;
        }
        if ($fini !== '') {
            $where[] = 'DATE(h.Fec_Entrada) >= :fini';
            $params[':fini'] = $fini;
        }
        if ($ffin !== '') {
            $where[] = 'DATE(h.Fec_Entrada) <= :ffin';
            $params[':ffin'] = $ffin;
        }
        if ($q !== '') {
            $where[] = '(h.Fol_Folio LIKE :q OR h.Proveedor LIKE :q OR h.Proyecto LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "SELECT 
                    h.Fol_Folio AS id,
                    h.Fol_Folio AS folio,
                    DATE(h.Fec_Entrada) AS fecha,
                    h.tipo AS tipo_entrada,
                    h.STATUS AS estatus,
                    ap.empresa_id,
                    CONCAT('Empresa ', ap.empresa_id) AS empresa_nombre,
                    ap.clave AS cve_almac,
                    ap.nombre AS almacen_nombre,
                    COALESCE(SUM(d.CantidadRecibida), 0) AS total_pzas,
                    COALESCE(SUM(d.CantidadRecibida * COALESCE(d.costoUnitario,0) * (1 + COALESCE(d.IVA,0)/100)), 0) AS total_importe,
                    h.Cve_Usuario AS usuario_crea
                FROM th_entalmacen h
                LEFT JOIN td_entalmacen d ON d.fol_folio = h.Fol_Folio
                LEFT JOIN c_almacenp ap   ON ap.clave = h.Cve_Almac
                $sqlWhere
                GROUP BY 
                    h.Fol_Folio, h.Fec_Entrada, h.tipo, h.STATUS,
                    ap.empresa_id, ap.clave, ap.nombre, h.Cve_Usuario
                ORDER BY h.Fec_Entrada DESC, h.Fol_Folio DESC
                LIMIT 500";

        try {
            $st = $pdo->prepare($sql);
            $st->execute($params);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'error' => 'Error al buscar ingresos: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if ($act === 'postear') {
        // TODO: lógica real de posteo a Kardex (SP)
        echo json_encode(['ok' => false, 'error' => 'Posteo no implementado en esta fase'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['ok' => false, 'error' => 'Acción no reconocida'], JSON_UNESCAPED_UNICODE);
    exit;
}

$TITLE = 'Administración de Ingresos';
require_once __DIR__ . '/../bi/_menu_global.php';
?>
<style>
    .ap-card {
        background: #ffffff;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.12);
        border: 1px solid #e1e5eb;
        margin-bottom: 15px;
    }
    .ap-card-header {
        background: #0F5AAD;
        color: #ffffff;
        padding: 8px 12px;
        font-size: 13px;
        font-weight: 600;
        border-radius: 8px 8px 0 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .ap-card-title {
        margin: 0;
    }
    .ap-card-subtitle {
        font-size: 11px;
        opacity: 0.85;
    }
    .ap-card-body {
        padding: 12px;
    }
    .ap-label {
        font-size: 11px;
        font-weight: 600;
        color: #555;
        margin-bottom: 2px;
    }
    .ap-form-control {
        font-size: 12px;
        height: 30px;
        padding: 4px 8px;
    }
    .ap-small {
        font-size: 10px;
    }

    .ap-btn-primary {
        background-color: #0F5AAD;
        border-color: #0F5AAD;
        font-size: 12px;
        padding: 5px 14px;
        border-radius: 4px;
        color: #fff;
    }
    
    .ap-btn-secondary {
        background-color: #ffffff;
        border-color: #0F5AAD;
        color: #0F5AAD;
        font-size: 12px;
        padding: 5px 14px;
        border-radius: 4px;
    }
    .ap-btn-secondary:hover {
        background-color: #0F5AAD;
        color: #ffffff;
    }

    .ap-badge-status {
        font-size: 10px;
        border-radius: 999px;
        padding: 3px 8px;
    }

    .ap-pill-radio label {
        margin-right: 4px;
        cursor: pointer;
        border-radius: 999px;
    }

    .ap-pill-radio input[type="radio"] {
        display: none;
    }

    .ap-pill-radio .btn {
        font-size: 11px;
        padding: 3px 8px;
    }

    .ap-pill-radio .btn-check:checked + .btn-outline-primary {
        background-color: #0F5AAD;
        color: #ffffff;
    }

    .ap-filter-hint {
        font-size: 10px;
        color: #777;
    }

    .table-ingresos thead th {
        background-color: #f4f6f9;
        font-size: 11px;
        font-weight: 600;
        color: #555;
    }

    .table-ingresos tbody td {
        font-size: 11px;
        vertical-align: middle;
        padding-top: 4px;
        padding-bottom: 4px;
    }

    .ap-badge-tipo {
        font-size: 10px;
        border-radius: 999px;
    }

    .ap-btn-postear {
        font-size: 11px;
        padding: 2px 6px;
        border-radius: 999px;
    }

    .ap-legend {
        font-size: 10px;
        color: #777;
    }

    .dataTables_filter {
        display: none;
    }

    .dataTables_length label,
    .dataTables_info,
    .dataTables_paginate {
        font-size: 11px;
    }

    .dataTables_paginate .paginate_button {
        padding: 3px 8px !important;
        font-size: 11px;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #0F5AAD;
        border-color: #0F5AAD;
        color: #fff !important;
    }

    .ap-toggle-columnas .dropdown-menu {
        font-size: 11px;
        min-width: 220px;
    }
</style>

<div class="container-fluid py-3">
    <div class="ap-card">
        <div class="ap-card-header">
            <div>
                <h5 class="ap-card-title mb-0">Administración de Ingresos</h5>
                <div class="ap-card-subtitle">
                    Control de ingresos por OC, RL, CrossDocking y otros tipos de entrada.
                </div>
            </div>
            <div class="text-end">
                <button type="button" class="btn btn-sm ap-btn-secondary" id="btnRefrescar">
                    <i class="fa fa-sync-alt"></i> Refrescar
                </button>
            </div>
        </div>
        <div class="ap-card-body">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-2">
                    <label class="ap-label">Empresa</label>
                    <select class="form-select ap-form-control" id="cboEmpresa"></select>
                    <div class="ap-filter-hint">Determina el universo de almacenes.</div>
                </div>
                <div class="col-12 col-md-2">
                    <label class="ap-label">Almacén</label>
                    <select class="form-select ap-form-control" id="cboAlmacen"></select>
                    <div class="ap-filter-hint">Filtra ingresos por almacén.</div>
                </div>

                <div class="col-12 col-md-3">
                    <label class="ap-label">Rango de fechas (Fec. Entrada)</label>
                    <div class="d-flex gap-1">
                        <input type="date" class="form-control ap-form-control" id="fini">
                        <input type="date" class="form-control ap-form-control" id="ffin">
                    </div>
                </div>

                <div class="col-12 col-md-3">
                    <label class="ap-label">Buscar</label>
                    <input type="text" id="txtBuscar" class="form-control ap-form-control" placeholder="Folio, proveedor, proyecto...">
                </div>

                <div class="col-12 col-md-2">
                    <label class="ap-label">Acciones</label>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-sm ap-btn-primary w-100" id="btnBuscar">
                            <i class="fa fa-search"></i> Buscar
                        </button>
                    </div>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-12 col-md-6">
                    <label class="ap-label">Tipo de ingreso</label>
                    <div class="btn-group ap-pill-radio" role="group" aria-label="Tipo de ingreso">
                        <input type="radio" class="btn-check" name="tipo_ingreso" id="tipoTodos" value="" checked>
                        <label class="btn btn-outline-primary btn-sm" for="tipoTodos">Todos</label>

                        <input type="radio" class="btn-check" name="tipo_ingreso" id="tipoOC" value="OC">
                        <label class="btn btn-outline-primary btn-sm" for="tipoOC">OC</label>

                        <input type="radio" class="btn-check" name="tipo_ingreso" id="tipoRL" value="RL">
                        <label class="btn btn-outline-primary btn-sm" for="tipoRL">RL</label>

                        <input type="radio" class="btn-check" name="tipo_ingreso" id="tipoCD" value="CD">
                        <label class="btn btn-outline-primary btn-sm" for="tipoCD">CrossDocking</label>

                        <input type="radio" class="btn-check" name="tipo_ingreso" id="tipoTR" value="TR">
                        <label class="btn btn-outline-primary btn-sm" for="tipoTR">Traslados</label>
                    </div>
                </div>
                <div class="col-12 col-md-6 text-md-end mt-2 mt-md-0">
                    <span class="ap-legend">
                        <span class="badge bg-secondary ap-badge-status">Abierto</span> pendiente de posteo •
                        <span class="badge bg-success ap-badge-status">Cerrado</span> ya posteado a Kardex
                    </span>
                </div>
            </div>

            <hr class="my-2">

            <div class="d-flex justify-content-between align-items-center mb-1">
                <div class="ap-filter-hint">
                    Resultados limitados a 500 ingresos. Ajusta filtros para precisar la consulta.
                </div>
                <div class="dropdown ap-toggle-columnas">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        Columnas
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" id="menuColumnas"></ul>
                </div>
            </div>

            <div class="table-responsive">
                <table id="tblIngresos" class="table table-striped table-bordered table-hover table-ingresos w-100">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Estatus</th>
                            <th>Empresa</th>
                            <th>Almacén</th>
                            <th class="text-end">Total piezas</th>
                            <th class="text-end">Importe total</th>
                            <th>Usuario</th>
                            <th style="width: 70px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<script>
let dtIngresos = null;

$(document).ready(function () {
    dtIngresos = $('#tblIngresos').DataTable({
        paging: true,
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        ordering: true,
        info: true,
        searching: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-MX.json'
        },
        columnDefs: [
            { targets: [6,7], className: 'text-end' },
            { targets: [9], orderable: false }
        ]
    });

    construirMenuColumnas();
    cargarEmpresas();
    cargarAlmacenes();
    buscarIngresos();

    $('#btnBuscar').on('click', function () {
        buscarIngresos();
    });

    $('#btnRefrescar').on('click', function () {
        buscarIngresos();
    });

    $('#cboEmpresa').on('change', function () {
        cargarAlmacenes();
    });

    // Buscar con Enter en el texto
    $('#txtBuscar').on('keyup', function (e) {
        if (e.key === 'Enter') {
            buscarIngresos();
        }
    });
});

function construirMenuColumnas() {
    const tbl = $('#tblIngresos').DataTable();
    const headerCells = $('#tblIngresos thead th');
    const $menu = $('#menuColumnas');
    $menu.empty();

    headerCells.each(function (i) {
        const colTitle = $(this).text();
        const checked = tbl.column(i).visible() ? 'checked' : '';
        const item = `
            <li>
                <label class="dropdown-item">
                    <input type="checkbox" class="form-check-input me-1" data-col="${i}" ${checked}>
                    ${colTitle}
                </label>
            </li>
        `;
        $menu.append(item);
    });

    $menu.find('input[type="checkbox"]').on('change', function () {
        const colIndex = $(this).data('col');
        const visible = $(this).is(':checked');
        $('#tblIngresos').DataTable().column(colIndex).visible(visible);
    });
}

function cargarEmpresas() {
    $.getJSON('ingresos_admin.php', {ajax: 'empresas'}, function (r) {
        if (!r || !r.ok) return;
        const $cbo = $('#cboEmpresa');
        $cbo.empty().append('<option value="">Todas</option>');
        (r.data || []).forEach(row => {
            $cbo.append(
                $('<option>', {
                    value: row.id || row.empresa_id || '',
                    text: row.nombre || row.descripcion || ''
                })
            );
        });
    });
}

function cargarAlmacenes() {
    $.getJSON('ingresos_admin.php', {ajax: 'almacenes', empresa_id: $('#cboEmpresa').val()}, function (r) {
        if (!r || !r.ok) return;
        const $cbo = $('#cboAlmacen');
        $cbo.empty().append('<option value="">Todos</option>');
        (r.data || []).forEach(row => {
            $cbo.append(
                $('<option>', {
                    value: row.id || row.cve_almac || '',
                    text: (row.clave || row.cve_almac || '') + ' - ' + (row.nombre || row.des_almac || '')
                })
            );
        });
    });
}

function buscarIngresos() {
    if (!dtIngresos) return;

    const tipo = $('input[name="tipo_ingreso"]:checked').val() || '';
    const empresa_id = $('#cboEmpresa').val();
    const almacen_id = $('#cboAlmacen').val();
    const q = $('#txtBuscar').val();
    const fini = $('#fini').val();
    const ffin = $('#ffin').val();

    $.getJSON('ingresos_admin.php', {
        ajax: 'buscar',
        tipo: tipo,
        empresa_id: empresa_id,
        almacen_id: almacen_id,
        q: q,
        fini: fini,
        ffin: ffin
    }, function (r) {
        dtIngresos.clear();
        if (!r || !r.ok) {
            dtIngresos.draw();
            return;
        }
        (r.data || []).forEach(row => {
            const tipoHtml = renderTipo(row.tipo_entrada || '');
            dtIngresos.row.add([
                row.folio || '',
                row.fecha || '',
                tipoHtml,
                row.estatus || '',
                row.empresa_nombre || '',
                row.almacen_nombre || '',
                Number(row.total_pzas || 0).toLocaleString(),
                Number(row.total_importe || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}),
                row.usuario_crea || '',
                renderAcciones(row.id)
            ]);
        });
        dtIngresos.draw();
    });
}

function renderTipo(t) {
    t = (t || '').toUpperCase();
    let className = 'bg-secondary';
    let text = t;

    switch (t) {
        case 'OC':
            className = 'bg-primary';
            text = 'OC';
            break;
        case 'RL':
            className = 'bg-warning text-dark';
            text = 'RL';
            break;
        case 'CD':
            className = 'bg-info text-dark';
            text = 'CD';
            break;
        case 'TR':
            className = 'bg-success';
            text = 'TR';
            break;
    }
    return '<span class="badge ap-badge-tipo ' + className + '">' + text + '</span>';
}

function renderAcciones(id) {
    return '' +
        '<button type="button" class="btn btn-outline-secondary btn-sm me-1" onclick="verIngreso(' + id + ')">' +
            '<i class="fa fa-eye"></i>' +
        '</button>' +
        '<button type="button" class="btn btn-outline-success btn-sm ap-btn-postear" onclick="postearIngreso(' + id + ')">' +
            '<i class="fa fa-check"></i>' +
        '</button>';
}

function verIngreso(id) {
    if (!id) return;
    // Enlazar después a un detalle o popup
    alert('Detalle de ingreso ID: ' + id + ' (pendiente de implementar).');
}

function postearIngreso(id) {
    if (!id) return;
    if (!confirm('¿Cerrar y postear a Kardex este ingreso?')) return;

    $.post('ingresos_admin.php?ajax=postear', {id: id}, function (r) {
        if (r && r.ok) {
            alert('Ingreso posteado correctamente (demo).');
            buscarIngresos();
        } else {
            alert('No se pudo postear.\n' + (r && r.error ? r.error : 'Error desconocido.'));
        }
    }, 'json').fail(function () {
        alert('Error de comunicación con el servidor.');
    });
}
</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
?>
