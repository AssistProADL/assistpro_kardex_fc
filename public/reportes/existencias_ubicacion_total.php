<?php
// public/reportes/existencias_ubicacion_total.php

require_once __DIR__ . '/../../app/db.php';

// ------------------------- Helpers
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function fmt_mx4($value): string { return number_format((float)($value ?? 0), 4, '.', ','); }
function fmt_csv4($value): string { return number_format((float)($value ?? 0), 4, '.', ''); }

// UI pills
function pill($text, $cls=''){ return '<span class="apx-pill '.$cls.'">'.h($text).'</span>'; }
function pill_level($nivel){
    $nivel = (string)$nivel;
    if ($nivel === 'PZ') return pill('PZ', 'apx-pill-lvl apx-pill-lvl-pz');
    if ($nivel === 'CJ') return pill('CJ', 'apx-pill-lvl apx-pill-lvl-cj');
    if ($nivel === 'LP') return pill('LP', 'apx-pill-lvl apx-pill-lvl-lp');
    return pill($nivel, 'apx-pill');
}
function pill_num($value, $cls=''){
    $v = (float)($value ?? 0);
    return '<span class="apx-pill apx-pill-num '.$cls.'">'.fmt_mx4($v).'</span>';
}

// ------------------------- Inputs
$debug = (($_GET['debug'] ?? '') === '1');
if ($debug) { ini_set('display_errors','1'); error_reporting(E_ALL); }

$articulo   = trim($_GET['articulo'] ?? '');
$lote       = trim($_GET['lote'] ?? '');
$nivel      = trim($_GET['nivel'] ?? 'Todos'); // PZ | CJ | LP | Todos
$cve_almac  = trim($_GET['cve_almac'] ?? '');
$idy_ubica  = trim($_GET['idy_ubica'] ?? '');  // legacy
$codigocsd  = trim($_GET['codigocsd'] ?? '');  // NUEVO: filtro directo por CodigoCSD
$solo_disp  = (($_GET['solo_disponible'] ?? '1') === '1');
$incl_zeros = (($_GET['incluir_ceros'] ?? '0') === '1');
$limit      = (int)($_GET['limit'] ?? 200);
$offset     = (int)($_GET['offset'] ?? 0);
$q          = trim($_GET['q'] ?? '');
$export     = trim($_GET['export'] ?? ''); // csv | json

if ($limit <= 0) $limit = 200;
if ($limit > 2000) $limit = 2000;
if ($offset < 0) $offset = 0;

// ------------------------- SQL base
// BL = CodigoCSD (ya lo da c_ubicacion)
// LP Code = code del pallet/contenedor/pieza según nivel (sale de cada tabla fuente)
$base = "
SELECT
    x.nivel,
    x.cve_almac,
    x.idy_ubica,
    u.CodigoCSD AS bl,              -- BL = CodigoCSD
    x.lp_code AS lp_code,           -- LP/Contenedor/Pieza Code
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
        IFNULL(e.code,'') AS lp_code, -- code visual si existe
        CAST(IFNULL(e.Existencia,0) AS DECIMAL(18,4)) AS total,
        CAST(IFNULL(e.Existencia,0) * IFNULL(e.Cuarentena,0) AS DECIMAL(18,4)) AS qa,
        CAST(0 AS DECIMAL(18,4)) AS rp,
        'ts_existenciapiezas' AS fuente
    FROM ts_existenciapiezas e

    UNION ALL

    -- CAJAS / CONTENEDOR
    SELECT
        'CJ' AS nivel,
        e.Cve_Almac AS cve_almac,
        e.idy_ubica,
        e.cve_articulo,
        IFNULL(e.cve_lote,'') AS cve_lote,
        IFNULL(e.code,'') AS lp_code, -- code del contenedor/caja
        CAST(IFNULL(e.PiezasXCaja,0) AS DECIMAL(18,4)) AS total,
        CAST(0 AS DECIMAL(18,4)) AS qa,
        CAST(0 AS DECIMAL(18,4)) AS rp,
        'ts_existenciacajas' AS fuente
    FROM ts_existenciacajas e

    UNION ALL

    -- TARIMAS / LP
    SELECT
        'LP' AS nivel,
        e.cve_almac,
        e.idy_ubica,
        e.cve_articulo,
        IFNULL(e.lote,'') AS cve_lote,
        IFNULL(e.code,'') AS lp_code, -- code del pallet/LP
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

// ------------------------- WHERE dinámico
$params = [];
$sql = $base;

if ($articulo !== '') { $sql .= " AND x.cve_articulo LIKE :articulo "; $params[':articulo'] = "%{$articulo}%"; }
if ($lote !== '')     { $sql .= " AND IFNULL(x.cve_lote,'') LIKE :lote "; $params[':lote'] = "%{$lote}%"; }
if ($nivel !== '' && $nivel !== 'Todos') { $sql .= " AND x.nivel = :nivel "; $params[':nivel'] = $nivel; }
if ($cve_almac !== '') { $sql .= " AND x.cve_almac = :cve_almac "; $params[':cve_almac'] = $cve_almac; }
if ($idy_ubica !== '') { $sql .= " AND x.idy_ubica = :idy_ubica "; $params[':idy_ubica'] = $idy_ubica; }

// NUEVO: filtro directo por CodigoCSD (BL)
if ($codigocsd !== '') {
    $sql .= " AND IFNULL(u.CodigoCSD,'') LIKE :codigocsd ";
    $params[':codigocsd'] = "%{$codigocsd}%";
}

// q por coincidencia (incluye BL=CodigoCSD y LP code)
if ($q !== '') {
    $sql .= " AND (
        x.cve_articulo LIKE :q
        OR IFNULL(x.cve_lote,'') LIKE :q
        OR IFNULL(u.CodigoCSD,'') LIKE :q
        OR IFNULL(x.lp_code,'') LIKE :q
        OR CAST(x.idy_ubica AS CHAR) LIKE :q
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

$sql .= " ORDER BY x.nivel, x.cve_almac, u.CodigoCSD, x.cve_articulo ";
$sql_paginated = $sql . " LIMIT {$limit} OFFSET {$offset} ";

// ------------------------- EXPORTS (antes del menú)
if ($export === 'json') {
    $rows = db_all($sql_paginated, $params);
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

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>1,'kpi'=>$kpi,'limit'=>$limit,'offset'=>$offset,'rows'=>$rows],
        JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

if ($export === 'csv') {
    $rows = db_all($sql_paginated, $params);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="existencias_ubicacion_total.csv"');

    $out = fopen('php://output', 'w');
    // BL y LP Code separados
    fputcsv($out, ['Nivel','Almac_Clave','BL(CodigoCSD)','LP_Code','Articulo','Lote','Caducidad','Total','QA','RP','Obsol','Disp','Fuente']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['nivel'],
            $r['cve_almac'],
            $r['bl'],
            $r['lp_code'],
            $r['cve_articulo'],
            $r['cve_lote'],
            $r['caducidad'],
            fmt_csv4($r['total']),
            fmt_csv4($r['qa']),
            fmt_csv4($r['rp']),
            fmt_csv4($r['obsoleto']),
            fmt_csv4($r['disponible']),
            $r['fuente'],
        ]);
    }
    fclose($out);
    exit;
}

// ------------------------- UI con menú
$menu_global = __DIR__ . '/../bi/_menu_global.php';
$menu_global_end = __DIR__ . '/../bi/_menu_global_end.php';
if (file_exists($menu_global)) include $menu_global;

try {
    $almacenes = db_all("SELECT cve_almac, des_almac FROM c_almacen ORDER BY cve_almac");
    $rows = db_all($sql_paginated, $params);

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

    $prevOffset = max(0, $offset - $limit);
    $nextOffset = $offset + $limit;
    $qsPrev = http_build_query(array_merge($_GET, ['offset'=>$prevOffset, 'export'=>'']));
    $qsNext = http_build_query(array_merge($_GET, ['offset'=>$nextOffset, 'export'=>'']));

    $chips = [];
    if ($articulo !== '') $chips[] = pill("Artículo: {$articulo}", 'apx-chip');
    if ($lote !== '') $chips[] = pill("Lote: {$lote}", 'apx-chip');
    if ($nivel !== '' && $nivel !== 'Todos') $chips[] = pill("Nivel: {$nivel}", 'apx-chip');
    if ($cve_almac !== '') $chips[] = pill("Almac: {$cve_almac}", 'apx-chip');
    if ($codigocsd !== '') $chips[] = pill("BL: {$codigocsd}", 'apx-chip');
    if ($q !== '') $chips[] = pill("Buscar: {$q}", 'apx-chip');
    $chips[] = pill("Solo disp: ".($solo_disp?'Sí':'No'), 'apx-chip');

    ?>
    <style>
        /* 10px para toda la vista (densidad de info) */
        .apx-wrap{padding:10px}
        .apx-title{font-size:24px; font-weight:900; margin:0 0 4px; letter-spacing:-.2px}
        .apx-sub{margin:0 0 10px; color:#5c6470}
        .apx-grid{display:grid; grid-template-columns: 360px 1fr; gap:12px; align-items:start}
        .apx-card{background:#fff; border:1px solid #e7e9ef; border-radius:16px; padding:10px; box-shadow: 0 6px 18px rgba(15,23,42,.04)}
        .apx-kpis{display:grid; grid-template-columns: repeat(4, 1fr); gap:10px; margin-bottom:10px}
        .apx-kpi{background:#fff; border:1px solid #e7e9ef; border-radius:16px; padding:10px}
        .apx-kpi .lbl{color:#6b7280; font-size:12px; margin-bottom:6px}
        .apx-kpi .val{font-size:20px; font-weight:900; letter-spacing:-.2px}
        .apx-row{display:grid; grid-template-columns:1fr; gap:10px}
        .apx-row label{font-size:12px; color:#6b7280}
        .apx-row input,.apx-row select{width:100%; padding:10px; border:1px solid #e5e7eb; border-radius:12px; outline:none}
        .apx-row input:focus,.apx-row select:focus{border-color:#c7d2fe; box-shadow:0 0 0 3px rgba(99,102,241,.10)}
        .apx-two{display:grid; grid-template-columns:1fr 1fr; gap:10px}
        .apx-btns{display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:10px}
        .apx-btn{display:inline-flex; align-items:center; justify-content:center; padding:10px 12px; border-radius:12px; border:1px solid #d6dae3; background:#fff; cursor:pointer; text-decoration:none; color:#111827; font-weight:700}
        .apx-primary{background:#2563eb; border-color:#2563eb; color:#fff}
        .apx-toolbar{display:flex; gap:10px; justify-content:flex-end; align-items:center; margin-bottom:10px}
        .apx-muted{color:#6b7280; font-size:12px}

        .apx-pill{display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px; font-size:12px; border:1px solid #e5e7eb; background:#fff}
        .apx-pill-num{font-variant-numeric: tabular-nums}
        .apx-pill-blue{background:#eef2ff; border-color:#dbe4ff; color:#3730a3}
        .apx-pill-red{background:#ffecec; border-color:#ffd2d2; color:#991b1b}
        .apx-pill-green{background:#ecfdf5; border-color:#bbf7d0; color:#065f46}
        .apx-pill-amber{background:#fffbeb; border-color:#fde68a; color:#92400e}
        .apx-pill-slate{background:#f1f5f9; border-color:#e2e8f0; color:#334155}
        .apx-pill-lvl{font-weight:900; letter-spacing:.2px}
        .apx-pill-lvl-pz{background:#f1f5f9; border-color:#e2e8f0; color:#0f172a}
        .apx-pill-lvl-cj{background:#eef2ff; border-color:#dbe4ff; color:#312e81}
        .apx-pill-lvl-lp{background:#ecfdf5; border-color:#bbf7d0; color:#065f46}
        .apx-chip{background:#f8fafc; border-color:#e2e8f0; color:#334155}

        .apx-sticky{position: sticky; top: 10px}
        .apx-result-head{
            display:flex; align-items:flex-start; justify-content:space-between; gap:10px;
            padding-bottom:10px; border-bottom:1px dashed #e6e8ef;
            position: sticky; top: 10px; background:#fff; z-index: 5;
        }
        .apx-chips{display:flex; gap:8px; flex-wrap:wrap}
        .apx-pager{display:flex; gap:8px; align-items:center; justify-content:flex-end}

        .apx-table-zone{
            height: calc(100vh - 390px);
            min-height: 340px;
            overflow: auto;
            border:1px solid #eef0f5;
            border-radius:14px;
            margin-top:10px;
        }
        .apx-table{width:100%; min-width: 1320px; border-collapse:separate; border-spacing:0}
        .apx-table th,.apx-table td{border-bottom:1px solid #eef0f5; padding:10px; font-size:13px; white-space:nowrap}
        .apx-table th{color:#6b7280; font-weight:900; background:#fbfcff; position: sticky; top: 52px; z-index: 3}
        .apx-table tbody tr:nth-child(even){background:#fcfdff}
        .apx-table tbody tr:hover{background:#f6f8ff}
        .apx-num{text-align:right; font-variant-numeric: tabular-nums}
        .apx-col-article{max-width: 520px; overflow:hidden; text-overflow:ellipsis}

        @media (max-width: 1200px){
            .apx-kpis{grid-template-columns: repeat(2, 1fr)}
            .apx-grid{grid-template-columns: 1fr}
            .apx-table{min-width: 1100px}
        }
    </style>

    <div class="apx-wrap">
        <div style="display:flex; justify-content:space-between; gap:10px; align-items:center">
            <div>
                <div class="apx-title">Existencias por Ubicación (Total)</div>
                <div class="apx-sub">BL = CodigoCSD · LP Code = pallet / contenedor · 4 decimales.</div>
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
            <div class="apx-card apx-sticky">
                <h3 style="margin:0 0 10px">Filtros</h3>
                <form method="get" class="apx-row">
                    <div>
                        <label>Artículo (coincidencia)</label>
                        <input name="articulo" value="<?= h($articulo) ?>" placeholder="Ej. 1000008409 o coincidencia">
                    </div>

                    <div>
                        <label>BL / CodigoCSD (coincidencia)</label>
                        <input name="codigocsd" value="<?= h($codigocsd) ?>" placeholder="Ej. W202010101">
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
                        <input name="idy_ubica" value="<?= h($idy_ubica) ?>" placeholder="Opcional (legacy)">
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
                            <label>Buscar (coincidencia)</label>
                            <input name="q" value="<?= h($q) ?>" placeholder="Artículo / Lote / BL / LP Code">
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
                <div class="apx-result-head">
                    <div>
                        <div style="font-weight:900; font-size:14px; margin-bottom:6px">Resultados</div>
                        <div class="apx-chips"><?= implode("\n", $chips) ?></div>
                    </div>

                    <div style="min-width:240px">
                        <div class="apx-pager">
                            <?php
                            $qsPrev = http_build_query(array_merge($_GET, ['offset'=>max(0, $offset-$limit), 'export'=>'']));
                            $qsNext = http_build_query(array_merge($_GET, ['offset'=>$offset+$limit, 'export'=>'']));
                            ?>
                            <a class="apx-btn" href="?<?= h($qsPrev) ?>">&laquo;</a>
                            <span class="apx-muted">Offset: <?= (int)$offset ?></span>
                            <a class="apx-btn" href="?<?= h($qsNext) ?>">&raquo;</a>
                        </div>
                        <div class="apx-muted" style="text-align:right; margin-top:6px">
                            Limit: <?= (int)$limit ?> · Solo disp: <?= $solo_disp ? 'Sí' : 'No' ?><?= $debug ? ' · DEBUG=ON' : '' ?>
                        </div>
                    </div>
                </div>

                <div class="apx-table-zone">
                    <table class="apx-table">
                        <thead>
                        <tr>
                            <th>Nivel</th>
                            <th>Almac (Clave)</th>
                            <th>BL (CodigoCSD)</th>
                            <th>LP Code</th>
                            <th>Artículo</th>
                            <th>Lote</th>
                            <th class="apx-num">Total</th>
                            <th class="apx-num">QA</th>
                            <th class="apx-num">RP</th>
                            <th class="apx-num">Obsol</th>
                            <th class="apx-num">Disp</th>
                            <th>Fuente</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$rows): ?>
                            <tr><td colspan="12" class="apx-muted">Sin resultados con los filtros actuales.</td></tr>
                        <?php else: foreach($rows as $r): ?>
                            <?php
                                $qa  = (float)$r['qa'];
                                $rp  = (float)$r['rp'];
                                $ob  = (float)$r['obsoleto'];
                                $dis = (float)$r['disponible'];
                            ?>
                            <tr>
                                <td><?= pill_level($r['nivel']) ?></td>
                                <td><?= pill($r['cve_almac'], 'apx-pill apx-pill-slate') ?></td>
                                <td><?= pill(($r['bl'] ?? ''), 'apx-pill apx-pill-slate') ?></td>
                                <td><?= pill(($r['lp_code'] ?? ''), 'apx-pill apx-pill-slate') ?></td>
                                <td class="apx-col-article"><?= h($r['cve_articulo']) ?></td>
                                <td><?= h($r['cve_lote']) ?></td>

                                <td class="apx-num"><?= pill_num($r['total'], 'apx-pill-slate') ?></td>
                                <td class="apx-num"><?= $qa > 0 ? pill_num($qa, 'apx-pill-amber') : pill_num(0, 'apx-pill-slate') ?></td>
                                <td class="apx-num"><?= $rp > 0 ? pill_num($rp, 'apx-pill-blue') : pill_num(0, 'apx-pill-slate') ?></td>
                                <td class="apx-num"><?= $ob > 0 ? pill_num($ob, 'apx-pill-red') : pill_num(0, 'apx-pill-slate') ?></td>
                                <td class="apx-num"><?= $dis > 0 ? pill_num($dis, 'apx-pill-green') : pill_num(0, 'apx-pill-slate') ?></td>

                                <td><?= pill($r['fuente'], 'apx-pill apx-pill-blue') ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="apx-muted" style="margin-top:10px">
                    BL separado (CodigoCSD) · LP Code (pallet/contenedor) · Layout densificado a 10px · Export OK.
                </div>
            </div>
        </div>
    </div>
    <?php

} catch (Throwable $e) {
    ?>
    <div style="padding:10px">
        <div style="background:#fff;border:1px solid #ffd2d2;border-radius:14px;padding:10px">
            <div style="font-weight:800;color:#b91c1c">Error al cargar Existencias por Ubicación (Total)</div>
            <div style="margin-top:8px;color:#374151"><?= h($e->getMessage()) ?></div>
            <?php if ($debug): ?>
                <pre style="margin-top:10px;white-space:pre-wrap;color:#111827"><?= h($e->getTraceAsString()) ?></pre>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

if (file_exists($menu_global_end)) include $menu_global_end;
