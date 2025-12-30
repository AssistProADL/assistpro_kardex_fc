<?php
require_once '../../app/db.php';

$usuario_id = $_GET['usuario_id'];
$cve_clte   = $_GET['cve_clte'];

$sql = "
SELECT COUNT(*) total
FROM sfa_encuestas e
WHERE e.obligatoria = 1
AND e.activa = 1
AND NOT EXISTS (
    SELECT 1 FROM sfa_encuesta_frecuencia_log f
    WHERE f.encuesta_id = e.id
    AND f.usuario_id = :u
    AND f.cve_clte = :c
    AND f.fecha = CURDATE()
)
";

$row = db_row($sql, ['u'=>$usuario_id,'c'=>$cve_clte]);

echo json_encode([
    'ok' => $row['total'] == 0,
    'pendientes' => $row['total']
]);
