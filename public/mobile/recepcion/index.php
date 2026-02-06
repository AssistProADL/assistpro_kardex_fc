<?php
// /public/mobile/recepcion/index.php
?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>AssistPro ER · Recepción</title>

  <!-- CSS mobile (misma convención que tu menú) -->
  <link rel="stylesheet" href="../css/rf.css?v=1">

  <!-- Font Awesome v6 (igual que tu menú) -->
  <link rel="stylesheet" href="../../bi/assets/fontawesome6/css/all.min.css">

  <style>
    :root{
      --ap-blue:#000f9f;
      --ap-blue-2:#0016c9;
      --ap-bg:#f4f6fb;
      --ap-text:#0e1630;
      --ap-muted:#6b778c;
      --ap-card:#ffffff;
      --ap-shadow: 0 10px 26px rgba(12, 23, 54, .10);
      --ap-radius: 18px;
      --ok:#12b76a;
      --warn:#f79009;
      --danger:#f04438;
    }

    html,body{height:100%}
    body{
      margin:0;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      background: var(--ap-bg);
      color: var(--ap-text);
    }
    *{box-sizing:border-box}

    .wrap{
      min-height:100%;
      display:flex;
      align-items:flex-start;
      justify-content:center;
      padding: 16px 10px 18px;
    }
    .card{
      width:100%;
      max-width: 420px;
      background: var(--ap-card);
      border-radius: var(--ap-radius);
      box-shadow: var(--ap-shadow);
      padding: 14px 14px 12px;
    }

    .topbar{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      margin-bottom: 10px;
    }

    .backBtn{
      border:0;
      background:#fff;
      border-radius: 12px;
      padding: 10px 12px;
      box-shadow: 0 6px 16px rgba(12,23,54,.08);
      font-weight: 900;
      cursor:pointer;
      display:flex;
      align-items:center;
      gap:8px;
      color: var(--ap-text);
      text-decoration:none;
      user-select:none;
    }

    .pill{
      font-size: 12px;
      font-weight: 900;
      padding: 8px 10px;
      border-radius: 999px;
      background: rgba(0,15,159,.08);
      color: var(--ap-blue);
      white-space: nowrap;
    }

    .head{
      display:flex;
      align-items:center;
      gap: 12px;
      margin-bottom: 8px;
    }

    .iconBox{
      width: 54px;
      height: 54px;
      border-radius: 14px;
      background: linear-gradient(135deg, var(--ap-blue), var(--ap-blue-2));
      display:flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      box-shadow: 0 10px 22px rgba(0,15,159,.18);
      flex: 0 0 auto;
    }
    .iconBox i{font-size: 20px}

    .titleBox{flex:1}
    .brand{
      font-weight: 1000;
      letter-spacing: .2px;
      margin: 0;
      font-size: 18px;
      line-height: 1.15;
    }
    .sub{
      margin: 2px 0 0;
      font-size: 12px;
      color: var(--ap-muted);
    }

    .kpis{
      display:flex;
      gap:10px;
      margin: 10px 0 12px;
    }
    .kpi{
      flex:1;
      background:#fff;
      border-radius: 14px;
      padding: 10px 10px;
      box-shadow: 0 6px 16px rgba(12,23,54,.08);
      min-height: 56px;
    }
    .kpi .label{
      font-size: 11px;
      color: var(--ap-muted);
      font-weight: 800;
      margin-bottom: 4px;
    }
    .kpi .value{
      font-size: 14px;
      font-weight: 1000;
      letter-spacing: .2px;
      display:flex;
      align-items:center;
      gap:8px;
    }

    .heroBtn{
      width:100%;
      border:0;
      background: linear-gradient(135deg, var(--ap-blue), var(--ap-blue-2));
      color:#fff;
      border-radius: 14px;
      padding: 12px 12px;
      font-weight: 1000;
      letter-spacing:.3px;
      display:flex;
      align-items:center;
      justify-content:center;
      gap:10px;
      cursor:pointer;
      margin: 6px 0 12px;
      box-shadow: 0 10px 22px rgba(0,15,159,.18);
      text-decoration:none;
      user-select:none;
      font-size: 14px;
    }

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
      font-weight: 1000;
      letter-spacing:.2px;
      cursor:pointer;
      display:flex;
      align-items:center;
      justify-content:center;
      gap:10px;
      text-decoration:none;
      box-shadow: 0 8px 18px rgba(0,15,159,.16);
      font-size: 13px;
      line-height: 1.05;
      min-height: 46px;
      text-align:center;
    }
    .btn i{font-size: 15px; opacity:.95}

    .btn.secondary{
      background: #0b1220;
      box-shadow: 0 8px 18px rgba(11,18,32,.18);
    }

    .btn.wide{ grid-column: 1 / -1; }

    .note{
      font-size: 11px;
      color: var(--ap-muted);
      line-height: 1.35;
      background: rgba(0,0,0,.03);
      padding: 10px 10px;
      border-radius: 14px;
    }

    .footer{
      display:flex;
      align-items:center;
      justify-content:center;
      gap:6px;
      font-size: 11px;
      color: var(--ap-muted);
      padding-top: 8px;
    }
    .footer b{color:#2b3b63}
  </style>
</head>

<body>
  <div class="wrap">
    <div class="card">

      <div class="topbar">
        <a class="backBtn" href="../menu.php">
          <i class="fa-solid fa-chevron-left"></i>
          <span>Menú</span>
        </a>
        <div class="pill" id="almPill">ALM: —</div>
      </div>

      <div class="head">
        <div class="iconBox"><i class="fa-solid fa-dolly"></i></div>
        <div class="titleBox">
          <p class="brand">Recepción de Mercancía</p>
          <p class="sub">Captura operativa + trazabilidad en piso (mobile)</p>
        </div>
      </div>

      <div class="kpis">
        <div class="kpi">
          <div class="label">Usuario</div>
          <div class="value"><i class="fa-solid fa-user"></i> <span id="uLabel">—</span></div>
        </div>
        <div class="kpi">
          <div class="label">Almacén</div>
          <div class="value"><i class="fa-solid fa-warehouse"></i> <span id="aLabel">—</span></div>
        </div>
      </div>

      <!-- CTA principal (estratégico): recepción por OC -->
      <a class="heroBtn" href="recepcion_oc_mobile.php" id="btnOC">
        <i class="fa-solid fa-file-invoice"></i>
        <span>RECEPCIÓN POR OC</span>
      </a>

      <div class="grid">
        <a class="btn" href="libre.php">
          <i class="fa-solid fa-truck-ramp-box"></i>
          <span>Recepción<br>Libre</span>
        </a>

        <a class="btn" href="crossdocking.php">
          <i class="fa-solid fa-shuffle"></i>
          <span>Cross<br>Docking</span>
        </a>

        <a class="btn wide secondary" href="historial.php">
          <i class="fa-solid fa-clock-rotate-left"></i>
          <span>Historial · Recepciones</span>
        </a>
      </div>

      <div class="note">
        <b>Roadmap táctico:</b> este hub ya deja listo el “contracto de navegación”.
        Cada flujo (OC/Libre/CrossDock) puede consumir tus APIs actuales sin romper legacy, y permite instrumentar auditoría (quién, cuándo, en qué almacén).
      </div>

      <div class="footer">
        <span>Powered by</span> <b>Adventech Logística</b>
      </div>

    </div>
  </div>

  <script>
    (function(){
      // Mismo patrón que tu menú: estado por localStorage
      const user = localStorage.getItem('mobile_user') || '';
      const alm  = localStorage.getItem('mobile_almacen') || '';
      const almName = localStorage.getItem('mobile_almacen_nombre') || '';

      // Gate de acceso operativo
      if(!user || !alm){
        window.location.href = '../index.html';
        return;
      }

      document.getElementById('uLabel').textContent = user;
      document.getElementById('aLabel').textContent = (almName ? almName : alm);
      document.getElementById('almPill').textContent = 'ALM: ' + (almName ? almName : alm);
    })();
  </script>
</body>
</html>
