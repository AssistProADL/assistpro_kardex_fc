<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../app/db.php';
db_pdo();
global $pdo;

function jexit(int $code, array $payload){ http_response_code($code); echo json_encode($payload, JSON_UNESCAPED_UNICODE); exit; }
function read_json(): array { $raw=file_get_contents('php://input'); $d=json_decode($raw,true); if(!is_array($d)) jexit(400,['error'=>'JSON inválido']); return $d; }
function must($a,string $k,string $m){ if(!isset($a[$k])||$a[$k]==='') jexit(400,['error'=>$m,'field'=>$k]); return $a[$k]; }
function now_dt(): string { return date('Y-m-d H:i:s'); }
function table_exists(PDO $pdo,string $t): bool { $st=$pdo->prepare("SHOW TABLES LIKE ?"); $st->execute([$t]); return (bool)$st->fetchColumn(); }

try{
  if($_SERVER['REQUEST_METHOD']!=='POST') jexit(405,['error'=>'Método no permitido']);
  $data=read_json();

  if(!table_exists($pdo,'t_tareas')) jexit(500,['error'=>'No existe tabla t_tareas']);

  $task_id=(int)must($data,'task_id','Falta task_id');
  $usuario=(string)must($data,'usuario','Falta usuario');

  $hora_fin=$data['hora_fin'] ?? now_dt();
  $estado=$data['estado'] ?? 'CERRADA';
  if(!in_array($estado,['CERRADA','CANCELADA'],true)) jexit(400,['error'=>'estado inválido (CERRADA|CANCELADA)']);

  $pdo->beginTransaction();

  $st=$pdo->prepare("SELECT id,hora_inicio,estado FROM t_tareas WHERE id=? FOR UPDATE");
  $st->execute([$task_id]);
  $row=$st->fetch(PDO::FETCH_ASSOC);
  if(!$row){ $pdo->rollBack(); jexit(404,['error'=>'Tarea no existe']); }
  if($row['estado']!=='ABIERTA'){ $pdo->rollBack(); jexit(409,['error'=>'Tarea no está ABIERTA']); }

  $ini=strtotime($row['hora_inicio']);
  $fin=strtotime($hora_fin);
  if($ini===false||$fin===false||$fin<$ini){ $pdo->rollBack(); jexit(400,['error'=>'hora_fin inválida']); }
  $dur=$fin-$ini;

  $payload_cierre=json_encode($data, JSON_UNESCAPED_UNICODE);

  $up=$pdo->prepare("
    UPDATE t_tareas
    SET hora_fin=?, duracion_seg=?, estado=?, payload_cierre=?, updated_at=NOW()
    WHERE id=?
  ");
  $up->execute([$hora_fin,$dur,$estado,$payload_cierre,$task_id]);

  $pdo->commit();

  jexit(200,[
    'status'=>'OK',
    'task_id'=>$task_id,
    'hora_fin'=>$hora_fin,
    'duracion_seg'=>$dur,
    'estado'=>$estado
  ]);

}catch(Throwable $e){
  if($pdo && $pdo->inTransaction()) $pdo->rollBack();
  jexit(500,['error'=>$e->getMessage()]);
}
