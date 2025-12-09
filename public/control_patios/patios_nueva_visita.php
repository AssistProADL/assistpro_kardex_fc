<?php
// public/control_patios/patios_nueva_visita.php
declare(strict_types=1);


require_once __DIR__ . '/../../app/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Método no permitido');
    }

    $usuario       = $_SESSION['username'] ?? 'SISTEMA';
    $empresa_id    = isset($_POST['empresa_id']) ? trim((string)$_POST['empresa_id']) : '';
    $almacenp_id   = isset($_POST['almacenp_id']) ? trim((string)$_POST['almacenp_id']) : '';
    $id_transporte = isset($_POST['id_transporte']) ? (int)$_POST['id_transporte'] : 0;
    $observaciones = isset($_POST['observaciones']) ? trim((string)$_POST['observaciones']) : '';

    if ($empresa_id === '') {
        throw new RuntimeException('Empresa inválida.');
    }
    if ($almacenp_id === '') {
        throw new RuntimeException('Almacén/PV inválido.');
    }
    if ($id_transporte <= 0) {
        throw new RuntimeException('Debe seleccionar un transporte.');
    }

    $id_visita = null;

    db_tx(function () use ($empresa_id, $almacenp_id, $id_transporte, $observaciones, $usuario, &$id_visita) {

        dbq("
            INSERT INTO t_patio_visita (
                id_cita,
                id_transporte,
                empresa_id,
                almacenp_id,
                id_zona,
                id_anden_actual,
                estatus,
                fecha_llegada,
                fecha_salida,
                observaciones,
                usuario_checkin,
                usuario_checkout,
                usuario_asigna,
                fecha_asigna
            ) VALUES (
                NULL,
                :id_transporte,
                :empresa_id,
                :almacenp_id,
                NULL,
                NULL,
                'EN_PATIO',
                NOW(),
                NULL,
                :observaciones,
                :usuario_checkin,
                NULL,
                NULL,
                NULL
            )
        ", [
            ':id_transporte'   => $id_transporte,
            ':empresa_id'      => $empresa_id,   // cve_cia
            ':almacenp_id'     => $almacenp_id,  // c_almacenp.id
            ':observaciones'   => $observaciones,
            ':usuario_checkin' => $usuario
        ]);

        $id_visita = (int)db_val("SELECT LAST_INSERT_ID() AS id");

        dbq("
            INSERT INTO t_patio_mov (
                id_visita,
                id_anden,
                estatus,
                fecha,
                usuario,
                comentario
            ) VALUES (
                :id_visita,
                NULL,
                'EN_PATIO',
                NOW(),
                :usuario,
                :comentario
            )
        ", [
            ':id_visita' => $id_visita,
            ':usuario'   => $usuario,
            ':comentario'=> 'Registro de llegada al patio'
        ]);
    });

    echo json_encode([
        'ok'        => true,
        'msg'       => 'Visita registrada correctamente',
        'id_visita' => $id_visita
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'ok'    => false,
        'error' => $e->getMessage()
    ]);
}
