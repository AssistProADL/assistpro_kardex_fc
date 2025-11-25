<?php
// public/partials/filtros_assistpro.php

// La receta por defecto llega del template
if (!isset($vista_id_default)) {
    $vista_id_default = '';
}
?>
<div class="card mb-2">
    <div class="card-header py-1" style="background:#0F5AAD;color:#fff;">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong>Filtros generales — AssistPro</strong>
            </div>
            <div class="d-flex align-items-center" style="gap:.25rem;">
                <select id="receta_vista" class="form-select form-select-sm" style="width:220px;">
                    <option value="">— Recetas de vista —</option>
                </select>
                <button id="btn_limpiar" type="button" class="btn btn-sm btn-light">
                    Limpiar
                </button>
                <button id="btn_aplicar" type="button" class="btn btn-sm btn-primary">
                    Aplicar
                </button>
            </div>
        </div>
    </div>
    <div class="card-body pb-2">

        <!-- Filtros -->
        <form id="form_filtros">
            <div class="row g-2">

                <!-- CONTEXTO -->
                <div class="col-12 col-xl-3">
                    <h6 style="font-size:11px;font-weight:bold;">Contexto</h6>
                    <div class="mb-1">
                        <label class="form-label mb-0" style="font-size:11px;">Empresa</label>
                        <select id="f_empresa" name="empresa" class="form-select form-select-sm">
                            <option value="">Todas</option>
                        </select>
                    </div>

                    <div class="mb-1">
                        <label class="form-label mb-0" style="font-size:11px;">Almacén</label>
                        <select id="f_almacen" name="almacen" class="form-select form-select-sm">
                            <option value="">Todos</option>
                        </select>
                    </div>

                    <div class="mb-1">
                        <label class="form-label mb-0" style="font-size:11px;">Zona de Almacenaje</label>
                        <select id="f_zona" name="zona" class="form-select form-select-sm">
                            <option value="">Todas</option>
                        </select>
                    </div>

                    <div class="mb-1">
                        <label class="form-label mb-0" style="font-size:11px;">BL (Bin Location)</label>
                        <input id="f_bl" name="bl" type="text"
                               class="form-control form-control-sm"
                               placeholder="BL / CodigoCSD">
                        <div style="font-size:10px;color:#777;">
                            Fuente: c_ubicacion.CodigoCSD
                        </div>
                    </div>
                </div>

                <!-- ATRIBUTOS DE UBICACIÓN -->
                <div class="col-12 col-xl-3">
                    <h6 style="font-size:11px;font-weight:bold;">Atributos de ubicación</h6>

                    <div class="mb-1">
                        <label class="form-label mb-0" style="font-size:11px;">Tipo BL</label>
                        <select id="f_tipo_bl" name="tipo_bl" class="form-select form-select-sm">
                            <option value="">Todos</option>
                        </select>
                    </div>

                    <div class="mb-1">
                        <label class="form-label mb-0" style="font-size:11px;">Status</label>
                        <select id="f_status_bl" name="status_bl" class="form-select form-select-sm">
                            <option value="">Todos</option>
                        </select>
                    </div>

                    <div class="mb-1">
                        <label class="form-label mb-0" style="font-size:11px;">Pasillo</label>
                        <input id="f_pasillo" name="pasillo" class="form-control form-control-sm" type="text">
                    </div>

                    <div class="mb-1">
                        <label class="form-label mb-0" style="font-size:11px;">Rack</label>
                        <input id="f_rack" name="rack" class="form-control form-control-sm" type="text">
                    </div>

                    <div class="mb-1">
                        <label class="form-label mb-0" style="font-size:11px;">Nivel</label>
                        <input id="f_nivel" name="nivel" class="form-control form-control-sm" type="text">
                    </div>
                </div>

                <!-- ENTIDADES LOGÍSTICAS -->
                <div class="col-12 col-xl-3">
                    <h6 style="font-size:11px;font-weight:bold;">Entidades logísticas</h6>

                    <div class="mb-1">
                        <label class="form-label mb-0" style="font-size:11px;">License Plate (LP)</label>
                        <input id="f_lp" name="lp" class="form-control form-control-sm"
                               placeholder="LP / LPN / contenedor">
                    </div>

                    <div class="mb-1">
                        <label class="form-label mb-0" style="font-size:11px;">Pallet o Contenedor</label>
                        <input id="f_pallet" name="pallet" class="form-control form-control-sm"
                               placeholder="Pallet / Contenedor">
                    </div>

                    <div class="mb-1">
                        <label class="form-label mb-0" style="font-size:11px;">Ruta</label>
                        <select id="f_ruta" name="ruta" class="form-select form-select-sm">
                            <option value="">Todas</option>
                        </select>
                    </div>

                    <div class="mb-1">
                        <label class="form-label mb-0" style="font-size:11px;">Día Operativo</label>
                        <input id="f_dia_oper" name="dia_oper" type="date"
                               class="form-control form-control-sm">
                    </div>

                    <div class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" id="f_solo_no_qa" name="solo_no_qa" value="1">
                        <label class="form-check-label" for="f_solo_no_qa" style="font-size:11px;">
                            Excluir QA (solo disponible)
                        </label>
                    </div>
                </div>

                <!-- TERCEROS, PRODUCTO Y FECHAS -->
                <div class="col-12 col-xl-3">
                    <h6 style="font-size:11px;font-weight:bold;">Terceros y producto</h6>

                    <div class="mb-1">
                        <label class="form-label mb-0" style="font-size:11px;">Cliente / Proveedor</label>
                        <select id="f_cliente" name="cliente" class="form-select form-select-sm">
                            <option value="">Todos</option>
                        </select>
                    </div>

                    <div class="mb-1">
                        <label class="form-label mb-0" style="font-size:11px;">Producto</label>
                        <input id="f_producto" name="producto" class="form-control form-control-sm"
                               placeholder="SKU / clave / código">
                    </div>

                    <div class="mb-1">
                        <label class="form-label mb-0" style="font-size:11px;">Lote</label>
                        <input id="f_lote" name="lote" class="form-control form-control-sm"
                               placeholder="Lote o N.S.">
                    </div>

                    <div class="mb-1">
                        <label class="form-label mb-0" style="font-size:11px;">Serie</label>
                        <input id="f_serie" name="serie" class="form-control form-control-sm"
                               placeholder="Serie / N.S.">
                    </div>

                    <h6 class="mt-2" style="font-size:11px;font-weight:bold;">Fechas</h6>

                    <div class="mb-1">
                        <label class="form-label mb-0" style="font-size:11px;">Fecha inicial</label>
                        <input id="f_fini" name="f_ini" type="date" class="form-control form-control-sm">
                    </div>

                    <div class="mb-1">
                        <label class="form-label mb-0" style="font-size:11px;">Fecha final</label>
                        <input id="f_ffin" name="f_fin" type="date" class="form-control form-control-sm">
                    </div>
                </div>

            </div>
        </form>

    </div>
</div>

<!-- Cards -->
<div class="row g-2 mb-2">
    <div class="col-6 col-md-3">
        <div class="card card-kpi" style="border-radius:.75rem;box-shadow:0 2px 4px rgba(0,0,0,.08);">
            <div class="card-body py-2 px-3">
                <div style="font-size:11px;text-transform:uppercase;color:#666;">Registros</div>
                <div id="kpi_total_reg" style="font-size:1.2rem;font-weight:bold;">0</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-kpi" style="border-radius:.75rem;box-shadow:0 2px 4px rgba(0,0,0,.08);">
            <div class="card-body py-2 px-3">
                <div style="font-size:11px;text-transform:uppercase;color:#666;">License Plates</div>
                <div id="kpi_total_lp" style="font-size:1.2rem;font-weight:bold;">0</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-kpi" style="border-radius:.75rem;box-shadow:0 2px 4px rgba(0,0,0,.08);">
            <div class="card-body py-2 px-3">
                <div style="font-size:11px;text-transform:uppercase;color:#666;">Ubicaciones ocupadas</div>
                <div id="kpi_total_ubicas" style="font-size:1.2rem;font-weight:bold;">0</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-kpi" style="border-radius:.75rem;box-shadow:0 2px 4px rgba(0,0,0,.08);">
            <div class="card-body py-2 px-3">
                <div style="font-size:11px;text-transform:uppercase;color:#666;">Productos únicos</div>
                <div id="kpi_total_prod" style="font-size:1.2rem;font-weight:bold;">0</div>
            </div>
        </div>
    </div>
</div>

<!-- Grilla -->
<div class="card">
    <div class="card-body p-2">
        <div class="table-responsive" style="max-height:480px;overflow-y:auto;overflow-x:auto;">
            <table id="tabla_resultados" class="table table-sm table-striped table-hover mb-0">
                <thead class="table-light">
                <tr id="thead_resultados">
                    <!-- Se llena dinámicamente según la receta -->
                </tr>
                </thead>
                <tbody id="tbody_resultados">
                <tr>
                    <td colspan="12" class="text-center text-muted">
                        Seleccione una receta, configure filtros y pulse <strong>Aplicar</strong>.
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-2" id="paginacion_wrap" style="display:none;">
            <div style="font-size:11px;">
                <span id="pag_info"></span>
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn_prev">«</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn_next">»</button>
            </div>
        </div>
    </div>
</div>

<script>
// ------------------ CONFIG ------------------
const API_FILTROS_URL = '../api/filtros_assistpro.php';
const VISTA_DEFAULT   = <?php echo json_encode($vista_id_default, JSON_UNESCAPED_UNICODE); ?>;

// Estado de paginación en front
let state = {
    vista_id: VISTA_DEFAULT || '',
    page: 1,
    per_page: 25,
    total_rows: 0
};

// ------------------ INIT ------------------
document.addEventListener('DOMContentLoaded', () => {
    initFiltrosAssistPro();

    document.getElementById('btn_aplicar').addEventListener('click', () => {
        state.page = 1;
        aplicarFiltros();
    });

    document.getElementById('btn_limpiar').addEventListener('click', limpiarFiltros);

    document.getElementById('btn_prev').addEventListener('click', () => {
        if (state.page > 1) {
            state.page--;
            aplicarFiltros(false);
        }
    });

    document.getElementById('btn_next').addEventListener('click', () => {
        const maxPage = Math.ceil(state.total_rows / state.per_page);
        if (state.page < maxPage) {
            state.page++;
            aplicarFiltros(false);
        }
    });
});

function initFiltrosAssistPro() {
    fetch(API_FILTROS_URL + '?action=init')
        .then(r => r.json())
        .then(data => {
            if (!data.ok) {
                console.error('Init filtros error:', data.error);
                return;
            }

            // Recetas
            const selRec = document.getElementById('receta_vista');
            selRec.innerHTML = '<option value="">— Recetas de vista —</option>';
            (data.recetas || []).forEach(r => {
                const opt = document.createElement('option');
                opt.value = r.id;
                opt.textContent = r.nombre;
                selRec.appendChild(opt);
            });

            if (VISTA_DEFAULT && data.recetas.some(r => r.id === VISTA_DEFAULT)) {
                selRec.value = VISTA_DEFAULT;
                state.vista_id = VISTA_DEFAULT;
            }

            // Empresas
            const selEmp = document.getElementById('f_empresa');
            (data.empresas || []).forEach(e => {
                const opt = document.createElement('option');
                opt.value = e.cve_cia;
                opt.textContent = e.des_cia;
                selEmp.appendChild(opt);
            });

            // Almacenes
            const selAlm = document.getElementById('f_almacen');
            (data.almacenes || []).forEach(a => {
                const opt = document.createElement('option');
                opt.value = a.cve_almac;
                opt.textContent = a.clave_almacen + ' - ' + a.des_almac;
                selAlm.appendChild(opt);
            });

            // Rutas
            const selRuta = document.getElementById('f_ruta');
            (data.rutas || []).forEach(r => {
                const opt = document.createElement('option');
                opt.value = r.cve_ruta;
                opt.textContent = r.cve_ruta + ' - ' + r.descripcion;
                selRuta.appendChild(opt);
            });

            // Clientes
            const selCli = document.getElementById('f_cliente');
            (data.clientes || []).forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.Cve_Clte;
                opt.textContent = c.Cve_Clte + ' - ' + c.RazonSocial;
                selCli.appendChild(opt);
            });

            // Si hay receta default, podemos precargar una vez
            if (state.vista_id) {
                aplicarFiltros();
            }
        })
        .catch(err => {
            console.error('Init filtros error:', err);
        });
}

function limpiarFiltros() {
    document.getElementById('form_filtros').reset();
    document.getElementById('tbody_resultados').innerHTML = `
        <tr>
            <td colspan="12" class="text-center text-muted">
                Seleccione una receta, configure filtros y pulse <strong>Aplicar</strong>.
            </td>
        </tr>`;
    document.getElementById('kpi_total_reg').textContent  = '0';
    document.getElementById('kpi_total_lp').textContent   = '0';
    document.getElementById('kpi_total_ubicas').textContent = '0';
    document.getElementById('kpi_total_prod').textContent = '0';
    state.total_rows = 0;
    document.getElementById('paginacion_wrap').style.display = 'none';
}

function recogerFiltros() {
    const f = document.getElementById('form_filtros');
    const fd = new FormData(f);
    const obj = {};
    fd.forEach((v, k) => {
        if (v !== '') obj[k] = v;
    });
    // check QA
    const chkNoQA = document.getElementById('f_solo_no_qa');
    if (chkNoQA.checked) {
        obj['solo_no_qa'] = '1';
    }
    return obj;
}

function aplicarFiltros(showLoading = true) {
    const vistaSelect = document.getElementById('receta_vista');
    const vistaId = vistaSelect.value;
    if (!vistaId) {
        alert('Seleccione una receta de vista.');
        return;
    }
    state.vista_id = vistaId;

    const filtros = recogerFiltros();
    const params = new URLSearchParams();
    params.append('action', 'consulta');
    params.append('vista_id', vistaId);
    params.append('page', state.page);
    params.append('per_page', state.per_page);
    Object.keys(filtros).forEach(k => params.append(k, filtros[k]));

    if (showLoading) {
        document.getElementById('tbody_resultados').innerHTML = `
            <tr><td colspan="12" class="text-center text-muted">Cargando...</td></tr>`;
    }

    fetch(API_FILTROS_URL, {
        method: 'POST',
        body: params
    })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) {
                console.error('Consulta error:', data.error);
                document.getElementById('tbody_resultados').innerHTML = `
                    <tr><td colspan="12" class="text-center text-danger">
                        Error en consulta: ${data.error || 'desconocido'}
                    </td></tr>`;
                return;
            }

            // KPIs
            const cards = data.cards || {};
            document.getElementById('kpi_total_reg').textContent   = (cards.total_registros || 0).toLocaleString();
            document.getElementById('kpi_total_lp').textContent    = (cards.total_lps || 0).toLocaleString();
            document.getElementById('kpi_total_ubicas').textContent = (cards.total_ubicaciones || 0).toLocaleString();
            document.getElementById('kpi_total_prod').textContent  = (cards.total_productos || 0).toLocaleString();

            // Grilla
            const rows = data.rows || [];
            state.total_rows = data.total_rows || rows.length;

            pintarTablaSegunReceta(vistaId, rows);

            // Paginación
            const maxPage = Math.max(1, Math.ceil(state.total_rows / state.per_page));
            document.getElementById('paginacion_wrap').style.display = (maxPage > 1 ? 'flex' : 'none');
            document.getElementById('pag_info').textContent =
                `Página ${state.page} de ${maxPage} — ${state.total_rows.toLocaleString()} reg.`;
        })
        .catch(err => {
            console.error('Consulta error:', err);
            document.getElementById('tbody_resultados').innerHTML = `
                <tr><td colspan="12" class="text-center text-danger">
                    Error de comunicación con el servidor.
                </td></tr>`;
        });
}

function pintarTablaSegunReceta(vistaId, rows) {
    const thead = document.getElementById('thead_resultados');
    const tbody = document.getElementById('tbody_resultados');
    thead.innerHTML = '';
    tbody.innerHTML = '';

    if (vistaId === 'existencias_ubicacion') {
        // Cabecera específica
        const cols = [
            'BL','Pasillo','Rack','Nivel',
            'Almacén',
            'Producto','Descripción','Tipo','Lote/Serie','Caducidad',
            'Existencia','QA','Estado BL','LP'
        ];
        cols.forEach(c => {
            const th = document.createElement('th');
            th.textContent = c;
            if (c === 'Existencia') th.classList.add('text-end');
            thead.appendChild(th);
        });

        if (!rows.length) {
            tbody.innerHTML = `
                <tr><td colspan="${cols.length}" class="text-center text-muted">
                    No se encontraron registros con los filtros aplicados.
                </td></tr>`;
            return;
        }

        rows.forEach(r => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${escapeHtml(r.bl ?? '')}</td>
                <td>${escapeHtml(r.pasillo ?? '')}</td>
                <td>${escapeHtml(r.rack ?? '')}</td>
                <td>${escapeHtml(r.nivel ?? '')}</td>
                <td>${escapeHtml(r.cve_almac ?? '')}</td>
                <td>${escapeHtml(r.cve_articulo ?? '')}</td>
                <td>${escapeHtml(r.des_articulo ?? '')}</td>
                <td>${escapeHtml(r.tipo_control ?? '')}</td>
                <td>${escapeHtml(r.cve_lote ?? '')}</td>
                <td>${escapeHtml(r.Caducidad ?? '')}</td>
                <td class="text-end">${Number(r.existencia ?? 0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</td>
                <td class="text-center">${r.es_qa ? 'QA' : ''}</td>
                <td>${escapeHtml(r.estado_bl ?? '')}</td>
                <td>${escapeHtml(r.CveLP ?? '')}</td>
            `;
            tbody.appendChild(tr);
        });
    } else {
        // Receta desconocida
        thead.innerHTML = '<th>Resultado</th>';
        tbody.innerHTML = `
            <tr><td class="text-center text-muted">
                Receta no configurada todavía.
            </td></tr>`;
    }
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
