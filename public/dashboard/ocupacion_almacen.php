 <?php
require_once __DIR__ . '/../bi/_menu_global.php';

/* =========================
   DB bootstrap
========================= */
$dbCandidates = [
  __DIR__ . '/../../app/db.php',
  __DIR__ . '/../app/db.php',
  __DIR__ . '/../../../app/db.php',
];

$dbLoaded = false;
foreach ($dbCandidates as $p) {
  if (file_exists($p)) {
    require_once $p;
    $dbLoaded = true;
    break;
  }
}
if (!$dbLoaded) {
  die("No se encontró db.php");
}

/* PDO real (tu instancia lo soporta) */
if (function_exists('db_pdo')) {
  $pdo = db_pdo();
}
$hasPDO = isset($pdo) && $pdo instanceof PDO;

/* =========================
   Helpers DB
========================= */
$db_all_safe = function(string $sql, array $params = []) use ($hasPDO, $pdo) {
  if ($hasPDO) {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }
  return db_all($sql, $params);
};

$db_one_safe = function(string $sql, array $params = []) use ($hasPDO, $pdo) {
  if ($hasPDO) {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetch(PDO::FETCH_ASSOC);
  }
  return db_one($sql, $params);
};

/* =========================
   Selector: ALMACÉN PADRE
========================= */
$almacenesP = $db_all_safe("
  SELECT
    id AS cve_almacenp,
    CONCAT(IFNULL(clave,''), ' - ', IFNULL(nombre,'')) AS label
  FROM c_almacenp
  WHERE IFNULL(Activo,1)=1
  ORDER BY nombre
");

/* =========================
   Parametría
========================= */
$cve_almacenp = isset($_GET['cve_almacenp']) ? (int)$_GET['cve_almacenp'] : 0;
$cve_almac_legacy = isset($_GET['cve_almac']) ? (int)$_GET['cve_almac'] : 0;

/* Legacy → Padre (SIN headers) */
if ($cve_almacenp <= 0 && $cve_almac_legacy > 0) {
  $row = $db_one_safe("
    SELECT cve_almacenp
    FROM c_almacen
    WHERE cve_almac = :cve_almac
    LIMIT 1
  ", [':cve_almac' => $cve_almac_legacy]);

  if (!empty($row['cve_almacenp'])) {
    $dest = strtok($_SERVER["REQUEST_URI"], '?');
    $target = $dest . '?cve_almacenp=' . (int)$row['cve_almacenp'];
    echo "<script>window.location.replace(" . json_encode($target) . ");</script>";
    exit;
  }
}

/* Default */
if ($cve_almacenp <= 0 && !empty($almacenesP)) {
  $cve_almacenp = (int)$almacenesP[0]['cve_almacenp'];
}
?>
<style>
.ap-container{ padding:10px; }
.ap-title{ font-size:20px;font-weight:600;color:#0d6efd;display:flex;gap:8px;align-items:center;margin-bottom:10px; }
.ap-toolbar{ display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:10px; }
.ap-select{ display:flex;gap:8px;align-items:center;background:#fff;padding:8px 10px;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.08); }
.ap-select label{ font-size:12px;color:#6c757d;margin:0; }
.ap-select select{ min-width:360px;padding:6px 10px;border:1px solid #dee2e6;border-radius:8px;outline:none; }

.ap-cards{ display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:10px; }
.ap-card{ border-radius:8px;padding:10px;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.08); }
.ap-card h6{ margin:0;font-size:12px;color:#6c757d; }
.ap-card span{ font-size:22px;font-weight:bold; }
.ap-card small{ display:block;margin-top:2px;color:#6c757d;font-size:11px; }

.badge{ display:inline-block;padding:3px 8px;border-radius:999px;font-size:12px;font-weight:600; }
.badge-green{ background:#198754;color:#fff; }
.badge-yellow{ background:#ffc107;color:#000; }
.badge-red{ background:#dc3545;color:#fff; }

.ap-panel{ border-radius:8px;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.08);padding:12px; }
.spinner{ text-align:center;padding:16px;font-size:14px;color:#6c757d; }
.kpi-row{ display:grid;grid-template-columns:1fr 1fr;gap:12px; }
.kpi-box{ border:1px solid #e9ecef;border-radius:10px;padding:10px; }
.kpi-box h6{ margin:0 0 8px 0;font-size:12px;color:#6c757d; }
.kpi-line{ display:flex;justify-content:space-between;padding:4px 0;font-size:13px; }
.kpi-line b{ font-weight:700; }
.note{ margin-top:8px;font-size:12px;color:#6c757d; }
</style>

<div class="ap-container">
  <div class="ap-title"><i class="bi bi-box-seam"></i> Ocupación de Almacén (Pallets)</div>

  <div class="ap-toolbar">
    <div class="ap-select">
      <label for="sel_almacenp">Almacén</label>
      <select id="sel_almacenp">
        <?php foreach($almacenesP as $a): ?>
          <option value="<?= (int)$a['cve_almacenp'] ?>" <?= ((int)$a['cve_almacenp']===$cve_almacenp?'selected':'') ?>>
            <?= htmlspecialchars($a['label']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="ap-cards">
    <div class="ap-card"><h6>Racks Total</h6><span id="card_rack_total">0</span><small>Ubicaciones (cve_nivel &gt; 1)</small></div>
    <div class="ap-card"><h6>Racks Ocupadas</h6><span id="card_rack_ocu">0</span><small>Con ≥ 1 pallet</small></div>
    <div class="ap-card"><h6>Racks Libres</h6><span id="card_rack_lib">0</span><small>Slots disponibles</small></div>
    <div class="ap-card"><h6>% Ocupación Rack</h6><span id="card_rack_pct"><span class="badge badge-green">0%</span></span><small>Semáforo</small></div>
    <div class="ap-card"><h6>Pallets a Piso</h6><span id="card_piso">0</span><small>Sin límite (solo conteo)</small></div>
  </div>

  <div class="ap-panel">
    <div id="panel_body" class="spinner"><i class="bi bi-arrow-repeat"></i> Cargando KPI...</div>
  </div>
</div>

<script>
const API_URL = "../api/ocupacion_almacen.php";

function badgeForPct(pct){
  if (pct < 70) return 'badge badge-green';
  if (pct < 85) return 'badge badge-yellow';
  return 'badge badge-red';
}
function fmtInt(n){ return (Number(n)||0).toLocaleString('es-MX'); }

function loadKPI(cve_almacenp){
  const panel = document.getElementById("panel_body");
  panel.innerHTML = `<div class="spinner"><i class="bi bi-arrow-repeat"></i> Cargando KPI...</div>`;

  fetch(`${API_URL}?cve_almacenp=${encodeURIComponent(cve_almacenp)}`)
    .then(r => r.json())
    .then(res => {
      if (!res || res.success === false) {
        panel.innerHTML = `<div class="spinner">Error al cargar KPI</div>`;
        return;
      }

      const data = res.data || {};
      const rack = data.rack || {};
      const piso = data.piso || {};
      const cont = data.contenedores_ref || null;

      const rack_total = Number(rack.total || 0);
      const rack_ocu   = Number(rack.ocupadas || 0);
      const rack_lib   = Number(rack.libres || Math.max(0, rack_total - rack_ocu));
      const rack_pct   = Number(rack.ocupacion_pct || (rack_total>0 ? (rack_ocu/rack_total)*100 : 0));
      const pallets_piso = Number(piso.pallets || 0);

      document.getElementById("card_rack_total").innerText = fmtInt(rack_total);
      document.getElementById("card_rack_ocu").innerText   = fmtInt(rack_ocu);
      document.getElementById("card_rack_lib").innerText   = fmtInt(rack_lib);
      document.getElementById("card_piso").innerText       = fmtInt(pallets_piso);
      document.getElementById("card_rack_pct").innerHTML =
        `<span class="${badgeForPct(rack_pct)}">${rack_pct.toFixed(2)}%</span>`;

      let html = `
        <div class="kpi-row">
          <div class="kpi-box">
            <h6>Ocupación de Racks</h6>
            <div class="kpi-line"><span>Total</span><b>${fmtInt(rack_total)}</b></div>
            <div class="kpi-line"><span>Ocupadas</span><b>${fmtInt(rack_ocu)}</b></div>
            <div class="kpi-line"><span>Libres</span><b>${fmtInt(rack_lib)}</b></div>
            <div class="kpi-line"><span>% Ocupación</span><b>${rack_pct.toFixed(2)}%</b></div>
            <div class="note">Slots físicos (cve_nivel &gt; 1). Se marca ocupada si tiene ≥ 1 pallet.</div>
          </div>
          <div class="kpi-box">
            <h6>Piso</h6>
            <div class="kpi-line"><span>Pallets a piso</span><b>${fmtInt(pallets_piso)}</b></div>
            <div class="note">Sin límite: solo conteo.</div>
          </div>
        </div>
      `;

      if (cont) {
        html += `<div class="note" style="margin-top:10px;">
          Referencia: Contenedores en rack <b>${fmtInt(cont.rack||0)}</b> · Contenedores en piso <b>${fmtInt(cont.piso||0)}</b>
        </div>`;
      }

      panel.innerHTML = html;
    })
    .catch(() => panel.innerHTML = `<div class="spinner">Error al cargar KPI</div>`);
}

const sel = document.getElementById('sel_almacenp');
sel.addEventListener('change', () => {
  const v = sel.value;
  const url = new URL(window.location.href);
  url.searchParams.delete('cve_almac');
  url.searchParams.set('cve_almacenp', v);
  window.location.href = url.toString();
});

(function init(){
  if (sel.value) loadKPI(sel.value);
})();
</script>

