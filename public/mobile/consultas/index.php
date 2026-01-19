<?php
?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AssistPro ER · Consultas</title>
  <link rel="stylesheet" href="../css/rf.css?v=1">
  <link rel="stylesheet" href="../bi/assets/fontawesome6/css/all.min.css">
  <style>
    body{margin:0;background:#f3f5f9;font-family:system-ui,Segoe UI,Roboto,Arial}
    .wrap{min-height:100dvh;display:flex;justify-content:center;padding:14px 10px}
    .card{width:100%;max-width:380px;background:#fff;border-radius:16px;box-shadow:0 12px 26px rgba(16,24,40,.10);padding:14px}
    .top{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
    .ttl{font-weight:900;font-size:16px}
    .pill{font-size:12px;font-weight:800;padding:6px 10px;border-radius:999px;background:rgba(0,15,159,.08);color:#000f9f}
    .btn{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:14px;border-radius:14px;border:0;background:#000f9f;color:#fff;font-weight:900;text-decoration:none;margin:10px 0}
    .btn.ghost{background:#111827}
    .mini{font-size:11px;color:#64748b;text-align:center;margin-top:8px}
  </style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <div class="top">
      <div class="ttl"><i class="fa-solid fa-magnifying-glass"></i> Consultas</div>
      <div class="pill" id="alm">ALM: —</div>
    </div>

    <a class="btn" href="producto.php"><i class="fa-solid fa-barcode"></i> Producto</a>

    <a class="btn ghost" href="../menu.php"><i class="fa-solid fa-house"></i> Menú</a>
    <div class="mini">AssistPro ER · Powered by Adventech Logística</div>
  </div>
</div>

<script>
(() => {
  const u = localStorage.getItem('mobile_user') || '';
  const a = localStorage.getItem('mobile_almacen') || '';
  const an = localStorage.getItem('mobile_almacen_nombre') || a;
  if(!u || !a){ location.href = '../index.html'; return; }
  document.getElementById('alm').textContent = 'ALM: ' + (an || a);
})();
</script>
</body>
</html>
