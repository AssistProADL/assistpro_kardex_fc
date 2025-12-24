<?php
// ======================================================================
//  API: TIPOS DE TRANSPORTE
//  Ruta: /public/api/tipos_transporte.php
//  Devuelve lista de tipos de transporte con capacidades y dimensiones
// ======================================================================

require_once __DIR__ . '/../../app/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();

try {
    $sql = "SELECT 
                id,
                clave_ttransporte,
                desc_ttransporte AS nombre,
                alto AS altura_mts,
                ancho AS ancho_mts,
                fondo AS fondo_mts,
                capacidad_carga AS capacidad_peso_kg,
                imagen
            FROM tipo_transporte
            WHERE Activo = 1
            ORDER BY desc_ttransporte";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular capacidad volumétrica (alto × ancho × fondo)
    foreach ($tipos as &$tipo) {
        $alto = floatval($tipo['altura_mts'] ?? 0);
        $ancho = floatval($tipo['ancho_mts'] ?? 0);
        $fondo = floatval($tipo['fondo_mts'] ?? 0);

        $tipo['capacidad_volumen_m3'] = round($alto * $ancho * $fondo, 2);

        // Convertir a números
        $tipo['altura_mts'] = $alto;
        $tipo['ancho_mts'] = $ancho;
        $tipo['fondo_mts'] = $fondo;
        $tipo['capacidad_peso_kg'] = floatval($tipo['capacidad_peso_kg'] ?? 0);
    }

    echo json_encode([
        'success' => true,
        'data' => $tipos
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener tipos de transporte: ' . $e->getMessage()
    ]);
}
