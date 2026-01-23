<?php
// public/mobile/movimientos/pick_to_lp.php
?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pick to LP · AssistPro ER</title>

  <link rel="stylesheet" href="../consultas/css/rf.css?v=1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    .wrap{max-width:420px;margin:18px auto;padding:0 12px}
    .card{background:#fff;border-radius:18px;box-shadow:0 10px 30px rgba(0,0,0,10);overflow:hidden}
    .hdr{display:flex;align-items:center;gap:10px;padding:16px 16px 8px}
    .logoMini{width:34px;height:34px;border-radius:10px;display:grid;place-items:center;background:#0b4;color:#fff;font-weight:900}
    .ttl{font-size:16px;font-weight:900;margin:0;line-height:1.1}
    .sub{font-size:12px;color:#667;margin:2px 0 0}
    .badge{margin-left:auto;font-size:12px;font-weight:800;background:#eef2ff;color:#1e3a8a;padding:6px 10px;border-radius:999px}
    .body{padding:0 16px 16px}

    .tabs{display:flex;gap:8px;margin:10px 0 10px}
    .tab{flex:1;border:1px solid #e7eaf1;border-radius:12px;padding:10px 10px;font-weight:900;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px}
    .tab.on{background:#0b1220;color:#fff;border-color:#0b1220}

    .stepBar{display:flex;gap:8px;margin:10px 0 6px}
    .pill{flex:1;text-align:center;font-size:11px;font-weight:900;padding:8px 8px;border-radius:999px;border:1px solid #e7eaf1;color:#445;background:#fff}
    .pill.on{background:#111827;color:#fff;border-color:#111827}
    .pill.ok{background:#0b4;color:#fff;border-color:#0b4}

    .inpRow{display:flex;gap:10px;align-items:center;margin-top:10px}
    .inpIcon{width:36px;height:36px;border-radius:12px;background:#f2f4f7;display:grid;place-items:center;font-weight:900}
    input[type="text"], input[type="number"]{flex:1;width:100%;padding:12px 12px;border:1px solid #dde0e6;border-radius:12px;outline:none;font-size:14px}
    .hint{font-size:12px;color:#667;margin:8px 2px 0}

    .msg{margin-top:10px;padding:10px 12px;border-radius:12px;font-weight:800;font-size:12px;display:none}
    .msg.err{background:#ffe9ea;border:1px solid #ffc2c6;color:#991b1b}
    .msg.ok{background:#ecfdf5;border:1px solid #86efac;color:#065f46}

    .sel{display:none;margin-top:10px;background:#0b1220;color:#fff;border-radius:14px;padding:12px}
    .sel .sku{font-weight:900}
    .sel .des{font-size:12px;opacity:.85;margin-top:2px}

    .kpis{display:none;grid-template-columns:1fr 1fr;gap:10px;margin-top:12px}
    .kpi{background:#0b1220;color:#fff;border-radius:14px;padding:10px 12px}
    .kpi .n{font-size:18px;font-weight:900}
    .kpi .t{font-size:11px;opacity:.8;margin-top:2px}

    .tbl{display:none;margin-top:12px;border:1px solid #e7eaf1;border-radius:14px;overflow:hidden}
    .tblHead,.tblRow{display:grid;grid-template-columns:1.2fr 1.2fr .8fr .8fr .8fr;gap:8px;padding:10px 10px}
    .tblHead{background:#f7f9fc;font-size:11px;font-weight:900;color:#445}
    .tblRow{font-size:12px;border-top:1px solid #eef1f6;align-items:start}
    .muted{color:#64748b}

    .miniBtns{display:flex;gap:8px;margin-top:10px}
    .miniBtn{flex:1;border:1px solid #e7eaf1;border-radius:12px;padding:10px 10px;font-weight:900;background:#fff;cursor:pointer}
    .miniBtn.danger{border-color:#fecaca;background:#fff5f5;color:#991b1b}
    .miniBtn.dark{background:#111827;color:#fff;border-color:#111827}

    .btns{display:flex;gap:10px;margin-top:14px}
    .btn{flex:1;border:0;border-radius:14px;padding:12px 12px;font-weight:900;cursor:pointer}
    .btnDark{background:#111827;color:#fff}
    .btnBlue{background:#1d4ed8;color:#fff}
    .btnGreen{background:#0b4;color:#fff}
    .btnGray{background:#e5e7eb;color:#111827}

    .ft{text-align:center;font-size:11px;color:#667;padding:10px 0 14px}
    .right{float:right}
    .chip{display:inline-block;font-size:11px;font-weight:900;padding:4px 8px;border-radius:999px;background:rgba(255,255,255,.12)}
    .chipW{background:#eef2ff;color:#1e3a8a}
  </style>
</head>

<body>
<div class="wrap">
  <div class="card">
    <div class="hdr">
      <div class="logoMini">ER</div>
      <div>
        <p class="ttl">Pick to LP</p>
        <p class="sub">Consolidación controlada: Origen → LP Destino</p>
      </div>
      <div class="badge" id="almBadge">ALM: —</div>
    </div>

    <div class="body">
      <div class="tabs">
        <button class="tab on" id="tabPick"><i class="fa-solid fa-arrows-to-dot"></i> Pick to LP</button>
        <button class="tab" id="tabHelp"><i class="fa-solid fa-circle-info"></i> Ayuda</button>
      </div>

      <div class="stepBar">
        <div class="pill on" id="p1">1 · LP Destino</div>
        <div class="pill" id="p2">2 · BL Destino</div>
        <div class="pill" id="p3">3 · Origen</div>
        <div class="pill" id="p4">4 · Ejecutar</div>
      </div>

      <div class="hint" id="hintTxt">
        Escanea primero el <b>LP destino</b> (debe tener <b>Utilizado='N'</b>).
      </div>

      <div class="msg err" id="msgErr"></div>
      <div class="msg ok" id="msgOk"></div>

      <div class="sel" id="selBox">
        <div class="sku" id="selTitle">—</div>
        <div class="des" id="selDesc">—</div>
        <div style="margin-top:8px;">
          <span class="chip" id="selChip">PICK_TO_LP</span>
          <span class="chip chipW right" id="selCount">0 movimientos</span>
        </div>
      </div>

      <div class="kpis" id="kpis">
        <div class="kpi">
          <div class="n" id="kMov">0</div>
          <div class="t">Movimientos</div>
        </div>
        <div class="kpi">
          <div class="n" id="kQty">0.0000</div>
          <div class="t">Cantidad Total</div>
        </div>
      </div>

      <div class="inpRow" id="rowScan">
        <div class="inpIcon"><i class="fa-solid fa-barcode"></i></div>
        <input type="text" id="scan" placeholder="Escanear..." autocomplete="off" />
      </div>

      <div class="inpRow" id="rowQty" style="display:none;">
        <div class="inpIcon"><i class="fa-solid fa-hashtag"></i></div>
        <input type="number" id="qty" placeholder="Cantidad" min="0" step="0.0001" />
      </div>

      <div class="miniBtns" id="rowActions" style="display:none;">
        <button class="miniBtn dark" id="btnAdd"><i class="fa-solid fa-plus"></i> Agregar</button>
        <button class="miniBtn danger" id="btnCancelAdd"><i class="fa-solid fa-xmark"></i> Cancelar</button>
      </div>

      <div class="tbl" id="tbl">
        <div class="tblHead">
          <div>Origen (BL)</div>
          <div>LP Origen</div>
          <div>SKU</div>
          <div>Tipo</div>
          <div>Cant</div>
        </div>
        <div id="rows"></div>
      </div>

      <div class="btns">
        <button class="btn btnDark" onclick="history.back()"><i class="fa-solid fa-arrow-left"></i> Volver</button>
        <button class="btn btnBlue" onclick="location.href='../index.html'"><i class="fa-solid fa-house"></i> Menú</button>
      </div>

      <div class="btns" style="margin-top:10px">
        <button class="btn btnGray" id="btnReset"><i class="fa-solid fa-rotate-left"></i> Reiniciar</button>
        <button class="btn btnGreen" id="btnExecute" disabled><i class="fa-solid fa-bolt"></i> Ejecutar Pick</button>
      </div>

      <div class="ft">Powered by <b>Adventech Logística</b></div>
    </div>
  </div>
</div>

<script>
const API_PICK   = '../../api/pick_to_lp/api_pick_to_lp_ejecutar.php';
const API_LP_VAL = '../../api/license_plates/api_validar_lp.php';

let step = 1;
let ctx = {
  almacen: '',
  lp_destino: '',
  bl_destino: '',
  origen_bl: '',
  origen_lp: '',
  sku: '',
  tipo: 'PIEZA',
  cantidad: 0,
  movimientos: []
};

try{
  const alm = localStorage.getItem('mobile_almacen') || localStorage.getItem('alm_clave') || '';
  if(alm) { ctx.almacen = alm; document.getElementById('almBadge').textContent = 'ALM: ' + alm; }
}catch(e){}

const QTY_FMT = new Intl.NumberFormat('en-US', { minimumFractionDigits: 4, maximumFractionDigits: 4 });
function toNum(v){
  if(v === null || v === undefined) return 0;
  if(typeof v === 'number') return Number.isFinite(v) ? v : 0;
  const s = String(v).trim(); if(!s) return 0;
  const cleaned = s.replaceAll(',', '');
  const n = parseFloat(cleaned);
  return Number.isFinite(n) ? n : 0;
}
function fmtQty(v){ return QTY_FMT.format(toNum(v)); }
function escapeHtml(s){
  return String(s ?? '')
    .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
    .replaceAll('"','&quot;').replaceAll("'","&#039;");
}
async function fetchJson(url, opts={}){
  const r = await fetch(url, { cache:'no-store', ...opts });
  const txt = await r.text();
  try { return JSON.parse(txt); } catch(e){ return { ok:false, _raw: txt.substring(0,240) }; }
}

function setStep(n){
  step = n;
  ['p1','p2','p3','p4'].forEach((id, idx)=>{
    const el = document.getElementById(id);
    el.classList.remove('on'); el.classList.remove('ok');
    if(idx+1 === step) el.classList.add('on');
  });

  if(ctx.lp_destino) document.getElementById('p1').classList.add('ok');
  if(ctx.bl_destino) document.getElementById('p2').classList.add('ok');
  if(ctx.movimientos.length) document.getElementById('p3').classList.add('ok');

  const hint = document.getElementById('hintTxt');
  if(step===1) hint.innerHTML = 'Escanea el <b>LP destino</b> (debe tener <b>Utilizado=\'N\'</b> y estar activo).';
  if(step===2) hint.innerHTML = 'Escanea el <b>BL destino</b> donde quedará el LP.';
  if(step===3) hint.innerHTML = 'Escanea <b>BL origen</b> o <b>LP origen</b>. Después captura <b>SKU</b> y <b>cantidad</b>.';
  if(step===4) hint.innerHTML = 'Valida el resumen y ejecuta. Se generará <b>kardex</b> por movimiento.';

  document.getElementById('rowQty').style.display = (step===3 && ctx.origen_bl && ctx.sku) ? 'flex' : 'none';
  document.getElementById('rowActions').style.display = (step===3 && ctx.origen_bl && ctx.sku) ? 'flex' : 'none';
  document.getElementById('btnExecute').disabled = !(ctx.lp_destino && ctx.bl_destino && ctx.movimientos.length);

  const scan = document.getElementById('scan');
  if(step===1) scan.placeholder = 'Escanear LP destino...';
  if(step===2) scan.placeholder = 'Escanear BL destino...';
  if(step===3){
    if(!ctx.origen_bl && !ctx.origen_lp) scan.placeholder = 'Escanear BL origen o LP origen...';
    else if(!ctx.sku) scan.placeholder = 'Capturar SKU (o escanear código)...';
    else scan.placeholder = 'Cambiar SKU o escanear nuevo origen...';
  }
  if(step===4) scan.placeholder = '—';

  refreshSummary();
}

function showErr(t){ const e=document.getElementById('msgErr'); e.textContent=t; e.style.display='block'; document.getElementById('msgOk').style.display='none'; }
function showOk(t){ const e=document.getElementById('msgOk'); e.textContent=t; e.style.display='block'; document.getElementById('msgErr').style.display='none'; }
function clearMsgs(){ document.getElementById('msgErr').style.display='none'; document.getElementById('msgOk').style.display='none'; }

function refreshSummary(){
  const sel = document.getElementById('selBox');
  const ttl = document.getElementById('selTitle');
  const des = document.getElementById('selDesc');
  const cnt = document.getElementById('selCount');

  let lines = [];
  if(ctx.lp_destino) lines.push(`LP destino: ${ctx.lp_destino}`);
  if(ctx.bl_destino) lines.push(`BL destino: ${ctx.bl_destino}`);
  if(ctx.origen_bl || ctx.origen_lp){
    lines.push(`Origen: ${ctx.origen_bl || '—'}${ctx.origen_lp ? ' · ' + ctx.origen_lp : ''}`);
  }

  ttl.textContent = ctx.lp_destino ? 'Pick to LP' : '—';
  des.textContent = lines.length ? lines.join('  |  ') : '—';
  cnt.textContent = `${ctx.movimientos.length} movimientos`;

  sel.style.display = (ctx.lp_destino || ctx.bl_destino || ctx.movimientos.length) ? 'block' : 'none';

  const kpis = document.getElementById('kpis');
  const tbl  = document.getElementById('tbl');
  if(ctx.movimientos.length){
    kpis.style.display='grid';
    tbl.style.display='block';
  }else{
    kpis.style.display='none';
    tbl.style.display='none';
  }

  document.getElementById('kMov').textContent = ctx.movimientos.length.toLocaleString();
  let total = 0;
  ctx.movimientos.forEach(m=> total += toNum(m.cantidad));
  document.getElementById('kQty').textContent = fmtQty(total);

  const box = document.getElementById('rows');
  box.innerHTML = '';
  ctx.movimientos.forEach((m, idx)=>{
    const row = document.createElement('div');
    row.className = 'tblRow';
    row.innerHTML = `
      <div><b>${escapeHtml(m.bl_origen||'—')}</b></div>
      <div class="muted">${escapeHtml(m.lp_origen||'—')}</div>
      <div class="muted">${escapeHtml(m.articulo||'—')}</div>
      <div class="muted">${escapeHtml(m.tipo||'—')}</div>
      <div><b>${fmtQty(m.cantidad)}</b></div>
    `;
    row.style.cursor = 'pointer';
    row.title = 'Tap para eliminar';
    row.addEventListener('click', ()=>{
      ctx.movimientos.splice(idx,1);
      showOk('Movimiento eliminado.');
      setStep(3);
      refreshSummary();
    });
    box.appendChild(row);
  });
}

async function validateLPDestino(lp){
  // Validación OFICIAL por c_charolas.Utilizado (Archivo 8)
  const url = `${API_LP_VAL}?lp=${encodeURIComponent(lp)}&almacen=${encodeURIComponent(ctx.almacen)}`;
  const j = await fetchJson(url);
  if(!j || j.ok===false) return { ok:false, msg:(j?.error || 'No se pudo validar LP.') };

  if(!j.disponible){
    return { ok:false, msg: (j.mensaje || `LP NO disponible. Utilizado=${j.utilizado}`) };
  }
  return { ok:true, msg: j.mensaje || 'LP disponible' };
}

function resetAll(){
  ctx.lp_destino = '';
  ctx.bl_destino = '';
  ctx.origen_bl = '';
  ctx.origen_lp = '';
  ctx.sku = '';
  ctx.tipo = 'PIEZA';
  ctx.cantidad = 0;
  ctx.movimientos = [];
  document.getElementById('scan').value = '';
  document.getElementById('qty').value = '';
  clearMsgs();
  setStep(1);
}

async function executePick(){
  clearMsgs();
  const payload = {
    lp_destino: ctx.lp_destino,
    bl_destino: ctx.bl_destino,
    almacen: ctx.almacen,
    movimientos: ctx.movimientos
  };

  const j = await fetchJson(API_PICK, {
    method:'POST',
    headers:{ 'Content-Type':'application/json' },
    body: JSON.stringify(payload)
  });

  if(!j || j.ok===false){
    showErr(j?.error || j?.mensaje || 'No se pudo ejecutar Pick to LP.');
    return;
  }

  showOk(j?.mensaje || 'Pick to LP ejecutado correctamente.');
  ctx.origen_bl = '';
  ctx.origen_lp = '';
  ctx.sku = '';
  document.getElementById('scan').value = '';
  document.getElementById('qty').value = '';
  ctx.movimientos = [];
  setStep(1); // Nuevo LP destino para siguiente corrida (porque ya quedó Utilizado='S')
}

document.getElementById('btnReset').addEventListener('click', resetAll);
document.getElementById('btnExecute').addEventListener('click', ()=>{
  setStep(4);
  executePick();
});

document.getElementById('btnCancelAdd').addEventListener('click', ()=>{
  ctx.sku = '';
  document.getElementById('qty').value = '';
  document.getElementById('scan').value = '';
  showOk('Captura cancelada.');
  setStep(3);
});

document.getElementById('btnAdd').addEventListener('click', ()=>{
  clearMsgs();
  const q = toNum(document.getElementById('qty').value);
  if(q <= 0){ showErr('Cantidad inválida.'); return; }
  ctx.cantidad = q;

  ctx.movimientos.push({
    bl_origen: ctx.origen_bl || '',
    lp_origen: ctx.origen_lp || '',
    articulo: ctx.sku,
    cantidad: ctx.cantidad,
    tipo: ctx.tipo
  });

  ctx.sku = '';
  ctx.cantidad = 0;
  document.getElementById('qty').value = '';
  document.getElementById('scan').value = '';
  showOk('Movimiento agregado. Tap en una fila para eliminar.');
  setStep(3);
});

document.getElementById('scan').addEventListener('keydown', async (e)=>{
  if(e.key !== 'Enter') return;
  e.preventDefault();
  clearMsgs();

  const v = (document.getElementById('scan').value || '').trim();
  if(!v){ showErr('Escanea un valor.'); return; }

  // STEP 1: LP destino
  if(step===1){
    const res = await validateLPDestino(v);
    if(!res.ok){ showErr(res.msg); return; }
    ctx.lp_destino = v;
    showOk('LP destino validado (Utilizado=N).');
    document.getElementById('scan').value = '';
    setStep(2);
    return;
  }

  // STEP 2: BL destino (sin validador dedicado aún; se acepta input)
  if(step===2){
    ctx.bl_destino = v;
    showOk('BL destino capturado.');
    document.getElementById('scan').value = '';
    setStep(3);
    return;
  }

  // STEP 3: Origen + SKU
  if(step===3){
    if(!ctx.origen_bl && !ctx.origen_lp){
      const up = v.toUpperCase();
      if(up.startsWith('LP') || up.includes('LPW') || up.includes('LPT') || up.includes('LPC')){
        ctx.origen_lp = v;
        ctx.origen_bl = '';
        showOk('LP origen capturado. Ahora captura SKU.');
      }else{
        ctx.origen_bl = v;
        ctx.origen_lp = '';
        showOk('BL origen capturado. Ahora captura SKU.');
      }
      document.getElementById('scan').value = '';
      setStep(3);
      return;
    }

    if(!ctx.sku){
      ctx.sku = v;
      ctx.tipo = ctx.origen_lp ? 'CAJA' : 'PIEZA';
      showOk(`SKU capturado (${ctx.tipo}). Captura cantidad y presiona Agregar.`);
      document.getElementById('scan').value = '';
      setStep(3);
      document.getElementById('qty').focus();
      return;
    }

    ctx.origen_bl = v;
    ctx.origen_lp = '';
    ctx.sku = '';
    document.getElementById('qty').value = '';
    showOk('Origen actualizado. Captura SKU.');
    document.getElementById('scan').value = '';
    setStep(3);
    return;
  }
});

// init
resetAll();

document.getElementById('tabHelp').addEventListener('click', ()=>{
  document.getElementById('tabHelp').classList.add('on');
  document.getElementById('tabPick').classList.remove('on');
  showOk("Reglas: LP destino debe estar Activo y Utilizado='N'. Al ejecutar, se marcará Utilizado='S'.");
  setTimeout(()=>{
    document.getElementById('tabHelp').classList.remove('on');
    document.getElementById('tabPick').classList.add('on');
  }, 900);
});
</script>
</body>
</html>
