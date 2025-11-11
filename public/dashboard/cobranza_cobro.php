<?php
require_once __DIR__ . '/../../app/db.php';

$doc = $_GET['doc'] ?? '';
if (!$doc) {
    die('<div class="alert alert-danger">Documento no especificado.</div>');
}

// Buscar información del documento
$sql = "SELECT * FROM v_cobranza_analitico WHERE Documento = :doc LIMIT 1";
$info = db_one($sql, [':doc'=>$doc]);

if (!$info) {
    die('<div class="alert alert-warning">No se encontró información para el documento seleccionado.</div>');
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

  <div class="card p-3 mb-3">
    <div class="row">
      <div class="col-md-3"><div class="label">Documento</div><div class="value"><?=$info['Documento']?></div></div>
      <div class="col-md-3"><div class="label">Cliente</div><div class="value"><?=$info['RazonSocial']?></div></div>
      <div class="col-md-3"><div class="label">Saldo</div><div class="value">$<?=number_format($info['Saldo'],2)?></div></div>
      <div class="col-md-3"><div class="label">Estatus</div><div class="value"><?=$info['EstatusTexto']?></div></div>
    </div>
    <div class="row mt-2">
      <div class="col-md-3"><div class="label">Fecha Registro</div><div class="value"><?=$info['FechaReg']?></div></div>
      <div class="col-md-3"><div class="label">Fecha Vencimiento</div><div class="value"><?=$info['FechaVence']?></div></div>
      <div class="col-md-3"><div class="label">Ruta</div><div class="value"><?=$info['RutaDescripcion']?></div></div>
      <div class="col-md-3"><div class="label">Empresa</div><div class="value"><?=$info['IdEmpresa']?></div></div>
    </div>
  </div>

  <form method="post" id="frmCobro">
    <div class="card p-3">
      <div class="row g-2">
        <div class="col-md-3">
          <label class="label">Fecha de cobro</label>
          <input type="date" name="fecha_cobro" class="form-control form-control-sm" value="<?=date('Y-m-d')?>" required>
        </div>
        <div class="col-md-3">
          <label class="label">Monto a cobrar</label>
          <input type="number" step="0.01" name="monto" class="form-control form-control-sm" required>
        </div>
        <div class="col-md-3">
          <label class="label">Forma de pago</label>
          <select name="forma_pago" class="form-select form-select-sm" required>
            <option value="">Seleccionar...</option>
            <option>EFECTIVO</option>
            <option>TRANSFERENCIA</option>
            <option>CHEQUE</option>
            <option>TARJETA</option>
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
$('#frmCobro').on('submit',function(e){
  e.preventDefault();
  let data=$(this).serialize();
  $.post('cobranza_cobro_save.php?doc=<?=$info['Documento']?>',data,function(r){
     alert(r.mensaje);
     if(r.ok) location.href='cobranza_analitico.php';
  },'json');
});
</script>
</body>
</html>
