<?php
// /public/dashboard/log_ws.php
require_once __DIR__ . '/../../app/db.php';
$PAGE_TITLE = 'Bitácora de Web Services (stg_t_log_ws)';

/* ========= AJAX JSON ========== */
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');

    $modo = $_GET['modo'] ?? '';
    $fini = $_GET['fini'] ?? '';
    $ffin = $_GET['ffin'] ?? '';

    try {
        $params = [];
        $sql = "SELECT 
                    Id, Fecha, Referencia, Mensaje, Respuesta, Enviado, Proceso, Dispositivo
                FROM stg_t_log_ws ";

        if ($modo === 'rango') {
            if (!$fini || !$ffin) {
                echo json_encode(['ok' => false, 'error' => 'Debes proporcionar fecha inicial y final']);
                exit;
            }
            // Ojo: las columnas están como TEXT; usamos STR_TO_DATE para filtrar/ordenar
            $sql .= " WHERE STR_TO_DATE(Fecha, '%Y-%m-%d %H:%i:%s') 
                      BETWEEN STR_TO_DATE(CONCAT(:fini,' 00:00:00'), '%Y-%m-%d %H:%i:%s') 
                      AND STR_TO_DATE(CONCAT(:ffin,' 23:59:59'), '%Y-%m-%d %H:%i:%s') ";
            $params[':fini'] = $fini;
            $params[':ffin'] = $ffin;
            $sql .= " ORDER BY STR_TO_DATE(Fecha, '%Y-%m-%d %H:%i:%s') DESC LIMIT 5000";
        } else {
            $sql .= " ORDER BY STR_TO_DATE(Fecha, '%Y-%m-%d %H:%i:%s') DESC LIMIT 500";
        }

        if (function_exists('db_all')) {
            $rows = db_all($sql, $params);
        } else {
            // Fallback PDO simple
            if (!isset($pdo) || !($pdo instanceof PDO)) {
                throw new Exception('Conexión PDO no disponible.');
            }
            $stmt = $pdo->prepare($sql);
            foreach ($params as $k=>$v) $stmt->bindValue($k, $v);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($PAGE_TITLE, ENT_QUOTES, 'UTF-8'); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- jQuery + DataTables estable -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<style>
:root { --azul:#0d6efd; --gris:#6c757d; }
body { font-family: Arial, sans-serif; font-size: 12px; margin: 10px; }
h2 { font-size: 16px; margin: 6px 0 10px; }
.filters { display: grid; grid-template-columns: repeat(10, minmax(120px, 1fr)); gap: 8px; align-items: end; margin-bottom: 10px; }
.filters label { font-size: 11px; color:#333; }
input[type="date"], select { padding: 6px; font-size: 12px; width: 100%; }
button { padding: 8px 10px; border: none; border-radius: 8px; cursor: pointer; font-size: 12px; }
button.primary { background: var(--azul); color: #fff; }
button.secondary { background: var(--gris); color: #fff; }
.btn-accion { padding: 4px 8px; border-radius: 6px; border: 1px solid var(--azul); background:#fff; color:var(--azul); cursor:pointer; }
.badge-ok { display:inline-flex; align-items:center; gap:4px; font-size:10px; padding:2px 6px; border-radius:12px; background:#d1e7dd; color:#0f5132; border:1px solid #a3cfbb; }
.badge-fail { display:inline-flex; align-items:center; gap:4px; font-size:10px; padding:2px 6px; border-radius:12px; background:#f8d7da; color:#842029; border:1px solid #f1aeb5; }
.dataTables_wrapper { font-size: 10px; }
table.dataTable thead th { font-size: 11px; }
table.dataTable tbody td { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 380px; }

/* Modal simple */
#modal { position: fixed; inset: 0; background: rgba(0,0,0,.4); display: none; align-items: center; justify-content: center; z-index: 9999; }
#modal .box { width: min(1100px, 92vw); max-height: 82vh; overflow: auto; background: #fff; border-radius: 10px; padding: 14px; box-shadow: 0 10px 30px rgba(0,0,0,.2); }
#modal .box h3 { margin: 0 0 8px; }
#modal pre { white-space: pre-wrap; word-break: break-word; font-family: Consolas, monospace; font-size: 11px; background:#f8f9fa; border:1px solid #e9ecef; padding:10px; border-radius:8px; }
#modal .close { float: right; cursor: pointer; border: 0; background: #dc3545; color:#fff; border-radius:8px; padding:6px 10px; }
</style>
</head>
<body>

<?php
/* ===== INICIO / MENÚ (fall-backs para iframe) ===== */
foreach ([
 
 include __DIR__.'../_menu_global.php',

 

] as $inc) {
    if (is_file($inc)) { include_once $inc; break; }
}
?>

<h2><?= htmlspecialchars($PAGE_TITLE, ENT_QUOTES, 'UTF-8'); ?></h2>

<div class="filters">
  <div>
    <label>Fecha inicial</label>
    <input type="date" id="fini">
  </div>
  <div>
    <label>Fecha final</label>
    <input type="date" id="ffin">
  </div>
  <div>
    <label>&nbsp;</label>
    <button id="btnBuscar" class="primary" style="width:100%">Buscar por rango</button>
  </div>
  <div>
    <label>&nbsp;</label>
    <button id="btn500" class="secondary" style="width:100%">Cargar primeras 500</button>
  </div>
  <div>
    <label>Proceso</label>
    <select id="fProceso"><option value="">(Todos)</option></select>
  </div>
  <div>
    <label>Status</label>
    <select id="fStatus">
      <option value="">(Todos)</option>
      <option value="ok">Correcto (OK)</option>
      <option value="bad">Envío Incorrecto</option>
    </select>
  </div>
  <div></div><div></div><div></div><div></div>
</div>

<table id="tblLogs" class="display" style="width:100%">
  <thead>
    <tr>
      <th>Acciones</th>
      <th>Id</th>
      <th>Fecha</th>
      <th>Referencia</th>
      <th>Mensaje</th>
      <th>Respuesta</th>
      <th>Enviado</th>
      <th>Proceso</th>
      <th>Dispositivo</th>
    </tr>
  </thead>
  <tbody></tbody>
</table>

<!-- Modal detalle -->
<div id="modal">
  <div class="box">
    <button class="close" id="btnClose">Cerrar</button>
    <h3>Detalle de Transacción</h3>
    <div id="detCampos" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
      <div><b>Id:</b> <span id="detId"></span></div>
      <div><b>Fecha:</b> <span id="detFecha"></span></div>
      <div><b>Referencia:</b> <span id="detRef"></span></div>
      <div><b>Proceso:</b> <span id="detProc"></span></div>
      <div><b>Dispositivo:</b> <span id="detDisp"></span></div>
      <div><b>Enviado:</b> <span id="detEnv"></span></div>
    </div>
    <div>
      <b>Mensaje</b>
      <pre id="detMensaje"></pre>
    </div>
    <div>
      <b>Respuesta</b>
      <pre id="detRespuesta"></pre>
    </div>
  </div>
</div>

<?php
/* ===== END / FOOTER (fall-backs) ===== */
foreach ([
    __DIR__ . '/../_menu_global_end.php',
    __DIR__ . '/../_end.php',
    __DIR__ . '/../footer.php',
    __DIR__ . '/../_footer.php',
] as $inc) {
    if (is_file($inc)) { include_once $inc; break; }
}
?>

<script>
/* Endpoint del propio archivo (soporta iframe/rutas) */
const endpoint = '<?= htmlspecialchars(basename(__FILE__), ENT_QUOTES, "UTF-8"); ?>';

let tabla;

/* === Helpers === */
function esOK(resp) {
  if (resp == null) return false;
  const s = String(resp).trim().toLowerCase();
  if (/"error"\s*:\s*"?ok"?/.test(s)) return true;
  if (/"status"\s*:\s*"?ok"?/.test(s)) return true;
  if (s === 'ok' || s === '{"ok":true}') return true;
  return s.includes('ok') && !s.includes('error') && !s.includes('fail') && !s.includes('exception');
}

function renderAcciones(row) {
  const ok = esOK(row.Respuesta);
  const check = ok ? '<span class="badge-ok" title="Respuesta OK">✓ OK</span>'
                   : '<span class="badge-fail" title="Revisar">!</span>';
  const btnVer = `<button class="btn-accion btn-ver" data-id="${row.Id??''}">Ver</button>`;
  const btnRe = `<button class="btn-accion btn-reenviar" data-id="${row.Id??''}">↻ Reenviar</button>`;
  return check + ' ' + btnVer + ' ' + btnRe;
}

/* Filtros personalizados (Proceso + Status) */
let filtroProceso = '';
let filtroStatus = ''; // '', 'ok', 'bad'
$.fn.dataTable.ext.search.push(function(settings, data, dataIndex, rowData) {
  if (filtroProceso && String(rowData.Proceso||'') !== filtroProceso) return false;
  if (filtroStatus === 'ok' && !esOK(rowData.Respuesta)) return false;
  if (filtroStatus === 'bad' && esOK(rowData.Respuesta)) return false;
  return true;
});

/* Cargar datos y armar tabla */
function cargarDatos(params) {
  $.get(endpoint, params, function(resp) {
    if (!resp.ok) { alert(resp.error || 'Error al cargar'); return; }

    // Llenar opciones de Proceso
    const procesos = new Set();
    (resp.data||[]).forEach(r => { if (r.Proceso) procesos.add(r.Proceso); });
    const $p = $('#fProceso');
    $p.empty().append('<option value="">(Todos)</option>');
    Array.from(procesos).sort().forEach(v => $p.append(`<option value="${v}">${v}</option>`));

    if (tabla) {
      tabla.clear().rows.add(resp.data).draw();
      return;
    }

    tabla = $('#tblLogs').DataTable({
      data: resp.data,
      columns: [
        { data: null, render: (_, __, row)=> renderAcciones(row), orderable:false },
        { data: 'Id' },
        { data: 'Fecha' },
        { data: 'Referencia' },
        { data: 'Mensaje' },
        { data: 'Respuesta' },
        { data: 'Enviado' },
        { data: 'Proceso' },
        { data: 'Dispositivo' }
      ],
      order: [[2, 'desc']], // Fecha (col 2)
      scrollY: '55vh',
      scrollX: true,
      pageLength: 25,
      language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' }
    });
  }, 'json');
}

/* Botones de carga manual (no auto-carga al entrar) */
$('#btn500').on('click', () => cargarDatos({ ajax:1, modo:'500' }));
$('#btnBuscar').on('click', () => {
  const fini = $('#fini').val(), ffin = $('#ffin').val();
  if (!fini || !ffin) { alert('Selecciona ambas fechas'); return; }
  cargarDatos({ ajax:1, modo:'rango', fini, ffin });
});

/* Filtros inmediatos */
$('#fProceso').on('change', function(){
  filtroProceso = this.value || '';
  if (tabla) tabla.draw();
});
$('#fStatus').on('change', function(){
  filtroStatus = this.value || '';
  if (tabla) tabla.draw();
});

/* Acciones: Modal Ver + Reenviar */
$(document).on('click', '.btn-ver', function(){
  const id = $(this).data('id');
  if (!tabla) return;
  const row = tabla.rows().data().toArray().find(r => String(r.Id) === String(id));
  if (!row) return;

  $('#detId').text(row.Id || '');
  $('#detFecha').text(row.Fecha || '');
  $('#detRef').text(row.Referencia || '');
  $('#detProc').text(row.Proceso || '');
  $('#detDisp').text(row.Dispositivo || '');
  $('#detEnv').text(row.Enviado || '');
  $('#detMensaje').text(row.Mensaje || '');
  $('#detRespuesta').text(row.Respuesta || '');
  $('#modal').css('display','flex');
});
$('#btnClose, #modal').on('click', function(e){
  if (e.target.id === 'modal' || e.target.id === 'btnClose') {
    $('#modal').hide();
  }
});

$(document).on('click', '.btn-reenviar', function(){
  const id = $(this).data('id');
  if (!id) { alert('Id no disponible'); return; }
  if (confirm('¿Reenviar transacción Id=' + id + '?')) {
    // TODO: conectar a tu endpoint real
    // fetch('/public/importar/reenvio_ws.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id }) })
    //   .then(r=>r.json()).then(j=>alert(j.ok?'Reenviado':'Error: '+j.error));
    alert('Solicitud de reenvío enviada (pendiente integrar endpoint real).');
  }
});
</script>
</body>
</html>
