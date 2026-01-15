<?php
/* ===========================================================
   RTM · Pendiente de Acomodo (Solo lectura)
   - UI estilo AssistPro
   - Selects consumen filtros_assistpro.php
   - Grilla: 25 líneas visibles, scroll H/V, 1 renglón por fila
   =========================================================== */

require_once __DIR__ . '/../../app/db.php';

$activeSection = 'ingresos';
$activeItem    = 'rtm_pendiente_acomodo';
$pageTitle     = 'RTM · Pendiente de Acomodo';
require_once __DIR__ . '/../bi/_menu_global.php';

/* ======================== Filtros (GET) ======================== */
$f_empresa  = trim($_GET['empresa'] ?? '');
$f_almacen  = trim($_GET['almacen'] ?? '');
$f_zona     = trim($_GET['zona'] ?? '');     // zona recep/staging (claverp)
$f_q        = trim($_GET['q'] ?? '');

/* ======================== Query RTM ============================
   v_pendientesacomodo.cve_ubicacion (claverp) -> c_ubicacion -> c_almacen -> c_almacenp
*/
$where = ["1=1"];
$params = [];

if ($f_almacen !== '') {
    $where[] = "u.cve_almac = :almacen";
    $params['almacen'] = $f_almacen;
}
if ($f_zona !== '') {
    $where[] = "p.cve_ubicacion = :zona";
    $params['zona'] = $f_zona;
}
if ($f_q !== '') {
    $where[] = "(p.cve_articulo LIKE :q OR a.des_articulo LIKE :q OR p.cve_lote LIKE :q OR prov.Nombre LIKE :q)";
    $params['q'] = "%{$f_q}%";
}

$sql = "
SELECT
    ap.nombre               AS empresa,
    ca.des_almac            AS almacen,
    p.cve_ubicacion         AS zona_recepcion,

    prov.cve_proveedor      AS cve_proveedor,
    prov.Nombre             AS proveedor,

    p.cve_articulo          AS cve_articulo,
    a.des_articulo          AS des_articulo,
    p.cve_lote              AS cve_lote,
    l.Caducidad             AS caducidad,
    p.Cantidad              AS pendiente,

    p.cve_ubicacion         AS bl_recepcion
FROM v_pendientesacomodo p
LEFT JOIN c_articulo a
       ON a.cve_articulo = p.cve_articulo
LEFT JOIN c_lotes l
       ON l.cve_articulo = p.cve_articulo
      AND l.Lote         = p.cve_lote
LEFT JOIN c_proveedores prov
       ON prov.ID_Proveedor = p.ID_Proveedor
LEFT JOIN c_ubicacion u
       ON u.claverp = p.cve_ubicacion
LEFT JOIN c_almacen ca
       ON ca.cve_almac = u.cve_almac
LEFT JOIN c_almacenp ap
       ON ap.clave = ca.cve_almacenp
WHERE " . implode(" AND ", $where) . "
ORDER BY empresa, almacen, proveedor, p.cve_articulo, p.cve_lote
";

$rows = db_all($sql, $params);

/* ======================== KPIs ======================== */
$kpi_lineas = count($rows);
$kpi_pend   = 0.0;
$kpi_skus   = [];
foreach ($rows as $r) {
    $kpi_pend += (float)($r['pendiente'] ?? 0);
    $sku = (string)($r['cve_articulo'] ?? '');
    if ($sku !== '') $kpi_skus[$sku] = true;
}
$kpi_skus_cnt = count($kpi_skus);

/* ======================== API filtros ======================== */
$API_FILTROS = '../api/filtros_assistpro.php?action=init';
?>

<style>
  /* ===== Frame / márgenes (AssistPro) ===== */
  .wrapper-content { padding: 10px 12px !important; }
  .ibox { margin-bottom: 12px !important; }
  .ibox-title { padding: 8px 12px !important; }
  .ibox-content { padding: 10px 12px !important; }

  /* ===== Tipografía compacta ===== */
  .rtm-page * { font-size: 10px; }
  .rtm-title { font-size: 20px; font-weight: 800; margin: 0; }
  .rtm-sub { font-size: 11px; color: #6b7280; margin-top: 2px; }

  /* ===== Cards KPI ===== */
  .kpi-card {
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:10px;
    padding:10px 12px;
    box-shadow: 0 1px 2px rgba(0,0,0,.03);
  }
  .kpi-label { font-size: 10px; text-transform: uppercase; color:#6b7280; }
  .kpi-value { font-size: 18px; font-weight: 800; color:#0F5AAD; line-height: 1.1; }

  /* ===== Sección filtros separada ===== */
  .filters-row { display:flex; gap:12px; flex-wrap:wrap; }
  .filters-row .fcol { min-width: 260px; flex: 1; }
  .filters-actions { display:flex; gap:8px; align-items:flex-end; }

  /* ===== Grilla (1 renglón por fila + scroll) ===== */
  #tblRTM { width: 100% !important; font-size: 10px; }
  #tblRTM thead th { white-space: nowrap; }
  #tblRTM tbody td {
    white-space: nowrap !important;
    padding: 4px 6px !important;
    vertical-align: middle;
  }
  /* evita que DataTables meta doble altura */
  table.dataTable tbody th, table.dataTable tbody td { line-height: 1.15; }
</style>

<div class="wrapper wrapper-content animated fadeIn rtm-page">

  <!-- Header -->
  <div class="row">
    <div class="col-lg-12">
      <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-end;">
        <div>
          <div class="rtm-title">RTM · Pendiente de Acomodo</div>
          <div class="rtm-sub">Inventario recibido en staging/recibo pendiente de ubicación final (solo lectura).</div>
        </div>
        <div>
          <a href="../putaway/rtm_general.php" class="btn btn-primary btn-sm">
            <i class="fa fa-cubes"></i> PutAway (Acomodo)
          </a>
        </div>
      </div>
      <hr style="margin:10px 0;">
    </div>
  </div>

  <!-- 1) FILTROS (independiente) -->
  <div class="ibox">
    <div class="ibox-title"><h5 style="margin:0;">Filtros</h5></div>
    <div class="ibox-content">
      <form method="get" id="frmFiltros">
        <div class="filters-row">
          <div class="fcol">
            <label>Empresa</label>
            <select class="form-control input-sm" name="empresa" id="empresa"></select>
          </div>
          <div class="fcol">
            <label>Almacén</label>
            <select class="form-control input-sm" name="almacen" id="almacen"></select>
          </div>
          <div class="fcol">
            <label>Zona de Recepción</label>
            <select class="form-control input-sm" name="zona" id="zona"></select>
          </div>
          <div class="fcol">
            <label>Buscar</label>
            <input class="form-control input-sm" name="q" id="q" value="<?= htmlspecialchars((string)$f_q) ?>"
                   placeholder="Artículo | Lote | Proveedor">
          </div>
          <div class="filters-actions">
            <button class="btn btn-primary btn-sm" type="submit"><i class="fa fa-filter"></i> Aplicar</button>
            <a class="btn btn-default btn-sm" href="rtm_pendiente_acomodo.php"><i class="fa fa-eraser"></i> Limpiar</a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- KPIs -->
  <div class="row" style="margin-bottom:12px;">
    <div class="col-md-4">
      <div class="kpi-card">
        <div class="kpi-label">Líneas pendientes</div>
        <div class="kpi-value"><?= number_format($kpi_lineas) ?></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="kpi-card">
        <div class="kpi-label">Cantidad pendiente</div>
        <div class="kpi-value"><?= number_format($kpi_pend, 3) ?></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="kpi-card">
        <div class="kpi-label">SKUs afectados</div>
        <div class="kpi-value"><?= number_format($kpi_skus_cnt) ?></div>
      </div>
    </div>
  </div>

  <!-- 2) GRILLA (independiente) -->
  <div class="ibox">
    <div class="ibox-title"><h5 style="margin:0;">Detalle RTM</h5></div>
    <div class="ibox-content">
      <div class="table-responsive">
        <table id="tblRTM" class="table table-striped table-bordered table-hover">
          <thead>
            <tr>
              <th style="width:70px;">Acciones</th>
              <th>Zona Recepción</th>
              <th>Proveedor</th>
              <th>SKU</th>
              <th>Descripción</th>
              <th>Lote</th>
              <th>Caducidad</th>
              <th>BL Recepción</th>
              <th style="text-align:right;">Pendiente</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td>
                  <button type="button" class="btn btn-default btn-xs rtm-btn-ver"
                          data-empresa="<?= htmlspecialchars((string)($r['empresa'] ?? ''), ENT_QUOTES) ?>"
                          data-almacen="<?= htmlspecialchars((string)($r['almacen'] ?? ''), ENT_QUOTES) ?>"
                          data-zona="<?= htmlspecialchars((string)($r['zona_recepcion'] ?? ''), ENT_QUOTES) ?>"
                          data-proveedor="<?= htmlspecialchars(trim((string)($r['cve_proveedor'] ?? '') . ' ' . (string)($r['proveedor'] ?? '')), ENT_QUOTES) ?>"
                          data-sku="<?= htmlspecialchars((string)($r['cve_articulo'] ?? ''), ENT_QUOTES) ?>"
                          data-desc="<?= htmlspecialchars((string)($r['des_articulo'] ?? ''), ENT_QUOTES) ?>"
                          data-lote="<?= htmlspecialchars((string)($r['cve_lote'] ?? ''), ENT_QUOTES) ?>"
                          data-cad="<?= htmlspecialchars((string)($r['caducidad'] ?? ''), ENT_QUOTES) ?>"
                          data-bl="<?= htmlspecialchars((string)($r['bl_recepcion'] ?? ''), ENT_QUOTES) ?>"
                          data-pend="<?= htmlspecialchars((string)number_format((float)($r['pendiente'] ?? 0), 3), ENT_QUOTES) ?>"
                          title="Ver detalle">
                    <i class="fa fa-search"></i>
                  </button>
                </td>
                <td><?= htmlspecialchars((string)($r['zona_recepcion'] ?? '')) ?></td>
                <td><?= htmlspecialchars(trim((string)($r['cve_proveedor'] ?? '') . ' ' . (string)($r['proveedor'] ?? ''))) ?></td>
                <td><?= htmlspecialchars((string)($r['cve_articulo'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($r['des_articulo'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($r['cve_lote'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($r['caducidad'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($r['bl_recepcion'] ?? '')) ?></td>
                <td style="text-align:right;"><?= number_format((float)($r['pendiente'] ?? 0), 3) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="rtm-sub" style="margin-top:6px;">Vista solo lectura. Scroll H/V activo. 25 líneas visibles.</div>
    </div>
  </div>

</div>

<!-- Modal detalle (Acciones) -->
<div class="modal fade" id="rtmDetalleModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Detalle RTM</h4>
      </div>
      <div class="modal-body">
        <div class="row" style="margin-bottom:8px;">
          <div class="col-md-4"><b>Empresa:</b> <span id="m_empresa"></span></div>
          <div class="col-md-4"><b>Almacén:</b> <span id="m_almacen"></span></div>
          <div class="col-md-4"><b>Zona:</b> <span id="m_zona"></span></div>
        </div>
        <div class="row" style="margin-bottom:8px;">
          <div class="col-md-6"><b>Proveedor:</b> <span id="m_proveedor"></span></div>
          <div class="col-md-6"><b>BL Recepción:</b> <span id="m_bl"></span></div>
        </div>
        <hr style="margin:10px 0;">
        <div class="row" style="margin-bottom:8px;">
          <div class="col-md-4"><b>SKU:</b> <span id="m_sku"></span></div>
          <div class="col-md-8"><b>Descripción:</b> <span id="m_desc"></span></div>
        </div>
        <div class="row" style="margin-bottom:8px;">
          <div class="col-md-4"><b>Lote:</b> <span id="m_lote"></span></div>
          <div class="col-md-4"><b>Caducidad:</b> <span id="m_cad"></span></div>
          <div class="col-md-4"><b>Pendiente:</b> <span id="m_pend"></span></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
/* ===========================================================
   Selects corporativos (igual patrón que Recepción, pero sin depender de jQuery)
   - Empresa: ../api/empresas_api.php  (resp: {ok:1, data:[{cve_cia, des_cia}]})
   - Almacén / Zonas: ../api/filtros_assistpro.php (resp: {almacenes:[], zonas_recep:[], zonas_almacenaje:[]})
   Nota: usamos JS nativo para evitar fallas por orden de carga de $.
   =========================================================== */

(function(){
  const API_EMPRESAS = '../api/empresas_api.php';
  const API_FILTROS  = '../api/filtros_assistpro.php';

  const PRE = {
    empresa: <?= json_encode($f_empresa) ?>,
    almacen: <?= json_encode($f_almacen) ?>,
    zona:    <?= json_encode($f_zona) ?>
  };

  function $(id){ return document.getElementById(id); }
  function setOpts(sel, rows, getVal, getText, placeholder){
    sel.innerHTML = '';
    const o0 = document.createElement('option');
    o0.value = '';
    o0.textContent = placeholder || 'Seleccione...';
    sel.appendChild(o0);
    (rows||[]).forEach(r=>{
      const o = document.createElement('option');
      o.value = getVal(r) ?? '';
      o.textContent = getText(r) ?? '';
      sel.appendChild(o);
    });
  }

  async function fetchJson(url){
    const r = await fetch(url, {cache:'no-store', credentials:'same-origin'});
    const t = await r.text();
    try{ return JSON.parse(t); }catch(e){ return {ok:0, error:'No JSON', detail:t.slice(0,500)}; }
  }

  let CACHE_FILTROS = null;
  async function filtros(){
    if(CACHE_FILTROS) return CACHE_FILTROS;
    CACHE_FILTROS = await fetchJson(API_FILTROS);
    return CACHE_FILTROS;
  }

  async function loadEmpresas(){
    const j = await fetchJson(API_EMPRESAS);
    const sel = $('empresa');
    if(!sel) return;
    if(!j || !j.ok){
      setOpts(sel, [], r=>r.cve_cia, r=>r.des_cia, 'Seleccione...');
      return;
    }
    setOpts(sel, j.data||[], r=>r.cve_cia, r=>r.des_cia, 'Seleccione...');

    // default: primera empresa
    if(PRE.empresa){ sel.value = String(PRE.empresa); }
    else if(sel.options.length>1){ sel.selectedIndex = 1; }
  }

  async function loadAlmacenes(){
    const f = await filtros();
    const sel = $('almacen');
    if(!sel) return;
    const rows = (f.almacenes || []);

    // En recepción usan clave_almacen; en RTM rtm_1 filtra por cve_almac.
    // Para no romper el SQL del RTM, dejamos VALUE = cve_almac y mostramos descripción.
    setOpts(sel, rows,
      r => (r.cve_almac ?? ''),
      r => {
        const clave = (r.clave_almacen ?? '').toString().trim();
        const desc  = (r.des_almac ?? r.nombre ?? '').toString().trim();
        if(clave && desc) return `${clave} - ${desc}`;
        return desc || clave || (r.cve_almac ?? '');
      },
      'Seleccione...'
    );

    // guardamos la clave_almacen en dataset para match robusto
    Array.from(sel.options).forEach((o,i)=>{
      if(i===0) return;
      const r = rows[i-1];
      if(r && r.clave_almacen) o.dataset.clave = r.clave_almacen;
    });

    if(PRE.almacen){ sel.value = String(PRE.almacen); }
  }

  async function loadZonas(){
    const f = await filtros();
    const selZona = $('zona');
    const selAlm  = $('almacen');
    if(!selZona || !selAlm) return;

    const almVal   = (selAlm.value||'').trim();
    const almClave = (selAlm.options[selAlm.selectedIndex]?.dataset?.clave||'').trim();

    // RTM usa "zona" como ubicación (claverp/cve_ubicacion). Tomamos zonas_recep (staging/recepción).
    const all = (f.zonas_recep || []);
    let rows = all;

    // match por cve_almac (puede venir como clave o id)
    if(almVal || almClave){
      const key1 = String(almVal).toUpperCase();
      const key2 = String(almClave).toUpperCase();
      rows = all.filter(z=>{
        const zk = String(z.cve_almac ?? '').trim().toUpperCase();
        return (key1 && zk===key1) || (key2 && zk===key2);
      });
      if(!rows.length){
        // fallback: sin filtro si no hubo match
        rows = all;
      }
    }

    setOpts(selZona, rows,
      z => (z.claverp ?? z.cve_ubicacion ?? ''),
      z => {
        const clave = (z.claverp ?? z.cve_ubicacion ?? '').toString().trim();
        const desc  = (z.descripcion ?? z.desc_ubicacion ?? '').toString().trim();
        if(clave && desc) return `${clave} - ${desc}`;
        return desc || clave;
      },
      'Seleccione...'
    );

    if(PRE.zona){ selZona.value = String(PRE.zona); }
  }

  // init (sin depender de jQuery)
  document.addEventListener('DOMContentLoaded', async ()=>{
    try{
      await loadEmpresas();
      await loadAlmacenes();
      await loadZonas();

      // Acciones: ver detalle en modal
      document.addEventListener('click', (ev)=>{
        const btn = ev.target.closest ? ev.target.closest('.rtm-btn-ver') : null;
        if(!btn) return;
        const set = (id, val)=>{ const el = $(id); if(el) el.textContent = val || ''; };
        set('m_emp',  btn.dataset.empresa);
        set('m_alm',  btn.dataset.almacen);
        set('m_zona', btn.dataset.zona);
        set('m_prov', btn.dataset.proveedor);
        set('m_sku',  btn.dataset.sku);
        set('m_desc', btn.dataset.desc);
        set('m_lote', btn.dataset.lote);
        set('m_cad',  btn.dataset.cad);
        set('m_bl',   btn.dataset.bl);
        set('m_pend', btn.dataset.pend);

        // Bootstrap modal (si está disponible)
        if(window.jQuery && window.jQuery.fn && window.jQuery.fn.modal){
          window.jQuery('#rtmDetalleModal').modal('show');
        }else{
          alert(`SKU: ${btn.dataset.sku}\nLote: ${btn.dataset.lote}\nPendiente: ${btn.dataset.pend}`);
        }
      });

      const emp = $('empresa');
      const alm = $('almacen');

      if(emp){
        emp.addEventListener('change', async ()=>{
          // en recepción, almacenes dependen de empresa; aquí mantenemos la UX aunque el API regrese todos.
          PRE.almacen = '';
          PRE.zona = '';
          await loadAlmacenes();
          await loadZonas();
        });
      }
      if(alm){
        alm.addEventListener('change', async ()=>{
          PRE.zona = '';
          await loadZonas();
        });
      }
    }catch(e){
      console.error(e);
      if(window.toastr && toastr.error) toastr.error(e.message || 'Error cargando selects');
    }
  });
})();
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
