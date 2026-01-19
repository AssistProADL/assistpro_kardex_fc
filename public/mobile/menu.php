<?php
// /public/mobile/menu.php
?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>AssistPro ER · Menú</title>

  <!-- CSS mobile -->
  <link rel="stylesheet" href="css/rf.css?v=1">

  <!-- Font Awesome v6 (si ya lo tienes global, puedes quitarlo) -->
  <link rel="stylesheet" href="../bi/assets/fontawesome6/css/all.min.css">

  <style>
    /* Ajuste ultra mobile-first (480x800) */
    :root{
      --ap-blue:#000f9f;   /* azul corporativo */
      --ap-blue-2:#0016c9;
      --ap-bg:#f4f6fb;
      --ap-text:#0e1630;
      --ap-muted:#6b778c;
      --ap-card:#ffffff;
      --ap-shadow: 0 10px 26px rgba(12, 23, 54, .10);
      --ap-radius: 18px;
    }

    html,body{height:100%}
    body{
      margin:0;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      background: var(--ap-bg);
      color: var(--ap-text);
    }

    .wrap{
      min-height:100%;
      display:flex;
      align-items:flex-start;
      justify-content:center;
      padding: 16px 10px 18px;
    }

    .card{
      width:100%;
      max-width: 380px; /* <- CLAVE: ancho real para 4–5" */
      background: var(--ap-card);
      border-radius: var(--ap-radius);
      box-shadow: var(--ap-shadow);
      padding: 14px 14px 12px;
    }

    .head{
      display:flex;
      align-items:center;
      gap: 12px;
      margin-bottom: 10px;
    }

    .logoBox{
      width: 64px;  /* ~4x vs 16px-20px anterior */
      height: 64px;
      border-radius: 14px;
      background: #fff;
      display:flex;
      align-items:center;
      justify-content:center;
      box-shadow: 0 6px 16px rgba(12,23,54,.08);
      flex: 0 0 auto;
      overflow:hidden;
    }
    .logoBox img{
      width: 56px;
      height: 56px;
      object-fit: contain;
      display:block;
    }

    .titleBox{flex:1}
    .brand{
      font-weight: 900;
      letter-spacing: .2px;
      margin: 0;
      font-size: 18px;
      line-height: 1.1;
    }
    .sub{
      margin: 2px 0 0;
      font-size: 12px;
      color: var(--ap-muted);
    }

    .pill{
      margin-left:auto;
      font-size: 12px;
      font-weight: 800;
      padding: 7px 10px;
      border-radius: 999px;
      background: rgba(0,15,159,.08);
      color: var(--ap-blue);
      white-space: nowrap;
    }

    .heroBtn{
      width:100%;
      border:0;
      background: linear-gradient(135deg, var(--ap-blue), var(--ap-blue-2));
      color:#fff;
      border-radius: 14px;
      padding: 12px 12px;
      font-weight: 900;
      letter-spacing:.3px;
      display:flex;
      align-items:center;
      justify-content:center;
      gap:10px;
      cursor:pointer;
      margin: 10px 0 12px;
      box-shadow: 0 10px 22px rgba(0,15,159,.18);
      text-decoration:none;
      user-select:none;
      font-size: 14px;
    }
    .heroBtn i{opacity:.95}

    .grid{
      display:grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-bottom: 12px;
    }

    .btn{
      border:0;
      border-radius: 14px;
      padding: 11px 10px;
      background: var(--ap-blue);
      color:#fff;
      font-weight: 900;
      letter-spacing:.2px;
      cursor:pointer;
      display:flex;
      align-items:center;
      justify-content:center;
      gap:10px;
      text-decoration:none;
      box-shadow: 0 8px 18px rgba(0,15,159,.16);
      font-size: 13px;
      line-height: 1;
      min-height: 44px; /* accesible */
    }
    .btn i{font-size: 15px}

    .btn.secondary{
      background: #0b1220;
      box-shadow: 0 8px 18px rgba(11,18,32,.18);
    }

    .btn.wide{
      grid-column: 1 / -1;
    }

    .footer{
      display:flex;
      align-items:center;
      justify-content:center;
      gap:6px;
      font-size: 11px;
      color: var(--ap-muted);
      padding-top: 6px;
    }
    .footer b{color:#2b3b63}

    /* Anti-scroll horizontal en pantallas chicas */
    *{box-sizing:border-box}
  </style>
</head>

<body>
  <div class="wrap">
    <div class="card">

      <div class="head">
        <div class="logoBox">
          <img src="../assets/logo/assistpro-er.svg" alt="AssistPro ER">
        </div>

        <div class="titleBox">
          <p class="brand">AssistPRO · ER</p>
          <p class="sub">Acceso operativo <span id="uLabel"></span></p>
        </div>

        <div class="pill" id="almPill">ALM: —</div>
      </div>

      <!-- CTA principal: Consultas (módulo más rápido para dar valor) -->
      <a class="heroBtn" href="consultas/index.php" id="btnConsultas">
        <i class="fa-solid fa-magnifying-glass"></i>
        <span>CONSULTAS · STOCK</span>
      </a>

      <!-- Menú operativo -->
      <div class="grid">
        <a class="btn" href="recepcion/index.php">
          <i class="fa-solid fa-dolly"></i>
          <span>1 RECEPCIÓN</span>
        </a>

        <a class="btn" href="acomodo/index.php">
          <i class="fa-solid fa-warehouse"></i>
          <span>2 ACOMODO</span>
        </a>

        <a class="btn" href="traslados/index.php">
          <i class="fa-solid fa-right-left"></i>
          <span>3 TRASLADOS</span>
        </a>

        <a class="btn" href="palletizacion/index.php">
          <i class="fa-solid fa-layer-group"></i>
          <span>4 PALLETIZACIÓN</span>
        </a>

        <a class="btn" href="picking/index.php">
          <i class="fa-solid fa-box-open"></i>
          <span>5 PICKING</span>
        </a>

        <a class="btn" href="inventarios/index.php">
          <i class="fa-solid fa-clipboard-check"></i>
          <span>6 INVENTARIOS</span>
        </a>

        <a class="btn wide" href="consultas/index.php">
          <i class="fa-solid fa-magnifying-glass"></i>
          <span>7 CONSULTAS</span>
        </a>

        <button class="btn secondary wide" id="btnLogout" type="button">
          <i class="fa-solid fa-right-from-bracket"></i>
          <span>CERRAR SESIÓN</span>
        </button>
      </div>

      <div class="footer">
        <span>Powered by</span> <b>Adventech Logística</b>
      </div>

    </div>
  </div>

  <script>
    (function(){
      // Sin sesiones: controlamos el “estado” por localStorage
      const user = localStorage.getItem('mobile_user') || '';
      const alm  = localStorage.getItem('mobile_almacen') || '';
      const almName = localStorage.getItem('mobile_almacen_nombre') || '';

      // Si no hay login operativo, fuera (y siempre a mobile)
      if(!user || !alm){
        window.location.href = 'index.html';
        return;
      }

      // UI labels
      const uLabel = document.getElementById('uLabel');
      const almPill = document.getElementById('almPill');

      uLabel.textContent = '· Usuario: ' + user;

      let almTxt = almName ? almName : alm;
      // Si almName viene "WH5" y alm también, evitamos duplicados
      almPill.textContent = 'ALM: ' + almTxt;

      // Logout
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
