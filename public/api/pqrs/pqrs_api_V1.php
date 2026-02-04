<?php
// =====================================================
// PQRS API v2
// Ruta: /public/pqrs/pqrs_api.php
// =====================================================
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) { @session_start(); }

require_once __DIR__ . '/../../app/db.php';
db_pdo();
global $pdo;

function jexit(array $arr, int $code=200): void {
  http_response_code($code);
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

function q(string $k, $default='') {
  return isset($_GET[$k]) ? trim((string)$_GET[$k]) : $default;
}

function folio_next(PDO $pdo): string {
  // Folio: PQ-AAAAMMDD-0001
  $ymd = date('Ymd');

  $pdo->beginTransaction();
  try {
    $st = $pdo->prepare("SELECT seq FROM pqrs_folio_seq WHERE ymd=? FOR UPDATE");
    $st->execute([$ymd]);
    $seq = $st->fetchColumn();

    if ($seq === false) {
      $seqN = 1;
      $stI = $pdo->prepare("INSERT INTO pqrs_folio_seq(ymd, seq) VALUES(?,?)");
      $stI->execute([$ymd, $seqN]);
    } else {
      $seqN = (int)$seq + 1;
      $stU = $pdo->prepare("UPDATE pqrs_folio_seq SET seq=? WHERE ymd=?");
      $stU->execute([$seqN, $ymd]);
    }

    $pdo->commit();
    return sprintf("PQ-%s-%04d", $ymd, $seqN);
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // fallback si algo raro pasa:
    return "PQ-$ymd-" . str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT);
  }
}

$action = strtolower(q('action',''));

try {

  // ----------------------------
  // Catalogos
  // ----------------------------
  if ($action === 'catalogos') {
    $status = $pdo->query("SELECT clave,nombre,es_final,orden FROM pqrs_cat_status WHERE activo=1 ORDER BY orden ASC")->fetchAll(PDO::FETCH_ASSOC);
    $tipo   = $pdo->query("SELECT clave,nombre FROM pqrs_cat_tipo WHERE activo=1 ORDER BY clave")->fetchAll(PDO::FETCH_ASSOC);
    $ref    = $pdo->query("SELECT clave,nombre FROM pqrs_cat_ref_tipo WHERE activo=1 ORDER BY clave")->fetchAll(PDO::FETCH_ASSOC);

    // motivos por tipo
    $m = $pdo->query("SELECT id_motivo,tipo,clave,nombre FROM pqrs_cat_motivo WHERE activo=1 ORDER BY tipo, nombre")->fetchAll(PDO::FETCH_ASSOC);
    $motivos = ['REGISTRO'=>[], 'CIERRE'=>[], 'NO_PROCEDE'=>[]];
    foreach ($m as $r) { $motivos[$r['tipo']][] = $r; }

    jexit(['ok'=>1,'status'=>$status,'tipo'=>$tipo,'ref'=>$ref,'motivos'=>$motivos]);
  }

  // ----------------------------
  // Folio next
  // ----------------------------
  if ($action === 'folio_next') {
    jexit(['ok'=>1,'folio'=>folio_next($pdo)]);
  }

  // ----------------------------
  // Listar pedidos por cliente (dependiente UI)
  // Nota: usamos th_pedido directo para filtrar por Cve_clte
  // ----------------------------
  if ($action === 'pedidos_by_cliente') {
    $cve = q('cve_clte','');
    $term = q('q','');
    $limit = (int)q('limit','30');
    if ($limit <= 0 || $limit > 100) $limit = 30;

    if ($cve === '') jexit(['ok'=>1,'rows'=>[]]);

    $sql = "
      SELECT Fol_folio, Fec_Pedido, status
      FROM th_pedido
      WHERE Cve_clte = ?
        AND (?='' OR Fol_folio LIKE ?)
      ORDER BY id_pedido DESC
      LIMIT $limit
    ";
    $like = "%$term%";
    $st = $pdo->prepare($sql);
    $st->execute([$cve, $term, $like]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    jexit(['ok'=>1,'rows'=>$rows]);
  }

  // ----------------------------
  // Crear caso
  // ----------------------------
  if ($action === 'crear') {
    // input por POST (form) o JSON
    $data = $_POST;
    if (!$data) {
      $raw = file_get_contents('php://input');
      $j = json_decode($raw ?: '{}', true);
      if (is_array($j)) $data = $j;
    }

    $cve_clte  = trim((string)($data['cve_clte'] ?? ''));
    $cve_alm   = trim((string)($data['cve_almacen'] ?? ''));
    $tipo      = trim((string)($data['tipo'] ?? ''));
    $ref_tipo  = trim((string)($data['ref_tipo'] ?? ''));
    $ref_folio = trim((string)($data['ref_folio'] ?? ''));

    $reporta_nombre   = trim((string)($data['reporta_nombre'] ?? ''));
    $reporta_contacto = trim((string)($data['reporta_contacto'] ?? ''));
    $reporta_cargo    = trim((string)($data['reporta_cargo'] ?? ''));

    $responsable_recibo = trim((string)($data['responsable_recibo'] ?? ''));
    $responsable_accion = trim((string)($data['responsable_accion'] ?? ''));

    $asunto = trim((string)($data['asunto'] ?? ''));
    $descripcion = trim((string)($data['descripcion'] ?? ''));

    $status_clave = trim((string)($data['status_clave'] ?? 'NUEVA'));

    $motivo_registro_id  = (int)($data['motivo_registro_id'] ?? 0);
    $motivo_registro_txt = trim((string)($data['motivo_registro_txt'] ?? ''));

    $susceptible_cobro = (int)($data['susceptible_cobro'] ?? 0) ? 1 : 0;
    $monto_estimado    = trim((string)($data['monto_estimado'] ?? ''));

    // Validaciones ejecutivas (lo mínimo robusto)
    if ($cve_clte === '') jexit(['ok'=>0,'error'=>'Cliente es obligatorio'], 400);
    if ($cve_alm === '') jexit(['ok'=>0,'error'=>'Almacén/CEDIS es obligatorio'], 400);
    if (!in_array($tipo, ['P','Q','R','S'], true)) jexit(['ok'=>0,'error'=>'Tipo PQRS inválido'], 400);
    if ($ref_tipo === '') jexit(['ok'=>0,'error'=>'Tipo de referencia es obligatorio'], 400);
    if ($ref_folio === '') jexit(['ok'=>0,'error'=>'Folio de referencia es obligatorio'], 400);
    if ($reporta_nombre === '') jexit(['ok'=>0,'error'=>'Quién reporta es obligatorio'], 400);
    if ($responsable_recibo === '') jexit(['ok'=>0,'error'=>'Responsable interno (recibe) es obligatorio'], 400);
    if ($descripcion === '') jexit(['ok'=>0,'error'=>'Descripción es obligatoria'], 400);

    // Validación de referencia si ref_tipo=PEDIDO
    if (strtoupper($ref_tipo) === 'PEDIDO') {
      $st = $pdo->prepare("SELECT 1 FROM th_pedido WHERE Fol_folio=? AND Cve_clte=? LIMIT 1");
      $st->execute([$ref_folio, $cve_clte]);
      if (!$st->fetchColumn()) {
        jexit(['ok'=>0,'error'=>'El pedido no existe o no pertenece al cliente seleccionado'], 400);
      }
    }

    // Validación de almacén (opcional pero recomendable)
    $stA = $pdo->prepare("SELECT 1 FROM c_almacenp WHERE clave=? AND COALESCE(Activo,1)=1 LIMIT 1");
    $stA->execute([$cve_alm]);
    if (!$stA->fetchColumn()) {
      jexit(['ok'=>0,'error'=>'Almacén/CEDIS inválido'], 400);
    }

    $folio = folio_next($pdo);
    $creado_por = (string)($_SESSION['usuario'] ?? '');

    $monto = null;
    if ($monto_estimado !== '') {
      $monto = (float)str_replace([',','$',' '], '', $monto_estimado);
    }

    $pdo->beginTransaction();
    try {
      $sql = "
        INSERT INTO pqrs_case(
          fol_pqrs, cve_clte, cve_almacen, tipo,
          ref_tipo, ref_folio,
          reporta_nombre, reporta_contacto, reporta_cargo,
          responsable_recibo, responsable_accion,
          asunto, descripcion,
          status_clave,
          motivo_registro_id, motivo_registro_txt,
          susceptible_cobro, monto_estimado,
          creado_por
        ) VALUES (
          :fol, :clte, :alm, :tipo,
          :ref_tipo, :ref_folio,
          :rep_nom, :rep_con, :rep_car,
          :resp_rec, :resp_acc,
          :asunto, :desc,
          :status,
          :mot_id, :mot_txt,
          :cobro, :monto,
          :creado
        )
      ";
      $st = $pdo->prepare($sql);
      $st->execute([
        ':fol'=>$folio, ':clte'=>$cve_clte, ':alm'=>$cve_alm, ':tipo'=>$tipo,
        ':ref_tipo'=>strtoupper($ref_tipo), ':ref_folio'=>$ref_folio,
        ':rep_nom'=>$reporta_nombre,
        ':rep_con'=>($reporta_contacto!=='' ? $reporta_contacto : null),
        ':rep_car'=>($reporta_cargo!=='' ? $reporta_cargo : null),
        ':resp_rec'=>$responsable_recibo,
        ':resp_acc'=>($responsable_accion!=='' ? $responsable_accion : null),
        ':asunto'=>($asunto!=='' ? $asunto : null),
        ':desc'=>$descripcion,
        ':status'=>($status_clave!=='' ? $status_clave : 'NUEVA'),
        ':mot_id'=>($motivo_registro_id>0 ? $motivo_registro_id : null),
        ':mot_txt'=>($motivo_registro_txt!=='' ? $motivo_registro_txt : null),
        ':cobro'=>$susceptible_cobro,
        ':monto'=>$monto,
        ':creado'=>($creado_por!=='' ? $creado_por : null),
      ]);

      $id_case = (int)$pdo->lastInsertId();

      // event: ALTA
      $stE = $pdo->prepare("INSERT INTO pqrs_event(id_case, evento, detalle, usuario) VALUES(?,?,?,?)");
      $stE->execute([$id_case, 'ALTA', 'Alta de caso', ($creado_por!==''?$creado_por:null)]);

      $pdo->commit();
      jexit(['ok'=>1,'id_case'=>$id_case,'folio'=>$folio]);

    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      jexit(['ok'=>0,'error'=>'Error al crear','detalle'=>$e->getMessage()], 500);
    }
  }

  // ----------------------------
  // Obtener detalle
  // ----------------------------
  if ($action === 'get') {
    $id = (int)q('id_case','0');
    if ($id<=0) jexit(['ok'=>0,'error'=>'id_case requerido'], 400);

    $st = $pdo->prepare("SELECT * FROM pqrs_case WHERE id_case=? LIMIT 1");
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) jexit(['ok'=>0,'error'=>'No encontrado'], 404);

    $ev = $pdo->prepare("SELECT * FROM pqrs_event WHERE id_case=? ORDER BY id_event DESC");
    $ev->execute([$id]);

    jexit(['ok'=>1,'case'=>$row,'events'=>$ev->fetchAll(PDO::FETCH_ASSOC)]);
  }

  // ----------------------------
  // Listar con filtros
  // ----------------------------
  if ($action === 'list') {
    $status = q('status','');
    $clte   = q('cve_clte','');
    $ref    = q('ref_folio','');
    $desde  = q('desde','');
    $hasta  = q('hasta','');

    $sql = "SELECT id_case, fol_pqrs, cve_clte, cve_almacen, tipo, ref_tipo, ref_folio, status_clave, creado_en
            FROM pqrs_case WHERE 1=1 ";
    $p = [];

    if ($status !== '') { $sql .= " AND status_clave=? "; $p[] = $status; }
    if ($clte !== '') { $sql .= " AND cve_clte=? "; $p[] = $clte; }
    if ($ref !== '') { $sql .= " AND ref_folio LIKE ? "; $p[] = "%$ref%"; }

    if ($desde !== '') { $sql .= " AND DATE(creado_en) >= ? "; $p[] = $desde; }
    if ($hasta !== '') { $sql .= " AND DATE(creado_en) <= ? "; $p[] = $hasta; }

    $sql .= " ORDER BY id_case DESC LIMIT 500";

    $st = $pdo->prepare($sql);
    $st->execute($p);
    jexit(['ok'=>1,'rows'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
  }

  jexit(['ok'=>0,'error'=>'Acción no soportada','debug'=>['action'=>$action]], 400);

} catch (Throwable $e) {
  jexit(['ok'=>0,'error'=>'Error servidor','detalle'=>$e->getMessage()], 500);
}
