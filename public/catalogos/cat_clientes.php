<?php
require_once __DIR__ . '/../bi/_menu_global.php';
?>

    <style>
        .ap-container{padding:12px;font-size:12px}
        .ap-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
        .ap-title{font-size:18px;font-weight:600;color:#0b5ed7}
        .ap-toolbar button{margin-left:4px;padding:4px 10px;font-size:12px}
        .ap-grid{border:1px solid #dcdcdc;height:520px;overflow:auto}
        .ap-grid table{width:100%;border-collapse:collapse}
        .ap-grid th{position:sticky;top:0;background:#f4f6fb;padding:6px;border-bottom:1px solid #ccc}
        .ap-grid td{padding:5px;border-bottom:1px solid #eee;white-space:nowrap}
        .ap-actions i{cursor:pointer;margin-right:6px;color:#0b5ed7}
        .ap-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999}
        .ap-modal-content{background:#fff;width:720px;margin:4% auto;padding:15px}
        .ap-modal-content h3{margin:0 0 10px 0}
        .ap-modal-content input{width:100%;padding:5px;margin-bottom:6px;font-size:12px}
        .ap-import-cols span{display:inline-block;background:#eef2ff;color:#1e3a8a;padding:4px 8px;border-radius:12px;font-size:11px;margin:2px}
        button.primary{background:#0b5ed7;color:#fff;border:none;padding:6px 12px}
    </style>

    <div class="ap-container">

        <div class="ap-header">
            <div class="ap-title"><i class="fa fa-users"></i> Catálogo de Clientes</div>
            <div class="ap-toolbar">
                <button onclick="nuevoCliente()">➕ Agregar</button>
                <button onclick="exportarDatos()">⬇ Exportar</button>
                <button onclick="abrirImport()">⬆ Importar</button>
                <button onclick="toggleInactivos()">👁 Ver inactivos</button>
            </div>
        </div>

        <div class="ap-grid">
            <table>
                <thead>
                <tr>
                    <th>Acciones</th>
                    <th>Clave</th>
                    <th>Razón Social</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Crédito</th>
                </tr>
                </thead>
                <tbody id="tbodyClientes"></tbody>
            </table>
        </div>

    </div>

    <!-- MODAL CLIENTE -->
    <div class="ap-modal" id="mdlCliente">
        <div class="ap-modal-content">
            <h3>Cliente</h3>
            <input type="hidden" id="id_cliente">
            <input id="Cve_Clte" placeholder="Clave">
            <input id="RazonSocial" placeholder="Razón Social">
            <input id="RazonComercial" placeholder="Razón Comercial">
            <input id="RFC" placeholder="RFC">
            <input id="Ciudad" placeholder="Ciudad">
            <input id="Estado" placeholder="Estado">
            <input id="Telefono1" placeholder="Teléfono" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
            <input id="email_cliente" placeholder="Email">
            <input id="credito" placeholder="Crédito">
            <div style="text-align:right">
                <button class="primary" onclick="guardarCliente()">Guardar</button>
                <button onclick="cerrarModal('mdlCliente')">Cancelar</button>
            </div>
        </div>
    </div>

    <!-- MODAL IMPORT -->
    <div class="ap-modal" id="mdlImport">
        <div class="ap-modal-content">
            <h3>Importar clientes</h3>

            <input type="file" id="fileCsv" accept=".csv">
            <small>CSV UTF-8 · coma · encabezado obligatorio</small>

            <div class="ap-import-cols" style="margin:10px 0">
                <span>Cve_Clte</span><span>RazonSocial</span><span>RazonComercial</span>
                <span>RFC</span><span>Ciudad</span><span>Estado</span>
                <span>Telefono1</span><span>email_cliente</span><span>credito</span>
            </div>

            <button onclick="descargarLayout()">Descargar layout CSV</button>
            <button class="primary" onclick="previsualizarCsv()">Previsualizar</button>

            <div id="csvPreviewWrap" style="display:none;margin-top:10px">
                <div class="ap-grid" style="max-height:260px">
                    <table>
                        <thead id="csvHead"></thead>
                        <tbody id="csvBody"></tbody>
                    </table>
                </div>
                <div style="text-align:right;margin-top:8px">
                    <button class="primary" onclick="importarCsv()">Importar</button>
                    <button onclick="cerrarModal('mdlImport')">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const API = '../api/clientes.php';
        let verInactivos = false;

        /* ===== LISTAR ===== */
        function cargarClientes(){
            fetch(API+'?action=list&inactivos='+(verInactivos?1:0))
                .then(r=>r.json())
                .then(rows=>{
                    let html='';
                    rows.forEach(c=>{
                        html+=`
        <tr>
          <td class="ap-actions">
            ${verInactivos
                            ? `<i class="fa fa-undo" onclick="recuperar(${c.id_cliente})"></i>`
                            : `<i class="fa fa-edit" onclick="editarCliente(${c.id_cliente})"></i>
                 <i class="fa fa-trash" onclick="eliminarCliente(${c.id_cliente})"></i>`}
          </td>
          <td>${c.Cve_Clte||''}</td>
          <td>${c.RazonSocial||''}</td>
          <td>${c.email_cliente||''}</td>
          <td>${c.Telefono1||''}</td>
          <td>${c.credito||0}</td>
        </tr>`;
                    });
                    tbodyClientes.innerHTML = html;
                });
        }

        /* ===== CRUD ===== */
        function nuevoCliente(){
            document.querySelectorAll('#mdlCliente input').forEach(i=>i.value='');
            mdlCliente.style.display='block';
        }

        function editarCliente(id){
            fetch(API+'?action=get&id_cliente='+id)
                .then(r=>r.json())
                .then(c=>{
                    for(let k in c){ if(document.getElementById(k)) document.getElementById(k).value=c[k]; }
                    mdlCliente.style.display='block';
                });
        }

        function guardarCliente(){
            if(email_cliente.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email_cliente.value)){
                alert('Email inválido'); return;
            }
            const fd=new FormData();
            fd.append('action',id_cliente.value?'update':'create');
            document.querySelectorAll('#mdlCliente input').forEach(i=>fd.append(i.id,i.value));
            fetch(API,{method:'POST',body:fd}).then(()=>{cerrarModal('mdlCliente');cargarClientes();});
        }

        function eliminarCliente(id){
            if(!confirm('¿Eliminar cliente?'))return;
            const fd=new FormData();
            fd.append('action','delete');fd.append('id_cliente',id);
            fetch(API,{method:'POST',body:fd}).then(cargarClientes);
        }

        function recuperar(id){
            const fd=new FormData();
            fd.append('action','restore');fd.append('id_cliente',id);
            fetch(API,{method:'POST',body:fd}).then(cargarClientes);
        }

        /* ===== CSV ===== */
        function exportarDatos(){ window.open(API+'?action=export_csv&tipo=datos','_blank'); }
        function descargarLayout(){ window.open(API+'?action=export_csv&tipo=layout','_blank'); }
        function abrirImport(){ mdlImport.style.display='block'; }

        function previsualizarCsv(){
            const f=fileCsv.files[0]; if(!f)return;
            const r=new FileReader();
            r.onload=e=>{
                const rows=e.target.result.split('\n').filter(x=>x);
                csvHead.innerHTML='<tr>'+rows[0].split(',').map(h=>`<th>${h}</th>`).join('')+'</tr>';
                csvBody.innerHTML=rows.slice(1,6).map(r=>'<tr>'+r.split(',').map(c=>`<td>${c}</td>`).join('')+'</tr>').join('');
                csvPreviewWrap.style.display='block';
            };
            r.readAsText(f);
        }

        function importarCsv(){
            const fd=new FormData();
            fd.append('action','import_csv');
            fd.append('file',fileCsv.files[0]);
            fetch(API,{method:'POST',body:fd})
                .then(r=>r.json())
                .then(resp=>{
                    if(resp.success){ cerrarModal('mdlImport'); cargarClientes(); }
                    else alert(resp.error||'Error');
                });
        }

        /* ===== UTILS ===== */
        function toggleInactivos(){ verInactivos=!verInactivos; cargarClientes(); }
        function cerrarModal(id){ document.getElementById(id).style.display='none'; }

        document.addEventListener('DOMContentLoaded',cargarClientes);
    </script>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
?>
<?php
