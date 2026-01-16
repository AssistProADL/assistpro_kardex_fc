<?php
// public/api/control_patios/api_control_patios.php
declare(strict_types=1);

require_once __DIR__ . '/../../api_base.php';
require_once __DIR__ . '/../../api_siempre.php';
require_once __DIR__ . '/../../../app/db.php';

header('Content-Type: application/json; charset=utf-8');

function json_ok($data = []) { echo json_encode(["ok"=>true] + $data); exit; }
function json_err($msg, $extra = []) { echo json_encode(["ok"=>false, "error"=>$msg] + $extra); exit; }

$accion = $_REQUEST['accion'] ?? '';

try {

  if ($accion === 'tablero') {
    $empresa_id  = $_GET['empresa_id']  ?? '';
    $almacenp_id = $_GET['almacenp_id'] ?? '';

    $where = [];
    $params = [];

    if ($empresa_id !== '') { $where[] = "v.empresa_id = ?"; $params[] = $empresa_id; }
    if ($almacenp_id !== '') { $where[] = "v.almacenp_id = ?"; $params[] = $almacenp_id; }

    $sqlWhere = $where ? ("WHERE ".implode(" AND ", $where)) : "";

    $rows = db_all("
      SELECT
        v.id_visita,
        v.id_transporte,
        v.empresa_id,
        v.almacenp_id,
        v.id_anden_actual,
        v.estatus,
        v.fecha_llegada,
        v.fecha_salida,
        t.ID_Transporte AS id_transporte_cod,
        t.Nombre AS nombre_transporte,
        t.Placas AS placas
      FROM t_patio_visita v
      LEFT JOIN t_transporte t ON t.id = v.id_transporte
      $sqlWhere
      ORDER BY v.fecha_llegada DESC, v.id_visita DESC
    ", $params);

    json_ok(["data"=>$rows]);
  }

  if ($accion === 'asignar_anden') {
    $id_visita = (int)($_POST['id_visita'] ?? 0);
    $id_anden  = (int)($_POST['id_anden'] ?? 0);
    $comentario = trim((string)($_POST['comentario'] ?? ''));

    if ($id_visita <= 0) json_err("id_visita inválido");
    if ($id_anden <= 0) json_err("id_anden inválido");

    // Actualiza visita -> ASIGNADO_ANDEN
    db_exec("
      UPDATE t_patio_visita
      SET id_anden_actual = ?,
          estatus = 'ASIGNADO_ANDEN',
          usuario_asigna = COALESCE(usuario_asigna, 'system'),
          fecha_asigna = COALESCE(fecha_asigna, NOW())
      WHERE id_visita = ?
    ", [$id_anden, $id_visita]);

    // Movimiento
    db_exec("
      INSERT INTO t_patio_mov (id_visita, id_anden, estatus, fecha, usuario, comentario)
      VALUES (?, ?, 'ASIGNADO_ANDEN', NOW(), 'system', ?)
    ", [$id_visita, $id_anden, $comentario]);

    json_ok(["msg"=>"Andén asignado"]);
  }

  if ($accion === 'iniciar_operacion') {
    $id_visita = (int)($_POST['id_visita'] ?? 0);
    $comentario = trim((string)($_POST['comentario'] ?? 'Inicio operación'));

    if ($id_visita <= 0) json_err("id_visita inválido");

    // Cambia a EN_DESCARGA (si tu operación es carga, cambia a EN_CARGA)
    db_exec("
      UPDATE t_patio_visita
      SET estatus = 'EN_DESCARGA'
      WHERE id_visita = ?
    ", [$id_visita]);

    // Movimiento (anden actual)
    $v = db_one("SELECT id_anden_actual FROM t_patio_visita WHERE id_visita = ?", [$id_visita]);
    $id_anden = (int)($v['id_anden_actual'] ?? 0);

    db_exec("
      INSERT INTO t_patio_mov (id_visita, id_anden, estatus, fecha, usuario, comentario)
      VALUES (?, ?, 'EN_DESCARGA', NOW(), 'system', ?)
    ", [$id_visita, $id_anden, $comentario]);

    json_ok(["msg"=>"Operación iniciada"]);
  }

  if ($accion === 'registrar_salida') {
    $id_visita = (int)($_POST['id_visita'] ?? 0);
    $comentario = trim((string)($_POST['comentario'] ?? 'Salida'));

    if ($id_visita <= 0) json_err("id_visita inválido");

    // En la tabla estatus NO tiene SALIDA (enum), por eso solo ponemos fecha_salida + mov
    db_exec("
      UPDATE t_patio_visita
      SET fecha_salida = NOW(),
          usuario_checkout = 'system'
      WHERE id_visita = ?
    ", [$id_visita]);

    $v = db_one("SELECT id_anden_actual FROM t_patio_visita WHERE id_visita = ?", [$id_visita]);
    $id_anden = (int)($v['id_anden_actual'] ?? 0);

    db_exec("
      INSERT INTO t_patio_mov (id_visita, id_anden, estatus, fecha, usuario, comentario)
      VALUES (?, ?, 'SALIDA', NOW(), 'system', ?)
    ", [$id_visita, $id_anden, $comentario]);

    json_ok(["msg"=>"Salida registrada"]);
  }

  json_err("Acción no soportada: $accion");

} catch (Throwable $e) {
  json_err("Error: ".$e->getMessage());
}
