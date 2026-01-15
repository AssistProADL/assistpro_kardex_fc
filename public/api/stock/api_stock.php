<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

// 1) Cargar PDO SIEMPRE con require_once para evitar redeclare
try {
    require_once __DIR__ . '/../../../app/db.php';
    $pdo = db();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["ok"=>0,"error"=>"Conexión PDO inválida: ".$e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2) Helpers
function jexit(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonBody(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') return [];
    $j = json_decode($raw, true);
    return is_array($j) ? $j : [];
}

function hasColumn(PDO $pdo, string $table, string $col): bool {
    static $cache = [];
    $key = $table.'.'.$col;
    if (array_key_exists($key, $cache)) return (bool)$cache[$key];

    $sql = "SELECT COUNT(*) c
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :t
              AND COLUMN_NAME = :c";
    $st = $pdo->prepare($sql);
    $st->execute([':t'=>$table, ':c'=>$col]);
    $cache[$key] = ((int)$st->fetchColumn() > 0);
    return (bool)$cache[$key];
}

/**
 * Determina si el artículo permite lote/caducidad.
 * - Si no existen columnas de control en c_articulo, no bloquea (modo legacy).
 * - Ajusta aquí los nombres si tu c_articulo usa otros.
 */
function articuloFlags(PDO $pdo, string $cve_articulo): array {
    $flags = ['lote'=>false,'caducidad'=>false];

    // Nombres comunes (ajustables)
    $colLote = null;
    foreach (['maneja_lote','Maneja_Lote','lote_req','requiere_lote'] as $c) {
        if (hasColumn($pdo, 'c_articulo', $c)) { $colLote = $c; break; }
    }
    $colCad = null;
    foreach (['maneja_caducidad','Maneja_Caducidad','caducidad_req','requiere_caducidad'] as $c) {
        if (hasColumn($pdo, 'c_articulo', $c)) { $colCad = $c; break; }
    }

    if (!$colLote && !$colCad) {
        // modo legacy: no forzar validación si no hay control en catálogo
        return $flags;
    }

    $cols = [];
    if ($colLote) $cols[] = $colLote.' AS lote';
    if ($colCad)  $cols[] = $colCad.' AS caducidad';

    $sql = "SELECT ".implode(',', $cols)." FROM c_articulo WHERE cve_articulo = :a LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([':a'=>$cve_articulo]);
    $row = $st->fetch();
    if (!$row) return $flags;

    if (array_key_exists('lote',$row))      $flags['lote']      = ((string)$row['lote'] === '1' || strtoupper((string)$row['lote']) === 'S');
    if (array_key_exists('caducidad',$row)) $flags['caducidad'] = ((string)$row['caducidad'] === '1' || strtoupper((string)$row['caducidad']) === 'S');
    return $flags;
}

/**
 * Inserta un movimiento en t_cardex (si existe la tabla).
 * No detiene la operación si falla el kardex (para no frenar operación).
 */
function kardex(PDO $pdo, array $k): void {
    try {
        // Validar existencia de tabla
        $st = $pdo->query("SHOW TABLES LIKE 't_cardex'");
        if (!$st->fetch()) return;

        $sql = "INSERT INTO t_cardex
                (cve_articulo, cve_lote, fecha, origen, destino, cantidad, ajuste, stockinicial,
                 id_TipoMovimiento, cve_usuario, Cve_Almac, Cve_Almac_Origen, Cve_Almac_Destino,
                 Activo, Fec_Ingreso, Id_Motivo, ID_Proveedor_Dueno, Referencia,
                 contenedor_clave, contenedor_lp, pallet_clave, pallet_lp)
                VALUES
                (:cve_articulo, :cve_lote, NOW(), :origen, :destino, :cantidad, :ajuste, :stockinicial,
                 :id_TipoMovimiento, :cve_usuario, :Cve_Almac, :Cve_Almac_Origen, :Cve_Almac_Destino,
                 1, CURDATE(), :Id_Motivo, :ID_Proveedor_Dueno, :Referencia,
                 :contenedor_clave, :contenedor_lp, :pallet_clave, :pallet_lp)";
        $ins = $pdo->prepare($sql);
        $ins->execute([
            ':cve_articulo'       => $k['cve_articulo'] ?? null,
            ':cve_lote'           => $k['cve_lote'] ?? null,
            ':origen'             => $k['origen'] ?? null,
            ':destino'            => $k['destino'] ?? null,
            ':cantidad'           => $k['cantidad'] ?? null,
            ':ajuste'             => $k['ajuste'] ?? null,
            ':stockinicial'       => $k['stockinicial'] ?? null,
            ':id_TipoMovimiento'  => $k['id_TipoMovimiento'] ?? null,
            ':cve_usuario'        => $k['cve_usuario'] ?? 'SISTEMA',
            ':Cve_Almac'          => $k['Cve_Almac'] ?? null,
            ':Cve_Almac_Origen'   => $k['Cve_Almac_Origen'] ?? null,
            ':Cve_Almac_Destino'  => $k['Cve_Almac_Destino'] ?? null,
            ':Id_Motivo'          => $k['Id_Motivo'] ?? null,
            ':ID_Proveedor_Dueno' => $k['ID_Proveedor_Dueno'] ?? null,
            ':Referencia'         => $k['Referencia'] ?? null,
            ':contenedor_clave'   => $k['contenedor_clave'] ?? null,
            ':contenedor_lp'      => $k['contenedor_lp'] ?? null,
            ':pallet_clave'       => $k['pallet_clave'] ?? null,
            ':pallet_lp'          => $k['pallet_lp'] ?? null,
        ]);
    } catch (Throwable $e) {
        // no-op intencional
    }
}

// 3) Modo “help” al abrir en navegador (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    jexit(200, [
        "ok"=>1,
        "service"=>"api_stock",
        "status"=>"running",
        "usage"=>[
            "POST JSON"=>[
                "accion"=>"upsert_stock | add | sub",
                "cve_almac"=>"int",
                "idy_ubica"=>"int",
                "cve_articulo"=>"string",
                "cantidad"=>"number",
                "nivel"=>"piezas | cajas | tarima",
                "cve_lote"=>"string opcional",
                "caducidad"=>"YYYY-MM-DD opcional",
                "epc"=>"string opcional (puede ir vacío)",
                "code"=>"string opcional",
                "ID_Proveedor"=>"int opcional",
                "Cuarentena"=>"0|1 opcional",
                "Referencia"=>"string opcional"
            ]
        ]
    ]);
}

// 4) POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jexit(405, ["ok"=>0,"error"=>"Método no permitido"]);
}

$in = getJsonBody();
if (!$in) jexit(400, ["ok"=>0,"error"=>"JSON inválido"]);

$accion      = (string)($in['accion'] ?? '');
$cve_almac   = $in['cve_almac'] ?? null;
$idy_ubica   = $in['idy_ubica'] ?? null;
$cve_art     = (string)($in['cve_articulo'] ?? '');
$cantidad    = $in['cantidad'] ?? null;
$nivel       = strtolower((string)($in['nivel'] ?? 'piezas'));
$cve_lote    = (string)($in['cve_lote'] ?? '');
$caducidad   = (string)($in['caducidad'] ?? '');
$epc         = (string)($in['epc'] ?? '');
$code        = (string)($in['code'] ?? '');
$idProv      = (int)($in['ID_Proveedor'] ?? 0);
$cuar        = (int)($in['Cuarentena'] ?? 0);
$ref         = (string)($in['Referencia'] ?? '');

if (!in_array($accion, ['upsert_stock','add','sub'], true)) {
    jexit(400, ["ok"=>0,"error"=>"accion inválida"]);
}
if (!is_numeric($cve_almac) || !is_numeric($idy_ubica) || $cve_art === '' || !is_numeric($cantidad)) {
    jexit(400, ["ok"=>0,"error"=>"Campos obligatorios faltantes (cve_almac, idy_ubica, cve_articulo, cantidad)"]);
}
$cve_almac = (int)$cve_almac;
$idy_ubica = (int)$idy_ubica;
$cantidad  = (float)$cantidad;

if (!in_array($nivel, ['piezas','cajas','tarima'], true)) {
    jexit(400, ["ok"=>0,"error"=>"nivel inválido"]);
}

// Validación lote/caducidad según catálogo
$flags = articuloFlags($pdo, $cve_art);
if ($cve_lote !== '' && !$flags['lote']) {
    jexit(400, ["ok"=>0,"error"=>"Este artículo NO permite lote según c_articulo"]);
}
if ($caducidad !== '' && !$flags['caducidad']) {
    jexit(400, ["ok"=>0,"error"=>"Este artículo NO permite caducidad según c_articulo"]);
}
if ($caducidad !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $caducidad)) {
    jexit(400, ["ok"=>0,"error"=>"caducidad inválida (YYYY-MM-DD)"]);
}

// 5) Ejecutar operación
$table = $nivel === 'piezas' ? 'ts_existenciapiezas' : ($nivel === 'cajas' ? 'ts_existenciacajas' : 'ts_existenciatarima');

try {
    $pdo->beginTransaction();

    // Buscar registro existente (por EPC si viene; si no, por llave natural)
    $row = null;

    if ($epc !== '') {
        $st = $pdo->prepare("SELECT * FROM {$table} WHERE epc = :epc LIMIT 1");
        $st->execute([':epc'=>$epc]);
        $row = $st->fetch();
    }

    if (!$row) {
        // llave natural por nivel (lo más estable con tus estructuras)
        if ($nivel === 'piezas') {
            $st = $pdo->prepare("SELECT * FROM ts_existenciapiezas
                                 WHERE cve_almac=:a AND idy_ubica=:u AND cve_articulo=:c AND cve_lote=:l
                                 LIMIT 1");
            $st->execute([':a'=>$cve_almac,':u'=>$idy_ubica,':c'=>$cve_art,':l'=>$cve_lote]);
            $row = $st->fetch();
        } elseif ($nivel === 'cajas') {
            // En cajas puede variar; usamos (almac, ubica, articulo, lote, code) como llave operativa
            $st = $pdo->prepare("SELECT * FROM ts_existenciacajas
                                 WHERE Cve_Almac=:a AND idy_ubica=:u AND cve_articulo=:c AND cve_lote=:l
                                   AND (code = :code OR :code = '')
                                 LIMIT 1");
            $st->execute([':a'=>$cve_almac,':u'=>$idy_ubica,':c'=>$cve_art,':l'=>$cve_lote,':code'=>$code]);
            $row = $st->fetch();
        } else {
            $st = $pdo->prepare("SELECT * FROM ts_existenciatarima
                                 WHERE cve_almac=:a AND idy_ubica=:u AND cve_articulo=:c AND lote=:l
                                   AND (code = :code OR :code = '')
                                 LIMIT 1");
            $st->execute([':a'=>$cve_almac,':u'=>$idy_ubica,':c'=>$cve_art,':l'=>$cve_lote,':code'=>$code]);
            $row = $st->fetch();
        }
    }

    // Calcular nueva existencia
    $existCol = $nivel === 'cajas' ? 'PiezasXCaja' : ($nivel === 'tarima' ? 'existencia' : 'Existencia');
    $current = $row ? (float)($row[$existCol] ?? 0) : 0.0;

    if ($accion === 'upsert_stock') {
        $new = $cantidad; // set
        $delta = $new - $current;
    } elseif ($accion === 'add') {
        $new = $current + $cantidad;
        $delta = $cantidad;
    } else { // sub
        $new = $current - $cantidad;
        $delta = -$cantidad;
    }

    // Upsert
    if ($nivel === 'piezas') {
        if ($row) {
            $id = (int)$row['id'];
            $st = $pdo->prepare("UPDATE ts_existenciapiezas
                                 SET Existencia=:e, ID_Proveedor=:p, Cuarentena=:q,
                                     epc=:epc, code=:code
                                 WHERE id=:id");
            $st->execute([
                ':e'=>$new, ':p'=>$idProv, ':q'=>$cuar,
                ':epc'=>($epc!==''?$epc:($row['epc']??null)),
                ':code'=>($code!==''?$code:($row['code']??null)),
                ':id'=>$id
            ]);
        } else {
            $st = $pdo->prepare("INSERT INTO ts_existenciapiezas
                (cve_almac, idy_ubica, cve_articulo, cve_lote, Existencia, ClaveEtiqueta, ID_Proveedor, Cuarentena, epc, code)
                VALUES
                (:a,:u,:c,:l,:e,NULL,:p,:q,:epc,:code)");
            $st->execute([
                ':a'=>$cve_almac, ':u'=>$idy_ubica, ':c'=>$cve_art, ':l'=>$cve_lote,
                ':e'=>$new, ':p'=>$idProv, ':q'=>$cuar,
                ':epc'=>($epc!==''?$epc:null),
                ':code'=>($code!==''?$code:null),
            ]);
        }
    } elseif ($nivel === 'cajas') {
        if ($row) {
            $id = (int)($row['Id_Caja'] ?? 0);
            // Si no hay PK clara, actualizamos por EPC o por llave natural
            if ($epc !== '') {
                $st = $pdo->prepare("UPDATE ts_existenciacajas
                                     SET PiezasXCaja=:e, epc=:epc, code=:code
                                     WHERE epc=:epc");
                $st->execute([':e'=>$new, ':epc'=>$epc, ':code'=>($code!==''?$code:($row['code']??null))]);
            } else {
                $st = $pdo->prepare("UPDATE ts_existenciacajas
                                     SET PiezasXCaja=:e, code=:code
                                     WHERE Cve_Almac=:a AND idy_ubica=:u AND cve_articulo=:c AND cve_lote=:l
                                     LIMIT 1");
                $st->execute([':e'=>$new,':code'=>($code!==''?$code:($row['code']??null)),':a'=>$cve_almac,':u'=>$idy_ubica,':c'=>$cve_art,':l'=>$cve_lote]);
            }
        } else {
            $st = $pdo->prepare("INSERT INTO ts_existenciacajas
                (idy_ubica, cve_articulo, cve_lote, PiezasXCaja, Id_Caja, Cve_Almac, nTarima, Id_Pzs, epc, code)
                VALUES
                (:u,:c,:l,:e,0,:a,NULL,NULL,:epc,:code)");
            $st->execute([
                ':u'=>$idy_ubica, ':c'=>$cve_art, ':l'=>$cve_lote, ':e'=>$new, ':a'=>$cve_almac,
                ':epc'=>($epc!==''?$epc:null),
                ':code'=>($code!==''?$code:null),
            ]);
        }
    } else { // tarima
        if ($row) {
            if ($epc !== '') {
                $st = $pdo->prepare("UPDATE ts_existenciatarima
                                     SET existencia=:e, epc=:epc, code=:code
                                     WHERE epc=:epc");
                $st->execute([':e'=>$new, ':epc'=>$epc, ':code'=>($code!==''?$code:($row['code']??null))]);
            } else {
                $st = $pdo->prepare("UPDATE ts_existenciatarima
                                     SET existencia=:e, code=:code
                                     WHERE cve_almac=:a AND idy_ubica=:u AND cve_articulo=:c AND lote=:l
                                     LIMIT 1");
                $st->execute([':e'=>$new,':code'=>($code!==''?$code:($row['code']??null)),':a'=>$cve_almac,':u'=>$idy_ubica,':c'=>$cve_art,':l'=>$cve_lote]);
            }
        } else {
            $st = $pdo->prepare("INSERT INTO ts_existenciatarima
                (cve_almac, idy_ubica, cve_articulo, lote, Fol_Folio, ntarima, capacidad, existencia, Activo, ID_Proveedor, Cuarentena, epc, code)
                VALUES
                (:a,:u,:c,:l,0,0,0,:e,1,:p,:q,:epc,:code)");
            $st->execute([
                ':a'=>$cve_almac, ':u'=>$idy_ubica, ':c'=>$cve_art, ':l'=>$cve_lote,
                ':e'=>$new, ':p'=>$idProv, ':q'=>$cuar,
                ':epc'=>($epc!==''?$epc:null),
                ':code'=>($code!==''?$code:null),
            ]);
        }
    }

    // Kardex (delta real)
    kardex($pdo, [
        'cve_articulo'      => $cve_art,
        'cve_lote'          => ($cve_lote !== '' ? $cve_lote : null),
        'origen'            => "UBI:$idy_ubica",
        'destino'           => "UBI:$idy_ubica",
        'cantidad'          => abs($delta),
        'ajuste'            => $delta,
        'stockinicial'      => $current,
        'id_TipoMovimiento' => 1, // ajusta catálogo de movimientos si lo tienes
        'cve_usuario'       => 'SISTEMA',
        'Cve_Almac'         => (string)$cve_almac,
        'Cve_Almac_Origen'  => (string)$cve_almac,
        'Cve_Almac_Destino' => (string)$cve_almac,
        'ID_Proveedor_Dueno'=> ($idProv ?: null),
        'Referencia'        => ($ref !== '' ? $ref : null),
    ]);

    $pdo->commit();

    jexit(200, [
        "ok"=>1,
        "accion"=>$accion,
        "nivel"=>$nivel,
        "table"=>$table,
        "cve_articulo"=>$cve_art,
        "cve_lote"=>$cve_lote,
        "caducidad"=>$caducidad,
        "epc"=>$epc,
        "code"=>$code,
        "prev"=>$current,
        "new"=>$new,
        "delta"=>$delta
    ]);

} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    jexit(500, ["ok"=>0,"error"=>$e->getMessage()]);
}
