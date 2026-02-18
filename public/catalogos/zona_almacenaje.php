<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
.ap-container{padding:15px;font-size:12px}
.ap-title{font-size:18px;font-weight:600;margin-bottom:15px}
.ap-card{background:#fff;border:1px solid #e9ecef;border-radius:6px;padding:12px;margin-bottom:12px}
.table{font-size:11px}
.ap-scroll{max-height:420px;overflow-y:auto}
.badge-activo{background:#198754;font-size:10px}
.badge-inactivo{background:#dc3545;font-size:10px}
.btn-xs{padding:2px 6px;font-size:10px}
</style>

<div class="ap-container">

    <div class="ap-title">Zona de Almacenaje</div>

    <div class="ap-card">
        <div class="row g-2">

            <div class="col-md-3">
                <label>Empresa</label>
                <select id="f_empresa" class="form-select form-select-sm"></select>
            </div>

            <div class="col-md-3">
                <label>Almacén</label>
                <select id="f_almacen" class="form-select form-select-sm"></select>
            </div>

            <div class="col-md-3 align-self-end">
                <button class="btn btn-success btn-sm" onclick="nuevo()">+ Nuevo</button>
                <button class="btn btn-outline-secondary btn-sm" onclick="toggleInactivas()">Inactivas</button>
            </div>

        </div>
    </div>

    <div class="ap-card">
        <div class="ap-scroll">
            <table class="table table-bordered table-sm table-hover">
                <thead>
                    <tr>
                        <th width="80">Acciones</th>
                        <th>Clave</th>
                        <th>Descripción</th>
                        <th>Tipo</th>
                        <th>ABC</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody id="tbodyZonas"></tbody>
            </table>
        </div>
    </div>

</div>

<script>

let mostrarInactivas=false;

init();

function init(){
    fetch('../api/filtros_assistpro.php?action=init&secciones=empresas,almacenes')
    .then(r=>r.json())
    .then(res=>{
        loadEmpresas(res.empresas);
        window._almacenes=res.almacenes;
    });
}

function loadEmpresas(empresas){
    let html='<option value="">Seleccione</option>';
    empresas.forEach(e=>{
        html+=`<option value="${e.cve_cia}">${e.des_cia}</option>`;
    });
    document.getElementById('f_empresa').innerHTML=html;
}

document.getElementById('f_empresa')
.addEventListener('change',function(){

    let empresa=this.value;
    let html='<option value="">Seleccione</option>';

    window._almacenes.forEach(a=>{
        if(a.idp && empresa){
            html+=`<option value="${a.idp}">${a.nombre}</option>`;
        }
    });

    document.getElementById('f_almacen').innerHTML=html;
    document.getElementById('tbodyZonas').innerHTML='';
});

document.getElementById('f_almacen')
.addEventListener('change',loadZonas);

function loadZonas(){

    let empresa=document.getElementById('f_empresa').value;
    let almacen=document.getElementById('f_almacen').value;

    if(!empresa || !almacen) return;

    fetch(`../api/filtros_assistpro.php?action=init&secciones=zonas_almacenaje&empresa=${empresa}&almacen=${almacen}`)
    .then(r=>r.json())
    .then(res=>{

        let zonas=res.zonas_almacenaje || [];
        let html='';

        if(zonas.length===0){
            html=`<tr><td colspan="6" class="text-center text-muted">Sin registros</td></tr>`;
        }

        zonas.forEach(z=>{
            let badge=z.Activo==1
                ? `<span class="badge badge-activo">Activo</span>`
                : `<span class="badge badge-inactivo">Inactivo</span>`;

            html+=`
            <tr>
                <td>
                    <button class="btn btn-warning btn-xs" onclick="editar(${z.cve_almac})">✏</button>
                </td>
                <td>${z.clave_almacen}</td>
                <td>${z.des_almac}</td>
                <td>${z.Cve_TipoZona ?? ''}</td>
                <td>${z.clasif_abc ?? ''}</td>
                <td>${badge}</td>
            </tr>`;
        });

        document.getElementById('tbodyZonas').innerHTML=html;
    });
}

</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
