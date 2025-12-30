<?php
require_once '../../app/db.php';

$usuario_id = $_GET['usuario_id'] ?? null;
$cve_clte   = $_GET['cve_clte'] ?? null;

$sql = "
SELECT e.id, e.nombre, e.tipo, e.obligatoria
FROM sfa_encuestas e
JOIN sfa_encuesta_asignacion a ON a.encuesta_id = e.id
WHERE e.activa = 1
AND CURDATE() BETWEEN e.fecha_inicio AND e.fecha_fin
AND (a.cve_clte = :cve_clte OR a.cve_clte IS NULL)
AND NOT EXISTS (
    SELECT 1
    FROM sfa_encuesta_frecuencia_log f
    WHERE f.encuesta_id = e.id
    AND f.usuario_id = :usuario_id
    AND f.cve_clte = :cve_clte
    AND f.fecha = CURDATE()
)
";

$data = db_all($sql, [
    'usuario_id' => $usuario_id,
    'cve_clte'   => $cve_clte
]);

echo json_encode([
    'ok' => 1,
    'encuestas' => $data
]);
<?php
