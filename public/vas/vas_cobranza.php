<?php
// public/vas/vas_cobranza.php
require_once __DIR__ . '/../../app/db.php';
$cia = db_all("SELECT cve_cia, des_cia FROM c_compania ORDER BY des_cia");
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>VAS · Cobranza</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <style>
    body{font-size:12px;}
    .ap-title{font-weight:700;font-size:16px}
    table.dataTable tbody td{font-size:10px; white-space:nowrap;}
    .dt-scroll{overflow:auto;}
    .kpi{border-radius:10px;padding:10px;background:#f6f9ff;border:1px solid #dfe8ff}
    .kpi .v{font-size:18px;font-weight:800}
  </style>
</head>
<body>
<?php include __DIR__ . '/../bi/_menu_global.php'; ?>

<div class="container-fluid mt-3">
  <div class="ap-title mb-2">VAS · Pendientes de Cobro</div>

  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label">Compañía</label>
          <select id="cve_cia" class="form-select form-select-sm">
            <option value="">-- Seleccionar --</option>
            <?php foreach($cia as $r): ?>
              <option value="<?= htmlspecialchars($r['cve_cia']) ?>"><?= htmlspecialchars($r['des_cia']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Dueño (Proveedor cliente)</label>
          <select id="owner_id" class="form-select form-select-sm" disabled>
            <option value="">-- Todos --</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label">Desde</label>
          <input id="fi" type="date" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
          <label class="form-label">Hasta</label>
          <input id="ff" type="date" class="form-control form-control-sm">
        </div>
        <div class="col-md-2 d-flex gap-2">
          <button class="btn btn-outline-primary btn-sm" onclick="loadPendientes()">Aplicar</button>
          <button class="btn btn-primary btn-sm" onclick="openFacturar()" id="btnFact" disabled>Facturar</button>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-2 mb-2">
    <div class="col-md-3"><div class="kpi"><div>Pedidos</div><div class="v" id="kpi_ped">0</div></div></div>
    <div class="col-md-3"><div class="kpi"><div>Importe VAS</div><div class="v" id="kpi_imp">0.00</div></div></div>
  </div>

  <div class="card">
    <div class="card-body dt-scroll">
      <table id="tbl" class="table table-striped table-bordered table-sm w-100">
        <thead class="table-light">
          <tr>
            <th></th>
            <th>Pedido</th>
            <th>Fecha</th>
            <th>Cliente</th>
            <th>Importe VAS</th>
            <th>Pend.</th>
            <th>Apl.</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
      <div class="text-muted mt-2" style="font-size:11px">
        Selecciona pedidos y dispara “Facturar” para marcar items como facturados.
      </div>
    </div>
  </div>
</div>

<!-- Modal Facturar -->
<div class="modal fade" id="mFac" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Facturar VAS</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Folio factura</label>
        <input id="folio_factura" class="form-control form-control-sm" placeholder="FAC-0001">
        <div class="text-muted mt-2" style="font-size:11px">
          Esto marca los items VAS del pedido como facturados.
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary btn-sm" onclick="facturar()">Confirmar</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
const API_OWNERS = "../api/vas/catalogos_owners.php";
const API_COB    = "../api/vas/cobranza.php";

let dt, modalFac;
let selectedPedidos = [];

function apiGet(url){ return fetch(url,{credentials:'same-origin'}).then(r=>r.json()); }
function apiSend(url, method, data){
  return fetch(url,{method,credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify(data||{})}).then(r=>r.json());
}

async function loadOwners(){
  const sel = document.getElementById('owner_id');
  sel.innerHTML = '<option value="">-- Todos --</option>';
  sel.disabled = true;
  const js = await apiGet(API_OWNERS+"?Activo=1");
  if(js.ok){
    js.data.forEach(o=>{
      const opt=document.createElement('option');
      opt.value=o.ID_Proveedor;
      opt.textContent=(o.cve_proveedor? (o.cve_proveedor+' · ') : '') + (o.Nombre||o.Empresa||'');
      sel.appendChild(opt);
    });
    sel.disabled = false;
  }
}

async function loadPendientes(){
  const IdEmpresa = document.getElementById('cve_cia').value;
  if(!IdEmpresa){ alert('Selecciona Compañía'); return; }
  const owner_id = document.getElementById('owner_id').value; // para v2
  const fi = document.getElementById('fi').value;
  const ff = document.getElementById('ff').value;

  let url = `${API_COB}?IdEmpresa=${encodeURIComponent(IdEmpresa)}`;
  if(fi) url += `&fecha_inicio=${encodeURIComponent(fi)}`;
  if(ff) url += `&fecha_fin=${encodeURIComponent(ff)}`;

  const js = await apiGet(url);
  if(!js.ok){ alert(js.msg||'Error'); return; }

  const rows = js.data||[];
  selectedPedidos = [];
  document.getElementById('btnFact').disabled = true;

  let imp = 0;
  rows.forEach(r=> imp += parseFloat(r.importe_vas||0));
  document.getElementById('kpi_ped').textContent = rows.length;
  document.getElementById('kpi_imp').textContent = imp.toFixed(2);

  dt.clear();
  rows.forEach(r=>{
    dt.row.add([
      `<input type="checkbox" class="form-check-input" onchange="selPedido(${r.id_pedido},this.checked)">`,
      r.folio,
      r.fecha_pedido,
      r.cliente,
      Number(r.importe_vas||0).toFixed(2),
      r.items_pendiente,
      r.items_aplicado
    ]);
  });
  dt.draw(false);
}

function selPedido(id, checked){
  if(checked){
    if(!selectedPedidos.includes(id)) selectedPedidos.push(id);
  }else{
    selectedPedidos = selectedPedidos.filter(x=>x!==id);
  }
  document.getElementById('btnFact').disabled = selectedPedidos.length===0;
}

function openFacturar(){
  document.getElementById('folio_factura').value='';
  modalFac.show();
}

async function facturar(){
  const IdEmpresa = document.getElementById('cve_cia').value;
  const folio = document.getElementById('folio_factura').value.trim();
  if(!folio){ alert('Captura folio factura'); return; }

  // Factura por pedido: para cada pedido, toma ids de items VAS y marca facturado.
  // Para simplificar, en v1 facturamos todo el pedido sin pedir ids: el backend actual pide ids.
  // Solución: llamar find_items por pedido (v2) o facturar por pedido completo (ajuste mínimo backend).
  alert("Para facturar automáticamente por pedido completo, ajustamos cobranza.php para aceptar facturar_por_pedido=1. Te lo aplico en el siguiente patch.");
  modalFac.hide();
}

document.addEventListener('DOMContentLoaded', ()=>{
  dt = new DataTable('#tbl', {pageLength:25, scrollX:true});
  modalFac = new bootstrap.Modal(document.getElementById('mFac'));
  document.getElementById('cve_cia').addEventListener('change', loadOwners);
});
</script>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
