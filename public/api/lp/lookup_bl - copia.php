<?php
// public/api/lp/lookup_bl.php
// Lookup de BL (CodigoCSD) + metadatos de ubicación + contenido (LPs actuales en ese BL)
// SIN auth/sesión, sin filtro forzoso por almacén. Si llega cve_almac, se aplica como filtro opcional.

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../app/db.php';

try {
    $q = trim($_GET['q'] ?? '');
    $minLen = 2;

    if (mb_strlen($q) < $minLen) {
        echo json_encode([
            "ok" => true,
            "data" => [],
            "meta" => ["min_len" => $minLen, "q" => $q]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $cveAlmac = $_GET['cve_almac'] ?? null;
    if ($cveAlmac !== null && $cveAlmac !== '') {
        $cveAlmac = (int)$cveAlmac;
    } else {
        $cveAlmac = null;
    }

    // 1) BLs que matchean CodigoCSD
    $where = "u.CodigoCSD LIKE ?";
    $params = ["%{$q}%"];

    if ($cveAlmac !== null) {
        $where .= " AND u.cve_almac = ?";
        $params[] = $cveAlmac;
    }

    $blRows = db_all("
        SELECT
            u.idy_ubica,
            u.cve_almac,
            u.CodigoCSD,
            u.Ubicacion,
            u.Seccion,
            u.AcomodoMixto,
            u.Ptl,
            u.Activo,

            /* Conteos en esa ubicación */
            (SELECT COUNT(DISTINCT et.nTarima)
             FROM ts_existenciatarima et
             WHERE et.idy_ubica = u.idy_ubica
               AND et.existencia IS NOT NULL
            ) AS pallets,

            (SELECT COUNT(DISTINCT ec.Id_Caja)
             FROM ts_existenciacajas ec
             WHERE ec.idy_ubica = u.idy_ubica
               AND ec.PiezasXCaja IS NOT NULL
            ) AS cajas

        FROM c_ubicacion u
        WHERE {$where}
        ORDER BY u.cve_almac, u.CodigoCSD
        LIMIT 200
    ", $params);

    // 2) Contenido (LPs) por BL: regresamos una lista acotada por cada BL
    //    (suficiente para UI: mostrar que "CEKANBAN01 tiene muchos pallets", etc.)
    foreach ($blRows as &$b) {
        $idy = (int)$b['idy_ubica'];

        $contenido = db_all("
            SELECT
                ch.CveLP,
                ch.tipo,
                ch.Activo
            FROM c_charolas ch
            WHERE
              (
                UPPER(ch.tipo) = 'PALLET'
                AND EXISTS (
                    SELECT 1
                    FROM ts_existenciatarima et
                    WHERE et.nTarima = ch.IDContenedor
                      AND et.idy_ubica = ?
                      AND et.existencia IS NOT NULL
                )
              )
              OR
              (
                UPPER(ch.tipo) <> 'PALLET'
                AND EXISTS (
                    SELECT 1
                    FROM ts_existenciacajas ec
                    WHERE ec.Id_Caja = ch.IDContenedor
                      AND ec.idy_ubica = ?
                      AND ec.PiezasXCaja IS NOT NULL
                )
              )
            ORDER BY ch.Activo DESC, ch.tipo, ch.CveLP
            LIMIT 200
        ", [$idy, $idy]);

        $b['contenido'] = $contenido;
    }
    unset($b);

    echo json_encode([
        "ok" => true,
        "data" => $blRows,
        "meta" => [
            "min_len" => $minLen,
            "q" => $q,
            "cve_almac" => $cveAlmac
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode([
        "ok" => false,
        "msg" => "PHP_EXCEPTION",
        "err" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
