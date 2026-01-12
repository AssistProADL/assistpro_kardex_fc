<?php
// public/api/vas/pedidos_servicios.php
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

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$raw = file_get_contents('php://input');
$body = [];
if ($raw) {
  $tmp = json_decode($raw, true);
  if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) $body = $tmp;
}

$IdEmpresa = $_GET['IdEmpresa'] ?? ($body['IdEmpresa'] ?? null);
$id_pedido = intval($_GET['id_pedido'] ?? ($body['id_pedido'] ?? 0));
$id = intval($_GET['id'] ?? ($body['id'] ?? 0)); // item vas_pedido_servicio.id

if (!$IdEmpresa) jerr("Falta IdEmpresa");
if ($id_pedido <= 0) jerr("Falta id_pedido");

$pedido = db_one("SELECT id_pedido, Fol_folio, Fec_Pedido, Cve_clte, cve_almac
                  FROM th_pedido WHERE id_pedido=:id", ["id"=>$id_pedido]);
if (!$pedido) jerr("Pedido no encontrado", "NOT_FOUND");

$cliente = db_one("SELECT id_cliente, Cve_Clte, RazonSocial, IdEmpresa
                   FROM c_cliente WHERE Cve_Clte=:c", ["c"=>$pedido['Cve_clte']]);
if (!$cliente) jerr("Cliente del pedido no encontrado", "NOT_FOUND");

if ($method === 'GET') {
  $items = db_all("SELECT ps.id, ps.id_servicio, s.nombre AS servicio, ps.cantidad, ps.precio_unitario, ps.total,
                          ps.estatus, ps.fecha_aplicacion, ps.folio_factura, ps.fecha_factura
                   FROM vas_pedido_servicio ps
                   JOIN vas_servicio s ON s.id_servicio=ps.id_servicio
                   WHERE ps.IdEmpresa=:e AND ps.id_pedido=:p
                   ORDER BY ps.id DESC",
                   ["e"=>$IdEmpresa,"p"=>$id_pedido]);

  $tot = db_val("SELECT COALESCE(SUM(total),0) FROM vas_pedido_servicio WHERE IdEmpresa=:e AND id_pedido=:p",
                ["e"=>$IdEmpresa,"p"=>$id_pedido]);

  jok([
    "pedido"=>[
      "id_pedido"=>$pedido['id_pedido'],
      "folio"=>$pedido['Fol_folio'],
      "fecha"=>$pedido['Fec_Pedido'],
      "cve_almac"=>$pedido['cve_almac'],
      "Cve_clte"=>$pedido['Cve_clte']
    ],
    "cliente"=>[
      "id_cliente"=>$cliente['id_cliente'],
      "Cve_Clte"=>$cliente['Cve_Clte'],
      "RazonSocial"=>$cliente['RazonSocial']
    ],
    "items"=>$items,
    "totales"=>["importe_vas"=>floatval($tot)]
  ]);
}

// ADD
if ($method === 'POST') {
  $id_servicio = intval($body['id_servicio'] ?? 0);
  $cantidad = isset($body['cantidad']) ? floatval($body['cantidad']) : 0;
  $precio_unitario = array_key_exists('precio_unitario', $body) ? floatval($body['precio_unitario']) : null;

  if ($id_servicio<=0) jerr("Falta id_servicio");
  if ($cantidad<=0) jerr("cantidad debe ser > 0");

  // Servicio activo
  $srv = db_one("SELECT id_servicio, tipo_cobro, precio_base
                 FROM vas_servicio WHERE id_servicio=:s AND IdEmpresa=:e AND Activo=1",
                 ["s"=>$id_servicio,"e"=>$IdEmpresa]);
  if (!$srv) jerr("Servicio no encontrado o inactivo", "NOT_FOUND");

  // Validar habilitado para cliente
  $habil = db_one("SELECT precio_cliente, tipo_cobro, Activo
                   FROM vas_cliente_servicio
                   WHERE IdEmpresa=:e AND id_cliente=:c AND id_servicio=:s AND Activo=1",
                  ["e"=>$IdEmpresa,"c"=>$cliente['id_cliente'],"s"=>$id_servicio]);
  if (!$habil) jerr("Servicio no habilitado para el cliente", "CONFLICT");

  if ($precio_unitario === null) {
    $precio_unitario = ($habil['precio_cliente'] !== null) ? floatval($habil['precio_cliente']) : floatval($srv['precio_base']);
  }
  $total = round($cantidad * $precio_unitario, 2);

  dbq("INSERT INTO vas_pedido_servicio
       (IdEmpresa, cve_almac, id_pedido, Fol_folio, id_cliente, Cve_Clte, id_servicio, cantidad, precio_unitario, total, estatus, created_by)
       VALUES (:e,:a,:p,:f,:ic,:cc,:s,:q,:pu,:t,'pendiente',:u)",
      [
        "e"=>$IdEmpresa,
        "a"=>$pedido['cve_almac'],
        "p"=>$pedido['id_pedido'],
        "f"=>$pedido['Fol_folio'],
        "ic"=>$cliente['id_cliente'],
        "cc"=>$cliente['Cve_Clte'],
        "s"=>$id_servicio,
        "q"=>$cantidad,
        "pu"=>$precio_unitario,
        "t"=>$total,
        "u"=>($_SESSION['usuario'] ?? 'API')
      ]);

  $newId = db_val("SELECT LAST_INSERT_ID()");
  jok(["id"=>intval($newId), "total"=>$total], "Agregado");
}

// UPDATE
if ($method === 'PUT') {
  if ($id<=0) jerr("Falta id (item) ?id=");

  $item = db_one("SELECT id, estatus FROM vas_pedido_servicio
                  WHERE id=:id AND IdEmpresa=:e AND id_pedido=:p",
                  ["id"=>$id,"e"=>$IdEmpresa,"p"=>$id_pedido]);
  if (!$item) jerr("Item no encontrado", "NOT_FOUND");
  if ($item['estatus'] === 'facturado') jerr("No se puede editar un item facturado", "CONFLICT");

  $sets=[]; $p=["id"=>$id,"e"=>$IdEmpresa,"p"=>$id_pedido, "u"=>($_SESSION['usuario'] ?? 'API')];

  if (array_key_exists('cantidad', $body)) { $sets[]="cantidad=:cantidad"; $p["cantidad"]=floatval($body["cantidad"]); }
  if (array_key_exists('precio_unitario', $body)) { $sets[]="precio_unitario=:precio_unitario"; $p["precio_unitario"]=floatval($body["precio_unitario"]); }
  if (array_key_exists('estatus', $body)) { $sets[]="estatus=:estatus"; $p["estatus"]=$body["estatus"]; }
  if (array_key_exists('fecha_aplicacion', $body)) { $sets[]="fecha_aplicacion=:fecha_aplicacion"; $p["fecha_aplicacion"]=$body["fecha_aplicacion"]; }

  if (!$sets) jerr("Nada para actualizar");

  // recalcular total si cambia cantidad o precio
  $cur = db_one("SELECT cantidad, precio_unitario FROM vas_pedido_servicio WHERE id=:id", ["id"=>$id]);
  $q = isset($p["cantidad"]) ? $p["cantidad"] : floatval($cur["cantidad"]);
  $pu = isset($p["precio_unitario"]) ? $p["precio_unitario"] : floatval($cur["precio_unitario"]);
  $p["total"] = round($q * $pu, 2);
  $sets[]="total=:total";

  dbq("UPDATE vas_pedido_servicio SET ".implode(", ", $sets).", updated_at=NOW(), updated_by=:u
       WHERE id=:id AND IdEmpresa=:e AND id_pedido=:p", $p);

  jok(["total"=>$p["total"]], "Actualizado");
}

// DELETE
if ($method === 'DELETE') {
  if ($id<=0) jerr("Falta id (item) ?id=");

  $item = db_one("SELECT estatus FROM vas_pedido_servicio WHERE id=:id AND IdEmpresa=:e AND id_pedido=:p",
                 ["id"=>$id,"e"=>$IdEmpresa,"p"=>$id_pedido]);
  if (!$item) jerr("Item no encontrado", "NOT_FOUND");
  if ($item['estatus'] === 'facturado') jerr("No se puede eliminar un item facturado", "CONFLICT");

  dbq("DELETE FROM vas_pedido_servicio WHERE id=:id AND IdEmpresa=:e AND id_pedido=:p",
      ["id"=>$id,"e"=>$IdEmpresa,"p"=>$id_pedido]);

  jok(null, "Eliminado");
}

jerr("Método no soportado", "VALIDATION", ["method"=>$method]);
