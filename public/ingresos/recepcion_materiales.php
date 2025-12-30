<?php
/* ===========================================================
   public/ingresos/recepcion_materiales.php
   Recepción de Materiales (OC)
   - th_aduana / td_aduana como fuente de OC
   - Sin db_all(): solo PDO
   - Empresa: c_compania.cve_cia / c_compania.des_cia
   =========================================================== */

require_once __DIR__ . '/../../app/db.php';

$pdo = db_pdo();
if (!$pdo) {
  die('PDO no inicializado');
}

function jexit($payload, int $code = 200): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function p($k, $d=null){ return $_POST[$k] ?? $_GET[$k] ?? $d; }

/* =========================
   ROUTER JSON (mismo archivo)
   ========================= */
$action = (string)p('action', '');
if ($action !== '') {
  try {
    switch ($action) {

      /* ===== Lista OCs pendientes por Proveedor + Almacén =====
         Pendiente = existe detalle donde cantidad > Ingresado
         Default status A (como acordaste para default).
      */
      case 'oc_list': {
        $cve_almac = trim((string)p('cve_almac', ''));
        $id_prov   = (int)p('id_proveedor', 0);

        if ($cve_almac === '' || $id_prov <= 0) jexit(['ok'=>true,'data'=>[]]);

        $sql = "
          SELECT
            h.ID_Aduana,
            h.num_pedimento,
            h.Pedimento,
            h.Factura,
            h.fech_pedimento,
            h.status,
            COUNT(d.Id_DetAduana) AS partidas,
            COALESCE(SUM(d.cantidad),0) AS cantidad_total,
            COALESCE(SUM(COALESCE(d.Ingresado,0)),0) AS cantidad_ingresada
          FROM th_aduana h
          INNER JOIN td_aduana d ON d.ID_Aduana = h.ID_Aduana
          WHERE h.Activo = 1
            AND h.status = 'A'
            AND h.Cve_Almac = :alm
            AND h.ID_Proveedor = :prov
            AND COALESCE(d.cantidad,0) > COALESCE(d.Ingresado,0)
          GROUP BY h.ID_Aduana, h.num_pedimento, h.Pedimento, h.Factura, h.fech_pedimento, h.status
          ORDER BY h.ID_Aduana DESC
          LIMIT 200
        ";
        $st = $pdo->prepare($sql);
        $st->execute([':alm'=>$cve_almac, ':prov'=>$id_prov]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        jexit(['ok'=>true,'data'=>$rows]);
      } break;

      /* ===== Detalle pendiente de OC ===== */
      case 'oc_det': {
        $idAduana = (int)p('id_aduana', 0);
        if ($idAduana <= 0) jexit(['ok'=>false,'error'=>'id_aduana inválido'], 400);

        $sqlH = "
          SELECT
            h.ID_Aduana,
            h.num_pedimento,
            h.Pedimento,
            h.Factura,
            h.fech_pedimento,
            h.status,
            h.Cve_Almac,
            h.ID_Proveedor,
            p.Nombre AS proveedor
          FROM th_aduana h
          LEFT JOIN c_proveedores p ON p.ID_Proveedor = h.ID_Proveedor
          WHERE h.ID_Aduana = :id
          LIMIT 1
        ";
        $st = $pdo->prepare($sqlH);
        $st->execute([':id'=>$idAduana]);
        $head = $st->fetch(PDO::FETCH_ASSOC);
        if (!$head) jexit(['ok'=>false,'error'=>'OC no encontrada'], 404);

        $sqlD = "
          SELECT
            d.Id_DetAduana,
            d.cve_articulo,
            a.des_articulo,
            a.unidadMedida,
            COALESCE(d.cantidad,0) AS solicitada,
            COALESCE(d.Ingresado,0) AS ingresada,
            (COALESCE(d.cantidad,0) - COALESCE(d.Ingresado,0)) AS pendiente,
            d.cve_lote
          FROM td_aduana d
          LEFT JOIN c_articulo a ON a.cve_articulo = d.cve_articulo
          WHERE d.ID_Aduana = :id
            AND (COALESCE(d.cantidad,0) > COALESCE(d.Ingresado,0))
          ORDER BY d.Id_DetAduana
        ";
        $st = $pdo->prepare($sqlD);
        $st->execute([':id'=>$idAduana]);
        $det = $st->fetchAll(PDO::FETCH_ASSOC);

        jexit(['ok'=>true,'head'=>$head,'det'=>$det]);
      } break;

      default:
        jexit(['ok'=>false,'error'=>'Acción no soportada: '.$action], 400);
    }

  } catch (Throwable $e) {
    jexit(['ok'=>false,'error'=>$e->getMessage()], 500);
  }
}

/* =========================
   UI (HTML)
   ========================= */

$activeSection = 'ingresos';
$activeItem    = 'recepcion_materiales';
$pageTitle     = 'Recepción de Materiales';

include __DIR__ . '/../bi/_menu_global.php';

/* ===== Catálogos UI (PDO directo, sin db_all) ===== */
$empresas = [];
$almacenes = [];
$proveedores = [];
$zonas = [];

try {
  $empresas = $pdo->query("
    SELECT cve_cia, des_cia
    FROM c_compania
    WHERE COALESCE(Activo,1)=1
    ORDER BY des_cia
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $empresas = []; }

try {
  // Ajustado a tu esquema típico de c_almacenp (clave, nombre)
  $almacenes = $pdo->query("
    SELECT clave AS cve_almac, nombre
    FROM c_almacenp
    WHERE COALESCE(Activo,1)=1
    ORDER BY nombre
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $almacenes = []; }

try {
  $proveedores = $pdo->query("
    SELECT ID_Proveedor, Nombre
    FROM c_proveedores
    WHERE COALESCE(Activo,1)=1
    ORDER BY Nombre
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $proveedores = []; }

try {
  // Si tu tabla tiene más campos, no pasa nada.
  $zonas = $pdo->query("
    SELECT cve_ubicacion, desc_ubicacion
    FROM tubicacionesretencion
    WHERE COALESCE(Activo,1)=1
    ORDER BY desc_ubicacion
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $zonas = []; }

$idAduanaPreset = (int)($_GET['id_aduana'] ?? 0);
?>

<style>
.ap-container{padding:12px;font-size:12px}
.ap-title{font-size:18px;font-weight:600;color:#0b5ed7;margin-bottom:10px}
.ap-card{background:#fff;border:1px solid #d0d7e2;border-radius:10px;padding:10px;margin-bottom:10px;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.table td,.table th{padding:6px 8px;vertical-align:middle}
.small-note{font-size:11px;color:#6c757d}
.kpis{display:flex;gap:10px;flex-wrap:wrap;margin-top:6px}
.kpi{width:170px;background:#f8fbff;border:1px solid #d0d7e2;border-radius:10px;padding:8px}
.kpi b{font-size:16px}
</style>

<div class="ap-container">
  <div class="ap-title">Recepción de Materiales <span class="text-muted" style="font-size:12px">OC</span></div>

  <div class="ap-card">
    <div class="row g-2 align-items-end">
      <div class="col-12 col-md-3">
        <label class="form-label">Empresa</label>
        <select id="cboEmpresa" class="form-select form-select-sm">
          <option value="">Seleccione</option>
          <?php foreach($empresas as $e): ?>
            <option value="<?=h($e['cve_cia'])?>"><?=h($e['des_cia'])?></option>
          <?php endforeach; ?>
        </select>
        <div class="small-note">Fuente: c_compania (cve_cia / des_cia)</div>
      </div>

      <div class="col-12 col-md-3">
        <label class="form-label">Almacén</label>
        <select id="cboAlmacen" class="form-select form-select-sm">
          <option value="">Seleccione</option>
          <?php foreach($almacenes as $a): ?>
            <option value="<?=h($a['cve_almac'])?>"><?=h('['.$a['cve_almac'].'] '.$a['nombre'])?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12 col-md-3">
        <label class="form-label">Zona de Recepción</label>
        <select id="cboZona" class="form-select form-select-sm">
          <option value="">Seleccione una Zona de Recepción</option>
          <?php foreach($zonas as $z): ?>
            <option value="<?=h($z['cve_ubicacion'])?>"><?=h('['.$z['cve_ubicacion'].'] '.$z['desc_ubicacion'])?></option>
          <?php endforeach; ?>
        </select>
        <div class="small-note">Fuente: tubicacionesretencion</div>
      </div>

      <div class="col-12 col-md-3">
        <label class="form-label">Proveedor</label>
        <select id="cboProveedor" class="form-select form-select-sm">
          <option value="">Seleccione proveedor</option>
          <?php foreach($proveedores as $p): ?>
            <option value="<?= (int)$p['ID_Proveedor'] ?>"><?=h($p['Nombre'])?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12 col-md-6 mt-2">
        <label class="form-label">Número de OC (pendiente)</label>
        <select id="cboOC" class="form-select form-select-sm">
          <option value="">Seleccione una OC</option>
        </select>
        <div class="small-note">Se listan OCs status = A, filtradas por Proveedor + Almacén, solo con partidas pendientes.</div>
      </div>

      <div class="col-12 col-md-6 mt-2">
        <label class="form-label">Factura / Documento</label>
        <input id="txtFactura" class="form-control form-control-sm" placeholder="Factura o documento comercial">
      </div>
    </div>

    <div class="kpis">
      <div class="kpi"><div class="small-note">Líneas pendientes</div><b id="kpiLineas">0</b></div>
      <div class="kpi"><div class="small-note">Pendiente total</div><b id="kpiPendiente">0</b></div>
      <div class="kpi"><div class="small-note">Capturadas</div><b id="kpiCaptura">0</b></div>
      <div class="kpi"><div class="small-note">LPs</div><b>0</b></div>
    </div>
  </div>

  <div class="ap-card" id="cardPendientes" style="display:none;">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <div class="fw-semibold">Productos de la OC (pendientes)</div>
      <button class="btn btn-sm btn-primary" type="button" onclick="guardarRecepcion()">Guardar recepción</button>
    </div>

    <div class="table-responsive">
      <table class="table table-sm table-striped table-bordered" id="tblPend">
        <thead class="table-light">
          <tr>
            <th style="width:50px">#</th>
            <th style="width:140px">Clave</th>
            <th>Descripción</th>
            <th style="width:80px">UM</th>
            <th style="width:110px">Solicitada</th>
            <th style="width:110px">Ingresada</th>
            <th style="width:110px">Pendiente</th>
            <th style="width:160px">Capturar</th>
          </tr>
        </thead>
        <tbody>
          <tr><td colspan="8" class="text-center text-muted">Seleccione una OC para visualizar partidas.</td></tr>
        </tbody>
      </table>
    </div>

    <div class="small-note">
      Nota: en esta fase solo armamos el “payload” operativo. La persistencia en th_entalmacen/td_entalmacen la conectamos en el siguiente paso (con transacción y kardex).
    </div>
  </div>
</div>

<script>
const SELF = 'recepcion_materiales.php';
const presetOC = <?= (int)$idAduanaPreset ?>;

async function api(action, params){
  const u = new URL(window.location.href);
  u.searchParams.set('action', action);
  Object.keys(params||{}).forEach(k => u.searchParams.set(k, params[k]));
  const r = await fetch(u.toString(), { headers:{'Accept':'application/json'} });
  return await r.json();
}

function nf(v){ const n=parseFloat(v); return isNaN(n)?0:n; }
function fmt(v){ return (Math.round(nf(v)*10000)/10000).toFixed(4); }

async function cargarOCs(){
  const alm  = (document.getElementById('cboAlmacen').value||'').trim();
  const prov = (document.getElementById('cboProveedor').value||'').trim();
  const cbo  = document.getElementById('cboOC');

  cbo.innerHTML = '<option value="">Seleccione una OC</option>';
  document.getElementById('cardPendientes').style.display = 'none';
  document.getElementById('kpiLineas').textContent = '0';
  document.getElementById('kpiPendiente').textContent = '0';
  document.getElementById('kpiCaptura').textContent = '0';

  if (!alm || !prov) return;

  const r = await api('oc_list', { cve_almac: alm, id_proveedor: prov });
  if (!r.ok) { alert('Error al cargar OCs: '+(r.error||'')); return; }

  (r.data||[]).forEach(oc=>{
    const op = document.createElement('option');
    op.value = oc.ID_Aduana;
    const fol = oc.Pedimento || oc.num_pedimento || oc.ID_Aduana;
    const fac = oc.Factura ? (' — '+oc.Factura) : '';
    op.textContent = `${fol}${fac} — Pend:${fmt(nf(oc.cantidad_total)-nf(oc.cantidad_ingresada))}`;
    op.setAttribute('data-factura', oc.Factura || '');
    cbo.appendChild(op);
  });

  if (presetOC){
    cbo.value = String(presetOC);
    await onOCChange();
  }
}

async function onOCChange(){
  const cbo = document.getElementById('cboOC');
  const id  = (cbo.value||'').trim();
  const tb  = document.querySelector('#tblPend tbody');

  if (!id){
    tb.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Seleccione una OC para visualizar partidas.</td></tr>';
    document.getElementById('cardPendientes').style.display = 'none';
    return;
  }

  const fac = cbo.options[cbo.selectedIndex]?.getAttribute('data-factura') || '';
  if (!document.getElementById('txtFactura').value) document.getElementById('txtFactura').value = fac;

  const r = await api('oc_det', { id_aduana: id });
  if (!r.ok){ alert('Error al cargar detalle: '+(r.error||'')); return; }

  const det = r.det || [];
  tb.innerHTML = '';

  let totalPend = 0;
  det.forEach((x, i)=>{
    totalPend += nf(x.pendiente);
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${i+1}</td>
      <td><b>${x.cve_articulo||''}</b></td>
      <td>${x.des_articulo||''}</td>
      <td>${x.unidadMedida||''}</td>
      <td class="text-end">${fmt(x.solicitada)}</td>
      <td class="text-end">${fmt(x.ingresada)}</td>
      <td class="text-end"><b>${fmt(x.pendiente)}</b></td>
      <td>
        <input class="form-control form-control-sm cap"
               type="number" step="0.0001" min="0"
               value="0"
               data-det="${x.Id_DetAduana}"
               data-cve="${x.cve_articulo||''}"
               data-pend="${fmt(x.pendiente)}">
      </td>
    `;
    tb.appendChild(tr);
  });

  if (!det.length){
    tb.innerHTML = '<tr><td colspan="8" class="text-center text-muted">La OC no tiene partidas pendientes.</td></tr>';
  }

  document.getElementById('kpiLineas').textContent = String(det.length);
  document.getElementById('kpiPendiente').textContent = fmt(totalPend);
  document.getElementById('kpiCaptura').textContent = '0';
  document.getElementById('cardPendientes').style.display = 'block';

  // KPI captura en vivo
  document.querySelectorAll('input.cap').forEach(inp=>{
    inp.addEventListener('input', ()=>{
      let cap = 0;
      document.querySelectorAll('input.cap').forEach(i2=> cap += nf(i2.value));
      document.getElementById('kpiCaptura').textContent = fmt(cap);
    });
  });
}

function guardarRecepcion(){
  const idOC = (document.getElementById('cboOC').value||'').trim();
  if (!idOC){ alert('Selecciona una OC'); return; }

  const caps = [...document.querySelectorAll('input.cap')].map(i=>({
    Id_DetAduana: i.getAttribute('data-det'),
    cve_articulo: i.getAttribute('data-cve'),
    pendiente: nf(i.getAttribute('data-pend')||'0'),
    captura: nf(i.value||'0')
  })).filter(x=>x.captura>0);

  if (!caps.length){ alert('Captura al menos una cantidad recibida.'); return; }

  // Validación: no exceder pendiente
  for (const x of caps){
    if (x.captura > x.pendiente + 0.0000001){
      alert(`Captura excede pendiente para ${x.cve_articulo}. Pendiente: ${fmt(x.pendiente)}`);
      return;
    }
  }

  const payload = {
    Empresa: (document.getElementById('cboEmpresa').value||'').trim(),
    Almacen: (document.getElementById('cboAlmacen').value||'').trim(),
    Zona: (document.getElementById('cboZona').value||'').trim(),
    Proveedor: (document.getElementById('cboProveedor').value||'').trim(),
    ID_Aduana: idOC,
    Factura: (document.getElementById('txtFactura').value||'').trim(),
    lineas: caps
  };

  console.log('Payload recepción listo', payload);
  alert('Payload listo. Siguiente paso: persistir en th_entalmacen/td_entalmacen con transacción + kardex.');
}

// wiring
document.getElementById('cboAlmacen').addEventListener('change', cargarOCs);
document.getElementById('cboProveedor').addEventListener('change', cargarOCs);
document.getElementById('cboOC').addEventListener('change', onOCChange);

// si viene OC preseleccionada por URL, necesitamos cargar lista tras elegir almacén/proveedor.
// En esta fase, el preset lo ejecuta al cargarOCs() cuando ya hay filtros.
</script>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
