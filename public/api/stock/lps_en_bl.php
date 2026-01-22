<?php
// public/api/stock/lps_en_bl.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../app/db.php';

function respond($payload, int $code = 200): void
{
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $pdo = db();

  $bl = trim((string) ($_GET['CodigoCSD'] ?? $_GET['bl'] ?? ''));
  $tipo = strtoupper(trim((string) ($_GET['tipo'] ?? ''))); // PALET | CONTENEDOR | ''
  $cve_al = isset($_GET['cve_almac']) ? (int) $_GET['cve_almac'] : 0;

  if ($bl === '') {
    respond(['ok' => 0, 'error' => 'CodigoCSD (bl) es obligatorio'], 400);
  }

  // 1) Lookup de ubicación (para idy_ubica y cve_almac real del BL)
  $st = $pdo->prepare("
    SELECT CodigoCSD, idy_ubica, cve_almac, Activo, AcomodoMixto
    FROM c_ubicacion
    WHERE CodigoCSD = :bl
    LIMIT 1
  ");
  $st->bindValue(':bl', $bl);
  $st->execute();
  $ub = $st->fetch(PDO::FETCH_ASSOC);

  if (!$ub) {
    // FALLBACK: ¿Es un LP escaneado en lugar de un BL?
    // 1. Buscar si existe como LP
    $stLP = $pdo->prepare("SELECT IDContenedor, CveLP FROM c_charolas WHERE CveLP = :code LIMIT 1");
    $stLP->bindValue(':code', $bl);
    $stLP->execute();
    $lpRow = $stLP->fetch(PDO::FETCH_ASSOC);

    if ($lpRow) {
      // 2. Es un LP. Buscar su ubicación actual (donde tenga existencia positiva)
      // Usamos la misma lógica que lookup_lp_origen
      $stLoc = $pdo->prepare("
        SELECT bl 
        FROM v_inv_existencia_multinivel 
        WHERE nTarima = :idCont AND cantidad > 0 
        GROUP BY bl 
        ORDER BY SUM(cantidad) DESC 
        LIMIT 1
      ");
      $stLoc->bindValue(':idCont', $lpRow['IDContenedor']);
      $stLoc->execute();
      $locVal = $stLoc->fetchColumn();

      if ($locVal) {
        // 3. Encontramos la ubicación del LP. Usamos ESA ubicación como si fuera la escaneada.
        $bl = $locVal;

        // Re-consultamos la info de ubicación
        $st->bindValue(':bl', $bl);
        $st->execute();
        $ub = $st->fetch(PDO::FETCH_ASSOC);
      }
    }
  }

  if (!$ub) {
    respond(['ok' => 0, 'error' => 'BL (CodigoCSD) no existe', 'scanned' => $_GET['bl'] ?? ''], 404);
  }

  $idy_ubica = (int) $ub['idy_ubica'];
  $cve_ub_al = (int) $ub['cve_almac'];

  // Si no mandan almacén, usamos el del BL
  if ($cve_al <= 0)
    $cve_al = $cve_ub_al;

  // 2) Regla corporativa: BL debe ser del mismo almacén solicitado
  if ($cve_al !== $cve_ub_al) {
    respond([
      'ok' => 0,
      'error' => 'BL pertenece a otro almacén',
      'bl' => $bl,
      'cve_almac_req' => $cve_al,
      'cve_almac_bl' => $cve_ub_al,
      'ubicacion' => [
        'idy_ubica' => $idy_ubica,
        'Activo' => (int) $ub['Activo'],
        'AcomodoMixto' => (string) $ub['AcomodoMixto'],
      ]
    ], 409);
  }

  // 3) Consultas por tipo (tarimas / contenedores)
  $items = [];

  // ---- PALET (ts_existenciatarima) ----
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
      WHERE et.idy_ubica  = :idy_ubica
        AND COALESCE(et.existencia,0) > 0
        AND (ch.Activo = 1 OR ch.Activo = '1' OR ch.Activo = 'S')
      GROUP BY ch.IDContenedor, ch.CveLP
      ORDER BY ch.CveLP
      LIMIT 500
    ");
    $st->bindValue(':idy_ubica', $idy_ubica, PDO::PARAM_INT);
    $st->execute();
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
      WHERE ec.idy_ubica = :idy_ubica
        AND COALESCE(ec.PiezasXCaja,0) > 0
        AND (ch.Activo = 1 OR ch.Activo = '1' OR ch.Activo = 'S')
      GROUP BY ch.IDContenedor, ch.CveLP
      ORDER BY ch.CveLP
      LIMIT 500
    ");
    $st->bindValue(':idy_ubica', $idy_ubica, PDO::PARAM_INT);
    $st->execute();
    $items = array_merge($items, $st->fetchAll(PDO::FETCH_ASSOC));
  }

  // Normaliza totales
  foreach ($items as &$it) {
    $it['IDContenedor'] = (int) $it['IDContenedor'];
    $it['CveLP'] = (string) $it['CveLP'];
    $it['tipo'] = (string) $it['tipo'];
    $it['total'] = (float) $it['total'];
  }
  unset($it);

  // 4) Si se 'escaneó' un LP (fallback) y no está en la lista (por filtros de stock > 0 u otros),
  //    lo agregamos manualmente para que el usuario pueda seleccionarlo (ej. para fusionar en él).
  if (isset($lpRow) && $lpRow) {
    $found = false;
    foreach ($items as $it) {
      if ($it['IDContenedor'] == $lpRow['IDContenedor']) {
        $found = true;
        break;
      }
    }
    if (!$found) {
      // Obtenemos datos básicos del LP para agregarlo
      // Si ya tenemos IDContenedor y CveLP en $lpRow, solo falta 'tipo' y 'total' (que sería 0 o lo que tenga)
      $stAdd = $pdo->prepare("SELECT IDContenedor, CveLP, tipo FROM c_charolas WHERE IDContenedor = :id");
      $stAdd->execute([':id' => $lpRow['IDContenedor']]);
      $add = $stAdd->fetch(PDO::FETCH_ASSOC);
      if ($add) {
        $add['tipo'] = strtoupper($add['tipo'] ?? 'LP');
        $add['total'] = 0; // Si no salió en query principal, asumimos 0 o no importa para el selector
        $items[] = $add;
      }
    }
  }

  respond([
    'ok' => 1,
    'bl' => $bl,
    'cve_almac' => $cve_al,
    'ubicacion' => [
      'idy_ubica' => $idy_ubica,
      'Activo' => (int) $ub['Activo'],
      'AcomodoMixto' => (string) $ub['AcomodoMixto'],
    ],
    'tipo' => $tipo,
    'count' => count($items),
    'items' => $items
  ]);

} catch (Throwable $e) {
  respond(['ok' => 0, 'error' => $e->getMessage() . ' [lps_en_bl]'], 500);
}
