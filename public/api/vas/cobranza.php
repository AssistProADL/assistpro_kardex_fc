<?php
// public/api/vas/cobranza.php
// GET: devuelve pedidos con VAS pendiente/aplicado (vw_vas_pendiente_cobro)
// POST action=facturar: marca items de vas_pedido_servicio como facturados

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../app/db.php';

function jerr($msg, $detalle=null, $http=200) {
  http_response_code($http);
  echo json_encode(["ok"=>0,"msg"=>$msg,"detalle"=>$detalle], JSON_UNESCAPED_UNICODE);
  exit;
}
function jok($data=null, $msg="OK") {
  $out=["ok"=>1,"msg"=>$msg];
  if ($data !== null) $out["data"] = $data;
  echo json_encode($out, JSON_UNESCAPED_UNICODE);
  exit;
}

$action = trim($_GET['action'] ?? '');

try {
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'facturar') {
    $raw = file_get_contents('php://input');
    $j = json_decode($raw, true);
    if (!is_array($j)) jerr("JSON inválido", substr($raw,0,200));

    $IdEmpresa = $j['IdEmpresa'] ?? $j['idEmpresa'] ?? null;
    $ids = $j['id_pedidos'] ?? $j['id_pedido'] ?? null;

    if (!$IdEmpresa) jerr("Falta IdEmpresa");
    if (!is_array($ids) || !count($ids)) jerr("Falta id_pedidos[]");

    // normaliza ids a enteros
    $ids = array_values(array_filter(array_map('intval', $ids), fn($x)=>$x>0));
    if (!count($ids)) jerr("id_pedidos vacío");

    // folio_factura: se puede dejar manual después. Aquí ponemos un folio por lote.
    $folio = 'FAC-' . date('Ymd-His');
    $now = date('Y-m-d H:i:s');

    $in = implode(',', array_fill(0, count($ids), '?'));
    $sql = "UPDATE vas_pedido_servicio
            SET estatus = 'facturado',
                folio_factura = IFNULL(NULLIF(folio_factura,''), ?),
                fecha_factura = IFNULL(fecha_factura, ?)
            WHERE IdEmpresa = ?
              AND id_pedido IN ($in)
              AND estatus IN ('pendiente','aplicado')";
    $params = array_merge([$folio, $now, $IdEmpresa], $ids);

    // Ejecuta UPDATE (en este proyecto db_all ejecuta statements preparados; el resultado puede ser [])
    db_all($sql, $params);
    jok(["folio_factura"=>$folio, "ids"=>$ids], "Facturado");
  }

  // GET
  $IdEmpresa = $_GET['IdEmpresa'] ?? $_GET['idEmpresa'] ?? null;
  if (!$IdEmpresa) jerr("Falta IdEmpresa");

  $owner_id = isset($_GET['owner_id']) && $_GET['owner_id'] !== '' ? intval($_GET['owner_id']) : null;
  $fi = $_GET['fecha_inicio'] ?? null;
  $ff = $_GET['fecha_fin'] ?? null;

  // defaults: última semana si no viene
  if (!$ff) $ff = date('Y-m-d');
  if (!$fi) $fi = date('Y-m-d', strtotime('-7 days'));

  $sql = "SELECT IdEmpresa,
                 cve_almac,
                 id_pedido,
                 folio_pedido,
                 fecha_pedido,
                 id_cliente,
                 cliente,
                 importe_vas,
                 items_pendiente,
                 items_aplicado
          FROM vw_vas_pendiente_cobro
          WHERE IdEmpresa = :e
            AND fecha_pedido BETWEEN :fi AND :ff";
  $p = ["e"=>$IdEmpresa, "fi"=>$fi, "ff"=>$ff];

  if ($owner_id) {
    $sql .= " AND id_cliente = :oid";
    $p["oid"] = $owner_id;
  }

  $sql .= " ORDER BY fecha_pedido DESC, id_pedido DESC LIMIT 2000";

  $rows = db_all($sql, $p);
  jok($rows);

} catch (Throwable $ex) {
  jerr("Error en API", $ex->getMessage(), 200);
}
