<?php
// public/api/vas/clientes_servicios.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../app/db.php';

function jerr($msg, $type='VALIDATION', $detalle=[]) {
  echo json_encode(["ok"=>0,"error"=>$type,"msg"=>$msg,"detalle"=>$detalle], JSON_UNESCAPED_UNICODE);
  exit;
}
function jok($data=null, $msg="OK") {
  $out=["ok"=>1,"msg"=>$msg];
  if ($data!==null) $out["data"]=$data;
  echo json_encode($out, JSON_UNESCAPED_UNICODE);
  exit;
}
function req_user($body){
  $h = $_SERVER['HTTP_X_USER'] ?? '';
  $u = trim($h) !== '' ? trim($h) : trim($body['usuario'] ?? '');
  return $u !== '' ? $u : 'API';
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$raw = file_get_contents('php://input');
$body = [];
if ($raw) {
  $tmp = json_decode($raw, true);
  if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) $body = $tmp;
}

$IdEmpresa = $_GET['IdEmpresa'] ?? ($body['IdEmpresa'] ?? null);
$cve_almac = $_GET['cve_almac'] ?? ($body['cve_almac'] ?? null); // contextual
$id_cliente = intval($_GET['id_cliente'] ?? ($body['id_cliente'] ?? 0));
$owner_id   = intval($_GET['owner_id']   ?? ($body['owner_id']   ?? 0)); // NUEVO (c_proveedores.ID_Proveedor)

if (!$IdEmpresa) jerr("Falta IdEmpresa");
if ($id_cliente <= 0 && $owner_id <= 0) jerr("Falta id_cliente o owner_id");

$validTipos = ['fijo','por_pieza','por_pedido','por_hora'];

if ($method === 'GET') {

  $subject = null;
  $subjectType = null;

  if ($owner_id > 0) {
    $subjectType = "OWNER";
    $subject = db_one("SELECT ID_Proveedor, cve_proveedor, Empresa, Nombre, Activo
                       FROM c_proveedores
                       WHERE ID_Proveedor=:id AND es_cliente=1",
                      ["id"=>$owner_id]);
    if (!$subject) jerr("Owner no encontrado (c_proveedores.es_cliente=1)", "NOT_FOUND");

    $sql = "
      SELECT
        s.id_servicio,
        s.clave_servicio,
        s.nombre,
        s.tipo_cobro AS tipo_cobro_default,
        s.precio_base,
        s.moneda,
        IF(cs.id IS NULL, 0, 1) AS habilitado,
        cs.tipo_cobro AS tipo_cobro_cliente,
        cs.precio_cliente,
        cs.Activo
      FROM vas_servicio s
      LEFT JOIN vas_cliente_servicio cs
        ON cs.IdEmpresa = s.IdEmpresa
       AND cs.ID_Proveedor = :owner_id
       AND cs.id_servicio = s.id_servicio
      WHERE s.IdEmpresa = :IdEmpresa
        AND s.Activo = 1
      ORDER BY s.nombre ASC
    ";
    $rows = db_all($sql, ["IdEmpresa"=>$IdEmpresa, "owner_id"=>$owner_id]);

  } else {
    $subjectType = "CLIENTE";
    $subject = db_one("SELECT id_cliente, Cve_Clte, RazonSocial, RazonComercial, IdEmpresa, Activo
                       FROM c_cliente WHERE id_cliente=:id",
                      ["id"=>$id_cliente]);
    if (!$subject) jerr("Cliente no encontrado", "NOT_FOUND");

    $sql = "
      SELECT
        s.id_servicio,
        s.clave_servicio,
        s.nombre,
        s.tipo_cobro AS tipo_cobro_default,
        s.precio_base,
        s.moneda,
        IF(cs.id IS NULL, 0, 1) AS habilitado,
        cs.tipo_cobro AS tipo_cobro_cliente,
        cs.precio_cliente,
        cs.Activo
      FROM vas_servicio s
      LEFT JOIN vas_cliente_servicio cs
        ON cs.IdEmpresa = s.IdEmpresa
       AND cs.id_cliente = :id_cliente
       AND cs.id_servicio = s.id_servicio
      WHERE s.IdEmpresa = :IdEmpresa
        AND s.Activo = 1
      ORDER BY s.nombre ASC
    ";
    $rows = db_all($sql, ["IdEmpresa"=>$IdEmpresa, "id_cliente"=>$id_cliente]);
  }

  jok([
    "subjectType"=>$subjectType,
    "subject"=>$subject,
    "servicios"=>$rows
  ]);
}

// Bulk UPSERT
if ($method === 'POST') {
  $items = $body['items'] ?? null;
  if (!is_array($items) || !count($items)) jerr("items es requerido (array)");

  // Validación sujeto
  if ($owner_id > 0) {
    $own = db_one("SELECT ID_Proveedor FROM c_proveedores WHERE ID_Proveedor=:id AND es_cliente=1",
                 ["id"=>$owner_id]);
    if (!$own) jerr("Owner no encontrado", "NOT_FOUND");
  } else {
    $cli = db_one("SELECT id_cliente FROM c_cliente WHERE id_cliente=:id", ["id"=>$id_cliente]);
    if (!$cli) jerr("Cliente no encontrado", "NOT_FOUND");
  }

  $user = req_user($body);

  db_tx(function() use ($items, $IdEmpresa, $id_cliente, $owner_id, $cve_almac, $validTipos, $user) {

    foreach ($items as $it) {
      $id_servicio = intval($it['id_servicio'] ?? 0);
      if ($id_servicio <= 0) continue;

      // Servicio existe y activo
      $srv = db_one("SELECT id_servicio, tipo_cobro, precio_base
                     FROM vas_servicio
                     WHERE id_servicio=:id AND IdEmpresa=:e AND Activo=1",
                    ["id"=>$id_servicio, "e"=>$IdEmpresa]);
      if (!$srv) continue;

      $Activo = isset($it['Activo']) ? intval($it['Activo']) : (isset($it['activo']) ? intval($it['activo']) : 1);
      $tipo = $it['tipo_cobro'] ?? $srv['tipo_cobro'];
      $precio = array_key_exists('precio_cliente', $it) ? $it['precio_cliente'] : null;

      if (!in_array($tipo, $validTipos, true)) $tipo = $srv['tipo_cobro'];
      if ($precio !== null) $precio = floatval($precio);

      // OWNER MODE
      if ($owner_id > 0) {
        $exist = db_one("SELECT id FROM vas_cliente_servicio
                         WHERE IdEmpresa=:e AND ID_Proveedor=:o AND id_servicio=:s",
                        ["e"=>$IdEmpresa, "o"=>$owner_id, "s"=>$id_servicio]);

        if ($exist) {
          dbq("UPDATE vas_cliente_servicio
               SET cve_almac=:a, tipo_cobro=:t, precio_cliente=:p, Activo=:x,
                   updated_at=NOW(), updated_by=:u
               WHERE id=:id",
              [
                "a"=>$cve_almac, "t"=>$tipo, "p"=>$precio, "x"=>$Activo,
                "u"=>$user, "id"=>$exist['id']
              ]);
        } else {
          dbq("INSERT INTO vas_cliente_servicio
               (IdEmpresa, cve_almac, ID_Proveedor, id_servicio, tipo_cobro, precio_cliente, Activo, created_by)
               VALUES (:e,:a,:o,:s,:t,:p,:x,:u)",
              [
                "e"=>$IdEmpresa, "a"=>$cve_almac, "o"=>$owner_id,
                "s"=>$id_servicio, "t"=>$tipo, "p"=>$precio, "x"=>$Activo,
                "u"=>$user
              ]);
        }
        continue;
      }

      // CLIENT MODE (legacy)
      if ($id_cliente <= 0) continue;

      $exist = db_one("SELECT id FROM vas_cliente_servicio
                       WHERE IdEmpresa=:e AND id_cliente=:c AND id_servicio=:s",
                      ["e"=>$IdEmpresa, "c"=>$id_cliente, "s"=>$id_servicio]);

      if ($exist) {
        dbq("UPDATE vas_cliente_servicio
             SET cve_almac=:a, tipo_cobro=:t, precio_cliente=:p, Activo=:x,
                 updated_at=NOW(), updated_by=:u
             WHERE id=:id",
            [
              "a"=>$cve_almac, "t"=>$tipo, "p"=>$precio, "x"=>$Activo,
              "u"=>$user, "id"=>$exist['id']
            ]);
      } else {
        dbq("INSERT INTO vas_cliente_servicio
             (IdEmpresa, cve_almac, id_cliente, id_servicio, tipo_cobro, precio_cliente, Activo, created_by)
             VALUES (:e,:a,:c,:s,:t,:p,:x,:u)",
            [
              "e"=>$IdEmpresa, "a"=>$cve_almac, "c"=>$id_cliente,
              "s"=>$id_servicio, "t"=>$tipo, "p"=>$precio, "x"=>$Activo,
              "u"=>$user
            ]);
      }
    }
  });

  jok(["updated"=>count($items)], "Configuración guardada");
}

jerr("Método no soportado", "VALIDATION", ["method"=>$method]);
