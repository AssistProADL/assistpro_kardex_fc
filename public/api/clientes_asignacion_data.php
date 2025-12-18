<?php
require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

header('Content-Type: application/json');

$almacen = $_POST['almacen'] ?? '';
$buscar  = $_POST['buscar'] ?? '';
$pagina  = max(1, (int)($_POST['pagina'] ?? 1));
$limit   = 25;
$offset  = ($pagina - 1) * $limit;

if ($almacen === '') {
    echo json_encode(['data'=>[], 'pagina'=>$pagina, 'registros'=>0]);
    exit;
}

$params = [':almacen' => $almacen];

$sql = "
SELECT
  d.id_destinatario           AS id,
  c.Cve_Clte                  AS clave_cliente,
  c.Razon_Social              AS cliente,
  d.clave_destinatario,
  d.nombre                    AS destinatario,
  d.direccion,
  d.colonia,
  d.postal,
  d.ciudad,
  d.estado,
  d.latitud,
  d.longitud,
  rd.Cve_Ruta                 AS ruta_id,
  r.cve_ruta                  AS ruta,
  rd.Lu, rd.Ma, rd.Mi, rd.Ju, rd.Vi, rd.Sa, rd.Do
FROM c_destinatarios d
JOIN c_cliente c ON c.Cve_Clte = d.Cve_Clte
LEFT JOIN reldaycli rd
  ON rd.Id_Destinatario = d.id_destinatario
 AND rd.Cve_Almac = :almacen
LEFT JOIN t_ruta r ON r.ID_Ruta = rd.Cve_Ruta
WHERE d.Activo = 1
";

if ($buscar !== '') {
  $sql .= "
    AND (
      c.Razon_Social LIKE :b
      OR d.nombre LIKE :b
      OR d.colonia LIKE :b
      OR d.postal LIKE :b
    )
  ";
  $params[':b'] = "%$buscar%";
}

$sql .= " ORDER BY c.Razon_Social LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = [];

while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $r['dias'] = [];
  foreach (['Lu','Ma','Mi','Ju','Vi','Sa','Do'] as $d) {
    if (!empty($r[$d])) $r['dias'][] = $d;
    unset($r[$d]);
  }
  $data[] = $r;
}

echo json_encode([
  'pagina'    => $pagina,
  'registros' => count($data),
  'data'      => $data
]);
