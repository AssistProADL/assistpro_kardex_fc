<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$fullName = trim(
  $_SESSION['nombre_completo']
  ?? $_SESSION['full_name']
  ?? $_SESSION['username']
  ?? ''
);
$perfil   = trim($_SESSION['perfil'] ?? '');

$alm      = $_SESSION['cve_almac'] ?? '';
$allEmp   = $_SESSION['empresas_all'] ?? false;
$empN     = is_array($_SESSION['empresas'] ?? null) ? count($_SESSION['empresas']) : 0;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Business Intelligence Suite</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root{
      --ap-blue:#0a2a6b;
      --ap-blue-2:#123a90;
      --sidebar-w:260px;
      --sidebar-w-mini:72px;
      --topbar-h:58px;
    }

    /* Layout base */
    body{ background:#fff; }
    .ap-shell{ position:relative; z-index:1; }
    .ap-main{ margin-left:var(--sidebar-w); min-height:100vh; background:#fff; transition:margin-left .2s ease; }
    .ap-overlay{ display:none !important; } /* nunca oscurecer */

    /* Sidebar azul */
    .ap-sidebar{
      position:fixed; inset:0 auto 0 0; width:var(--sidebar-w);
      background:linear-gradient(180deg,var(--ap-blue),var(--ap-blue-2));
      color:#fff; padding:14px 12px; box-shadow:0 10px 30px rgba(0,0,0,.18);
      transition:width .2s ease;
    }
    /* Placa del logo en blanco */
    .brand-plate{
      background:#fff; border-radius:14px; padding:8px 10px; margin-bottom:12px;
      display:flex; align-items:center; justify-content:center;
      box-shadow:0 6px 18px rgba(0,0,0,.12);
    }
    .brand-plate img{ height:28px; display:block; }

    .sec-title{letter-spacing:.08em; opacity:.78; font-size:.78rem; margin:14px 8px 8px;}
    .ap-sidebar .nav-link{
      color:#e9eef5; border-radius:12px; padding:.55rem .8rem; display:flex; align-items:center; gap:.5rem;
      white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    .ap-sidebar .nav-link:hover{background:rgba(255,255,255,.12); color:#fff}

    /* Topbar blanco con título centrado y sesión a la derecha */
    .ap-topbar{
      position:sticky; top:0; z-index:5; height:var(--topbar-h);
      display:flex; align-items:center; justify-content:center;
      background:#fff; border-bottom:1px solid #eef2f6;
    }
    .ap-topbar .hamburger{ position:absolute; left:10px; }
    .ap-topbar .title{ font-weight:700; color:#0a2a6b; }
    .ap-topbar .right{
      position:absolute; right:12px; display:flex; align-items:center; gap:.5rem; flex-wrap:wrap;
    }
    .pill{background:#eef3ff; color:#0a2a6b; padding:.25rem .6rem; border-radius:999px; font-size:.82rem;}
    .container-fluid{ background:#fff; }

    /* Estado colapsado */
    body.sidebar-collapsed .ap-sidebar{ width:var(--sidebar-w-mini); }
    body.sidebar-collapsed .ap-main{ margin-left:var(--sidebar-w-mini); }
    body.sidebar-collapsed .sec-title{ display:none; }
    body.sidebar-collapsed .ap-sidebar .nav-link span.label{ display:none; }
  </style>
</head>
<body>
<div class="ap-shell">

  <!-- SIDEBAR (azul) con placa blanca del logo -->
  <aside class="ap-sidebar">
    <div class="brand-plate">
      <img src="/assistpro_kardex_fc/assets/logo/assistpro-er.svg" alt="AssistPro ER">
    </div>

    <div class="sec-title">NAVEGACIÓN</div>
    <nav class="nav flex-column">
      
      <a class="nav-link" href="/assistpro_kardex_fc/public/bi/index.php">
        <i class="bi bi-grid-1x2-fill"></i><span class="label">Dashboard</span>
      </a>
      
      <a class="nav-link" href="/assistpro_kardex_fc/public/bi/resumen_basico.php">
        <i class="bi bi-speedometer2"></i><span class="label">Análisis Inicial</span>
      </a>

<a class="nav-link" href="/assistpro_kardex_fc/public/catalogos/generador.php">
        <i class="bi bi-speedometer2"></i><span class="label">Generador de Catálogos</span>
      </a>      

<a class="nav-link" href="/assistpro_kardex_fc/public/catalogos/cat_c_usuario.php">
        <i class="bi bi-speedometer2"></i><span class="label">Catálogo de Usuarios</span>
      </a>

<a class="nav-link" href="/assistpro_kardex_fc/public/catalogos/cat_c_proveedores.php">
        <i class="bi bi-speedometer2"></i><span class="label">Proveedores</span>
      </a>


<a class="nav-link" href="/assistpro_kardex_fc/public/catalogos/cat_c_clientes.php">
        <i class="bi bi-speedometer2"></i><span class="label">Clientes</span>
      </a>




      <a class="nav-link" href="/assistpro_kardex_fc/public/catalogos/cat_c_articulo.php">
        <i class="bi bi-speedometer2"></i><span class="label">Catálogo de productos</span>
      </a>

      <a class="nav-link" href="/assistpro_kardex_fc/public/catalogos/cat_c_charolas.php">
        <i class="bi bi-speedometer2"></i><span class="label">Pallets y Contenedores</span>
      </a>

      
      <a class="nav-link" href="/assistpro_kardex_fc/public/catalogos/cat_c_articulo.php">
        <i class="bi bi-speedometer2"></i><span class="label">Catálogo de productos</span>
      </a>

      <a class="nav-link" href="/assistpro_kardex_fc/public/dashboard/configuracion_almacen.php">
        <i class="bi bi-speedometer2"></i><span class="label">Configuración del Almacén</span>
      </a>
      
	  <a class="nav-link" href="/assistpro_kardex_fc/public/procesos/recepcion_materiales.php">
        <i class="bi bi-speedometer2"></i><span class="label">Recepción de Materiales</span>
      </a>
	  
      <a class="nav-link" href="/assistpro_kardex_fc/public/bi/kardex_productividad.php">
        <i class="bi bi-speedometer2"></i><span class="label">Kardex Productividad</span>
      </a>

      <a class="nav-link" href="/assistpro_kardex_fc/public/bi/kardex.php">
        <i class="bi bi-speedometer2"></i><span class="label">Kardex </span>
      </a>
      
    

      <a class="nav-link" href="/assistpro_kardex_fc/public/bi/log_operaciones.php">
        <i class="bi bi-speedometer2"></i><span class="label">Log Operaciones</span>
      </a>

      <a class="nav-link" href="/assistpro_kardex_fc/public/bi/log_ws.php">
        <i class="bi bi-speedometer2"></i><span class="label">Log WS </span>
      </a>
      
 
        <a class="nav-link" href="/assistpro_kardex_fc/public/manufactura/bom.php">
        <i class="bi bi-speedometer2"></i><span class="label">Kits | Manufactura</span>
        </a> 
        
        <a class="nav-link" href="/assistpro_kardex_fc/public/manufactura/orden_produccion.php">
        <i class="bi bi-speedometer2"></i><span class="label">Generación OTs</span>
        </a>

      <a class="nav-link" href="#"><i class="bi bi-cash-coin"></i><span class="label">Financiero</span></a>
      <a class="nav-link" href="#"><i class="bi bi-people"></i><span class="label">Clientes/Proyectos</span></a>
      <a class="nav-link" href="#"><i class="bi bi-gear"></i><span class="label">Administración</span></a>
      <hr class="border-white opacity-25">
      <a class="nav-link" href="/assistpro_kardex_fc/public/logout.php"><i class="bi bi-box-arrow-right"></i><span class="label">Salir</span></a>
    </nav>
  </aside>

  <!-- MAIN -->
  <main class="ap-main">
    <div class="ap-topbar">
      <div class="hamburger">
        <button id="btn-toggle-sidebar" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-list"></i>
        </button>
      </div>
      <div class="title">Business Intelligence Suite</div>
      <div class="right">
        <span class="pill">
          <i class="bi bi-person-circle me-1"></i>
          <?= htmlspecialchars($fullName ?: '—') ?>
          <?= $perfil ? ' · <b>'.htmlspecialchars($perfil).'</b>' : '' ?>
        </span>
        <span class="pill"><i class="bi bi-house-door me-1"></i><?= htmlspecialchars($alm ?: '—') ?></span>
        <span class="pill"><i class="bi bi-building me-1"></i><?= $allEmp ? "Todas las empresas" : ($empN>0 ? "$empN empresa(s)" : "Sin empresas") ?></span>
      </div>
    </div>

    <div class="container-fluid py-3">
