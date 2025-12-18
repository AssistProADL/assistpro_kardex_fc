<?php
ob_clean();
require_once __DIR__ . '/../../app/db.php';

$pdo = db_pdo();
if (!$pdo) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'PDO no inicializado']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {

    switch ($action) {

        /* ================= LIST ================= */
        case 'list':
            header('Content-Type: application/json');
            $stmt = $pdo->query("
                SELECT id_user, cve_usuario, nombre_completo,
                       email, perfil, status, Activo
                FROM c_usuario
                ORDER BY nombre_completo
            ");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        /* ================= GET ================= */
        case 'get':
            header('Content-Type: application/json');
            $stmt = $pdo->prepare("SELECT * FROM c_usuario WHERE id_user=?");
            $stmt->execute([$_GET['id_user'] ?? 0]);
            echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
            break;

        /* ================= CREATE ================= */
        case 'create':
            header('Content-Type: application/json');
            $stmt = $pdo->prepare("
                INSERT INTO c_usuario
                (cve_usuario,nombre_completo,email,perfil,
                 des_usuario,pwd_usuario,status,Activo,fec_ingreso)
                VALUES (?,?,?,?,?,?,?, ?,NOW())
            ");
            $stmt->execute([
                $_POST['cve_usuario'],
                $_POST['nombre_completo'],
                $_POST['email'],
                $_POST['perfil'],
                $_POST['des_usuario'],
                '',
                $_POST['status'],
                $_POST['Activo']
            ]);
            echo json_encode(['success'=>true]);
            break;

        /* ================= UPDATE ================= */
        case 'update':
            header('Content-Type: application/json');
            $stmt = $pdo->prepare("
                UPDATE c_usuario SET
                    cve_usuario=?,
                    nombre_completo=?,
                    email=?,
                    perfil=?,
                    des_usuario=?,
                    status=?,
                    Activo=?
                WHERE id_user=?
            ");
            $stmt->execute([
                $_POST['cve_usuario'],
                $_POST['nombre_completo'],
                $_POST['email'],
                $_POST['perfil'],
                $_POST['des_usuario'],
                $_POST['status'],
                $_POST['Activo'],
                $_POST['id_user']
            ]);
            echo json_encode(['success'=>true]);
            break;

        /* ================= DELETE ================= */
        case 'delete':
            header('Content-Type: application/json');
            $stmt = $pdo->prepare("UPDATE c_usuario SET Activo=0 WHERE id_user=?");
            $stmt->execute([$_POST['id_user']]);
            echo json_encode(['success'=>true]);
            break;

        /* ================= RECOVER ================= */
        case 'recover':
            header('Content-Type: application/json');
            $stmt = $pdo->prepare("UPDATE c_usuario SET Activo=1 WHERE id_user=?");
            $stmt->execute([$_POST['id_user']]);
            echo json_encode(['success'=>true]);
            break;

        /* ================= EXPORT CSV ================= */
        case 'export_csv':
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=usuarios.csv');

            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'cve_usuario','nombre_completo','email',
                'perfil','des_usuario','status','Activo'
            ]);

            $stmt = $pdo->query("
                SELECT cve_usuario,nombre_completo,email,
                       perfil,des_usuario,status,Activo
                FROM c_usuario
                ORDER BY nombre_completo
            ");

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($out, $row);
            }
            fclose($out);
            exit;

        /* ================= IMPORT CSV ================= */
        case 'import_csv':
            header('Content-Type: application/json');

            if (!isset($_FILES['file'])) {
                echo json_encode(['error'=>'Archivo no recibido']);
                exit;
            }

            $fh = fopen($_FILES['file']['tmp_name'], 'r');
            fgetcsv($fh); // header

            $sql = $pdo->prepare("
                INSERT INTO c_usuario
                (cve_usuario,nombre_completo,email,perfil,
                 des_usuario,status,Activo,fec_ingreso)
                VALUES (?,?,?,?,?,?,?,NOW())
                ON DUPLICATE KEY UPDATE
                    nombre_completo=VALUES(nombre_completo),
                    email=VALUES(email),
                    perfil=VALUES(perfil),
                    des_usuario=VALUES(des_usuario),
                    status=VALUES(status),
                    Activo=VALUES(Activo)
            ");

            while (($row = fgetcsv($fh)) !== false) {
                $sql->execute($row);
            }

            fclose($fh);
            echo json_encode(['success'=>true]);
            break;

        default:
            header('Content-Type: application/json');
            echo json_encode(['error'=>'Acción no válida','action'=>$action]);
    }

} catch (Throwable $e) {
    header('Content-Type: application/json');
    echo json_encode(['error'=>$e->getMessage()]);
}
