<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();

function jerr($msg, $extra=[]){ echo json_encode(array_merge(['error'=>$msg], $extra)); exit; }
function col_exists(PDO $pdo, string $table, string $col): bool {
  $st=$pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?");
  $st->execute([$table,$col]);
  return (int)$st->fetchColumn() > 0;
}

$alm = $_POST['almacen'] ?? ($_POST['IdEmpresa'] ?? ($_GET['almacen'] ?? ($_GET['IdEmpresa'] ?? '')));
$alm = trim((string)$alm);
if($alm==='') jerr('Almacén (IdEmpresa) requerido');

$buscar = trim((string)($_POST['buscar'] ?? ($_GET['buscar'] ?? '')));
$pagina = (int)($_POST['pagina'] ?? ($_GET['pagina'] ?? 1));
if($pagina<1) $pagina = 1;

$pageSize = (int)($_POST['pageSize'] ?? ($_GET['pageSize'] ?? 200)); // para mapa conviene >25
if($pageSize<25) $pageSize = 25;
if($pageSize>2000) $pageSize = 2000;

$off = ($pagina-1)*$pageSize;

/* ============================
   Determinar "nombre" de c_cliente (si existe)
   ============================ */
$clienteNameExpr = "NULL"; // default: no rompe
if(col_exists($pdo,'c_cliente','nombre'))        $clienteNameExpr = "c.nombre";
elseif(col_exists($pdo,'c_cliente','Nombre'))   $clienteNameExpr = "c.Nombre";
elseif(col_exists($pdo,'c_cliente','razonsocial')) $clienteNameExpr = "c.razonsocial";
elseif(col_exists($pdo,'c_cliente','RazonSocial')) $clienteNameExpr = "c.RazonSocial";
elseif(col_exists($pdo,'c_cliente','nombrecomercial')) $clienteNameExpr = "c.nombrecomercial";
elseif(col_exists($pdo,'c_cliente','NombreComercial')) $clienteNameExpr = "c.NombreComercial";
// si no existe nada, se queda NULL

$where = " WHERE d.Activo='1' AND d.id_destinatario IS NOT NULL AND d.id_destinatario<>0 ";
$params = [':alm'=>$alm];

if($buscar!==''){
  $where .= " AND (
    d.razonsocial LIKE :q OR d.direccion LIKE :q OR d.colonia LIKE :q OR d.postal LIKE :q
    OR d.ciudad LIKE :q OR d.estado LIKE :q OR d.clave_destinatario LIKE :q
    OR CAST(d.id_destinatario AS CHAR) LIKE :q
  )";
  $params[':q'] = "%$buscar%";
}

/*
  relclirutas:
  - IdCliente = id_destinatario
  - IdRuta = ruta
  - IdEmpresa = empresa/almacen (varchar)
*/
$sql = "
SELECT
  d.id_destinatario AS id,
  d.clave_destinatario,
  d.razonsocial AS destinatario,
  d.direccion,
  d.colonia,
  d.postal,
  d.ciudad,
  d.estado,
  d.telefono,
  d.email_destinatario,
  d.latitud,
  d.longitud,

  d.Cve_Clte AS clave_cliente,
  $clienteNameExpr AS cliente_nombre,  -- opcional si existe

  rc.IdRuta AS id_ruta_actual,
  r.cve_ruta,
  r.descripcion AS ruta_desc
FROM c_destinatarios d
LEFT JOIN c_cliente c
  ON c.Cve_Clte = d.Cve_Clte
LEFT JOIN relclirutas rc
  ON rc.IdCliente = d.id_destinatario
  AND rc.IdEmpresa = :alm
LEFT JOIN t_ruta r
  ON r.ID_Ruta = rc.IdRuta
$where
ORDER BY
  (CASE WHEN d.latitud IS NULL OR d.latitud='' OR d.longitud IS NULL OR d.longitud='' THEN 1 ELSE 0 END),
  d.razonsocial
LIMIT $pageSize OFFSET $off
";

try{
  $st = $pdo->prepare($sql);
  foreach($params as $k=>$v) $st->bindValue($k, $v);
  $st->execute();
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  $data = [];
  foreach($rows as $r){
    // si no hay cliente_nombre, usamos el destinatario para no romper demo
    $cliente = $r['cliente_nombre'] ?? null;
    if($cliente===null || trim((string)$cliente)==='') $cliente = $r['destinatario'] ?? '';

    $data[] = [
      'id' => (int)$r['id'],
      'clave_destinatario' => $r['clave_destinatario'] ?? '',
      'destinatario' => $r['destinatario'] ?? '',
      'direccion' => $r['direccion'] ?? '',
      'colonia' => $r['colonia'] ?? '',
      'postal' => $r['postal'] ?? '',
      'ciudad' => $r['ciudad'] ?? '',
      'estado' => $r['estado'] ?? '',
      'latitud' => $r['latitud'],
      'longitud' => $r['longitud'],
      'clave_cliente' => $r['clave_cliente'] ?? '',
      'cliente' => $cliente,
      'ruta' => ($r['cve_ruta'] ? ($r['cve_ruta'].' - '.($r['ruta_desc'] ?? '')) : '--'),
      'id_ruta_actual' => $r['id_ruta_actual'] ? (int)$r['id_ruta_actual'] : null,
    ];
  }

  echo json_encode([
    'success' => true,
    'page' => $pagina,
    'pageSize' => $pageSize,
    'count' => count($data),
    'data' => $data,
  ]);
}catch(Throwable $e){
  jerr('Error consultando clientes', ['detalle'=>$e->getMessage()]);
}
