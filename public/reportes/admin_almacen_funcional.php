<?php
require_once __DIR__ . '/../../app/db.php';

/* ==== Parámetros ==== */
$perPage = 25;
$page    = max(1, (int)($_GET['page'] ?? 1));
$off     = ($page-1)*$perPage;

$sub     = trim($_GET['sub'] ?? '');   // filtro por zona (c_almacen.nombre)
$q       = trim($_GET['q']   ?? '');   // búsqueda (zona / compañía)

/* ==== Catálogo de Zonas (nombre) ==== */

$optsSub = db_all("
  SELECT DISTINCT des_almac
  FROM c_almacen
  WHERE des_almac IS NOT NULL AND des_almac <> ''
  ORDER BY des_almac
  LIMIT 2000
");

/* ==== Base de consulta ====
   charolas -> almacen (zona)   : ap.clave = al.cve_almacenp
   charolas -> almacenp (descr) : ap.clave     = ch.cve_almac  (MOSTRAR ap.nombre)
   Solo contenedores Activo = 1
*/
$params = [];
$sqlBase = "
  FROM c_charolas ch
 
   
  LEFT JOIN c_almacenp ap
    ON  ap.id      = ch.cve_almac
   

 LEFT JOIN c_almacen al
    ON  al.cve_almacenp = ap.id
  
LEFT JOIN c_compania c
    ON c.cve_cia  = ap.cve_cia
  WHERE 1=1
    AND COALESCE(ch.Activo,0) = 1
";

/* Filtros */
if ($sub !== '') {
  $sqlBase .= " AND al.nombre = :sub ";
  $params['sub'] = $sub;
}
if ($q !== '') {
  $sqlBase .= " AND (
      al.nombre LIKE :q
      OR c.des_cia  LIKE :q
  )";
  $params['q'] = "%{$q}%";
}

/* Conteo */
$total = (int) db_val("SELECT COUNT(*) ".$sqlBase, $params);

/* SELECT (sin IDContenedor) 
   Cve_Almac: reemplazada por ap.nombre (descripción del almacén de c_almacenp)
*/
$sqlData = "
  SELECT
    c.des_cia           AS __desc_cia,
    al.des_almac        AS __zona_nombre,        -- zona de almacenaje (c_almacen.nombre)
    ap.nombre        AS __almacen_desc,       -- *** descripción del almacén (c_almacenp.nombre) ***
    al.cve_almac        AS __zona_clave,         -- clave de zona (referencia)

    /* columnas visibles de charolas (sin IDContenedor ni Permanente) */
    ch.cve_almac        AS ch__cve_almac,        -- se mantiene para referencia interna
    ch.Clave_Contenedor AS ch__Clave_Contenedor,
    ch.descripcion      AS ch__descripcion,
    ch.Pedido           AS ch__Pedido,
    ch.sufijo           AS ch__sufijo,
    ch.tipo             AS ch__tipo
  $sqlBase
  ORDER BY COALESCE(al.des_almac,''), ap.nombre, ch.Clave_Contenedor
  LIMIT :lim OFFSET :off
";
$params['lim'] = $perPage;
$params['off'] = $off;
$rows = dbq($sqlData, $params)->fetchAll(PDO::FETCH_ASSOC);

/* Helpers UI */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
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
    .wrap{max-width:1100px;margin:0 auto;padding:20px}
    h1{margin:0 0 8px}
    .muted{opacity:.8;font-size:12px;margin:0 0 12px}
    .block{background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px;box-shadow:0 1px 2px rgba(0,0,0,.03);margin-top:12px}
    .row{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}
    .row input[type="text"], .row select{padding:6px 8px;border:1px solid var(--line);border-radius:10px;font-size:12px}
    .row button{padding:6px 10px;border-radius:10px;border:1px solid var(--line);background:#fff;cursor:pointer;font-size:12px}

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
    <p class="muted">
      Mostrando solo contenedores con <code>Activo = 1</code>.  
      La columna <strong>Almacén</strong> proviene de <code>c_almacenp.nombre</code> (relación: <code>c_almacenp.clave = c_charolas.cve_almac</code>).
    </p>

    <div class="block">
      <form class="row" method="get" action="">
        <select name="sub" title="Zona (nombre)">
          <option value="">— Zona de almacenaje (nombre) —</option>
          <?php foreach($optsSub as $o){
            $ds = $o['des_almac']; $sel = ($sub===$ds)?'selected':'';
          ?>
            <option value="<?=h($ds)?>" <?=$sel?>><?=h($ds)?></option>
          <?php } ?>
        </select>

        <input type="text" name="q" placeholder="Buscar por Zona (nombre) o Compañía" value="<?=h($q)?>">
        <button>Aplicar</button>
        <?php if ($sub || $q): ?>
          <a href="admin_almacen.php" style="font-size:12px;margin-left:6px">Limpiar</a>
        <?php endif; ?>
        <span class="pill"><?= number_format($total) ?> resultados</span>
        <span class="pill">pág <?= $page ?> · <?= $perPage ?>/pág</span>
      </form>

      <div class="scroll">
        <table>
          <thead>
            <tr>
              <th>Compañía</th>
              <th>Zona (nombre)</th>
              <th>Almacén (descr. c_almacenp)</th>
              <th>Zona (cve_almac)</th>

              <!-- Sin IDContenedor -->
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
            <tr><td colspan="10" style="text-align:center;opacity:.75">Sin resultados. Verifica que existan charolas con Activo=1 y que cve_almac coincida con claves en c_almacenp / c_almacen.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="pager" style="margin-top:8px">
        <?php if ($page>1): ?>
          <a href="?<?= http_build_query(array_merge($_GET,['page'=>$page-1])) ?>">« Anterior</a>
        <?php endif; ?>
        <?php if ($off + count($rows) < $total): ?>
          <a href="?<?= http_build_query(array_merge($_GET,['page'=>$page+1])) ?>">Siguiente »</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>
