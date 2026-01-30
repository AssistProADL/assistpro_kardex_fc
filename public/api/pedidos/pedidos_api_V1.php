<?php
// public/api/pedidos/pedidos_api.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) { @session_start(); }

// ROOT del proyecto (pedidos -> api -> public -> raíz)
$root = realpath(__DIR__ . '/../../../');
if (!$root) {
  http_response_code(500);
  echo json_encode(['ok'=>0,'error'=>'No se pudo resolver ROOT del proyecto'], JSON_UNESCAPED_UNICODE);
  exit;
}

require_once $root . '/app/db.php';

// ---------- helpers ----------
function jexit(array $arr, int $code=200): void {
  http_response_code($code);
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}
function get_json_body(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw ?: '[]', true);
  return is_array($data) ? $data : [];
}

// Acción
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$action = strtolower(trim((string)$action));

// Alias opcionales
if ($action === 'cliente') $action = 'clientes';
if ($action === 'producto' || $action === 'productos') $action = 'articulos';

try {
  /** @var PDO $pdo */
  $pdo = db(); // si tu db.php no expone db(), dime y lo ajusto al patrón $pdo global
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
  jexit(['ok'=>0,'error'=>'No hay conexión a BD','detalle'=>$e->getMessage()], 500);
}

try {

  // ==========================
  // EMPRESAS (si no existe c_compania, regresamos demo)
  // ==========================
  if ($action === 'empresas') {
    try {
      $st = $pdo->query("SELECT id_compania AS id, nombre AS nombre FROM c_compania WHERE COALESCE(activo,1)=1 ORDER BY nombre");
      $rows = $st->fetchAll(PDO::FETCH_ASSOC);
      jexit(['ok'=>1,'rows'=>$rows]);
    } catch (Throwable $e) {
      jexit(['ok'=>1,'rows'=>[
        ['id'=>1,'nombre'=>'FOAM CREATIONS MEXICO SA DE CV']
      ],'nota'=>'c_compania no disponible; usando demo']);
    }
  }

  // ==========================
  // ALMACENES (c_almacenp)
  // ==========================
  if ($action === 'almacenes') {
    $empresa = trim((string)($_GET['empresa_id'] ?? ''));
    $empresa = ctype_digit($empresa) ? (int)$empresa : 0;

    $sql = "
      SELECT a.id AS id, a.clave AS cve, a.nombre AS nombre
      FROM c_almacenp a
      WHERE COALESCE(a.Activo,1)=1
    ";

    $params = [];
    if ($empresa > 0) {
      $sql .= " AND COALESCE(a.cve_cia,0)=? ";
      $params[] = $empresa;
    }

    $sql .= " ORDER BY a.nombre ";

    $st = $pdo->prepare($sql);
    $st->execute($params);
    jexit(['ok'=>1,'rows'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
  }

  // ==========================
  // CLIENTES (c_cliente)  ✅ POSICIONAL (evita HY093)
  // ==========================
  if ($action === 'clientes') {
    $q = trim((string)($_GET['q'] ?? ''));
    $limit = (int)($_GET['limit'] ?? 20);
    if ($limit <= 0 || $limit > 50) $limit = 20;

    $qLike = '%' . $q . '%';

    $sql = "
      SELECT
        id_cliente,
        Cve_Clte,
        RazonSocial,
        RazonComercial,
        RFC,
        Id_Vendedor,
        cve_ruta,
        Cve_Almacenp
      FROM c_cliente
      WHERE COALESCE(Activo,1)=1
        AND (
          ? = '' OR
          Cve_Clte LIKE ? OR
          RazonSocial LIKE ? OR
          COALESCE(RazonComercial,'') LIKE ? OR
          COALESCE(RFC,'') LIKE ?
        )
      ORDER BY
        CASE WHEN Cve_Clte = ? THEN 0 ELSE 1 END,
        RazonSocial ASC
      LIMIT $limit
    ";

    $st = $pdo->prepare($sql);
    $st->execute([$q, $qLike, $qLike, $qLike, $qLike, $qLike, $q]);

    jexit(['ok'=>1,'rows'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
  }

  // ==========================
  // ARTICULOS (c_articulo) ✅ POSICIONAL
  // ==========================
  if ($action === 'articulos') {
    $q = trim((string)($_GET['q'] ?? ''));
    $limit = (int)($_GET['limit'] ?? 20);
    if ($limit <= 0 || $limit > 50) $limit = 20;

    $qLike = '%' . $q . '%';

    $sql = "
      SELECT
        id,
        cve_articulo,
        des_articulo,
        cve_umed,
        cve_almac,
        mav_pctiva,
        PrecioVenta,
        control_lotes,
        control_numero_series,
        barras2,
        barras3
      FROM c_articulo
      WHERE COALESCE(Activo,1)=1
        AND (
          ? = '' OR
          cve_articulo LIKE ? OR
          des_articulo LIKE ? OR
          COALESCE(barras2,'') LIKE ? OR
          COALESCE(barras3,'') LIKE ?
        )
      ORDER BY
        CASE WHEN cve_articulo = ? THEN 0 ELSE 1 END,
        des_articulo ASC
      LIMIT $limit
    ";

    $st = $pdo->prepare($sql);
    $st->execute([$q, $qLike, $qLike, $qLike, $qLike, $q]);

    jexit(['ok'=>1,'rows'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
  }

  // ==========================
  // ARTICULO INFO (1 artículo)
  // ==========================
  if ($action === 'articulo_info') {
    $cve = trim((string)($_GET['cve'] ?? ''));
    if ($cve === '') jexit(['ok'=>0,'error'=>'Falta cve'], 400);

    $sql = "
      SELECT
        id,
        cve_articulo,
        des_articulo,
        des_detallada,
        cve_umed,
        cve_almac,
        mav_pctiva,
        IEPS,
        PrecioVenta,
        control_lotes,
        control_numero_series
      FROM c_articulo
      WHERE cve_articulo=? AND COALESCE(Activo,1)=1
      LIMIT 1
    ";
    $st = $pdo->prepare($sql);
    $st->execute([$cve]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) jexit(['ok'=>0,'error'=>'Artículo no encontrado/inactivo'], 404);
    jexit(['ok'=>1,'row'=>$row]);
  }

  // ==========================
  // CREAR PEDIDO (th_pedido + td_pedido)
  // ==========================
  if ($action === 'crear') {
    $p = get_json_body();

    $cliente     = trim((string)($p['Cve_clte'] ?? ''));
    $fecPedido   = trim((string)($p['Fec_Pedido'] ?? date('Y-m-d')));
    $fecEntrega  = trim((string)($p['Fec_Entrega'] ?? $fecPedido));
    $vendedor    = trim((string)($p['cve_Vendedor'] ?? ''));
    $obs         = (string)($p['Observaciones'] ?? '');
    $cveAlmac    = trim((string)($p['cve_almac'] ?? ''));
    $cveUbic     = trim((string)($p['cve_ubicacion'] ?? ''));
    $dest        = trim((string)($p['destinatario'] ?? ''));
    $tipoVenta   = trim((string)($p['tipo_venta'] ?? 'preventa'));
    $prioridad   = (int)($p['ID_Tipoprioridad'] ?? 0);
    $rangoHora   = trim((string)($p['rango_hora'] ?? ''));
    $idem        = trim((string)($p['idempotency_key'] ?? ''));
    $partidas    = $p['partidas'] ?? [];

    if ($cliente === '' || !is_array($partidas) || count($partidas) === 0) {
      jexit(['ok'=>0,'error'=>'Datos incompletos: Cliente/Partidas'], 400);
    }

    // validar cliente activo
    $st = $pdo->prepare("SELECT 1 FROM c_cliente WHERE Cve_Clte=? AND COALESCE(Activo,1)=1 LIMIT 1");
    $st->execute([$cliente]);
    if (!$st->fetchColumn()) jexit(['ok'=>0,'error'=>'Cliente inválido/inactivo'], 400);

    $pdo->beginTransaction();

    // idempotencia por fuente_detalle
    if ($idem !== '') {
      $st = $pdo->prepare("SELECT id_pedido, Fol_folio FROM th_pedido WHERE fuente_detalle=? LIMIT 1");
      $st->execute([$idem]);
      $ex = $st->fetch(PDO::FETCH_ASSOC);
      if ($ex) {
        $pdo->commit();
        jexit(['ok'=>1,'id_pedido'=>(int)$ex['id_pedido'],'Fol_folio'=>$ex['Fol_folio'],'idempotente'=>1]);
      }
    }

    // folio
    $ymd = date('Ymd', strtotime($fecPedido));
    $st = $pdo->prepare("SELECT COUNT(*) FROM th_pedido WHERE Fec_Pedido=?");
    $st->execute([$fecPedido]);
    $seq = (int)$st->fetchColumn() + 1;
    $folio = sprintf("PED-%s-%06d", $ymd, $seq);

    // insertar encabezado
    $sqlH = "
      INSERT INTO th_pedido
      (Fol_folio,Fec_Pedido,Cve_clte,status,Fec_Entrega,cve_Vendedor,fuente_detalle,Observaciones,
       ID_Tipoprioridad,Fec_Entrada,rango_hora,cve_almac,cve_ubicacion,destinatario,Activo,tipo_venta,Cve_Usuario)
      VALUES
      (?,?,?,?,?,?,?,?,?,NOW(),?,?,?,?,?,1,?,?)
    ";
    $stH = $pdo->prepare($sqlH);
    $stH->execute([
      $folio,
      $fecPedido,
      $cliente,
      'A',
      $fecEntrega,
      ($vendedor !== '' ? $vendedor : null),
      ($idem !== '' ? $idem : null),
      ($obs !== '' ? $obs : null),
      ($prioridad > 0 ? $prioridad : null),
      ($rangoHora !== '' ? $rangoHora : null),
      ($cveAlmac !== '' ? $cveAlmac : null),
      ($cveUbic !== '' ? $cveUbic : null),
      ($dest !== '' ? $dest : null),
      ($tipoVenta !== '' ? $tipoVenta : null),
      ($_SESSION['usuario'] ?? null),
    ]);

    $idPedido = (int)$pdo->lastInsertId();

    // insertar detalle
    $sqlD = "
      INSERT INTO td_pedido
      (Fol_folio,Cve_articulo,Num_cantidad,id_unimed,status,itemPos,cve_lote,Num_revisadas,Activo,
       Precio_unitario,Desc_Importe,IVA,Fec_Entrega,Proyecto)
      VALUES
      (?,?,?,?,?,?,?,0,1,?,?,?,?,?)
    ";
    $stD = $pdo->prepare($sqlD);

    $pos = 1;
    foreach ($partidas as $r) {
      $art  = trim((string)($r['Cve_articulo'] ?? ''));
      $qty  = (float)($r['Num_cantidad'] ?? 0);
      $uom  = isset($r['id_unimed']) ? (int)$r['id_unimed'] : null;
      $lote = trim((string)($r['cve_lote'] ?? ''));
      $precio = array_key_exists('Precio_unitario',$r) ? (float)$r['Precio_unitario'] : null;
      $desc   = array_key_exists('Desc_Importe',$r) ? (float)$r['Desc_Importe'] : 0.0;
      $iva    = array_key_exists('IVA',$r) ? (float)$r['IVA'] : null;
      $proy   = trim((string)($r['Proyecto'] ?? ''));

      if ($art === '' || $qty <= 0) throw new RuntimeException("Partida inválida (artículo/cantidad)");

      // defaults del artículo
      $stA = $pdo->prepare("SELECT cve_umed, PrecioVenta, mav_pctiva, control_lotes FROM c_articulo WHERE cve_articulo=? AND COALESCE(Activo,1)=1 LIMIT 1");
      $stA->execute([$art]);
      $a = $stA->fetch(PDO::FETCH_ASSOC);
      if (!$a) throw new RuntimeException("Artículo inválido/inactivo: $art");

      if ($uom === null && $a['cve_umed'] !== null) $uom = (int)$a['cve_umed'];
      if ($precio === null) $precio = (float)($a['PrecioVenta'] ?? 0);
      if ($iva === null) $iva = (float)($a['mav_pctiva'] ?? 0);

      if (($a['control_lotes'] ?? '') === 'S' && $lote === '') {
        throw new RuntimeException("Artículo requiere lote: $art");
      }

      $stD->execute([
        $folio,
        $art,
        $qty,
        $uom,
        'A',
        $pos,
        ($lote !== '' ? $lote : null),
        $precio,
        $desc,
        $iva,
        $fecEntrega,
        ($proy !== '' ? $proy : null),
      ]);

      $pos++;
    }

    $pdo->commit();
    jexit(['ok'=>1,'id_pedido'=>$idPedido,'Fol_folio'=>$folio]);
  }

  // ==========================
  // CONSULTAR PEDIDO
  // ==========================
  if ($action === 'consultar') {
    $id = (int)($_GET['id_pedido'] ?? 0);
    $folio = trim((string)($_GET['Fol_folio'] ?? ''));

    if ($id <= 0 && $folio === '') jexit(['ok'=>0,'error'=>'Falta id_pedido o Fol_folio'], 400);

    if ($id > 0) {
      $st = $pdo->prepare("SELECT * FROM th_pedido WHERE id_pedido=? LIMIT 1");
      $st->execute([$id]);
    } else {
      $st = $pdo->prepare("SELECT * FROM th_pedido WHERE Fol_folio=? LIMIT 1");
      $st->execute([$folio]);
    }
    $h = $st->fetch(PDO::FETCH_ASSOC);
    if (!$h) jexit(['ok'=>0,'error'=>'Pedido no encontrado'], 404);

    $st = $pdo->prepare("SELECT * FROM td_pedido WHERE Fol_folio=? ORDER BY itemPos ASC, id ASC");
    $st->execute([$h['Fol_folio']]);
    $d = $st->fetchAll(PDO::FETCH_ASSOC);

    jexit(['ok'=>1,'header'=>$h,'detail'=>$d]);
  }

  // ==========================
  // LISTAR PEDIDOS
  // ==========================
  if ($action === 'listar') {
    $status = trim((string)($_GET['status'] ?? 'A'));
    $desde  = trim((string)($_GET['desde'] ?? date('Y-m-01')));
    $hasta  = trim((string)($_GET['hasta'] ?? date('Y-m-d')));
    $clte   = trim((string)($_GET['Cve_clte'] ?? ''));

    $sql = "
      SELECT
        h.id_pedido, h.Fol_folio, h.Fec_Pedido, h.Fec_Entrega, h.status,
        h.Cve_clte, c.RazonSocial, h.cve_almac, h.destinatario
      FROM th_pedido h
      LEFT JOIN c_cliente c ON c.Cve_Clte = h.Cve_clte
      WHERE h.Fec_Pedido BETWEEN ? AND ?
    ";
    $p = [$desde, $hasta];

    if ($status !== '') { $sql .= " AND h.status=? "; $p[] = $status; }
    if ($clte !== '') { $sql .= " AND h.Cve_clte=? "; $p[] = $clte; }

    $sql .= " ORDER BY h.Fec_Pedido DESC, h.id_pedido DESC LIMIT 500 ";

    $st = $pdo->prepare($sql);
    $st->execute($p);

    jexit(['ok'=>1,'rows'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
  }

  jexit(['ok'=>0,'error'=>'Acción no soportada','debug'=>['action'=>$action,'get'=>$_GET]], 400);

} catch (Throwable $e) {
  if ($pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
  jexit(['ok'=>0,'error'=>'Error servidor','detalle'=>$e->getMessage(),'debug'=>['action'=>$action,'get'=>$_GET]], 500);
}
