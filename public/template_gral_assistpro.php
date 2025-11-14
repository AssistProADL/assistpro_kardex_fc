<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>
<div class="container-fluid p-2" style="font-size:10px;">
  <?php include __DIR__ . '/../partials/filtros_assistpro.php'; ?>

  <div class="d-flex align-items-center justify-content-between mb-2">
    <h6 class="m-0" style="color:#0F5AAD; font-weight:700;">Template Gral AssistPro</h6>
    <div class="small text-muted">Filtros conectados a BD real</div>
  </div>

  <!-- KPIs (se llenarán cuando conectemos reporte) -->
  <div class="row g-2 mb-2">
    <div class="col-6 col-md-3">
      <div class="card" style="border-left:4px solid #0F5AAD;">
        <div class="card-body p-2">
          <div class="text-muted">Registros</div>
          <div id="kpi_total" style="font-size:18px;font-weight:700;">0</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card" style="border-left:4px solid #00A3E0;">
        <div class="card-body p-2">
          <div class="text-muted">LP únicos</div>
          <div id="kpi_lp" style="font-size:18px;font-weight:700;">0</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Grilla (vacía hasta que conectemos endpoint de reporte) -->
  <div class="card">
    <div class="card-body p-2">
      <div class="table-responsive" style="max-height:60vh; overflow:auto;">
        <table class="table table-sm table-striped table-hover">
          <thead class="table-light" style="position:sticky; top:0; z-index:1;">
            <tr>
              <th>#</th>
              <th>Empresa</th>
              <th>Almacén</th>
              <th>Zona</th>
              <th>BL</th>
              <th>Producto</th>
              <th>LP</th>
              <th>Lote</th>
              <th>Serie</th>
              <th>Existencia</th>
              <th>Fecha</th>
            </tr>
          </thead>
          <tbody id="tb_rows">
            <tr><td colspan="11" class="text-center text-muted">Aplica filtros para consultar.</td></tr>
          </tbody>
        </table>
      </div>
      <div id="ap_alert" class="alert alert-warning mt-2 p-1" style="display:none;font-size:10px;"></div>
    </div>
  </div>
</div>

<script>
// No hay datos dummy. Solo mostramos un aviso de conexión de filtros.
(async ()=>{
  try{
    const res = await fetch('/assistpro_kardex_fc/public/api/filtros_assistpro.php?fn=base');
    if(!res.ok) throw new Error(`HTTP ${res.status}`);
    const json = await res.json();
    if(!json || !json.empresas){ throw new Error('Respuesta vacía'); }
    const box = document.getElementById('ap_alert');
    box.style.display='block';
    box.className='alert alert-success mt-2 p-1';
    box.textContent='✅ API de filtros conectada. Selecciona filtros para consultar.';
    console.log('Empresas:', json.empresas?.length||0,
                'Productos:', json.productos?.length||0,
                'Terceros:', json.terceros?.length||0);
  }catch(e){
    const box = document.getElementById('ap_alert');
    box.style.display='block';
    box.className='alert alert-danger mt-2 p-1';
    box.textContent='❌ Error conectando con API de filtros.';
    console.error(e);
  }
})();
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
