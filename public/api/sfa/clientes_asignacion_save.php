<?php
// Ruta esperada: /public/api/sfa/clientes_asignacion_save.php
// Guarda días de visita en reldaycli (UPSERT por almacen+ruta+destinatario).

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');

header('Content-Type: application/json; charset=utf-8');

while (ob_get_level()) { @ob_end_clean(); }
ob_start();

try {
    require_once __DIR__ . '/../../../app/db.php'; // public/api/sfa -> projectRoot/app/db.php

    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?? '', true);

    if (!is_array($data)) {
        ob_clean();
        echo json_encode(['ok'=>0,'error'=>'JSON inválido'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $almacen_id = (int)($data['almacen_id'] ?? $data['almacen'] ?? 0);
    $ruta_id    = (int)($data['ruta_id'] ?? $data['ruta'] ?? 0);
    $items      = $data['items'] ?? [];

    if ($almacen_id <= 0 || $ruta_id <= 0 || !is_array($items) || count($items) === 0) {
        ob_clean();
        echo json_encode([
            'ok' => 0,
            'error' => 'Parámetros incompletos (almacen_id/ruta_id/items).',
            'debug' => ['almacen_id'=>$almacen_id,'ruta_id'=>$ruta_id,'items_count'=>is_array($items)?count($items):null]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Recomendado: índice único para que ON DUPLICATE funcione perfecto:
    // ALTER TABLE reldaycli ADD UNIQUE KEY uk_reldaycli (Cve_Almac, Cve_Ruta, Id_Destinatario);
    //
    // Aun así, blindamos: primero DELETE por llave y luego INSERT, evitando duplicados aunque no exista índice.

    $ok = 0;
    $del = 0;

    db_tx(function() use ($almacen_id, $ruta_id, $items, &$ok, &$del) {

        $sqlDel = "DELETE FROM reldaycli
                   WHERE Cve_Almac = :alm AND Cve_Ruta = :rut AND Id_Destinatario = :dest";

        // Si tu tabla NO tiene Sec, elimina Sec en INSERT/UPDATE y en el data.php
        $sqlIns = "INSERT INTO reldaycli
                    (Cve_Almac, Cve_Ruta, Cve_Cliente, Id_Destinatario, Cve_Vendedor,
                     Lu, Ma, Mi, Ju, Vi, Sa, `Do`, Sec)
                   VALUES
                    (:alm, :rut, :cli, :dest, :vend,
                     :Lu, :Ma, :Mi, :Ju, :Vi, :Sa, :Do, :Sec)";

        foreach ($items as $it) {
            if (!is_array($it)) continue;

            $dest = (int)($it['Id_Destinatario'] ?? $it['id_destinatario'] ?? 0);
            $cli  = (int)($it['Cve_Cliente'] ?? $it['cve_cliente'] ?? 0);
            $vend = (int)($it['Cve_Vendedor'] ?? $it['cve_vendedor'] ?? 0);
            $sec  = (string)($it['Sec'] ?? $it['sec'] ?? '');

            if ($dest <= 0) continue;

            $Lu = (int)($it['Lu'] ?? 0);
            $Ma = (int)($it['Ma'] ?? 0);
            $Mi = (int)($it['Mi'] ?? 0);
            $Ju = (int)($it['Ju'] ?? 0);
            $Vi = (int)($it['Vi'] ?? 0);
            $Sa = (int)($it['Sa'] ?? 0);
            $Do = (int)($it['Do'] ?? 0);

            $sum = $Lu+$Ma+$Mi+$Ju+$Vi+$Sa+$Do;

            // Si ya no hay días marcados, se elimina asignación
            if ($sum === 0) {
                $aff = dbq($sqlDel, [':alm'=>$almacen_id,':rut'=>$ruta_id,':dest'=>$dest]);
                $del += (int)$aff;
                continue;
            }

            // Elimina duplicados por llave (si existían)
            dbq($sqlDel, [':alm'=>$almacen_id,':rut'=>$ruta_id,':dest'=>$dest]);

            // Inserta nuevo (estado final)
            dbq($sqlIns, [
                ':alm'=>$almacen_id,
                ':rut'=>$ruta_id,
                ':cli'=>$cli,
                ':dest'=>$dest,
                ':vend'=>$vend,
                ':Lu'=>$Lu, ':Ma'=>$Ma, ':Mi'=>$Mi, ':Ju'=>$Ju, ':Vi'=>$Vi, ':Sa'=>$Sa, ':Do'=>$Do,
                ':Sec'=>$sec
            ]);

            $ok++;
        }
    });

    ob_clean();
    echo json_encode([
        'ok' => 1,
        'message' => 'Planeación guardada.',
        'total_ok' => $ok,
        'total_deleted' => $del,
        'debug' => ['almacen_id'=>$almacen_id,'ruta_id'=>$ruta_id]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    ob_clean();
    echo json_encode([
        'ok' => 0,
        'error' => 'Error guardando planeación.',
        'detalle' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
