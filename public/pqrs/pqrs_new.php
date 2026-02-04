<?php 
require_once __DIR__ . '/../bi/_menu_global.php';
require_once __DIR__ . '/../../app/db.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Nueva Incidencia (PQRS)</title>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
/* ===============================
   ASSISTPRO UI – CORPORATIVO
================================ */
body{
  font-family:'Inter','Segoe UI',Roboto,Helvetica,Arial,sans-serif;
  background:#f4f7fb;
  color:#334155;
}

.assistpro-title{
  font-weight:700;
  color:#1e3a8a;
  display:flex;
  align-items:center;
  gap:.5rem;
}

.assistpro-title i{
  font-size:1.6rem;
  color:#2563eb;
}

/* Cards */
.card{
  background:#ffffff;
  border-radius:14px;
  border:1px solid #e5eaf3;
}

.card-header{
  background:#f8fafc;
  font-weight:600;
  color:#1e293b;
  border-bottom:1px solid #e5eaf3;
  border-left:5px solid #2563eb;
  padding:.75rem 1rem;
}

.card-body{
  padding:1rem 1.25rem;
}

/* Labels */
label{
  font-size:.78rem;
  font-weight:600;
  color:#475569;
}

/* Inputs */
.form-control,
.select2-container--default .select2-selection--single{
  background:#ffffff;
  border:1px solid #cbd5e1;
  border-radius:8px;
  height:40px;
  font-size:.85rem;
}

.form-control:focus,
.select2-container--focus .select2-selection{
  border-color:#2563eb;
  box-shadow:0 0 0 2px rgba(37,99,235,.15);
}

/* Textarea como grilla (5 líneas máx) */
textarea.form-control{
  min-height:120px;
  max-height:120px;
  overflow:auto;
  resize:none;
  padding:10px;
  white-space:nowrap;
}

.select2-container{width:100%!important;}

/* Buttons */
.btn-primary{
  background:#2563eb;
  border:none;
  border-radius:8px;
  font-weight:600;
}

.btn-primary:hover{
  background:#1d4ed8;
}

.btn-light{
  border-radius:8px;
}

/* Toast */
.toast{
  border-radius:12px;
}
</style>
</head>

<body>

<div class="container-fluid mt-4">

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <h3 class="assistpro-title mb-0">
    <i class="bi bi-chat-left-dots"></i>
    Nueva Incidencia PQRS
  </h3>
  <a href="pqrs.php" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left"></i> Regresar
  </a>
</div>

<form id="formPQRS">

<!-- DATOS BASE -->
<div class="card mb-4">
<div class="card-header">Datos base</div>
<div class="card-body row g-3">

<div class="col-md-4">
<label>Almacén / CEDIS *</label>
<select id="cve_almacen" name="cve_almacen" class="form-control" required></select>
</div>

<div class="col-md-4">
<label>Tipo PQRS *</label>
<select name="tipo" class="form-control" required>
  <option value="">Seleccione</option>
  <option value="P">Petición</option>
  <option value="Q">Queja</option>
  <option value="R">Reclamo</option>
  <option value="S">Sugerencia</option>
</select>
</div>

<div class="col-md-4">
<label>Status *</label>
<select id="status_clave" name="status_clave" class="form-control" required></select>
</div>

<div class="col-md-6">
<label>Cliente *</label>
<select id="cve_clte" name="cve_clte" class="form-control" required></select>
</div>

<div class="col-md-3">
<label>Referencia tipo *</label>
<select id="ref_tipo" name="ref_tipo" class="form-control" required>
  <option value="">Seleccione</option>
  <option value="PEDIDO">Pedido</option>
  <option value="OC">OC</option>
  <option value="EMBARQUE">Embarque</option>
</select>
</div>

<div class="col-md-3">
<label>Pedido / OC / Embarque *</label>
<select id="ref_folio" name="ref_folio" class="form-control" required></select>
</div>

</div>
</div>

<!-- QUIÉN REPORTA -->
<div class="card mb-4">
<div class="card-header">Quién reporta y responsables</div>
<div class="card-body row g-3">

<div class="col-md-4">
<label>Quién reporta *</label>
<input type="text" name="reporta_nombre" class="form-control" required>
</div>

<div class="col-md-4">
<label>Contacto</label>
<input type="text" name="reporta_contacto" class="form-control">
</div>

<div class="col-md-4">
<label>Cargo</label>
<input type="text" name="reporta_cargo" class="form-control">
</div>

<div class="col-md-6">
<label>Responsable interno (recibe) *</label>
<input type="text" name="responsable_recibo" class="form-control" required>
</div>

<div class="col-md-6">
<label>Responsable de la acción</label>
<input type="text" name="responsable_accion" class="form-control">
</div>

</div>
</div>

<!-- CONTENIDO -->
<div class="card mb-4">
<div class="card-header">Contenido del caso</div>
<div class="card-body row g-3">

<div class="col-md-6">
<label>Asunto</label>
<input type="text" name="asunto" class="form-control">
</div>

<div class="col-md-6">
<label>Susceptible a cobro</label>
<select name="susceptible_cobro" class="form-control">
  <option value="0">No</option>
  <option value="1">Sí</option>
</select>
</div>

<div class="col-md-12">
<label>Descripción *</label>
<textarea name="descripcion" class="form-control" required></textarea>
</div>

</div>
</div>

<!-- BOTONES -->
<div class="d-flex justify-content-end gap-2 mb-5">
  <a href="pqrs.php" class="btn btn-light">Cancelar</a>
  <button type="submit" class="btn btn-primary px-4">
    <i class="bi bi-save me-1"></i> Guardar PQRS
  </button>
</div>

</form>
</div>

<!-- TOAST -->
<div class="position-fixed top-0 end-0 p-4" style="z-index:1055">
  <div id="toastMsg" class="toast text-bg-success border-0">
    <div class="d-flex">
      <div class="toast-body" id="toastText"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(function(){

$('#cve_almacen').select2({
  placeholder:'Seleccione almacén...',
  ajax:{
    url:'/assistpro_kardex_fc/public/api/almacenes_api.php',
    dataType:'json',
    data:()=>({ action:'list' }),
    processResults:r=>({
      results:r.rows.map(x=>({ id:x.id, text:x.nombre }))
    })
  }
});

$('#status_clave').select2({
  placeholder:'Seleccione status...',
  ajax:{
    url:'/assistpro_kardex_fc/public/api/pqrs/pqrs_api.php',
    dataType:'json',
    data:()=>({ action:'status' }),
    processResults:r=>({ results:r.results })
  }
});

$('#cve_clte').select2({
  placeholder:'Buscar cliente...',
  minimumInputLength:2,
  ajax:{
    url:'/assistpro_kardex_fc/public/api/pedidos/pedidos_api.php',
    dataType:'json',
    data:p=>({ action:'clientes', q:p.term }),
    processResults:r=>({
      results:r.rows.map(x=>({
        id:x.Cve_Clte,
        text:x.Cve_Clte+' - '+x.RazonSocial
      }))
    })
  }
});

$('#ref_folio').select2({
  placeholder:'Seleccione...',
  minimumInputLength:1,
  ajax:{
    transport:function(params,success,failure){
      if(!$('#ref_tipo').val()||!$('#cve_clte').val()){
        success({results:[]});return;
      }
      $.getJSON('/assistpro_kardex_fc/public/api/pqrs/pqrs_api.php',{
        action:'referencias_by_cliente',
        ref_tipo:$('#ref_tipo').val(),
        cve_clte:$('#cve_clte').val(),
        q:params.data.term
      }).done(r=>success({results:r.results}))
      .fail(failure);
    }
  }
});

$('#ref_tipo').on('change',()=>$('#ref_folio').val(null).trigger('change'));

$('#formPQRS').on('submit',function(e){
  e.preventDefault();
  $.post('/assistpro_kardex_fc/public/api/pqrs/pqrs_api.php?action=crear',
    $(this).serialize(),
    function(){
      $('#toastText').text('PQRS creada correctamente');
      new bootstrap.Toast($('#toastMsg')[0]).show();
      setTimeout(()=>location.href='pqrs.php',1200);
    },'json');
});

});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
