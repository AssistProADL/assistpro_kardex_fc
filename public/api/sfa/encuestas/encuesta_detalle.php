<?php
require_once '../../app/db.php';

$encuesta_id = $_GET['encuesta_id'];

$encuesta = db_row(
    "SELECT * FROM sfa_encuestas WHERE id = :id",
    ['id' => $encuesta_id]
);

$preguntas = db_all(
    "SELECT * FROM sfa_preguntas WHERE encuesta_id = :id ORDER BY orden",
    ['id' => $encuesta_id]
);

foreach ($preguntas as &$p) {
    $p['opciones'] = db_all(
        "SELECT * FROM sfa_pregunta_opciones WHERE pregunta_id = :pid ORDER BY orden",
        ['pid' => $p['id']]
    );
}

echo json_encode([
    'ok' => 1,
    'encuesta' => $encuesta,
    'preguntas' => $preguntas
]);
