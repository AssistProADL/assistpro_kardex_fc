<?php
require_once __DIR__ . '/../../app/db.php';
function g($k,$d=null){ return isset($_GET[$k]) ? trim($_GET[$k]) : $d; }
$IFRAME = (g('iframe','0')==='1' || strtolower(g('iframe','0'))==='true');
if($IFRAME){ header("Content-Security-Policy: frame-ancestors 'self'"); }

if(isset($_GET['ajax'])){
  header('Content-Type: application/json; charset=utf-8');
  $modo=g('modo','500'); $fini=g('fini',''); $ffin=g('ffin','');
  try{
    $params=[]; 
    $sql="SELECT Id,Fecha,Referencia,Mensaje,Respuesta,Enviado,Proceso,Dispositivo FROM t_log_ws ";
    if($modo==='rango'){
      if(!$fini||!$ffin){ echo json_encode(['ok'=>false,'error'=>'Debes proporcionar fecha inicial y final']); exit; }
      $sql.=" WHERE STR_TO_DATE(Fecha,'%Y-%m-%d %H:%i:%s') 
              BETWEEN STR_TO_DATE(CONCAT(:fini,' 00:00:00'),'%Y-%m-%d %H:%i:%s')
              AND STR_TO_DATE(CONCAT(:ffin,' 23:59:59'),'%Y-%m-%d %H:%i:%s')";
      $params[':fini']=$fini; $params[':ffin']=$ffin;
      $sql.=" ORDER BY STR_TO_DATE(Fecha,'%Y-%m-%d %H:%i:%s') DESC LIMIT 5000";
    }else{
      $sql.=" ORDER BY STR_TO_DATE(Fecha,'%Y-%m-%d %H:%i:%s') DESC LIMIT 500";
    }
    $rows = function_exists('db_all') ? db_all($sql,$params) : [];
    echo json_encode(['ok'=>true,'data'=>$rows], JSON_UNESCAPED_UNICODE);
  }catch(Throwable $e){ echo json_encode(['ok'=>false,'error'=>$e->getMessage()]); }
  exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Bitácora de Web Services</title>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<style>
:root{ --primary:#0d6efd; --muted:#6c757d; --ok-bg:#d1e7dd; --ok-fg:#0f5132; --bad-bg:#f8d7da; --bad-fg:#842029; }
body{ font-family: Arial, sans-serif; font-size:12px; margin:10px; color:#222; }

/* Topbar en una sola fila */
.topbar{ display:flex; align-items:flex-end; gap:16px; flex-wrap:wrap; margin:8px 0 12px; }
.h1{ font-size:28px; font-weight:800; margin:0 16px 0 0; }
.kpis{ display:flex; gap:10px; flex-wrap:wrap; }
.card{ background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:10px 14px; min-width:180px; box-shadow:0 1px 2px rgba(0,0,0,.04); }
.card h4{ margin:0 0 6px; font-size:12px; color:#555; font-weight:600; }
.card .num{ font-size:22px; font-weight:800; }
.card.ok{ border-color:#bcd7c8; }
.card.bad{ border-color:#f1aeb5; }
.filters-inline{ display:flex; gap:8px; align-items:end; flex-wrap:wrap; margin-left:auto; }
.filters-inline .field{ display:flex; flex-direction:column; gap:4px; }
.filters-inline label{ font-size:11px; color:#333; }
.filters-inline input[type="date"], .filters-inline select{ padding:6px; font-size:12px; }
button{ padding:8px 10px; border:none; border-radius:8px; cursor:pointer; font-size:12px; }
button.primary{ background:var(--primary); color:#fff; } button.secondary{ background:var(--muted); color:#fff; }

.dataTables_wrapper{ font-size:10px; }
table.dataTable thead th{ font-size:11px; }
table.dataTable tbody td{ white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:420px; }

.badge-ok{ display:inline-flex; align-items:center; gap:4px; font-size:10px; padding:2px 6px; border-radius:12px; background:var(--ok-bg); color:var(--ok-fg); border:1px solid #a3cfbb; }
.badge-fail{ display:inline-flex; align-items:center; gap:4px; font-size:10px; padding:2px 6px; border-radius:12px; background:var(--bad-bg); color:var(--bad-fg); border:1px solid #f1aeb5; }
.btn-accion{ padding:4px 8px; border-radius:6px; border:1px solid var(--primary); background:#fff; color:var(--primary); cursor:pointer; }

/* Modal */
#modal{ position:fixed; inset:0; background:rgba(0,0,0,.4); display:none; align-items:center; justify-content:center; z-index:1000; }
#modal .box{ width:min(1000px,92vw); max-height:80vh; overflow:auto; background:#fff; border-radius:10px; padding:14px; }
#modal .box h3{ margin:0 0 8px; }
#modal pre{ white-space:pre-wrap; word-break:break-word; font-family:Consolas,monospace; font-size:11px; background:#f8f9fa; border:1px solid #e9ecef; padding:10px; border-radius:8px; }
#modal .close{ float:right; cursor:pointer; border:0; background:#dc3545; color:#fff; border-radius:8px; padding:6px 10px; }
</style>
</head>
<body>
<?php if(!$IFRAME): include __DIR__ . '/_menu_global.php'; endif; ?>

<!-- Topbar: título + KPIs + filtros en UNA FILA -->
<div class="topbar">
  <h1 class="h1">Bitácora de Web Services</h1>
  <div class="kpis">
    <div class="card ok">
      <h4>Correctos (OK)</h4>
      <div class="num" id="kpiOk">0</div>
    </div>
    <div class="card bad">
      <h4>Con error</h4>
      <div class="num" id="kpiBad">0</div>
    </div>
  </div>

  <div class="filters-inline">
    <div class="field">
      <label>Fecha inicial</label>
      <input type="date" id="fini">
    </div>
    <div class="field">
      <label>Fecha final</label>
      <input type="date" id="ffin">
    </div>
    <div class="field">
      <label>&nbsp;</label>
      <button id="btnBuscar" class="primary">Buscar por rango</button>
    </div>
    <div class="field">
      <label>&nbsp;</label>
      <button id="btn500" class="secondary">Cargar primeras 500</button>
    </div>
    <div class="field">
      <label>Proceso</label>
      <select id="fProceso"><option value="">(Todos)</option></select>
    </div>
    <div class="field">
      <label>Status</label>
      <select id="fStatus">
        <option value="">(Todos)</option>
        <option value="ok">Correcto (OK)</option>
        <option value="bad">Envío Incorrecto</option>
      </select>
    </div>
  </div>
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

<?php if(!$IFRAME): include __DIR__ . '/_menu_global_end.php'; endif; ?>

<!-- Modal -->
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
    <div><b>Mensaje</b><pre id="detMensaje"></pre></div>
    <div><b>Respuesta</b><pre id="detRespuesta"></pre></div>
  </div>
</div>

<script>
let tabla, filtroProceso='', filtroStatus='';

function esOK(resp){
  if(resp==null) return false;
  const s=String(resp).trim().toLowerCase();
  if (/"error"\s*:\s*"?ok"?/.test(s)) return true;
  if (/"status"\s*:\s*"?ok"?/.test(s)) return true;
  if (s==='ok' || s==='{"ok":true}') return true;
  return s.includes('ok') && !s.includes('error') && !s.includes('fail') && !s.includes('exception');
}
function renderAcciones(row){
  const ok=esOK(row.Respuesta);
  const check= ok ? '<span class="badge-ok" title="Respuesta OK">✓ OK</span>' :
                    '<span class="badge-fail" title="Revisar">!</span>';
  const btnVer=`<button class="btn-accion btn-ver" data-id="${row.Id??''}">Ver</button>`;
  const btnRe =`<button class="btn-accion btn-reenviar" data-id="${row.Id??''}">↻ Reenviar</button>`;
  return check+' '+btnVer+' '+btnRe;
}

/* Filtro personalizado */
$.fn.dataTable.ext.search.push((settings, data, dataIndex, rowData)=>{
  if (filtroProceso && String(rowData.Proceso||'')!==filtroProceso) return false;
  if (filtroStatus==='ok'  && !esOK(rowData.Respuesta)) return false;
  if (filtroStatus==='bad' &&  esOK(rowData.Respuesta)) return false;
  return true;
});

function actualizarKpis(){
  if(!tabla){ $('#kpiOk').text('0'); $('#kpiBad').text('0'); return; }
  let ok=0,bad=0;
  tabla.rows({filter:'applied'}).every(function(){
    const r=this.data();
    if(esOK(r.Respuesta)) ok++; else bad++;
  });
  $('#kpiOk').text(ok); $('#kpiBad').text(bad);
}
function llenarProcesos(datos){
  const procesos=new Set();
  (datos||[]).forEach(r=>{ if(r.Proceso) procesos.add(r.Proceso); });
  const $p=$('#fProceso'); $p.empty().append('<option value="">(Todos)</option>');
  Array.from(procesos).sort().forEach(v=>$p.append(`<option value="${v}">${v}</option>`));
}
function cargarDatos(params){
  $.get(location.pathname, {...params, ajax:1}, (resp)=>{
    if(!resp.ok){ alert(resp.error||'Error al cargar'); return; }
    llenarProcesos(resp.data);
    if(tabla){
      tabla.clear().rows.add(resp.data).draw(); actualizarKpis(); return;
    }
    tabla = $('#tblLogs').DataTable({
      data: resp.data,
      columns: [
        { data:null, render:(_,__,row)=>renderAcciones(row), orderable:false },
        { data:'Id' },{ data:'Fecha' },{ data:'Referencia' },
        { data:'Mensaje' },{ data:'Respuesta' },{ data:'Enviado' },
        { data:'Proceso' },{ data:'Dispositivo' }
      ],
      order:[[2,'desc']],
      scrollY:'55vh', scrollX:true,
      pageLength:25,
      language:{ url:'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' }
    }).on('draw', actualizarKpis);
    actualizarKpis();
  }, 'json');
}

/* Carga por defecto: últimas 500 */
$(function(){ cargarDatos({modo:'500'}); });

/* Botones */
$('#btn500').on('click', ()=> cargarDatos({modo:'500'}));
$('#btnBuscar').on('click', ()=>{
  const fini=$('#fini').val(), ffin=$('#ffin').val();
  if(!fini||!ffin){ alert('Selecciona ambas fechas'); return; }
  cargarDatos({modo:'rango',fini,ffin});
});

/* Filtros inmediatos */
$('#fProceso').on('change', function(){ filtroProceso=this.value||''; if(tabla){ tabla.draw(); actualizarKpis(); }});
$('#fStatus').on('change',  function(){ filtroStatus =this.value||''; if(tabla){ tabla.draw(); actualizarKpis(); }});

/* ✅ Botón VER: toma la fila desde el DOM (robusto con paginación) */
$(document).on('click','.btn-ver', function(){
  if(!tabla) return;
  const row = tabla.row($(this).closest('tr')).data();
  if(!row) return;
  $('#detId').text(row.Id||''); $('#detFecha').text(row.Fecha||''); $('#detRef').text(row.Referencia||'');
  $('#detProc').text(row.Proceso||''); $('#detDisp').text(row.Dispositivo||''); $('#detEnv').text(row.Enviado||'');
  $('#detMensaje').text(row.Mensaje||''); $('#detRespuesta').text(row.Respuesta||'');
  $('#modal').css('display','flex');
});
$('#btnClose,#modal').on('click', e=>{ if(e.target.id==='modal'||e.target.id==='btnClose') $('#modal').hide(); });

/* Reenviar (hook) */
$(document).on('click','.btn-reenviar', function(){
  const id=$(this).data('id'); if(!id){ alert('Id no disponible'); return; }
  if(confirm('¿Reenviar transacción Id='+id+'?')){
    alert('Solicitud de reenvío enviada (pendiente integrar endpoint).');
  }
});
</script>
</body>
</html>
