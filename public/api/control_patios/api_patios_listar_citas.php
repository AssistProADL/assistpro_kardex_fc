<?php
// public/api/control_patios/api_patios_confirmar_cita.php
declare(strict_types=1);

require_once __DIR__ . '/../../api/api_base.php';
require_once __DIR__ . '/../../api/api_siempre.php';
require_once __DIR__ . '/../../../app/db.php';

header('Content-Type: application/json; charset=utf-8');

function json_ok($data = []) { echo json_encode(["ok"=>true] + $data); exit; }
function json_err($msg, $extra = []) { echo json_encode(["ok"=>false, "error"=>$msg] + $extra); exit; }

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err("Método no permitido", ["code"=>405]);

  $id_cita = (int)($_POST['id_cita'] ?? 0);
  $crear_visita = (int)($_POST['crear_visita'] ?? 1); // 1 por default

  // opcional: reprogramar ventana al confirmar
  $ventana_ini = trim((string)($_POST['ventana_inicio'] ?? ''));
  $ventana_fin = trim((string)($_POST['ventana_fin'] ?? ''));

  if ($id_cita <= 0) json_err("id_cita inválido");

  $cita = db_one("SELECT * FROM t_patio_cita WHERE id_cita = ?", [$id_cita]);
  if (!$cita) json_err("La cita no existe");

  if (($cita['estatus'] ?? '') === 'CANCELADA') json_err("No puedes confirmar una cita cancelada");

  // Si mandan nueva ventana, validar
  if ($ventana_ini !== '' || $ventana_fin !== '') {
    if ($ventana_ini === '' || $ventana_fin === '') json_err("Si reprogramas, envía ventana_inicio y ventana_fin");
    $iniTs = strtotime($ventana_ini);
    $finTs = strtotime($ventana_fin);
    if (!$iniTs || !$finTs) json_err("Formato de ventana inválido");
    if ($finTs <= $iniTs) json_err("ventana_fin debe ser mayor a ventana_inicio");
  } else {
    $iniTs = strtotime((string)$cita['ventana_inicio']);
    $finTs = strtotime((string)$cita['ventana_fin']);
  }

  $usuario = $_SESSION['usuario'] ?? 'system';

  // Actualiza a CONFIRMADA
  db_exec("
    UPDATE t_patio_cita
    SET estatus = 'CONFIRMADA',
        usuario_confirma = ?,
        fecha_confirma = NOW(),
        ventana_inicio = ?,
        ventana_fin = ?,
        usuario_modifica = ?,
        fecha_modifica = NOW()
    WHERE id_cita = ?
  ", [
    $usuario,
    date('Y-m-d H:i:s', $iniTs),
    date('Y-m-d H:i:s', $finTs),
    $usuario,
    $id_cita
  ]);

  // ¿ya existe visita?
  $v = db_one("SELECT id_visita FROM t_patio_visita WHERE id_cita = ? LIMIT 1", [$id_cita]);
  $id_visita = (int)($v['id_visita'] ?? 0);

  if ($crear_visita === 1 && $id_visita <= 0) {
    // Llama al API interno "crear visita" reusando función/SQL (sin HTTP)
    // Mantenerlo simple: insert directo aquí (o puedes delegar a api_patios_crear_visita.php).
    $empresa_id   = $cita['empresa_id'];
    $almacenp_id  = $cita['almacenp_id'];
    $id_transporte = (int)($cita['id_transporte'] ?? 0);

    // Crea visita (estatus inicial operativo)
    db_exec("
      INSERT INTO t_patio_visita
        (id_cita, id_transporte, empresa_id, almacenp_id, estatus, fecha_llegada, observaciones, usuario_checkin)
      VALUES
        (?, ?, ?, ?, 'EN_PATIO', NOW(), ?, ?)
    ", [
      $id_cita,
      ($id_transporte > 0 ? $id_transporte : null),
      $empresa_id,
      $almacenp_id,
      "Creada desde confirmación de cita",
      $usuario
    ]);

    $id_visita = (int)db_one("SELECT LAST_INSERT_ID() AS id", [])['id'];

    // movimiento
    db_exec("
      INSERT INTO t_patio_mov (id_visita, id_anden, estatus, fecha, usuario, comentario)
      VALUES (?, 0, 'EN_PATIO', NOW(), ?, 'Visita creada al confirmar cita')
    ", [$id_visita, $usuario]);
  }

  json_ok(["msg"=>"Cita confirmada", "id_visita"=>$id_visita]);

} catch (Throwable $e) {
  json_err("Error: ".$e->getMessage());
}
