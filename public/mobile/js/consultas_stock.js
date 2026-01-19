// ===============================
// Consultas RF - Stock (sin sesiones)
// ===============================
const API_STOCK_EXIST = "/assistpro_kardex_fc/public/api/stock/existencias_ubicacion_total.php";

const $ = (id) => document.getElementById(id);

function rfCtx(){
  return {
    user: localStorage.getItem("rf_user") || "",
    almacen: localStorage.getItem("rf_almacen") || ""
  };
}

function guardRF(){
  const ctx = rfCtx();
  if(!ctx.almacen){
    // sin almacén, regresamos al login móvil
    window.location.href = "/assistpro_kardex_fc/public/mobile/?rf=1";
    return false;
  }
  $("rfUserLbl").textContent = "Usuario: " + (ctx.user || "--");
  $("rfAlmLbl").textContent  = ctx.almacen;
  return true;
}

function setEstado(msg, isError=false){
  $("estado").innerHTML = isError ? `<span class="text-danger">${msg}</span>` : msg;
}

function num(n){
  const x = Number(n);
  return isNaN(x) ? 0 : x;
}

function renderKPIs(k){
  $("kpis").classList.remove("d-none");
  $("k_total").textContent = `Total: ${num(k?.existencia_total)}`;
  $("k_disp").textContent  = `Disponible: ${num(k?.existencia_disponible)}`;
  $("k_cuar").textContent  = `Cuarentena: ${num(k?.en_cuarentena)}`;
  $("k_res").textContent   = `Reservado: ${num(k?.reservado_picking)}`;
  $("k_obs").textContent   = `Obsoleto: ${num(k?.obsoleto)}`;
}

function pill(text, cls){
  return `<span class="badge ${cls}" style="font-size:12px;">${text}</span>`;
}

function renderRows(rows){
  if(!rows || rows.length === 0){
    $("results").innerHTML = `<div class="alert alert-light border">Sin resultados</div>`;
    return;
  }

  const html = rows.slice(0, 200).map(r => {
    const art = r.cve_articulo || "";
    const lote = r.cve_lote || "";
    const alm = r.cve_almac || "";
    const nivel = r.nivel ?? "";
    const ub = r.idy_ubica ?? "";
    const bl = r.bl ?? ""; // CodigoCSD en tu vista
    const lp = r.lp ?? r.license_plate ?? r.charola ?? "";

    const tot = num(r.cantidad || r.existencia_total);
    const disp = num(r.existencia_disponible);
    const cua = num(r.en_cuarentena);
    const res = num(r.reservado_picking);
    const obs = num(r.obsoleto);

    return `
      <div class="card mb-2 p-2" style="border-radius:12px;">
        <div class="d-flex justify-content-between">
          <div><strong>${art}</strong></div>
          <div class="text-muted"><small>${alm} · N${nivel}</small></div>
        </div>
        <div class="text-muted"><small>
          UB: ${ub}
          ${bl ? " · BL: "+bl : ""}
          ${lp ? " · LP: "+lp : ""}
          ${lote ? " · Lote: "+lote : ""}
        </small></div>

        <div class="d-flex flex-wrap gap-2 mt-2">
          ${pill("Tot: "+tot, "bg-primary")}
          ${pill("Disp: "+disp, "bg-success")}
          ${pill("Cuar: "+cua, "bg-warning text-dark")}
          ${pill("Res: "+res, "bg-info text-dark")}
          ${pill("Obs: "+obs, "bg-danger")}
        </div>
      </div>
    `;
  }).join("");

  $("results").innerHTML = html;
}

function buildParams(q){
  const ctx = rfCtx();
  const params = new URLSearchParams();
  params.set("cve_almac", ctx.almacen);
  params.set("solo_disponible", "1");

  // Estrategia RF: intentamos por prioridad
  // 1) si parece BL (alfanum largo) => bl
  // 2) si parece LP (prefijos típicos) => lp
  // 3) default => cve_articulo (coincidencia)
  const s = (q || "").trim();
  if(!s) return params;

  // heurísticas simples (ajustables):
  const isBL = s.length >= 6 && /[A-Za-z]/.test(s) && /\d/.test(s);
  const isLP = /^LP/i.test(s) || /^CH/i.test(s) || /^PAL/i.test(s);

  if(isLP){
    params.set("lp", s);
  }else if(isBL){
    params.set("bl", s);
  }else{
    params.set("cve_articulo", s);
  }
  return params;
}

async function buscar(){
  try{
    const q = $("q").value.trim();
    if(!q){
      setEstado("Captura o escanea un valor.", true);
      return;
    }

    setEstado("Consultando...");
    $("results").innerHTML = "";
    $("kpis").classList.add("d-none");

    const url = API_STOCK_EXIST + "?" + buildParams(q).toString();
    const res = await fetch(url);
    const data = await res.json();

    if(!data.ok){
      setEstado(data.error || "Error en consulta", true);
      return;
    }

    renderKPIs(data.kpis || {});
    renderRows(data.rows || []);
    setEstado(`OK · ${data.rows?.length || 0} filas`);

  }catch(e){
    setEstado("Error: " + e.message, true);
  }
}

function limpiar(){
  $("q").value = "";
  $("results").innerHTML = "";
  $("kpis").classList.add("d-none");
  setEstado("");
  $("q").focus();
}

document.addEventListener("DOMContentLoaded", () => {
  if(!guardRF()) return;

  $("btnBuscar").addEventListener("click", buscar);
  $("btnLimpiar").addEventListener("click", limpiar);

  $("q").addEventListener("keydown", (e) => {
    if(e.key === "Enter"){
      e.preventDefault();
      buscar();
    }
  });

  setTimeout(() => $("q").focus(), 200);
});
