<?php
require_once __DIR__ . '/../../app/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES,'UTF-8'); }

$pdo = db_pdo();
$table = 'c_aduanas';
$title = 'Aduanas';
$cols = array (
  0 => 'Id_CatAduana',
  1 => 'Cve_CatAduana',
  2 => 'Des_CatAduana',
);
$friendly = array (
  'Id_CatAduana' => 'Id Cataduana',
  'Cve_CatAduana' => 'Cve Cataduana',
  'Des_CatAduana' => 'Des Cataduana',
);

// Listado simple
$rows = [];
$total = 0;
try {
    $sql = "SELECT * FROM $table LIMIT 25";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $total = count($rows);
} catch (Throwable $e) {
    echo "<div style='color:red'>Error: ".h($e->getMessage())."</div>";
}

require_once __DIR__ . '/../bi/_menu_global.php';
?>
<div class="container-fluid" style="font-size:10px;">
  <div style="background:#fff;border:1px solid #eef1f4;border-radius:10px;padding:10px;">
    <h4 style="color:#000F9F;margin-bottom:8px;"><?=h($title)?></h4>
    <p class="text-muted">Total registros: <?=$total?></p>
    <div style="overflow:auto;max-height:70vh;">
      <table class="table table-bordered table-sm" style="font-size:10px;width:100%;white-space:nowrap;">
        <thead class="table-light">
          <tr>
            <?php foreach($cols as $c): ?>
              <th><?=h($friendly[$c] ?? $c)?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach($rows as $r): ?>
            <tr>
              <?php foreach($cols as $c): ?>
                <td><?=h((string)($r[$c] ?? ''))?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>