<?php
// _menu_global.php
// Menú lateral corporativo Adventech Logística (2 niveles, colapsable por sección)
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
    :root{
        --sidebar-width: 230px;
        --sidebar-bg: #0F5AAD;
        --sidebar-text: #dbe3f4;
        --sidebar-text-muted: #a9c3ec;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-size: 10px;
        background-color: #f5f6fa;
        margin: 0;
        padding-left: var(--sidebar-width); /* reserva espacio para el menú */
    }

    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: var(--sidebar-width);
        height: 100vh;
        background-color: #0F5AAD;
        color: #fff;
        overflow-y: auto;
        box-shadow: 2px 0 6px rgba(0,0,0,0.2);
        z-index: 1000;
    }

    .sidebar .logo {
        text-align: center;
        padding: 14px 10px;
        border-bottom: 1px solid rgba(255,255,255,0.12);
        font-size: 11px;
        letter-spacing: 0.5px;
        font-weight: 600;
    }

    .sidebar .logo img{
        max-width: 150px;
        display:block;
        margin:0 auto 4px auto;
    }

    .sidebar .menu-header {
        padding: 8px 14px;
        font-weight: 600;
        font-size: 10px;
        text-transform: uppercase;
        color: var(--sidebar-text-muted);
        border-top: 1px solid rgba(255,255,255,0.12);
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        user-select: none;
    }

    .sidebar .menu-header .left {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .sidebar .menu-header i.fa-folder-open {
        font-size: 11px;
    }

    .sidebar .menu-header .chevron {
        font-size: 10px;
        transition: transform 0.2s ease;
    }

    .sidebar .menu-header.collapsed .chevron {
        transform: rotate(-90deg);
    }

    .sidebar a {
        text-decoration: none;
        color: var(--sidebar-text);
        display: block;
        padding: 7px 14px;
        transition: all 0.15s ease;
        font-size: 10px;
        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
    }

    .sidebar a i {
        width: 14px;
        margin-right: 6px;
        font-size: 10px;
    }

    .sidebar a:hover {
        background-color: rgba(255,255,255,0.14);
        color: #fff;
    }

    .submenu.collapsed {
        display: none;
    }

    .submenu a {
        padding-left: 26px;
        border-left: 2px solid transparent;
    }

    .submenu a:hover {
        border-left-color: rgba(255,255,255,0.5);
    }

    @media (max-width: 991.98px) {
        body {
            padding-left: 0;
        }
        .sidebar {
            transform: translateX(-100%);
            transition: transform 0.25s ease;
        }
        .sidebar.sidebar-show {
            transform: translateX(0);
        }
    }
</style>
</head>
<body>

<div class="sidebar">
    <div class="logo">
        <img src="/assistpro_kardex_fc/public/img/logo_adventech_white.png" alt="Logo">
        AssistPro WMS
    </div>

    <?php
    // Base del proyecto
    $baseUrl = '/assistpro_kardex_fc/public/';

    // Menús principales
    $menus = [

        // DASHBOARD
        'Dashboard' => [
            ['titulo' => 'Online Tracking',          'url' => 'dashboard/onlinetracking.php',               'icono' => 'fa-location-dot'],
            ['titulo' => 'Resumen Ejecutivo',        'url' => 'dashboard/resumen.php',                      'icono' => 'fa-chart-line'],
            ['titulo' => 'Inventario',               'url' => 'dashboard/inventario.php',                   'icono' => 'fa-boxes-stacked'],
            ['titulo' => 'Monitoreo Entregas',       'url' => 'dashboard/monitoreo.php',                    'icono' => 'fa-truck-fast'],
            ['titulo' => 'Monitoreo Pedidos',        'url' => 'dashboard/monitoreopedidos.php',             'icono' => 'fa-receipt'],
            ['titulo' => 'Resumen Básico',           'url' => 'dashboard/resumen_basico.php',               'icono' => 'fa-chart-pie'],
            ['titulo' => 'Dashboard License Plate',  'url' => 'dashboard/license_plate.php',                'icono' => 'fa-barcode'],
            ['titulo' => 'Dashboard Inventarios',    'url' => 'dashboard/adm_inventarios.php',              'icono' => 'fa-warehouse'],
            ['titulo' => 'Config. de Almacén',       'url' => 'dashboard/configuracion_almacen.php',        'icono' => 'fa-gear'],
            ['titulo' => 'Cobranza – Cobro',         'url' => 'dashboard/cobranza_cobro.php',               'icono' => 'fa-money-bill-wave'],
            ['titulo' => 'Cobranza – Analítico',     'url' => 'dashboard/cobranza_analitico.php',           'icono' => 'fa-chart-area'],
            ['titulo' => 'Kardex Productividad',     'url' => 'dashboard/kardex_productividad_session.php', 'icono' => 'fa-chart-line'],
            ['titulo' => 'Kardex Dashboard',         'url' => 'dashboard/kardex.php',                       'icono' => 'fa-file-lines'],
        ],

        // ADMINISTRACIÓN
        'Administración' => [
            ['titulo' => 'Compañías',                'url' => 'admin/companias.php',                        'icono' => 'fa-building'],
            ['titulo' => 'Usuarios',                 'url' => 'admin/usuarios.php',                         'icono' => 'fa-user'],
            ['titulo' => 'Perfiles',                 'url' => 'admin/perfiles.php',                         'icono' => 'fa-id-badge'],
            ['titulo' => 'Zonas',                    'url' => 'admin/zonas.php',                            'icono' => 'fa-map'],
        ],

        // CATÁLOGOS
        'Catálogos' => [
            
            ['titulo' => 'Ajustes | Incidencias',    'url' => 'catalogos/catalogos/cat_c_motivoajuste.php',           'icono' => 'fa-circle'],
            ['titulo' => 'License Plate',            'url' => 'catalogos/catalogos/cat_c_licenseplate.php',           'icono' => 'fa-circle'],
            ['titulo' => 'Motivo No Ventas',         'url' => 'catalogos/catalogos/cat_c_motivosnoventas.php',        'icono' => 'fa-circle'],
            ['titulo' => 'Motivo de Devolución',     'url' => 'catalogos/catalogos/cat_c_motivodevolucion.php',       'icono' => 'fa-circle'],
            ['titulo' => 'Pallet y Contenedores',    'url' => 'catalogos/catalogos/cat_c_contenedores.php',           'icono' => 'fa-box-open'],
            ['titulo' => 'Rutas / Destinatarios',    'url' => 'catalogos/catalogos/cat_c_destinatarios.php',          'icono' => 'fa-route'],
            ['titulo' => 'Protocolos de Entrada',    'url' => 'catalogos/catalogos/cat_c_protocolos.php',             'icono' => 'fa-clipboard-check'],
            ['titulo' => 'Proveedores',              'url' => 'catalogos/catalogos/cat_c_proveedores.php',            'icono' => 'fa-truck-field'],
            ['titulo' => 'Proyectos | CC',           'url' => 'catalogos/catalogos/cat_c_proyectos.php',              'icono' => 'fa-diagram-project'],
            ['titulo' => 'QA | Cuarentena',          'url' => 'catalogos/catalogos/cat_c_motivocuarentena.php',       'icono' => 'fa-triangle-exclamation'],
            ['titulo' => 'Rutas',                    'url' => 'catalogos/catalogos/cat_c_ruta.php',                   'icono' => 'fa-road'],
            ['titulo' => 'Tipo de Prioridad',        'url' => 'catalogos/catalogos/cat_c_tipodeprioridad.php',        'icono' => 'fa-layer-group'],
        ],

        // SFA
        'SFA' => [
            ['titulo' => 'Lista de Precios',         'url' => 'sfa/lista_precios.php',                     'icono' => 'fa-tags'],
            ['titulo' => 'Lista de Descuentos',      'url' => 'sfa/lista_descuentos.php',                  'icono' => 'fa-percent'],
            ['titulo' => 'Promociones',              'url' => 'sfa/promociones.php',                       'icono' => 'fa-gift'],
            ['titulo' => 'Grupo Promociones',        'url' => 'sfa/grupo_promociones.php',                 'icono' => 'fa-layer-group'],
            ['titulo' => 'Formas de Pago',           'url' => 'sfa/formas_pago.php',                       'icono' => 'fa-credit-card'],
            ['titulo' => 'Ticket',                   'url' => 'sfa/ticket.php',                            'icono' => 'fa-ticket'],
        ],

        // PROCESOS
        'Procesos' => [
            ['titulo' => 'Entradas',                 'url' => 'procesos/entradas.php',                      'icono' => 'fa-right-to-bracket'],
            ['titulo' => 'Cross Dock',               'url' => 'procesos/crossdock.php',                     'icono' => 'fa-right-left'],
            ['titulo' => 'Put Away',                 'url' => 'procesos/putaway.php',                       'icono' => 'fa-box-archive'],
            ['titulo' => 'Pallets y Contenedores',   'url' => 'procesos/paletsycontenedores.php',           'icono' => 'fa-boxes-packing'],
            ['titulo' => 'Picking',                  'url' => 'procesos/picking.php',                       'icono' => 'fa-hand-pointer'],
            ['titulo' => 'Reabasto (Replenishment)', 'url' => 'procesos/reabasto.php',                      'icono' => 'fa-rotate'],
            ['titulo' => 'QA / Auditoría',           'url' => 'procesos/qa_auditoria.php',                  'icono' => 'fa-clipboard-check'],
            ['titulo' => 'Inventarios',              'url' => 'procesos/inventarios.php',                   'icono' => 'fa-clipboard-list'],
            ['titulo' => 'Existencias',              'url' => 'procesos/existencias.php',                   'icono' => 'fa-cubes'],
            ['titulo' => 'Embarques',                'url' => 'procesos/embarques.php',                     'icono' => 'fa-truck-ramp-box'],
            ['titulo' => 'Planeación',               'url' => 'procesos/planeacion.php',                    'icono' => 'fa-calendar-check'],
            ['titulo' => 'Administración',           'url' => 'procesos/administracion.php',                'icono' => 'fa-briefcase'],
            ['titulo' => 'Manufactura',              'url' => 'procesos/manufactura.php',                   'icono' => 'fa-industry'],
            ['titulo' => 'Control de Incidencias (PQRS)','url' => 'procesos/incidencias.php',              'icono' => 'fa-flag'],
            ['titulo' => 'Control de Activos',       'url' => 'procesos/control_activos.php',               'icono' => 'fa-screwdriver-wrench'],
            ['titulo' => 'Logística Inversa',        'url' => 'procesos/logistica_inversa.php',             'icono' => 'fa-rotate-left'],
        ],

        // REPORTES
        'Reportes' => [
            ['titulo' => 'Log de Operaciones',       'url' => 'reportes/operaciones.php',                   'icono' => 'fa-timeline'],
            ['titulo' => 'Salidas',                  'url' => 'reportes/salidas.php',                       'icono' => 'fa-arrow-right-arrow-left'],
            ['titulo' => 'Kardex | Trazabilidad',    'url' => 'reportes/kardex.php',                        'icono' => 'fa-clipboard-list'],
            ['titulo' => 'Kardex | Movimientos',     'url' => 'reportes/kardexw.php',                       'icono' => 'fa-right-left'],
        ],

        // UTILERÍAS
        'Utilerías' => [
            ['titulo' => 'Log de Operaciones',       'url' => 'utilerias/log_operaciones.php',              'icono' => 'fa-timeline'],
            ['titulo' => 'Log WebServices',              'url' => 'utilerias/log_ws.php',                   'icono' => 'fa-arrow-right-arrow-left'],
            ['titulo' => 'Browser ETL',       'url' => 'etl/etl_browser.php',              'icono' => 'fa-timeline'],
            ['titulo' => 'Administrador Procesos ',              'url' => 'etl/administrador_procesos.php',                   'icono' => 'fa-arrow-right-arrow-left'],
            ['titulo' => 'Generador de Catálogos',   'url' => 'utilerias/generador.php',                     'icono' => 'fa-clipboard-list'],
        ],
    ];    
    ?>

    <?php foreach ($menus as $menu => $submenus):
        $menuId = 'menu_' . preg_replace('/[^a-z0-9]+/i', '_', strtolower($menu));
        $collapsed = true; // todos colapsados por defecto
    ?>
        <div class="menu-header menu-toggle <?= $collapsed ? 'collapsed' : '' ?>" data-target="#<?= $menuId ?>">
            <div class="left">
                <i class="fa fa-folder-open"></i> <?= htmlspecialchars($menu) ?>
            </div>
            <i class="fa fa-chevron-down chevron"></i>
        </div>
        <div class="submenu <?= $collapsed ? 'collapsed' : '' ?>" id="<?= $menuId ?>">
            <?php foreach ($submenus as $item):
                $icono = !empty($item['icono']) ? $item['icono'] : 'fa-circle';
            ?>
                <a href="<?= $baseUrl . htmlspecialchars($item['url']) ?>">
                    <i class="fa <?= htmlspecialchars($icono) ?>"></i>
                    <?= htmlspecialchars($item['titulo']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.menu-toggle').forEach(function(header) {
        header.addEventListener('click', function() {
            var targetSelector = this.getAttribute('data-target');
            if (!targetSelector) return;
            var target = document.querySelector(targetSelector);
            if (!target) return;

            target.classList.toggle('collapsed');
            this.classList.toggle('collapsed');
        });
    });
});
</script>

<!-- el resto de cada página se cierra/complementa en _menu_global_end.php -->
