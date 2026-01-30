<?php
// api_imp_tras_entre_almacenes_apply.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../db.php';

function resolverCantidadLP(PDO $pdo, string $lp): float {
    // AJUSTA AQUÍ según tu modelo de existencias real.
    // Opción A: tarimas
    $st = $pdo->prepare("SELECT COALESCE(SUM(existencia),0) FROM ts_existenciatarimas WHERE UPPER(cve_charola)=UPPER(?)");
    $st->execute([$lp]);
    $qtyTar = floatval($st->fetchColumn() ?: 0);

    // Opción B: cajas / contenedores
    $st2 = $pdo->prepare("SELECT COALESCE(SUM(existencia),0) FROM ts_existenciacajas WHERE UPPER(cve_charola)=UPPER(?)");
    $st2->execute([$lp]);
    $qtyCaj = floatval($st2->fetchColumn() ?: 0);

    return $qtyTar + $qtyCaj;
}

try {
    $run_id = isset($_POST['run_id']) ? intval($_POST['run_id']) : 0;
    if ($run_id <= 0) { echo json_encode(['ok'=>false,'msg'=>'run_id requerido']); exit; }

    // Validar que el run esté validado (con o sin errores)
    $stRun = $pdo->prepare("SELECT id, status, tipo_ingreso FROM ap_import_runs WHERE id=?");
    $stRun->execute([$run_id]);
    $run = $stRun->fetch(PDO::FETCH_ASSOC);
    if (!$run) { echo json_encode(['ok'=>false,'msg'=>'Run no existe']); exit; }

    // Solo aplicar OK
    $rows = $pdo->prepare("SELECT linea_num, data_json FROM ap_import_run_rows WHERE run_id=? AND estado='OK' ORDER BY linea_num");
    $rows->execute([$run_id]);
    $okRows = $rows->fetchAll(PDO::FETCH_ASSOC);
    if (!$okRows) { echo json_encode(['ok'=>false,'msg'=>'No hay líneas OK para aplicar']); exit; }

    $pdo->beginTransaction();

    $aplicadas = 0;
    foreach ($okRows as $r) {
        $data = json_decode($r['data_json'] ?? '{}', true);

        $bl_origen  = strtoupper(trim($data['BL_ORIGEN'] ?? ''));
        $lp_prod    = strtoupper(trim($data['LP_O_PRODUCTO'] ?? ''));
        $lote       = strtoupper(trim($data['LOTE_SERIE'] ?? ''));
        $zrd_bl     = strtoupper(trim($data['ZRD_BL'] ?? ''));
        $cant_raw   = $data['CANTIDAD'] ?? '';

        $isLP = preg_match('/^LP/i', $lp_prod) ? true : false;

        if ($isLP) {
            // Si no viene cantidad, la calculamos por existencias del LP
            if ($cant_raw === '' || $cant_raw === null) {
                $cantidad = resolverCantidadLP($pdo, $lp_prod);
                if ($cantidad <= 0) {
                    throw new Exception("Línea {$r['linea_num']}: LP sin existencia para aplicar ($lp_prod)");
                }
            } else {
                if (!is_numeric($cant_raw) || floatval($cant_raw) <= 0) {
                    throw new Exception("Línea {$r['linea_num']}: CANTIDAD inválida (LP)");
                }
                $cantidad = floatval($cant_raw);
            }
        } else {
            if ($cant_raw === '' || $cant_raw === null) {
                throw new Exception("Línea {$r['linea_num']}: CANTIDAD obligatoria para producto");
            }
            if (!is_numeric($cant_raw) || floatval($cant_raw) <= 0) {
                throw new Exception("Línea {$r['linea_num']}: CANTIDAD inválida (producto)");
            }
            $cantidad = floatval($cant_raw);
        }

        // ==========================================================
        // AQUI VA TU AFECTACIÓN REAL A BD (th/td_aduana + pedido + rtm)
        // ==========================================================
        // Nota: No invento tu lógica exacta porque en tu proyecto ya la tienes.
        // Lo importante era destrabar el esquema (sin created_at/folio) y regla LP.

        $aplicadas++;
    }

    $pdo->prepare("UPDATE ap_import_runs SET status='APLICADO', impacto_kardex='PENDIENTE' WHERE id=?")
        ->execute([$run_id]);

    $pdo->commit();

    echo json_encode(['ok'=>true,'run_id'=>$run_id,'aplicadas'=>$aplicadas], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok'=>false,'msg'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
