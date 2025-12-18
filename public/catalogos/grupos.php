<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
    /* ================== ESTILO ASSISTPRO ================== */
    .ap-container{ padding:12px; }
    .ap-title{
        font-size:18px; font-weight:600; color:#0d6efd;
        display:flex; align-items:center; gap:8px; margin-bottom:10px;
    }
    .ap-toolbar{ display:flex; gap:8px; flex-wrap:wrap; margin-bottom:10px; }
    .ap-toolbar input{
        max-width:220px; border-radius:16px; padding:8px 12px;
        border:none; background:#f3f5f8;
        box-shadow:inset 0 0 0 1px #e1e5ea;
    }

    /* ===== GRID ===== */
    .ap-grid{
        border:1px solid #dee2e6;
        border-radius:16px;
        overflow:auto;
        max-height:520px;
    }
    .ap-grid table{
        width:100%;
        border-collapse:collapse;
        font-size:12px;
    }
    .ap-grid thead{ background:#e7f1ff; }
    .ap-grid th,
    .ap-grid td{
        border:1px solid #dee2e6;
        padding:8px;
        text-align:center;
    }
    .ap-grid th{ font-weight:600; }

    /* ===== MODAL BASE ===== */
    .ap-modal{
        position:fixed;
        inset:0;
        background:rgba(0,0,0,.55);
        display:none;
        align-items:center;
        justify-content:center;
        z-index:9999;
    }
    .ap-modal.show{ display:flex; }

    .ap-modal-content{
        background:#fff;
        width:980px;
        max-width:96%;
        border-radius:18px;
        overflow:hidden;
        box-shadow:0 20px 45px rgba(0,0,0,.25);
    }

    .ap-modal-header{
        background:#0d6efd;
        color:#fff;
        padding:16px 22px;
        display:flex;
        justify-content:space-between;
        align-items:center;
    }
    .ap-modal-header h3{
        margin:0;
        font-size:16px;
        font-weight:600;
        display:flex;
        align-items:center;
        gap:8px;
    }
    .ap-modal-header button{
        background:none;
        border:none;
        color:#fff;
        font-size:22px;
    }

    .ap-modal-body{
        padding:22px;
        font-size:13px;
    }

    .ap-modal-footer{
        padding:18px 22px;
        background:#f8f9fa;
        display:flex;
        justify-content:flex-end;
        gap:12px;
    }

    /* ===== FORM ===== */
    .ap-form{
        display:flex;
        flex-direction:column;
        gap:16px;
    }
    .ap-form label{
        font-size:12px;
        font-weight:600;
        color:#495057;
    }
    .ap-form input{
        width:100%;
        padding:14px 16px;
        border-radius:18px;
        border:none;
        background:#f3f5f8;
        box-shadow:inset 0 0 0 1px #e1e5ea;
    }
    .ap-form input:focus{
        background:#fff;
        box-shadow:
                inset 0 0 0 1px #0d6efd,
                0 0 0 3px rgba(13,110,253,.15);
        outline:none;
    }

    /* ===== IMPORTAR ===== */
    .ap-import-cols{
        display:grid;
        grid-template-columns:repeat(5,1fr);
        gap:10px;
        margin:16px 0;
    }
    .ap-import-cols span{
        background:#f8f9fa;
        border-radius:14px;
        padding:10px;
        font-size:12px;
        font-weight:600;
        text-align:center;
    }
    .ap-import-actions{
        display:flex;
        gap:10px;
        margin-top:10px;
    }

    /* ===== BOTONES ===== */
    .ap-btn{
        padding:8px 18px;
        border-radius:14px;
        border:1px solid #dee2e6;
        background:#f8f9fa;
        font-size:13px;
    }
    .ap-btn.primary{
        background:#0d6efd;
        border:none;
        color:#fff;
    }

    /* ===== PREVIEW ===== */
    .ap-grid-preview{
        border:1px solid #dee2e6;
        border-radius:14px;
        max-height:260px;
        overflow:auto;
    }
    .ap-grid-preview table{
        width:100%;
        border-collapse:collapse;
        font-size:12px;
    }
    .ap-grid-preview th,
    .ap-grid-preview td{
        border:1px solid #dee2e6;
        padding:8px;
        text-align:center;
    }

    /* quitar outline */
    button,button:focus,button:active{
        outline:none;
        box-shadow:none;
    }
</style>

<div class="ap-container">

    <div class="ap-title">
        <i class="fa fa-layer-group"></i> Catálogo Grupos de Artículos
    </div>

    <div class="ap-toolbar">
        <input id="txtBuscar" placeholder="Buscar...">
        <button class="btn btn-primary btn-sm" onclick="cargarDatos()">Buscar</button>
        <button class="btn btn-success btn-sm" onclick="nuevoGrupo()">Nuevo</button>
        <button class="btn btn-warning btn-sm" onclick="mostrarImportar()">Importar</button>
        <button class="btn btn-secondary btn-sm" onclick="toggleInactivos()">Ver Inactivos</button>
        <button class="btn btn-info btn-sm" onclick="exportarCSV()">Exportar</button>
    </div>

    <div class="ap-grid">
        <table>
            <thead>
            <tr>
                <th>Opciones</th>
                <th>Clave</th>
                <th>Descripción</th>
                <th>% Contable</th>
                <th>% Fiscal</th>
                <th>Almacén</th>
                <th>Estatus</th>
            </tr>
            </thead>
            <tbody id="gridDatos"></tbody>
        </table>
    </div>
</div>

<!-- ================== MODAL NUEVO / EDITAR ================== -->
<div class="ap-modal" id="mdlGrupo">
    <div class="ap-modal-content">
        <div class="ap-modal-header">
            <h3 id="tituloGrupo"><i class="fa fa-plus"></i> Grupo</h3>
            <button onclick="cerrarGrupo()">×</button>
        </div>

        <div class="ap-modal-body">
            <input type="hidden" id="g_id">
            <div class="ap-form">
                <div><label>Clave *</label><input id="g_cve"></div>
                <div><label>Descripción</label><input id="g_des"></div>
                <div><label>% Depósito Contable</label><input type="number" id="g_cont"></div>
                <div><label>% Depósito Fiscal</label><input type="number" id="g_fisc"></div>
                <div><label>Almacén</label><input type="number" id="g_alm"></div>
            </div>
        </div>

        <div class="ap-modal-footer">
            <button class="ap-btn" onclick="cerrarGrupo()">Cancelar</button>
            <button class="ap-btn primary" onclick="guardarGrupo()">Guardar</button>
        </div>
    </div>
</div>

<!-- ================== MODAL IMPORTAR ================== -->
<div class="ap-modal" id="mdlImport">
    <div class="ap-modal-content">
        <div class="ap-modal-header">
            <h3><i class="fa fa-upload"></i> Importar Grupos</h3>
            <button onclick="cerrarImportar()">×</button>
        </div>

        <div class="ap-modal-body">
            <label>Archivo CSV</label>
            <input type="file" id="fileCsv">

            <div class="ap-import-cols">
                <span>Clave</span>
                <span>Descripción</span>
                <span>% Contable</span>
                <span>% Fiscal</span>
                <span>Almacén</span>
            </div>

            <div class="ap-import-actions">
                <button class="ap-btn" onclick="descargarLayout()">Layout</button>
                <button class="ap-btn primary" onclick="previsualizar()">Previsualizar</button>
            </div>

            <div id="previewWrap" style="display:none">
                <div class="ap-grid-preview">
                    <table>
                        <thead id="csvHead"></thead>
                        <tbody id="csvBody"></tbody>
                    </table>
                </div>
                <div style="text-align:right;margin-top:10px">
                    <button class="ap-btn primary" onclick="importar()">Importar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const api = '../api/api_cat_grupos.php';
    let dataCache=[], verInactivos=0;

    /* ================== CARGAR ================== */
    function cargarDatos(){
        fetch(`${api}?action=list&inactivos=${verInactivos}&search[value]=${txtBuscar.value}`)
            .then(r=>r.json())
            .then(d=>{
                dataCache=d.data||[];
                renderGrid();
            });
    }

    /* ================== GRID ================== */
    function renderGrid(){
        gridDatos.innerHTML=dataCache.map(r=>`
        <tr>
            <td>
                <button class="btn btn-warning btn-sm" onclick="editar(${r.id})">✎</button>
                ${r.Activo==1
            ? `<button class="btn btn-danger btn-sm" onclick="desactivar(${r.id})">🗑</button>`
            : `<button class="btn btn-success btn-sm" onclick="restaurar(${r.id})">↺</button>`}
            </td>
            <td>${r.cve_gpoart}</td>
            <td>${r.des_gpoart||''}</td>
            <td>${r.por_depcont??''}</td>
            <td>${r.por_depfical??''}</td>
            <td>${r.id_almacen??''}</td>
            <td>${r.Activo==1?'Activo':'Inactivo'}</td>
        </tr>
    `).join('');
    }

    /* ================== CRUD ================== */
    function nuevoGrupo(){
        limpiar();
        tituloGrupo.innerHTML='<i class="fa fa-plus"></i> Nuevo Grupo';
        mdlGrupo.classList.add('show');
    }
    function editar(id){
        const r=dataCache.find(x=>x.id==id);
        if(!r) return;
        g_id.value=r.id;
        g_cve.value=r.cve_gpoart;
        g_des.value=r.des_gpoart;
        g_cont.value=r.por_depcont ?? '';
        g_fisc.value=r.por_depfical ?? '';
        g_alm.value=r.id_almacen ?? '';
        tituloGrupo.innerHTML='<i class="fa fa-edit"></i> Editar Grupo';
        mdlGrupo.classList.add('show');
    }
    function cerrarGrupo(){ mdlGrupo.classList.remove('show'); }
    function limpiar(){
        g_id.value='';
        g_cve.value='';
        g_des.value='';
        g_cont.value='';
        g_fisc.value='';
        g_alm.value='';
    }

    /* ================== GUARDAR (FIX DOUBLE / NULL) ================== */
    function guardarGrupo(){

        if(!g_cve.value.trim()){
            alert('La clave del grupo es obligatoria');
            return;
        }

        fetch(api,{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({
                action:'save',
                id: g_id.value || null,
                cve_gpoart: g_cve.value.trim(),
                des_gpoart: g_des.value.trim(),
                por_depcont: g_cont.value !== '' ? Number(g_cont.value) : null,
                por_depfical: g_fisc.value !== '' ? Number(g_fisc.value) : null,
                id_almacen: g_alm.value !== '' ? Number(g_alm.value) : null
            })
        })
            .then(r=>r.json())
            .then(resp=>{
                if(resp.success){
                    cerrarGrupo();
                    cargarDatos();
                }else{
                    alert(resp.error || 'No se pudo guardar el registro');
                }
            })
            .catch(()=>{
                alert('Error de comunicación con el servidor');
            });
    }

    /* ================== ESTATUS ================== */
    function desactivar(id){
        fetch(api,{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({action:'delete',id})
        }).then(()=>cargarDatos());
    }
    function restaurar(id){
        fetch(api,{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({action:'restore',id})
        }).then(()=>cargarDatos());
    }

    /* ================== IMPORTAR ================== */
    function mostrarImportar(){ mdlImport.classList.add('show'); }
    function cerrarImportar(){
        mdlImport.classList.remove('show');
        previewWrap.style.display='none';
        fileCsv.value='';
    }
    function descargarLayout(){
        const csv =
            "\uFEFFsep=;\n" +
            "Clave;Descripcion;Porc_Contable;Porc_Fiscal;Almacen\n";

        const blob = new Blob(
            [csv],
            { type:'text/csv;charset=utf-8;' }
        );

        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'layout_grupos_articulos.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    function previsualizar(){
        const f=fileCsv.files[0];
        if(!f) return;
        const r=new FileReader();
        r.onload=e=>{
            const lines=e.target.result.split(/\r?\n/).filter(x=>x.trim());
            const d=lines[0].includes(';')?';':',';
            csvHead.innerHTML='<tr>'+lines[0].split(d).map(h=>`<th>${h}</th>`).join('')+'</tr>';
            csvBody.innerHTML=lines.slice(1,6).map(l=>'<tr>'+l.split(d).map(c=>`<td>${c}</td>`).join('')+'</tr>').join('');
            previewWrap.style.display='block';
        };
        r.readAsText(f);
    }
    function importar(){
        const f=fileCsv.files[0];
        if(!f) return;
        const r=new FileReader();
        r.onload=e=>{
            const lines=e.target.result.split(/\r?\n/).filter(x=>x.trim());
            const d=lines[0].includes(';')?';':',';
            lines.slice(1).forEach(l=>{
                const c=l.split(d);
                fetch(api,{
                    method:'POST',
                    headers:{'Content-Type':'application/json'},
                    body:JSON.stringify({
                        action:'save',
                        cve_gpoart:c[0],
                        des_gpoart:c[1],
                        por_depcont:c[2]!==''?Number(c[2]):null,
                        por_depfical:c[3]!==''?Number(c[3]):null,
                        id_almacen:c[4]!==''?Number(c[4]):null
                    })
                });
            });
            cerrarImportar();
            cargarDatos();
        };
        r.readAsText(f);
    }

    /* ================== OTROS ================== */
    function toggleInactivos(){
        verInactivos = verInactivos ? 0 : 1;
        cargarDatos();
    }
    function exportarCSV(){
        window.location = `${api}?action=export`;
    }

    cargarDatos();
</script>
