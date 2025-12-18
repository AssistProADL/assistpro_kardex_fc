<?php
include __DIR__ . '/../bi/_menu_global.php';
?>

<style>
.table-sm td, .table-sm th {
  padding: 0.25rem;
  font-size: 11px;
  white-space: nowrap;
}
</style>

<div class="container-fluid">

  <!-- ================= HEADER ================= -->
  <div class="row mb-2">
    <div class="col-md-6">
      <h4 class="fw-bold">Planeación de Rutas | Asignación de Clientes</h4>
    </div>
    <div class="col-md-6 text-end">
      <a href="resumen_rutas.php" class="btn btn-outline-primary btn-sm">📊 Resumen</a>
      <a href="geo_distribucion_clientes.php" class="btn btn-outline-success btn-sm">🌍 Georreferencia</a>
    </div>
  </div>

  <!-- ================= FILTROS ================= -->
  <div class="card mb-2">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label">Almacén</label>
          <select id="f_almacen" class="form-select form-select-sm"></select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Buscar</label>
          <input id="f_buscar" class="form-control form-control-sm"
                 placeholder="Cliente / Destinatario / Colonia / CP">
        </div>
        <div class="col-md-2">
          <button id="btn_buscar" class="btn btn-primary btn-sm w-100">Buscar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ================= ACCIONES MASIVAS ================= -->
  <div class="card mb-2">
    <div class="card-body">
      <div class="row g-2 align-items-end">

        <div class="col-md-3">
          <label class="form-label fw-bold">Ruta destino (global)</label>
          <select id="ruta_global" class="form-select form-select-sm"></select>
        </div>

        <div class="col-md-5">
          <label class="form-label fw-bold">Días de visita</label><br>
          <label class="me-2"><input type="checkbox" class="dia-global" value="Lu"> L</label>
          <label class="me-2"><input type="checkbox" class="dia-global" value="Ma"> M</label>
          <label class="me-2"><input type="checkbox" class="dia-global" value="Mi"> M</label>
          <label class="me-2"><input type="checkbox" class="dia-global" value="Ju"> J</label>
          <label class="me-2"><input type="checkbox" class="dia-global" value="Vi"> V</label>
          <label class="me-2"><input type="checkbox" class="dia-global" value="Sa"> S</label>
        </div>

        <div class="col-md-4 text-end">
          <button id="btn_guardar" class="btn btn-success btn-sm">Guardar planeación</button>
        </div>

      </div>
    </div>
  </div>

  <!-- ================= TABLA ================= -->
  <div class="card">
    <div class="card-body p-1">
      <div style="max-height:450px; overflow:auto;">
        <table class="table table-bordered table-sm align-middle">
          <thead class="table-light">
            <tr>
              <th><input type="checkbox" id="chk_all"></th>
              <th>Cliente</th>
              <th>Destinatario</th>
              <th>Dirección</th>
              <th>Colonia</th>
              <th>CP</th>
              <th>Ciudad</th>
              <th>Estado</th>
              <th>Lat</th>
              <th>Lng</th>
              <th>Ruta Act</th>
              <th>Ruta New</th>
              <th>Días</th>
              <th>Seq</th>
            </tr>
          </thead>
          <tbody id="tabla_destinatarios">
            <tr>
              <td colspan="14" class="text-center text-muted">Seleccione un almacén</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<script>
let pagina = 1;

/* ===========================
   CARGA SELECTS
   =========================== */
function cargarSelect(url, id, placeholder, extra={}) {
  const fd = new FormData();
  Object.keys(extra).forEach(k => fd.append(k, extra[k]));
  fetch(url,{method:'POST',body:fd})
    .then(r=>r.json())
    .then(d=>{
      const s=document.getElementById(id);
      s.innerHTML=`<option value="">${placeholder}</option>`;
      d.forEach(o=>{
        s.innerHTML+=`<option value="${o.id}">${o.nombre}</option>`;
      });
    });
}

/* ===========================
   CARGA DATOS
   =========================== */
function cargarDatos(){
  const alm=document.getElementById('f_almacen').value;
  if(!alm) return;

  const fd=new FormData();
  fd.append('almacen',alm);
  fd.append('buscar',document.getElementById('f_buscar').value);
  fd.append('pagina',pagina);

  fetch('../api/clientes_asignacion_data.php',{method:'POST',body:fd})
    .then(r=>r.json())
    .then(renderTabla);
}

/* ===========================
   RENDER TABLA
   =========================== */
function renderTabla(resp){
  const tb=document.getElementById('tabla_destinatarios');
  tb.innerHTML='';
  if(!resp.data || resp.data.length===0){
    tb.innerHTML=`<tr><td colspan="14" class="text-center">Sin datos</td></tr>`;
    return;
  }

  resp.data.forEach(r=>{
    tb.innerHTML+=`
      <tr>
        <td><input type="checkbox" class="chk-row" data-id="${r.id}"></td>
        <td>[${r.clave_cliente}] ${r.cliente}</td>
        <td>[${r.id}] ${r.clave_destinatario ?? ''} ${r.destinatario}</td>
        <td>${r.direccion ?? ''}</td>
        <td>${r.colonia ?? ''}</td>
        <td>${r.postal ?? ''}</td>
        <td>${r.ciudad ?? ''}</td>
        <td>${r.estado ?? ''}</td>
        <td>${r.latitud ?? ''}</td>
        <td>${r.longitud ?? ''}</td>
        <td>${r.ruta ?? '--'}</td>
        <td>
          <select class="form-select form-select-sm ruta-fila">
            <option value="">(global)</option>
          </select>
        </td>
        <td class="dias-fila">
          <label><input type="checkbox" value="Lu">L</label>
          <label><input type="checkbox" value="Ma">M</label>
          <label><input type="checkbox" value="Mi">M</label>
          <label><input type="checkbox" value="Ju">J</label>
          <label><input type="checkbox" value="Vi">V</label>
          <label><input type="checkbox" value="Sa">S</label>
        </td>
        <td><input type="number" class="form-control form-control-sm secuencia" style="width:50px"></td>
      </tr>`;
  });
}

/* ===========================
   GUARDAR
   =========================== */
document.getElementById('btn_guardar').onclick=()=>{

  const almacen=document.getElementById('f_almacen').value;
  const rutaGlobal=document.getElementById('ruta_global').value;

  const diasGlobal=[...document.querySelectorAll('.dia-global:checked')].map(d=>d.value);

  const items=[];
  document.querySelectorAll('#tabla_destinatarios tr').forEach(tr=>{
    const chk=tr.querySelector('.chk-row');
    if(!chk || !chk.checked) return;

    items.push({
      id_destinatario: chk.dataset.id,
      ruta: tr.querySelector('.ruta-fila').value,
      dias: [...tr.querySelectorAll('.dias-fila input:checked')].map(d=>d.value),
      secuencia: tr.querySelector('.secuencia').value
    });
  });

  if(items.length===0){alert('Seleccione al menos un destinatario');return;}
  if(!rutaGlobal && items.every(i=>!i.ruta)){alert('Seleccione ruta');return;}
  if(diasGlobal.length===0 && items.every(i=>i.dias.length===0)){alert('Seleccione días');return;}

  fetch('../api/clientes_asignacion_save.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({
      almacen:almacen,
      ruta_global:rutaGlobal,
      dias_global:diasGlobal,
      items:items
    })
  })
  .then(r=>r.json())
  .then(resp=>{
    alert(resp.mensaje || 'Planeación guardada');
    cargarDatos();
  });
};

/* ===========================
   EVENTOS
   =========================== */
document.getElementById('btn_buscar').onclick=()=>{pagina=1;cargarDatos();};
document.getElementById('chk_all').onchange=e=>{
  document.querySelectorAll('.chk-row').forEach(c=>c.checked=e.target.checked);
};

document.addEventListener('DOMContentLoaded',()=>{
  cargarSelect('../api/catalogo_almacenes.php','f_almacen','Seleccione Almacén');
  cargarSelect('../api/catalogo_rutas.php','ruta_global','Seleccione Ruta');

  document.getElementById('f_almacen').addEventListener('change',e=>{
    cargarSelect('../api/catalogo_rutas.php','ruta_global','Seleccione Ruta',{almacen:e.target.value});
    cargarDatos();
  });
});
</script>

<?php
include __DIR__ . '/../bi/_menu_global_end.php';
?>
