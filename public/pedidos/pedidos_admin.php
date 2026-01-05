<?php
// public/pedidos/pedidos_admin.php
 
require_once __DIR__ . '/../bi/_menu_global.php';
?>
<style>
  :root{
    --ap-primary:#0F5AAD;
    --ap-bg:#F5F7FB;
    --ap-card:#FFFFFF;
    --ap-border:#E5E7EB;
    --ap-muted:#6B7280;
    --ap-warn:#B45309;
  }
  body{ background:var(--ap-bg); }
  .ap-wrap{ padding:16px 18px 32px 18px; }
  .ap-title{ font-size:18px; font-weight:800; color:var(--ap-primary); margin:0 0 6px 0; }
  .ap-sub{ font-size:11px; color:var(--ap-muted); margin-bottom:10px; }
  .ap-card{
    background:var(--ap-card);
    border:1px solid var(--ap-border);
    border-radius:12px;
    padding:14px 14px;
    margin-bottom:12px;
  }
  .ap-card h3{ font-size:13px; font-weight:800; margin:0 0 10px 0; color:#111827; }
  label{ font-size:11px; color:var(--ap-muted); margin-bottom:4px; }
  .ap-kpis{ display:grid; grid-template-columns:repeat(12,minmax(0,1fr)); gap:10px; }
  .ap-kpi{ grid-column:span 3; border:1px solid var(--ap-border); border-radius:12px; padding:10px 12px; background:#fff; }
  .ap-kpi .t{ font-size:11px; color:var(--ap-muted); }
  .ap-kpi .v{ font-size:18px; font-weight:900; color:#111827; line-height:1.2; }
  @media(max-width:1100px){ .ap-kpi{ grid-column:span 6; } }
  @media(max-width:700px){ .ap-kpi{ grid-column:span 12; } }

  .ap-grid{ display:grid; grid-template-columns:repeat(12,minmax(0,1fr)); gap:10px 12px; }
  .ap-col-2{ grid-column:span 2; }
  .ap-col-3{ grid-column:span 3; }
  .ap-col-4{ grid-column:span 4; }
  .ap-col-6{ grid-column:span 6; }
  .ap-col-12{ grid-column:span 12; }
  @media(max-width:1100px){
    .ap-col-2,.ap-col-3,.ap-col-4{ grid-column:span 6; }
    .ap-col-6{ grid-column:span 12; }
  }
  @media(max-width:700px){
    .ap-col-2,.ap-col-3,.ap-col-4,.ap-col-6,.ap-col-12{ grid-column:span 12; }
  }

  input,select{
    width:100%;
    padding:7px 8px;
    border:1px solid var(--ap-border);
    border-radius:10px;
    font-size:12px;
    background:#fff;
  }
  .ap-actions{ display:flex; gap:8px; align-items:center; justify-content:flex-end; flex-wrap:wrap; }
  .ap-btn{
    border:0; border-radius:10px; padding:7px 12px;
    font-size:12px; font-weight:800; cursor:pointer;
    display:inline-flex; align-items:center; gap:6px;
  }
  .ap-btn-primary{ background:var(--ap-primary); color:#fff; }
  .ap-btn-light{ background:#EEF2FF; color:#111827; }
  .ap-btn-gray{ background:#E5E7EB; color:#111827; }
  .ap-chipbar{ display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
  .ap-chip{
    border:1px solid var(--ap-border);
    background:#fff;
    border-radius:999px;
    padding:5px 10px;
    font-size:11px;
    font-weight:800;
    cursor:pointer;
  }
  .ap-chip:hover{ border-color:#C7D2FE; }
  .ap-small{ font-size:11px; color:var(--ap-muted); }

  .ap-table-wrap{ width:100%; overflow:auto; border:1px solid var(--ap-border); border-radius:12px; background:#fff; }
  table.ap-table{ width:100%; border-collapse:collapse; font-size:11px; min-width:1200px; }
  .ap-table th,.ap-table td{ padding:7px 8px; border-bottom:1px solid #EEF2F7; white-space:nowrap; }
  .ap-table th{ background:#F9FAFB; color:#6B7280; font-weight:900; position:sticky; top:0; z-index:2; }
  .ap-table tr:hover td{ background:#F3F4F6; }
  .ap-pill{ padding:2px 8px; border-radius:999px; font-weight:900; font-size:10px; display:inline-block; }
  .pill-A{ background:#DCFCE7; color:#166534; }
  .pill-C{ background:#E5E7EB; color:#374151; }
  .pill-X{ background:#FEE2E2; color:#991B1B; }
  .ap-link{ color:var(--ap-primary); font-weight:900; cursor:pointer; text-decoration:none; }
  .ap-link:hover{ text-decoration:underline; }

  .ap-empty{
    border:1px dashed #FCD34D;
    background:#FFFBEB;
    color:var(--ap-warn);
    border-radius:12px;
    padding:10px 12px;
    font-size:11px;
    font-weight:800;
    margin-top:10px;
  }

  /* Modal detalle */
  .ap-modal-h{ font-weight:900; color:#111827; }
  .ap-det-grid{ display:grid; grid-template-columns:repeat(12,minmax(0,1fr)); gap:10px; }
  .ap-det{ grid-column:span 4; background:#F9FAFB; border:1px solid var(--ap-border); border-radius:12px; padding:10px 12px; }
  .ap-det .k{ font-size:11px; color:var(--ap-muted); }
  .ap-det .v{ font-size:12px; font-weight:900; color:#111827; }
  @media(max-width:900px){ .ap-det{ grid-column:span 12; } }
</style>

<div class="ap-wrap">
  <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:12px; flex-wrap:wrap;">
    <div>
      <div class="ap-title">Pedidos · Administrador</div>
      <div class="ap-sub">Bandeja operativa (fuente API). Default: últimos 30 días. Máx. 25 registros por carga.</div>
    </div>
    <div class="ap-actions">
      <button class="ap-btn ap-btn-gray" type="button" onclick="apLimpiar()">Limpiar</button>
      <button class="ap-btn ap-btn-primary" type="button" onclick="apBuscar()">Buscar / Actualizar</button>
    </div>
  </div>

  <!-- KPIs -->
  <div class="ap-card">
    <h3>KPIs</h3>
    <div class="ap-kpis">
      <div class="ap-kpi"><div class="t">Pedidos listados (máx. 25)</div><div class="v" id="k_total">0</div></div>
      <div class="ap-kpi"><div class="t">Activos</div><div class="v" id="k_activos">0</div></div>
      <div class="ap-kpi"><div class="t">Cerrados</div><div class="v" id="k_cerrados">0</div></div>
      <div class="ap-kpi"><div class="t">Prioridad Alta/Urgente</div><div class="v" id="k_prio">0</div></div>
    </div>
    <div id="k_msg" style="display:none;" class="ap-empty"></div>
  </div>

  <!-- Filtros -->
  <div class="ap-card">
    <h3>Filtros</h3>

    <div class="ap-chipbar" style="margin-bottom:10px;">
      <span class="ap-small" style="font-weight:800;">Rangos rápidos:</span>
      <button class="ap-chip" type="button" onclick="apRango('hoy')">Hoy</button>
      <button class="ap-chip" type="button" onclick="apRango('7')">Últimos 7 días</button>
      <button class="ap-chip" type="button" onclick="apRango('30')">Últimos 30 días</button>
      <button class="ap-chip" type="button" onclick="apRango('mes')">Mes actual</button>
      <label class="ap-small" style="display:flex; align-items:center; gap:6px; margin-left:8px;">
        <input type="checkbox" id="f_ignore_dates" style="width:auto; transform:translateY(1px);">
        Ignorar fechas (trae últimos 25)
      </label>
    </div>

    <div class="ap-grid">
      <div class="ap-col-3">
        <label>Almacén</label>
        <select id="f_almacen"></select>
      </div>

      <div class="ap-col-2">
        <label>Status</label>
        <select id="f_status">
          <option value="">Todos</option>
          <option value="A">Activos (A)</option>
          <option value="C">Cerrados (C)</option>
          <option value="X">Cancelados (X)</option>
        </select>
      </div>

      <div class="ap-col-2">
        <label>Desde</label>
        <input type="date" id="f_desde">
      </div>

      <div class="ap-col-2">
        <label>Hasta</label>
        <input type="date" id="f_hasta">
      </div>

      <div class="ap-col-3">
        <label>Ruta</label>
        <input type="text" id="f_ruta" placeholder="Ej. RUTA-43">
      </div>

      <div class="ap-col-4">
        <label>Cliente</label>
        <input type="text" id="f_cliente_q" placeholder="Buscar por código / razón social / RFC">
        <input type="hidden" id="f_cliente_cve">
        <div class="ap-small" id="f_cliente_hint"></div>
      </div>

      <div class="ap-col-4">
        <label>Buscar (Folio / Texto)</label>
        <input type="text" id="f_buscar" placeholder="PED-..., PRUEBA, etc.">
      </div>

      <div class="ap-col-4" style="display:flex; align-items:flex-end; gap:8px;">
        <button class="ap-btn ap-btn-light" type="button" onclick="apExportCSV()">Export CSV</button>
        <div class="ap-small">Exporta lo que ves (máx. 25).</div>
      </div>
    </div>
  </div>

  <!-- Tabla -->
  <div class="ap-card">
    <h3>Pedidos (operación)</h3>
    <div class="ap-table-wrap" style="max-height:62vh;">
      <table class="ap-table" id="tblPedidos">
        <thead>
          <tr>
            <th>Acciones</th>
            <th>Status</th>
            <th>Folio</th>
            <th>Fecha Pedido</th>
            <th>Entrega</th>
            <th>Almacén</th>
            <th>Ruta</th>
            <th>Cliente</th>
            <th>Prioridad</th>
            <th>Ventana</th>
            <th>Fuente</th>
            <th>Usuario</th>
            <th>Obs</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
    <div class="ap-small" style="margin-top:8px;">
      Estándar AssistPro: 25 registros por carga, scroll horizontal/vertical, acciones a la izquierda.
    </div>
  </div>
</div>

<!-- Modal Detalle (Bootstrap 5) -->
<div class="modal fade" id="mdlDetalle" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <div class="ap-modal-h" id="m_titulo">Detalle de Pedido</div>
          <div class="ap-small" id="m_sub">—</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="ap-det-grid" style="margin-bottom:12px;">
          <div class="ap-det"><div class="k">Folio</div><div class="v" id="m_folio">—</div></div>
          <div class="ap-det"><div class="k">Cliente</div><div class="v" id="m_cliente">—</div></div>
          <div class="ap-det"><div class="k">Almacén</div><div class="v" id="m_alm">—</div></div>
          <div class="ap-det"><div class="k">Status</div><div class="v" id="m_status">—</div></div>
          <div class="ap-det"><div class="k">Fecha Pedido</div><div class="v" id="m_fp">—</div></div>
          <div class="ap-det"><div class="k">Entrega</div><div class="v" id="m_fe">—</div></div>
        </div>

        <div class="ap-table-wrap" style="max-height:45vh;">
          <table class="ap-table" id="tblDetalle">
            <thead>
              <tr>
                <th>#</th>
                <th>Artículo</th>
                <th>Cantidad</th>
                <th>UOM</th>
                <th>Lote</th>
                <th>Surtido Cajas</th>
                <th>Surtido Piezas</th>
                <th>Revisadas</th>
                <th>Empacados</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="ap-btn ap-btn-gray" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
  // API relativa desde /public/pedidos/ -> /public/api/pedidos/
  const API = '../api/pedidos/pedidos_api.php';
  let AP_ROWS = [];

  function esc(s){ return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
  function pill(st){
    if(st==='A') return '<span class="ap-pill pill-A">A</span>';
    if(st==='C') return '<span class="ap-pill pill-C">C</span>';
    if(st==='X') return '<span class="ap-pill pill-X">X</span>';
    return '<span class="ap-pill pill-C">'+esc(st||'?')+'</span>';
  }

  function iso(d){
    const z = new Date(d.getTime() - d.getTimezoneOffset()*60000);
    return z.toISOString().slice(0,10);
  }

  function setMsg(txt){
    const el = document.getElementById('k_msg');
    if(!txt){ el.style.display='none'; el.textContent=''; return; }
    el.style.display='block';
    el.textContent = txt;
  }

  // -------- Rangos rápidos
  function apRango(mode){
    const hoy = new Date();
    let d1, d2;
    if(mode==='hoy'){
      d1 = new Date(hoy); d2 = new Date(hoy);
    } else if(mode==='7'){
      d2 = new Date(hoy); d1 = new Date(hoy); d1.setDate(d1.getDate()-7);
    } else if(mode==='30'){
      d2 = new Date(hoy); d1 = new Date(hoy); d1.setDate(d1.getDate()-30);
    } else if(mode==='mes'){
      d2 = new Date(hoy); d1 = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    }
    document.getElementById('f_desde').value = iso(d1);
    document.getElementById('f_hasta').value = iso(d2);
    document.getElementById('f_ignore_dates').checked = false;
    apBuscar();
  }

  // -------- Almacenes
  async function apLoadAlmacenes(){
    const sel = document.getElementById('f_almacen');
    sel.innerHTML = '<option value="">Cargando...</option>';
    try{
      const r = await fetch(`${API}?action=almacenes`);
      const j = await r.json();
      if(!j.ok) throw new Error(j.error||'Error');
      sel.innerHTML = '<option value="">Todos</option>' + (j.rows||[]).map(x =>
        `<option value="${esc(x.cve)}">(${esc(x.cve)}) - ${esc(x.nombre)}</option>`
      ).join('');
    }catch(e){
      sel.innerHTML = '<option value="">(sin almacenes)</option>';
    }
  }

  // -------- Cliente typeahead (simple)
  let tCli=null;
  document.getElementById('f_cliente_q').addEventListener('input', (e)=>{
    clearTimeout(tCli);
    const q = e.target.value.trim();
    if(q.length<2){
      document.getElementById('f_cliente_cve').value='';
      document.getElementById('f_cliente_hint').textContent='';
      return;
    }
    tCli=setTimeout(()=>apBuscarCliente(q), 250);
  });

  async function apBuscarCliente(q){
    const hint = document.getElementById('f_cliente_hint');
    try{
      const r = await fetch(`${API}?action=clientes&q=${encodeURIComponent(q)}&limit=10`);
      const j = await r.json();
      if(!j.ok) throw new Error(j.error||'Error');
      if(!j.rows || j.rows.length===0){
        hint.textContent='Sin resultados';
        document.getElementById('f_cliente_cve').value='';
        return;
      }
      const pick = j.rows[0];
      document.getElementById('f_cliente_cve').value = pick.Cve_Clte || '';
      hint.textContent = `Seleccionado: ${pick.Cve_Clte || ''} · ${pick.RazonSocial || ''}`;
    }catch(e){
      hint.textContent='Error consultando clientes';
    }
  }

  // -------- Buscar
  async function apBuscar(){
    setMsg('');

    const st   = document.getElementById('f_status').value.trim();
    const alm  = document.getElementById('f_almacen').value.trim();
    const ruta = document.getElementById('f_ruta').value.trim();
    const clte = document.getElementById('f_cliente_cve').value.trim();
    let d1     = document.getElementById('f_desde').value;
    let d2     = document.getElementById('f_hasta').value;
    const txt  = document.getElementById('f_buscar').value.trim().toUpperCase();
    const ign  = document.getElementById('f_ignore_dates').checked;

    // si el usuario puso fechas invertidas, las corregimos
    if(d1 && d2 && d1 > d2){
      const tmp = d1; d1 = d2; d2 = tmp;
      document.getElementById('f_desde').value = d1;
      document.getElementById('f_hasta').value = d2;
    }

    const qs = new URLSearchParams();
    qs.set('action','pedidos');
    if(st)  qs.set('status', st);
    if(alm) qs.set('cve_almac', alm);
    if(ruta)qs.set('ruta', ruta);
    if(clte)qs.set('cve_clte', clte);

    // regla clave: SOLO mandamos fechas si NO está "ignorar" y vienen ambas
    if(!ign && d1 && d2){
      qs.set('desde', d1);
      qs.set('hasta', d2);
    }

    const url = `${API}?${qs.toString()}`;

    const tbody = document.querySelector('#tblPedidos tbody');
    tbody.innerHTML = `<tr><td colspan="13" style="padding:14px;color:#6B7280;">Cargando...</td></tr>`;

    try{
      const r = await fetch(url);
      const j = await r.json();
      if(!j.ok) throw new Error(j.error||'Error');

      AP_ROWS = (j.rows||[]);

      // filtro local por texto/folio (porque el API trae 25)
      let rows = AP_ROWS;
      if(txt){
        rows = rows.filter(x =>
          String(x.Fol_folio||'').toUpperCase().includes(txt) ||
          String(x.Observaciones||'').toUpperCase().includes(txt) ||
          String(x.Cve_clte||'').toUpperCase().includes(txt) ||
          String(x.ruta||'').toUpperCase().includes(txt)
        );
      }

      apRender(rows);
      apKPIs(rows);

      if(rows.length === 0){
        const msg =
          (ign ? 'Sin resultados con filtros actuales. Tip: desmarca "Ignorar fechas" o ajusta almacén/status.' :
          (d1 && d2 ? `Sin pedidos en el rango ${d1} a ${d2}. Tip: usa “Últimos 30 días” o “Ignorar fechas”.` :
                      'Sin pedidos con filtros actuales. Tip: usa rangos rápidos.'));
        setMsg(msg);
      }

    }catch(e){
      tbody.innerHTML = `<tr><td colspan="13" style="padding:14px;color:#991B1B;">Error: ${esc(e.message)}</td></tr>`;
      AP_ROWS = [];
      apKPIs([]);
      setMsg('Error consultando API. Revisar consola/red o validar endpoint.');
    }
  }

  function apRender(rows){
    const tbody = document.querySelector('#tblPedidos tbody');
    tbody.innerHTML = '';
    if(!rows || rows.length===0){
      tbody.innerHTML = `<tr><td colspan="13" style="padding:14px;color:#6B7280;">Sin datos</td></tr>`;
      return;
    }

    rows.forEach(r=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><a class="ap-link" onclick="apVerDetalle('${esc(r.Fol_folio)}')">Ver</a></td>
        <td>${pill(r.status)}</td>
        <td><strong>${esc(r.Fol_folio)}</strong></td>
        <td>${esc(r.Fec_Pedido||'')}</td>
        <td>${esc(r.Fec_Entrega||'')}</td>
        <td>${esc(r.cve_almac||r.clave_almacen||'')}</td>
        <td>${esc(r.ruta||'')}</td>
        <td>${esc(r.Cve_clte||'')}</td>
        <td>${esc(r.ID_Tipoprioridad||'')}</td>
        <td>${esc(r.rango_hora||'')}</td>
        <td>${esc(r.fuente_detalle||'')}</td>
        <td>${esc(r.Cve_Usuario||'')}</td>
        <td title="${esc(r.Observaciones||'')}">${esc((r.Observaciones||'').toString().slice(0,40))}</td>
      `;
      tbody.appendChild(tr);
    });
  }

  function apKPIs(rows){
    const total = rows.length;
    const activos = rows.filter(x=>String(x.status||'')==='A').length;
    const cerrados = rows.filter(x=>String(x.status||'')==='C').length;
    const prio = rows.filter(x=>{
      const p = Number(x.ID_Tipoprioridad||0);
      return p===1 || p===2;
    }).length;

    document.getElementById('k_total').textContent = total;
    document.getElementById('k_activos').textContent = activos;
    document.getElementById('k_cerrados').textContent = cerrados;
    document.getElementById('k_prio').textContent = prio;
  }

  function apLimpiar(){
    document.getElementById('f_status').value='';
    document.getElementById('f_almacen').value='';
    document.getElementById('f_ruta').value='';
    document.getElementById('f_cliente_q').value='';
    document.getElementById('f_cliente_cve').value='';
    document.getElementById('f_cliente_hint').textContent='';
    document.getElementById('f_buscar').value='';
    document.getElementById('f_ignore_dates').checked=false;

    // default inteligente: últimos 30 días
    apRango('30');
  }

  // -------- Detalle Modal
  async function apVerDetalle(folio){
    try{
      const r = await fetch(`${API}?action=pedido_detalle&folio=${encodeURIComponent(folio)}`);
      const j = await r.json();
      if(!j.ok) throw new Error(j.error||'Error');

      const h = j.header || {};
      const d = j.detail || [];

      document.getElementById('m_sub').textContent = `Partidas: ${d.length}`;
      document.getElementById('m_folio').textContent = h.Fol_folio || folio;
      document.getElementById('m_cliente').textContent = h.Cve_clte || '—';
      document.getElementById('m_alm').textContent = h.cve_almac || '—';
      document.getElementById('m_status').textContent = h.status || '—';
      document.getElementById('m_fp').textContent = h.Fec_Pedido || '—';
      document.getElementById('m_fe').textContent = h.Fec_Entrega || '—';

      const tb = document.querySelector('#tblDetalle tbody');
      tb.innerHTML = '';
      if(d.length===0){
        tb.innerHTML = `<tr><td colspan="10" style="padding:14px;color:#6B7280;">Sin partidas</td></tr>`;
      }else{
        d.forEach((x,i)=>{
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>${i+1}</td>
            <td>${esc(x.Cve_articulo||'')}</td>
            <td style="text-align:right;">${Number(x.Num_cantidad||0).toLocaleString()}</td>
            <td>${esc(x.id_unimed||'')}</td>
            <td>${esc(x.cve_lote||'')}</td>
            <td style="text-align:right;">${Number(x.SurtidoXCajas||0).toLocaleString()}</td>
            <td style="text-align:right;">${Number(x.SurtidoXPiezas||0).toLocaleString()}</td>
            <td style="text-align:right;">${Number(x.Num_revisadas||0).toLocaleString()}</td>
            <td style="text-align:right;">${Number(x.Num_Empacados||0).toLocaleString()}</td>
            <td>${esc(x.status||'')}</td>
          `;
          tb.appendChild(tr);
        });
      }

      const mdl = new bootstrap.Modal(document.getElementById('mdlDetalle'));
      mdl.show();

    }catch(e){
      alert('No se pudo cargar detalle: ' + e.message);
    }
  }

  // -------- Export CSV
  function apExportCSV(){
    const rows = AP_ROWS;
    if(!rows || rows.length===0){ alert('No hay datos para exportar'); return; }

    const cols = [
      'Fol_folio','status','Fec_Pedido','Fec_Entrega','cve_almac','ruta','Cve_clte',
      'ID_Tipoprioridad','rango_hora','fuente_detalle','Cve_Usuario','Observaciones'
    ];

    const csv = [cols.join(',')]
      .concat(rows.map(r => cols.map(c => `"${String(r[c]??'').replace(/"/g,'""')}"`).join(',')))
      .join('\n');

    const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'pedidos_admin.csv';
    document.body.appendChild(a);
    a.click();
    a.remove();
  }

  // Init
  (async function(){
    await apLoadAlmacenes();

    // Default ejecutivo: últimos 30 días (evita “pantalla vacía”)
    apRango('30');
  })();
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
