<?php
// /public/mobile/consultas/producto.php
// Consulta móvil: Producto (coincidencia) -> lista ubicaciones donde existe

?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Consulta Producto • AssistPro ER</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

  <!-- CSS base (si existe) -->
  <link rel="stylesheet" href="../css/rf.css?v=1.0">

  <!-- Font Awesome v6 (mismo que menu.php) -->
  <link rel="stylesheet" href="../../bi/assets/fontawesome6/css/all.min.css">

  <!-- Estilo compacto (igual a menu.php) -->
  <style>
    :root{
      --ap-blue:#000f9f;
      --ap-blue-2:#0016c9;
      --ap-bg:#f4f6fb;
      --ap-text:#0e1630;
      --ap-muted:#6b778c;
      --ap-card:#ffffff;
      --ap-shadow: 0 10px 26px rgba(12, 23, 54, .10);
      --ap-radius: 18px;
      --ap-border: rgba(12,23,54,.12);
    }

    *{box-sizing:border-box}
    html,body{height:100%}
    body{
      margin:0;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      background: var(--ap-bg);
      color: var(--ap-text);
    }

    .wrap{
      min-height:100%;
      display:flex;
      align-items:flex-start;
      justify-content:center;
      padding: 16px 10px 18px;
    }

    .card{
      width:100%;
      max-width: 420px;
      background: var(--ap-card);
      border-radius: var(--ap-radius);
      box-shadow: var(--ap-shadow);
      padding: 14px 14px 12px;
    }

    .head{
      display:flex;
      align-items:center;
      gap: 12px;
      margin-bottom: 10px;
    }

    .logoBox{
      width: 58px;
      height: 58px;
      border-radius: 14px;
      background: #fff;
      display:flex;
      align-items:center;
      justify-content:center;
      box-shadow: 0 6px 16px rgba(12,23,54,.08);
      overflow:hidden;
      flex: 0 0 auto;
    }
    .logoBox img{width:50px;height:50px;object-fit:contain;display:block}

    .titleBox{flex:1}
    .brand{font-weight:900;letter-spacing:.2px;margin:0;font-size:16px;line-height:1.1}
    .sub{margin:2px 0 0;font-size:12px;color:var(--ap-muted)}

    .pill{
      margin-left:auto;
      font-size: 12px;
      font-weight: 800;
      padding: 7px 10px;
      border-radius: 999px;
      background: rgba(0,15,159,.08);
      color: var(--ap-blue);
      white-space: nowrap;
    }

    .field{margin: 10px 0}
    label{display:block;font-size:12px;font-weight:800;color:var(--ap-muted);margin:0 0 6px}

    .inputRow{
      display:flex;
      align-items:center;
      gap:10px;
      border: 1px solid var(--ap-border);
      background:#fff;
      border-radius: 14px;
      padding: 10px 12px;
    }
    .ico{color: var(--ap-blue);opacity:.95;min-width:18px;text-align:center}
    input{
      border:0;
      outline:0;
      width:100%;
      font-size:14px;
      background:transparent;
      color: var(--ap-text);
    }
    .help{font-size:12px;color:var(--ap-muted);margin-top:6px}

    .alert{
      display:none;
      margin: 10px 0;
      padding: 10px 12px;
      border-radius: 14px;
      font-size: 13px;
      background: rgba(0,15,159,.06);
      color: var(--ap-text);
      border: 1px solid rgba(0,15,159,.12);
    }

    .list{display:flex;flex-direction:column;gap:8px;margin-top:10px}
    .rowBtn{
      width:100%;
      border: 1px solid var(--ap-border);
      background:#fff;
      border-radius: 14px;
      padding: 10px 12px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      cursor:pointer;
      text-align:left;
    }
    .rowMain{min-width:0}
    .rowTitle{font-weight:900;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .rowSub{font-size:12px;color:var(--ap-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px}
    .rowRight{color: var(--ap-blue);opacity:.9}

    .miniCard{
      border: 1px solid var(--ap-border);
      background:#fff;
      border-radius: 14px;
      padding: 10px 12px;
      margin-top: 10px;
    }
    .miniTop{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:8px}
    .chip{
      font-size:11px;
      font-weight:900;
      padding: 6px 10px;
      border-radius: 999px;
      background: rgba(0,15,159,.08);
      color: var(--ap-blue);
      white-space:nowrap;
    }
    .chipSoft{background: rgba(11,18,32,.06); color:#0b1220}
    .miniGrid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
    .kv{display:flex;flex-direction:column;gap:2px}
    .k{font-size:11px;color:var(--ap-muted);font-weight:800}
    .v{font-size:13px;font-weight:900;color:var(--ap-text)}

    .actions{display:flex;gap:10px;margin-top:12px}
    .btn{
      flex:1;
      border:0;
      background: var(--ap-blue);
      color:#fff;
      border-radius: 14px;
      padding: 11px 10px;
      font-weight: 900;
      letter-spacing:.2px;
      display:flex;
      align-items:center;
      justify-content:center;
      gap:10px;
      text-decoration:none;
      box-shadow: 0 8px 18px rgba(0,15,159,.16);
      min-height: 44px;
      font-size: 13px;
      line-height:1;
    }
    .btnDark{background:#0b1220; box-shadow: 0 8px 18px rgba(11,18,32,.18)}

    .footer{
      display:flex;
      align-items:center;
      justify-content:center;
      gap:6px;
      font-size: 11px;
      color: var(--ap-muted);
      padding-top: 8px;
    }
    .footer b{color:#2b3b63}
  </style>
</head>

<body>
  <div class="wrap">
    <div class="card">

      <div class="head">
        <div class="logoBox">
          <img src="../../assets/logo/assistpro-er.svg" alt="AssistPro ER">
        </div>

        <div class="titleBox">
          <p class="brand">Consulta · Producto</p>
          <p class="sub" id="uBadge">Usuario: —</p>
        </div>

        <div class="pill" id="almBadge"><i class="fa-solid fa-warehouse"></i> ALM: —</div>
      </div>

      <div class="field">
        <label for="q"><i class="fa-solid fa-magnifying-glass"></i> Buscar (código o descripción)</label>
        <div class="inputRow">
          <span class="ico"><i class="fa-solid fa-barcode"></i></span>
          <input id="q" type="text" placeholder="Ej. 10001 o M7W9" autocomplete="off" inputmode="search">
        </div>
        <div class="help">Coincidencias salen abajo. Enter toma la primera.</div>
      </div>

      <div id="msg" class="alert rf-alert"></div>

      <!-- Resultados: sugerencias -->
      <div id="suggest" class="list"></div>

      <!-- Existencias del producto seleccionado -->
      <div id="detail"></div>

      <div class="actions">
        <a class="btn btnDark" href="../menu.php"><i class="fa-solid fa-arrow-left"></i> Volver</a>
        <a class="btn" href="../menu.php"><i class="fa-solid fa-bars"></i> Menú</a>
      </div>

      <div class="footer">
        <span>Powered by</span> <b>Adventech Logística</b>
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
      <button class="rowBtn" type="button" data-code="${esc(code)}">
        <div class="rowMain">
          <div class="rowTitle">${esc(code)}</div>
          <div class="rowSub">${esc(desc)}</div>
        </div>
        <div class="rowRight"><i class="fa-solid fa-chevron-right"></i></div>
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
      detail.innerHTML = `<div class="alert" style="display:block;background:rgba(11,18,32,.06);border-color:rgba(11,18,32,.14)">Sin existencias para <b>${esc(articulo)}</b>.</div>`;
      return;
    }

    // Agrupar por BL/LP para UX (cards compactas)
    const html = [];
    html.push(`<div class="help">Ubicaciones encontradas: <b>${rows.length}</b></div>`);

    rows.forEach(r => {
      const nivel = r.nivel || r.Nivel || "";
      const bl = r.codigocsd || r.CodigoCSD || r.bl || r.BL || "";
      const lp = r.lp_code || r.LP || r.lp || r.LPCode || "";
      const lote = r.lote || r.Lote || "";
      const total = r.total || r.Total || r.cantidad || r.Cantidad || "0";

      html.push(`
        <div class="miniCard">
          <div class="miniTop">
            <span class="chip">${esc(nivel || "—")}</span>
            ${lp ? `<span class="chip chipSoft"><i class="fa-solid fa-tag"></i> LP: ${esc(lp)}</span>` : ``}
          </div>

          <div class="miniGrid">
            <div class="kv"><span class="k">BL</span><span class="v">${esc(bl || "—")}</span></div>
            <div class="kv"><span class="k">Lote</span><span class="v">${esc(lote || "—")}</span></div>
            <div class="kv"><span class="k">Total</span><span class="v">${esc(total)}</span></div>
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
      suggest.innerHTML = `<div class="alert" style="display:block;background:rgba(11,18,32,.06);border-color:rgba(11,18,32,.14)">Sin resultados</div>`;
      return;
    }
    suggest.innerHTML = lastSuggestions.map(buildSuggestionItem).join("");
  }

  function runSearch(){
    const term = q.value.trim();
    clearUI();
    hideMsg();

    if(term.length < 2){
      return;
    }

    // Strategy:
    // 1) Consultar existencias_ubicacion_total con el término tal cual
    // 2) Si devuelve rows, levantamos sugerencias (por artículo) y si hay 1, autoseleccionamos
    fetchExistencias(term)
      .then(data => {
        // Tolerante: data puede venir como {ok:true, rows:[]}, {data:[]}, etc.
        const rows = data.rows || data.data || data.resultados || [];
        if(!Array.isArray(rows) || !rows.length){
          suggest.innerHTML = `<div class="alert" style="display:block;background:rgba(11,18,32,.06);border-color:rgba(11,18,32,.14)">Sin resultados</div>`;
          return;
        }

        showSuggestionsFromRows(rows);

        // Autoselección si solo hay 1 artículo
        if(lastSuggestions.length === 1){
          const art = lastSuggestions[0].cve_articulo;
          // recargar detalle con el artículo exacto
          fetchExistencias(art).then(d2 => {
            const rows2 = d2.rows || d2.data || d2.resultados || [];
            renderDetail(rows2, art);
          });
        }
      })
      .catch(err => {
        showMsg("Error consultando existencias. Revisa API: " + err, "warn");
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
