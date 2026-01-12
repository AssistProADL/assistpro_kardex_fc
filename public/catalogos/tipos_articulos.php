<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
    /* =========================================================
   ASSISTPRO – TIPO DE ARTÍCULOS
========================================================= */
    body {
        font-family: system-ui, -apple-system, sans-serif;
        background: #f4f6fb;
        margin: 0;
    }

    .ap-container {
        padding: 20px;
        font-size: 13px;
        max-width: 1800px;
        margin: 0 auto;
    }

    .ap-title {
        font-size: 20px;
        font-weight: 600;
        color: #0b5ed7;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* CARDS (KPIs) */
    .ap-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .ap-card {
        background: #fff;
        border: 1px solid #e0e6ed;
        border-radius: 12px;
        padding: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        transition: all 0.2s;
        cursor: pointer;
    }

    .ap-card:hover {
        border-color: #0b5ed7;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(11, 94, 215, 0.1);
    }

    .ap-card .h {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        font-weight: 600;
        color: #333;
    }

    .ap-card .k {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 8px;
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

    /* CHIPS */
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

    /* REQ INDICATOR */
    .ap-req-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #dc3545;
    }

    .ap-req-ok {
        background: #198754;
    }

    /* GRID */
    .ap-grid {
        background: #fff;
        border: 1px solid #e0e6ed;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        max-height: 600px;
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

    /* PAGER */
    .ap-pager {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 15px;
        padding: 0 5px;
    }

    .ap-pager button {
        background: #fff;
        border: 1px solid #dee2e6;
        padding: 6px 14px;
        border-radius: 6px;
        cursor: pointer;
        color: #495057;
    }

    .ap-pager button:disabled {
        opacity: 0.5;
        cursor: default;
    }

    .ap-pager button:hover:not(:disabled) {
        background: #f8f9fa;
        border-color: #ced4da;
    }

    .ap-pager select {
        padding: 6px;
        border-radius: 6px;
        border: 1px solid #dee2e6;
        color: #495057;
        margin-left: 5px;
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
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        margin-top: 15px;
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

    .ap-input i {
        color: #adb5bd;
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

    .ap-error {
        font-size: 12px;
        color: #dc3545;
        display: none;
        margin-top: 4px;
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
    <div class="ap-title"><i class="fa fa-tag"></i> Tipo de Artículos</div>

    <div class="ap-cards" id="cards"></div>

    <div class="ap-toolbar">
        <div class="ap-chip" id="inacLabel"><i class="fa fa-eye"></i> Mostrando: <b>Activos</b></div>

        <div class="ap-search">
            <i class="fa fa-search"></i>
            <input id="q" placeholder="Buscar clave, descripción…" onkeydown="if(event.key==='Enter')buscar()">
        </div>
        <button class="ap-chip" onclick="buscar()">Buscar</button>
        <button class="ap-chip" onclick="limpiar()">Limpiar</button>

        <div style="flex:1"></div>

        <button class="ap-chip" onclick="nuevo()"><i class="fa fa-plus"></i> Agregar</button>
        <button class="ap-chip" onclick="exportar()"><i class="fa fa-download"></i> Exportar</button>
        <button class="ap-chip" onclick="abrirImportar()"><i class="fa fa-upload"></i> Importar</button>
        <button class="ap-chip" onclick="toggleInactivos()"><i class="fa fa-filter"></i> Inactivos</button>
    </div>

    <div class="ap-grid">
        <table>
            <thead>
                <tr>
                    <th>Acciones</th>
                    <th>Req</th>
                    <th>Clave</th>
                    <th>Descripción</th>
                    <th>Grupo</th>
                    <th>Almacén</th>
                    <th>Estatus</th>
                </tr>
            </thead>
            <tbody id="tb"></tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div class="ap-pager">
        <div class="left">
            <button onclick="prevPage()" id="btnPrev"><i class="fa fa-chevron-left"></i> Anterior</button>
            <button onclick="nextPage()" id="btnNext">Siguiente <i class="fa fa-chevron-right"></i></button>
            <span class="ap-chip" id="lblRange" style="background:transparent; border:none; padding:0;">Mostrando
                0–0</span>
        </div>
        <div class="right" style="display:flex; align-items:center;">
            <span>Página:</span>
            <select id="selPage" onchange="goPage(this.value)"></select>

            <span style="margin-left:15px">Por página:</span>
            <select id="selPerPage" onchange="setPerPage(this.value)">
                <option value="25" selected>25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
    </div>
</div>

<!-- MODAL CRUD -->
<div class="ap-modal" id="mdl">
    <div class="ap-modal-content">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px">
            <h3 style="margin:0"><i class="fa fa-tag"></i> Tipo Artículo</h3>
            <button onclick="cerrarModal('mdl')"
                style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i
                    class="fa fa-times"></i></button>
        </div>

        <input type="hidden" id="id">

        <div class="ap-form">
            <div class="ap-field">
                <div class="ap-label">Clave *</div>
                <div class="ap-input"><i class="fa fa-hashtag"></i><input id="cve_ssgpoart" placeholder="Clave única">
                </div>
                <div class="ap-error" id="err_cve">Clave obligatoria.</div>
            </div>

            <div class="ap-field">
                <div class="ap-label">Descripción *</div>
                <div class="ap-input"><i class="fa fa-align-left"></i><input id="des_ssgpoart"
                        placeholder="Descripción..."></div>
                <div class="ap-error" id="err_des">Descripción obligatoria.</div>
            </div>

            <div class="ap-field">
                <div class="ap-label">Grupo (ID)</div>
                <div class="ap-input"><i class="fa fa-layer-group"></i><input id="cve_sgpoart"
                        placeholder="ID Grupo (opcional)"></div>
            </div>

            <div class="ap-field">
                <div class="ap-label">Almacén (ID)</div>
                <div class="ap-input"><i class="fa fa-warehouse"></i><input id="id_almacen"
                        placeholder="ID Almacén (opcional)"></div>
            </div>
        </div>

        <div style="text-align:right;margin-top:15px;display:flex;justify-content:flex-end;gap:10px">
            <button class="ghost" onclick="cerrarModal('mdl')">Cancelar</button>
            <button class="primary" onclick="guardar()">Guardar</button>
        </div>
    </div>
</div>

<!-- MODAL IMPORT -->
<div class="ap-modal" id="mdlImport">
    <div class="ap-modal-content" style="width:700px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px">
            <h3 style="margin:0"><i class="fa fa-upload"></i> Importar Tipos de Artículos</h3>
            <button onclick="cerrarModal('mdlImport')"
                style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i
                    class="fa fa-times"></i></button>
        </div>

        <div class="ap-chip" style="margin-bottom:15px">
            UPSERT por <b>Clave</b>.<br>
            Columnas: <b>Clave, Descripción, Grupo, Almacén</b>.
        </div>

        <div class="ap-input">
            <i class="fa fa-file-csv"></i>
            <input type="file" id="fileCsv" accept=".csv">
        </div>

        <div style="margin-top:15px; display:flex; gap:10px;">
            <button class="ghost" onclick="descargarLayout()"><i class="fa fa-download"></i> Descargar layout</button>
            <button class="primary" onclick="previsualizarCsv()"><i class="fa fa-eye"></i> Previsualizar</button>
        </div>

        <div id="csvPreviewWrap" style="display:none;margin-top:15px">
            <h4 style="margin:0 0 10px; font-size:14px; color:#555;">Previsualización</h4>
            <div class="ap-grid" style="height:200px">
                <table style="font-size:12px;">
                    <thead id="csvHead"></thead>
                    <tbody id="csvBody"></tbody>
                </table>
            </div>

            <div class="ap-chip" id="importMsg"
                style="margin-top:15px; width:100%; display:none; justify-content:center;"></div>
        </div>

        <div style="text-align:right;margin-top:15px;display:flex;justify-content:flex-end;gap:10px">
            <button class="ghost" onclick="cerrarModal('mdlImport')">Cerrar</button>
            <button class="primary" onclick="importarCsv()" id="btnImportarFinal"
                style="display:none;">Importar</button>
        </div>
    </div>
</div>

<script>
    const API = '../api/articulos.php'; // Usa API existente pero estandarizamos llamadas

    let verInactivos = false;
    let qLast = '';
    let page = 1;
    let perPage = 25;
    let total = 0;
    let lastRows = [];

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function reqDot(r) {
        const ok = !!(String(r.cve_ssgpoart || '').trim() !== '' && String(r.des_ssgpoart || '').trim() !== '');
        return '<span class="ap-req-dot ' + (ok ? 'ap-req-ok' : '') + '"></span>';
    }

    function loadCards() {
        // Implement or leave empty if no KPI API is ready yet.
        // Articulos API currently doesn't provide KPI endpoint.
        cards.innerHTML = '';
        // Example placeholder
        // cards.innerHTML = '<div class="ap-card"><div class="h"><b>Total</b></div><div class="k">'+total+'</div></div>';
    }

    /* ===== Paginación ===== */
    function setPager() {
        const start = total > 0 ? ((page - 1) * perPage + (lastRows.length ? 1 : 0)) : 0;
        let end = total > 0 ? Math.min(page * perPage, total) : 0;
        if (total === 0) end = 0;

        lblRange.innerText = 'Mostrando ' + start + '–' + end + (total > 0 ? ' de ' + total : '');

        const maxPages = total > 0 ? Math.max(1, Math.ceil(total / perPage)) : 1;
        selPage.innerHTML = '';
        for (let i = 1; i <= maxPages; i++) {
            const o = document.createElement('option');
            o.value = i; o.textContent = i;
            if (i === page) o.selected = true;
            selPage.appendChild(o);
        }
        btnPrev.disabled = (page <= 1);
        btnNext.disabled = total > 0 ? (page >= maxPages) : (lastRows.length < perPage);
    }
    function prevPage() { if (page > 1) { page--; cargar(); } }
    function nextPage() {
        const maxPages = total > 0 ? Math.ceil(total / perPage) : 1;
        if (page < maxPages) { page++; cargar(); }
        else if (total === 0 && lastRows.length === perPage) { page++; cargar(); }
    }
    function goPage(p) { page = Math.max(1, parseInt(p, 10) || 1); cargar(); }
    function setPerPage(v) { perPage = parseInt(v, 10) || 25; page = 1; cargar(); }

    /* ===== Listado ===== */
    function cargar() {
        const offset = (page - 1) * perPage;
        const url = API + '?action=list'
            + '&inactivos=' + (verInactivos ? 1 : 0)
            + '&q=' + encodeURIComponent(qLast || '')
            + '&limit=' + encodeURIComponent(perPage)
            + '&offset=' + encodeURIComponent(offset)
            + '&page=' + encodeURIComponent(page); // Some APIs use page, some offset

        fetch(url).then(r => r.json()).then(resp => {
            if (resp.error) { alert(resp.error); return; }

            const rows = resp.rows || [];
            total = Number(resp.total || 0) || 0;
            lastRows = rows;

            let h = '';
            rows.forEach(r => {
                const vid = r.id;
                const st = Number(r.Activo || 0) === 1;

                let btns = '';
                if (verInactivos && !st) {
                    btns = '<i class="fa fa-undo" title="Restaurar" onclick="restaurar(' + vid + ')"></i>';
                } else {
                    btns = '<i class="fa fa-edit" title="Editar" onclick="editar(' + vid + ')"></i>'
                        + '<i class="fa fa-trash" title="Desactivar" onclick="eliminar(' + vid + ')"></i>';
                }

                h += '<tr>'
                    + '<td class="ap-actions">' + btns + '</td>'
                    + '<td>' + reqDot(r) + '</td>'
                    + '<td>' + escapeHtml(String(r.cve_ssgpoart || '')) + '</td>'
                    + '<td>' + escapeHtml(String(r.des_ssgpoart || '')) + '</td>'
                    + '<td>' + escapeHtml(String(r.cve_sgpoart || '')) + '</td>'
                    + '<td>' + escapeHtml(String(r.id_almacen || '')) + '</td>'
                    + '<td>' + (st ? '<span class="ap-chip ok">Activo</span>' : '<span class="ap-chip warn">Inactivo</span>') + '</td>'
                    + '</tr>';
            });

            tb.innerHTML = h || '<tr><td colspan="7" style="text-align:center;padding:20px;color:#777">Sin datos</td></tr>';
            inacLabel.innerHTML = '<i class="fa fa-eye"></i> Mostrando: <b>' + (verInactivos ? 'Inactivos' : 'Activos') + '</b>';
            setPager();
        });
    }

    function buscar() { qLast = document.getElementById('q').value.trim(); page = 1; cargar(); }
    function limpiar() { document.getElementById('q').value = ''; qLast = ''; page = 1; cargar(); }
    function toggleInactivos() { verInactivos = !verInactivos; page = 1; cargar(); }

    /* ===== CRUD ===== */
    function hideErrors() {
        err_cve.style.display = 'none';
        err_des.style.display = 'none';
    }
    function validar() {
        hideErrors();
        let ok = true;
        if (!cve_ssgpoart.value.trim()) { err_cve.style.display = 'block'; ok = false; }
        if (!des_ssgpoart.value.trim()) { err_des.style.display = 'block'; ok = false; }
        return ok;
    }
    function nuevo() {
        id.value = '';
        cve_ssgpoart.value = '';
        des_ssgpoart.value = '';
        cve_sgpoart.value = '';
        id_almacen.value = '';
        hideErrors();
        mdl.style.display = 'block';
    }
    function editar(rid) {
        // Use local cache if possible or fetch
        const row = lastRows.find(r => r.id == rid);
        if (row) {
            id.value = row.id;
            cve_ssgpoart.value = row.cve_ssgpoart || '';
            des_ssgpoart.value = row.des_ssgpoart || '';
            cve_sgpoart.value = row.cve_sgpoart || '';
            id_almacen.value = row.id_almacen || '';
            hideErrors();
            mdl.style.display = 'block';
        }
    }
    function guardar() {
        if (!validar()) return;

        // Payload JSON to match backend expectation
        const payload = {
            action: id.value ? 'update' : 'create',
            cve_ssgpoart: cve_ssgpoart.value,
            des_ssgpoart: des_ssgpoart.value,
            cve_sgpoart: cve_sgpoart.value,
            id_almacen: id_almacen.value
        };
        if (id.value) payload.id = id.value;

        fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
            .then(r => r.json())
            .then(resp => {
                if (resp.error || resp.success === false) {
                    alert('Error: ' + (resp.error || 'Desconocido'));
                    return;
                }
                alert('Guardado exitosamente');
                cerrarModal('mdl');
                cargar();
            })
            .catch(err => alert('Error de red: ' + err.message));
    }
    function eliminar(rid) {
        if (!confirm('¿Desactivar?')) return;
        fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: rid })
        }).then(() => cargar());
    }
    function restaurar(rid) {
        fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'restore', id: rid })
        }).then(() => cargar());
    }

    /* ===== CSV ===== */
    function exportar() { window.open(API + '?action=export', '_blank'); } // Uses API's export
    function descargarLayout() {
        // Create a client-side CSV or verify if API has a layout action.
        // The previous implementation used client-side. Let's stick to client-side strictly conforming to human headers.
        const csvContent = "\uFEFFClave,Descripción,Grupo,Almacén\n";
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute("download", "layout_tipos_articulos.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function abrirImportar() {
        fileCsv.value = '';
        csvPreviewWrap.style.display = 'none';
        importMsg.style.display = 'none';
        document.getElementById('btnImportarFinal').style.display = 'none';
        mdlImport.style.display = 'block';
    }

    function previsualizarCsv() {
        const f = fileCsv.files[0];
        if (!f) { alert('Selecciona un CSV'); return; }
        const r = new FileReader();
        r.onload = e => {
            const rows = e.target.result.split('\n').filter(x => x.trim() !== '');
            const headers = rows[0].split(',').map(h => '<th>' + h + '</th>').join('');
            csvHead.innerHTML = '<tr>' + headers + '</tr>';

            const bodyRows = rows.slice(1, 6).map(r => {
                const cells = r.split(',').map(c => '<td>' + c + '</td>').join('');
                return '<tr>' + cells + '</tr>';
            }).join('');
            csvBody.innerHTML = bodyRows;

            csvPreviewWrap.style.display = 'block';
            importMsg.style.display = 'none';
            document.getElementById('btnImportarFinal').style.display = 'block';
        };
        r.readAsText(f);
    }

    function importarCsv() {
        // Use API import_csv if available, or build specific logic.
        // The current API `articulos.php` does NOT have `import_csv`.
        // The previous file had client-side generic import logic sending `create` one by one.
        // We will preserve that logic for now as it is safest without modifying API drastically yet.

        const f = fileCsv.files[0];
        if (!f) return;
        const r = new FileReader();
        r.onload = async e => {
            const lines = e.target.result.split('\n').filter(l => l.trim());
            if (lines.length < 2) return;
            const rows = lines.slice(1);

            let ok = 0, err = 0;

            for (const row of rows) {
                const c = row.split(',');
                // Map Client-Side by position (Clave, Desc, Grupo, Almacen)
                if (c.length < 2) continue;

                const payload = {
                    action: 'create',
                    cve_ssgpoart: c[0].trim(), // Clave
                    des_ssgpoart: c[1] ? c[1].trim() : '', // Descripcion
                    cve_sgpoart: c[2] ? c[2].trim() : '', // Grupo
                    id_almacen: c[3] ? c[3].trim() : '' // Almacen
                };

                try {
                    const res = await fetch(API, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const j = await res.json();
                    if (j.success) ok++; else err++;
                } catch (e) { err++; }
            }

            importMsg.style.display = 'flex';
            importMsg.className = 'ap-chip ok';
            importMsg.innerText = 'Importado: ' + ok + ' OK, ' + err + ' Errores';
            setTimeout(() => { cerrarModal('mdlImport'); cargar(); }, 2000);
        };
        r.readAsText(f);
    }

    function cerrarModal(mid) { document.getElementById(mid).style.display = 'none'; }

    document.addEventListener('DOMContentLoaded', () => {
        console.log('Tipos Articulos v2 loaded');
        cargar();
    });
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>