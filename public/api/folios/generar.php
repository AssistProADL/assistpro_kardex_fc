<?php
require_once __DIR__ . '/../_base.php';

if ($method !== 'POST') {
    response(['error' => 'Método no permitido'], 405);
}

$data = json_decode(file_get_contents("php://input"), true);

$empresa_id = $data['empresa_id'] ?? null;
$modulo     = $data['modulo'] ?? null;
$codigo     = $data['codigo'] ?? null;
$usuario    = $_SESSION['usuario'] ?? 'api';

if (!$empresa_id || !$modulo || !$codigo) {
    response(['error' => 'empresa_id, modulo y codigo son requeridos'], 400);
}

$sql = "
    CALL sp_next_folio_diario(
        :empresa_id,
        :modulo,
        '01',
        :codigo,
        CURDATE(),
        :usuario,
        @folio,
        @num
    )
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':empresa_id' => $empresa_id,
    ':modulo'     => $modulo,
    ':codigo'     => strtoupper($codigo),
    ':usuario'    => $usuario
]);

$res = $pdo->query("SELECT @folio AS folio, @num AS consecutivo")
           ->fetch(PDO::FETCH_ASSOC);

response($res);
