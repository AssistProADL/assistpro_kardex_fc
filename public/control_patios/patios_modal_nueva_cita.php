<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$fecha     = $_GET['fecha'] ?? date('Y-m-d');
$cve_almac = $_GET['cve_almac'] ?? '';
$hora_ini  = $_GET['hora_inicio'] ?? '';
$rampa     = $_GET['rampa'] ?? '';
?>

<style>
.ap-wrap *{ font-size:11px !important; }
.ap-card{
  background:#fff;
  border:1px solid #e9ecef;
  padding:15px;
  border-radius:6px;
  max-width:800px;
}
.ap-title{
  font-size:16px;
  font-weight:700;
  color:#0b3a82;
  margin-bottom:15px;
}
.form-row{
  display:flex;
  gap:10px;
  margin-bottom:10px;
}
.form-row > div{
  flex:1;
}
</style>

<div class="ap-wrap">
  <div class="ap-card">
    <div class="ap-title">Nueva Cita</div>

    <div class="form-row">
      <div>
        <label>Empresa</label>
        <select id="empresa" class="form-control form-control-sm">
          <option value="">Seleccione</option>
        </select>
      </div>
      <div>
        <label>Almacén</label>
        <input type="number" id="cve_almac" class="form-control form-control-sm"
               value="<?=htmlspecialchars($cve_almac)?>">
      </div>
    </div>

    <div class="form-row">
      <div>
        <label>Flujo</label>
        <select id="tipo_flujo" class="form-control form-control-sm">
          <option value="IN">IN</option>
          <option value="OUT">OUT</option>
        </select>
      </div>
      <div>
        <label>Fecha</label>
        <input type="date" id="fecha" class="form-control form-control-sm"
               value="<?=htmlspecialchars($fecha)?>">
      </div>
    </div>

    <div class="form-row">
      <div>
        <label>Hora inicio</label>
        <input type="time" id="hora_inicio" class="form-control form-control-sm"
               value="<?=htmlspecialchars($hora_ini)?>">
      </div>
      <div>
        <label>Hora fin</label>
        <input type="time" id="hora_fin" class="form-control form-control-sm">
      </div>
    </div>

    <div class="form-row">
      <div>
        <label>Rampa</label>
        <input type="number" id="rampa" class="form-control form-control-sm"
               value="<?=htmlspecialchars($rampa)?>">
      </div>
      <div>
        <label>Referencia</label>
        <input type="text" id="referencia" class="form-control form-control-sm">
      </div>
    </div>

    <div class="form-row">
      <div>
        <label>Placa</label>
        <input type="text" id="placa" class="form-control form-control-sm">
      </div>
      <div>
        <label>Operador</label>
        <input type="text" id="operador" class="form-control form-control-sm">
      </div>
    </div>

    <div style="margin-top:15px;">
      <button class="btn btn-sm btn-success" onclick="guardar()">Guardar</button>
      <button class="btn btn-sm btn-secondary" onclick="window.history.back()">Cancelar</button>
    </div>

  </div>
</div>

<script>
const API = '../../api/control_patios/patios_planeador_api.php';

async function guardar(){

  const data = new URLSearchParams();
  data.append('action','create');
  data.append('empresa_id', document.getElementById('empresa').value || 1);
  data.append('cve_almac', document.getElementById('cve_almac').value);
  data.append('tipo_flujo', document.getElementById('tipo_flujo').value);
  data.append('fecha', document.getElementById('fecha').value);
  data.append('hora_inicio', document.getElementById('hora_inicio').value);
  data.append('hora_fin', document.getElementById('hora_fin').value);
  data.append('rampa_posicion_id', document.getElementById('rampa').value);
  data.append('referencia', document.getElementById('referencia').value);
  data.append('placa', document.getElementById('placa').value);
  data.append('operador', document.getElementById('operador').value);
  data.append('canal','INTERNO');

  const res = await fetch(API,{
    method:'POST',
    body:data
  });

  const json = await res.json();

  if(!json.ok){
    alert(json.msg);
    return;
  }

  alert('Cita creada correctamente');
  window.location.href='patios_planeador.php';
}
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>