<?php
// public/api/sfa/rutas_planeacion_data.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../app/db.php';

function jexit($arr){ echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }

$raw = file_get_contents('php://input');
if(!$raw) jexit(['ok'=>0,'error'=>'Body JSON requerido']);
$in = json_decode($raw, true);
if(!$in) jexit(['ok'=>0,'error'=>'JSON inválido']);

$almacen_id = $in['almacen_id'] ?? null;
$ruta_id    = $in['ruta_id'] ?? null;
$q          = trim($in['q'] ?? '');

if(!$almacen_id || !$ruta_id) jexit(['ok'=>0,'error'=>'almacen_id y ruta_id requeridos']);

try{
  // Supuestos base (de tus definiciones):
  // - RelClirutas.IdCliente = c_destinatarios.id_destinatario
  // - RelClirutas.ID_Ruta   = t_ruta.ID_Ruta
  // - t_ruta.cve_almacenp   = almacén
  //
  // Traemos TODOS los destinatarios (clientes) del sistema (o del almacén si tu modelo lo permite),
  // y marcamos si están asignados a la ruta actual.
  //
  // IMPORTANTE: Si tu tabla de destinatarios tiene otras columnas, ajustas aquí.
  $whereQ = '';
  $params = ['ruta_id'=>$ruta_id];

  if($q !== ''){
    $whereQ = " AND (
      d.nombre LIKE :q OR d.clave_destinatario LIKE :q OR d.direccion LIKE :q OR d.colonia LIKE :q OR d.cp LIKE :q
    )";
    $params['q'] = "%$q%";
  }

  // Universo: clientes/ destinatarios.
  // Nota: si hay columna de almacen en c_destinatarios, aquí filtras por $almacen_id.
  $sql = "
    SELECT
      d.id_destinatario AS id_cliente,
      CONCAT('[', d.id_destinatario, '] ', COALESCE(d.clave_destinatario,''), ' ', COALESCE(d.nombre,'')) AS cliente,
      COALESCE(d.nombre,'') AS destinatario,
      COALESCE(d.direccion,'') AS direccion,
      COALESCE(d.colonia,'') AS colonia,
      COALESCE(d.cp,'') AS cp,
      COALESCE(d.ciudad,'') AS ciudad,
      COALESCE(d.estado,'') AS estado,

      -- rutas actuales del cliente (todas)
      (SELECT GROUP_CONCAT(DISTINCT r2.cve_ruta ORDER BY r2.cve_ruta SEPARATOR ', ')
         FROM RelClirutas rc2
         JOIN t_ruta r2 ON r2.ID_Ruta = rc2.ID_Ruta
        WHERE rc2.IdCliente = d.id_destinatario
      ) AS rutas_actuales,

      -- asignado a la ruta actual
      CASE WHEN EXISTS(
        SELECT 1 FROM RelClirutas rc
         WHERE rc.IdCliente = d.id_destinatario
           AND rc.ID_Ruta = :ruta_id
      ) THEN 1 ELSE 0 END AS asignado_esta_ruta,

      '' AS lista_precio,
      '' AS promocion,
      '' AS descuento

    FROM c_destinatarios d
    WHERE 1=1
    $whereQ
    ORDER BY d.nombre
    LIMIT 2000
  ";

  $data = db_all($sql, $params);

  jexit(['ok'=>1,'data'=>$data]);

}catch(Exception $e){
  jexit(['ok'=>0,'error'=>'Error servidor','detalle'=>$e->getMessage()]);
}
