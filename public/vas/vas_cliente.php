<?php
// public/vas/vas_cliente.php
require_once __DIR__ . '/../../app/db.php';

// Empresas (mismo patrón que servicios_vas.php)
$cia = db_all("SELECT cve_cia, des_cia FROM c_compania ORDER BY des_cia");

// Defaults
$defaultCia = (int)($cia[0]['cve_cia'] ?? 1);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>VAS · Servicios por Cliente</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

  <style>
    :root{ --corp:#0b2e83; }
    .page-title{ color:var(--corp); font-weight:700; letter-spacing:.2px; }
    .card-soft{ border:1px solid #e9eef7; border-radius:14px; box-shadow:0 6px 18px rgba(16,24,40,.06); }
    .card-soft .card-header{ background:rgba(11,46,131,.06); border-bottom:1px solid #e9eef7; font-weight:700; color:var(--corp); }
    .kpi{ font-weight:800; font-size:1.6rem; }
    table.dataTable tbody td{ padding-top:.45rem !important; padding-bottom:.45rem !important; vertical-align:middle; }
    .btn-xs{ padding:.2rem .45rem; font-size:.78rem; border-radius:.45rem; }
    .badge-soft{ background:rgba(11,46,131,.10); color:var(--corp); border:1px solid rgba(11,46,131,.18); }
    .select2-container .select2-selection--single{ height:38px; }
    .select2-container--default .select2-selection--single .select2-selection__rendered{ line-height:38px; }
    .select2-container--default .select2-selection--single .select2-selection__arrow{ height:38px; }
  </style>
</head>
<body>

<?php require_once __DIR__ . '/../bi/_menu_global.php'; ?>

<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <h4 class="page-title mb-0">VAS · Servicios por Cliente</h4>
      <div class="text-muted" style="font-size:.9rem;">Matriz de precios (vw_vas_servicios_cliente) con filtros por compañía y almacén.</div>
    </div>
    <span class="badge badge-soft">API</span>
  </div>

  <div class="card card-soft mb-3">
    <div class="card-header">Filtros</div>
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label mb-1">Compañía</label>
          <select id="IdEmpresa" class="form-select">
            <?php foreach($cia as $c): ?>
              <option value="<?= (int)$c['cve_cia'] ?>" <?= ((int)$c['cve_cia']===$defaultCia?'selected':'') ?>>
                <?= htmlspecialchars($c['des_cia']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label mb-1">Almacén (contexto)</label>
          <select id="cve_almac" class="form-select">
            <option value="Todos">Todos</option>
          </select>
          <div class="text-muted" style="font-size:.78rem" id="srcHint"></div>
        </div>

        <div class="col-md-4">
          <label class="form-label mb-1">Buscar</label>
          <input id="q" class="form-control" placeholder="cliente / servicio / clave" />
        </div>

        <div class="col-md-2 d-flex gap-2">
          <button id="btnAplicar" class="btn btn-primary w-100">Aplicar</button>
          <button id="btnLimpiar" class="btn btn-outline-secondary w-100">Limpiar</button>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="card card-soft">
        <div class="card-header">Servicios activos</div>
        <div class="card-body"><div class="kpi" id="kpiActivos">0</div></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card card-soft">
        <div class="card-header">Servicios total</div>
        <div class="card-body"><div class="kpi" id="kpiTotal">0</div></div>
      </div>
    </div>
  </div>

  <div class="card card-soft">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>Detalle</span>
      <small class="text-muted">Acciones a la izquierda · espaciado compacto</small>
    </div>
    <div class="card-body">
      <table id="tbl" class="table table-striped table-hover w-100">
        <thead>
          <tr>
            <th style="width:90px;">Acciones</th>
            <th>Cliente</th>
            <th>Servicio</th>
            <th>Tipo cobro</th>
            <th class="text-end">Precio base</th>
            <th class="text-end">Precio cliente</th>
            <th class="text-end">Precio final</th>
            <th>Moneda</th>
            <th style="width:70px;">Activo</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal edición -->
<div class="modal fade" id="mdlEdit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Editar precio cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="m_id_cliente">
        <input type="hidden" id="m_id_servicio">
        <input type="hidden" id="m_cve_almac">

        <div class="mb-2">
          <div class="fw-semibold" id="m_cliente"></div>
          <div class="text-muted" id="m_servicio" style="font-size:.9rem"></div>
        </div>

        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label mb-1">Precio base</label>
            <input id="m_precio_base" class="form-control" readonly>
          </div>
          <div class="col-md-6">
            <label class="form-label mb-1">Moneda</label>
            <input id="m_moneda" class="form-control" readonly>
          </div>

          <div class="col-md-6">
            <label class="form-label mb-1">Precio cliente</label>
            <input id="m_precio_cliente" type="number" step="0.01" class="form-control" placeholder="(vacío = usar base)">
          </div>
          <div class="col-md-6">
            <label class="form-label mb-1">Activo</label>
            <select id="m_activo" class="form-select">
              <option value="1">Sí</option>
              <option value="0">No</option>
            </select>
          </div>
        </div>

        <div class="alert alert-info mt-3 mb-0" style="font-size:.85rem">
          Estrategia comercial: si “Precio cliente” está vacío, el sistema cobra “Precio base”.
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button id="btnGuardar" class="btn btn-primary">Guardar</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
  const apiBase = '/assistpro_kardex_fc/public/api';
  const apiVas  = `${apiBase}/vas/clientes_servicios.php`;
  const apiAlm  = `${apiBase}/filtros_almacenes.php`;

  let dt;

  function money(v){
    if(v === null || v === undefined || v === '') return '';
    const n = Number(v);
    if(Number.isNaN(n)) return v;
    return n.toFixed(2);
  }

  async function loadAlmacenes(){
    const cve_cia = $('#IdEmpresa').val();
    const url = `${apiAlm}?action=almacenes&cve_cia=${encodeURIComponent(cve_cia)}`;
    $('#srcHint').text(`Fuente: ${url.replace(apiBase+'/','api/')}`);
    const res = await fetch(url);
    const j = await res.json();

    const $s = $('#cve_almac');
    const keep = $s.val() || 'Todos';
    $s.empty().append(`<option value="Todos">Todos</option>`);

    if(j && j.ok && Array.isArray(j.data)){
      j.data.forEach(r=>{
        const v = r.clave ? r.clave : (r.almacenp_id ?? r.almacen_id ?? '');
        const label = r.nombre ?? v;
        if(v) $s.append(`<option value="${String(v).replaceAll('"','&quot;')}">${label}</option>`);
      });
    }
    $s.val(keep);
    $s.trigger('change.select2');
  }

  async function loadGrid(){
    const IdEmpresa = $('#IdEmpresa').val();
    const cve_almac = $('#cve_almac').val();
    const q = $('#q').val().trim();

    const url = `${apiVas}?action=grid&IdEmpresa=${encodeURIComponent(IdEmpresa)}&cve_almac=${encodeURIComponent(cve_almac)}&q=${encodeURIComponent(q)}`;
    const res = await fetch(url);
    const j = await res.json();

    const rows = (j && j.ok && j.data && Array.isArray(j.data.rows)) ? j.data.rows : [];
    const kpi  = (j && j.ok && j.data && j.data.kpi) ? j.data.kpi : {total:0,activos:0};

    $('#kpiTotal').text(kpi.total ?? 0);
    $('#kpiActivos').text(kpi.activos ?? 0);

    dt.clear().rows.add(rows).draw();
  }

  function openEdit(row){
    $('#m_id_cliente').val(row.id_cliente);
    $('#m_id_servicio').val(row.id_servicio);
    $('#m_cve_almac').val(row.cve_almac || '');

    $('#m_cliente').text(row.cliente || row.RazonSocial || '');
    $('#m_servicio').text(`${row.clave_servicio || ''} · ${row.servicio || ''}`);

    $('#m_precio_base').val(money(row.precio_base));
    $('#m_moneda').val(row.moneda || 'MXN');
    $('#m_precio_cliente').val(row.precio_cliente ?? '');
    $('#m_activo').val(String(row.Activo ?? 1));

    const mdl = new bootstrap.Modal(document.getElementById('mdlEdit'));
    mdl.show();
  }

  async function saveEdit(){
    const payload = {
      IdEmpresa: Number($('#IdEmpresa').val()),
      cve_almac: $('#m_cve_almac').val() || null,
      id_cliente: Number($('#m_id_cliente').val()),
      id_servicio: Number($('#m_id_servicio').val()),
      precio_cliente: ($('#m_precio_cliente').val().trim() === '' ? null : Number($('#m_precio_cliente').val())),
      Activo: Number($('#m_activo').val()),
      usuario: 'API'
    };

    const res = await fetch(apiVas, {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify(payload)
    });
    const j = await res.json();
    if(!j.ok){
      alert(j.msg || 'No se pudo guardar');
      return;
    }
    bootstrap.Modal.getInstance(document.getElementById('mdlEdit')).hide();
    await loadGrid();
  }

  $(async function(){
    $('#IdEmpresa, #cve_almac').select2({ width:'100%' });

    dt = $('#tbl').DataTable({
      pageLength: 25,
      lengthMenu: [10,25,50,100],
      order: [[1,'asc'],[2,'asc']],
      data: [],
      columns: [
        { data: null, orderable:false, render: (d,t,row)=>{
            return `
              <button class="btn btn-outline-primary btn-xs me-1" data-act="edit">Editar</button>
            `;
          }
        },
        { data: 'cliente', render:(d,t,row)=> (row.cliente || row.RazonSocial || '') },
        { data: 'servicio' },
        { data: 'tipo_cobro' },
        { data: 'precio_base', className:'text-end', render:(d)=> money(d) },
        { data: 'precio_cliente', className:'text-end', render:(d)=> (d===null||d===undefined||d===''? '' : money(d)) },
        { data: 'precio_final', className:'text-end', render:(d)=> money(d) },
        { data: 'moneda' },
        { data: 'Activo', render:(d)=> (Number(d)===1?'<span class="badge text-bg-success">Sí</span>':'<span class="badge text-bg-secondary">No</span>') }
      ],
      language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' }
    });

    $('#tbl tbody').on('click','button[data-act="edit"]', function(){
      const row = dt.row($(this).closest('tr')).data();
      openEdit(row);
    });

    $('#btnGuardar').on('click', saveEdit);

    $('#btnAplicar').on('click', async ()=>{ await loadGrid(); });
    $('#btnLimpiar').on('click', async ()=>{
      $('#q').val('');
      $('#cve_almac').val('Todos').trigger('change');
      await loadGrid();
    });

    $('#IdEmpresa').on('change', async ()=>{
      await loadAlmacenes();
      await loadGrid();
    });

    await loadAlmacenes();
    await loadGrid();
  });
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
