<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>
<style>
  .ap-container {
    padding: 12px;
    font-size: 12px
  }

  .ap-title {
    font-size: 18px;
    font-weight: 700;
    color: #0b5ed7;
    margin-bottom: 8px
  }

  .ap-cards {
    display: grid;
    grid-template-columns: repeat(4, minmax(180px, 1fr));
    gap: 10px;
    margin-bottom: 10px
  }

  .ap-card {
    border: 1px solid #dcdcdc;
    border-radius: 10px;
    background: #fff;
    padding: 10px 12px;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(0, 0, 0, .04)
  }

  .ap-card .h {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap
  }

  .ap-card .k {
    margin-top: 6px;
    display: flex;
    gap: 6px;
    flex-wrap: wrap
  }

  .ap-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 10px
  }

  .ap-search {
    display: flex;
    align-items: center;
    gap: 6px;
    border: 1px solid #d0d7e2;
    border-radius: 8px;
    padding: 6px 8px;
    background: #fff
  }

  .ap-search input {
    border: 0;
    outline: 0;
    font-size: 12px;
    width: 320px
  }

  .ap-chip {
    border: 1px solid #d0d7e2;
    border-radius: 18px;
    padding: 6px 10px;
    background: #fff;
    font-size: 12px;
    cursor: pointer
  }

  .ap-chip.ok {
    background: #d1e7dd;
    border-color: #badbcc;
    color: #0f5132
  }

  .ap-chip.warn {
    background: #fff3cd;
    border-color: #ffecb5;
    color: #7a5d00
  }

  .ap-grid {
    border: 1px solid #dcdcdc;
    height: 500px;
    overflow: auto
  }

  .ap-grid table {
    width: 100%;
    border-collapse: collapse
  }

  .ap-grid th {
    position: sticky;
    top: 0;
    background: #f4f6fb;
    padding: 6px;
    border-bottom: 1px solid #ccc;
    white-space: nowrap
  }

  .ap-grid td {
    padding: 5px;
    border-bottom: 1px solid #eee;
    white-space: nowrap;
    vertical-align: middle
  }

  .ap-actions i {
    cursor: pointer;
    margin-right: 8px;
    color: #0b5ed7
  }

  .ap-req-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #dc3545
  }

  .ap-req-ok {
    background: #198754
  }

  .ap-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 45);
    z-index: 9999
  }

  .ap-modal-content {
    background: #fff;
    width: 920px;
    margin: 2.5% auto;
    padding: 15px;
    border-radius: 10px
  }

  .ap-form {
    display: grid;
    grid-template-columns: 1fr 2fr 1fr;
    gap: 10px
  }

  .ap-field {
    display: flex;
    flex-direction: column;
    gap: 4px
  }

  .ap-label {
    font-weight: 600
  }

  .ap-input {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid #d0d7e2;
    border-radius: 6px;
    padding: 6px 8px;
    background: #fff
  }

  .ap-input i {
    color: #0b5ed7;
    min-width: 14px
  }

  .ap-input input,
  .ap-input select {
    border: 0;
    outline: 0;
    font-size: 12px;
    width: 100%;
    background: transparent
  }

  .ap-error {
    display: none;
    color: #dc3545;
    font-size: 11px
  }

  button.primary {
    background: #0b5ed7;
    color: #fff;
    border: none;
    padding: 6px 12px;
    border-radius: 6px
  }

  button.ghost {
    background: #fff;
    border: 1px solid #d0d7e2;
    padding: 6px 12px;
    border-radius: 6px
  }

  .ap-pager {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 10px
  }

  .ap-pager .left,
  .ap-pager .right {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap
  }

  .ap-pager button {
    padding: 6px 10px;
    border-radius: 8px;
    border: 1px solid #d0d7e2;
    background: #fff
  }

  .ap-pager button:disabled {
    opacity: .5;
    cursor: not-allowed
  }

  .ap-pager select {
    border: 1px solid #d0d7e2;
    border-radius: 8px;
    padding: 6px 8px;
    font-size: 12px
  }
</style>

<div class="ap-container">
  <div class="ap-title"><i class="fa fa-shield-alt"></i> Catálogo · QA | Cuarentena</div>

  <div class="ap-cards" id="cards"></div>

  <div class="ap-toolbar">
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <div class="ap-chip" id="tipoLabel"><i class="fa fa-filter"></i> Tipo: <b>Todos</b></div>
      <div class="ap-chip" id="inacLabel"><i class="fa fa-eye"></i> Mostrando: <b>Activos</b></div>
    </div>

    <div class="ap-search">
      <i class="fa fa-search"></i>
      <input id="q" placeholder="Buscar motivo o flags…" onkeydown="if(event.key==='Enter')buscar()">
      <button class="ap-chip" onclick="limpiar()">Limpiar</button>
    </div>

    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <button class="ap-chip" onclick="nuevo()"><i class="fa fa-plus"></i> Agregar</button>
      <button class="ap-chip" onclick="exportarDatos()"><i class="fa fa-download"></i> Exportar</button>
      <button class="ap-chip" onclick="abrirImport()"><i class="fa fa-upload"></i> Importar</button>
      <button class="ap-chip" onclick="toggleInactivos()"><i class="fa fa-eye"></i> Toggle</button>
    </div>
  </div>

  <div class="ap-grid">
    <table>
      <thead>
        <tr>
          <th>Acciones</th>
          <th>Req</th>
          <th>ID</th>
          <th>Tipo</th>
          <th>Motivo</th>
          <th>Dev. Proveedor</th>
          <th>Activo</th>
        </tr>
      </thead>
      <tbody id="tb"></tbody>
    </table>
  </div>

  <div class="ap-pager">
    <div class="left">
      <button onclick="prevPage()" id="btnPrev">◀ Prev</button>
      <button onclick="nextPage()" id="btnNext">Next ▶</button>
      <span class="ap-chip" id="lblRange">Mostrando 0–0</span>
      <span class="ap-chip">Página</span>
      <select id="selPage" onchange="goPage(this.value)"></select>
    </div>
    <div class="right">
      <span class="ap-chip">Por página</span>
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
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
      <h3 style="margin:0"><i class="fa fa-shield-alt"></i> Motivo QA/Cuarentena</h3>
      <div class="ap-chip"><span style="color:#dc3545;font-weight:700">*</span> Obligatorios: <b>Tipo</b> y
        <b>Motivo</b></div>
    </div>

    <input type="hidden" id="id">

    <div class="ap-form" style="margin-top:10px">
      <div class="ap-field">
        <div class="ap-label">Tipo Catálogo *</div>
        <div class="ap-input"><i class="fa fa-exchange-alt"></i>
          <!-- UI: Entrada/Salida; API guardará Q/S -->
          <select id="Tipo_Cat">
            <option value="E">Entrada</option>
            <option value="S">Salida</option>
          </select>
        </div>
        <div class="ap-error" id="err_tipo">Tipo obligatorio.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Motivo *</div>
        <div class="ap-input"><i class="fa fa-align-left"></i><input id="Des_Motivo"
            placeholder="Ej. Producto dañado / Falla de calidad / Contaminación..."></div>
        <div class="ap-error" id="err_motivo">Motivo obligatorio.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Activo</div>
        <div class="ap-input"><i class="fa fa-toggle-on"></i>
          <select id="Activo">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Dev. Proveedor</div>
        <div class="ap-input"><i class="fa fa-truck-loading"></i>
          <select id="dev_proveedor">
            <option value="0">No</option>
            <option value="1">Sí</option>
          </select>
        </div>
      </div>
    </div>

    <div style="text-align:right;margin-top:10px">
      <button class="primary" onclick="guardar()">Guardar</button>
      <button class="ghost" onclick="cerrarModal('mdl')">Cancelar</button>
    </div>
  </div>
</div>

<!-- MODAL IMPORT -->
<div class="ap-modal" id="mdlImport">
  <div class="ap-modal-content">
    <h3><i class="fa fa-upload"></i> Importar QA | Cuarentena</h3>
    <div class="ap-chip" style="margin-bottom:15px">UPSERT por <b>(Tipo + Motivo)</b>. Columnas: <b>ID, Tipo, Motivo,
        Dev. Proveedor, Activo</b>.</div>

    <input type="file" id="fileCsv" accept=".csv" style="margin-top:10px">

    <div style="margin:10px 0">
      <button class="ap-chip" onclick="descargarLayout()"><i class="fa fa-download"></i> Descargar layout</button>
      <button class="ap-chip ok" onclick="previsualizarCsv()"><i class="fa fa-eye"></i> Previsualizar</button>
    </div>

    <div id="csvPreviewWrap" style="display:none;margin-top:10px">
      <div class="ap-grid" style="height:260px">
        <table>
          <thead id="csvHead"></thead>
          <tbody id="csvBody"></tbody>
        </table>
      </div>
      <div style="text-align:right;margin-top:8px">
        <button class="primary" onclick="importarCsv()"><i class="fa fa-upload"></i> Importar</button>
        <button class="ghost" onclick="cerrarModal('mdlImport')">Cancelar</button>
      </div>
      <div class="ap-chip" id="importMsg" style="margin-top:10px;display:none"></div>
    </div>
  </div>
</div>

<script>
  const API = '../api/qa_cuarentena.php';
  const KPI = '../api/qa_cuarentena_kpi.php';

  let filtroTipo = '';
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

  function tipoTxt(t) {
    if (t === 'Q') return 'Entrada';
    if (t === 'S') return 'Salida';
    return (t || '');
  }

  function reqDot(r) {
    const ok = !!(String(r.Tipo_Cat || '').trim() !== '' && String(r.Des_Motivo || '').trim() !== '');
    return `<span class="ap-req-dot ${ok ? 'ap-req-ok' : ''}" title="${ok ? 'OK' : 'Faltan obligatorios'}"></span>`;
  }

  /* Cards KPI */
  function loadCards() {
    // Standardize metric card loading
    // Note: Assuming KPI API returns standard structure. If not, this keeps existing logic but cleaned.
    fetch(KPI + '?action=kpi').then(r => r.json()).then(rows => {
      let totalAll = 0, actAll = 0, inaAll = 0, badAll = 0;
      rows.forEach(x => {
        totalAll += Number(x.total || 0) || 0;
        actAll += Number(x.activos || 0) || 0;
        inaAll += Number(x.inactivos || 0) || 0;
        badAll += Number(x.inconsistentes || 0) || 0;
      });

      let h = '';
      // Card general
      h += `
    <div class="ap-card" onclick="setTipo('')">
      <div class="h">
        <b><i class="fa fa-layer-group"></i> Todos</b>
        <span class="ap-chip ok">${actAll} Act</span>
      </div>
      <div class="k">
        <span class="ap-chip">Total: ${totalAll}</span>
        <span class="ap-chip warn">Inac: ${inaAll}</span>
        <span class="ap-chip">Bad: ${badAll}</span>
      </div>
    </div>`;

      rows.forEach(x => {
        const t = (x.Tipo_Cat || '');
        const label = tipoTxt(t) || t || 'SIN TIPO';
        h += `
      <div class="ap-card" onclick="setTipo('${String(t).replace(/'/g, "\\'")}')">
        <div class="h">
          <b><i class="fa fa-shield-alt"></i> ${escapeHtml(label)}</b>
          <span class="ap-chip ok">${x.activos} Act</span>
        </div>
        <div class="k">
          <span class="ap-chip">Total: ${x.total}</span>
          <span class="ap-chip warn">Inac: ${x.inactivos}</span>
          <span class="ap-chip">Bad: ${x.inconsistentes}</span>
        </div>
      </div>`;
      });

      cards.innerHTML = h || `<div class="ap-chip warn">Sin datos</div>`;
    });
  }

  function setTipo(t) {
    filtroTipo = t || '';
    tipoLabel.innerHTML = `<i class="fa fa-filter"></i> Tipo: <b>${filtroTipo ? tipoTxt(filtroTipo) : 'Todos'}</b> ${filtroTipo ? '<span class="ap-chip" style="cursor:pointer" onclick="setTipo(\'\')">Quitar</span>' : ''
      }`;
    page = 1;
    cargar();
  }

  function setPager() {
    const start = total > 0 ? ((page - 1) * perPage + (lastRows.length ? 1 : 0)) : 0;
    const end = total > 0 ? Math.min(page * perPage, total) : 0;
    lblRange.innerText = `Mostrando ${start}–${end}` + (total > 0 ? ` de ${total}` : '');

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
    else if (total === 0 && lastRows.length === perPage) {
      page++; cargar(); // corner case
    }
  }
  function goPage(p) { page = Math.max(1, parseInt(p, 10) || 1); cargar(); }
  function setPerPage(v) { perPage = parseInt(v, 10) || 25; page = 1; cargar(); }

  function cargar() {
    const offset = (page - 1) * perPage;
    const url = API + '?action=list'
      + '&tipo=' + encodeURIComponent(filtroTipo || '')
      + '&inactivos=' + (verInactivos ? 1 : 0)
      + '&q=' + encodeURIComponent(qLast || '')
      + '&limit=' + encodeURIComponent(perPage)
      + '&offset=' + encodeURIComponent(offset);

    fetch(url).then(r => r.json()).then(resp => {
      if (resp.error) { alert(resp.error); return; }

      const rows = resp.rows || [];
      total = Number(resp.total || 0) || 0;
      lastRows = rows;

      let h = '';
      rows.forEach(r => {
        h += `
      <tr>
        <td class="ap-actions">
          ${verInactivos
            ? `<i class="fa fa-undo" title="Recuperar" onclick="recuperar(${r.id})"></i>`
            : `<i class="fa fa-edit" title="Editar" onclick="editar(${r.id})"></i>
               <i class="fa fa-trash" title="Inactivar" onclick="eliminar(${r.id})"></i>`}
        </td>
        <td>${reqDot(r)}</td>
        <td>${r.id || ''}</td>
        <td>${escapeHtml(tipoTxt(r.Tipo_Cat))}</td>
        <td>${escapeHtml(r.Des_Motivo || '')}</td>
        <td>${Number(r.dev_proveedor || 0) === 1 ? '<span class="ap-chip ok">Sí</span>' : '<span class="ap-chip">No</span>'}</td>
        <td>${Number(r.Activo || 0) === 1 ? '<span class="ap-chip ok">Sí</span>' : '<span class="ap-chip warn">No</span>'}</td>
      </tr>`;
      });

      tb.innerHTML = h || `<tr><td colspan="7" style="text-align:center;color:#6c757d;padding:20px">Sin resultados</td></tr>`;
      inacLabel.innerHTML = `<i class="fa fa-eye"></i> Mostrando: <b>${verInactivos ? 'Inactivos' : 'Activos'}</b>`;
      setPager();
    });
  }

  function buscar() { qLast = document.getElementById('q').value.trim(); page = 1; cargar(); }
  function limpiar() { document.getElementById('q').value = ''; qLast = ''; page = 1; cargar(); }
  function toggleInactivos() { verInactivos = !verInactivos; page = 1; cargar(); }

  function hideErrors() { err_tipo.style.display = 'none'; err_motivo.style.display = 'none'; }
  function validar() {
    hideErrors();
    let ok = true;
    if (!Tipo_Cat.value.trim()) { err_tipo.style.display = 'block'; ok = false; }
    if (!Des_Motivo.value.trim()) { err_motivo.style.display = 'block'; ok = false; }
    return ok;
  }

  function nuevo() {
    Tipo_Cat.value = 'E';
    Des_Motivo.value = '';
    dev_proveedor.value = '0';
    Activo.value = '1';
    id.value = '';
    hideErrors();
    mdl.style.display = 'block';
  }
  function editar(rid) {
    fetch(API + '?action=get&id=' + rid).then(r => r.json()).then(x => {
      id.value = x.id || '';
      // DB trae Q/S; UI usa E/S
      Tipo_Cat.value = (x.Tipo_Cat === 'Q') ? 'E' : 'S';
      Des_Motivo.value = x.Des_Motivo || '';
      dev_proveedor.value = String(x.dev_proveedor ?? '0');
      Activo.value = String(x.Activo ?? '1');
      hideErrors();
      mdl.style.display = 'block';
    });
  }

  function guardar() {
    if (!validar()) return;

    const fd = new FormData();
    fd.append('action', id.value ? 'update' : 'create');
    fd.append('id', id.value);
    fd.append('Tipo_Cat', Tipo_Cat.value);
    fd.append('Des_Motivo', Des_Motivo.value);
    fd.append('dev_proveedor', dev_proveedor.value);
    fd.append('Activo', Activo.value);

    fetch(API, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(resp => {
        if (resp && resp.error) {
          alert(resp.error + (resp.detalles ? "\n- " + resp.detalles.join("\n- ") : ""));
          return;
        }
        cerrarModal('mdl');
        loadCards();
        cargar();
      });
  }

  function eliminar(rid) {
    if (!confirm('¿Inactivar motivo?')) return;
    const fd = new FormData(); fd.append('action', 'delete'); fd.append('id', rid);
    fetch(API, { method: 'POST', body: fd }).then(() => { loadCards(); cargar(); });
  }
  function recuperar(rid) {
    const fd = new FormData(); fd.append('action', 'restore'); fd.append('id', rid);
    fetch(API, { method: 'POST', body: fd }).then(() => { loadCards(); cargar(); });
  }

  function exportarDatos() { window.open(API + '?action=export_csv&tipo=datos', '_blank'); }
  function descargarLayout() { window.open(API + '?action=export_csv&tipo=layout', '_blank'); }

  function abrirImport() {
    fileCsv.value = '';
    csvPreviewWrap.style.display = 'none';
    importMsg.style.display = 'none';
    mdlImport.style.display = 'block';
  }
  function previsualizarCsv() {
    const f = fileCsv.files[0];
    if (!f) { alert('Selecciona un CSV'); return; }
    const r = new FileReader();
    r.onload = e => {
      const rows = e.target.result.split('\n').filter(x => x.trim() !== '');
      csvHead.innerHTML = '<tr>' + rows[0].split(',').map(h => `<th>${escapeHtml(h)}</th>`).join('') + '</tr>';
      csvBody.innerHTML = rows.slice(1, 6).map(rr => '<tr>' + rr.split(',').map(c => `<td>${escapeHtml(c)}</td>`).join('') + '</tr>').join('');
      csvPreviewWrap.style.display = 'block';
      importMsg.style.display = 'none';
    };
    r.readAsText(f);
  }
  function importarCsv() {
    const f = fileCsv.files[0];
    if (!f) { alert('Selecciona un CSV'); return; }

    const fd = new FormData();
    fd.append('action', 'import_csv');
    fd.append('file', f);

    fetch(API, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(resp => {
        importMsg.style.display = 'flex';
        if (resp.error) {
          importMsg.className = 'ap-chip warn';
          importMsg.innerHTML = `<b>Error:</b> ${escapeHtml(resp.error)}`;
          return;
        }
        importMsg.className = 'ap-chip ok';
        importMsg.innerHTML = `<b>Importación:</b> OK ${resp.rows_ok || 0} | Err ${resp.rows_err || 0}`;
        if (resp.errores && resp.errores.length > 0) {
          // Show first few errors
          importMsg.innerHTML += '<br><small>Revisar consola para detalles de errores</small>';
          console.table(resp.errores);
        }
        setTimeout(() => { cerrarModal('mdlImport'); loadCards(); cargar(); }, 2500);
      });
  }

  function cerrarModal(mid) { document.getElementById(mid).style.display = 'none'; }

  document.addEventListener('DOMContentLoaded', () => {
    console.log('QA Cuarentena v2 loaded');
    selPerPage.value = '25';
    loadCards();
    cargar();
  });
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>