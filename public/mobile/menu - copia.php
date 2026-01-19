<?php
// /public/mobile/menu.php
// Menú móvil standalone (sin sesiones, usa localStorage)
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>AssistPro ER · Menú</title>

  <!-- Font Awesome (si ya lo tienes cargado globalmente en el web no importa, aquí lo pedimos directo) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- Tu CSS móvil -->
  <link rel="stylesheet" href="css/rf.css?v=<?=time()?>">

  <style>
    /* Hardening para handhelds */
    :root{ --maxw: 420px; }
    body{
      margin:0;
      background:#f3f5f9;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      -webkit-text-size-adjust: 100%;
      text-size-adjust: 100%;
    }

    .page{
      min-height: 100dvh;
      display:flex;
      align-items:flex-start;
      justify-content:center;
      padding: 14px 10px 22px;
    }

    .card{
      width:100%;
      max-width: var(--maxw);
      background:#fff;
      border-radius: 16px;
      box-shadow: 0 12px 26px rgba(16, 24, 40, .10);
      border: 1px solid rgba(0,0,0,.06);
      overflow:hidden;
    }

    .header{
      display:flex;
      align-items:center;
      gap:12px;
      padding: 14px 14px 10px;
    }

    .logoWrap{
      width: 64px;
      height: 64px;
      display:flex;
      align-items:center;
      justify-content:center;
      border-radius: 14px;
      border: 1px solid rgba(0,0,0,.08);
      background:#fff;
      flex: 0 0 auto;
      overflow:hidden;
    }
    /* LOGO x4 aprox (comparado con el mini que tenías) */
    .logoWrap img{
      height: 72px;           /* <= aquí crece el logo */
      width: auto;
      display:block;
    }

    .headText{
      flex:1;
      min-width:0;
    }

    .title{
      font-weight:800;
      letter-spacing:.2px;
      margin:0;
      font-size: 18px;
      line-height: 1.1;
    }
    .subtitle{
      margin:4px 0 0;
      font-size: 12px;
      color:#667085;
    }

    .badge{
      margin-left:auto;
      background: rgba(0, 40, 160, .08);
      color:#0028A0;
      border: 1px solid rgba(0, 40, 160, .15);
      padding: 6px 10px;
      border-radius: 999px;
      font-weight: 800;
      font-size: 12px;
      white-space:nowrap;
      flex: 0 0 auto;
    }

    .body{
      padding: 10px 14px 14px;
    }

    .grid{
      display:grid;
      grid-template-columns: 1fr; /* móvil: una columna */
      gap:10px;
    }

    /* Si hay espacio (desktop/tablet) permite 2 columnas, pero NO en handheld pequeño */
    @media (min-width: 520px){
      .grid{ grid-template-columns: 1fr 1fr; }
      :root{ --maxw: 460px; }
    }

    .btn{
      appearance:none;
      border:0;
      width:100%;
      cursor:pointer;
      border-radius: 14px;
      padding: 14px 14px;
      font-weight: 900;
      letter-spacing:.3px;
      display:flex;
      align-items:center;
      justify-content:center;
      gap:10px;
      text-decoration:none;
      user-select:none;
      -webkit-tap-highlight-color: transparent;
    }
    .btn-primary{
      background:#0017A8; /* tono corporativo tipo lateral */
      color:#fff;
      box-shadow: 0 10px 20px rgba(0, 23, 168, .18);
    }
    .btn-primary:active{ transform: translateY(1px); }

    .btn-outline{
      background:#fff;
      color:#0017A8;
      border: 2px solid rgba(0, 23, 168, .25);
    }

    .btn-black{
      background:#111827;
      color:#fff;
    }

    .btn-wide{
      grid-column: 1 / -1; /* ocupa todo */
    }

    .footer{
      padding: 10px 14px 14px;
      text-align:center;
      font-size: 11px;
      color:#667085;
    }
    .footer b{ color:#1f2937; }

    /* Ajustes ultra chicos */
    @media (max-width: 420px){
      :root{ --maxw: 380px; }
      .logoWrap{ width:56px; height:56px; }
      .logoWrap img{ height: 64px; }
      .title{ font-size: 17px; }
    }
    @media (max-width: 360px){
      :root{ --maxw: 340px; }
      .btn{ padding: 12px 12px; }
      .badge{ font-size: 11px; }
    }
  </style>
</head>

<body>
  <div class="page">
    <div class="card">
      <div class="header">
        <div class="logoWrap" title="AssistPro ER">
          <!-- Ajusta ruta si tu logo está en otro lugar -->
          <img src="../assets/logo/assistpro-er.svg" alt="AssistPro ER">
        </div>

        <div class="headText">
          <h1 class="title">ASSISTPRO · ER</h1>
          <p class="subtitle">Usuario: <b id="lblUser">—</b></p>
        </div>

        <div class="badge" id="lblAlm">ALM: —</div>
      </div>

      <div class="body">
        <!-- Botón destacado: Consultas / Stock -->
        <a class="btn btn-primary btn-wide" href="stock.php">
          <i class="fa-solid fa-magnifying-glass"></i>
          CONSULTAS · STOCK
        </a>

        <div style="height:10px"></div>

        <div class="grid">
          <a class="btn btn-primary" href="recepcion.php">
            <i class="fa-solid fa-truck-ramp-box"></i>
            1 RECEPCIÓN
          </a>

          <a class="btn btn-primary" href="acomodo.php">
            <i class="fa-solid fa-warehouse"></i>
            2 ACOMODO
          </a>

          <a class="btn btn-primary" href="traslados.php">
            <i class="fa-solid fa-right-left"></i>
            3 TRASLADOS
          </a>

          <a class="btn btn-primary" href="palletizacion.php">
            <i class="fa-solid fa-layer-group"></i>
            4 PALLETIZACIÓN
          </a>

          <a class="btn btn-primary" href="picking.php">
            <i class="fa-solid fa-box"></i>
            5 PICKING
          </a>

          <a class="btn btn-primary" href="inventarios.php">
            <i class="fa-solid fa-clipboard-check"></i>
            6 INVENTARIOS
          </a>

          <a class="btn btn-primary" href="consultas.php">
            <i class="fa-solid fa-magnifying-glass"></i>
            7 CONSULTAS
          </a>

          <button class="btn btn-black btn-wide" id="btnLogout" type="button">
            <i class="fa-solid fa-right-from-bracket"></i>
            CERRAR SESIÓN
          </button>
        </div>
      </div>

      <div class="footer">
        Powered by <b>Adventech Logística</b>
      </div>
    </div>
  </div>

<script>
(function(){
  // 1) Validación de “sesión” móvil via localStorage
  const user = localStorage.getItem('mobile_user') || '';
  const alm  = localStorage.getItem('mobile_almacen') || '';
  const almNom = localStorage.getItem('mobile_almacen_nombre') || '';

  if(!user || !alm){
    // si entran directo a menu.php sin login
    window.location.href = 'index.html';
    return;
  }

  // 2) Pintar encabezados
  document.getElementById('lblUser').textContent = user;
  document.getElementById('lblAlm').textContent = 'ALM: ' + (almNom ? almNom : alm);

  // 3) Logout
  document.getElementById('btnLogout').addEventListener('click', function(){
    localStorage.removeItem('mobile_user');
    localStorage.removeItem('mobile_almacen');
    localStorage.removeItem('mobile_almacen_nombre');
    window.location.href = 'index.html';
  });
})();
</script>
</body>
</html>
