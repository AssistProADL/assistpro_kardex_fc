<?php
// public/control_patios/patios_admin.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

/**
 * Administración de Patios (YMS)
 * - NO tocar _menu_global.php ni _menu_global_end.php (solo includes)
 * - Sin jQuery (evita "$ is not defined")
 * - Sin imágenes externas: usa FontAwesome
 * - Modales propios (sin Bootstrap JS)
 * - Tablero con UX: badges, aging, alertas, conteos
 */

// EMPRESAS
$empresas = db_all("
    SELECT cve_cia, des_cia
    FROM c_compania
    WHERE COALESCE(Activo,1) = 1
    ORDER BY des_cia
");

// ALMACENES / PATIOS
$almacenesp = db_all("
    SELECT id, clave, nombre
    FROM c_almacenp
    ORDER BY clave, nombre
");

// TRANSPORTISTAS
$transportistas = db_all("
    SELECT ID_Proveedor, cve_proveedor, Nombre
    FROM c_proveedores
    WHERE COALESCE(Activo,1) = 1
      AND COALESCE(es_transportista,0) = 1
    ORDER BY Nombre
");

// TRANSPORTES (unidades)
$transportes = db_all("
    SELECT id, ID_Transporte, Nombre, Placas
    FROM t_transporte
    ORDER BY ID_Transporte
");
?>

<style>
/* ==========================================================
   Estilos del módulo (theme institucional ya vive en _menu_global.php)
   ========================================================== */
.patios-wrap { font-size:12px; padding:10px; }
.patios-wrap .small-label { font-size:10px; }
.patios-wrap #msg-global { font-size:11px; }
.patios-wrap .help-muted { color:#6c757d; font-size:11px; }

/* Encabezados de columnas */
.patios-wrap .col-etapa-title{
  font-weight:800; font-size:12px; text-align:center;
  margin:6px 0 10px; text-transform:uppercase;
  display:flex; align-items:center; justify-content:center; gap:8px;
}
.patios-wrap .badge-mini{
  font-size:10px; padding:2px 8px; border-radius:999px;
  background:#e9ecef; color:#212529; border:1px solid #dee2e6;
}
.badge-ok{ background:#d1e7dd; color:#0f5132; border-color:#badbcc; }
.badge-warn{ background:#fff3cd; color:#664d03; border-color:#ffecb5; }
.badge-crit{ background:#f8d7da; color:#842029; border-color:#f5c2c7; }

/* Cards */
.patios-wrap .etapa-card{
  border:1px solid #ddd; border-radius:14px;
  padding:10px; margin-bottom:12px; font-size:11px;
  background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.08);
  transition:transform .12s ease, box-shadow .12s ease, border-color .12s ease;
}
.patios-wrap .etapa-card:hover{
  transform:translateY(-1px);
  box-shadow:0 10px 28px rgba(0,0,0,.12);
}
.patios-wrap .etapa-header{
  font-weight:800; font-size:12px; display:flex; align-items:center;
  justify-content:space-between; gap:8px;
  margin-bottom:8px;
}
.patios-wrap .etapa-left{
  display:flex; align-items:center; gap:10px; min-width:0;
}
.patio-ico{
  font-size:18px;
  width:24px;
  text-align:center;
  opacity:.95;
}
.etapa-title-strong{
  display:flex; flex-direction:column; line-height:1.15; min-width:0;
}
.etapa-title-strong .top{
  font-weight:900; font-size:12px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.etapa-title-strong .sub{
  font-weight:600; font-size:10px; color:#6c757d;
}

/* Aging (SLA visual) */
.aging-warning{ border-left:6px solid #fd7e14; }
.aging-critical{
  border-left:6px solid #dc3545;
  box-shadow:0 0 0 2px rgba(220,53,69,.12);
  border-color:#f1b0b7;
}

/* Pills */
.estado-pill{
  display:inline-flex; align-items:center; gap:6px;
  padding:2px 10px; border-radius:999px;
  font-size:10px; color:#fff; margin:6px 0 0;
  font-weight:700;
}
.estado-EN_PATIO{ background:#0d6efd; }
.estado-ASIGNADO_ANDEN{ background:#6f42c1; }
.estado-EN_DESCARGA{ background:#fd7e14; color:#000; }
.estado-EN_CARGA{ background:#fd7e14; color:#000; }
.estado-INSPECCION{ background:#0dcaf0; color:#000; }
.estado-EN_QA{ background:#0dcaf0; color:#000; }
.estado-SALIDA{ background:#198754; }
.estado-PENDIENTE{ background:#dc3545; }
.estado-ERROR{ background:#dc3545; }

/* meta chips */
.mini-meta{ display:flex; gap:8px; flex-wrap:wrap; color:#6c757d; font-size:10px; margin-top:6px; }
.meta-chip{
  border:1px solid #e9ecef; background:#f8f9fa; border-radius:999px;
  padding:1px 8px; font-size:10px; color:#495057;
}

/* Botones */
.ap-btn{
  display:inline-flex; align-items:center; justify-content:center; gap:7px;
  border-radius:10px; padding:6px 10px; font-size:11px;
  cursor:pointer; border:1px solid #ddd; background:#fff;
  transition:background .12s ease, transform .12s ease, border-color .12s ease;
}
.ap-btn:hover{ transform:translateY(-1px); }
.ap-btn-primary{ background:#0d6efd; border-color:#0d6efd; color:#fff; }
.ap-btn-outline{ background:#fff; }
.ap-btn-danger{ background:#dc3545; border-color:#dc3545; color:#fff; }
.ap-btn:disabled{ opacity:.6; cursor:not-allowed; transform:none; }

/* Barra superior de alertas */
.patios-alert{
  display:flex; align-items:center; gap:10px;
  margin:6px 0 10px;
  font-size:11px;
}
.patios-alert .dot{
  width:8px; height:8px; border-radius:999px; background:#adb5bd;
}
.patios-alert.crit .dot{ background:#dc3545; }
.patios-alert.warn .dot{ background:#fd7e14; }

/* ===== Modales propios (sin Bootstrap JS) ===== */
.ap-modal-backdrop{
  position:fixed; inset:0; background:rgba(0,0,0,.45);
  display:none; align-items:center; justify-content:center;
  z-index:9999;
}
.ap-modal-backdrop.show{ display:flex; }
.ap-modal{
  width:min(900px, 96vw);
  background:#fff; border-radius:14px; overflow:hidden;
  box-shadow:0 12px 40px rgba(0,0,0,.25);
}
.ap-modal-header{
  padding:10px 12px;
  border-bottom:1px solid #eee;
  display:flex; align-items:center; justify-content:space-between;
  background:#0b2a52; color:#fff;
}
.ap-modal-title{ font-weight:900; font-size:13px; display:flex; align-items:center; gap:8px; }
.ap-modal-close{ border:none; background:transparent; font-size:18px; line-height:1; cursor:pointer; color:#fff; opacity:.9; }
.ap-modal-close:hover{ opacity:1; }
.ap-modal-body{ padding:12px; }
.ap-modal-footer{
  padding:10px 12px;
  border-top:1px solid #eee;
  display:flex; gap:8px; justify-content:flex-end;
}
.ap-modal-row{ display:flex; gap:10px; flex-wrap:wrap; }
.ap-modal-col{ flex:1 1 260px; min-width:260px; }
.ap-select, .ap-input, .ap-textarea{
  width:100%; border:1px solid #ddd; border-radius:10px;
  padding:7px 9px; font-size:12px;
}
.ap-textarea{ min-height:70px; }
.table-sm{ font-size:11px; }
.table-sm th{ font-weight:800; }
.ap-msg{
  font-size:11px;
  padding:8px 10px;
  border-radius:10px;
  border:1px solid #eee;
  background:#f8f9fa;
}
.ap-msg.error{ border-color:#f5c2c7; background:#f8d7da; color:#842029; }
.ap-msg.ok{ border-color:#badbcc; background:#d1e7dd; color:#0f5132; }
</style>

<div class="container-fluid patios-wrap">
  <div class="d-flex align-items-end justify-content-between flex-wrap gap-2">
    <div>
      <h5 class="mb-1">Administración de Patios</h5>
      <div id="msg-global" class="text-muted">
        Selecciona empresa y almacén. Luego presiona <b>Nueva visita</b>.
      </div>
      <div id="alerta-global" class="patios-alert" style="display:none;">
        <span class="dot"></span>
        <span id="alerta-texto"></span>
      </div>
    </div>
  </div>

  <!-- Filtros -->
  <div class="row g-2 mb-2 mt-1">
    <div class="col-md-3">
      <label class="small-label">Empresa (c_compania)</label>
      <select id="f_empresa" class="form-control form-control-sm ap-select">
        <option value="">(Seleccione)</option>
        <?php foreach ($empresas as $e): ?>
          <option value="<?= htmlspecialchars((string)$e['cve_cia']) ?>">
            <?= htmlspecialchars((string)$e['cve_cia'].' - '.(string)$e['des_cia']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-5">
      <label class="small-label">Almacén / Patio (c_almacenp.id)</label>
      <select id="f_almacenp" class="form-control form-control-sm ap-select">
        <option value="">(Seleccione)</option>
        <?php foreach ($almacenesp as $a): ?>
          <option value="<?= htmlspecialchars((string)$a['id']) ?>">
            <?= htmlspecialchars((string)$a['clave'].' - '.(string)$a['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-4 d-flex align-items-end gap-2">
      <button id="btn_refrescar" class="ap-btn ap-btn-outline" type="button">
        <i class="fa-solid fa-rotate"></i> Refrescar
      </button>
      <button id="btn_nueva_visita" class="ap-btn ap-btn-primary" type="button">
        <i class="fa-solid fa-plus"></i> Nueva visita
      </button>
    </div>
  </div>

  <!-- Tablero 4 columnas -->
  <div class="row">
    <div class="col-md-3">
      <div class="col-etapa-title">
        1. Cita
        <span class="badge-mini" id="cnt-etapa-1">0</span>
      </div>
      <div id="col-etapa-1"></div>
    </div>

    <div class="col-md-3">
      <div class="col-etapa-title">
        2. Arribo / En Patio
        <span class="badge-mini" id="cnt-etapa-2">0</span>
      </div>
      <div id="col-etapa-2"></div>
    </div>

    <div class="col-md-3">
      <div class="col-etapa-title">
        3. Inspección / QA
        <span class="badge-mini" id="cnt-etapa-3">0</span>
      </div>
      <div id="col-etapa-3"></div>
    </div>

    <div class="col-md-3">
      <div class="col-etapa-title">
        4. Carga / Descarga
        <span class="badge-mini" id="cnt-etapa-4">0</span>
      </div>
      <div id="col-etapa-4"></div>
    </div>
  </div>
</div>

<!-- ======================= MODAL: NUEVA VISITA ======================= -->
<div id="mdlNuevaVisita" class="ap-modal-backdrop" aria-hidden="true">
  <div class="ap-modal">
    <div class="ap-modal-header">
      <div class="ap-modal-title">
        <i class="fa-solid fa-plus"></i> Nueva visita
      </div>
      <button class="ap-modal-close" type="button" data-close="mdlNuevaVisita">×</button>
    </div>

    <div class="ap-modal-body">
      <div id="nv-msg" class="ap-msg" style="display:none;"></div>

      <form id="formNuevaVisita">
        <div class="ap-modal-row">
          <div class="ap-modal-col">
            <label class="small-label">Empresa</label>
            <select name="empresa_id" id="nv_empresa" class="ap-select" required>
              <option value="">(Seleccione)</option>
              <?php foreach ($empresas as $e): ?>
                <option value="<?= htmlspecialchars((string)$e['cve_cia']) ?>">
                  <?= htmlspecialchars((string)$e['cve_cia'].' - '.(string)$e['des_cia']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="ap-modal-col">
            <label class="small-label">Almacén / Patio</label>
            <select name="almacenp_id" id="nv_almacenp" class="ap-select" required>
              <option value="">(Seleccione)</option>
              <?php foreach ($almacenesp as $a): ?>
                <option value="<?= htmlspecialchars((string)$a['id']) ?>">
                  <?= htmlspecialchars((string)$a['clave'].' - '.(string)$a['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="ap-modal-col">
            <label class="small-label">Transportista</label>
            <select name="transportista_id" id="nv_transportista" class="ap-select">
              <option value="">(Opcional)</option>
              <?php foreach ($transportistas as $t): ?>
                <option value="<?= htmlspecialchars((string)$t['ID_Proveedor']) ?>">
                  <?= htmlspecialchars((string)$t['cve_proveedor'].' - '.(string)$t['Nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="ap-modal-col">
            <label class="small-label">Transporte / Unidad</label>
            <select name="id_transporte" id="nv_transporte" class="ap-select">
              <option value="">(Opcional)</option>
              <?php foreach ($transportes as $tr): ?>
                <option value="<?= htmlspecialchars((string)$tr['ID_Transporte']) ?>">
                  <?= htmlspecialchars((string)$tr['ID_Transporte'].' - '.(string)$tr['Nombre'].' '.(string)$tr['Placas']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="ap-modal-col" style="flex:1 1 100%; min-width:100%;">
            <label class="small-label">Observaciones</label>
            <textarea name="observaciones" id="nv_obs" class="ap-textarea" placeholder="Notas operativas (opcional)"></textarea>
            <div class="help-muted mt-1">
              Tip: al registrar la visita, quedará en “Cita / Pendiente” para que el guardia/planner continúe el flujo.
            </div>
          </div>
        </div>
      </form>
    </div>

    <div class="ap-modal-footer">
      <button class="ap-btn ap-btn-outline" type="button" data-close="mdlNuevaVisita">Cerrar</button>
      <button id="btnSubmitNuevaVisita" class="ap-btn ap-btn-primary" type="button">
        <i class="fa-solid fa-check"></i> Guardar
      </button>
    </div>
  </div>
</div>

<!-- ======================= MODAL: VINCULAR OCs ======================= -->
<div id="mdlVincularOC" class="ap-modal-backdrop" aria-hidden="true">
  <div class="ap-modal">
    <div class="ap-modal-header">
      <div class="ap-modal-title">
        <i class="fa-solid fa-link"></i> Vincular órdenes de compra
      </div>
      <button class="ap-modal-close" type="button" data-close="mdlVincularOC">×</button>
    </div>

    <div class="ap-modal-body">
      <input type="hidden" id="id_visita_oc" value="">

      <div id="oc-msg" class="ap-msg" style="display:none;"></div>

      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
        <div class="help-muted">
          Selecciona OCs pendientes y confirma. El vínculo se guarda en <b>t_patio_doclink</b>.
        </div>
        <button id="btnRecargarOCs" class="ap-btn ap-btn-outline" type="button">
          <i class="fa-solid fa-rotate"></i> Recargar
        </button>
      </div>

      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle">
          <thead>
            <tr>
              <th style="width:34px;">Sel</th>
              <th>OC</th>
              <th>Proveedor</th>
              <th>Monto</th>
              <th>Estado</th>
              <th>Fecha</th>
            </tr>
          </thead>
          <tbody id="tbOCs">
            <tr><td colspan="6" class="text-muted">Sin datos.</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="ap-modal-footer">
      <button class="ap-btn ap-btn-outline" type="button" data-close="mdlVincularOC">Cerrar</button>
      <button id="btnVincularSeleccionadas" class="ap-btn ap-btn-primary" type="button">
        <i class="fa-solid fa-check"></i> Vincular seleccionadas
      </button>
    </div>
  </div>
</div>

<script>
/* ==========================================================
   JS sin jQuery (evita "$ is not defined")
   ========================================================== */
(function(){
  const el = (id)=>document.getElementById(id);
  const qs = (sel, root=document)=>root.querySelector(sel);
  const qsa = (sel, root=document)=>Array.from(root.querySelectorAll(sel));

  const API_TABLERO = "patios_tablero_api.php";
  const API_NUEVA   = "patios_nueva_visita.php";
  const API_OCS     = "patios_oc_pendientes.php";
  const API_VINC    = "patios_vincular_oc.php";

  function showGlobalMsg(text, tone="info"){
    const msg = el("msg-global");
    msg.classList.remove("text-muted","text-danger","text-success");
    if(tone==="error") msg.classList.add("text-danger");
    else if(tone==="ok") msg.classList.add("text-success");
    else msg.classList.add("text-muted");
    msg.innerHTML = text;
  }

  function setAlerta(totalCrit){
    const box = el("alerta-global");
    const txt = el("alerta-texto");
    if(!totalCrit){
      box.style.display="none";
      box.classList.remove("crit","warn");
      return;
    }
    box.style.display="flex";
    box.classList.add("crit");
    txt.textContent = "Atención: " + totalCrit + " visitas en espera crítica (SLA).";
  }

  function openModal(id){
    const b = el(id);
    if(!b) return;
    b.classList.add("show");
    b.setAttribute("aria-hidden","false");
  }
  function closeModal(id){
    const b = el(id);
    if(!b) return;
    b.classList.remove("show");
    b.setAttribute("aria-hidden","true");
  }

  // close handlers
  document.addEventListener("click", (ev)=>{
    const tgt = ev.target;
    const closeId = tgt && tgt.getAttribute ? tgt.getAttribute("data-close") : null;
    if(closeId) closeModal(closeId);
    // click outside modal (backdrop)
    if(tgt && tgt.classList && tgt.classList.contains("ap-modal-backdrop")){
      tgt.classList.remove("show");
    }
  });

  async function postForm(url, dataObj){
    const body = new URLSearchParams();
    Object.keys(dataObj || {}).forEach(k => body.append(k, dataObj[k] ?? ""));
    const res = await fetch(url, { method:"POST", headers:{ "Content-Type":"application/x-www-form-urlencoded" }, body });
    const txt = await res.text();
    try { return JSON.parse(txt); } catch(e) { return { ok:false, error: txt || "Respuesta no válida" }; }
  }

  async function getJSON(url, params){
    const u = new URL(url, window.location.href);
    Object.keys(params||{}).forEach(k => u.searchParams.set(k, params[k]));
    const res = await fetch(u.toString(), { method:"GET" });
    const txt = await res.text();
    try { return JSON.parse(txt); } catch(e) { return { ok:false, error: txt || "Respuesta no válida" }; }
  }

  function iconForStage(stage){
    // sólo íconos (no imágenes)
    switch(String(stage)){
      case "1": return '<i class="fa-solid fa-calendar-check patio-ico"></i>';
      case "2": return '<i class="fa-solid fa-truck-moving patio-ico"></i>';
      case "3": return '<i class="fa-solid fa-clipboard-check patio-ico"></i>';
      case "4": return '<i class="fa-solid fa-dolly patio-ico"></i>';
      default:  return '<i class="fa-solid fa-truck patio-ico"></i>';
    }
  }

  function estadoClass(estatus){
    const e = String(estatus || "PENDIENTE").toUpperCase();
    // algunos estatus vienen como enum; normalizamos a clases
    if(e.includes("PEND")) return "estado-PENDIENTE";
    if(e.includes("EN_PATIO")) return "estado-EN_PATIO";
    if(e.includes("ASIGNADO")) return "estado-ASIGNADO_ANDEN";
    if(e.includes("DESCARGA")) return "estado-EN_DESCARGA";
    if(e.includes("CARGA")) return "estado-EN_CARGA";
    if(e.includes("INSPECCION")) return "estado-INSPECCION";
    if(e.includes("QA")) return "estado-EN_QA";
    if(e.includes("SALIDA")) return "estado-SALIDA";
    return "estado-PENDIENTE";
  }

  function calcAgingClass(mins){
    // warning: > 240m (4h), critical: > 720m (12h)
    if(mins >= 720) return "aging-critical";
    if(mins >= 240) return "aging-warning";
    return "";
  }

  function fmtAging(mins){
    mins = Math.max(0, Number(mins||0));
    const h = Math.floor(mins / 60);
    const m = mins % 60;
    if(h<=0) return (m+"m");
    return (h+"h "+m+"m");
  }

  function renderCard(v, stage){
    const id_visita = v.id_visita ?? v.id ?? "";
    const id_transporte = v.id_transporte ?? "";
    const anden = v.id_anden_actual ?? v.anden ?? "";
    const estatus = v.estatus ?? "PENDIENTE";
    const llego = v.fecha_llegada ?? v.llego ?? v.fecha ?? "";
    const mins = v.aging_min ?? v.aging_mins ?? 0;
    const aClass = calcAgingClass(mins);

    const title = (v.id_transporte ? (v.id_transporte + " - ") : "") + (v.folio || v.folio_cita || "");
    const sub = "Visita #" + (id_visita || "-");

    return `
      <div class="etapa-card ${aClass}">
        <div class="etapa-header">
          <div class="etapa-left">
            ${iconForStage(stage)}
            <div class="etapa-title-strong">
              <div class="top">${escapeHtml(title || "Sin folio")}</div>
              <div class="sub">${escapeHtml(sub)}</div>
            </div>
          </div>

          <div class="mini-meta" style="margin-top:0;">
            <span class="meta-chip">Aging: ${fmtAging(mins)}</span>
          </div>
        </div>

        <div>
          <span class="estado-pill ${estadoClass(estatus)}">
            <i class="fa-solid fa-circle"></i> ${escapeHtml(String(estatus))}
          </span>

          <div class="mini-meta">
            <span class="meta-chip">Andén: ${escapeHtml(String(anden || "-"))}</span>
            <span class="meta-chip">Llegó: ${escapeHtml(String(llego || "-"))}</span>
          </div>

          <div class="d-flex gap-2 mt-2 flex-wrap">
            <button class="ap-btn ap-btn-outline btn-vincular-oc" data-id_visita="${escapeAttr(id_visita)}" type="button">
              <i class="fa-solid fa-link"></i> Vincular OCs
            </button>
          </div>
        </div>
      </div>
    `;
  }

  function escapeHtml(s){
    s = String(s ?? "");
    return s.replace(/[&<>"']/g, (c)=>({
      "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"
    }[c]));
  }
  function escapeAttr(s){
    return escapeHtml(s).replace(/"/g,"&quot;");
  }

  async function cargarTablero(){
    const emp = el("f_empresa").value;
    const alm = el("f_almacenp").value;

    // limpia columnas
    ["1","2","3","4"].forEach(n=>{
      el("col-etapa-"+n).innerHTML = "";
      el("cnt-etapa-"+n).textContent = "0";
      el("cnt-etapa-"+n).className = "badge-mini";
    });

    if(!emp || !alm){
      showGlobalMsg('Selecciona empresa y almacén. Luego presiona <b>Nueva visita</b>.', "info");
      setAlerta(0);
      return;
    }

    showGlobalMsg("Tablero actualizado.", "ok");

    const resp = await getJSON(API_TABLERO, { empresa_id: emp, almacenp_id: alm });
    if(!resp || !resp.ok){
      showGlobalMsg("Error al cargar tablero: " + escapeHtml(resp && resp.error ? resp.error : "desconocido"), "error");
      setAlerta(0);
      return;
    }

    const data = Array.isArray(resp.data) ? resp.data : [];
    // agrupación por etapa
    const buckets = { "1":[], "2":[], "3":[], "4":[] };
    let critCount = 0;

    data.forEach(v=>{
      const etapa = String(v.etapa ?? v.stage ?? "1");
      if(buckets[etapa]) buckets[etapa].push(v);
      const mins = Number(v.aging_min ?? v.aging_mins ?? 0);
      if(mins >= 720) critCount++;
    });

    setAlerta(critCount);

    // render
    ["1","2","3","4"].forEach(n=>{
      const arr = buckets[n] || [];
      el("cnt-etapa-"+n).textContent = String(arr.length);

      // color del badge según saturación/crit
      const badge = el("cnt-etapa-"+n);
      if(arr.length === 0) badge.classList.add("badge-ok");
      else if(critCount > 0 && n==="1") badge.classList.add("badge-crit");
      else badge.classList.add("badge-warn");

      if(arr.length === 0){
        el("col-etapa-"+n).innerHTML = `<div class="help-muted">Sin registros.</div>`;
      }else{
        el("col-etapa-"+n).innerHTML = arr.map(v=>renderCard(v,n)).join("");
      }
    });
  }

  function syncNuevaVisitaDefaults(){
    // precarga empresa/alm del filtro
    const emp = el("f_empresa").value;
    const alm = el("f_almacenp").value;
    el("nv_empresa").value = emp || "";
    el("nv_almacenp").value = alm || "";
    el("nv_obs").value = "";
    el("nv_transportista").value = "";
    el("nv_transporte").value = "";

    const msg = el("nv-msg");
    msg.style.display="none";
    msg.className="ap-msg";
    msg.textContent="";
  }

  async function guardarNuevaVisita(){
    const empresa_id = el("nv_empresa").value;
    const almacenp_id = el("nv_almacenp").value;

    if(!empresa_id || !almacenp_id){
      const m = el("nv-msg");
      m.style.display="block";
      m.className="ap-msg error";
      m.textContent="Empresa y almacén son obligatorios.";
      return;
    }

    const payload = {
      empresa_id,
      almacenp_id,
      transportista_id: el("nv_transportista").value,
      id_transporte: el("nv_transporte").value,
      observaciones: el("nv_obs").value
    };

    const m = el("nv-msg");
    m.style.display="block";
    m.className="ap-msg";
    m.textContent="Guardando...";

    const resp = await postForm(API_NUEVA, payload);
    if(!resp || !resp.ok){
      m.className="ap-msg error";
      m.textContent = (resp && resp.error) ? resp.error : "Error al guardar visita.";
      return;
    }

    m.className="ap-msg ok";
    m.textContent = "Visita registrada (#" + (resp.id_visita || resp.id || "") + ").";
    await cargarTablero();
    setTimeout(()=>closeModal("mdlNuevaVisita"), 500);
  }

  async function cargarOCsPendientes(id_visita){
    const tbody = el("tbOCs");
    const m = el("oc-msg");
    m.style.display="none";
    m.className="ap-msg";
    m.textContent="";

    tbody.innerHTML = `<tr><td colspan="6" class="text-muted">Cargando...</td></tr>`;

    const resp = await getJSON(API_OCS, { id_visita: id_visita });
    if(!resp || !resp.ok){
      tbody.innerHTML = `<tr><td colspan="6" class="text-muted">Sin datos.</td></tr>`;
      m.style.display="block";
      m.className="ap-msg error";
      m.textContent = (resp && resp.error) ? resp.error : "No fue posible cargar OCs.";
      return;
    }

    const rows = Array.isArray(resp.data) ? resp.data : [];
    if(rows.length === 0){
      tbody.innerHTML = `<tr><td colspan="6" class="text-muted">No hay OCs pendientes.</td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map(r=>{
      const id = r.id_oc ?? r.oc_id ?? r.id ?? "";
      const folio = r.folio ?? r.folio_oc ?? r.oc ?? "";
      const prov = r.proveedor ?? r.proveedor_nombre ?? r.nombre_proveedor ?? "";
      const monto = r.monto_total ?? r.monto ?? "";
      const est = r.estatus ?? r.estado ?? "PENDIENTE";
      const fec = r.fecha ?? r.fecha_oc ?? "";
      return `
        <tr>
          <td><input type="checkbox" class="chk-oc" value="${escapeAttr(id)}"></td>
          <td>${escapeHtml(folio || id)}</td>
          <td>${escapeHtml(prov)}</td>
          <td>${escapeHtml(String(monto))}</td>
          <td>${escapeHtml(String(est))}</td>
          <td>${escapeHtml(String(fec))}</td>
        </tr>
      `;
    }).join("");
  }

  async function vincularOCsSeleccionadas(){
    const id_visita = el("id_visita_oc").value;
    const checks = qsa(".chk-oc").filter(x=>x.checked).map(x=>x.value);

    const m = el("oc-msg");
    m.style.display="block";
    m.className="ap-msg";
    m.textContent="";

    if(!checks.length){
      m.className="ap-msg error";
      m.textContent="Seleccione al menos una OC.";
      return;
    }

    m.textContent="Vinculando...";
    const resp = await postForm(API_VINC, { id_visita: id_visita, oc_ids: checks.join(",") });
    if(!resp || !resp.ok){
      m.className="ap-msg error";
      m.textContent = (resp && resp.error) ? resp.error : "Error al vincular.";
      return;
    }

    m.className="ap-msg ok";
    m.textContent="OC(s) vinculada(s).";
    await cargarTablero();
    setTimeout(()=>closeModal("mdlVincularOC"), 500);
  }

  // Eventos
  el("btn_refrescar").addEventListener("click", cargarTablero);
  el("f_empresa").addEventListener("change", cargarTablero);
  el("f_almacenp").addEventListener("change", cargarTablero);

  el("btn_nueva_visita").addEventListener("click", ()=>{
    syncNuevaVisitaDefaults();
    openModal("mdlNuevaVisita");
  });
  el("btnSubmitNuevaVisita").addEventListener("click", guardarNuevaVisita);

  // Delegación para botones dentro de cards
  document.addEventListener("click", (ev)=>{
    const btn = ev.target.closest ? ev.target.closest(".btn-vincular-oc") : null;
    if(!btn) return;
    const id_visita = btn.getAttribute("data-id_visita") || "";
    el("id_visita_oc").value = id_visita;
    openModal("mdlVincularOC");
    cargarOCsPendientes(id_visita);
  });

  el("btnVincularSeleccionadas").addEventListener("click", vincularOCsSeleccionadas);
  el("btnRecargarOCs").addEventListener("click", ()=>{
    const id_visita = el("id_visita_oc").value;
    if(id_visita) cargarOCsPendientes(id_visita);
  });

  // inicial
  cargarTablero();
})();
</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
?>
