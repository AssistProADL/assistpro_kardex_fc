<?php require_once __DIR__.'/../bi/_menu_global.php'; ?>
<div class="container-fluid">
<h4><i class="fa fa-archive"></i> Administración de Importaciones · Traslados</h4>

<table class="table table-sm table-bordered mt-3" id="tbl">
<thead>
<tr>
<th>Folio</th>
<th>Usuario</th>
<th>Origen</th>
<th>Destino</th>
<th>Estatus</th>
<th>Documentos</th>
</tr>
</thead>
<tbody></tbody>
</table>
</div>

<script>
const API='../api/importadores/api_admin_importaciones.php';

fetch(API+'?action=list')
.then(r=>r.json())
.then(d=>{
 let h='';
 d.forEach(r=>{
  h+=`<tr>
   <td>${r.folio}</td>
   <td>${r.usuario}</td>
   <td>${r.almacen_origen}</td>
   <td>${r.zona_destino}</td>
   <td><span class="badge badge-success">RECIBIDO / RTM</span></td>
   <td>
    <a href="${API}?action=pdf_origen&folio=${r.folio}" target="_blank">PDF Origen</a> |
    <a href="${API}?action=pdf_destino&folio=${r.folio}" target="_blank">PDF Destino</a> |
    <a href="${API}?action=excel_validacion&folio=${r.folio}" target="_blank">Excel</a>
   </td>
  </tr>`;
 });
 document.querySelector('#tbl tbody').innerHTML=h;
});
</script>
<?php require_once __DIR__.'/../bi/_menu_global_end.php'; ?>
