<?php
// kardex_preview.php — Vista directa (read-only) sobre v_kardex_doble_partida
// No toca tu diseño existente. Úsalo solo para validar que la vista devuelve filas en el navegador.

error_reporting(E_ALL);
ini_set('display_errors', '1');

// 1) Conexión
$__dbLoaded = false;
$__dbPath = __DIR__ . '/../app/db.php';
if (file_exists($__dbPath)) {
    require_once $__dbPath;
    $__dbLoaded = isset($pdo) && ($pdo instanceof PDO);
}
if (!$__dbLoaded) {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;dbname=assistpro_etl_fc;charset=utf8mb4',
        'root', '',
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        )
    );
}

// 2) Parámetros simples
$empresa_id  = (isset($_GET['empresa_id']) && $_GET['empresa_id'] !== '') ? trim($_GET['empresa_id']) : null;
$limit       = (isset($_GET['limit']) && ctype_digit((string)$_GET['limit'])) ? (int)$_GET['limit'] : 200;
$offset      = (isset($_GET['offset']) && ctype_digit((string)$_GET['offset'])) ? (int)$_GET['offset'] : 0;

// 3) WHERE mínimo
$where = array();
$params = array();
if ($empresa_id !== null) { $where[] = 'empresa_id = :empresa_id'; $params[':empresa_id'] = $empresa_id; }
$whereSql = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

// 4) Prueba de total
$sqlTotal = "SELECT COUNT(*) FROM v_kardex_doble_partida $whereSql";
$total = 0;
try { $total = (int) qOne($pdo, $sqlTotal, $params); } catch (Throwable $e) { $total = -1; }

// 5) Consulta directa (sin ORDER BY para evitar errores de columnas)
$sql = "
  SELECT * 
  FROM v_kardex_doble_partida
  $whereSql
  LIMIT :limit OFFSET :offset
";
$st = $pdo->prepare($sql);
foreach ($params as $k=>$v) { $st->bindValue($k, $v); }
$st->bindValue(':limit', $limit, PDO::PARAM_INT);
$st->bindValue(':offset', $offset, PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll();

// 6) Render muy simple (no modifica tu front real)
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Preview v_kardex_doble_partida</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Helvetica,Arial,sans-serif;padding:16px}
table{border-collapse:collapse;width:100%;font-size:12px}
th,td{border:1px solid #ddd;padding:6px}
th{background:#f6f6f6;text-align:left;position:sticky;top:0}
.caption{margin:0 0 12px 0;font-weight:600}
.badge{display:inline-block;padding:2px 8px;border-radius:999px;background:#eef;border:1px solid #99f;color:#003;margin-left:8px}
.toolbar{display:flex;gap:8px;align-items:center;margin-bottom:12px}
.toolbar input{padding:6px 8px;border:1px solid #ccc;border-radius:6px}
.toolbar button{padding:6px 10px;border:1px solid #ccc;border-radius:6px;background:#fafafa;cursor:pointer}
</style>
</head>
<body>

<h3 class="caption">Preview de vista: v_kardex_doble_partida
  <span class="badge">DB: <?php echo htmlspecialchars(qOne($pdo,'SELECT DATABASE()')); ?></span>
  <span class="badge">Total (filtro): <?php echo (int)$total; ?></span>
  <span class="badge">Limit/Offset: <?php echo (int)$limit; ?> / <?php echo (int)$offset; ?></span>
</h3>

<div class="toolbar">
  <form method="get" action="">
    Empresa:
    <input name="empresa_id" value="<?php echo htmlspecialchars($empresa_id ?? ''); ?>" placeholder="empresa_id">
    <input name="limit" value="<?php echo (int)$limit; ?>" size="4">
    <input name="offset" value="<?php echo (int)$offset; ?>" size="4">
    <button type="submit">Aplicar</button>
  </form>
</div>

<?php if (!$rows): ?>
  <div style="padding:8px;border:1px solid #f99;background:#fee;color:#900">No hay filas para el filtro actual.</div>
<?php else: ?>
  <div style="overflow:auto;max-height:70vh;border:1px solid #ddd">
  <table>
    <thead>
      <tr>
        <?php foreach (array_keys($rows[0]) as $col): ?>
          <th><?php echo htmlspecialchars($col); ?></th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <?php foreach ($r as $v): ?>
            <td><?php echo htmlspecialchars((string)$v); ?></td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
<?php endif; ?>

</body>
</html>
<?php
// helpers locales
function qOne($pdo, $sql, $p = array()) {
    $st = $pdo->prepare($sql);
    $st->execute($p);
    return $st->fetchColumn();
}
?>