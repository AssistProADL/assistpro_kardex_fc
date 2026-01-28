<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
  /* =========================================================
   ASSISTPRO STYLES - Orden de Producción
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

  .ap-card .k {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-top: 6px;
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
    font-size: 12px;
  }

  .ap-search input {
    border: none;
    background: transparent;
    outline: none;
    width: 100%;
    font-size: 13px;
    color: #495057;
  }

  /* CHIPS / BUTTONS */
  .ap-chip {
    font-size: 12px;
    background: #f8f9fa;
    color: #495057;
    border: 1px solid #ced4da;
    border-radius: 4px;
    padding: 5px 12px;
    display: inline-flex;
    gap: 5px;
    align-items: center;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.15s;
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
    border-color: #0b5ed7;
  }

  .ap-chip.success {
    background: #198754;
    color: #fff;
    border-color: #198754;
  }

  .ap-chip.success:hover {
    background: #157347;
    border-color: #157347;
  }

  .ap-chip.danger {
    background: #dc3545;
    color: #fff;
    border-color: #dc3545;
  }

  .ap-chip.danger:hover {
    background: #bb2d3b;
    border-color: #bb2d3b;
  }

  .ap-chip.ok {
    background: #d1e7dd;
    color: #0f5132;
    border-color: #badbcc;
  }

  .ap-chip.warn {
    background: #fff3cd;
    color: #664d03;
    border-color: #ffe69c;
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
    height: calc(100vh - 220px);
    overflow-y: auto;
    overflow-x: auto;
  }

  .ap-grid table {
    width: 100%;
    border-collapse: collapse;
  }

  .ap-grid th {
    background: #fff;
    padding: 5px 8px;
    text-align: left;
    font-weight: 600;
    color: #212529;
    border-bottom: 2px solid #dee2e6;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 10;
    font-size: 11px;
    line-height: 1.3;
  }

  .ap-grid td {
    padding: 4px 8px;
    border-bottom: 1px solid #e9ecef;
    color: #212529;
    vertical-align: middle;
    white-space: nowrap;
    font-size: 11px;
    background: #fff;
    font-weight: 400;
    line-height: 1.2;
  }

  .ap-grid tbody tr:nth-child(even) td {
    background: #f8f9fa;
  }

  .ap-grid tr:hover td {
    background: #e9ecef !important;
  }

  .ap-grid tr.inactivo td {
    background: #f3f3f3 !important;
    color: #999;
  }

  .ap-actions i {
    cursor: pointer;
    margin-right: 8px;
    color: #6c757d;
    transition: color 0.15s;
    font-size: 13px;
  }

  .ap-actions i:hover {
    color: #0d6efd;
  }

  /* MODAL & FORM - ASSISTPRO STYLE */
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
    width: 900px;
    max-width: 95%;
    max-height: 90vh;
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    padding: 24px;
  }

  /* Form Grid similar a usuarios.php pero adaptado para más campos */
  .ap-form {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
    margin-top: 20px;
  }

  /* Sin títulos de sección intrusivos, solo el grid limpio */

  .ap-form.full {
    grid-template-columns: 1fr;
  }

  .ap-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }

  .ap-field.full-width {
    grid-column: 1 / -1;
  }

  /* Labels estilo usuarios.php */
  .ap-label {
    font-weight: 500;
    font-size: 13px;
    color: #495057;
  }

  /* Inputs estilo usuarios.php */
  .ap-input {
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 8px 12px;
    background: #fff;
    transition: all 0.2s;
    min-height: 38px;
  }

  .ap-input:focus-within {
    border-color: #0b5ed7;
    box-shadow: 0 0 0 3px rgba(11, 94, 215, 0.1);
  }

  .ap-input i {
    color: #adb5bd;
    /* Iconos sutiles */
    font-size: 14px;
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
    padding: 0;
    margin: 0;
  }

  .ap-input textarea {
    resize: vertical;
    min-height: 60px;
    padding: 4px 0;
  }

  .ap-err-msg {
    color: #dc3545;
    font-size: 11px;
    margin-top: 2px;
    display: none;
  }

  /* PAGER */
  .ap-pager {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 12px;
    padding: 0 5px;
    font-size: 12px;
  }

  /* BUTTONS */
  button.primary {
    background: #0d6efd;
    color: #fff;
    border: none;
    padding: 7px 16px;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.15s;
    font-size: 13px;
  }

  button.primary:hover {
    background: #0b5ed7;
  }

  button.success {
    background: #198754;
    color: #fff;
    border: none;
    padding: 7px 16px;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.15s;
    font-size: 13px;
  }

  button.success:hover {
    background: #157347;
  }

  button.danger {
    background: #dc3545;
    color: #fff;
    border: none;
    padding: 7px 16px;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
    font-size: 13px;
  }

  button.ghost {
    background: #fff;
    color: #495057;
    border: 1px solid #ced4da;
    padding: 7px 16px;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s;
    font-size: 13px;
  }

  button.ghost:hover {
    background: #e9ecef;
    border-color: #adb5bd;
  }

  /* SECTION HEADERS */
  .ap-section-header {
    font-size: 14px;
    font-weight: 600;
    color: #0d6efd;
    margin: 20px 0 12px 0;
    padding-bottom: 6px;
    border-bottom: 2px solid #e9ecef;
    grid-column: 1 / -1;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .ap-section-header:first-child {
    margin-top: 0;
  }

  /* SELECT CHEVRON INDICATOR */
  .ap-input select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236c757d' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 8px center;
    padding-right: 28px;
    cursor: pointer;
  }
</style>

<div class="ap-container">
  <div class="ap-title"><i class="fa fa-boxes-stacked"></i> Catálogo de Artículos</div>

  <div class="ap-cards" id="cards"></div>

  <div class="ap-toolbar">
    <div class="ap-search">
      <i class="fa fa-search"></i>
      <input id="q" placeholder="Buscar por clave, descripción, SAP, grupo, tipo, barras…"
        onkeydown="if(event.key==='Enter')buscar()">
    </div>
    <button class="ap-chip primary" onclick="buscar()">Buscar</button>
    <button class="ap-chip" onclick="limpiar()">Limpiar</button>

    <div style="flex:1"></div>

    <button class="ap-chip" onclick="nuevo()"><i class="fa fa-plus"></i> Nuevo</button>
    <button class="ap-chip success" onclick="exportarDatos()"><i class="fa fa-download"></i> Exportar</button>
    <button class="ap-chip" onclick="abrirImport()"><i class="fa fa-upload"></i> Importar</button>
    <button class="ap-chip" onclick="descargarLayout()"><i class="fa fa-file-csv"></i> Layout</button>
    <button class="ap-chip" id="btnToggle" onclick="toggleInactivos()"><i class="fa fa-eye"></i> Ver inactivos</button>
  </div>

  <!-- FILTROS RÁPIDOS -->
  <div class="ap-toolbar" style="flex-wrap: wrap; gap: 8px;">
    <div style="display: flex; align-items: center; gap: 6px;">
      <i class="fa fa-filter" style="color: #6c757d; font-size: 12px;"></i>
      <span style="font-size: 12px; font-weight: 600; color: #495057;">Filtros:</span>
    </div>

    <div style="display: flex; align-items: center; gap: 4px;">
      <label style="font-size: 11px; color: #6c757d;">Grupo:</label>
      <select id="filtro_grupo" onchange="aplicarFiltros()"
        style="font-size: 11px; padding: 3px 6px; border: 1px solid #ced4da; border-radius: 4px; background: #fff;">
        <option value="">Todos</option>
      </select>
    </div>

    <div style="display: flex; align-items: center; gap: 4px;">
      <label style="font-size: 11px; color: #6c757d;">Tipo:</label>
      <select id="filtro_tipo" onchange="aplicarFiltros()"
        style="font-size: 11px; padding: 3px 6px; border: 1px solid #ced4da; border-radius: 4px; background: #fff;">
        <option value="">Todos</option>
      </select>
    </div>

    <div style="display: flex; align-items: center; gap: 4px;">
      <label style="font-size: 11px; color: #6c757d;">Clasificación:</label>
      <select id="filtro_clasificacion" onchange="aplicarFiltros()"
        style="font-size: 11px; padding: 3px 6px; border: 1px solid #ced4da; border-radius: 4px; background: #fff;">
        <option value="">Todos</option>
      </select>
    </div>

    <div style="display: flex; align-items: center; gap: 4px;">
      <label style="font-size: 11px; color: #6c757d;">Compuesto:</label>
      <select id="filtro_compuesto" onchange="aplicarFiltros()"
        style="font-size: 11px; padding: 3px 6px; border: 1px solid #ced4da; border-radius: 4px; background: #fff;">
        <option value="">Todos</option>
        <option value="S">Sí</option>
        <option value="N">No</option>
      </select>
    </div>

    <div style="display: flex; align-items: center; gap: 4px;">
      <label style="font-size: 11px; color: #6c757d;">Caduca:</label>
      <select id="filtro_caduca" onchange="aplicarFiltros()"
        style="font-size: 11px; padding: 3px 6px; border: 1px solid #ced4da; border-radius: 4px; background: #fff;">
        <option value="">Todos</option>
        <option value="S">Sí</option>
        <option value="N">No</option>
      </select>
    </div>

    <button class="ap-chip" onclick="limpiarFiltros()" style="margin-left: auto;">
      <i class="fa fa-times"></i> Limpiar filtros
    </button>
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
          <th>CB Pieza</th>
          <th>CB Caja</th>
          <th>CB Pallet</th>
          <th>Descripción</th>
          <th>KIT</th>
          <th>Usa Envase</th>
          <th>Dimensiones (mm)</th>
          <th>Volumen (m3)</th>
          <th>Peso U (Kgs)</th>
          <th>Costo</th>
          <th>Precio</th>
          <th>Piezas por Caja</th>
          <th>Cajas por Pallet</th>
          <th>Grupo</th>
          <th>Clasificación</th>
          <th>Tipo</th>
          <th>Empresa|Proveedor</th>
          <th>Tipo de Producto</th>
          <th>UMAS</th>
          <th>U. Medida</th>
          <th>Clave Alterna</th>
          <th>Caduca</th>
          <th>Lotes</th>
          <th>Series</th>
          <th>Garantía</th>
          <th>Ecom. Activo</th>
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
      <div class="ap-chip"><span style="color:#dc3545;font-weight:700">*</span> Obligatorios: <b>Almacén, ID, Clave,
          Descripción</b></div>
      <button onclick="cerrarModal('mdl')"
        style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i
          class="fa fa-times"></i></button>
    </div>

    <input type="hidden" id="k_cve_almac">
    <input type="hidden" id="k_id">

    <div class="ap-form">
      <!-- SECCIÓN: CONTEXTO -->
      <div class="ap-section-header">
        <i class="fa fa-warehouse"></i> Contexto
      </div>
      <div class="ap-field">
        <div class="ap-label">Almacén *</div>
        <div class="ap-input"><i class="fa fa-warehouse"></i>
          <select id="cve_almac">
            <option value="">Seleccione...</option>
          </select>
        </div>
        <div id="err_cve_almac" class="ap-err-msg">Requerido</div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Estado</div>
        <div class="ap-input"><i class="fa fa-toggle-on"></i><select id="Activo">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select></div>
      </div>

      <!-- SECCIÓN: IDENTIFICACIÓN -->
      <div class="ap-section-header">
        <i class="fa fa-fingerprint"></i> Identificación del Producto
      </div>
      <div class="ap-field">
        <div class="ap-label">ID *</div>
        <div class="ap-input"><i class="fa fa-hashtag"></i><input id="id"
            oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="1001"></div>
        <div id="err_id" class="ap-err-msg">Requerido</div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Clave Artículo *</div>
        <div class="ap-input"><i class="fa fa-barcode"></i><input id="cve_articulo" placeholder="ART-001"></div>
        <div id="err_cve_articulo" class="ap-err-msg">Requerido</div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Clave Alterna</div>
        <div class="ap-input"><i class="fa fa-code-branch"></i><input id="cve_alt" placeholder="ALT-001"></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">SAP</div>
        <div class="ap-input"><i class="fa fa-link"></i><input id="Cve_SAP" placeholder="SAP-001"></div>
      </div>
      <div class="ap-field full-width">
        <div class="ap-label">Descripción *</div>
        <div class="ap-input"><i class="fa fa-align-left"></i><input id="des_articulo" placeholder="Descripción corta">
        </div>
        <div id="err_des_articulo" class="ap-err-msg">Requerido</div>
      </div>
      <div class="ap-field full-width">
        <div class="ap-label">Descripción Detallada</div>
        <div class="ap-input"><i class="fa fa-file-alt"></i><textarea id="des_detallada" rows="2"
            placeholder="Descripción detallada"></textarea></div>
      </div>

      <!-- SECCIÓN: CLASIFICACIÓN -->
      <div class="ap-section-header">
        <i class="fa fa-sitemap"></i> Clasificación y Características
      </div>
      <div class="ap-field">
        <div class="ap-label">Grupo</div>
        <div class="ap-input"><i class="fa fa-layer-group"></i><select id="grupo">
            <option value="">Seleccione...</option>
          </select></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Clasificación</div>
        <div class="ap-input"><i class="fa fa-sitemap"></i><select id="clasificacion">
            <option value="">Seleccione...</option>
          </select></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Tipo</div>
        <div class="ap-input"><i class="fa fa-tag"></i><select id="tipo">
            <option value="">Seleccione...</option>
          </select></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Tipo Producto</div>
        <div class="ap-input"><i class="fa fa-cubes"></i><select id="tipo_producto">
            <option value="">Seleccione...</option>
          </select></div>
      </div>
      <div class="ap-field full-width">
        <div class="ap-label">Proveedor</div>
        <div class="ap-input"><i class="fa fa-truck"></i><select id="proveedor">
            <option value="">Seleccione...</option>
          </select></div>
      </div>

      <!-- SECCIÓN: CONFIGURACIÓN LOGÍSTICA -->
      <div class="ap-section-header">
        <i class="fa fa-boxes"></i> Configuración Logística (Unidades)
      </div>
      <div class="ap-field">
        <div class="ap-label">U. Medida Base</div>
        <div class="ap-input"><i class="fa fa-ruler-combined"></i><select id="unidadMedida">
            <option value="">Seleccione...</option>
          </select></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Clave U.M.</div>
        <div class="ap-input"><i class="fa fa-tag"></i><input id="cve_umed"
            oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="1"></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">UMAS</div>
        <div class="ap-input"><i class="fa fa-ruler"></i><input id="umas" placeholder="UMAS"></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Peso (Kg)</div>
        <div class="ap-input"><i class="fa fa-weight-hanging"></i><input id="peso" placeholder="0.00"></div>
      </div>
      <div class="ap-field full-width">
        <div class="ap-label">Dimensiones (Alto x Ancho x Fondo)</div>
        <div class="ap-input-group" style="display:flex; gap:5px;">
          <div class="ap-input" style="flex:1"><i class="fa fa-arrows-v"></i><input id="alto" placeholder="Alto (mm)"
              type="number" step="0.01"></div>
          <div class="ap-input" style="flex:1"><i class="fa fa-arrows-h"></i><input id="ancho" placeholder="Ancho (mm)"
              type="number" step="0.01"></div>
          <div class="ap-input" style="flex:1"><i class="fa fa-expand"></i><input id="fondo" placeholder="Fondo (mm)"
              type="number" step="0.01"></div>
        </div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Usa Envase</div>
        <div class="ap-input"><i class="fa fa-wine-bottle"></i><select id="Usa_Envase">
            <option value="N">No</option>
            <option value="S">Sí</option>
          </select></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Unidades x Caja</div>
        <div class="ap-input"><i class="fa fa-boxes"></i><input id="num_multiplo" placeholder="Piezas por Caja"></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Cajas x Pallet</div>
        <div class="ap-input"><i class="fa fa-pallet"></i><input id="cajas_palet" placeholder="Cajas por Pallet"></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Barras (Pieza)</div>
        <div class="ap-input"><i class="fa fa-barcode"></i><input id="cve_codprov" placeholder="Código de Barra"></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Barras 2 (Caja)</div>
        <div class="ap-input"><i class="fa fa-barcode"></i><input id="barras2" placeholder="Código de Caja"></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Barras 3 (Pallet)</div>
        <div class="ap-input"><i class="fa fa-barcode"></i><input id="barras3" placeholder="Código Pallet"></div>
      </div>

      <!-- SECCIÓN: CONTROL Y TRAZABILIDAD -->
      <div class="ap-section-header">
        <i class="fa fa-shield"></i> Control y Trazabilidad
      </div>
      <div class="ap-field">
        <div class="ap-label">Control ABC</div>
        <div class="ap-input"><i class="fa fa-sort-alpha-down"></i><select id="control_abc">
            <option value="">(vacío)</option>
            <option value="A">A</option>
            <option value="B">B</option>
            <option value="C">C</option>
          </select></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Control Lotes</div>
        <div class="ap-input"><i class="fa fa-box"></i><select id="control_lotes">
            <option value="N">No</option>
            <option value="S">Sí</option>
          </select></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Control Series</div>
        <div class="ap-input"><i class="fa fa-fingerprint"></i><select id="control_numero_series">
            <option value="N">No</option>
            <option value="S">Sí</option>
          </select></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Caduca</div>
        <div class="ap-input"><i class="fa fa-hourglass"></i><select id="Caduca">
            <option value="N">No</option>
            <option value="S">Sí</option>
          </select></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Control Garantía</div>
        <div class="ap-input"><i class="fa fa-shield"></i><select id="control_garantia">
            <option value="N">No</option>
            <option value="S">Sí</option>
          </select></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Compuesto</div>
        <div class="ap-input"><i class="fa fa-diagram-project"></i><select id="Compuesto">
            <option value="N">No</option>
            <option value="S">Sí</option>
          </select></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Tipo Garantía</div>
        <div class="ap-input"><i class="fa fa-calendar"></i><select id="tipo_garantia">
            <option value="">(vacío)</option>
            <option value="MESES">MESES</option>
            <option value="ANIOS">AÑOS</option>
            <option value="HORAS_USO">HORAS USO</option>
          </select></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Valor Garantía</div>
        <div class="ap-input"><i class="fa fa-hashtag"></i><input id="valor_garantia"
            oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="0"></div>
      </div>

      <!-- SECCIÓN: INFORMACIÓN COMERCIAL -->
      <div class="ap-section-header">
        <i class="fa fa-dollar-sign"></i> Información Comercial
      </div>
      <div class="ap-field">
        <div class="ap-label">Moneda</div>
        <div class="ap-input"><i class="fa fa-coins"></i><input id="moneda" placeholder="MXN" disabled value="Pesos">
        </div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Costo</div>
        <div class="ap-input"><i class="fa fa-dollar-sign"></i><input id="imp_costo" placeholder="0.00"></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Precio Venta</div>
        <div class="ap-input"><i class="fa fa-cash-register"></i><input id="PrecioVenta" placeholder="0.00"></div>
      </div>

      <!-- SECCIÓN: ECOMMERCE -->
      <div class="ap-section-header">
        <i class="fa fa-globe"></i> Ecommerce
      </div>
      <div class="ap-field">
        <div class="ap-label">Activo en Web</div>
        <div class="ap-input"><i class="fa fa-globe"></i><select id="ecommerce_activo">
            <option value="0">No</option>
            <option value="1">Sí</option>
          </select></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Destacado</div>
        <div class="ap-input"><i class="fa fa-star"></i><select id="ecommerce_destacado">
            <option value="0">No</option>
            <option value="1">Sí</option>
          </select></div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Categoría Web</div>
        <div class="ap-input"><i class="fa fa-tags"></i><input id="ecommerce_categoria" placeholder="Categoría Web">
        </div>
      </div>
      <div class="ap-field">
        <div class="ap-label">Subcategoría Web</div>
        <div class="ap-input"><i class="fa fa-tag"></i><input id="ecommerce_subcategoria"
            placeholder="Subcategoría Web"></div>
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
      <button onclick="cerrarModal('mdlImport')"
        style="background:transparent; border:none; font-size:18px; cursor:pointer;"><i
          class="fa fa-times"></i></button>
    </div>

    <div class="ap-chip" style="margin-bottom:15px">UPSERT por <b>(Almacén + ID)</b>. Columnas: <b>Almacén, ID, Clave,
        Descripción...</b> + opcionales.</div>

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

      <div class="ap-chip" id="importMsg" style="margin-top:15px; width:100%; display:none; justify-content:center;">
      </div>

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
    } catch (e) { console.error(e); }
  }

  function limpiar() { document.getElementById('q').value = ''; refrescar(1); }
  function buscar() { refrescar(1); }

  // Funciones de filtros
  function aplicarFiltros() {
    renderGrid(cacheRows);
  }

  function limpiarFiltros() {
    document.getElementById('filtro_grupo').value = '';
    document.getElementById('filtro_tipo').value = '';
    document.getElementById('filtro_clasificacion').value = '';
    document.getElementById('filtro_compuesto').value = '';
    document.getElementById('filtro_caduca').value = '';
    document.getElementById('filtro_lotes').value = '';
    document.getElementById('filtro_series').value = '';
    refrescar(1);
  }

  // Cargar catálogos de forma independiente para evitar interferencias
  async function cargarAlmacenes() {
    try {
      const r = await fetch(`${API}?action=autocomplete&field=almacen`);
      const j = await r.json();
      const sel = document.getElementById('cve_almac');
      if (j.values && sel) {
        const current = sel.value;
        sel.innerHTML = '<option value="">Seleccione...</option>' +
          j.values.map(v => `<option value="${v.id}">${v.id} - ${esc(v.nombre)}</option>`).join('');
        if (current) sel.value = current;
      }
    } catch (e) { console.error('Error cargando almacenes:', e); }
  }

  async function cargarGrupos(cve_almac = '') {
    try {
      const r = await fetch(`${API}?action=autocomplete&field=grupo&cve_almac=${cve_almac}`);
      const j = await r.json();
      const sel = document.getElementById('grupo');
      const filtro = document.getElementById('filtro_grupo');

      if (j.values) {
        const opciones = j.values.map(v => `<option value="${v.id}">${v.id} - ${esc(v.nombre)}</option>`).join('');
        if (sel) {
          const current = sel.value;
          sel.innerHTML = '<option value="">Seleccione...</option>' + opciones;
          if (current && j.values.find(v => v.id == current)) sel.value = current;
        }
        if (filtro) {
          const current = filtro.value;
          filtro.innerHTML = '<option value="">Todos</option>' + opciones;
          if (current) filtro.value = current;
        }
      }
    } catch (e) { console.error('Error cargando grupos:', e); }
  }

  async function cargarClasificaciones(cve_almac = '', grupo_id = '') {
    try {
      const r = await fetch(`${API}?action=autocomplete&field=clasificacion&cve_almac=${cve_almac}&parent_id=${grupo_id}`);
      const j = await r.json();
      const sel = document.getElementById('clasificacion');
      const filtro = document.getElementById('filtro_clasificacion');

      if (j.values) {
        const opciones = j.values.map(v => `<option value="${v.id}">${v.id} - ${esc(v.nombre)}</option>`).join('');
        if (sel) {
          const current = sel.value;
          sel.innerHTML = '<option value="">Seleccione...</option>' + opciones;
          // Solo mantener el valor si existe en las nuevas opciones
          if (current && j.values.find(v => v.id == current)) sel.value = current;
        }
        if (filtro) {
          const current = filtro.value;
          filtro.innerHTML = '<option value="">Todos</option>' + opciones;
          if (current) filtro.value = current;
        }
      }
    } catch (e) { console.error('Error cargando clasificaciones:', e); }
  }

  async function cargarTipos(cve_almac = '', clasificacion_id = '') {
    try {
      const r = await fetch(`${API}?action=autocomplete&field=tipo&cve_almac=${cve_almac}&parent_id=${clasificacion_id}`);
      const j = await r.json();
      const sel = document.getElementById('tipo');
      const filtro = document.getElementById('filtro_tipo');

      if (j.values) {
        const opciones = j.values.map(v => `<option value="${v.id}">${v.id} - ${esc(v.nombre)}</option>`).join('');
        if (sel) {
          const current = sel.value;
          sel.innerHTML = '<option value="">Seleccione...</option>' + opciones;
          if (current && j.values.find(v => v.id == current)) sel.value = current;
        }
        if (filtro) {
          const current = filtro.value;
          filtro.innerHTML = '<option value="">Todos</option>' + opciones;
          if (current) filtro.value = current;
        }
      }
    } catch (e) { console.error('Error cargando tipos:', e); }
  }

  async function cargarProveedores() {
    try {
      const r = await fetch(`${API}?action=autocomplete&field=proveedor`);
      const j = await r.json();
      const sel = document.getElementById('proveedor');
      if (j.values && sel) {
        const current = sel.value;
        sel.innerHTML = '<option value="">Seleccione...</option>' +
          j.values.map(v => `<option value="${v.id}">${v.id} - ${esc(v.nombre)}</option>`).join('');
        if (current) sel.value = current;
      }
    } catch (e) { console.error('Error cargando proveedores:', e); }
  }

  async function cargarUnidadesMedida() {
    try {
      const r = await fetch(`${API}?action=autocomplete&field=unidadMedida`);
      const j = await r.json();
      const sel = document.getElementById('unidadMedida');
      if (j.values && sel) {
        const current = sel.value;
        sel.innerHTML = '<option value="">Seleccione...</option>' +
          j.values.map(v => `<option value="${v.id}">${v.id} - ${esc(v.nombre)}</option>`).join('');
        if (current) sel.value = current;
      }
    } catch (e) { console.error('Error cargando unidades:', e); }
  }

  async function cargarTiposProducto() {
    try {
      const r = await fetch(`${API}?action=autocomplete&field=tipo_producto`);
      const j = await r.json();
      const sel = document.getElementById('tipo_producto');
      if (j.values && sel) {
        const current = sel.value;
        sel.innerHTML = '<option value="">Seleccione...</option>' +
          j.values.map(v => `<option value="${v.id}">${v.id} - ${esc(v.nombre)}</option>`).join('');
        if (current) sel.value = current;
      }
    } catch (e) { console.error('Error cargando tipos producto:', e); }
  }

  // Función para cargar todos los catálogos iniciales
  async function cargarTodosCatalogos() {
    await cargarAlmacenes();
    await cargarGrupos();
    await cargarClasificaciones();
    await cargarTipos();
    await cargarProveedores();
    await cargarUnidadesMedida();
    await cargarTiposProducto();
    console.log('✅ Todos los catálogos cargados');
  }

  // Event listeners para cascada
  document.addEventListener('DOMContentLoaded', () => {
    // Listener para Almacén - recarga Grupos cuando cambia
    const selAlmacen = document.getElementById('cve_almac');
    if (selAlmacen) {
      selAlmacen.addEventListener('change', function () {
        const almacen = this.value;
        console.log('Almacén changed:', almacen);
        cargarGrupos(almacen);
        // Limpiar dependientes
        document.getElementById('grupo').value = '';
        document.getElementById('clasificacion').innerHTML = '<option value="">Seleccione...</option>';
        document.getElementById('tipo').innerHTML = '<option value="">Seleccione...</option>';
      });
    }

    // Listener para Grupo - recarga Clasificaciones cuando cambia
    const selGrupo = document.getElementById('grupo');
    if (selGrupo) {
      selGrupo.addEventListener('change', function () {
        const almacen = document.getElementById('cve_almac')?.value || '';
        const grupo = this.value;
        console.log('Grupo changed:', { grupo, almacen });
        cargarClasificaciones(almacen, grupo);
        // Limpiar dependientes
        document.getElementById('clasificacion').value = '';
        document.getElementById('tipo').innerHTML = '<option value="">Seleccione...</option>';
      });
    }

    // Listener para Clasificación - recarga Tipos cuando cambia
    const selClasif = document.getElementById('clasificacion');
    if (selClasif) {
      selClasif.addEventListener('change', function () {
        const almacen = document.getElementById('cve_almac')?.value || '';
        const clasificacion = this.value;
        console.log('Clasificación changed:', { clasificacion, almacen });
        cargarTipos(almacen, clasificacion);
        // Limpiar dependiente
        document.getElementById('tipo').value = '';
      });
    }
  });

  // ========== LISTENERS PARA FILTROS DE LA TABLA ==========
  document.addEventListener('DOMContentLoaded', () => {
    const filtroGrupo = document.getElementById('filtro_grupo');
    if (filtroGrupo) {
      filtroGrupo.addEventListener('change', () => {
        console.log('Filtro Grupo changed');
        refrescar(1); // Recargar desde página 1
      });
    }

    const filtroTipo = document.getElementById('filtro_tipo');
    if (filtroTipo) {
      filtroTipo.addEventListener('change', () => {
        console.log('Filtro Tipo changed');
        refrescar(1);
      });
    }

    const filtroClasif = document.getElementById('filtro_clasificacion');
    if (filtroClasif) {
      filtroClasif.addEventListener('change', () => {
        console.log('Filtro Clasificación changed');
        refrescar(1);
      });
    }

    const filtroCompuesto = document.getElementById('filtro_compuesto');
    if (filtroCompuesto) {
      filtroCompuesto.addEventListener('change', () => {
        console.log('Filtro Compuesto changed');
        refrescar(1);
      });
    }

    const filtroCaduca = document.getElementById('filtro_caduca');
    if (filtroCaduca) {
      filtroCaduca.addEventListener('change', () => {
        console.log('Filtro Caduca changed');
        refrescar(1);
      });
    }
  });

  function filtrarRows(rows) {
    const grupo = document.getElementById('filtro_grupo')?.value || '';
    const tipo = document.getElementById('filtro_tipo')?.value || '';
    const clasificacion = document.getElementById('filtro_clasificacion')?.value || '';
    const compuesto = document.getElementById('filtro_compuesto')?.value || '';
    const caduca = document.getElementById('filtro_caduca')?.value || '';

    console.log('Filtros activos:', { grupo, tipo, clasificacion, compuesto, caduca });

    const filtered = rows.filter(r => {
      if (grupo && String(r.grupo) !== String(grupo)) {
        console.log(`Filtrado por grupo: ${r.grupo} !== ${grupo}`);
        return false;
      }
      if (tipo && String(r.tipo) !== String(tipo)) {
        console.log(`Filtrado por tipo: ${r.tipo} !== ${tipo}`);
        return false;
      }
      if (clasificacion && String(r.clasificacion) !== String(clasificacion)) {
        console.log(`Filtrado por clasificacion: ${r.clasificacion} !== ${clasificacion}`);
        return false;
      }
      if (compuesto && String(r.Compuesto) !== String(compuesto)) return false;
      if (caduca && String(r.Caduca) !== String(caduca)) return false;
      return true;
    });

    console.log(`Filtrados: ${filtered.length} de ${rows.length} registros`);
    return filtered;
  }

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

    // Obtener valores de filtros
    const grupo = document.getElementById('filtro_grupo')?.value || '';
    const tipo = document.getElementById('filtro_tipo')?.value || '';
    const clasificacion = document.getElementById('filtro_clasificacion')?.value || '';
    const compuesto = document.getElementById('filtro_compuesto')?.value || '';
    const caduca = document.getElementById('filtro_caduca')?.value || '';

    // Construir URL con filtros
    let url = `${API}?action=list&inactivos=${verInactivos}&q=${q}&page=${curPage}&limit=25`;
    if (grupo) url += `&grupo=${grupo}`;
    if (tipo) url += `&tipo=${tipo}`;
    if (clasificacion) url += `&clasificacion=${clasificacion}`;
    if (compuesto) url += `&compuesto=${compuesto}`;
    if (caduca) url += `&caduca=${caduca}`;

    console.log('Refrescando con filtros:', { grupo, tipo, clasificacion, compuesto, caduca });

    try {
      const r = await fetch(url);
      const j = await r.json();
      if (j.error) { showMsg('Error: ' + j.error, 'warn'); return; }

      cacheRows = j.rows || [];
      renderGrid(cacheRows); // Ya no necesita filtrarRows porque viene filtrado del backend
      renderPager(j.page, j.pages, j.total);
    } catch (e) { console.error(e); }
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

    // Aplicar filtros
    const filteredRows = filtrarRows(rows);

    if (!filteredRows.length) {
      tb.innerHTML = '<tr><td colspan="22" style="text-align:center;padding:20px;color:#777">Sin resultados</td></tr>';
      return;
    }

    tb.innerHTML = filteredRows.map(r => {
      const act = (parseInt(r.Activo ?? 1, 10) === 1);
      return `<tr>
      <td class="ap-actions">
        <i class="fa fa-pencil-alt" onclick="editar({cve_almac:${r.cve_almac},id:${r.id}})" title="Editar"></i>
        ${r.Activo == 1
          ? `<i class="fa fa-trash" onclick="cambiarEstado(${r.id},0)" title="Desactivar" style="color:#d9534f"></i>`
          : `<i class="fa fa-check" onclick="cambiarEstado(${r.id},1)" title="Activar" style="color:#5cb85c"></i>`
        }
      </td>
      <td>${esc(r.almacen_nombre || r.cve_almac)}</td>
      <td>${esc(r.id)}</td>
      <td><b>${esc(r.cve_articulo)}</b></td>
      <td>${esc(r.cve_codprov)}</td>
      <td>${esc(r.barras2)}</td>
      <td>${esc(r.barras3)}</td>
      <td>${esc(r.des_articulo)}</td>
      <td>${esc(r.Compuesto)}</td>
      <td>${esc(r.Usa_Envase)}</td>
      <td style="text-align:right">${esc(r.alto || 0)}x${esc(r.ancho || 0)}x${esc(r.fondo || 0)}</td>
      <td style="text-align:right">${esc(r.volumen)}</td>
      <td style="text-align:right">${esc(r.peso)}</td>
      <td style="text-align:right">${esc(r.imp_costo)}</td>
      <td style="text-align:right">${esc(r.PrecioVenta)}</td>
      <td style="text-align:right">${esc(r.num_multiplo)}</td>
      <td style="text-align:right">${esc(r.cajas_palet)}</td>
      <td style="font-weight:bold; color:blue">${esc(r.grupo)}</td>
      <td>${esc(r.clasificacion_nombre || r.clasificacion)}</td>
      <td>${esc(r.tipo_nombre || r.tipo)}</td>
      <td>${esc(r.proveedor_nombre)}</td>
      <td>${esc(r.tipo_producto_nombre || r.tipo_producto)}</td>
      <td style="text-align:center">${esc(r.umas)}</td>
      <td style="text-align:center">${esc(r.unidadMedida_nombre || r.unidadMedida)}</td>
      <td>${esc(r.clave_alterna)}</td>
      <td style="text-align:center">${esc(r.Caduca)}</td>
      <td style="text-align:center">${esc(r.control_lotes)}</td>
      <td style="text-align:center">${esc(r.control_numero_series)}</td>
      <td style="text-align:center">${esc(r.control_garantia)}</td>
      <td style="text-align:center">${esc(r.ecommerce_activo)}</td>
    </tr>`;
    }).join('');
  }

  function nuevo() {
    [
      'k_cve_almac', 'k_id', 'cve_almac', 'id', 'cve_articulo', 'des_articulo', 'des_detallada', 'unidadMedida', 'cve_umed', 'imp_costo', 'PrecioVenta',
      'tipo', 'grupo', 'clasificacion', 'Compuesto', 'Caduca', 'control_lotes', 'control_numero_series', 'control_garantia', 'tipo_garantia', 'valor_garantia',
      'Cve_SAP', 'cve_alt', 'barras2', 'barras3', 'ecommerce_activo', 'ecommerce_categoria', 'ecommerce_subcategoria', 'ecommerce_destacado', 'Activo',
      'alto', 'ancho', 'fondo', 'peso', 'num_multiplo', 'cajas_palet', 'Usa_Envase', 'proveedor', 'tipo_producto', 'umas', 'control_abc', 'cve_codprov'
    ].forEach(x => { const el = document.getElementById(x); if (el) el.value = ''; });

    document.getElementById('ecommerce_activo').value = '0';
    document.getElementById('ecommerce_destacado').value = '0';
    document.getElementById('Activo').value = '1';
    document.getElementById('Usa_Envase').value = 'N';
    document.getElementById('Compuesto').value = 'N';
    document.getElementById('Caduca').value = 'N';
    document.getElementById('control_lotes').value = 'N';
    document.getElementById('control_numero_series').value = 'N';
    document.getElementById('control_garantia').value = 'N';

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

      // Cascade Load
      document.getElementById('cve_almac').value = j.cve_almac;

      await cargarGrupos(j.cve_almac);
      document.getElementById('grupo').value = j.grupo;

      await cargarClasificaciones(j.cve_almac, j.grupo);
      document.getElementById('clasificacion').value = j.clasificacion;

      await cargarTipos(j.cve_almac, j.clasificacion);
      document.getElementById('tipo').value = j.tipo;

      const map = [
        'id', 'cve_articulo', 'des_articulo', 'des_detallada', 'unidadMedida', 'cve_umed', 'imp_costo', 'PrecioVenta',
        'Compuesto', 'Caduca', 'control_lotes', 'control_numero_series', 'control_garantia', 'tipo_garantia', 'valor_garantia',
        'Cve_SAP', 'cve_alt', 'barras2', 'barras3', 'ecommerce_activo', 'ecommerce_categoria', 'ecommerce_subcategoria', 'ecommerce_destacado', 'Activo',
        'alto', 'ancho', 'fondo', 'peso', 'num_multiplo', 'cajas_palet', 'Usa_Envase', 'proveedor', 'tipo_producto', 'umas', 'control_abc', 'cve_codprov'
      ];
      map.forEach(f => {
        const el = document.getElementById(f);
        if (el) {
          if (f === 'proveedor') {
            el.value = (j['ID_Proveedor'] ?? '');
          } else {
            el.value = (j[f] ?? '');
          }
        }
      });

      abrirModal('mdl');
    } catch (e) { console.error(e); }
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
      'Cve_SAP', 'cve_alt', 'barras2', 'barras3', 'ecommerce_activo', 'ecommerce_categoria', 'ecommerce_subcategoria', 'ecommerce_destacado', 'Activo',
      'alto', 'ancho', 'fondo', 'peso', 'num_multiplo', 'cajas_palet', 'Usa_Envase', 'proveedor', 'tipo_producto', 'umas', 'control_abc', 'cve_codprov'
    ];
    fields.forEach(f => fd.append(f, (document.getElementById(f)?.value ?? '').toString().trim()));


    try {
      const r = await fetch(API, { method: 'POST', body: fd });
      const j = await r.json();
      if (j.error) { showMsg('Error: ' + j.error, 'warn'); return; }

      cerrarModal('mdl');
      showMsg('<i class="fa fa-check"></i> Guardado', 'ok');
      refrescar();
    } catch (e) { console.error(e); }
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
    } catch (e) { console.error(e); }
  }

  async function alta(cve_almac, id) {
    if (!confirm('¿Reactivar artículo?')) return;
    const fd = new FormData();
    fd.append('action', 'restore');
    fd.append('cve_almac', cve_almac);
    fd.append('id', id);
    try {
      const r = await fetch(API, { method: 'POST', body: fd });
      const j = await r.json();
      if (j.error) { showMsg('Error: ' + j.error, 'warn'); return; }
      showMsg('<i class="fa fa-rotate-left"></i> Reactivado', 'ok');
      refrescar();
    } catch (e) { console.error(e); }
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

    } catch (e) {
      im.className = 'ap-chip warn';
      im.innerHTML = 'Error de red';
      console.error(e);
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    console.log('Articulos v2 loaded');
    cargarTodosCatalogos();
    refrescar();
  });
</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
?>