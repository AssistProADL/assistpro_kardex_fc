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

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
/* =========================
   FONDO GENERAL
========================= */
body{
  font-family:'Inter','Segoe UI',Roboto,Helvetica,Arial,sans-serif;
  background:#f1f5f9;
  color:#334155;
}

/* =========================
   TITULOS
========================= */
h3{
  font-weight:600;
  color:#0f172a;
  margin-bottom:1rem;
}

/* =========================
   CARDS / SECCIONES
========================= */
.card{
  background:#ffffff;
  border-radius:12px;
  border:1px solid #e2e8f0;
  box-shadow:0 8px 22px rgba(15,23,42,.06);
  margin-bottom:1.5rem;
}

/* BARRA SUPERIOR DE CADA SECCIÓN */
.card-header{
  background:#f8fafc;
  font-weight:600;
  color:#0f172a;
  border-bottom:1px solid #e2e8f0;
  border-top:4px solid #2563eb; /* acento visual */
}

/* =========================
   LABELS
========================= */
label{
  font-size:.85rem;
  font-weight:600;
  color:#475569;
  margin-bottom:4px;
}

/* =========================
   INPUTS (CLAROS)
========================= */
.form-control,
.select2-container--default .select2-selection--single{
  background:#ffffff;              /* BLANCO */
  border:1px solid #cbd5e1;
  border-radius:8px;
  height:40px;
  font-size:.9rem;
  color:#0f172a;
}

/* Placeholder */
.form-control::placeholder{
  color:#94a3b8;
}

/* Focus */
.form-control:focus,
.select2-container--focus .select2-selection{
  border-color:#2563eb;
  box-shadow:0 0 0 3px rgba(37,99,235,.15);
}

/* Textarea */
textarea.form-control{
  height:auto;
  min-height:110px;
}

/* =========================
   SELECT2
========================= */
.select2-container{
  width:100%!important;
}

.select2-container--default .select2-selection--single{
  display:flex;
  align-items:center;
  padding-left:8px;
}

/* =========================
   BOTONES
========================= */
.btn-primary{
  background:#2563eb;
  border:none;
  border-radius:8px;
  padding:8px 18px;
  font-weight:600;
  box-shadow:0 10px 22px rgba(37,99,235,.35);
}
.btn-primary:hover{
  background:#1d4ed8;
}

.btn-outline-secondary{
  border-radius:8px;
  padding:8px 18px;
}

/* =========================
   TOAST
========================= */
.toast{
  border-radius:12px;
  box-shadow:0 14px 35px rgba(15,23,42,.25);
}
</style>
</head>

<body>

<div class="container-fluid mt-4">

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <h3 class="mb-0">Nueva Incidencia (PQRS)</h3>
  <a href="pqrs.php" class="btn btn-outline-secondary">
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
<textarea name="descripcion" class="form-control" rows="4" required></textarea>
</div>

</div>
</div>

<!-- BOTONES -->
<div class="d-flex justify-content-end gap-2 mb-5">
  <a href="pqrs.php" class="btn btn-light">Cancelar</a>
  <button type="submit" class="btn btn-primary px-4">Guardar PQRS</button>
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

<script>
$(function(){

/* Almacenes */
$('#cve_almacen').select2({
  placeholder:'Seleccione almacén...',
  ajax:{
    url:'/assistpro_kardex_fc/public/api/almacenes_api.php',
    dataType:'json',
    data:()=>({ action:'list' }),
    processResults:r=>({
      results:r.rows.map(x=>({
        id:x.id,
        text:x.nombre
      }))
    })
  }
});

/* Status */
$('#status_clave').select2({
  placeholder:'Seleccione status...',
  ajax:{
    url:'/assistpro_kardex_fc/public/api/pqrs/pqrs_api.php',
    dataType:'json',
    data:()=>({ action:'status' }),
    processResults:r=>({ results:r.results })
  }
});

/* Clientes */
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

/* Referencias */
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

/* Guardar */
$('#formPQRS').on('submit',function(e){
  e.preventDefault();
  $.post('/assistpro_kardex_fc/public/api/pqrs/pqrs_api.php?action=crear',
    $(this).serialize(),
    function(r){
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
