<?php
require_once __DIR__ . '/../../bi/_menu_global.php';
$TITLE = 'Envío de Rutas';
?>

<style>
    .ap-card {
        background: #ffffff;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.12);
        border: 1px solid #e1e5eb;
        margin-bottom: 10px;
    }
    .ap-card-header {
        background: #0F5AAD;
        color: #ffffff;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 600;
        border-radius: 8px 8px 0 0;
    }
    .ap-card-body {
        padding: 8px 10px;
        font-size: 11px;
    }
    .ap-label {
        font-size: 10px;
        font-weight: 600;
        color: #555;
        margin-bottom: 2px;
    }
    .ap-form-control {
        font-size: 11px;
        height: 28px;
        padding: 2px 6px;
    }
    .ap-btn {
        font-size: 11px;
        padding: 4px 10px;
    }
    #tabla_rutas {
        font-size: 10px;
        width: 100% !important;
        white-space: nowrap;
    }
    .ap-summary {
        font-size: 11px;
        margin-bottom: 5px;
    }
    .ap-message {
        font-size: 11px;
        margin-top: 5px;
        display: none;
    }
</style>

<div class="container-fluid mt-2">
    <!-- Filtros -->
    <div class="ap-card">
        <div class="ap-card-header">
            Filtros para Envío de Rutas
        </div>
        <div class="ap-card-body">
            <form id="formFiltros" class="mb-0">
                <div class="row align-items-end">
                    <div class="col-md-2">
                        <label class="ap-label" for="f_empresa">Empresa</label>
                        <select id="f_empresa" name="empresa" class="form-control ap-form-control">
                            <option value="">[Todas]</option>
                            <!-- Llenar vía filtros_assistpro.php o API -->
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="ap-label" for="f_almacen">Almacén</label>
                        <select id="f_almacen" name="almacen" class="form-control ap-form-control">
                            <option value="">[Todos]</option>
                            <!-- Llenar vía filtros_assistpro.php o API -->
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="ap-label" for="f_fecha">Fecha operación</label>
                        <input type="date" id="f_fecha" name="fecha" class="form-control ap-form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="ap-label" for="f_tipo_ruta">Tipo de ruta</label>
                        <select id="f_tipo_ruta" name="tipo_ruta" class="form-control ap-form-control">
                            <option value="">[Todos]</option>
                            <option value="ENTREGA">Entrega</option>
                            <option value="VENTA">Venta</option>
                            <option value="REPARTO">Reparto</option>
                            <!-- Ajustar catálogo real -->
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="button" id="btnAplicarFiltros" class="btn btn-primary ap-btn">
                            <i class="fa fa-filter"></i> Aplicar filtros
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Grilla de rutas -->
    <div class="ap-card">
        <div class="ap-card-header">
            Rutas disponibles para envío
        </div>
        <div class="ap-card-body">
            <div class="ap-summary">
                Rutas encontradas: <span id="lbl_total_rutas">0</span> |
                Rutas seleccionadas: <span id="lbl_rutas_sel">0</span>
            </div>
            <div class="table-responsive">
                <table id="tabla_rutas" class="table table-sm table-striped table-bordered">
                    <thead>
                    <tr>
                        <th style="width:30px;">
                            <input type="checkbox" id="chk_all">
                        </th>
                        <th>Empresa</th>
                        <th>Almacén</th>
                        <th>Ruta</th>
                        <th>Descripción</th>
                        <th>Día</th>
                        <th>Total clientes</th>
                        <th>Estatus</th>
                        <th>Último envío</th>
                    </tr>
                    </thead>
                    <tbody>
                    <!-- Llenar vía DataTables / AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Acciones -->
    <div class="ap-card">
        <div class="ap-card-header">
            Envío de rutas a dispositivos
        </div>
        <div class="ap-card-body">
            <div class="row align-items-center">
                <div class="col-md-3 mb-1">
                    <button type="button" id="btnEnviarRutas" class="btn btn-success ap-btn">
                        <i class="fa fa-paper-plane"></i> Enviar rutas seleccionadas
                    </button>
                </div>
                <div class="col-md-9">
                    <div id="msg_box" class="alert ap-message mb-0" role="alert"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>

<script>
    // Ajusta estas rutas a tus APIs reales
    const API_RUTAS_LIST   = '../../api/api_rutas_envio.php?action=list';
    const API_RUTAS_SEND   = '../../api/api_rutas_envio.php?action=send';
    const API_FILTROS      = '../../api/filtros_assistpro.php';

    let tablaRutas = null;

    $(document).ready(function () {
        // Inicializar combos de empresa/almacen (ejemplo; ajusta según tu filtros_assistpro.php)
        cargarEmpresas();
        $('#f_empresa').on('change', function() {
            cargarAlmacenes($(this).val());
        });

        // Inicializar DataTable
        tablaRutas = $('#tabla_rutas').DataTable({
            processing: true,
            serverSide: false, // si tienes API server-side, cambia a true y configura ajax
            paging: true,
            pageLength: 25,
            lengthChange: false,
            scrollX: true,
            scrollY: '50vh',
            scrollCollapse: true,
            order: [[3, 'asc']], // Orden por Ruta
            columns: [
                {
                    data: null,
                    orderable: false,
                    render: function (data, type, row) {
                        return '<input type="checkbox" class="chk_ruta" value="' + row.id_ruta + '">';
                    }
                },
                { data: 'empresa' },
                { data: 'almacen' },
                { data: 'ruta_codigo' },
                { data: 'ruta_descripcion' },
                { data: 'dia' },
                { data: 'total_clientes' },
                { data: 'estatus' },
                { data: 'ultimo_envio' }
            ]
        });

        // Seleccionar / deseleccionar todos
        $('#chk_all').on('change', function() {
            const checked = $(this).is(':checked');
            $('.chk_ruta').prop('checked', checked);
            actualizarSeleccionadas();
        });

        // Cambios individuales
        $('#tabla_rutas tbody').on('change', '.chk_ruta', function () {
            actualizarSeleccionadas();
        });

        // Filtros
        $('#btnAplicarFiltros').on('click', function () {
            cargarRutas();
        });

        // Enviar rutas
        $('#btnEnviarRutas').on('click', function () {
            enviarRutasSeleccionadas();
        });

        // Carga inicial (sin filtros o con valores default)
        cargarRutas();
    });

    function mostrarMensaje(tipo, texto) {
        const $box = $('#msg_box');
        $box.removeClass('alert-success alert-danger alert-info')
            .addClass('alert-' + tipo)
            .text(texto)
            .show();
    }

    function limpiarMensaje() {
        $('#msg_box').hide().text('');
    }

    function actualizarSeleccionadas() {
        const totalSel = $('.chk_ruta:checked').length;
        $('#lbl_rutas_sel').text(totalSel);
    }

    function cargarRutas() {
        limpiarMensaje();
        $('#lbl_rutas_sel').text('0');
        $('#lbl_total_rutas').text('0');
        $('#chk_all').prop('checked', false);

        const params = {
            empresa: $('#f_empresa').val(),
            almacen: $('#f_almacen').val(),
            fecha:   $('#f_fecha').val(),
            tipo_ruta: $('#f_tipo_ruta').val()
        };

        $.ajax({
            url: API_RUTAS_LIST,
            method: 'GET',
            data: params,
            dataType: 'json'
        }).done(function (resp) {
            if (!resp.ok) {
                mostrarMensaje('danger', resp.error || 'Error al obtener rutas.');
                tablaRutas.clear().draw();
                return;
            }
            const data = resp.data || [];
            tablaRutas.clear();
            tablaRutas.rows.add(data).draw();
            $('#lbl_total_rutas').text(data.length);
        }).fail(function (xhr) {
            console.error(xhr.responseText);
            mostrarMensaje('danger', 'Error de comunicación con el servidor al listar rutas.');
        });
    }

    function enviarRutasSeleccionadas() {
        limpiarMensaje();
        const seleccionadas = [];
        $('.chk_ruta:checked').each(function () {
            seleccionadas.push($(this).val());
        });

        if (seleccionadas.length === 0) {
            mostrarMensaje('info', 'No hay rutas seleccionadas para enviar.');
            return;
        }

        $.ajax({
            url: API_RUTAS_SEND,
            method: 'POST',
            data: {
                rutas: seleccionadas
            },
            dataType: 'json'
        }).done(function (resp) {
            if (!resp.ok) {
                mostrarMensaje('danger', resp.error || 'Ocurrió un error al enviar las rutas.');
                return;
            }
            mostrarMensaje('success', resp.message || 'Rutas enviadas correctamente.');
            // Opcional: recargar grilla para refrescar estatus / último envío
            cargarRutas();
        }).fail(function (xhr) {
            console.error(xhr.responseText);
            mostrarMensaje('danger', 'Error de comunicación con el servidor al enviar rutas.');
        });
    }

    // === Ejemplo de uso de filtros_assistpro.php, ajusta según tu API real ===
    function cargarEmpresas() {
        $.ajax({
            url: API_FILTROS,
            method: 'GET',
            data: { action: 'empresas' },
            dataType: 'json'
        }).done(function (resp) {
            if (!resp.ok) return;
            const $cmb = $('#f_empresa');
            $cmb.empty().append('<option value="">[Todas]</option>');
            (resp.data || []).forEach(function (e) {
                $cmb.append('<option value="' + e.cve_empresa + '">' + e.nombre + '</option>');
            });
        }).fail(function (xhr) {
            console.error('Error cargando empresas', xhr.responseText);
        });
    }

    function cargarAlmacenes(empresa) {
        const $cmb = $('#f_almacen');
        $cmb.empty().append('<option value="">[Todos]</option>');
        if (!empresa) return;

        $.ajax({
            url: API_FILTROS,
            method: 'GET',
            data: { action: 'almacenes', empresa: empresa },
            dataType: 'json'
        }).done(function (resp) {
            if (!resp.ok) return;
            (resp.data || []).forEach(function (a) {
                $cmb.append('<option value="' + a.cve_almacen + '">' + a.nombre + '</option>');
            });
        }).fail(function (xhr) {
            console.error('Error cargando almacenes', xhr.responseText);
        });
    }
</script>
