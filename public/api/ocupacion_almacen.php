<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// Recomendado para que NO rompa JSON con warnings/notices
ini_set('display_errors', '0');
error_reporting(E_ALL);

/**
 * Carga db.php
 * Debe existir db_pdo()
 */
$dbCandidates = [
  __DIR__ . '/../../app/db.php',
  __DIR__ . '/../app/db.php',
  __DIR__ . '/../../../app/db.php',
];

$dbLoaded = false;
foreach ($dbCandidates as $p) {
  if (file_exists($p)) {
    require_once $p;
    $dbLoaded = true;
    break;
  }
}

if (!$dbLoaded || !function_exists('db_pdo')) {
  echo json_encode(['success' => false, 'message' => 'No se encontró db.php o db_pdo()']);
  exit;
}

$pdo = db_pdo();
if (!($pdo instanceof PDO)) {
  echo json_encode(['success' => false, 'message' => 'PDO no disponible']);
  exit;
}

try {
  $cve_almacenp = isset($_GET['cve_almacenp']) ? (int)$_GET['cve_almacenp'] : 0;
  if ($cve_almacenp <= 0) {
    echo json_encode(['success' => false, 'message' => 'Falta o inválido cve_almacenp']);
    exit;
  }

  // 1) Zonas hijas del almacén padre
  $st = $pdo->prepare("
    SELECT cve_almac
    FROM c_almacen
    WHERE cve_almacenp = :cve_almacenp
      AND IFNULL(Activo,1) = 1
  ");
  $st->execute([':cve_almacenp' => $cve_almacenp]);
  $zonas = $st->fetchAll(PDO::FETCH_COLUMN, 0);

  if (!$zonas) {
    echo json_encode([
      'success' => true,
      'data' => [
        'cve_almacenp' => $cve_almacenp,
        'zonas' => [],
        'rack' => ['total' => 0, 'ocupadas' => 0, 'libres' => 0, 'ocupacion_pct' => 0],
        'piso' => ['pallets' => 0],
        'contenedores_ref' => ['rack' => 0, 'piso' => 0],
      ]
    ]);
    exit;
  }

  // 2) IN dinámico SOLO con parámetros usados
  $ph = [];
  $paramsIn = [];
  foreach ($zonas as $i => $id) {
    $k = ":a{$i}";
    $ph[] = $k;
    $paramsIn[$k] = (int)$id;
  }
  $in = implode(',', $ph);

  // 3) KPI Rack Total (slots físicos)
  $st = $pdo->prepare("
    SELECT COUNT(*) AS racks_total
    FROM c_ubicacion u
    WHERE u.cve_almac IN ($in)
      AND u.cve_nivel > 1
  ");
  $st->execute($paramsIn);
  $rack_total = (int)$st->fetchColumn();

  // 4) KPI Rack Ocupadas por pallets (>=1 pallet por ubicación)
  $st = $pdo->prepare("
    SELECT COUNT(DISTINCT u.idy_ubica) AS racks_ocupadas
    FROM v_inv_existencia_multinivel v
    JOIN c_charolas ch ON ch.IDContenedor = v.nTarima
    JOIN c_ubicacion u ON u.idy_ubica = v.idy_ubica
    WHERE v.cve_almac IN ($in)
      AND u.cve_nivel > 1
      AND LOWER(TRIM(ch.tipo)) = 'pallet'
  ");
  $st->execute($paramsIn);
  $rack_ocupadas = (int)$st->fetchColumn();

  // 5) KPI Piso: SOLO conteo de pallets (sin límite)
  $st = $pdo->prepare("
    SELECT COUNT(DISTINCT ch.IDContenedor) AS pallets_piso
    FROM v_inv_existencia_multinivel v
    JOIN c_charolas ch ON ch.IDContenedor = v.nTarima
    JOIN c_ubicacion u ON u.idy_ubica = v.idy_ubica
    WHERE v.cve_almac IN ($in)
      AND u.cve_nivel = 0
      AND LOWER(TRIM(ch.tipo)) = 'pallet'
  ");
  $st->execute($paramsIn);
  $pallets_piso = (int)$st->fetchColumn();

  // 6) Contenedores (solo referencia)
  $st = $pdo->prepare("
    SELECT
      SUM(CASE WHEN u.cve_nivel > 1 THEN 1 ELSE 0 END) AS cont_rack,
      SUM(CASE WHEN u.cve_nivel = 0 THEN 1 ELSE 0 END) AS cont_piso
    FROM v_inv_existencia_multinivel v
    JOIN c_charolas ch ON ch.IDContenedor = v.nTarima
    JOIN c_ubicacion u ON u.idy_ubica = v.idy_ubica
    WHERE v.cve_almac IN ($in)
      AND LOWER(TRIM(ch.tipo)) = 'contenedor'
  ");
  $st->execute($paramsIn);
  $rowCont = $st->fetch(PDO::FETCH_ASSOC) ?: [];
  $cont_rack = (int)($rowCont['cont_rack'] ?? 0);
  $cont_piso = (int)($rowCont['cont_piso'] ?? 0);

  $rack_libres = max(0, $rack_total - $rack_ocupadas);
  $rack_pct = ($rack_total > 0) ? round(($rack_ocupadas / $rack_total) * 100, 2) : 0.00;

  echo json_encode([
    'success' => true,
    'data' => [
      'cve_almacenp' => $cve_almacenp,
      'zonas' => array_map('intval', $zonas),
      'rack' => [
        'total' => $rack_total,
        'ocupadas' => $rack_ocupadas,
        'libres' => $rack_libres,
        'ocupacion_pct' => $rack_pct
      ],
      'piso' => [
        'pallets' => $pallets_piso
      ],
      'contenedores_ref' => [
        'rack' => $cont_rack,
        'piso' => $cont_piso
      ]
    ]
  ]);
  exit;

} catch (Throwable $e) {
  // Ojo: NO imprimimos warnings HTML; devolvemos JSON puro
  echo json_encode([
    'success' => false,
    'message' => 'Error KPI ocupación',
    'error' => $e->getMessage()
  ]);
  exit;
}
