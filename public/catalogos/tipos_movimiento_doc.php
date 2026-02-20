<?php
// NO usamos sesión
require_once '../bi/_menu_global.php';
?>

<style>
    /* Global */
    :root{
        --corp-blue:#0b3a82;   /* azul corporativo */
        --ok:#198754;
        --warn:#ffc107;
        --info:#0dcaf0;
        --danger:#dc3545;
        --muted:#6c757d;
        --card-border:#e9ecef;
    }
    .tm-wrap, .tm-wrap *{ font-size:10px !important; }
    .tm-title{
        display:flex; align-items:center; gap:10px;
        color:var(--corp-blue);
        font-weight:800;
        font-size:18px !important;
        margin: 6px 0 12px 0;
    }
    .tm-title .tm-ico{
        width:20px; height:20px; display:inline-flex; align-items:center; justify-content:center;
        color:var(--corp-blue);
    }
    .tm-toolbar{
        background:#fff;
        border:1px solid var(--card-border);
        border-radius:10px;
        padding:10px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        margin-bottom:10px;
    }
    .tm-left{
        display:flex; align-items:center; gap:8px; flex-wrap:wrap;
    }
    .tm-label{ font-weight:700; color:#212529; }
    .tm-select, .tm-input{
        height:30px;
        border:1px solid #d0d7de;
        border-radius:8px;
        padding:4px 8px;
        outline:none;
        min-width:120px;
        background:#fff;
    }
    .tm-btn{
        height:30px;
        border-radius:8px;
        padding:4px 10px;
        border:1px solid transparent;
        cursor:pointer;
        font-weight:700;
        display:inline-flex; align-items:center; gap:6px;
    }
    .tm-btn-primary{ background:#0d6efd; color:#fff; }
    .tm-btn-success{ background:#146c43; color:#fff; }
    .tm-btn-outline{
        background:#fff; border-color:#d0d7de; color:#212529;
    }
    .tm-btn:disabled{ opacity:.6; cursor:not-allowed; }

    /* KPI cards */
    .tm-kpis{
        display:grid;
        grid-template-columns: repeat(4, minmax(160px, 1fr));
        gap:10px;
        margin-bottom:10px;
    }
    .tm-kpi{
        background:#fff;
        border:1px solid var(--card-border);
        border-radius:10px;
        padding:10px;
        display:flex;
        flex-direction:column;
        gap:6px;
        min-height:62px;
    }
    .tm-kpi .kpi-title{ color:var(--muted); font-weight:700; }
    .tm-kpi .kpi-val{ font-size:16px !important; font-weight:900; line-height:1; }
    .tm-kpi.total{ border-color:#ced4da; }
    .tm-kpi.activos{ border-color:rgba(25,135,84,.55); }
    .tm-kpi.inactivos{ border-color:rgba(255,193,7,.65); }
    .tm-kpi.folio{ border-color:rgba(13,202,240,.7); }
    .tm-kpi.activos .kpi-val{ color:var(--ok); }
    .tm-kpi.inactivos .kpi-val{ color:var(--warn); }
    .tm-kpi.folio .kpi-val{ color:var(--info); }

    /* Table */
    .tm-card{
        background:#fff;
        border:1px solid var(--card-border);
        border-radius:10px;
        padding:10px;
    }
    .tm-table{
        width:100%;
        border-collapse:collapse;
    }
    .tm-table th, .tm-table td{
        border:1px solid #e5e7eb;
        padding:8px;
        text-align:left;
        vertical-align:middle;
        white-space:nowrap;
    }
    .tm-table th{
        background:#f8f9fa;
        font-weight:800;
    }
    .tm-badge{
        padding:3px 8px;
        border-radius:999px;
        font-weight:800;
        display:inline-block;
    }
    .tm-badge.ok{ background:rgba(25,135,84,.12); color:var(--ok); border:1px solid rgba(25,135,84,.25); }
    .tm-badge.off{ background:rgba(220,53,69,.10); color:var(--danger); border:1px solid rgba(220,53,69,.22); }

    .tm-actions{
        display:flex; gap:6px; align-items:center;
    }
    .tm-iconbtn{
        height:26px; width:26px;
        border-radius:8px;
        border:1px solid #d0d7de;
        background:#fff;
        cursor:pointer;
        display:inline-flex; align-items:center; justify-content:center;
    }
    .tm-iconbtn:hover{ background:#f8f9fa; }
    .tm-iconbtn.edit{ border-color:rgba(13,110,253,.35); }
    .tm-iconbtn.toggle{ border-color:rgba(220,53,69,.28); }

    /* Modal (sin Bootstrap para evitar dependencia) */
    .tm-modal-backdrop{
        position:fixed; inset:0;
        background:rgba(0,0,0,.35);
        display:none;
        align-items:center; justify-content:center;
        z-index:9999;
        padding:16px;
    }
    .tm-modal{
        width:min(520px, 100%);
        background:#fff;
        border-radius:12px;
        border:1px solid #e5e7eb;
        box-shadow:0 20px 60px rgba(0,0,0,.22);
        overflow:hidden;
    }
    .tm-modal-head{
        padding:10px 12px;
        background:#f8f9fa;
        border-bottom:1px solid #e5e7eb;
        display:flex; align-items:center; justify-content:space-between;
    }
    .tm-modal-title{
        font-size:12px !important;
        font-weight:900;
        color:var(--corp-blue);
        display:flex; align-items:center; gap:8px;
    }
    .tm-x{
        border:1px solid #d0d7de;
        background:#fff;
        width:28px; height:28px;
        border-radius:8px;
        cursor:pointer;
        display:flex; align-items:center; justify-content:center;
        font-weight:900;
    }
    .tm-modal-body{ padding:12px; }
    .tm-grid{
        display:grid;
        grid-template-columns: 1fr 1fr;
        gap:10px;
    }
    .tm-field label{
        display:block; font-weight:800; margin-bottom:4px;
    }
    .tm-field .tm-input, .tm-field .tm-select{
        width:100%;
        height:30px;
    }
    .tm-modal-foot{
        padding:10px 12px;
        border-top:1px solid #e5e7eb;
        display:flex; gap:8px; justify-content:flex-end;
        background:#fff;
    }
    .tm-hint{ color:var(--muted); margin-top:6px; }
    .tm-error{
        margin-top:8px;
        padding:8px;
        border-radius:10px;
        border:1px solid rgba(220,53,69,.28);
        background:rgba(220,53,69,.06);
        color:#842029;
        display:none;
        white-space:pre-wrap;
    }

    @media (max-width: 900px){
        .tm-kpis{ grid-template-columns: 1fr 1fr; }
        .tm-grid{ grid-template-columns: 1fr; }
    }
</style>

<div class="tm-wrap">
    <div class="tm-title">
        <span class="tm-ico" aria-hidden="true">
            <!-- Icono (SVG) -->
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                <path d="M7 7h10M7 12h10M7 17h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3h11A2.5 2.5 0 0 1 20 5.5v13A2.5 2.5 0 0 1 17.5 21h-11A2.5 2.5 0 0 1 4 18.5v-13Z" stroke="currentColor" stroke-width="2"/>
            </svg>
        </span>
        Catálogo de Tipos de Movimiento
    </div>

    <div class="tm-toolbar">
        <div class="tm-left">
            <span class="tm-label">Módulo:</span>
            <select id="modulo" class="tm-select">
                <option value="OC">OC</option>
                <option value="REC">REC</option>
                <option value="INV">INV</option>
                <option value="OT">OT</option>
                <option value="ASN">ASN</option>
                <option value="TR">TR</option>
                <option value="OT_IMPORT">OT_IMPORT</option>
            </select>

            <button id="btnBuscar" class="tm-btn tm-btn-primary" type="button">
                Buscar
            </button>
        </div>

        <button id="btnNuevo" class="tm-btn tm-btn-success" type="button">
            + Nuevo
        </button>
    </div>

    <div class="tm-kpis">
        <div class="tm-kpi total">
            <div class="kpi-title">Total</div>
            <div class="kpi-val" id="kpi_total">0</div>
        </div>
        <div class="tm-kpi activos">
            <div class="kpi-title">Activos</div>
            <div class="kpi-val" id="kpi_activos">0</div>
        </div>
        <div class="tm-kpi inactivos">
            <div class="kpi-title">Inactivos</div>
            <div class="kpi-val" id="kpi_inactivos">0</div>
        </div>
        <div class="tm-kpi folio">
            <div class="kpi-title">Requieren Folio</div>
            <div class="kpi-val" id="kpi_folio">0</div>
        </div>
    </div>

    <div class="tm-card">
        <table class="tm-table">
            <thead>
                <tr>
                    <th style="width:76px;">Acciones</th>
                    <th style="width:90px;">Código</th>
                    <th>Nombre</th>
                    <th style="width:120px;">Requiere Folio</th>
                    <th style="width:110px;">Estado</th>
                </tr>
            </thead>
            <tbody id="tbody">
                <tr><td colspan="5" style="text-align:center;color:#6c757d;">Sin registros</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL Alta/Edición (sin Bootstrap) -->
<div id="modalBackdrop" class="tm-modal-backdrop" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="tm-modal">
        <div class="tm-modal-head">
            <div class="tm-modal-title">
                <span aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </span>
                <span id="modalTitle">Nuevo Tipo de Movimiento</span>
            </div>
            <button class="tm-x" type="button" id="btnCerrarModal">×</button>
        </div>

        <div class="tm-modal-body">
            <div class="tm-grid">
                <div class="tm-field">
                    <label>Módulo</label>
                    <input id="f_modulo" class="tm-input" type="text" readonly>
                </div>

                <div class="tm-field">
                    <label>Código</label>
                    <input id="f_codigo" class="tm-input" type="text" maxlength="20" placeholder="Ej. OCN">
                </div>

                <div class="tm-field" style="grid-column: 1 / -1;">
                    <label>Nombre</label>
                    <input id="f_nombre" class="tm-input" type="text" maxlength="100" placeholder="Ej. Orden de Compra Nacional">
                </div>

                <div class="tm-field" style="grid-column: 1 / -1;">
                    <label>Descripción (opcional)</label>
                    <input id="f_descripcion" class="tm-input" type="text" maxlength="255" placeholder="Descripción operativa">
                </div>

                <div class="tm-field">
                    <label>Requiere Folio</label>
                    <select id="f_requiere_folio" class="tm-select">
                        <option value="1">Sí</option>
                        <option value="0">No</option>
                    </select>
                </div>

                <div class="tm-field">
                    <label>Estado</label>
                    <select id="f_activo" class="tm-select">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
            </div>

            <div class="tm-hint">Nota: Este catálogo alimenta el control de movimientos y la lógica de folios.</div>
            <div id="modalError" class="tm-error"></div>
        </div>

        <div class="tm-modal-foot">
            <button class="tm-btn tm-btn-outline" type="button" id="btnCancelar">Cancelar</button>
            <button class="tm-btn tm-btn-primary" type="button" id="btnGuardar">Guardar</button>
        </div>
    </div>
</div>

<script>
/**
 * RUTAS (robustas)
 * La vista está en: /public/catalogos/tipos_movimiento_doc.php
 * El API está en:   /public/api/catalogos/tipos_movimiento_doc.php
 */
const API_LIST = '../api/catalogos/tipos_movimiento_doc.php';

// Empresa fija (ajusta si tu UI ya la trae de otro lado)
const EMPRESA_ID = 1;

let cacheRows = [];
let editRow = null;

function setTbody(html){
    document.getElementById('tbody').innerHTML = html;
}

function setKpis(rows){
    const total = rows.length;
    const activos = rows.filter(r => String(r.activo) === '1').length;
    const inactivos = total - activos;
    const folio = rows.filter(r => String(r.requiere_folio) === '1').length;

    document.getElementById('kpi_total').innerText = total;
    document.getElementById('kpi_activos').innerText = activos;
    document.getElementById('kpi_inactivos').innerText = inactivos;
    document.getElementById('kpi_folio').innerText = folio;
}

function normalizeResponse(json){
    // API puede regresar:
    // 1) { ok:true, data:[...] }
    // 2) { data:[...] }
    // 3) [...]
    // 4) { rows:[...] }
    if (Array.isArray(json)) return json;
    if (json && Array.isArray(json.data)) return json.data;
    if (json && Array.isArray(json.rows)) return json.rows;
    return [];
}

async function fetchJson(url, options){
    const res = await fetch(url, options || {});
    const text = await res.text();

    // Intentar JSON sin tronar la UI
    let json = null;
    try { json = JSON.parse(text); } catch(e){}

    if (!res.ok){
        // si no es JSON, mostrar crudo
        const msg = json ? JSON.stringify(json) : text;
        throw new Error('HTTP ' + res.status + ' - ' + msg);
    }
    if (!json){
        throw new Error('Respuesta no-JSON: ' + text);
    }
    return json;
}

function iconPencil(){
    return `
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 20h9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4 11.5-11.5Z"
                  stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
        </svg>`;
}

function iconToggle(){
    return `
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M4 12a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <path d="M20 12a8 8 0 0 1-16 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" opacity=".35"/>
            <path d="M12 7v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>`;
}

function renderRows(rows){
    if (!rows.length){
        setTbody('<tr><td colspan="5" style="text-align:center;color:#6c757d;">Sin registros</td></tr>');
        return;
    }

    let html = '';
    rows.forEach((r, idx) => {
        const codigo = r.codigo ?? '';
        const nombre = r.nombre ?? '';
        const requiere = String(r.requiere_folio) === '1' ? 'Sí' : 'No';
        const activo = String(r.activo) === '1';

        html += `
        <tr>
            <td>
                <div class="tm-actions">
                    <button class="tm-iconbtn edit" type="button" title="Editar"
                        onclick="uiEditar(${idx})">${iconPencil()}</button>
                    <button class="tm-iconbtn toggle" type="button" title="${activo ? 'Desactivar' : 'Activar'}"
                        onclick="uiToggle(${idx})">${iconToggle()}</button>
                </div>
            </td>
            <td>${escapeHtml(codigo)}</td>
            <td>${escapeHtml(nombre)}</td>
            <td>${escapeHtml(requiere)}</td>
            <td>
                ${activo
                    ? '<span class="tm-badge ok">Activo</span>'
                    : '<span class="tm-badge off">Inactivo</span>'}
            </td>
        </tr>`;
    });
    setTbody(html);
}

function escapeHtml(str){
    return String(str ?? '')
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'","&#039;");
}

async function cargar(){
    const modulo = document.getElementById('modulo').value;
    const btn = document.getElementById('btnBuscar');
    btn.disabled = true;

    try{
        // GET con query params
        const url = API_LIST + '?empresa_id=' + encodeURIComponent(EMPRESA_ID) + '&modulo=' + encodeURIComponent(modulo);
        const json = await fetchJson(url, { method:'GET' });
        const rows = normalizeResponse(json);

        cacheRows = rows;
        setKpis(rows);
        renderRows(rows);

    }catch(err){
        console.error(err);
        cacheRows = [];
        setKpis([]);
        setTbody('<tr><td colspan="5" style="text-align:center;color:#dc3545;">Error cargando datos. Revisa consola.</td></tr>');
    }finally{
        btn.disabled = false;
    }
}

/**
 * Modal (sin bootstrap)
 */
function modalOpen(){
    const b = document.getElementById('modalBackdrop');
    b.style.display = 'flex';
    b.setAttribute('aria-hidden','false');
}
function modalClose(){
    const b = document.getElementById('modalBackdrop');
    b.style.display = 'none';
    b.setAttribute('aria-hidden','true');
    document.getElementById('modalError').style.display='none';
    document.getElementById('modalError').innerText='';
}

function abrirAlta(){
    editRow = null;
    const modulo = document.getElementById('modulo').value;

    document.getElementById('modalTitle').innerText = 'Nuevo Tipo de Movimiento';
    document.getElementById('f_modulo').value = modulo;
    document.getElementById('f_codigo').value = '';
    document.getElementById('f_codigo').readOnly = false;

    document.getElementById('f_nombre').value = '';
    document.getElementById('f_descripcion').value = '';
    document.getElementById('f_requiere_folio').value = '1';
    document.getElementById('f_activo').value = '1';

    modalOpen();
}

function uiEditar(idx){
    const r = cacheRows[idx];
    if (!r) return;

    editRow = r;

    document.getElementById('modalTitle').innerText = 'Editar Tipo de Movimiento';
    document.getElementById('f_modulo').value = r.modulo ?? document.getElementById('modulo').value;
    document.getElementById('f_codigo').value = r.codigo ?? '';
    document.getElementById('f_codigo').readOnly = true; // clave de negocio

    document.getElementById('f_nombre').value = r.nombre ?? '';
    document.getElementById('f_descripcion').value = r.descripcion ?? '';
    document.getElementById('f_requiere_folio').value = String(r.requiere_folio ?? '1');
    document.getElementById('f_activo').value = String(r.activo ?? '1');

    modalOpen();
}

/**
 * Guardar:
 * - En muchos catálogos ustedes manejan "upsert" por (empresa_id, modulo, codigo).
 * - Enviamos POST x-www-form-urlencoded a la MISMA API (si tu API ya lo soporta).
 *   Si tu API separa endpoints, solo cambia API_SAVE / API_TOGGLE aquí.
 */
const API_SAVE   = '../api/catalogos/tipos_movimiento_doc.php';
const API_TOGGLE = '../api/catalogos/tipos_movimiento_doc.php';

function showModalError(msg){
    const box = document.getElementById('modalError');
    box.style.display='block';
    box.innerText = msg;
}

async function guardar(){
    const payload = {
        empresa_id: EMPRESA_ID,
        modulo: document.getElementById('f_modulo').value.trim(),
        codigo: document.getElementById('f_codigo').value.trim(),
        nombre: document.getElementById('f_nombre').value.trim(),
        descripcion: document.getElementById('f_descripcion').value.trim(),
        requiere_folio: document.getElementById('f_requiere_folio').value,
        activo: document.getElementById('f_activo').value,
        accion: editRow ? 'update' : 'create'
    };

    if (!payload.modulo || !payload.codigo || !payload.nombre){
        showModalError('Campos obligatorios: Módulo, Código, Nombre.');
        return;
    }

    const btn = document.getElementById('btnGuardar');
    btn.disabled = true;

    try{
        const body = new URLSearchParams(payload).toString();
        const json = await fetchJson(API_SAVE, {
            method:'POST',
            headers:{ 'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8' },
            body
        });

        // Acepta {ok:true} o cualquier respuesta exitosa
        modalClose();
        await cargar();

    }catch(err){
        console.error(err);
        showModalError('No se pudo guardar.\n' + err.message);
    }finally{
        btn.disabled = false;
    }
}

async function uiToggle(idx){
    const r = cacheRows[idx];
    if (!r) return;

    const nuevo = (String(r.activo) === '1') ? '0' : '1';

    try{
        const payload = {
            empresa_id: EMPRESA_ID,
            modulo: r.modulo ?? document.getElementById('modulo').value,
            codigo: r.codigo,
            activo: nuevo,
            accion: 'toggle'
        };
        const body = new URLSearchParams(payload).toString();

        await fetchJson(API_TOGGLE, {
            method:'POST',
            headers:{ 'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8' },
            body
        });

        await cargar();
    }catch(err){
        console.error(err);
        alert('No se pudo cambiar el estado. Revisa consola.');
    }
}

/**
 * Eventos
 */
document.getElementById('btnBuscar').addEventListener('click', cargar);
document.getElementById('btnNuevo').addEventListener('click', abrirAlta);
document.getElementById('btnCerrarModal').addEventListener('click', modalClose);
document.getElementById('btnCancelar').addEventListener('click', modalClose);
document.getElementById('btnGuardar').addEventListener('click', guardar);

// Cerrar modal con click fuera
document.getElementById('modalBackdrop').addEventListener('click', (e)=>{
    if (e.target && e.target.id === 'modalBackdrop') modalClose();
});

// Cargar inicial
cargar();
</script>

<?php
require_once '../bi/_menu_global_end.php';
?>
