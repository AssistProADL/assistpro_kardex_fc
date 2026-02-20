<?php
/*****************************************************************
 * PROMOCIONES V2 API (LEGACY COMPATIBLE)
 * Ubicación: /public/api/promociones/promociones_v2_api.php
 *
 * Tablas legacy:
 *  - listapromo(id, Lista, Descripcion, Caduca, FechaI, FechaF, Grupo, Activa, Tipo, Cve_Almac)
 *  - detallegpopromo(Id, PromoId, Articulo, cve_gpoart, Cantidad, TipMed, IdEmpresa)
 *  - listapromomaster(id, ListaMaster, Promociones, Cve_Almac)
 *  - detallepromaster (opcional, si deseas normalizar master->promos después)
 *****************************************************************/

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../../../app/db.php'; // ajusta si tu árbol cambia

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

function upper(string $s): string {
    $s = trim($s);
    if ($s === '') return '';
    return mb_strtoupper($s, 'UTF-8');
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

    // YYYY-MM-DD
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
        [$y,$m,$d] = explode('-', $v);
        if (!checkdate((int)$m, (int)$d, (int)$y)) throw new Exception("FECHA_INVALIDA: $v");
        return $v;
    }

    // DD/MM/YYYY
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $v)) {
        [$d,$m,$y] = explode('/', $v);
        if (!checkdate((int)$m, (int)$d, (int)$y)) throw new Exception("FECHA_INVALIDA: $v");
        return sprintf('%04d-%02d-%02d', (int)$y, (int)$m, (int)$d);
    }

    // DDMMYYYY
    if (preg_match('/^\d{8}$/', $v)) {
        $d = substr($v, 0, 2);
        $m = substr($v, 2, 2);
        $y = substr($v, 4, 4);
        if (!checkdate((int)$m, (int)$d, (int)$y)) throw new Exception("FECHA_INVALIDA: $v");
        return sprintf('%04d-%02d-%02d', (int)$y, (int)$m, (int)$d);
    }

    throw new Exception("FORMATO_FECHA_NO_SOPORTADO: $v");
}

/** Valida rango (si ambos vienen) */
function validate_range(string $ini, string $fin): void {
    if ($ini !== '' && $fin !== '' && $fin < $ini) {
        throw new Exception('RANGO_FECHAS_INVALIDO: Fecha fin no puede ser menor a fecha inicio');
    }
}

/* =========================
   Router
========================= */
$action = lower(in_str('action', ''));

function lower(string $s): string {
    return mb_strtolower(trim($s), 'UTF-8');
}

if ($action === '') {
    out([
        'ok' => 0,
        'error' => 'ACCIÓN NO VÁLIDA',
        'hint' => [
            'GET  ?action=ping',
            'GET  ?action=list&cve_almac=WH8&q=CHERRY&page=1&limit=25',
            'GET  ?action=get&id=50',
            'POST action=save_header',
            'POST action=save_detail',
            'POST action=save_full',
            'POST action=group_save',
        ]
    ], 400);
}

try {

    /* =========================
       ping
    ========================= */
    if ($action === 'ping') {
        out(['ok' => 1, 'msg' => 'OK', 'ts' => date('Y-m-d H:i:s')]);
    }

    /* =========================
       list (promociones)
       filtros: cve_almac, q, tipo, activa
    ========================= */
    if ($action === 'list') {
        $cve_almac = trim(in_str('cve_almac', ''));
        $q         = upper(in_str('q', ''));
        $tipo      = upper(in_str('tipo', ''));
        $activa    = in_str('activa', '1'); // default solo activas
        $page      = max(1, in_int('page', 1));
        $limit     = min(200, max(1, in_int('limit', 25)));
        $off       = ($page - 1) * $limit;

        $w = [];
        $p = [];

        if ($cve_almac !== '') { $w[] = "Cve_Almac = :cve"; $p[':cve'] = $cve_almac; }
        if ($tipo !== '')      { $w[] = "UPPER(Tipo) = :tipo"; $p[':tipo'] = $tipo; }
        if ($activa !== '')    { $w[] = "Activa = :act"; $p[':act'] = (int)$activa; }

        if ($q !== '') {
            // Busca por Lista (folio) o Descripcion (nombre)
            $w[] = "(UPPER(Lista) LIKE :q OR UPPER(Descripcion) LIKE :q)";
            $p[':q'] = "%$q%";
        }

        $where = $w ? ('WHERE ' . implode(' AND ', $w)) : '';

        $sqlCount = "SELECT COUNT(*) FROM listapromo $where";
        $stc = $GLOBALS['pdo']->prepare($sqlCount);
        $stc->execute($p);
        $total = (int)$stc->fetchColumn();

        $sql = "
            SELECT
                id,
                Lista,
                Descripcion,
                Tipo,
                Activa,
                Grupo,
                Caduca,
                FechaI,
                FechaF,
                Cve_Almac
            FROM listapromo
            $where
            ORDER BY id DESC
            LIMIT $limit OFFSET $off
        ";

        $st = $GLOBALS['pdo']->prepare($sql);
        $st->execute($p);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        out([
            'ok' => 1,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'rows' => $rows
        ]);
    }

    /* =========================
       get (promo header + detail)
    ========================= */
    if ($action === 'get') {
        $id = in_int('id', 0);
        if ($id <= 0) throw new Exception('ID_INVALIDO');

        $st = $pdo->prepare("SELECT * FROM listapromo WHERE id = ?");
        $st->execute([$id]);
        $h = $st->fetch(PDO::FETCH_ASSOC);
        if (!$h) throw new Exception('PROMO_NO_ENCONTRADA');

        $sd = $pdo->prepare("SELECT * FROM detallegpopromo WHERE PromoId = ? ORDER BY Id ASC");
        $sd->execute([$id]);
        $d = $sd->fetchAll(PDO::FETCH_ASSOC);

        out(['ok' => 1, 'header' => $h, 'detail' => $d]);
    }

    /* =========================
       save_header
       - Crea/actualiza en listapromo
       Requeridos:
         - id_empresa (int)
         - cve_almac (string)
         - tipo (UNIDADES|TICKET|ACUMULADA...)
         - nombre (Descripcion)
       Opcionales:
         - id (si update)
         - lista (folio/clave promo) si no, se genera
         - fecha_i, fecha_f
         - grupo (string)
         - activa (0/1)
         - caduca (0/1)
    ========================= */
    if ($action === 'save_header') {
        $id          = in_int('id', 0);
        $id_empresa  = in_int('id_empresa', 0);
        $cve_almac   = trim(in_str('cve_almac', ''));
        $tipo        = upper(in_str('tipo', 'UNIDADES'));
        $nombre      = upper(in_str('nombre', ''));
        $lista       = upper(in_str('lista', '')); // folio/clave promo
        $grupo       = upper(in_str('grupo', ''));
        $activa      = in_int('activa', 1);
        $caduca      = in_int('caduca', 0);

        $fecha_i_raw = in_str('fecha_i', '');
        $fecha_f_raw = in_str('fecha_f', '');
        $fecha_i     = normalize_date($fecha_i_raw);
        $fecha_f     = normalize_date($fecha_f_raw);
        validate_range($fecha_i, $fecha_f);

        if ($id_empresa <= 0) throw new Exception('FALTA_ID_EMPRESA');
        if ($cve_almac === '') throw new Exception('FALTA_ALMACEN');
        if ($nombre === '') throw new Exception('FALTA_NOMBRE');
        if ($tipo === '') throw new Exception('FALTA_TIPO');

        // Generación folio/Lista (si no viene)
        if ($lista === '') {
            // Folio simple: PROMO-YYYYMMDD-HHMMSS (controlable luego por c_folios)
            $lista = 'PROMO-' . date('Ymd-His');
        }

        if ($id > 0) {
            $sql = "UPDATE listapromo
                    SET Lista=?, Descripcion=?, Caduca=?, FechaI=?, FechaF=?, Grupo=?, Activa=?, Tipo=?, Cve_Almac=?
                    WHERE id=?";
            $pdo->prepare($sql)->execute([
                $lista, $nombre, $caduca,
                ($fecha_i !== '' ? $fecha_i : null),
                ($fecha_f !== '' ? $fecha_f : null),
                ($grupo !== '' ? $grupo : null),
                $activa, $tipo, $cve_almac,
                $id
            ]);

            out(['ok' => 1, 'id' => $id, 'msg' => 'HEADER_UPDATED', 'Lista' => $lista]);
        } else {
            $sql = "INSERT INTO listapromo (Lista, Descripcion, Caduca, FechaI, FechaF, Grupo, Activa, Tipo, Cve_Almac)
                    VALUES (?,?,?,?,?,?,?,?,?)";
            $pdo->prepare($sql)->execute([
                $lista, $nombre, $caduca,
                ($fecha_i !== '' ? $fecha_i : null),
                ($fecha_f !== '' ? $fecha_f : null),
                ($grupo !== '' ? $grupo : null),
                $activa, $tipo, $cve_almac
            ]);
            $newId = (int)$pdo->lastInsertId();

            out(['ok' => 1, 'id' => $newId, 'msg' => 'HEADER_CREATED', 'Lista' => $lista]);
        }
    }

    /* =========================
       save_detail
       Inserta detalle en detallegpopromo
       Requeridos:
         - promo_id (id listapromo)
         - id_empresa
         - articulo (cve_articulo / producto)
         - cantidad
         - tipmed (UM)
       Opcional:
         - cve_gpoart (si aplica como “grupo”/disparador)
       NOTA: si quieres un detalle “limpio” (reemplazar), usa replace=1
    ========================= */
    if ($action === 'save_detail') {
        $promo_id   = in_int('promo_id', 0);
        $id_empresa = in_int('id_empresa', 0);
        $articulo   = upper(in_str('articulo', ''));
        $cve_gpoart = upper(in_str('cve_gpoart', ''));
        $cantidad   = (float)str_replace(',', '.', in_str('cantidad', '0'));
        $tipmed     = upper(in_str('tipmed', 'PZA'));
        $replace    = in_int('replace', 0); // 1 = borra y reinserta

        if ($promo_id <= 0) throw new Exception('FALTA_PROMO_ID');
        if ($id_empresa <= 0) throw new Exception('FALTA_ID_EMPRESA');
        if ($articulo === '') throw new Exception('FALTA_ARTICULO');
        if ($cantidad <= 0) throw new Exception('CANTIDAD_INVALIDA');
        if ($tipmed === '') throw new Exception('FALTA_TIPMED');

        $pdo->beginTransaction();
        try {
            if ($replace === 1) {
                $pdo->prepare("DELETE FROM detallegpopromo WHERE PromoId = ?")->execute([$promo_id]);
            }

            $sql = "INSERT INTO detallegpopromo (PromoId, Articulo, cve_gpoart, Cantidad, TipMed, IdEmpresa)
                    VALUES (?,?,?,?,?,?)";
            $pdo->prepare($sql)->execute([
                $promo_id, $articulo,
                ($cve_gpoart !== '' ? $cve_gpoart : null),
                $cantidad, $tipmed, $id_empresa
            ]);

            $pdo->commit();
            out(['ok' => 1, 'msg' => 'DETAIL_SAVED']);
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /* =========================
       save_full (encabezado + detalle)
       Este es el que debes usar desde el UI para no “quedarte sin detalle”.

       POST:
         - id_empresa, cve_almac, tipo, nombre, lista(opc), fecha_i, fecha_f, grupo(opc)
         - base_articulo (disparador)  [si es producto]
         - rw_articulo (regalo)        [producto obsequio]
         - rw_cantidad
         - rw_tipmed
       Reglas:
         - Creamos header en listapromo
         - Insertamos 2 renglones en detallegpopromo:
             1) disparador (Cantidad=objetivo si aplica; si no, 1)
             2) regalo (Cantidad=rw_cantidad)
         Nota: si tu legacy interpreta “detalle” distinto, lo ajustamos,
               pero esto ya te deja trazabilidad completa.
    ========================= */
    /* =========================
   save_full (V2 compatible con UI actual)
========================= */
if ($action === 'save_full') {

    // ====== Compatibilidad nombres UI V2 ======
    $id_empresa = in_int('id_empresa', 0);
    if ($id_empresa <= 0) {
        $id_empresa = in_int('cve_cia', 0); // viene así desde UI
    }

    $cve_almac  = trim(in_str('cve_almac', ''));
    $tipo       = upper(in_str('tipo', ''));
    if ($tipo === '') {
        $tipo = upper(in_str('tipo_promo', 'UNIDADES'));
    }

    $nombre     = upper(in_str('nombre', ''));
    if ($nombre === '') {
        $nombre = upper(in_str('promo_nombre', ''));
    }

    $lista      = upper(in_str('lista', ''));

    $fecha_i = normalize_date(in_str('fecha_i', in_str('vig_ini', '')));
    $fecha_f = normalize_date(in_str('fecha_f', in_str('vig_fin', '')));
    validate_range($fecha_i, $fecha_f);

    $grupo   = upper(in_str('grupo', ''));
    $activa  = in_int('activa', 1);
    $caduca  = in_int('caduca', 0);

    // ===== BASE =====
    $base_articulo = upper(in_str('base_articulo', ''));
    if ($base_articulo === '') {
        $base_articulo = upper(in_str('base_val', ''));
    }

    $base_cantidad = (float)str_replace(',', '.', in_str('base_cantidad', in_str('th_qty', '1')));
    if ($base_cantidad <= 0) $base_cantidad = 1;

    $base_tipmed = upper(in_str('base_tipmed', in_str('th_um', 'PZA')));

    // ===== REWARD =====
    $rw_articulo = upper(in_str('rw_articulo', ''));
    if ($rw_articulo === '') {
        $rw_articulo = upper(in_str('rw_val', ''));
    }

    $rw_cantidad = (float)str_replace(',', '.', in_str('rw_cantidad', in_str('rw_qty', '0')));
    $rw_tipmed   = upper(in_str('rw_tipmed', in_str('rw_um', 'PZA')));

    // ===== VALIDACIONES =====
    if ($id_empresa <= 0) throw new Exception('FALTA_ID_EMPRESA');
    if ($cve_almac === '') throw new Exception('FALTA_ALMACEN');
    if ($nombre === '') throw new Exception('FALTA_NOMBRE');
    if ($tipo === '') throw new Exception('FALTA_TIPO');
    if ($base_articulo === '') throw new Exception('FALTA_BASE_ARTICULO');
    if ($rw_articulo === '') throw new Exception('FALTA_RW_ARTICULO');
    if ($rw_cantidad <= 0) throw new Exception('FALTA_RW_CANTIDAD');

    if ($lista === '') $lista = 'PROMO-' . date('Ymd-His');

    $pdo->beginTransaction();
    try {

        // HEADER
        $pdo->prepare("
            INSERT INTO listapromo 
            (Lista, Descripcion, Caduca, FechaI, FechaF, Grupo, Activa, Tipo, Cve_Almac)
            VALUES (?,?,?,?,?,?,?,?,?)
        ")->execute([
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

        $promo_id = (int)$pdo->lastInsertId();

        // BASE
        $pdo->prepare("
            INSERT INTO detallegpopromo
            (PromoId, Articulo, cve_gpoart, Cantidad, TipMed, IdEmpresa)
            VALUES (?,?,?,?,?,?)
        ")->execute([
            $promo_id,
            $base_articulo,
            null,
            $base_cantidad,
            $base_tipmed,
            $id_empresa
        ]);

        // REWARD
        $pdo->prepare("
            INSERT INTO detallegpopromo
            (PromoId, Articulo, cve_gpoart, Cantidad, TipMed, IdEmpresa)
            VALUES (?,?,?,?,?,?)
        ")->execute([
            $promo_id,
            $rw_articulo,
            null,
            $rw_cantidad,
            $rw_tipmed,
            $id_empresa
        ]);

        $pdo->commit();

        out([
            'ok' => 1,
            'msg' => 'PROMO_CREATED_WITH_DETAIL',
            'id' => $promo_id,
            'folio' => $lista
        ]);

    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

    /* =========================
       group_save (listapromomaster)
       Crea/actualiza grupo master que agrupa N promociones
       POST:
         - id (opcional update)
         - cve_almac (req)
         - lista_master (req)  -> nombre/clave del grupo
         - promociones (req)   -> CSV de ids de listapromo (ej "50,51,52")
    ========================= */
    if ($action === 'group_save') {
        $id          = in_int('id', 0);
        $cve_almac   = trim(in_str('cve_almac', ''));
        $lista_master= upper(in_str('lista_master', ''));
        $promos_csv  = trim(in_str('promociones', ''));

        if ($cve_almac === '') throw new Exception('FALTA_ALMACEN');
        if ($lista_master === '') throw new Exception('FALTA_LISTA_MASTER');
        if ($promos_csv === '') throw new Exception('FALTA_PROMOCIONES');

        // sanitiza CSV
        $ids = array_filter(array_map('trim', explode(',', $promos_csv)), fn($x) => $x !== '');
        foreach ($ids as $x) {
            if (!ctype_digit($x)) throw new Exception('PROMOCIONES_INVALIDAS (solo ids numéricos, CSV)');
        }
        $promos_csv = implode(',', $ids);

        if ($id > 0) {
            $pdo->prepare("UPDATE listapromomaster SET ListaMaster=?, Promociones=?, Cve_Almac=? WHERE id=?")
                ->execute([$lista_master, $promos_csv, $cve_almac, $id]);
            out(['ok' => 1, 'msg' => 'MASTER_UPDATED', 'id' => $id]);
        } else {
            $pdo->prepare("INSERT INTO listapromomaster (ListaMaster, Promociones, Cve_Almac) VALUES (?,?,?)")
                ->execute([$lista_master, $promos_csv, $cve_almac]);
            $newId = (int)$pdo->lastInsertId();
            out(['ok' => 1, 'msg' => 'MASTER_CREATED', 'id' => $newId]);
        }
    }

    /* =========================
       delete (soft)
    ========================= */
    if ($action === 'delete') {
        $id = in_int('id', 0);
        if ($id <= 0) throw new Exception('ID_INVALIDO');

        $pdo->prepare("UPDATE listapromo SET Activa=0 WHERE id=?")->execute([$id]);
        out(['ok' => 1, 'msg' => 'PROMO_DISABLED', 'id' => $id]);
    }

    out(['ok' => 0, 'error' => 'ACCIÓN NO VÁLIDA'], 400);

} catch (Throwable $e) {
    // Respuesta siempre JSON (evita el “Unexpected token <” del front)
    out([
        'ok' => 0,
        'error' => $e->getMessage()
    ], 200);
}
