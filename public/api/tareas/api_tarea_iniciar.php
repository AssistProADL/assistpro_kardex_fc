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

  $empresa_id=(int)must($data,'empresa_id','Falta empresa_id');
  $tipo_tarea=(string)must($data,'tipo_tarea','Falta tipo_tarea');
  $usuario=(string)must($data,'usuario','Falta usuario');

  $referencia=$data['referencia'] ?? null;

  $cve_almac=$data['cve_almac'] ?? null;
  $idy_ubica_origen=$data['idy_ubica_origen'] ?? null;
  $idy_ubica_destino=$data['idy_ubica_destino'] ?? null;
  $bl_origen=$data['bl_origen'] ?? null;
  $bl_destino=$data['bl_destino'] ?? null;
  $lp_tarima=$data['lp_tarima'] ?? null;
  $lp_contenedor=$data['lp_contenedor'] ?? null;

  $hora_inicio=$data['hora_inicio'] ?? now_dt();

  // Guardamos payload completo como LONGTEXT (no JSON type)
  $payload_inicio = json_encode($data, JSON_UNESCAPED_UNICODE);

  $st=$pdo->prepare("
    INSERT INTO t_tareas
    (empresa_id,tipo_tarea,referencia,usuario,
     cve_almac,idy_ubica_origen,idy_ubica_destino,bl_origen,bl_destino,
     lp_tarima,lp_contenedor,
     hora_inicio,estado,payload_inicio,updated_at)
    VALUES
    (?,?,?,?,?,?,?,?,?,?,?,?, 'ABIERTA', ?, NOW())
  ");

  $st->execute([
    $empresa_id,$tipo_tarea,$referencia,$usuario,
    $cve_almac,$idy_ubica_origen,$idy_ubica_destino,$bl_origen,$bl_destino,
    $lp_tarima,$lp_contenedor,
    $hora_inicio,$payload_inicio
  ]);

  $task_id=(int)$pdo->lastInsertId();

  jexit(200,[
    'status'=>'OK',
    'task_id'=>$task_id,
    'hora_inicio'=>$hora_inicio,
    'estado'=>'ABIERTA'
  ]);

}catch(Throwable $e){
  jexit(500,['error'=>$e->getMessage()]);
}
