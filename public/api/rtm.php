<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/db.php';

$pdo = db_pdo();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
if ($action === '') {
  echo json_encode(['ok'=>false,'msg'=>'Falta parámetro action','data'=>[]]);
  exit;
}

function jexit($ok, $msg='', $data=[], $extra=[]) {
  echo json_encode(array_merge(['ok'=>$ok,'msg'=>$msg,'data'=>$data], $extra));
  exit;
}

try {

  if ($action === 'almacenes') {
    // Catálogo para filtro (clave humana + id técnico)
    $sql = "SELECT id, clave, nombre
            FROM c_almacenp
            WHERE IFNULL(Activo,1)=1
            ORDER BY clave";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    jexit(true, '', $rows);
  }

  if ($action === 'list') {
    $almac = $_GET['almac'] ?? $_POST['almac'] ?? '';
    if ($almac === '') jexit(false, 'Falta parámetro almac (clave, ej. WH8)', []);

    $page  = (int)($_GET['page'] ?? $_POST['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? $_POST['limit'] ?? 25);
    $limit = max(1, min(200, $limit));
    $page  = max(1, $page);
    $off   = ($page-1)*$limit;

    $sql = "
      SELECT
        h.Fol_Folio AS folio,
        h.tipo,
        h.Cve_Almac AS cve_almac,
        h.Fec_Entrada AS fecha_entrada,
        h.Cve_Proveedor AS id_proveedor,
        h.Proveedor AS proveedor,
        h.ID_Protocolo AS protocolo,
        h.Consec_protocolo AS consec_protocolo,
        COUNT(d.id) AS partidas,
        SUM(IFNULL(d.CantidadRecibida,0)) AS total_recibido,
        SUM(IFNULL(d.CantidadUbicada,0)) AS total_acomodado,
        (SUM(IFNULL(d.CantidadRecibida,0)) - SUM(IFNULL(d.CantidadUbicada,0))) AS pendiente,
        ROUND(
          (SUM(IFNULL(d.CantidadUbicada,0)) / NULLIF(SUM(IFNULL(d.CantidadRecibida,0)),0)) * 100
        ,2) AS avance
      FROM th_entalmacen h
      JOIN td_entalmacen d
        ON d.fol_folio = h.Fol_Folio
      WHERE IFNULL(h.Cve_Almac,'') = :almac
        AND IFNULL(d.CantidadRecibida,0) > 0
      GROUP BY
        h.Fol_Folio, h.tipo, h.Cve_Almac, h.Fec_Entrada,
        h.Cve_Proveedor, h.Proveedor, h.ID_Protocolo, h.Consec_protocolo
      HAVING avance < 100
      ORDER BY h.Fec_Entrada DESC, h.Fol_Folio DESC
      LIMIT $off, $limit
    ";

    $st = $pdo->prepare($sql);
    $st->execute([':almac'=>$almac]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    // total (para paginación)
    $sql2 = "
      SELECT COUNT(*) total FROM (
        SELECT h.Fol_Folio
        FROM th_entalmacen h
        JOIN td_entalmacen d ON d.fol_folio = h.Fol_Folio
        WHERE IFNULL(h.Cve_Almac,'') = :almac
          AND IFNULL(d.CantidadRecibida,0) > 0
        GROUP BY h.Fol_Folio
        HAVING ROUND((SUM(IFNULL(d.CantidadUbicada,0))/NULLIF(SUM(IFNULL(d.CantidadRecibida,0)),0))*100,2) < 100
      ) x
    ";
    $st2 = $pdo->prepare($sql2);
    $st2->execute([':almac'=>$almac]);
    $total = (int)$st2->fetchColumn();

    jexit(true, '', $rows, ['page'=>$page,'limit'=>$limit,'total'=>$total]);
  }

  if ($action === 'detail') {
    $folio = (int)($_GET['folio'] ?? $_POST['folio'] ?? 0);
    if ($folio <= 0) jexit(false, 'Falta parámetro folio', []);

    $sql = "
      SELECT
        d.id,
        d.fol_folio,
        d.cve_articulo,
        d.cve_lote,
        d.CantidadPedida,
        d.CantidadRecibida,
        d.CantidadUbicada,
        (IFNULL(d.CantidadRecibida,0) - IFNULL(d.CantidadUbicada,0)) AS pendiente,
        d.cve_ubicacion,
        d.fecha_inicio,
        d.fecha_fin,
        d.num_pedimento,
        d.factura_articulo
      FROM td_entalmacen d
      WHERE d.fol_folio = :folio
        AND IFNULL(d.CantidadRecibida,0) > 0
      ORDER BY d.cve_articulo, d.cve_lote
    ";
    $st = $pdo->prepare($sql);
    $st->execute([':folio'=>$folio]);
    jexit(true, '', $st->fetchAll(PDO::FETCH_ASSOC));
  }

  if ($action === 'debug_sql') {
    // Para soporte: devuelve las consultas base sin ejecutarlas
    jexit(true, '', [], [
      'sql_list'   => 'RTM list usa th_entalmacen + td_entalmacen (Recibida vs Ubicada) filtrado por Cve_Almac.',
      'sql_detail' => 'RTM detail lee td_entalmacen por fol_folio.'
    ]);
  }

  jexit(false, 'Acción no soportada', []);

} catch (Exception $e) {
  jexit(false, $e->getMessage(), []);
}
