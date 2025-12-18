<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<style>
.ap-title{
    display:flex;
    align-items:center;
    gap:8px;
    font-size:16px;
    font-weight:600;
    color:#0F5AAD;
    margin-bottom:10px;
}
table.dataTable tbody tr{ font-size:10px; }
.dataTables_scrollBody{ max-height:420px!important; }
tr.inactivo{ background:#f3f3f3; color:#999; }
.dt-center{ text-align:center; }
</style>

<div class="card p-2 mb-2">
    <div class="ap-title">
        <i class="fa-solid fa-users"></i>
        Catálogo de Usuarios
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary btn-sm" onclick="nuevo()">Nuevo</button>
        <button class="btn btn-outline-success btn-sm" onclick="exportarCSV()">Exportar CSV</button>
        <button class="btn btn-outline-primary btn-sm" onclick="modalImport.show()">Importar CSV</button>
    </div>
</div>

<div class="card p-2">
<table id="tblUsuarios" class="display compact nowrap" width="100%">
<thead>
<tr>
    <th>Acciones</th>
    <th>ID</th>
    <th>Clave</th>
    <th>Nombre</th>
    <th>Email</th>
    <th>Perfil</th>
    <th>Recuperar</th>
</tr>
</thead>
</table>
</div>

<!-- MODAL USUARIO -->
<div class="modal fade" id="mdlUsuario">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header">
    <h5 class="modal-title">Usuario</h5>
    <button class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<input type="hidden" id="id_user">
<div class="row g-2">
    <div class="col-md-6"><label>Clave</label><input class="form-control" id="cve_usuario"></div>
    <div class="col-md-6"><label>Nombre</label><input class="form-control" id="nombre_completo"></div>
    <div class="col-md-6"><label>Email</label><input class="form-control" id="email"></div>
    <div class="col-md-6"><label>Perfil</label><input class="form-control" id="perfil"></div>
    <div class="col-md-12"><label>Descripción</label><textarea class="form-control" id="des_usuario"></textarea></div>
    <div class="col-md-6">
        <label>Status</label>
        <select id="status" class="form-control">
            <option value="A">Activo</option>
            <option value="I">Inactivo</option>
        </select>
    </div>
    <div class="col-md-6">
        <label>Activo</label>
        <select id="Activo" class="form-control">
            <option value="1">Sí</option>
            <option value="0">No</option>
        </select>
    </div>
</div>
</div>
<div class="modal-footer">
    <button class="btn btn-success" onclick="guardar()">Guardar</button>
    <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
</div>
</div>
</div>
</div>

<!-- MODAL IMPORT -->
<div class="modal fade" id="mdlImport">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
    <h5 class="modal-title">Importar Usuarios CSV</h5>
    <button class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <p class="small text-muted">
        Layout:<br>
        <code>cve_usuario,nombre_completo,email,perfil,des_usuario,status,Activo</code>
    </p>
    <input type="file" id="csvFile" class="form-control" accept=".csv">
</div>
<div class="modal-footer">
    <button class="btn btn-primary" onclick="importarCSV()">Importar</button>
</div>
</div>
</div>
</div>

<script>
let tabla;
let modalUsuario;
let modalImport;

$(document).ready(function(){

    modalUsuario = new bootstrap.Modal('#mdlUsuario');
    modalImport  = new bootstrap.Modal('#mdlImport');

    tabla = $('#tblUsuarios').DataTable({
        pageLength:25,
        scrollY:420,
        scrollX:true,
        scrollCollapse:true,
        lengthChange:false,
        ajax:{
            url:'../api/usuarios.php',
            dataSrc:function(json){
                if(Array.isArray(json)) return json;
                if(json.data) return json.data;
                return [];
            }
        },
        rowCallback:function(row,data){
            if(data.Activo==0) $(row).addClass('inactivo');
        },
        columns:[
            {
                data:null,
                orderable:false,
                className:'dt-center',
                render:d=>`
                    <button class="btn btn-xs btn-warning" onclick="editar(${d.id_user})">✎</button>
                    <button class="btn btn-xs btn-danger" onclick="eliminar(${d.id_user})">🗑</button>
                `
            },
            {data:'id_user'},
            {data:'cve_usuario'},
            {data:'nombre_completo'},
            {data:'email'},
            {data:'perfil'},
            {
                data:'Activo',
                orderable:false,
                className:'dt-center',
                render:(d,t,r)=> d==0
                    ? `<button class="btn btn-xs btn-success" onclick="recuperar(${r.id_user})">♻</button>`
                    : ''
            }
        ]
    });
});

function nuevo(){
    $('#id_user').val('');
    $('input,textarea').val('');
    $('#status').val('A');
    $('#Activo').val(1);
    modalUsuario.show();
}

function editar(id){
    $.getJSON('../api/usuarios.php?action=get&id_user='+id,u=>{
        Object.keys(u).forEach(k=>$('#'+k).length && $('#'+k).val(u[k]));
        modalUsuario.show();
    });
}

function guardar(){
    let action = $('#id_user').val() ? 'update' : 'create';
    $.post('../api/usuarios.php?action='+action,{
        id_user:$('#id_user').val(),
        cve_usuario:$('#cve_usuario').val(),
        nombre_completo:$('#nombre_completo').val(),
        email:$('#email').val(),
        perfil:$('#perfil').val(),
        des_usuario:$('#des_usuario').val(),
        status:$('#status').val(),
        Activo:$('#Activo').val()
    },()=>{
        modalUsuario.hide();
        tabla.ajax.reload(null,false);
    });
}

function eliminar(id){
    if(confirm('¿Dar de baja el usuario?')){
        $.post('../api/usuarios.php?action=delete',{id_user:id},()=>tabla.ajax.reload(null,false));
    }
}

function recuperar(id){
    $.post('../api/usuarios.php?action=recover',{id_user:id},()=>tabla.ajax.reload(null,false));
}

function exportarCSV(){
    window.location = '../api/usuarios.php?action=export_csv';
}

function importarCSV(){
    let f = $('#csvFile')[0].files[0];
    if(!f) return;
    let fd = new FormData();
    fd.append('file',f);
    $.ajax({
        url:'../api/usuarios.php?action=import_csv',
        type:'POST',
        data:fd,
        processData:false,
        contentType:false,
        success:()=>{
            modalImport.hide();
            tabla.ajax.reload();
        }
    });
}
</script>
