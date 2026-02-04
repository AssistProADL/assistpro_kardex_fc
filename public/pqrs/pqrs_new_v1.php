<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Nueva Incidencia (PQRS v2)</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
.select2-container { width:100%!important; }
</style>
</head>

<body>

<div class="container-fluid mt-3">
<h3>Nueva Incidencia (PQRS v2)</h3>

<form id="formPQRS">

<!-- ===================== DATOS BASE ===================== -->
<div class="card mb-3">
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

<!-- ===================== QUIÉN REPORTA ===================== -->
<div class="card mb-3">
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

<!-- ===================== CONTENIDO ===================== -->
<div class="card mb-3">
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

<button type="submit" class="btn btn-primary">Guardar PQRS</button>

</form>
</div>

<script>
$(function(){

/* ===================== ALMACENES ===================== */
$('#cve_almacen').select2({
  placeholder:'Seleccione almacén...',
  ajax:{
    url:'/assistpro_kardex_fc/public/api/almacenes_api.php',
    dataType:'json',
    delay:250,
    data:()=>({ action:'list' }),
    processResults:function(resp){
      return {
        results: resp.rows.map(r => ({
          id: r.clave ?? r.id,     // CLAVE es la correcta
          text: r.nombre
        }))
      };
    }
  }
});

/* ===================== STATUS ===================== */
$('#status_clave').select2({
  ajax:{
    url:'/assistpro_kardex_fc/public/api/pqrs/pqrs_api.php',
    dataType:'json',
    data:()=>({ action:'status' }),
    processResults: resp => ({ results: resp.results })
  }
});

/* ===================== CLIENTES ===================== */
$('#cve_clte').select2({
  minimumInputLength:2,
  ajax:{
    url:'/assistpro_kardex_fc/public/api/pedidos/pedidos_api.php',
    dataType:'json',
    data: p => ({ action:'clientes', q:p.term }),
    processResults: r => ({
      results: r.rows.map(x=>({ id:x.Cve_Clte, text:x.Cve_Clte+' - '+x.RazonSocial }))
    })
  }
});

/* ===================== REFERENCIAS ===================== */
$('#ref_folio').select2({
  minimumInputLength:1,
  ajax:{
    transport:function(params,success,failure){
      const cliente = $('#cve_clte').val();
      const tipo = $('#ref_tipo').val();
      if(!cliente || !tipo) return success({results:[]});

      $.ajax({
        url:'/assistpro_kardex_fc/public/api/pqrs/pqrs_api.php',
        data:{
          action:'referencias_by_cliente',
          cve_clte:cliente,
          ref_tipo:tipo,
          q:params.data.term
        },
        dataType:'json',
        success: r => success({results:r.results}),
        error: failure
      });
    }
  }
});

$('#ref_tipo').on('change',()=>$('#ref_folio').val(null).trigger('change'));

/* ===================== GUARDAR ===================== */
$('#formPQRS').on('submit',function(e){
  e.preventDefault();
  $.post(
    '/assistpro_kardex_fc/public/api/pqrs/pqrs_api.php?action=crear',
    $(this).serialize(),
    resp => {
      if(resp.ok){
        alert('PQRS creada: '+resp.folio);
        location.reload();
      }else{
        alert(resp.error || 'Error al guardar');
      }
    },
    'json'
  );
});

});
</script>

</body>
</html>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
