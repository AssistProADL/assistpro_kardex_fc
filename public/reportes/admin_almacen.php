<?php
require_once __DIR__ . '/../../app/db.php';

/* ==== Parámetros ==== */
$perPage = 25;
$page    = max(1, (int)($_GET['page'] ?? 1));
$off     = ($page-1)*$perPage;

/* Filtros por columna (todos los visibles) */
$f = [
  'desc_cia'        => trim($_GET['f_desc_cia']        ?? ''),
  'zona_nombre'     => trim($_GET['f_zona_nombre']     ?? ''), // al.des_almac
  'almacen_desc'    => trim($_GET['f_almacen_desc']    ?? ''), // ap.nombre
  'zona_clave'      => trim($_GET['f_zona_clave']      ?? ''), // al.cve_almac
  'ch_cve_almac'    => trim($_GET['f_ch_cve_almac']    ?? ''), // ch.cve_almac
  'clave_cont'      => trim($_GET['f_clave_cont']      ?? ''), // ch.Clave_Contenedor
  'descripcion'     => trim($_GET['f_descripcion']     ?? ''), // ch.descripcion
  'pedido'          => trim($_GET['f_pedido']          ?? ''), // ch.Pedido
  'sufijo'          => trim($_GET['f_sufijo']          ?? ''), // ch.sufijo
  'tipo'            => trim($_GET['f_tipo']            ?? ''), // ch.tipo
];
$export = isset($_GET['export']) && $_GET['export']==='excel';

/* ==== Base de consulta (Activas) ====
   - almacenp.clave -> almacen.cve_almacenp (zona)
   - charolas.cve_almac -> almacenp.clave   (almacén)
*/
$params = [];
$sqlBase = "
  FROM stg_c_charolas ch
  LEFT JOIN stg_c_almacenp ap
         ON ap.Id      = ch.cve_almac
  LEFT JOIN stg_c_almacen al
         ON al.cve_almacenp  = ap.Id
  LEFT JOIN stg_c_compania c
         ON c.Cve_Cia  = ap.Cve_Cia
  WHERE COALESCE(ch.Activo,0) = 1
";

/* ==== Aplicar filtros por columna ==== */
function addLike(&$sql, &$params, $col, $val, $paramKey){
  if ($val !== ''){ $sql .= " AND $col LIKE :$paramKey "; $params[$paramKey] = "%{$val}%"; }
}
addLike($sqlBase, $params, "c.des_cia",          $f['desc_cia'],     "f_desc_cia");
addLike($sqlBase, $params, "al.des_almac",       $f['zona_nombre'],  "f_zona_nombre");
addLike($sqlBase, $params, "ap.nombre",       $f['almacen_desc'], "f_almacen_desc");
addLike($sqlBase, $params, "al.cve_almac",       $f['zona_clave'],   "f_zona_clave");
addLike($sqlBase, $params, "ch.cve_almac",       $f['ch_cve_almac'], "f_ch_cve_almac");
addLike($sqlBase, $params, "ch.Clave_Contenedor",$f['clave_cont'],   "f_clave_cont");
addLike($sqlBase, $params, "ch.descripcion",     $f['descripcion'],  "f_descripcion");
addLike($sqlBase, $params, "ch.Pedido",          $f['pedido'],       "f_pedido");
addLike($sqlBase, $params, "ch.sufijo",          $f['sufijo'],       "f_sufijo");
addLike($sqlBase, $params, "ch.tipo",            $f['tipo'],         "f_tipo");

/* ==== Totales para cards ==== */
$total        = (int) db_val("SELECT COUNT(*) ".$sqlBase, $params);
$zonasUnicas  = (int) db_val("SELECT COUNT(DISTINCT al.des_almac) ".$sqlBase, $params);
$almUnicos    = (int) db_val("SELECT COUNT(DISTINCT ap.nombre) ".$sqlBase, $params);
$ciasUnicas   = (int) db_val("SELECT COUNT(DISTINCT c.des_cia) ".$sqlBase, $params);
$contUnicos   = (int) db_val("SELECT COUNT(DISTINCT ch.Clave_Contenedor) ".$sqlBase, $params);

/* ==== Select principal (sin IDContenedor) ==== */
$selectCols = "
  c.des_cia           AS __desc_cia,
  al.des_almac        AS __zona_nombre,
  ap.nombre        AS __almacen_desc,
  al.cve_almac        AS __zona_clave,

  ch.cve_almac        AS ch__cve_almac,
  ch.Clave_Contenedor AS ch__Clave_Contenedor,
  ch.descripcion      AS ch__descripcion,
  ch.Pedido           AS ch__Pedido,
  ch.sufijo           AS ch__sufijo,
  ch.tipo             AS ch__tipo
";

/* ==== Exportar a Excel (CSV con filtros aplicados) ==== */
if ($export) {
  $file = "charolas_activas_" . date('Ymd_His') . ".csv";
  header('Content-Type: text/csv; charset=UTF-8');
  header("Content-Disposition: attachment; filename=\"$file\"");
  // BOM para Excel
  echo "\xEF\xBB\xBF";
  $out = fopen("php://output", "w");

  // Encabezados
  fputcsv($out, [
    'Compañía','Zona (nombre)','Almacén (descr.)','Zona (cve_almac)',
    'cve_almac (cont)','Clave_Contenedor','descripcion','Pedido','sufijo','tipo'
  ]);

  // Traer todos los datos filtrados (sin LIMIT)
  $sqlExp = "SELECT $selectCols $sqlBase ORDER BY COALESCE(al.des_almac,''), ap.nombre, ch.Clave_Contenedor";

  $stmt = dbq($sqlExp, $params);
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($out, [
      $row['__desc_cia'] ?? '',
      $row['__zona_nombre'] ?? '',
      $row['__almacen_desc'] ?? '',
      $row['__zona_clave'] ?? '',
      $row['ch__cve_almac'] ?? '',
      $row['ch__Clave_Contenedor'] ?? '',
      $row['ch__descripcion'] ?? '',
      $row['ch__Pedido'] ?? '',
      $row['ch__sufijo'] ?? '',
      $row['ch__tipo'] ?? '',
    ]);
  }
  fclose($out);
  exit;
}

/* ==== Datos paginados para la grilla ==== */
$sqlData = "
  SELECT $selectCols
  $sqlBase
  ORDER BY COALESCE(al.des_almac,''), ap.nombre, ch.Clave_Contenedor
  LIMIT :lim OFFSET :off
";
$params['lim'] = $perPage;
$params['off'] = $off;
$rows = dbq($sqlData, $params)->fetchAll(PDO::FETCH_ASSOC);

/* Helpers UI */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function qh($arr){ return http_build_query($arr); }
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Infraestructura de Almacén — Charolas (Activas)</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    :root { --blue:#0F5AAD; --bg:#f7f9fc; --card:#fff; --line:#e3e6ee; --text:#1b1f23; }
    *{box-sizing:border-box}
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Arial,sans-serif;margin:0;background:#f7f9fc;color:#1b1f23}
    .wrap{max-width:1200px;margin:0 auto;padding:20px}
    h1{margin:0 0 8px}
    .muted{opacity:.8;font-size:12px;margin:0 0 12px}
    .cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin:10px 0 16px}
    .card{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:12px;box-shadow:0 1px 2px rgba(0,0,0,.04)}
    .card h3{margin:0 0 6px;font-size:12px;font-weight:600;opacity:.8}
    .card .num{font-size:26px;font-weight:700;color:var(--blue)}
    .block{background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px;box-shadow:0 1px 2px rgba(0,0,0,.03);margin-top:12px}
    .row{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}
    .row input[type="text"], .row select{padding:6px 8px;border:1px solid var(--line);border-radius:10px;font-size:12px;min-width:160px}
    .row button, .btn{padding:6px 10px;border-radius:10px;border:1px solid var(--line);background:#fff;cursor:pointer;font-size:12px;text-decoration:none}
    .btn.primary{background:#0F5AAD;border-color:#0F5AAD;color:#fff}

    /* Tabla compacta 10px + no-wrap + scroll X/Y */
    table{border-collapse:collapse;width:max-content;min-width:100%;font-size:10px;background:#fff;table-layout:auto}
    th,td{border-bottom:1px solid #eef1f6;padding:6px 8px;line-height:14px;white-space:nowrap;vertical-align:middle}
    th{background:#f3f6fb;font-weight:600}
    tr:last-child td{border-bottom:none}
    .scroll{max-height:26rem;overflow:auto;border:1px solid var(--line);border-radius:12px;background:#fff;width:100%}
    .pill{display:inline-block;background:#eef3ff;border:1px solid #d9e4ff;color:#274690;padding:2px 8px;border-radius:999px;font-size:11px;margin-left:6px}
    .pager a{margin-right:8px;font-size:11px}
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Infraestructura de Almacén — Charolas (Activas)</h1>
    <p class="muted">Filtra por cualquier columna. Exporta a Excel con los filtros aplicados.</p>

    <!-- Cards resumen -->
    <div class="cards">
      <div class="card"><h3>Resultados</h3><div class="num"><?= number_format($total) ?></div></div>
      <div class="card"><h3>Zonas únicas</h3><div class="num"><?= number_format($zonasUnicas) ?></div></div>
      <div class="card"><h3>Almacenes únicos</h3><div class="num"><?= number_format($almUnicos) ?></div></div>
      <div class="card"><h3>Compañías únicas</h3><div class="num"><?= number_format($ciasUnicas) ?></div></div>
      <div class="card"><h3>Contenedores únicos</h3><div class="num"><?= number_format($contUnicos) ?></div></div>
    </div>

    <div class="block">
      <form class="row" method="get" action="">
        <input name="f_desc_cia"        value="<?=h($f['desc_cia'])?>"       placeholder="Compañía">
        <input name="f_zona_nombre"     value="<?=h($f['zona_nombre'])?>"    placeholder="Zona (nombre)">
        <input name="f_almacen_desc"    value="<?=h($f['almacen_desc'])?>"   placeholder="Almacén (descr.)">
        <input name="f_zona_clave"      value="<?=h($f['zona_clave'])?>"     placeholder="Zona (cve_almac)">
        <input name="f_ch_cve_almac"    value="<?=h($f['ch_cve_almac'])?>"   placeholder="cve_almac (charola)">
        <input name="f_clave_cont"      value="<?=h($f['clave_cont'])?>"     placeholder="Clave_Contenedor">
        <input name="f_descripcion"     value="<?=h($f['descripcion'])?>"    placeholder="descripcion">
        <input name="f_pedido"          value="<?=h($f['pedido'])?>"         placeholder="Pedido">
        <input name="f_sufijo"          value="<?=h($f['sufijo'])?>"         placeholder="sufijo">
        <input name="f_tipo"            value="<?=h($f['tipo'])?>"           placeholder="tipo">

        <button>Aplicar</button>
        <?php if (array_filter($f)): ?>
          <a class="btn" href="admin_almacen.php">Limpiar</a>
        <?php endif; ?>
        <a class="btn primary" href="?<?= h(qh(array_merge($_GET,['export'=>'excel','page'=>1]))) ?>">Exportar a Excel</a>
        <span class="pill"><?= number_format($total) ?> resultados</span>
        <span class="pill">pág <?= $page ?> · <?= $perPage ?>/pág</span>
      </form>

      <div class="scroll">
        <table>
          <thead>
            <tr>
              <th>Compañía</th>
              <th>Zona (nombre)</th>
              <th>Almacén (descr.)</th>
              <th>Zona (cve_almac)</th>
              <th>cve_almac (charola)</th>
              <th>Clave_Contenedor</th>
              <th>descripcion</th>
              <th>Pedido</th>
              <th>sufijo</th>
              <th>tipo</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= h($r['__desc_cia'] ?? '') ?></td>
              <td><?= h($r['__zona_nombre'] ?? '') ?></td>
              <td><?= h($r['__almacen_desc'] ?? '') ?></td>
              <td><?= h($r['__zona_clave'] ?? '') ?></td>
              <td><?= h($r['ch__cve_almac'] ?? '') ?></td>
              <td><?= h($r['ch__Clave_Contenedor'] ?? '') ?></td>
              <td><?= h($r['ch__descripcion'] ?? '') ?></td>
              <td><?= h($r['ch__Pedido'] ?? '') ?></td>
              <td><?= h($r['ch__sufijo'] ?? '') ?></td>
              <td><?= h($r['ch__tipo'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <tr><td colspan="10" style="text-align:center;opacity:.75">Sin resultados con los filtros actuales.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="pager" style="margin-top:8px">
        <?php if ($page>1): ?>
          <a class="btn" href="?<?= h(qh(array_merge($_GET,['page'=>$page-1]))) ?>">« Anterior</a>
        <?php endif; ?>
        <?php if ($off + count($rows) < $total): ?>
          <a class="btn" href="?<?= h(qh(array_merge($_GET,['page'=>$page+1]))) ?>">Siguiente »</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>
