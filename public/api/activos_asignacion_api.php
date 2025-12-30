<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

function s($v){ $v = trim((string)$v); return $v==='' ? null : $v; }
function i($v){ return ($v==='' || $v===null) ? null : (int)$v; }

try {

  /* =========================
     CATÁLOGOS (Modal)
     ========================= */
  if ($action === 'catalogos') {

    // Activos
    $activos = db_all("
      SELECT a.id_activo, a.num_serie, a.tipo_activo, a.marca, a.modelo, a.descripcion, a.estatus, a.activo
      FROM c_activos a
      WHERE a.deleted_at IS NULL
      ORDER BY a.id_activo DESC
      LIMIT 500
    ");

    // Clientes (OJO: en BD es Cve_Clte + RazonSocial)
    $clientes = db_all("
      SELECT c.id_cliente,
             c.Cve_Clte AS cve_clte,
             c.RazonSocial AS razonsocial
      FROM c_cliente c
      ORDER BY c.RazonSocial
      LIMIT 1000
    ");

    // Destinatarios (OJO: en BD es Cve_Clte)
    $dest = db_all("
      SELECT d.id_destinatario,
             d.Cve_Clte AS cve_clte,
             d.razonsocial,
             d.direccion,
             d.ciudad,
             d.estado,
             d.dir_principal
      FROM c_destinatarios d
      ORDER BY d.razonsocial
      LIMIT 2000
    ");

    // Almacenes (c_almacenp: id, clave, nombre)
    $alm = db_all("
      SELECT a.id, a.clave, a.nombre
      FROM c_almacenp a
      ORDER BY a.nombre
      LIMIT 1000
    ");

    echo json_encode(['ok'=>1, 'activos'=>$activos, 'clientes'=>$clientes, 'destinatarios'=>$dest, 'almacenes'=>$alm], JSON_UNESCAPED_UNICODE);
    exit;
  }

  /* =========================
     LISTADO
     ========================= */
  if ($action === 'list') {
    $q = s($_GET['q'] ?? null);
    $soloVigentes = (int)($_GET['vigentes'] ?? 1);

    $where = ["u.deleted_at IS NULL"];
    $params = [];

    if ($soloVigentes === 1) $where[] = "u.vigencia = 1";

    if ($q) {
      $where[] = "(
        a.num_serie LIKE :q OR a.marca LIKE :q OR a.modelo LIKE :q OR a.descripcion LIKE :q
        OR c.RazonSocial LIKE :q OR d.razonsocial LIKE :q
      )";
      $params[':q'] = "%$q%";
    }

    $wsql = "WHERE " . implode(" AND ", $where);

    $rows = db_all("
      SELECT
        u.id,
        u.id_activo,
        a.num_serie,
        a.tipo_activo,
        a.marca,
        a.modelo,
        a.descripcion,
        a.estatus,
        u.cve_cia,
        u.id_almacenp,
        ap.clave AS almacen_clave,
        ap.nombre AS almacen_nombre,
        u.id_cliente,
        c.Cve_Clte AS cve_clte,
        c.RazonSocial AS cliente_nombre,
        u.id_destinatario,
        d.razonsocial AS destinatario_nombre,
        u.latitud,
        u.longitud,
        u.vigencia,
        u.fecha_desde,
        u.fecha_hasta
      FROM t_activo_ubicacion u
      LEFT JOIN c_activos a       ON a.id_activo = u.id_activo
      LEFT JOIN c_cliente c       ON c.id_cliente = u.id_cliente
      LEFT JOIN c_destinatarios d ON d.id_destinatario = u.id_destinatario
      LEFT JOIN c_almacenp ap     ON ap.id = u.id_almacenp
      $wsql
      ORDER BY u.vigencia DESC, u.fecha_desde DESC, u.id DESC
      LIMIT 1500
    ", $params);

    echo json_encode(['ok'=>1, 'rows'=>$rows], JSON_UNESCAPED_UNICODE);
    exit;
  }

  /* =========================
     GET
     ========================= */
  if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $row = db_one("
      SELECT *
      FROM t_activo_ubicacion
      WHERE id = :id
      LIMIT 1
    ", [':id'=>$id]);

    echo json_encode(['ok'=>1, 'row'=>$row], JSON_UNESCAPED_UNICODE);
    exit;
  }

  /* =========================
     SAVE (cierra vigente + crea nueva)
     ========================= */
  if ($action === 'save') {
    $id_activo = (int)($_POST['id_activo'] ?? 0);
    $cve_cia = (int)($_POST['cve_cia'] ?? 0);

    $id_almacenp = s($_POST['id_almacenp'] ?? null);
    $id_cliente = i($_POST['id_cliente'] ?? null);
    $id_destinatario = i($_POST['id_destinatario'] ?? null);

    $lat = s($_POST['latitud'] ?? null);
    $lng = s($_POST['longitud'] ?? null);

    $fecha_desde = s($_POST['fecha_desde'] ?? null); // "YYYY-mm-dd HH:ii:ss" desde UI

    if ($id_activo<=0 || $cve_cia<=0) {
      echo json_encode(['ok'=>0,'error'=>'id_activo y cve_cia son obligatorios'], JSON_UNESCAPED_UNICODE);
      exit;
    }

    $pdo->beginTransaction();

    // 1) cerrar vigente
    $pdo->prepare("
      UPDATE t_activo_ubicacion
         SET vigencia = 0,
             fecha_hasta = NOW(),
             updated_at = NOW()
       WHERE id_activo = ?
         AND vigencia = 1
         AND deleted_at IS NULL
    ")->execute([$id_activo]);

    // 2) insertar nueva
    $st = $pdo->prepare("
      INSERT INTO t_activo_ubicacion
        (id_activo, cve_cia, id_almacenp, id_cliente, id_destinatario,
         latitud, longitud, vigencia, fecha_desde, created_at)
      VALUES
        (:id_activo, :cve_cia, :id_almacenp, :id_cliente, :id_destinatario,
         :latitud, :longitud, 1, :fecha_desde, NOW())
    ");

    $st->execute([
      ':id_activo'       => $id_activo,
      ':cve_cia'         => $cve_cia,
      ':id_almacenp'     => ($id_almacenp!=='' ? $id_almacenp : null),
      ':id_cliente'      => $id_cliente,
      ':id_destinatario' => $id_destinatario,
      ':latitud'         => $lat,
      ':longitud'        => $lng,
      ':fecha_desde'     => ($fecha_desde ? $fecha_desde : date('Y-m-d H:i:s')),
    ]);

    $pdo->commit();

    echo json_encode(['ok'=>1], JSON_UNESCAPED_UNICODE);
    exit;
  }

  /* =========================
     CLOSE (cierra una asignación específica)
     ========================= */
  if ($action === 'close') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id<=0) {
      echo json_encode(['ok'=>0,'error'=>'id inválido'], JSON_UNESCAPED_UNICODE);
      exit;
    }

    $pdo->prepare("
      UPDATE t_activo_ubicacion
         SET vigencia = 0,
             fecha_hasta = NOW(),
             updated_at = NOW()
       WHERE id = ?
         AND deleted_at IS NULL
    ")->execute([$id]);

    echo json_encode(['ok'=>1], JSON_UNESCAPED_UNICODE);
    exit;
  }

  echo json_encode(['ok'=>0,'error'=>'Acción no soportada'], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
  echo json_encode(['ok'=>0,'error'=>'Error servidor','detalle'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
