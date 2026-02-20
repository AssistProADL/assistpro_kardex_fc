<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$fecha = $_GET['fecha'] ?? date('Y-m-d');
$cve_almac = $_GET['cve_almac'] ?? 100; // default patio
?>

<style>
:root{
  --corp-blue:#0b3a82;
  --ok:#198754;
  --warn:#ffc107;
  --danger:#dc3545;
  --card-border:#e9ecef;
}
.ap-wrap, .ap-wrap *{ font-size:10px !important; }
.ap-title{
  font-size:18px !important;
  font-weight:700;
  color:var(--corp-blue);
  margin-bottom:10px;
}
.ap-toolbar{
  display:flex;
  gap:10px;
  margin-bottom:10px;
}
.ap-grid{
  border:1px solid var(--card-border);
  overflow:auto;
  max-height:520px;
  background:#fff;
}
.ap-table{
  border-collapse:collapse;
  width:100%;
  min-width:1200px;
}
.ap-table th{
  background:#f8f9fa;
  position:sticky;
  top:0;
  z-index:2;
  border:1px solid #dee2e6;
  text-align:center;
  padding:4px;
}
.ap-table td{
  border:1px solid #dee2e6;
  height:60px;
  vertical-align:top;
  padding:2px;
}
.slot{
  cursor:pointer;
}
.cita{
  border-radius:4px;
  padding:2px 4px;
  color:#fff;
  font-weight:600;
  margin-bottom:2px;
}
.status-CONFIRMADA{ background:var(--ok); }
.status-PROGRAMADA{ background:#6c757d; }
.status-EN_RAMPA{ background:var(--warn); color:#000; }
.status-FINALIZADA{ background:#343a40; }
.status-CANCELADA{ background:var(--danger); }
</style>

<div class="ap-wrap">
  <div class="ap-title">Planeador de Citas – Control de Patios</div>

  <div class="ap-toolbar">
    <input type="date" id="f_fecha" value="<?=htmlspecialchars($fecha)?>" class="form-control form-control-sm" style="width:150px">
    <input type="number" id="f_almac" value="<?=htmlspecialchars($cve_almac)?>" class="form-control form-control-sm" style="width:90px">
    <button class="btn btn-sm btn-primary" onclick="cargar()">Cargar</button>
    <button class="btn btn-sm btn-success" onclick="nueva()">Nueva Cita</button>
  </div>

  <div class="ap-grid">
    <table class="ap-table" id="tablaPlaneador">
      <thead>
        <tr id="theadRampas"></tr>
      </thead>
      <tbody>
        <tr id="filaHoras"></tr>
      </tbody>
    </table>
  </div>
</div>

 
<script>
const API = '../api/control_patios/patios_planeador_api.php';

let HORAS = [];
let RAMPAS = [];

function generarHoras(){
  HORAS = [];
  for(let h=6; h<=22; h++){
    HORAS.push((h<10?'0':'')+h+":00");
  }
}

async function cargar(){
  generarHoras();

  const fecha=document.getElementById('f_fecha').value;
  const almac=document.getElementById('f_almac').value;

  const res=await fetch(`${API}?action=calendar&cve_almac=${almac}&fecha=${fecha}`);
  const json=await res.json();

  if(!json.ok){ alert(json.msg); return; }

  RAMPAS = json.data.rampas || [];
  const citas = json.data.citas || [];

  construirTabla();
  pintarCitas(citas);
}

function construirTabla(){
  const thead=document.getElementById('theadRampas');
  thead.innerHTML='<th>Hora</th>';

  RAMPAS.forEach(r=>{
    thead.innerHTML+=`<th>${r.codigo}</th>`;
  });

  const tbody=document.getElementById('filaHoras');
  tbody.innerHTML='';

  HORAS.forEach(h=>{
    let row=`<tr data-hora="${h}">
              <td style="width:70px;text-align:center;font-weight:bold">${h}</td>`;
    RAMPAS.forEach(r=>{
      row+=`<td class="slot" data-rampa="${r.id}" onclick="crearEnSlot('${h}',${r.id})"></td>`;
    });
    row+='</tr>';
    tbody.innerHTML+=row;
  });
}

function pintarCitas(citas){
  citas.forEach(c=>{

    if(!c.rampa_posicion_id) return;

    const inicio=c.hora_inicio.substring(0,5);
    const fin=c.hora_fin.substring(0,5);

    const fila=document.querySelector(`tr[data-hora="${inicio}"]`);
    if(!fila) return;

    const cell=fila.querySelector(`td[data-rampa="${c.rampa_posicion_id}"]`);
    if(!cell) return;

    const duracion = calcularBloques(inicio, fin);

    cell.innerHTML+=`
      <div class="cita status-${c.estatus}" 
           style="height:${duracion*60}px"
           onclick="detalle(${c.id});event.stopPropagation();">
        ${c.folio}<br>
        ${c.referencia ?? ''}<br>
        ${c.placa ?? ''}
      </div>
    `;
  });
}

function calcularBloques(inicio, fin){
  const i1 = HORAS.indexOf(inicio);
  const i2 = HORAS.indexOf(fin);
  if(i1<0 || i2<0) return 1;
  return Math.max(1, i2 - i1);
}

function nueva(){
  const fecha=document.getElementById('f_fecha').value;
  const almac=document.getElementById('f_almac').value;
  window.location.href=`patios_modal_nueva_cita.php?fecha=${fecha}&cve_almac=${almac}`;
}

function crearEnSlot(hora,pos){
  const fecha=document.getElementById('f_fecha').value;
  const almac=document.getElementById('f_almac').value;
  window.location.href=`patios_modal_nueva_cita.php?fecha=${fecha}&cve_almac=${almac}&hora_inicio=${hora}&rampa=${pos}`;
}

function detalle(id){
  window.location.href=`patios_estado_oc.php?cita_id=${id}`;
}

cargar();
</script>


<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
