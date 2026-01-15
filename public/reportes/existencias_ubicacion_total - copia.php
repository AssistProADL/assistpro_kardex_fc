<?php
// public/reportes/existencias_ubicacion_total.php
// Existencias por Ubicación (Total) - 4 decimales (planta / granel)
// Layout-compatible: NO imprime <html> completo (lo maneja _menu_global)

require_once __DIR__ . '/../../app/db.php';

$debug = (($_GET['debug'] ?? '') === '1');
if ($debug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function fmt_mx4($value): string { return number_format((float)($value ?? 0), 4, '.', ','); }
function fmt_csv4($value): string { return number_format((float)($value ?? 0), 4, '.', ''); }

// ---- Inputs
$articulo   = trim($_GET['articulo'] ?? '');
$lote       = trim($_GET['lote'] ?? '');
$nivel      = trim($_GET['nivel'] ?? 'Todos'); // PZ | CJ | LP | Todos
$cve_almac  = trim($_GET['cve_almac'] ?? '');
$idy_ubica  = trim($_GET['idy_ubica'] ?? '');
$solo_disp  = (($_GET['solo_disponible'] ?? '1') === '1');
$incl_zeros = (($_GET['incluir_ceros'] ?? '0') === '1');
$limit      = (int)($_GET['limit'] ?? 200);
$offset     = (int)($_GET['offset'] ?? 0);
$q          = trim($_GET['q'] ?? '');
$export     = trim($_GET['export'] ?? ''); // csv | json

if ($limit <= 0) $limit = 200;
if ($limit > 2000) $limit = 2000;
if ($offset < 0) $offset = 0;

// ---- Layout
$menu_global = __DIR__ . '/../bi/_menu_global.php';
$menu_global_end = __DIR__ . '/../bi/_menu_global_end.php';
if (file_exists($menu_global)) include $menu_global;

// ---- Exec principal con control de errores (evita pantalla blanca)
try {
    // Catálogo almacenes
    $almacenes = db_all("
        SELECT cve_almac, des_almac
        FROM c_almacen
        ORDER BY cve_almac
    ");

    // SQL base (según tus estructuras)
    $base = "
    SELECT
        x.nivel,
        x.cve_almac,
        x.idy_ubica,
        u.CodigoCSD,
        CONCAT_WS('', u.cve_pasillo, u.cve_rack, u.cve_nivel, u.Seccion, u.Ubicacion) AS bl_lp,
        x.cve_articulo,
        x.cve_lote,
        cl.Caducidad AS caducidad,
        x.total,
        x.qa,
        x.rp,

        CAST(
            CASE
                WHEN cl.Caducidad IS NULL THEN 0
                WHEN cl.Caducidad < CURDATE() THEN x.total
                ELSE 0
            END
        AS DECIMAL(18,4)) AS obsoleto,

        CAST(
            (x.total - x.qa - x.rp -
                CASE
                    WHEN cl.Caducidad IS NULL THEN 0
                    WHEN cl.Caducidad < CURDATE() THEN x.total
                    ELSE 0
                END
            )
        AS DECIMAL(18,4)) AS disponible,

        x.fuente
    FROM (
        -- PIEZAS
        SELECT
            'PZ' AS nivel,
            e.cve_almac,
            e.idy_ubica,
            e.cve_articulo,
            IFNULL(e.cve_lote,'') AS cve_lote,
            CAST(IFNULL(e.Existencia,0) AS DECIMAL(18,4)) AS total,
            CAST(IFNULL(e.Existencia,0) * IFNULL(e.Cuarentena,0) AS DECIMAL(18,4)) AS qa,
            CAST(0 AS DECIMAL(18,4)) AS rp,
            'ts_existenciapiezas' AS fuente
        FROM ts_existenciapiezas e

        UNION ALL

        -- CAJAS (cantidad = PiezasXCaja, almacén = Cve_Almac)
        SELECT
            'CJ' AS nivel,
            e.Cve_Almac AS cve_almac,
            e.idy_ubica,
            e.cve_articulo,
            IFNULL(e.cve_lote,'') AS cve_lote,
            CAST(IFNULL(e.PiezasXCaja,0) AS DECIMAL(18,4)) AS total,
            CAST(0 AS DECIMAL(18,4)) AS qa,
            CAST(0 AS DECIMAL(18,4)) AS rp,
            'ts_existenciacajas' AS fuente
        FROM ts_existenciacajas e

        UNION ALL

        -- TARIMAS / LP (lote en campo: lote, existencia en: existencia)
        SELECT
            'LP' AS nivel,
            e.cve_almac,
            e.idy_ubica,
            e.cve_articulo,
            IFNULL(e.lote,'') AS cve_lote,
            CAST(IFNULL(e.existencia,0) AS DECIMAL(18,4)) AS total,
            CAST(IFNULL(e.existencia,0) * IFNULL(e.Cuarentena,0) AS DECIMAL(18,4)) AS qa,
            CAST(0 AS DECIMAL(18,4)) AS rp,
            'ts_existenciatarima' AS fuente
        FROM ts_existenciatarima e
    ) x
    LEFT JOIN c_ubicacion u
        ON u.idy_ubica = x.idy_ubica
    LEFT JOIN c_lotes cl
        ON cl.cve_articulo = x.cve_articulo
       AND cl.Lote = x.cve_lote
    WHERE 1=1
    ";

    $params = [];
    $sql = $base;

    if ($articulo !== '') { $sql .= " AND x.cve_articulo LIKE :articulo "; $params[':articulo'] = "%{$articulo}%"; }
    if ($lote !== '')     { $sql .= " AND IFNULL(x.cve_lote,'') LIKE :lote "; $params[':lote'] = "%{$lote}%"; }
    if ($nivel !== '' && $nivel !== 'Todos') { $sql .= " AND x.nivel = :nivel "; $params[':nivel'] = $nivel; }
    if ($cve_almac !== '') { $sql .= " AND x.cve_almac = :cve_almac "; $params[':cve_almac'] = $cve_almac; }
    if ($idy_ubica !== '') { $sql .= " AND x.idy_ubica = :idy_ubica "; $params[':idy_ubica'] = $idy_ubica; }

    if ($q !== '') {
        $sql .= " AND (
            x.cve_articulo LIKE :q
            OR IFNULL(x.cve_lote,'') LIKE :q
            OR CAST(x.idy_ubica AS CHAR) LIKE :q
            OR IFNULL(u.CodigoCSD,'') LIKE :q
        ) ";
        $params[':q'] = "%{$q}%";
    }

    if ($solo_disp) {
        $sql .= " AND (
            (x.total - x.qa - x.rp -
                CASE
                    WHEN cl.Caducidad IS NULL THEN 0
                    WHEN cl.Caducidad < CURDATE() THEN x.total
                    ELSE 0
                END
            ) > 0
        ) ";
    }

    if (!$incl_zeros) { $sql .= " AND x.total <> 0 "; }

    $sql .= " ORDER BY x.nivel, x.cve_almac, x.idy_ubica, x.cve_articulo ";
    $sql_paginated = $sql . " LIMIT {$limit} OFFSET {$offset} ";

    $rows = db_all($sql_paginated, $params);

    // KPIs
    $kpi = db_row("
        SELECT
            CAST(IFNULL(SUM(t.total),0) AS DECIMAL(18,4)) AS existencia_total,
            CAST(IFNULL(SUM(t.qa),0) AS DECIMAL(18,4))    AS en_cuarentena,
            CAST(IFNULL(SUM(t.rp),0) AS DECIMAL(18,4))    AS reservado_picking,
            CAST(IFNULL(SUM(t.obsoleto),0) AS DECIMAL(18,4)) AS obsoleto,
            CAST(IFNULL(SUM(t.disponible),0) AS DECIMAL(18,4)) AS disponible
        FROM (
            {$sql}
        ) t
    ", $params);

    // Export
    if ($export === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok'=>1,'kpi'=>$kpi,'limit'=>$limit,'offset'=>$offset,'rows'=>$rows], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($export === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="existencias_ubicacion_total.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Nivel','Almac','Ubica','CodigoCSD','BL/LP','Articulo','Lote','Caducidad','Total','QA','RP','Obsol','Disp','Fuente']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['nivel'], $r['cve_almac'], $r['idy_ubica'], $r['CodigoCSD'], $r['bl_lp'],
                $r['cve_articulo'], $r['cve_lote'], $r['caducidad'],
                fmt_csv4($r['total']), fmt_csv4($r['qa']), fmt_csv4($r['rp']),
                fmt_csv4($r['obsoleto']), fmt_csv4($r['disponible']), $r['fuente'],
            ]);
        }
        fclose($out);
        exit;
    }

    // ------------------------- UI (solo contenido, sin <html>)
    ?>
    <style>
        .apx-wrap{padding:18px}
        .apx-title{font-size:26px; font-weight:800; margin:0 0 4px}
        .apx-sub{margin:0 0 16px; color:#5c6470}
        .apx-grid{display:grid; grid-template-columns: 360px 1fr; gap:16px; align-items:start}
        .apx-card{background:#fff; border:1px solid #e7e9ef; border-radius:14px; padding:14px}
        .apx-kpis{display:grid; grid-template-columns: repeat(4, 1fr); gap:12px; margin-bottom:12px}
        .apx-kpi{background:#fff; border:1px solid #e7e9ef; border-radius:14px; padding:12px}
        .apx-kpi .lbl{color:#6b7280; font-size:12px; margin-bottom:6px}
        .apx-kpi .val{font-size:22px; font-weight:800}
        .apx-row{display:grid; grid-template-columns:1fr; gap:10px}
        .apx-row label{font-size:12px; color:#6b7280}
        .apx-row input,.apx-row select{width:100%; padding:10px 10px; border:1px solid #e5e7eb; border-radius:10px; outline:none}
        .apx-two{display:grid; grid-template-columns:1fr 1fr; gap:10px}
        .apx-btns{display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:10px}
        .apx-btn{display:inline-flex; align-items:center; justify-content:center; padding:10px 12px; border-radius:10px; border:1px solid #d6dae3; background:#fff; cursor:pointer; text-decoration:none; color:#111827}
        .apx-primary{background:#2563eb; border-color:#2563eb; color:#fff}
        .apx-table{width:100%; border-collapse:separate; border-spacing:0}
        .apx-table th,.apx-table td{border-bottom:1px solid #eef0f5; padding:10px 10px; font-size:13px; text-align:left; white-space:nowrap}
        .apx-table th{color:#6b7280; font-weight:700; background:#fbfcff}
        .apx-pill{display:inline-block; padding:4px 8px; border-radius:999px; font-size:12px; border:1px solid #e5e7eb; background:#fff}
        .apx-pill-red{background:#ffecec; border-color:#ffd2d2}
        .apx-pill-blue{background:#eef2ff; border-color:#dbe4ff}
        .apx-toolbar{display:flex; gap:10px; justify-content:flex-end; align-items:center; margin-bottom:10px}
        .apx-muted{color:#6b7280; font-size:12px}
        .apx-pager{display:flex; gap:8px; align-items:center; justify-content:flex-end}
    </style>

    <div class="apx-wrap">
        <div style="display:flex; justify-content:space-between; gap:10px; align-items:center">
            <div>
                <div class="apx-title">Existencias por Ubicación (Total)</div>
                <div class="apx-sub">Total, cuarentena, reservado picking, obsolescencia y disponible (por ubicación / BL / LP / nivel) · 4 decimales.</div>
            </div>
            <div class="apx-toolbar">
                <a class="apx-btn" href="?<?= h(http_build_query(array_merge($_GET, ['export'=>'json']))) ?>">Copiar JSON</a>
                <a class="apx-btn" href="?<?= h(http_build_query(array_merge($_GET, ['export'=>'csv']))) ?>">Export CSV</a>
                <a class="apx-btn apx-primary" href="?<?= h(http_build_query(array_merge($_GET, ['export'=>'']))) ?>">Actualizar</a>
            </div>
        </div>

        <div class="apx-kpis">
            <div class="apx-kpi"><div class="lbl">Existencia total</div><div class="val"><?= fmt_mx4($kpi['existencia_total'] ?? 0) ?></div></div>
            <div class="apx-kpi"><div class="lbl">En cuarentena</div><div class="val"><?= fmt_mx4($kpi['en_cuarentena'] ?? 0) ?></div></div>
            <div class="apx-kpi"><div class="lbl">Reservado picking</div><div class="val"><?= fmt_mx4($kpi['reservado_picking'] ?? 0) ?></div></div>
            <div class="apx-kpi"><div class="lbl">Disponible</div><div class="val"><?= fmt_mx4($kpi['disponible'] ?? 0) ?></div></div>
        </div>

        <div class="apx-grid">
            <div class="apx-card">
                <h3 style="margin:0 0 10px">Filtros</h3>
                <form method="get" class="apx-row">
                    <div>
                        <label>Artículo</label>
                        <input name="articulo" value="<?= h($articulo) ?>" placeholder="Ej. 1000008409 o coincidencia">
                    </div>
                    <div>
                        <label>Lote</label>
                        <input name="lote" value="<?= h($lote) ?>" placeholder="Opcional">
                    </div>

                    <div class="apx-two">
                        <div>
                            <label>Nivel</label>
                            <select name="nivel">
                                <?php
                                $opts = ['Todos'=>'Todos','PZ'=>'PZ','CJ'=>'CJ','LP'=>'LP'];
                                foreach($opts as $k=>$v){
                                    $sel = ($nivel===$k) ? 'selected' : '';
                                    echo "<option value='".h($k)."' {$sel}>".h($v)."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label>Almacén</label>
                            <select name="cve_almac">
                                <option value="">Todos</option>
                                <?php foreach($almacenes as $a): ?>
                                    <option value="<?= h($a['cve_almac']) ?>" <?= ($cve_almac!=='' && (string)$cve_almac===(string)$a['cve_almac'])?'selected':'' ?>>
                                        <?= h($a['cve_almac'].' - '.$a['des_almac']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label>Ubicación (idy_ubica)</label>
                        <input name="idy_ubica" value="<?= h($idy_ubica) ?>" placeholder="Ej. 32514">
                    </div>

                    <div style="display:flex; gap:10px; align-items:center; margin-top:6px">
                        <input style="width:auto" type="checkbox" name="solo_disponible" value="1" <?= $solo_disp?'checked':'' ?>>
                        <label style="margin:0">Solo disponible</label>
                    </div>

                    <div style="display:flex; gap:10px; align-items:center">
                        <input style="width:auto" type="checkbox" name="incluir_ceros" value="1" <?= $incl_zeros?'checked':'' ?>>
                        <label style="margin:0">Incluir ceros</label>
                    </div>

                    <div class="apx-two">
                        <div>
                            <label>Filas (limit)</label>
                            <select name="limit">
                                <?php foreach([50,100,200,500,1000,2000] as $l): ?>
                                    <option value="<?= $l ?>" <?= ($limit===$l)?'selected':'' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Buscar</label>
                            <input name="q" value="<?= h($q) ?>" placeholder="Filtra en la tabla...">
                        </div>
                    </div>

                    <input type="hidden" name="offset" value="0">

                    <div class="apx-btns">
                        <button class="apx-btn apx-primary" type="submit">Aplicar filtros</button>
                        <a class="apx-btn" href="existencias_ubicacion_total.php">Limpiar</a>
                    </div>
                </form>
            </div>

            <div class="apx-card">
                <div class="apx-pager">
                    <?php
                        $prevOffset = max(0, $offset - $limit);
                        $nextOffset = $offset + $limit;
                        $qsPrev = http_build_query(array_merge($_GET, ['offset'=>$prevOffset]));
                        $qsNext = http_build_query(array_merge($_GET, ['offset'=>$nextOffset]));
                    ?>
                    <a class="apx-btn" href="?<?= h($qsPrev) ?>">&laquo;</a>
                    <span class="apx-muted">Offset: <?= (int)$offset ?></span>
                    <a class="apx-btn" href="?<?= h($qsNext) ?>">&raquo;</a>
                </div>

                <div style="overflow:auto; border:1px solid #eef0f5; border-radius:12px; margin-top:10px">
                    <table class="apx-table">
                        <thead>
                        <tr>
                            <th>Nivel</th><th>Almac</th><th>Ubica</th><th>BL / LP</th><th>Artículo</th><th>Lote</th>
                            <th style="text-align:right">Total</th>
                            <th style="text-align:right">QA</th>
                            <th style="text-align:right">RP</th>
                            <th style="text-align:right">Obsol</th>
                            <th style="text-align:right">Disp</th>
                            <th>Fuente</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$rows): ?>
                            <tr><td colspan="12" class="apx-muted">Sin resultados con los filtros actuales.</td></tr>
                        <?php else: foreach($rows as $r): ?>
                            <tr>
                                <td><?= h($r['nivel']) ?></td>
                                <td><?= h($r['cve_almac']) ?></td>
                                <td><?= h($r['idy_ubica']) ?></td>
                                <td><?= h($r['bl_lp'] ?: ($r['CodigoCSD'] ?: '')) ?></td>
                                <td><?= h($r['cve_articulo']) ?></td>
                                <td><?= h($r['cve_lote']) ?></td>
                                <td style="text-align:right"><?= fmt_mx4($r['total']) ?></td>
                                <td style="text-align:right"><?= fmt_mx4($r['qa']) ?></td>
                                <td style="text-align:right"><?= fmt_mx4($r['rp']) ?></td>
                                <td style="text-align:right">
                                    <?php
                                        $ob = (float)$r['obsoleto'];
                                        echo ($ob > 0)
                                            ? '<span class="apx-pill apx-pill-red">'.fmt_mx4($ob).'</span>'
                                            : fmt_mx4($ob);
                                    ?>
                                </td>
                                <td style="text-align:right"><?= fmt_mx4($r['disponible']) ?></td>
                                <td><span class="apx-pill apx-pill-blue"><?= h($r['fuente']) ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="apx-muted" style="margin-top:10px">
                    Servicio: existencias_ubicacion_total · Limit: <?= (int)$limit ?> · Offset: <?= (int)$offset ?> · Solo disponible: <?= $solo_disp ? 'Sí' : 'No' ?>
                    <?= $debug ? ' · DEBUG=ON' : '' ?>
                </div>
            </div>
        </div>
    </div>
    <?php

} catch (Throwable $e) {
    // Render error visible (evita pantalla blanca)
    ?>
    <div style="padding:18px">
        <div style="background:#fff;border:1px solid #ffd2d2;border-radius:14px;padding:14px">
            <div style="font-weight:800;color:#b91c1c">Error al cargar Existencias por Ubicación (Total)</div>
            <div style="margin-top:8px;color:#374151"><?= h($e->getMessage()) ?></div>
            <?php if ($debug): ?>
                <pre style="margin-top:10px;white-space:pre-wrap;color:#111827"><?= h($e->getTraceAsString()) ?></pre>
            <?php else: ?>
                <div style="margin-top:8px;color:#6b7280;font-size:12px">Tip: agrega <b>?debug=1</b> a la URL para ver el detalle técnico.</div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// Cierre layout
if (file_exists($menu_global_end)) include $menu_global_end;
