<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../../app/db.php';
db_pdo();
global $pdo;

/* ===================== HELPERS ===================== */
function jexit($arr, $code = 200) {
  http_response_code($code);
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

function q($k, $d = '') {
  return isset($_REQUEST[$k]) ? trim((string)$_REQUEST[$k]) : $d;
}

/* ===================== ACTION ===================== */
$action = strtolower(q('action'));

try {

  /* ======================================================
     STATUS
     ====================================================== */
  if ($action === 'status') {
    $rows = $pdo->query("
      SELECT clave AS id, nombre AS text
      FROM pqrs_cat_status
      WHERE activo = 1
      ORDER BY orden
    ")->fetchAll(PDO::FETCH_ASSOC);

    jexit(['results' => $rows]);
  }

  /* ======================================================
     REFERENCIAS POR CLIENTE
     ====================================================== */
  if ($action === 'referencias_by_cliente') {

    $cve  = q('cve_clte');
    $tipo = strtoupper(q('ref_tipo'));
    $term = q('q');
    $limit = min(max((int)q('limit',20),1),50);

    if ($cve === '' || $tipo === '') {
      jexit(['results'=>[]]);
    }

    switch ($tipo) {
      case 'PEDIDO':
        $sql = "
          SELECT Fol_folio AS id,
                 CONCAT(Fol_folio,' | ',DATE_FORMAT(Fec_Pedido,'%Y-%m-%d'),' | ',status) AS text
          FROM th_pedido
          WHERE Cve_clte = ?
            AND (?='' OR Fol_folio LIKE ?)
          ORDER BY id_pedido DESC
          LIMIT $limit
        ";
        break;

      default:
        jexit(['results'=>[]]);
    }

    $like = "%$term%";
    $st = $pdo->prepare($sql);
    $st->execute([$cve, $term, $like]);

    jexit(['results'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
  }

  /* ======================================================
     CREAR PQRS  🔥 (ESTO FALTABA)
     ====================================================== */
  if ($action === 'crear') {

    $data = $_POST ?: json_decode(file_get_contents('php://input'), true) ?: [];

    $required = [
      'cve_almacen','tipo','status_clave','cve_clte',
      'ref_tipo','ref_folio','reporta_nombre',
      'responsable_recibo','descripcion'
    ];

    foreach ($required as $k) {
      if (empty($data[$k])) {
        jexit(['error'=>"Campo obligatorio faltante: $k"],400);
      }
    }

    $folio = 'PQ-' . date('Ymd-His');
    $usuario = $_SESSION['usuario'] ?? null;

    $pdo->beginTransaction();
    try {

      $st = $pdo->prepare("
        INSERT INTO pqrs_case (
          fol_pqrs, cve_clte, cve_almacen, tipo,
          ref_tipo, ref_folio,
          reporta_nombre, reporta_contacto, reporta_cargo,
          responsable_recibo, responsable_accion,
          asunto, descripcion,
          status_clave,
          susceptible_cobro,
          creado_por
        ) VALUES (
          :folio,:clte,:alm,:tipo,
          :rt,:rf,
          :rn,:rc,:rcr,
          :rr,:ra,
          :asunto,:desc,
          :status,
          :cobro,
          :user
        )
      ");

      $st->execute([
        ':folio'=>$folio,
        ':clte'=>$data['cve_clte'],
        ':alm'=>$data['cve_almacen'],
        ':tipo'=>$data['tipo'],
        ':rt'=>$data['ref_tipo'],
        ':rf'=>$data['ref_folio'],
        ':rn'=>$data['reporta_nombre'],
        ':rc'=>$data['reporta_contacto'] ?? null,
        ':rcr'=>$data['reporta_cargo'] ?? null,
        ':rr'=>$data['responsable_recibo'],
        ':ra'=>$data['responsable_accion'] ?? null,
        ':asunto'=>$data['asunto'] ?? null,
        ':desc'=>$data['descripcion'],
        ':status'=>$data['status_clave'],
        ':cobro'=>!empty($data['susceptible_cobro']) ? 1 : 0,
        ':user'=>$usuario
      ]);

      $id = (int)$pdo->lastInsertId();

      $pdo->prepare("
        INSERT INTO pqrs_event(id_case, evento, detalle, usuario)
        VALUES (?,?,?,?)
      ")->execute([$id,'ALTA','Alta de PQRS',$usuario]);

      $pdo->commit();

      jexit(['ok'=>1,'id_case'=>$id,'folio'=>$folio]);

    } catch(Throwable $e) {
      $pdo->rollBack();
      jexit(['error'=>$e->getMessage()],500);
    }
  }

  /* ====================================================== */
  jexit(['error'=>'Acción no soportada'],400);

} catch(Throwable $e) {
  jexit(['error'=>'Error servidor','detalle'=>$e->getMessage()],500);
}
