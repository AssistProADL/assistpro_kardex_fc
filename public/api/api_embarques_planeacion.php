<?php
// public/api/api_embarques_planeacion.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/auth_check.php';
require_once __DIR__ . '/../../app/db.php';

$cfg = __DIR__ . '/../../config.php';
if (file_exists($cfg))
    require_once $cfg;

/* ============================================================
   Helpers
============================================================= */

function api_ok($data = [], array $extra = []): void
{
    echo json_encode(array_merge(['ok' => true, 'data' => $data], $extra));
    exit;
}

function api_error(string $msg, array $extra = []): void
{
    http_response_code(500);
    echo json_encode(array_merge(['ok' => false, 'error' => $msg], $extra));
    exit;
}

function param(string $name, $default = null)
{
    if (isset($_POST[$name]))
        return $_POST[$name];
    if (isset($_GET[$name]))
        return $_GET[$name];
    return $default;
}

function ap_pdo(): PDO
{
    if (function_exists('db_pdo')) {
        $pdo = db_pdo();
        if ($pdo instanceof PDO)
            return $pdo;
    }
    api_error("No existe conexión PDO");
}

/* ============================================================
   Dispatcher
============================================================= */

$action = param('action', 'ping');

try {

    $pdo = ap_pdo();

    /* ============================================================
       0) Ping
    ============================================================= */
    if ($action === 'ping') {
        api_ok([
            'message' => 'API Planeación Embarques OK',
            'time' => date('Y-m-d H:i:s'),
        ]);
    }

    /* ============================================================
       1) cargarGridPrincipal
    ============================================================= */
    if ($action === 'cargarGridPrincipal') {

        /* ================================
           Lectura de filtros
        ================================= */
        $page = max(1, (int) param('page', 1));
        $pageSize = max(1, (int) param('pageSize', 25));
        $sinPag = (param('sin_paginacion') == '1');

        $f = [
            'empresa' => trim((string) param('empresa', '')),
            'almacen' => trim((string) param('almacen', '')),
            'ruta' => trim((string) param('ruta', '')),
            'cliente' => trim((string) param('cliente', '')),
            'isla' => trim((string) param('isla', '')),
            'colonia' => trim((string) param('colonia', '')),
            'cpostal' => trim((string) param('cpostal', '')),
            'fecha_desde' => trim((string) param('fecha_desde', '')),
            'fecha_hasta' => trim((string) param('fecha_hasta', '')),
            'estatus' => trim((string) param('estatus', '')),
            'texto' => trim((string) param('texto', '')),
        ];

        /* ================================
           Resolver almacén por ID o clave
        ================================= */
        $idAlm = null;

        if ($f['almacen'] !== '') {
            if (ctype_digit($f['almacen'])) {
                $idAlm = (int) $f['almacen'];
            }

            $idFromClave = db_val("
                SELECT id
                FROM c_almacenp
                WHERE clave = :c
            ", [':c' => $f['almacen']]);

            if ($idFromClave !== null) {
                $idAlm = (int) $idFromClave;
            }
        }

        /* ================================
           WHERE dinámico
        ================================= */

        $w = [];
        $p = [];

        /* 1) EMPRESA dependiente de ALMACÉN via cve_cia */
        if ($f['empresa'] !== '') {
            $w[] = "rel.cve_almac IN (
                        SELECT id
                        FROM c_almacenp
                        WHERE cve_cia = :empresa
                    )";
            $p[':empresa'] = $f['empresa'];
        }

        /* 2) Subpedido cerrado y ubicación válida */
        $w[] = "sub.status = 'C'";
        $w[] = "ue.AreaStagging = 'N'";

        /* 3) Almacén */
        if ($idAlm !== null) {
            $w[] = "rel.cve_almac = :alm";
            $p[':alm'] = $idAlm;
        }

        /* 4) Ruta */
        if ($f['ruta'] !== '') {
            $w[] = "rt.ID_Ruta = :ruta";
            $p[':ruta'] = $f['ruta'];
        }

        /* 5) Cliente */
        if ($f['cliente'] !== '') {
            $w[] = "(cli.id_cliente = :cl OR des.id_destinatario = :cl)";
            $p[':cl'] = $f['cliente'];
        }

        /* 6) Colonia */
        if ($f['colonia'] !== '') {
            $w[] = "(des.colonia = :col OR cli.Colonia = :col)";
            $p[':col'] = $f['colonia'];
        }

        /* 7) CP */
        if ($f['cpostal'] !== '') {
            $w[] = "(des.postal = :cp OR cli.CodigoPostal = :cp)";
            $p[':cp'] = $f['cpostal'];
        }

        /* 8) Isla */
        if ($f['isla'] !== '') {
            $w[] = "ue.ID_Embarque = :isla";
            $p[':isla'] = $f['isla'];
        }

        /* 9) Fechas */
        if ($f['fecha_desde'] !== '') {
            $w[] = "ped.Fec_Entrega >= :fd";
            $p[':fd'] = $f['fecha_desde'] . ' 00:00:00';
        }

        if ($f['fecha_hasta'] !== '') {
            $w[] = "ped.Fec_Entrega < :fh";
            $p[':fh'] = date('Y-m-d', strtotime($f['fecha_hasta'] . ' +1 day')) . ' 00:00:00';
        }

        /* 10) Estatus */
        if ($f['estatus'] !== '') {
            $w[] = "sub.status = :est";
            $p[':est'] = $f['estatus'];
        }

        /* 11) Texto general */
        if ($f['texto'] !== '') {
            $w[] = "(
                ped.Fol_folio             LIKE :tx OR
                cli.RazonSocial           LIKE :tx OR
                cli.RazonComercial        LIKE :tx OR
                des.razonsocial           LIKE :tx OR
                des.direccion             LIKE :tx OR
                des.colonia               LIKE :tx OR
                cli.Colonia               LIKE :tx OR
                des.postal                LIKE :tx OR
                cli.CodigoPostal          LIKE :tx
            )";
            $p[':tx'] = '%' . $f['texto'] . '%';
        }

        $WHERE = $w ? 'WHERE ' . implode(' AND ', $w) : '';

        /* ================================
           FROM (NO SE TOCA)
        ================================= */
        $FROM = "
            FROM th_subpedido sub
            INNER JOIN th_pedido ped
                ON ped.Fol_folio = sub.fol_folio
            INNER JOIN Rel_PedidoDest rpd
                ON rpd.Fol_Folio = sub.fol_folio
            INNER JOIN c_destinatarios des
                ON des.id_destinatario = rpd.Id_Destinatario
            INNER JOIN c_cliente cli
                ON cli.Cve_Clte = ped.Cve_clte
            LEFT JOIN t_clientexruta tcr
                ON tcr.clave_cliente = des.id_destinatario
            LEFT JOIN t_ruta rt
                ON rt.ID_Ruta = tcr.clave_ruta
                OR rt.ID_Ruta = ped.cve_ubicacion
                OR rt.ID_Ruta = ped.ruta
            INNER JOIN rel_uembarquepedido rel
                ON rel.fol_folio = sub.fol_folio
            INNER JOIN t_ubicacionembarque ue
                ON ue.cve_ubicacion = rel.cve_ubicacion
        ";

        /* ================================
           Conteo
        ================================= */
        $sqlCount = "SELECT COUNT(DISTINCT sub.fol_folio) AS total $FROM $WHERE";
        $total = (int) db_val($sqlCount, $p);

        /* ================================
           SELECT principal
        ================================= */
        $SELECT = "
            SELECT
                sub.fol_folio          AS folio,
                ped.Fec_Pedido         AS fecha_pedido,
                ped.Fec_Entrega        AS fecha_entrega,
                cli.RazonSocial        AS razon_social,
                cli.id_cliente         AS id_cliente,
                des.id_destinatario    AS id_destinatario,
                des.direccion          AS direccion_cliente,
                des.colonia            AS colonia_dest,
                cli.Colonia            AS colonia_cliente,
                des.postal             AS cp_dest,
                cli.CodigoPostal       AS cp_cliente,
                rt.cve_ruta            AS cve_ruta,
                rt.descripcion         AS ruta,
                ue.descripcion         AS isla,
                ue.ID_Embarque         AS id_isla,
                sub.status             AS status_subpedido
        ";

        $sqlData = "
            $SELECT
            $FROM
            $WHERE
            GROUP BY sub.fol_folio
            ORDER BY ped.Fec_Entrega DESC, ped.Fol_folio DESC
        ";

        $paramsData = $p;

        if (!$sinPag) {
            $offset = ($page - 1) * $pageSize;
            $sqlData .= " LIMIT :off, :lim";
            $paramsData[':off'] = $offset;
            $paramsData[':lim'] = $pageSize;
        }

        $rows = db_all($sqlData, $paramsData);

        if (!$rows) {
            api_ok([], [
                'pagination' => [
                    'page' => 1,
                    'pageSize' => 0,
                    'totalRows' => 0,
                    'totalPages' => 0,
                ],
                'filters' => $f,
                'kpis' => [
                    'embarques_dia' => 0,
                    'planeados_7d' => 0,
                    'en_ruta' => 0,
                    'retrasados' => 0,
                ],
            ]);
        }

        /* ================================
           Normalizar colonia / CP
        ================================= */
        foreach ($rows as &$r) {
            $r['colonia'] = $r['colonia_dest'] ?: $r['colonia_cliente'];
            $r['codigo_postal'] = $r['cp_dest'] ?: $r['cp_cliente'];
        }
        unset($r);

        /* ================================
           Agregados por IN
        ================================= */
        $folios = array_values(array_unique(array_column($rows, 'folio')));

        $agg_guias = [];
        $agg_vol = [];
        $agg_pzas = [];

        if ($folios) {

            $IN = implode(',', array_fill(0, count($folios), '?'));

            /* 1) Guías/peso/cajas */
            $res1 = db_all("
                SELECT
                    fol_folio                AS folio,
                    COUNT(DISTINCT Guia)     AS total_guias,
                    SUM(Peso)                AS peso_total,
                    COUNT(NCaja)             AS total_cajas
                FROM th_cajamixta
                WHERE fol_folio IN ($IN)
                GROUP BY fol_folio
            ", $folios);

            foreach ($res1 as $x) {
                $agg_guias[$x['folio']] = [
                    'total_guias' => (int) ($x['total_guias'] ?? 0),
                    'peso_total' => (float) ($x['peso_total'] ?? 0),
                    'total_cajas' => (int) ($x['total_cajas'] ?? 0),
                ];
            }

            /* 2) Volumen */
            $res2 = db_all("
                SELECT
                    cm.fol_folio AS folio,
                    SUM((tc.largo/1000)*(tc.alto/1000)*(tc.ancho/1000)) AS volumen_total
                FROM th_cajamixta cm
                INNER JOIN c_tipocaja tc
                    ON tc.id_tipocaja = cm.cve_tipocaja
                WHERE cm.fol_folio IN ($IN)
                GROUP BY cm.fol_folio
            ", $folios);

            foreach ($res2 as $x) {
                $agg_vol[$x['folio']] = (float) ($x['volumen_total'] ?? 0);
            }

            /* 3) Piezas */
            $res3 = db_all("
                SELECT
                    fol_folio    AS folio,
                    SUM(Cantidad) AS piezas
                FROM td_surtidopiezas
                WHERE fol_folio IN ($IN)
                GROUP BY fol_folio
            ", $folios);

            foreach ($res3 as $x) {
                $agg_pzas[$x['folio']] = (float) ($x['piezas'] ?? 0);
            }
        }

        /* ================================
           Aplicar agregados
        ================================= */
        foreach ($rows as &$r) {

            $folio = $r['folio'];

            $g = $agg_guias[$folio] ?? [
                'total_guias' => 0,
                'peso_total' => 0,
                'total_cajas' => 0,
            ];

            $r['total_guias'] = $g['total_guias'];
            $r['peso_total'] = $g['peso_total'];
            $r['total_cajas'] = $g['total_cajas'];
            $r['volumen'] = $agg_vol[$folio] ?? 0;
            $r['piezas'] = $agg_pzas[$folio] ?? 0;
            $r['total_pallets'] = 0;
        }
        unset($r);

        /* ================================
           KPIs
        ================================= */
        $sqlKpi = "
            SELECT
                SUM(CASE WHEN ped.Fec_Entrega = CURDATE() THEN 1 ELSE 0 END) AS embarques_dia,
                SUM(CASE WHEN ped.Fec_Entrega >= CURDATE()
                          AND ped.Fec_Entrega < DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                         THEN 1 ELSE 0 END) AS planeados_7d,
                SUM(CASE WHEN sub.status = 'RUTA' THEN 1 ELSE 0 END)     AS en_ruta,
                SUM(CASE WHEN sub.status = 'C' AND ped.Fec_Entrega < CURDATE()
                         THEN 1 ELSE 0 END) AS retrasados
            $FROM
            $WHERE
        ";

        $kpis = [
            'embarques_dia' => 0,
            'planeados_7d' => 0,
            'en_ruta' => 0,
            'retrasados' => 0,
        ];

        try {
            $k = db_one($sqlKpi, $p);
            if ($k) {
                $kpis['embarques_dia'] = (int) ($k['embarques_dia'] ?? 0);
                $kpis['planeados_7d'] = (int) ($k['planeados_7d'] ?? 0);
                $kpis['en_ruta'] = (int) ($k['en_ruta'] ?? 0);
                $kpis['retrasados'] = (int) ($k['retrasados'] ?? 0);
            }
        } catch (Throwable $e) {
        }

        /* ================================
           Respuesta final
        ================================= */
        $totalPages = $sinPag
            ? 1
            : (int) ceil($total / max(1, $pageSize));

        api_ok($rows, [
            'pagination' => [
                'page' => $page,
                'pageSize' => $sinPag ? count($rows) : $pageSize,
                'totalRows' => $total,
                'totalPages' => $totalPages,
            ],
            'filters' => $f,
            'kpis' => $kpis,
        ]);
    }

    /* ============================================================
       Acción no soportada
    ============================================================= */
    api_error("Acción no soportada: $action");

} catch (Throwable $e) {
    api_error("Error interno: " . $e->getMessage());
}
