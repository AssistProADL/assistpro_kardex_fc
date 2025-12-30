<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();
$action = $_GET['action'] ?? 'list';

function out($ok, $extra=[]){
  echo json_encode(array_merge(['ok'=>$ok?1:0], $extra), JSON_UNESCAPED_UNICODE);
  exit;
}

/* =========================================
   LISTAR ALERTAS RECIENTES
   ========================================= */
if ($action === 'list') {

  $cve_cia = (int)($_GET['cve_cia'] ?? 0);
  $since   = $_GET['since'] ?? null; // timestamp opcional

  if ($cve_cia <= 0) out(false, ['error'=>'cve_cia requerido']);

  $sql = "
    SELECT
      e.id_evento,
      e.id_activo,
      a.numero_serie,
      e.tipo_evento,
      e.descripcion,
      e.created_at
    FROM t_activo_evento e
    JOIN c_activos a ON a.id_activo = e.id_activo
    WHERE e.cve_cia = :cia
      AND e.tipo_evento IN ('TEMP_ALERTA','GEO_ALERTA','MANTENIMIENTO_IN')
  ";

  $params = [':cia'=>$cve_cia];

  if ($since) {
    $sql .= " AND e.created_at > :since";
    $params[':since'] = $since;
  }

  $sql .= " ORDER BY e.created_at DESC LIMIT 20";

  $st = $pdo->prepare($sql);
  $st->execute($params);

  out(true, ['data'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
}

out(false, ['error'=>'Acción no válida']);
