<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

try {
  $pdo = db_pdo();

  $idEmpresa = trim($_REQUEST['IdEmpresa'] ?? $_REQUEST['idEmpresa'] ?? '');
  $rutaId    = (int)($_REQUEST['ruta_id'] ?? $_REQUEST['RutaId'] ?? 0);

  if ($idEmpresa === '') {
    echo json_encode(['error' => 'Almacén (IdEmpresa) requerido']);
    exit;
  }

  $where = [];
  $p = [':emp' => $idEmpresa];

  // Si rutaId=0 => todas
  if ($rutaId > 0) {
    $where[] = "rc.IdRuta = :ruta";
    $p[':ruta'] = $rutaId;
  }

  // Solo los que tienen GPS (para mapa). Si quieres ver también sin GPS, quita este filtro.
  $where[] = "d.latitud IS NOT NULL AND d.latitud<>'' AND d.longitud IS NOT NULL AND d.longitud<>''";

  $sql = "
    SELECT
      d.id_destinatario,
      d.razonsocial AS destinatario,
      d.direccion,
      d.colonia,
      d.postal,
      d.ciudad,
      d.estado,
      d.telefono,
      d.latitud,
      d.longitud,
      c.Cve_Clte,
      COALESCE(c.RazonComercial, c.RazonSocial) AS cliente_nombre,
      c.dias_credito,
      c.saldo_actual,
      c.limite_credito,
      rc.IdRuta AS ruta_id,
      r.cve_ruta,
      r.descripcion AS ruta_nombre
    FROM relclirutas rc
    INNER JOIN c_destinatarios d ON d.id_destinatario = rc.IdCliente
    LEFT JOIN c_cliente c ON c.Cve_Clte = d.Cve_Clte
    INNER JOIN t_ruta r ON r.ID_Ruta = rc.IdRuta
    WHERE rc.IdEmpresa = :emp
      " . (count($where) ? " AND " . implode(" AND ", $where) : "") . "
    ORDER BY r.cve_ruta, d.razonsocial
  ";

  $st = $pdo->prepare($sql);
  $st->execute($p);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  // Normaliza lat/lng a float
  foreach ($rows as &$x) {
    $x['lat'] = (float)$x['latitud'];
    $x['lng'] = (float)$x['longitud'];
    $x['dias_credito'] = (int)($x['dias_credito'] ?? 0);
    $x['saldo_actual'] = (float)($x['saldo_actual'] ?? 0);
    $x['limite_credito'] = (float)($x['limite_credito'] ?? 0);
  }

  echo json_encode([
    'ok' => true,
    'total' => count($rows),
    'data' => $rows
  ]);

} catch (Throwable $e) {
  echo json_encode([
    'error' => 'Error consultando clientes',
    'detalle' => $e->getMessage()
  ]);
}
