<?php
// public/utilerias/generador.php
require_once __DIR__ . '/../../app/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function nice_label_global(string $c): string {
    $c = str_replace('_',' ', $c);
    $c = preg_replace('/\s+id$/i','', $c);
    return mb_convert_case($c, MB_CASE_TITLE, 'UTF-8');
}

/* ================= Helpers ================= */

function tablas(PDO $pdo): array {
    $sql = "SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_type = 'BASE TABLE'
            ORDER BY table_name";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
}

function columnas(PDO $pdo, string $t): array {
    $st = $pdo->prepare(
        "SELECT column_name,data_type,column_type,is_nullable,column_key
         FROM information_schema.columns
         WHERE table_schema = DATABASE()
           AND table_name=?
         ORDER BY ordinal_position"
    );
    $st->execute([$t]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function friendly_title(string $table): string {
    $t = preg_replace('/^c_/', '', $table);
    $t = str_replace('_', ' ', $t);
    return mb_convert_case($t, MB_CASE_TITLE, "UTF-8");
}

/* ================= Acción: GENERAR ================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'generate') {
    $pdo   = db_pdo();
    $tabla = preg_replace('/[^a-zA-Z0-9_]/', '', (string)($_POST['tabla'] ?? ''));
    if ($tabla === '') { die('Tabla requerida'); }

    // columnas seleccionadas
    $colsSel = array_values(array_filter(array_map(
        fn($c) => preg_replace('/[^a-zA-Z0-9_]/', '', $c),
        $_POST['cols'] ?? []
    )));

    if (empty($colsSel)) {
        $colsSel = array_column(columnas($pdo, $tabla), 'column_name');
    }

    // friendly names
    $friendlyPost = $_POST['friendly'] ?? [];
    $friendly = [];
    foreach ($friendlyPost as $col => $fn) {
        $colSafe = preg_replace('/[^a-zA-Z0-9_]/','', $col);
        if ($colSafe === '') continue;
        $fn = trim((string)$fn);
        if ($fn === '') {
            $fn = nice_label_global($colSafe);
        }
        $friendly[$colSafe] = $fn;
    }

    $colsPhp      = var_export($colsSel, true);
    $friendlyPhp  = var_export($friendly, true);
    $title        = friendly_title($tabla);
    $file         = 'cat_'.$tabla.'.php';

    // ✅ RUTA CORREGIDA — ahora los catálogos se generan en /public/catalogos/
    $abs          = dirname(__DIR__) . '/catalogos/' . $file;

    // --- PLANTILLA BASE ---
    $tpl = <<<'PHP'
<?php
require_once __DIR__ . '/../../app/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES,'UTF-8'); }

$pdo = db_pdo();
$table = '__TABLA__';
$title = '__TITLE__';
$cols = __COLS__;
$friendly = __FRIENDLY__;

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
PHP;

    // Reemplazar placeholders
    $tpl = str_replace(
        ['__TABLA__','__COLS__','__TITLE__','__FRIENDLY__'],
        [$tabla, $colsPhp, addslashes($title), $friendlyPhp],
        $tpl
    );

    // Crear archivo en /public/catalogos
    file_put_contents($abs, $tpl);

    // Redirigir para abrir el catálogo generado
    $url = 'catalogos/'.basename($abs);
    echo "<script>
        if (parent && parent.document && parent.document.getElementById('content')) {
            parent.document.getElementById('content').src = '$url';
        } else {
            window.location.href = '$url';
        }
    </script>";
    exit;
}

/* ================= UI DEL GENERADOR ================= */

$pdo      = db_pdo();
$tablaSel = isset($_GET['tabla']) ? preg_replace('/[^a-zA-Z0-9_]/','', $_GET['tabla']) : '';
$lista    = tablas($pdo);
$cols     = $tablaSel ? columnas($pdo, $tablaSel) : [];

require_once __DIR__ . '/../bi/_menu_global.php';
?>
<div class="container-fluid" style="font-size:10px;">
  <div style="background:#fff;border:1px solid #eef1f4;border-radius:10px;padding:10px;">
    <h3 style="color:#000F9F;">Generador de catálogos</h3>

    <form method="get" class="mb-3" style="display:flex;gap:12px;align-items:center;">
      <label>Tabla:
        <select name="tabla" onchange="this.form.submit()" style="padding:4px 6px;font-size:10px;">
          <option value="">-- Selecciona tabla --</option>
          <?php foreach($lista as $t): ?>
            <option value="<?=h($t)?>" <?=$t===$tablaSel?'selected':''?>><?=h($t)?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <?php if($tablaSel): ?>
        <span style="color:#7a8088;font-size:11px;">
          Archivo a generar:
          <code>public/catalogos/cat_<?=h($tablaSel)?>.php</code>
        </span>
      <?php endif; ?>
    </form>

    <?php if($tablaSel && $cols): ?>
      <form method="post">
        <input type="hidden" name="accion" value="generate">
        <input type="hidden" name="tabla" value="<?=h($tablaSel)?>">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:6px 12px;">
          <?php foreach($cols as $c): ?>
            <?php $colName = $c['column_name']; $defFriendly = nice_label_global($colName); ?>
            <label style="font-size:11px;display:flex;flex-direction:column;background:#f8f9fa;border:1px solid #e2e6ea;border-radius:6px;padding:4px 6px;">
              <span style="display:flex;align-items:center;margin-bottom:3px;">
                <input type="checkbox" name="cols[]" value="<?=h($colName)?>" checked style="margin-right:6px;">
                <strong><?=h($colName)?></strong>
                <span style="color:#7a8088;font-size:10px;margin-left:4px;">(<?=h($c['data_type'])?>)</span>
              </span>
              <input
                type="text"
                name="friendly[<?=h($colName)?>]"
                value="<?=h($defFriendly)?>"
                placeholder="Nombre amigable"
                style="padding:3px 5px;border:1px solid #dde1e6;border-radius:4px;font-size:10px;">
            </label>
          <?php endforeach; ?>
        </div>
        <div style="margin-top:12px;">
          <button style="padding:6px 12px;border-radius:999px;background:#000F9F;color:#fff;border:0;font-size:11px;cursor:pointer;">
            Generar catálogo
          </button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
