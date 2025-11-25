<?php
require_once __DIR__ . '/../../app/db.php';

$doc = $_GET['doc'] ?? '';
if (!$doc) {
    die('<div class="alert alert-danger m-3">Documento no especificado.</div>');
}

// Traemos info del documento desde la vista analítica
$info = db_one("SELECT * FROM v_cobranza_analitico WHERE Documento = ? LIMIT 1", [$doc]);
if (!$info) {
    die('<div class="alert alert-warning m-3">No se encontró información para el documento seleccionado.</div>');
}

// Buscar último abono en detallecob para este IdCobranza
$last = db_one("SELECT Saldo, SaldoAnt, Abono, Fecha 
                FROM detallecob 
                WHERE IdCobranza = ? 
                ORDER BY Fecha DESC 
                LIMIT 1", [$info['id']]);

if ($last) {
    $saldoActual = (float)$last['Saldo'];      // saldo después del último abono
    $saldoAnt    = $saldoActual;              // saldo de referencia para el nuevo abono
} else {
    $saldoActual = (float)$info['Saldo'];     // saldo actual en cobranza
    $saldoAnt    = $saldoActual;
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Cobro de Documento</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
:root{--primary:#0F5AAD;--bg:#f4f6fb;}
body{background:var(--bg);font-size:10px;}
.card{border-radius:.75rem;box-shadow:0 4px 8px rgba(0,0,0,.08);}
.label{font-weight:600;color:#6b7280;text-transform:uppercase;font-size:9px;}
.value{font-size:11px;color:#111827;}
</style>
</head>
<body class="p-3">
<div class="container">
  <h5 class="text-primary fw-bold mb-3">Cobro de Documento</h5>

  <!-- Datos del documento -->
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
        <div class="label">Saldo actual</div>
        <div class="value text-danger fw-bold">$<?=number_format($saldoActual,2)?></div>
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
        <div class="value"><?=$info['EstatusTexto']?></div>
      </div>
    </div>
  </div>

  <!-- Formulario de cobro -->
  <form method="post" id="frmCobro">
    <input type="hidden" name="saldo_ant" value="<?=htmlspecialchars($saldoAnt)?>">
    <div class="card p-3">
      <div class="row g-2">
        <div class="col-md-3">
          <label class="label">Fecha de cobro</label>
          <input type="date" name="fecha_cobro" class="form-control form-control-sm"
                 value="<?=date('Y-m-d')?>" required>
        </div>
        <div class="col-md-3">
          <label class="label">Monto a cobrar</label>
          <input type="number" step="0.01" min="0" name="monto" class="form-control form-control-sm" required>
        </div>
        <div class="col-md-3">
          <label class="label">Forma de pago</label>
          <select name="forma_pago" class="form-select form-select-sm" required>
            <option value="">Seleccionar...</option>
            <!-- IDs de forma de pago (puedes ajustarlos a tu catálogo real) -->
            <option value="1">Efectivo</option>
            <option value="2">Transferencia</option>
            <option value="3">Cheque</option>
            <option value="4">Tarjeta</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="label">Referencia / Folio</label>
          <input type="text" name="referencia" class="form-control form-control-sm">
        </div>
      </div>
      <div class="row mt-2">
        <div class="col-md-12">
          <label class="label">Observaciones</label>
          <textarea name="obs" class="form-control form-control-sm" rows="2"></textarea>
        </div>
      </div>
      <div class="row mt-3">
        <div class="col text-end">
          <button type="submit" class="btn btn-sm btn-primary">Registrar Cobro</button>
          <a href="cobranza_analitico.php" class="btn btn-sm btn-outline-secondary">Regresar</a>
        </div>
      </div>
    </div>
  </form>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$('#frmCobro').on('submit', function(e){
  e.preventDefault();
  let data = $(this).serialize();
  $.post('cobranza_cobro_save.php?doc=<?=urlencode($info['Documento'])?>', data, function(r){
      if(r && r.mensaje) alert(r.mensaje);
      if(r && r.ok){
          window.location.href = 'cobranza_analitico.php';
      }
  }, 'json').fail(function(){
      alert('Error al registrar el cobro.');
  });
});
</script>
</body>
</html>
