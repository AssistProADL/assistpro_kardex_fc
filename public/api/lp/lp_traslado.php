<?php
// public/api/lp/lp_traslado.php
// Traslado entre ubicaciones por LP (CveLP) / Contenedor (IDContenedor)
// Fases: init | preview | execute

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../../../app/auth_check.php';
require_once __DIR__ . '/../../../app/db.php';

/**
 * ✅ CLAVE: db.php expone $GLOBALS['pdo'] SOLO si se llama db_pdo()
 * NO vamos a cambiar db.php; solo lo inicializamos aquí.
 */
try {
    if (function_exists('db_pdo')) {
        db_pdo(); // inicializa y setea $GLOBALS['pdo']
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'msg' => 'No se pudo inicializar DB: ' . $e->getMessage(),
        'code' => 'DB_INIT_FAIL'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

global $pdo;
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'msg' => 'No se detectó conexión PDO ($pdo). db.php requiere db_pdo().',
        'code' => 'NO_PDO'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* =========================
 * Helpers
 * ========================= */
function jexit(bool $ok, string $msg, array $extra = [], int $http = 200): void {
    http_response_code($http);
    echo json_encode(array_merge([
        'ok' => $ok,
        'msg' => $msg,
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function p(string $key, $default = '') {
    return $_POST[$key] ?? ($_GET[$key] ?? $default);
}

function s($v): string {
    return trim((string)$v);
}

function now(): string {
    return date('Y-m-d H:i:s');
}

function uuid_tx(): string {
    return 'LPTR-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(6)), 0, 12);
}

/**
 * Detecta tabla real de tarimas (blindaje: plural vs singular)
 */
function tarima_table(): string {
    // db_table_exists viene de db.php
    if (function_exists('db_table_exists')) {
        if (db_table_exists('ts_existenciatarimas')) return 'ts_existenciatarimas'; // tu estándar (plural)
        if (db_table_exists('ts_existenciatarima'))  return 'ts_existenciatarima';  // fallback (singular)
    }
    // si no pudiéramos detectar, nos quedamos con plural (lo más probable)
    return 'ts_existenciatarimas';
}

/* =========================
 * Inputs
 * ========================= */
$action = s(p('action', 'init'));      // init | preview | execute
$lp     = s(p('lp', ''));              // CveLP lógico
$idc    = s(p('id_contenedor', ''));    // IDContenedor físico
$code   = s(p('code', ''));            // code/clave visual (si aplica)
$dst    = s(p('idy_ubica_dst', ''));    // destino idy_ubica
$ref    = s(p('referencia', ''));      // referencia humana
$user   = s(p('cve_usuario', ''));     // opcional: si no, se toma de sesión

if ($user === '') {
    if (!empty($_SESSION['cve_usuario'])) $user = (string)$_SESSION['cve_usuario'];
    elseif (!empty($_SESSION['username'])) $user = (string)$_SESSION['username'];
    elseif (!empty($_SESSION['user'])) $user = (string)$_SESSION['user'];
}
if ($user === '') $user = 'SYSTEM';

/* ============================================================
 * 1) Resolver LP / Contenedor
 * ============================================================ */
function resolve_lp_container(PDO $pdo, string $lp, string $idc, string $code): array {
    $out = [
        'found' => false,
        'IDContenedor' => null,
        'CveLP' => null,
        'tipo' => null,
        'idy_ubica' => null,
        'clave_fisica' => null,
    ];

    if ($lp !== '') {
        $st = $pdo->prepare("SELECT * FROM c_charolas WHERE CveLP = :lp LIMIT 1");
        $st->execute([':lp' => $lp]);
        $ch = $st->fetch(PDO::FETCH_ASSOC);
        if ($ch) {
            $out['found'] = true;
            $out['IDContenedor'] = $ch['IDContenedor'] ?? null;
            $out['CveLP'] = $ch['CveLP'] ?? $lp;
            $out['tipo'] = $ch['tipo'] ?? null;
            $out['idy_ubica'] = $ch['idy_ubica'] ?? null;
            $out['clave_fisica'] = $ch['code'] ?? ($ch['clave'] ?? null);
            return $out;
        }
    }

    if ($idc !== '') {
        $st = $pdo->prepare("SELECT * FROM c_charolas WHERE IDContenedor = :idc LIMIT 1");
        $st->execute([':idc' => $idc]);
        $ch = $st->fetch(PDO::FETCH_ASSOC);
        if ($ch) {
            $out['found'] = true;
            $out['IDContenedor'] = $ch['IDContenedor'] ?? $idc;
            $out['CveLP'] = $ch['CveLP'] ?? null;
            $out['tipo'] = $ch['tipo'] ?? null;
            $out['idy_ubica'] = $ch['idy_ubica'] ?? null;
            $out['clave_fisica'] = $ch['code'] ?? ($ch['clave'] ?? null);
            return $out;
        }
    }

    if ($code !== '') {
        // Inferir desde existencias cajas: Id_Caja / nTarima
        $st = $pdo->prepare("SELECT Id_Caja, nTarima FROM ts_existenciacajas WHERE code = :c LIMIT 1");
        $st->execute([':c' => $code]);
        $ex = $st->fetch(PDO::FETCH_ASSOC);
        if ($ex) {
            $idInfer = $ex['Id_Caja'] ?: ($ex['nTarima'] ?: null);
            if ($idInfer) {
                $st2 = $pdo->prepare("SELECT * FROM c_charolas WHERE IDContenedor = :idc LIMIT 1");
                $st2->execute([':idc' => $idInfer]);
                $ch = $st2->fetch(PDO::FETCH_ASSOC);
                if ($ch) {
                    $out['found'] = true;
                    $out['IDContenedor'] = $ch['IDContenedor'] ?? $idInfer;
                    $out['CveLP'] = $ch['CveLP'] ?? null;
                    $out['tipo'] = $ch['tipo'] ?? null;
                    $out['idy_ubica'] = $ch['idy_ubica'] ?? null;
                    $out['clave_fisica'] = $code;
                    return $out;
                }
            }
        }

        // Inferir desde tarima (tabla puede ser plural o singular)
        $tTar = tarima_table();
        $st = $pdo->prepare("SELECT ntarima FROM {$tTar} WHERE code = :c LIMIT 1");
        $st->execute([':c' => $code]);
        $et = $st->fetch(PDO::FETCH_ASSOC);
        if ($et && !empty($et['ntarima'])) {
            $st2 = $pdo->prepare("SELECT * FROM c_charolas WHERE IDContenedor = :idc LIMIT 1");
            $st2->execute([':idc' => $et['ntarima']]);
            $ch = $st2->fetch(PDO::FETCH_ASSOC);
            if ($ch) {
                $out['found'] = true;
                $out['IDContenedor'] = $ch['IDContenedor'] ?? $et['ntarima'];
                $out['CveLP'] = $ch['CveLP'] ?? null;
                $out['tipo'] = $ch['tipo'] ?? null;
                $out['idy_ubica'] = $ch['idy_ubica'] ?? null;
                $out['clave_fisica'] = $code;
                return $out;
            }
        }
    }

    return $out;
}

/* ============================================================
 * 2) Contenido del LP / Contenedor
 * ============================================================ */
function load_lp_contents(PDO $pdo, string $idContenedor): array {
    $rows = [];

    // cajas
    $st = $pdo->prepare("
        SELECT
            Cve_Almac AS cve_almac,
            idy_ubica,
            cve_articulo,
            cve_lote,
            SUM(PiezasXCaja) AS qty
        FROM ts_existenciacajas
        WHERE Id_Caja = :idc OR (nTarima IS NOT NULL AND nTarima = :idc)
        GROUP BY Cve_Almac, idy_ubica, cve_articulo, cve_lote
    ");
    $st->execute([':idc' => $idContenedor]);
    $r1 = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($r1 as $r) {
        $r['fuente'] = 'CAJAS';
        $rows[] = $r;
    }

    // tarima (plural/singular)
    $tTar = tarima_table();
    $st = $pdo->prepare("
        SELECT
            cve_almac AS cve_almac,
            idy_ubica,
            cve_articulo,
            lote AS cve_lote,
            SUM(COALESCE(existencia,0)) AS qty
        FROM {$tTar}
        WHERE ntarima = :idc
        GROUP BY cve_almac, idy_ubica, cve_articulo, lote
    ");
    $st->execute([':idc' => $idContenedor]);
    $r2 = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($r2 as $r) {
        $r['fuente'] = 'TARIMA';
        $rows[] = $r;
    }

    // qty > 0
    $rows = array_values(array_filter($rows, function($x){
        return (float)($x['qty'] ?? 0) > 0;
    }));

    return $rows;
}

/* ============================================================
 * 3) Validar destino
 * ============================================================ */
function validate_dest(PDO $pdo, string $idy): array {
    if ($idy === '' || !ctype_digit($idy)) return [false, 'Destino inválido.'];
    $st = $pdo->prepare("SELECT idy_ubica, CodigoCSD FROM c_ubicacion WHERE idy_ubica = :u LIMIT 1");
    $st->execute([':u' => $idy]);
    $u = $st->fetch(PDO::FETCH_ASSOC);
    if (!$u) return [false, 'Destino no existe en c_ubicacion.'];
    return [true, $u];
}

/* ============================================================
 * Acción: INIT
 * ============================================================ */
if ($action === 'init') {
    if ($lp === '' && $idc === '' && $code === '') {
        jexit(false, 'Proporciona lp, id_contenedor o code.', ['code' => 'MISSING_INPUT'], 400);
    }

    $hdr = resolve_lp_container($pdo, $lp, $idc, $code);
    if (!$hdr['found']) {
        jexit(false, 'No se encontró el LP/Contenedor en c_charolas.', ['code' => 'NOT_FOUND'], 404);
    }

    $idCont = (string)($hdr['IDContenedor'] ?? '');
    if ($idCont === '') {
        jexit(false, 'El registro no tiene IDContenedor. No se puede operar traslado.', ['code' => 'NO_CONTAINER'], 409);
    }

    $contents = load_lp_contents($pdo, $idCont);

    // Origen inferido (dominante)
    $oriCounts = [];
    foreach ($contents as $r) {
        $k = (string)($r['idy_ubica'] ?? '');
        if ($k === '') continue;
        $oriCounts[$k] = ($oriCounts[$k] ?? 0) + 1;
    }
    if ($oriCounts) {
        arsort($oriCounts);
        $ori = (string)array_key_first($oriCounts);
    } else {
        $ori = (string)($hdr['idy_ubica'] ?? '');
    }

    jexit(true, 'INIT OK', [
        'data' => [
            'header' => $hdr,
            'origen_idy_ubica' => $ori,
            'contents' => $contents,
            'tarima_table' => tarima_table(),
        ],
        'inherit' => [
            'movement' => 'lp_tr',
            'user' => $user,
            'ts' => now(),
        ]
    ]);
}

/* ============================================================
 * Acción: PREVIEW
 * ============================================================ */
if ($action === 'preview') {
    if ($lp === '' && $idc === '' && $code === '') {
        jexit(false, 'Proporciona lp, id_contenedor o code.', ['code' => 'MISSING_INPUT'], 400);
    }

    $hdr = resolve_lp_container($pdo, $lp, $idc, $code);
    if (!$hdr['found']) jexit(false, 'No se encontró el LP/Contenedor en c_charolas.', ['code'=>'NOT_FOUND'], 404);

    $idCont = (string)($hdr['IDContenedor'] ?? '');
    if ($idCont === '') jexit(false, 'No hay IDContenedor.', ['code'=>'NO_CONTAINER'], 409);

    [$okDst, $dstRow] = validate_dest($pdo, $dst);
    if (!$okDst) jexit(false, (string)$dstRow, ['code'=>'BAD_DEST'], 400);

    $contents = load_lp_contents($pdo, $idCont);
    $warn = $contents ? [] : ['LP sin existencias. Traslado aplicará solo cambio de ubicación del contenedor.'];

    // Origen inferido (dominante)
    $oriCounts = [];
    foreach ($contents as $r) {
        $k = (string)($r['idy_ubica'] ?? '');
        if ($k === '') continue;
        $oriCounts[$k] = ($oriCounts[$k] ?? 0) + 1;
    }
    if ($oriCounts) {
        arsort($oriCounts);
        $ori = (string)array_key_first($oriCounts);
    } else {
        $ori = (string)($hdr['idy_ubica'] ?? '');
    }

    $sum = [
        'lineas' => count($contents),
        'skus' => count(array_unique(array_map(fn($x)=> (string)$x['cve_articulo'], $contents))),
        'qty_total' => array_sum(array_map(fn($x)=> (float)$x['qty'], $contents)),
        'origen_idy_ubica' => $ori,
        'destino_idy_ubica' => (string)$dstRow['idy_ubica'],
        'destino_codigo' => (string)$dstRow['CodigoCSD'],
    ];

    jexit(true, 'PREVIEW OK', [
        'data' => [
            'header' => $hdr,
            'summary' => $sum,
            'contents' => $contents,
            'warnings' => $warn,
            'tarima_table' => tarima_table(),
        ],
        'inherit' => [
            'movement' => 'lp_tr',
            'user' => $user,
            'ts' => now(),
        ]
    ]);
}

/* ============================================================
 * Acción: EXECUTE (transaccional)
 * ============================================================ */
if ($action === 'execute') {
    if ($lp === '' && $idc === '' && $code === '') {
        jexit(false, 'Proporciona lp, id_contenedor o code.', ['code' => 'MISSING_INPUT'], 400);
    }

    $hdr = resolve_lp_container($pdo, $lp, $idc, $code);
    if (!$hdr['found']) jexit(false, 'No se encontró el LP/Contenedor en c_charolas.', ['code'=>'NOT_FOUND'], 404);

    $idCont = (string)($hdr['IDContenedor'] ?? '');
    if ($idCont === '') jexit(false, 'No hay IDContenedor.', ['code'=>'NO_CONTAINER'], 409);

    [$okDst, $dstRow] = validate_dest($pdo, $dst);
    if (!$okDst) jexit(false, (string)$dstRow, ['code'=>'BAD_DEST'], 400);

    $contents = load_lp_contents($pdo, $idCont);

    // Origen inferido
    $ori = (string)($hdr['idy_ubica'] ?? '');
    if ($contents) {
        $oriCounts = [];
        foreach ($contents as $r) {
            $k = (string)($r['idy_ubica'] ?? '');
            if ($k === '') continue;
            $oriCounts[$k] = ($oriCounts[$k] ?? 0) + 1;
        }
        if ($oriCounts) {
            arsort($oriCounts);
            $ori = (string)array_key_first($oriCounts);
        }
    }

    if ($ori !== '' && $ori === (string)$dstRow['idy_ubica']) {
        jexit(false, 'Origen y destino son iguales. No aplica traslado.', ['code'=>'SAME_UBICA'], 409);
    }

    $tx = uuid_tx();
    if ($ref === '') $ref = $tx;

    $cont_fisico = (string)($hdr['clave_fisica'] ?? $idCont);
    $lp_logico   = (string)($hdr['CveLP'] ?? '');

    // Ajusta a tu catálogo real si aplica
    $TIPO_TRASLADO = 12;

    try {
        $pdo->beginTransaction();

        // 1) Actualizar ubicación del contenedor/charola (si existe el campo)
        try {
            $st = $pdo->prepare("UPDATE c_charolas SET idy_ubica = :dst WHERE IDContenedor = :idc");
            $st->execute([':dst' => (int)$dstRow['idy_ubica'], ':idc' => $idCont]);
        } catch (Throwable $e) {
            // no bloquea (esquemas variados)
        }

        // 2) Actualizar existencias cajas
        $st = $pdo->prepare("
            UPDATE ts_existenciacajas
            SET idy_ubica = :dst
            WHERE Id_Caja = :idc OR (nTarima IS NOT NULL AND nTarima = :idc)
        ");
        $st->execute([':dst' => (int)$dstRow['idy_ubica'], ':idc' => $idCont]);
        $affCajas = $st->rowCount();

        // 3) Actualizar existencias tarima (plural/singular)
        $tTar = tarima_table();
        $st = $pdo->prepare("
            UPDATE {$tTar}
            SET idy_ubica = :dst
            WHERE ntarima = :idc
        ");
        $st->execute([':dst' => (int)$dstRow['idy_ubica'], ':idc' => $idCont]);
        $affTar = $st->rowCount();

        // 4) Kardex (doble apunte por renglón)
        $kInserted = 0;

        if ($contents) {
            $ins = $pdo->prepare("
                INSERT INTO t_cardex
                (fecha, id_TipoMovimiento, cve_articulo, cve_lote, origen, destino,
                 stockinicial, cantidad, ajuste, cve_usuario, Referencia, Cve_Almac,
                 contenedor_clave, contenedor_lp, pallet_clave, pallet_lp)
                VALUES
                (:fecha, :tipo, :art, :lote, :ori, :dst,
                 :stockini, :cant, :aj, :usr, :ref, :alm,
                 :c_cont, :lp_cont, :c_pal, :lp_pal)
            ");

            foreach ($contents as $r) {
                $alm  = (int)($r['cve_almac'] ?? 0);
                $art  = (string)($r['cve_articulo'] ?? '');
                $lote = (string)($r['cve_lote'] ?? '');
                $qty  = (float)($r['qty'] ?? 0);

                if ($art === '' || $qty <= 0) continue;

                // salida (negativo)
                $ins->execute([
                    ':fecha' => now(),
                    ':tipo' => $TIPO_TRASLADO,
                    ':art' => $art,
                    ':lote' => $lote,
                    ':ori' => ($ori === '' ? null : (int)$ori),
                    ':dst' => (int)$dstRow['idy_ubica'],
                    ':stockini' => $qty,
                    ':cant' => -$qty,
                    ':aj' => 0,
                    ':usr' => $user,
                    ':ref' => $ref,
                    ':alm' => $alm,
                    ':c_cont' => $cont_fisico,
                    ':lp_cont' => $lp_logico,
                    ':c_pal' => $cont_fisico,
                    ':lp_pal' => $lp_logico,
                ]);
                $kInserted++;

                // entrada (positivo)
                $ins->execute([
                    ':fecha' => now(),
                    ':tipo' => $TIPO_TRASLADO,
                    ':art' => $art,
                    ':lote' => $lote,
                    ':ori' => ($ori === '' ? null : (int)$ori),
                    ':dst' => (int)$dstRow['idy_ubica'],
                    ':stockini' => $qty,
                    ':cant' => $qty,
                    ':aj' => 0,
                    ':usr' => $user,
                    ':ref' => $ref,
                    ':alm' => $alm,
                    ':c_cont' => $cont_fisico,
                    ':lp_cont' => $lp_logico,
                    ':c_pal' => $cont_fisico,
                    ':lp_pal' => $lp_logico,
                ]);
                $kInserted++;
            }
        }

        $pdo->commit();

        $finalContents = load_lp_contents($pdo, $idCont);

        $summary = [
            'tx_id' => $tx,
            'referencia' => $ref,
            'origen_idy_ubica' => $ori,
            'destino_idy_ubica' => (string)$dstRow['idy_ubica'],
            'destino_codigo' => (string)$dstRow['CodigoCSD'],
            'rows_moved_cajas' => $affCajas,
            'rows_moved_tarima' => $affTar,
            'kardex_rows_inserted' => $kInserted,
            'tarima_table' => tarima_table(),
        ];

        jexit(true, 'TRASLADO EJECUTADO', [
            'data' => [
                'header' => $hdr,
                'summary' => $summary,
                'contents_final' => $finalContents,
            ],
            'inherit' => [
                'movement' => 'lp_tr',
                'user' => $user,
                'ts' => now(),
                'extra' => [
                    'contenedor_fisico' => $cont_fisico,
                    'lp_logico' => $lp_logico,
                ]
            ]
        ]);

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        jexit(false, 'Error al ejecutar traslado: ' . $e->getMessage(), ['code'=>'TX_FAIL'], 500);
    }
}

jexit(false, 'Acción inválida. Usa init|preview|execute.', ['code'=>'BAD_ACTION'], 400);
