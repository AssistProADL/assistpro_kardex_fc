<?php
ob_clean();
require_once __DIR__ . '/../../app/db.php';

$pdo = db_pdo();
$action = $_POST['action'] ?? '';

try {

    if ($action === 'create') {

        $tabla   = $_POST['tabla'];
        $pk      = $_POST['pk'];
        $campos  = $_POST['campos']; // array
        $titulo  = $_POST['titulo'];
        $icono   = $_POST['icono'];

        /* ===== 1. CREAR TABLA ===== */
        $cols = [];
        foreach ($campos as $c) {
            $cols[] = "$c VARCHAR(255)";
        }

        $sql = "
        CREATE TABLE IF NOT EXISTS $tabla (
            $pk INT AUTO_INCREMENT PRIMARY KEY,
            ".implode(',', $cols).",
            Activo TINYINT DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $pdo->exec($sql);

        /* ===== 2. REGISTRAR CATÁLOGO ===== */
        $stmt = $pdo->prepare("
            INSERT INTO c_catalogo
            (nombre_catalogo,tabla,pk,icono,titulo)
            VALUES (?,?,?,?,?)
        ");
        $stmt->execute([
            $titulo,
            $tabla,
            $pk,
            $icono,
            $titulo
        ]);

        echo json_encode(['success'=>true]);
        exit;
    }

    echo json_encode(['error'=>'Acción no válida']);

} catch (Throwable $e) {
    echo json_encode(['error'=>$e->getMessage()]);
}
