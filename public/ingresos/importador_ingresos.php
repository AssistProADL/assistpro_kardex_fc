<?php
// public/Ingresos/importador_ingresos.php
session_start();

require_once __DIR__ . '/../bi/_menu_global.php';
require_once __DIR__ . '/../../app/db.php';

$TITLE = 'Importador de Ingresos';
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
        font-size: 11px;
    }
    .ap-form-control {
        font-size: 11px;
        height: 28px;
        padding: 2px 6px;
    }
    .ap-label {
        font-size: 11px;
        font-weight: 600;
        margin-bottom: 2px;
    }
    .table-sm th, .table-sm td {
        font-size: 10px;
        padding: 4px 6px;
        white-space: nowrap;
    }
    .scroll-table {
        max-height: 340px;
        overflow-x: auto;
        overflow-y: auto;
    }
</style>

<div class="container-fluid">
    <h4 class="mb-3"><i class="fa fa-download"></i> Importador de Ingresos</h4>

    <!-- Filtros principales -->
    <div class="ap-card">
        <div class="ap-card-header">Parámetros de ingreso</div>
        <div class="ap-card-body">
            <form id="form-importador" enctype="multipart/form-data">
                <div class="row mb-2">
                    <div class="col-md-3">
                        <label class="ap-label">Empresa</label>
                        <select name="empresa_id" id="empresa_id" class="form-control ap-form-control">
                            <option value="">[Seleccione]</option>
                            <!-- Se llena vía filtros_assistpro.php -->
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="ap-label">Almacén</label>
                        <select name="almacen_id" id="almacen_id" class="form-control ap-form-control">
                            <option value="">[Seleccione]</option>
                            <!-- Se llena vía filtros_assistpro.php -->
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="ap-label">Tipo de ingreso</label>
                        <select name="tipo_ingreso" id="tipo_ingreso" class="form-control ap-form-control">
                            <option value="">[Seleccione]</option>
                            <option value="OC">OC - Lista para recibir</option>
                            <option value="OC_PUT">OC + Acomodo automático</option>
                            <option value="RL">Recepción Libre</option>
                            <option value="XD">CrossDocking</option>
                            <option value="ASN">ASN</option>
                            <option value="INV_INI">Inventario Inicial</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="chk_acomodo_auto" name="acomodo_auto">
                            <label class="form-check-label" for="chk_acomodo_auto" style="font-size:11px;">
                                Acomodo automático (cuando aplique)
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Layout y archivo -->
                <div class="row mb-2">
                    <div class="col-md-4">
                        <label class="ap-label">Layout</label><br>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-descargar-layout">
                            <i class="fa fa-file-excel-o"></i> Descargar layout
                        </button>
                    </div>
                    <div class="col-md-4">
                        <label class="ap-label">Archivo a importar (CSV/XLSX)</label>
                        <input type="file" name="archivo" id="archivo" class="form-control ap-form-control">
                    </div>
                    <div class="col-md-4 d-flex align-items-end justify-content-end">
                        <button type="button" class="btn btn-sm btn-secondary mr-2" id="btn-previsualizar">
                            <i class="fa fa-eye"></i> Previsualizar
                        </button>
                        <button type="button" class="btn btn-sm btn-success" id="btn-procesar" disabled>
                            <i class="fa fa-check"></i> Procesar ingresos
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Resultados / previsualización -->
    <div class="ap-card">
        <div class="ap-card-header">Previsualización y validaciones</div>
        <div class="ap-card-body">
            <div class="row mb-2">
                <div class="col-md-3">
                    <span class="ap-label">Totales</span>
                    <div id="resumen-totales" style="font-size:11px;">
                        Líneas: 0 | OK: 0 | Error: 0
                    </div>
                </div>
                <div class="col-md-9">
                    <span class="ap-label">Mensajes</span>
                    <div id="mensajes-importador" style="font-size:11px; max-height:80px; overflow-y:auto; border:1px solid #eee; padding:4px;"></div>
                </div>
            </div>

            <div class="scroll-table">
                <table class="table table-sm table-striped table-bordered" id="tabla-previsualizacion">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Estado</th>
                            <th>Mensaje</th>
                            <th>Tipo ingreso</th>
                            <th>Origen</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>UOM</th>
                            <th>BL destino</th>
                            <th>Nivel</th>
                            <th>ID Contenedor</th>
                            <th>ID Pallet</th>
                            <th>EPC</th>
                            <th>Code</th>
                            <th>Lote</th>
                            <th>Caducidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Se llenará vía JS / API -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Cargar empresas y almacenes usando filtros_assistpro.php
function cargarFiltrosEmpresaAlmacen(empresaSeleccionada) {
    const selEmpresa = document.getElementById('empresa_id');
    const selAlmacen = document.getElementById('almacen_id');
    if (!selEmpresa || !selAlmacen) {
        return;
    }

    const params = new URLSearchParams();
    params.set('action', 'init');
    params.set('secciones', 'empresas,almacenes');
    if (empresaSeleccionada) {
        params.set('empresa', empresaSeleccionada);
    }

    fetch('../api/filtros_assistpro.php?' + params.toString())
        .then(r => r.json())
        .then(data => {
            if (!data || data.ok === false) {
                console.error('Error en filtros_assistpro:', data && data.error);
                return;
            }

            // Llenar empresas solo cuando no hay empresa seleccionada (primer load)
            if (!empresaSeleccionada && Array.isArray(data.empresas)) {
                selEmpresa.innerHTML = '<option value=\"\">[Seleccione]</option>';
                data.empresas.forEach(emp => {
                    const opt = document.createElement('option');
                    const clave = emp.clave_empresa || emp.cve_cia || '';
                    opt.value = emp.cve_cia || clave;
                    opt.textContent = (clave ? '[' + clave + '] ' : '') + (emp.des_cia || '');
                    selEmpresa.appendChild(opt);
                });
            }

            // Llenar almacenes (ya vienen filtrados por empresa si se envió el parámetro)
            if (Array.isArray(data.almacenes)) {
                selAlmacen.innerHTML = '<option value=\"\">[Seleccione]</option>';
                data.almacenes.forEach(alm => {
                    const clave = alm.clave_almacen || alm.cve_almac || '';
                    const desc  = alm.des_almac || clave;
                    const opt   = document.createElement('option');
                    opt.value = alm.cve_almac || clave;
                    opt.textContent = (clave ? '[' + clave + '] ' : '') + desc;
                    selAlmacen.appendChild(opt);
                });
            }
        })
        .catch(err => {
            console.error('Error de comunicación con filtros_assistpro:', err);
        });
}

document.addEventListener('DOMContentLoaded', function () {
    const selEmpresa = document.getElementById('empresa_id');
    const selAlmacen = document.getElementById('almacen_id');

    // Cargar empresas y almacenes iniciales
    cargarFiltrosEmpresaAlmacen('');

    // Cuando cambia la empresa, recargar almacenes filtrados
    if (selEmpresa) {
        selEmpresa.addEventListener('change', function () {
            const emp = this.value || '';
            if (selAlmacen) {
                selAlmacen.innerHTML = '<option value=\"\">[Seleccione]</option>';
            }
            if (emp) {
                cargarFiltrosEmpresaAlmacen(emp);
            }
        });
    }

    // Botón: Descargar layout
    document.getElementById('btn-descargar-layout').addEventListener('click', function () {
        const tipo = document.getElementById('tipo_ingreso').value;
        if (!tipo) {
            alert('Seleccione primero el tipo de ingreso.');
            return;
        }
        window.location.href = '../api/importador_ingresos.php?action=layout&tipo_ingreso=' + encodeURIComponent(tipo);
    });

    // Botón: Previsualizar
    document.getElementById('btn-previsualizar').addEventListener('click', function () {
        const form = document.getElementById('form-importador');
        const fd = new FormData(form);
        fd.append('action', 'previsualizar');

        fetch('../api/importador_ingresos.php', {
            method: 'POST',
            body: fd
        })
            .then(r => r.json())
            .then(data => {
                if (!data.ok) {
                    document.getElementById('mensajes-importador').innerText = data.error || 'Error desconocido';
                    return;
                }

                document.getElementById('resumen-totales').innerText =
                    'Líneas: ' + (data.total || 0) +
                    ' | OK: ' + (data.total_ok || 0) +
                    ' | Error: ' + (data.total_err || 0);

                const tbody = document.querySelector('#tabla-previsualizacion tbody');
                tbody.innerHTML = '';
                (data.filas || []).forEach((row, idx) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${idx + 1}</td>
                        <td>${row.estado || ''}</td>
                        <td>${row.mensaje || ''}</td>
                        <td>${row.tipo_ingreso || ''}</td>
                        <td>${row.origen || ''}</td>
                        <td>${row.producto || ''}</td>
                        <td>${row.cantidad || ''}</td>
                        <td>${row.uom || ''}</td>
                        <td>${row.bl_destino || ''}</td>
                        <td>${row.nivel || ''}</td>
                        <td>${row.id_contenedor || ''}</td>
                        <td>${row.id_pallet || ''}</td>
                        <td>${row.epc || ''}</td>
                        <td>${row.code || ''}</td>
                        <td>${row.lote || ''}</td>
                        <td>${row.caducidad || ''}</td>
                    `;
                    tbody.appendChild(tr);
                });

                document.getElementById('mensajes-importador').innerText = data.mensaje_global || 'Previsualización generada.';
                document.getElementById('btn-procesar').disabled = (data.total_ok || 0) === 0;
            })
            .catch(err => {
                document.getElementById('mensajes-importador').innerText = 'Error de comunicación: ' + err;
            });
    });

    // Botón: Procesar
    document.getElementById('btn-procesar').addEventListener('click', function () {
        if (!confirm('¿Desea procesar los ingresos?')) return;

        const form = document.getElementById('form-importador');
        const fd = new FormData(form);
        fd.append('action', 'procesar');

        fetch('../api/importador_ingresos.php', {
            method: 'POST',
            body: fd
        })
            .then(r => r.json())
            .then(data => {
                document.getElementById('mensajes-importador').innerText = data.mensaje || (data.ok ? 'Procesado.' : 'Error al procesar.');
                if (data.ok) {
                    document.getElementById('btn-procesar').disabled = true;
                }
            })
            .catch(err => {
                document.getElementById('mensajes-importador').innerText = 'Error de comunicación: ' + err;
            });
    });
});
</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
