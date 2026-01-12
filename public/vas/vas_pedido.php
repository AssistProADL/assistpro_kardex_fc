<?php
// public/vas/vas_pedido.php
require_once __DIR__ . '/../../app/db.php';
$cia = db_all("SELECT cve_cia, des_cia FROM c_compania ORDER BY des_cia");
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>VAS · Pedido</title>
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
  <div class="ap-title mb-2">VAS · Servicios por Pedido</div>

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
          <label class="form-label">Folio pedido</label>
          <input id="folio" class="form-control form-control-sm" placeholder="Ej. 00012345">
        </div>
        <div class="col-md-2">
          <button class="btn btn-outline-primary btn-sm" onclick="buscarPedido()">Buscar</button>
        </div>
        <div class="col-md-4 text-end">
          <button class="btn btn-primary btn-sm" onclick="openAdd()" id="btnAdd" disabled>+ Agregar servicio</button>
        </div>
      </div>
      <div class="mt-2" id="pedidoInfo" style="font-size:11px;color:#6c757d"></div>
    </div>
  </div>

  <div class="row g-2 mb-2">
    <div class="col-md-3"><div class="kpi"><div>Total VAS</div><div class="v" id="kpi_total">0.00</div></div></div>
    <div class="col-md-3"><div class="kpi"><div>Items</div><div class="v" id="kpi_items">0</div></div></div>
  </div>

  <div class="card">
    <div class="card-body dt-scroll">
      <table id="tbl" class="table table-striped table-bordered table-sm w-100">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Servicio</th>
            <th>Cantidad</th>
            <th>Precio</th>
            <th>Total</th>
            <th>Estatus</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Agregar -->
<div class="modal fade" id="mAdd" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Agregar servicio VAS</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Servicio</label>
          <select id="id_servicio" class="form-select form-select-sm"></select>
        </div>
        <div class="row g-2">
          <div class="col-6">
            <label class="form-label">Cantidad</label>
            <input id="cantidad" type="number" step="0.01" class="form-control form-control-sm" value="1">
          </div>
          <div class="col-6">
            <label class="form-label">Precio unitario</label>
            <input id="precio_unitario" type="number" step="0.01" class="form-control form-control-sm" placeholder="opcional">
          </div>
        </div>
        <div class="text-muted mt-2" style="font-size:11px">
          Si dejas precio vacío, toma precio negociado del dueño o el base.
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-primary btn-sm" onclick="addServicio()">Agregar</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
const API_PEDSERV = "../api/vas/pedidos_servicios.php";
const API_SERV    = "../api/vas/servicios.php";

let dt, modalAdd;
let currentPedido = null;

function apiGet(url){ return fetch(url,{credentials:'same-origin'}).then(r=>r.json()); }
function apiSend(url, method, data){
  return fetch(url,{method,credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify(data||{})}).then(r=>r.json());
}

async function buscarPedido(){
  const IdEmpresa = document.getElementById('cve_cia').value;
  const folio = document.getElementById('folio').value.trim();
  if(!IdEmpresa){ alert('Selecciona Compañía'); return; }
  if(!folio){ alert('Captura folio'); return; }

  // localizar id_pedido por Fol_folio
  const js = await apiGet(`../api/vas/find_pedido.php?Fol_folio=${encodeURIComponent(folio)}`); // si no existe, lo creamos
  if(!js.ok){ alert(js.msg||'No encontrado'); return; }
  currentPedido = js.data;

  document.getElementById('pedidoInfo').innerHTML =
    `Pedido <b>${currentPedido.Fol_folio}</b> · Fecha ${currentPedido.Fec_Pedido} · Cliente ${currentPedido.Cve_clte} · Almacén ${currentPedido.cve_almac || '-'} · Owner ${currentPedido.Id_Proveedor || '-'}`
  ;

  document.getElementById('btnAdd').disabled = false;
  await loadItems();
  await loadServiciosCatalogo();
}

async function loadItems(){
  const IdEmpresa = document.getElementById('cve_cia').value;
  const js = await apiGet(`${API_PEDSERV}?IdEmpresa=${encodeURIComponent(IdEmpresa)}&id_pedido=${currentPedido.id_pedido}`);
  if(!js.ok){ alert(js.msg||'Error'); return; }

  const items = js.data.items || [];
  document.getElementById('kpi_items').textContent = items.length;
  document.getElementById('kpi_total').textContent = Number(js.data.totales.importe_vas||0).toFixed(2);

  dt.clear();
  items.forEach(r=>{
    dt.row.add([
      r.id,
      r.servicio,
      r.cantidad,
      Number(r.precio_unitario).toFixed(2),
      Number(r.total).toFixed(2),
      r.estatus,
      `<div class="btn-group btn-group-sm">
        <button class="btn btn-outline-danger" onclick="delItem(${r.id})">🗑️</button>
      </div>`
    ]);
  });
  dt.draw(false);
}

async function loadServiciosCatalogo(){
  const IdEmpresa = document.getElementById('cve_cia').value;
  const js = await apiGet(`${API_SERV}?IdEmpresa=${encodeURIComponent(IdEmpresa)}&Activo=1`);
  const sel = document.getElementById('id_servicio');
  sel.innerHTML = '';
  if(js.ok){
    (js.data||[]).forEach(s=>{
      const opt = document.createElement('option');
      opt.value = s.id_servicio;
      opt.textContent = `${s.clave_servicio} · ${s.nombre}`;
      sel.appendChild(opt);
    });
  }
}

function openAdd(){
  if(!currentPedido) return;
  document.getElementById('cantidad').value = 1;
  document.getElementById('precio_unitario').value = '';
  modalAdd.show();
}

async function addServicio(){
  const IdEmpresa = document.getElementById('cve_cia').value;
  const id_servicio = parseInt(document.getElementById('id_servicio').value||0);
  const cantidad = parseFloat(document.getElementById('cantidad').value||0);
  const precio_unitario_val = document.getElementById('precio_unitario').value.trim();
  const payload = { IdEmpresa, id_pedido: currentPedido.id_pedido, id_servicio, cantidad };
  if(precio_unitario_val!=='') payload.precio_unitario = parseFloat(precio_unitario_val);

  const js = await apiSend(`${API_PEDSERV}?IdEmpresa=${encodeURIComponent(IdEmpresa)}&id_pedido=${currentPedido.id_pedido}`, 'POST', payload);
  if(!js.ok){ alert(js.msg||'Error'); return; }
  modalAdd.hide();
  loadItems();
}

async function delItem(id){
  const IdEmpresa = document.getElementById('cve_cia').value;
  if(!confirm('¿Eliminar item VAS?')) return;
  const js = await apiSend(`${API_PEDSERV}?IdEmpresa=${encodeURIComponent(IdEmpresa)}&id_pedido=${currentPedido.id_pedido}&id=${id}`, 'DELETE', {IdEmpresa});
  if(!js.ok){ alert(js.msg||'Error'); return; }
  loadItems();
}

document.addEventListener('DOMContentLoaded', ()=>{
  dt = new DataTable('#tbl', {pageLength:25, scrollX:true, order:[[0,'desc']]});
  modalAdd = new bootstrap.Modal(document.getElementById('mAdd'));
});
</script>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
