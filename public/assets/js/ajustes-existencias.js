// Ajustes de Existencias - JavaScript Completo
$(document).ready(function () {
    // Obtener base URL dinámicamente
    const getBaseUrl = () => {
        const path = window.location.pathname;
        const parts = path.split('/public/');
        return parts[0] + '/public';
    };
    const baseUrl = getBaseUrl();

    $('.select2').select2({
        width: '100%'
    });

    // Fix para Select2 dentro de Modal (Motivos)
    $('#motivo_selector').select2({
        dropdownParent: $('#modalMotivos'),
        width: '100%'
    });

    // Inicializar Select2 para Artículos con carga diferida
    $('#articulo').select2({
        placeholder: 'Buscar artículo...',
        allowClear: true,
        ajax: {
            url: `${baseUrl}/api/ajustes/existencias/search-articulos`,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term,
                    page: params.page || 1,
                    almacen: $('#almacen').val(),
                    almacenaje: $('#zona').val(),
                    tipo: $('#tipo').val()
                };
            },
            processResults: function (data, params) {
                params.page = params.page || 1;
                return {
                    results: data.results,
                    pagination: {
                        more: data.pagination.more
                    }
                };
            },
            cache: true
        },
        minimumInputLength: 0, // Permitir cargar al abrir
        templateResult: function (repo) {
            if (repo.loading) return repo.text;
            return repo.text;
        },
        templateSelection: function (repo) {
            return repo.text;
        }
    });

    // Eventos de filtros
    $('#almacen, #zona, #tipo, #articulo').on('change', function () {
        // Si cambia el almacén, limpiar artículo ya que depende del almacén
        if (this.id === 'almacen') {
            $('#articulo').val(null).trigger('change');
        }
        if (table) table.ajax.reload();
    });
    let table;
    let tableDetalle;
    let currentFilters = {};
    let currentItems = [];

    // Loader functions
    function showLoader() {
        if ($('.ap-loading-overlay').length === 0) {
            $('.ap-table-container').css('position', 'relative').append(`
                <div class="ap-loading-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.7); z-index: 10; display: flex; justify-content: center; align-items: center; flex-direction: column;">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 text-primary">Cargando...</div>
                </div>
            `);
        }
    }

    function hideLoader() {
        $('.ap-loading-overlay').fadeOut(300, function () {
            $(this).remove();
        });
    }

    // Cargar almacenes al iniciar (PRIMERO)
    loadAlmacenes();

    // Eventos
    $('#almacen').on('change', function () {
        const almacen = $(this).val();
        if (almacen) {
            loadZonas(almacen);
            $('#zona').prop('disabled', false);
        } else {
            $('#zona').html('<option value="">Todas</option>').prop('disabled', true);
        }
    });

    $('#btn_aplicar').on('click', applyFilters);
    $('#btn_limpiar').on('click', clearFilters);

    // Aplicar filtros
    function applyFilters() {
        const $btn = $('#btn_aplicar');
        const $btnClean = $('#btn_limpiar');
        const originalText = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Buscando...');
        $btnClean.prop('disabled', true);

        showLoader();

        currentFilters = {
            almacen: $('#almacen').val(),
            almacenaje: $('#zona').val(),
            tipo: $('#tipo').val(),
            articulo: $('#articulo').val(),
            search: $('#buscar_bl').val()
        };

        // Load KPIs
        loadKPIs();

        if (table) {
            table.ajax.reload(function () {
                $btn.prop('disabled', false).html(originalText);
                $btnClean.prop('disabled', false);
                hideLoader();
            });
        } else {
            initDataTable(function () {
                $btn.prop('disabled', false).html(originalText);
                $btnClean.prop('disabled', false);
                hideLoader();
            });
        }
    }

    // Limpiar filtros
    function clearFilters() {
        $('#almacen, #zona, #tipo, #articulo, #buscar_bl').val('');
        $('#zona').prop('disabled', true);
        currentFilters = {};
        applyFilters();
    }

    // Cargar almacenes
    function loadAlmacenes() {
        $.get(`${baseUrl}/api/almacen/predeterminado`, function (resp) {
            if (resp.success && resp.data) {
                const $select = $('#almacen');
                $select.html('<option value="">Seleccione...</option>');
                resp.data.forEach(alm => {
                    $select.append(`<option value="${alm.id}">${alm.clave} - ${alm.nombre}</option>`);
                });
                // Auto-seleccionar si solo hay uno
                if (resp.data.length === 1) {
                    $select.val(resp.data[0].id).trigger('change');
                }

                // DESPUÉS de cargar almacenes, inicializar tabla y KPIs
                loadKPIs();
                initDataTable();
            }
        }).fail(function () {
            console.error('Error al cargar almacenes');
        });
    }

    // Cargar zonas
    function loadZonas(almacenId) {
        $.get(`${baseUrl}/api/almacen/zonas`, {
            almacen_id: almacenId
        }, function (resp) {
            if (resp.success && resp.data) {
                const $select = $('#zona');
                $select.html('<option value="">Seleccione...</option>');
                resp.data.forEach(zona => {
                    $select.append(`<option value="${zona.cve_almac}">${zona.cve_almac} - ${zona.des_almac}</option>`);
                });
            }
        }).fail(function () {
            console.error('Error al cargar zonas');
        });
    }

    // Cargar KPIs
    function loadKPIs() {
        $.get(`${baseUrl}/api/ajustes/existencias/kpis`, currentFilters, function (resp) {
            if (resp.success) {
                $('#kpi_total').text(resp.data.total_ubicaciones || 0);
                $('#kpi_ocupacion').text((resp.data.porcentaje_ocupacion || 0) + '%');
                $('#kpi_vacias').text(resp.data.vacias || 0);
            }
        });
    }

    // Inicializar DataTable
    function initDataTable(callback) {
        $('#btn_aplicar, #btn_limpiar').prop('disabled', true);
        showLoader();
        table = $('#tblAjustes').DataTable({
            processing: false,
            serverSide: true,
            searching: false,
            responsive: true,
            scrollX: true,
            ajax: {
                url: `${baseUrl}/api/ajustes/existencias`,
                beforeSend: function () {
                    showLoader();
                },
                data: function (d) {
                    return $.extend({}, d, currentFilters, {
                        limit: d.length,
                        offset: d.start
                    });
                },
                error: function (xhr, error, thrown) {
                    console.error('Error en DataTable:', error);
                    hideLoader();
                    $('#btn_aplicar, #btn_limpiar').prop('disabled', false).html('<i class="fa fa-search"></i> Buscar');
                }
            },
            initComplete: function () {
                $('#btn_aplicar, #btn_limpiar').prop('disabled', false);
                if (callback) callback();
            },
            drawCallback: function () {
                hideLoader();
            },
            columns: [
                {
                    data: null,
                    orderable: false,
                    width: '120px',
                    className: 'text-center',
                    render: (d) => {
                        const hasPallets = d.clave_contenedor && d.clave_contenedor.trim() !== '';
                        const hasLps = d.CveLP && d.CveLP.trim() !== '';

                        return `
                        <div class="d-flex justify-content-center gap-2">
                            <a href="javascript:void(0);" class="btn-detalle btn btn-sm btn-light" data-id="${d.idy_ubica}" data-area="${d.AreaProduccion}" data-almacen="${d.cve_almacenp || d.cve_almac}" title="Ver Detalle">
                                <i class="fa fa-search"></i>
                            </a>
                            <button class="btn btn-sm btn-info btn-pallets" title="Ver Pallets/Contenedores" style="color: white;" ${!hasPallets ? 'disabled' : ''}>
                                <i class="fa fa-box"></i>
                            </button>
                            <button class="btn btn-sm btn-warning btn-lps" title="Ver License Plates" ${!hasLps ? 'disabled' : ''}>
                                <i class="fa fa-barcode"></i>
                            </button>
                        </div>
                    `;
                    }
                },
                { data: 'almacen_zona', width: '200px' },
                { data: 'BL', width: '70px' },
                { data: 'zona_almacenaje', width: '210px' },
                {
                    data: null,
                    width: '100px',
                    className: 'text-end',
                    render: (d) => ''
                },
                {
                    data: null,
                    width: '110px',
                    className: 'text-end',
                    render: (d) => ''
                },
                {
                    data: null,
                    width: '250px',
                    className: 'text-center',
                    render: (d) => {
                        const alto = parseFloat(d.num_alto || 0).toFixed(2);
                        const ancho = parseFloat(d.num_ancho || 0).toFixed(2);
                        const largo = parseFloat(d.num_largo || 0).toFixed(2);
                        return `${alto} X ${ancho} X ${largo}`;
                    }
                }
            ],
            autoWidth: false,
            responsive: false,
            scrollX: true,
            pageLength: 10,
            language: {
                url: '//cdn.datatables.net/plug-ins/2.3.5/i18n/es-MX.json'
            }
        });

        $('#tblAjustes').on('click', '.btn-detalle', function () {
            const id = $(this).data('id');
            const area = $(this).data('area');
            const almacen = $(this).data('almacen');
            loadDetalle(id, area, almacen);
        });

        $('#tblAjustes').on('click', '.btn-pallets', function () {
            const tr = $(this).closest('tr');
            const row = $('#tblAjustes').DataTable().row(tr);
            const data = row.data();
            showLista('Pallets / Contenedores', data.clave_contenedor);
        });

        $('#tblAjustes').on('click', '.btn-lps', function () {
            const tr = $(this).closest('tr');
            const row = $('#tblAjustes').DataTable().row(tr);
            const data = row.data();
            showLista('License Plates (LP)', data.CveLP);
        });
    }

    // Cargar detalle de ubicación
    function loadDetalle(ubicacion, areaProduccion, almacenId) {
        $('#modalDetalle').modal('show');

        // Estructura base si no existe
        if ($('#tblDetalle').length === 0) {
            $('#detalleContent').html(`
                <div id="detalleHeader"></div>
                <div class="table-responsive mt-3">
                    <table class="table table-hover table-striped table-bordered" id="tblDetalle" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>Artículo</th>
                                <th>Descripción</th>
                                <th>Lote</th>
                                <th>Caducidad</th>
                                <th>Serie</th>
                                <th>Existencia</th>
                                <th>Contenedor</th>
                                <th>Proveedor</th>
                                <th>Peso U.</th>
                                <th>Vol. U.</th>
                                <th>Peso Total</th>
                                <th>Vol. Total</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            `);
        }

        // Destruir tabla previa
        if ($.fn.DataTable.isDataTable('#tblDetalle')) {
            $('#tblDetalle').DataTable().destroy();
        }

        // Inicializar DataTable
        tableDetalle = $('#tblDetalle').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            ajax: {
                url: `${baseUrl}/api/ajustes/existencias/detalles`,
                data: {
                    ubicacion: ubicacion,
                    almacen: almacenId || currentFilters.almacen,
                    areaProduccion: areaProduccion
                },
                dataSrc: function (json) {
                    if (json.extra_data) {
                        updateDetalleHeader(json.extra_data);
                    }
                    // Guardar datos originales para comparación
                    currentItems = json.data;
                    return json.data;
                }
            },
            columns: [
                { data: 'cve_articulo' },
                { data: 'descripcion' },
                { data: 'lote' },
                { data: 'caducidad' },
                { data: 'serie' },
                {
                    data: 'Existencia_Total',
                    render: function (data, type, row) {
                        return `<input type="number" class="form-control input-sm cambio-existencia" 
                                data-id="${row.cve_articulo}" 
                                data-lote="${row.lote || ''}" 
                                data-original="${data}" 
                                value="${data}" 
                                min="0"
                                oninput="validity.valid||(value=''); if(value<0) value=0;"
                                style="width: 100px; text-align: right;">`;
                    }
                },
                { data: 'contenedor' },
                { data: 'proveedor' },
                { data: 'peso_unitario' },
                { data: 'volumen_unitario' },
                { data: 'peso_total' },
                { data: 'volumen_total' }
            ],
            pageLength: 10,
            language: {
                url: '//cdn.datatables.net/plug-ins/2.3.5/i18n/es-MX.json'
            },
            drawCallback: function () {
                // Re-aplicar eventos de cambio si es necesario
                $('.cambio-existencia').off('change').on('change', function () {
                    const original = parseFloat($(this).data('original'));
                    const current = parseFloat($(this).val());

                    if (current !== original) {
                        $(this).addClass('bg-warning');
                    } else {
                        $(this).removeClass('bg-warning');
                    }
                });
            }
        });

        // Agregar botón de guardar si no existe
        if ($('#btn-guardar-cambios').length === 0) {
            $('#detalleContent').append(`
                <div class="text-end mt-3">
                    <button id="btn-guardar-cambios" class="btn btn-primary">
                        <i class="fa fa-save"></i> Guardar Cambios
                    </button>
                </div>
            `);

            $('#btn-guardar-cambios').on('click', function () {
                prepararGuardado(almacenId || currentFilters.almacen, ubicacion);
            });
        }
    }

    function prepararGuardado(almacenId, ubicacionId) {
        const cambios = [];
        let error = false;

        $('#tblDetalle tbody tr').each(function () {
            const row = tableDetalle.row(this).data();
            const $input = $(this).find('.cambio-existencia');

            if ($input.length > 0) {
                const nuevaExistencia = parseFloat($input.val());
                const existenciaOriginal = parseFloat($input.data('original'));

                if (nuevaExistencia !== existenciaOriginal) {
                    if (isNaN(nuevaExistencia) || nuevaExistencia < 0) {
                        alert('Por favor ingrese cantidades válidas');
                        error = true;
                        return false;
                    }

                    cambios.push({
                        id_almacen: almacenId,
                        id_ubica: ubicacionId,
                        clave: row.cve_articulo,
                        lote: row.lote,
                        id_proveedor: row.id_proveedor,
                        contenedor: row.contenedor,
                        existencia: nuevaExistencia,
                        existencia_actual: existenciaOriginal
                    });
                }
            }
        });

        if (error) return;

        if (cambios.length === 0) {
            alert('No hay cambios para guardar');
            return;
        }

        // Mostrar modal de motivos y ocultar detalle
        $('#modalDetalle').modal('hide');
        $('#modalMotivos').modal('show');
        loadMotivos();

        // Configurar botón de confirmar en el modal
        $('#btn_guardar_ajuste').off('click').on('click', function () {
            const motivo = $('#motivo_selector').val();
            if (!motivo) {
                alert('Debe seleccionar un motivo');
                return;
            }
            guardarCambios(cambios, motivo);
        });

        // Restaurar modal detalle al cerrar motivos si no se guardó
        $('#modalMotivos').on('hidden.bs.modal', function () {
            if ($('#modalDetalle').is(':hidden') && !window.guardadoExitoso) {
                $('#modalDetalle').modal('show');
            }
            window.guardadoExitoso = false; // Reset flag
        });
    }

    function loadMotivos() {
        if ($('#motivo_selector option').length <= 1) {
            $.get(`${baseUrl}/api/catalogos/motivos`, {
                status: "A"
            }, function (resp) {
                if (resp.success && resp.data) {
                    const $select = $('#motivo_selector');
                    resp.data.forEach(m => {
                        $select.append(`<option value="${m.id}">${m.descri}</option>`);
                    });
                }
            }, 'json');
        }
    }

    function guardarCambios(items, motivo) {
        const $btn = $('#btn_guardar_ajuste');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: `${baseUrl}/api/ajustes/existencias/update`, // Usar ruta Laravel
            method: 'POST',
            data: {
                items: items,
                motivos: motivo,
                _token: $('meta[name="csrf-token"]').attr('content') // Si usas CSRF
            },
            success: function (resp) {
                if (resp.success) {
                    window.guardadoExitoso = true; // Flag para evitar reabrir detalle
                    $('#modalMotivos').modal('hide');
                    $('#modalDetalle').modal('hide');
                    alert('Cambios guardados correctamente. Folio: ' + (resp.data.folio || ''));
                    if (table) table.ajax.reload();
                } else {
                    alert('Error: ' + resp.message);
                }
            },
            error: function (xhr) {
                console.error(xhr);
                alert('Error al guardar cambios');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar Ajuste');
            }
        });
    }

    function updateDetalleHeader(data) {
        const pesoTotal = parseFloat(data.peso_total || 0);
        const volumenTotal = parseFloat(data.volumen_total || 0);
        const ubicacion = data.ubicacion || {};
        const pesoMax = parseFloat(ubicacion.PesoMaximo || 0);
        const volMax = parseFloat(ubicacion.VolumenMaximo || 0);

        const pesoDisponible = (pesoMax - pesoTotal).toFixed(4);
        const volumenDisponible = (volMax - volumenTotal).toFixed(4);

        const pesoPorcentaje = pesoMax > 0 ? ((pesoTotal / pesoMax) * 100).toFixed(2) : 0;
        const volumenPorcentaje = volMax > 0 ? ((volumenTotal / volMax) * 100).toFixed(2) : 0;

        const html = `
            <div class="row mb-3">
                <div class="col-12">
                    <h5><i class="fa fa-map-marker-alt text-primary"></i> Ubicación: <strong>${ubicacion.CodigoCSD || ''}</strong></h5>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-3"><label class="ap-label">Peso Máximo (Kg)</label><input type="text" class="form-control ap-form-control" value="${pesoMax}" readonly></div>
                <div class="col-md-3"><label class="ap-label">Peso Ocupado (Kg)</label><input type="text" class="form-control ap-form-control" value="${pesoTotal.toFixed(4)}" readonly></div>
                <div class="col-md-3"><label class="ap-label">Peso Disponible (Kg)</label><input type="text" class="form-control ap-form-control" value="${pesoDisponible}" readonly></div>
                <div class="col-md-3"><label class="ap-label">% Ocupación Peso</label><input type="text" class="form-control ap-form-control" value="${pesoPorcentaje}%" style="border-color: ${pesoTotal > pesoMax ? 'red' : 'green'}" readonly></div>
            </div>
            <div class="row mb-4">
                <div class="col-md-3"><label class="ap-label">Volumen Máximo (m³)</label><input type="text" class="form-control ap-form-control" value="${volMax}" readonly></div>
                <div class="col-md-3"><label class="ap-label">Volumen Ocupado (m³)</label><input type="text" class="form-control ap-form-control" value="${volumenTotal.toFixed(4)}" readonly></div>
                <div class="col-md-3"><label class="ap-label">Volumen Disponible (m³)</label><input type="text" class="form-control ap-form-control" value="${volumenDisponible}" readonly></div>
                <div class="col-md-3"><label class="ap-label">% Ocupación Volumen</label><input type="text" class="form-control ap-form-control" value="${volumenPorcentaje}%" style="border-color: ${volumenTotal > volMax ? 'red' : 'green'}" readonly></div>
            </div>
        `;
        $('#detalleHeader').html(html);
    }

    // Mostrar lista en modal
    function showLista(titulo, itemsString) {
        $('#modalListaTitle').text(titulo);
        const $content = $('#modalListaContent');
        $content.empty();

        if (!itemsString) {
            $content.append('<li class="list-group-item text-muted">No hay datos disponibles</li>');
        } else {
            const items = itemsString.split(',');
            items.forEach(item => {
                if (item.trim()) {
                    $content.append(`<li class="list-group-item">${item.trim()}</li>`);
                }
            });
        }

        $('#modalLista').modal('show');
    }
});
