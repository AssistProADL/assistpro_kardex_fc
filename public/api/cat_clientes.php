<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/db.php';

/*
  API Clientes
  Tabla: c_cliente
  BD: assistpro_etl_fc
*/

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === '') {
    echo json_encode([
        'error' => 'Acción no válida'
    ]);
    exit;
}

// conexión PDO REAL del core
$pdo = db_pdo();

switch ($action) {

    /* =============================
     * LISTAR (grilla)
     * ============================= */
    case 'list':

        $sql = "
            SELECT
                id_cliente,
                Cve_Clte,
                RazonSocial,
                RazonComercial,
                RFC,
                Ciudad,
                Estado,
                Telefono1,
                email_cliente,
                credito,
                limite_credito,
                dias_credito,
                Activo
            FROM c_cliente
            WHERE Activo = 1
            ORDER BY RazonSocial
            LIMIT 25
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        echo json_encode($stmt->fetchAll());
        break;

    /* =============================
     * OBTENER UNO
     * ============================= */
    case 'get':

        if (empty($_GET['id_cliente'])) {
            echo json_encode(['error' => 'id_cliente requerido']);
            exit;
        }

        $stmt = $pdo->prepare(
            "SELECT * FROM c_cliente WHERE id_cliente = ?"
        );
        $stmt->execute([$_GET['id_cliente']]);
        echo json_encode($stmt->fetch());
        break;

    /* =============================
     * CREAR
     * ============================= */
    case 'create':

        $sql = "
            INSERT INTO c_cliente (
                Cve_Clte,
                RazonSocial,
                RazonComercial,
                RFC,
                Ciudad,
                Estado,
                Telefono1,
                email_cliente,
                credito,
                limite_credito,
                dias_credito,
                Activo
            ) VALUES (
                :Cve_Clte,
                :RazonSocial,
                :RazonComercial,
                :RFC,
                :Ciudad,
                :Estado,
                :Telefono1,
                :email_cliente,
                :credito,
                :limite_credito,
                :dias_credito,
                1
            )
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':Cve_Clte'        => $_POST['Cve_Clte'] ?? null,
            ':RazonSocial'    => $_POST['RazonSocial'] ?? null,
            ':RazonComercial' => $_POST['RazonComercial'] ?? null,
            ':RFC'            => $_POST['RFC'] ?? null,
            ':Ciudad'         => $_POST['Ciudad'] ?? null,
            ':Estado'         => $_POST['Estado'] ?? null,
            ':Telefono1'      => $_POST['Telefono1'] ?? null,
            ':email_cliente'  => $_POST['email_cliente'] ?? null,
            ':credito'        => $_POST['credito'] ?? 0,
            ':limite_credito' => $_POST['limite_credito'] ?? 0,
            ':dias_credito'   => $_POST['dias_credito'] ?? 0
        ]);

        echo json_encode([
            'success' => true,
            'id_cliente' => $pdo->lastInsertId()
        ]);
        break;

    /* =============================
     * ACTUALIZAR
     * ============================= */
    case 'update':

        $sql = "
            UPDATE c_cliente SET
                Cve_Clte = :Cve_Clte,
                RazonSocial = :RazonSocial,
                RazonComercial = :RazonComercial,
                RFC = :RFC,
                Telefono1 = :Telefono1,
                email_cliente = :email_cliente,
                credito = :credito,
                limite_credito = :limite_credito,
                dias_credito = :dias_credito
            WHERE id_cliente = :id_cliente
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':Cve_Clte'        => $_POST['Cve_Clte'],
            ':RazonSocial'    => $_POST['RazonSocial'],
            ':RazonComercial' => $_POST['RazonComercial'],
            ':RFC'            => $_POST['RFC'],
            ':Telefono1'      => $_POST['Telefono1'],
            ':email_cliente'  => $_POST['email_cliente'],
            ':credito'        => $_POST['credito'],
            ':limite_credito' => $_POST['limite_credito'],
            ':dias_credito'   => $_POST['dias_credito'],
            ':id_cliente'     => $_POST['id_cliente']
        ]);

        echo json_encode(['success' => true]);
        break;

    /* =============================
     * BAJA LÓGICA
     * ============================= */
    case 'delete':

        $stmt = $pdo->prepare(
            "UPDATE c_cliente SET Activo = 0 WHERE id_cliente = ?"
        );
        $stmt->execute([$_POST['id_cliente']]);
        echo json_encode(['success' => true]);
        break;

    /* =============================
     * RECUPERAR
     * ============================= */
    case 'restore':

        $stmt = $pdo->prepare(
            "UPDATE c_cliente SET Activo = 1 WHERE id_cliente = ?"
        );
        $stmt->execute([$_POST['id_cliente']]);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['error' => 'Acción no reconocida']);
}
