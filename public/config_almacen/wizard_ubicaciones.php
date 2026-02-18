<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>
<style>
  :root{
    --corp-blue:#0b3a82;
    --muted:#6c757d;
    --bg:#eef2f6;
    --card:#ffffff;
    --line:#e5e7eb;
    --ok:#198754;
    --bad:#dc3545;
  }
  body{ background:var(--bg); }
  .ap-wrap, .ap-wrap *{ font-size:10px !important; }
  .ap-wrap{ padding:14px; }
  .ap-title{
    display:flex;align-items:center;gap:10px;
    color:var(--corp-blue);
    font-weight:800;
    font-size:18px !important;
    margin: 4px 0 10px 0;
  }
  .ap-card{
    background:var(--card);
    border:1px solid var(--line);
    border-radius:12px;
    padding:12px;
    box-shadow:0 1px 3px rgba(0,0,0,.05);
    margin-bottom:12px;
  }
  .row{ display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end; }
  .col{ display:flex; flex-direction:column; gap:4px; min-width:170px; }
  label{ color:#111827; font-weight:700; }
  input, select{
    border:1px solid var(--line);
    border-radius:8px;
    padding:7px 8px;
    background:#fff;
    outline:none;
  }
  input:focus, select:focus{ border-color:var(--corp-blue); box-shadow:0 0 0 2px rgba(11,58,130,.12); }
  .btn{
    border:1px solid var(--line);
    background:#fff;
    padding:8px 10px;
    border-radius:10px;
    cursor:pointer;
    font-weight:800;
  }
  .btn.primary{ background:var(--corp-blue); border-color:var(--corp-blue); color:#fff; }
  .btn:disabled{ opacity:.5; cursor:not-allowed; }
  .msg-ok{ color:var(--ok); font-weight:800; }
  .msg-bad{ color:var(--bad); font-weight:800; }
  .muted{ color:var(--muted); }
  .flags{ display:flex; flex-wrap:wrap; gap:8px; }
  .flags label{ font-weight:700; color:#111827; }
  .grid{
    max-height:55vh;
    overflow:auto;
    border:1px solid var(--line);
    border-radius:12px;
    background:#fff;
  }
  table{ border-collapse:collapse; width:100%; }
  th,td{ border-bottom:1px solid #f1f5f9; padding:6px 8px; text-align:center; white-space:nowrap; }
  th{ position:sticky; top:0; background:#f8fafc; z-index:1; font-weight:900; }
  .kpi{ display:flex; gap:14px; flex-wrap:wrap; }
  .kpi .box{
    border:1px solid var(--line);
    background:#fff;
    border-radius:12px;
    padding:10px 12px;
    min-width:160px;
  }
  .kpi .v{ font-size:16px !important; font-weight:900; color:#111827; }
  .kpi .t{ color:var(--muted); font-weight:800; }
</style>

<div class="ap-wrap">
  <div class="ap-title">Wizard BL – Generador de Ubicaciones (CodigoCSD)</div>

  <div class="ap-card">
    <div class="row">
      <div class="col" style="min-width:260px">
        <label>Empresa</label>
        <select id="selEmpresa">
          <option value="">Seleccione</option>
        </select>
      </div>

      <div class="col" style="min-width:320px">
        <label>Almacén Padre (c_almacenp)</label>
        <select id="selAlmacenP" disabled>
          <option value="">Seleccione</option>
        </select>
      </div>

      <div class="col" style="min-width:320px">
        <label>Almacén Operativo / Zona (c_almacen)</label>
        <select id="selAlmacen" disabled>
          <option value="">Seleccione</option>
        </select>
      </div>
    </div>

    <hr style="border:none;border-top:1px solid var(--line);margin:12px 0">

    <div class="row">
      <div class="col">
        <label>Pasillo</label>
        <input id="pasillo" placeholder="Ej: A">
      </div>
      <div class="col">
        <label>Rack</label>
        <input id="rack" placeholder="Ej: 01">
      </div>

      <div class="col">
        <label>Nivel desde</label>
        <input id="nivel_desde" value="01">
      </div>
      <div class="col">
        <label>Nivel hasta</label>
        <input id="nivel_hasta" value="01">
      </div>

      <div class="col">
        <label>Sección desde</label>
        <input id="sec_desde" value="01">
      </div>
      <div class="col">
        <label>Sección hasta</label>
        <input id="sec_hasta" value="01">
      </div>

      <div class="col">
        <label>Posición desde</label>
        <input id="pos_desde" value="01">
      </div>
      <div class="col">
        <label>Posición hasta</label>
        <input id="pos_hasta" value="05">
      </div>

      <div class="col">
        <label>Status</label>
        <select id="activo">
          <option value="1">Activo</option>
          <option value="0">Bloqueado</option>
        </select>
      </div>

      <div class="col" style="min-width:260px">
        <label>Banderas</label>
        <div class="flags">
          <label><input type="checkbox" id="picking"> Picking</label>
          <label><input type="checkbox" id="piso"> Piso</label>
          <label><input type="checkbox" id="mixto"> Mixto</label>
          <label><input type="checkbox" id="prod"> Producción</label>
          <label><input type="checkbox" id="reab"> Reabasto</label>
          <label><input type="checkbox" id="ptl"> PTL</label>
        </div>
      </div>

      <div class="col">
        <button class="btn primary" id="btnPreview" disabled>Generar previsualización</button>
      </div>
      <div class="col">
        <button class="btn" id="btnSave" disabled>Guardar</button>
      </div>
    </div>

    <div style="margin-top:10px">
      <span id="msg" class="muted"></span>
    </div>
  </div>

  <div class="ap-card">
    <div class="kpi">
      <div class="box"><div class="v" id="k_total">0</div><div class="t">Total ubicaciones</div></div>
      <div class="box"><div class="v" id="k_dup">0</div><div class="t">Duplicados (precheck)</div></div>
      <div class="box"><div class="v" id="k_niv">0</div><div class="t">Niveles</div></div>
      <div class="box"><div class="v" id="k_sec">0</div><div class="t">Secciones</div></div>
      <div class="box"><div class="v" id="k_pos">0</div><div class="t">Posiciones</div></div>
    </div>

    <div style="margin-top:10px" class="grid">
      <table>
        <thead>
          <tr>
            <th>CodigoCSD</th>
            <th>Pasillo</th>
            <th>Rack</th>
            <th>Nivel</th>
            <th>Sección</th>
            <th>Posición</th>
            <th>Picking</th>
            <th>PTL</th>
            <th>Mixto</th>
            <th>Producción</th>
            <th>Piso</th>
            <th>Reabasto</th>
          </tr>
        </thead>
        <tbody id="tbPrev">
          <tr><td colspan="12" class="muted">Sin previsualización.</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  // APIs existentes
  const API_EMPRESAS = "../api/empresas_api.php?action=list&solo_activas=1";
  const API_FILTROS  = "../api/filtros_assistpro.php?action=init"; // + &empresa=
  const API_ZONAS    = "../api/zonas_api.php?solo_activas=1";      // + &almacenp_id=

  // Nuevo API del wizard
  const API_WIZARD   = "../api/api_wizard_bl.php";

  const selEmpresa  = document.getElementById('selEmpresa');
  const selAlmacenP = document.getElementById('selAlmacenP');
  const selAlmacen  = document.getElementById('selAlmacen');

  const btnPreview  = document.getElementById('btnPreview');
  const btnSave     = document.getElementById('btnSave');

  const msg = document.getElementById('msg');

  const k_total = document.getElementById('k_total');
  const k_dup   = document.getElementById('k_dup');
  const k_niv   = document.getElementById('k_niv');
  const k_sec   = document.getElementById('k_sec');
  const k_pos   = document.getElementById('k_pos');

  const tbPrev  = document.getElementById('tbPrev');

  let previewPayload = null;

  function setMsg(text, ok=true){
    msg.className = ok ? "msg-ok" : "msg-bad";
    msg.textContent = text;
  }

  function resetAll(){
    previewPayload = null;
    btnSave.disabled = true;
    k_total.textContent = "0";
    k_dup.textContent = "0";
    k_niv.textContent = "0";
    k_sec.textContent = "0";
    k_pos.textContent = "0";
    tbPrev.innerHTML = `<tr><td colspan="12" class="muted">Sin previsualización.</td></tr>`;
  }

  async function loadEmpresas(){
    const r = await fetch(API_EMPRESAS);
    const j = await r.json();
    const data = j.data || [];
    selEmpresa.innerHTML = `<option value="">Seleccione</option>` +
      data.map(x => `<option value="${x.cve_cia}">${(x.clave_empresa||'')} - ${(x.des_cia||'')}</option>`).join('');
  }

  async function loadAlmacenP(cve_cia){
    selAlmacenP.disabled = true;
    selAlmacen.disabled = true;
    selAlmacenP.innerHTML = `<option value="">Cargando...</option>`;
    selAlmacen.innerHTML = `<option value="">Seleccione</option>`;
    btnPreview.disabled = true;
    resetAll();

    const r = await fetch(API_FILTROS + "&empresa=" + encodeURIComponent(cve_cia));
    const j = await r.json();
    if (!j.ok){
      selAlmacenP.innerHTML = `<option value="">Error</option>`;
      setMsg(j.error || 'Error al cargar almacenes padre', false);
      return;
    }
    const alps = j.almacenes || [];
    selAlmacenP.innerHTML = `<option value="">Seleccione</option>` +
      alps.map(a => `<option value="${a.idp}">${(a.cve_almac||'')} - ${(a.nombre||'')}</option>`).join('');
    selAlmacenP.disabled = false;
    setMsg("Empresa cargada. Selecciona Almacén Padre.", true);
  }

  async function loadZonas(almacenp_id){
    selAlmacen.disabled = true;
    selAlmacen.innerHTML = `<option value="">Cargando...</option>`;
    btnPreview.disabled = true;
    resetAll();

    const r = await fetch(API_ZONAS + "&almacenp_id=" + encodeURIComponent(almacenp_id));
    const j = await r.json();
    const data = j.data || [];
    selAlmacen.innerHTML = `<option value="">Seleccione</option>` +
      data.map(z => `<option value="${z.cve_almac}">${(z.clave_almacen||'')} - ${(z.des_almac||'')}</option>`).join('');

    selAlmacen.disabled = false;
    setMsg("Almacén Padre cargado. Selecciona Almacén Operativo.", true);
  }

  function canPreview(){
    return !!selAlmacen.value;
  }

  async function doPreview(){
    if (!canPreview()){
      setMsg("Selecciona Almacén Operativo (c_almacen).", false);
      return;
    }

    btnPreview.disabled = true;
    btnSave.disabled = true;
    setMsg("Generando previsualización...", true);

    const fd = new FormData();
    fd.append('action','preview');
    fd.append('cve_almac', selAlmacen.value);

    fd.append('pasillo', document.getElementById('pasillo').value);
    fd.append('rack', document.getElementById('rack').value);

    fd.append('nivel_desde', document.getElementById('nivel_desde').value);
    fd.append('nivel_hasta', document.getElementById('nivel_hasta').value);

    fd.append('sec_desde', document.getElementById('sec_desde').value);
    fd.append('sec_hasta', document.getElementById('sec_hasta').value);

    fd.append('pos_desde', document.getElementById('pos_desde').value);
    fd.append('pos_hasta', document.getElementById('pos_hasta').value);

    fd.append('activo', document.getElementById('activo').value);

    if (document.getElementById('picking').checked) fd.append('picking','1');
    if (document.getElementById('ptl').checked)     fd.append('ptl','1');
    if (document.getElementById('mixto').checked)   fd.append('mixto','1');
    if (document.getElementById('prod').checked)    fd.append('prod','1');
    if (document.getElementById('piso').checked)    fd.append('piso','1');
    if (document.getElementById('reab').checked)    fd.append('reab','1');

    const r = await fetch(API_WIZARD, { method:'POST', body:fd });
    const j = await r.json();

    if (!j.ok){
      setMsg(j.msg || "Error en previsualización.", false);
      btnPreview.disabled = !canPreview();
      return;
    }

    previewPayload = j.data;

    const s = previewPayload.summary || {total:0,dup:0,niv:0,sec:0,pos:0};
    k_total.textContent = s.total ?? 0;
    k_dup.textContent   = s.dup ?? 0;
    k_niv.textContent   = s.niv ?? 0;
    k_sec.textContent   = s.sec ?? 0;
    k_pos.textContent   = s.pos ?? 0;

    const rows = (previewPayload.rows || []);
    if (!rows.length){
      tbPrev.innerHTML = `<tr><td colspan="12" class="muted">Sin datos.</td></tr>`;
    } else {
      // render (limit visual 500, pero payload completo se guarda)
      const lim = Math.min(rows.length, 500);
      tbPrev.innerHTML = rows.slice(0,lim).map(r => `
        <tr>
          <td>${r.CodigoCSD||''}</td>
          <td>${r.cve_pasillo||''}</td>
          <td>${r.cve_rack||''}</td>
          <td>${r.cve_nivel||''}</td>
          <td>${r.Seccion||''}</td>
          <td>${r.Ubicacion||''}</td>
          <td>${r.picking||''}</td>
          <td>${r.Ptl||''}</td>
          <td>${r.AcomodoMixto||''}</td>
          <td>${r.AreaProduccion||''}</td>
          <td>${r.AreaStagging||''}</td>
          <td>${r.Reabasto||''}</td>
        </tr>
      `).join('') + (rows.length>lim ? `<tr><td colspan="12" class="muted">Mostrando ${lim} de ${rows.length} (se guardan todos).</td></tr>` : '');
    }

    btnPreview.disabled = !canPreview();
    btnSave.disabled = false;

    setMsg(`Previsualización OK: ${s.total} ubicaciones. Duplicados detectados: ${s.dup}.`, true);
  }

  async function doSave(){
    if (!previewPayload || !previewPayload.rows || !previewPayload.rows.length){
      setMsg("No hay previsualización para guardar.", false);
      return;
    }

    btnSave.disabled = true;
    btnPreview.disabled = true;
    setMsg("Guardando ubicaciones (transacción)...", true);

    const fd = new FormData();
    fd.append('action','save');
    fd.append('payload', JSON.stringify(previewPayload));

    const r = await fetch(API_WIZARD, { method:'POST', body:fd });
    const j = await r.json();

    if (!j.ok){
      setMsg(j.msg || "Error al guardar.", false);
      btnPreview.disabled = !canPreview();
      btnSave.disabled = false;
      return;
    }

    setMsg(j.msg || "Guardado completado.", true);
    // reset para siguiente corrida
    resetAll();
    btnPreview.disabled = !canPreview();
  }

  // eventos
  selEmpresa.addEventListener('change', () => {
    const v = selEmpresa.value;
    selAlmacenP.innerHTML = `<option value="">Seleccione</option>`;
    selAlmacen.innerHTML  = `<option value="">Seleccione</option>`;
    selAlmacenP.disabled = true;
    selAlmacen.disabled = true;
    btnPreview.disabled = true;
    resetAll();
    if (v) loadAlmacenP(v);
    else setMsg("Selecciona empresa.", true);
  });

  selAlmacenP.addEventListener('change', () => {
    const v = selAlmacenP.value;
    selAlmacen.innerHTML = `<option value="">Seleccione</option>`;
    selAlmacen.disabled = true;
    btnPreview.disabled = true;
    resetAll();
    if (v) loadZonas(v);
    else setMsg("Selecciona almacén padre.", true);
  });

  selAlmacen.addEventListener('change', () => {
    resetAll();
    btnPreview.disabled = !canPreview();
    setMsg(canPreview() ? "Listo. Genera previsualización." : "Selecciona almacén operativo.", true);
  });

  btnPreview.addEventListener('click', (e) => { e.preventDefault(); doPreview(); });
  btnSave.addEventListener('click', (e) => { e.preventDefault(); doSave(); });

  // init
  loadEmpresas().then(() => setMsg("Selecciona empresa para iniciar.", true));
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
