<?php
/*****************************************************************
 * PROMOCIONES V2 API (LEGACY COMPATIBLE + V2 UI)
 * Ubicación: /public/api/promociones/promociones_v2_api.php
 *
 * - save_full hace TODO en 1 transacción
 * - TipMed se guarda como id_umed (INT) (no cve_umed)
 * - listapromo.id puede NO ser AUTO_INCREMENT (se genera por MAX(id)+1)
 *****************************************************************/

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../../../app/db.php';

try {
    $pdo = db_pdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => 0, 'error' => 'DB_CONNECT_ERROR', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

/* =========================
   Helpers
========================= */
function out(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function in_str(string $k, string $default = ''): string {
    $v = $_POST[$k] ?? $_GET[$k] ?? $default;
    return is_string($v) ? trim($v) : $default;
}
function in_int(string $k, int $default = 0): int {
    $v = $_POST[$k] ?? $_GET[$k] ?? null;
    if ($v === null || $v === '') return $default;
    return (int)$v;
}
function in_float(string $k, float $default = 0.0): float {
    $v = $_POST[$k] ?? $_GET[$k] ?? null;
    if ($v === null || $v === '') return $default;
    if (is_string($v)) $v = str_replace(',', '.', $v);
    return (float)$v;
}
function upper(string $s): string {
    $s = trim($s);
    if ($s === '') return '';
    return mb_strtoupper($s, 'UTF-8');
}
function lower(string $s): string {
    return mb_strtolower(trim($s), 'UTF-8');
}

/**
 * Acepta:
 *  - YYYY-MM-DD
 *  - DD/MM/YYYY
 *  - DDMMYYYY
 * Regresa YYYY-MM-DD o '' si vacío. Lanza excepción si inválida.
 */
function normalize_date(string $v): string {
    $v = trim($v);
    if ($v === '') return '';

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
        [$y,$m,$d] = explode('-', $v);
        if (!checkdate((int)$m, (int)$d, (int)$y)) throw new Exception("FECHA_INVALIDA: $v");
        return $v;
    }

    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $v)) {
        [$d,$m,$y] = explode('/', $v);
        if (!checkdate((int)$m, (int)$d, (int)$y)) throw new Exception("FECHA_INVALIDA: $v");
        return sprintf('%04d-%02d-%02d', (int)$y, (int)$m, (int)$d);
    }

    if (preg_match('/^\d{8}$/', $v)) {
        $d = substr($v, 0, 2);
        $m = substr($v, 2, 2);
        $y = substr($v, 4, 4);
        if (!checkdate((int)$m, (int)$d, (int)$y)) throw new Exception("FECHA_INVALIDA: $v");
        return sprintf('%04d-%02d-%02d', (int)$y, (int)$m, (int)$d);
    }

    throw new Exception("FORMATO_FECHA_NO_SOPORTADO: $v");
}

function validate_range(string $ini, string $fin): void {
    if ($ini !== '' && $fin !== '' && $fin < $ini) {
        throw new Exception('RANGO_FECHAS_INVALIDO: Fecha fin no puede ser menor a fecha inicio');
    }
}

/**
 * TipMed en detallegpopromo es INT (id_umed). Resolver por:
 *  1) si viene numérico => usarlo
 *  2) mapear por c_unimed (cve_umed o mav_cveunimed)
 *  3) fallback: id_umed de PZA si existe; si no, 1
 */
function resolve_umed_id(PDO $pdo, string $tipmed_raw): int {
    $t = trim($tipmed_raw);
    if ($t !== '' && ctype_digit($t)) return (int)$t;

    $t = upper($t);
    if ($t === '') $t = 'PZA';

    $st = $pdo->prepare("SELECT id_umed FROM c_unimed WHERE UPPER(cve_umed)=? OR UPPER(mav_cveunimed)=? LIMIT 1");
    $st->execute([$t, $t]);
    $id = (int)($st->fetchColumn() ?: 0);
    if ($id > 0) return $id;

    $st = $pdo->prepare("SELECT id_umed FROM c_unimed WHERE UPPER(cve_umed)='PZA' OR UPPER(mav_cveunimed)='PZA' LIMIT 1");
    $st->execute();
    $id = (int)($st->fetchColumn() ?: 0);
    return $id > 0 ? $id : 1;
}

/** Genera id para listapromo si no es AUTO_INCREMENT. */
function next_listapromo_id(PDO $pdo): int {
    $id = (int)$pdo->query("SELECT IFNULL(MAX(id),0)+1 AS nx FROM listapromo")->fetchColumn();
    return max(1, $id);
}

/** Folio (Lista) corporativo simple: PROMO-YYYYMMDD-### */
function next_lista_folio(PDO $pdo): string {
    $prefix = 'PROMO-' . date('Ymd') . '-';
    $st = $pdo->prepare("SELECT Lista FROM listapromo WHERE Lista LIKE ? ORDER BY Lista DESC LIMIT 1");
    $st->execute([$prefix . '%']);
    $last = (string)($st->fetchColumn() ?: '');
    $seq = 0;
    if ($last !== '' && preg_match('/-(\d{3})$/', $last, $m)) $seq = (int)$m[1];
    $seq++;
    return $prefix . str_pad((string)$seq, 3, '0', STR_PAD_LEFT);
}

/* =========================
   Router
========================= */
$action = lower(in_str('action', ''));

if ($action === '') {
    out(['ok' => 0, 'error' => 'ACCION_INVALIDA'], 400);
}

try {

    if ($action === 'ping') {
        out(['ok' => 1, 'msg' => 'OK', 'ts' => date('Y-m-d H:i:s')]);
    }

    if ($action === 'save_full') {
        $id_empresa = in_int('id_empresa', 0);
        if ($id_empresa <= 0) $id_empresa = in_int('cve_cia', 0);

        $cve_almac  = trim(in_str('cve_almac', ''));
        $tipo       = upper(in_str('tipo', upper(in_str('tipo_promo', 'UNIDADES'))));
        $nombre     = upper(in_str('nombre', upper(in_str('promo_nombre', ''))));
        $lista      = upper(in_str('lista', ''));

        $fecha_i    = normalize_date(in_str('fecha_i', in_str('vig_ini', '')));
        $fecha_f    = normalize_date(in_str('fecha_f', in_str('vig_fin', '')));
        validate_range($fecha_i, $fecha_f);

        $grupo      = upper(in_str('grupo', ''));
        $activa     = in_int('activa', 1);
        $caduca     = in_int('caduca', 0);

        // Base (UI manda base_val)
        $base_articulo = upper(in_str('base_articulo', in_str('base_val', '')));
        $base_cantidad = in_float('base_cantidad', in_float('th_qty', 1.0));
        if ($base_cantidad <= 0) $base_cantidad = 1;
        $base_tipmed_raw = in_str('base_tipmed', in_str('th_um', 'PZA'));

        // Reward (UI manda rw_val, rw_qty, rw_um)
        $rw_articulo = upper(in_str('rw_articulo', in_str('rw_val', '')));
        $rw_cantidad = in_float('rw_cantidad', in_float('rw_qty', 0.0));
        $rw_tipmed_raw = in_str('rw_tipmed', in_str('rw_um', 'PZA'));

        if ($id_empresa <= 0) throw new Exception('FALTA_ID_EMPRESA');
        if ($cve_almac === '') throw new Exception('FALTA_ALMACEN');
        if ($nombre === '') throw new Exception('FALTA_NOMBRE');
        if ($tipo === '') throw new Exception('FALTA_TIPO');
        if ($base_articulo === '') throw new Exception('FALTA_BASE_ARTICULO');
        if ($rw_articulo === '') throw new Exception('FALTA_RW_ARTICULO');
        if ($rw_cantidad <= 0) throw new Exception('FALTA_RW_CANTIDAD');

        if ($lista === '') $lista = next_lista_folio($pdo);

        // TipMed INT
        $base_tipmed_id = resolve_umed_id($pdo, $base_tipmed_raw);
        $rw_tipmed_id   = resolve_umed_id($pdo, $rw_tipmed_raw);

        $pdo->beginTransaction();
        try {
            // Header: id legacy
            $promo_id = next_listapromo_id($pdo);

            $pdo->prepare("INSERT INTO listapromo
                (id, Lista, Descripcion, Caduca, FechaI, FechaF, Grupo, Activa, Tipo, Cve_Almac)
                VALUES (?,?,?,?,?,?,?,?,?,?)"
            )->execute([
                $promo_id,
                $lista,
                $nombre,
                $caduca,
                ($fecha_i !== '' ? $fecha_i : null),
                ($fecha_f !== '' ? $fecha_f : null),
                ($grupo !== '' ? $grupo : null),
                $activa,
                $tipo,
                $cve_almac
            ]);

            // Detalle: 2 renglones
            $pdo->prepare("DELETE FROM detallegpopromo WHERE PromoId = ?")->execute([$promo_id]);

            $pdo->prepare("INSERT INTO detallegpopromo (PromoId, Articulo, cve_gpoart, Cantidad, TipMed, IdEmpresa)
                           VALUES (?,?,?,?,?,?)")->execute([
                $promo_id,
                $base_articulo,
                null,
                $base_cantidad,
                $base_tipmed_id,
                $id_empresa
            ]);

            $pdo->prepare("INSERT INTO detallegpopromo (PromoId, Articulo, cve_gpoart, Cantidad, TipMed, IdEmpresa)
                           VALUES (?,?,?,?,?,?)")->execute([
                $promo_id,
                $rw_articulo,
                null,
                $rw_cantidad,
                $rw_tipmed_id,
                $id_empresa
            ]);

            $pdo->commit();
            out(['ok' => 1, 'msg' => 'PROMO_CREATED_WITH_DETAIL', 'id' => $promo_id, 'folio' => $lista]);

        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    out(['ok' => 0, 'error' => 'ACCION_NO_IMPLEMENTADA'], 400);

} catch (Throwable $e) {
    out(['ok' => 0, 'error' => $e->getMessage()], 200);
}
