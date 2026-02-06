<?php
// =====================================================
// AssistPro PQRS – Vista Ejecutiva (UI afinada)
// - No toca BD / No inventa columnas
// - Usa: reporta_nombre, status_clave, creado_en
// - Cliente + Razón Social desde /public/api/clientes.php (frontend)
// =====================================================
require_once __DIR__ . '/../bi/_menu_global.php';
require_once __DIR__ . '/../../app/db.php';
db_pdo();
global $pdo;

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function fmtFecha($f){ return $f ? date('d/m/Y', strtotime($f)) : ''; }

// Tipos reales en tu tabla: P/Q/R/S
$tipoLabels = [
  'P' => 'Peticiones',
  'Q' => 'Quejas',
  'R' => 'Reclamos',
  'S' => 'Sugerencias'
];

/* ================= Filtros GET (opcional) ================= */
$f_tipo = trim($_GET['tipo'] ?? ''); // P/Q/R/S

/* ================= KPIs por tipo (P/Q/R/S) ================= */
$kpiTipo = ['P'=>0,'Q'=>0,'R'=>0,'S'=>0];
foreach($pdo->query("SELECT tipo, COUNT(*) total FROM pqrs_case GROUP BY tipo") as $r){
  $t = (string)$r['tipo'];
  if(isset($kpiTipo[$t])) $kpiTipo[$t] = (int)$r['total'];
}

/* ================= Listado (API intacta) ================= */
$sql = "SELECT * FROM pqrs_case WHERE 1=1";
$params = [];

if($f_tipo !== '' && isset($tipoLabels[$f_tipo])){
  $sql .= " AND tipo = ?";
  $params[] = $f_tipo;
}

$sql .= " ORDER BY id_case DESC LIMIT 500";
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
/* ===== AssistPro ===== */
body, table { font-size:10px; }
th, td {
  padding:4px 6px;
  white-space:nowrap;
  vertical-align:middle;
}
.ap-wrap { max-height:260px; overflow:auto; }

/* ===== Cards (todas juntas) ===== */
.ap-cards{
  display:grid;
  grid-template-columns:repeat(4, 1fr);
  gap:10px;
  margin-bottom:10px;
}
.ap-card{
  background:#fff;
  border:1px solid #e5eaf3;
  border-radius:10px;
  cursor:pointer;
}
.ap-card:hover{ background:#f4f8ff; }
.ap-card .card-body{ padding:8px; text-align:center; }
.ap-card .ttl{ font-size:9px; color:#64748b; font-weight:700; }
.ap-card .val{ font-size:20px; font-weight:800; color:#1e3a8a; }

/* ===== Estados (según tus valores reales) ===== */
.badge-status{
  padding:4px 8px;
  border-radius:12px;
  font-size:9px;
  font-weight:800;
  display:inline-flex;
  align-items:center;
  gap:6px;
}
.dot{ width:7px; height:7px; border-radius:50%; display:inline-block; }

.st-ok   { background:#dcfce7; color:#166534; }  /* CERRADA / RESUELTO */
.st-rev  { background:#fef9c3; color:#854d0e; }  /* EN_ESPERA */
.st-proc { background:#e0f2fe; color:#075985; }  /* EN_PROCESO / NUEVA */
.st-bad  { background:#fee2e2; color:#991b1b; }  /* CANCELADA */
.st-na   { background:#e5e7eb; color:#374151; }  /* NO_PROCEDE / otros */
</style>

<div class="container-fluid mt-3">

  <h5 class="text-primary fw-bold mb-2">
    <i class="bi bi-chat-left-dots"></i> Control PQRS
  </h5>

  <!-- =========================================================
       CARDS (TODAS JUNTAS, SIN QUITAR NINGUNA)
       Tipos reales: P/Q/R/S (no PETICION/QUEJA...)
  ========================================================== -->
  <div class="ap-cards">
    <?php foreach($tipoLabels as $k=>$lbl): ?>
      <div class="ap-card" onclick="location.href='?tipo=<?=$k?>'">
        <div class="card-body">
          <div class="ttl"><?=$lbl?></div>
          <div class="val"><?=$kpiTipo[$k] ?? 0?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- =========================================================
       TABLA
  ========================================================== -->
  <div class="card">
    <div class="card-body p-1">
      <div class="ap-wrap">
        <table class="table table-hover table-sm mb-0">
          <thead class="table-light">
            <tr>
              <th>PDF</th>
              <th>Folio</th>
              <th>Cliente</th>
              <th>Referencia</th>
              <th>Tipo</th>
              <th>Reportó</th>
              <th>Cierre</th>
              <th>Fecha</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($rows as $r):

            $st = strtoupper(trim($r['status_clave'] ?? ''));

            // Mapeo REAL a colores (según tu screenshot)
            if($st === 'CERRADA' || $st === 'RESUELTO'){
              $cls = 'st-ok';   $dot = '#16a34a';
            }elseif($st === 'EN_ESPERA' || $st === 'EN_REVISION'){
              $cls = 'st-rev';  $dot = '#ca8a04';
            }elseif($st === 'EN_PROCESO' || $st === 'NUEVA' || $st === 'ABIERTO'){
              $cls = 'st-proc'; $dot = '#0284c7';
            }elseif(str_contains($st, 'CANCEL')){
              $cls = 'st-bad';  $dot = '#dc2626';
            }elseif($st === 'NO_PROCEDE' || $st === 'NO PROCEDIO'){
              $cls = 'st-na';   $dot = '#6b7280';
            }else{
              $cls = 'st-na';   $dot = '#6b7280';
            }

            $tipo = (string)($r['tipo'] ?? '');
            $tipoShow = $tipoLabels[$tipo] ?? $tipo;

          ?>
            <tr>
              <!-- PDF (no se quita) -->
              <td>
                <a href="pqrs_pdf.php?id_case=<?=(int)$r['id_case']?>"
                   target="_blank"
                   class="btn btn-outline-danger btn-sm"
                   title="Reporte PDF">
                  <i class="fas fa-file-pdf"></i>
                </a>
              </td>

              <td><b><?=h($r['fol_pqrs'])?></b></td>

              <!-- Cliente: clave + razón social via API (con normalización) -->
              <td class="clte" data-clte="<?=h($r['cve_clte'])?>">
                <?=h($r['cve_clte'])?>
              </td>

              <td><?=h($r['ref_folio'])?></td>
              <td><?=h($tipoShow)?></td>

              <!-- Reportó: columna real -->
              <td><?=h($r['reporta_nombre'])?></td>

              <!-- Cierre con “spinner dot” (semáforo) -->
              <td>
                <span class="badge-status <?=$cls?>">
                  <span class="dot" style="background:<?=$dot?>"></span>
                  <?=h($r['status_clave'])?>
                </span>
              </td>

              <td><?=fmtFecha($r['creado_en'])?></td>
            </tr>
          <?php endforeach; ?>

          <?php if(!$rows): ?>
            <tr><td colspan="8" class="text-center text-muted">Sin registros</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<script>
// =========================================================
// Clientes desde API: /public/api/clientes.php
// - Normaliza claves para matchear CL0000000003 vs CL100 etc.
// =========================================================
(function(){
  function normKey(s){
    s = (s||'').toString().trim().toUpperCase();
    if(!s) return '';
    // quita espacios
    s = s.replace(/\s+/g,'');
    return s;
  }

  // genera variantes: original, sin ceros a la izquierda en parte numérica, etc.
  function variants(k){
    k = normKey(k);
    const out = new Set();
    if(!k) return out;
    out.add(k);

    // Caso prefijo letras + numeros (CL0000000003)
    const m = k.match(/^([A-Z]+)0*([0-9]+)$/);
    if(m){
      const pref = m[1];
      const num  = m[2];
      out.add(pref + num);              // CL3
      out.add(pref + String(parseInt(num,10))); // CL3 (por si)
    }

    // Caso letras+numeros con ceros intermedios (poco común)
    const m2 = k.match(/^([A-Z]+)([0-9]+)$/);
    if(m2){
      out.add(m2[1] + String(parseInt(m2[2],10)));
    }

    return out;
  }

  fetch('../api/clientes.php')
    .then(r => r.json())
    .then(data => {
      const map = {};

      // Indexa por todas las variantes del API
      data.forEach(c => {
        const cve = normKey(c.Cve_Cte || '');
        const rs  = (c.RazonSocial || '').toString().trim();
        if(!cve || !rs) return;

        variants(cve).forEach(v => { map[v] = rs; });
      });

      // Pinta tabla
      document.querySelectorAll('.clte').forEach(td => {
        const raw = td.dataset.clte || '';
        const vs = variants(raw);
        let found = '';
        for(const v of vs){
          if(map[v]) { found = map[v]; break; }
        }
        if(found){
          td.textContent = normKey(raw) + ' – ' + found;
        }
      });
    })
    .catch(()=>{});
})();
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
