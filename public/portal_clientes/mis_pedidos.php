<?php require_once __DIR__ . '/../bi/_menu_global.php'; ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Mis pedidos</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{font-family:Arial,sans-serif;font-size:10px;background:#f2f4f8;margin:0}
.wrap{padding:14px 18px;margin-left:260px;max-width:1100px}
h1{font-size:16px;margin:0 0 10px;color:#0F5AAD}
table{width:100%;border-collapse:collapse;background:#fff;border-radius:12px;overflow:hidden}
th,td{padding:6px 8px;border-bottom:1px solid #eee;text-align:left}
th{background:#f5f7fb}
.btn{font-size:10px;padding:4px 8px;border:1px solid #0F5AAD;border-radius:6px;background:#0F5AAD;color:#fff;cursor:pointer}
.btn.btn-light{background:#fff;color:#0F5AAD}
.modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.4);display:none;align-items:center;justify-content:center;z-index:1000}
.modal{background:#fff;padding:14px;width:95%;max-width:720px;border-radius:12px;max-height:90vh;overflow:auto}
.modal .close{float:right;cursor:pointer;font-size:12px}
</style>
</head>
<body>
<div class="wrap">
  <h1>Mis pedidos</h1>
  <table>
    <thead>
      <tr><th>ID</th><th>Fecha</th><th>Artículos</th><th>Total</th><th>Acciones</th></tr>
    </thead>
    <tbody id="rows"></tbody>
  </table>
</div>

<div id="detalle" class="modal-backdrop">
  <div class="modal">
    <span class="close" onclick="cerrar()">✕</span>
    <h2 id="t"></h2>
    <table style="width:100%;border-collapse:collapse;margin-top:8px">
      <thead><tr><th>Clave</th><th>Descripción</th><th>Precio</th><th>Cant.</th><th>Subtotal</th></tr></thead>
      <tbody id="d"></tbody>
    </table>
    <div style="text-align:right;margin-top:8px">Total: $<span id="tot"></span></div>
  </div>
</div>

<script>
function fmt(d){const x=new Date(d);return x.toLocaleString();}
function load(){
  const orders=JSON.parse(localStorage.getItem('ap_orders')||'[]');
  const rows=document.getElementById('rows');
  if(!orders.length){
    rows.innerHTML='<tr><td colspan="5" style="color:#888">Sin pedidos.</td></tr>';
    return;
  }
  rows.innerHTML = orders.map(o=>{
    const n=o.items.reduce((s,i)=>s+i.cantidad,0);
    const oJson = JSON.stringify(o).replace(/'/g,"&#39;");
    return `<tr>
      <td>${o.id}</td>
      <td>${fmt(o.fecha)}</td>
      <td>${n}</td>
      <td>$ ${Number(o.total).toFixed(2)}</td>
      <td>
        <button class="btn btn-light" onclick='ver(${oJson})'>Ver</button>
        <a class="btn" href="imprimir_pedido.php?id=${o.id}" target="_blank">Imprimir</a>
      </td>
    </tr>`;
  }).join('');
}
function ver(o){
  document.getElementById('t').textContent='Pedido '+o.id+' — '+fmt(o.fecha);
  let tot=0;
  document.getElementById('d').innerHTML = o.items.map(it=>{
    const sub=it.cantidad*it.precio; tot+=sub;
    return `<tr><td>${it.cve_articulo}</td><td>${it.des_articulo}</td><td>$ ${it.precio.toFixed(2)}</td><td>${it.cantidad}</td><td>$ ${sub.toFixed(2)}</td></tr>`;
  }).join('');
  document.getElementById('tot').textContent = tot.toFixed(2);
  document.getElementById('detalle').style.display='flex';
}
function cerrar(){ document.getElementById('detalle').style.display='none'; }
window.addEventListener('DOMContentLoaded', load);
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
