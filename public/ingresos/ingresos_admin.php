<?php
// public/procesos/ingresos_admin.php
// Administración de Ingresos (OC, RL, CrossDocking) – UI AssistPro

if (isset($_GET['ajax'])) {
    // Placeholder de API: aquí luego se conectará a la BD real
    require_once __DIR__ . '/../../app/db.php';
    header('Content-Type: application/json; charset=utf-8');
    $act = $_GET['ajax'] ?? '';

    try {
        $pdo = db_pdo(); // función estándar de AssistPro
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'error' => 'Sin conexión a BD: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($act === 'empresas') {
        // TODO: reemplazar por SELECT real (c_compania / c_empresa)
        echo json_encode([
            'ok'   => true,
            'data' => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($act === 'almacenes') {
        // TODO: reemplazar por SELECT real (c_almacenp)
        echo json_encode([
            'ok'   => true,
            'data' => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($act === 'buscar') {
        // TODO: adaptar a th_entrada / vistas reales
        echo json_encode([
            'ok'   => true,
            'data' => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($act === 'postear') {
        // TODO: lógica de posteo a Kardex (SP)
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
        height: 32px;
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
        background-color: #f5f5f5;
        border-color: #ced4da;
        color: #333;
        font-size: 12px;
        padding: 5px 14px;
        border-radius: 4px;
    }
    .ap-btn-oc {
        background-color: #00A3E0;
        border-color: #00A3E0;
        color: #fff;
        font-size: 11px;
        padding: 4px 12px;
        border-radius: 4px;
    }
    .ap-btn-export {
        background-color: #6c757d;
        border-color: #6c757d;
        color: #fff;
        font-size: 11px;
        padding: 4px 12px;
        border-radius: 4px;
    }
    .ap-btn-report {
        background-color: #17a2b8;
        border-color: #17a2b8;
        color: #fff;
        font-size: 11px;
        padding: 4px 12px;
        border-radius: 4px;
    }
    .ap-btn-import {
        background-color: #ffc107;
        border-color: #ffc107;
        color: #212529;
        font-size: 11px;
        padding: 4px 12px;
        border-radius: 4px;
    }

    table.dataTable thead th {
        background-color: #f4f6f9;
        color: #555;
        font-size: 10px !important;
        font-weight: 600;
        padding: 6px 4px;
        border-bottom: 1px solid #dee2e6;
        white-space: nowrap;
    }
    table.dataTable tbody td {
        font-size: 10px !important;
        padding: 4px 4px;
        vertical-align: middle;
        white-space: nowrap;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        font-size: 10px;
    }
    .dataTables_wrapper .dataTables_info {
        font-size: 9px;
    }

    .badge-tipo {
        font-size: 9px;
        font-weight: 600;
        border-radius: 10px;
        padding: 2px 6px;
    }
    .badge-oc {
        background-color: #0F5AAD;
        color: #fff;
    }
    .badge-rl {
        background-color: #6c757d;
        color: #fff;
    }
    .badge-xd {
        background-color: #00A3E0;
        color: #fff;
    }
    .badge-dev {
        background-color: #dc3545;
        color: #fff;
    }
</style>

<div class="container-fluid">

    <div class="ap-card">
        <div class="ap-card-header">
            Administración de Ingresos (OC, Recepción Libre, CrossDocking)
        </div>
        <div class="ap-card-body">
            <div class="row">
                <!-- Columna principal: filtros + grilla -->
                <div class="col-lg-9 col-md-8">

                    <!-- Filtros -->
                    <div class="ap-card mb-2">
                        <div class="ap-card-body">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-3">
                                    <div class="ap-label mb-1">Tipo de ingreso</div>
                                    <div class="ap-small">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="tipo_ingreso" id="tipo_todos" value="" checked>
                                            <label class="form-check-label" for="tipo_todos">Todos</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="tipo_ingreso" id="tipo_oc" value="OC">
                                            <label class="form-check-label" for="tipo_oc">Orden de Compra</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="tipo_ingreso" id="tipo_rl" value="RL">
                                            <label class="form-check-label" for="tipo_rl">Recepción Libre</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="tipo_ingreso" id="tipo_xd" value="XD">
                                            <label class="form-check-label" for="tipo_xd">CrossDocking</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <label class="ap-label" for="cboEmpresa">Empresa</label>
                                    <select id="cboEmpresa" class="form-control ap-form-control">
                                        <option value="">Todas</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="ap-label" for="cboAlmacen">Almacén</label>
                                    <select id="cboAlmacen" class="form-control ap-form-control">
                                        <option value="">Todos</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="ap-label" for="txtBuscar">Buscar</label>
                                    <input type="text" id="txtBuscar" class="form-control ap-form-control" placeholder="Folio, proveedor, proyecto...">
                                </div>
                            </div>

                            <div class="row g-2 align-items-end mt-1">
                                <div class="col-md-3">
                                    <label class="ap-label" for="fini">Fecha inicio</label>
                                    <input type="date" id="fini" class="form-control ap-form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="ap-label" for="ffin">Fecha fin</label>
                                    <input type="date" id="ffin" class="form-control ap-form-control">
                                </div>
                                <div class="col-md-6 text-end">
                                    <button type="button" id="btnBuscar" class="btn ap-btn-primary">
                                        Buscar
                                    </button>
                                    <button type="button" id="btnNuevoOC" class="btn ap-btn-oc">
                                        Nuevo ingreso (Recepción de Materiales)
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Grilla principal -->
                    <div class="ap-card">
                        <div class="ap-card-body">
                            <table id="tblIngresos" class="table table-striped table-bordered table-sm" style="width:100%;">
                                <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Fecha</th>
                                    <th>Tipo</th>
                                    <th>Estatus</th>
                                    <th>Empresa</th>
                                    <th>Almacén</th>
                                    <th class="text-end">Total Pzas</th>
                                    <th class="text-end">Total Importe</th>
                                    <th>Usuario</th>
                                    <th>Acciones</th>
                                </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- Columna lateral: Reportes + Utilerías -->
                <div class="col-lg-3 col-md-4">

                    <div class="ap-card mb-2">
                        <div class="ap-card-header">
                            Reportes
                        </div>
                        <div class="ap-card-body ap-small">
                            <p class="mb-2">Documentos PDF y exportaciones de ingresos.</p>
                            <button type="button" class="btn ap-btn-report w-100 mb-1" id="btnRepPdf">
                                Reporte PDF de ingresos
                            </button>
                            <button type="button" class="btn ap-btn-export w-100 mb-1" id="btnExportExcel">
                                Exportar a Excel / CSV
                            </button>
                        </div>
                    </div>

                    <div class="ap-card">
                        <div class="ap-card-header">
                            Utilerías
                        </div>
                        <div class="ap-card-body ap-small">
                            <p class="mb-2">Herramientas de importación / mantenimiento.</p>
                            <button type="button" class="btn ap-btn-import w-100 mb-1" id="btnImportIngresos">
                                Importador de ingresos
                            </button>
                            <button type="button" class="btn ap-btn-secondary w-100 mb-1" id="btnUtilOtros">
                                Otras utilerías (futuro)
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

</div>

<script>
let dtIngresos = null;

$(document).ready(function () {
    initIngresosAdmin();
});

function initIngresosAdmin() {
    cargarEmpresas();
    cargarAlmacenes();
    initGrilla();

    $('#btnBuscar').on('click', function () {
        buscarIngresos();
    });

    $('#btnNuevoOC').on('click', function () {
        // Enlazar a Recepción de Materiales (pantalla de ingresos POS)
        window.location.href = 'recepcion_materiales.php';
    });

    // Placeholders para reportes / utilerías
    $('#btnRepPdf').on('click', function () {
        alert('La generación de reportes PDF se implementará en el módulo de Reportes.');
    });
    $('#btnExportExcel').on('click', function () {
        alert('La exportación se implementará en el módulo de Reportes.');
    });
    $('#btnImportIngresos').on('click', function () {
        alert('El importador se implementará en el módulo de Utilerías.');
    });
    $('#btnUtilOtros').on('click', function () {
        alert('Utilería pendiente de definir.');
    });
}

function initGrilla() {
    dtIngresos = $('#tblIngresos').DataTable({
        paging: true,
        pageLength: 25,
        lengthChange: false,
        searching: false,
        ordering: false,
        info: true,
        scrollX: true,
        autoWidth: false,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-MX.json'
        }
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
    $.getJSON('ingresos_admin.php', {ajax: 'almacenes'}, function (r) {
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
    const tipo = (t || '').toUpperCase();
    if (tipo === 'OC') return '<span class="badge badge-tipo badge-oc">OC</span>';
    if (tipo === 'RL') return '<span class="badge badge-tipo badge-rl">RL</span>';
    if (tipo === 'XD') return '<span class="badge badge-tipo badge-xd">XD</span>';
    if (tipo === 'DEV') return '<span class="badge badge-tipo badge-dev">DEV</span>';
    return '<span class="badge badge-tipo">' + (tipo || '-') + '</span>';
}

function renderAcciones(id) {
    if (!id) id = '';
    return '' +
        '<button type="button" class="btn btn-xs btn-outline-secondary me-1" title="Ver detalle" onclick="verIngreso(' + id + ')">' +
        '<i class="fa fa-search"></i>' +
        '</button>' +
        '<button type="button" class="btn btn-xs btn-outline-success" title="Postear a Kardex" onclick="postearIngreso(' + id + ')">' +
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
