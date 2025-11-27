<?php
// /public/portal_clientes/catalogo.php
require_once __DIR__ . '/../bi/_menu_global.php';
require_once __DIR__ . '/../../app/db.php'; // por si deseas usar combos desde BD

// Opcional: obtener categorías distintas para el combo
$categorias = db_all("SELECT DISTINCT ecommerce_categoria AS cat FROM v_ecommerce_articulos WHERE ecommerce_categoria IS NOT NULL AND ecommerce_categoria <> '' ORDER BY 1;");
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Catálogo E-Commerce</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  /* Estilo corporativo base - 10px, tarjetas, una sola fila con scroll si se requiere */
  body { font-family: Arial, sans-serif; font-size: 10px; }
  .container { padding: 12px; }
  .filtros { display:flex; gap:8px; align-items:center; margin-bottom: 10px; }
  .filtros input, .filtros select { font-size:10px; padding:6px; }
  .grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap:10px; }
  .card { border:1px solid #e1e5ee; border-radius:10px; padding:10px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
  .card h4 { margin:6px 0 4px; font-size:12px; color:#0F5AAD; }
  .card .sku { color:#666; font-size:10px; }
  .card .precio { font-weight:bold; margin-top:4px; }
  .card img { width:100%; height:160px; object-fit:cover; border-radius:8px; background:#f5f7fb; }
  .acciones { display:flex; gap:6px; margin-top:8px; }
  .btn { border:1px solid #0F5AAD; color:#0F5AAD; background:#fff; border-radius:8px; padding:6px 8px; font-size:10px; cursor:pointer; }
  .btn:hover { background:#0F5AAD; color:#fff; }
  .header { color:#0F5AAD; font-size:14px; margin:0 0 12px; display:flex; align-items:center; gap:8px; }
  .badge { background:#e8f0fe; color:#0F5AAD; padding:2px 6px; border-radius:6px; font-size:9px; }
  /* Modal */
  .modal-backdrop { position:fixed; inset:0; background:rgba(0,0,0,.25); display:none; align-items:center; justify-content:center; }
  .modal { background:#fff; width:720px; max-width:95vw; border-radius:12px; padding:14px; box-shadow:0 10px 25px rgba(0,0,0,.25); }
  .modal h3 { margin:0 0 8px; color:#0F5AAD; }
  .modal .close { float:right; cursor:pointer; }
  @media (max-width:768px){
    .grid { grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); }
    .card img { height:120px; }
  }
</style>
</head>
<body>
<div class="container">
  <h2 class="header">Catálogo E-Commerce <span id="total" class="badge">0</span></h2>
  <div class="filtros">
    <input type="text" id="q" placeholder="Buscar (SKU, descripción, tags)">
    <select id="categoria">
      <option value="">Todas las categorías</option>
      <?php foreach ($categorias as $c): ?>
        <option value="<?= htmlspecialchars($c['cat']) ?>"><?= htmlspecialchars($c['cat']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn" id="btnBuscar">Buscar</button>
    <button class="btn" id="btnLimpiar">Limpiar</button>
  </div>

  <div id="grid" class="grid"></div>
</div>

<!-- Modal Detalle -->
<div id="dlg" class="modal-backdrop">
  <div class="modal">
    <span class="close btn" onclick="cerrar()">Cerrar</span>
    <h3 id="m_titulo">Detalle</h3>
    <div style="display:flex; gap:12px; flex-wrap:wrap;">
      <img id="m_img" src="" alt="" style="width:260px; height:200px; object-fit:cover; border-radius:8px;">
      <div style="flex:1; min-width:200px;">
        <div><b>SKU:</b> <span id="m_sku"></span></div>
        <div><b>Categoría:</b> <span id="m_cat"></span></div>
        <div><b>Subcategoría:</b> <span id="m_sub"></span></div>
        <div class="precio"><b>Precio:</b> $<span id="m_precio"></span></div>
        <div><b>Tags:</b> <span id="m_tags"></span></div>
      </div>
    </div>
    <div style="margin-top:10px;">
      <b>Galería:</b>
      <div id="m_galeria" style="display:flex; gap:8px; flex-wrap:wrap; margin-top:6px;"></div>
    </div>
  </div>
</div>

<script>
const API = '../../public/api/ecommerce_articulos.php';

function parseGaleria(s) {
  if (!s) return [];
  try {
    // Permite JSON ["url1","url2"] o CSV "url1,url2"
    if (s.trim().startsWith('[')) return JSON.parse(s);
  } catch(e){}
  return s.split(',').map(x => x.trim()).filter(Boolean);
}

function tarjeta(p) {
  const img = p.ecommerce_img_principal || '';
  const precio = (p.PrecioVenta ?? 0).toFixed(2);
  return `
    <div class="card">
      <img src="${img}" onerror="this.src='https://via.placeholder.com/640x480?text=Sin+Imagen'">
      <h4 title="${p.des_articulo}">${p.des_articulo}</h4>
      <div class="sku">${p.cve_articulo}</div>
      <div class="precio">$ ${precio}</div>
      <div style="margin-top:4px; color:#666;">
        ${p.ecommerce_categoria ?? ''} ${p.ecommerce_subcategoria ? ' / ' + p.ecommerce_subcategoria : ''}
      </div>
      <div class="acciones">
        <button class="btn" onclick='verDetalle(${JSON.stringify(p)})'>Ver detalle</button>
        <!-- futuro: Agregar al carrito -->
      </div>
    </div>
  `;
}

function render(data) {
  const grid = document.getElementById('grid');
  grid.innerHTML = data.map(tarjeta).join('');
  document.getElementById('total').textContent = data.length;
}

async function buscar() {
  const q = document.getElementById('q').value.trim();
  const categoria = document.getElementById('categoria').value.trim();
  const params = new URLSearchParams({ action:'list' });
  if (q) params.append('q', q);
  if (categoria) params.append('categoria', categoria);

  const res = await fetch(`${API}?${params.toString()}`);
  const js = await res.json();
  if (js.ok) render(js.data || []); else render([]);
}

function limpiar() {
  document.getElementById('q').value = '';
  document.getElementById('categoria').value = '';
  buscar();
}

function verDetalle(p) {
  document.getElementById('m_titulo').textContent = p.des_articulo || '';
  document.getElementById('m_sku').textContent = p.cve_articulo || '';
  document.getElementById('m_cat').textContent = p.ecommerce_categoria || '';
  document.getElementById('m_sub').textContent = p.ecommerce_subcategoria || '';
  document.getElementById('m_precio').textContent = (p.PrecioVenta ?? 0).toFixed(2);
  document.getElementById('m_tags').textContent = p.ecommerce_tags || '';

  const img = p.ecommerce_img_principal || '';
  const gal = parseGaleria(p.ecommerce_img_galeria || '');
  document.getElementById('m_img').src = img || 'https://via.placeholder.com/640x480?text=Sin+Imagen';

  const box = document.getElementById('m_galeria');
  box.innerHTML = gal.map(u => `<img src="${u}" style="width:110px;height:90px;object-fit:cover;border-radius:6px;" onerror="this.src='https://via.placeholder.com/110x90?text=No+Img'">`).join('');

  document.getElementById('dlg').style.display = 'flex';
}
function cerrar(){ document.getElementById('dlg').style.display = 'none'; }

document.getElementById('btnBuscar').addEventListener('click', buscar);
document.getElementById('btnLimpiar').addEventListener('click', limpiar);
window.addEventListener('DOMContentLoaded', buscar);
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
