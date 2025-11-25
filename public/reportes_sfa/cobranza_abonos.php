<?php
require_once __DIR__ . '/../../app/db.php';

$doc = $_GET['doc'] ?? '';
if (!$doc) {
    die('<div class="alert alert-danger m-3">Documento no especificado.</div>');
}

// Información del documento
$info = db_one("SELECT * FROM v_cobranza_analitico WHERE Documento = ? LIMIT 1", [$doc]);
if (!$info) {
    die('<div class="alert alert-warning m-3">No se encontró información para el documento seleccionado.</div>');
}

// Abonos registrados (OJO: ya NO filtramos por Cancelada = 1)
$abonos = db_all("
    SELECT Fecha, Abono, FormaP, SaldoAnt, Saldo, ClaveBco, Cancelada
    FROM detallecob
    WHERE IdCobranza = ?
    ORDER BY Fecha ASC, Id ASC
", [$info['id']]);

function nombreFormaPago($id){
    switch((int)$id){
        case 1: return 'Efectivo';
        case 2: return 'Transferencia';
        case 3: return 'Cheque';
        case 4: return 'Tarjeta';
        default: return 'ID '.$id;
    }
}
function fnum($n){return number_format($n,2,'.',',');}

$saldoOriginal = (float)$info['Saldo'];
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Abonos del Documento</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<style>
:root{--primary:#0F5AAD;--bg:#f4f6fb;}
body{background:var(--bg);font-size:10px;}
.card{border-radius:.75rem;box-shadow:0 4px 8px rgba(0,0,0,.08);}
.label{font-weight:600;color:#6b7280;text-transform:uppercase;font-size:9px;}
.value{font-size:11px;color:#111827;}
.table-sm th,.table-sm td{padding:.25rem .35rem;font-size:10px;}
.dataTables_scrollBody{max-height:400px!important;}
.badge-canc{font-size:9px;}
</style>
</head>
<body class="p-3">
<div class="container-fluid">
  <h5 class="text-primary fw-bold mb-3">Abonos del Documento</h5>

  <!-- Encabezado -->
  <div class="card p-3 mb-3">
    <div class="row">
      <div class="col-md-3">
        <div class="label">Documento</div>
        <div class="value"><?=$info['Documento']?></div>
      </div>
      <div class="col-md-3">
        <div class="label">Cliente</div>
        <div class="value"><?=$info['RazonSocial']?></div>
      </div>
      <div class="col-md-2">
        <div class="label">Total documento</div>
        <div class="value fw-bold">$<?=fnum($saldoOriginal)?></div>
      </div>
      <div class="col-md-2">
        <div class="label">Ruta</div>
        <div class="value"><?=$info['RutaDescripcion']?></div>
      </div>
      <div class="col-md-2">
        <div class="label">Empresa</div>
        <div class="value"><?=$info['IdEmpresa']?></div>
      </div>
    </div>
    <div class="row mt-2">
      <div class="col-md-3">
        <div class="label">Fecha registro</div>
        <div class="value"><?=$info['FechaReg']?></div>
      </div>
      <div class="col-md-3">
        <div class="label">Fecha vencimiento</div>
        <div class="value"><?=$info['FechaVence']?></div>
      </div>
      <div class="col-md-3">
        <div class="label">Estatus</div>
        <div class="value"><?=$info['Status']==2?'PAGADO':'ABIERTO'?></div>
      </div>
      <div class="col-md-3">
        <div class="label">Estatus del crédito</div>
        <div class="value"><?=$info['EstatusTexto']?></div>
      </div>
    </div>
  </div>

  <!-- Lista de abonos -->
  <div class="card p-3">
    <h6 class="mb-2">Abonos registrados</h6>
    <div class="table-responsive">
      <table id="tblAbonos" class="table table-striped table-bordered table-sm nowrap w-100">
        <thead class="table-light">
          <tr>
            <th>Fecha</th>
            <th>Abono</th>
            <th>Forma de pago</th>
            <th>Referencia / Banco</th>
            <th>Saldo anterior</th>
            <th>Saldo después</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($abonos as $a): ?>
          <tr>
            <td><?=$a['Fecha']?></td>
            <td class="text-end"><?=fnum($a['Abono'])?></td>
            <td><?=nombreFormaPago($a['FormaP'])?></td>
            <td><?=$a['ClaveBco']?></td>
            <td class="text-end"><?=fnum($a['SaldoAnt'])?></td>
            <td class="text-end"><?=fnum($a['Saldo'])?></td>
            <td class="text-center">
              <?php if((int)$a['Cancelada'] === 1): ?>
                <span class="badge bg-success badge-canc">Vigente</span>
              <?php else: ?>
                <span class="badge bg-secondary badge-canc">Sin marca</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="mt-3 text-end">
      <a href="cobranza_analitico.php" class="btn btn-sm btn-outline-secondary">Regresar</a>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function(){
  $('#tblAbonos').DataTable({
    pageLength:25,
    scrollY:'350px',
    scrollX:true,
    scrollCollapse:true,
    language:{url:'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json'}
  });
});
</script>
</body>
</html>
