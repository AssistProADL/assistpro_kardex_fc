<?php
// public/api/sfa/planeacion_rutas_data.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../app/db.php';

function out($arr, $code=200){ http_response_code($code); echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }

try{
  $almacenId = isset($_GET['almacen_id']) ? trim($_GET['almacen_id']) : '';
  $rutaId    = isset($_GET['ruta_id']) ? trim($_GET['ruta_id']) : '';
  $q         = isset($_GET['q']) ? trim($_GET['q']) : '';

  if($almacenId==='') out(['ok'=>0,'error'=>'almacen_id requerido'], 400);
  if($rutaId==='') out(['ok'=>0,'error'=>'ruta_id requerido'], 400);

  $cveRuta = db_val("SELECT cve_ruta FROM t_ruta WHERE ID_Ruta = ? OR cve_ruta = ? LIMIT 1", [$rutaId, $rutaId]);
  if(!$cveRuta) out(['ok'=>0,'error'=>'Ruta no encontrada'], 404);

  $params = [$cveRuta, $almacenId];
  $where = " d.Cve_Almac = ? ";

  if($q !== ''){
    $like = '%'.$q.'%';
    $where .= " AND (c.Cve_Clte LIKE ? OR c.RazonComercial LIKE ? OR c.RazonSocial LIKE ? OR d.razonsocial LIKE ? OR d.clave_destinatario LIKE ? OR d.colonia LIKE ? OR d.postal LIKE ?) ";
    array_push($params, $like,$like,$like,$like,$like,$like,$like);
  }

  $sql = "
    SELECT
      d.id_destinatario AS id_destinatario,
      c.Cve_Clte,
      c.RazonComercial,
      c.RazonSocial,
      d.clave_destinatario,
      d.razonsocial AS destinatario,
      d.direccion,
      d.colonia,
      d.postal,
      d.ciudad,
      d.estado,
      IFNULL(rutas.rutas_actuales,'') AS rutas_actuales,

      CASE WHEN rdc.Id IS NULL THEN 0 ELSE 1 END AS asignado_esta_ruta,
      IFNULL(rdc.Lu,0) AS Lu,
      IFNULL(rdc.Ma,0) AS Ma,
      IFNULL(rdc.Mi,0) AS Mi,
      IFNULL(rdc.Ju,0) AS Ju,
      IFNULL(rdc.Vi,0) AS Vi,
      IFNULL(rdc.Sa,0) AS Sa,
      IFNULL(rdc.Do,0) AS Do
    FROM c_destinatarios d
    JOIN c_cliente c ON c.Cve_Clte = d.Cve_Clte

    LEFT JOIN (
      SELECT
        rdc2.Id_Destinatario,
        rdc2.Cve_Cliente,
        GROUP_CONCAT(DISTINCT rdc2.Cve_Ruta ORDER BY rdc2.Cve_Ruta SEPARATOR ', ') AS rutas_actuales
      FROM reldaycli rdc2
      GROUP BY rdc2.Id_Destinatario, rdc2.Cve_Cliente
    ) rutas ON rutas.Id_Destinatario = d.id_destinatario AND rutas.Cve_Cliente = c.Cve_Clte

    LEFT JOIN reldaycli rdc
      ON rdc.Id_Destinatario = d.id_destinatario
     AND rdc.Cve_Cliente     = c.Cve_Clte
     AND rdc.Cve_Ruta        = ?

    WHERE {$where}
    ORDER BY c.Cve_Clte, d.clave_destinatario
    LIMIT 2000
  ";

  $rows = db_all($sql, $params);
  out(['ok'=>1,'data'=>$rows,'debug'=>['almacen_id'=>$almacenId,'ruta_id'=>$rutaId,'cve_ruta'=>$cveRuta]]);
}catch(Throwable $e){
  out(['ok'=>0,'error'=>'Error servidor','detalle'=>$e->getMessage()], 500);
}
