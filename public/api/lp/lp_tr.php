<?php
/**
 * lp_tr.php
 * Traslado de contenedores entre ubicaciones (BL)
 * - Objeto real del movimiento: CONTENEDOR
 * - LP es contexto lógico
 * - Permite:
 *   - mover 1 o N contenedores
 *   - adoptar a LP destino
 *   - crear pallet nuevo (LP nuevo o preimpreso)
 *   - inactivar LP origen si queda vacío
 * - Validaciones:
 *   - mismo almacén
 *   - acomodo_mixto
 *   - capacidad peso / volumen
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../app/auth_check.php';
require_once __DIR__ . '/../../../app/db.php';
global $pdo;

function out($ok, $msg, $extra = [], $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge([
        'ok' => $ok,
        'msg' => $msg
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function p($k, $d = null) {
    return $_POST[$k] ?? $_GET[$k] ?? $d;
}

function now() {
    return date('Y-m-d H:i:s');
}

/* ===========================
   ENTRADAS
=========================== */
$action          = p('action');                // preview | execute
$lp_origen       = trim(p('lp'));              // LP origen
$idy_dst         = (int)p('idy_ubica_dst');    // BL destino (resuelto por UI)
$contenedores    = p('contenedores', []);      // array Id_Caja
$crear_lp        = (int)p('crear_lp', 0);
$lp_preimpreso   = trim(p('lp_preimpreso', ''));
$lp_destino      = trim(p('lp_destino', ''));  // usar LP existente
$usuario         = $_SESSION['cve_usuario'] ?? 'SYSTEM';

if (!in_array($action, ['preview','execute'])) {
    out(false, 'Acción inválida', [], 400);
}
if (!$lp_origen || !$idy_dst) {
    out(false, 'LP origen y destino son obligatorios', [], 400);
}
if (!is_array($contenedores) || count($contenedores) === 0) {
    out(false, 'Debe seleccionar al menos un contenedor', [], 400);
}

/* ===========================
   HEADER LP ORIGEN
=========================== */
$st = $pdo->prepare("
    SELECT IDContenedor, CveLP, tipo, Activo
    FROM c_charolas
    WHERE CveLP = :lp
    LIMIT 1
");
$st->execute([':lp'=>$lp_origen]);
$lpOri = $st->fetch(PDO::FETCH_ASSOC);

if (!$lpOri || (int)$lpOri['Activo'] !== 1) {
    out(false, 'LP origen no existe o está inactivo', [], 409);
}

$idPalletOrigen = (int)$lpOri['IDContenedor'];

/* ===========================
   CONTENEDORES ORIGEN
=========================== */
$in = implode(',', array_fill(0, count($contenedores), '?'));
$params = $contenedores;
array_unshift($params, $idPalletOrigen);

$st = $pdo->prepare("
    SELECT Id_Caja, nTarima, cve_almac, idy_ubica,
           peso_total, volumen_total
    FROM ts_existenciacajas
    WHERE nTarima = ?
      AND Id_Caja IN ($in)
");
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

if (count($rows) !== count($contenedores)) {
    out(false, 'Uno o más contenedores no pertenecen al pallet origen', [], 409);
}

/* ===========================
   ALMACÉN ORIGEN
=========================== */
$alm_origen = (int)$rows[0]['cve_almac'];

/* ===========================
   UBICACIÓN DESTINO
=========================== */
$st = $pdo->prepare("
    SELECT idy_ubica, CodigoCSD, cve_almac,
           acomodo_mixto,
           capacidad_peso,
           capacidad_volumen
    FROM c_ubicacion
    WHERE idy_ubica = ?
    LIMIT 1
");
$st->execute([$idy_dst]);
$ubi = $st->fetch(PDO::FETCH_ASSOC);

if (!$ubi) {
    out(false, 'Ubicación destino no existe', [], 404);
}
if ((int)$ubi['cve_almac'] !== $alm_origen) {
    out(false, 'Destino pertenece a otro almacén', [], 409);
}
if ((int)$ubi['acomodo_mixto'] !== 1) {
    out(false, 'Ubicación destino no permite traslado', [], 409);
}

/* ===========================
   CAPACIDAD PESO / VOLUMEN
=========================== */
$peso_mov = 0;
$vol_mov  = 0;
foreach ($rows as $r) {
    $peso_mov += (float)$r['peso_total'];
    $vol_mov  += (float)$r['volumen_total'];
}

$st = $pdo->prepare("
    SELECT
      SUM(peso_total) AS peso_ocupado,
      SUM(volumen_total) AS vol_ocupado
    FROM v_existencia_por_ubicacion
    WHERE idy_ubica = ?
");
$st->execute([$idy_dst]);
$occ = $st->fetch(PDO::FETCH_ASSOC);

$peso_ocupado = (float)($occ['peso_ocupado'] ?? 0);
$vol_ocupado  = (float)($occ['vol_ocupado'] ?? 0);

if ($peso_ocupado + $peso_mov > (float)$ubi['capacidad_peso']) {
    out(false, 'Excede capacidad de peso de la ubicación', [], 409);
}
if ($vol_ocupado + $vol_mov > (float)$ubi['capacidad_volumen']) {
    out(false, 'Excede capacidad volumétrica de la ubicación', [], 409);
}

/* ===========================
   PREVIEW
=========================== */
if ($action === 'preview') {
    out(true, 'PREVIEW OK', [
        'data' => [
            'lp_origen' => $lp_origen,
            'contenedores' => $contenedores,
            'almacen_origen' => $alm_origen,
            'destino' => $ubi['CodigoCSD'],
            'peso' => $peso_mov,
            'volumen' => $vol_mov
        ]
    ]);
}

/* ===========================
   EXECUTE
=========================== */
try {
    $pdo->beginTransaction();

    /* ---- Resolver LP DESTINO ---- */
    if ($crear_lp) {
        if ($lp_preimpreso) {
            $lp_dest = $lp_preimpreso;
        } else {
            $lp_dest = 'LP'.date('YmdHis').rand(100,999);
        }

        $st = $pdo->prepare("
            INSERT INTO c_charolas (CveLP, tipo, Activo)
            VALUES (?, 'PALLET', 1)
        ");
        $st->execute([$lp_dest]);
        $idPalletDestino = (int)$pdo->lastInsertId();

    } elseif ($lp_destino) {
        $st = $pdo->prepare("
            SELECT IDContenedor
            FROM c_charolas
            WHERE CveLP = ? AND Activo = 1
            LIMIT 1
        ");
        $st->execute([$lp_destino]);
        $idPalletDestino = (int)$st->fetchColumn();
        if (!$idPalletDestino) {
            out(false, 'LP destino inválido', [], 409);
        }
        $lp_dest = $lp_destino;
    } else {
        out(false, 'Debe definir LP destino o crear uno nuevo', [], 400);
    }

    /* ---- Reasignar contenedores ---- */
    $in = implode(',', array_fill(0, count($contenedores), '?'));
    $params = array_merge([$idPalletDestino, $idy_dst], $contenedores);

    $st = $pdo->prepare("
        UPDATE ts_existenciacajas
        SET nTarima = ?,
            idy_ubica = ?
        WHERE Id_Caja IN ($in)
    ");
    $st->execute($params);

    /* ---- Kardex por contenedor ---- */
    $st = $pdo->prepare("
        INSERT INTO t_cardex
        (fecha, id_TipoMovimiento, cve_usuario,
         origen, destino,
         contenedor_lp, pallet_lp, Referencia)
        VALUES
        (?, 3, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($contenedores as $c) {
        $st->execute([
            now(), $usuario,
            $lp_origen, $lp_dest,
            $lp_origen, $lp_dest,
            'lp_tr'
        ]);
    }

    /* ---- Verificar pallet origen vacío ---- */
    $st = $pdo->prepare("
        SELECT COUNT(*) FROM ts_existenciacajas
        WHERE nTarima = ?
    ");
    $st->execute([$idPalletOrigen]);
    $restantes = (int)$st->fetchColumn();

    if ($restantes === 0) {
        $st = $pdo->prepare("
            UPDATE c_charolas
            SET Activo = 0
            WHERE IDContenedor = ?
        ");
        $st->execute([$idPalletOrigen]);
    }

    $pdo->commit();

    out(true, 'TRASLADO EJECUTADO', [
        'data' => [
            'lp_origen' => $lp_origen,
            'lp_destino' => $lp_dest,
            'contenedores' => $contenedores,
            'lp_origen_inactivo' => ($restantes === 0)
        ]
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    out(false, 'Error en traslado: '.$e->getMessage(), [], 500);
}
