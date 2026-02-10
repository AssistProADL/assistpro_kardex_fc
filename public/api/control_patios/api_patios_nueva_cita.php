<?php
// public/api/control_patios/api_patios_nueva_cita.php
declare(strict_types=1);

require_once __DIR__ . '/../../api/api_base.php';
require_once __DIR__ . '/../../api/api_siempre.php';
require_once __DIR__ . '/../../../app/db.php';

header('Content-Type: application/json; charset=utf-8');

function json_ok($data = []) { echo json_encode(["ok"=>true] + $data); exit; }
function json_err($msg, $extra = []) { echo json_encode(["ok"=>false, "error"=>$msg] + $extra); exit; }

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err("Método no permitido", ["code"=>405]);

  // Acepta JSON o form-urlencoded
  $raw = file_get_contents('php://input');
  $json = json_decode($raw, true);
  $p = is_array($json) ? $json : $_POST;

  $empresa_id     = trim((string)($p['empresa_id'] ?? ''));
  $almacenp_id    = trim((string)($p['almacenp_id'] ?? ''));
  $tipo_operacion = trim((string)($p['tipo_operacion'] ?? 'RECEPCION')); // RECEPCION|EMBARQUE|MIXTA
  $ventana_ini    = trim((string)($p['ventana_inicio'] ?? ''));
  $ventana_fin    = trim((string)($p['ventana_fin'] ?? ''));

  $prioridad      = (int)($p['prioridad'] ?? 3);
  $id_transporte  = (int)($p['id_transporte'] ?? 0);

  $id_proveedor   = (int)($p['id_proveedor'] ?? 0);
  $id_cliente     = (int)($p['id_cliente'] ?? 0);

  $referencia_doc = trim((string)($p['referencia_doc'] ?? ''));
  $comentarios    = trim((string)($p['comentarios'] ?? ''));

  if ($empresa_id === '') json_err("empresa_id es obligatorio");
  if ($almacenp_id === '') json_err("almacenp_id es obligatorio");
  if ($ventana_ini === '' || $ventana_fin === '') json_err("ventana_inicio y ventana_fin son obligatorios");

  $iniTs = strtotime($ventana_ini);
  $finTs = strtotime($ventana_fin);
  if (!$iniTs || !$finTs) json_err("Formato de ventana inválido (usa datetime)");
  if ($finTs <= $iniTs) json_err("ventana_fin debe ser mayor a ventana_inicio");

  // Regla: al menos proveedor o cliente
  if ($id_proveedor <= 0 && $id_cliente <= 0) {
    json_err("Debes indicar id_proveedor o id_cliente");
  }

  // Normaliza prioridad 1..3
  if ($prioridad < 1) $prioridad = 1;
  if ($prioridad > 3) $prioridad = 3;

  // Normaliza tipo_operacion
  $validTipos = ['RECEPCION','EMBARQUE','MIXTA'];
  if (!in_array($tipo_operacion, $validTipos, true)) json_err("tipo_operacion inválido");

  // Usuario (ajusta a tu sesión real)
  $usuario = $_SESSION['usuario'] ?? 'system';

  db_exec("
    INSERT INTO t_patio_cita
      (id_transporte, empresa_id, almacenp_id, tipo_operacion,
       ventana_inicio, ventana_fin, prioridad, estatus,
       referencia_doc, id_cliente, id_proveedor, comentarios,
       usuario_crea, fecha_crea)
    VALUES
      (?, ?, ?, ?,
       ?, ?, ?, 'PROGRAMADA',
       ?, ?, ?, ?,
       ?, NOW())
  ", [
    $id_transporte > 0 ? $id_transporte : null,
    $empresa_id,
    $almacenp_id,
    $tipo_operacion,
    date('Y-m-d H:i:s', $iniTs),
    date('Y-m-d H:i:s', $finTs),
    $prioridad,
    ($referencia_doc !== '' ? $referencia_doc : null),
    ($id_cliente > 0 ? $id_cliente : null),
    ($id_proveedor > 0 ? $id_proveedor : null),
    ($comentarios !== '' ? $comentarios : null),
    $usuario
  ]);

  $id_cita = (int)db_one("SELECT LAST_INSERT_ID() AS id", [])['id'];

  json_ok(["id_cita"=>$id_cita, "msg"=>"Cita creada"]);

} catch (Throwable $e) {
  json_err("Error: ".$e->getMessage());
}
