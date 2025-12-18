<?php
require_once __DIR__ . '/../bi/_menu_global.php';
require_once __DIR__ . '/../../app/db.php';

// Cargar categorías únicas desde c_articulo
$categorias = db_all("
    SELECT DISTINCT ecommerce_categoria AS cat
    FROM c_articulo
    WHERE IFNULL(Activo,0)=1
      AND IFNULL(ecommerce_activo,0)=1
      AND ecommerce_categoria IS NOT NULL
      AND ecommerce_categoria <> ''
    ORDER BY 1
");
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Catálogo E-Commerce</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{font-family:Arial,sans-serif;font-size:10px;background:#f2f4f8;margin:0}
.wrap{padding:14px 18px;margin-left:260px;}
.catalog-container{max-width:1200px;margin:0 auto}
h1{font-size:16px;margin:0 0 10px;color:#0F5AAD}

/* Filtros */
.filtros{display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;margin:10px 0}
.filtros label{font-size:9px;font-weight:bold;color:#555;display:block;margin-bottom:2px}
.filtros input[type=text],.filtros select{font-size:10px;padding:3px 6px;border:1px solid #ccc;border-radius:4px;min-width:140px}
.btn{font-size:10px;padding:4px 8px;border-radius:4px;border:1px solid #0F5AAD;background:#0F5AAD;color:#fff;cursor:pointer}
.btn.btn-light{background:#fff;color:#0F5AAD}

/* Tarjetas */
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;margin-top:8px}
.card{background:#fff;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,.08);padding:8px;display:flex;flex-direction:column;height:100%}
.card img{width:100%;height:150px;object-fit:contain;border-radius:8px;background:#fafafa}
.card h4{font-size:11px;margin:6px 0 2px;color:#333}
.card .sku{font-size:9px;color:#888}
.card .precio{font-size:12px;color:#0F5AAD;font-weight:bold;margin-top:4px}
.card .meta{font-size:9px;color:#666;margin-top:2px}
.card .acciones{margin-top:auto;display:flex;justify-content:space-between;align-items:center;margin-top:6px}
.card .acciones button{font-size:9px;padding:3px 6px;border-radius:4px;border:1px solid #0F5AAD;background:#0F5AAD;color:#fff;cursor:pointer}
.card .acciones .btn-detalle{background:#fff;color:#0F5AAD}
.card .comp{margin-top:4px;font-size:9px;color:#555}
.card .comp label{cursor:pointer}
.card .comp input{vertical-align:middle;margin-right:3px}

/* Resumen categorías / paginador */
.resumen-cats{margin:6px 0 8px;font-size:9px}
.resumen-cats .tag{background:#f5f7fb;padding:2px 6px;border-radius:6px;margin-right:4px}
.paginador{margin-top:10px;display:flex;gap:6px;flex-wrap:wrap;font-size:9px;align-items:center}
.page-link{padding:3px 7px;border-radius:6px;border:1px solid #ccc;text-decoration:none;cursor:pointer;color:#333}
.page-link.act{background:#0F5AAD;color:#fff;border-color:#0F5AAD}

/* carrito flotante */
#cartPanel{position:fixed;right:16px;bottom:16px;background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.15);padding:10px 12px;font-size:10px;display:none;flex-direction:column;min-width:220px;z-index:999}
#cartPanel h3{margin:0 0 6px;font-size:11px;color:#0F5AAD}

/* MODALES PROPIOS (para no chocar con Bootstrap) */
.ap-modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.4);display:none;align-items:center;justify-content:center;z-index:1000}
.ap-modal{background:#fff;padding:14px;width:95%;max-width:820px;border-radius:12px;max-height:90vh;overflow:auto}
.ap-modal .close{float:right;cursor:pointer;font-size:12px}

/* Tabs en modal detalle */
.ap-tabs{display:flex;gap:4px;margin-top:4px;border-bottom:1px solid #ddd}
.ap-tab-btn{font-size:10px;padding:4px 8px;border:1px solid #ddd;border-bottom:none;border-radius:6px 6px 0 0;background:#f5f7fb;cursor:pointer}
.ap-tab-btn.active{background:#fff;border-color:#0F5AAD;border-bottom:1px solid #fff;color:#0F5AAD;font-weight:bold}
.ap-tab-pane{display:none;padding-top:6px;font-size:10px}
.ap-tab-pane.active{display:block}

/* Layout info en modal */
.ap-info-layout{display:flex;gap:12px;flex-wrap:wrap;margin-top:4px}
.ap-info-img img{width:180px;max-height:200px;object-fit:contain;background:#fafafa;border-radius:8px}
.ap-info-data h2{font-size:13px;margin:0 0 4px}
.ap-info-data p{margin:2px 0;font-size:10px}
#detTags .tag{display:inline-block;background:#f5f7fb;border-radius:6px;padding:2px 6px;font-size:9px;margin-right:3px;margin-top:2px}
.ap-modal-footer{text-align:right;margin-top:10px}

/* ficha técnica tipo grilla */
#detalleModal table.ft-grid{width:100%;border-collapse:collapse;margin-top:6px;font-size:10px}
#detalleModal table.ft-grid th,#detalleModal table.ft-grid td{border:1px solid #ddd;padding:3px 5px}
#detalleModal table.ft-grid th{background:#f5f7fb;font-weight:bold}

/* tabla carrito */
#cartTable{width:100%;border-collapse:collapse;font-size:10px;margin-top:8px}
#cartTable th,#cartTable td{border-bottom:1px solid #eee;padding:4px 6px;text-align:left}
#cartTable th{background:#f5f7fb}

/* Banners */
.banner-wrap{position:relative;margin:6px 0 12px;border-radius:12px;overflow:hidden;background:#e9eef8}
.banner-track{display:flex;transition:transform .4s ease}
.banner{min-width:100%;height:160px;display:flex;align-items:center;justify-content:center}
.banner img{width:100%;height:160px;object-fit:cover}
.banner-nav{position:absolute;inset:0;display:flex;justify-content:space-between;align-items:center;padding:0 8px}
.banner-btn{background:rgba(0,0,0,.25);border:none;color:#fff;border-radius:20px;width:28px;height:28px;cursor:pointer}

/* Galería de fotos en tab Fotos */
#detFotos{display:flex;flex-wrap:wrap;gap:6px;margin-top:4px}
#detFotos img{width:100px;height:80px;object-fit:cover;border-radius:6px;background:#fafafa}

/* Barra comparador */
.compare-bar{position:fixed;left:280px;right:16px;bottom:60px;background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.2);padding:6px 10px;font-size:9px;display:none;align-items:center;justify-content:space-between;z-index:998}
</style>
</head>
<body>
<div class="wrap catalog-container">
    <h1>Catálogo E-Commerce</h1>

    <!-- BANNERS -->
    <div class="banner-wrap" id="bannerWrap" style="display:none;">
        <div class="banner-track" id="bannerTrack"></div>
        <div class="banner-nav">
            <button class="banner-btn" id="bPrev">‹</button>
            <button class="banner-btn" id="bNext">›</button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filtros">
        <div>
            <label>Buscar (SKU, descripción, t.)</label>
            <input type="text" id="q">
        </div>
        <div>
            <label>Categoría</label>
            <select id="categoria">
                <option value="">Todas las categorías</option>
                <?php foreach ($categorias as $c): ?>
                <option value="<?php echo htmlspecialchars($c['cat'] ?? ''); ?>">
                    <?php echo htmlspecialchars($c['cat'] ?? ''); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button class="btn" id="btnBuscar">Buscar</button>
            <button class="btn btn-light" id="btnLimpiar">Limpiar</button>
        </div>
        <div style="margin-left:auto;">Total: <span id="total">0</span></div>
    </div>

    <div id="resumenCats" class="resumen-cats"></div>
    <div id="grid" class="grid"></div>

    <div id="paginador" class="paginador" style="display:none;">
        <span id="btnPrev" class="page-link">&laquo;</span>
        <span id="infoPag"></span>
        <div id="pages"></div>
        <span id="btnNext" class="page-link">&raquo;</span>
    </div>
</div>

<!-- Panel carrito flotante -->
<div id="cartPanel">
    <h3>Carrito</h3>
    <div>Artículos: <span id="cartItems">0</span></div>
    <div>Total: $<span id="cartTotal">0.00</span></div>
    <br>
    <button class="btn btn-light" id="btnVerCarrito">Ver detalle</button>
    <a class="btn" href="mis_pedidos.php">Mis pedidos</a>
</div>

<!-- Barra comparador -->
<div id="compareBar" class="compare-bar">
    <span id="compareCount">0 artículos para comparar</span>
    <div>
        <button class="btn btn-light" type="button" onclick="limpiarComparador()">Limpiar</button>
        <button class="btn" type="button" onclick="verComparador()">Comparar</button>
    </div>
</div>

<!-- Modal detalle producto -->
<div id="detalleModal" class="ap-modal-backdrop">
  <div class="ap-modal">
    <span class="close" onclick="cerrarDetalle()">✕</span>

    <div class="ap-tabs">
        <button type="button" class="ap-tab-btn active" data-tab="info" onclick="activarTab('info')">Información</button>
        <button type="button" class="ap-tab-btn" data-tab="ficha" onclick="activarTab('ficha')">Ficha técnica</button>
        <button type="button" class="ap-tab-btn" data-tab="spec" onclick="activarTab('spec')">Especificaciones</button>
        <button type="button" class="ap-tab-btn" data-tab="fotos" onclick="activarTab('fotos')">Fotos</button>
    </div>

    <div id="tab-info" class="ap-tab-pane active" data-tab="info">
        <div class="ap-info-layout">
            <div class="ap-info-img">
                <img id="detImg" alt="">
            </div>
            <div class="ap-info-data">
                <h2 id="detTitulo"></h2>
                <p id="detSku"></p>
                <p id="detPrecio"></p>
                <p id="detCategoria"></p>
                <div id="detTags"></div>
            </div>
        </div>
    </div>

    <div id="tab-ficha" class="ap-tab-pane" data-tab="ficha">
        <div id="detInfo"></div>
    </div>

    <div id="tab-spec" class="ap-tab-pane" data-tab="spec">
        <div id="detSpec"></div>
    </div>

    <div id="tab-fotos" class="ap-tab-pane" data-tab="fotos">
        <div id="detFotos"></div>
    </div>

    <div class="ap-modal-footer">
      <button class="btn" id="detAgregar">Agregar al carrito</button>
    </div>
  </div>
</div>

<!-- Modal carrito -->
<div id="cartModal" class="ap-modal-backdrop">
  <div class="ap-modal">
    <span class="close" onclick="cerrarCart()">✕</span>
    <h2>Detalle del carrito</h2>
    <table id="cartTable">
      <thead>
        <tr>
          <th>Clave</th><th>Descripción</th><th>Precio</th>
          <th>Cant.</th><th>Subtotal</th><th></th>
        </tr>
      </thead>
      <tbody id="cartBody"></tbody>
    </table>
    <div style="margin-top:8px;text-align:right;font-size:11px">
      Total: $<span id="cartModalTotal">0.00</span>
    </div>
    <div style="margin-top:10px;text-align:right">
      <button class="btn btn-light" onclick="cerrarCart()">Cerrar</button>
      <button class="btn" id="btnConfirmar">Confirmar pedido</button>
    </div>
  </div>
</div>

<!-- Modal comparador -->
<div id="compareModal" class="ap-modal-backdrop">
  <div class="ap-modal">
    <span class="close" onclick="cerrarComparador()">✕</span>
    <h2>Comparar productos</h2>
    <div id="compareContent" style="margin-top:8px;"></div>
  </div>
</div>

<script>
const API_PROD   = '../api/ecommerce_articulos.php';
const API_PEDIDO = '../api/ecommerce_pedidos.php';

let dataGlobal = [];
let cart = JSON.parse(localStorage.getItem('ap_cart') || '[]');
const PER_PAGE = 16;
let pageActual = 1;
let comparador = [];

// refs DOM
const elQ          = document.getElementById('q');
const elCategoria  = document.getElementById('categoria');
const btnBuscar    = document.getElementById('btnBuscar');
const btnLimpiar   = document.getElementById('btnLimpiar');
const grid         = document.getElementById('grid');
const totalSpan    = document.getElementById('total');
const resumenCats  = document.getElementById('resumenCats');
const paginador    = document.getElementById('paginador');
const btnPrev      = document.getElementById('btnPrev');
const btnNext      = document.getElementById('btnNext');
const infoPag      = document.getElementById('infoPag');
const pages        = document.getElementById('pages');

const cartPanel    = document.getElementById('cartPanel');
const cartItems    = document.getElementById('cartItems');
const cartTotal    = document.getElementById('cartTotal');
const btnVerCarrito= document.getElementById('btnVerCarrito');

const detalleModal = document.getElementById('detalleModal');
const detTitulo    = document.getElementById('detTitulo');
const detSku       = document.getElementById('detSku');
const detImg       = document.getElementById('detImg');
const detPrecio    = document.getElementById('detPrecio');
const detCategoria = document.getElementById('detCategoria');
const detInfo      = document.getElementById('detInfo');
const detSpec      = document.getElementById('detSpec');
const detFotos     = document.getElementById('detFotos');
const detTags      = document.getElementById('detTags');
const detAgregar   = document.getElementById('detAgregar');

const cartModal    = document.getElementById('cartModal');
const cartBody     = document.getElementById('cartBody');
const cartModalTotal = document.getElementById('cartModalTotal');
const btnConfirmar = document.getElementById('btnConfirmar');

const bannerWrap   = document.getElementById('bannerWrap');
const bannerTrack  = document.getElementById('bannerTrack');
const bPrev        = document.getElementById('bPrev');
const bNext        = document.getElementById('bNext');

const compareBar   = document.getElementById('compareBar');
const compareCount = document.getElementById('compareCount');
const compareModal = document.getElementById('compareModal');
const compareContent = document.getElementById('compareContent');

function imgUrl(path){
  if(!path) return 'https://via.placeholder.com/640x480?text=Producto';
  if(/^https?:\/\//i.test(path) || /^\/\//.test(path)) return path;
  if(/^\d+x\d+\?text=/i.test(path)) return 'https://via.placeholder.com/'+path;
  return '../'+String(path).replace(/^\/+/, '');
}

// -------- BANNERS --------
async function cargarBanners(){
  const base = '../portal_clientes/banners/';
  const posibles = [
    'banner-1.jpg','banner-1.png','banner-1.webp',
    'banner-2.jpg','banner-2.png','banner-2.webp',
    'banner-3.jpg','banner-3.png','banner-3.webp'
  ];
  const existentes = [];
  for (const f of posibles){
    try{
      const r = await fetch(base+f,{method:'HEAD'});
      if(r.ok) existentes.push(base+f);
    }catch(e){}
  }
  if(!existentes.length) return;
  bannerWrap.style.display='block';
  bannerTrack.innerHTML = existentes.map(src=>`<div class="banner"><img src="${src}" alt=""></div>`).join('');
  let idx=0;
  function go(n){ idx=(n+existentes.length)%existentes.length; bannerTrack.style.transform=`translateX(-${idx*100}%)`; }
  bPrev.onclick = ()=>go(idx-1);
  bNext.onclick = ()=>go(idx+1);
  setInterval(()=>go(idx+1),6000);
}

// -------- Tarjeta producto --------
function tarjeta(p){
  const img = imgUrl(p.ecommerce_img_principal);
  const precio = Number(p.PrecioVenta ?? 0).toFixed(2);
  const cat = p.ecommerce_categoria ?? '';
  const sub = p.ecommerce_subcategoria ?? '';

  const checked = comparador.includes(p.id) ? 'checked' : '';

  return `
    <div class="card">
      <img src="${img}" alt="">
      <h4>${p.des_articulo}</h4>
      <div class="sku">${p.cve_articulo}</div>
      <div class="precio">$ ${precio}</div>
      <div class="meta">${cat}${sub?(' / '+sub):''}</div>
      <div class="acciones">
        <button class="btn-detalle" onclick="verDetalle(${p.id})">Ver detalle</button>
        <button onclick="addToCart(${p.id})">Agregar</button>
      </div>
      <div class="comp">
        <label><input type="checkbox" class="cmp-check" onchange="toggleComparador(${p.id},this.checked)" ${checked}>Comparar</label>
      </div>
    </div>
  `;
}

function resumenCategorias(){
  if(!dataGlobal.length){ resumenCats.innerHTML=''; return; }
  const map = {};
  dataGlobal.forEach(p=>{
    const c = p.ecommerce_categoria || '(SIN CATEGORÍA)';
    map[c] = (map[c]||0)+1;
  });
  resumenCats.innerHTML = Object.entries(map)
    .map(([c,n])=>`<span class="tag">${c}: ${n}</span>`).join('');
}

// -------- Búsqueda + paginación --------
async function buscar(){
  const params = new URLSearchParams();
  const q = elQ.value.trim();
  const cat = elCategoria.value;

  if(q)   params.append('q', q);
  if(cat) params.append('categoria', cat);

  const resp = await fetch(API_PROD + '?' + params.toString());
  const js   = await resp.json();

  if(!js.ok){
    alert(js.error || 'Error en API de productos');
    return;
  }

  dataGlobal = js.data || [];
  totalSpan.textContent = dataGlobal.length;
  resumenCategorias();
  renderPage(1);
}

function limpiar(){
  elQ.value = '';
  elCategoria.value = '';
  buscar();
}

function renderPage(page){
  const total = dataGlobal.length;
  const totalPages = Math.max(1, Math.ceil(total / PER_PAGE));
  if(page<1) page=1;
  if(page>totalPages) page=totalPages;
  pageActual = page;

  const start = (page-1)*PER_PAGE;
  const slice = dataGlobal.slice(start, start+PER_PAGE);
  grid.innerHTML = slice.map(tarjeta).join('');

  infoPag.textContent = `Página ${page} de ${totalPages}`;
  let html = '';
  for(let p=1;p<=totalPages;p++){
    html += `<span class="page-link ${p===page?'act':''}" onclick="renderPage(${p})">${p}</span>`;
  }
  pages.innerHTML = html;
  paginador.style.display = total ? 'flex' : 'none';
}

btnPrev.onclick = ()=>renderPage(pageActual-1);
btnNext.onclick = ()=>renderPage(pageActual+1);
btnBuscar.onclick = buscar;
btnLimpiar.onclick = limpiar;

// -------- Tabs modal detalle --------
function activarTab(tab){
  const btns = document.querySelectorAll('.ap-tab-btn');
  const panes = document.querySelectorAll('.ap-tab-pane');
  btns.forEach(b=>b.classList.toggle('active', b.dataset.tab === tab));
  panes.forEach(p=>p.classList.toggle('active', p.dataset.tab === tab));
}
window.activarTab = activarTab;

// -------- Aux para fotos --------
function galleryImages(p){
  const arr = [];
  if(p.ecommerce_img_principal) arr.push(imgUrl(p.ecommerce_img_principal));
  if(p.ecommerce_img_galeria){
    String(p.ecommerce_img_galeria).split(',').forEach(t=>{
      const s = t.trim();
      if(s) arr.push(imgUrl(s));
    });
  }
  if(!arr.length) arr.push('https://via.placeholder.com/640x480?text=Producto');
  return arr;
}

// -------- Detalle producto / ficha --------
function getProduct(id){
  return dataGlobal.find(p=>Number(p.id)===Number(id));
}
let prodSel = null;

function verDetalle(id){
  const p = getProduct(id);
  if(!p) return;
  prodSel = p;

  const imgs = galleryImages(p);

  detTitulo.textContent = p.des_articulo;
  detSku.textContent    = 'Clave: ' + p.cve_articulo;
  detImg.src            = imgs[0];
  detPrecio.textContent = 'Precio: $ ' + Number(p.PrecioVenta ?? 0).toFixed(2);
  detCategoria.textContent = 'Categoría: ' + (p.ecommerce_categoria || 'N/D') +
                             (p.ecommerce_subcategoria ? (' / ' + p.ecommerce_subcategoria) : '');

  if(p.des_detallada && String(p.des_detallada).trim() !== ''){
    detInfo.innerHTML = p.des_detallada;
  } else {
    detInfo.innerHTML = '<em>Sin ficha técnica capturada.</em>';
  }

  const tags = (p.ecommerce_tags || '').split(',').map(t=>t.trim()).filter(Boolean);
  detTags.innerHTML = tags.length
    ? tags.map(t=>`<span class="tag">${t}</span>`).join(' ')
    : '<span style="color:#999;">Sin tags definidos.</span>';

  // Especificaciones: placeholder para futura integración SAP / ERP
  detSpec.innerHTML = `
    <p>Esta sección está pensada para mostrar atributos técnicos provenientes de SAP B1 u otro ERP (por ejemplo: medidas, peso, familia, grupo, clasificación, códigos de barras adicionales, etc.).</p>
    <p>Por ahora la ficha técnica detallada está disponible en la pestaña <strong>"Ficha técnica"</strong>.</p>
  `;

  detFotos.innerHTML = imgs.map(src=>`<img src="${src}" alt="">`).join('');

  detAgregar.onclick = ()=>{
    addToCart(p.id);
    cerrarDetalle();
    verCarritoDetalle();
  };

  activarTab('info');
  detalleModal.style.display = 'flex';
}

function cerrarDetalle(){
  detalleModal.style.display = 'none';
}

// -------- Carrito --------
function persistCart(){ localStorage.setItem('ap_cart', JSON.stringify(cart)); }

function addToCart(id){
  const p = getProduct(id);
  if(!p) return;
  let item = cart.find(i=>i.id===id);
  if(item) item.cantidad++;
  else cart.push({
    id:p.id,
    cve_articulo:p.cve_articulo,
    des_articulo:p.des_articulo,
    precio:Number(p.PrecioVenta ?? 0),
    cantidad:1
  });
  updateCartUI();
  persistCart();
}

function updateCartUI(){
  let totalItems=0,totalImporte=0;
  cart.forEach(i=>{ totalItems+=i.cantidad; totalImporte+=i.cantidad*i.precio; });
  cartItems.textContent = totalItems;
  cartTotal.textContent = totalImporte.toFixed(2);
  cartPanel.style.display = totalItems ? 'flex' : 'none';
}

function verCarritoDetalle(){
  if(!cart.length){ alert('Carrito vacío'); return; }
  let tbody='', total=0;
  cart.forEach(it=>{
    const sub = it.cantidad*it.precio;
    total += sub;
    tbody += `<tr>
      <td>${it.cve_articulo}</td>
      <td>${it.des_articulo}</td>
      <td>$ ${it.precio.toFixed(2)}</td>
      <td><input type="number" min="1" value="${it.cantidad}"
                 style="width:50px;font-size:10px"
                 onchange="cambiarCantidad(${it.id},this.value)"></td>
      <td>$ ${sub.toFixed(2)}</td>
      <td><button class="btn btn-light" onclick="eliminarItem(${it.id})">X</button></td>
    </tr>`;
  });
  cartBody.innerHTML = tbody;
  cartModalTotal.textContent = total.toFixed(2);
  cartModal.style.display = 'flex';
}

function cerrarCart(){ cartModal.style.display='none'; }

function cambiarCantidad(id,val){
  const qty = parseInt(val,10);
  const item = cart.find(i=>i.id===id);
  if(!item) return;
  if(!qty || qty<=0) cart = cart.filter(i=>i.id!==id);
  else item.cantidad = qty;
  updateCartUI();
  persistCart();
  if(cart.length) verCarritoDetalle(); else cerrarCart();
}

function eliminarItem(id){
  cart = cart.filter(i=>i.id!==id);
  updateCartUI();
  persistCart();
  cart.length ? verCarritoDetalle() : cerrarCart();
}

// Confirmar pedido → API + copia en localStorage
btnConfirmar.onclick = async ()=>{
  if(!cart.length){ alert('Carrito vacío'); return; }
  if(!confirm('¿Confirmar pedido?')) return;

  const resp = await fetch(API_PEDIDO, {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({items:cart})
  });
  const js = await resp.json();
  if(!js.ok){
    alert(js.error || 'Error al guardar pedido');
    return;
  }

  const orders = JSON.parse(localStorage.getItem('ap_orders') || '[]');
  const total  = cart.reduce((s,i)=>s+i.cantidad*i.precio,0);
  const pedido = {
    id: (js.pedido_id ?? Date.now()),
    fecha: new Date().toISOString(),
    total: total,
    items: cart
  };
  orders.unshift(pedido);
  localStorage.setItem('ap_orders', JSON.stringify(orders));

  alert('Pedido generado.\nID: '+pedido.id+'\nTotal: $ '+total.toFixed(2));
  cart = [];
  persistCart();
  updateCartUI();
  cerrarCart();
};

// -------- Comparador --------
function toggleComparador(id,checked){
  id = Number(id);
  if(checked){
    if(!comparador.includes(id)) comparador.push(id);
  } else {
    comparador = comparador.filter(x=>x!==id);
  }
  updateComparadorUI();
}

function updateComparadorUI(){
  const n = comparador.length;
  if(n){
    compareBar.style.display='flex';
    compareCount.textContent = n + ' artículo' + (n!==1?'s':'') + ' para comparar';
  } else {
    compareBar.style.display='none';
  }
}

function limpiarComparador(){
  comparador = [];
  updateComparadorUI();
  document.querySelectorAll('.cmp-check').forEach(ch=>ch.checked=false);
}

function verComparador(){
  if(!comparador.length){ alert('Seleccione al menos un artículo para comparar'); return; }
  const prods = comparador.map(id=>getProduct(id)).filter(Boolean);
  if(!prods.length){ alert('No hay productos para comparar'); return; }

  let html = '<table style="width:100%;border-collapse:collapse;font-size:10px">';
  html += '<tr><th style="border-bottom:1px solid #eee;padding:4px 6px;">Campo</th>';
  prods.forEach(p=>{
     html += `<th style="border-bottom:1px solid #eee;padding:4px 6px;">${p.cve_articulo}</th>`;
  });
  html += '</tr>';

  function row(label,fn){
     html += `<tr><td style="border-bottom:1px solid #eee;padding:3px 6px;font-weight:bold;">${label}</td>`;
     prods.forEach(p=>{
        html += `<td style="border-bottom:1px solid #eee;padding:3px 6px;">${fn(p)}</td>`;
     });
     html += '</tr>';
  }

  row('Descripción', p=>p.des_articulo);
  row('Precio', p=>'$ '+Number(p.PrecioVenta ?? 0).toFixed(2));
  row('Categoría', p=>p.ecommerce_categoria || '');
  row('Subcategoría', p=>p.ecommerce_subcategoria || '');
  row('Tags', p=>(p.ecommerce_tags || '').replace(/,/g, ', '));

  html += '</table>';
  compareContent.innerHTML = html;
  compareModal.style.display='flex';
}

function cerrarComparador(){
  compareModal.style.display='none';
}

// arranque
window.addEventListener('DOMContentLoaded', ()=>{
  cargarBanners();
  buscar();
  updateCartUI();
  updateComparadorUI();
});

btnVerCarrito.onclick = verCarritoDetalle;
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
