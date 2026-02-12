<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

/*
  geo_clientes_data.php
  Fuente para geo_distribucion_clientes.php

  Ajuste clave:
  - relclirutas.IdCliente en tu BD puede venir como:
      a) id_destinatario numérico (legacy)
      b) clave de cliente (ej. CLI0000000292)
      c) fallback "DEST_123" (cuando no viene clave)
    Por eso, el JOIN contra c_destinatarios debe contemplar los 3 casos.

  Además:
  - Se agrega LEFT JOIN a reldaycli para indicar si el destinatario tiene visitas (Lu..Do > 0).
*/

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
      r.descripcion AS ruta_nombre,
      /* Visitas (derivado de reldaycli) */
      CASE
        WHEN dy.Id IS NULL THEN 0
        WHEN (COALESCE(dy.Lu,0)+COALESCE(dy.Ma,0)+COALESCE(dy.Mi,0)+COALESCE(dy.Ju,0)+COALESCE(dy.Vi,0)+COALESCE(dy.Sa,0)+COALESCE(dy.Do,0)) > 0 THEN 1
        ELSE 0
      END AS tiene_visita
    FROM relclirutas rc

    /* JOIN robusto a destinatarios:
       - Si rc.IdCliente es numérico -> id_destinatario
       - Si rc.IdCliente es 'DEST_123' -> id_destinatario = 123
       - Si rc.IdCliente es clave (CLI..., DEMO...) -> se une por d.Cve_Clte
    */
    INNER JOIN c_destinatarios d ON (
         (rc.IdCliente REGEXP '^[0-9]+$' AND d.id_destinatario = CAST(rc.IdCliente AS UNSIGNED))
      OR (rc.IdCliente LIKE 'DEST\\_%' AND d.id_destinatario = CAST(SUBSTRING(rc.IdCliente,6) AS UNSIGNED))
      OR (rc.IdCliente NOT REGEXP '^[0-9]+$' AND rc.IdCliente NOT LIKE 'DEST\\_%' AND d.Cve_Clte = rc.IdCliente)
    )

    LEFT JOIN c_cliente c ON c.Cve_Clte = d.Cve_Clte
    INNER JOIN t_ruta r ON r.ID_Ruta = rc.IdRuta

    /* reldaycli es por destinatario (Id_Destinatario) */
    LEFT JOIN reldaycli dy
      ON dy.Cve_Almac = rc.IdEmpresa
     AND dy.Cve_Ruta  = CAST(rc.IdRuta AS CHAR)
     AND dy.Id_Destinatario = d.id_destinatario

    WHERE rc.IdEmpresa = :emp
      " . (count($where) ? " AND " . implode(" AND ", $where) : "") . "
    ORDER BY r.cve_ruta, d.razonsocial
  ";

  $st = $pdo->prepare($sql);
  $st->execute($p);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  // Normaliza lat/lng a float
  foreach ($rows as &$x) {
    $x['lat'] = (float)($x['latitud'] ?? 0);
    $x['lng'] = (float)($x['longitud'] ?? 0);
    $x['dias_credito'] = (int)($x['dias_credito'] ?? 0);
    $x['saldo_actual'] = (float)($x['saldo_actual'] ?? 0);
    $x['limite_credito'] = (float)($x['limite_credito'] ?? 0);
    $x['tiene_visita'] = (int)($x['tiene_visita'] ?? 0);
  }
  unset($x);

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
