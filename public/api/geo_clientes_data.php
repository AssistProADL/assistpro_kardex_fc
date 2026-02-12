<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

try {

  $pdo = db_pdo();

  $idEmpresa = $_REQUEST['IdEmpresa'] ?? null;
  $idAlmacen = $_REQUEST['IdAlmacen'] ?? null;
  $rutaId    = (int)($_REQUEST['ruta_id'] ?? 0);

  $where = [];
  $params = [];

  /*
  |--------------------------------------------------------------------------
  | JOIN JERÁRQUICO CORREGIDO
  |--------------------------------------------------------------------------
  */

  $sqlFrom = "
    FROM relclirutas rc
    INNER JOIN t_ruta r          ON r.ID_Ruta = rc.IdRuta
    INNER JOIN c_almacenp a      ON a.id = r.cve_almacenp
    INNER JOIN c_compania emp    ON emp.cve_cia = a.cve_cia
    INNER JOIN c_destinatarios d ON (
         (rc.IdCliente REGEXP '^[0-9]+$' AND d.id_destinatario = CAST(rc.IdCliente AS UNSIGNED))
      OR (rc.IdCliente LIKE 'DEST\\_%' AND d.id_destinatario = CAST(SUBSTRING(rc.IdCliente,6) AS UNSIGNED))
      OR (rc.IdCliente NOT REGEXP '^[0-9]+$' AND rc.IdCliente NOT LIKE 'DEST\\_%' AND d.Cve_Clte = rc.IdCliente)
    )
    LEFT JOIN c_cliente c ON c.Cve_Clte = d.Cve_Clte
  ";

  /*
  |--------------------------------------------------------------------------
  | FILTROS
  |--------------------------------------------------------------------------
  */

  if (!empty($idEmpresa)) {
    $where[] = "emp.cve_cia = :empresa";
    $params[':empresa'] = $idEmpresa;
  }

  if (!empty($idAlmacen)) {
    $where[] = "a.id = :almacen";
    $params[':almacen'] = $idAlmacen;
  }

  if ($rutaId > 0) {
    $where[] = "r.ID_Ruta = :ruta";
    $params[':ruta'] = $rutaId;
  }

  $where[] = "d.latitud IS NOT NULL AND d.longitud IS NOT NULL";

  $whereSql = count($where) ? " WHERE " . implode(" AND ", $where) : "";

  /*
  |--------------------------------------------------------------------------
  | DATA SIN DUPLICADOS (AGRUPACIÓN LÓGICA)
  |--------------------------------------------------------------------------
  */

  $sqlData = "
    SELECT
      emp.cve_cia            AS empresa_id,
      emp.clave_empresa      AS empresa_clave,
      emp.des_cia            AS empresa_nombre,

      a.id                   AS almacen_id,
      a.clave                AS almacen_clave,
      a.nombre               AS almacen_nombre,

      r.ID_Ruta              AS ruta_id,
      r.descripcion          AS ruta_nombre,

      d.id_destinatario,
      d.razonsocial          AS destinatario,
      d.latitud,
      d.longitud,

      COALESCE(c.RazonComercial, c.RazonSocial) AS cliente_nombre,
      COALESCE(c.saldo_actual,0) AS saldo_actual

    $sqlFrom
    $whereSql

    GROUP BY
      emp.cve_cia,
      a.id,
      r.ID_Ruta,
      d.id_destinatario

    ORDER BY
      a.id,
      r.ID_Ruta,
      d.razonsocial
  ";

  $st = $pdo->prepare($sqlData);
  $st->execute($params);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  /*
  |--------------------------------------------------------------------------
  | META CONSISTENTE
  |--------------------------------------------------------------------------
  */

  $sqlMeta = "
    SELECT
      COUNT(DISTINCT d.id_destinatario) AS total,
      SUM(DISTINCT COALESCE(c.saldo_actual,0)) AS saldo_total
    $sqlFrom
    $whereSql
  ";

  $stMeta = $pdo->prepare($sqlMeta);
  $stMeta->execute($params);
  $meta = $stMeta->fetch(PDO::FETCH_ASSOC);

  echo json_encode([
    'ok' => true,
    'meta' => [
      'total' => (int)($meta['total'] ?? 0),
      'saldo_total' => (float)($meta['saldo_total'] ?? 0)
    ],
    'data' => $rows
  ]);

} catch (Throwable $e) {

  echo json_encode([
    'ok' => false,
    'error' => $e->getMessage()
  ]);
}
