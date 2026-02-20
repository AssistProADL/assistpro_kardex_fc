<?php
// VAS - Cobranza (Pendientes de cobro)
// UI: filtra por compañía (IdEmpresa), owner (cliente/proveedor cliente) y rango de fechas.
// Nota: el catálogo VAS se gestiona por empresa (IdEmpresa). Owner es filtro operativo.

require_once __DIR__ . '/../bi/_menu_global.php';
?>
<div class="container-fluid py-3">

  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <h3 class="m-0" style="color:#0b2c5f;font-weight:800;">VAS · Cobranza</h3>
      <div class="text-muted" style="font-size:13px;">
        Pendientes por pedido (estatus <b>pendiente</b>/<b>aplicado</b>). <b>Facturar</b> marca ítems como facturados.
      </div>
    </div>
  </div>

  <!-- Filtros -->
  <div class="card shadow-sm border-0 mb-3" style="border-radius:16px;">
    <div class="card-body">
      <div class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label fw-semibold">Compañía</label>
          <select id="cve_cia" class="form-select"></select>
          <div class="text-muted mt-1" style="font-size:12px;">
            Fuente: <span class="font-monospace">api/vas/catalogos_owners.php</span> (empresas / owners).
          </div>
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">Dueño (Proveedor cliente)</label>
          <select id="owner_id" class="form-select" disabled>
            <option value="">Todos</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label fw-semibold">Desde</label>
          <input type="date" id="fi" class="form-control">
        </div>

        <div class="col-md-2">
          <label class="form-label fw-semibold">Hasta</label>
          <input type="date" id="ff" class="form-control">
        </div>

        <div class="col-12 d-flex gap-2 justify-content-end">
          <button id="btnAplicar" class="btn btn-outline-primary">Aplicar</button>
          <button id="btnFacturar" class="btn btn-primary">Facturar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- KPIs -->
  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="card shadow-sm border-0" style="border-radius:16px;">
        <div class="card-body">
          <div class="text-muted" style="font-size:13px;">Pedidos</div>
          <div id="kpi_pedidos" style="font-size:28px;font-weight:800;">0</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm border-0" style="border-radius:16px;">
        <div class="card-body">
          <div class="text-muted" style="font-size:13px;">Importe VAS</div>
          <div id="kpi_importe" style="font-size:28px;font-weight:800;">0.00</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabla -->
  <div class="card shadow-sm border-0" style="border-radius:16px;">
    <div class="card-body">
      <div class="table-responsive">
        <table id="tbl" class="table table-sm align-middle w-100">
          <thead class="table-light">
            <tr>
              <th style="width:34px;"><input type="checkbox" id="chkAll"></th>
              <th>Pedido</th>
              <th>Fecha</th>
              <th>Cliente</th>
              <th class="text-end">Importe VAS</th>
              <th class="text-end">Pend.</th>
              <th class="text-end">Apl.</th>
              <th>Almacén</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <div class="text-muted mt-2" style="font-size:12px;">
        Tip: selecciona pedidos y presiona <b>Facturar</b> para marcar ítems como facturados.
      </div>
    </div>
  </div>
</div>

<!-- jQuery (primero), DataTables (después) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css"/>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script>
(function(){
  const $cia   = $('#cve_cia');
  const $owner = $('#owner_id');
  const $fi    = $('#fi');
  const $ff    = $('#ff');

  const $kpiPedidos = $('#kpi_pedidos');
  const $kpiImporte = $('#kpi_importe');

  const apiEmpresas = '../api/vas/catalogos_owners.php?action=empresas';
  const apiOwners   = (idEmpresa) => `../api/vas/catalogos_owners.php?action=owners&IdEmpresa=${encodeURIComponent(idEmpresa)}`;
  const apiCobranza = (params) => `../api/vas/cobranza.php?${params}`;

  let dt = null;

  function ymd(d){
    const pad = (n)=>String(n).padStart(2,'0');
    return d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate());
  }

  function setDefaultDates(){
    const today = new Date();
    const from = new Date(today);
    from.setDate(from.getDate()-7);
    $fi.val(ymd(from));
    $ff.val(ymd(today));
  }

  function money(v){
    const n = Number(v||0);
    return n.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
  }

  async function fetchJson(url){
    const r = await fetch(url, {credentials:'same-origin'});
    const t = await r.text();
    try { return JSON.parse(t); }
    catch(e){
      console.error('JSON inválido', url, t);
      throw new Error('JSON inválido. URL='+url+' BodyHead='+t.slice(0,160));
    }
  }

  async function loadEmpresas(){
    $cia.html('<option value="">Cargando...</option>');
    const j = await fetchJson(apiEmpresas);
    if(!j.ok){ throw new Error(j.msg||'Error cargando empresas'); }
    const rows = j.data || [];
    $cia.empty();
    for(const it of rows){
      const val = it.IdEmpresa ?? it.idEmpresa ?? it.cve_cia ?? it.id;
      const txt = it.nombre ?? it.des_cia ?? it.empresa ?? it.clave ?? ('Empresa '+val);
      $cia.append(new Option(txt, val));
    }
    // Default: primera empresa si existe
    if(rows.length){ $cia.val(rows[0].IdEmpresa ?? rows[0].idEmpresa ?? rows[0].cve_cia ?? rows[0].id); }
  }

  async function loadOwners(){
    const idEmpresa = $cia.val();
    $owner.prop('disabled', true).html('<option value="">Cargando...</option>');
    if(!idEmpresa){
      $owner.html('<option value="">Todos</option>');
      return;
    }
    const j = await fetchJson(apiOwners(idEmpresa));
    if(!j.ok){ throw new Error(j.msg||'Error cargando owners'); }
    const rows = j.data || [];
    $owner.empty().append(new Option('Todos', ''));
    for(const it of rows){
      $owner.append(new Option(it.owner_nombre, it.owner_id));
    }
    $owner.prop('disabled', false);
  }

  function ensureDT(){
    if(dt) return dt;
    dt = $('#tbl').DataTable({
      pageLength: 25,
      lengthMenu: [[10,25,50,100],[10,25,50,100]],
      order: [[1,'desc']],
      language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' },
      columnDefs: [
        { targets: 0, orderable:false, searchable:false },
        { targets: [4,5,6], className:'text-end' }
      ]
    });
    return dt;
  }

  async function loadCobranza(){
    const idEmpresa = $cia.val();
    if(!idEmpresa) return;

    const owner = $owner.val();
    const fi = $fi.val();
    const ff = $ff.val();

    const params = new URLSearchParams();
    params.set('IdEmpresa', idEmpresa);
    if(owner) params.set('owner_id', owner);
    if(fi) params.set('fecha_inicio', fi);
    if(ff) params.set('fecha_fin', ff);

    const j = await fetchJson(apiCobranza(params.toString()));
    if(!j.ok){ throw new Error(j.msg||'Error cargando cobranza'); }

    const rows = j.data || [];
    const dtt = ensureDT();
    dtt.clear();

    let totalImporte = 0;
    for(const r of rows){
      totalImporte += Number(r.importe_vas||0);

      const chk = `<input type="checkbox" class="rowChk" data-id_pedido="${r.id_pedido}" data-folio="${(r.folio_pedido||'').replace(/"/g,'&quot;')}">`;
      dtt.row.add([
        chk,
        r.folio_pedido || r.id_pedido,
        r.fecha_pedido || '',
        r.cliente || '',
        money(r.importe_vas),
        r.items_pendiente ?? '',
        r.items_aplicado ?? '',
        r.cve_almac ?? ''
      ]);
    }
    dtt.draw();

    $kpiPedidos.text(rows.length);
    $kpiImporte.text(money(totalImporte));

    $('#chkAll').prop('checked', false);
  }

  async function facturarSeleccion(){
    const idEmpresa = $cia.val();
    if(!idEmpresa) return;

    const ids = [];
    $('#tbl tbody .rowChk:checked').each(function(){
      ids.push($(this).data('id_pedido'));
    });
    if(!ids.length){
      alert('Selecciona al menos un pedido.');
      return;
    }

    if(!confirm(`Se marcarán como FACTURADOS los ítems VAS de ${ids.length} pedido(s). ¿Continuar?`)) return;

    const payload = { IdEmpresa: idEmpresa, id_pedidos: ids };
    const r = await fetch('../api/vas/cobranza.php?action=facturar', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify(payload),
      credentials:'same-origin'
    });
    const t = await r.text();
    let j;
    try { j = JSON.parse(t); }
    catch(e){ throw new Error('Respuesta no-JSON en facturar: '+t.slice(0,160)); }

    if(!j.ok){
      alert(j.msg || 'No se pudo facturar.');
      return;
    }

    await loadCobranza();
    alert('Listo. Pedidos actualizados.');
  }

  // UI events
  $('#btnAplicar').on('click', async ()=>{ try{ await loadCobranza(); }catch(e){ alert(e.message); } });
  $('#btnFacturar').on('click', async ()=>{ try{ await facturarSeleccion(); }catch(e){ alert(e.message); } });

  $('#chkAll').on('change', function(){
    const v = $(this).is(':checked');
    $('#tbl tbody .rowChk').prop('checked', v);
  });

  $cia.on('change', async ()=>{
    try{
      await loadOwners();
      await loadCobranza();
    }catch(e){ alert(e.message); }
  });

  // Init
  (async function init(){
    try{
      setDefaultDates();
      await loadEmpresas();
      await loadOwners();
      await loadCobranza();
    }catch(e){
      alert(e.message);
    }
  })();
})();
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
