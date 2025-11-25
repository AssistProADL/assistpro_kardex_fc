<?php
// public/dashboard/cobranza_analitico.php
require_once __DIR__ . '/../../app/db.php';

// ===== CATALOGO DE RUTAS (para el combo) =====
$rutas = db_all("
    SELECT ID_Ruta, cve_ruta, descripcion
    FROM t_ruta
    ORDER BY cve_ruta
", []);

// ===== FILTROS =====
$f_ruta       = $_GET['ruta']       ?? '';
$f_cliente    = $_GET['cliente']    ?? '';
$f_doc        = $_GET['doc']        ?? '';
$f_fini       = $_GET['fini']       ?? '';
$f_ffin       = $_GET['ffin']       ?? '';
$f_statuscred = $_GET['statuscred'] ?? '';   // 1=ABIERTO, 2=PAGADO

$where  = [];
$params = [];

// usar alias v. en todos los campos
if ($f_ruta !== '') {
    $where[]  = 'v.RutaId = ?';
    $params[] = (int)$f_ruta;
}
if ($f_cliente !== '') {
    $where[]  = '(v.RazonSocial LIKE ? OR v.Cve_Clte LIKE ?)';
    $params[] = "%$f_cliente%";
    $params[] = "%$f_cliente%";
}
if ($f_doc !== '') {
    $where[]  = 'v.Documento LIKE ?';
    $params[] = "%$f_doc%";
}
if ($f_fini !== '') {
    $where[]  = 'v.FechaReg >= ?';
    $params[] = $f_fini . ' 00:00:00';
}
if ($f_ffin !== '') {
    $where[]  = 'v.FechaReg <= ?';
    $params[] = $f_ffin . ' 23:59:59';
}
if ($f_statuscred !== '') {          // 1=abierto, 2=pagado
    $where[]  = 'v.Status = ?';
    $params[] = (int)$f_statuscred;
}

$whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// ===== CONSULTA: vista + abonos de detallecob =====
$sql = "
    SELECT 
        v.*,
        IFNULL(dc.TotalAbonos,0)                 AS TotalAbonos,
        (v.Saldo - IFNULL(dc.TotalAbonos,0))     AS SaldoRestante
    FROM v_cobranza_analitico v
    LEFT JOIN (
        SELECT IdCobranza, SUM(Abono) AS TotalAbonos
        FROM detallecob
        GROUP BY IdCobranza
    ) dc ON dc.IdCobranza = v.id
    $whereSql
    ORDER BY v.FechaVence IS NULL, v.FechaVence ASC
";

$rows = db_all($sql, $params);

// ===== KPIs =====
$totalCreditos     = 0;  // suma de cobranza.Saldo (Total original)
$totalSaldo        = 0;  // saldo restante (vigente/vencido)
$carteraVencida    = 0;
$carteraVigente    = 0;
$sumaDias          = 0;
$countDias         = 0;
$totalAbonosGlobal = 0;

$porEmpresa = [];
$aging = ['0-0'=>0,'1-30'=>0,'31-60'=>0,'61-90'=>0,'91-120'=>0,'121+'=>0,'SIN_RIESGO'=>0];

foreach ($rows as $r) {
    $totalDoc       = floatval($r['Saldo']);          // cobranza.Saldo = monto original
    $totalAbonoDoc  = floatval($r['TotalAbonos']);    // suma de abonos
    $saldoPendiente = floatval($r['SaldoRestante']);  // saldo real
    $estatus        = $r['EstatusTexto'];
    $empresa        = $r['IdEmpresa'];
    $dias           = $r['DiasAtraso'];
    $rango          = $r['RangoAntiguedad'];

    $totalCreditos     += $totalDoc;
    $totalSaldo        += $saldoPendiente;
    $totalAbonosGlobal += $totalAbonoDoc;

    if ($estatus === 'VENCIDO') $carteraVencida += $saldoPendiente;
    if ($estatus === 'VIGENTE') $carteraVigente += $saldoPendiente;
    if ($dias > 0) { $sumaDias += $dias; $countDias++; }

    $porEmpresa[$empresa] = ($porEmpresa[$empresa] ?? 0) + $saldoPendiente;
    $aging[$rango]        = ($aging[$rango] ?? 0) + $saldoPendiente;
}
$promDias = $countDias ? $sumaDias / $countDias : 0;

function fnum($n){return number_format($n,2,'.',',');}

$jsEmp    = json_encode(array_keys($porEmpresa));
$jsVal    = json_encode(array_values($porEmpresa));
$jsAgingL = json_encode(array_keys($aging));
$jsAgingV = json_encode(array_values($aging));

// ===== Frame global =====
$TITLE = 'Reporte Analítico de Cobranza';
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<!-- CSS / JS específicos de este reporte -->
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
:root{--primary:#0F5AAD;--bg:#f4f6fb;}
body{background:var(--bg);}
.table-sm th,table-sm td{padding:.25rem .35rem;font-size:10px;}
.card-kpi{border-radius:1rem;box-shadow:0 4px 10px rgba(0,0,0,.06);border:0;}
.card-kpi h6{font-size:.78rem;text-transform:uppercase;color:#6b7280;}
.card-kpi .value{font-size:1.4rem;font-weight:700;}
.chart-container{position:relative;max-height:250px;height:250px;}
.status-VIGENTE{background:#dcfce7!important;}
.status-VENCIDO{background:#fee2e2!important;}
.status-PAGADO{background:#f3f4f6!important;color:#6b7280;}
.dataTables_scrollBody{max-height:410px!important;}
.btn-action{font-size:10px;padding:.1rem .4rem;}
</style>

<div class="container-fluid py-3">
  <h5 class="text-primary fw-bold mb-2">Reporte Analítico de Cobranza</h5>

  <!-- Filtros -->
  <form class="card p-3 mb-3" method="get">
    <div class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="text-muted small">Ruta</label>
        <select name="ruta" class="form-select form-select-sm">
          <option value="">(Todas)</option>
          <?php foreach($rutas as $rt): ?>
            <option value="<?=$rt['ID_Ruta']?>"
              <?=$f_ruta!=='' && (int)$f_ruta===(int)$rt['ID_Ruta']?'selected':''?>>
              <?=$rt['cve_ruta']?> - <?=$rt['descripcion']?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="text-muted small">Cliente / Razón Social</label>
        <input type="text" name="cliente" class="form-control form-control-sm"
               value="<?=htmlspecialchars($f_cliente)?>" placeholder="Buscar cliente...">
      </div>
      <div class="col-md-2">
        <label class="text-muted small">Documento</label>
        <input type="text" name="doc" class="form-control form-control-sm"
               value="<?=htmlspecialchars($f_doc)?>" placeholder="No. documento">
      </div>
      <div class="col-md-2">
        <label class="text-muted small">Fecha desde</label>
        <input type="date" name="fini" class="form-control form-control-sm"
               value="<?=htmlspecialchars($f_fini)?>">
      </div>
      <div class="col-md-2">
        <label class="text-muted small">Fecha hasta</label>
        <input type="date" name="ffin" class="form-control form-control-sm"
               value="<?=htmlspecialchars($f_ffin)?>">
      </div>
    </div>

    <div class="row g-2 mt-2 align-items-end">
      <div class="col-md-2">
        <label class="text-muted small">Estatus crédito</label>
        <select name="statuscred" class="form-select form-select-sm">
          <option value="">(Todos)</option>
          <option value="1" <?=$f_statuscred==='1'?'selected':''?>>ABIERTO</option>
          <option value="2" <?=$f_statuscred==='2'?'selected':''?>>PAGADO</option>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2 mt-3 mt-md-0">
        <button type="submit" class="btn btn-primary btn-sm mt-auto">Filtrar</button>
        <a href="cobranza_analitico.php" class="btn btn-outline-secondary btn-sm mt-auto">Limpiar</a>
      </div>
    </div>
  </form>

  <!-- KPIs -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3 col-xl-2">
      <div class="card card-kpi p-3">
        <h6>Total por cobrar</h6>
        <div class="value">$<?=fnum($totalCreditos)?></div>
      </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
      <div class="card card-kpi p-3">
        <h6>Cartera vencida</h6>
        <div class="value text-danger">$<?=fnum($carteraVencida)?></div>
      </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
      <div class="card card-kpi p-3">
        <h6>Total abonos</h6>
        <div class="value text-primary">$<?=fnum($totalAbonosGlobal)?></div>
      </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
      <div class="card card-kpi p-3">
        <h6>Cartera vigente</h6>
        <div class="value text-success">$<?=fnum($carteraVigente)?></div>
      </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
      <div class="card card-kpi p-3">
        <h6>Promedio días atraso</h6>
        <div class="value"><?=number_format($promDias,1)?> días</div>
      </div>
    </div>
  </div>

  <!-- Gráficas -->
  <div class="row g-3 mb-3">
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header py-2">
          <strong>Saldo por empresa</strong>
        </div>
        <div class="card-body">
          <div class="chart-container">
            <canvas id="chartEmp"></canvas>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header py-2">
          <strong>Antigüedad de saldos</strong>
        </div>
        <div class="card-body">
          <div class="chart-container">
            <canvas id="chartAge"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabla -->
  <div class="card">
    <div class="card-body">
      <table id="tbl" class="table table-striped table-bordered table-sm nowrap w-100">
        <thead class="table-light">
        <tr>
          <th>Acciones</th>
          <th>Empresa</th>
          <th>Cliente</th>
          <th>Razón Social</th>
          <th>Ruta</th>
          <th>Documento</th>
          <th>TipoDoc</th>
          <th>Total</th>
          <th>Abonos</th>
          <th>Saldo</th>
          <th>Estatus</th>
          <th>Estatus del crédito</th>
          <th>Rango</th>
          <th>Fecha Reg</th>
          <th>Fecha Vence</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($rows as $r):
            $cls               = 'status-'.$r['EstatusTexto'];
            $estatusDocTxt     = ($r['Status'] == 1 ? 'ABIERTO' : ($r['Status'] == 2 ? 'PAGADO' : 'N/D'));
            $estatusCreditoTxt = $r['EstatusTexto'];
            $totalDoc          = floatval($r['Saldo']);
            $abonosDoc         = floatval($r['TotalAbonos']);
            $saldoPendiente    = floatval($r['SaldoRestante']);
        ?>
          <tr class="<?=$cls?>">
            <td class="text-nowrap">
              <a href="cobranza_cobro.php?doc=<?=urlencode($r['Documento'])?>"
                 class="btn btn-sm btn-outline-success btn-action" title="Registrar abono">💵</a>
              <a href="cobranza_abonos.php?doc=<?=urlencode($r['Documento'])?>"
                 class="btn btn-sm btn-outline-primary btn-action" title="Ver abonos">📄</a>
            </td>
            <td><?=$r['IdEmpresa']?></td>
            <td><?=$r['Cve_Clte']?></td>
            <td><?=$r['RazonSocial']?></td>
            <td><?=$r['RutaDescripcion']?></td>
            <td><?=$r['Documento']?></td>
            <td><?=$r['TipoDoc']?></td>
            <td class="text-end"><?=fnum($totalDoc)?></td>
            <td class="text-end"><?=fnum($abonosDoc)?></td>
            <td class="text-end"><?=fnum($saldoPendiente)?></td>
            <td><?=$estatusDocTxt?></td>
            <td><?=$estatusCreditoTxt?></td>
            <td><?=$r['RangoAntiguedad']?></td>
            <td><?=$r['FechaReg']?></td>
            <td><?=$r['FechaVence']?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div><!-- /.container-fluid -->

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
$(function(){
  $('#tbl').DataTable({
    pageLength:25,
    scrollY:'400px',
    scrollX:true,
    scrollCollapse:true,
    dom:'Bfrtip',
    buttons:[
      {extend:'excelHtml5',text:'Exportar Excel'},
      {extend:'csvHtml5',text:'CSV'},
      {extend:'print',text:'Imprimir'}
    ],
    language:{url:'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json'}
  });

  new Chart(document.getElementById('chartEmp'),{
    type:'bar',
    data:{labels:<?=$jsEmp?>,datasets:[{data:<?=$jsVal?>,label:'Saldo',backgroundColor:'#0F5AAD'}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}}}
  });

  new Chart(document.getElementById('chartAge'),{
    type:'pie',
    data:{labels:<?=$jsAgingL?>,datasets:[{data:<?=$jsAgingV?>,backgroundColor:['#0ea5e9','#facc15','#22c55e','#f97316','#ef4444','#6b7280','#e5e7eb']}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}}}
  });
});
</script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
?>
