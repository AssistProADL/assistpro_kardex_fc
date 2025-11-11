<?php
require_once __DIR__ . '/../../app/db.php';

// ============================================
//    CONSULTA BASE
// ============================================
$sql = "SELECT * FROM v_cobranza_analitico ORDER BY FechaVence IS NULL, FechaVence ASC";
$rows = db_all($sql, []);

// ============================================
//    KPI
// ============================================
$totalSaldo = $carteraVencida = $carteraVigente = $sumaDias = 0;
$countDias = 0;
$porEmpresa = [];
$aging = ['0-0'=>0,'1-30'=>0,'31-60'=>0,'61-90'=>0,'91-120'=>0,'121+'=>0,'SIN_RIESGO'=>0];

foreach ($rows as $r) {
    $saldo = floatval($r['Saldo']);
    $estatus = $r['EstatusTexto'];
    $empresa = $r['IdEmpresa'];
    $dias = $r['DiasAtraso'];
    $rango = $r['RangoAntiguedad'];
    $totalSaldo += $saldo;

    if ($estatus === 'VENCIDO') $carteraVencida += $saldo;
    if ($estatus === 'VIGENTE') $carteraVigente += $saldo;
    if ($dias > 0) { $sumaDias += $dias; $countDias++; }

    $porEmpresa[$empresa] = ($porEmpresa[$empresa] ?? 0) + $saldo;
    $aging[$rango] = ($aging[$rango] ?? 0) + $saldo;
}
$promDias = $countDias ? $sumaDias / $countDias : 0;
function fnum($n){ return number_format($n,2,'.',','); }

$jsEmp = json_encode(array_keys($porEmpresa));
$jsVal = json_encode(array_values($porEmpresa));
$jsAgingL = json_encode(array_keys($aging));
$jsAgingV = json_encode(array_values($aging));
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>AssistPro - Reporte Analítico de Cobranza</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
:root{--primary:#0F5AAD;--accent:#00A3E0;--bg:#f4f6fb;}
body{background:var(--bg);}
.card-kpi{border-radius:1rem;box-shadow:0 4px 10px rgba(0,0,0,.06);border:0;}
.card-kpi h6{font-size:.78rem;text-transform:uppercase;color:#6b7280;}
.card-kpi .value{font-size:1.4rem;font-weight:700;}
.chart-container{position:relative;max-height:250px;height:250px;}
.table-sm th,.table-sm td{padding:.25rem .35rem;font-size:10px !important;}
.status-VIGENTE{background:#dcfce7 !important;}
.status-VENCIDO{background:#fee2e2 !important;}
.status-PAGADO{background:#f3f4f6 !important;color:#6b7280;}
.status-SIN_VENCIMIENTO{background:#fef9c3 !important;}
.status-SIN_DATOS{background:#dbeafe !important;}
.btn-action{font-size:10px;padding:.1rem .4rem;}
.dataTables_scrollBody{max-height:410px !important;}
</style>
</head>
<body>
<div class="container-fluid py-3">
    <h3 class="text-primary fw-bold mb-1">Reporte Analítico de Cobranza</h3>
    <p class="text-muted mb-3">Análisis financiero por cliente, ruta y antigüedad de saldos.</p>

    <!-- KPIs -->
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card card-kpi p-3"><h6>Total por cobrar</h6><div class="value">$<?=fnum($totalSaldo)?></div></div></div>
        <div class="col-md-3"><div class="card card-kpi p-3"><h6>Cartera vencida</h6><div class="value text-danger">$<?=fnum($carteraVencida)?></div></div></div>
        <div class="col-md-3"><div class="card card-kpi p-3"><h6>Cartera vigente</h6><div class="value text-success">$<?=fnum($carteraVigente)?></div></div></div>
        <div class="col-md-3"><div class="card card-kpi p-3"><h6>Promedio días atraso</h6><div class="value"><?=number_format($promDias,1)?> días</div></div></div>
    </div>

    <!-- Gráficas -->
    <div class="row g-3 mb-3">
        <div class="col-md-6"><div class="card card-kpi p-3"><h6>Saldo por empresa</h6><div class="chart-container"><canvas id="chartEmp"></canvas></div></div></div>
        <div class="col-md-6"><div class="card card-kpi p-3"><h6>Antigüedad de saldos</h6><div class="chart-container"><canvas id="chartAge"></canvas></div></div></div>
    </div>

    <!-- Tabla -->
    <div class="card"><div class="card-body">
        <table id="tbl" class="table table-bordered table-striped table-sm nowrap w-100">
            <thead class="table-light">
                <tr>
                    <th>Acciones</th>
                    <th>Empresa</th>
                    <th>Cliente</th>
                    <th>Razón Social</th>
                    <th>Ruta</th>
                    <th>Documento</th>
                    <th>TipoDoc</th>
                    <th>Saldo</th>
                    <th>Estatus</th>
                    <th>Rango</th>
                    <th>Días atraso</th>
                    <th>Fecha Reg</th>
                    <th>Fecha Vence</th>
                    <th>Últ. Pago</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($rows as $r): 
                    $cls='status-'.$r['EstatusTexto']; ?>
                <tr class="<?=$cls?>">
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary btn-action" 
                                data-doc="<?=htmlspecialchars($r['Documento'])?>"
                                data-cliente="<?=htmlspecialchars($r['RazonSocial'])?>"
                                data-saldo="<?=fnum($r['Saldo'])?>"
                                data-fecha="<?=htmlspecialchars($r['FechaReg'])?>"
                                data-vencimiento="<?=htmlspecialchars($r['FechaVence'])?>"
                                data-estatus="<?=htmlspecialchars($r['EstatusTexto'])?>"
                                onclick="verDetalle(this)">
                            🔍
                        </button>
                    </td>
                    <td><?=$r['IdEmpresa']?></td>
                    <td><?=$r['Cve_Clte']?></td>
                    <td><?=$r['RazonSocial']?></td>
                    <td><?=$r['RutaDescripcion']?></td>
                    <td><?=$r['Documento']?></td>
                    <td><?=$r['TipoDoc']?></td>
                    <td class="text-end"><?=fnum($r['Saldo'])?></td>
                    <td><?=$r['EstatusTexto']?></td>
                    <td><?=$r['RangoAntiguedad']?></td>
                    <td><?=$r['DiasAtraso']?></td>
                    <td><?=$r['FechaReg']?></td>
                    <td><?=$r['FechaVence']?></td>
                    <td><?=$r['UltPago']?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div></div>
</div>

<!-- Modal Detalle -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white py-2">
        <h6 class="modal-title">Detalle del Documento</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="font-size:10px;">
        <div id="detalleBody"></div>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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

    // Charts
    new Chart(document.getElementById('chartEmp'),{
        type:'bar',
        data:{labels:<?=$jsEmp?>,datasets:[{data:<?=$jsVal?>,label:'Saldo',backgroundColor:'#0F5AAD'}]},
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}}}
    });
    new Chart(document.getElementById('chartAge'),{
        type:'pie',
        data:{labels:<?=$jsAgingL?>,datasets:[{data:<?=$jsAgingV?>,backgroundColor:['#0ea5e9','#22c55e','#facc15','#f97316','#ef4444','#6b7280','#e5e7eb']}]},
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}}}
    });
});

// Ver detalle
function verDetalle(btn){
    let doc=$(btn).data('doc'),
        cli=$(btn).data('cliente'),
        sal=$(btn).data('saldo'),
        fec=$(btn).data('fecha'),
        ven=$(btn).data('vencimiento'),
        est=$(btn).data('estatus');
    let html=`
        <b>Documento:</b> ${doc}<br>
        <b>Cliente:</b> ${cli}<br>
        <b>Saldo:</b> $${sal}<br>
        <b>Fecha Registro:</b> ${fec}<br>
        <b>Fecha Vencimiento:</b> ${ven}<br>
        <b>Estatus:</b> ${est}
    `;
    $('#detalleBody').html(html);
    new bootstrap.Modal(document.getElementById('modalDetalle')).show();
}
</script>
</body>
</html>
