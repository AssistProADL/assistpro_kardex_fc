<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
.ap-container{padding:12px;font-size:12px}
.ap-title{font-size:18px;font-weight:600;color:#0b5ed7;margin-bottom:10px}
.ap-cards{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:10px}
.ap-card{width:250px;background:#fff;border:1px solid #d0d7e2;border-radius:10px;padding:10px;cursor:pointer}
.ap-card:hover{border-color:#0b5ed7}
.ap-card .h{display:flex;justify-content:space-between}
.ap-card .k{display:flex;gap:6px;flex-wrap:wrap;margin-top:6px}
.ap-chip{font-size:11px;background:#eef2ff;color:#1e3a8a;border-radius:10px;padding:2px 8px}
.ap-chip.ok{background:#d1e7dd;color:#0f5132}
.ap-chip.warn{background:#fff3cd;color:#7a5d00}
.ap-toolbar{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px}
.ap-search{display:flex;gap:6px;border:1px solid #ccc;padding:4px 6px;border-radius:6px}
.ap-search input{border:0;outline:0;width:260px}
.ap-grid{border:1px solid #ccc;height:480px;overflow:auto}
.ap-grid table{width:100%;border-collapse:collapse}
.ap-grid th{position:sticky;top:0;background:#f4f6fb;padding:6px}
.ap-grid td{padding:5px;border-bottom:1px solid #eee}
.ap-actions i{cursor:pointer;margin-right:6px;color:#0b5ed7}
.ap-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999}
.ap-modal-content{background:#fff;width:900px;margin:4% auto;padding:15px;border-radius:10px}
</style>

<div class="ap-container">
  <div class="ap-title"><i class="fa fa-box-open"></i> Pallets / Contenedores físicos</div>

  <div class="ap-cards" id="cards"></div>

  <div class="ap-toolbar">
    <div class="ap-chip" id="filtroLabel">Almacén: <b>Todos</b></div>

    <div class="ap-search">
      <i class="fa fa-search"></i>
      <input id="q" placeholder="Buscar..." onkeydown="if(event.key==='Enter')buscar()">
      <button class="ap-chip" onclick="limpiar()">Limpiar</button>
    </div>

    <button class="ap-chip" onclick="toggleInactivos()">Inactivos</button>
  </div>

  <div class="ap-grid">
    <table>
      <thead>
        <tr>
          <th>Acciones</th>
          <th>Almacén</th>
          <th>Clave</th>
          <th>Tipo</th>
          <th>Permanente</th>
          <th>LP</th>
          <th>Peso</th>
          <th>Activo</th>
        </tr>
      </thead>
      <tbody id="tb"></tbody>
    </table>
  </div>
</div>

<script>
const API = '../api/api_cat_pallets_contenedores.php';
const KPI = '../api/pallets_contenedores_kpi.php';

let filtroAlmClave = '';
let verInactivos = false;
let qLast = '';

function loadCards(){
  fetch(KPI+'?action=kpi')
    .then(r=>r.json())
    .then(rows=>{
      let h='';
      rows.forEach(x=>{
        h+=`
        <div class="ap-card" onclick="setAlm('${x.almac_clave||''}')">
          <div class="h">
            <b>${x.almac_clave||''}</b>
            <span class="ap-chip ok">${x.activas}</span>
          </div>
          <div class="k">
            <span class="ap-chip">${x.almac_nombre||''}</span>
            <span class="ap-chip">Total ${x.total}</span>
          </div>
        </div>`;
      });
      cards.innerHTML = h;
    });
}

function setAlm(clave){
  filtroAlmClave = clave;
  filtroLabel.innerHTML = `Almacén: <b>${clave||'Todos'}</b>`;
  cargar();
}

function cargar(){
  const url = API+'?action=list'
    +'&almac_clave='+encodeURIComponent(filtroAlmClave)
    +'&q='+encodeURIComponent(qLast||'');

  fetch(url)
    .then(r=>r.json())
    .then(resp=>{
      const rows = resp.rows || [];
      let h='';
      rows.forEach(c=>{
        h+=`
        <tr>
          <td class="ap-actions">
            <i class="fa fa-edit"></i>
          </td>
          <td>
            <b>${c.almac_clave||''}</b><br>
            <small>${c.almac_nombre||''}</small>
          </td>
          <td>${c.Clave_Contenedor}</td>
          <td>${c.tipo||''}</td>
          <td>${c.Permanente==1?'Sí':'No'}</td>
          <td>${c.CveLP?c.CveLP:'Libre'}</td>
          <td>${c.peso||''}</td>
          <td>${c.Activo==1?'✔':'✖'}</td>
        </tr>`;
      });
      tb.innerHTML = h || `<tr><td colspan="8">Sin datos</td></tr>`;
    });
}

function buscar(){ qLast = q.value.trim(); cargar(); }
function limpiar(){ q.value=''; qLast=''; cargar(); }
function toggleInactivos(){ verInactivos=!verInactivos; cargar(); }

document.addEventListener('DOMContentLoaded',()=>{
  loadCards();
  cargar();
});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
