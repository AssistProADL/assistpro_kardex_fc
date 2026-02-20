<?php
// public/api/sfa/clientes_comercial_data.php
// Devuelve asignación comercial actual por destinatario.
// Soporta GET o POST(JSON):
//   destinatarios: [id,...] (opcional)
//   q: texto (opcional) para buscar dentro de c_destinatarios
//   limit (opcional)

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../app/db.php';

function read_json_body() {
  $raw = file_get_contents('php://input');
  if (!$raw) return null;
  $j = json_decode($raw, true);
  return is_array($j) ? $j : null;
}

try {
  $body = read_json_body() ?: [];
  $destinatarios = $body['destinatarios'] ?? ($_GET['destinatarios'] ?? []);
  $q = trim((string)($body['q'] ?? ($_GET['q'] ?? '')));
  $limit = (int)($body['limit'] ?? ($_GET['limit'] ?? 200));
  if ($limit <= 0 || $limit > 2000) $limit = 200;

  $params = [];
  $where = [];

  if (is_string($destinatarios)) {
    // "1,2,3"
    $destinatarios = array_filter(array_map('trim', explode(',', $destinatarios)), fn($v)=>$v!=='');
  }
  if (is_array($destinatarios) && count($destinatarios) > 0) {
    $in = [];
    foreach ($destinatarios as $i => $id) {
      $k = ":d{$i}";
      $in[] = $k;
      $params[$k] = (int)$id;
    }
    $where[] = 'd.id_destinatario IN (' . implode(',', $in) . ')';
  }
  if ($q !== '') {
    $params[':q'] = "%{$q}%";
    $where[] = '(d.destinatario LIKE :q OR d.clave_destinatario LIKE :q OR d.colonia LIKE :q OR d.cp LIKE :q)';
  }

  $sql = "
    SELECT
      d.id_destinatario,
      d.clave_destinatario,
      d.destinatario,
      rc.ListaP,
      lp.Descripcion AS lista_precio,
      rc.ListaPromo,
      lpro.Descripcion AS lista_promo,
      rc.ListaD,
      ld.Descripcion AS lista_desc
    FROM c_destinatarios d
    LEFT JOIN relclilis rc ON rc.Id_Destinatario = d.id_destinatario
    LEFT JOIN listap lp ON lp.Id = rc.ListaP
    LEFT JOIN listapromo lpro ON lpro.Id = rc.ListaPromo
    LEFT JOIN listad ld ON ld.Id = rc.ListaD
  ";
  if (count($where) > 0) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
  }
  $sql .= ' ORDER BY d.destinatario LIMIT ' . (int)$limit;

  $rows = db_all($sql, $params);

  echo json_encode([
    'ok' => 1,
    'rows' => $rows,
    'count' => count($rows)
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => 0,
    'error' => 'Error servidor',
    'detalle' => $e->getMessage()
  ]);
}
