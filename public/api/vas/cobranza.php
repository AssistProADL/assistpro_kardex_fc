<?php
// public/api/vas/cobranza.php
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
if (!$IdEmpresa) jerr("Falta IdEmpresa");

if ($method === 'GET') {
  $owner_id = isset($_GET['owner_id']) ? intval($_GET['owner_id']) : 0;
  $fi = $_GET['fecha_inicio'] ?? null;
  $ff = $_GET['fecha_fin'] ?? null;

  $sql = "
    SELECT
      ps.id_pedido,
      p.Fol_folio AS folio,
      p.Fec_Pedido AS fecha_pedido,
      p.Id_Proveedor,
      pr.Nombre AS owner_nombre,
      p.Cve_clte,
      c.RazonSocial AS cliente,
      SUM(ps.total) AS importe_vas,
      SUM(CASE WHEN ps.estatus='pendiente' THEN 1 ELSE 0 END) AS items_pendiente,
      SUM(CASE WHEN ps.estatus='aplicado' THEN 1 ELSE 0 END) AS items_aplicado
    FROM vas_pedido_servicio ps
    JOIN th_pedido p ON p.id_pedido = ps.id_pedido
    LEFT JOIN c_proveedores pr ON pr.ID_Proveedor = p.Id_Proveedor
    LEFT JOIN c_cliente c ON c.Cve_Clte = p.Cve_clte
    WHERE ps.IdEmpresa = :e
      AND ps.estatus IN ('pendiente','aplicado')
  ";
  $par = ["e"=>$IdEmpresa];

  if ($owner_id > 0) { $sql .= " AND p.Id_Proveedor = :oid "; $par["oid"] = $owner_id; }
  if ($fi) { $sql .= " AND p.Fec_Pedido >= :fi "; $par["fi"] = $fi; }
  if ($ff) { $sql .= " AND p.Fec_Pedido <= :ff "; $par["ff"] = $ff; }

  $sql .= "
    GROUP BY ps.id_pedido, p.Fol_folio, p.Fec_Pedido, p.Id_Proveedor, pr.Nombre, p.Cve_clte, c.RazonSocial
    ORDER BY p.Fec_Pedido DESC
    LIMIT 500
  ";

  $rows = db_all($sql, $par);
  jok($rows);
}

if ($method === 'POST') {
  $action = $body['action'] ?? '';
  if ($action !== 'facturar') jerr("action inválida; use action='facturar'");

  $id_pedido = intval($body['id_pedido'] ?? 0);
  $folio_factura = trim($body['folio_factura'] ?? '');
  $fecha_factura = $body['fecha_factura'] ?? date('Y-m-d H:i:s');
  $facturar_por_pedido = intval($body['facturar_por_pedido'] ?? 0);

  if ($id_pedido <= 0) jerr("Falta id_pedido");
  if ($folio_factura === '') jerr("folio_factura requerido");

  $ids = $body['ids'] ?? [];
  if ($facturar_por_pedido === 1) {
    $tmp = db_all("SELECT id
                   FROM vas_pedido_servicio
                   WHERE IdEmpresa=:e AND id_pedido=:p
                     AND estatus IN ('pendiente','aplicado')",
                  ["e"=>$IdEmpresa, "p"=>$id_pedido]);
    $ids = array_map(fn($r)=>intval($r['id']), $tmp);
    if (!count($ids)) jerr("No hay items para facturar", "CONFLICT");
  } else {
    if (!is_array($ids) || !count($ids)) jerr("ids (items) requerido o use facturar_por_pedido=1");
    $ids = array_map('intval', $ids);
  }

  $user = req_user($body);
  $facturados = 0;

  db_tx(function() use ($IdEmpresa, $id_pedido, $ids, $folio_factura, $fecha_factura, $user, &$facturados) {
    foreach ($ids as $id) {
      if ($id <= 0) continue;

      $it = db_one("SELECT id, estatus
                    FROM vas_pedido_servicio
                    WHERE id=:id AND IdEmpresa=:e AND id_pedido=:p",
                  ["id"=>$id, "e"=>$IdEmpresa, "p"=>$id_pedido]);
      if (!$it) continue;
      if ($it['estatus'] === 'facturado') continue;

      dbq("UPDATE vas_pedido_servicio
           SET estatus='facturado',
               folio_factura=:f,
               fecha_factura=:ff,
               updated_at=NOW(),
               updated_by=:u
           WHERE id=:id AND IdEmpresa=:e AND id_pedido=:p",
          [
            "f"=>$folio_factura,
            "ff"=>$fecha_factura,
            "u"=>$user,
            "id"=>$id, "e"=>$IdEmpresa, "p"=>$id_pedido
          ]);

      $facturados++;
    }
  });

  jok(["facturados"=>$facturados, "id_pedido"=>$id_pedido, "folio_factura"=>$folio_factura], "Marcado como facturado");
}

jerr("Método no soportado", "VALIDATION", ["method"=>$method]);
