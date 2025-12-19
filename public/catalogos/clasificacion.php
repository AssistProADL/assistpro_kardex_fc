<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>
<!-- ===== DATATABLES SERVERSIDE ===== -->
<link rel="stylesheet"
      href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<style>
    /* ================== MODAL BASE ================== */
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
        border-radius:12px;
        overflow:hidden;
        border:none;
        box-shadow:0 18px 35px rgba(0,0,0,.25);
    }

    /* ================== HEADER ================== */
    .ap-modal-header{
        background:#0d6efd;
        color:#fff;
        padding:14px 20px;
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
        background:transparent;
        border:none;
        color:#fff;
        font-size:22px;
        cursor:pointer;
    }

    /* ================== BODY ================== */
    .ap-modal-body{
        padding:20px;
        font-size:13px;
        color:#212529;
    }

    /* ================== LABELS ================== */
    .ap-modal-body label{
        font-size:13px;
        font-weight:600;
        color:#343a40;
        margin-bottom:6px;
        display:block;
    }

    /* ================== INPUTS ================== */
    .ap-modal-body input[type="text"],
    .ap-modal-body input[type="number"],
    .ap-modal-body input[type="file"]{
        width:100%;
        max-width:320px;
        padding:8px 10px;
        font-size:13px;
        border-radius:8px;
        border:1px solid #dee2e6;
        background:#fff;
        transition:border-color .15s, box-shadow .15s;
    }

    .ap-modal-body input:focus{
        border-color:#0d6efd;
        box-shadow:0 0 0 2px rgba(13,110,253,.15);
    }

    /* ================== COLUMNAS ESPERADAS ================== */
    .ap-import-cols{
        display:grid;
        grid-template-columns:repeat(6,1fr);
        gap:10px;
        margin:18px 0;
    }
    .ap-import-cols span{
        background:#f8f9fa;
        border:1px solid #e9ecef;
        border-radius:10px;
        padding:10px 6px;
        font-size:12px;
        font-weight:600;
        text-align:center;
        color:#495057;
    }

    /* ================== BOTONES ================== */
    .ap-import-actions,
    .ap-modal-footer{
        display:flex;
        gap:10px;
        margin-top:10px;
    }

    .ap-import-actions button,
    .ap-modal-footer button{
        padding:8px 14px;
        font-size:13px;
        border-radius:10px;
        border:1px solid #dee2e6;
        background:#f8f9fa;
        cursor:pointer;
        transition:background .15s, color .15s;
    }

    .ap-import-actions button.primary,
    .ap-modal-footer .btn-primary{
        background:#0d6efd;
        color:#fff;
        border:none;
    }

    .ap-import-actions button:hover,
    .ap-modal-footer button:hover{
        opacity:.9;
    }

    /* ================== PREVIEW ================== */
    .ap-grid{
        border:1px solid #e9ecef;
        border-radius:10px;
        overflow:auto;
    }
    .ap-grid table{
        width:100%;
        border-collapse:collapse;
        font-size:12px;
    }
    .ap-grid th,
    .ap-grid td{
        border:1px solid #e9ecef;
        padding:8px;
        text-align:center;
    }

    /* ================== FORM NUEVO / EDITAR ================== */
    #mdlNuevo .ap-form-grid{
        display:grid;
        grid-template-columns:repeat(3,1fr);
        gap:18px 24px;
    }

    #mdlNuevo .ap-form-grid > div{
        display:flex;
        flex-direction:column;
    }

    #mdlNuevo input[type="checkbox"]{
        width:16px;
        height:16px;
        accent-color:#0d6efd;
    }

    /* ================== FOOTER ================== */
    .ap-modal-footer{
        justify-content:flex-end;
        padding:16px 20px;
        background:#f8f9fa;
    }

    /* ================== QUITAR BORDES NEGROS ================== */
    .ap-modal button,
    .ap-modal button:focus,
    .ap-modal button:active{
        outline:none !important;
        box-shadow:none !important;
    }
    /* ======================================================
   ASSISTPRO – INPUTS SUAVES (NUEVO / EDITAR)
   ====================================================== */

    #mdlNuevo input[type="text"],
    #mdlNuevo input[type="number"]{
        background:#f8f9fa;                 /* fondo suave */
        border:1px solid #e1e5ea;           /* borde claro */
        border-radius:12px;                 /* esquinas redondeadas */
        padding:10px 12px;                  /* más aire */
        font-size:13px;
        color:#212529;
        transition:
                background .15s ease,
                border-color .15s ease,
                box-shadow .15s ease;
    }

    /* Placeholder más suave */
    #mdlNuevo input::placeholder{
        color:#adb5bd;
    }

    /* Focus AssistPro */
    #mdlNuevo input:focus{
        background:#fff;
        border-color:#0d6efd;
        box-shadow:0 0 0 3px rgba(13,110,253,.15);
    }

    /* Input de solo lectura (editar clave, si aplica) */
    #mdlNuevo input[readonly]{
        background:#eef1f4;
        border-color:#dee2e6;
        color:#6c757d;
    }
    /* ======================================================
       ASSISTPRO – FORMULARIO EN LISTA (NUEVO / EDITAR)
       ====================================================== */

    /* Forzar layout vertical */
    #mdlNuevo .ap-form-grid{
        display:flex !important;
        flex-direction:column !important;
        gap:14px !important;
    }

    /* Cada campo ocupa toda la fila */
    #mdlNuevo .ap-form-grid > div{
        width:100%;
    }

    /* Inputs ocupan todo el ancho */
    #mdlNuevo .ap-form-grid input[type="text"],
    #mdlNuevo .ap-form-grid input[type="number"]{
        width:100% !important;
        max-width:none !important;
    }

    /* Checkbox alineado como item de lista */
    #mdlNuevo .ap-modal-body > label{
        display:flex;
        align-items:center;
        gap:8px;
        margin-top:6px;
        font-weight:600;
    }

    /* Separación visual tipo lista */
    #mdlNuevo .ap-form-grid > div{
        padding-bottom:4px;
        border-bottom:1px solid #f1f3f5;
    }

    /* Último campo sin línea */
    #mdlNuevo .ap-form-grid > div:last-child{
        border-bottom:none;
    }
    /* Quitar línea inferior y aumentar espacio en cada campo (formularios) */
    #mdlNuevo .ap-form-grid > div{
        padding-bottom:12px !important;     /* un poco más de espacio vertical */
        border-bottom:none !important;      /* quitar la línea inferior */
    }

    /* Asegurar que el último campo no tenga línea (por si hay reglas previas) */
    #mdlNuevo .ap-form-grid > div:last-child{
        border-bottom:none !important;
    }

    /* Hacer los campos de los formularios un poco más grandes y consistentes */
    #mdlNuevo .ap-form-grid input,
    #mdlNuevo .ap-form-grid select,
    #mdlNuevo .ap-form-grid textarea,
    .ap-modal-body input,
    .ap-modal-body select,
    .ap-modal-body textarea {
        padding:12px 14px !important;       /* ligeramente más grande */
        font-size:14px !important;          /* tamaño de texto un poco mayor */
        border-radius:12px !important;
        background:#f8f9fa !important;
    }

    /* Aumentar ligeramente el ancho del modal para dar más aire */
    .ap-modal-content{
        width:1020px !important;
        max-width:96% !important;
    }
    /* Eliminar borde/outline negro y sombras en inputs del formulario/modal */
    .ap-modal input,
    .ap-modal select,
    .ap-modal textarea,
    #mdlNuevo input,
    #mdlNuevo select,
    #mdlNuevo textarea,
    input.form-control,
    input.form-control:focus,
    input.form-control:hover,
    input.form-control:active {
        border: 1px solid #e6eaef !important; /* borde sutil */
        box-shadow: none !important;
        -webkit-box-shadow: none !important;
        -moz-box-shadow: none !important;
        outline: none !important;
        transition: none !important;
        background: #f8f9fa !important;
    }

    /* Asegurar que el foco no muestre borde negro ni shadow */
    .ap-modal input:focus,
    #mdlNuevo input:focus,
    .ap-modal select:focus,
    #mdlNuevo select:focus,
    .ap-modal textarea:focus,
    #mdlNuevo textarea:focus,
    input.form-control:focus {
        border-color: #0d6efd !important; /* indicador sutil */
        box-shadow: 0 0 0 4px rgba(13,110,253,0.06) !important; /* muy tenue */
        outline: none !important;
    }

    /* Quitar cualquier outline por focus-visible */
    :focus-visible { outline: none !important; box-shadow: none !important; }

    /* Evitar cambios al pasar el cursor */
    .ap-modal input:hover,
    #mdlNuevo input:hover,
    input.form-control:hover {
        border-color: #e6eaef !important;
        box-shadow: none !important;
    }

    /* --- AÑADIR dentro del <style> (al final del bloque style) --- */

    .ap-paginator{
        display:flex;
        gap:6px;
        align-items:center;
        justify-content:center;
        margin-top:10px;
        flex-wrap:wrap;
    }
    .ap-paginator .page-btn{
        padding:6px 10px;
        border-radius:8px;
        border:1px solid #e6eaef;
        background:#f8f9fa;
        color:#212529;
        cursor:pointer;
        font-size:13px;
    }
    .ap-paginator .page-btn:hover{ background:#e9eef7; }
    .ap-paginator .page-btn.active{
        background:#0d6efd;
        color:#fff;
        border-color:transparent;
    }
    .ap-paginator .page-btn.disabled{
        opacity:.45;
        cursor:default;
    }
    .ap-paginator .page-info{
        font-size:13px;
        color:#6c757d;
        margin-left:8px;
    }

    /* fin del bloque paginador */


</style>






<div class="container-fluid" style="padding:10px">

    <h4 class="text-primary mb-3">
        <i class="fa fa-tags"></i> Clasificación de Artículos
    </h4>

    <div class="d-flex mb-2 gap-2 flex-wrap">
        <input type="text" id="txtBuscar" class="form-control form-control-sm"
               placeholder="Buscar..." style="max-width:200px">

        <button class="btn btn-primary btn-sm" onclick="buscar()">
            <i class="fa fa-search"></i> Buscar
        </button>

        <button class="btn btn-success btn-sm" onclick="nuevoRegistro()">
            <i class="fa fa-plus"></i> Nuevo
        </button>

        <button class="btn btn-secondary btn-sm" onclick="toggleInactivos()">
            <i class="fa fa-eye"></i> Ver Inactivos
        </button>

        <button class="btn btn-info btn-sm" onclick="exportarCSV()">
            <i class="fa fa-file-csv"></i> Exportar
        </button>

        <button class="btn btn-warning btn-sm" onclick="mostrarModalImportar()">
            <i class="fa fa-upload"></i> Importar
        </button>
    </div>

    <div class="table-responsive" style="max-height:520px">
        <table class="table table-sm table-hover text-center align-middle">
            <thead class="table-primary">
            <tr>
                <th style="width:120px">Opciones</th>
                <th>Clave</th>
                <th>Grupo</th>
                <th>Descripción</th>
                <th>Almacén</th>
                <th>Múltiplo</th>
                <th>Incluye</th>
                <th>Estatus</th>
            </tr>
            </thead>
            <tbody id="gridDatos"></tbody>
        </table>

</table>
</div>
<!-- contenedor del paginador -->
<div id="paginador" class="ap-paginator" aria-label="Paginador"></div>


    </div>
</div>

<!-- ================== MODAL IMPORTAR ================== -->
<div class="ap-modal" id="mdlImport">
    <div class="ap-modal-content">
        <div class="ap-modal-header">
            <h3><i class="fa fa-upload"></i> Importar Clasificación</h3>
            <button onclick="cerrarModalImportar()">×</button>
        </div>

        <div class="ap-modal-body">
            <label>Archivo CSV</label>
            <input type="file" id="fileCsvImport" accept=".csv">


            <div class="ap-import-cols">
                <span>Clave</span>
                <span>Grupo</span>
                <span>Descripción</span>
                <span>Almacén</span>
                <span>Múltiplo</span>
                <span>Incluye</span>
            </div>

            <div class="ap-import-actions">
                <button onclick="descargarLayoutImport()">Descargar layout</button>
                <button class="primary" onclick="previsualizarCsvImport()">Previsualizar</button>
                <button onclick="cerrarModalImportar()">Cancelar</button>
            </div>

            <div id="csvPreviewWrapImport" style="display:none">
                <div class="ap-grid" style="max-height:260px">
                    <table>
                        <thead id="csvHeadImport"></thead>
                        <tbody id="csvBodyImport"></tbody>
                    </table>
                </div>
                <div style="text-align:right;margin-top:8px">
                    <button class="primary" onclick="importarCsvImport()">Importar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================== MODAL NUEVO / EDITAR ================== -->
<div class="ap-modal" id="mdlNuevo">
    <div class="ap-modal-content">
        <div class="ap-modal-header">
            <h3 id="tituloModalNuevo"><i class="fa fa-plus"></i> Clasificación</h3>
            <button onclick="cerrarModalNuevo()">×</button>
        </div>

        <div class="ap-modal-body">
            <input type="hidden" id="edit_id">

            <div class="ap-form-grid">
                <div><label>Clave *</label><input id="n_cve_sgpoart"></div>
                <div><label>Grupo</label><input id="n_cve_gpoart"></div>
                <div><label>Almacén</label><input id="n_id_almacen"></div>
            </div>

            <div class="ap-form-grid">
                <div style="grid-column:span 2">
                    <label>Descripción *</label><input id="n_des_sgpoart">
                </div>
                <div>
                    <label>Múltiplo</label><input type="number" id="n_Num_Multiplo" value="0">
                </div>
            </div>

            <label>
                <input type="checkbox" id="n_Ban_Incluye"> Incluye
            </label>
        </div>

        <div class="ap-modal-footer">
            <button onclick="cerrarModalNuevo()">Cancelar</button>
            <button class="btn btn-primary btn-sm" onclick="guardarNuevo()">Guardar</button>
        </div>
    </div>
</div>

<!-- ================== MODAL ALERTA ================== -->
<div class="ap-modal" id="mdlAlert">
    <div class="ap-modal-content" style="max-width:420px">
        <div class="ap-modal-header" id="apAlertHeader">
            <h3 id="apAlertTitle">Aviso</h3>
            <button onclick="cerrarApAlert()">×</button>
        </div>
        <div class="ap-modal-body">
            <p id="apAlertMsg" style="margin:0"></p>
        </div>
        <div class="ap-modal-footer">
            <button class="btn btn-primary btn-sm" onclick="cerrarApAlert()">Aceptar</button>
        </div>
    </div>
</div>

<script>
    const api = '../api/clasificacion.php';
    const apiAlmacenes = '../api/filtros_almacenes.php';

    let dataCache = [], verInactivos = 0;
    let paginaActual = 1;
    const filasPorPagina = 25;

    /* ================== ALERTAS ================== */
    function apAlert(tipo, msg){
        const h = document.getElementById('apAlertHeader');
        const t = document.getElementById('apAlertTitle');
        const m = document.getElementById('apAlertMsg');
        h.className = 'ap-modal-header';
        if(tipo === 'success'){ h.classList.add('bg-success'); t.innerText = 'Éxito'; }
        else if(tipo === 'error'){ h.classList.add('bg-danger'); t.innerText = 'Error'; }
        else if(tipo === 'warning'){ h.classList.add('bg-warning'); t.innerText = 'Atención'; }
        else{ h.classList.add('bg-info'); t.innerText = 'Aviso'; }
        m.innerText = msg;
        mdlAlert.classList.add('show');
    }
    function cerrarApAlert(){ mdlAlert.classList.remove('show'); }

    /* ================== IMPORTAR ================== */
    function mostrarModalImportar(){ mdlImport.classList.add('show'); }
    function cerrarModalImportar(){
        mdlImport.classList.remove('show');
        fileCsvImport.value = '';
        csvPreviewWrapImport.style.display = 'none';
    }

    function descargarLayoutImport(){
        let csv =
            "sep=;\r\n" +
            "Clave Clasificación;Grupo de Artículo;Descripción;Almacén;Múltiplo;Incluye\r\n";

        const utf16 = new Uint16Array(Array.from(csv).map(c => c.charCodeAt(0)));
        const blob = new Blob([utf16], { type: 'text/csv;charset=utf-16le;' });

        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'layout_clasificacion_articulos.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    function previsualizarCsvImport(){
        const f = fileCsvImport.files[0];
        if(!f) return;

        const r = new FileReader();
        r.onload = e => {
            const l = e.target.result.split(/\r?\n/).filter(x => x.trim());
            const d = l[0].includes(';') ? ';' : ',';
            csvHeadImport.innerHTML =
                '<tr>' + l[0].split(d).map(h => `<th>${h}</th>`).join('') + '</tr>';
            csvBodyImport.innerHTML =
                l.slice(1,6).map(r =>
                    '<tr>' + r.split(d).map(c => `<td>${c}</td>`).join('') + '</tr>'
                ).join('');
            csvPreviewWrapImport.style.display = 'block';
        };
        r.readAsText(f);
    }

    function importarCsvImport(){
        const f = fileCsvImport.files[0];
        if(!f) return apAlert('warning','Selecciona un archivo CSV');

        const r = new FileReader();
        r.onload = e => {
            const lines = e.target.result.split(/\r?\n/).filter(l => l.trim());
            const d = lines[0].includes(';') ? ';' : ',';
            const data = lines.slice(1);
            const reqs = [];

            data.forEach(l => {
                const c = l.split(d);
                if(!c[0] || !c[2]) return;
                reqs.push(fetch(api,{
                    method:'POST',
                    headers:{'Content-Type':'application/json'},
                    body:JSON.stringify({
                        action:'create',
                        cve_sgpoart:c[0],
                        cve_gpoart:c[1]||null,
                        des_sgpoart:c[2],
                        id_almacen:c[3]||null,
                        Num_Multiplo:c[4]||0,
                        Ban_Incluye:c[5]||0
                    })
                }));
            });

            Promise.all(reqs)
                .then(()=>{
                    cerrarModalImportar();
                    cargarDatos();
                    apAlert('success','Importación realizada correctamente');
                })
                .catch(()=> apAlert('error','Error durante la importación'));
        };
        r.readAsText(f);
    }

    /* ================== NUEVO / EDITAR ================== */
    function nuevoRegistro(){
        limpiarNuevo();
        edit_id.value = '';
        cargarAlmacenes(); // 👈 AQUÍ se consume el API de almacenes
        tituloModalNuevo.innerHTML = '<i class="fa fa-plus"></i> Nueva Clasificación';
        mdlNuevo.classList.add('show');
    }

    function editar(id){
        const r = dataCache.find(x => x.id == id);
        if(!r) return;

        edit_id.value = r.id;
        n_cve_sgpoart.value = r.cve_sgpoart;
        n_cve_gpoart.value = r.cve_gpoart;
        n_des_sgpoart.value = r.des_sgpoart;
        n_Num_Multiplo.value = r.Num_Multiplo;
        n_Ban_Incluye.checked = r.Ban_Incluye == 1;

        cargarAlmacenes(r.id_almacen); // 👈 AQUÍ se consume el API de almacenes

        tituloModalNuevo.innerHTML = '<i class="fa fa-edit"></i> Editar Clasificación';
        mdlNuevo.classList.add('show');
    }

    function cerrarModalNuevo(){ mdlNuevo.classList.remove('show'); }

    function limpiarNuevo(){
        n_cve_sgpoart.value = '';
        n_cve_gpoart.value = '';
        n_des_sgpoart.value = '';
        n_id_almacen.value = '';
        n_Num_Multiplo.value = 0;
        n_Ban_Incluye.checked = false;
    }

    function guardarNuevo(){
        if(!n_cve_sgpoart.value || !n_des_sgpoart.value)
            return apAlert('warning','Clave y descripción son obligatorias');

        const action = edit_id.value ? 'update' : 'create';

        fetch(api,{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({
                action,
                id: edit_id.value,
                cve_sgpoart: n_cve_sgpoart.value,
                cve_gpoart: n_cve_gpoart.value,
                des_sgpoart: n_des_sgpoart.value,
                id_almacen: n_id_almacen.value,
                Num_Multiplo: n_Num_Multiplo.value,
                Ban_Incluye: n_Ban_Incluye.checked ? 1 : 0
            })
        })
            .then(r => r.json())
            .then(resp => {
                if(resp.success){
                    apAlert('success', edit_id.value
                        ? 'Registro actualizado correctamente'
                        : 'Registro agregado correctamente');
                    cerrarModalNuevo();
                    cargarDatos();
                } else {
                    apAlert('error', resp.error || 'No se pudo guardar');
                }
            })
            .catch(()=> apAlert('error','Error de comunicación con el servidor'));
    }

    /* ================== GRID ================== */
    function cargarDatos(){
        fetch(`${api}?inactivos=${verInactivos}`)
            .then(r => r.json())
            .then(d => {
                dataCache = d.data || [];
                renderGrid(dataCache);
            });
    }

    function renderGrid(d){
        const inicio = (paginaActual - 1) * filasPorPagina;
        const fin = inicio + filasPorPagina;
        const datosPagina = d.slice(inicio, fin);

        gridDatos.innerHTML = datosPagina.map(r => `
            <tr>
                <td>
                    <button class="btn btn-warning btn-sm" onclick="editar(${r.id})">
                        <i class="fa fa-edit"></i>
                    </button>
                    ${r.Activo==1
            ? `<button class="btn btn-danger btn-sm" onclick="desactivar(${r.id})">
                               <i class="fa fa-trash"></i>
                           </button>`
            : `<button class="btn btn-success btn-sm" onclick="restaurar(${r.id})">
                               <i class="fa fa-undo"></i>
                           </button>`}
                </td>
                <td>${r.cve_sgpoart}</td>
                <td>${r.cve_gpoart||''}</td>
                <td>${r.des_sgpoart||''}</td>
                <td>${r.id_almacen||''}</td>
                <td>${r.Num_Multiplo}</td>
                <td>${r.Ban_Incluye==1?'Sí':'No'}</td>
                <td>${r.Activo==1?'Activo':'Inactivo'}</td>
            </tr>
        `).join('');

        renderPaginador(d.length);
    }

    function buscar(){
        const q = txtBuscar.value.toLowerCase();
        renderGrid(dataCache.filter(r => r.cve_sgpoart.toLowerCase().includes(q)));
    }
    function toggleInactivos(){ verInactivos = verInactivos ? 0 : 1; cargarDatos(); }

    function exportarCSV(){ window.location = `${api}?action=export`; }

    function desactivar(id){
        fetch(api,{method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({action:'delete',id})})
            .then(()=>{ cargarDatos(); apAlert('success','Registro desactivado'); });
    }

    function restaurar(id){
        fetch(api,{method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({action:'restore',id})})
            .then(()=>{ cargarDatos(); apAlert('success','Registro recuperado'); });
    }

    /* ================== ALMACENES ================== */
    function cargarAlmacenes(selectedId = null){
        const select = document.getElementById('n_id_almacen');
        if (!select) return;

        fetch(apiAlmacenes)
            .then(r => r.json())
            .then(data => {
                if (!Array.isArray(data)) {
                    select.innerHTML = '<option value="">Sin almacenes disponibles</option>';
                    return;
                }

                select.innerHTML =
                    '<option value="">Seleccione un almacén</option>' +
                    data.map(a =>
                        `<option value="${a.id}" ${
                            String(selectedId) === String(a.id) ? 'selected' : ''
                        }>${a.nombre}</option>`
                    ).join('');
            })
            .catch(() => {
                select.innerHTML = '<option value="">Error al cargar almacenes</option>';
            });
    }

    cargarDatos();
</script>

