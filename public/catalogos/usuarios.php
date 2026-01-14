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

    .ap-grid tr.inactivo td {
        background: #f3f3f3;
        color: #999;
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
        width: 800px;
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
    .ap-input select,
    .ap-input textarea {
        border: none;
        outline: none;
        width: 100%;
        font-size: 14px;
        color: #212529;
        background: transparent;
        font-family: inherit;
    }

    .ap-input textarea {
        resize: vertical;
        min-height: 60px;
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

    button.danger {
        background: #dc3545;
        color: #fff;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
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
    <div class="ap-title"><i class="fa fa-users"></i> Catálogo de Usuarios</div>

    <!-- TOOLBAR -->
    <div class="ap-toolbar">
        <div class="ap-search">
            <i class="fa fa-search"></i>
            <input id="q" placeholder="Buscar por clave, nombre, email o perfil..."
                onkeydown="if(event.key==='Enter')buscar()">
        </div>
        <button class="ap-chip" onclick="buscar()">Buscar</button>
        <button class="ap-chip" onclick="limpiar()">Limpiar</button>

        <div style="border-left:1px solid #dee2e6; height:24px; margin:0 5px;"></div>

        <button class="ap-chip" id="btnToggleInactive" onclick="toggleInactivos()">
            <i class="fa fa-eye"></i> Ver Inactivos
        </button>

        <div style="flex:1"></div>

        <button class="ap-chip primary" onclick="nuevo()"><i class="fa fa-plus"></i> Nuevo</button>
        <button class="ap-chip" onclick="exportarCSV()"><i class="fa fa-download"></i> Exportar</button>
        <button class="ap-chip" onclick="abrirImport()"><i class="fa fa-upload"></i> Importar CSV</button>
    </div>

    <span class="ap-chip" id="msg" style="display:none; margin-bottom:10px;"></span>

    <!-- GRID -->
    <div class="ap-grid">
        <table>
            <thead>
                <tr>
                    <th>Acciones</th>
                    <th>ID</th>
                    <th>Clave</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Perfil</th>
                    <th>Status</th>
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
            <h3 style="margin:0"><i class="fa fa-user"></i> <span id="mdlTitle">Usuario</span></h3>
            <button onclick="cerrarModal('mdl')"
                style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i
                    class="fa fa-times"></i></button>
        </div>

        <input type="hidden" id="id_user" value="0">

        <div class="ap-form">
            <div class="ap-field">
                <div class="ap-label">Clave</div>
                <div class="ap-input"><i class="fa fa-key"></i><input id="cve_usuario"></div>
            </div>
            <div class="ap-field">
                <div class="ap-label">Nombre Completo</div>
                <div class="ap-input"><i class="fa fa-user"></i><input id="nombre_completo"></div>
            </div>
            <div class="ap-field">
                <div class="ap-label">Email</div>
                <div class="ap-input"><i class="fa fa-envelope"></i><input id="email" type="email"></div>
            </div>
            <div class="ap-field">
                <div class="ap-label">Perfil</div>
                <div class="ap-input"><i class="fa fa-id-badge"></i><input id="perfil"></div>
            </div>
            <div class="ap-field">
                <div class="ap-label">Status</div>
                <div class="ap-input"><i class="fa fa-toggle-on"></i>
                    <select id="status">
                        <option value="A">Activo</option>
                        <option value="I">Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="ap-field">
                <div class="ap-label">Activo</div>
                <div class="ap-input"><i class="fa fa-check-circle"></i>
                    <select id="Activo">
                        <option value="1">Sí</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="ap-form full" style="margin-top:10px;">
            <div class="ap-field">
                <div class="ap-label">Descripción</div>
                <div class="ap-input"><i class="fa fa-align-left"></i><textarea id="des_usuario"></textarea></div>
            </div>
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

        <div class="ap-chip" style="margin-bottom:15px; width:100%; justify-content:center;">
            Layout: cve_usuario,nombre_completo,email,perfil,des_usuario,status,Activo
        </div>

        <div class="ap-input">
            <i class="fa fa-file-csv"></i>
            <input type="file" id="csvFile" accept=".csv">
        </div>

        <div id="importResult" style="margin-top:15px;"></div>

        <div style="margin-top:15px; display:flex; gap:10px; justify-content:flex-end;">
            <button class="primary" onclick="importarCSV()"><i class="fa fa-cloud-arrow-up"></i> Importar</button>
        </div>
    </div>
</div>

<script>
    const API = '../api/usuarios.php';
    let curPage = 1;
    let viewInactive = false;

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

        const start = (curPage - 1) * 25;

        const params = new URLSearchParams();
        params.append('action', 'list');
        params.append('draw', 1);
        params.append('start', start);
        params.append('length', 25);
        params.append('search[value]', q);
        params.append('include_inactive', viewInactive ? 1 : 0);

        fetch(API + '?' + params.toString())
            .then(r => r.json())
            .then(d => {
                if (d.success || d.data || Array.isArray(d)) {
                    const data = d.data || d;
                    renderGrid(data || []);
                    renderPager(d.recordsFiltered || data.length || 0, d.recordsTotal || data.length || 0);
                } else {
                    showMsg(d.message || 'Error al cargar', 'warn');
                }
            })
            .catch(e => console.error(e));
    }

    function renderGrid(rows) {
        const tb = document.getElementById('tb');
        if (!rows.length) {
            tb.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:20px;color:#777">Sin resultados</td></tr>';
            return;
        }
        tb.innerHTML = rows.map(r => {
            const activo = parseInt(r.Activo ?? 1);
            const statusCls = activo === 1 ? 'ok' : 'warn';
            const statusTxt = activo === 1 ? 'Activo' : 'Inactivo';
            const rowClass = activo === 0 ? ' class="inactivo"' : '';

            return `<tr${rowClass}>
        <td class="ap-actions">
           <i class="fa fa-pen" title="Editar" onclick="editar(${r.id_user})"></i>
           ${activo === 1
                    ? `<i class="fa fa-ban" title="Inactivar" onclick="eliminar(${r.id_user})"></i>`
                    : `<i class="fa fa-rotate-left" title="Recuperar" onclick="recuperar(${r.id_user})"></i>`
                }
        </td>
        <td>${esc(r.id_user)}</td>
        <td>${esc(r.cve_usuario)}</td>
        <td>${esc(r.nombre_completo)}</td>
        <td>${esc(r.email)}</td>
        <td>${esc(r.perfil)}</td>
        <td><span class="ap-chip ${statusCls}" style="padding:2px 8px;font-size:11px;">${statusTxt}</span></td>
      </tr>`;
        }).join('');
    }

    function renderPager(filtered, total) {
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

    function nuevo() {
        document.getElementById('mdlTitle').innerText = 'Nuevo Usuario';
        document.getElementById('id_user').value = 0;

        ['cve_usuario', 'nombre_completo', 'email', 'perfil', 'des_usuario'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('status').value = 'A';
        document.getElementById('Activo').value = 1;

        abrirModal('mdl');
    }

    function editar(id) {
        fetch(API + '?action=get&id_user=' + id)
            .then(r => r.json())
            .then(j => {
                if (!j.success && !j.id_user) { showMsg(j.message || 'Error al cargar', 'warn'); return; }
                const u = j.row || j;
                document.getElementById('mdlTitle').innerText = 'Editar Usuario #' + u.id_user;
                document.getElementById('id_user').value = u.id_user;
                document.getElementById('cve_usuario').value = u.cve_usuario || '';
                document.getElementById('nombre_completo').value = u.nombre_completo || '';
                document.getElementById('email').value = u.email || '';
                document.getElementById('perfil').value = u.perfil || '';
                document.getElementById('des_usuario').value = u.des_usuario || '';
                document.getElementById('status').value = u.status || 'A';
                document.getElementById('Activo').value = u.Activo || 1;

                abrirModal('mdl');
            });
    }

    function guardar() {
        const id = document.getElementById('id_user').value;
        const isUpdate = (parseInt(id) > 0);

        const fd = new FormData();
        fd.append('action', isUpdate ? 'update' : 'create');
        if (isUpdate) fd.append('id_user', id);

        fd.append('cve_usuario', document.getElementById('cve_usuario').value);
        fd.append('nombre_completo', document.getElementById('nombre_completo').value);
        fd.append('email', document.getElementById('email').value);
        fd.append('perfil', document.getElementById('perfil').value);
        fd.append('des_usuario', document.getElementById('des_usuario').value);
        fd.append('status', document.getElementById('status').value);
        fd.append('Activo', document.getElementById('Activo').value);

        fetch(API, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(j => {
                if (j.success) {
                    showMsg(j.message || 'Guardado correctamente', 'ok');
                    cerrarModal('mdl');
                    refrescar(curPage);
                } else {
                    showMsg(j.message || 'Error al guardar', 'warn');
                }
            });
    }

    function eliminar(id) {
        if (!confirm('¿Dar de baja el usuario?')) return;
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id_user', id);
        fetch(API, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(j => {
                if (j.success) { showMsg(j.message || 'Usuario inactivado', 'ok'); refrescar(curPage); }
                else showMsg(j.message || 'Error', 'warn');
            });
    }

    function recuperar(id) {
        const fd = new FormData();
        fd.append('action', 'recover');
        fd.append('id_user', id);
        fetch(API, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(j => {
                if (j.success) { showMsg(j.message || 'Usuario recuperado', 'ok'); refrescar(curPage); }
                else showMsg(j.message || 'Error', 'warn');
            });
    }

    function exportarCSV() {
        window.open(API + '?action=export_csv', '_blank');
    }

    // Import Logic
    function abrirImport() {
        document.getElementById('csvFile').value = '';
        document.getElementById('importResult').innerHTML = '';
        abrirModal('mdlImport');
    }

    function importarCSV() {
        const f = document.getElementById('csvFile').files[0];
        if (!f) { alert('Selecciona un archivo'); return; }
        const fd = new FormData();
        fd.append('action', 'import_csv');
        fd.append('file', f);

        document.getElementById('importResult').innerHTML = '<div class="ap-chip">Importando...</div>';

        fetch(API, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(j => {
                const div = document.getElementById('importResult');
                if (j.success) {
                    div.innerHTML = `<div class="ap-chip ok">Importados: ${j.total_ok || 'OK'} <br> Errores: ${j.total_err || 0}</div>`;
                    setTimeout(() => { cerrarModal('mdlImport'); refrescar(1); }, 3000);
                } else {
                    let html = `<div style="color:red;font-size:12px;margin-bottom:5px;">${j.message}</div>`;
                    if (j.errors && j.errors.length) {
                        html += `<div style="max-height:100px;overflow:auto;font-size:11px;background:#fff3cd;padding:5px;">${j.errors.join('<br>')}</div>`;
                    }
                    div.innerHTML = html;
                }
            })
            .catch(e => document.getElementById('importResult').innerHTML = 'Error de red');
    }

    // Init
    document.addEventListener('DOMContentLoaded', () => {
        refrescar(1);
    });
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>