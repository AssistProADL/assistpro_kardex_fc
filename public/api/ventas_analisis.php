<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

function s($v){ $v = trim((string)$v); return $v==='' ? null : $v; }
function i0($v){ return ($v==='' || $v===null) ? 0 : (int)$v; }

/* =========================
 * EXPORT CSV
 * ========================= */
if($action==='export_csv'){
  $tipo = $_GET['tipo'] ?? 'layout';

  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=ventas_'.$tipo.'.csv');

  $out = fopen('php://output','w');
  $headers = [
    'Id','IdEmpresa','RutaId','VendedorId','CodCliente','Documento','Fecha',
    'TipoVta','DiasCred','CreditoDispo','Saldo','Fvence',
    'SubTotal','IVA','IEPS','TOTAL','Items','FormaPag','DocSalida','Cancelada','Kg'
  ];
  fputcsv($out,$headers);

  if($tipo==='datos'){
    $sql = "SELECT ".implode(',', $headers)." FROM Venta ORDER BY Fecha DESC, Id DESC";
    foreach($pdo->query($sql) as $row) fputcsv($out,$row);
  }
  fclose($out);
  exit;
}

/* =========================
 * WHERE dinámico
 * ========================= */
function build_where(&$params){
  $where = " WHERE 1=1 ";

  $IdEmpresa  = s($_GET['IdEmpresa'] ?? null);
  $RutaId     = i0($_GET['RutaId'] ?? 0);
  $VendedorId = i0($_GET['VendedorId'] ?? 0);
  $Cancelada  = $_GET['Cancelada'] ?? '';
  $q          = trim((string)($_GET['q'] ?? ''));

  $fecha_ini  = s($_GET['fecha_ini'] ?? null);
  $fecha_fin  = s($_GET['fecha_fin'] ?? null);

  if($IdEmpresa){
    $where .= " AND v.IdEmpresa = :IdEmpresa ";
    $params[':IdEmpresa'] = $IdEmpresa;
  }
  if($RutaId > 0){
    $where .= " AND v.RutaId = :RutaId ";
    $params[':RutaId'] = $RutaId;
  }
  if($VendedorId > 0){
    $where .= " AND v.VendedorId = :VendedorId ";
    $params[':VendedorId'] = $VendedorId;
  }
  if($Cancelada !== '' && ($Cancelada==='0' || $Cancelada==='1')){
    $where .= " AND IFNULL(v.Cancelada,0) = :Cancelada ";
    $params[':Cancelada'] = (int)$Cancelada;
  }
  if($fecha_ini){
    $where .= " AND v.Fecha >= :fecha_ini ";
    $params[':fecha_ini'] = $fecha_ini . " 00:00:00";
  }
  if($fecha_fin){
    $where .= " AND v.Fecha <= :fecha_fin ";
    $params[':fecha_fin'] = $fecha_fin . " 23:59:59";
  }
  if($q !== ''){
    $where .= " AND (v.Documento LIKE :q OR v.CodCliente LIKE :q OR v.DocSalida LIKE :q OR v.TipoVta LIKE :q) ";
    $params[':q'] = "%$q%";
  }

  return $where;
}

/* =========================
 * DETALLE (Venta + DetalleVet)
 * Cliente: c_destinatarios.id_destinatario = Venta.CodCliente
 * ========================= */
if($action==='detalle'){
  $Id = (int)($_GET['Id'] ?? 0);
  if($Id<=0){ echo json_encode(['error'=>'Id requerido']); exit; }

  $sqlH = "
    SELECT v.*, cd.razonsocial AS ClienteNombre
    FROM Venta v
    LEFT JOIN c_destinatarios cd
      ON cd.id_destinatario = CAST(v.CodCliente AS UNSIGNED)
    WHERE v.Id = ?
    LIMIT 1
  ";
  $stH = $pdo->prepare($sqlH);
  $stH->execute([$Id]);
  $head = $stH->fetch(PDO::FETCH_ASSOC);
  if(!$head){ echo json_encode(['error'=>'No existe venta']); exit; }

  $sqlD = "
    SELECT
      d.ID, d.Articulo, d.Descripcion,
      d.Precio, d.Pza, d.Kg,
      d.DescPorc, d.DescMon,
      d.Importe, d.IVA, d.IEPS,
      d.Comisiones, d.Utilidad, d.Tipo
    FROM DetalleVet d
    WHERE d.Docto = :Docto
      AND d.RutaId = :RutaId
      AND d.IdEmpresa = :IdEmpresa
    ORDER BY d.ID ASC
  ";
  $stD = $pdo->prepare($sqlD);
  $stD->bindValue(':Docto', $head['Documento'], PDO::PARAM_STR);
  $stD->bindValue(':RutaId', (int)$head['RutaId'], PDO::PARAM_INT);
  $stD->bindValue(':IdEmpresa', $head['IdEmpresa'], PDO::PARAM_STR);
  $stD->execute();
  $detail = $stD->fetchAll(PDO::FETCH_ASSOC);

  $sqlT = "
    SELECT
      COUNT(*) AS partidas,
      SUM(IFNULL(d.Importe,0)) AS importe,
      SUM(IFNULL(d.IVA,0)) AS iva,
      SUM(IFNULL(d.IEPS,0)) AS ieps
    FROM DetalleVet d
    WHERE d.Docto = :Docto
      AND d.RutaId = :RutaId
      AND d.IdEmpresa = :IdEmpresa
  ";
  $stT = $pdo->prepare($sqlT);
  $stT->bindValue(':Docto', $head['Documento'], PDO::PARAM_STR);
  $stT->bindValue(':RutaId', (int)$head['RutaId'], PDO::PARAM_INT);
  $stT->bindValue(':IdEmpresa', $head['IdEmpresa'], PDO::PARAM_STR);
  $stT->execute();
  $tot = $stT->fetch(PDO::FETCH_ASSOC);

  $importe = (float)($tot['importe'] ?? 0);
  $iva     = (float)($tot['iva'] ?? 0);
  $ieps    = (float)($tot['ieps'] ?? 0);

  echo json_encode([
    'head'=>$head,
    'detail'=>$detail,
    'line_totals'=>[
      'partidas'=>(int)($tot['partidas'] ?? 0),
      'importe'=>$importe,
      'iva'=>$iva,
      'ieps'=>$ieps,
      'total_calculado'=>$importe+$iva+$ieps
    ]
  ]);
  exit;
}

/* =========================
 * KPIs
 * ========================= */
if($action==='kpis'){
  $params = [];
  $where = build_where($params);

  $sql = "
    SELECT
      COUNT(*) AS ventas,
      SUM(IFNULL(v.TOTAL,0)) AS total,
      SUM(IFNULL(v.SubTotal,0)) AS subtotal,
      SUM(IFNULL(v.IVA,0)) AS iva,
      SUM(IFNULL(v.IEPS,0)) AS ieps,
      SUM(IFNULL(v.Items,0)) AS items,
      AVG(IFNULL(v.TOTAL,0)) AS ticket_promedio
    FROM Venta v
    $where
  ";
  $st = $pdo->prepare($sql);
  foreach($params as $k=>$v) $st->bindValue($k,$v);
  $st->execute();
  $k = $st->fetch(PDO::FETCH_ASSOC);

  echo json_encode([
    'kpis'=>[
      'ventas'=>(int)($k['ventas'] ?? 0),
      'total'=>(float)($k['total'] ?? 0),
      'subtotal'=>(float)($k['subtotal'] ?? 0),
      'iva'=>(float)($k['iva'] ?? 0),
      'ieps'=>(float)($k['ieps'] ?? 0),
      'items'=>(float)($k['items'] ?? 0),
      'ticket_promedio'=>(float)($k['ticket_promedio'] ?? 0),
    ]
  ]);
  exit;
}

/* =========================
 * LIST paginado
 * ========================= */
if($action==='list'){
  $page = max(1, (int)($_GET['page'] ?? 1));
  $pageSize = (int)($_GET['pageSize'] ?? 25);
  if($pageSize <= 0) $pageSize = 25;
  if($pageSize > 100) $pageSize = 100;

  $offset = ($page - 1) * $pageSize;

  $params = [];
  $where = build_where($params);

  $sqlCount = "SELECT COUNT(*) FROM Venta v $where";
  $stc = $pdo->prepare($sqlCount);
  foreach($params as $k=>$v) $stc->bindValue($k,$v);
  $stc->execute();
  $total = (int)$stc->fetchColumn();

  $sql = "
    SELECT
      v.Id, v.IdEmpresa, v.RutaId, v.VendedorId,
      v.CodCliente,
      cd.razonsocial AS ClienteNombre,
      v.Documento, v.Fecha, v.TipoVta,
      v.DiasCred, v.CreditoDispo, v.Saldo, v.Fvence,
      v.SubTotal, v.IVA, v.IEPS, v.TOTAL,
      v.Items, v.FormaPag, v.DocSalida, v.Cancelada, v.Kg
    FROM Venta v
    LEFT JOIN c_destinatarios cd
      ON cd.id_destinatario = CAST(v.CodCliente AS UNSIGNED)
    $where
    ORDER BY v.Fecha DESC, v.Id DESC
    LIMIT :lim OFFSET :off
  ";
  $st = $pdo->prepare($sql);
  foreach($params as $k=>$v) $st->bindValue($k,$v);
  $st->bindValue(':lim', $pageSize, PDO::PARAM_INT);
  $st->bindValue(':off', $offset, PDO::PARAM_INT);

  $st->execute();
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'page'=>$page,
    'pageSize'=>$pageSize,
    'total'=>$total,
    'pages'=>$pageSize ? (int)ceil($total / $pageSize) : 1,
    'rows'=>$rows
  ]);
  exit;
}

echo json_encode(['error'=>'Acción no válida']);
