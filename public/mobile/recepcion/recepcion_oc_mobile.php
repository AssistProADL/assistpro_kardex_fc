<?php
// /public/mobile/recepcion/recepcion_oc_mobile.php
?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Recepción OC (OCN) · Mobile</title>

  <!-- RF CSS (mismo patrón que pick_to_lp) -->
  <link rel="stylesheet" href="../css/rf.css?v=1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    .wrap{max-width:420px;margin:18px auto;padding:0 12px}
    .card{background:#fff;border-radius:18px;box-shadow:0 10px 30px rgba(0,0,0,10);overflow:hidden}
    .hdr{display:flex;align-items:center;gap:10px;padding:16px 16px 8px}
    .logoMini{width:34px;height:34px;border-radius:10px;display:grid;place-items:center;background:#000f9f;color:#fff;font-weight:900}
    .ttl{font-size:16px;font-weight:900;margin:0;line-height:1.1}
    .sub{font-size:12px;color:#667;margin:2px 0 0}
    .badge{margin-left:auto;font-size:12px;font-weight:800;background:#eef2ff;color:#1e3a8a;padding:6px 10px;border-radius:999px}
    .badge2{margin-left:6px;font-size:12px;font-weight:800;background:#ecfeff;color:#155e75;padding:6px 10px;border-radius:999px}
    .body{padding:0 16px 16px}

    .tabs{display:flex;gap:8px;margin:10px 0 10px}
    .tab{flex:1;border:1px solid #e7eaf1;border-radius:12px;padding:10px 10px;font-weight:900;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px}
    .tab.on{background:#0b1220;color:#fff;border-color:#0b1220}

    .stepBar{display:flex;gap:8px;margin:10px 0 6px}
    .pill{flex:1;text-align:center;font-size:11px;font-weight:900;padding:8px 8px;border-radius:999px;border:1px solid #e7eaf1;color:#445;background:#fff}
    .pill.on{background:#111827;color:#fff;border-color:#111827}
    .pill.ok{background:#0b4;color:#fff;border-color:#0b4}

    .row{display:flex;gap:10px;margin:10px 0}
    .col{flex:1}
    label{display:block;font-size:11px;color:#556;font-weight:900;margin:0 0 6px}
    input,select,button,textarea{width:100%;padding:12px 12px;border-radius:12px;border:1px solid #e7eaf1;font-weight:900;font-size:14px;outline:none}
    input:focus,select:focus,textarea:focus{border-color:#94a3b8;box-shadow:0 0 0 3px rgba(148,163,184,.20)}
    textarea{min-height:64px;resize:vertical}
    .btn{border:0;background:#000f9f;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:10px}
    .btn2{border:0;background:#0b1220;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:10px}
    .btn3{border:0;background:#0b4;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:10px}
    .btnWarn{border:0;background:#f59e0b;color:#111827;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:10px}
    .btnDanger{border:0;background:#ef4444;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:10px}
    .mini{font-size:12px;padding:10px 10px}

    .msg{margin:10px 0;padding:10px 12px;border-radius:12px;font-weight:900;font-size:12px}
    .ok{background:#dcfce7;color:#065f46;border:1px solid #86efac}
    .err{background:#fee2e2;color:#7f1d1d;border:1px solid #fecaca}
    .info{background:#eff6ff;color:#1e3a8a;border:1px solid #bfdbfe}

    .list{margin:10px 0;display:flex;flex-direction:column;gap:8px}
    .item{border:1px solid #e7eaf1;border-radius:14px;padding:10px 12px}
    .item .t1{font-weight:1000;font-size:13px;display:flex;justify-content:space-between;gap:10px}
    .item .t2{margin-top:4px;color:#667;font-size:12px;font-weight:900;line-height:1.2}
    .item .t3{margin-top:6px;display:flex;gap:8px;flex-wrap:wrap}
    .chip{font-size:11px;font-weight:1000;padding:6px 10px;border-radius:999px;background:#f1f5f9;color:#0f172a;border:1px solid #e2e8f0}
    .chip.dark{background:#0b1220;color:#fff;border-color:#0b1220}
    .chip.blue{background:#eef2ff;color:#1e3a8a;border-color:#c7d2fe}
  </style>
</head>

<body>
<div class="wrap">
  <div class="card">
    <div class="hdr">
      <div class="logoMini">RX</div>
      <div>
        <p class="ttl">Recepción OC (OCN) · Mobile</p>
        <p class="sub">Ciego + Acumulación por Contenedor (LP) + Palletizar opcional</p>
      </div>
      <div class="badge" id="almBadge">ALM: —</div>
      <div class="badge2" id="usrBadge">USR: —</div>
    </div>

    <div class="body">
      <div class="tabs">
        <button class="tab on" id="tabRx"><i class="fa-solid fa-file-invoice"></i>Recepción</button>
        <button class="tab" id="tabHelp"><i class="fa-solid fa-circle-info"></i>Ayuda</button>
      </div>

      <div id="panelRx">
        <div class="stepBar">
          <div class="pill on" id="s1">1 · OC</div>
          <div class="pill" id="s2">2 · Captura</div>
          <div class="pill" id="s3">3 · Cont/Pal</div>
          <div class="pill" id="s4">4 · Cierres</div>
        </div>

        <div id="msgBox" style="display:none" class="msg info"></div>

        <!-- STEP 1: OC -->
        <div id="step1">
          <div class="row">
            <div class="col">
              <label>Proveedor (opcional)</label>
              <select id="proveedor"></select>
            </div>
            <div class="col">
              <label>Buscar OC (folio)</label>
              <input id="qoc" placeholder="Ej: 1356" inputmode="numeric">
            </div>
          </div>

          <div class="row">
            <button class="btn" id="btnBuscar"><i class="fa-solid fa-magnifying-glass"></i>Buscar OCs</button>
          </div>

          <div class="list" id="ocList"></div>
        </div>

        <!-- STEP 2/3/4: Captura -->
        <div id="step2" style="display:none">
          <div class="msg info" id="ocSelBox">OC seleccionada: —</div>

          <div class="row">
            <div class="col">
              <label>LP Contenedor (pregenerado)</label>
              <input id="lpCont" placeholder="Escanea / escribe LP contenedor">
            </div>
            <div class="col">
              <label>LP Pallet (tarima) · opcional</label>
              <input id="lpPallet" placeholder="Si vas a palletizar completo">
            </div>
          </div>

          <div class="row">
            <div class="col">
              <label>Ubicación Origen (Recepción)</label>
              <select id="ubOri"></select>
            </div>
            <div class="col">
              <label>Ubicación Destino (Tarima)</label>
              <select id="ubDes"></select>
            </div>
          </div>

          <div class="row">
            <div class="col">
              <label>SKU / Clave artículo</label>
              <input id="sku" placeholder="Escanea o teclea" autocomplete="off">
            </div>
            <div class="col">
              <label>Cantidad</label>
              <input id="qty" placeholder="0" inputmode="decimal">
            </div>
          </div>

          <div class="row">
            <div class="col">
              <label>Nivel Origen</label>
              <select id="nivel">
                <option value="PIEZA">PIEZA</option>
                <option value="CAJA">CAJA</option>
              </select>
            </div>
            <div class="col">
              <label>Lote (si aplica)</label>
              <input id="lote" placeholder="Opcional">
            </div>
          </div>

          <div class="row">
            <button class="btn3" id="btnAdd"><i class="fa-solid fa-plus"></i>Agregar a contenedor</button>
          </div>

          <div class="msg info">
            <b>Ciego:</b> aquí no se muestran cantidades esperadas/pendientes. Se captura “lo real” y se audita contra OC en backoffice.
          </div>

          <div class="list" id="capList"></div>

          <div class="row">
            <button class="btn2" id="btnCerrarCont"><i class="fa-solid fa-box"></i>Cerrar Contenedor</button>
          </div>

          <div class="row">
            <button class="btnWarn" id="btnPalletizar"><i class="fa-solid fa-dolly"></i>Palletizar Contenedor → Pallet</button>
          </div>

          <div class="row">
            <button class="btnDanger" id="btnCerrarPallet"><i class="fa-solid fa-circle-stop"></i>Cerrar Pallet (reiniciar ciclo)</button>
          </div>

          <div class="row">
            <button class="btn mini" id="btnVolver"><i class="fa-solid fa-arrow-left"></i>Volver a OCs</button>
          </div>
        </div>
      </div>

      <div id="panelHelp" style="display:none">
        <div class="msg info">
          <b>Operación recomendada:</b><br>
          1) Selecciona OC (proveedor o folio).<br>
          2) Selecciona/escanea <b>LP Contenedor</b>.<br>
          3) Captura SKUs y cantidades (ciego).<br>
          4) <b>Cierra contenedor</b> → ahí impacta inventario.<br>
          5) Si aplica, palletiza a <b>LP Pallet</b>.<br>
          6) Cierra pallet y repite.
        </div>
      </div>

    </div>
  </div>
</div>

<script>
(function(){
  // =========================
  // Gate de sesión móvil
  // =========================
  const usuario = (localStorage.getItem('mobile_user') || '').trim();
  const cve_almac = (localStorage.getItem('mobile_almacen') || '').trim();
  if(!usuario || !cve_almac){
    window.location.href = '../index.html';
    return;
  }
  document.getElementById('almBadge').textContent = 'ALM: ' + cve_almac;
  document.getElementById('usrBadge').textContent = 'USR: ' + usuario;

  // =========================
  // Endpoints (desde /public/mobile/recepcion/)
  // =========================
  const API_CAT   = '../../api/recepcion/recepcion_catalogos_api.php';
  const API_OC    = '../../api/recepcion/recepcion_oc_api.php';
  const API_RX    = '../../api/recepcion/recepcion_api.php';
  const API_PAL   = '../../api/recepcion/api_palletizar.php';

  // =========================
  // State
  // =========================
  let ocSel = null; // {id_oc, folio, proveedor_id, proveedor_nombre}
  let zonas = [];   // [{id, clave, nombre}]
  let capturas = []; // líneas acumuladas para el contenedor actual

  const $ = (id)=>document.getElementById(id);

  function setMsg(type, text){
    const box = $('msgBox');
    box.className = 'msg ' + (type || 'info');
    box.textContent = text;
    box.style.display = 'block';
    setTimeout(()=>{ box.style.display='none'; }, 3500);
  }

  function setStep(n){
    const pills = [$('s1'),$('s2'),$('s3'),$('s4')];
    pills.forEach((p,i)=>{
      p.classList.remove('on'); p.classList.remove('ok');
      if(i < n-1) p.classList.add('ok');
      if(i === n-1) p.classList.add('on');
    });
  }

  function renderCapturas(){
    const list = $('capList');
    list.innerHTML = '';
    if(capturas.length===0){
      list.innerHTML = '<div class="msg info">Sin capturas en este contenedor.</div>';
      return;
    }
    capturas.forEach((x,idx)=>{
      const div = document.createElement('div');
      div.className = 'item';
      div.innerHTML = `
        <div class="t1">
          <span>${x.cve_articulo}</span>
          <span>${x.cantidad}</span>
        </div>
        <div class="t2">${x.descripcion || ''}</div>
        <div class="t3">
          <span class="chip blue">${x.nivel_origen}</span>
          ${x.cve_lote ? `<span class="chip">Lote: ${x.cve_lote}</span>`:''}
          <span class="chip">Cont: ${$('lpCont').value || '—'}</span>
        </div>
      `;
      list.appendChild(div);
    });
  }

  function keyDraft(){
    const lpC = ($('lpCont').value || '').trim();
    const idOC = ocSel ? ocSel.id_oc : '0';
    return `rx_ocn_${cve_almac}_${usuario}_${idOC}_${lpC || 'NO_CONT'}`;
  }

  function saveDraft(){
    try{
      const lpC = ($('lpCont').value || '').trim();
      if(!ocSel) return;
      const payload = {
        protocolo: 'OCN',
        almacen: cve_almac,
        usuario,
        oc: ocSel,
        lp_contenedor: lpC,
        lp_pallet: ($('lpPallet').value||'').trim(),
        ub_ori: $('ubOri').value,
        ub_des: $('ubDes').value,
        capturas,
        updated_at: new Date().toISOString()
      };
      localStorage.setItem(keyDraft(), JSON.stringify(payload));
    }catch(e){}
  }

  function clearDraft(){
    try{ localStorage.removeItem(keyDraft()); }catch(e){}
  }

  async function jget(url){
    const r = await fetch(url, {credentials:'same-origin'});
    const j = await r.json();
    if(!r.ok) throw new Error(j.error || 'Error');
    return j;
  }
  async function jpost(url, body){
    const r = await fetch(url, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify(body),
      credentials:'same-origin'
    });
    const j = await r.json();
    if(!r.ok) throw new Error(j.error || 'Error');
    return j;
  }

  // =========================
  // Cargar catálogos
  // =========================
  async function loadCatalogos(){
    const j = await jget(`${API_CAT}?almacen=${encodeURIComponent(cve_almac)}`);
    const prov = j.proveedores || [];
    zonas = j.zonas || [];

    // Proveedores
    const sel = $('proveedor');
    sel.innerHTML = `<option value="">(Todos)</option>`;
    prov.forEach(p=>{
      const opt = document.createElement('option');
      opt.value = p.id;
      opt.textContent = `${p.clave} · ${p.nombre}`;
      sel.appendChild(opt);
    });

    // Zonas origen/destino
    const o1 = $('ubOri'); const o2 = $('ubDes');
    o1.innerHTML = ''; o2.innerHTML = '';
    zonas.forEach(z=>{
      const opt1 = document.createElement('option');
      opt1.value = z.id;
      opt1.textContent = `${z.clave} · ${z.nombre}`;
      o1.appendChild(opt1);

      const opt2 = document.createElement('option');
      opt2.value = z.id;
      opt2.textContent = `${z.clave} · ${z.nombre}`;
      o2.appendChild(opt2);
    });
  }

  // =========================
  // Buscar OCs
  // =========================
  async function buscarOCs(){
    const prov = $('proveedor').value;
    const q = ($('qoc').value||'').trim();
    if(!prov && !q){
      setMsg('info','Captura proveedor o folio OC para acotar.');
      return;
    }
    const url = `${API_OC}?almacen=${encodeURIComponent(cve_almac)}`
      + (prov ? `&proveedor=${encodeURIComponent(prov)}` : '')
      + (q ? `&q=${encodeURIComponent(q)}` : '');

    const j = await jget(url);
    renderOCs(j.data || []);
  }

  function renderOCs(rows){
    const list = $('ocList');
    list.innerHTML = '';
    if(rows.length===0){
      list.innerHTML = '<div class="msg info">Sin OCs con ese criterio.</div>';
      return;
    }
    rows.forEach(r=>{
      const div = document.createElement('div');
      div.className = 'item';
      const fol = r.Fol_Folio ?? r.folio ?? r.id_oc ?? '';
      const prov = r.Nombre ?? r.proveedor ?? '';
      const st = r.status ?? '';
      div.innerHTML = `
        <div class="t1">
          <span>OC: ${fol}</span>
          <span class="chip dark">${st || '—'}</span>
        </div>
        <div class="t2">${prov}</div>
        <div class="t3">
          <span class="chip">Protocolo: OCN</span>
          <span class="chip">Alm: ${cve_almac}</span>
        </div>
      `;
      div.addEventListener('click', ()=>{
        ocSel = {
          id_oc: parseInt(r.IDy_OC ?? r.id_oc ?? fol, 10) || parseInt(fol,10) || 0,
          folio: String(fol),
          proveedor_id: r.IDy_Proveedor ?? r.proveedor_id ?? null,
          proveedor_nombre: String(prov || '')
        };
        $('ocSelBox').innerHTML = `<b>OC seleccionada:</b> ${ocSel.folio} · ${ocSel.proveedor_nombre}`;
        $('step1').style.display = 'none';
        $('step2').style.display = 'block';
        setStep(2);
        setMsg('ok','OC seleccionada. Captura LP contenedor y empieza a recibir.');
      });
      list.appendChild(div);
    });
  }

  // =========================
  // Agregar captura
  // =========================
  $('btnAdd').addEventListener('click', ()=>{
    const lpC = ($('lpCont').value||'').trim();
    if(!ocSel) return setMsg('err','Selecciona una OC primero.');
    if(!lpC) return setMsg('err','Falta LP Contenedor.');
    const art = ($('sku').value||'').trim();
    const cant = parseFloat(($('qty').value||'0').replace(',','.')) || 0;
    const nivel = $('nivel').value;
    const lote = ($('lote').value||'').trim();

    if(!art) return setMsg('err','Falta SKU.');
    if(cant<=0) return setMsg('err','Cantidad inválida.');

    // Consolidar: mismo art + nivel + lote
    const key = `${art}__${nivel}__${lote}`;
    const found = capturas.find(x => `${x.cve_articulo}__${x.nivel_origen}__${x.cve_lote||''}` === key);
    if(found){
      found.cantidad = Math.round((found.cantidad + cant) * 1000)/1000;
    }else{
      capturas.push({
        cve_articulo: art,
        cantidad: cant,
        nivel_origen: nivel,
        cve_lote: lote || null,
        descripcion: '' // (ciego) opcional
      });
    }

    $('sku').value=''; $('qty').value=''; $('lote').value='';
    $('sku').focus();
    renderCapturas();
    saveDraft();
    setStep(3);
    setMsg('ok','Agregado al contenedor.');
  });

  // =========================
  // Cerrar contenedor: impacta inventario (tu regla)
  // =========================
  $('btnCerrarCont').addEventListener('click', async ()=>{
    try{
      const lpC = ($('lpCont').value||'').trim();
      if(!ocSel) return setMsg('err','Selecciona una OC.');
      if(!lpC) return setMsg('err','Falta LP Contenedor.');
      if(capturas.length<1) return setMsg('err','No hay capturas para cerrar.');

      // Payload: ajusta action según tu recepcion_api.php
      const payload = {
        action: 'guardar_recepcion',
        tipo: 'OC',               // tu backend mapeará a OCN
        protocolo: 'OCN',         // forzado
        empresa_id: 1,            // si lo usas, puedes traerlo de localStorage
        usuario_operador: usuario,
        almacen: parseInt(cve_almac,10),
        id_oc: ocSel.id_oc,
        folio_oc: ocSel.folio,
        contenedor_lp: lpC,
        detalle: capturas
      };

      const j = await jpost(API_RX, payload);
      setMsg('ok', `Contenedor cerrado. Ref: ${(j.referencia||j.id_recepcion||'OK')}`);

      // al cerrar, ya hay stock en origen; dejamos capturas en 0 para siguiente contenedor
      capturas = [];
      renderCapturas();
      clearDraft();
      setStep(4);
    }catch(e){
      setMsg('err', e.message || 'Error al cerrar contenedor');
    }
  });

  // =========================
  // Palletizar contenedor -> pallet
  // Nota: asume stock en ubicación origen ya existe (por cierre contenedor).
  // =========================
  $('btnPalletizar').addEventListener('click', async ()=>{
    try{
      const lpPal = ($('lpPallet').value||'').trim();
      if(!lpPal) return setMsg('err','Falta LP Pallet (tarima).');
      if(!ocSel) return setMsg('err','Selecciona una OC.');

      // Para palletizar, tomamos "la última captura cerrada" idealmente desde server.
      // MVP: usa capturas actuales si aún no se cerró (pero recomendado cerrar contenedor primero).
      if(capturas.length>0){
        return setMsg('info','Recomendado: primero Cierra Contenedor para impactar inventario, luego palletiza.');
      }

      const idy_origen = parseInt($('ubOri').value,10);
      const idy_destino= parseInt($('ubDes').value,10);

      const zOri = zonas.find(z=>String(z.id)===String(idy_origen));
      const zDes = zonas.find(z=>String(z.id)===String(idy_destino));

      // Aquí necesitas enviar las líneas a palletizar.
      // MVP: el operador vuelve a capturar las líneas del contenedor a pallet (o usamos un endpoint para "cargar por contenedor").
      // Como todavía no tenemos ese endpoint, hacemos modo manual rápido:
      const art = prompt('SKU a palletizar (manual rápido):');
      if(!art) return;
      const qty = parseFloat((prompt('Cantidad:')||'0').replace(',','.'))||0;
      if(qty<=0) return;

      const nivel = prompt('Nivel Origen PIEZA/CAJA:','PIEZA') || 'PIEZA';
      const lote = prompt('Lote (opcional):','') || '';

      const payload = {
        empresa_id: 1,
        usuario: usuario,
        cve_almac: parseInt(cve_almac,10),
        idy_ubica_origen: idy_origen,
        idy_ubica_destino: idy_destino,
        bl_origen: (zOri ? zOri.nombre : 'RECEPCION'),
        bl_destino:(zDes ? zDes.nombre : 'TARIMA'),
        lp_tarima: lpPal,
        contenedor_lp: ($('lpCont').value||'').trim(),
        referencia: `PAL-OCN-${ocSel.folio}-${Date.now()}`,
        detalle: [{
          cve_articulo: art.trim(),
          cantidad: qty,
          nivel_origen: (nivel||'PIEZA').toUpperCase(),
          cve_lote: lote.trim() || null
        }]
      };

      const j = await jpost(API_PAL, payload);
      setMsg('ok', `Palletizado OK · ${j.lp_tarima} · líneas: ${j.lineas}`);
    }catch(e){
      setMsg('err', e.message || 'Error al palletizar');
    }
  });

  // =========================
  // Cerrar pallet (ciclo)
  // =========================
  $('btnCerrarPallet').addEventListener('click', ()=>{
    $('lpCont').value='';
    $('lpPallet').value='';
    capturas = [];
    renderCapturas();
    setStep(2);
    setMsg('ok','Pallet cerrado. Listo para siguiente ciclo.');
  });

  $('btnVolver').addEventListener('click', ()=>{
    ocSel = null;
    capturas = [];
    renderCapturas();
    $('step2').style.display='none';
    $('step1').style.display='block';
    setStep(1);
  });

  // Tabs
  $('tabRx').addEventListener('click', ()=>{
    $('tabRx').classList.add('on'); $('tabHelp').classList.remove('on');
    $('panelRx').style.display='block'; $('panelHelp').style.display='none';
  });
  $('tabHelp').addEventListener('click', ()=>{
    $('tabHelp').classList.add('on'); $('tabRx').classList.remove('on');
    $('panelHelp').style.display='block'; $('panelRx').style.display='none';
  });

  // Buscar
  $('btnBuscar').addEventListener('click', ()=>buscarOCs());

  // Init
  (async ()=>{
    try{
      await loadCatalogos();
      setMsg('info','Listo. Selecciona proveedor o captura OC para buscar.');
    }catch(e){
      setMsg('err', e.message || 'No pude cargar catálogos');
    }
  })();

})();
</script>
</body>
</html>
