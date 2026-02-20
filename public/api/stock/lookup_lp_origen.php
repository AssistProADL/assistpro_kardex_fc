<?php
// public/api/stock/lookup_lp_origen.php
// Devuelve: LP maestro + BL actual (si es único) + detalle SKU/Lote/Cantidad (18,4)

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

  $cvelp = trim((string) ($_GET['CveLP'] ?? $_GET['cvelp'] ?? ''));
  if ($cvelp === '')
    respond(['ok' => 0, 'error' => 'Falta parámetro CveLP.'], 400);

  // Maestro LP
  $st = $pdo->prepare("SELECT IDContenedor, cve_almac, tipo, Activo, Clave_Contenedor, CveLP FROM c_charolas WHERE CveLP = :lp LIMIT 1");
  $st->bindValue(':lp', $cvelp);
  $st->execute();
  $lp = $st->fetch(PDO::FETCH_ASSOC);
  if (!$lp)
    respond(['ok' => 0, 'error' => 'CveLP no existe en c_charolas.'], 404);

  $idCont = (int) $lp['IDContenedor'];

  // Detalle: Consultar directamente las tablas (no vista) para obtener BL actual
  $sql = "
    SELECT 
      u.CodigoCSD AS bl,
      et.cve_articulo,
      et.lote AS cve_lote,
      et.existencia AS cantidad
    FROM ts_existenciatarima et
    LEFT JOIN c_ubicacion u ON u.idy_ubica = et.idy_ubica
    WHERE et.ntarima = :idCont 
      AND et.existencia > 0
    ORDER BY u.CodigoCSD, et.cve_articulo, et.lote
  ";
  $st = $pdo->prepare($sql);
  $st->bindValue(':idCont', $idCont, PDO::PARAM_INT);
  $st->execute();
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  $strict = !isset($_GET['strict']) || $_GET['strict'] !== '0';

  if (!$rows && $strict) {
    // SMART ERROR: Investigar por qué no tiene existencia
    // 1. Verificar si fue fusionado o movido recientemente
    $sqlMov = "SELECT Destino, Fecha, TipoMovimiento FROM v_t_movcharolas WHERE ID_Contenedor = :id ORDER BY id DESC LIMIT 1";
    // Nota: v_t_movcharolas suele ser un view con joins. Si no existe, usamos tabla directa.
    // Fallback tabla directa por seguridad
    $sqlMov = "
        SELECT mc.Destino, mc.Fecha 
        FROM t_movcharolas mc 
        WHERE mc.ID_Contenedor = :id 
        ORDER BY mc.id DESC LIMIT 1
      ";
    $lastMov = db_row($sqlMov, [':id' => $idCont]);

    $msg = 'LP sin existencia positiva.';

    if ($lastMov) {
      $destino = $lastMov['Destino'] ?? '?';
      $fecha = $lastMov['Fecha'] ?? '';
      $msg = "El LP está vacío. Fue procesado/movido a Destino: [{$destino}] el {$fecha}.";
    }

    respond(['ok' => 0, 'error' => $msg], 400);
  }

  $bls = [];
  $total = 0.0;
  foreach ($rows as $r) {
    if ($r['bl']) {  // Solo contar BLs no nulos
      $bls[$r['bl']] = 1;
    }
    $total += (float) $r['cantidad'];
  }
  $bl_list = array_keys($bls);
  // Si hay múltiples ubicaciones, las mostramos todas separadas por coma
  // De esta forma el usuario ve dónde está el material, aunque esté dividido
  $bl_actual = (count($bl_list) > 0) ? implode(', ', $bl_list) : null;

  respond([
    'ok' => 1,
    'service' => 'lookup_lp_origen',
    'lp' => $lp,
    'bl_actual' => $bl_actual,
    'bls' => $bl_list,
    'total' => $total,
    'rows' => $rows
  ]);

} catch (Throwable $e) {
  respond(['ok' => 0, 'error' => $e->getMessage() . ' [lookup_lp_origen]'], 500);
}
