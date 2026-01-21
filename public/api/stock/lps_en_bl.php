<?php
// public/api/stock/lps_en_bl.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../app/db.php';

function respond($payload, int $code = 200): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $pdo = db();

  $bl   = trim((string)($_GET['CodigoCSD'] ?? $_GET['bl'] ?? ''));
  $tipo = strtoupper(trim((string)($_GET['tipo'] ?? ''))); // PALET | CONTENEDOR | ''
  $cve_al = isset($_GET['cve_almac']) ? (int)$_GET['cve_almac'] : 0;

  if ($bl === '') {
    respond(['ok'=>0,'error'=>'CodigoCSD (bl) es obligatorio'], 400);
  }

  // 1) Lookup de ubicación (para idy_ubica y cve_almac real del BL)
  $st = $pdo->prepare("
    SELECT CodigoCSD, idy_ubica, cve_almac, Activo, AcomodoMixto
    FROM c_ubicacion
    WHERE CodigoCSD = :bl
    LIMIT 1
  ");
  $st->execute([':bl'=>$bl]);
  $ub = $st->fetch(PDO::FETCH_ASSOC);

  if (!$ub) {
    respond(['ok'=>0,'error'=>'BL (CodigoCSD) no existe'], 404);
  }

  $idy_ubica = (int)$ub['idy_ubica'];
  $cve_ub_al = (int)$ub['cve_almac'];

  // Si no mandan almacén, usamos el del BL
  if ($cve_al <= 0) $cve_al = $cve_ub_al;

  // 2) Regla corporativa: BL debe ser del mismo almacén solicitado
  if ($cve_al !== $cve_ub_al) {
    respond([
      'ok'=>0,
      'error'=>'BL pertenece a otro almacén',
      'bl'=>$bl,
      'cve_almac_req'=>$cve_al,
      'cve_almac_bl'=>$cve_ub_al,
      'ubicacion'=>[
        'idy_ubica'=>$idy_ubica,
        'Activo'=>(int)$ub['Activo'],
        'AcomodoMixto'=>(string)$ub['AcomodoMixto'],
      ]
    ], 409);
  }

  // 3) Consultas por tipo (tarimas / contenedores)
  $items = [];

  // ---- PALET (ts_existenciatarima) ----
  if ($tipo === '' || $tipo === 'PALET' || $tipo === 'PALLET') {
    $st = $pdo->prepare("
      SELECT
        ch.IDContenedor,
        ch.CveLP,
        'PALET' AS tipo,
        SUM(COALESCE(et.existencia,0)) AS total
      FROM ts_existenciatarima et
      INNER JOIN c_charolas ch
        ON ch.IDContenedor = et.ntarima
      WHERE et.cve_almac = :cve_almac
        AND et.idy_ubica  = :idy_ubica
        AND COALESCE(et.existencia,0) > 0
        AND (ch.Activo = 1 OR ch.Activo = '1' OR ch.Activo = 'S')
      GROUP BY ch.IDContenedor, ch.CveLP
      ORDER BY ch.CveLP
      LIMIT 500
    ");
    $st->execute([
      ':cve_almac' => $cve_al,
      ':idy_ubica' => $idy_ubica
    ]);
    $items = array_merge($items, $st->fetchAll(PDO::FETCH_ASSOC));
  }

  // ---- CONTENEDOR (ts_existenciacajas) ----
  if ($tipo === '' || $tipo === 'CONTENEDOR') {
    $st = $pdo->prepare("
      SELECT
        ch.IDContenedor,
        ch.CveLP,
        'CONTENEDOR' AS tipo,
        SUM(COALESCE(ec.PiezasXCaja,0)) AS total
      FROM ts_existenciacajas ec
      INNER JOIN c_charolas ch
        ON ch.IDContenedor = ec.Id_Caja
      WHERE ec.Cve_Almac = :cve_almac
        AND ec.idy_ubica = :idy_ubica
        AND COALESCE(ec.PiezasXCaja,0) > 0
        AND (ch.Activo = 1 OR ch.Activo = '1' OR ch.Activo = 'S')
      GROUP BY ch.IDContenedor, ch.CveLP
      ORDER BY ch.CveLP
      LIMIT 500
    ");
    $st->execute([
      ':cve_almac' => $cve_al,
      ':idy_ubica' => $idy_ubica
    ]);
    $items = array_merge($items, $st->fetchAll(PDO::FETCH_ASSOC));
  }

  // Normaliza totales
  foreach ($items as &$it) {
    $it['IDContenedor'] = (int)$it['IDContenedor'];
    $it['CveLP'] = (string)$it['CveLP'];
    $it['tipo'] = (string)$it['tipo'];
    $it['total'] = (float)$it['total'];
  }
  unset($it);

  respond([
    'ok' => 1,
    'bl' => $bl,
    'cve_almac' => $cve_al,
    'ubicacion' => [
      'idy_ubica' => $idy_ubica,
      'Activo' => (int)$ub['Activo'],
      'AcomodoMixto' => (string)$ub['AcomodoMixto'],
    ],
    'tipo' => $tipo,
    'count' => count($items),
    'items' => $items
  ]);

} catch (Throwable $e) {
  respond(['ok'=>0,'error'=>$e->getMessage()], 500);
}
