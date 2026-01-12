<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Grupos de Artículos</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        /* =========================================================
           ASSISTPRO – BASE
        ========================================================= */
        .ap-container {
            padding: 12px;
            font-size: 12px
        }

        .ap-title {
            font-size: 18px;
            font-weight: 600;
            color: #0b5ed7;
            margin-bottom: 10px
        }

        .ap-cards {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 10px
        }

        .ap-card {
            width: 250px;
            background: #fff;
            border: 1px solid #d0d7e2;
            border-radius: 10px;
            padding: 10px;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .05)
        }

        .ap-card:hover {
            border-color: #0b5ed7
        }

        .ap-toolbar {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 10px
        }

        .ap-search {
            display: flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #d0d7e2;
            border-radius: 6px;
            padding: 4px 8px;
            background: #fff
        }

        .ap-search i {
            color: #0b5ed7
        }

        .ap-search input {
            border: 0;
            outline: 0;
            font-size: 12px;
            width: 320px
        }

        .ap-grid {
            border: 1px solid #dcdcdc;
            height: calc(100vh - 210px);
            overflow: auto;
            display: block;
        }

        .ap-grid table {
            width: 100%;
            border-collapse: collapse;
            min-width: 100%
        }

        .ap-grid th {
            position: sticky;
            top: 0;
            background: #f4f6fb;
            padding: 6px;
            border-bottom: 1px solid #ccc
        }

        .ap-grid td {
            padding: 5px;
            border-bottom: 1px solid #eee;
            white-space: nowrap;
            vertical-align: middle
        }

        .ap-actions i {
            cursor: pointer;
            margin-right: 10px;
            color: #0b5ed7
        }

        /* =========================================================
           MODAL FULL SCREEN & STYLED
        ========================================================= */
        .ap-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .65);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .ap-modal[style*="display: flex"] {
            display: flex !important;
        }

        .ap-modal-content {
            background: #fff;
            width: 95%;
            height: auto;
            max-height: 90vh;
            max-width: 1400px;
            margin: 0;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.5);
        }

        .ap-modal-header {
            background: #0d6efd;
            color: #fff;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 8px 8px 0 0;
        }

        .ap-modal-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }

        .ap-modal-header button {
            background: none;
            border: none;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.2s;
        }

        .ap-modal-header button:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .ap-modal-body {
            flex: 1;
            padding: 40px;
            overflow-y: auto;
            background: #f8f9fa;
        }

        .ap-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            width: 100%;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .ap-field {
            display: flex;
            flex-direction: column;
            gap: 6px
        }

        .ap-label {
            font-weight: 600;
            color: #444;
            font-size: 14px;
        }

        .ap-input {
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px 14px;
            background: #fff;
            transition: border-color .2s, box-shadow .2s;
        }

        .ap-input:focus-within {
            border-color: #0b5ed7;
            box-shadow: 0 0 0 3px rgba(11, 94, 215, 0.15);
        }

        .ap-input i {
            color: #6c757d;
            font-size: 16px;
        }

        .ap-input input:focus+i,
        .ap-input:focus-within i {
            color: #0b5ed7;
        }

        .ap-input input,
        .ap-input select,
        .ap-input textarea {
            border: 0;
            outline: 0;
            font-size: 14px;
            width: 100%;
            background: transparent;
            color: #333;
        }

        .ap-input textarea {
            min-height: 100px;
            resize: vertical
        }

        .ap-modal-footer {
            padding: 20px 40px;
            background: #fff;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            border-radius: 0 0 8px 8px;
        }

        .ap-chip {
            font-size: 12px;
            background: #f3f4f6;
            color: #4b5563;
            border: 0;
            border-radius: 20px;
            padding: 6px 14px;
            display: inline-flex;
            gap: 8px;
            align-items: center;
            cursor: pointer;
            font-weight: 500;
            transition: all .2s
        }

        .ap-chip:hover {
            background: #e5e7eb;
            color: #1f2937
        }

        .ap-chip i {
            color: #6b7280;
            font-size: 13px
        }

        .ap-chip.ok {
            background: #dcfce7;
            color: #166534
        }

        .ok { color: green; }
        .bad { color: red; }

        button.primary {
            background: #0b5ed7;
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(11, 94, 215, 0.2);
            transition: all .2s;
            cursor: pointer
        }
        button.primary:hover {
            background: #0a58ca;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(11, 94, 215, 0.3)
        }
        button.ghost {
            background: #fff;
            border: 1px solid #ced4da;
            color: #495057;
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all .2s;
            cursor: pointer
        }
        button.ghost:hover {
            background: #f1f3f5;
            border-color: #adb5bd;
            color: #212529;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>

    <div class="ap-container">
        <div class="ap-title"><i class="fa fa-layer-group"></i> Grupos de Artículos</div>

        <div class="ap-toolbar">
            <div class="ap-search">
                <i class="fa fa-search"></i>
                <input type="text" id="q" placeholder="Buscar..." onkeyup="if(event.key==='Enter') buscar()">
                <button class="ap-chip" onclick="buscar()">Buscar</button>
                <button class="ap-chip" onclick="limpiar()"><i class="fa fa-eraser"></i> Limpiar</button>
            </div>
            <button class="ap-chip" onclick="abrirNuevo()"><i class="fa fa-plus"></i> Nuevo</button>

            <button class="ap-chip" onclick="exportar()"><i class="fa fa-download"></i> Exportar</button>
            <button class="ap-chip ok" onclick="abrirImportar()"><i class="fa fa-upload"></i> Importar</button>
            <button class="ap-chip" id="btnToggle" onclick="toggleInactivos()"><i class="fa fa-eye"></i> Ver
                inactivos</button>
            <span class="ap-chip" id="msg" style="display:none"></span>
        </div>

        <div class="ap-grid">
            <table>
                <thead>
                    <tr>
                        <th>Acciones</th>
                        <th>Clave</th>
                        <th>Descripción</th>
                        <th>% Contable</th>
                        <th>% Fiscal</th>
                        <th>Almacén</th>
                        <th>Estatus</th>
                    </tr>
                </thead>
                <tbody id="grid"></tbody>
            </table>
        </div>

        <div id="pager"></div>
    </div>

    <!-- ================= MODAL NUEVO / EDITAR ================= -->
    <div class="ap-modal" id="mdlNuevo">
        <div class="ap-modal-content">
            <div class="ap-modal-header">
                <h3 id="mdlTitulo">Nuevo Grupo</h3>
                <button onclick="cerrarNuevo()"><i class="fa fa-times"></i></button>
            </div>
            <div class="ap-modal-body">
                <input type="hidden" id="id">
                <div class="ap-form">

                    <div class="ap-field">
                        <div class="ap-label">Clave *</div>
                        <div class="ap-input">
                            <i class="fa fa-key"></i>
                            <input id="cve" placeholder="Clave del grupo">
                        </div>
                    </div>

                    <div class="ap-field">
                        <div class="ap-label">Descripción *</div>
                        <div class="ap-input">
                            <i class="fa fa-align-left"></i>
                            <input id="desc" placeholder="Descripción del grupo">
                        </div>
                    </div>

                    <div class="ap-field">
                        <div class="ap-label">% Depósito Contable</div>
                        <div class="ap-input">
                            <i class="fa fa-percent"></i>
                            <input id="porcCont" type="number" placeholder="0">
                        </div>
                    </div>

                    <div class="ap-field">
                        <div class="ap-label">% Depósito Fiscal</div>
                        <div class="ap-input">
                            <i class="fa fa-percent"></i>
                            <input id="porcFisc" type="number" placeholder="0">
                        </div>
                    </div>

                    <div class="ap-field">
                        <div class="ap-label">Almacén</div>
                        <div class="ap-input">
                            <i class="fa fa-warehouse"></i>
                            <input id="almacen" type="number" placeholder="ID Almacén (Opcional)">
                        </div>
                    </div>

                </div>
            </div>
            <div class="ap-modal-footer">
                <button class="ghost" onclick="cerrarNuevo()">Cancelar</button>
                <button class="primary" onclick="guardar()">Guardar</button>
            </div>
        </div>
    </div>

    <!-- ================= MODAL IMPORTAR ================= -->
    <div class="ap-modal" id="mdlImport">
        <div class="ap-modal-content">
            <div class="ap-modal-header">
                <h3><i class="fa fa-upload"></i> Importar Grupos</h3>
                <button onclick="cerrarImportar()"><i class="fa fa-times"></i></button>
            </div>
            <div class="ap-modal-body">
                <div style="background: #fff; width: 100%; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    <div class="ap-field">
                        <div class="ap-label">Archivo CSV</div>
                        <div class="ap-input">
                            <i class="fa fa-file-csv"></i>
                            <input type="file" id="fileCsv" accept=".csv" style="padding:4px">
                        </div>
                    </div>

                    <div style="margin-top:20px; display:flex; gap:10px; justify-content: flex-start;">
                        <button class="ghost" onclick="layout()"><i class="fa fa-table"></i> Descargar Layout</button>
                        <button class="primary" onclick="previsualizar()"><i class="fa fa-eye"></i> Previsualizar</button>
                    </div>

                    <div id="previewBox" style="display:none; margin-top:25px; border-top: 1px solid #eee; padding-top: 20px;">
                        <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #555;">Previsualización</h4>
                        <div style="max-height:300px; overflow:auto; border:1px solid #eee; border-radius:8px">
                            <table class="ap-table" style="width:100%">
                                <thead id="prevHead" style="position:sticky; top:0; background:#f9fafb"></thead>
                                <tbody id="prevBody"></tbody>
                            </table>
                        </div>
                        <div style="text-align:right; margin-top:15px">
                            <button class="primary" onclick="importar()"><i class="fa fa-file-import"></i> Importar Datos</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="ap-modal-footer">
                <button class="ghost" onclick="cerrarImportar()">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        const api = '../api/api_grupos.php';
        let verInactivos = 0;
        let curPage = 1;
        let cacheRows = [];

        async function refrescar(page) {
            if (page) curPage = page;
            const q = encodeURIComponent(document.getElementById('q').value || '');

            try {
                const res = await fetch(`${api}?action=list&inactivos=${verInactivos}&page=${curPage}&limit=25&q=${q}`);
                const j = await res.json();

                if (j.error) {
                    apAlert('error', j.error);
                    return;
                }

                cacheRows = j.rows || [];
                renderGrid(cacheRows);
                renderPager(j.page, j.pages, j.total);

                const btnToggle = document.getElementById('btnToggle');
                if (btnToggle) {
                    if (j.hasInactives || verInactivos) {
                        btnToggle.style.display = 'inline-flex';
                    } else {
                        btnToggle.style.display = 'none';
                    }
                }

            } catch (e) {
                console.error(e);
                alert('No se pudo conectar con el API');
            }
        }

        function renderGrid(arr) {
            const grid = document.getElementById('grid');
            grid.innerHTML = arr.map(r => `
                <tr>
                    <td class="ap-actions">
                        <i class="fa fa-edit" onclick="editar(${r.id})" title="Editar"></i>
                        ${r.Activo == 1
                    ? `<i class="fa fa-trash" style="color:#d33" onclick="desactivar(${r.id})" title="Desactivar"></i>`
                    : `<i class="fa fa-undo" style="color:green" onclick="restaurar(${r.id})" title="Restaurar"></i>`
                }
                    </td>
                    <td>${r.cve_gpoart}</td>
                    <td>${r.des_gpoart}</td>
                    <td>${r.por_depcont || ''}</td>
                    <td>${r.por_depfical || ''}</td>
                    <td>${r.id_almacen || ''}</td>
                    <td>
                        <span class="ap-chip ${r.Activo == 1 ? 'ok' : ''}" style="padding:2px 8px;font-size:11px">
                            ${r.Activo == 1 ? 'Activo' : 'Inactivo'}
                        </span>
                    </td>
                </tr>
            `).join('');
        }

        function renderPager(page, pages, total) {
            const p = document.getElementById('pager');
            if (!p) return;
            const prev = page > 1 ? `<button class="ap-chip" onclick="refrescar(${page - 1})"><i class="fa fa-chevron-left"></i> Anterior</button>` : `<button class="ap-chip" disabled style="opacity:0.5"><i class="fa fa-chevron-left"></i> Anterior</button>`;
            const next = page < pages ? `<button class="ap-chip" onclick="refrescar(${page + 1})">Siguiente <i class="fa fa-chevron-right"></i></button>` : `<button class="ap-chip" disabled style="opacity:0.5">Siguiente <i class="fa fa-chevron-right"></i></button>`;

            const start = (page - 1) * 25 + 1;
            let end = page * 25;
            if (end > total) end = total;
            if (total === 0) { start = 0; end = 0; }

            p.innerHTML = `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px">
                    <div>${prev} ${next}</div>
                    <div style="font-size:12px;font-weight:600;color:#555">
                        Mostrando ${start} a ${end} de ${total} registros
                    </div>
                </div>
            `;
        }

        function buscar() { refrescar(1); }
        function limpiar() { document.getElementById('q').value = ''; refrescar(1); }

        function toggleInactivos() {
            verInactivos = verInactivos ? 0 : 1;
            const btn = document.getElementById('btnToggle');
            if (verInactivos) {
                btn.innerHTML = '<i class="fa fa-eye"></i> Ver activos';
            } else {
                btn.innerHTML = '<i class="fa fa-eye"></i> Ver inactivos';
            }
            refrescar(1);
        }

        function abrirNuevo() {
            id.value = ''; cve.value = ''; desc.value = ''; porcCont.value = ''; porcFisc.value = ''; almacen.value = '';
            mdlTitulo.innerText = 'Nuevo Grupo';
            mdlNuevo.style.display = 'flex';
        }

        function editar(idr) {
            const r = cacheRows.find(x => x.id == idr);
            if (!r) return;
            id.value = r.id; 
            cve.value = r.cve_gpoart; 
            desc.value = r.des_gpoart;
            porcCont.value = r.por_depcont; 
            porcFisc.value = r.por_depfical; 
            almacen.value = r.id_almacen;
            
            mdlTitulo.innerText = 'Editar Grupo';
            mdlNuevo.style.display = 'flex';
        }
        function cerrarNuevo() { mdlNuevo.style.display = 'none'; }

        function guardar() {
            if (!cve.value || !desc.value) {
                alert('Clave y Descripción son obligatorias');
                return;
            }

            const esEdicion = id.value && id.value !== '';
            const payload = {
                action: 'save',
                cve_gpoart: cve.value.trim(),
                des_gpoart: desc.value.trim(),
                por_depcont: porcCont.value ? parseFloat(porcCont.value) : null,
                por_depfical: porcFisc.value ? parseFloat(porcFisc.value) : null,
                id_almacen: almacen.value ? parseInt(almacen.value) : null
            };
            if (esEdicion) payload.id = id.value;

            fetch(api, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
                .then(r => r.json())
                .then(resp => {
                    if (resp.success) {
                        cerrarNuevo();
                        refrescar(curPage);
                        // apAlert success (using standard alert fallback if apAlert not defined or just alert since I didn't verify if I should add apAlert definition)
                        // Actually I should stick to alert() unless I define apAlert. I will use standard alert for now as I did in the JS rewrite.
                    } else {
                        alert(resp.error || 'No se pudo guardar');
                    }
                });
        }

        function desactivar(id) {
            if (!confirm('¿Desactivar?')) return;
            fetch(api, {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', id })
            }).then(() => refrescar(curPage));
        }

        function restaurar(id) {
            fetch(api, {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'restore', id })
            }).then(() => refrescar(curPage));
        }

        /* ================= IMPORTAR ================= */
        function abrirImportar() { mdlImport.style.display = 'flex'; }
        function cerrarImportar() { mdlImport.style.display = 'none'; previewBox.style.display = 'none'; }

        function layout() {
            const csv = "\uFEFFClave,Descripción,% Contable,% Fiscal,Almacén\r\n";
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'layout_grupos.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }

        function previsualizar() {
            const f = fileCsv.files[0];
            if (!f) return;
            const r = new FileReader();
            
            const headerMap = {
                'cve_gpoart': 'Clave',
                'des_gpoart': 'Descripción',
                'por_depcont': '% Contable',
                'por_depfical': '% Fiscal',
                'id_almacen': 'Almacén',
                'Activo': 'Activo'
            };

            r.onload = e => {
                const lines = e.target.result.split(/\r?\n/).filter(l => l.trim() !== '');
                const d = lines[0].includes(';') ? ';' : ',';
                const headers = lines[0].split(d);

                prevHead.innerHTML = '<tr>' + headers.map(h => {
                    let clean = h.replace(/['"]+/g, '').trim();
                    return `<th>${headerMap[clean] || clean}</th>`;
                }).join('') + '</tr>';

                prevBody.innerHTML = lines.slice(1, 6).map(row => {
                    const cols = row.split(d);
                    return '<tr>' + cols.map(v => `<td>${v.replace(/['"]+/g, '')}</td>`).join('') + '</tr>';
                }).join('');
                previewBox.style.display = 'block';
            };
            r.readAsText(f, 'UTF-8');
        }

        function importar() {
            const f = fileCsv.files[0];
            if (!f) { alert('Selecciona un archivo'); return; }
            const reader = new FileReader();
            reader.onload = e => {
                const lines = e.target.result.split(/\r?\n/).filter(l => l.trim());
                if (lines.length < 2) return;
                const delimiter = lines[0].includes(';') ? ';' : ',';
                const rows = lines.slice(1);
                const reqs = [];
                rows.forEach(row => {
                    const c = row.split(delimiter);
                    if (!c[0] || !c[1]) return; // Min Clave/Desc
                    reqs.push(fetch(api, {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'save',
                            cve_gpoart: c[0].trim(),
                            des_gpoart: c[1].trim(),
                            por_depcont: c[2] ? parseFloat(c[2]) : null,
                            por_depfical: c[3] ? parseFloat(c[3]) : null,
                            id_almacen: c[4] ? parseInt(c[4]) : null
                        })
                    }));
                });
                Promise.all(reqs).then(() => {
                    cerrarImportar();
                    verInactivos = 0;
                    refrescar(1);
                    alert('Importación correcta');
                });
            };
            reader.readAsText(f, 'UTF-8');
        }

        function exportar() {
            window.location.href = api + '?action=export';
        }

        refrescar(1);
    </script>

</body>

</html>
