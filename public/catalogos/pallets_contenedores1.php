<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
.ap-container{padding:12px;font-size:12px}
.ap-title{font-size:18px;font-weight:600;color:#0b5ed7;margin-bottom:10px}
.ap-cards{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px}
.ap-card{
  width:230px;background:#fff;border:1px solid #d0d7e2;
  border-radius:10px;padding:10px;cursor:pointer;
  box-shadow:0 1px 3px rgba(0,0,0,.05)
}
.ap-card.active{border-color:#0b5ed7;background:#eef4ff}
.ap-card .h{display:flex;justify-content:space-between}
.ap-chip{font-size:11px;background:#eef2ff;color:#1e3a8a;border-radius:10px;padding:2px 8px}
.ap-chip.ok{background:#d1e7dd;color:#0f5132}
.ap-chip.warn{background:#fff3cd;color:#7a5d00}

.ap-toolbar{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px}
.ap-grid{border:1px solid #dcdcdc;height:500px;overflow:auto}
.ap-grid table{width:100%;border-collapse:collapse}
.ap-grid th{
  position:sticky;top:0;background:#f4f6fb;
  padding:6px;border-bottom:1px solid #ccc
}
.ap-grid td{padding:5px;border-bottom:1px solid #eee;white-space:nowrap}

.pagination{margin-top:10px}
.pagination button{
  border:1px solid #d0d7e2;background:#fff;
  padding:4px 8px;margin-right:4px;border-radius:6px;
}
.pagination button.active{background:#0b5ed7;color:#fff}
</style>

<div class="ap-container">

<div class="ap-title">Pallets / Contenedores</div>

<div class="ap-cards" id="cards"></div>

<div class="ap-toolbar">
<select id="tipo">
  <option value="">Todos</option>
  <option value="Pallet">Pallet</option>
  <option value="Contenedor">Contenedor</option>
</select>

<input id="q" placeholder="Buscar clave, pedido, LP...">

<button onclick="buscar()">Buscar</button>
<button onclick="limpiar()">Limpiar</button>
<button onclick="exportar()">Exportar Excel</button>
<button onclick="toggleInactivos()">Inactivos</button>
</div>

<div class="ap-grid">
<table>
<thead>
<tr>
<th>Almacén</th>
<th>Clave</th>
<th>Tipo</th>
<th>Pedido</th>
<th>LP</th>
<th>Activo</th>
</tr>
</thead>
<tbody id="tb"></tbody>
</table>
</div>

<div class="pagination" id="paginacion"></div>

</div>

<script>
const API='../api/pallets_contenedores.php';

let filtroAlmacen='';
let verInactivos=0;
let pagina=1;

function loadCards(){
 fetch('../api/pallets_contenedores_kpi.php?action=kpi')
 .then(r=>r.json())
 .then(rows=>{
   let h='';
   rows.forEach(x=>{
     h+=`
     <div class="ap-card ${filtroAlmacen===x.almac_clave?'active':''}"
      onclick="setAlmacen('${x.almac_clave}')">
       <div class="h">
         <b>${x.almac_clave}</b>
         <span class="ap-chip ok">${x.activas} Act</span>
       </div>
       <div>
         <span class="ap-chip">Total ${x.total}</span>
         <span class="ap-chip warn">Inac ${x.inactivas}</span>
       </div>
     </div>`;
   });
   cards.innerHTML=h;
 });
}

function setAlmacen(clave){
 filtroAlmacen=clave;
 pagina=1;
 loadCards();
 cargar();
}

function buscar(){ pagina=1; cargar(); }
function limpiar(){
 document.getElementById('q').value='';
 document.getElementById('tipo').value='';
 filtroAlmacen='';
 pagina=1;
 loadCards();
 cargar();
}
function toggleInactivos(){ verInactivos=verInactivos?0:1; cargar(); }

function cargar(){
 const tipo=document.getElementById('tipo').value;
 const q=document.getElementById('q').value;

 const url=API+'?action=list'
   +'&almac_clave='+encodeURIComponent(filtroAlmacen)
   +'&tipo='+encodeURIComponent(tipo)
   +'&q='+encodeURIComponent(q)
   +'&inactivos='+verInactivos
   +'&page='+pagina;

 fetch(url)
 .then(r=>r.json())
 .then(resp=>{
   let h='';
   resp.rows.forEach(r=>{
     h+=`
     <tr>
       <td>${r.almac_clave||''}</td>
       <td><b>${r.Clave_Contenedor}</b></td>
       <td>${r.tipo}</td>
       <td>${r.Pedido||''}</td>
       <td>${r.CveLP||''}</td>
       <td>${r.Activo==1?'Sí':'No'}</td>
     </tr>`;
   });
   tb.innerHTML=h||'<tr><td colspan="6">Sin resultados</td></tr>';

   renderPaginacion(resp.pagina,resp.total_paginas);
 });
}

function renderPaginacion(actual,total){
 let h='';
 for(let i=1;i<=total;i++){
   h+=`<button class="${i==actual?'active':''}" onclick="irPagina(${i})">${i}</button>`;
 }
 paginacion.innerHTML=h;
}

function irPagina(p){ pagina=p; cargar(); }

function exportar(){
 const tipo=document.getElementById('tipo').value;
 const q=document.getElementById('q').value;

 window.open(
   API+'?action=export_excel'
   +'&almac_clave='+encodeURIComponent(filtroAlmacen)
   +'&tipo='+encodeURIComponent(tipo)
   +'&q='+encodeURIComponent(q)
   +'&inactivos='+verInactivos
 );
}

document.addEventListener('DOMContentLoaded',()=>{
 loadCards();
 cargar();
});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
