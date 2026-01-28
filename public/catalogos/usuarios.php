<?php
require_once __DIR__ . '/../bi/_menu_global.php';

?>

<style>
    /* =========================================================
   ASSISTPRO STYLES - Catálogo de Usuarios
========================================================= */
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #e9ecef;
        margin: 0;
        color: #212529;
    }

    .ap-container {
        padding: 10px 15px;
        font-size: 12px;
        max-width: 100%;
        margin: 0 auto;
    }

    /* TITLE */
    .ap-title {
        font-size: 16px;
        font-weight: 600;
        color: #212529;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* CARDS (KPIs) */
    .ap-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 8px;
        margin-bottom: 10px;
    }

    .ap-card {
        background: #fff;
        border: 1px solid #ced4da;
        border-radius: 4px;
        padding: 8px 10px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        transition: all 0.2s;
        cursor: pointer;
    }

    .ap-card:hover {
        border-color: #adb5bd;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    }

    .ap-card .h {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
        font-weight: 600;
        color: #495057;
        font-size: 12px;
    }

    .ap-card .v {
        font-size: 20px;
        font-weight: 700;
        color: #212529;
    }

    /* TOOLBAR */
    .ap-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        align-items: center;
        margin-bottom: 8px;
        background: #fff;
        padding: 8px 10px;
        border-radius: 4px;
        border: 1px solid #dee2e6;
    }

    .ap-search {
        display: flex;
        align-items: center;
        gap: 6px;
        flex: 1;
        min-width: 250px;
        background: #fff;
        padding: 5px 10px;
        border-radius: 4px;
        border: 1px solid #ced4da;
    }

    .ap-search i {
        color: #6c757d;
    }

    .ap-search input {
        border: none;
        background: transparent;
        outline: none;
        width: 100%;
        font-size: 12px;
    }

    /* CHIPS / BUTTONS */
    .ap-chip {
        font-size: 11px;
        background: #f8f9fa;
        color: #495057;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 5px 10px;
        display: inline-flex;
        gap: 5px;
        align-items: center;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
        white-space: nowrap;
    }

    .ap-chip:hover {
        background: #e9ecef;
        border-color: #adb5bd;
    }

    .ap-chip.primary {
        background: #0d6efd;
        color: #fff;
        border-color: #0d6efd;
    }

    .ap-chip.primary:hover {
        background: #0b5ed7;
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
        border: 1px solid #dee2e6;
        border-radius: 4px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .ap-grid-wrapper {
        max-height: calc(100vh - 220px);
        overflow-y: auto;
    }

    .ap-grid table {
        width: 100%;
        border-collapse: collapse;
    }

    .ap-grid th {
        background: #f8f9fa;
        padding: 8px 10px;
        text-align: left;
        font-weight: 600;
        color: #495057;
        border-bottom: 2px solid #dee2e6;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 10;
        font-size: 11px;
    }

    .ap-grid td {
        padding: 6px 10px;
        border-bottom: 1px solid #f1f3f5;
        color: #212529;
        vertical-align: middle;
        white-space: nowrap;
        font-size: 11px;
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
        margin-right: 10px;
        color: #6c757d;
        transition: color 0.2s;
        font-size: 13px;
    }

    .ap-actions i:hover {
        color: #0d6efd;
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
        width: 700px;
        max-width: 95%;
        max-height: 90vh;
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        overflow-y: auto;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        padding: 15px 20px;
    }

    .ap-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e9ecef;
    }

    .ap-modal-header h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #212529;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .ap-modal-header button {
        background: transparent;
        border: none;
        font-size: 18px;
        cursor: pointer;
        color: #6c757d;
        padding: 0;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
    }

    .ap-modal-header button:hover {
        background: #f8f9fa;
        color: #212529;
    }

    .ap-form {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-top: 10px;
    }

    .ap-form.full {
        grid-template-columns: 1fr;
    }

    .ap-field {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .ap-label {
        font-weight: 500;
        font-size: 11px;
        color: #495057;
    }

    .ap-input {
        display: flex;
        align-items: center;
        gap: 8px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        padding: 6px 10px;
        background: #fff;
        transition: all 0.2s;
    }

    .ap-input:focus-within {
        border-color: #0d6efd;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
    }

    .ap-input i {
        color: #6c757d;
        font-size: 12px;
    }

    .ap-input input,
    .ap-input select,
    .ap-input textarea {
        border: none;
        outline: none;
        width: 100%;
        font-size: 12px;
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
        margin-top: 8px;
        padding: 0 5px;
        font-size: 11px;
    }

    .ap-pager-info {
        color: #6c757d;
    }

    .ap-pager-controls {
        display: flex;
        gap: 5px;
    }

    /* MODAL FOOTER */
    .ap-modal-footer {
        text-align: right;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #e9ecef;
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }

    button.primary {
        background: #0d6efd;
        color: #fff;
        border: none;
        padding: 6px 14px;
        border-radius: 4px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.2s;
        font-size: 12px;
    }

    button.primary:hover {
        background: #0b5ed7;
    }

    button.ghost {
        background: #fff;
        color: #495057;
        border: 1px solid #dee2e6;
        padding: 6px 14px;
        border-radius: 4px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 12px;
    }

    button.ghost:hover {
        background: #f8f9fa;
        border-color: #adb5bd;
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
        <div class="ap-grid-wrapper">
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
    </div>

    <!-- PAGER -->
    <div id="pager" class="ap-pager"></div>
</div>

<!-- MODAL EDIT/NEW -->
<div class="ap-modal" id="mdl">
    <div class="ap-modal-content">
        <div class="ap-modal-header">
            <h3><i class="fa fa-user"></i> <span id="mdlTitle">Usuario</span></h3>
            <button onclick="cerrarModal('mdl')"><i class="fa fa-times"></i></button>
        </div>

        <input type="hidden" id="id_user" value="0">

        <div class="ap-form">
            <div class="ap-field">
                <div class="ap-label">Clave | Nombre Usuario</div>
                <div class="ap-input"><i class="fa fa-key"></i><input id="cve_usuario"></div>
            </div>
            <div class="ap-field">
                <div class="ap-label">Nombre Completo</div>
                <div class="ap-input"><i class="fa fa-user"></i><input id="nombre_completo"></div>
            </div>
            <div class="ap-field">
                <div class="ap-label">Correo</div>
                <div class="ap-input"><i class="fa fa-envelope"></i><input id="email" type="email"></div>
            </div>
            <div class="ap-field">
                <div class="ap-label">Perfil de usuario</div>
                <div class="ap-input"><i class="fa fa-id-badge"></i>
                    <select id="perfil">
                        <option value="">Seleccione...</option>
                    </select>
                </div>
            </div>

            <div class="ap-field">
                <div class="ap-label">Contraseña</div>
                <div class="ap-input"><i class="fa fa-lock"></i><input id="pwd_usuario" type="password"
                        autocomplete="new-password"></div>
            </div>
            <div class="ap-field">
                <div class="ap-label">Confirmar Contraseña</div>
                <div class="ap-input"><i class="fa fa-lock"></i><input id="pwd_confirm" type="password"
                        autocomplete="new-password"></div>
            </div>

            <div class="ap-field" style="grid-column: span 2">
                <div class="ap-label">Empresa</div>
                <div class="ap-input"><i class="fa fa-building"></i>
                    <select id="cve_cia">
                        <option value="">Seleccione...</option>
                    </select>
                </div>
            </div>

            <div class="ap-field" style="grid-column: span 2">
                <div class="ap-label">Descripción</div>
                <div class="ap-input"><i class="fa fa-align-left"></i><input id="des_usuario"></div>
            </div>
        </div>

        <div style="margin-top:15px; border-top:1px solid #eee; padding-top:10px;">
            <div class="ap-label" style="margin-bottom:8px">Tipo de Usuario</div>
            <div id="tipos_usuario_container" style="display:flex; flex-wrap:wrap; gap:15px;">
                <!-- Radio Buttons dinámicos aquí -->
            </div>
        </div>

        <div class="ap-form" style="display:none"> <!-- Ocultos por default o reubicados si se necesita -->
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

        <div class="ap-modal-footer">
            <button class="ghost" onclick="cerrarModal('mdl')">Cancelar</button>
            <button class="primary" onclick="guardar()">Guardar</button>
        </div>
    </div>
</div>

<!-- MODAL IMPORT -->
<div class="ap-modal" id="mdlImport">
    <div class="ap-modal-content" style="width:500px">
        <div class="ap-modal-header">
            <h3><i class="fa fa-upload"></i> Importar CSV</h3>
            <button onclick="cerrarModal('mdlImport')"><i class="fa fa-times"></i></button>
        </div>

        <div class="ap-chip" style="margin-bottom:15px; width:100%; justify-content:center;">
            Layout: cve_usuario,nombre_completo,email,perfil,des_usuario,status,Activo
        </div>

        <div class="ap-input">
            <i class="fa fa-file-csv"></i>
            <input type="file" id="csvFile" accept=".csv">
        </div>

        <div id="importResult" style="margin-top:15px;"></div>

        <div class="ap-modal-footer">
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

    // Cache de perfiles cargados desde t_perfilesusuarios
    let perfilesCache = {};

    async function cargarPerfilesCache() {
        try {
            const r = await fetch(API + '?action=perfiles');
            const j = await r.json();
            if (j.success && j.data) {
                perfilesCache = {};
                j.data.forEach(p => {
                    perfilesCache[p.ID_PERFIL] = p.PER_NOMBRE;
                });
            }
        } catch (e) {
            console.error('Error cargando perfiles:', e);
        }
    }

    function getPerfilNombre(perfil) {
        return perfilesCache[perfil] || perfil || 'Sin perfil';
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
        <td>${getPerfilNombre(r.perfil)}</td>
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
      <div class="ap-pager-info">
         Mostrando ${start}-${end} de ${filtered} registros
      </div>
      <div class="ap-pager-controls">
        ${prev}
        <span class="ap-chip" style="cursor:default">Página ${curPage}</span>
        ${next}
      </div>
    `;
    }

    function esc(s) {
        if (s == null) return '';
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function postAction(act, id) {
        const fd = new FormData();
        fd.append('action', act);
        fd.append('id_user', id);
        fetch(API, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(j => {
                if (j.success) { showMsg(j.message, 'ok'); refrescar(); }
                else showMsg(j.message, 'warn');
            })
            .catch(e => console.error(e));
    }
    function cargarEmpresas() {
        fetch(API + '?action=empresas')
            .then(r => r.json())
            .then(j => {
                if (j.success && j.data) {
                    const sel = document.getElementById('cve_cia');
                    const currentVal = sel.value;
                    sel.innerHTML = '<option value="">Seleccione...</option>' +
                        j.data.map(c => `<option value="${c.cve_cia}">${esc(c.des_cia)}</option>`).join('');
                    if (currentVal) sel.value = currentVal;
                }
            })
            .catch(e => console.error(e));
    }

    function cargarTiposUsuario() {
        fetch(API + '?action=tipos_usuario')
            .then(r => r.json())
            .then(j => {
                if (j.success && j.data) {
                    const div = document.getElementById('tipos_usuario_container');
                    div.innerHTML = j.data.map(t => `
                        <label style="display:flex; align-items:center; gap:5px; font-size:12px; cursor:pointer;">
                            <input type="radio" name="id_tipo_usuario" value="${t.id_tipo}">
                            ${esc(t.des_tipo)}
                        </label>
                    `).join('');
                }
            })
            .catch(e => console.error(e));
    }

    function cargarPerfiles() {
        fetch(API + '?action=perfiles')
            .then(r => r.json())
            .then(j => {
                if (j.success && j.data) {
                    const sel = document.getElementById('perfil');
                    const currentValue = sel.value;

                    sel.innerHTML = '<option value="">Seleccione...</option>' +
                        j.data.map(p => `<option value="${p.ID_PERFIL}">${esc(p.PER_NOMBRE)}</option>`).join('');

                    if (currentValue) sel.value = currentValue;
                }
            })
            .catch(e => console.error('Error cargando perfiles:', e));
    }

    function buscar() { refrescar(1); }
    function limpiar() {
        document.getElementById('q').value = '';
        refrescar(1);
    }

    function nuevo() {
        document.getElementById('mdlTitle').innerText = 'Agregar Usuario';
        document.getElementById('id_user').value = 0;

        ['cve_usuario', 'nombre_completo', 'email', 'perfil', 'des_usuario', 'pwd_usuario', 'pwd_confirm', 'cve_cia'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('status').value = 'A';
        document.getElementById('Activo').value = 1;

        cargarPerfiles();
        cargarEmpresas();
        cargarTiposUsuario();

        // Asegurar renderizado
        setTimeout(() => document.querySelectorAll('input[name="id_tipo_usuario"]').forEach(r => r.checked = false), 100);

        abrirModal('mdl');
    }

    function editar(id) {
        fetch(API + '?action=get&id_user=' + id)
            .then(r => r.json())
            .then(j => {
                if (!j.success && !j.id_user) { showMsg(j.message || 'Error al cargar', 'warn'); return; }
                const u = j.row || j;

                // Cargar catálogos
                cargarPerfiles();
                cargarEmpresas();
                cargarTiposUsuario();

                setTimeout(() => {
                    document.getElementById('mdlTitle').innerText = 'Editar Usuario #' + u.id_user;
                    document.getElementById('id_user').value = u.id_user;
                    document.getElementById('cve_usuario').value = u.cve_usuario || '';
                    document.getElementById('nombre_completo').value = u.nombre_completo || '';
                    document.getElementById('email').value = u.email || '';
                    document.getElementById('perfil').value = u.perfil || '';
                    document.getElementById('des_usuario').value = u.des_usuario || '';
                    document.getElementById('status').value = u.status || 'A';
                    document.getElementById('Activo').value = u.Activo || 1;
                    document.getElementById('cve_cia').value = u.cve_cia || '';

                    document.getElementById('pwd_usuario').value = '';
                    document.getElementById('pwd_confirm').value = '';

                    // Set radio button con reintento seguro
                    const setRadio = () => {
                        const radios = document.querySelectorAll('input[name="id_tipo_usuario"]');
                        let marked = false;
                        radios.forEach(r => {
                            if (parseInt(r.value) === parseInt(u.id_tipo_usuario)) {
                                r.checked = true;
                                marked = true;
                            }
                        });
                        return marked;
                    };

                    if (!setRadio()) {
                        setTimeout(setRadio, 300); // Retry if rendering was slow
                    }

                    abrirModal('mdl');
                }, 200);
            });
    }

    function guardar() {
        const id = document.getElementById('id_user').value;
        const isUpdate = (parseInt(id) > 0);

        const fd = new FormData();
        fd.append('action', isUpdate ? 'update' : 'create');
        if (isUpdate) fd.append('id_user', id);

        // Campos requeridos básicos
        const cve = document.getElementById('cve_usuario').value.trim();
        const nom = document.getElementById('nombre_completo').value.trim();

        if (!cve || !nom) { showMsg('Clave y Nombre son obligatorios', 'warn'); return; }

        // Validar Tipo de Usuario
        const selectedTipo = document.querySelector('input[name="id_tipo_usuario"]:checked');
        if (!selectedTipo) { showMsg('Debe seleccionar un Tipo de Usuario', 'warn'); return; }

        fd.append('cve_usuario', cve);
        fd.append('nombre_completo', nom);
        fd.append('email', document.getElementById('email').value.trim());
        fd.append('perfil', document.getElementById('perfil').value);
        fd.append('des_usuario', document.getElementById('des_usuario').value);
        fd.append('status', document.getElementById('status').value);
        fd.append('Activo', document.getElementById('Activo').value);
        fd.append('cve_cia', document.getElementById('cve_cia').value);

        // Passwords
        fd.append('pwd_usuario', document.getElementById('pwd_usuario').value);
        fd.append('pwd_confirm', document.getElementById('pwd_confirm').value);

        fd.append('id_tipo_usuario', selectedTipo.value);

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
    document.addEventListener('DOMContentLoaded', async () => {
        cargarPerfiles();
        cargarTiposUsuario();
        // cargarEmpresas() se carga al abrir el modal para asegurar datos frescos, 
        // pero podríamos llamarlo aquí tambien. Dejémoslo en nuevo/editar.
        refrescar(1);
    });
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>