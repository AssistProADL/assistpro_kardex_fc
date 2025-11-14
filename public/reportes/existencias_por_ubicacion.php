<?php
// /public/reportes/existencias_por_ubicacion.php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php'; // menú corporativo
?>
<div class="container-fluid p-2">
  <?php include __DIR__ . '/../partials/filtros_assistpro.php'; ?>

  <!-- KPIs -->
  <div class="row g-2 mb-2" style="font-size:10px;">
    <div class="col-12 col-md-3">
      <div class="card" style="border-left:4px solid #0F5AAD;">
        <div class="card-body p-2">
          <div class="text-muted">Filas (coincidencias)</div>
          <div id="kpi_total_filas" style="font-size:18px;font-weight:700;">0</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card" style="border-left:4px solid #00A3E0;">
        <div class="card-body p-2">
          <div class="text-muted">LP únicos</div>
          <div id="kpi_lp_unicos" style="font-size:18px;font-weight:700;">0</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card" style="border-left:4px solid #099;">
        <div class="card-body p-2">
          <div class="text-muted">Existencia total</div>
          <div id="kpi_total_exist" style="font-size:18px;font-weight:700;">0</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Grilla -->
  <div class="card">
    <div class="card-body p-2">
      <div class="table-responsive" style="font-size:10px; max-height:60vh; overflow:auto;">
        <table class="table table-sm table-striped table-hover">
          <thead class="table-light" style="position:sticky; top:0; z-index:1;">
            <tr>
              <th>#</th>
              <th>Empresa</th>
              <th>Almacén</th>
              <th>Zona</th>
              <th>BL (codigocsd)</th>
              <th>Tipo BL</th>
              <th>Status</th>
              <th>Pasillo</th>
              <th>Rack</th>
              <th>Nivel</th>
              <th>SKU</th>
              <th>Producto</th>
              <th>UM</th>
              <th>LP</th>
              <th>Tipo LP</th>
              <th>Lote</th>
              <th>Serie</th>
              <th>Existencia</th>
              <th>Actualizado</th>
            </tr>
          </thead>
          <tbody id="tb_rows"></tbody>
        </table>
      </div>
      <div class="d-flex justify-content-between align-items-center mt-2" style="font-size:10px;">
        <div><small class="text-muted">* Mostrando 25 registros por defecto (ajustable).</small></div>
        <div class="d-flex gap-2">
          <button class="btn btn-light btn-sm" id="btn_prev">Anterior</button>
          <button class="btn btn-light btn-sm" id="btn_next">Siguiente</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const API_REP = '/public/api/reporte_existencias_ubic.php';
let limit = 25, offset = 0, lastPayload = {};

function pintaKPIs(k){
  document.getElementById('kpi_total_filas').textContent = (k?.total_filas ?? 0);
  document.getElementById('kpi_lp_unicos').textContent   = (k?.lp_unicos ?? 0);
  document.getElementById('kpi_total_exist').textContent  = (k?.total_existencia ?? 0);
}
function pintaTabla(rows){
  const tb = document.getElementById('tb_rows');
  tb.innerHTML = '';
  rows.forEach((r, i)=>{
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${offset + i + 1}</td>
      <td>${r.empresa ?? ''}</td>
      <td>${r.almacen ?? ''}</td>
      <td>${r.zona ?? ''}</td>
      <td>${r.bl ?? ''}</td>
      <td>${r.tipo_bl ?? ''}</td>
      <td>${r.status_bl ?? ''}</td>
      <td>${r.pasillo ?? ''}</td>
      <td>${r.rack ?? ''}</td>
      <td>${r.nivel ?? ''}</td>
      <td>${r.sku ?? ''}</td>
      <td>${r.producto ?? ''}</td>
      <td>${r.um ?? ''}</td>
      <td>${r.lp ?? ''}</td>
      <td>${r.lp_tipo ?? ''}</td>
      <td>${r.lote ?? ''}</td>
      <td>${r.serie ?? ''}</td>
      <td class="text-end">${(r.existencia ?? 0)}</td>
      <td>${(r.updated_at ?? '').toString().slice(0,19).replace('T',' ')}</td>
    `;
    tb.appendChild(tr);
  });
}
async function consulta(payload){
  const res = await fetch(API_REP, {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({...payload, limit, offset})
  });
  const json = await res.json();
  if(!json.ok){ alert('Error: '+json.error); return; }
  pintaKPIs(json.kpi);
  pintaTabla(json.rows);
  // Habilita/deshabilita paginación
  document.getElementById('btn_prev').disabled = (offset === 0);
  const masPosibles = (offset + limit) < (json.kpi?.total_filas ?? 0);
  document.getElementById('btn_next').disabled = !masPosibles;
}

document.addEventListener('assistpro:filtros:aplicar', (e)=>{
  offset = 0; // reset página
  lastPayload = e.detail || {};
  consulta(lastPayload);
});

document.getElementById('btn_prev').addEventListener('click', ()=>{
  if(offset===0) return;
  offset = Math.max(0, offset - limit);
  consulta(lastPayload);
});
document.getElementById('btn_next').addEventListener('click', ()=>{
  offset += limit;
  consulta(lastPayload);
});

// Carga inicial vacía (o podrías forzar una empresa por defecto)
consulta({});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
