<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
/* =========================================================
   ASSISTPRO – ARTÍCULOS
========================================================= */
body { font-family: system-ui, -apple-system, sans-serif; background: #f4f6fb; margin: 0; }
.ap-container { padding: 20px; font-size: 13px; max-width: 100%; margin: 0 auto; }

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
.ap-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 20px; }
.ap-card {
  background: #fff;
  border: 1px solid #e0e6ed;
  border-radius: 12px;
  padding: 15px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
  transition: all 0.2s;
  cursor: pointer;
}
.ap-card:hover { border-color: #0b5ed7; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(11, 94, 215, 0.1); }
.ap-card .h { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; font-weight: 600; color: #333; }
.ap-card .k { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }

/* TOOLBAR */
.ap-toolbar { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 15px; background: #fff; padding: 10px; border-radius: 10px; border: 1px solid #e0e6ed; }
.ap-search { display: flex; align-items: center; gap: 8px; flex: 1; min-width: 300px; background: #f8f9fa; padding: 6px 12px; border-radius: 8px; border: 1px solid #dee2e6; }
.ap-search i { color: #6c757d; }
.ap-search input { border: none; background: transparent; outline: none; width: 100%; font-size: 13px; }

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
.ap-chip:hover { background: #e9ecef; color: #212529; border-color: #ced4da; }
.ap-chip.ok { background: #d1e7dd; color: #0f5132; border-color: #badbcc; }
.ap-chip.warn { background: #fff3cd; color: #664d03; border-color: #ffecb5; }
button.ap-chip { font-family: inherit; }

/* GRID */
.ap-grid {
  background: #fff;
  border: 1px solid #e0e6ed;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
  height: calc(100vh - 280px);
  overflow-y: auto;
}
.ap-grid table { width: 100%; border-collapse: collapse; }
.ap-grid th { background: #f8f9fa; padding: 12px; text-align: left; font-weight: 600; color: #495057; border-bottom: 1px solid #dee2e6; white-space: nowrap; position: sticky; top: 0; z-index: 10; }
.ap-grid td { padding: 10px 12px; border-bottom: 1px solid #f1f3f5; color: #212529; vertical-align: middle; white-space: nowrap; }
.ap-grid tr:hover td { background: #f8f9fa; }
.ap-actions i { cursor: pointer; margin-right: 12px; color: #6c757d; transition: color 0.2s; font-size: 14px; }
.ap-actions i:hover { color: #0b5ed7; }

/* MODAL */
.ap-modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(2px); }
.ap-modal[style*="display: block"] { display: flex !important; }
.ap-modal-content { background: #fff; width: 1100px; max-width: 95%; max-height: 90vh; border-radius: 12px; display: flex; flex-direction: column; overflow-y: auto; box-shadow: 0 10px 25px rgba(0,0,0,0.1); padding: 20px; }

.ap-form { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 15px; }
.ap-field { display: flex; flex-direction: column; gap: 5px; }
.ap-label { font-weight: 500; font-size: 13px; color: #495057; }
.ap-input { display: flex; align-items: center; gap: 10px; border: 1px solid #dee2e6; border-radius: 8px; padding: 8px 12px; background: #fff; transition: all 0.2s; }
.ap-input:focus-within { border-color: #0b5ed7; box-shadow: 0 0 0 3px rgba(11, 94, 215, 0.1); }
.ap-input i { color: #adb5bd; }
.ap-input input, .ap-input select, .ap-input textarea { border: none; outline: none; width: 100%; font-size: 14px; color: #212529; background: transparent; }
.ap-input textarea { min-height: 60px; resize: vertical; margin: 0; }
.ap-error { font-size: 12px; color: #dc3545; display: none; margin-top: 4px; }

/* PAGER */
.ap-pager { display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding: 0 5px; }

button.primary { background: #0b5ed7; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 500; cursor: pointer; transition: background 0.2s; }
button.primary:hover { background: #0a58ca; }
button.ghost { background: #fff; color: #495057; border: 1px solid #dee2e6; padding: 8px 16px; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.2s; }
button.ghost:hover { background: #f1f3f5; border-color: #ced4da; }
</style>

<div class="ap-container">
  <div class="ap-title"><i class="fa fa-boxes-stacked"></i> Catálogo de Artículos</div>

  <div class="ap-cards" id="cards"></div>

  <div class="ap-toolbar">
    <div class="ap-search">
      <i class="fa fa-search"></i>
      <input id="q" placeholder="Buscar por clave, descripción, SAP, grupo, tipo, barras…" onkeydown="if(event.key==='Enter')buscar()">
    </div>
    <button class="ap-chip" onclick="buscar()">Buscar</button>
    <button class="ap-chip" onclick="limpiar()">Limpiar</button>

    <div style="flex:1"></div>

    <button class="ap-chip" onclick="nuevo()"><i class="fa fa-plus"></i> Nuevo</button>
    <button class="ap-chip" onclick="exportarDatos()"><i class="fa fa-download"></i> Exportar</button>
    <button class="ap-chip" onclick="abrirImport()"><i class="fa fa-upload"></i> Importar</button>
    <button class="ap-chip" onclick="descargarLayout()"><i class="fa fa-file-csv"></i> Layout</button>
    <button class="ap-chip" id="btnToggle" onclick="toggleInactivos()"><i class="fa fa-eye"></i> Ver inactivos</button>
  </div>
  
  <span class="ap-chip" id="msg" style="display:none; margin-bottom:10px;"></span>

  <div class="ap-grid">
    <table>
      <thead>
        <tr>
          <th>Acciones</th>
          <th>Almacén</th>
          <th>ID</th>
          <th>Clave</th>
          <th>Descripción</th>
          <th>U. Medida</th>
          <th>Clave U.M.</th>
          <th>Costo</th>
          <th>Precio</th>
          <th>Tipo</th>
          <th>Grupo</th>
          <th>Clasificación</th>
          <th>Compuesto</th>
          <th>Caduca</th>
          <th>Lotes</th>
          <th>Series</th>
          <th>Garantía</th>
          <th>Ecom. Activo</th>
          <th>Ecom. Cat</th>
          <th>Ecom. Sub</th>
          <th>Destacado</th>
          <th>Estatus</th>
        </tr>
      </thead>
      <tbody id="tb"></tbody>
    </table>
  </div>

  <div id="pager" class="ap-pager"></div>
</div>

<!-- MODAL EDIT -->
<div class="ap-modal" id="mdl">
  <div class="ap-modal-content">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px">
      <h3 style="margin:0"><i class="fa fa-cube"></i> Artículo</h3>
      <div class="ap-chip"><span style="color:#dc3545;font-weight:700">*</span> Obligatorios: <b>Almacén, ID, Clave, Descripción</b></div>
      <button onclick="cerrarModal('mdl')" style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i class="fa fa-times"></i></button>
    </div>

    <input type="hidden" id="k_cve_almac">
    <input type="hidden" id="k_id">

    <div class="ap-form">
      <div class="ap-field">
        <div class="ap-label">Almacén *</div>
        <div class="ap-input"><i class="fa fa-warehouse"></i>
          <input id="cve_almac" oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="1">
        </div>
        <div class="ap-error" id="err_cve_almac">Almacén obligatorio.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">ID *</div>
        <div class="ap-input"><i class="fa fa-hashtag"></i>
          <input id="id" oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="1001">
        </div>
        <div class="ap-error" id="err_id">ID obligatorio.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Clave Artículo *</div>
        <div class="ap-input"><i class="fa fa-barcode"></i><input id="cve_articulo" placeholder="ART-001"></div>
        <div class="ap-error" id="err_cve_articulo">Clave obligatoria.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Descripción *</div>
        <div class="ap-input"><i class="fa fa-align-left"></i><input id="des_articulo" placeholder="Descripción corta"></div>
        <div class="ap-error" id="err_des_articulo">Descripción obligatoria.</div>
      </div>

      <div class="ap-field" style="grid-column:span 2">
        <div class="ap-label">Descripción Detallada</div>
        <div class="ap-input"><i class="fa fa-file-lines"></i><textarea id="des_detallada" placeholder="Descripción detallada"></textarea></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Unidad Medida</div>
        <div class="ap-input"><i class="fa fa-ruler"></i><input id="unidadMedida" oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="1"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Clave U.M.</div>
        <div class="ap-input"><i class="fa fa-ruler-combined"></i><input id="cve_umed" oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="1"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Costo</div>
        <div class="ap-input"><i class="fa fa-dollar-sign"></i><input id="imp_costo" placeholder="12.50"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Precio Venta</div>
        <div class="ap-input"><i class="fa fa-cash-register"></i><input id="PrecioVenta" placeholder="18.90"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Tipo</div>
        <div class="ap-input"><i class="fa fa-tag"></i><input id="tipo" placeholder="PT"></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Grupo</div>
        <div class="ap-input"><i class="fa fa-layer-group"></i><input id="grupo" placeholder="GPO1"></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Clasificación</div>
        <div class="ap-input"><i class="fa fa-sitemap"></i><input id="clasificacion" placeholder="CLAS1"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Compuesto</div>
        <div class="ap-input"><i class="fa fa-diagram-project"></i><select id="Compuesto">
            <option value="">(vacío)</option>
            <option value="S">S</option>
            <option value="N">N</option>
          </select></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Caduca</div>
        <div class="ap-input"><i class="fa fa-hourglass"></i><select id="Caduca">
            <option value="">(vacío)</option>
            <option value="S">S</option>
            <option value="N">N</option>
          </select></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Control Lotes</div>
        <div class="ap-input"><i class="fa fa-box"></i><select id="control_lotes">
            <option value="">(vacío)</option>
            <option value="S">S</option>
            <option value="N">N</option>
          </select></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Control Series</div>
        <div class="ap-input"><i class="fa fa-fingerprint"></i><select id="control_numero_series">
            <option value="">(vacío)</option>
            <option value="S">S</option>
            <option value="N">N</option>
          </select></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Control Garantía</div>
        <div class="ap-input"><i class="fa fa-shield"></i><select id="control_garantia">
            <option value="">(vacío)</option>
            <option value="S">S</option>
            <option value="N">N</option>
          </select></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Tipo Garantía</div>
        <div class="ap-input"><i class="fa fa-calendar"></i>
          <select id="tipo_garantia">
            <option value="">(vacío)</option>
            <option value="MESES">MESES</option>
            <option value="ANIOS">ANIOS</option>
            <option value="HORAS_USO">HORAS_USO</option>
          </select>
        </div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Valor Garantía</div>
        <div class="ap-input"><i class="fa fa-hashtag"></i><input id="valor_garantia" oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="0"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">SAP</div>
        <div class="ap-input"><i class="fa fa-link"></i><input id="Cve_SAP" placeholder="SAP-001"></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Clave Alterna</div>
        <div class="ap-input"><i class="fa fa-code-branch"></i><input id="cve_alt" placeholder="ALT-001"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Barras 2</div>
        <div class="ap-input"><i class="fa fa-barcode"></i><input id="barras2" placeholder="750..."></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Barras 3</div>
        <div class="ap-input"><i class="fa fa-barcode"></i><input id="barras3" placeholder="750..."></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Ecommerce Activo</div>
        <div class="ap-input"><i class="fa fa-cart-shopping"></i><select id="ecommerce_activo">
            <option value="0">No</option>
            <option value="1">Sí</option>
          </select></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Ecommerce Cat.</div>
        <div class="ap-input"><i class="fa fa-tags"></i><input id="ecommerce_categoria" placeholder="CAT"></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Ecommerce Sub.</div>
        <div class="ap-input"><i class="fa fa-tag"></i><input id="ecommerce_subcategoria" placeholder="SUB"></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Ecommerce Destacado</div>
        <div class="ap-input"><i class="fa fa-star"></i><select id="ecommerce_destacado">
            <option value="0">No</option>
            <option value="1">Sí</option>
          </select></div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Estatus</div>
        <div class="ap-input"><i class="fa fa-toggle-on"></i><select id="Activo">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select></div>
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
  <div class="ap-modal-content" style="width:700px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px">
      <h3 style="margin:0"><i class="fa fa-upload"></i> Importar artículos</h3>
      <button onclick="cerrarModal('mdlImport')" style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i class="fa fa-times"></i></button>
    </div>
    
    <div class="ap-chip" style="margin-bottom:15px">UPSERT por <b>(Almacén + ID)</b>. Columnas: <b>Almacén, ID, Clave, Descripción...</b> + opcionales.</div>

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
        <table>
          <thead id="csvHead"></thead>
          <tbody id="csvBody"></tbody>
        </table>
      </div>
      
      <div class="ap-chip" id="importMsg" style="margin-top:15px; width:100%; display:none; justify-content:center;"></div>
      
      <div style="text-align:right;margin-top:15px;">
        <button class="primary" onclick="importarCsv()"><i class="fa fa-upload"></i> Importar</button>
      </div>
    </div>
  </div>
</div>

<script>
  const API = '../api/articulos_api.php';
  const KPI = '../api/articulos_api_kpi.php';

  const CSV_MAP = {
    'Almacén': 'cve_almac', 'ID': 'id', 'Clave': 'cve_articulo', 'Descripción': 'des_articulo',
    'U. Medida': 'unidadMedida', 'Clave U.M.': 'cve_umed', 'Costo': 'imp_costo', 'Precio': 'PrecioVenta',
    'Tipo': 'tipo', 'Grupo': 'grupo', 'Clasificación': 'clasificacion', 'Compuesto': 'Compuesto', 'Caduca': 'Caduca',
    'Lotes': 'control_lotes', 'Series': 'control_numero_series', 'Garantía': 'control_garantia', 'Tipo Garantía': 'tipo_garantia', 'Valor Garantía': 'valor_garantia',
    'SAP': 'Cve_SAP', 'Clave Alterna': 'cve_alt', 'Barras 2': 'barras2', 'Barras 3': 'barras3',
    'Ecom. Activo': 'ecommerce_activo', 'Ecom. Cat': 'ecommerce_categoria', 'Ecom. Sub': 'ecommerce_subcategoria', 'Destacado': 'ecommerce_destacado',
    'Estatus': 'Activo'
  };

  let verInactivos = 0;
  let cacheRows = [];
  let previewRows = [];
  let curPage = 1;

  function showMsg(txt, cls = '') { const m = document.getElementById('msg'); m.style.display = 'inline-flex'; m.className = 'ap-chip ' + cls; m.innerHTML = txt; setTimeout(() => { m.style.display = 'none' }, 3500); }
  function abrirModal(id) { document.getElementById(id).style.display = 'block'; }
  function cerrarModal(id) { document.getElementById(id).style.display = 'none'; }

  function card(title, icon, chips) {
    return `
    <div class="ap-card" onclick="refrescar(1)">
      <div class="h"><b>${title}</b><i class="fa ${icon}"></i></div>
      <div class="k">${chips.map(c => `<span class="ap-chip ${c.cls || ''}">${c.txt}</span>`).join('')}</div>
    </div>`;
  }

  async function cargarKPI() {
    try {
        const r = await fetch(KPI + '?action=kpi'); 
        const j = await r.json();
        const cards = document.getElementById('cards');
        cards.innerHTML =
        card('Total', 'fa-database', [{ txt: `${j.total || 0} Registros` }]) +
        card('Activos', 'fa-circle-check', [{ txt: `${j.activos || 0} Operando`, cls: 'ok' }]) +
        card('Inactivos', 'fa-trash', [{ txt: `${j.inactivos || 0} Depurados`, cls: 'warn' }]);
    } catch(e) { console.error(e); }
  }

  function limpiar() { document.getElementById('q').value = ''; refrescar(1); }
  function buscar() { refrescar(1); }

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

  async function refrescar(p) {
    if (p) curPage = p;
    await cargarKPI();
    const q = encodeURIComponent(document.getElementById('q').value || '');
    const url = `${API}?action=list&inactivos=${verInactivos}&q=${q}&page=${curPage}&limit=25`;
    try {
        const r = await fetch(url);
        const j = await r.json();
        if (j.error) { showMsg('Error: ' + j.error, 'warn'); return; }

        cacheRows = j.rows || [];
        renderGrid(cacheRows);
        renderPager(j.page, j.pages, j.total);
    } catch(e) { console.error(e); }
  }

  function renderPager(page, pages, total) {
    const p = document.getElementById('pager');
    if (!p) return;
    const limit = 25;
    const start = total > 0 ? (page - 1) * limit + 1 : 0;
    const end = Math.min(page * limit, total);

    const prev = page > 1 ? `<button class="ap-chip" onclick="refrescar(${page - 1})"><i class="fa fa-chevron-left"></i> Anterior</button>` : `<button class="ap-chip" disabled style="opacity:0.5;cursor:default"><i class="fa fa-chevron-left"></i> Anterior</button>`;
    const next = page < pages ? `<button class="ap-chip" onclick="refrescar(${page + 1})">Siguiente <i class="fa fa-chevron-right"></i></button>` : `<button class="ap-chip" disabled style="opacity:0.5;cursor:default">Siguiente <i class="fa fa-chevron-right"></i></button>`;

    p.innerHTML = `
    <div style="font-size:13px;color:#6b7280;font-weight:500">
      Mostrando <b>${start}</b> a <b>${end}</b> de <b>${total}</b> registros
    </div>
    <div style="display:flex;gap:8px">${prev} ${next}</div>
  `;
  }

  function esc(s) { return (s ?? '').toString().replace(/[&<>"']/g, m => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" }[m])); }

  function renderGrid(rows) {
    const tb = document.getElementById('tb');
    if(!rows.length) { tb.innerHTML = '<tr><td colspan="22" style="text-align:center;padding:20px;color:#777">Sin resultados</td></tr>'; return; }
    tb.innerHTML = rows.map(r => {
      const act = (parseInt(r.Activo ?? 1, 10) === 1);
      return `<tr>
      <td class="ap-actions">
        <i class="fa fa-pen" title="Editar" onclick='editar(${JSON.stringify({ cve_almac: r.cve_almac, id: r.id }).replace(/'/g, "&#039;")})'></i>
        ${act
          ? `<i class="fa fa-trash" title="Desactivar" onclick='baja(${r.cve_almac},${r.id})'></i>`
          : `<i class="fa fa-rotate-left" title="Reactivar" onclick='alta(${r.cve_almac},${r.id})'></i>`
        }
      </td>
      <td>${esc(r.cve_almac)}</td>
      <td>${esc(r.id)}</td>
      <td><b>${esc(r.cve_articulo)}</b></td>
      <td>${esc(r.des_articulo)}</td>
      <td>${esc(r.unidadMedida)}</td>
      <td>${esc(r.cve_umed)}</td>
      <td>${esc(r.imp_costo)}</td>
      <td>${esc(r.PrecioVenta)}</td>
      <td>${esc(r.tipo)}</td>
      <td>${esc(r.grupo)}</td>
      <td>${esc(r.clasificacion)}</td>
      <td>${esc(r.Compuesto)}</td>
      <td>${esc(r.Caduca)}</td>
      <td>${esc(r.control_lotes)}</td>
      <td>${esc(r.control_numero_series)}</td>
      <td>${esc(r.control_garantia)}</td>
      <td>${esc(r.ecommerce_activo)}</td>
      <td>${esc(r.ecommerce_categoria)}</td>
      <td>${esc(r.ecommerce_subcategoria)}</td>
      <td>${esc(r.ecommerce_destacado)}</td>
      <td>${act ? '<span class="ap-chip ok">1</span>' : '<span class="ap-chip warn">0</span>'}</td>
    </tr>`;
    }).join('');
  }

  function nuevo() {
    ['k_cve_almac', 'k_id', 'cve_almac', 'id', 'cve_articulo', 'des_articulo', 'des_detallada', 'unidadMedida', 'cve_umed', 'imp_costo', 'PrecioVenta',
      'tipo', 'grupo', 'clasificacion', 'Compuesto', 'Caduca', 'control_lotes', 'control_numero_series', 'control_garantia', 'tipo_garantia', 'valor_garantia',
      'Cve_SAP', 'cve_alt', 'barras2', 'barras3', 'ecommerce_activo', 'ecommerce_categoria', 'ecommerce_subcategoria', 'ecommerce_destacado', 'Activo'
    ].forEach(x => { const el = document.getElementById(x); if (el) el.value = ''; });

    document.getElementById('ecommerce_activo').value = '0';
    document.getElementById('ecommerce_destacado').value = '0';
    document.getElementById('Activo').value = '1';

    document.getElementById('k_cve_almac').value = '';
    document.getElementById('k_id').value = '';
    abrirModal('mdl');
  }

  function setErr(id, on) {
    const e = document.getElementById(id);
    if (!e) return;
    e.style.display = on ? 'block' : 'none';
  }

  async function editar(key) {
    try {
        const r = await fetch(`${API}?action=get&cve_almac=${encodeURIComponent(key.cve_almac)}&id=${encodeURIComponent(key.id)}`);
        const j = await r.json();
        if (j.error) { showMsg('Error: ' + j.error, 'warn'); return; }
    
        document.getElementById('k_cve_almac').value = j.cve_almac;
        document.getElementById('k_id').value = j.id;
    
        const map = [
          'cve_almac', 'id', 'cve_articulo', 'des_articulo', 'des_detallada', 'unidadMedida', 'cve_umed', 'imp_costo', 'PrecioVenta',
          'tipo', 'grupo', 'clasificacion', 'Compuesto', 'Caduca', 'control_lotes', 'control_numero_series', 'control_garantia', 'tipo_garantia', 'valor_garantia',
          'Cve_SAP', 'cve_alt', 'barras2', 'barras3', 'ecommerce_activo', 'ecommerce_categoria', 'ecommerce_subcategoria', 'ecommerce_destacado', 'Activo'
        ];
        map.forEach(f => {
          const el = document.getElementById(f);
          if (el) el.value = (j[f] ?? '');
        });
    
        abrirModal('mdl');
    } catch(e) { console.error(e); }
  }

  async function guardar() {
    const cve_almac = document.getElementById('cve_almac').value.trim();
    const id = document.getElementById('id').value.trim();
    const cve_articulo = document.getElementById('cve_articulo').value.trim();
    const des_articulo = document.getElementById('des_articulo').value.trim();

    setErr('err_cve_almac', cve_almac === '');
    setErr('err_id', id === '');
    setErr('err_cve_articulo', cve_articulo === '');
    setErr('err_des_articulo', des_articulo === '');

    if (cve_almac === '' || id === '' || cve_articulo === '' || des_articulo === '') return;

    const k_cve_almac = document.getElementById('k_cve_almac').value.trim();
    const k_id = document.getElementById('k_id').value.trim();
    const isUpdate = (k_cve_almac !== '' && k_id !== '');

    const fd = new FormData();
    fd.append('action', isUpdate ? 'update' : 'create');
    fd.append('k_cve_almac', k_cve_almac);
    fd.append('k_id', k_id);

    const fields = [
      'cve_almac', 'id', 'cve_articulo', 'des_articulo', 'des_detallada', 'unidadMedida', 'cve_umed', 'imp_costo', 'PrecioVenta',
      'tipo', 'grupo', 'clasificacion', 'Compuesto', 'Caduca', 'control_lotes', 'control_numero_series', 'control_garantia', 'tipo_garantia', 'valor_garantia',
      'Cve_SAP', 'cve_alt', 'barras2', 'barras3', 'ecommerce_activo', 'ecommerce_categoria', 'ecommerce_subcategoria', 'ecommerce_destacado', 'Activo'
    ];
    fields.forEach(f => fd.append(f, (document.getElementById(f)?.value ?? '').toString().trim()));

    try {
        const r = await fetch(API, { method: 'POST', body: fd });
        const j = await r.json();
        if (j.error) { showMsg('Error: ' + j.error, 'warn'); return; }
    
        cerrarModal('mdl');
        showMsg('<i class="fa fa-check"></i> Guardado', 'ok');
        refrescar();
    } catch(e) { console.error(e); }
  }

  async function baja(cve_almac, id) {
    if (!confirm('¿Desactivar artículo?')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('cve_almac', cve_almac);
    fd.append('id', id);
    try {
        const r = await fetch(API, { method: 'POST', body: fd });
        const j = await r.json();
        if (j.error) { showMsg('Error: ' + j.error, 'warn'); return; }
        showMsg('<i class="fa fa-trash"></i> Desactivado', 'warn');
        refrescar();
    } catch(e) { console.error(e); }
  }

  async function alta(cve_almac, id) {
    if (!confirm('¿Reactivar artículo?')) return;
    const fd = new FormData();
    fd.append('action', 'restore');
    fd.append('cve_almac', cve_almac);
    fd.append('id', id);
    try{
        const r = await fetch(API, { method: 'POST', body: fd });
        const j = await r.json();
        if (j.error) { showMsg('Error: ' + j.error, 'warn'); return; }
        showMsg('<i class="fa fa-rotate-left"></i> Reactivado', 'ok');
        refrescar();
    } catch(e) { console.error(e); }
  }

  function exportarDatos() {
    const q = encodeURIComponent(document.getElementById('q').value || '');
    window.location = `${API}?action=export&inactivos=${verInactivos}&q=${q}`;
  }

  function descargarLayout() {
    window.location = `${API}?action=layout`;
  }

  function abrirImport() {
    document.getElementById('fileCsv').value = '';
    document.getElementById('csvPreviewWrap').style.display = 'none';
    document.getElementById('importMsg').style.display = 'none';
    previewRows = [];
    abrirModal('mdlImport');
  }

  function parseCSV(text) {
    const lines = text.replace(/\r/g, '').split('\n').filter(x => x.trim() !== '');
    if (!lines.length) return { headers: [], rows: [] };

    const headers = lines[0].split(',').map(h => h.trim());
    // Use global CSV_MAP
    // Check if friendly
    const isFriendly = headers.some(h => CSV_MAP[h]);

    const rows = [];
    for (let i = 1; i < lines.length; i++) {
        // Handle commas inside quotes? Basic split for now. 
        // Note: Logic in previous files was basic split too. 
      const cols = lines[i].split(',');
      const obj = {};
      headers.forEach((h, idx) => {
        const key = isFriendly ? (CSV_MAP[h] || h) : h;
        obj[key] = (cols[idx] ?? '').trim();
      });
      rows.push(obj);
    }
    return { headers, rows };
  }

  async function previsualizarCsv() {
    const f = document.getElementById('fileCsv').files[0];
    if (!f) { alert('Selecciona un CSV'); return; }
    const text = await f.text();
    const { headers, rows } = parseCSV(text);

    if (!headers.length || !rows.length) { alert('CSV vacío'); return; }

    // Fix Preview: accessing rows by HEADER name will fail if mapped. 
    // We must map the header to key to get value.
    const isFriendly = headers.some(h => CSV_MAP[h]);

    document.getElementById('csvHead').innerHTML = `<tr>${headers.map(h => `<th>${esc(h)}</th>`).join('')}</tr>`;
    document.getElementById('csvBody').innerHTML = rows.slice(0, 200).map(r => {
        return `<tr>${headers.map(h => {
            const key = isFriendly ? (CSV_MAP[h] || h) : h;
            return `<td>${esc(r[key])}</td>`;
        }).join('')}</tr>`;
    }).join('');

    previewRows = rows;
    document.getElementById('csvPreviewWrap').style.display = 'block';
  }

  async function importarCsv() {
    if (!previewRows.length) { alert('Primero previsualiza'); return; }

    const im = document.getElementById('importMsg');
    im.style.display = 'inline-flex';
    im.className = 'ap-chip';
    im.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Importando…';

    try {
        const r = await fetch(`${API}?action=import`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ rows: previewRows })
        });
    
        const j = await r.json();
        if (j.error) {
          im.className = 'ap-chip warn';
          im.innerHTML = `<i class="fa fa-triangle-exclamation"></i> ${esc(j.error)}`;
          return;
        }
    
        const ok = j.ok ?? 0, err = j.err ?? 0;
        im.className = err ? 'ap-chip warn' : 'ap-chip ok';
        im.innerHTML = `<i class="fa fa-check"></i> Importación OK: ${ok} | Errores: ${err}`;
    
        if (err && j.errores && j.errores.length) {
          console.warn('Errores import:', j.errores);
        }
        
        setTimeout(() => { cerrarModal('mdlImport'); refrescar(); }, 2500);

    } catch(e) { 
        im.className = 'ap-chip warn';
        im.innerHTML = 'Error de red';
        console.error(e);
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    console.log('Articulos v2 loaded');
    refrescar();
  });
</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
?>