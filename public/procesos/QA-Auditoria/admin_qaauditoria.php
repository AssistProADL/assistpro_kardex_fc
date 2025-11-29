<?php
// QA Auditoría – Administración de Auditoría y Empaque
// Mantiene las mismas rutas lógicas (/api/qaauditoria/index.php)
// Adaptado al template actual AssistPro (sidebar _menu_global.php)

require_once __DIR__ . '/../bi/_menu_global.php';
?>

<div class="content p-3 p-md-4" style="font-size:10px;">
    <div class="container-fluid">

        <!-- Título -->
        <div class="row mb-3">
            <div class="col-12">
                <h5 class="mb-0 fw-bold" style="color:#0F5AAD;">
                    <i class="fa fa-clipboard-check me-2"></i>QA Auditoría
                </h5>
                <small class="text-muted">Administración de pedidos pendientes por auditar / auditados</small>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card shadow-sm mb-3">
            <div class="card-body py-2">
                <div class="row g-2 align-items-end">

                    <div class="col-12 col-md-3">
                        <label class="form-label mb-1">Status de Orden</label>
                        <select id="select-status" class="form-select form-select-sm">
                            <option value="">Seleccione un Status</option>
                            <option value="R">Auditando</option>
                            <option value="L">Pendiente de auditar</option>
                            <!-- <option value="C">Pendiente de Embarque</option> -->
                        </select>
                    </div>

                    <div class="col-12 col-md-5">
                        <label class="form-label mb-1">&nbsp;</label>
                        <div class="input-group input-group-sm">
                            <input type="text"
                                   id="input-busqueda"
                                   class="form-control"
                                   placeholder="Ingrese el # de Pedido o Folio a buscar...">
                            <button class="btn btn-primary" type="button" id="btn-buscar">
                                <i class="fa fa-search"></i> Buscar
                            </button>
                            <button class="btn btn-primary" type="button" id="btn-areas-dispo">
                                <i class="fa fa-search"></i> Áreas de Revisión Disponibles
                            </button>
                        </div>
                    </div>

                    <div class="col-12 col-md-4 text-end">
                        <!-- espacio para futuros filtros -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla principal -->
        <div class="card shadow-sm">
            <div class="card-body p-2">

                <div class="d-flex justify-content-end mb-1">
                    <label class="form-check-label" style="font-size:10px;">
                        <input type="checkbox" class="form-check-input me-1" id="chk-asignar-todo">
                        Asignar todo
                    </label>
                </div>

                <div class="table-responsive">
                    <table id="tabla-qa" class="table table-sm table-striped table-hover mb-0" style="font-size:10px; width:100%;">
                        <thead>
                        <tr>
                            <th style="width:40px;">Acción</th>
                            <th>No. pedido</th>
                            <th># Folio</th>
                            <th>Cliente</th>
                            <th># Artículos</th>
                            <th>Área de Revisión</th>
                            <th>Status</th>
                            <th style="width:80px;">Cambiar Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <!-- se llena por DataTables -->
                        </tbody>
                    </table>
                </div>

                <div class="text-end mt-2">
                    <button type="button" class="btn btn-sm btn-primary" id="btn-cambiar-status">
                        <span class="fa"></span> Cambiar a Pendiente por Auditar
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" id="btn-cambiar-auditado">
                        <span class="fa"></span> Cambiar a Auditado
                    </button>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- Librerías requeridas (si no vienen ya en layout general) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
    $(function () {

        // Inicializar DataTable
        var tabla = $('#tabla-qa').DataTable({
            pageLength: 25,
            lengthChange: false,
            ordering: false,
            searching: false,
            info: true,
            autoWidth: false,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            columns: [
                {
                    data: null,
                    className: 'text-center',
                    render: function (data, type, row) {
                        var folio = row.Fol_Folio || '';
                        return '' +
                            '<button type="button" class="btn btn-link btn-xs p-0 text-primary btn-detalle" ' +
                            'data-folio="' + folio + '" title="Ver detalle">' +
                            '<i class="fa fa-search"></i>' +
                            '</button>';
                    }
                },
                {data: 'No_pedido'},
                {data: 'Fol_Folio'},
                {data: 'Cliente'},
                {data: 'NumArticulos'},          // el API puede devolver este campo con otro nombre; ajustar si es necesario
                {data: 'AreaRevision'},
                {data: 'Status'},
                {
                    data: 'Cambiar_Status',
                    className: 'text-center',
                    render: function (data, type, row) {
                        var checked = (data === 'Yes' || data === 1 || data === '1') ? 'checked' : '';
                        return '<input type="checkbox" class="form-check-input chk-cambiar" ' + checked + '>';
                    }
                }
            ],
            data: [] // se llenará vía AJAX
        });

        // Carga inicial
        ReloadGrid();

        // Buscar
        $('#btn-buscar').on('click', function () {
            ReloadGrid();
        });

        // Áreas disponibles (placeholder de momento)
        $('#btn-areas-dispo').on('click', function () {
            console.log('Áreas de revisión disponibles – pendiente implementar.');
            // Aquí se podrá abrir un modal o consultar /api/qaauditoria/index.php con otra acción
        });

        // Asignar todo
        $('#chk-asignar-todo').on('change', function () {
            var marcado = $(this).is(':checked');
            $('#tabla-qa').find('.chk-cambiar').prop('checked', marcado);
        });

        // Extraer folios marcados
        function obtenerFoliosSeleccionados() {
            var folios = [];
            tabla.rows().every(function () {
                var rowNode = this.node();
                var $chk = $(rowNode).find('.chk-cambiar');
                if ($chk.prop('checked')) {
                    var data = this.data();
                    if (data && data.Fol_Folio) {
                        folios.push(data.Fol_Folio);
                    }
                }
            });
            return folios;
        }

        // Cambiar a Pendiente por Auditar (status = L)
        $('#btn-cambiar-status').on('click', function () {
            var folios = obtenerFoliosSeleccionados();
            if (!folios.length) {
                alert('Seleccione al menos un pedido para cambiar su status.');
                return;
            }

            $.ajax({
                url: '/api/qaauditoria/index.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'cambio_status',
                    folios: folios,
                    status: 'L'
                },
                success: function (res) {
                    console.log('Cambio de status OK', res);
                    ReloadGrid();
                },
                error: function (xhr) {
                    console.error('Error cambiando status', xhr.responseText || xhr.statusText);
                }
            });
        });

        // Cambiar a Auditado (status = P)
        $('#btn-cambiar-auditado').on('click', function () {
            var folios = obtenerFoliosSeleccionados();
            if (!folios.length) {
                alert('Seleccione al menos un pedido para cambiar a auditado.');
                return;
            }

            $.ajax({
                url: '/api/qaauditoria/index.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'cambio_auditado',
                    folios: folios,
                    status: 'P'
                },
                success: function (res) {
                    console.log('Cambio a auditado OK', res);
                    ReloadGrid();
                },
                error: function (xhr) {
                    console.error('Error cambiando a auditado', xhr.responseText || xhr.statusText);
                }
            });
        });

        // Recarga de información desde el API (equivale a jqGrid ReloadGrid del legacy)
        function ReloadGrid() {
            var status = $('#select-status').val();

            $.ajax({
                url: '/api/qaauditoria/index.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'pedidos_qa',
                    status: status
                    // si más adelante se soporta búsqueda por folio/pedido:
                    // criterio: $('#input-busqueda').val()
                },
                success: function (res) {
                    console.log('Respuesta pedidos_qa', res);

                    // Intentar detectar si viene como {data:[...]} o como arreglo directo
                    var rows = Array.isArray(res) ? res : (res.data || []);
                    tabla.clear().rows.add(rows).draw();

                    // reset de selección
                    $('#chk-asignar-todo').prop('checked', false);
                },
                error: function (xhr) {
                    console.error('Error cargando pedidos QA', xhr.responseText || xhr.statusText);
                    tabla.clear().draw();
                    $('#chk-asignar-todo').prop('checked', false);
                }
            });
        }

        // Detalle (placeholder)
        $('#tabla-qa').on('click', '.btn-detalle', function () {
            var folio = $(this).data('folio') || '';
            console.log('Detalle de folio', folio);
            // Aquí podrías abrir auditoriayempaque.php con el folio seleccionado, por ejemplo:
            // window.location.href = 'auditoriayempaque.php?folio=' + encodeURIComponent(folio);
        });

    });
</script>
