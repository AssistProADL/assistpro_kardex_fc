<?php
// public/sfa/resumen_rutas.php
// Dashboard Corporativo | Resumen de Rutas
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard Corporativo | Resumen de Rutas</title>

  <!-- Bootstrap 5 (usa el que ya tengas en tu proyecto) -->
  <link href="../assets/bootstrap.min.css" rel="stylesheet">
  <script src="../assets/bootstrap.bundle.min.js"></script>

  <!-- (Opcional) FontAwesome si ya lo usas en otras vistas -->
  <link href="../assets/fontawesome/css/all.min.css" rel="stylesheet">

  <style>
    :root{
      --ap-blue:#0b2c6f;
      --ap-blue-2:#0d47a1;
      --ap-border:#e6e9ef;
      --ap-muted:#6c757d;
      --ap-bg:#f7f9fc;
    }
    body{ background: var(--ap-bg); }
    .ap-title{ font-weight:800; font-size:20px; }
    .ap-sub{ color:var(--ap-muted); font-size:12px; }
    .ap-hint{ color:var(--ap-muted); font-size:11px; }
    .ap-box{ border:1px solid var(--ap-border); border-radius:12px; }
    .ap-kpi{
      border:1px solid var(--ap-border);
      border-radius:14px;
      background:#fff;
      padding:18px 16px;
      text-align:center;
      min-height:92px;
    }
    .ap-kpi .v{ font-weight:900; font-size:26px; line-height:1; }
    .ap-kpi .l{ color:var(--ap-muted); font-size:12px; margin-top:6px; }
    .ap-topbar{
      display:flex; align-items:flex-start; justify-content:space-between;
      gap:12px; margin-bottom:14px;
    }
    .ap-actions{ display:flex; gap:10px; flex-wrap:wrap; }
    .table-sm td, .table-sm th{ font-size:12px; }
    .badge-ok{ background:#e8fff0; color:#0a7a2f; border:1px solid #b7f0c8; }
    .badge-warn{ background:#fff7e6; color:#8a5a00; border:1px solid #ffe0a3; }
    .badge-bad{ background:#ffecec; color:#a30000; border:1px solid #ffb3b3; }
    .btn-ap{ background:var(--ap-blue-2); border-color:var(--ap-blue-2); color:#fff; }
    .btn-ap:hover{ filter:brightness(.95); color:#fff; }
    .ap-link{ text-decoration:none; }
  </style>
</head>

<body>
<?php include __DIR__ . "/../bi/_menu_global.php"; ?>

<div class="container-fluid" style="max-width: 1400px;">
  <div class="ap-topbar mt-3">
    <div>
      <div class="ap-title">Dashboard Corporativo | Resumen de Rutas</div>
      <div class="ap-sub">Planeación semanal + cobertura geográfica. (No carga datos hasta aplicar filtros)</div>
    </div>
    <div class="ap-actions">
      <a href="planeacion_rutas_destinatarios.php" class="btn btn-outline-primary btn-sm">✏ Asignar Clientes</a>
      <a href="geo_distribucion_clientes.php" class="btn btn-outline-success btn-sm">🌍 Georreferencia</a>
    </div>
  </div>

  <!-- FILTROS -->
  <div class="card mb-3 ap-box">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label mb-1">Empresa / Almacén</label>
          <select id="f_almacen" class="form-select form-select-sm">
            <option value="">Cargando...</option>
          </select>
          <div class="ap-hint mt-1">Fuente: <span class="text-muted">../api/catalogo_almacenes.php</span></div>
        </div>

        <div class="col-md-3">
          <label class="form-label mb-1">Ruta</label>
          <select id="f_ruta" class="form-select form-select-sm" disabled>
            <option value="">Seleccione almacén</option>
          </select>
          <div class="ap-hint mt-1">Fuente: <span class="text-muted">../api/rutas_api.php</span></div>
        </div>

        <div class="col-md-2">
          <label class="form-label mb-1">Desde</label>
          <input id="f_desde" type="date" class="form-control form-control-sm">
        </div>

        <div class="col-md-2">
          <label class="form-label mb-1">Hasta</label>
          <input id="f_hasta" type="date" class="form-control form-control-sm">
        </div>

        <div class="col-md-2">
          <label class="form-label mb-1">Tipo venta</label>
          <select id="f_tipo" class="form-select form-select-sm">
            <option value="">(Todas)</option>
            <option value="ENTREGA">Entrega</option>
            <option value="PREVENTA">Preventa</option>
          </select>
        </div>

        <div class="col-md-12 mt-2 d-flex gap-2 flex-wrap">
          <button id="btn_actualizar" class="btn btn-ap btn-sm" type="button">
            <i class="fa-solid fa-rotate"></i> Actualizar
          </button>
          <button id="btn_limpiar" class="btn btn-outline-secondary btn-sm" type="button">
            Limpiar
          </button>
          <div class="ap-hint align-self-center ms-2">
            Endpoint datos: <span class="text-muted">../api/resumen_rutas_data.php</span>
            &nbsp; <span id="txt_estado_endpoint" class="badge text-bg-light">OK</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- KPIs -->
  <div class="row g-3 mb-3">
    <div class="col-md-2">
      <div class="ap-kpi">
        <div class="v" id="kpi_rutas">0</div>
        <div class="l">Rutas activas</div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="ap-kpi">
        <div class="v" id="kpi_clientes">0</div>
        <div class="l">Clientes asignados</div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="ap-kpi">
        <div class="v" id="kpi_sinruta">0</div>
        <div class="l">Clientes sin ruta</div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="ap-kpi">
        <div class="v" id="kpi_geo">0%</div>
        <div class="l">Cobertura geo</div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="ap-kpi">
        <div class="v" id="kpi_docs">0</div>
        <div class="l">Documentos</div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="ap-kpi">
        <div class="v" id="kpi_ventas">$0.00</div>
        <div class="l">Total ventas</div>
      </div>
    </div>
  </div>

  <!-- RESUMEN POR RUTA -->
  <div class="card mb-3 ap-box">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-bold mb-0">Resumen por Ruta</h6>
        <div class="ap-hint" id="txt_total_rutas">Sin consulta</div>
      </div>

      <div class="table-responsive" style="max-height: 340px; overflow:auto;">
        <table class="table table-sm table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th style="width:110px">Acciones</th>
              <th>Ruta</th>
              <th style="width:110px" class="text-end">Clientes</th>
              <th style="width:90px" class="text-center">Días</th>
              <th style="width:90px" class="text-end">CPs</th>
              <th style="width:90px" class="text-end">Geo %</th>
              <th style="width:90px" class="text-center">Estado</th>
            </tr>
          </thead>
          <tbody id="tb_rutas">
            <tr><td colspan="7" class="text-muted">Aplique filtros y presione Actualizar.</td></tr>
          </tbody>
        </table>
      </div>
      <div class="ap-hint mt-2">
        Nota: Se consolida automáticamente cualquier duplicado por Ruta (si el endpoint devuelve más de un renglón por la misma ruta).
      </div>
    </div>
  </div>

  <!-- DISTRIBUCIÓN POR DÍA -->
  <div class="card mb-3 ap-box">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-bold mb-0">Distribución por Día</h6>
        <div class="ap-hint" id="txt_total_dias">Sin consulta</div>
      </div>

      <div class="table-responsive" style="max-height: 320px; overflow:auto;">
        <table class="table table-sm table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th>Día</th>
              <th class="text-end" style="width:120px">Rutas</th>
              <th class="text-end" style="width:120px">Clientes</th>
            </tr>
          </thead>
          <tbody id="tb_dias">
            <tr><td colspan="3" class="text-muted">Aplique filtros y presione Actualizar.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . "/../bi/_menu_global_end.php"; ?>

<script>
(function(){
  const $ = (id)=>document.getElementById(id);

  const elAlmacen = $('f_almacen');
  const elRuta    = $('f_ruta');
  const elDesde   = $('f_desde');
  const elHasta   = $('f_hasta');
  const elTipo    = $('f_tipo');

  const tbRutas = $('tb_rutas');
  const tbDias  = $('tb_dias');

  // Defaults de fechas: últimos 7 días (si no hay valor)
  function iso(d){ return d.toISOString().slice(0,10); }
  function setDefaultDates(){
    const now = new Date();
    const past = new Date(now.getTime() - (7*24*60*60*1000));
    if(!elHasta.value) elHasta.value = iso(now);
    if(!elDesde.value) elDesde.value = iso(past);
  }
  setDefaultDates();

  // ====== CARGA CATÁLOGOS ======
  async function loadAlmacenes(){
    try{
      elAlmacen.innerHTML = '<option value="">Cargando...</option>';
      const r = await fetch('../api/catalogo_almacenes.php', {cache:'no-store'});
      const j = await r.json();
      const data = (j.data || j.almacenes || j || []);
      elAlmacen.innerHTML = '<option value="">Seleccione almacén</option>';
      data.forEach(a=>{
        const id = a.id_almacen ?? a.id ?? a.IdEmpresa ?? a.IdAlmacen ?? a.almacen_id ?? a.Id;
        const nombre = a.nombre ?? a.almacen ?? a.Nombre ?? a.descripcion ?? a.Descripcion ?? '';
        const clave = a.clave ?? a.cve ?? a.Cve ?? a.Cve_Alm ?? a.Cve_Almac ?? '';
        const label = (clave ? (clave + ' - ') : '') + nombre;
        if(id !== undefined && id !== null && String(id).trim() !== ''){
          const opt = document.createElement('option');
          opt.value = id;
          opt.textContent = label || ('Almacén ' + id);
          elAlmacen.appendChild(opt);
        }
      });
    }catch(e){
      elAlmacen.innerHTML = '<option value="">Error cargando almacenes</option>';
      $('txt_estado_endpoint').className = 'badge text-bg-danger';
      $('txt_estado_endpoint').textContent = 'ERROR';
    }
  }

  async function loadRutas(almacenId){
    elRuta.disabled = true;
    elRuta.innerHTML = '<option value="">Cargando...</option>';
    if(!almacenId){
      elRuta.innerHTML = '<option value="">Seleccione almacén</option>';
      return;
    }
    try{
      const r = await fetch('../api/rutas_api.php?almacen_id=' + encodeURIComponent(almacenId), {cache:'no-store'});
      const j = await r.json();
      const data = (j.data || j.rutas || j || []);
      elRuta.innerHTML = '<option value="">(Todas)</option>';
      data.forEach(x=>{
        const id = x.id_ruta ?? x.ID_Ruta ?? x.id ?? x.IdRuta ?? x.ruta_id;
        const cve = x.cve_ruta ?? x.Cve_Ruta ?? x.cve ?? x.clave ?? '';
        const desc = x.descripcion ?? x.Descripcion ?? x.ruta ?? x.nombre ?? '';
        const label = (cve ? (cve + ' - ') : '') + (desc || ('Ruta ' + id));
        if(id !== undefined && id !== null && String(id).trim() !== ''){
          const opt = document.createElement('option');
          opt.value = id;
          opt.textContent = label;
          elRuta.appendChild(opt);
        }
      });
      elRuta.disabled = false;
    }catch(e){
      elRuta.innerHTML = '<option value="">Error cargando rutas</option>';
      elRuta.disabled = true;
    }
  }

  // ====== NORMALIZACIÓN (ANTI-DUPLICADOS) ======
  // Consolida filas duplicadas por la misma ruta (clave: id_ruta/cve/ruta texto)
  function dedupeRutas(list){
    const map = new Map();
    (list || []).forEach(r=>{
      const key = String(
        r.id_ruta ?? r.ID_Ruta ?? r.ruta_id ?? r.cve_ruta ?? r.Cve_Ruta ?? r.ruta ?? r.Ruta ?? ''
      ).trim() || JSON.stringify(r);

      const clientes = Number(r.clientes ?? r.Clientes ?? 0) || 0;
      const cps      = Number(r.cps ?? r.CPs ?? r.cp ?? 0) || 0;
      const geo      = Number(r.geo_pct ?? r.geo ?? r.Geo ?? 0) || 0;

      if(!map.has(key)){
        map.set(key, {
          ...r,
          _clientes: clientes,
          _cps: cps,
          _geo_max: geo,
          _dias: r.dias ?? r.Dias ?? '-',
          _ruta_txt: r.ruta ?? r.Ruta ?? r.descripcion ?? r.Descripcion ?? '',
          _estado: (r.estado ?? r.Estado ?? 'OK')
        });
      }else{
        const cur = map.get(key);
        cur._clientes += clientes;
        cur._cps += cps;
        cur._geo_max = Math.max(cur._geo_max, geo);
        // Mantén días/estado más “crítico” si aparece
        const est = (r.estado ?? r.Estado ?? 'OK');
        if(String(est).toUpperCase() !== 'OK') cur._estado = est;
        map.set(key, cur);
      }
    });

    // Devuelve una lista “limpia”
    return Array.from(map.values()).map(x=>{
      const out = {...x};
      out.clientes = x._clientes;
      out.cps = x._cps;
      out.geo_pct = x._geo_max;
      out.dias = x._dias;
      out.ruta = x._ruta_txt || (x.cve_ruta ? x.cve_ruta : (x.id_ruta ? ('Ruta ' + x.id_ruta) : ''));
      out.estado = x._estado || 'OK';
      return out;
    });
  }

  // ====== RENDER ======
  function badgeEstado(v){
    const s = String(v||'').toUpperCase();
    if(s === 'OK') return '<span class="badge badge-ok">● OK</span>';
    if(s === 'WARN' || s === 'WARNING') return '<span class="badge badge-warn">● WARN</span>';
    return '<span class="badge badge-bad">● ' + (v||'ERR') + '</span>';
  }

  function renderRutas(raw){
    const rutas = dedupeRutas(raw);

    tbRutas.innerHTML = '';
    if(!rutas.length){
      tbRutas.innerHTML = '<tr><td colspan="7" class="text-muted">Sin datos para los filtros seleccionados.</td></tr>';
      $('txt_total_rutas').textContent = '0 rutas (página 1/1)';
      return rutas;
    }

    rutas.forEach(r=>{
      const rutaTxt = r.ruta ?? r.descripcion ?? '';
      const idRuta = r.id_ruta ?? r.ID_Ruta ?? r.ruta_id ?? r.id ?? '';
      const geo = Number(r.geo_pct ?? 0) || 0;

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>
          <a class="btn btn-sm btn-outline-success" href="geo_distribucion_clientes.php?ruta_id=${encodeURIComponent(idRuta)}&almacen_id=${encodeURIComponent(elAlmacen.value)}" title="Ver mapa">
            Mapa
          </a>
        </td>
        <td>${(rutaTxt || '').toString()}</td>
        <td class="text-end">${Number(r.clientes ?? 0).toLocaleString('es-MX')}</td>
        <td class="text-center">${(r.dias ?? '-')}</td>
        <td class="text-end">${Number(r.cps ?? 0).toLocaleString('es-MX')}</td>
        <td class="text-end">${geo.toFixed(0)}%</td>
        <td class="text-center">${badgeEstado(r.estado)}</td>
      `;
      tbRutas.appendChild(tr);
    });

    $('txt_total_rutas').textContent = rutas.length + ' rutas (página 1/1)';
    return rutas;
  }

  function renderDias(list){
    tbDias.innerHTML = '';
    const rows = (list || []);
    if(!rows.length){
      tbDias.innerHTML = '<tr><td colspan="3" class="text-muted">Sin datos de distribución.</td></tr>';
      $('txt_total_dias').textContent = '0 días';
      return;
    }
    rows.forEach(d=>{
      const dia = d.dia ?? d.Dia ?? d.nombre ?? '';
      const rutas = Number(d.rutas ?? d.Rutas ?? 0) || 0;
      const clientes = Number(d.clientes ?? d.Clientes ?? 0) || 0;
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${dia}</td>
        <td class="text-end">${rutas.toLocaleString('es-MX')}</td>
        <td class="text-end">${clientes.toLocaleString('es-MX')}</td>
      `;
      tbDias.appendChild(tr);
    });
    $('txt_total_dias').textContent = rows.length + ' días';
  }

  function money(n){
    const v = Number(n||0) || 0;
    return v.toLocaleString('es-MX',{style:'currency',currency:'MXN'});
  }

  function setKPIs(resp, rutasClean){
    // Preferir valores del endpoint si existen, pero recalcular rutas si venían duplicadas
    const k = resp.kpis || resp.resumen || resp || {};
    const rutasActivas = (rutasClean ? rutasClean.length : (Number(k.rutas_activas ?? k.rutas ?? 0) || 0));
    const clientes = Number(k.clientes_asignados ?? k.clientes ?? 0) || 0;
    const sinruta = Number(k.clientes_sin_ruta ?? k.sin_ruta ?? 0) || 0;
    const geo = Number(k.cobertura_geo ?? k.geo_pct ?? 0) || 0;
    const docs = Number(k.documentos ?? k.docs ?? 0) || 0;
    const ventas = Number(k.total_ventas ?? k.ventas ?? 0) || 0;

    $('kpi_rutas').textContent = rutasActivas.toLocaleString('es-MX');
    $('kpi_clientes').textContent = clientes.toLocaleString('es-MX');
    $('kpi_sinruta').textContent = sinruta.toLocaleString('es-MX');
    $('kpi_geo').textContent = geo.toFixed(0) + '%';
    $('kpi_docs').textContent = docs.toLocaleString('es-MX');
    $('kpi_ventas').textContent = money(ventas);
  }

  // ====== CONSULTA ======
  async function consultar(){
    const almacen_id = elAlmacen.value;
    const ruta_id = elRuta.value;
    const desde = elDesde.value;
    const hasta = elHasta.value;
    const tipo = elTipo.value;

    if(!almacen_id){
      alert('Seleccione un almacén.');
      return;
    }
    if(!desde || !hasta){
      alert('Defina rango de fechas.');
      return;
    }

    // Construcción de query (ruta es opcional)
    const qs = new URLSearchParams();
    qs.set('almacen_id', almacen_id);
    if(ruta_id) qs.set('ruta_id', ruta_id);
    qs.set('desde', desde);
    qs.set('hasta', hasta);
    if(tipo) qs.set('tipo_venta', tipo);

    tbRutas.innerHTML = '<tr><td colspan="7" class="text-muted">Consultando...</td></tr>';
    tbDias.innerHTML  = '<tr><td colspan="3" class="text-muted">Consultando...</td></tr>';

    try{
      const r = await fetch('../api/resumen_rutas_data.php?' + qs.toString(), {cache:'no-store'});
      const resp = await r.json();

      // Compatibilidad: algunas APIs devuelven {ok:1,data:{...}} o {success:true,...}
      const payload = resp.data || resp;

      const rutasRaw = payload.rutas || payload.resumen_ruta || [];
      const rutasClean = renderRutas(rutasRaw);

      renderDias(payload.distribucion_dia || payload.dias || []);
      setKPIs(payload, rutasClean);

      $('txt_estado_endpoint').className = 'badge text-bg-success';
      $('txt_estado_endpoint').textContent = 'OK';
    }catch(e){
      $('txt_estado_endpoint').className = 'badge text-bg-danger';
      $('txt_estado_endpoint').textContent = 'ERROR';
      tbRutas.innerHTML = '<tr><td colspan="7" class="text-danger">Error consultando datos.</td></tr>';
      tbDias.innerHTML  = '<tr><td colspan="3" class="text-danger">Error consultando datos.</td></tr>';
      console.error(e);
    }
  }

  function limpiar(){
    elRuta.value = '';
    elTipo.value = '';
    setDefaultDates();
    $('kpi_rutas').textContent = '0';
    $('kpi_clientes').textContent = '0';
    $('kpi_sinruta').textContent = '0';
    $('kpi_geo').textContent = '0%';
    $('kpi_docs').textContent = '0';
    $('kpi_ventas').textContent = '$0.00';
    tbRutas.innerHTML = '<tr><td colspan="7" class="text-muted">Aplique filtros y presione Actualizar.</td></tr>';
    tbDias.innerHTML  = '<tr><td colspan="3" class="text-muted">Aplique filtros y presione Actualizar.</td></tr>';
    $('txt_total_rutas').textContent = 'Sin consulta';
    $('txt_total_dias').textContent = 'Sin consulta';
  }

  // ====== EVENTOS ======
  elAlmacen.addEventListener('change', ()=>{
    loadRutas(elAlmacen.value);
  });
  $('btn_actualizar').addEventListener('click', consultar);
  $('btn_limpiar').addEventListener('click', limpiar);

  // Init
  loadAlmacenes().then(()=>{
    // no auto-consulta (tu nota: “No carga datos hasta aplicar filtros”)
  });

})();
</script>

</body>
</html>
