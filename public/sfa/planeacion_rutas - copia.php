<?php
// public/sfa/planeacion_rutas.php

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

// Catálogo de almacenes: usamos la CLAVE como value para empatar con reldaycli.Cve_Almac
$almacenes = [];
try {
    if (db_table_exists('c_almacenp')) {
        $almacenes = db_all("
            SELECT id, clave, nombre
            FROM c_almacenp
            WHERE IFNULL(activo,1) = 1
            ORDER BY clave
        ");
    }
} catch (Exception $e) {
    $almacenes = [];
}
?>
<style>
    .card-kpi {
        border-radius: .75rem;
        box-shadow: 0 2px 4px rgba(0,0,0,.08);
        background-color: #ffffff;
    }

    .form-control.input-sm {
        height: 26px;
        padding: 2px 6px;
        font-size: 11px;
    }

    label {
        font-size: 11px;
        margin-bottom: 2px;
    }

    #tabla_clientes {
        font-size: 10px;
        margin-bottom: 0;
    }
    #tabla_clientes thead th {
        position: sticky;
        top: 0;
        background: #f8f9fa;
        z-index: 2;
    }
    #tabla_clientes thead th,
    #tabla_clientes tbody td {
        padding: 3px 6px;
        vertical-align: middle;
        white-space: nowrap;
    }
</style>

<div class="wrapper wrapper-content animated fadeInRight" style="padding-top:20px;">

    <div class="row">
        <div class="col-lg-12">

            <div class="ibox" style="margin-top:10px;">
                <div class="ibox-title" style="padding:12px 20px;">
                    <h3 style="margin:0;font-size:18px;">Planeación de Rutas | Destinatarios</h3>
                    <small style="font-size:11px;color:#666;">
                        Planeación de visitas (solo asignación, sin operaciones comerciales)
                    </small>
                </div>

                <div class="ibox-content" style="padding:20px 25px;">

                    <!-- FILTROS SUPERIORES -->
                    <form id="frm_filtros" onsubmit="return false;">
                        <div class="row" style="font-size:11px;">

                            <div class="col-md-3">
                                <label>Almacén:</label>
                                <select class="form-control input-sm" id="f_almacen" name="f_almacen">
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($almacenes as $a): ?>
                                        <option value="<?php echo htmlspecialchars((string)$a['clave']); ?>">
                                            <?php echo htmlspecialchars($a['clave'] . ' - ' . $a['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label>Código Postal:</label>
                                <input type="text" class="form-control input-sm"
                                       id="f_cp" name="f_cp" placeholder="Ej. 01000">
                            </div>

                            <div class="col-md-3">
                                <label>Ruta:</label>
                                <select class="form-control input-sm" id="f_ruta" name="f_ruta">
                                    <option value="">Seleccione...</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label>Agente / Operador:</label>
                                <select class="form-control input-sm" id="f_agente" name="f_agente">
                                    <option value="">Seleccione...</option>
                                </select>
                            </div>
                        </div>

                        <div class="row" style="font-size:11px;margin-top:8px;">
                            <div class="col-md-3">
                                <label>Día:</label>
                                <select class="form-control input-sm" id="f_dia" name="f_dia">
                                    <option value="">Todos</option>
                                    <option value="LUN">Lunes</option>
                                    <option value="MAR">Martes</option>
                                    <option value="MIE">Miércoles</option>
                                    <option value="JUE">Jueves</option>
                                    <option value="VIE">Viernes</option>
                                    <option value="SAB">Sábado</option>
                                    <option value="DOM">Domingo</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label>&nbsp;</label>
                                <div class="checkbox" style="margin-top:4px;">
                                    <label style="padding-left:0;">
                                        <input type="checkbox" id="f_entregas" name="f_entregas" value="1">
                                        Solo entregas
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-3" style="display:flex;align-items:flex-end;">
                                <button type="button" id="btn_buscar"
                                        class="btn btn-primary btn-sm" style="font-size:11px;">
                                    <i class="fa fa-search"></i> Buscar
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- KPIs -->
                    <div class="row" style="font-size:11px;margin-top:15px;">
                        <div class="col-md-4">
                            <div class="card-kpi p-2">
                                <div style="color:#555;">TOTAL DE CLIENTES</div>
                                <div id="kpi_total_clientes" style="font-weight:bold;font-size:16px;">0</div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card-kpi p-2">
                                <div style="color:#555;">CLIENTES POR RUTA</div>
                                <div id="kpi_clientes_ruta" style="font-weight:bold;font-size:16px;">0</div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card-kpi p-2">
                                <div style="color:#555;">CLIENTES POR DÍA</div>
                                <div id="kpi_clientes_dia" style="font-weight:bold;font-size:16px;">0</div>
                            </div>
                        </div>
                    </div>

                    <!-- BOTONES LATERALES + GRILLA -->
                    <div class="row" style="margin-top:15px;margin-bottom:5px;font-size:11px;">

                        <div class="col-md-2">
                            <button type="button" class="btn btn-primary btn-block btn-sm" id="btn_nuevo">
                                <i class="fa fa-user-plus"></i> Nuevo destinatario
                            </button>
                            <button type="button" class="btn btn-default btn-block btn-sm" id="btn_importar"
                                    style="margin-top:5px;">
                                <i class="fa fa-upload"></i> Importar planeación de visitas
                            </button>
                            <button type="button" class="btn btn-default btn-block btn-sm" id="btn_exportar"
                                    style="margin-top:5px;">
                                <i class="fa fa-download"></i> Exportar destinatarios
                            </button>
                            <button type="button" class="btn btn-warning btn-block btn-sm" id="btn_reiniciar"
                                    style="margin-top:5px;">
                                <i class="fa fa-refresh"></i> Reiniciar secuencia
                            </button>
                            <button type="button" class="btn btn-info btn-block btn-sm"
                                    data-toggle="modal" data-target="#modal_mapa" id="btn_mapas"
                                    style="margin-top:5px;">
                                <i class="fa fa-map-marker"></i> Visualizar rutas
                            </button>
                        </div>

                        <div class="col-md-10">
                            <div class="table-responsive"
                                 style="max-height:480px;overflow-y:auto;overflow-x:auto;border:1px solid #e5e6e7;">
                                <table id="tabla_clientes"
                                       class="table table-striped table-hover">
                                    <thead>
                                    <tr>
                                        <th style="width:30px;">
                                            <input type="checkbox" id="chk_all">
                                        </th>
                                        <th style="min-width:80px;">Ruta</th>
                                        <th style="min-width:70px;">Secuencia</th>
                                        <th style="min-width:90px;">Clave Cliente</th>
                                        <th style="min-width:200px;">Razón Comercial</th>
                                        <th style="min-width:200px;">Cliente</th>
                                        <th style="min-width:110px;">Clave Destinatario</th>
                                        <th style="min-width:200px;">Destinatario</th>
                                        <th style="min-width:220px;">Dirección</th>
                                        <th style="min-width:160px;">Colonia</th>
                                        <th style="min-width:80px;">CP</th>
                                        <th style="min-width:160px;">Ciudad</th>
                                        <th style="min-width:160px;">Municipio/Estado</th>
                                        <th style="min-width:30px;">L</th>
                                        <th style="min-width:30px;">M</th>
                                        <th style="min-width:30px;">X</th>
                                        <th style="min-width:30px;">J</th>
                                        <th style="min-width:30px;">V</th>
                                        <th style="min-width:30px;">S</th>
                                        <th style="min-width:30px;">D</th>
                                    </tr>
                                    </thead>
                                    <tbody id="tbody_clientes">
                                    <tr>
                                        <td colspan="20" class="text-center text-muted">
                                            Configure filtros y pulse <strong>Buscar</strong> para listar clientes.
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div> <!-- row -->

                </div> <!-- ibox-content -->
            </div>
        </div>
    </div>
</div>

<!-- MODAL MAPA -->
<div class="modal fade" id="modal_mapa" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" style="width:1200px;max-width:95%;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    Planeación de Rutas | Mapa de clientes
                </h4>
            </div>
            <div class="modal-body">
                <div id="mapa" style="height:55vh;width:100%;"></div>
            </div>
            <div class="modal-footer">
                <div style="float:left;font-size:11px;">
                    <div style="background-color:#0000ff;width:16px;height:16px;display:inline-block;"></div>
                    <span>&nbsp;Almacén</span>&nbsp;&nbsp;
                    <div style="background-color:#ff3300;width:16px;height:16px;display:inline-block;"></div>
                    <span>&nbsp;Clientes contado</span>&nbsp;&nbsp;
                    <div style="background-color:#009900;width:16px;height:16px;display:inline-block;"></div>
                    <span>&nbsp;Clientes crédito</span>
                </div>
                <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
const API_RUTAS_URL = 'planeacion_rutas_data.php';

document.addEventListener('DOMContentLoaded', function () {

    const chkAll = document.getElementById('chk_all');
    chkAll.addEventListener('change', function () {
        document.querySelectorAll('#tbody_clientes input[type=checkbox].chk_row').forEach(function (chk) {
            chk.checked = chkAll.checked;
        });
    });

    document.getElementById('f_almacen').addEventListener('change', function () {
        cargarRutas();
        limpiarAgentes();
        limpiarTabla();
        actualizarKpis(0,0,0);
    });

    document.getElementById('f_ruta').addEventListener('change', function () {
        cargarAgentes();
    });

    document.getElementById('btn_buscar').addEventListener('click', function () {
        buscarClientes();
    });

    document.getElementById('btn_reiniciar').addEventListener('click', function () {
        alert('Reinicio de secuencia: en la siguiente fase conectamos contra la tabla de secuencias.');
    });

    document.getElementById('btn_nuevo').addEventListener('click', function () {
        alert('Nuevo destinatario: aquí abriremos el formulario en la siguiente fase.');
    });

    document.getElementById('btn_importar').addEventListener('click', function () {
        alert('Importar planeación: conectaremos el layout de Excel en la siguiente fase.');
    });

    document.getElementById('btn_exportar').addEventListener('click', function () {
        alert('Exportar: generaremos el Excel con la planeación actual.');
    });

    actualizarKpis(0,0,0);
});

function cargarRutas() {
    const alm = document.getElementById('f_almacen').value || '';
    const selRuta = document.getElementById('f_ruta');
    selRuta.innerHTML = '<option value="">Seleccione...</option>';

    if (!alm) return;

    fetch(API_RUTAS_URL + '?action=rutas_por_almacen&almacen=' + encodeURIComponent(alm))
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            data.rows.forEach(r => {
                const opt = document.createElement('option');
                opt.value = r.ID_Ruta;
                opt.textContent = r.cve_ruta + ' - ' + r.descripcion;
                selRuta.appendChild(opt);
            });
        })
        .catch(err => console.error('Error rutas:', err));
}

function limpiarAgentes() {
    const sel = document.getElementById('f_agente');
    sel.innerHTML = '<option value="">Seleccione...</option>';
}

function cargarAgentes() {
    const rutaId = document.getElementById('f_ruta').value || '';
    limpiarAgentes();
    if (!rutaId) return;

    fetch(API_RUTAS_URL + '?action=agentes_por_ruta&ruta_id=' + encodeURIComponent(rutaId))
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const sel = document.getElementById('f_agente');
            data.rows.forEach(a => {
                const opt = document.createElement('option');
                opt.value = a.Id_Vendedor;
                opt.textContent = a.Cve_Vendedor + ' - ' + a.Nombre;
                sel.appendChild(opt);
            });
        })
        .catch(err => console.error('Error agentes:', err));
}

function buscarClientes() {
    const params = new URLSearchParams({
        action: 'listar',
        almacen: document.getElementById('f_almacen').value || '',
        cp: document.getElementById('f_cp').value || '',
        ruta_id: document.getElementById('f_ruta').value || '',
        agente_id: document.getElementById('f_agente').value || '',
        dia: document.getElementById('f_dia').value || '',
        solo_entregas: document.getElementById('f_entregas').checked ? '1' : '0',
        criterio: '' // reservado por si agregamos caja de búsqueda libre
    });

    fetch(API_RUTAS_URL + '?' + params.toString())
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                alert(data.error || 'Error en la consulta');
                return;
            }
            pintarTabla(data.rows || []);
            actualizarKpis(
                data.total_clientes || 0,
                data.clientes_por_ruta || 0,
                data.clientes_por_dia || 0
            );
        })
        .catch(err => {
            console.error('Error listar:', err);
            alert('Error de comunicación con el servidor.');
        });
}

function limpiarTabla() {
    const tbody = document.getElementById('tbody_clientes');
    tbody.innerHTML = '<tr><td colspan="20" class="text-center text-muted">' +
        'Configure filtros y pulse <strong>Buscar</strong>.' +
        '</td></tr>';
}

function pintarTabla(rows) {
    const tbody = document.getElementById('tbody_clientes');
    tbody.innerHTML = '';

    if (!rows.length) {
        limpiarTabla();
        return;
    }

    rows.forEach(r => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
              <input type="checkbox" class="chk_row" value="${escapeHtml(r.id_destinatario ?? '')}">
            </td>
            <td>${escapeHtml(r.cve_ruta ?? '')}</td>
            <td>${escapeHtml(r.secuencia ?? '')}</td>
            <td>${escapeHtml(r.Cve_Clte ?? '')}</td>
            <td>${escapeHtml(r.RazonComercial ?? '')}</td>
            <td>${escapeHtml(r.RazonSocial ?? '')}</td>
            <td>${escapeHtml(r.clave_destinatario ?? '')}</td>
            <td>${escapeHtml(r.destinatario ?? '')}</td>
            <td>${escapeHtml(r.direccion ?? '')}</td>
            <td>${escapeHtml(r.colonia ?? '')}</td>
            <td>${escapeHtml(r.postal ?? '')}</td>
            <td>${escapeHtml(r.ciudad ?? '')}</td>
            <td>${escapeHtml(r.municipio ?? '')}</td>
            <td class="text-center">${r.Lu ? '✔' : ''}</td>
            <td class="text-center">${r.Ma ? '✔' : ''}</td>
            <td class="text-center">${r.Mi ? '✔' : ''}</td>
            <td class="text-center">${r.Ju ? '✔' : ''}</td>
            <td class="text-center">${r.Vi ? '✔' : ''}</td>
            <td class="text-center">${r.Sa ? '✔' : ''}</td>
            <td class="text-center">${r.Do ? '✔' : ''}</td>
        `;
        tbody.appendChild(tr);
    });
}

function actualizarKpis(total, porRuta, porDia) {
    document.getElementById('kpi_total_clientes').textContent = Number(total || 0).toLocaleString();
    document.getElementById('kpi_clientes_ruta').textContent = Number(porRuta || 0).toLocaleString();
    document.getElementById('kpi_clientes_dia').textContent = Number(porDia || 0).toLocaleString();
}

function escapeHtml(text) {
    return String(text ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
