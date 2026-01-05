<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>
<style>
.ap-container{padding:12px;font-size:12px}
.ap-title{font-size:18px;font-weight:700;color:#0b5ed7;margin-bottom:8px}
.ap-sub{color:#6c757d;margin-top:-4px;margin-bottom:10px}
.ap-chip{border:1px solid #d0d7e2;border-radius:18px;padding:6px 10px;background:#fff;font-size:12px;cursor:pointer;white-space:nowrap}
.ap-chip.ok{background:#d1e7dd;border-color:#badbcc;color:#0f5132}
.ap-chip.danger{background:#f8d7da;border-color:#f5c2c7;color:#842029}
.ap-chip.warn{background:#fff3cd;border-color:#ffecb5;color:#7a5d00}
.ap-muted{color:#6c757d}

.ap-toolbar{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px}
.ap-leftfilters{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.ap-search{display:flex;align-items:center;gap:6px;border:1px solid #d0d7e2;border-radius:10px;padding:6px 8px;background:#fff}
.ap-search input{border:0;outline:0;font-size:12px;width:340px;background:transparent}

.ap-grid{border:1px solid #dcdcdc;height:540px;overflow:auto;border-radius:10px;background:#fff}
.ap-grid table{width:100%;border-collapse:collapse}
.ap-grid th{position:sticky;top:0;background:#f4f6fb;padding:7px;border-bottom:1px solid #ccc;white-space:nowrap}
.ap-grid td{padding:6px;border-bottom:1px solid #eee;white-space:nowrap;vertical-align:middle}
.ap-actions i{cursor:pointer;margin-right:10px;color:#0b5ed7}

.ap-pager{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-top:10px}
.ap-pager .left,.ap-pager .right{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.ap-pager button{padding:6px 10px;border-radius:10px;border:1px solid #d0d7e2;background:#fff}
.ap-pager button:disabled{opacity:.5;cursor:not-allowed}
.ap-pager select{border:1px solid #d0d7e2;border-radius:10px;padding:6px 8px;font-size:12px}

.ap-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999}
.ap-modal-content{background:#fff;width:1200px;max-width:96vw;margin:2.2% auto;padding:14px;border-radius:12px}
.ap-modal-head{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
button.ghost{background:#fff;border:1px solid #d0d7e2;padding:7px 12px;border-radius:10px}
</style>

<div class="ap-container">
  <div class="ap-title"><i class="fa fa-file-invoice-dollar"></i> Cuentas por Cobrar | Consolidado</div>
  <div class="ap-sub">Clientes con cobranza abierta (Status=1). Consolidado con Abonos y Saldo Final por cliente.</div>

  <div class="ap-toolbar">
    <div class="ap-leftfilters">
      <div class="ap-chip" id="fEmpresa"><i class="fa fa-building"></i> Empresa: <b>Todas</b></div>
      <div class="ap-chip" id="btnVencidos" onclick="toggleVencidos()"><i class="fa fa-exclamation-triangle"></i> Solo vencidos: <b>No</b></div>

      <div class="ap-chip">
        <i class="fa fa-calendar"></i> Vence:
        <input type="date" id="fv_from" style="border:0;outline:0;font-size:12px;background:transparent" onchange="buscar()">
        <span class="ap-muted">a</span>
        <input type="date" id="fv_to" style="border:0;outline:0;font-size:12px;background:transparent" onchange="buscar()">
        <span class="ap-chip" onclick="clearFechas()">Quitar</span>
      </div>
    </div>

    <div class="ap-search">
      <i class="fa fa-search"></i>
      <input id="q" placeholder="Buscar Cve_Clte, razón social, documento…" onkeydown="if(event.key==='Enter')buscar()">
      <button class="ap-chip" onclick="limpiar()">Limpiar</button>
    </div>

    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <button class="ap-chip" onclick="exportarConsolidado()"><i class="fa fa-download"></i> Exportar consolidado</button>
    </div>
  </div>

  <div class="ap-grid">
    <table>
      <thead>
        <tr>
          <th>Acciones</th>
          <th>Cliente</th>
          <th>Clave Dest.</th>
          <th>Razón Social</th>
          <th>Empresa</th>
          <th>Ruta</th>

          <th style="text-align:right">Saldo</th>
          <th style="text-align:right">Abonos</th>
          <th style="text-align:right">Saldo Final</th>

          <th style="text-align:right">Docs</th>
          <th style="text-align:right">Vencidos</th>
          <th>Próx. Vence</th>
          <th style="text-align:right">Max Atraso</th>
          <th>Último Pago</th>
        </tr>
      </thead>
      <tbody id="tb"></tbody>
    </table>
  </div>

  <div class="ap-pager">
    <div class="left">
      <button onclick="prevPage()" id="btnPrev">◀ Prev</button>
      <button onclick="nextPage()" id="btnNext">Next ▶</button>
      <span class="ap-chip" id="lblRange">Mostrando 0–0</span>
      <span class="ap-chip">Página</span>
      <select id="selPage" onchange="goPage(this.value)"></select>
    </div>
    <div class="right">
      <span class="ap-chip">Por página</span>
      <select id="selPerPage" onchange="setPerPage(this.value)">
        <option value="25" selected>25</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>
    </div>
  </div>
</div>

<!-- MODAL DOCS -->
<div class="ap-modal" id="mdlDocs">
  <div class="ap-modal-content">
    <div class="ap-modal-head">
      <div>
        <div style="font-size:16px;font-weight:800;color:#0b5ed7">
          <i class="fa fa-list"></i> Documentos pendientes
        </div>
        <div class="ap-muted" id="docsSub">Cliente</div>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button class="ap-chip" onclick="exportarDocs()"><i class="fa fa-download"></i> Exportar docs</button>
        <button class="ghost" onclick="cerrarModal('mdlDocs')">Cerrar</button>
      </div>
    </div>

    <div class="ap-grid" style="height:420px;margin-top:10px">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Documento</th>
            <th>Tipo</th>
            <th style="text-align:right">Saldo</th>
            <th style="text-align:right">Abonos</th>
            <th style="text-align:right">Saldo Neto</th>
            <th>Status</th>
            <th>Ruta</th>
            <th>Vence</th>
            <th style="text-align:right">Días atraso</th>
            <th>Últ. Pago</th>
          </tr>
        </thead>
        <tbody id="tbDocs"></tbody>
      </table>
    </div>
  </div>
</div>

<script>
const API = '../api/cxc_consolidado.php';

let filtroEmpresa = '';
let soloVencidos  = false;

let page = 1;
let perPage = 25;
let total = 0;
let lastRows = [];

let lastDocsClienteId = 0;
let lastDocsClienteTxt = '';

function money(v){
  const n = Number(v||0);
  return n.toLocaleString('es-MX',{minimumFractionDigits:2,maximumFractionDigits:2});
}
function dmy(iso){
  if(!iso) return '';
  const s = String(iso).substring(0,10);
  const [y,m,d] = s.split('-');
  if(!y||!m||!d) return s;
  return `${d}/${m}/${y}`;
}

function toggleVencidos(){
  soloVencidos = !soloVencidos;
  btnVencidos.innerHTML = `<i class="fa fa-exclamation-triangle"></i> Solo vencidos: <b>${soloVencidos?'Sí':'No'}</b>`;
  page = 1; cargar();
}
function clearFechas(){ fv_from.value=''; fv_to.value=''; buscar(); }

/* ===== Paginación ===== */
function setPager(){
  const start = total>0 ? ((page-1)*perPage + (lastRows.length?1:0)) : 0;
  const end   = total>0 ? Math.min(page*perPage, total) : 0;
  lblRange.innerText = `Mostrando ${start}-${end}` + (total>0 ? ` de ${total}` : '');

  const maxPages = Math.max(1, Math.ceil(total/perPage));
  selPage.innerHTML='';
  for(let i=1;i<=maxPages;i++){
    const o=document.createElement('option');
    o.value=i; o.textContent=i;
    if(i===page) o.selected=true;
    selPage.appendChild(o);
  }
  btnPrev.disabled = (page<=1);
  btnNext.disabled = (page>=maxPages);
}
function prevPage(){ if(page>1){ page--; cargar(); } }
function nextPage(){
  const maxPages = Math.max(1, Math.ceil(total/perPage));
  if(page<maxPages){ page++; cargar(); }
}
function goPage(p){ page = Math.max(1, parseInt(p,10)||1); cargar(); }
function setPerPage(v){ perPage = parseInt(v,10)||25; page=1; cargar(); }

/* ===== Listado ===== */
function cargar(){
  const offset = (page-1)*perPage;
  const url = API+'?action=list'
    + '&empresa='+encodeURIComponent(filtroEmpresa||'')
    + '&solo_vencidos='+(soloVencidos?1:0)
    + '&fv_from='+encodeURIComponent(fv_from.value||'')
    + '&fv_to='+encodeURIComponent(fv_to.value||'')
    + '&q='+encodeURIComponent((q.value||'').trim())
    + '&limit='+encodeURIComponent(perPage)
    + '&offset='+encodeURIComponent(offset);

  fetch(url).then(r=>r.json()).then(resp=>{
    const rows = resp.rows || [];
    total = Number(resp.total||0) || 0;
    lastRows = rows;

    let h='';
    rows.forEach(x=>{
      const saldo = Number(x.SaldoTotal||0);
      const abonos = Number(x.AbonosCliente||0);
      const final  = Number(x.SaldoFinalCliente||0);
      const venc   = Number(x.DocsVencidos||0);
      const atraso = (x.MaxDiasAtraso===null || x.MaxDiasAtraso===undefined) ? '' : Number(x.MaxDiasAtraso||0);

      const clienteClave = x.Cve_Clte || '';
      const clienteId = Number(x.ClienteId||0) || 0;

      h += `
      <tr>
        <td class="ap-actions">
          <i class="fa fa-eye" title="Ver documentos" onclick="verDocs(${clienteId}, '${String(clienteClave).replace(/'/g,"\\'")}', '${String(x.razonsocial||'').replace(/'/g,"\\'")}')"></i>
          <i class="fa fa-download" title="Exportar docs" onclick="quickExportDocs(${clienteId})"></i>
        </td>

        <td><b>${clienteClave}</b></td>
        <td>${x.clave_destinatario||''}</td>
        <td>${x.razonsocial||''}</td>
        <td>${x.Empresa||x.IdEmpresa||''}</td>
        <td>${x.Ruta||x.RutaId||''}</td>

        <td style="text-align:right">$ ${money(saldo)}</td>
        <td style="text-align:right">$ ${money(abonos)}</td>
        <td style="text-align:right"><b>$ ${money(final)}</b></td>

        <td style="text-align:right">${x.DocsPendientes||0}</td>
        <td style="text-align:right">${venc>0 ? `<span class="ap-chip danger">${venc}</span>` : `<span class="ap-chip ok">0</span>`}</td>
        <td>${dmy(x.ProxVencimiento)}</td>
        <td style="text-align:right">${atraso!=='' ? atraso+' d' : ''}</td>
        <td>${x.UltimoPago||''}</td>
      </tr>`;
    });

    tb.innerHTML = h || `<tr><td colspan="14" style="text-align:center;color:#6c757d;padding:20px">Sin resultados</td></tr>`;
    setPager();
  });
}

function buscar(){ page=1; cargar(); }
function limpiar(){ q.value=''; page=1; cargar(); }

function exportarConsolidado(){
  const url = API+'?action=export_csv&tipo=consolidado'
    + '&empresa='+encodeURIComponent(filtroEmpresa||'')
    + '&solo_vencidos='+(soloVencidos?1:0)
    + '&fv_from='+encodeURIComponent(fv_from.value||'')
    + '&fv_to='+encodeURIComponent(fv_to.value||'')
    + '&q='+encodeURIComponent((q.value||'').trim());
  window.open(url,'_blank');
}

/* ===== Docs modal ===== */
function verDocs(clienteId, cve, razon){
  lastDocsClienteId = clienteId;
  lastDocsClienteTxt = (cve?(`<b>${cve}</b> · `):'') + (razon||'');
  docsSub.innerHTML = `${lastDocsClienteTxt} <span class="ap-muted">(ID ${clienteId})</span>`;

  const url = API+'?action=docs'
    + '&clienteId='+encodeURIComponent(clienteId)
    + '&empresa='+encodeURIComponent(filtroEmpresa||'')
    + '&solo_vencidos='+(soloVencidos?1:0);

  fetch(url).then(r=>r.json()).then(resp=>{
    const rows = resp.rows || [];
    let h='';
    rows.forEach(d=>{
      const atraso = (d.DiasAtraso===null || d.DiasAtraso===undefined) ? '' : Number(d.DiasAtraso||0);
      const vencChip = (atraso!=='' && atraso>0) ? `<span class="ap-chip danger">${atraso}</span>` : `<span class="ap-chip ok">0</span>`;
      h += `
        <tr>
          <td>${d.id||''}</td>
          <td>${d.Documento||''}</td>
          <td>${d.TipoDoc||''}</td>
          <td style="text-align:right">$ ${money(d.Saldo)}</td>
          <td style="text-align:right">$ ${money(d.Abonos)}</td>
          <td style="text-align:right"><b>$ ${money(d.SaldoNeto)}</b></td>
          <td>${d.Status??''}</td>
          <td>${d.Ruta||d.RutaId||''}</td>
          <td>${dmy(d.FechaVence)}</td>
          <td style="text-align:right">${vencChip}</td>
          <td>${d.UltPago||''}</td>
        </tr>`;
    });
    tbDocs.innerHTML = h || `<tr><td colspan="11" style="text-align:center;color:#6c757d;padding:20px">Sin documentos</td></tr>`;
    document.getElementById('mdlDocs').style.display='block';
  });
}

function exportarDocs(){
  if(!lastDocsClienteId) return;
  const url = API+'?action=export_csv&tipo=docs&clienteId='+encodeURIComponent(lastDocsClienteId);
  window.open(url,'_blank');
}
function quickExportDocs(clienteId){
  const url = API+'?action=export_csv&tipo=docs&clienteId='+encodeURIComponent(clienteId);
  window.open(url,'_blank');
}

function cerrarModal(id){ document.getElementById(id).style.display='none'; }

document.addEventListener('DOMContentLoaded', ()=>{
  selPerPage.value='25';
  cargar();
});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
