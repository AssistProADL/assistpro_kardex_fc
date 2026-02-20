<?php
require_once __DIR__ . '/_qa_bootstrap.php';

$tipo = qa_param('tipo', null); // opcional: 'A','Q','S' etc según tu c_motivo.Tipo_Cat
$sql = "SELECT id, Tipo_Cat, Des_Motivo
        FROM c_motivo
        WHERE Activo = 1";

$params = [];
if ($tipo !== null && $tipo !== '') {
  $sql .= " AND Tipo_Cat = ?";
  $params[] = $tipo;
}
$sql .= " ORDER BY Des_Motivo";

$data = qa_query_all($sql, $params);
qa_ok($data);
