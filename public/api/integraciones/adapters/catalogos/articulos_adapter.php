<?php
/**
 * Adapter: Artículos
 * Evento: CAT_ARTICULOS_SYNC
 */

require_once __DIR__ . '/../../../../app/db.php';

function articulos_adapter(array $payload, array $contexto): array
{
    global $pdo;

    $sistema = $contexto['sistema'] ?? 'DESCONOCIDO';
    $items   = $payload['items'] ?? [];

    if (empty($items)) {
        return [
            'ok' => false,
            'error' => 'No se recibieron items'
        ];
    }

    $procesados = 0;
    $errores = [];

    foreach ($items as $idx => $item) {

        $sku  = strtoupper(trim($item['sku'] ?? ''));
        $desc = trim($item['descripcion'] ?? '');
        $uom  = trim($item['unidad'] ?? '');
        $activo = isset($item['activo']) ? (int)$item['activo'] : 1;

        if ($sku === '' || $desc === '') {
            $errores[] = "Item {$idx}: SKU o descripción vacíos";
            continue;
        }

        // 🔁 Mapear unidad
        if ($uom !== '') {
            $stmt = $pdo->prepare("
                SELECT unidad_interna
                FROM c_mapeo_unidades
                WHERE sistema_origen = ? AND unidad_origen = ? AND activo = 1
                LIMIT 1
            ");
            $stmt->execute([$sistema, $uom]);
            $map = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($map) {
                $uom = $map['unidad_interna'];
            } else {
                $errores[] = "SKU {$sku}: unidad '{$uom}' no mapeada";
                continue;
            }
        }

        // 🔐 UPSERT de artículo (ejemplo genérico)
        try {
            $stmt = $pdo->prepare("
                INSERT INTO c_articulos (sku, descripcion, unidad, activo)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                  descripcion = VALUES(descripcion),
                  unidad = VALUES(unidad),
                  activo = VALUES(activo)
            ");
            $stmt->execute([$sku, $desc, $uom, $activo]);
            $procesados++;
        } catch (Exception $e) {
            $errores[] = "SKU {$sku}: " . $e->getMessage();
        }
    }

    return [
        'ok' => empty($errores),
        'procesados' => $procesados,
        'errores' => $errores
    ];
}
