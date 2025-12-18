<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<!-- ================= DEPENDENCIAS ================= -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<style>
/* ===== TÍTULO ===== */
.ap-title {
    font-size:18px;
    font-weight:600;
    color:#0F5AAD;
    padding:15px 0 10px 0;
    display:flex;
    align-items:center;
    gap:10px;
}

/* ===== GRILLA ===== */
table.dataTable tbody tr {
    font-size:10px;
    height:25px;
}

.dataTables_wrapper .dataTables_scrollBody {
    border:1px solid #dbe3f0;
}

/* ===== BOTONES ===== */
.btn-ap {
    padding:2px 6px;
    font-size:11px;
}

/* ===== SPINNER ===== */
#spinner {
    display:none;
    position:absolute;
    top:50%;
    left:50%;
    transform:translate(-50%,-50%);
    z-index:999;
}
</style>

<div class="ap-title">
    <i class="fa fa-ruler"></i>
    Catálogo de Unidades de Medida
</div>

<div style="margin-bottom:8px;">
    <button class="btn btn-primary btn-sm" onclick="nuevo()">
        <i class="fa fa-plus"></i> Nuevo
    </button>

    <button class="btn btn-secondary btn-sm" onclick="exportCSV()">
        <i class="fa fa-file-export"></i> Exportar CSV
    </button>

    <input type="file" id="csvFile" accept=".csv" style="display:none" onchange="importCSV(this)">
    <button class="btn btn-secondary btn-sm" onclick="$('#csvFile').click()">
        <i class="fa fa-file-import"></i> Importar CSV
    </button>
</div>

<div id="spinner">
    <i class="fa fa-spinner fa-spin fa-2x"></i>
</div>

<table id="tblUM" class="display nowrap" style="width:100%">
    <thead>
        <tr>
            <th>Opciones</th>
            <th>Clave</th>
            <th>Descripción</th>
            <th>Clave SAT</th>
            <th>Imp. Costo Prom.</th>
            <th>Status</th>
        </tr>
    </thead>
</table>

<script>
let tablaUM;

$(function(){

    tablaUM = $('#tblUM').DataTable({
        ajax:{
            url:'../api/unidades_medida.php',
            type:'POST',
            beforeSend:()=>$('#spinner').show(),
            complete:()=>$('#spinner').hide(),
            dataSrc:''
        },
        pageLength:25,
        scrollY:'60vh',
        scrollX:true,
        ordering:false,
        columns:[
            {
                data:null,
                render:d=>{
                    if(d.Activo==1){
                        return `
                            <button class="btn btn-sm btn-outline-primary btn-ap" onclick='editar(${JSON.stringify(d)})'>✏️</button>
                            <button class="btn btn-sm btn-outline-danger btn-ap" onclick='eliminar(${d.id_umed})'>🗑️</button>
                        `;
                    } else {
                        return `
                            <button class="btn btn-sm btn-outline-success btn-ap" onclick='recuperar(${d.id_umed})'>♻️</button>
                        `;
                    }
                }
            },
            {data:'cve_umed'},
            {data:'des_umed'},
            {data:'mav_cveunimed'},
            {data:'imp_cosprom'},
            {data:'Activo', render:d=>d==1?'Activo':'Inactivo'}
        ]
    });

});

/* ===== CRUD ===== */

function recuperar(id){
    if(!confirm('¿Deseas recuperar esta unidad de medida?')) return;
    $('#spinner').show();

    $.post('../api/unidades_medida.php',
        {action:'restore', id_umed:id},
        ()=>{
            tablaUM.ajax.reload(null,false);
            $('#spinner').hide();
        }
    );
}

function eliminar(id){
    if(!confirm('¿Inactivar unidad de medida?')) return;
    $('#spinner').show();

    $.post('../api/unidades_medida.php',
        {action:'delete', id_umed:id},
        ()=>{
            tablaUM.ajax.reload(null,false);
            $('#spinner').hide();
        }
    );
}

/* ===== CSV ===== */
function exportCSV(){
    window.location='../api/unidades_medida.php?action=export_csv';
}

function importCSV(input){
    let f=new FormData();
    f.append('file',input.files[0]);
    f.append('action','import_csv');

    $('#spinner').show();
    $.ajax({
        url:'../api/unidades_medida.php',
        method:'POST',
        data:f,
        processData:false,
        contentType:false,
        success:()=>{
            tablaUM.ajax.reload();
            $('#spinner').hide();
        }
    });
}
</script>
