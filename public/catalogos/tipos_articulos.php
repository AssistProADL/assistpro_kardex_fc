<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Tipo de Artículos</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        /* =========================================================
           ASSISTPRO – BASE
        ========================================================= */
        .container-fluid{ padding:14px; }
        h4{ font-weight:600; }

        /* =========================================================
           TOOLBAR
        ========================================================= */
        .ap-toolbar{
            display:flex; gap:8px; flex-wrap:wrap; margin-bottom:10px;
        }
        .ap-toolbar input{
            border-radius:8px; border:1px solid #dee2e6; padding:6px 10px;
        }

        /* =========================================================
           GRID
        ========================================================= */
        .ap-card{
            background:#fff;
            border-radius:12px;
            box-shadow:0 6px 20px rgba(0,0,0,.06);
            padding:10px;
        }
        .ap-table{
            width:100%;
            border-collapse:collapse;
            font-size:13px;
        }
        .ap-table thead{
            background:#f1f3f5;
        }
        .ap-table th,.ap-table td{
            padding:8px;
            border-bottom:1px solid #e9ecef;
            text-align:center;
        }
        .ap-table tbody tr:hover{ background:#f8f9fa; }

        /* =========================================================
           BUTTONS
        ========================================================= */
        .btn{
            border:none; border-radius:8px;
            padding:6px 12px; font-size:13px;
            cursor:pointer;
        }
        .btn-primary{ background:#0d6efd; color:#fff; }
        .btn-success{ background:#198754; color:#fff; }
        .btn-warning{ background:#ffc107; }
        .btn-danger{ background:#dc3545; color:#fff; }
        .btn-secondary{ background:#6c757d; color:#fff; }
        .btn-info{ background:#0dcaf0; }
        .btn-sm{ padding:5px 10px; font-size:12px; }

        /* =========================================================
           MODAL BASE
        ========================================================= */
        .ap-modal{
            position:fixed; inset:0;
            background:rgba(0,0,0,.55);
            display:none; align-items:center; justify-content:center;
            z-index:9999;
        }
        .ap-modal.show{ display:flex; }

        .ap-modal-content{
            background:#fff;
            width:760px; max-width:95%;
            border-radius:14px;
            box-shadow:0 18px 35px rgba(0,0,0,.25);
            overflow:hidden;
        }

        /* HEADER */
        .ap-modal-header{
            background:#0d6efd; color:#fff;
            padding:14px 18px;
            display:flex; justify-content:space-between; align-items:center;
        }
        .ap-modal-header h3{
            margin:0; font-size:16px; font-weight:600;
        }
        .ap-modal-header button{
            background:none; border:none; color:#fff; font-size:22px;
        }

        /* BODY */
        .ap-modal-body{ padding:18px; font-size:13px; }

        /* FOOTER */
        .ap-modal-footer{
            padding:14px 18px;
            display:flex; justify-content:flex-end; gap:10px;
            background:#f8f9fa;
        }

        /* =========================================================
           FORM (NUEVO / EDITAR) – ESTILO ASSISTPRO
        ========================================================= */
        .ap-form-list{
            display:grid;
            grid-template-columns:repeat(2,1fr);
            gap:16px 24px;
        }
        .ap-form-list > div{
            display:flex; flex-direction:column;
        }
        .ap-form-list > div.full{
            grid-column:1 / -1;
        }
        .ap-form-list label{
            font-weight:600; margin-bottom:4px;
        }
        .ap-form-list input{
            border-radius:12px;
            border:1px solid #e1e5ea;
            padding:10px 12px;
            background:#f8f9fa;
        }
        .ap-form-list input:focus{
            outline:none;
            background:#fff;
            border-color:#0d6efd;
            box-shadow:0 0 0 3px rgba(13,110,253,.15);
        }

        /* =========================================================
           IMPORTADOR
        ========================================================= */
        .ap-import-cols{
            display:grid;
            grid-template-columns:repeat(4,1fr);
            gap:8px; margin:12px 0;
        }
        .ap-import-cols span{
            background:#f1f3f5;
            padding:8px;
            border-radius:8px;
            font-size:12px;
            font-weight:600;
            text-align:center;
        }
        #previewBox{
            margin-top:12px;
        }
        #previewBox table{
            width:100%;
            border-collapse:collapse;
        }
        #previewBox th,#previewBox td{
            border:1px solid #dee2e6;
            padding:6px;
            font-size:12px;
        }
        #previewBox .ap-table{
            max-height:260px;
            overflow:auto;
        }

        /* quitar outline negro */
        button:focus,button:active{ outline:none; box-shadow:none; }
    </style>
</head>

<body>

<div class="container-fluid">
    <h4 class="text-primary"><i class="fa fa-tag"></i> Tipo de Artículos</h4>

    <div class="ap-toolbar">
        <input id="txtBuscar" placeholder="Buscar...">
        <button class="btn btn-primary btn-sm" onclick="buscar()">Buscar</button>
        <button class="btn btn-success btn-sm" onclick="abrirNuevo()">Nuevo</button>
        <button id="btnInactivos" class="btn btn-success btn-sm" onclick="toggleInactivos()">
            <i class="fa fa-eye"></i> Inactivos
        </button>
        <button class="btn btn-warning btn-sm" onclick="abrirImportar()">Importar</button>
        <button class="btn btn-info btn-sm" onclick="exportar()">Exportar</button>
    </div>

    <div class="ap-card">
        <table class="ap-table">
            <thead>
            <tr>
                <th>Acciones</th>
                <th>Clave</th>
                <th>Descripción</th>
                <th>Grupo</th>
                <th>Almacén</th>
                <th>Estatus</th>
            </tr>
            </thead>
            <tbody id="grid"></tbody>
        </table>
    </div>
</div>

<!-- ================= MODAL NUEVO / EDITAR ================= -->
<div class="ap-modal" id="mdlNuevo">
    <div class="ap-modal-content">
        <div class="ap-modal-header">
            <h3 id="mdlTitulo">Nuevo Tipo de Artículo</h3>
            <button onclick="cerrarNuevo()">×</button>
        </div>
        <div class="ap-modal-body">
            <input type="hidden" id="id">
            <div class="ap-form-list">
                <div><label>Clave *</label><input id="cve"></div>
                <div><label>Grupo</label><input id="grupo"></div>
                <div class="full"><label>Descripción *</label><input id="desc"></div>
                <div><label>Almacén</label><input id="almacen"></div>
            </div>
        </div>
        <div class="ap-modal-footer">
            <button class="btn btn-secondary" onclick="cerrarNuevo()">Cancelar</button>
            <button class="btn btn-primary" onclick="guardar()">Guardar</button>
        </div>
    </div>
</div>

<!-- ================= MODAL IMPORTAR ================= -->
<div class="ap-modal" id="mdlImport">
    <div class="ap-modal-content">
        <div class="ap-modal-header">
            <h3><i class="fa fa-upload"></i> Importar Tipo de Artículos</h3>
            <button onclick="cerrarImportar()">×</button>
        </div>
        <div class="ap-modal-body">
            <label>Archivo CSV</label>
            <input type="file" id="fileCsv" accept=".csv">

            <div class="ap-import-cols">
                <span>Clave</span>
                <span>Grupo</span>
                <span>Descripción</span>
                <span>Almacén</span>
            </div>

            <div style="margin-top:10px">
                <button class="btn btn-secondary btn-sm" onclick="layout()">Layout</button>
                <button class="btn btn-primary btn-sm" onclick="previsualizar()">Previsualizar</button>
            </div>

            <div id="previewBox" style="display:none">
                <table class="ap-table">
                    <thead id="prevHead"></thead>
                    <tbody id="prevBody"></tbody>
                </table>
                <div style="text-align:right;margin-top:10px">
                    <button class="btn btn-success btn-sm" onclick="importar()">Importar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const api = '../api/articulos.php';
    let dataCache=[], inactivos=0;

    /* ================= CARGA ================= */
    function cargar(){
        fetch(`${api}?action=list&inactivos=${inactivos}`)
            .then(r=>r.json())
            .then(j=>{
                dataCache = j.data || [];
                render(dataCache);
            })
            .catch(()=>alert('No se pudo conectar con el API'));
    }
    function render(arr){
        grid.innerHTML = arr.map(r=>`
        <tr>
            <td>
                <button class="btn btn-warning btn-sm" onclick="editar(${r.id})"><i class="fa fa-edit"></i></button>
                ${r.Activo==1
            ? `<button class="btn btn-danger btn-sm" onclick="desactivar(${r.id})"><i class="fa fa-trash"></i></button>`
            : `<button class="btn btn-success btn-sm" onclick="restaurar(${r.id})"><i class="fa fa-undo"></i></button>`
        }
            </td>
            <td>${r.cve_ssgpoart}</td>
            <td>${r.des_ssgpoart}</td>
            <td>${r.cve_sgpoart||''}</td>
            <td>${r.id_almacen||''}</td>
            <td>${r.Activo==1?'Activo':'Inactivo'}</td>
        </tr>
    `).join('');
    }
    function buscar(){
        const q=txtBuscar.value.toLowerCase();
        render(dataCache.filter(x=>x.des_ssgpoart.toLowerCase().includes(q)));
    }

    /* ================= ACTIVOS / INACTIVOS ================= */
    function toggleInactivos(){
        inactivos = inactivos ? 0 : 1;
        if(inactivos){
            btnInactivos.className='btn btn-secondary btn-sm';
            btnInactivos.innerHTML='<i class="fa fa-eye"></i> Activos';
        }else{
            btnInactivos.className='btn btn-success btn-sm';
            btnInactivos.innerHTML='<i class="fa fa-eye"></i> Inactivos';
        }
        cargar();
    }

    /* ================= NUEVO / EDITAR ================= */
    function abrirNuevo(){
        id.value=''; cve.value=''; grupo.value=''; desc.value=''; almacen.value='';
        mdlTitulo.innerText='Nuevo Tipo de Artículo';
        mdlNuevo.classList.add('show');
    }
    function editar(idr){
        const r=dataCache.find(x=>x.id==idr);
        id.value=r.id; cve.value=r.cve_ssgpoart; grupo.value=r.cve_sgpoart;
        desc.value=r.des_ssgpoart; almacen.value=r.id_almacen;
        mdlTitulo.innerText='Editar Tipo de Artículo';
        mdlNuevo.classList.add('show');
    }
    function cerrarNuevo(){ mdlNuevo.classList.remove('show'); }
    function guardar(){

        if(!cve.value || !desc.value){
            apAlert('warning','Clave y Descripción son obligatorias');
            return;
        }

        const esEdicion = id.value && id.value !== '';

        const idAlmacen = almacen.value && !isNaN(almacen.value)
            ? parseInt(almacen.value, 10)
            : null;

        const payload = {
            action: esEdicion ? 'update' : 'create',
            cve_ssgpoart: cve.value.trim(),
            cve_sgpoart: grupo.value && !isNaN(grupo.value)
                ? parseInt(grupo.value,10)
                : null,
            des_ssgpoart: desc.value.trim(),
            id_almacen: idAlmacen
        };

        if(esEdicion){
            payload.id = id.value;
        }

        fetch(api,{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify(payload)
        })
            .then(r => r.json())
            .then(resp => {
                if(resp.success){
                    cerrarNuevo();
                    cargar();
                    apAlert(
                        'success',
                        esEdicion
                            ? 'Registro actualizado correctamente'
                            : 'Registro agregado correctamente'
                    );
                }else{
                    apAlert('error', resp.error || 'No se pudo guardar');
                }
            })
            .catch(()=>{
                apAlert('error','Error de comunicación con el API');
            });
    }


    function desactivar(id){
        fetch(api,{method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({action:'delete',id})})
            .then(()=>cargar());
    }
    function restaurar(id){
        fetch(api,{method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({action:'restore',id})})
            .then(()=>cargar());
    }

    /* ================= IMPORTAR ================= */
    function abrirImportar(){
        mdlImport.classList.add('show');
    }

    function cerrarImportar(){
        mdlImport.classList.remove('show');
        previewBox.style.display='none';
    }

    /* ===================== LAYOUT CSV ===================== */
    function layout(){
        // BOM UTF-8 + delimitador COMA (Excel MX / ES)
        const csv =
            "\uFEFFClave,Grupo,Descripción,Almacén\r\n";

        const blob = new Blob(
            [csv],
            { type: 'text/csv;charset=utf-8;' }
        );

        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'layout_tipo_articulos.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    /* ===================== PREVISUALIZAR ===================== */
    function previsualizar(){
        const f = fileCsv.files[0];
        if(!f) return;

        const r = new FileReader();
        r.onload = e => {

            const lines = e.target.result
                .split(/\r?\n/)
                .filter(l => l.trim() !== '');

            // Detectar delimitador automáticamente
            const d = lines[0].includes(';') ? ';' : ',';

            // Encabezados
            const headers = lines[0].split(d);
            prevHead.innerHTML =
                '<tr>' + headers.map(h => `<th>${h}</th>`).join('') + '</tr>';

            // Primeras 5 filas (preview limpio)
            prevBody.innerHTML =
                lines.slice(1,6).map(row => {
                    const cols = row.split(d);
                    return '<tr>' + cols.map(v => `<td>${v}</td>`).join('') + '</tr>';
                }).join('');

            previewBox.style.display = 'block';
        };

        r.readAsText(f,'UTF-8');
    }

    /* ===================== IMPORTAR ===================== */
    function importar(){
        const f = fileCsv.files[0];
        if(!f){
            apAlert('warning','Selecciona un archivo CSV');
            return;
        }

        const reader = new FileReader();

        reader.onload = e => {
            const lines = e.target.result.split(/\r?\n/).filter(l => l.trim());
            if(lines.length < 2){
                apAlert('warning','El archivo no contiene datos');
                return;
            }

            const delimiter = lines[0].includes(';') ? ';' : ',';
            const rows = lines.slice(1);

            const reqs = [];

            rows.forEach(row => {
                const c = row.split(delimiter);

                if(!c[0] || !c[2]) return; // Clave y descripción obligatorias

                reqs.push(
                    fetch(api,{
                        method:'POST',
                        headers:{'Content-Type':'application/json'},
                        body: JSON.stringify({
                            action:'create',
                            cve_ssgpoart: c[0].trim(),
                            cve_sgpoart: c[1] && !isNaN(c[1]) ? parseInt(c[1],10) : null,
                            des_ssgpoart: c[2].trim(),
                            id_almacen: c[3] && !isNaN(c[3]) ? parseInt(c[3],10) : null
                        })
                    })
                );
            });

            Promise.all(reqs)
                .then(() => {
                    cerrarImportar();
                    verInactivos = 0;   // 🔥 FORZAR ACTIVOS
                    cargar();
                    apAlert('success','Importación realizada correctamente');
                })
                .catch(() => {
                    apAlert('error','Error durante la importación');
                });
        };

        reader.readAsText(f,'UTF-8');
    }



    /* ===================== EXPORTAR ===================== */
    function exportar(){
        fetch(api + '?action=list&inactivos=0')
            .then(r => r.json())
            .then(resp => {
                if(!resp.success){
                    apAlert('error','No se pudo exportar');
                    return;
                }

                // BOM UTF-8 + DELIMITADOR COMA (CONSISTENTE)
                let csv = "\uFEFFClave,Grupo,Descripción,Almacén\r\n";

                resp.data.forEach(r => {
                    csv +=
                        `${r.cve_ssgpoart ?? ''},` +
                        `${r.cve_sgpoart ?? ''},` +
                        `"${(r.des_ssgpoart ?? '').replace(/"/g,'""')}",` +
                        `${r.id_almacen ?? ''}\r\n`;
                });

                const blob = new Blob(
                    [csv],
                    { type: 'text/csv;charset=utf-8;' }
                );

                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'tipo_articulos_export.csv';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            })
            .catch(() => apAlert('error','Error al generar el archivo'));
    }


    cargar();

</script>

</body>
</html>
