<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
  /* =========================================================
   ASSISTPRO – UBICACIONES
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

  button.ap-chip {
    font-family: inherit;
  }

  /* BADGES */
  .ap-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 11px;
    background: #e7f1ff;
    color: #004085;
    margin-right: 4px;
    font-weight: 500;
  }

  .ap-badge.ok {
    background: #d1e7dd;
    color: #0f5132;
  }

  .ap-badge.warn {
    background: #fff3cd;
    color: #664d03;
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
    width: 1100px;
    max-width: 95%;
    max-height: 90vh;
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    padding: 20px;
  }

  .ap-form-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
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

  .ap-help {
    font-size: 12px;
    color: #6c757d;
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
  <div class="ap-title"><i class="fa fa-map-marked-alt"></i> Catálogo de Ubicaciones (BL)</div>

  <div class="ap-toolbar">
    <div class="ap-search"
      title="Buscar por BL (CodigoCSD), claverp, sección, ubicación, pasillo/rack/nivel, tecnología, tipo, ABC, almacén">
      <i class="fa fa-search"></i>
      <input id="q" placeholder="Buscar ubicación…" onkeydown="if(event.key==='Enter')buscar()">
    </div>
    <button class="ap-chip" onclick="buscar()">Buscar</button>
    <button class="ap-chip" onclick="limpiarBusqueda()">Limpiar</button>

    <div style="flex:1"></div>

    <button class="ap-chip" onclick="nuevo()"><i class="fa fa-plus"></i> Agregar</button>
    <button class="ap-chip" onclick="exportarDatos()"><i class="fa fa-download"></i> Exportar</button>
    <button class="ap-chip" onclick="abrirImport()"><i class="fa fa-upload"></i> Importar</button>
    <button class="ap-chip" onclick="toggleInactivos()"><i class="fa fa-eye"></i> Inactivos</button>
  </div>

  <div class="ap-grid">
    <table>
      <thead>
        <tr>
          <th>Acciones</th>
          <th>Req</th>
          <th>Almacén</th>
          <th>BL</th>
          <th>Sección</th>
          <th>Ubicación</th>
          <th>Pasillo</th>
          <th>Rack</th>
          <th>Nivel</th>
          <th>Status</th>
          <th>Picking</th>
          <th>Tipo</th>
          <th>Flags</th>
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
      <span class="ap-chip" id="lblRange" style="background:transparent; border:none; padding:0;">Mostrando 0–0</span>
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

<!-- MODAL -->
<div class="ap-modal" id="mdl">
  <div class="ap-modal-content">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px">
      <h3 style="margin:0"><i class="fa fa-map-marker-alt"></i> Ubicación</h3>
      <button onclick="cerrarModal('mdl')"
        style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i
          class="fa fa-times"></i></button>
    </div>

    <div class="ap-chip" style="margin-bottom:10px"><span style="color:#dc3545;font-weight:700">*</span> Obligatorios:
      <b>Almacén</b> y <b>BL (CodigoCSD)</b>
    </div>

    <input type="hidden" id="idy_ubica">

    <div class="ap-form-grid">
      <div class="ap-field">
        <div class="ap-label">Almacén <span style="color:#dc3545">*</span></div>
        <div class="ap-input"><i class="fa fa-warehouse"></i><input id="cve_almac" placeholder="ID almacén"
            oninput="this.value=this.value.replace(/[^0-9]/g,'')"></div>
        <div class="ap-error" id="err_alm">Almacén obligatorio.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">BL / CodigoCSD <span style="color:#dc3545">*</span></div>
        <div class="ap-input"><i class="fa fa-qrcode"></i><input id="CodigoCSD" placeholder="Bin Location (BL)"></div>
        <div class="ap-error" id="err_bl">CodigoCSD obligatorio.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Clave RP</div>
        <div class="ap-input"><i class="fa fa-link"></i><input id="claverp" placeholder="claverp"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Sección</div>
        <div class="ap-input"><i class="fa fa-layer-group"></i><input id="Seccion" placeholder="Sección"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Ubicación</div>
        <div class="ap-input"><i class="fa fa-map-pin"></i><input id="Ubicacion" placeholder="Ubicación"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Pasillo</div>
        <div class="ap-input"><i class="fa fa-grip-lines-vertical"></i><input id="cve_pasillo" placeholder="Pasillo">
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Rack</div>
        <div class="ap-input"><i class="fa fa-th"></i><input id="cve_rack" placeholder="Rack"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Nivel</div>
        <div class="ap-input"><i class="fa fa-sort-numeric-up"></i><input id="cve_nivel" placeholder="Nivel"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Status</div>
        <div class="ap-input"><i class="fa fa-traffic-light"></i><input id="Status" placeholder="Ej. A/I/B (1 char)">
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Picking</div>
        <div class="ap-input"><i class="fa fa-hand-pointer"></i><input id="picking" placeholder="S/N (1 char)"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Tecnología</div>
        <div class="ap-input"><i class="fa fa-microchip"></i><input id="TECNOLOGIA" placeholder="RFID/QR/etc"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Tipo</div>
        <div class="ap-input"><i class="fa fa-tag"></i><input id="Tipo" placeholder="1 char"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Acomodo Mixto</div>
        <div class="ap-input"><i class="fa fa-random"></i>
          <select id="AcomodoMixto">
            <option value="">(vacío)</option>
            <option value="S">S</option>
            <option value="N">N</option>
          </select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Área Producción</div>
        <div class="ap-input"><i class="fa fa-industry"></i>
          <select id="AreaProduccion">
            <option value="">(vacío)</option>
            <option value="S">S</option>
            <option value="N">N</option>
          </select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Área Stagging</div>
        <div class="ap-input"><i class="fa fa-dolly"></i>
          <select id="AreaStagging">
            <option value="">(vacío)</option>
            <option value="S">S</option>
            <option value="N">N</option>
          </select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">CrossDocking</div>
        <div class="ap-input"><i class="fa fa-exchange-alt"></i>
          <select id="Ubicacion_CrossDocking">
            <option value="N">N</option>
            <option value="S">S</option>
          </select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Staging Pedidos</div>
        <div class="ap-input"><i class="fa fa-clipboard-list"></i>
          <select id="Staging_Pedidos">
            <option value="N">N</option>
            <option value="S">S</option>
          </select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">PTL</div>
        <div class="ap-input"><i class="fa fa-lightbulb"></i>
          <select id="Ptl">
            <option value="">(vacío)</option>
            <option value="S">S</option>
            <option value="N">N</option>
          </select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Maneja Cajas</div>
        <div class="ap-input"><i class="fa fa-box"></i>
          <select id="Maneja_Cajas">
            <option value="">(vacío)</option>
            <option value="S">S</option>
            <option value="N">N</option>
          </select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Maneja Piezas</div>
        <div class="ap-input"><i class="fa fa-cubes"></i>
          <select id="Maneja_Piezas">
            <option value="">(vacío)</option>
            <option value="S">S</option>
            <option value="N">N</option>
          </select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Reabasto</div>
        <div class="ap-input"><i class="fa fa-sync"></i>
          <select id="Reabasto">
            <option value="">(vacío)</option>
            <option value="S">S</option>
            <option value="N">N</option>
          </select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Orden Secuencia</div>
        <div class="ap-input"><i class="fa fa-sort-amount-up"></i><input id="orden_secuencia"
            oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Dim. Ancho</div>
        <div class="ap-input"><i class="fa fa-ruler-horizontal"></i><input id="num_ancho" placeholder="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Dim. Largo</div>
        <div class="ap-input"><i class="fa fa-ruler"></i><input id="num_largo" placeholder="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Dim. Alto</div>
        <div class="ap-input"><i class="fa fa-ruler-vertical"></i><input id="num_alto" placeholder="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Volumen Disp.</div>
        <div class="ap-input"><i class="fa fa-cube"></i><input id="num_volumenDisp" placeholder="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Peso Máximo</div>
        <div class="ap-input"><i class="fa fa-weight-hanging"></i><input id="PesoMaximo" placeholder="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Peso Ocupado</div>
        <div class="ap-input"><i class="fa fa-weight"></i><input id="PesoOcupado" placeholder="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Máximo</div>
        <div class="ap-input"><i class="fa fa-arrow-up"></i><input id="Maximo"
            oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Mínimo</div>
        <div class="ap-input"><i class="fa fa-arrow-down"></i><input id="Minimo"
            oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Clasif ABC</div>
        <div class="ap-input"><i class="fa fa-chart-line"></i><input id="clasif_abc" placeholder="A/B/C"></div>
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

    </div>

    <div style="text-align:right;margin-top:15px;display:flex;justify-content:flex-end;gap:10px">
      <button class="ghost" onclick="cerrarModal('mdl')">Cancelar</button>
      <button class="primary" onclick="guardar()">Guardar</button>
    </div>
  </div>
</div>

<!-- IMPORT -->
<div class="ap-modal" id="mdlImport">
  <div class="ap-modal-content" style="width:700px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px">
      <h3 style="margin:0"><i class="fa fa-upload"></i> Importar ubicaciones</h3>
      <button onclick="cerrarModal('mdlImport')"
        style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i
          class="fa fa-times"></i></button>
    </div>

    <div class="ap-chip" style="margin-bottom:15px">Layout FULL con UPSERT por BL (CodigoCSD). Previsualiza antes de
      importar.</div>

    <div class="ap-input">
      <i class="fa fa-file-csv"></i>
      <input type="file" id="fileCsv" accept=".csv">
    </div>

    <div style="margin-top:15px;display:flex;gap:10px">
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

      <div class="ap-help" id="importMsg" style="margin-top:15px"></div>
    </div>

    <div style="text-align:right;margin-top:15px;display:flex;justify-content:flex-end;gap:10px">
      <button class="ghost" onclick="cerrarModal('mdlImport')">Cerrar</button>
      <button class="primary" onclick="importarCsv()" id="btnImportarFinal" style="display:none;">Importar</button>
    </div>
  </div>
</div>

<script>
  const API = '../api/ubicaciones.php';

  let verInactivos = false;
  let qLast = '';
  let page = 1;
  let perPage = 25;
  let total = 0;
  let lastRows = [];

  function reqIndicator(u) {
    const ok = (String(u.cve_almac || '').trim() !== '') && (String(u.CodigoCSD || '').trim() !== '');
    return `<span class="ap-req-dot ${ok ? 'ap-req-ok' : ''}" title="${ok ? 'OK' : 'Faltan obligatorios'}"></span>`;
  }

  function flags(u) {
    let b = '';
    if ((u.AcomodoMixto || '') === 'S') b += `<span class="ap-badge ok"><i class="fa fa-random"></i> Mixto</span>`;
    if ((u.AreaProduccion || '') === 'S') b += `<span class="ap-badge"><i class="fa fa-industry"></i> Prod</span>`;
    if ((u.AreaStagging || '') === 'S') b += `<span class="ap-badge"><i class="fa fa-dolly"></i> Stg</span>`;
    if ((u.Ubicacion_CrossDocking || '') === 'S') b += `<span class="ap-badge"><i class="fa fa-exchange-alt"></i> XDock</span>`;
    if ((u.Staging_Pedidos || '') === 'S') b += `<span class="ap-badge"><i class="fa fa-clipboard-list"></i> Ped</span>`;
    if ((u.Ptl || '') === 'S') b += `<span class="ap-badge"><i class="fa fa-lightbulb"></i> PTL</span>`;
    return b || `<span class="ap-badge warn">N/A</span>`;
  }

  /* ===== Paginación ===== */
  function setPager() {
    const start = total > 0 ? ((page - 1) * perPage + (lastRows.length ? 1 : 0)) : 0;
    let end = total > 0 ? Math.min(page * perPage, total) : 0;
    if (total === 0) { end = 0; }

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
    else if (total === 0 && lastRows.length === perPage) { page++; cargar(); }
  }
  function goPage(p) { page = Math.max(1, parseInt(p, 10) || 1); cargar(); }
  function setPerPage(v) { perPage = parseInt(v, 10) || 25; page = 1; cargar(); }

  function cargar() {
    const offset = (page - 1) * perPage;
    const url = API + '?action=list&inactivos=' + (verInactivos ? 1 : 0) + '&q=' + encodeURIComponent(qLast || '')
      + '&limit=' + encodeURIComponent(perPage) + '&offset=' + encodeURIComponent(offset);

    fetch(url).then(r => r.json()).then(resp => {
      const rows = resp.rows || resp || [];
      total = Number(resp.total || 0) || 0;
      lastRows = rows;

      let h = '';
      rows.forEach(u => {
        h += `
      <tr>
        <td class="ap-actions">
          ${verInactivos
            ? `<i class="fa fa-undo" title="Recuperar" onclick="recuperar(${u.idy_ubica})"></i>`
            : `<i class="fa fa-edit" title="Editar" onclick="editar(${u.idy_ubica})"></i>
               <i class="fa fa-trash" title="Inactivar" onclick="eliminar(${u.idy_ubica})"></i>`}
        </td>
        <td>${reqIndicator(u)}</td>
        <td>${u.cve_almac || ''}</td>
        <td><b>${u.CodigoCSD || ''}</b></td>
        <td>${u.claverp || ''}</td>
        <td>${u.Seccion || ''}</td>
        <td>${u.Ubicacion || ''}</td>
        <td>${u.cve_pasillo || ''}</td>
        <td>${u.cve_rack || ''}</td>
        <td>${u.cve_nivel || ''}</td>
        <td>${u.TECNOLOGIA || ''}</td>
        <td>${u.Status || ''}</td>
        <td>${u.picking || ''}</td>
        <td>${u.orden_secuencia || ''}</td>
        <td>${u.num_ancho || ''}</td>
        <td>${u.num_largo || ''}</td>
        <td>${u.num_alto || ''}</td>
        <td>${u.num_volumenDisp || ''}</td>
        <td>${u.PesoMaximo || ''}</td>
        <td>${u.PesoOcupado || ''}</td>
        <td>${u.Maximo || ''}</td>
        <td>${u.Minimo || ''}</td>
        <td>${u.clasif_abc || ''}</td>
        <td>${u.Maneja_Cajas || ''}</td>
        <td>${u.Maneja_Piezas || ''}</td>
        <td>${u.Reabasto || ''}</td>
        <td>${Number(u.Activo || 1) === 1 ? '1' : '0'}</td>
        <td>${u.Tipo || ''}</td>
        <td>${flags(u)}</td>
      </tr>`;
      });
      tb.innerHTML = h || `<tr><td colspan="29" style="text-align:center;color:#6c757d;padding:20px">Sin resultados</td></tr>`;
      setPager();
    });
  }

  function buscar() { qLast = q.value.trim(); page = 1; cargar(); }
  function limpiarBusqueda() { q.value = ''; qLast = ''; page = 1; cargar(); }

  function hideErrors() { err_alm.style.display = 'none'; err_bl.style.display = 'none'; }
  function validar() {
    hideErrors();
    let ok = true;
    if (!cve_almac.value.trim()) { err_alm.style.display = 'block'; ok = false; }
    if (!CodigoCSD.value.trim()) { err_bl.style.display = 'block'; ok = false; }
    return ok;
  }

  function nuevo() {
    document.querySelectorAll('#mdl input').forEach(i => i.value = '');
    document.querySelectorAll('#mdl select').forEach(s => {
      if (s.id === 'Activo') s.value = '1';
      else if (s.id === 'Ubicacion_CrossDocking' || s.id === 'Staging_Pedidos') s.value = 'N';
      else s.value = '';
    });
    hideErrors();
    mdl.style.display = 'block';
  }

  function editar(id) {
    fetch(API + '?action=get&idy_ubica=' + id).then(r => r.json()).then(u => {
      for (let k in u) {
        const el = document.getElementById(k);
        if (el) el.value = (u[k] === null || u[k] === undefined) ? '' : u[k];
      }
      hideErrors();
      mdl.style.display = 'block';
    });
  }

  function guardar() {
    if (!validar()) return;

    const fd = new FormData();
    fd.append('action', idy_ubica.value ? 'update' : 'create');
    document.querySelectorAll('#mdl input').forEach(i => fd.append(i.id, i.value));
    document.querySelectorAll('#mdl select').forEach(s => fd.append(s.id, s.value));

    fetch(API, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(resp => {
        if (resp && resp.error) {
          alert(resp.error + (resp.detalles ? "\n- " + resp.detalles.join("\n- ") : ""));
          return;
        }
        cerrarModal('mdl');
        cargar();
      });
  }

  function eliminar(id) {
    if (!confirm('¿Inactivar ubicación?')) return;
    const fd = new FormData(); fd.append('action', 'delete'); fd.append('idy_ubica', id);
    fetch(API, { method: 'POST', body: fd }).then(() => cargar());
  }
  function recuperar(id) {
    const fd = new FormData(); fd.append('action', 'restore'); fd.append('idy_ubica', id);
    fetch(API, { method: 'POST', body: fd }).then(() => cargar());
  }

  function exportarDatos() { window.open(API + '?action=export_csv&tipo=datos', '_blank'); }
  function descargarLayout() { window.open(API + '?action=export_csv&tipo=layout', '_blank'); }

  function abrirImport() {
    fileCsv.value = '';
    csvPreviewWrap.style.display = 'none';
    importMsg.innerHTML = '';
    document.getElementById('btnImportarFinal').style.display = 'none';
    mdlImport.style.display = 'block';
  }

  function previsualizarCsv() {
    const f = fileCsv.files[0];
    if (!f) { alert('Selecciona un CSV'); return; }
    const r = new FileReader();
    r.onload = e => {
      const rows = e.target.result.split('\n').filter(x => x.trim() !== '');
      csvHead.innerHTML = '<tr>' + rows[0].split(',').map(h => `<th>${h}</th>`).join('') + '</tr>';
      csvBody.innerHTML = rows.slice(1, 6).map(r => '<tr>' + r.split(',').map(c => `<td>${c}</td>`).join('') + '</tr>').join('');
      csvPreviewWrap.style.display = 'block';
      importMsg.innerHTML = '';
      document.getElementById('btnImportarFinal').style.display = 'block';
    };
    r.readAsText(f);
  }

  function importarCsv() {
    const fd = new FormData();
    fd.append('action', 'import_csv');
    fd.append('file', fileCsv.files[0]);

    fetch(API, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(resp => {
        if (resp.error) {
          importMsg.innerHTML = `<span style="color:#dc3545"><b>Error:</b> ${resp.error}</span>`;
          return;
        }
        const ok = resp.rows_ok ?? 0;
        const err = resp.rows_err ?? 0;
        importMsg.innerHTML = `<span style="color:#0f5132"><b>Importación:</b> OK ${ok} | Err ${err}</span>`;
        if (err > 0 && resp.errores) {
          importMsg.innerHTML += `<div class="ap-help">Primeros errores: ${resp.errores.slice(0, 3).map(e => `Fila ${e.fila}: ${e.motivo}`).join(' · ')}</div>`;
        }
        setTimeout(() => { cerrarModal('mdlImport'); cargar(); }, 2000);
      });
  }

  function toggleInactivos() { verInactivos = !verInactivos; page = 1; cargar(); }
  function cerrarModal(id) { document.getElementById(id).style.display = 'none'; }

  document.addEventListener('DOMContentLoaded', () => {
    selPerPage.value = '25';
    cargar();
  });
</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
?>