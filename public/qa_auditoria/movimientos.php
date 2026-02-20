<?php
// session_start();
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<div class="content p-3 p-md-4" style="font-size:10px;">
  <div class="container-fluid">

    <div class="row mb-2">
      <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0 fw-bold" style="color:#0F5AAD;">
            <i class="fa fa-vial-circle-check me-2"></i>QA | Movimientos (Ingreso / Liberación)
          </h5>
          <small class="text-muted">Bloquea / libera a nivel pallet, caja, pieza (por lote) o ubicación completa.</small>
        </div>
        <div class="d-flex gap-2">
          <a class="btn btn-sm btn-outline-primary" href="listas_adminqa.php"><i class="fa fa-list me-1"></i>Admin QA</a>
          <a class="btn btn-sm btn-outline-primary" href="listas_qacuarentena.php"><i class="fa fa-map-location-dot me-1"></i>QA por BL</a>
        </div>
      </div>
    </div>

    <div class="card shadow-sm mb-3">
      <div class="card-body py-2">
        <div class="row g-2 align-items-end">

          <div class="col-12 col-md-3">
            <label class="form-label mb-1">Tipo Movimiento</label>
            <select id="tipo_mov" class="form-select form-select-sm">
              <option value="IN">Ingreso a QA</option>
              <option value="OUT">Liberación QA</option>
            </select>
          </div>

          <div class="col-12 col-md-3">
            <label class="form-label mb-1">Motivo</label>
            <select id="id_motivo" class="form-select form-select-sm">
              <option value="">Cargando motivos...</option>
            </select>
          </div>

          <div class="col-12 col-md-2">
            <label class="form-label mb-1">Usuario</label>
            <input id="usuario" class="form-control form-control-sm"
              value="<?php echo isset($_SESSION['usuario']) ? htmlspecialchars($_SESSION['usuario']) : ''; ?>"
              placeholder="Usuario WMS">
          </div>

          <div class="col-12 col-md-2">
            <label class="form-label mb-1">Folio (opcional)</label>
            <input id="folio" class="form-control form-control-sm" placeholder="QA-YYYYMMDD-HHMMSS-XXXX">
          </div>

          <div class="col-12 col-md-2 text-md-end">
            <button class="btn btn-sm btn-primary w-100" id="btn-ejecutar">
              <i class="fa fa-play me-1"></i> Ejecutar Movimiento
            </button>
            <button class="btn btn-sm btn-outline-secondary w-100 mt-1" id="btn-limpiar">
              <i class="fa fa-eraser me-1"></i> Limpiar
            </button>
          </div>

        </div>
      </div>
    </div>

    <div class="card shadow-sm mb-3">
      <div class="card-body py-2">
        <div class="row g-2">

          <div class="col-12 col-md-6">
            <label class="form-label mb-1">Alcance</label>
            <div class="d-flex flex-wrap gap-2">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="alcance" id="alc_lp" value="LP" checked>
                <label class="form-check-label" for="alc_lp">LP / Pallet / Contenedor</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="alcance" id="alc_prod" value="PROD">
                <label class="form-check-label" for="alc_prod">Producto + Lote</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="alcance" id="alc_bl" value="BL">
                <label class="form-check-label" for="alc_bl">Ubicación completa (BL)</label>
              </div>
            </div>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label mb-1">Acción rápida</label>
            <div class="d-flex gap-2">
              <button class="btn btn-sm btn-outline-primary" id="btn-agregar-seleccion">
                <i class="fa fa-cart-plus me-1"></i> Agregar Selección al Carrito
              </button>
              <span class="text-muted align-self-center">Carrito: <b id="carrito_count">0</b> ítems</span>
            </div>
          </div>

        </div>

        <hr class="my-2"/>

        <div id="panel_lp" class="row g-2">
          <div class="col-12 col-md-4">
            <label class="form-label mb-1">EPC / Code / LP</label>
            <div class="input-group input-group-sm">
              <input id="q_lp" class="form-control" placeholder="Escanea EPC o escribe LP (ej. LP1718...)">
              <button class="btn btn-primary" id="btn-buscar-lp"><i class="fa fa-search"></i></button>
            </div>
          </div>
          <div class="col-12 col-md-8">
            <div class="alert alert-light border mb-0 py-2" style="font-size:10px;">
              <b>Tip:</b> EPC (largo) se busca directo; LP/CODE (corto) se resuelve por <b>c_charolas</b> y luego existencias.
            </div>
          </div>
        </div>

        <div id="panel_prod" class="row g-2 d-none">
          <div class="col-12 col-md-3">
            <label class="form-label mb-1">Clave Artículo</label>
            <input id="q_art" class="form-control form-control-sm" placeholder="Cve Artículo">
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label mb-1">Lote</label>
            <input id="q_lote" class="form-control form-control-sm" placeholder="Lote (obligatorio si aplica)">
          </div>
          <div class="col-12 col-md-2">
            <label class="form-label mb-1">Almacén</label>
            <input id="q_almac" class="form-control form-control-sm" placeholder="Cve_Almac (opcional)">
          </div>
          <div class="col-12 col-md-2">
            <button class="btn btn-sm btn-primary w-100" id="btn-buscar-prod"><i class="fa fa-search me-1"></i>Buscar</button>
          </div>
        </div>

        <div id="panel_bl" class="row g-2 d-none">
          <div class="col-12 col-md-4">
            <label class="form-label mb-1">BL o idy_ubica</label>
            <div class="input-group input-group-sm">
              <input id="q_bl" class="form-control" placeholder="Ej: W801010101 o 12345">
              <button class="btn btn-primary" id="btn-buscar-bl"><i class="fa fa-search"></i></button>
            </div>
          </div>
        </div>

      </div>
    </div>

    <div class="card shadow-sm mb-3">
      <div class="card-body p-2">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <b>Resultados</b>
          <small class="text-muted">Selecciona filas y agrégalas al carrito.</small>
        </div>
        <div class="table-responsive">
          <table id="tabla_resultados" class="table table-sm table-striped table-hover mb-0" style="font-size:10px; width:100%;">
            <thead>
              <tr>
                <th style="width:25px;"><input type="checkbox" id="sel_all"/></th>
                <th>Nivel</th>
                <th>Almac</th>
                <th>BL</th>
                <th>idy_ubica</th>
                <th>Artículo</th>
                <th>Lote</th>
                <th>Id Cont</th>
                <th>Qty</th>
                <th>En QA</th>
                <th>EPC/Code</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-body p-2">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <b>Carrito QA</b>
          <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-danger" id="btn-quitar"><i class="fa fa-trash me-1"></i>Quitar seleccionados</button>
          </div>
        </div>
        <div class="table-responsive">
          <table id="tabla_carrito" class="table table-sm table-bordered mb-0" style="font-size:10px; width:100%;">
            <thead>
              <tr>
                <th style="width:25px;"><input type="checkbox" id="car_all"/></th>
                <th>Nivel</th>
                <th>Almac</th>
                <th>BL</th>
                <th>idy_ubica</th>
                <th>Artículo</th>
                <th>Lote</th>
                <th>Id Cont</th>
                <th>Qty</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
const API_BASE = '../api/qa_cuarentena';
let resultados = [];
let carrito = [];

function toast(msg, ok=true){
  const cls = ok ? 'alert-success' : 'alert-danger';
  const el = document.createElement('div');
  el.className = `alert ${cls} shadow-sm`;
  el.style.position='fixed'; el.style.top='15px'; el.style.right='15px'; el.style.zIndex=9999;
  el.style.fontSize='12px'; el.style.padding='8px 12px';
  el.innerText = msg;
  document.body.appendChild(el);
  setTimeout(()=>el.remove(), 2500);
}

function fmtNivel(n){
  if(n==='TR') return 'TARIMA';
  if(n==='CJ') return 'CAJA';
  if(n==='PZ') return 'PIEZA';
  return n;
}

function getAlcance(){
  return document.querySelector('input[name="alcance"]:checked').value;
}

function togglePanels(){
  const a = getAlcance();
  document.getElementById('panel_lp').classList.toggle('d-none', a!=='LP');
  document.getElementById('panel_prod').classList.toggle('d-none', a!=='PROD');
  document.getElementById('panel_bl').classList.toggle('d-none', a!=='BL');
  resultados = [];
  renderResultados();
}

function renderResultados(){
  const tbody = document.querySelector('#tabla_resultados tbody');
  tbody.innerHTML = '';
  resultados.forEach((r, idx)=>{
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input type="checkbox" class="sel_row" data-idx="${idx}"></td>
      <td>${fmtNivel(r.nivel)}</td>
      <td>${r.cve_almac ?? ''}</td>
      <td>${r.bl ?? ''}</td>
      <td>${r.idy_ubica ?? ''}</td>
      <td>${r.cve_articulo ?? ''}</td>
      <td>${r.cve_lote ?? ''}</td>
      <td>${r.id_contenedor ?? ''}</td>
      <td>${r.cantidad ?? ''}</td>
      <td>${(parseInt(r.cuarentena||0)===1) ? '<span class="badge bg-danger">SI</span>' : '<span class="badge bg-success">NO</span>'}</td>
      <td>${(r.epc||'') ? r.epc : (r.code||'')}</td>
    `;
    tbody.appendChild(tr);
  });
}

function renderCarrito(){
  const tbody = document.querySelector('#tabla_carrito tbody');
  tbody.innerHTML='';
  carrito.forEach((r, idx)=>{
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input type="checkbox" class="car_row" data-idx="${idx}"></td>
      <td>${fmtNivel(r.nivel)}</td>
      <td>${r.cve_almac ?? ''}</td>
      <td>${r.bl ?? ''}</td>
      <td>${r.idy_ubica ?? ''}</td>
      <td>${r.cve_articulo ?? ''}</td>
      <td>${r.cve_lote ?? ''}</td>
      <td>${r.id_contenedor ?? ''}</td>
      <td>${r.cantidad ?? ''}</td>
    `;
    tbody.appendChild(tr);
  });
  document.getElementById('carrito_count').innerText = carrito.length;
}

function dedupeKey(r){
  return [r.nivel,r.idy_ubica,r.cve_articulo,r.cve_lote,r.id_contenedor].join('|');
}

async function cargarMotivos(){
  const res = await fetch(`${API_BASE}/qa_motivos.php`);
  const js = await res.json();
  const sel = document.getElementById('id_motivo');
  sel.innerHTML = '<option value="">Seleccione</option>';
  (js.data||[]).forEach(m=>{
    const o = document.createElement('option');
    o.value = m.id;
    o.textContent = `${m.Des_Motivo}`;
    sel.appendChild(o);
  });
}

/** ✅ FIX: este era el cuello de botella */
async function buscarLP(){
  const q = document.getElementById('q_lp').value.trim();
  if(!q) return toast('Capture/escanee EPC o Code/LP', false);

  const res = await fetch(`${API_BASE}/qa_buscar_lp.php?q=${encodeURIComponent(q)}`);
  const js  = await res.json();

  if(!js.ok){
    resultados = [];
    renderResultados();
    return toast(js.msg || 'No encontrado', false);
  }

  const bl = js.data?.ubicacion?.CodigoCSD || '';
  resultados = (js.data?.matches || []).map(x => ({ ...x, bl }));

  renderResultados();
  toast(`Encontrado: ${resultados.length} registro(s)`, true);
}

async function buscarProducto(){
  const art = document.getElementById('q_art').value.trim();
  const lote = document.getElementById('q_lote').value.trim();
  const almac = document.getElementById('q_almac').value.trim();
  if(!art) return toast('Capture Clave Artículo', false);
  let url = `${API_BASE}/qa_buscar_producto.php?cve_articulo=${encodeURIComponent(art)}`;
  if(lote) url += `&cve_lote=${encodeURIComponent(lote)}`;
  if(almac) url += `&cve_almac=${encodeURIComponent(almac)}`;
  const res = await fetch(url);
  const js = await res.json();
  if(!js.ok) return toast(js.msg || 'Sin resultados', false);
  resultados = (js.data.rows||[]);
  renderResultados();
}

async function buscarBL(){
  const q = document.getElementById('q_bl').value.trim();
  if(!q) return toast('Capture BL o idy_ubica', false);
  let url = `${API_BASE}/qa_buscar_ubicacion.php?`;
  if(/^[0-9]+$/.test(q)) url += `idy_ubica=${encodeURIComponent(q)}`;
  else url += `bl=${encodeURIComponent(q)}`;
  const res = await fetch(url);
  const js = await res.json();
  if(!js.ok) return toast(js.msg || 'No encontrado', false);
  const bl = js.data.ubicacion?.CodigoCSD || '';
  resultados = (js.data.rows||[]).map(x=>({ ...x, bl }));
  renderResultados();
}

function agregarSeleccion(){
  const a = getAlcance();

  if(a === 'BL'){
    const q = document.getElementById('q_bl').value.trim();
    if(!q) return toast('Capture BL/idy_ubica', false);
    if(!resultados.length) return toast('Primero busca la ubicación para validar contenido', false);
    const u = resultados[0].idy_ubica;
    const key = ['U', u].join('|');
    if(carrito.some(x=>x._key===key)) return toast('Ubicación ya está en carrito', false);
    carrito.push({_key:key, nivel:'', cve_almac: resultados[0].cve_almac, bl: resultados[0].bl, idy_ubica: u, cve_articulo: '', cve_lote:'', id_contenedor:null, cantidad:null, _tipo_cat:'U'});
    renderCarrito();
    return toast('Ubicación agregada al carrito');
  }

  const sels = Array.from(document.querySelectorAll('.sel_row:checked')).map(x=>parseInt(x.dataset.idx));
  if(!sels.length) return toast('Seleccione al menos una fila', false);

  sels.forEach(i=>{
    const r = resultados[i];
    const item = {
      nivel: r.nivel,
      cve_almac: parseInt(r.cve_almac||0),
      bl: r.bl || '',
      idy_ubica: parseInt(r.idy_ubica||0),
      cve_articulo: r.cve_articulo || '',
      cve_lote: r.cve_lote || '',
      id_contenedor: r.id_contenedor ? parseInt(r.id_contenedor) : null,
      cantidad: r.cantidad ? parseFloat(r.cantidad) : null,
      pzsxcaja: (r.nivel==='CJ' && r.cantidad) ? parseInt(r.cantidad) : null
    };
    const key = dedupeKey(item);
    if(!carrito.some(x=>x._key===key)){
      item._key = key;
      carrito.push(item);
    }
  });

  renderCarrito();
  toast('Selección agregada al carrito');
}

function quitarSeleccionCarrito(){
  const sels = Array.from(document.querySelectorAll('.car_row:checked')).map(x=>parseInt(x.dataset.idx));
  if(!sels.length) return toast('Seleccione ítems del carrito', false);
  carrito = carrito.filter((_, idx)=>!sels.includes(idx));
  renderCarrito();
  toast('Ítems removidos');
}

async function ejecutar(){
  const tipo_mov = document.getElementById('tipo_mov').value;
  const id_motivo = document.getElementById('id_motivo').value;
  const usuario = document.getElementById('usuario').value.trim();
  const folio = document.getElementById('folio').value.trim();

  if(!id_motivo) return toast('Seleccione Motivo', false);
  if(!usuario) return toast('Capture Usuario', false);
  if(!carrito.length) return toast('Carrito vacío', false);

  const payload = {
    tipo_mov,
    id_motivo: parseInt(id_motivo),
    usuario,
    folio: folio || undefined,
    tipo_cat: null,
    items: carrito.map(x=>{
      return {
        nivel: x.nivel || '',
        cve_almac: x.cve_almac || 0,
        idy_ubica: x.idy_ubica,
        cve_articulo: x.cve_articulo || '',
        cve_lote: x.cve_lote || '',
        id_contenedor: x.id_contenedor,
        cantidad: x.cantidad,
        pzsxcaja: x.pzsxcaja || null
      }
    })
  };

  if(carrito.some(x=>x._tipo_cat==='U')) payload.tipo_cat = 'U';

  const res = await fetch(`${API_BASE}/qa_ejecutar.php`, {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify(payload)
  });
  const js = await res.json();
  if(!js.ok) return toast(js.msg || 'Error ejecutando', false);

  toast(`${js.msg} | Folio: ${js.data.folio}`);
  carrito = [];
  resultados = [];
  renderCarrito();
  renderResultados();
}

function limpiar(){
  resultados = [];
  carrito = [];
  renderResultados();
  renderCarrito();
  document.getElementById('q_lp').value = '';
  document.getElementById('q_art').value = '';
  document.getElementById('q_lote').value = '';
  document.getElementById('q_almac').value = '';
  document.getElementById('q_bl').value = '';
  toast('Limpieza realizada');
}

document.querySelectorAll('input[name="alcance"]').forEach(r=>r.addEventListener('change', togglePanels));
document.getElementById('btn-buscar-lp').addEventListener('click', buscarLP);
document.getElementById('btn-buscar-prod').addEventListener('click', buscarProducto);
document.getElementById('btn-buscar-bl').addEventListener('click', buscarBL);
document.getElementById('btn-agregar-seleccion').addEventListener('click', agregarSeleccion);
document.getElementById('btn-quitar').addEventListener('click', quitarSeleccionCarrito);
document.getElementById('btn-ejecutar').addEventListener('click', ejecutar);
document.getElementById('btn-limpiar').addEventListener('click', limpiar);

document.getElementById('sel_all').addEventListener('change', (e)=>{
  document.querySelectorAll('.sel_row').forEach(cb=>cb.checked=e.target.checked);
});
document.getElementById('car_all').addEventListener('change', (e)=>{
  document.querySelectorAll('.car_row').forEach(cb=>cb.checked=e.target.checked);
});

cargarMotivos().then(()=>{}).catch(()=>toast('No se pudieron cargar motivos', false));
togglePanels();
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
