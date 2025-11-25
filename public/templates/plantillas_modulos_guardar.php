<?php
// public/templates/plantillas_modulos_guardar.php
// Guarda / actualiza un módulo de plantillas de filtros

declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';

$pdo = db_pdo();

$clave       = $_POST['clave_modulo'] ?? '';
$nombre      = $_POST['nombre']       ?? '';
$vistaSql    = $_POST['vista_sql']    ?? '';
$descripcion = $_POST['descripcion']  ?? '';
$activo      = $_POST['activo']       ?? '1';

$clave       = trim($clave);
$nombre      = trim($nombre);
$vistaSql    = trim($vistaSql);
$descripcion = trim($descripcion);
$activo      = $activo === '0' ? 0 : 1;

// Validaciones básicas
if ($clave === '' || $nombre === '') {
    $msg = 'Clave de módulo y nombre son obligatorios.';
    header('Location: plantillas_modulos.php?msg=' . urlencode($msg));
    exit;
}

// NO se fuerza a mayúsculas para evitar romper claves usadas en vistas
// (ej. "existencias_ubicacion" debe coincidir con el código de tus vistas)
// $clave = strtoupper($clave);

try {
    // Buscar si ya existe
    $row = db_one("
        SELECT id
        FROM ap_plantillas_modulos
        WHERE clave_modulo = ?
    ", [$clave]);

    if ($row && isset($row['id'])) {
        // UPDATE
        $sql = "
            UPDATE ap_plantillas_modulos
            SET nombre      = :nombre,
                vista_sql   = :vista_sql,
                descripcion = :descripcion,
                activo      = :activo
            WHERE id = :id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre'      => $nombre,
            ':vista_sql'   => ($vistaSql !== '' ? $vistaSql : null),
            ':descripcion' => ($descripcion !== '' ? $descripcion : null),
            ':activo'      => $activo,
            ':id'          => $row['id'],
        ]);
        $msg = 'Módulo actualizado correctamente.';
    } else {
        // INSERT
        $sql = "
            INSERT INTO ap_plantillas_modulos
                (clave_modulo, nombre, vista_sql, descripcion, activo)
            VALUES
                (:clave_modulo, :nombre, :vista_sql, :descripcion, :activo)
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':clave_modulo'=> $clave,
            ':nombre'      => $nombre,
            ':vista_sql'   => ($vistaSql !== '' ? $vistaSql : null),
            ':descripcion' => ($descripcion !== '' ? $descripcion : null),
            ':activo'      => $activo,
        ]);
        $msg = 'Módulo creado correctamente.';
    }

} catch (Throwable $e) {
    $msg = 'Error guardando módulo: '.$e->getMessage();
}

header('Location: plantillas_modulos.php?msg=' . urlencode($msg));
exit;
