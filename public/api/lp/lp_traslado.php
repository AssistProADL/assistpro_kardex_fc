<?php
// public/api/lp/lp_tr.php
// Traslado entre ubicaciones por LP (CveLP) / Contenedor (IDContenedor)
// Fases: init | preview | execute

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../app/auth_check.php';
require_once __DIR__ . '/../../../app/db.php';

global $pdo;
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'msg' => 'No se detectó conexión PDO ($pdo). Revisa app/db.php.',
        'code' => 'NO_PDO'
    ]);
    exit;
}

function jexit($ok, $msg, $extra = [], $http = 200) {
    http_response_code($http);
    echo json_encode(array_merge([
        'ok' => (bool)$ok,
        'msg' => (string)$msg,
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function p($key, $default = '') {
    return isset($_POST[$key]) ? $_POST[$key] : (isset($_GET[$key]) ? $_GET[$key] : $default);
}

function s($v) {
    return trim((string)$v);
}

function now() {
    return date('Y-m-d H:i:s');
}

function uuid_tx() {
    // tx_id simple y suficientemente único para operación (sin extensiones)
    return 'LPTR-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(6)), 0, 12);
}

$action = s(p('action', 'init'));
$lp     = s(p('lp', ''));              // CveLP lógico
$idc    = s(p('id_contenedor', ''));   // IDContenedor físico (int)
$code   = s(p('code', ''));            // clave visual (si la manejan en existencias)
$dst    = s(p('idy_ubica_dst', ''));   // destino
$ref    = s(p('referencia', ''));      // referencia humana
$user   = s(p('cve_usuario', ''));     // opcional: si no, se toma de sesión

// --- usuario: si tu auth_check define algo, lo respetamos
if ($user === '') {
    if (isset($_SESSION['cve_usuario'])) $user = (string)$_SESSION['cve_usuario'];
    elseif (isset($_SESSION['user'])) $user = (string)$_SESSION['user'];
}
if ($user === '') $user = 'SYSTEM';

// -----------------------------------------------------------
// 1) Resolver contenedor/LP
// -----------------------------------------------------------
function resolve_lp_container(PDO $pdo, $lp, $idc, $code) {
    // Priorizamos lp -> c_charolas.CveLP
    // Si no hay lp, buscamos por IDContenedor
    // Si no hay, intentamos por code (si code está en existencias, buscamos Id_Caja o ntarima y de ahí charola)
    $out = [
        'found' => false,
        'IDContenedor' => null,
        'CveLP' => null,
        'tipo' => null,
        'idy_ubica' => null,
        'clave_fisica' => null, // si existe en c_charolas (depende de tu esquema)
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
        // buscar en existencias por code y inferir contenedor/tarima
        // cajas: code pertenece a caja/cont (Id_Caja)
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

        // tarima: code pertenece a tarima/LP (ntarima)
        $st = $pdo->prepare("SELECT ntarima FROM ts_existenciatarima WHERE code = :c LIMIT 1");
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

// -----------------------------------------------------------
// 2) Contenido del LP (cajas + tarimas)
// -----------------------------------------------------------
function load_lp_contents(PDO $pdo, $idContenedor) {
    // Resumen por artículo/lote y con almacén para kardex
    $rows = [];

    // cajas: PiezasXCaja por Id_Caja o por nTarima
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

    // tarimas: existencia por ntarima
    $st = $pdo->prepare("
        SELECT
            cve_almac AS cve_almac,
            idy_ubica,
            cve_articulo,
            lote AS cve_lote,
            SUM(COALESCE(existencia,0)) AS qty
        FROM ts_existenciatarima
        WHERE ntarima = :idc
        GROUP BY cve_almac, idy_ubica, cve_articulo, lote
    ");
    $st->execute([':idc' => $idContenedor]);
    $r2 = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($r2 as $r) {
        $r['fuente'] = 'TARIMA';
        $rows[] = $r;
    }

    // Limpieza: qty > 0
    $rows = array_values(array_filter($rows, function($x){
        return (float)$x['qty'] > 0;
    }));

    return $rows;
}

// -----------------------------------------------------------
// 3) Validar destino
// -----------------------------------------------------------
function validate_dest(PDO $pdo, $idy) {
    if ($idy === '' || !ctype_digit($idy)) return [false, 'Destino inválido.'];
    $st = $pdo->prepare("SELECT idy_ubica, CodigoCSD FROM c_ubicacion WHERE idy_ubica = :u LIMIT 1");
    $st->execute([':u' => $idy]);
    $u = $st->fetch(PDO::FETCH_ASSOC);
    if (!$u) return [false, 'Destino no existe en c_ubicacion.'];
    return [true, $u];
}

// -----------------------------------------------------------
// 4) Acción: INIT
// -----------------------------------------------------------
if ($action === 'init') {
    if ($lp === '' && $idc === '' && $code === '') {
        jexit(false, 'Proporciona lp, id_contenedor o code.', ['code' => 'MISSING_INPUT'], 400);
    }

    $hdr = resolve_lp_container($pdo, $lp, $idc, $code);
    if (!$hdr['found']) {
        jexit(false, 'No se encontró el LP/Contenedor en c_charolas.', ['code' => 'NOT_FOUND'], 404);
    }

    $idCont = $hdr['IDContenedor'];
    if ($idCont === null || $idCont === '') {
        jexit(false, 'El registro no tiene IDContenedor. No se puede operar traslado.', ['code' => 'NO_CONTAINER'], 409);
    }

    $contents = load_lp_contents($pdo, $idCont);

    // Origen inferido (si hay contenido, tomamos idy_ubica dominante)
    $ori = null;
    $oriCounts = [];
    foreach ($contents as $r) {
        $k = (string)$r['idy_ubica'];
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
        ],
        'inherit' => [
            'movement' => 'lp_tr',
            'user' => $user,
            'ts' => now(),
        ]
    ]);
}

// -----------------------------------------------------------
// 5) Acción: PREVIEW
// -----------------------------------------------------------
if ($action === 'preview') {
    if ($lp === '' && $idc === '' && $code === '') {
        jexit(false, 'Proporciona lp, id_contenedor o code.', ['code' => 'MISSING_INPUT'], 400);
    }
    $hdr = resolve_lp_container($pdo, $lp, $idc, $code);
    if (!$hdr['found']) jexit(false, 'No se encontró el LP/Contenedor en c_charolas.', ['code'=>'NOT_FOUND'], 404);

    $idCont = $hdr['IDContenedor'];
    if ($idCont === null || $idCont === '') jexit(false, 'No hay IDContenedor.', ['code'=>'NO_CONTAINER'], 409);

    [$okDst, $dstRow] = validate_dest($pdo, $dst);
    if (!$okDst) jexit(false, $dstRow, ['code'=>'BAD_DEST'], 400);

    $contents = load_lp_contents($pdo, $idCont);
    if (!$contents) {
        // puede ser “nació vacío”, pero traslado vacío se permite si lo quieres; aquí lo permitimos, solo avisamos.
        $warn = ['LP sin existencias. Traslado aplicará solo cambio de ubicación del contenedor.'];
    } else {
        $warn = [];
    }

    // Origen inferido
    $ori = null;
    $oriCounts = [];
    foreach ($contents as $r) {
        $k = (string)$r['idy_ubica'];
        if ($k === '') continue;
        $oriCounts[$k] = ($oriCounts[$k] ?? 0) + 1;
    }
    if ($oriCounts) {
        arsort($oriCounts);
        $ori = (string)array_key_first($oriCounts);
    } else {
        $ori = (string)($hdr['idy_ubica'] ?? '');
    }

    // Resumen ejecutivo
    $sum = [
        'lineas' => count($contents),
        'skus' => count(array_unique(array_map(fn($x)=>$x['cve_articulo'], $contents))),
        'qty_total' => array_sum(array_map(fn($x)=>(float)$x['qty'], $contents)),
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
        ],
        'inherit' => [
            'movement' => 'lp_tr',
            'user' => $user,
            'ts' => now(),
        ]
    ]);
}

// -----------------------------------------------------------
// 6) Acción: EXECUTE (transaccional)
// -----------------------------------------------------------
if ($action === 'execute') {
    if ($lp === '' && $idc === '' && $code === '') {
        jexit(false, 'Proporciona lp, id_contenedor o code.', ['code' => 'MISSING_INPUT'], 400);
    }
    $hdr = resolve_lp_container($pdo, $lp, $idc, $code);
    if (!$hdr['found']) jexit(false, 'No se encontró el LP/Contenedor en c_charolas.', ['code'=>'NOT_FOUND'], 404);

    $idCont = $hdr['IDContenedor'];
    if ($idCont === null || $idCont === '') jexit(false, 'No hay IDContenedor.', ['code'=>'NO_CONTAINER'], 409);

    [$okDst, $dstRow] = validate_dest($pdo, $dst);
    if (!$okDst) jexit(false, $dstRow, ['code'=>'BAD_DEST'], 400);

    $contents = load_lp_contents($pdo, $idCont);

    // Origen inferido (dominante)
    $ori = (string)($hdr['idy_ubica'] ?? '');
    if ($contents) {
        $oriCounts = [];
        foreach ($contents as $r) {
            $k = (string)$r['idy_ubica'];
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

    // Identidad física vs lógica
    $cont_fisico = $hdr['clave_fisica'] ?? (string)$idCont;  // clave contenedor físico
    $lp_logico   = $hdr['CveLP'] ?? '';                      // CveLP lógico

    // Tipo de movimiento (ajústalo a tu catálogo real si difiere)
    $TIPO_TRASLADO = 3;

    try {
        $pdo->beginTransaction();

        // 1) Actualizar ubicación del contenedor/charola (si existe el campo)
        //    No fallamos si no existe columna idy_ubica (por variaciones). Hacemos try.
        try {
            $st = $pdo->prepare("UPDATE c_charolas SET idy_ubica = :dst WHERE IDContenedor = :idc");
            $st->execute([':dst' => (int)$dstRow['idy_ubica'], ':idc' => $idCont]);
        } catch (Throwable $e) {
            // no bloquea
        }

        // 2) Actualizar existencias (ubicación) - cajas
        $st = $pdo->prepare("
            UPDATE ts_existenciacajas
            SET idy_ubica = :dst
            WHERE Id_Caja = :idc OR (nTarima IS NOT NULL AND nTarima = :idc)
        ");
        $st->execute([':dst' => (int)$dstRow['idy_ubica'], ':idc' => $idCont]);
        $affCajas = $st->rowCount();

        // 3) Actualizar existencias (ubicación) - tarima
        $st = $pdo->prepare("
            UPDATE ts_existenciatarima
            SET idy_ubica = :dst
            WHERE ntarima = :idc
        ");
        $st->execute([':dst' => (int)$dstRow['idy_ubica'], ':idc' => $idCont]);
        $affTar = $st->rowCount();

        // 4) Registrar kardex (doble apunte por renglón: salida + entrada)
        //    t_cardex: usamos Cve_Almac/cve_almac de las existencias para mantener consistencia.
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
                $alm = (int)$r['cve_almac'];
                $art = (string)$r['cve_articulo'];
                $lote = (string)$r['cve_lote'];
                $qty = (float)$r['qty'];

                // SALIDA (origen -> destino) cantidad negativa
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

                // ENTRADA (destino) cantidad positiva
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

        // Reconsultar “final”
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
                    'contenedor_fisico' => $cont_fisico,  // clave contenedor físico
                    'lp_logico' => $lp_logico,            // CveLP (license plate lógico)
                ]
            ]
        ]);

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        jexit(false, 'Error al ejecutar traslado: '.$e->getMessage(), ['code'=>'TX_FAIL'], 500);
    }
}

jexit(false, 'Acción inválida. Usa init|preview|execute.', ['code'=>'BAD_ACTION'], 400);
