<?php
/**
 * WebServices Admin · Dashboard
 * Ruta sugerida: /public/dashboard/wsadmin_dashboard.php
 *
 * Incluye API embebida:
 *  - GET  ?action=data        => JSON (grid + kpis)
 *  - GET  ?action=export_csv  => CSV  (mismo filtro)
 */

declare(strict_types=1);

// ======================
// 1) API EMBEBIDA (ANTES DE CUALQUIER OUTPUT)
// ======================
if (isset($_GET['action']) && in_array($_GET['action'], ['data','export_csv'], true)) {

    // Ajusta según tu estructura
    require_once __DIR__ . '/../../app/db.php';

    // ---- Config BD/Tabla ----
    // Si tu db.php ya conecta directo a assistpro_etl_fc, deja DBNAME vacío.
    $DBNAME = 'assistpro_etl_fc';
    $TABLE  = 'log_ws_ejecucion';

    // ---- Helpers locales ----
    $json_out = function(array $payload, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    };

    $csv_out = function(string $filename, array $rows): void {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        $out = fopen('php://output', 'w');
        // BOM para Excel
        fwrite($out, "\xEF\xBB\xBF");

        if (empty($rows)) {
            fputcsv($out, ['sin_datos']);
            fclose($out);
            exit;
        }

        // Header dinámico
        fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $r) fputcsv($out, $r);
        fclose($out);
        exit;
    };

    // Detecta si tu PDO está disponible
    if (!function_exists('db_one') || !function_exists('db_all') || !function_exists('db_val')) {
        $json_out(['ok'=>false,'error'=>'db.php no expone helpers db_one/db_all/db_val'], 500);
    }

    // ---- Descubre columnas reales de la tabla (para no fallar si cambian nombres) ----
    try {
        // Si tu conexión ya está sobre el schema correcto, INFORMATION_SCHEMA igual funciona.
        $cols = db_all("
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = :db
              AND TABLE_NAME   = :t
        ", ['db'=>$DBNAME, 't'=>$TABLE]);

        if (!$cols) {
            // Si DBNAME no aplica (conexión ya está en ese schema), intenta detectar schema actual:
            $currentDb = db_val("SELECT DATABASE()");
            $cols = db_all("
                SELECT COLUMN_NAME
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = :db
                  AND TABLE_NAME   = :t
            ", ['db'=>$currentDb, 't'=>$TABLE]);
            if ($cols) $DBNAME = (string)$currentDb;
        }

        if (!$cols) {
            $json_out(['ok'=>false,'error'=>"No se encontró {$DBNAME}.{$TABLE} en INFORMATION_SCHEMA"], 500);
        }

        $colset = [];
        foreach ($cols as $c) $colset[$c['COLUMN_NAME']] = true;

        $has = fn(string $c): bool => isset($colset[$c]);

        // ---- Mapeo flexible: elige la mejor columna disponible ----
        $C_FECHA_INI   = $has('fecha_ini')   ? 'fecha_ini'   : ($has('inicio') ? 'inicio' : ($has('created_at') ? 'created_at' : null));
        $C_FECHA_FIN   = $has('fecha_fin')   ? 'fecha_fin'   : ($has('fin')    ? 'fin'    : ($has('updated_at') ? 'updated_at' : null));
        $C_EVENTO      = $has('evento')      ? 'evento'      : ($has('proceso') ? 'proceso' : null);
        $C_CLIENTE     = $has('cliente')     ? 'cliente'     : ($has('cliente_id') ? 'cliente_id' : ($has('conexion_id') ? 'conexion_id' : null));
        $C_RESULTADO   = $has('resultado')   ? 'resultado'   : ($has('estado') ? 'estado' : ($has('status') ? 'status' : null));
        $C_TRACE       = $has('trace_id')    ? 'trace_id'    : ($has('trace') ? 'trace' : null);
        $C_REF         = $has('referencia')  ? 'referencia'  : ($has('ref') ? 'ref' : null);
        $C_SISTEMA     = $has('sistema')     ? 'sistema'     : null;
        $C_USUARIO     = $has('usuario')     ? 'usuario'     : null;
        $C_DISPO       = $has('dispositivo') ? 'dispositivo' : null;

        // Duración: preferir columna, si no, calcular
        $C_DUR_MS      = $has('duracion_ms') ? 'duracion_ms' : ($has('duration_ms') ? 'duration_ms' : null);

        // Proc/Err: preferir columnas si existen
        $C_PROC        = $has('proc')        ? 'proc'        : ($has('total_ok') ? 'total_ok' : null);
        $C_ERR         = $has('err')         ? 'err'         : ($has('total_err') ? 'total_err' : null);

        // ---- Parámetros / Filtros ----
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $pageSize = min(200, max(1, (int)($_GET['pageSize'] ?? 25)));
        $offset   = ($page - 1) * $pageSize;

        $desde = trim((string)($_GET['desde'] ?? ''));
        $hasta = trim((string)($_GET['hasta'] ?? ''));

        // default rango: hoy 00:00 a 23:59
        if ($desde === '' || $hasta === '') {
            $hoy = date('Y-m-d');
            $desde = $desde !== '' ? $desde : ($hoy.' 00:00:00');
            $hasta = $hasta !== '' ? $hasta : ($hoy.' 23:59:59');
        }

        $cliente   = trim((string)($_GET['cliente'] ?? ''));
        $evento    = trim((string)($_GET['evento'] ?? ''));
        $resultado = trim((string)($_GET['resultado'] ?? ''));
        $buscar    = trim((string)($_GET['buscar'] ?? ''));

        $where = [];
        $bind  = [];

        if ($C_FECHA_INI) {
            $where[] = "{$C_FECHA_INI} BETWEEN :desde AND :hasta";
            $bind['desde'] = $desde;
            $bind['hasta'] = $hasta;
        }

        if ($cliente !== '' && $cliente !== 'Todos' && $C_CLIENTE) {
            $where[] = "{$C_CLIENTE} = :cliente";
            $bind['cliente'] = $cliente;
        }

        if ($evento !== '' && $evento !== 'Todos' && $C_EVENTO) {
            $where[] = "{$C_EVENTO} = :evento";
            $bind['evento'] = $evento;
        }

        if ($resultado !== '' && $resultado !== 'Todos' && $C_RESULTADO) {
            $where[] = "{$C_RESULTADO} = :resultado";
            $bind['resultado'] = $resultado;
        }

        if ($buscar !== '') {
            $likeParts = [];
            if ($C_TRACE) { $likeParts[] = "{$C_TRACE} LIKE :q"; }
            if ($C_REF)   { $likeParts[] = "{$C_REF}   LIKE :q"; }
            if ($C_EVENTO){ $likeParts[] = "{$C_EVENTO} LIKE :q"; }
            if ($C_USUARIO){$likeParts[] = "{$C_USUARIO} LIKE :q"; }

            if ($likeParts) {
                $where[] = "(".implode(" OR ", $likeParts).")";
                $bind['q'] = "%{$buscar}%";
            }
        }

        $whereSql = $where ? ("WHERE ".implode(" AND ", $where)) : "";

        // ---- Select grid dinámico ----
        $selectCols = [];
        $selectCols[] = $has('id') ? 'id' : 'NULL AS id';
        $selectCols[] = $C_FECHA_INI ? "{$C_FECHA_INI} AS fecha_ini" : "NULL AS fecha_ini";
        $selectCols[] = $C_FECHA_FIN ? "{$C_FECHA_FIN} AS fecha_fin" : "NULL AS fecha_fin";
        $selectCols[] = $C_CLIENTE   ? "{$C_CLIENTE}  AS cliente"   : "NULL AS cliente";
        $selectCols[] = $C_EVENTO    ? "{$C_EVENTO}   AS evento"    : "NULL AS evento";
        $selectCols[] = $C_RESULTADO ? "{$C_RESULTADO} AS resultado": "NULL AS resultado";
        $selectCols[] = $C_PROC      ? "{$C_PROC}     AS proc"      : "NULL AS proc";
        $selectCols[] = $C_ERR       ? "{$C_ERR}      AS err"       : "NULL AS err";

        if ($C_DUR_MS) {
            $selectCols[] = "{$C_DUR_MS} AS duracion_ms";
        } else if ($C_FECHA_INI && $C_FECHA_FIN) {
            $selectCols[] = "ROUND(TIMESTAMPDIFF(MICROSECOND, {$C_FECHA_INI}, {$C_FECHA_FIN})/1000) AS duracion_ms";
        } else {
            $selectCols[] = "NULL AS duracion_ms";
        }

        $selectCols[] = $C_TRACE   ? "{$C_TRACE}   AS trace_id"  : "NULL AS trace_id";
        $selectCols[] = $C_REF     ? "{$C_REF}     AS referencia": "NULL AS referencia";
        $selectCols[] = $C_SISTEMA ? "{$C_SISTEMA} AS sistema"   : "NULL AS sistema";

        // ---- Total ----
        $sqlCount = "SELECT COUNT(*) FROM {$DBNAME}.{$TABLE} {$whereSql}";
        $total = (int)db_val($sqlCount, $bind);

        // ---- Rows ----
        $sqlRows = "
            SELECT ".implode(", ", $selectCols)."
            FROM {$DBNAME}.{$TABLE}
            {$whereSql}
            ORDER BY ".($C_FECHA_INI ? "{$C_FECHA_INI} DESC" : "id DESC")."
            LIMIT {$pageSize} OFFSET {$offset}
        ";
        $rows = db_all($sqlRows, $bind) ?: [];

        // ---- KPI: total / ok / error / bloqueado ----
        // Clasificación por texto en resultado/estado/status si existe.
        $k_total = $total;
        $k_ok = 0; $k_err = 0; $k_blk = 0;

        if ($C_RESULTADO) {
            // KPI en SQL para rendimiento
            $sqlK = "
              SELECT
                SUM(CASE WHEN UPPER({$C_RESULTADO}) IN ('OK','SUCCESS','EXITOSO') THEN 1 ELSE 0 END) AS ok,
                SUM(CASE WHEN UPPER({$C_RESULTADO}) IN ('ERROR','FAIL','FALLO') THEN 1 ELSE 0 END) AS err,
                SUM(CASE WHEN UPPER({$C_RESULTADO}) IN ('BLOQUEADO','BLOCKED','DENIED') THEN 1 ELSE 0 END) AS blk
              FROM {$DBNAME}.{$TABLE}
              {$whereSql}
            ";
            $k = db_one($sqlK, $bind) ?: ['ok'=>0,'err'=>0,'blk'=>0];
            $k_ok  = (int)($k['ok'] ?? 0);
            $k_err = (int)($k['err'] ?? 0);
            $k_blk = (int)($k['blk'] ?? 0);
        } else {
            // Fallback: si no hay resultado, intenta inferir
            foreach ($rows as $r) {
                if (!empty($r['fecha_fin'])) $k_ok++;
                else $k_err++;
            }
        }

        // ---- Catálogos de filtros (cliente/evento/resultados) ----
        $clientes = [];
        if ($C_CLIENTE) {
            $clientes = db_all("
                SELECT DISTINCT {$C_CLIENTE} AS v
                FROM {$DBNAME}.{$TABLE}
                WHERE {$C_CLIENTE} IS NOT NULL AND {$C_CLIENTE} <> ''
                ORDER BY v
                LIMIT 500
            ") ?: [];
        }

        $eventos = [];
        if ($C_EVENTO) {
            $eventos = db_all("
                SELECT DISTINCT {$C_EVENTO} AS v
                FROM {$DBNAME}.{$TABLE}
                WHERE {$C_EVENTO} IS NOT NULL AND {$C_EVENTO} <> ''
                ORDER BY v
                LIMIT 500
            ") ?: [];
        }

        $resultados = [];
        if ($C_RESULTADO) {
            $resultados = db_all("
                SELECT DISTINCT {$C_RESULTADO} AS v
                FROM {$DBNAME}.{$TABLE}
                WHERE {$C_RESULTADO} IS NOT NULL AND {$C_RESULTADO} <> ''
                ORDER BY v
                LIMIT 200
            ") ?: [];
        }

        // ---- Ultima ejecución ----
        $ultima = null;
        if ($C_FECHA_INI) {
            $ultima = db_val("SELECT MAX({$C_FECHA_INI}) FROM {$DBNAME}.{$TABLE}");
        }

        // Export CSV
        if ($_GET['action'] === 'export_csv') {
            $filename = "wsadmin_bitacora_" . date('Ymd_His') . ".csv";
            $csv_out($filename, $rows);
        }

        // JSON normal
        $json_out([
            'ok' => true,
            'meta' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $total,
                'desde' => $desde,
                'hasta' => $hasta,
                'ultima_ejecucion' => $ultima,
            ],
            'kpis' => [
                'total' => $k_total,
                'ok' => $k_ok,
                'error' => $k_err,
                'bloqueado' => $k_blk,
            ],
            'filters' => [
                'clientes' => array_map(fn($x)=>$x['v'], $clientes),
                'eventos' => array_map(fn($x)=>$x['v'], $eventos),
                'resultados' => array_map(fn($x)=>$x['v'], $resultados),
            ],
            'rows' => $rows,
        ]);

    } catch (Throwable $e) {
        $json_out(['ok'=>false,'error'=>$e->getMessage()], 500);
    }
}

// ======================
// 2) UI NORMAL (HTML)
// ======================
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>
<div class="container-fluid" style="padding:18px 18px 28px 18px;">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <h5 class="mb-0" style="font-weight:700;">
        <i class="fa-solid fa-diagram-project me-2"></i>WebServices Admin · Dashboard
        <span class="badge bg-secondary ms-2" style="font-weight:600;">Solo lectura</span>
      </h5>
      <div class="text-muted" style="font-size:12px;">Monitoreo ejecutivo de integraciones · bitácora y KPIs</div>
    </div>
    <div class="d-flex gap-2">
      <button id="btnRefresh" class="btn btn-outline-primary btn-sm">
        <i class="fa-solid fa-rotate me-1"></i>Actualizar
      </button>
      <button id="btnExport" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-file-csv me-1"></i>Exportar CSV
      </button>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="row g-3 mb-3">
    <div class="col-12 col-md-3">
      <div class="card shadow-sm" style="border-left:4px solid #0d6efd;">
        <div class="card-body">
          <div class="text-muted" style="font-size:11px;">TOTAL EJECUCIONES</div>
          <div class="d-flex align-items-end justify-content-between">
            <div id="kpiTotal" style="font-size:28px;font-weight:800;">—</div>
            <div class="text-muted" style="font-size:11px;">Rango actual</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card shadow-sm" style="border-left:4px solid #198754;">
        <div class="card-body">
          <div class="text-muted" style="font-size:11px;">OK</div>
          <div class="d-flex align-items-end justify-content-between">
            <div id="kpiOk" style="font-size:28px;font-weight:800;">—</div>
            <div class="text-muted" style="font-size:11px;">Procesos exitosos</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card shadow-sm" style="border-left:4px solid #dc3545;">
        <div class="card-body">
          <div class="text-muted" style="font-size:11px;">ERROR</div>
          <div class="d-flex align-items-end justify-content-between">
            <div id="kpiErr" style="font-size:28px;font-weight:800;">—</div>
            <div class="text-muted" style="font-size:11px;">Fallas técnicas/negocio</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card shadow-sm" style="border-left:4px solid #fd7e14;">
        <div class="card-body">
          <div class="text-muted" style="font-size:11px;">BLOQUEADO</div>
          <div class="d-flex align-items-end justify-content-between">
            <div id="kpiBlk" style="font-size:28px;font-weight:800;">—</div>
            <div class="text-muted" style="font-size:11px;">Reglas/ventanas/permiso</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filters -->
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-12 col-md-2">
          <label class="form-label" style="font-size:11px;">Desde</label>
          <input id="fDesde" type="datetime-local" class="form-control form-control-sm">
        </div>
        <div class="col-12 col-md-2">
          <label class="form-label" style="font-size:11px;">Hasta</label>
          <input id="fHasta" type="datetime-local" class="form-control form-control-sm">
        </div>

        <div class="col-12 col-md-2">
          <label class="form-label" style="font-size:11px;">Cliente</label>
          <select id="fCliente" class="form-select form-select-sm">
            <option value="Todos">Todos</option>
          </select>
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label" style="font-size:11px;">Evento</label>
          <select id="fEvento" class="form-select form-select-sm">
            <option value="Todos">Todos</option>
          </select>
        </div>

        <div class="col-12 col-md-2">
          <label class="form-label" style="font-size:11px;">Resultado</label>
          <select id="fResultado" class="form-select form-select-sm">
            <option value="Todos">Todos</option>
          </select>
        </div>

        <div class="col-12 col-md-1">
          <label class="form-label" style="font-size:11px;">Buscar</label>
          <input id="fBuscar" class="form-control form-control-sm" placeholder="trace/ref">
        </div>

        <div class="col-12 d-flex justify-content-end gap-2 mt-2">
          <button id="btnApply" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-filter me-1"></i>Aplicar
          </button>
          <button id="btnClear" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-eraser me-1"></i>Limpiar
          </button>
        </div>

        <div class="col-12">
          <div class="text-muted" style="font-size:11px;">
            Última ejecución: <span id="lblUltima">—</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Grid -->
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <div style="font-weight:700;"><i class="fa-solid fa-list-check me-2"></i>Bitácora de ejecuciones</div>
        <div class="d-flex align-items-center gap-2">
          <span class="text-muted" style="font-size:11px;">Página</span>
          <button id="btnPrev" class="btn btn-outline-secondary btn-sm">&lt;</button>
          <span id="lblPage" style="min-width:28px;text-align:center;">1</span>
          <button id="btnNext" class="btn btn-outline-secondary btn-sm">&gt;</button>
        </div>
      </div>

      <div class="table-responsive" style="max-height:420px; overflow:auto;">
        <table class="table table-sm table-hover align-middle" style="font-size:10px; min-width:1200px;">
          <thead class="table-primary" style="position:sticky; top:0; z-index:2;">
            <tr>
              <th style="width:60px;">Acción</th>
              <th>Fecha ini</th>
              <th>Cliente</th>
              <th>Evento</th>
              <th>Resultado</th>
              <th class="text-end">Proc</th>
              <th class="text-end">Err</th>
              <th class="text-end">Duración ms</th>
              <th>Trace ID</th>
              <th>Referencia</th>
              <th>Sistema</th>
            </tr>
          </thead>
          <tbody id="gridBody">
            <tr><td colspan="11" class="text-center text-muted py-4">Cargando…</td></tr>
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-between mt-2">
        <div class="text-muted" style="font-size:11px;">
          Mostrando <span id="lblFrom">—</span> a <span id="lblTo">—</span> de <span id="lblTotal">—</span>
        </div>
        <div class="text-muted" style="font-size:11px;">PageSize: <b id="lblPageSize">25</b></div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const pageSize = 25;
  let page = 1;
  let total = 0;

  const els = {
    kpiTotal: document.getElementById('kpiTotal'),
    kpiOk: document.getElementById('kpiOk'),
    kpiErr: document.getElementById('kpiErr'),
    kpiBlk: document.getElementById('kpiBlk'),

    fDesde: document.getElementById('fDesde'),
    fHasta: document.getElementById('fHasta'),
    fCliente: document.getElementById('fCliente'),
    fEvento: document.getElementById('fEvento'),
    fResultado: document.getElementById('fResultado'),
    fBuscar: document.getElementById('fBuscar'),

    lblUltima: document.getElementById('lblUltima'),
    gridBody: document.getElementById('gridBody'),

    btnApply: document.getElementById('btnApply'),
    btnClear: document.getElementById('btnClear'),
    btnRefresh: document.getElementById('btnRefresh'),
    btnExport: document.getElementById('btnExport'),
    btnPrev: document.getElementById('btnPrev'),
    btnNext: document.getElementById('btnNext'),
    lblPage: document.getElementById('lblPage'),

    lblFrom: document.getElementById('lblFrom'),
    lblTo: document.getElementById('lblTo'),
    lblTotal: document.getElementById('lblTotal'),
    lblPageSize: document.getElementById('lblPageSize'),
  };

  els.lblPageSize.textContent = String(pageSize);

  function toLocalInputValue(dtStr) {
    // dtStr: "YYYY-mm-dd HH:ii:ss"
    if(!dtStr) return '';
    const s = dtStr.replace(' ', 'T').slice(0,16);
    return s;
  }

  function defaultRangeToday(){
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth()+1).padStart(2,'0');
    const d = String(now.getDate()).padStart(2,'0');
    els.fDesde.value = `${y}-${m}-${d}T00:00`;
    els.fHasta.value = `${y}-${m}-${d}T23:59`;
  }

  function buildParams(){
    const p = new URLSearchParams();
    p.set('action','data');
    p.set('page', String(page));
    p.set('pageSize', String(pageSize));

    const desde = els.fDesde.value ? els.fDesde.value.replace('T',' ') + ':00' : '';
    const hasta = els.fHasta.value ? els.fHasta.value.replace('T',' ') + ':00' : '';
    if(desde) p.set('desde', desde);
    if(hasta) p.set('hasta', hasta);

    if(els.fCliente.value && els.fCliente.value !== 'Todos') p.set('cliente', els.fCliente.value);
    if(els.fEvento.value && els.fEvento.value !== 'Todos') p.set('evento', els.fEvento.value);
    if(els.fResultado.value && els.fResultado.value !== 'Todos') p.set('resultado', els.fResultado.value);

    const q = (els.fBuscar.value || '').trim();
    if(q) p.set('buscar', q);

    return p;
  }

  function setOptions(selectEl, values){
    const keep = selectEl.value;
    selectEl.innerHTML = '<option value="Todos">Todos</option>';
    (values || []).forEach(v=>{
      const opt = document.createElement('option');
      opt.value = v;
      opt.textContent = v;
      selectEl.appendChild(opt);
    });
    // intenta conservar selección
    if(keep) selectEl.value = keep;
  }

  function badge(resultado){
    const r = String(resultado || '').toUpperCase();
    if(['OK','SUCCESS','EXITOSO'].includes(r)) return '<span class="badge bg-success">OK</span>';
    if(['ERROR','FAIL','FALLO'].includes(r)) return '<span class="badge bg-danger">ERROR</span>';
    if(['BLOQUEADO','BLOCKED','DENIED'].includes(r)) return '<span class="badge bg-warning text-dark">BLOQUEADO</span>';
    return `<span class="badge bg-secondary">${resultado ?? '—'}</span>`;
  }

  function renderRows(rows){
    if(!rows || rows.length === 0){
      els.gridBody.innerHTML = '<tr><td colspan="11" class="text-center text-muted py-4">Sin datos</td></tr>';
      return;
    }

    els.gridBody.innerHTML = rows.map(r=>{
      const id = r.id ?? '';
      const f1 = r.fecha_ini ?? '';
      const cli = r.cliente ?? '';
      const ev  = r.evento ?? '';
      const res = r.resultado ?? '';
      const proc = (r.proc ?? '') === null ? '' : (r.proc ?? '');
      const err  = (r.err ?? '') === null ? '' : (r.err ?? '');
      const dur  = (r.duracion_ms ?? '') === null ? '' : (r.duracion_ms ?? '');
      const tr   = r.trace_id ?? '';
      const ref  = r.referencia ?? '';
      const sis  = r.sistema ?? '';

      return `
        <tr>
          <td class="text-center">
            <button class="btn btn-outline-primary btn-sm" style="padding:2px 6px;font-size:10px;"
              onclick="navigator.clipboard.writeText('${String(tr).replace(/'/g,"")}');">
              Copiar
            </button>
          </td>
          <td>${f1}</td>
          <td>${cli}</td>
          <td>${ev}</td>
          <td>${badge(res)}</td>
          <td class="text-end">${proc}</td>
          <td class="text-end">${err}</td>
          <td class="text-end">${dur}</td>
          <td style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${tr}</td>
          <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${ref}</td>
          <td>${sis}</td>
        </tr>
      `;
    }).join('');
  }

  async function load(){
    els.gridBody.innerHTML = '<tr><td colspan="11" class="text-center text-muted py-4">Cargando…</td></tr>';
    try{
      const p = buildParams();
      const url = location.pathname + '?' + p.toString();
      const resp = await fetch(url, { headers: { 'Accept':'application/json' } });

      // Si te regresan HTML por sesión, aquí se detecta antes de reventar JSON.parse
      const text = await resp.text();
      if(text.trim().startsWith('<')){
        console.error('Respuesta NO JSON (probable login/HTML):', text.slice(0,200));
        els.gridBody.innerHTML = '<tr><td colspan="11" class="text-center text-danger py-4">La API devolvió HTML (sesión/redirect). Revisa auth o ejecuta action=data sin menú.</td></tr>';
        return;
      }

      const data = JSON.parse(text);
      if(!data.ok){
        els.gridBody.innerHTML = '<tr><td colspan="11" class="text-center text-danger py-4">Error: '+(data.error||'')+'</td></tr>';
        return;
      }

      total = data.meta.total || 0;

      els.kpiTotal.textContent = data.kpis.total ?? '0';
      els.kpiOk.textContent    = data.kpis.ok ?? '0';
      els.kpiErr.textContent   = data.kpis.error ?? '0';
      els.kpiBlk.textContent   = data.kpis.bloqueado ?? '0';

      els.lblUltima.textContent = data.meta.ultima_ejecucion ?? '—';

      setOptions(els.fCliente, data.filters.clientes);
      setOptions(els.fEvento, data.filters.eventos);
      setOptions(els.fResultado, data.filters.resultados);

      renderRows(data.rows);

      els.lblPage.textContent = String(page);
      const from = total === 0 ? 0 : ((page-1)*pageSize + 1);
      const to = Math.min(page*pageSize, total);
      els.lblFrom.textContent = String(from);
      els.lblTo.textContent = String(to);
      els.lblTotal.textContent = String(total);

    }catch(e){
      console.error(e);
      els.gridBody.innerHTML = '<tr><td colspan="11" class="text-center text-danger py-4">Fallo al cargar. Ver consola.</td></tr>';
    }
  }

  // ---- Eventos UI ----
  els.btnApply.addEventListener('click', ()=>{ page=1; load(); });
  els.btnRefresh.addEventListener('click', ()=> load());
  els.btnClear.addEventListener('click', ()=>{
    page=1;
    defaultRangeToday();
    els.fCliente.value='Todos';
    els.fEvento.value='Todos';
    els.fResultado.value='Todos';
    els.fBuscar.value='';
    load();
  });

  els.btnPrev.addEventListener('click', ()=>{
    if(page>1){ page--; load(); }
  });

  els.btnNext.addEventListener('click', ()=>{
    const maxPage = Math.max(1, Math.ceil(total / pageSize));
    if(page < maxPage){ page++; load(); }
  });

  els.btnExport.addEventListener('click', ()=>{
    const p = buildParams();
    p.set('action','export_csv');
    const url = location.pathname + '?' + p.toString();
    window.location.href = url;
  });

  // Init
  defaultRangeToday();
  load();

})();
</script>
