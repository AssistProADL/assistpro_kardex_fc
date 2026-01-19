<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../app/db.php';

try {

    $pdo = db();

    // =========================
    // Parámetros de entrada
    // =========================
    // Filtros (soporta búsqueda directa por artículo y BL/CodigoCSD)
    $cve_articulo      = $_GET['cve_articulo'] ?? null;
    $cve_lote          = $_GET['cve_lote'] ?? null;
    $nivel             = $_GET['nivel'] ?? null;
    $cve_almac         = $_GET['cve_almac'] ?? null;
    $idy_ubica         = $_GET['idy_ubica'] ?? null;
    $bl                = $_GET['bl'] ?? null; // CodigoCSD
    $incluir_negativos = (int)($_GET['incluir_negativos'] ?? 0);
    $solo_disponible   = isset($_GET['solo_disponible']) ? (bool)$_GET['solo_disponible'] : false;
    $incluir_cero      = isset($_GET['incluir_cero']) ? (bool)$_GET['incluir_cero'] : false;
    $limit             = isset($_GET['limit']) ? (int)$_GET['limit'] : 500;
    $offset            = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    // =========================
    // WHERE dinámico
    // =========================
    $where = [];
    $bind  = [];

    if ($cve_articulo) {
        $where[] = "v.cve_articulo = :cve_articulo";
        $bind[':cve_articulo'] = $cve_articulo;
    }

    if ($cve_lote) {
        $where[] = "v.cve_lote = :cve_lote";
        $bind[':cve_lote'] = $cve_lote;
    }

    if ($nivel) {
        $where[] = "v.nivel = :nivel";
        $bind[':nivel'] = $nivel;
    }

    if ($cve_almac !== null) {
        $where[] = "v.cve_almac = :cve_almac";
        $bind[':cve_almac'] = $cve_almac;
    }

    if ($idy_ubica !== null) {
        $where[] = "v.idy_ubica = :idy_ubica";
        $bind[':idy_ubica'] = $idy_ubica;
    }

    if ($bl !== null && $bl !== '') {
        $where[] = "v.bl = :bl";
        $bind[':bl'] = $bl;
    }

    // Reglas de visibilidad de stock:
    // - Default: solo positivos (v.cantidad > 0)
    // - incluir_cero=1: positivos + ceros (v.cantidad >= 0)
    // - incluir_negativos=1: no filtramos por signo (incluye negativos)
    // - solo_disponible=1: fuerza a positivos (para "disponible")
    if ($solo_disponible) {
        $where[] = "v.cantidad > 0";
    } elseif (!$incluir_negativos) {
        $where[] = $incluir_cero ? "v.cantidad >= 0" : "v.cantidad > 0";
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // =========================
    // Query principal
    // =========================
    $sql = "
        SELECT
            v.nivel,
            v.cve_almac,
            v.idy_ubica,
            v.bl,
            v.cve_articulo,
            v.cve_lote,
            v.id_caja,
            v.nTarima,
            ch.CveLP AS CveLP,
            v.cantidad AS existencia_total,
            v.epc,
            v.code,
            v.fuente,

            IFNULL(q.cantidad_q,0) AS en_cuarentena,
            IFNULL(rp.apartadas,0) AS reservado_picking,
            IF(l.Caducidad IS NOT NULL AND l.Caducidad < CURDATE(), v.cantidad, 0) AS obsoleto,

            ( v.cantidad
              - IFNULL(q.cantidad_q,0)
              - IFNULL(rp.apartadas,0)
              - IF(l.Caducidad IS NOT NULL AND l.Caducidad < CURDATE(), v.cantidad, 0)
            ) AS existencia_disponible

        FROM v_inv_existencia_multinivel v

        /*
          nTarima es el ID interno del contenedor/tarima.
          Para UI debemos exponer la clave visible del LP: c_charolas.CveLP
          Relación Kardex: c_charolas.IDContenedor = v.nTarima
        */
        LEFT JOIN c_charolas ch
               ON ch.IDContenedor = v.nTarima

        LEFT JOIN (
            SELECT
                Cve_Articulo,
                Cve_Lote,
                Idy_Ubica,
                SUM(Cantidad) cantidad_q
            FROM t_movcuarentena
            WHERE Fec_Libera IS NULL
            GROUP BY Cve_Articulo, Cve_Lote, Idy_Ubica
        ) q ON q.Cve_Articulo = v.cve_articulo
           AND q.Cve_Lote = v.cve_lote
           AND q.Idy_Ubica = v.idy_ubica

        LEFT JOIN vs_apartadoparasurtido rp
           ON rp.Idy_Ubica = v.idy_ubica
          AND rp.Cve_Articulo = v.cve_articulo
          AND rp.cve_lote = v.cve_lote

        LEFT JOIN c_lotes l
           ON l.cve_articulo = v.cve_articulo
          AND l.lote = v.cve_lote

        $whereSQL
        ORDER BY v.cve_articulo, v.idy_ubica
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);

    foreach ($bind as $k => $v) {
        $stmt->bindValue($k, $v);
    }

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // =========================
    // KPIs
    // =========================
    $kpis = [
        'existencia_total'      => 0,
        'en_cuarentena'         => 0,
        'reservado_picking'     => 0,
        'obsoleto'              => 0,
        'existencia_disponible' => 0,
    ];

    foreach ($rows as $r) {
        $kpis['existencia_total']      += (float)$r['existencia_total'];
        $kpis['en_cuarentena']         += (float)$r['en_cuarentena'];
        $kpis['reservado_picking']     += (float)$r['reservado_picking'];
        $kpis['obsoleto']              += (float)$r['obsoleto'];
        $kpis['existencia_disponible'] += (float)$r['existencia_disponible'];
    }

    echo json_encode([
        'ok'      => 1,
        'service' => 'existencias_ubicacion_total',
        'filters' => $_GET,
        'kpis'    => $kpis,
        'rows'    => $rows
    ]);

} catch (Throwable $e) {

    echo json_encode([
        'ok'    => 0,
        'error' => $e->getMessage()
    ]);
}
