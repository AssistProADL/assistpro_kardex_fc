<?php
// /public/mobile/consultas/producto.php
// Consulta móvil: Producto (coincidencia) -> lista ubicaciones donde existe

?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Consulta Producto • AssistPro ER</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

  <!-- CSS móvil -->
  <link rel="stylesheet" href="../css/rf.css?v=1.0">

  <!-- Font Awesome (si ya lo tienes globalmente puedes quitar esto) -->
  <link rel="stylesheet" href="../../bi/assets/vendor/fontawesome-free/css/all.min.css">
</head>

<body class="rf-body">
  <div class="rf-shell rf-shell--narrow">

    <!-- Header -->
    <div class="rf-top">
      <div class="rf-brand">
        <img class="rf-logo rf-logo--lg" src="../../assets/logo/assistpro-er.svg" alt="AssistPro ER">
        <div>
          <div class="rf-title">Producto</div>
          <div class="rf-subtitle"><span id="uBadge">Usuario: —</span></div>
        </div>
      </div>

      <div class="rf-badges">
        <span class="rf-pill" id="almBadge"><i class="fa-solid fa-warehouse"></i> ALM: —</span>
      </div>
    </div>

    <!-- Card -->
    <div class="rf-card">
      <div class="rf-card-body">

        <label class="rf-label" for="q">
          <i class="fa-solid fa-magnifying-glass"></i> Buscar (código o descripción)
        </label>

        <input
          id="q"
          class="rf-input"
          type="text"
          placeholder="Ej. 10001 o M7W9"
          autocomplete="off"
          inputmode="search"
        >

        <div class="rf-help">Coincidencias salen abajo. Enter toma la primera.</div>

        <div id="msg" class="rf-alert rf-alert--soft" style="display:none;"></div>

        <!-- Resultados: sugerencias -->
        <div id="suggest" class="rf-list" style="margin-top:10px;"></div>

        <!-- Existencias del producto seleccionado -->
        <div id="detail" style="margin-top:12px;"></div>

        <div class="rf-actions">
          <a class="rf-btn rf-btn--dark" href="../menu.php"><i class="fa-solid fa-arrow-left"></i> Volver</a>
          <a class="rf-btn" href="../menu.php"><i class="fa-solid fa-bars"></i> Menú</a>
        </div>

        <div class="rf-footer-note">Powered by <b>Adventech Logística</b></div>
      </div>
    </div>
  </div>

<script>
(function(){
  // ========= Config =========
  // Wrapper móvil (ojo: producto.php está en /consultas/ => ../api/)
  const WRAPPER_FILTROS = "../api/filtros_assistpro.php";

  // API Stock (ajusta si tu ruta real difiere)
  // En tu proyecto los APIs están en /public/api/stock/...
  const API_EXISTENCIAS = "../../api/stock/existencias_ubicacion_total.php";

  // ========= Sesión ligera (sin PHP sessions) =========
  const mobileUser = localStorage.getItem("mobile_user") || "";
  const mobileAlm  = localStorage.getItem("mobile_almacen") || ""; // puede ser ID o clave según tu login

  const uBadge = document.getElementById("uBadge");
  const almBadge = document.getElementById("almBadge");
  const msg = document.getElementById("msg");
  const q = document.getElementById("q");
  const suggest = document.getElementById("suggest");
  const detail = document.getElementById("detail");

  uBadge.textContent = "Usuario: " + (mobileUser || "—");
  almBadge.innerHTML = '<i class="fa-solid fa-warehouse"></i> ALM: ' + (mobileAlm || "—");

  // Si no hay user/almacen, redirigir a login
  if(!mobileUser || !mobileAlm){
    window.location.href = "../index.html";
    return;
  }

  // ========= Helpers =========
  function showMsg(text, type){
    msg.style.display = "block";
    msg.className = "rf-alert " + (type ? ("rf-alert--" + type) : "rf-alert--soft");
    msg.textContent = text;
  }
  function hideMsg(){ msg.style.display = "none"; }

  function esc(s){
    return (s||"").toString()
      .replaceAll("&","&amp;")
      .replaceAll("<","&lt;")
      .replaceAll(">","&gt;");
  }

  // ========= (Opcional) Resolver etiqueta del almacén via wrapper =========
  // IMPORTANTE: si falla, NO bloqueamos la consulta
  fetch(WRAPPER_FILTROS, {cache:"no-store"})
    .then(r => r.json())
    .then(data => {
      // Intentos de lectura tolerante según tu estructura
      const almacenes = data?.almacenes || data?.data?.almacenes || data?.data || [];
      if(Array.isArray(almacenes) && almacenes.length){
        // Si mobileAlm es ID, buscar por id; si es clave, buscar por clave
        const found = almacenes.find(a =>
          String(a.id || a.id_almacen || a.id_almacenp || "") === String(mobileAlm)
          || String(a.clave || a.cve_almac || a.codigo || "") === String(mobileAlm)
        );
        if(found){
          const clave = found.clave || found.cve_almac || found.codigo || mobileAlm;
          almBadge.innerHTML = '<i class="fa-solid fa-warehouse"></i> ALM: ' + esc(clave);
          // Guardamos clave por si mobile_almacen venía como ID
          localStorage.setItem("mobile_almacen_clave", clave);
          localStorage.setItem("mobile_almacen_id", (found.id || found.id_almacen || found.id_almacenp || mobileAlm));
        }
      }
    })
    .catch(() => {
      // No hacemos ruido; solo mantenemos ALM del localStorage
    });

  // ========= Búsqueda =========
  let t = null;
  let lastSuggestions = [];

  function clearUI(){
    suggest.innerHTML = "";
    detail.innerHTML = "";
    lastSuggestions = [];
  }

  function buildSuggestionItem(item){
    // tolerante: item puede venir con diferentes nombres
    const code = item.cve_articulo || item.articulo || item.codigo || item.sku || "";
    const desc = item.descripcion || item.descrip || item.nombre || "";
    return `
      <button class="rf-row" type="button" data-code="${esc(code)}">
        <div class="rf-row-main">
          <div class="rf-row-title">${esc(code)}</div>
          <div class="rf-row-sub">${esc(desc)}</div>
        </div>
        <div class="rf-row-right"><i class="fa-solid fa-chevron-right"></i></div>
      </button>
    `;
  }

  // Esta función consulta existencias_ubicacion_total y lo usamos como:
  // - sugerencias: si el API ya soporta coincidencias por articulo
  // - detalle: mismo API, pero con articulo exacto (selección)
  function fetchExistencias(art){
    const almId = localStorage.getItem("mobile_almacen_id") || mobileAlm; // id preferente
    const params = new URLSearchParams();
    params.set("articulo", art);
    // En tu vista web usas cve_almac (pero a veces es clave, a veces id).
    // Mandamos ambos si tu API los tolera (si no, ignora el extra).
    params.set("cve_almac", almId);
    // Forzamos amplio: sin “solo disponible” para no ocultar
    params.set("nivel", "Todos");
    params.set("solo_disponible", "0");
    // limit razonable para móvil
    params.set("limit", "200");

    return fetch(API_EXISTENCIAS + "?" + params.toString(), {cache:"no-store"})
      .then(r => r.json());
  }

  function renderDetail(rows, articulo){
    if(!rows || !rows.length){
      detail.innerHTML = `<div class="rf-alert rf-alert--warn">Sin existencias para <b>${esc(articulo)}</b>.</div>`;
      return;
    }

    // Agrupar por BL/LP para UX (cards compactas)
    const html = [];
    html.push(`<div class="rf-hint">Ubicaciones encontradas: <b>${rows.length}</b></div>`);

    rows.forEach(r => {
      const nivel = r.nivel || r.Nivel || "";
      const bl = r.codigocsd || r.CodigoCSD || r.bl || r.BL || "";
      const lp = r.lp_code || r.LP || r.lp || r.LPCode || "";
      const lote = r.lote || r.Lote || "";
      const total = r.total || r.Total || r.cantidad || r.Cantidad || "0";

      html.push(`
        <div class="rf-mini-card">
          <div class="rf-mini-top">
            <span class="rf-chip">${esc(nivel || "—")}</span>
            ${lp ? `<span class="rf-chip rf-chip--soft"><i class="fa-solid fa-tag"></i> LP: ${esc(lp)}</span>` : ``}
          </div>

          <div class="rf-mini-grid">
            <div><span class="rf-k">BL</span><span class="rf-v">${esc(bl || "—")}</span></div>
            <div><span class="rf-k">Lote</span><span class="rf-v">${esc(lote || "—")}</span></div>
            <div><span class="rf-k">Total</span><span class="rf-v"><b>${esc(total)}</b></span></div>
          </div>
        </div>
      `);
    });

    detail.innerHTML = html.join("");
  }

  function showSuggestionsFromRows(rows){
    // Si el API devuelve múltiples artículos por coincidencia, creamos sugerencias.
    // Si devuelve solo el artículo exacto repetido, generamos una sugerencia única.
    const map = new Map();
    (rows || []).forEach(r => {
      const art = r.cve_articulo || r.articulo || r.Articulo || "";
      const desc = r.descripcion || r.Descripcion || "";
      if(art) map.set(art, {cve_articulo: art, descripcion: desc});
    });

    lastSuggestions = Array.from(map.values()).slice(0, 8);
    if(!lastSuggestions.length){
      suggest.innerHTML = `<div class="rf-alert rf-alert--warn">Sin resultados</div>`;
      return;
    }
    suggest.innerHTML = lastSuggestions.map(buildSuggestionItem).join("");
  }

  async function fetchArticulos(term){
    // articulos_api.php usa action=list (mismo patrón que tu UI de recepción)
    const url = API_ARTICULOS + "?action=list&limit=25&page=1&inactivos=0&q=" + encodeURIComponent(term);
    const r = await fetch(url, {cache:"no-store"});
    if(!r.ok){
      throw new Error("HTTP " + r.status + " en articulos_api.php");
    }
    const data = await r.json();
    // Respuesta esperada: {rows:[...], total, pages, page}
    return data.rows || data.data || data.resultados || [];
  }

  function runSearch(){
    const term = q.value.trim();
    clearUI();
    hideMsg();

    if(term.length < 2){
      return;
    }

    // Strategy:
    // 1) Catálogo (coincidencias) desde articulos_api.php
    // 2) Selección => existencias_ubicacion_total.php (stock)
    fetchArticulos(term)
      .then(rows => {
        if(!Array.isArray(rows) || !rows.length){
          suggest.innerHTML = `<div class="rf-alert rf-alert--warn">Sin resultados</div>`;
          return;
        }
        showSuggestionsFromRows(rows);
      })
      .catch(err => {
        showMsg("Catálogo no disponible (" + err + ")", "warn");
      });
  }

  // Debounce input
  q.addEventListener("input", () => {
    if(t) clearTimeout(t);
    t = setTimeout(runSearch, 250);
  });

  // Enter toma primera sugerencia
  q.addEventListener("keydown", (ev) => {
    if(ev.key === "Enter"){
      ev.preventDefault();
      const first = lastSuggestions[0];
      if(first && first.cve_articulo){
        const art = first.cve_articulo;
        clearUI();
        fetchExistencias(art).then(d2 => {
          const rows2 = d2.rows || d2.data || d2.resultados || [];
          renderDetail(rows2, art);
        });
      } else {
        runSearch();
      }
    }
  });

  // Click en sugerencia
  suggest.addEventListener("click", (ev) => {
    const btn = ev.target.closest("button[data-code]");
    if(!btn) return;
    const art = btn.getAttribute("data-code");
    clearUI();
    q.value = art;

    fetchExistencias(art).then(d2 => {
      const rows2 = d2.rows || d2.data || d2.resultados || [];
      renderDetail(rows2, art);
    });
  });

})();
</script>

</body>
</html>
