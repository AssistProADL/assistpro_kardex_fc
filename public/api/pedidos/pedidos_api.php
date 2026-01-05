<?php
// public/api/pedidos/pedidos_api.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) { @session_start(); }

$root = realpath(__DIR__ . '/../../../');
if (!$root) {
  http_response_code(500);
  echo json_encode(['ok'=>0,'error'=>'No se pudo resolver ROOT'], JSON_UNESCAPED_UNICODE);
  exit;
}
require_once $root . '/app/db.php';

function jexit(array $a, int $code=200): void {
  http_response_code($code);
  echo json_encode($a, JSON_UNESCAPED_UNICODE);
  exit;
}
function get_json_body(): array {
  $raw = file_get_contents('php://input');
  $j = json_decode($raw ?: '[]', true);
  return is_array($j) ? $j : [];
}

$action = strtolower(trim((string)($_GET['action'] ?? $_POST['action'] ?? '')));
if ($action === '') {
  jexit(['ok'=>0,'error'=>'Acción no soportada','debug'=>['action'=>$action,'get'=>$_GET,'post'=>$_POST]], 400);
}

try {
  /** @var PDO $pdo */
  $pdo = db();
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
  jexit(['ok'=>0,'error'=>'DB','detalle'=>$e->getMessage()], 500);
}

try {

  // =========================
  // ALMACENES
  // =========================
  if ($action === 'almacenes') {
    $empresa = trim((string)($_GET['empresa_id'] ?? ''));
    $empresa = ctype_digit($empresa) ? (int)$empresa : 0;

    $sql = "
      SELECT a.id, a.clave AS cve, a.nombre
      FROM c_almacenp a
      WHERE COALESCE(a.Activo,1)=1
    ";
    $p = [];
    if ($empresa > 0) {
      $sql .= " AND (COALESCE(a.cve_cia,0)=:emp OR COALESCE(a.empresa_id,0)=:emp) ";
      $p['emp'] = $empresa;
    }
    $sql .= " ORDER BY a.nombre ";

    $st = $pdo->prepare($sql);
    $st->execute($p);
    jexit(['ok'=>1,'rows'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
  }

  // =========================
  // CLIENTES (sin HY093)
  // =========================
  if ($action === 'clientes') {
    $q = trim((string)($_GET['q'] ?? ''));
    $limit = (int)($_GET['limit'] ?? 20);
    if ($limit <= 0 || $limit > 50) $limit = 20;
    $qLike = '%' . $q . '%';

    $sql = "
      SELECT id_cliente, Cve_Clte, RazonSocial, RazonComercial, RFC
      FROM c_cliente
      WHERE COALESCE(Activo,1)=1
        AND (
          :q1='' OR
          Cve_Clte LIKE :qLike OR
          RazonSocial LIKE :qLike OR
          COALESCE(RazonComercial,'') LIKE :qLike OR
          COALESCE(RFC,'') LIKE :qLike
        )
      ORDER BY
        CASE WHEN Cve_Clte=:q2 THEN 0 ELSE 1 END,
        RazonSocial
      LIMIT $limit
    ";
    $st = $pdo->prepare($sql);
    $st->execute(['q1'=>$q,'q2'=>$q,'qLike'=>$qLike]);
    jexit(['ok'=>1,'rows'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
  }

  // =========================
  // ARTICULOS (sin HY093)
  // =========================
  if ($action === 'articulos') {
    $q = trim((string)($_GET['q'] ?? ''));
    $limit = (int)($_GET['limit'] ?? 20);
    if ($limit <= 0 || $limit > 50) $limit = 20;
    $qLike = '%' . $q . '%';

    $sql = "
      SELECT id, cve_articulo, des_articulo, cve_umed, mav_pctiva, PrecioVenta
      FROM c_articulo
      WHERE COALESCE(Activo,1)=1
        AND (
          :q1='' OR
          cve_articulo LIKE :qLike OR
          des_articulo LIKE :qLike OR
          COALESCE(barras2,'') LIKE :qLike OR
          COALESCE(barras3,'') LIKE :qLike
        )
      ORDER BY
        CASE WHEN cve_articulo=:q2 THEN 0 ELSE 1 END,
        des_articulo
      LIMIT $limit
    ";
    $st = $pdo->prepare($sql);
    $st->execute(['q1'=>$q,'q2'=>$q,'qLike'=>$qLike]);
    jexit(['ok'=>1,'rows'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
  }

  // =========================
  // PEDIDOS (tipo Picking)
  // =========================
  if ($action === 'pedidos') {

    $status   = trim((string)($_GET['status'] ?? ''));
    $almac    = trim((string)($_GET['cve_almac'] ?? ''));
    $ruta     = trim((string)($_GET['ruta'] ?? ''));
    $clte     = trim((string)($_GET['cve_clte'] ?? ''));
    $desde    = trim((string)($_GET['desde'] ?? ''));
    $hasta    = trim((string)($_GET['hasta'] ?? ''));

    $tipoPed  = trim((string)($_GET['tipo_pedido'] ?? ''));      // h.TipoPedido
    $fuente   = trim((string)($_GET['fuente'] ?? ''));           // h.fuente_detalle
    $prio     = trim((string)($_GET['prioridad'] ?? ''));        // h.ID_Tipoprioridad
    $sem      = trim((string)($_GET['semaforo'] ?? ''));         // VERDE/AMARILLO/ROJO

    $sql = "
      SELECT
        h.id_pedido,
        h.Fol_folio,
        h.Fec_Pedido,
        h.Fec_Entrega,
        h.Cve_clte,
        h.status,
        h.TipoPedido,
        h.ruta,
        h.cve_almac,
        a.clave AS clave_almacen,
        h.fuente_detalle,
        h.ID_Tipoprioridad,
        h.rango_hora,
        h.Cve_Usuario,
        h.Observaciones,

        /* Agregados estilo Picking */
        COALESCE(x.lineas,0)        AS lineas,
        COALESCE(x.piezas,0)        AS piezas,
        COALESCE(x.pzas_surtidas,0) AS pzas_surtidas,
        COALESCE(x.avance_pct,0)    AS avance_pct,
        COALESCE(x.semaforo,'ROJO') AS semaforo

      FROM th_pedido h
      LEFT JOIN c_almacenp a
        ON (a.clave = h.cve_almac OR a.id = h.cve_almac)

      LEFT JOIN (
        SELECT
          d.Fol_folio,
          COUNT(*) AS lineas,
          SUM(COALESCE(d.Num_cantidad,0)) AS piezas,
          SUM(COALESCE(d.SurtidoXPiezas,0) + COALESCE(d.SurtidoXCajas,0)) AS pzas_surtidas,
          CASE
            WHEN SUM(COALESCE(d.Num_cantidad,0)) <= 0 THEN 0
            ELSE ROUND( (SUM(COALESCE(d.SurtidoXPiezas,0) + COALESCE(d.SurtidoXCajas,0)) / SUM(COALESCE(d.Num_cantidad,0))) * 100, 2)
          END AS avance_pct,
          CASE
            WHEN SUM(COALESCE(d.Num_cantidad,0)) <= 0 THEN 'ROJO'
            WHEN SUM(COALESCE(d.SurtidoXPiezas,0) + COALESCE(d.SurtidoXCajas,0)) <= 0 THEN 'ROJO'
            WHEN (SUM(COALESCE(d.SurtidoXPiezas,0) + COALESCE(d.SurtidoXCajas,0)) / NULLIF(SUM(COALESCE(d.Num_cantidad,0)),0)) >= 1 THEN 'VERDE'
            ELSE 'AMARILLO'
          END AS semaforo
        FROM td_pedido d
        GROUP BY d.Fol_folio
      ) x ON x.Fol_folio = h.Fol_folio

      WHERE COALESCE(h.Activo,1)=1
    ";

    $p = [];

    if ($status !== '') { $sql .= " AND h.status = :st "; $p['st'] = $status; }
    if ($almac  !== '') { $sql .= " AND h.cve_almac = :alm "; $p['alm'] = $almac; }
    if ($ruta   !== '') { $sql .= " AND h.ruta = :ruta "; $p['ruta'] = $ruta; }
    if ($clte   !== '') { $sql .= " AND h.Cve_clte = :clte "; $p['clte'] = $clte; }

    if ($tipoPed !== '') { $sql .= " AND COALESCE(h.TipoPedido,'') = :tp "; $p['tp'] = $tipoPed; }
    if ($fuente  !== '') { $sql .= " AND COALESCE(h.fuente_detalle,'') = :fue "; $p['fue'] = $fuente; }
    if ($prio    !== '' && ctype_digit($prio)) { $sql .= " AND COALESCE(h.ID_Tipoprioridad,0) = :pr "; $p['pr'] = (int)$prio; }

    if ($desde !== '' && $hasta !== '') {
      $sql .= " AND h.Fec_Pedido BETWEEN :d1 AND :d2 ";
      $p['d1'] = $desde;
      $p['d2'] = $hasta;
    }

    if ($sem !== '') { $sql .= " AND COALESCE(x.semaforo,'ROJO') = :sem "; $p['sem'] = strtoupper($sem); }

    $sql .= " ORDER BY h.Fec_Pedido DESC, h.Fol_folio DESC LIMIT 25 ";

    $st = $pdo->prepare($sql);
    $st->execute($p);
    jexit(['ok'=>1,'rows'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
  }

  // =========================
  // DETALLE
  // =========================
  if ($action === 'pedido_detalle') {
    $folio = trim((string)($_GET['folio'] ?? ''));
    if ($folio === '') jexit(['ok'=>0,'error'=>'Falta folio'], 400);

    $stH = $pdo->prepare("
      SELECT *
      FROM th_pedido
      WHERE Fol_folio = :f
      LIMIT 1
    ");
    $stH->execute(['f'=>$folio]);
    $header = $stH->fetch(PDO::FETCH_ASSOC);

    $stD = $pdo->prepare("
      SELECT
        id, Fol_folio, Cve_articulo, Num_cantidad, id_unimed, cve_lote,
        SurtidoXCajas, SurtidoXPiezas, Num_revisadas, Num_Empacados, status, itemPos, Precio_unitario, Desc_Importe, IVA
      FROM td_pedido
      WHERE Fol_folio = :f
      ORDER BY itemPos ASC, id ASC
    ");
    $stD->execute(['f'=>$folio]);
    $detail = $stD->fetchAll(PDO::FETCH_ASSOC);

    jexit(['ok'=>1,'header'=>$header,'detail'=>$detail]);
  }

  // =========================
  // CREAR (para pedido.php)
  // =========================
  if ($action === 'crear') {
    $b = get_json_body();
    if (empty($b['Cve_clte'])) jexit(['ok'=>0,'error'=>'Falta cliente'], 400);
    if (empty($b['cve_almac'])) jexit(['ok'=>0,'error'=>'Falta almacén'], 400);
    if (empty($b['partidas']) || !is_array($b['partidas'])) jexit(['ok'=>0,'error'=>'Sin partidas'], 400);

    $pdo->beginTransaction();

    $folio = trim((string)($b['Fol_folio'] ?? ''));
    if ($folio === '') $folio = 'PED-' . date('Ymd-His');

    $st = $pdo->prepare("
      INSERT INTO th_pedido
      (Fol_folio, Fec_Pedido, Cve_clte, status, Fec_Entrega, cve_almac, TipoPedido, ruta, ID_Tipoprioridad, rango_hora, fuente_detalle, Observaciones, Cve_Usuario, Activo)
      VALUES
      (:f, CURDATE(), :cl, 'A', :fe, :alm, :tp, :ruta, :pr, :rh, :fu, :obs, :usr, 1)
    ");
    $st->execute([
      'f'   => $folio,
      'cl'  => $b['Cve_clte'],
      'fe'  => ($b['Fec_Entrega'] ?? date('Y-m-d')),
      'alm' => $b['cve_almac'],
      'tp'  => ($b['TipoPedido'] ?? 'PEDIDO'),
      'ruta'=> ($b['ruta'] ?? null),
      'pr'  => (int)($b['ID_Tipoprioridad'] ?? 3),
      'rh'  => ($b['rango_hora'] ?? null),
      'fu'  => ($b['fuente_detalle'] ?? 'CAPTURA'),
      'obs' => ($b['Observaciones'] ?? null),
      'usr' => ($b['Cve_Usuario'] ?? ($_SESSION['usuario'] ?? 'SYS')),
    ]);

    $pos = 1;
    $stD = $pdo->prepare("
      INSERT INTO td_pedido
      (Fol_folio, Cve_articulo, Num_cantidad, status, itemPos, Num_revisadas, Num_Empacados, Activo, Precio_unitario, Desc_Importe, IVA, id_unimed, cve_lote)
      VALUES
      (:f, :a, :q, 'A', :p, 0, 0, 1, :pu, :di, :iva, :uom, :lote)
    ");

    foreach ($b['partidas'] as $r) {
      $stD->execute([
        'f'   => $folio,
        'a'   => $r['Cve_articulo'],
        'q'   => (float)($r['Num_cantidad'] ?? 0),
        'p'   => $pos++,
        'pu'  => (float)($r['Precio_unitario'] ?? 0),
        'di'  => (float)($r['Desc_Importe'] ?? 0),
        'iva' => (float)($r['IVA'] ?? 0),
        'uom' => ($r['id_unimed'] ?? null),
        'lote'=> ($r['cve_lote'] ?? null),
      ]);
    }

    $pdo->commit();
    jexit(['ok'=>1,'Fol_folio'=>$folio]);
  }

  jexit(['ok'=>0,'error'=>'Acción no soportada','debug'=>['action'=>$action,'get'=>$_GET]], 400);

} catch (Throwable $e) {
  if ($pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
  jexit(['ok'=>0,'error'=>'Error servidor','detalle'=>$e->getMessage(),'debug'=>['action'=>$action,'get'=>$_GET]], 500);
}
