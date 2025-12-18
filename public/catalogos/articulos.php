<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
.ap-container{padding:12px;font-size:12px}
.ap-title{font-size:18px;font-weight:600;color:#0b5ed7;margin-bottom:10px}
.ap-cards{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:10px}
.ap-card{width:250px;background:#fff;border:1px solid #d0d7e2;border-radius:10px;padding:10px;cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.ap-card:hover{border-color:#0b5ed7}
.ap-card .h{display:flex;justify-content:space-between;align-items:center}
.ap-card .h b{font-size:14px}
.ap-card .k{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
.ap-chip{font-size:11px;background:#eef2ff;color:#1e3a8a;border-radius:10px;padding:2px 8px;display:inline-flex;align-items:center;gap:6px}
.ap-chip.ok{background:#d1e7dd;color:#0f5132}
.ap-chip.warn{background:#fff3cd;color:#7a5d00}
.ap-toolbar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:10px}
.ap-search{display:flex;align-items:center;gap:6px;border:1px solid #d0d7e2;border-radius:6px;padding:4px 8px;background:#fff}
.ap-search i{color:#0b5ed7}
.ap-search input{border:0;outline:0;font-size:12px;width:320px}
.ap-grid{border:1px solid #dcdcdc;height:500px;overflow:auto}
.ap-grid table{width:100%;border-collapse:collapse}
.ap-grid th{position:sticky;top:0;background:#f4f6fb;padding:6px;border-bottom:1px solid #ccc}
.ap-grid td{padding:5px;border-bottom:1px solid #eee;white-space:nowrap;vertical-align:middle}
.ap-actions i{cursor:pointer;margin-right:8px;color:#0b5ed7}
.ap-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;overflow:auto}
.ap-modal-content{background:#fff;width:1100px;max-width:96vw;margin:2.5% auto;padding:15px;border-radius:10px}
.ap-form{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
.ap-field{display:flex;flex-direction:column;gap:4px}
.ap-label{font-weight:600}
.ap-input{display:flex;align-items:center;gap:8px;border:1px solid #d0d7e2;border-radius:6px;padding:6px 8px;background:#fff}
.ap-input i{color:#0b5ed7;min-width:14px}
.ap-input input,.ap-input select,.ap-input textarea{border:0;outline:0;font-size:12px;width:100%;background:transparent}
.ap-input textarea{resize:vertical}
.ap-error{display:none;color:#dc3545;font-size:11px}
button.primary{background:#0b5ed7;color:#fff;border:none;padding:6px 12px;border-radius:6px}
button.ghost{background:#fff;border:1px solid #d0d7e2;padding:6px 12px;border-radius:6px}
.ap-pager{display:flex;gap:8px;align-items:center;flex-wrap:wrap;justify-content:space-between;margin-top:8px}
.ap-pager .left,.ap-pager .right{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.ap-mini{font-size:11px;color:#6c757d}
.ap-btn{cursor:pointer}
.ap-badge{font-size:10px;border-radius:10px;padding:2px 8px}
.ap-badge.ok{background:#d1e7dd;color:#0f5132}
.ap-badge.no{background:#f8d7da;color:#842029}
.ap-spinner{display:none;position:fixed;inset:0;background:rgba(255,255,255,.55);z-index:9998;align-items:center;justify-content:center}
</style>

<div class="ap-spinner" id="spn">
  <div class="ap-chip"><i class="fa fa-spinner fa-spin"></i> Procesando…</div>
</div>

<div class="ap-container">
  <div class="ap-title"><i class="fa fa-cubes"></i> Catálogo de Artículos</div>

  <div class="ap-cards" id="cards"></div>

  <div class="ap-toolbar">
    <div class="ap-chip" id="filtroLabel"><i class="fa fa-filter"></i> Almacén: <b>Todos</b></div>

    <div class="ap-search">
      <i class="fa fa-search"></i>
      <input id="q" placeholder="Buscar SKU, descripción, grupo, tipo, SAP, barras…" onkeydown="if(event.key==='Enter')buscar()">
      <button class="ap-chip" onclick="limpiar()"><i class="fa fa-eraser"></i> Limpiar</button>
    </div>

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
          <th>ID</th>
          <th>Almacén</th>
          <th>SKU</th>
          <th>Descripción</th>
          <th>Grupo</th>
          <th>Clasif</th>
          <th>Tipo</th>
          <th>Tipo Prod</th>
          <th>Costo</th>
          <th>CostoProm</th>
          <th>Precio</th>
          <th>Lotes</th>
          <th>Series</th>
          <th>Garantía</th>
          <th>SAP</th>
          <th>eCom</th>
          <th>Dest</th>
          <th>Activo</th>
        </tr>
      </thead>
      <tbody id="tb"></tbody>
    </table>
  </div>

  <!-- Paginador -->
  <div class="ap-pager">
    <div class="left">
      <span class="ap-chip"><i class="fa fa-list-ol"></i> Mostrando <b id="lblRange">0-0</b> de <b id="lblTotal">0</b></span>
      <span class="ap-chip ap-btn" onclick="firstPage()"><i class="fa fa-angle-double-left"></i></span>
      <span class="ap-chip ap-btn" onclick="prevPage()"><i class="fa fa-angle-left"></i></span>
      <span class="ap-chip">Página <b id="lblPage">1</b> / <b id="lblPages">1</b></span>
      <span class="ap-chip ap-btn" onclick="nextPage()"><i class="fa fa-angle-right"></i></span>
      <span class="ap-chip ap-btn" onclick="lastPage()"><i class="fa fa-angle-double-right"></i></span>
    </div>
    <div class="right">
      <span class="ap-mini">Registros por página:</span>
      <select id="perPage" class="ap-chip" onchange="changePerPage()">
        <option value="25" selected>25</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>
      <span class="ap-mini">Ir a página:</span>
      <input id="goPage" class="ap-chip" style="width:80px" placeholder="1" onkeydown="if(event.key==='Enter')goToPage()">
      <button class="ap-chip" onclick="goToPage()"><i class="fa fa-share"></i> Ir</button>
    </div>
  </div>
</div>

<!-- MODAL ARTICULO -->
<div class="ap-modal" id="mdl">
  <div class="ap-modal-content">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
      <h3 style="margin:0"><i class="fa fa-cube"></i> Artículo</h3>
      <div class="ap-chip"><span style="color:#dc3545;font-weight:700">*</span> Obligatorios: <b>cve_almac</b>, <b>cve_articulo</b>, <b>des_articulo</b></div>
    </div>

    <input type="hidden" id="id">

    <div class="ap-form" style="margin-top:10px" id="frm">
      <!-- ===================== Identificación ===================== -->
      <div class="ap-field">
        <div class="ap-label">Almacén (cve_almac) *</div>
        <div class="ap-input"><i class="fa fa-warehouse"></i>
          <input id="cve_almac" oninput="this.value=this.value.replace(/[^0-9]/g,'')" placeholder="ID almacén">
        </div>
        <div class="ap-error" id="err_alm">cve_almac obligatorio.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">SKU (cve_articulo) *</div>
        <div class="ap-input"><i class="fa fa-qrcode"></i><input id="cve_articulo" placeholder="SKU-0001"></div>
        <div class="ap-error" id="err_sku">cve_articulo obligatorio.</div>
      </div>

      <div class="ap-field">
        <div class="ap-label">Descripción (des_articulo) *</div>
        <div class="ap-input"><i class="fa fa-align-left"></i><input id="des_articulo" placeholder="Descripción comercial"></div>
        <div class="ap-error" id="err_des">des_articulo obligatorio.</div>
      </div>

      <div class="ap-field" style="grid-column:1/-1">
        <div class="ap-label">Descripción detallada (des_detallada)</div>
        <div class="ap-input"><i class="fa fa-file-alt"></i><textarea id="des_detallada" rows="2" placeholder="Ficha técnica / atributos"></textarea></div>
      </div>

      <!-- ===================== Unidades / Multiplicidad ===================== -->
      <div class="ap-field"><div class="ap-label">cve_umed</div><div class="ap-input"><i class="fa fa-ruler"></i><input id="cve_umed" oninput="this.value=this.value.replace(/[^0-9]/g,'')"></div></div>
      <div class="ap-field"><div class="ap-label">comp_cveumed</div><div class="ap-input"><i class="fa fa-ruler-combined"></i><input id="comp_cveumed" oninput="this.value=this.value.replace(/[^0-9]/g,'')"></div></div>
      <div class="ap-field"><div class="ap-label">empq_cveumed</div><div class="ap-input"><i class="fa fa-box"></i><input id="empq_cveumed" oninput="this.value=this.value.replace(/[^0-9]/g,'')"></div></div>

      <div class="ap-field"><div class="ap-label">num_multiplo</div><div class="ap-input"><i class="fa fa-hashtag"></i><input id="num_multiplo" placeholder="0.00"></div></div>
      <div class="ap-field"><div class="ap-label">num_multiploch</div><div class="ap-input"><i class="fa fa-hashtag"></i><input id="num_multiploch" oninput="this.value=this.value.replace(/[^0-9]/g,'')"></div></div>
      <div class="ap-field"><div class="ap-label">num_volxpal</div><div class="ap-input"><i class="fa fa-cube"></i><input id="num_volxpal" placeholder="0.00"></div></div>

      <!-- ===================== Costos / precios ===================== -->
      <div class="ap-field"><div class="ap-label">imp_costo</div><div class="ap-input"><i class="fa fa-dollar-sign"></i><input id="imp_costo" placeholder="0.0000"></div></div>
      <div class="ap-field"><div class="ap-label">costo</div><div class="ap-input"><i class="fa fa-dollar-sign"></i><input id="costo" placeholder="0.00"></div></div>
      <div class="ap-field"><div class="ap-label">costoPromedio</div><div class="ap-input"><i class="fa fa-dollar-sign"></i><input id="costoPromedio" placeholder="0.00"></div></div>

      <div class="ap-field"><div class="ap-label">PrecioVenta</div><div class="ap-input"><i class="fa fa-tags"></i><input id="PrecioVenta" placeholder="0.00"></div></div>
      <div class="ap-field"><div class="ap-label">IEPS</div><div class="ap-input"><i class="fa fa-percent"></i><input id="IEPS" placeholder="0.0000"></div></div>
      <div class="ap-field"><div class="ap-label">cve_moneda</div><div class="ap-input"><i class="fa fa-coins"></i><input id="cve_moneda" oninput="this.value=this.value.replace(/[^0-9]/g,'')"></div></div>

      <!-- ===================== Dimensiones / peso ===================== -->
      <div class="ap-field"><div class="ap-label">peso</div><div class="ap-input"><i class="fa fa-weight"></i><input id="peso" placeholder="0.0000"></div></div>
      <div class="ap-field"><div class="ap-label">alto</div><div class="ap-input"><i class="fa fa-ruler-vertical"></i><input id="alto" placeholder="0.00"></div></div>
      <div class="ap-field"><div class="ap-label">ancho</div><div class="ap-input"><i class="fa fa-ruler-horizontal"></i><input id="ancho" placeholder="0.00"></div></div>

      <div class="ap-field"><div class="ap-label">fondo</div><div class="ap-input"><i class="fa fa-ruler"></i><input id="fondo" placeholder="0.00"></div></div>
      <div class="ap-field"><div class="ap-label">control_peso</div><div class="ap-input"><i class="fa fa-balance-scale"></i>
        <select id="control_peso"><option value="">(null)</option><option value="S">S</option><option value="N">N</option></select>
      </div></div>
      <div class="ap-field"><div class="ap-label">control_volumen</div><div class="ap-input"><i class="fa fa-cube"></i>
        <select id="control_volumen"><option value="">(null)</option><option value="S">S</option><option value="N">N</option></select>
      </div></div>

      <!-- ===================== Controles (lotes/series/caduca/compuesto/garantía) ===================== -->
      <div class="ap-field"><div class="ap-label">control_lotes</div><div class="ap-input"><i class="fa fa-layer-group"></i>
        <select id="control_lotes"><option value="">(null)</option><option value="S">S</option><option value="N">N</option></select>
      </div></div>

      <div class="ap-field"><div class="ap-label">control_numero_series</div><div class="ap-input"><i class="fa fa-fingerprint"></i>
        <select id="control_numero_series"><option value="">(null)</option><option value="S">S</option><option value="N">N</option></select>
      </div></div>

      <div class="ap-field"><div class="ap-label">Caduca</div><div class="ap-input"><i class="fa fa-hourglass-end"></i>
        <select id="Caduca"><option value="">(null)</option><option value="S">S</option><option value="N">N</option></select>
      </div></div>

      <div class="ap-field"><div class="ap-label">Compuesto</div><div class="ap-input"><i class="fa fa-sitemap"></i>
        <select id="Compuesto"><option value="">(null)</option><option value="S">S</option><option value="N">N</option></select>
      </div></div>

      <div class="ap-field"><div class="ap-label">control_garantia</div><div class="ap-input"><i class="fa fa-shield-alt"></i>
        <select id="control_garantia"><option value="">(null)</option><option value="S">S</option><option value="N">N</option></select>
      </div></div>

      <div class="ap-field"><div class="ap-label">tipo_garantia</div><div class="ap-input"><i class="fa fa-calendar-alt"></i>
        <select id="tipo_garantia"><option value="MESES">MESES</option><option value="ANIOS">ANIOS</option><option value="HORAS_USO">HORAS_USO</option></select>
      </div></div>

      <div class="ap-field"><div class="ap-label">valor_garantia</div><div class="ap-input"><i class="fa fa-stopwatch"></i><input id="valor_garantia" oninput="this.value=this.value.replace(/[^0-9]/g,'')"></div></div>

      <!-- ===================== Clasificación / negocio ===================== -->
      <div class="ap-field"><div class="ap-label">grupo</div><div class="ap-input"><i class="fa fa-folder"></i><input id="grupo"></div></div>
      <div class="ap-field"><div class="ap-label">clasificacion</div><div class="ap-input"><i class="fa fa-tags"></i><input id="clasificacion"></div></div>
      <div class="ap-field"><div class="ap-label">tipo</div><div class="ap-input"><i class="fa fa-tag"></i><input id="tipo"></div></div>

      <div class="ap-field"><div class="ap-label">tipo_producto</div><div class="ap-input"><i class="fa fa-cube"></i><input id="tipo_producto"></div></div>
      <div class="ap-field"><div class="ap-label">control_abc</div><div class="ap-input"><i class="fa fa-chart-line"></i>
        <select id="control_abc"><option value="">(null)</option><option value="A">A</option><option value="B">B</option><option value="C">C</option></select>
      </div></div>
      <div class="ap-field"><div class="ap-label">des_tipo</div><div class="ap-input"><i class="fa fa-info-circle"></i><input id="des_tipo"></div></div>

      <!-- ===================== Proveedor / alternos / SAP / barras ===================== -->
      <div class="ap-field"><div class="ap-label">ID_Proveedor</div><div class="ap-input"><i class="fa fa-truck"></i><input id="ID_Proveedor" oninput="this.value=this.value.replace(/[^0-9]/g,'')"></div></div>
      <div class="ap-field"><div class="ap-label">cve_codprov</div><div class="ap-input"><i class="fa fa-id-badge"></i><input id="cve_codprov"></div></div>
      <div class="ap-field"><div class="ap-label">remplazo</div><div class="ap-input"><i class="fa fa-exchange-alt"></i><input id="remplazo"></div></div>

      <div class="ap-field"><div class="ap-label">Cve_SAP</div><div class="ap-input"><i class="fa fa-link"></i><input id="Cve_SAP"></div></div>
      <div class="ap-field"><div class="ap-label">cve_alt</div><div class="ap-input"><i class="fa fa-code-branch"></i><input id="cve_alt"></div></div>
      <div class="ap-field"><div class="ap-label">barras2</div><div class="ap-input"><i class="fa fa-barcode"></i><input id="barras2"></div></div>

      <div class="ap-field"><div class="ap-label">barras3</div><div class="ap-input"><i class="fa fa-barcode"></i><input id="barras3"></div></div>
      <div class="ap-field"><div class="ap-label">Max_Cajas</div><div class="ap-input"><i class="fa fa-boxes"></i><input id="Max_Cajas" oninput="this.value=this.value.replace(/[^0-9]/g,'')"></div></div>
      <div class="ap-field"><div class="ap-label">cajas_palet</div><div class="ap-input"><i class="fa fa-pallet"></i><input id="cajas_palet" oninput="this.value=this.value.replace(/[^0-9]/g,'')"></div></div>

      <!-- ===================== Flags riesgos / refrigeración / envases ===================== -->
      <div class="ap-field"><div class="ap-label">req_refrigeracion</div><div class="ap-input"><i class="fa fa-snowflake"></i>
        <select id="req_refrigeracion"><option value="">(null)</option><option value="S">S</option><option value="N">N</option></select>
      </div></div>
      <div class="ap-field"><div class="ap-label">mat_peligroso</div><div class="ap-input"><i class="fa fa-exclamation-triangle"></i>
        <select id="mat_peligroso"><option value="">(null)</option><option value="S">S</option><option value="N">N</option></select>
      </div></div>
      <div class="ap-field"><div class="ap-label">mav_obsoleto</div><div class="ap-input"><i class="fa fa-ban"></i>
        <select id="mav_obsoleto"><option value="">(null)</option><option value="S">S</option><option value="N">N</option></select>
      </div></div>

      <div class="ap-field"><div class="ap-label">Ban_Envase</div><div class="ap-input"><i class="fa fa-wine-bottle"></i>
        <select id="Ban_Envase"><option value="">(null)</option><option value="S">S</option><option value="N">N</option></select>
      </div></div>
      <div class="ap-field"><div class="ap-label">Usa_Envase</div><div class="ap-input"><i class="fa fa-box"></i>
        <select id="Usa_Envase"><option value="">(null)</option><option value="S">S</option><option value="N">N</option></select>
      </div></div>
      <div class="ap-field"><div class="ap-label">Tipo_Envase</div><div class="ap-input"><i class="fa fa-cog"></i>
        <select id="Tipo_Envase"><option value="">(null)</option><option value="S">S</option><option value="N">N</option></select>
      </div></div>

      <!-- ===================== Campos MAV / ubicación / línea ===================== -->
      <div class="ap-field"><div class="ap-label">mav_almacenable</div><div class="ap-input"><i class="fa fa-warehouse"></i>
        <select id="mav_almacenable"><option value="">(null)</option><option value="S">S</option><option value="N">N</option></select>
      </div></div>
      <div class="ap-field"><div class="ap-label">mav_cveubica</div><div class="ap-input"><i class="fa fa-map-marker-alt"></i><input id="mav_cveubica"></div></div>
      <div class="ap-field"><div class="ap-label">mav_delinea</div><div class="ap-input"><i class="fa fa-stream"></i>
        <select id="mav_delinea"><option value="">(null)</option><option value="S">S</option><option value="N">N</option></select>
      </div></div>

      <div class="ap-field"><div class="ap-label">mav_pctiva</div><div class="ap-input"><i class="fa fa-percent"></i><input id="mav_pctiva" placeholder="0.0000"></div></div>
      <div class="ap-field"><div class="ap-label">mav_cveubica (alt)</div><div class="ap-input"><i class="fa fa-location-arrow"></i><input id="mav_cveubica"></div></div>
      <div class="ap-field"><div class="ap-label">mav_delinea (alt)</div><div class="ap-input"><i class="fa fa-stream"></i>
        <select id="mav_delinea"><option value="">(null)</option><option value="S">S</option><option value="N">N</option></select>
      </div></div>

      <!-- ===================== Otros ===================== -->
      <div class="ap-field"><div class="ap-label">cve_ssgpo</div><div class="ap-input"><i class="fa fa-layer-group"></i><input id="cve_ssgpo" oninput="this.value=this.value.replace(/[^0-9]/g,'')"></div></div>
      <div class="ap-field"><div class="ap-label">cve_tipcaja</div><div class="ap-input"><i class="fa fa-box"></i><input id="cve_tipcaja" oninput="this.value=this.value.replace(/[^0-9]/g,'')"></div></div>
      <div class="ap-field"><div class="ap-label">tipo_caja</div><div class="ap-input"><i class="fa fa-box-open"></i><input id="tipo_caja" oninput="this.value=this.value.replace(/[^0-9]/g,'')"></div></div>

      <div class="ap-field"><div class="ap-label">ban_condic</div><div class="ap-input"><i class="fa fa-check-circle"></i><input id="ban_condic" oninput="this.value=this.value.replace(/[^0-9]/g,'')"></div></div>
      <div class="ap-field"><div class="ap-label">cve_ssgpo</div><div class="ap-input"><i class="fa fa-layer-group"></i><input id="cve_ssgpo" oninput="this.value=this.value.replace(/[^0-9]/g,'')"></div></div>
      <div class="ap-field"><div class="ap-label">des_observ</div><div class="ap-input"><i class="fa fa-comment"></i><input id="des_observ"></div></div>

      <!-- ===================== eCommerce ===================== -->
      <div class="ap-field"><div class="ap-label">ecommerce_activo</div><div class="ap-input"><i class="fa fa-shopping-cart"></i>
        <select id="ecommerce_activo"><option value="0">0</option><option value="1">1</option></select>
      </div></div>
      <div class="ap-field"><div class="ap-label">ecommerce_categoria</div><div class="ap-input"><i class="fa fa-folder-open"></i><input id="ecommerce_categoria"></div></div>
      <div class="ap-field"><div class="ap-label">ecommerce_subcategoria</div><div class="ap-input"><i class="fa fa-folder"></i><input id="ecommerce_subcategoria"></div></div>

      <div class="ap-field" style="grid-column:1/-1">
        <div class="ap-label">ecommerce_img_principal</div>
        <div class="ap-input"><i class="fa fa-image"></i><input id="ecommerce_img_principal" placeholder="URL o path"></div>
      </div>
      <div class="ap-field" style="grid-column:1/-1">
        <div class="ap-label">ecommerce_img_galeria</div>
        <div class="ap-input"><i class="fa fa-images"></i><textarea id="ecommerce_img_galeria" rows="2" placeholder="JSON/texto con rutas"></textarea></div>
      </div>
      <div class="ap-field" style="grid-column:1/-1">
        <div class="ap-label">ecommerce_tags</div>
        <div class="ap-input"><i class="fa fa-hashtag"></i><input id="ecommerce_tags" placeholder="tag1,tag2,tag3"></div>
      </div>

      <div class="ap-field"><div class="ap-label">ecommerce_destacado</div><div class="ap-input"><i class="fa fa-star"></i>
        <select id="ecommerce_destacado"><option value="0">0</option><option value="1">1</option></select>
      </div></div>

      <!-- ===================== Estado ===================== -->
      <div class="ap-field"><div class="ap-label">Activo</div><div class="ap-input"><i class="fa fa-toggle-on"></i>
        <select id="Activo"><option value="1">Activo</option><option value="0">Inactivo</option></select>
      </div></div>

      <div class="ap-field"><div class="ap-label">fec_altaart</div><div class="ap-input"><i class="fa fa-clock"></i><input id="fec_altaart" placeholder="YYYY-MM-DD HH:MM:SS"></div></div>
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
    <h3><i class="fa fa-upload"></i> Importar artículos</h3>
    <div class="ap-chip">Layout FULL con UPSERT por <b>cve_almac + cve_articulo</b>. Previsualiza antes de importar.</div>

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
const API = '../api/articulos.php';

let filtroAlm=0;
let verInactivos=false;
let qLast='';
let page=1;
let perPage=25;
let pages=1;
let total=0;

const sp = (on)=>{ spn.style.display = on ? 'flex' : 'none'; };

function loadCards(){
  fetch(API+'?action=kpi').then(r=>r.json()).then(rows=>{
    let h='';
    rows.forEach(x=>{
      h+=`
      <div class="ap-card" onclick="setAlm(${x.cve_almac})">
        <div class="h">
          <b><i class="fa fa-warehouse"></i> Almacén ${x.cve_almac}</b>
          <span class="ap-chip ok">${x.activas} Act</span>
        </div>
        <div class="k">
          <span class="ap-chip">Total: ${x.total}</span>
          <span class="ap-chip warn">Inac: ${x.inactivas}</span>
          <span class="ap-chip">Lotes: ${x.con_lotes}</span>
          <span class="ap-chip">Series: ${x.con_series}</span>
          <span class="ap-chip">Gtia: ${x.con_garantia}</span>
          <span class="ap-chip">eCom: ${x.ecommerce_activos}</span>
          <span class="ap-chip">Dest: ${x.ecommerce_destacados}</span>
        </div>
      </div>`;
    });
    cards.innerHTML = h || `<div class="ap-chip warn">Sin datos</div>`;
  });
}

function setAlm(a){
  filtroAlm = a||0;
  filtroLabel.innerHTML = `<i class="fa fa-filter"></i> Almacén: <b>${filtroAlm?filtroAlm:'Todos'}</b> ${filtroAlm?'<span class="ap-chip" style="cursor:pointer" onclick="setAlm(0)">Quitar</span>':''}`;
  page=1;
  cargar();
}

function fmtMoney(v){
  if(v===null||v===undefined||String(v).trim()==='') return '';
  const n = Number(v);
  if(isNaN(n)) return v;
  return n.toLocaleString('es-MX',{minimumFractionDigits:2,maximumFractionDigits:2});
}

function renderPager(){
  lblPage.textContent = page;
  lblPages.textContent = pages;
  lblTotal.textContent = total;

  const start = total===0 ? 0 : ((page-1)*perPage + 1);
  const end = Math.min(total, page*perPage);
  lblRange.textContent = `${start}-${end}`;
  goPage.value = '';
}

function cargar(){
  sp(true);
  const url = API+'?action=list'
    + '&cve_almac='+encodeURIComponent(filtroAlm)
    + '&inactivos='+(verInactivos?1:0)
    + '&q='+encodeURIComponent(qLast||'')
    + '&page='+encodeURIComponent(page)
    + '&per_page='+encodeURIComponent(perPage);

  fetch(url).then(r=>r.json()).then(resp=>{
    const rows = resp.data || [];
    total = Number(resp.total||0);
    pages = Number(resp.pages||1);

    let h='';
    rows.forEach(c=>{
      const ecom = Number(c.ecommerce_activo||0)===1 ? `<span class="ap-badge ok">SI</span>` : `<span class="ap-badge no">NO</span>`;
      const dest = Number(c.ecommerce_destacado||0)===1 ? `<span class="ap-badge ok">SI</span>` : `<span class="ap-badge no">NO</span>`;
      const act  = Number(c.Activo||1)===1 ? `<span class="ap-badge ok">1</span>` : `<span class="ap-badge no">0</span>`;

      h+=`
      <tr>
        <td class="ap-actions">
          ${verInactivos
            ? `<i class="fa fa-undo" title="Recuperar" onclick="recuperar(${c.id})"></i>`
            : `<i class="fa fa-edit" title="Editar" onclick="editar(${c.id})"></i>
               <i class="fa fa-trash" title="Inactivar" onclick="eliminar(${c.id})"></i>`}
        </td>
        <td>${c.id||''}</td>
        <td>${c.cve_almac||''}</td>
        <td><b>${c.cve_articulo||''}</b></td>
        <td>${c.des_articulo||''}</td>
        <td>${c.grupo||''}</td>
        <td>${c.clasificacion||''}</td>
        <td>${c.tipo||''}</td>
        <td>${c.tipo_producto||''}</td>
        <td>${fmtMoney(c.costo)}</td>
        <td>${fmtMoney(c.costoPromedio)}</td>
        <td>${fmtMoney(c.PrecioVenta)}</td>
        <td>${c.control_lotes||''}</td>
        <td>${c.control_numero_series||''}</td>
        <td>${c.control_garantia||''}</td>
        <td>${c.Cve_SAP||''}</td>
        <td>${ecom}</td>
        <td>${dest}</td>
        <td>${act}</td>
      </tr>`;
    });

    tb.innerHTML = h || `<tr><td colspan="19" style="text-align:center;color:#6c757d;padding:20px">Sin resultados</td></tr>`;
    renderPager();
  }).catch(err=>{
    alert('Error al cargar: '+err);
  }).finally(()=>sp(false));
}

function buscar(){ qLast = q.value.trim(); page=1; cargar(); }
function limpiar(){ q.value=''; qLast=''; page=1; cargar(); }
function toggleInactivos(){ verInactivos=!verInactivos; page=1; cargar(); }

function changePerPage(){
  perPage = Number(document.getElementById('perPage').value||25);
  page=1;
  cargar();
}

function firstPage(){ if(page>1){ page=1; cargar(); } }
function prevPage(){ if(page>1){ page--; cargar(); } }
function nextPage(){ if(page<pages){ page++; cargar(); } }
function lastPage(){ if(page<pages){ page=pages; cargar(); } }

function goToPage(){
  const p = Number((goPage.value||'').trim()||0);
  if(!p) return;
  if(p<1) return;
  if(p>pages) return;
  page=p;
  cargar();
}

function hideErrors(){
  err_alm.style.display='none';
  err_sku.style.display='none';
  err_des.style.display='none';
}

function validar(){
  hideErrors();
  let ok=true;
  if(!cve_almac.value.trim()){ err_alm.style.display='block'; ok=false; }
  if(!cve_articulo.value.trim()){ err_sku.style.display='block'; ok=false; }
  if(!des_articulo.value.trim()){ err_des.style.display='block'; ok=false; }
  return ok;
}

function setField(k,v){
  const el = document.getElementById(k);
  if(!el) return;
  el.value = (v===null||v===undefined) ? '' : v;
}

function clearModal(){
  document.querySelectorAll('#frm input, #frm textarea').forEach(i=>i.value='');
  document.querySelectorAll('#frm select').forEach(s=>{
    if(s.id==='Activo') s.value='1';
    else if(s.id==='ecommerce_activo') s.value='0';
    else if(s.id==='ecommerce_destacado') s.value='0';
    else if(s.id==='tipo_garantia') s.value='MESES';
    else s.value='';
  });
  id.value='';
  hideErrors();
}

function nuevo(){
  clearModal();
  mdl.style.display='block';
}

function editar(idv){
  sp(true);
  fetch(API+'?action=get&id='+encodeURIComponent(idv))
    .then(r=>r.json())
    .then(c=>{
      clearModal();
      for(let k in c) setField(k,c[k]);
      mdl.style.display='block';
    })
    .finally(()=>sp(false));
}

function guardar(){
  if(!validar()) return;

  const fd = new FormData();
  fd.append('action', id.value ? 'update' : 'create');

  // Enviar TODOS los campos presentes en el formulario (y por ids)
  document.querySelectorAll('#frm input, #frm textarea, #frm select').forEach(el=>{
    fd.append(el.id, el.value);
  });

  // id hidden
  fd.append('id', id.value);

  sp(true);
  fetch(API,{method:'POST',body:fd})
    .then(r=>r.json())
    .then(resp=>{
      if(resp && resp.error){
        alert(resp.error + (resp.detalles ? "\n- " + resp.detalles.join("\n- ") : ""));
        return;
      }
      cerrarModal('mdl');
      loadCards();
      cargar();
    })
    .finally(()=>sp(false));
}

function eliminar(idv){
  if(!confirm('¿Inactivar artículo?')) return;
  const fd=new FormData(); fd.append('action','delete'); fd.append('id',idv);
  sp(true);
  fetch(API,{method:'POST',body:fd}).then(()=>{ loadCards(); cargar(); }).finally(()=>sp(false));
}
function recuperar(idv){
  const fd=new FormData(); fd.append('action','restore'); fd.append('id',idv);
  sp(true);
  fetch(API,{method:'POST',body:fd}).then(()=>{ loadCards(); cargar(); }).finally(()=>sp(false));
}

function exportarDatos(){
  const url = API+'?action=export_csv&tipo=datos'
    + '&cve_almac='+encodeURIComponent(filtroAlm)
    + '&inactivos='+(verInactivos?1:0)
    + '&q='+encodeURIComponent(qLast||'');
  window.open(url,'_blank');
}
function descargarLayout(){ window.open(API+'?action=export_csv&tipo=layout','_blank'); }

function abrirImport(){
  fileCsv.value='';
  csvPreviewWrap.style.display='none';
  importMsg.style.display='none';
  mdlImport.style.display='block';
}
function previsualizarCsv(){
  const f=fileCsv.files[0];
  if(!f){ alert('Selecciona un CSV'); return; }
  const r=new FileReader();
  r.onload=e=>{
    const rows=e.target.result.split('\n').filter(x=>x.trim()!=='');
    csvHead.innerHTML='<tr>'+rows[0].split(',').map(h=>`<th>${h}</th>`).join('')+'</tr>';
    csvBody.innerHTML=rows.slice(1,6).map(rr=>'<tr>'+rr.split(',').map(c=>`<td>${c}</td>`).join('')+'</tr>').join('');
    csvPreviewWrap.style.display='block';
    importMsg.style.display='none';
  };
  r.readAsText(f);
}
function importarCsv(){
  const fd=new FormData();
  fd.append('action','import_csv');
  fd.append('file',fileCsv.files[0]);

  sp(true);
  fetch(API,{method:'POST',body:fd})
    .then(r=>r.json())
    .then(resp=>{
      importMsg.style.display='block';
      if(resp.error){
        importMsg.className='ap-chip warn';
        importMsg.innerHTML = `<b>Error:</b> ${resp.error}`;
        return;
      }
      importMsg.className='ap-chip ok';
      importMsg.innerHTML = `<b>Importación:</b> OK ${resp.rows_ok||0} | Err ${resp.rows_err||0}`;
      cerrarModal('mdlImport');
      loadCards();
      page=1;
      cargar();
    })
    .finally(()=>sp(false));
}

function cerrarModal(id){ document.getElementById(id).style.display='none'; }

document.addEventListener('DOMContentLoaded', ()=>{
  loadCards();
  cargar();
});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
