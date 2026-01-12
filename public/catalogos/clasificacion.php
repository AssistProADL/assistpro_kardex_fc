<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
    /* =========================================================
   ASSISTPRO STYLES
========================================================= */
    body {
        font-family: system-ui, -apple-system, sans-serif;
        background: #f4f6fb;
        margin: 0;
    }

    .ap-container {
        padding: 20px;
        font-size: 13px;
        max-width: 100%;
        margin: 0 auto;
    }

    /* TITLE */
    .ap-title {
        font-size: 20px;
        font-weight: 600;
        color: #0b5ed7;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* TOOLBAR */
    .ap-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        margin-bottom: 15px;
        background: #fff;
        padding: 10px;
        border-radius: 10px;
        border: 1px solid #e0e6ed;
    }

    .ap-search {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 1;
        min-width: 300px;
        background: #f8f9fa;
        padding: 6px 12px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }

    .ap-search i {
        color: #6c757d;
    }

    .ap-search input {
        border: none;
        background: transparent;
        outline: none;
        width: 100%;
        font-size: 13px;
    }

    /* CHIPS / BUTTONS */
    .ap-chip {
        font-size: 12px;
        background: #f1f3f5;
        color: #495057;
        border: 1px solid #dee2e6;
        border-radius: 20px;
        padding: 5px 12px;
        display: inline-flex;
        gap: 6px;
        align-items: center;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
    }

    .ap-chip:hover {
        background: #e9ecef;
        color: #212529;
        border-color: #ced4da;
    }

    .ap-chip.primary {
        background: #0b5ed7;
        color: #fff;
        border-color: #0b5ed7;
    }

    .ap-chip.primary:hover {
        background: #0a58ca;
    }

    .ap-chip.ok {
        background: #d1e7dd;
        color: #0f5132;
        border-color: #badbcc;
    }

    .ap-chip.warn {
        background: #fff3cd;
        color: #664d03;
        border-color: #ffecb5;
    }

    button.ap-chip {
        font-family: inherit;
    }

    /* GRID */
    .ap-grid {
        background: #fff;
        border: 1px solid #e0e6ed;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        height: calc(100vh - 240px);
        overflow-y: auto;
    }

    .ap-grid table {
        width: 100%;
        border-collapse: collapse;
    }

    .ap-grid th {
        background: #f8f9fa;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #495057;
        border-bottom: 1px solid #dee2e6;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .ap-grid td {
        padding: 10px 12px;
        border-bottom: 1px solid #f1f3f5;
        color: #212529;
        vertical-align: middle;
        white-space: nowrap;
    }

    .ap-grid tr:hover td {
        background: #f8f9fa;
    }

    .ap-actions i {
        cursor: pointer;
        margin-right: 12px;
        color: #6c757d;
        transition: color 0.2s;
        font-size: 14px;
    }

    .ap-actions i:hover {
        color: #0b5ed7;
    }

    /* MODAL */
    .ap-modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(2px);
    }

    .ap-modal[style*="display: block"] {
        display: flex !important;
    }

    .ap-modal-content {
        background: #fff;
        width: 600px;
        max-width: 95%;
        max-height: 90vh;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        overflow-y: auto;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        padding: 20px;
    }

    .ap-form {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-top: 15px;
    }

    .ap-form.full {
        grid-template-columns: 1fr;
    }

    .ap-field {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .ap-label {
        font-weight: 500;
        font-size: 13px;
        color: #495057;
    }

    .ap-input {
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 8px 12px;
        background: #fff;
        transition: all 0.2s;
    }

    .ap-input:focus-within {
        border-color: #0b5ed7;
        box-shadow: 0 0 0 3px rgba(11, 94, 215, 0.1);
    }

    .ap-input input,
    .ap-input select {
        border: none;
        outline: none;
        width: 100%;
        font-size: 14px;
        color: #212529;
        background: transparent;
    }

    /* PAGER */
    .ap-pager {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 15px;
        padding: 0 5px;
    }

    button.primary {
        background: #0b5ed7;
        color: #fff;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.2s;
    }

    button.primary:hover {
        background: #0a58ca;
    }

    button.ghost {
        background: #fff;
        color: #495057;
        border: 1px solid #dee2e6;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

    button.ghost:hover {
        background: #f1f3f5;
        border-color: #ced4da;
    }
</style>

<div class="ap-container">
    <div class="ap-title"><i class="fa fa-tags"></i> Clasificación de Artículos</div>

    <!-- TOOLBAR -->
    <div class="ap-toolbar">
        <div class="ap-search">
            <i class="fa fa-search"></i>
            <input id="q" placeholder="Buscar clave o descripción..." onkeydown="if(event.key==='Enter')buscar()">
        </div>
        <button class="ap-chip" onclick="buscar()">Buscar</button>
        <button class="ap-chip" onclick="limpiar()">Limpiar</button>


        <button class="ap-chip" id="btnToggleInactive" onclick="toggleInactivos()">
            <i class="fa fa-eye"></i> Ver Inactivos
        </button>

        <div style="flex:1"></div>

        <button class="ap-chip primary" onclick="nuevo()"><i class="fa fa-plus"></i> Nuevo</button>
        <button class="ap-chip" onclick="exportar()"><i class="fa fa-download"></i> Exportar</button>
        <button class="ap-chip" onclick="abrirImport()"><i class="fa fa-upload"></i> Importar CSV</button>
    </div>

    <span class="ap-chip" id="msg" style="display:none; margin-bottom:10px;"></span>

    <!-- GRID -->
    <div class="ap-grid">
        <table>
            <thead>
                <tr>
                    <th>Opciones</th>
                    <th>Clave</th>
                    <th>Grupo</th>
                    <th>Descripción</th>
                    <th>Almacén</th>
                    <th>Múltiplo</th>
                    <th>Incluye</th>
                    <th>Estatus</th>
                </tr>
            </thead>
            <tbody id="tb"></tbody>
        </table>
    </div>

    <!-- PAGER -->
    <div id="pager" class="ap-pager"></div>
</div>

<!-- MODAL EDIT/NEW -->
<div class="ap-modal" id="mdl">
    <div class="ap-modal-content">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px">
            <h3 style="margin:0"><i class="fa fa-tag"></i> <span id="mdlTitle">Clasificación</span></h3>
            <button onclick="cerrarModal('mdl')"
                style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i
                    class="fa fa-times"></i></button>
        </div>

        <input type="hidden" id="mID" value="">

        <div class="ap-form">
            <div class="ap-field">
                <div class="ap-label">Clave *</div>
                <div class="ap-input"><input id="mClave" maxlength="50"></div>
            </div>
            <div class="ap-field">
                <div class="ap-label">Grupo</div>
                <div class="ap-input"><input id="mGrupo" maxlength="50"></div>
            </div>
        </div>

        <div class="ap-form full" style="margin-top:10px; grid-template-columns: 1fr;">
            <div class="ap-field">
                <div class="ap-label">Descripción *</div>
                <div class="ap-input"><input id="mDesc" maxlength="255"></div>
            </div>
        </div>

        <div class="ap-form" style="margin-top:10px;">
            <div class="ap-field">
                <div class="ap-label">Almacén</div>
                <div class="ap-input">
                    <select id="mAlmacen">
                        <option value="">Cargando...</option>
                    </select>
                </div>
            </div>
            <div class="ap-field">
                <div class="ap-label">Múltiplo</div>
                <div class="ap-input"><input type="number" id="mMultiplo" value="0"></div>
            </div>
        </div>

        <div class="ap-form full" style="margin-top:10px;">
            <label style="display:flex; gap:8px; align-items:center; font-size:13px; cursor:pointer;">
                <input type="checkbox" id="mIncluye">
                <span>Incluye (Ban_Incluye)</span>
            </label>
        </div>

        <div style="text-align:right;margin-top:20px;display:flex;justify-content:flex-end;gap:10px">
            <button class="ghost" onclick="cerrarModal('mdl')">Cancelar</button>
            <button class="primary" onclick="guardar()">Guardar</button>
        </div>
    </div>
</div>

<!-- MODAL IMPORT -->
<div class="ap-modal" id="mdlImport">
    <div class="ap-modal-content" style="width:500px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px">
            <h3 style="margin:0"><i class="fa fa-upload"></i> Importar CSV</h3>
            <button onclick="cerrarModal('mdlImport')"
                style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i
                    class="fa fa-times"></i></button>
        </div>

        <div class="ap-chip"
            style="margin-bottom:15px; width:100%; justify-content:center; flex-direction:column; gap:5px;">
            <div>Layout: Clave, Grupo, Descripción, Almacén, Múltiplo, Incluye</div>
            <button class="ghost" style="font-size:10px; padding:2px 6px;" onclick="bajarLayout()">Descargar Layout
                Modelo</button>
        </div>

        <div class="ap-input">
            <i class="fa fa-file-csv"></i>
            <input type="file" id="fileCsv" accept=".csv">
        </div>

        <div id="importResult" style="margin-top:15px;"></div>
        <!-- Preview Area -->
        <div id="importPreview"
            style="display:none; max-height:200px; overflow:auto; margin-top:10px; border:1px solid #eee;">
            <table class="table table-sm" style="font-size:11px; width:100%;">
                <thead id="impHead"></thead>
                <tbody id="impBody"></tbody>
            </table>
        </div>

        <div style="margin-top:15px; display:flex; gap:10px; justify-content:flex-end;">
            <button class="ghost" onclick="previsualizar()">Previsualizar</button>
            <button class="primary" onclick="procesarImport()"><i class="fa fa-cloud-arrow-up"></i> Importar</button>
        </div>
    </div>
</div>

<script>
    const API = '../api/clasificacion.php';
    const API_ALMACEN = '../api/filtros_almacenes.php';

    let curPage = 1;
    let viewInactive = false;
    let dataCache = [];
    let importData = []; // Para guardar datos parseados antes de enviar

    function showMsg(txt, cls = '') {
        const m = document.getElementById('msg');
        m.style.display = 'inline-flex';
        m.className = 'ap-chip ' + cls;
        m.innerHTML = txt;
        setTimeout(() => { m.style.display = 'none' }, 3500);
    }

    function abrirModal(id) { document.getElementById(id).style.display = 'block'; }
    function cerrarModal(id) { document.getElementById(id).style.display = 'none'; }

    function esc(s) {
        return (s ?? '').toString().replace(/[&<>"']/g, m => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" }[m]));
    }

    function toggleInactivos() {
        viewInactive = !viewInactive;
        const btn = document.getElementById('btnToggleInactive');
        if (viewInactive) {
            btn.classList.add('warn');
            btn.innerHTML = '<i class="fa fa-eye-slash"></i> Ocultar Inactivos';
        } else {
            btn.classList.remove('warn');
            btn.innerHTML = '<i class="fa fa-eye"></i> Ver Inactivos';
        }
        refrescar(1);
    }

    function refrescar(p = 1) {
        curPage = p;
        const q = document.getElementById('q').value;

        // API logic translation
        const start = (curPage - 1) * 25;
        const params = new URLSearchParams();
        params.append('action', 'list');
        params.append('start', start);
        params.append('length', 25);
        params.append('draw', 1);
        params.append('search[value]', q);
        params.append('inactivos', viewInactive ? 1 : 0);

        fetch(API + '?' + params.toString())
            .then(r => r.json())
            .then(d => {
                dataCache = d.data || [];
                const total = d.recordsFiltered || 0; // API returns filtered count
                renderGrid(dataCache);
                renderPager(total);
            })
            .catch(e => console.error(e));
    }

    function renderGrid(rows) {
        const tb = document.getElementById('tb');
        if (!rows.length) {
            tb.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:20px;color:#777">Sin resultados</td></tr>';
            return;
        }
        tb.innerHTML = rows.map(r => {
            const st = parseInt(r.Activo ?? 1);
            const cls = st === 1 ? 'ok' : 'warn';
            const txt = st === 1 ? 'Activo' : 'Inactivo';
            // Ajuste de IDs según API (usamos 'id' para acciones)

            return `<tr>
        <td class="ap-actions">
           <i class="fa fa-pen" title="Editar" onclick="editar(${r.id})"></i>
           ${st === 1
                    ? `<i class="fa fa-ban" title="Inactivar" onclick="toggle(${r.id}, 'delete')"></i>`
                    : `<i class="fa fa-rotate-left" title="Recuperar" onclick="toggle(${r.id}, 'restore')"></i>`
                }
        </td>
        <td>${esc(r.cve_sgpoart)}</td>
        <td>${esc(r.cve_gpoart)}</td>
        <td><div style="max-width:250px;overflow:hidden;text-overflow:ellipsis" title="${esc(r.des_sgpoart)}">${esc(r.des_sgpoart)}</div></td>
        <td>${esc(r.id_almacen)}</td>
        <td>${esc(r.Num_Multiplo)}</td>
        <td>${r.Ban_Incluye == 1 ? 'Sí' : 'No'}</td>
        <td><span class="ap-chip ${cls}" style="padding:2px 8px;font-size:11px;">${txt}</span></td>
      </tr>`;
        }).join('');
    }

    function renderPager(filtered) {
        const p = document.getElementById('pager');
        const totalPages = Math.ceil(filtered / 25);
        const start = filtered > 0 ? (curPage - 1) * 25 + 1 : 0;
        const end = Math.min(curPage * 25, filtered);

        const prev = curPage > 1 ? `<button class="ap-chip" onclick="refrescar(${curPage - 1})"><i class="fa fa-chevron-left"></i></button>` : '';
        const next = curPage < totalPages ? `<button class="ap-chip" onclick="refrescar(${curPage + 1})"><i class="fa fa-chevron-right"></i></button>` : '';

        p.innerHTML = `
      <div style="font-size:12px;color:#666">
         Mostrando ${start}-${end} de ${filtered} registros
      </div>
      <div style="display:flex;gap:5px">
        ${prev}
        <span class="ap-chip" style="cursor:default">Página ${curPage}</span>
        ${next}
      </div>
    `;
    }

    function buscar() { refrescar(1); }
    function limpiar() {
        document.getElementById('q').value = '';
        refrescar(1);
    }

    // --- ALMACENES ---
    function cargarAlmacenes(selectedVal = null) {
        const sel = document.getElementById('mAlmacen');
        sel.innerHTML = '<option value="">Cargando...</option>';
        fetch(API_ALMACEN)
            .then(r => r.json())
            .then(data => {
                if (!Array.isArray(data)) {
                    sel.innerHTML = '<option value="">Sin almacenes</option>'; return;
                }
                sel.innerHTML = '<option value="">-- Seleccione --</option>' +
                    data.map(a => `<option value="${a.id}">${a.nombre}</option>`).join('');

                if (selectedVal) sel.value = selectedVal;
            })
            .catch(e => sel.innerHTML = '<option value="">Error carga</option>');
    }

    // --- CRUD ---

    function nuevo() {
        document.getElementById('mdlTitle').innerText = 'Nueva Clasificación';
        document.getElementById('mID').value = '';
        document.getElementById('mClave').value = '';
        document.getElementById('mGrupo').value = '';
        document.getElementById('mDesc').value = '';
        document.getElementById('mMultiplo').value = 0;
        document.getElementById('mIncluye').checked = false;

        cargarAlmacenes();
        abrirModal('mdl');
    }

    function editar(id) {
        // Buscar en cache (la API list trae todos los campos necesarios)
        const r = dataCache.find(x => x.id == id);
        if (!r) { showMsg('Error localizando registro en cache', 'warn'); return; }

        document.getElementById('mdlTitle').innerText = 'Editar Clasificación';
        document.getElementById('mID').value = r.id;
        document.getElementById('mClave').value = r.cve_sgpoart;
        document.getElementById('mGrupo').value = r.cve_gpoart || '';
        document.getElementById('mDesc').value = r.des_sgpoart;
        document.getElementById('mMultiplo').value = r.Num_Multiplo;
        document.getElementById('mIncluye').checked = (r.Ban_Incluye == 1);

        cargarAlmacenes(r.id_almacen);
        abrirModal('mdl');
    }

    function guardar() {
        const id = document.getElementById('mID').value;
        const isUpdate = !!id; // empty string means create

        const payload = {
            action: isUpdate ? 'update' : 'create',
            cve_sgpoart: document.getElementById('mClave').value.trim(),
            cve_gpoart: document.getElementById('mGrupo').value.trim(),
            des_sgpoart: document.getElementById('mDesc').value.trim(),
            id_almacen: document.getElementById('mAlmacen').value,
            Num_Multiplo: document.getElementById('mMultiplo').value,
            Ban_Incluye: document.getElementById('mIncluye').checked ? 1 : 0
        };

        if (!payload.cve_sgpoart || !payload.des_sgpoart) {
            alert('Clave y Descripción son obligatorios'); return;
        }

        if (isUpdate) payload.id = id;

        fetch(API, {
            method: 'POST',
            body: JSON.stringify(payload),
            headers: { 'Content-Type': 'application/json' }
        })
            .then(r => r.json())
            .then(j => {
                if (j.success) {
                    showMsg('Guardado correctamente', 'ok');
                    cerrarModal('mdl');
                    refrescar(curPage); // reload current page
                } else {
                    showMsg(j.error || 'Error al guardar', 'warn');
                }
            });
    }

    function toggle(id, action) {
        // action: delete / restore
        if (!confirm('¿Estás seguro?')) return;

        const payload = { action: action, id: id };

        fetch(API, {
            method: 'POST',
            body: JSON.stringify(payload),
            headers: { 'Content-Type': 'application/json' }
        })
            .then(r => r.json())
            .then(j => {
                if (j.success) { showMsg('Acción correcta', 'ok'); refrescar(curPage); }
                else showMsg(j.error || 'Error', 'warn');
            });
    }

    function exportar() {
        window.open(API + '?action=export', '_blank');
    }

    // --- IMPORT ---
    function abrirImport() {
        document.getElementById('fileCsv').value = '';
        document.getElementById('importResult').innerHTML = '';
        document.getElementById('importPreview').style.display = 'none';
        importData = [];
        abrirModal('mdlImport');
    }

    function bajarLayout() {
        const csv = "sep=;\r\nClave Clasificación;Grupo de Artículo;Descripción;Almacén;Múltiplo;Incluye\r\n";
        const blob = new Blob([new Uint16Array([0xFEFF]), csv], { type: 'text/csv;charset=utf-16le;' }); // UTF-16LE BOM attempt or generic
        // Simpler: UTF-8 BOM
        const uniCsv = "\uFEFFClave;Grupo;Descripcion;AlmacenID;Multiplo;Incluye_1_0\r\n";
        const blob2 = new Blob([uniCsv], { type: 'text/csv;charset=utf-8' });
        const url = URL.createObjectURL(blob2);
        const a = document.createElement('a'); a.href = url; a.download = 'layout_clasificacion.csv';
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
    }

    function previsualizar() {
        const f = document.getElementById('fileCsv').files[0];
        if (!f) return;

        const r = new FileReader();
        r.onload = e => {
            const text = e.target.result;
            const rows = text.split(/\r?\n/).filter(x => x.trim() !== '');
            if (rows.length < 2) { alert('CSV vacío o sin datos'); return; }

            const delim = rows[0].includes(';') ? ';' : ',';

            // Render Header
            const heads = rows[0].split(delim);
            document.getElementById('impHead').innerHTML = `<tr>${heads.map(h => `<th>${esc(h)}</th>`).join('')}</tr>`;

            // Render Body & Store Data
            importData = []; // clear previous

            // Map rows to object structure expected by API create
            // Expected Order in CSV according to old code: Clave(0), Group(1), Dest(2), Alm(3), Multi(4), Incl(5)
            const bodyHtml = rows.slice(1, 11).map(line => { // Show max 10 preview
                const cols = line.split(delim);
                return `<tr>${cols.map(c => `<td>${esc(c)}</td>`).join('')}</tr>`;
            }).join('');
            document.getElementById('impBody').innerHTML = bodyHtml;

            // Parse ALL for import
            rows.slice(1).forEach(line => {
                const c = line.split(delim);
                if (c.length >= 3) { // Min required fields
                    importData.push({
                        action: 'create',
                        cve_sgpoart: c[0],
                        cve_gpoart: c[1] || null,
                        des_sgpoart: c[2],
                        id_almacen: c[3] || null,
                        Num_Multiplo: c[4] || 0,
                        Ban_Incluye: c[5] || 0
                    });
                }
            });

            document.getElementById('importPreview').style.display = 'block';
            document.getElementById('importResult').innerHTML = `<div class="ap-chip">Listo para importar ${importData.length} registros.</div>`;
        };
        r.readAsText(f);
    }

    function procesarImport() {
        if (!importData.length) { alert('No hay datos para importar. Previsualiza primero.'); return; }

        const div = document.getElementById('importResult');
        div.innerHTML = '<div class="ap-chip">Importando... esto puede tardar.</div>';

        // Execute distinct fetch requests in parallel (or sequential batches if too many)
        // Since generic implementation used Promise.all for everything, we replicate that but be careful with limits.
        const promises = importData.map(d =>
            fetch(API, {
                method: 'POST',
                body: JSON.stringify(d),
                headers: { 'Content-Type': 'application/json' }
            }).then(r => r.json())
        );

        Promise.all(promises)
            .then(results => {
                const ok = results.filter(r => r.success).length;
                const err = results.length - ok;
                div.innerHTML = `<div class="ap-chip ${err === 0 ? 'ok' : 'warn'}">Importados: ${ok} | Fallidos: ${err}</div>`;

                if (ok > 0) setTimeout(() => { cerrarModal('mdlImport'); refrescar(1); }, 2500);
            })
            .catch(e => {
                div.innerHTML = '<div class="ap-chip warn">Error de red o servidor durante importación masiva.</div>';
            });
    }

    // Init
    document.addEventListener('DOMContentLoaded', () => {
        refrescar(1);
    });
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>