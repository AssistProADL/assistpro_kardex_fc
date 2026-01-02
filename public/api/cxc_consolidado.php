<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

/* =========================
   Helpers filtros (solo ABIERTOS)
========================= */
function build_where(&$params){
  $empresa      = trim((string)($_GET['empresa'] ?? ''));      // Cobranza.IdEmpresa
  $rutaId       = trim((string)($_GET['rutaId'] ?? ''));       // Cobranza.RutaId
  $soloVencidos = (int)($_GET['solo_vencidos'] ?? 0);
  $fv_from      = trim((string)($_GET['fv_from'] ?? ''));      // YYYY-MM-DD
  $fv_to        = trim((string)($_GET['fv_to'] ?? ''));        // YYYY-MM-DD
  $q            = trim((string)($_GET['q'] ?? ''));

  $where = " WHERE 1=1 ";

  // Pendiente real
  $where .= " AND IFNULL(c.Saldo,0) > 0 ";

  // Solo abiertos: 1=abierto, 2=pagado
  $where .= " AND IFNULL(c.Status,0) = 1 ";

  if($empresa!==''){
    $where .= " AND c.IdEmpresa = :empresa ";
    $params[':empresa'] = $empresa;
  }

  if($rutaId!==''){
    $where .= " AND c.RutaId = :rutaId ";
    $params[':rutaId'] = (int)$rutaId;
  }

  if($soloVencidos){
    $where .= " AND c.FechaVence IS NOT NULL AND DATE(c.FechaVence) < CURDATE() ";
  }

  if($fv_from!==''){
    $where .= " AND c.FechaVence IS NOT NULL AND DATE(c.FechaVence) >= :fv_from ";
    $params[':fv_from'] = $fv_from;
  }
  if($fv_to!==''){
    $where .= " AND c.FechaVence IS NOT NULL AND DATE(c.FechaVence) <= :fv_to ";
    $params[':fv_to'] = $fv_to;
  }

  if($q!==''){
    $where .= " AND (
      c.Documento LIKE :q
      OR d.razonsocial LIKE :q
      OR d.clave_destinatario LIKE :q
      OR d.Cve_Clte LIKE :q
      OR cl.Cve_Clte LIKE :q
      OR cl.RazonSocial LIKE :q
      OR cl.RazonComercial LIKE :q
    ) ";
    $params[':q'] = "%$q%";
  }

  return $where;
}

/* =========================
   EXPORT CSV
   tipo=consolidado | tipo=docs
========================= */
if($action==='export_csv'){
  $tipo = $_GET['tipo'] ?? 'consolidado';
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=cxc_'.$tipo.'.csv');
  $out = fopen('php://output','w');

  // ---- EXPORT DOCS ----
  if($tipo==='docs'){
    $clienteId = (int)($_GET['clienteId'] ?? 0);
    if(!$clienteId){
      fputcsv($out, ['error','clienteId requerido']); fclose($out); exit;
    }

    $headers = [
      'id','ClienteId','Cve_Clte','clave_destinatario','razonsocial',
      'Documento','TipoDoc','Saldo','Abonos','SaldoNeto',
      'Status','RutaId','Ruta','UltPago','FechaReg','FechaVence','DiasAtraso','IdEmpresa'
    ];
    fputcsv($out,$headers);

    $sql = "
      SELECT
        c.id,
        c.Cliente AS ClienteId,
        cl.Cve_Clte,
        d.clave_destinatario,
        d.razonsocial,
        c.Documento,
        c.TipoDoc,
        ROUND(IFNULL(c.Saldo,0),2) AS Saldo,
        ROUND(IFNULL(ab.Abonos,0),2) AS Abonos,
        ROUND(IFNULL(c.Saldo,0) - IFNULL(ab.Abonos,0),2) AS SaldoNeto,
        c.Status,
        c.RutaId,
        r.descripcion AS Ruta,
        c.UltPago,
        c.FechaReg,
        c.FechaVence,
        CASE
          WHEN c.FechaVence IS NULL THEN NULL
          ELSE DATEDIFF(CURDATE(), DATE(c.FechaVence))
        END AS DiasAtraso,
        c.IdEmpresa
      FROM Cobranza c
      LEFT JOIN c_destinatarios d ON d.id_destinatario = c.Cliente
      LEFT JOIN c_cliente cl ON cl.Cve_Clte = d.Cve_Clte
      LEFT JOIN t_ruta r ON r.ID_Ruta = c.RutaId
      LEFT JOIN (
        SELECT
          Documento, IdEmpresa,
          SUM(CASE WHEN IFNULL(Cancelada,0)=0 THEN IFNULL(Abono,0) ELSE 0 END) AS Abonos
        FROM DetalleCob
        GROUP BY Documento, IdEmpresa
      ) ab ON ab.Documento = c.Documento AND ab.IdEmpresa = c.IdEmpresa
      WHERE c.Cliente = :clienteId
        AND IFNULL(c.Saldo,0) > 0
        AND IFNULL(c.Status,0) = 1
      ORDER BY (c.FechaVence IS NULL) ASC, c.FechaVence ASC, c.id DESC
    ";
    $st = $pdo->prepare($sql);
    $st->execute([':clienteId'=>$clienteId]);
    while($row = $st->fetch(PDO::FETCH_ASSOC)) fputcsv($out,$row);

    fclose($out); exit;
  }

  // ---- EXPORT CONSOLIDADO ----
  $headers = [
    'ClienteId','Cve_Clte','clave_destinatario','razonsocial',
    'IdEmpresa','Empresa','RutaId','Ruta',
    'DocsPendientes','DocsVencidos',
    'SaldoTotal','AbonosCliente','SaldoFinalCliente',
    'ProxVencimiento','MaxDiasAtraso','UltimoPago'
  ];
  fputcsv($out,$headers);

  $params = [];
  $where = build_where($params);

  $sql = "
    SELECT
      c.Cliente AS ClienteId,
      cl.Cve_Clte,
      d.clave_destinatario,
      d.razonsocial,
      c.IdEmpresa,
      ap.nombre AS Empresa,
      c.RutaId,
      r.descripcion AS Ruta,

      COUNT(*) AS DocsPendientes,
      SUM(CASE WHEN c.FechaVence IS NOT NULL AND DATE(c.FechaVence) < CURDATE() THEN 1 ELSE 0 END) AS DocsVencidos,

      ROUND(SUM(IFNULL(c.Saldo,0)),2) AS SaldoTotal,
      ROUND(SUM(IFNULL(ab.Abonos,0)),2) AS AbonosCliente,
      ROUND(SUM(IFNULL(c.Saldo,0) - IFNULL(ab.Abonos,0)),2) AS SaldoFinalCliente,

      MIN(CASE WHEN c.FechaVence IS NOT NULL THEN DATE(c.FechaVence) ELSE NULL END) AS ProxVencimiento,
      MAX(CASE WHEN c.FechaVence IS NOT NULL THEN DATEDIFF(CURDATE(), DATE(c.FechaVence)) ELSE NULL END) AS MaxDiasAtraso,
      MAX(CASE WHEN c.UltPago IS NOT NULL AND TRIM(c.UltPago)<>'' THEN c.UltPago ELSE NULL END) AS UltimoPago

    FROM Cobranza c
    LEFT JOIN c_destinatarios d ON d.id_destinatario = c.Cliente
    LEFT JOIN c_cliente cl ON cl.Cve_Clte = d.Cve_Clte
    LEFT JOIN t_ruta r ON r.ID_Ruta = c.RutaId
    LEFT JOIN c_almacenp ap ON ap.clave = c.IdEmpresa
    LEFT JOIN (
      SELECT
        Documento, IdEmpresa,
        SUM(CASE WHEN IFNULL(Cancelada,0)=0 THEN IFNULL(Abono,0) ELSE 0 END) AS Abonos
      FROM DetalleCob
      GROUP BY Documento, IdEmpresa
    ) ab ON ab.Documento = c.Documento AND ab.IdEmpresa = c.IdEmpresa

    $where
    GROUP BY
      c.Cliente, cl.Cve_Clte,
      c.IdEmpresa, c.RutaId,
      d.clave_destinatario, d.razonsocial,
      ap.nombre, r.descripcion
    ORDER BY SaldoFinalCliente DESC, DocsVencidos DESC
  ";

  $st = $pdo->prepare($sql);
  foreach($params as $k=>$v) $st->bindValue($k,$v);
  $st->execute();
  while($row = $st->fetch(PDO::FETCH_ASSOC)) fputcsv($out,$row);

  fclose($out); exit;
}

/* =========================
   LIST (Consolidado paginado server-side)
========================= */
if($action==='list'){
  $limit  = max(1, min(200, (int)($_GET['limit'] ?? 25)));
  $offset = max(0, (int)($_GET['offset'] ?? 0));

  $params = [];
  $where = build_where($params);

  $sqlCount = "
    SELECT COUNT(*) FROM (
      SELECT c.Cliente, c.IdEmpresa, c.RutaId
      FROM Cobranza c
      LEFT JOIN c_destinatarios d ON d.id_destinatario = c.Cliente
      LEFT JOIN c_cliente cl ON cl.Cve_Clte = d.Cve_Clte
      $where
      GROUP BY c.Cliente, c.IdEmpresa, c.RutaId
    ) t
  ";
  $stc = $pdo->prepare($sqlCount);
  foreach($params as $k=>$v) $stc->bindValue($k,$v);
  $stc->execute();
  $total = (int)$stc->fetchColumn();

  $sql = "
    SELECT
      c.Cliente AS ClienteId,
      cl.Cve_Clte,
      d.clave_destinatario,
      d.razonsocial,
      c.IdEmpresa,
      ap.nombre AS Empresa,
      c.RutaId,
      r.descripcion AS Ruta,

      COUNT(*) AS DocsPendientes,
      SUM(CASE WHEN c.FechaVence IS NOT NULL AND DATE(c.FechaVence) < CURDATE() THEN 1 ELSE 0 END) AS DocsVencidos,

      ROUND(SUM(IFNULL(c.Saldo,0)),2) AS SaldoTotal,
      ROUND(SUM(IFNULL(ab.Abonos,0)),2) AS AbonosCliente,
      ROUND(SUM(IFNULL(c.Saldo,0) - IFNULL(ab.Abonos,0)),2) AS SaldoFinalCliente,

      MIN(CASE WHEN c.FechaVence IS NOT NULL THEN DATE(c.FechaVence) ELSE NULL END) AS ProxVencimiento,
      MAX(CASE WHEN c.FechaVence IS NOT NULL THEN DATEDIFF(CURDATE(), DATE(c.FechaVence)) ELSE NULL END) AS MaxDiasAtraso,
      MAX(CASE WHEN c.UltPago IS NOT NULL AND TRIM(c.UltPago)<>'' THEN c.UltPago ELSE NULL END) AS UltimoPago

    FROM Cobranza c
    LEFT JOIN c_destinatarios d ON d.id_destinatario = c.Cliente
    LEFT JOIN c_cliente cl ON cl.Cve_Clte = d.Cve_Clte
    LEFT JOIN t_ruta r ON r.ID_Ruta = c.RutaId
    LEFT JOIN c_almacenp ap ON ap.clave = c.IdEmpresa
    LEFT JOIN (
      SELECT
        Documento, IdEmpresa,
        SUM(CASE WHEN IFNULL(Cancelada,0)=0 THEN IFNULL(Abono,0) ELSE 0 END) AS Abonos
      FROM DetalleCob
      GROUP BY Documento, IdEmpresa
    ) ab ON ab.Documento = c.Documento AND ab.IdEmpresa = c.IdEmpresa

    $where
    GROUP BY
      c.Cliente, cl.Cve_Clte,
      c.IdEmpresa, c.RutaId,
      d.clave_destinatario, d.razonsocial,
      ap.nombre, r.descripcion
    ORDER BY SaldoFinalCliente DESC, DocsVencidos DESC
    LIMIT $limit OFFSET $offset
  ";

  $st = $pdo->prepare($sql);
  foreach($params as $k=>$v) $st->bindValue($k,$v);
  $st->execute();

  echo json_encode(['rows'=>$st->fetchAll(PDO::FETCH_ASSOC),'total'=>$total]); exit;
}

/* =========================
   DOCS (detalle por cliente para modal)
========================= */
if($action==='docs'){
  $clienteId = (int)($_GET['clienteId'] ?? 0);
  if(!$clienteId){ echo json_encode(['error'=>'clienteId requerido']); exit; }

  $empresa = trim((string)($_GET['empresa'] ?? ''));

  $params = [':clienteId'=>$clienteId];
  $where = " WHERE c.Cliente = :clienteId
             AND IFNULL(c.Saldo,0) > 0
             AND IFNULL(c.Status,0) = 1 ";

  if($empresa!==''){ $where .= " AND c.IdEmpresa = :empresa "; $params[':empresa']=$empresa; }

  $sql = "
    SELECT
      c.id,
      c.Documento,
      c.TipoDoc,
      ROUND(IFNULL(c.Saldo,0),2) AS Saldo,
      ROUND(IFNULL(ab.Abonos,0),2) AS Abonos,
      ROUND(IFNULL(c.Saldo,0) - IFNULL(ab.Abonos,0),2) AS SaldoNeto,
      c.Status,
      c.RutaId,
      r.descripcion AS Ruta,
      c.UltPago,
      c.FechaReg,
      c.FechaVence,
      CASE
        WHEN c.FechaVence IS NULL THEN NULL
        ELSE DATEDIFF(CURDATE(), DATE(c.FechaVence))
      END AS DiasAtraso
    FROM Cobranza c
    LEFT JOIN t_ruta r ON r.ID_Ruta = c.RutaId
    LEFT JOIN (
      SELECT
        Documento, IdEmpresa,
        SUM(CASE WHEN IFNULL(Cancelada,0)=0 THEN IFNULL(Abono,0) ELSE 0 END) AS Abonos
      FROM DetalleCob
      GROUP BY Documento, IdEmpresa
    ) ab ON ab.Documento = c.Documento AND ab.IdEmpresa = c.IdEmpresa
    $where
    ORDER BY (c.FechaVence IS NULL) ASC, c.FechaVence ASC, c.id DESC
    LIMIT 500
  ";

  $st = $pdo->prepare($sql);
  foreach($params as $k=>$v) $st->bindValue($k,$v);
  $st->execute();

  echo json_encode(['rows'=>$st->fetchAll(PDO::FETCH_ASSOC)]); exit;
}

echo json_encode(['error'=>'Acción no válida']); exit;
