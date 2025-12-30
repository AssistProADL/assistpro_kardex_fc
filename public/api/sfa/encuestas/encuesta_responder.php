<?php
require_once '../../app/db.php';

$input = json_decode(file_get_contents("php://input"), true);

$encuesta_id = $input['encuesta_id'];
$usuario_id  = $input['usuario_id'];
$cve_clte    = $input['cve_clte'];

db_execute(
    "INSERT INTO sfa_respuestas (encuesta_id, usuario_id, cve_clte)
     VALUES (:e, :u, :c)",
    [
        'e' => $encuesta_id,
        'u' => $usuario_id,
        'c' => $cve_clte
    ]
);

$respuesta_id = db_last_id();

foreach ($input['respuestas'] as $r) {
    db_execute(
        "INSERT INTO sfa_respuesta_detalle (respuesta_id, pregunta_id, valor)
         VALUES (:rid, :pid, :val)",
        [
            'rid' => $respuesta_id,
            'pid' => $r['pregunta_id'],
            'val' => $r['valor']
        ]
    );
}

db_execute(
    "INSERT INTO sfa_encuesta_frecuencia_log (encuesta_id, usuario_id, cve_clte, fecha)
     VALUES (:e, :u, :c, CURDATE())",
    [
        'e' => $encuesta_id,
        'u' => $usuario_id,
        'c' => $cve_clte
    ]
);

echo json_encode(['ok' => 1]);
