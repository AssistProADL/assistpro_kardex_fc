<?php
// public/putaway/rtm_general.php
// RTM - Ready To Move (General) - Vista sin precarga, con filtros + grid + detalle en modal

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Ajusta estos includes según tu estructura real si ya tienes layout corporativo.
// No incluyo archivos inexistentes.
$ROOT = realpath(__DIR__ . '/../../');
require_once $ROOT . '/app/db.php';

// Si tienes menú global corporativo, descomenta y ajusta ruta.
// include_once __DIR__ . '/../_menu_global.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$empresa_id = isset($_GET['empresa_id']) ? (int)$_GET['empresa_id'] : 0;
$almacen    = isset($_GET['almacen']) ? trim($_GET['almacen']) : '';
$zona       = isset($_GET['zona_recibo']) ? trim($_GET['zona_recibo']) : '';

$API_URL = '../api/putaway/rtm_api.php'; // relativo desde /public/putaway/
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>RTM - Ready To Move (General)</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- FontAwesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <!-- DataTables (v1.x) -->
  <link href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.dataTables.min.css" rel="stylesheet">

  <style>
    body{ background:#f6f8fb; }
    .ap-title{ font-weight:800; font-size:34px; color:#0b4aa2; letter-spacing:-0.5px; }
    .ap-subtitle{ color:#5b6b82; font-size:12px; }
    .ap-card{ border:1px solid #e7ecf3; border-radius:14px; background:#fff; box-shadow: 0 6px 18px rgba(13,33,72,.06); }
    .ap-kpi-label{ font-size:11px; color:#6b7b92; text-transform:uppercase; letter-spacing:.04em; }
    .ap-kpi-val{ font-size:22px; font-weight:800; color:#0b2b5b; }
    .ap-filters .form-select, .ap-filters .form-control{ border-radius:12px; }
    .btn-ap{ border-radius:12px; font-weight:700; }
    table.dataTable thead th{ font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:#4b5a73; }
    table.dataTable tbody td{ font-size:12px; vertical-align:middle; }
    .badge-pill{ border-radius:999px; padding:.25rem .55rem; font-weight:800; }
    .overlay{
      position:fixed; inset:0; background:rgba(255,255,255,.75); display:none;
      align-items:center; justify-content:center; z-index:2000;
    }
    .overlay .box{
      background:#fff; border:1px solid #e7ecf3; border-radius:16px; padding:18px 22px;
      box-shadow:0 16px 38px rgba(13,33,72,.12);
      min-width:260px; text-align:center;
    }
    .overlay .spinner-border{ width:2.2rem; height:2.2rem; }
    .dt-actions .btn{ border-radius:10px; font-weight:700; }
    .modal-xl{ max-width:1200px; }
    .small-muted{ font-size:12px; color:#6b7b92; }
  </style>
</head>

<body>
  <!-- Si ya tienes sidebar global en tu layout, mantén tu include y elimina este container extra -->
  <div class="container-fluid py-3">
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-2">
      <div>
        <div class="ap-title"><i class="fa-solid fa-dolly me-2"></i>RTM · Ready To Move (General)</div>
        <div class="ap-subtitle">KPIs reales (sin precarga). Folios por filtros. Detalle bajo demanda (modal) vía acción “Ver”.</div>
      </div>
      <div>
        <a class="btn btn-outline-primary btn-ap" href="putaway_acomodo.php?modo=ACOMODO">
          <i class="fa-solid fa-warehouse me-1"></i> PutAway (Acomodo)
        </a>
      </div>
    </div>

    <!-- Filtros (NO tocar estructura, solo IDs estables) -->
    <div class="ap-card p-3 ap-filters mb-3">
      <div class="row g-2 align-items-end">
        <div class="col-12 col-md-5">
          <label class="form-label small fw-bold mb-1">Empresa</label>
          <select id="empresa_id" class="form-select">
            <option value="">Seleccione…</option>
          </select>
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label small fw-bold mb-1">Almacén</label>
          <select id="almacen" class="form-select" disabled>
            <option value="">Seleccione…</option>
          </select>
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label small fw-bold mb-1">Zona recibo / staging</label>
          <select id="zona_recibo" class="form-select" disabled>
            <option value="">(Seleccione almacén)</option>
          </select>
        </div>

        <div class="col-12 d-flex justify-content-end gap-2 mt-2">
          <button id="btnAplicar" class="btn btn-primary btn-ap">
            <i class="fa-solid fa-magnifying-glass me-1"></i> Aplicar
          </button>
          <button id="btnLimpiar" class="btn btn-outline-secondary btn-ap">
            <i class="fa-solid fa-eraser me-1"></i> Limpiar
          </button>
        </div>
      </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-3">
      <div class="col-12 col-md-3">
        <div class="ap-card p-3">
          <div class="ap-kpi-label">Folios pendientes</div>
          <div class="ap-kpi-val" id="kpi_folios">—</div>
          <div class="small-muted">Recibido ≠ Ubicado</div>
        </div>
      </div>
      <div class="col-12 col-md-3">
        <div class="ap-card p-3">
          <div class="ap-kpi-label">Líneas pendientes</div>
          <div class="ap-kpi-val" id="kpi_lineas">—</div>
          <div class="small-muted">SKU/Lote con pendiente</div>
        </div>
      </div>
      <div class="col-12 col-md-3">
        <div class="ap-card p-3">
          <div class="ap-kpi-label">Cantidad pendiente</div>
          <div class="ap-kpi-val" id="kpi_cant">—</div>
          <div class="small-muted">Pendiente total</div>
        </div>
      </div>
      <div class="col-12 col-md-3">
        <div class="ap-card p-3">
          <div class="ap-kpi-label">% Avance prom.</div>
          <div class="ap-kpi-val" id="kpi_avance">—</div>
          <div class="small-muted">Ubicado vs recibido</div>
        </div>
      </div>
    </div>

    <!-- Grid -->
    <div class="ap-card p-3">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
        <div>
          <div class="fw-bold">Folios RTM <span class="text-muted fw-normal">(Recibido ≠ Pedido y Pendiente de Acomodo)</span></div>
          <div class="small-muted">
            Acciones: <span class="badge bg-light text-dark border">Ver (modal)</span> ·
            <span class="badge bg-light text-dark border">Acomodar (PutAway)</span>
          </div>
        </div>
        <div class="small-muted">Sin precarga: se consulta únicamente al aplicar filtros.</div>
      </div>

      <div class="table-responsive">
        <table id="tblFolios" class="display nowrap" style="width:100%">
          <thead>
            <tr>
              <th>Acciones</th>
              <th>Folio</th>
              <th>Tipo</th>
              <th>OC</th>
              <th>Factura</th>
              <th>Proveedor</th>
              <th>Proyecto</th>
              <th>Protocolo</th>
              <th>Partidas</th>
              <th>Recibido</th>
              <th>Ubicado</th>
              <th>Pendiente</th>
              <th>Avance %</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Modal Detalle -->
  <div class="modal fade" id="mdlDetalle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content" style="border-radius:16px;">
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold mb-0">Detalle de folio <span id="mdlFolio" class="text-primary"></span></h5>
            <div class="small-muted">Detalle bajo demanda (no bloquea la grilla). Prioriza por SKU/Lote.</div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="table-responsive">
            <table id="tblDetalle" class="display nowrap" style="width:100%">
              <thead>
                <tr>
                  <th>Proveedor</th>
                  <th>Artículo</th>
                  <th>Descripción</th>
                  <th>Lote</th>
                  <th>Caducidad</th>
                  <th>Ubicación/Zona</th>
                  <th>Recibido</th>
                  <th>Ubicado</th>
                  <th>Pendiente</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <a id="btnAcomodarModal" class="btn btn-primary btn-ap" href="#">
            <i class="fa-solid fa-dolly me-1"></i> Acomodar este folio
          </a>
          <button type="button" class="btn btn-outline-secondary btn-ap" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Overlay -->
  <div class="overlay" id="overlay">
    <div class="box">
      <div class="spinner-border text-primary" role="status"></div>
      <div class="mt-2 fw-bold">Procesando…</div>
      <div class="small-muted">Consultando RTM</div>
    </div>
  </div>

  <!-- JS -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>

  <script>
    const API = <?= json_encode($API_URL) ?>;

    // Preselección por GET (si viene)
    const PRE_EMP = <?= (int)$empresa_id ?>;
    const PRE_ALM = <?= json_encode($almacen) ?>;
    const PRE_ZON = <?= json_encode($zona) ?>;

    function overlay(on){
      const el = document.getElementById('overlay');
      el.style.display = on ? 'flex' : 'none';
    }

    function fmtNum(x){
      if (x === null || x === undefined || x === '') return '—';
      const n = Number(x);
      if (Number.isNaN(n)) return '—';
      return n.toLocaleString('es-MX', {maximumFractionDigits: 3});
    }

    function safeText(v){
      return (v===null || v===undefined) ? '' : String(v);
    }

    // DataTables language local (evita CORS por json externo)
    const dtLang = {
      "decimal": "",
      "emptyTable": "No hay datos",
      "info": "Mostrando _START_ a _END_ de _TOTAL_",
      "infoEmpty": "Mostrando 0 a 0 de 0",
      "infoFiltered": "(filtrado de _MAX_ registros)",
      "lengthMenu": "Mostrar _MENU_",
      "loadingRecords": "Cargando...",
      "processing": "Procesando...",
      "search": "Buscar:",
      "zeroRecords": "No hay coincidencias",
      "paginate": {"first":"Primero","last":"Último","next":"Siguiente","previous":"Anterior"}
    };

    let dtFolios = null;
    let dtDetalle = null;
    let mdl = null;

    $(document).ready(function(){
      mdl = new bootstrap.Modal(document.getElementById('mdlDetalle'));

      dtFolios = $('#tblFolios').DataTable({
        language: dtLang,
        pageLength: 25,
        scrollX: true,
        processing: true,
        deferRender: true,
        order: [[1,'desc']],
        columns: [
          {data:null, orderable:false, searchable:false, className:'dt-actions'},
          {data:'folio'},
          {data:'tipo'},
          {data:'oc'},
          {data:'factura'},
          {data:'proveedor'},
          {data:'proyecto'},
          {data:'protocolo'},
          {data:'partidas'},
          {data:'recibido'},
          {data:'ubicado'},
          {data:'pendiente'},
          {data:'avance'}
        ],
        createdRow: function(row, data){
          const folio = safeText(data.folio);
          const alm = $('#almacen').val();
          const zon = $('#zona_recibo').val();

          const btnVer = `
            <button class="btn btn-sm btn-outline-primary me-1" data-action="ver" data-folio="${folio}">
              <i class="fa-regular fa-eye"></i> Ver
            </button>`;

          const urlAcom = `putaway_acomodo.php?modo=ACOMODO&almacen=${encodeURIComponent(alm)}&zona_recibo=${encodeURIComponent(zon)}&folio_sel=${encodeURIComponent(folio)}`;
          const btnAcom = `
            <a class="btn btn-sm btn-primary" href="${urlAcom}">
              <i class="fa-solid fa-dolly"></i> Acomodar
            </a>`;

          $('td', row).eq(0).html(btnVer + btnAcom);

          // badges
          const pend = Number(data.pendiente || 0);
          const av = Number(String(data.avance||'').replace('%','')) || 0;
          $('td', row).eq(11).html(`<span class="badge text-dark bg-warning badge-pill">${fmtNum(pend)}</span>`);
          $('td', row).eq(12).html(`<span class="badge text-white bg-${av>=95?'success':(av>=50?'primary':'danger')} badge-pill">${fmtNum(av)}%</span>`);
        }
      });

      dtDetalle = $('#tblDetalle').DataTable({
        language: dtLang,
        pageLength: 25,
        scrollX: true,
        processing: true,
        deferRender: true,
        order: [[8,'desc']],
        columns: [
          {data:'proveedor'},
          {data:'articulo'},
          {data:'descripcion'},
          {data:'lote'},
          {data:'caducidad'},
          {data:'ubicacion'},
          {data:'recibido'},
          {data:'ubicado'},
          {data:'pendiente'}
        ],
        createdRow: function(row, data){
          const pend = Number(data.pendiente || 0);
          $('td', row).eq(8).html(`<span class="badge text-dark bg-warning badge-pill">${fmtNum(pend)}</span>`);
        }
      });

      // acciones grid
      $('#tblFolios tbody').on('click', 'button[data-action="ver"]', async function(){
        const folio = $(this).data('folio');
        await verDetalle(folio);
      });

      $('#btnAplicar').on('click', async function(){
        await aplicar();
      });

      $('#btnLimpiar').on('click', function(){
        $('#empresa_id').val('');
        $('#almacen').prop('disabled', true).html('<option value="">Seleccione…</option>').val('');
        $('#zona_recibo').prop('disabled', true).html('<option value="">(Seleccione almacén)</option>').val('');
        setKpis(null);
        dtFolios.clear().draw();
      });

      // carga inicial selects
      cargarEmpresas().then(async ()=>{
        if (PRE_EMP){
          $('#empresa_id').val(String(PRE_EMP));
          await cargarAlmacenes(PRE_EMP);
          if (PRE_ALM){
            $('#almacen').val(PRE_ALM);
            await cargarZonas(PRE_ALM);
            if (PRE_ZON){
              $('#zona_recibo').val(PRE_ZON);
            }
          }
        }
      });

      // cascadas
      $('#empresa_id').on('change', async function(){
        const emp = $(this).val();
        $('#almacen').prop('disabled', true).html('<option value="">Seleccione…</option>').val('');
        $('#zona_recibo').prop('disabled', true).html('<option value="">(Seleccione almacén)</option>').val('');
        setKpis(null);
        dtFolios.clear().draw();
        if (emp) await cargarAlmacenes(emp);
      });

      $('#almacen').on('change', async function(){
        const alm = $(this).val();
        $('#zona_recibo').prop('disabled', true).html('<option value="">(Seleccione almacén)</option>').val('');
        setKpis(null);
        dtFolios.clear().draw();
        if (alm) await cargarZonas(alm);
      });

    });

    async function apiGet(params){
      const url = API + '?' + new URLSearchParams(params).toString();
      const res = await fetch(url, {headers:{'Accept':'application/json'}});
      return await res.json();
    }

    async function cargarEmpresas(){
      try{
        overlay(true);
        const j = await apiGet({action:'empresas'});
        const $e = $('#empresa_id');
        $e.html('<option value="">Seleccione…</option>');
        (j.data||[]).forEach(r=>{
          $e.append(`<option value="${r.id}">${r.id} - ${safeText(r.nombre)}</option>`);
        });
      }catch(err){
        console.error('empresas error', err);
      }finally{
        overlay(false);
      }
    }

    async function cargarAlmacenes(empresa_id){
      try{
        overlay(true);
        const j = await apiGet({action:'almacenes', empresa_id});
        const $a = $('#almacen');
        $a.html('<option value="">Seleccione…</option>');
        (j.data||[]).forEach(r=>{
          $a.append(`<option value="${safeText(r.clave)}">${safeText(r.clave)} - ${safeText(r.nombre)}</option>`);
        });
        $a.prop('disabled', false);
      }catch(err){
        console.error('almacenes error', err);
      }finally{
        overlay(false);
      }
    }

    async function cargarZonas(almacen){
      try{
        overlay(true);
        const j = await apiGet({action:'zonas', almacen});
        const $z = $('#zona_recibo');
        $z.html('<option value="">Seleccione…</option>');
        (j.data||[]).forEach(r=>{
          // API puede devolver clave/nombre
          const clave = safeText(r.clave || r.cve_ubicacion || r.zona || r.id || r.codigo);
          const nombre = safeText(r.nombre || r.descripcion || r.detalle || r.zona_nombre || '');
          const label = nombre ? `${clave} - ${nombre}` : clave;
          $z.append(`<option value="${clave}">${label}</option>`);
        });
        $z.prop('disabled', false);
      }catch(err){
        console.error('zonas error', err);
      }finally{
        overlay(false);
      }
    }

    function setKpis(k){
      if (!k){
        $('#kpi_folios').text('—');
        $('#kpi_lineas').text('—');
        $('#kpi_cant').text('—');
        $('#kpi_avance').text('—');
        return;
      }
      $('#kpi_folios').text(fmtNum(k.folios));
      $('#kpi_lineas').text(fmtNum(k.lineas));
      $('#kpi_cant').text(fmtNum(k.cantidad));
      $('#kpi_avance').text((k.avance===null||k.avance===undefined)?'—':fmtNum(k.avance)+'%');
    }

    async function aplicar(){
      const empresa_id = $('#empresa_id').val();
      const almacen = $('#almacen').val();
      const zona = $('#zona_recibo').val();

      if (!empresa_id || !almacen || !zona){
        alert('Seleccione Empresa, Almacén y Zona para consultar.');
        return;
      }

      overlay(true);
      try{
        // KPIs
        const k = await apiGet({action:'kpis', empresa_id, almacen, zona_recibo: zona});
        if (k && k.ok){
          setKpis(k.data || k.kpis || null);
        } else {
          setKpis(null);
        }

        // Folios (NO precarga)
        const f = await apiGet({action:'folios', empresa_id, almacen, zona_recibo: zona});
        if (f && f.ok){
          dtFolios.clear();
          dtFolios.rows.add(f.data || []);
          dtFolios.draw();
        } else {
          dtFolios.clear().draw();
        }
      }catch(err){
        console.error('aplicar error', err);
        dtFolios.clear().draw();
        setKpis(null);
      }finally{
        overlay(false);
      }
    }

    async function verDetalle(folio){
      const empresa_id = $('#empresa_id').val();
      const almacen = $('#almacen').val();
      const zona = $('#zona_recibo').val();

      if (!folio) return;

      overlay(true);
      try{
        document.getElementById('mdlFolio').textContent = folio;

        // botón acomodar dentro del modal
        const urlAcom = `putaway_acomodo.php?modo=ACOMODO&almacen=${encodeURIComponent(almacen)}&zona_recibo=${encodeURIComponent(zona)}&folio_sel=${encodeURIComponent(folio)}`;
        $('#btnAcomodarModal').attr('href', urlAcom);

        const d = await apiGet({action:'detalle', empresa_id, almacen, zona_recibo: zona, folio});
        dtDetalle.clear();
        dtDetalle.rows.add((d && d.ok) ? (d.data || []) : []);
        dtDetalle.draw();

        mdl.show();
      }catch(err){
        console.error('detalle error', err);
      }finally{
        overlay(false);
      }
    }
  </script>
</body>
</html>
