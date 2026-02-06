<?php
// =====================================================
// AssistPro PQRS – Vista Ejecutiva (UI afinada)
// NO se toca BD / NO se inventan columnas
// =====================================================
require_once __DIR__ . '/../bi/_menu_global.php';
require_once __DIR__ . '/../../app/db.php';
db_pdo();
global $pdo;

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function fmtFecha($f){ return $f ? date('d/m/Y', strtotime($f)) : ''; }

/* ================= KPIs (cards existentes + por tipo) ================= */
$kpiTipo = [];
foreach($pdo->query("SELECT tipo, COUNT(*) total FROM pqrs_case GROUP BY tipo") as $r){
  $kpiTipo[$r['tipo']] = (int)$r['total'];
}

/* ================= Listado (API intacta) ================= */
$rows = $pdo->query("
  SELECT *
  FROM pqrs_case
  ORDER BY id_case DESC
  LIMIT 500
")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
/* ===== AssistPro ===== */
body, table{font-size:10px;}
th, td{
  padding:4px 6px;
  white-space:nowrap;
  vertical-align:middle;
}
.ap-wrap{max-height:260px; overflow:auto;}

/* ===== Cards ===== */
.ap-cards{
  display:grid;
  grid-template-columns:repeat(4,1fr);
  gap:10px;
  margin-bottom:10px;
}
.ap-card{
  background:#fff;
  border:1px solid #e5eaf3;
  border-radius:10px;
  cursor:pointer;
}
.ap-card .card-body{padding:8px; text-align:center;}
.ap-card:hover{background:#f4f8ff;}
.ap-card .ttl{font-size:9px; color:#64748b; font-weight:700;}
.ap-card .val{font-size:20px; font-weight:800; color:#1e3a8a;}

/* ===== Estados ===== */
.badge-status{
  padding:4px 8px;
  border-radius:12px;
  font-size:9px;
  font-weight:700;
}
.st-ok{background:#dcfce7;color:#166534;}      /* RESUELTO */
.st-rev{background:#fef9c3;color:#854d0e;}     /* EN_REVISION */
.st-open{background:#e0f2fe;color:#075985;}   /* ABIERTO */
.st-bad{background:#fee2e2;color:#991b1b;}    /* CANCELADO */
.st-na{background:#e5e7eb;color:#374151;}     /* NO_PROCEDE / otros */
</style>

<div class="container-fluid mt-3">

<h5 class="text-primary fw-bold mb-2">
  <i class="bi bi-chat-left-dots"></i> Control PQRS
</h5>

<!-- =========================================================
     CARDS (TODAS JUNTAS, NO SE QUITA NINGUNA)
     - Si ya existían cards globales arriba, ESTE BLOQUE SE SUMA
========================================================== -->
<div class="ap-cards">
  <!-- Cards por tipo (se mantienen y conviven) -->
  <?php
  $tipos = [
    'PETICION'=>'Peticiones',
    'QUEJA'=>'Quejas',
    'RECLAMO'=>'Reclamos',
    'SUGERENCIA'=>'Sugerencias'
  ];
  foreach($tipos as $k=>$lbl):
  ?>
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
          $cls = match($st){
            'RESUELTO'    => 'st-ok',
            'EN_REVISION' => 'st-rev',
            'ABIERTO'     => 'st-open',
            'CANCELADO'   => 'st-bad',
            'NO_PROCEDE'  => 'st-na',
            default       => 'st-na'
          };
        ?>
          <tr>
            <td>
              <!-- Link PDF RESTAURADO -->
              <a href="pqrs_pdf.php?id_case=<?=(int)$r['id_case']?>"
                 target="_blank"
                 class="btn btn-outline-danger btn-sm">
                <i class="fas fa-file-pdf"></i>
              </a>
            </td>
            <td><b><?=h($r['fol_pqrs'])?></b></td>

            <!-- Cliente: Clave + Razón Social (API) -->
            <td class="clte" data-clte="<?=h($r['cve_clte'])?>">
              <?=h($r['cve_clte'])?>
            </td>

            <td><?=h($r['ref_folio'])?></td>
            <td><?=h($r['tipo'])?></td>

            <!-- Reportó (columna REAL) -->
            <td><?=h($r['reporta_nombre'])?></td>

            <!-- Cierre / Estado con colores correctos -->
            <td>
              <span class="badge-status <?=$cls?>">
                <?=h($r['status_clave'])?>
              </span>
            </td>

            <!-- Fecha dd/mm/aaaa -->
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

<!-- =========================================================
     CLIENTES DESDE API (Razón Social)
========================================================== -->
<script>
fetch('../api/clientes.php')
  .then(r => r.json())
  .then(data => {
    const map = {};
    data.forEach(c => {
      if (c.Cve_Cte && c.RazonSocial) {
        map[c.Cve_Cte] = c.RazonSocial;
      }
    });
    document.querySelectorAll('.clte').forEach(td => {
      const cve = td.dataset.clte;
      if (map[cve]) {
        td.textContent = cve + ' – ' + map[cve];
      }
    });
  })
  .catch(()=>{});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
