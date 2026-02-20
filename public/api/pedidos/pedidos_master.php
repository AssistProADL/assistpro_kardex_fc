<?php
require_once __DIR__ . '/../../../app/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = db();

try {

  $idPedido = (int)($_GET['id_pedido'] ?? 0);
  $limit = (int)($_GET['limit'] ?? 20);

  if ($limit <= 0 || $limit > 200) {
    $limit = 20;
  }

  /* =========================================================
       CASO 1: PEDIDO ESPECÍFICO
    ========================================================= */

  if ($idPedido > 0) {

    /* ---------- HEADER + CLIENTE ---------- */

    $sqlHeader = "
            SELECT
                h.*,

                c.id_cliente,
                c.RazonSocial,
                c.RazonComercial,
                c.RFC,
                c.CalleNumero,
                c.Colonia,
                c.Ciudad,
                c.Estado,
                c.Pais,
                c.CodigoPostal,
                c.Telefono1,
                c.Telefono2,
                c.email_cliente,
                c.credito,
                c.latitud,
                c.longitud

            FROM th_pedido h
            LEFT JOIN c_cliente c
                ON c.Cve_Clte = h.Cve_clte
            WHERE h.id_pedido = ?
            LIMIT 1
        ";

    $st = $pdo->prepare($sqlHeader);
    $st->execute([$idPedido]);

    $header = $st->fetch(PDO::FETCH_ASSOC);

    if (!$header) {
      echo json_encode(['ok' => 0, 'error' => 'Pedido no encontrado']);
      exit;
    }

    /* ---------- DETALLE ---------- */

    $sqlDetalle = "
            SELECT
                id,
                Fol_folio,
                Cve_articulo,
                Num_cantidad,
                Precio_unitario,
                Desc_Importe,
                IVA,
                Fec_Entrega,
                Proyecto,
                itemPos
            FROM td_pedido
            WHERE Fol_folio = ?
            ORDER BY itemPos ASC
        ";

    $stDetalle = $pdo->prepare($sqlDetalle);
    $stDetalle->execute([$header['Fol_folio']]);

    $detalle = $stDetalle->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
      'ok' => 1,
      'pedido' => $header,
      'detalle' => $detalle
    ], JSON_UNESCAPED_UNICODE);

    exit;
  }

  /* =========================================================
       CASO 2: LISTADO GENERAL
    ========================================================= */

  $sql = "
        SELECT
            h.id_pedido,
            h.Fol_folio,
            h.Fec_Pedido,
            h.Fec_Entrega,
            h.status,
            h.Cve_clte,
            c.RazonSocial,
            c.Ciudad,
            c.Estado
        FROM th_pedido h
        LEFT JOIN c_cliente c
            ON c.Cve_Clte = h.Cve_clte
        ORDER BY h.id_pedido DESC
        LIMIT $limit
    ";

  $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'ok' => 1,
    'total' => count($rows),
    'rows' => $rows
  ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {

  echo json_encode([
    'ok' => 0,
    'error' => $e->getMessage()
  ]);
}
