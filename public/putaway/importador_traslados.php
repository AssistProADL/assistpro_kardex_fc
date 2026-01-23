<?php
require_once __DIR__ . '/../bi/_menu_global.php';

$USR = $_SESSION['cve_usuario'] ?? ($_SESSION['username'] ?? ($_SESSION['usuario'] ?? 'SISTEMA'));
?>
<style>
    .ap-card{background:#fff;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.12);border:1px solid #e1e5eb;margin-bottom:15px}
    .ap-card-header{background:#0F5AAD;color:#fff;padding:8px 12px;font-size:13px;font-weight:600;border-radius:8px 8px 0 0}
    .ap-card-body{padding:12px;font-size:11px}
    .ap-form-control{font-size:11px;height:28px;padding:2px 6px}
    .ap-label{font-size:11px;font-weight:600;margin-bottom:2px}
    .table-sm th,.table-sm td{font-size:10px;padding:4px 6px;white-space:nowrap;vertical-align:middle}
    .scroll-table{max-height:340px;overflow-x:auto;overflow-y:auto}
    .scroll-table.bitacora{max-height:260px}
    .ap-badge{display:inline-block;font-size:10px;padding:2px 8px;border-radius:10px;border:1px solid #d0d7e2;background:#f7f9fc}
    .ap-badge.ok{background:#d1e7dd;border-color:#badbcc;color:#0f5132}
    .ap-badge.warn{background:#fff3cd;border-color:#ffecb5;color:#664d03}
    .ap-badge.err{background:#f8d7da;border-color:#f5c2c7;color:#842029}
</style>

<div class="container-fluid">
    <h4 class="mb-3"><i class="fa fa-exchange"></i> Importador de Traslados entre Almacenes</h4>

    <div class="ap-card">
        <div class="ap-card-header">Parámetros de importación</div>
        <div class="ap-card-body">
            <form id="form-importador" enctype="multipart/form-data">
                <div class="row mb-2">
                    <div class="col-md-3">
                        <label class="ap-label">Empresa</label>
                        <select name="empresa_id" id="empresa_id" class="form-control ap-form-control">
                            <option value="">[Seleccione]</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="ap-label">Almacén (contexto)</label>
                        <select name="almacen_id" id="almacen_id" class="form-control ap-form-control">
                            <option value="">[Seleccione]</option>
                        </select>
                        <small style="color:#6c757d;">Control corporativo: valida que el usuario importe al entorno correcto.</small>
                    </div>

                    <div class="col-md-3">
                        <label class="ap-label">Tipo de operación</label>
                        <select name="tipo_operacion" id="tipo_operacion" class="form-control ap-form-control">
                            <option value="TRAS_ALM" selected>TRAS_ALM - Traslado entre almacenes</option>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex align-items-end justify-content-end">
                        <button type="button" class="btn btn-sm btn-primary" id="btn-abrir-modal">
                            <i class="fa fa-upload"></i> Importar
                        </button>
                    </div>
                </div>

                <input type="file" name="archivo" id="archivo" class="d-none">
                <input type="hidden" name="usuario_importa" id="usuario_importa" value="<?php echo htmlspecialchars($USR); ?>">
                <input type="hidden" name="modo" id="modo" value="TRAS_ALM">
            </form>
        </div>
    </div>

    <div class="ap-card">
        <div class="ap-card-header">Previsualización y validaciones</div>
        <div class="ap-card-body">
            <div class="row mb-2">
                <div class="col-md-3">
                    <span class="ap-label">Totales</span>
                    <div id="resumen-totales" style="font-size:11px;">Líneas: 0 | OK: 0 | Error: 0</div>
                </div>
                <div class="col-md-9">
                    <span class="ap-label">Mensajes</span>
                    <div id="mensajes-importador" style="font-size:11px; max-height:80px; overflow-y:auto; border:1px solid #eee; padding:4px;"></div>
                </div>
            </div>

            <div class="scroll-table">
                <table class="table table-sm table-striped table-bordered" id="tabla-previsualizacion">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Estado</th>
                            <th>Mensaje</th>
                            <th>Almacén Origen</th>
                            <th>Zona almacenaje</th>
                            <th>Ubicación origen</th>
                            <th>LP/Contenedor origen</th>
                            <th>Almacén destino</th>
                            <th>Zona recibo destino</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <small style="color:#6c757d;">
                    Layout esperado (CSV): columnas del Excel original:
                    <b>ALMACEN ORIGEN</b>, <b>ZONA DE ALMACENAJE</b>, <b>UBICACIÓN ORIGEN</b>,
                    <b>LICENSE PLATE (LP) ORIGEN Y LP  CONTENEDOR  ORIGEN</b>, <b>ALMACEN DESTINO</b>,
                    <b>ZONA  RECIBO ALMACEN  DESTINO (t_ubicacionretencion)</b>.
                </small>
            </div>
        </div>
    </div>

    <div class="ap-card">
        <div class="ap-card-header">Bitácora de importaciones</div>
        <div class="ap-card-body">
            <div class="row mb-2">
                <div class="col-md-6">
                    <span class="ap-label">Últimas importaciones</span>
                    <div style="font-size:11px;color:#6c757d;">Folio, usuario, fecha, estatus, impacto kardex y rollback.</div>
                </div>
                <div class="col-md-6 d-flex align-items-end justify-content-end">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-refrescar-bitacora">
                        <i class="fa fa-refresh"></i> Refrescar
                    </button>
                </div>
            </div>

            <div class="scroll-table bitacora">
                <table class="table table-sm table-striped table-bordered" id="tabla-bitacora">
                    <thead>
                        <tr>
                            <th>Acciones</th>
                            <th>Folio importación</th>
                            <th>Usuario</th>
                            <th>Fecha importación</th>
                            <th>Status</th>
                            <th>Impacto Kardex</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<!-- MODAL -->
<div class="modal fade" id="modalImportar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="border-radius:10px; overflow:hidden;">
      <div class="modal-header" style="background:#0F5AAD;color:#fff;">
        <h6 class="modal-title"><i class="fa fa-upload"></i> Importar traslados entre almacenes</h6>
        <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="color:#fff;opacity:1;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body" style="font-size:11px;">
        <div class="row">
            <div class="col-md-4">
                <label class="ap-label">Tipo de importador</label>
                <select id="modal_tipo_operacion" class="form-control ap-form-control">
                    <option value="TRAS_ALM" selected>TRAS_ALM - Traslado entre almacenes</option>
                </select>
                <small style="color:#6c757d;">Define el layout y reglas de validación.</small>
            </div>

            <div class="col-md-4">
                <label class="ap-label">Layout</label><br>
                <button type="button" class="btn btn-sm btn-outline-primary" id="modal_btn_layout">
                    <i class="fa fa-file-excel-o"></i> Descargar layout
                </button>
                <div id="modal_layout_hint" style="margin-top:6px;color:#6c757d;"></div>
            </div>

            <div class="col-md-4">
                <label class="ap-label">Archivo a importar (CSV)</label>
                <input type="file" id="modal_archivo" class="form-control ap-form-control" accept=".csv,.txt">
                <small style="color:#6c757d;">Se validará antes de procesar.</small>
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="col-md-6">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="modal_chk_impactar_kardex" checked>
                    <label class="form-check-label" for="modal_chk_impactar_kardex" style="font-size:11px;">
                        Impactar Kardex (movimiento formal)
                    </label>
                </div>
                <small style="color:#6c757d;">Si desmarcas, el API puede correr en “modo simulación” (solo bitácora/validación).</small>
            </div>
            <div class="col-md-6 d-flex align-items-center justify-content-end">
                <button type="button" class="btn btn-sm btn-secondary mr-2" id="modal_btn_previsualizar">
                    <i class="fa fa-eye"></i> Previsualizar
                </button>
                <button type="button" class="btn btn-sm btn-success" id="modal_btn_procesar" disabled>
                    <i class="fa fa-check"></i> Procesar
                </button>
            </div>
        </div>

        <div id="modal_msg" style="margin-top:10px;border:1px solid #eee;padding:6px;max-height:70px;overflow:auto;"></div>
      </div>
    </div>
  </div>
</div>

<script>
const AP_USR = <?php echo json_encode($USR); ?>;

// rutas
const API_FILTROS = '../api/filtros_assistpro.php';
const API_IMPORT  = '../api/importador_traslados.php';

// modal helpers (BS5 / BS4+jQ / fallback)
function apShowModal(id){
  const el = document.getElementById(id); if(!el) return;
  if(window.bootstrap && bootstrap.Modal){ bootstrap.Modal.getOrCreateInstance(el).show(); return; }
  if(window.jQuery && jQuery.fn && jQuery.fn.modal){ jQuery(el).modal('show'); return; }
  el.classList.add('show'); el.style.display='block'; document.body.classList.add('modal-open');
}
function apHideModal(id){
  const el = document.getElementById(id); if(!el) return;
  if(window.bootstrap && bootstrap.Modal){ bootstrap.Modal.getOrCreateInstance(el).hide(); return; }
  if(window.jQuery && jQuery.fn && jQuery.fn.modal){ jQuery(el).modal('hide'); return; }
  el.classList.remove('show'); el.style.display='none'; document.body.classList.remove('modal-open');
}

function setMsgMain(msg){ document.getElementById('mensajes-importador').innerText = msg || ''; }
function setMsgModal(msg, isErr=false){
  const el = document.getElementById('modal_msg');
  el.style.color = isErr ? '#b02a37' : '#0f5132';
  el.innerText = msg || '';
}
function badgeStatus(status){
  const s=(status||'').toUpperCase();
  if(s.includes('PROC')||s==='OK') return '<span class="ap-badge ok">PROCESADO</span>';
  if(s.includes('ERR')) return '<span class="ap-badge err">ERROR</span>';
  return '<span class="ap-badge warn">PENDIENTE</span>';
}
function impactoKardexLabel(v){
  const s=(v===true||v==='1'||(typeof v==='string'&&v.toUpperCase()==='SI'))?'SI':(v||'N/D');
  return (s==='SI')?'<span class="ap-badge ok">SI</span>':'<span class="ap-badge warn">'+s+'</span>';
}

function cargarEmpresasYAlmacenes(){
  const selEmpresa=document.getElementById('empresa_id');
  const selAlmacen=document.getElementById('almacen_id');

  fetch(API_FILTROS+'?action=init')
    .then(r=>r.json())
    .then(data=>{
      if(!data||data.ok===false) return;

      if(Array.isArray(data.empresas)){
        selEmpresa.innerHTML='<option value="">[Seleccione]</option>';
        data.empresas.forEach(emp=>{
          const opt=document.createElement('option');
          opt.value=emp.cve_cia;
          opt.textContent='['+emp.cve_cia+'] '+(emp.des_cia||'');
          selEmpresa.appendChild(opt);
        });
      }
      if(Array.isArray(data.almacenes)){
        selAlmacen.innerHTML='<option value="">[Seleccione]</option>';
        data.almacenes.forEach(alm=>{
          const clave=alm.cve_almac||alm.clave_almacen||'';
          const desc=alm.des_almac||clave;
          const opt=document.createElement('option');
          opt.value=clave;
          opt.textContent=(clave?'['+clave+'] ':'')+desc;
          selAlmacen.appendChild(opt);
        });
      }
    });
}

function cargarBitacora(){
  fetch(API_IMPORT+'?action=runs_list')
    .then(r=>r.json())
    .then(data=>{
      const tb=document.querySelector('#tabla-bitacora tbody');
      tb.innerHTML='';
      const rows=(data && (data.data||data.rows||data.list))?(data.data||data.rows||data.list):[];
      rows.forEach(row=>{
        const folio=row.folio_importacion||row.folio||'';
        const usuario=row.usuario||row.user||row.cve_usuario||AP_USR||'SISTEMA';
        const fecha=row.fecha_importacion||row.fecha||row.created_at||'';
        const status=row.status||row.estado||'PENDIENTE';
        const impacto=(row.impacto_kardex!==undefined)?row.impacto_kardex:(row.kardex??'N/D');

        const tr=document.createElement('tr');
        tr.innerHTML=`
          <td style="text-align:center;">
            <button class="btn btn-sm btn-outline-danger" style="font-size:10px;padding:2px 6px;"
              onclick="rollbackImportacion('${folio}')"><i class="fa fa-undo"></i> Rollback</button>
          </td>
          <td><b>${folio}</b></td>
          <td>${usuario}</td>
          <td>${fecha}</td>
          <td>${badgeStatus(status)}</td>
          <td>${impactoKardexLabel(impacto)}</td>
        `;
        tb.appendChild(tr);
      });
    });
}
function rollbackImportacion(folio){
  if(!folio) return;
  if(!confirm('¿Rollback de la importación '+folio+'?')) return;
  fetch(API_IMPORT,{method:'POST',body:new URLSearchParams({action:'rollback',folio_importacion:folio})})
    .then(r=>r.json()).then(data=>{
      if(!data||!data.ok){ alert((data&&data.error)?data.error:'Rollback falló'); return; }
      alert('Rollback OK: '+folio);
      cargarBitacora();
    });
}

document.addEventListener('DOMContentLoaded', function(){
  cargarEmpresasYAlmacenes();
  cargarBitacora();
  document.getElementById('btn-refrescar-bitacora').addEventListener('click', cargarBitacora);

  document.getElementById('btn-abrir-modal').addEventListener('click', function(){
    const emp=document.getElementById('empresa_id').value;
    const alm=document.getElementById('almacen_id').value;
    if(!emp||!alm){ alert('Seleccione Empresa y Almacén antes de importar.'); return; }

    document.getElementById('modal_layout_hint').innerText='Layout listo para: TRAS_ALM';
    document.getElementById('modal_archivo').value='';
    document.getElementById('modal_btn_procesar').disabled=true;
    setMsgModal('');
    apShowModal('modalImportar');
  });

  document.getElementById('modal_btn_layout').addEventListener('click', function(){
    window.location.href = API_IMPORT + '?action=layout&tipo_operacion=TRAS_ALM';
  });

  document.getElementById('modal_archivo').addEventListener('change', function(){
    const file=this.files && this.files[0] ? this.files[0] : null;
    if(!file){ document.getElementById('modal_btn_procesar').disabled=true; return; }
    const dt=new DataTransfer();
    dt.items.add(file);
    document.getElementById('archivo').files=dt.files;
    setMsgModal('Archivo cargado: '+file.name,false);
  });

  document.getElementById('modal_btn_previsualizar').addEventListener('click', function(){
    const fd=new FormData(document.getElementById('form-importador'));
    fd.append('action','previsualizar');

    const impactar=document.getElementById('modal_chk_impactar_kardex').checked?'1':'0';
    fd.append('impactar_kardex',impactar);

    document.getElementById('usuario_importa').value = AP_USR || 'SISTEMA';
    fd.set('usuario_importa', document.getElementById('usuario_importa').value);

    setMsgModal('Previsualizando...',false);

    fetch(API_IMPORT,{method:'POST',body:fd})
      .then(r=>r.json())
      .then(data=>{
        if(!data||!data.ok){ setMsgModal((data&&data.error)?data.error:'Error',true); return; }

        document.getElementById('resumen-totales').innerText =
          'Líneas: '+(data.total||0)+' | OK: '+(data.total_ok||0)+' | Error: '+(data.total_err||0);

        const tbody=document.querySelector('#tabla-previsualizacion tbody');
        tbody.innerHTML='';
        (data.filas||[]).forEach((row,idx)=>{
          const tr=document.createElement('tr');
          tr.innerHTML=`
            <td>${idx+1}</td>
            <td>${row.estado||''}</td>
            <td>${row.mensaje||''}</td>
            <td>${row.almacen_origen||''}</td>
            <td>${row.zona_almacenaje||''}</td>
            <td>${row.ubicacion_origen||''}</td>
            <td>${row.lp_contenedor_origen||''}</td>
            <td>${row.almacen_destino||''}</td>
            <td>${row.zona_recibo_destino||''}</td>
          `;
          tbody.appendChild(tr);
        });

        setMsgMain(data.mensaje_global||'Previsualización OK');
        setMsgModal('Previsualización OK. Si todo está correcto, procesa.',false);
        document.getElementById('modal_btn_procesar').disabled = (data.total_ok||0)===0;
      })
      .catch(err=>setMsgModal('Error de comunicación: '+err,true));
  });

  document.getElementById('modal_btn_procesar').addEventListener('click', function(){
    if(!confirm('¿Procesar importación?\nSe generará folio y bitácora.')) return;

    const fd=new FormData(document.getElementById('form-importador'));
    fd.append('action','procesar');

    const impactar=document.getElementById('modal_chk_impactar_kardex').checked?'1':'0';
    fd.append('impactar_kardex',impactar);

    document.getElementById('usuario_importa').value = AP_USR || 'SISTEMA';
    fd.set('usuario_importa', document.getElementById('usuario_importa').value);

    setMsgModal('Procesando...',false);

    fetch(API_IMPORT,{method:'POST',body:fd})
      .then(r=>r.json())
      .then(data=>{
        if(!data||!data.ok){ setMsgModal((data&&data.error)?data.error:'Error',true); return; }
        setMsgMain((data.mensaje||'Procesado') + (data.folio_importacion?(' | Folio: '+data.folio_importacion):''));
        setMsgModal('Procesado OK.',false);
        apHideModal('modalImportar');
        cargarBitacora();
      })
      .catch(err=>setMsgModal('Error de comunicación: '+err,true));
  });

});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
