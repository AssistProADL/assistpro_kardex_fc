<?php
require_once __DIR__ . '/../../app/db.php';

/* ===== Parámetros UI ===== */
$show     = $_GET['show']    ?? '';                 // 'almacenes' | 'productos' | 'usuarios' | 'ubicaciones'
$q        = trim($_GET['q']  ?? '');                // filtro texto para la grilla visible
$almIn    = trim($_GET['almacen'] ?? '');           // almacén principal (c_almacenp.clave)
$perPage  = 25;

/* Paginación por grilla */
$pageA = max(1, (int)($_GET['pageA'] ?? 1));  $offA = ($pageA-1)*$perPage;
$pageP = max(1, (int)($_GET['pageP'] ?? 1));  $offP = ($pageP-1)*$perPage;
$pageU = max(1, (int)($_GET['pageU'] ?? 1));  $offU = ($pageU-1)*$perPage;
$pageUb= max(1, (int)($_GET['pageUb']?? 1));  $offUb= ($pageUb-1)*$perPage;

/* ===== KPIs ===== */
$tot_prod = (int) db_val("SELECT COUNT(*) FROM c_articulo");
$tot_alm  = (int) db_val("SELECT COUNT(*) FROM c_almacenp");
$tot_usr  = (int) db_val("SELECT COUNT(*) FROM c_usuario");
$tot_ubi  = (int) db_val("SELECT COUNT(CodigoCSD) FROM c_ubicacion");

/* ===== Helpers ===== */
function likeParam($s){ return "%{$s}%"; }

/* Catálogo de almacenes principales para selects */
$opts = db_all("SELECT clave, nombre FROM c_almacenp ORDER BY clave LIMIT 1000");

/* Resolver empresa del almacén principal (para filtrar productos/usuarios por compañía del AP) */
$empresaDeAlm = null;
if ($almIn !== '') {
  $empresaDeAlm = db_val("SELECT empresa_id FROM c_almacenp WHERE clave = :c LIMIT 1", ['c'=>$almIn]);
}

/* ===== Grilla: Almacenes principales (AP) + # ubicaciones (via ub -> al -> ap) ===== */
$paramsA = [];
$sqlAlmData = "
  SELECT 
    c.des_cia             AS desc_cia,
    ap.empresa_id,
    ap.clave              AS cve_almacenp,
    ap.nombre             AS nombre_almacenp,
    COALESCE(u.num_ub,0)  AS ubicaciones
  FROM c_almacenp AS ap
  LEFT JOIN c_compania AS c
    ON c.empresa_id = ap.empresa_id
  LEFT JOIN (
    SELECT a.empresa_id, a.cve_almacenp AS clave_ap, COUNT(ub.CodigoCSD) AS num_ub
    FROM c_ubicacion AS ub
    INNER JOIN c_almacen AS a
      ON a.empresa_id = ub.empresa_id AND a.cve_almac = ub.cve_almac
    GROUP BY a.empresa_id, a.cve_almacenp
  ) AS u
    ON u.empresa_id = ap.empresa_id AND u.clave_ap = ap.clave
  WHERE 1=1
";
if ($almIn !== '') { $sqlAlmData .= " AND ap.clave = :almIn"; $paramsA['almIn'] = $almIn; }
if ($q !== '') {
  $sqlAlmData .= " AND (ap.clave LIKE :qa OR ap.nombre LIKE :qa OR c.des_cia LIKE :qa OR CAST(ap.empresa_id AS CHAR) LIKE :qa)";
  $paramsA['qa'] = likeParam($q);
}
$sqlAlmCnt = "SELECT COUNT(*) FROM ($sqlAlmData) x";
$totalA = (int) db_val($sqlAlmCnt, $paramsA);
$sqlAlmData .= " ORDER BY ap.empresa_id, ap.clave LIMIT :limA OFFSET :offA";
$paramsA['limA'] = $perPage; $paramsA['offA'] = $offA;
$almacenes = dbq($sqlAlmData, $paramsA)->fetchAll();
$hasMoreA = ($offA + count($almacenes)) < $totalA;

/* ===== Grilla: Productos ===== */
$paramsP = [];
$sqlProdData = "
  SELECT 
    c.des_cia AS desc_cia,
    p.empresa_id,
    p.cve_articulo,
    p.des_articulo
  FROM c_articulo AS p
  LEFT JOIN c_compania AS c ON c.empresa_id = p.empresa_id
  WHERE 1=1
";
if ($empresaDeAlm !== null) { $sqlProdData .= " AND p.empresa_id = :empP"; $paramsP['empP'] = $empresaDeAlm; }
if ($q !== '') {
  $sqlProdData .= " AND (p.cve_articulo LIKE :qp OR p.des_articulo LIKE :qp OR c.des_cia LIKE :qp OR CAST(p.empresa_id AS CHAR) LIKE :qp)";
  $paramsP['qp'] = likeParam($q);
}
$sqlProdCnt = "SELECT COUNT(*) FROM ($sqlProdData) x";
$totalP = (int) db_val($sqlProdCnt, $paramsP);
$sqlProdData .= " ORDER BY p.empresa_id, p.cve_articulo LIMIT :limP OFFSET :offP";
$paramsP['limP'] = $perPage; $paramsP['offP'] = $offP;
$productos = dbq($sqlProdData, $paramsP)->fetchAll();
$hasMoreP = ($offP + count($productos)) < $totalP;

/* ===== Grilla: Usuarios (t_usuariosperfil + t_roles) ===== */
$paramsU = [];
$sqlUsrBase = "
  FROM c_usuario AS u
  LEFT JOIN c_compania       AS c  ON c.empresa_id = u.empresa_id
  LEFT JOIN t_usuariosperfil AS up ON up.empresa_id = u.empresa_id AND up.cve_usuario = u.cve_usuario
  LEFT JOIN t_roles          AS r  ON r.empresa_id = up.empresa_id AND r.id_role = up.Id_Perfil
  WHERE 1=1
";
if ($empresaDeAlm !== null) { $sqlUsrBase .= " AND u.empresa_id = :empU"; $paramsU['empU'] = $empresaDeAlm; }
if ($q !== '') {
  $sqlUsrBase .= " AND (u.cve_usuario LIKE :qu OR u.nombre_completo LIKE :qu OR u.email LIKE :qu OR c.des_cia LIKE :qu OR r.rol LIKE :qu)";
  $paramsU['qu'] = likeParam($q);
}
$sqlUsrCnt = "
  SELECT COUNT(*) FROM (
    SELECT u.empresa_id, u.cve_usuario
    $sqlUsrBase
    GROUP BY u.empresa_id, u.cve_usuario
  ) AS uu
";
$totalU = (int) db_val($sqlUsrCnt, $paramsU);
$sqlUsrData = "
  SELECT 
    c.des_cia AS desc_cia,
    u.empresa_id,
    u.cve_usuario,
    u.nombre_completo,
    u.email,
    u.status,
    GROUP_CONCAT(DISTINCT r.rol ORDER BY r.rol SEPARATOR ', ') AS perfiles
  $sqlUsrBase
  GROUP BY c.des_cia, u.empresa_id, u.cve_usuario, u.nombre_completo, u.email, u.status
  ORDER BY u.empresa_id, u.cve_usuario
  LIMIT :limU OFFSET :offU
";
$paramsU['limU'] = $perPage; $paramsU['offU'] = $offU;
$usuarios = dbq($sqlUsrData, $paramsU)->fetchAll();
$hasMoreU = ($offU + count($usuarios)) < $totalU;

/* ===== Grilla: Ubicaciones (ub -> al -> ap) ===== */
$paramsUb = [];
$sqlUbiBase = "
  FROM c_ubicacion AS ub
  INNER JOIN c_almacen AS al
    ON al.empresa_id = ub.empresa_id AND al.cve_almac = ub.cve_almac
  LEFT JOIN c_almacenp AS ap
    ON ap.empresa_id = al.empresa_id AND ap.clave = al.cve_almacenp
  LEFT JOIN c_compania  AS c
    ON c.empresa_id = ub.empresa_id
  LEFT JOIN (
    /* conteo por AP para la columna '# Ubicaciones (AP)' */
    SELECT a.empresa_id, a.cve_almacenp AS clave_ap, COUNT(ub.CodigoCSD) AS num_ub_ap
    FROM c_ubicacion AS ub
    INNER JOIN c_almacen AS a
      ON a.empresa_id = ub.empresa_id AND a.cve_almac = ub.cve_almac
    GROUP BY a.empresa_id, a.cve_almacenp
  ) AS uap
    ON uap.empresa_id = ap.empresa_id AND uap.clave_ap = ap.clave
  WHERE 1=1
";
if ($almIn !== '') { $sqlUbiBase .= " AND ap.clave = :almUb"; $paramsUb['almUb'] = $almIn; }
if ($q !== '') {
  $sqlUbiBase .= " AND (
      ap.clave LIKE :qub OR ap.nombre LIKE :qub
      OR al.des_almac LIKE :qub
      OR ub.CodigoCSD LIKE :qub
      OR c.des_cia LIKE :qub
  )";
  $paramsUb['qub'] = likeParam($q);
}
$sqlUbiCnt = "SELECT COUNT(*) $sqlUbiBase";
$totalUb = (int) db_val($sqlUbiCnt, $paramsUb);
$sqlUbiData = "
  SELECT
    c.des_cia      AS desc_cia,
    ub.empresa_id,
    ap.clave       AS cve_almacenp,
    ap.nombre      AS nom_almacenp,
    al.des_almac   AS des_subalmac,
    ub.CodigoCSD   AS codigo_ubic,
    COALESCE(uap.num_ub_ap,0) AS num_ub_ap
  $sqlUbiBase
  ORDER BY ub.empresa_id, ap.clave, al.des_almac, ub.CodigoCSD
  LIMIT :limUb OFFSET :offUb
";
$paramsUb['limUb'] = $perPage;
$paramsUb['offUb'] = $offUb;
$ubicaciones = dbq($sqlUbiData, $paramsUb)->fetchAll();
$hasMoreUb = ($offUb + count($ubicaciones)) < $totalUb;

/* ===== Totales por compañía ===== */
$por_empresa = db_all("
  SELECT
    e.empresa_id,
    COALESCE(c.des_cia, CONCAT('Empresa ', e.empresa_id)) AS desc_cia,
    COALESCE(p.cnt,0) AS productos,
    COALESCE(a.cnt,0) AS almacenes,
    COALESCE(u.cnt,0) AS usuarios
  FROM (
    SELECT empresa_id FROM c_articulo
    UNION
    SELECT empresa_id FROM c_almacenp
    UNION
    SELECT empresa_id FROM c_usuario
  ) AS e
  LEFT JOIN c_compania AS c
    ON c.empresa_id = e.empresa_id
  LEFT JOIN (SELECT empresa_id, COUNT(*) cnt FROM c_articulo  GROUP BY empresa_id) p
    ON p.empresa_id = e.empresa_id
  LEFT JOIN (SELECT empresa_id, COUNT(*) cnt FROM c_almacenp GROUP BY empresa_id) a
    ON a.empresa_id = e.empresa_id
  LEFT JOIN (SELECT empresa_id, COUNT(*) cnt FROM c_usuario  GROUP BY empresa_id) u
    ON u.empresa_id = e.empresa_id
  ORDER BY e.empresa_id
");
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Resumen Básico – Conteos</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    :root { --blue:#0F5AAD; --bg:#f7f9fc; --card:#fff; --line:#e3e6ee; --text:#1b1f23; }
    *{box-sizing:border-box}
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Arial,sans-serif;margin:0;background:var(--bg);color:var(--text)}
    .wrap{max-width:1100px;margin:0 auto;padding:24px}
    h1{margin:0 0 8px}
    .muted{opacity:.8;font-size:12px;margin:0 0 16px}
    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-bottom:18px}
    .card{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:16px;box-shadow:0 1px 2px rgba(0,0,0,.04);position:relative}
    .kpi{font-size:32px;font-weight:700;margin-top:6px;color:var(--blue)}
    .link{font-size:12px;position:absolute;right:14px;top:12px}
    .block{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:16px;box-shadow:0 1px 2px rgba(0,0,0,.03);margin-top:12px}

    /* Tabla compacta 10px + no-wrap + scroll X/Y */
    table{border-collapse:collapse;width:max-content;min-width:100%;font-size:10px;background:#fff;table-layout:auto}
    th,td{border-bottom:1px solid #eef1f6;padding:6px 8px;line-height:14px;white-space:nowrap;vertical-align:middle}
    th{background:#f3f6fb;font-weight:600}
    tr:last-child td{border-bottom:none}
    .scroll{max-height:25rem;overflow:auto;border:1px solid var(--line);border-radius:12px;background:#fff;width:100%}
    .scroll::-webkit-scrollbar{height:10px;width:10px}
    .scroll::-webkit-scrollbar-thumb{background:#ccc;border-radius:5px}
    .scroll::-webkit-scrollbar-track{background:#f3f3f3}

    .row{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}
    .row input[type="text"], .row select{padding:6px 8px;border:1px solid var(--line);border-radius:10px;font-size:12px}
    .row button{padding:6px 10px;border-radius:10px;border:1px solid var(--line);background:#fff;cursor:pointer;font-size:12px}
    .caps{font-size:11px;opacity:.75}
    .pill{display:inline-block;background:#eef3ff;border:1px solid #d9e4ff;color:#274690;padding:2px 8px;border-radius:999px;font-size:11px;margin-left:6px}
    .pager a{margin-right:8px;font-size:11px}
    .btn-link{font-size:10px;padding:4px 8px;border:1px solid #d0d7de;border-radius:8px;background:#fff;text-decoration:none}
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Resumen Básico – Foam Creations (FC)</h1>
    <p class="muted">Ubicaciones vía <code>ubicacion.cve_almac → almacen.cve_almac → almacenp.clave</code>. En “Ubicaciones” verás <strong># Ubicaciones (AP)</strong> por almacén principal.</p>

    <div class="grid">
      <div class="card">
        <div>Productos <a class="link" href="?<?=http_build_query(array_merge($_GET,['show'=>'productos','pageP'=>1]))?>#productos">Ver productos</a></div>
        <div class="kpi"><?= number_format($tot_prod) ?></div>
      </div>
      <div class="card">
        <div>Almacenes <a class="link" href="?<?=http_build_query(array_merge($_GET,['show'=>'almacenes','pageA'=>1]))?>#almacenes">Ver almacenes</a></div>
        <div class="kpi"><?= number_format($tot_alm) ?></div>
      </div>
      <div class="card">
        <div>Usuarios <a class="link" href="?<?=http_build_query(array_merge($_GET,['show'=>'usuarios','pageU'=>1]))?>#usuarios">Ver usuarios</a></div>
        <div class="kpi"><?= number_format($tot_usr) ?></div>
      </div>
      <div class="card">
        <div>Ubicaciones <a class="link" href="?<?=http_build_query(array_merge($_GET,['show'=>'ubicaciones','pageUb'=>1]))?>#ubicaciones">Ver ubicaciones</a></div>
        <div class="kpi"><?= number_format($tot_ubi) ?></div>
      </div>
    </div>

    <!-- Totales por compañía -->
    <div class="block">
      <div class="caps">Totales por compañía</div>
      <table>
        <thead><tr><th>Compañía (desc_cia)</th><th>Productos</th><th>Almacenes</th><th>Usuarios</th></tr></thead>
        <tbody>
        <?php foreach ($por_empresa as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['desc_cia']) ?></td>
            <td><?= number_format((int)$r['productos']) ?></td>
            <td><?= number_format((int)$r['almacenes']) ?></td>
            <td><?= number_format((int)$r['usuarios']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- ===== Grilla: Almacenes (principal) ===== -->
    <div id="almacenes" class="block" style="<?= $show==='almacenes' ? '' : 'display:none' ?>;">
      <div class="row">
        <div><strong>Almacenes</strong>
          <span class="pill"><?= number_format($totalA) ?> totales</span>
          <span class="pill">pág <?= $pageA ?> · <?= $perPage ?>/pág</span>
        </div>
      </div>
      <form class="row" method="get" action="#almacenes">
        <input type="hidden" name="show" value="almacenes">
        <input type="text" name="q" placeholder="Buscar (clave / nombre / desc_cia / empresa)" value="<?= htmlspecialchars($q) ?>">
        <select name="almacen">
          <option value="">— Filtro por almacén —</option>
          <?php foreach($opts as $o){ $sel = $almIn===$o['clave'] ? 'selected' : ''; ?>
            <option value="<?=htmlspecialchars($o['clave'])?>" <?=$sel?>><?=htmlspecialchars($o['clave']." — ".$o['nombre'])?></option>
          <?php } ?>
        </select>
        <button>Aplicar</button>
        <button type="button" onclick="document.getElementById('almacenes').style.display='none'">Ocultar</button>
      </form>

      <div class="scroll">
        <table>
          <thead>
            <tr>
              <th>Compañía</th>
              <th>Empresa</th>
              <th>Almacén (cve_almacenp)</th>
              <th>Nombre</th>
              <th># Ubicaciones</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($almacenes as $r): 
            $verUbUrl = '?'.http_build_query(array_merge($_GET,[
              'show'   => 'ubicaciones',
              'almacen'=> $r['cve_almacenp'],
              'pageUb' => 1,
              'pageA'  => $pageA
            ])).'#ubicaciones';
          ?>
            <tr>
              <td><?= htmlspecialchars($r['desc_cia'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['empresa_id']) ?></td>
              <td><?= htmlspecialchars($r['cve_almacenp']) ?></td>
              <td><?= htmlspecialchars($r['nombre_almacenp']) ?></td>
              <td style="text-align:right"><?= number_format((int)$r['ubicaciones']) ?></td>
              <td><a class="btn-link" href="<?=$verUbUrl?>">Ver ubicaciones</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="pager">
        <?php if ($pageA>1): ?>
          <a class="btn-link" href="?<?=http_build_query(array_merge($_GET,['show'=>'almacenes','pageA'=>$pageA-1]))?>#almacenes">« Anterior</a>
        <?php endif; ?>
        <?php if ($hasMoreA): ?>
          <a class="btn-link" href="?<?=http_build_query(array_merge($_GET,['show'=>'almacenes','pageA'=>$pageA+1]))?>#almacenes">Siguiente »</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- ===== Grilla: Productos ===== -->
    <div id="productos" class="block" style="<?= $show==='productos' ? '' : 'display:none' ?>;">
      <div class="row">
        <div><strong>Productos</strong>
          <span class="pill"><?= number_format($totalP) ?> totales</span>
          <span class="pill">pág <?= $pageP ?> · <?= $perPage ?>/pág</span>
        </div>
      </div>
      <form class="row" method="get" action="#productos">
        <input type="hidden" name="show" value="productos">
        <input type="text" name="q" placeholder="Buscar (cve_articulo / des_articulo / desc_cia / empresa)" value="<?= htmlspecialchars($q) ?>">
        <select name="almacen">
          <option value="">— Filtro por almacén —</option>
          <?php foreach($opts as $o){ $sel = $almIn===$o['clave'] ? 'selected' : ''; ?>
            <option value="<?=htmlspecialchars($o['clave'])?>" <?=$sel?>><?=htmlspecialchars($o['clave']." — ".$o['nombre'])?></option>
          <?php } ?>
        </select>
        <button>Aplicar</button>
        <button type="button" onclick="document.getElementById('productos').style.display='none'">Ocultar</button>
      </form>

      <div class="scroll">
        <table>
          <thead><tr><th>Compañía</th><th>Empresa</th><th>Artículo</th><th>Descripción</th></tr></thead>
          <tbody>
          <?php foreach ($productos as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['desc_cia'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['empresa_id']) ?></td>
              <td><?= htmlspecialchars($r['cve_articulo']) ?></td>
              <td><?= htmlspecialchars($r['des_articulo']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="pager">
        <?php if ($pageP>1): ?>
          <a class="btn-link" href="?<?=http_build_query(array_merge($_GET,['show'=>'productos','pageP'=>$pageP-1]))?>#productos">« Anterior</a>
        <?php endif; ?>
        <?php if ($hasMoreP): ?>
          <a class="btn-link" href="?<?=http_build_query(array_merge($_GET,['show'=>'productos','pageP'=>$pageP+1]))?>#productos">Siguiente »</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- ===== Grilla: Usuarios ===== -->
    <div id="usuarios" class="block" style="<?= $show==='usuarios' ? '' : 'display:none' ?>;">
      <div class="row">
        <div><strong>Usuarios</strong>
          <span class="pill"><?= number_format($totalU) ?> totales</span>
          <span class="pill">pág <?= $pageU ?> · <?= $perPage ?>/pág</span>
        </div>
      </div>
      <form class="row" method="get" action="#usuarios">
        <input type="hidden" name="show" value="usuarios">
        <input type="text" name="q" placeholder="Buscar (usuario / nombre / email / rol / desc_cia / empresa)" value="<?= htmlspecialchars($q) ?>">
        <select name="almacen">
          <option value="">— Filtro por almacén —</option>
          <?php foreach($opts as $o){ $sel = $almIn===$o['clave'] ? 'selected' : ''; ?>
            <option value="<?=htmlspecialchars($o['clave'])?>" <?=$sel?>><?=htmlspecialchars($o['clave']." — ".$o['nombre'])?></option>
          <?php } ?>
        </select>
        <button>Aplicar</button>
        <button type="button" onclick="document.getElementById('usuarios').style.display='none'">Ocultar</button>
      </form>

      <div class="scroll">
        <table>
          <thead><tr><th>Compañía</th><th>Empresa</th><th>Usuario</th><th>Nombre</th><th>Email</th><th>Perfiles (rol)</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach ($usuarios as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['desc_cia'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['empresa_id']) ?></td>
              <td><?= htmlspecialchars($r['cve_usuario']) ?></td>
              <td><?= htmlspecialchars($r['nombre_completo']) ?></td>
              <td><?= htmlspecialchars($r['email']) ?></td>
              <td><?= htmlspecialchars($r['perfiles'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['status']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="pager">
        <?php if ($pageU>1): ?>
          <a class="btn-link" href="?<?=http_build_query(array_merge($_GET,['show'=>'usuarios','pageU'=>$pageU-1]))?>#usuarios">« Anterior</a>
        <?php endif; ?>
        <?php if ($hasMoreU): ?>
          <a class="btn-link" href="?<?=http_build_query(array_merge($_GET,['show'=>'usuarios','pageU'=>$pageU+1]))?>#usuarios">Siguiente »</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- ===== Grilla: Ubicaciones ===== -->
    <div id="ubicaciones" class="block" style="<?= $show==='ubicaciones' ? '' : 'display:none' ?>;">
      <div class="row">
        <div><strong>Ubicaciones</strong>
          <span class="pill"><?= number_format($totalUb) ?> totales<?= $almIn ? " (AP {$almIn})" : "" ?></span>
          <?php if ($almIn !== ''):
            $totAp = (int) db_val("
              SELECT COALESCE(uap.num_ub_ap,0) FROM (
                SELECT a.empresa_id, a.cve_almacenp AS clave_ap, COUNT(ub.CodigoCSD) AS num_ub_ap
                FROM c_ubicacion AS ub
                INNER JOIN c_almacen AS a ON a.empresa_id = ub.empresa_id AND a.cve_almac = ub.cve_almac
                GROUP BY a.empresa_id, a.cve_almacenp
              ) uap
              INNER JOIN c_almacenp ap ON ap.empresa_id = :e AND ap.clave = :c
              WHERE uap.empresa_id = ap.empresa_id AND uap.clave_ap = ap.clave
            ", ['e'=>$empresaDeAlm ?? 0, 'c'=>$almIn]);
          ?>
            <span class="pill"># Ubicaciones (AP <?= htmlspecialchars($almIn) ?>): <?= number_format($totAp) ?></span>
          <?php endif; ?>
          <span class="pill">pág <?= $pageUb ?> · <?= $perPage ?>/pág</span>
        </div>
      </div>
      <form class="row" method="get" action="#ubicaciones">
        <input type="hidden" name="show" value="ubicaciones">
        <input type="text" name="q" placeholder="Buscar (AP clave/nombre · des_almac · CodigoCSD · desc_cia)" value="<?= htmlspecialchars($q) ?>">
        <select name="almacen">
          <option value="">— Filtro por almacén —</option>
          <?php foreach($opts as $o){ $sel = $almIn===$o['clave'] ? 'selected' : ''; ?>
            <option value="<?=htmlspecialchars($o['clave'])?>" <?=$sel?>><?=htmlspecialchars($o['clave']." — ".$o['nombre'])?></option>
          <?php } ?>
        </select>
        <button>Aplicar</button>
        <button type="button" onclick="document.getElementById('ubicaciones').style.display='none'">Ocultar</button>
      </form>

      <div class="scroll">
        <table>
          <thead>
            <tr>
              <th>Compañía</th>
              <th>Empresa</th>
              <th>Almacén Principal (cve_almacenp)</th>
              <th>Nombre Almacén Principal</th>
              <th>Zona/SubAlmacén (des_almac)</th>
              <th>Código Ubicación (CodigoCSD)</th>
              <th># Ubicaciones (AP)</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($ubicaciones as $r): 
            $selUrl = '?'.http_build_query(array_merge($_GET,[
              'almacen'=>$r['cve_almacenp'],
              'show'=>'almacenes',
              'pageA'=>1,
              'pageUb'=>$pageUb
            ])).'#almacenes';
          ?>
            <tr>
              <td><?= htmlspecialchars($r['desc_cia'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['empresa_id']) ?></td>
              <td><?= htmlspecialchars($r['cve_almacenp'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['nom_almacenp'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['des_subalmac'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['codigo_ubic'] ?? '') ?></td>
              <td style="text-align:right"><?= number_format((int)$r['num_ub_ap']) ?></td>
              <td><a class="btn-link" href="<?=$selUrl?>">Usar este almacén</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="pager">
        <?php if ($pageUb>1): ?>
          <a class="btn-link" href="?<?=http_build_query(array_merge($_GET,['show'=>'ubicaciones','pageUb'=>$pageUb-1]))?>#ubicaciones">« Anterior</a>
        <?php endif; ?>
        <?php if ($hasMoreUb): ?>
          <a class="btn-link" href="?<?=http_build_query(array_merge($_GET,['show'=>'ubicaciones','pageUb'=>$pageUb+1]))?>#ubicaciones">Siguiente »</a>
        <?php endif; ?>
      </div>
    </div>

  </div>
</body>
</html>
