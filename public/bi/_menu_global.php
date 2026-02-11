<?php
// _menu_global.php
require_once __DIR__ . '/../../app/auth_check.php';

// NO cerrar PHP aquí para evitar output antes del DOCTYPE
?><!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            /* Colores Institucionales - Manual de Identidad */
            --adl-primary: #000F9F;
            /* Azul Principal - PANTONE 072C */
            --adl-secondary: #DCE3EB;
            /* Gris Secundario - PANTONE 656C */
            --adl-accent: #95E1BF;
            /* Verde Menta Acento - PANTONE 656C */
            --adl-aux-blue: #3639A4;
            /* Azul Auxiliar */
            --adl-aux-black: #191817;
            /* Negro Auxiliar */
            --adl-white: #FFFFFF;
            /* Sidebar - Más compacto */
            --sidebar-width: 240px;
            --sidebar-bg: var(--adl-primary);
            --sidebar-text: rgba(255, 255, 255, 0.95);
            --sidebar-text-muted: rgba(255, 255, 255, 0.65);
            --sidebar-hover-bg: rgba(149, 225, 191, 0.15);
            /* Fondo verde menta al hacer hover */
            --sidebar-hover-text: #FFFFFF;
            /* Texto blanco al hacer hover */
            /* Shadows & Effects */
            --shadow-sm: 0 2px 8px rgba(0, 15, 159, 0.08);
            --shadow-md: 0 4px 16px rgba(0, 15, 159, 0.12);
            --shadow-lg: 0 8px 24px rgba(0, 15, 159, 0.16);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 14px;
            font-weight: 400;
            background-color: #F8F9FA;
            color: var(--adl-aux-black);
            margin: 0;
            padding-left: var(--sidebar-width);
            transition: padding-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* === SIDEBAR PRINCIPAL === */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, var(--adl-primary) 0%, #000C7A 100%);
            color: var(--sidebar-text);
            overflow-y: auto;
            overflow-x: hidden;
            box-shadow: var(--shadow-lg);
            z-index: 1050;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Scrollbar personalizado */
        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* === LOGO SECTION === */
        .sidebar .logo {
            text-align: center;
            padding: 16px 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.15);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .sidebar .logo img {
            max-width: 160px;
            height: auto;
            display: block;
            margin: 0 auto 6px auto;
            filter: brightness(0) invert(1);
            transition: transform 0.3s ease;
        }

        .sidebar .logo:hover img {
            transform: scale(1.05);
        }

        /* === MENU HEADERS === */
        .sidebar .menu-header {
            padding: 8px 12px;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--sidebar-text-muted);
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            user-select: none;
            transition: all 0.2s ease;
            margin-top: 4px;
        }

        .sidebar .menu-header:first-of-type {
            margin-top: 0;
            border-top: none;
        }

        .sidebar .menu-header:hover {
            background: rgba(255, 255, 255, 0.08);
            color: var(--sidebar-text);
        }

        .sidebar .menu-header .left {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .sidebar .menu-header i.fa-folder-open {
            font-size: 10px;
            opacity: 0.8;
        }

        .sidebar .menu-header .chevron {
            font-size: 9px;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0.7;
        }

        .sidebar .menu-header.collapsed .chevron {
            transform: rotate(-90deg);
        }

        /* === MENU ITEMS === */
        .sidebar a {
            text-decoration: none;
            color: var(--sidebar-text);
            display: flex;
            align-items: center;
            padding: 7px 12px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 12px;
            font-weight: 400;
            position: relative;
            border-left: 3px solid transparent;
        }

        .sidebar a::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--adl-accent);
            transform: scaleY(0);
            transition: transform 0.2s ease;
        }

        .sidebar a i {
            width: 18px;
            margin-right: 10px;
            font-size: 12px;
            text-align: center;
            opacity: 0.9;
            transition: all 0.2s ease;
        }

        /* Hover Effect: Fondo Verde Menta, Texto Blanco */
        .sidebar a:hover {
            background: var(--sidebar-hover-bg);
            color: var(--sidebar-hover-text);
            padding-left: 16px;
            font-weight: 500;
        }

        .sidebar a:hover::before {
            transform: scaleY(1);
            background: var(--adl-accent);
        }

        .sidebar a:hover i {
            opacity: 1;
            transform: scale(1.08);
            color: var(--sidebar-hover-text);
        }

        /* === SUBMENU === */
        .submenu {
            max-height: 1000px;
            overflow: hidden;
            transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .submenu.collapsed {
            max-height: 0;
        }

        .submenu a {
            padding-left: 38px;
            font-size: 11px;
            background: rgba(0, 0, 0, 0.1);
        }

        .submenu a:hover {
            padding-left: 42px;
            background: rgba(149, 225, 191, 0.12);
            color: var(--adl-white);
        }

        /* === LOGOUT BUTTON (Footer) === */
        .sidebar-footer {
            position: sticky;
            bottom: 0;
            background: linear-gradient(180deg, transparent 0%, rgba(0, 0, 0, 0.3) 100%);
            padding: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-logout {
            width: 100%;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            color: var(--sidebar-text);
            font-size: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-logout:hover {
            background: rgba(149, 225, 191, 0.2);
            color: var(--adl-white);
            border-color: var(--adl-accent);
            transform: translateY(-2px);
        }

        /* === MOBILE HAMBURGER === */
        .mobile-header {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 56px;
            background: var(--adl-white);
            box-shadow: var(--shadow-sm);
            z-index: 1040;
            padding: 0 16px;
            align-items: center;
            justify-content: space-between;
        }

        .mobile-logo img {
            height: 28px;
            width: auto;
        }

        .hamburger {
            width: 38px;
            height: 38px;
            border: none;
            background: var(--adl-primary);
            border-radius: 6px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .hamburger span {
            width: 18px;
            height: 2px;
            background: white;
            border-radius: 2px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .hamburger.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .hamburger.active span:nth-child(2) {
            opacity: 0;
        }

        .hamburger.active span:nth-child(3) {
            transform: rotate(-45deg) translate(5px, -5px);
        }

        /* Overlay para cerrar menú en mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1045;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.show {
            opacity: 1;
        }

        /* === RESPONSIVE === */
        @media (max-width: 991.98px) {
            body {
                padding-left: 0;
                padding-top: 56px;
            }

            .mobile-header {
                display: flex;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.sidebar-show {
                transform: translateX(0);
            }

            .sidebar-overlay {
                display: block;
            }
        }

        @media (max-width: 575.98px) {
            .sidebar {
                width: 260px;
            }

            .sidebar .logo {
                padding: 14px 10px;
            }

            .sidebar .logo img {
                max-width: 130px;
            }
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="logo">
            <img src="/assistpro_kardex_fc/public/img/logo.png" alt="Logo">
            AssistPro WMS
        </div>

        <?php
        // Base del proyecto
        $baseUrl = '/assistpro_kardex_fc/public/';

        // Menús principales
        $menus = [

            // DASHBOARD
            'Dashboard' => [
                ['titulo' => 'Análisis Global', 'url' => 'dashboard/index.php', 'icono' => 'fa-location-dot'],
                ['titulo' => 'Georeferencia Rutas', 'url' => 'sfa/geo_distribucion_clientes.php', 'icono' => 'fa-location-dot'],
                ['titulo' => 'Análisis Crédito', 'url' => 'dashboard/creditos.php', 'icono' => 'fa-location-dot'],
                ['titulo' => 'Lead Time Analysis', 'url' => 'dashboard/dashboard_lta.php', 'icono' => 'fa-boxes-stacked'],
                ['titulo' => 'Inventario', 'url' => 'dashboard/inventario.php', 'icono' => 'fa-boxes-stacked'],
                ['titulo' => 'Resumen Básico', 'url' => 'dashboard/resumen_basico.php', 'icono' => 'fa-chart-pie'],
                ['titulo' => 'Dashboard Inventarios', 'url' => 'dashboard/adm_inventarios.php', 'icono' => 'fa-warehouse'],
                ['titulo' => 'Ocupación Almacén', 'url' => 'dashboard/ocupacion_almacen.php', 'icono' => 'fa-chart-line'],
                ['titulo' => 'Kardex Dashboard', 'url' => 'dashboard/kardex.php', 'icono' => 'fa-file-lines'],
                ['titulo' => 'AssistPro ER Mobile', 'url' => 'mobile/index.html', 'icono' => 'fa-file-lines'],
            ],

            // ADMINISTRACIÓN
            'Administración' => [
                ['titulo' => 'Empresas', 'url' => 'catalogos/empresas.php', 'icono' => 'fa-building'],
                ['titulo' => 'Almacenes', 'url' => 'catalogos/almacenes.php', 'icono' => 'fa-building'],
                ['titulo' => 'Usuarios', 'url' => 'catalogos/usuarios.php', 'icono' => 'fa-user'],
                ['titulo' => 'Perfiles Usuario', 'url' => 'catalogos/perfiles_usuario.php', 'icono' => 'fa-id-badge'],
            ],

            // CATÁLOGOS
            'Catálogos' => [
                ['titulo' => 'Articulos', 'url' => 'catalogos/articulos.php', 'icono' => 'fa-circle'],
                ['titulo' => 'Tipos de Articulos', 'url' => 'catalogos/tipos_articulos.php', 'icono' => 'fa-circle'],
                ['titulo' => 'Grupo de Articulos', 'url' => 'catalogos/grupos.php', 'icono' => 'fa-layer-group'],

                ['titulo' => 'Motivo No Ventas', 'url' => 'catalogos/motivos_no_venta.php', 'icono' => 'fa-circle'],
                ['titulo' => 'Motivo de Devolución', 'url' => 'catalogos/motivos_devolucion.php', 'icono' => 'fa-circle'],
                ['titulo' => 'Pallets y Contenedores', 'url' => 'catalogos/pallets_contenedores.php', 'icono' => 'fa-box-open'],
                ['titulo' => 'BL Ubicaciones', 'url' => 'catalogos/ubicaciones.php', 'icono' => 'fa-box-open'],


                ['titulo' => 'Rutas', 'url' => 'catalogos/rutas.php', 'icono' => 'fa-route'],
                ['titulo' => 'Clientes', 'url' => 'catalogos/clientes.php', 'icono' => 'fa-road'],
                ['titulo' => 'Grupo de clientes', 'url' => 'catalogos/clientes_grupo.php', 'icono' => 'fa-layer-group'],
                ['titulo' => 'Destinatarios', 'url' => 'catalogos/destinatarios.php', 'icono' => 'fa-road'],
                ['titulo' => 'Proveedores', 'url' => 'catalogos/proveedores.php', 'icono' => 'fa-truck-field'],
                ['titulo' => 'Contactos', 'url' => 'catalogos/contactos.php', 'icono' => 'fa-layer-group'],


                ['titulo' => 'Tipos de Movimientos', 'url' => 'catalogos/tipos_movimiento_doc.php', 'icono' => 'fa-clipboard-check'],
                ['titulo' => 'Proyectos | CC', 'url' => 'catalogos/proyecto.php', 'icono' => 'fa-diagram-project'],
                ['titulo' => 'Ajustes | Incidencias', 'url' => 'catalogos/ajustes_incidencias.php', 'icono' => 'fa-triangle-exclamation'],
                ['titulo' => 'QA | Cuarentena', 'url' => 'catalogos/qa_cuarentena.php', 'icono' => 'fa-triangle-exclamation'],

                ['titulo' => 'Tipo de Pedidos', 'url' => 'catalogos/tipo_pedido.php', 'icono' => 'fa-layer-group'],
              
                ['titulo' => 'Tipo de Prioridad', 'url' => 'catalogos/tipos_prioridad.php', 'icono' => 'fa-layer-group'],
                ['titulo' => 'Formas de Pago', 'url' => 'catalogos/formas_pago.php', 'icono' => 'fa-layer-group'],
                ['titulo' => 'Configuracion de Tickets', 'url' => 'catalogos/ticket.php', 'icono' => 'fa-layer-group'],
                ['titulo' => 'Clasificacion', 'url' => 'catalogos/clasificacion.php', 'icono' => 'fa-layer-group'],

            ],

            // E-Commerce
            'E-Commerce' => [
                ['titulo' => 'Catálogo digital', 'url' => 'portal_clientes/catalogo.php', 'icono' => 'fa-building'],
                ['titulo' => 'Registro Prospectos', 'url' => 'portal_clientes/registro_cliente.php', 'icono' => 'fa-building'],
                ['titulo' => 'Mis pedidos', 'url' => 'portal_clientes/mis_pedidos.php', 'icono' => 'fa-building'],
                ['titulo' => 'Admin Banners', 'url' => 'portal_admin_imagenes/banners.php', 'icono' => 'fa-building'],
                ['titulo' => 'Edición F Técnica', 'url' => 'portal_admin_imagenes/editar_ficha.php', 'icono' => 'fa-building'],
            ],

            // CRM
            'CRM' => [
                ['titulo' => 'Cotizaciones', 'url' => 'crm/cotizaciones.php', 'icono' => 'fa-building'],
                ['titulo' => 'Actividades', 'url' => 'crm/actividades.php', 'icono' => 'fa-building'],
                ['titulo' => 'Dashboard', 'url' => 'crm/dashboard.php', 'icono' => 'fa-building'],
                ['titulo' => 'Leads', 'url' => 'crm/leads.php', 'icono' => 'fa-building'],
                ['titulo' => 'Oportunidades', 'url' => 'crm/oportunidades.php', 'icono' => 'fa-building'],
                ['titulo' => 'Gastos Operativos', 'url' => 'crm/gastos.php', 'icono' => 'fa-building'],

            ],

            // DEPOT SERVICE CONTROL
            'DSC Depot Service Control' => [
                ['titulo' => 'Recepción | Control de Garantías', 'url' => 'procesos/servicio_depot/recepcion.php', 'icono' => 'fa-circle'],
                ['titulo' => 'Administración Garantías', 'url' => 'procesos/servicio_depot/admin_ingenieria_servicio.php', 'icono' => 'fa-circle'],
                ['titulo' => 'Laboratorio Servicio', 'url' => 'procesos/servicio_depot/laboratorio_servicio.php', 'icono' => 'fa-circle'],
                ['titulo' => 'Reporte Diagnostico', 'url' => 'procesos/servicio_depot/reporte_diagnostico.php', 'icono' => 'fa-circle'],
                ['titulo' => 'Cotizaciones', 'url' => 'procesos/servicio_depot/servicio_generar_cotizacion.php', 'icono' => 'fa-circle'],
            ],


            // SFA
            'SFA' => [
                ['titulo' => 'Dashboard Activos',             'url' => 'sfa/dashboard_activos.php',          'icono' => 'fa-tags'],
                ['titulo' => 'Geolocalización Activos',       'url' => 'sfa/activos_geocercas.php',          'icono' => 'fa-tags'],
                ['titulo' => 'Resumen Ejecutivo',             'url' => 'sfa/resumen_rutas.php',              'icono' => 'fa-tags'],
                ['titulo' => 'Planeación de Rutas',           'url' => 'sfa/planeacion_rutas_destinatarios.php', 'icono' => 'fa-tags'],
                ['titulo' => 'Configuración Rutas',           'url' => 'sfa/rutas_planning.php',             'icono' => 'fa-tags'],
                ['titulo' => 'Administración Comercial',      'url' => 'sfa/destinatarios_config.php',       'icono' => 'fa-tags'],
                ['titulo' => 'Lista de Precios',              'url' => 'sfa/lista_precios.php',              'icono' => 'fa-tags'],
                ['titulo' => 'Lista de Descuentos',           'url' => 'sfa/lista_descuentos.php',           'icono' => 'fa-percent'],
                ['titulo' => 'Promociones',                   'url' => 'sfa/promociones/promo_design.php',   'icono' => 'fa-gift'],
                ['titulo' => 'Admin Promociones',             'url' => 'sfa/promociones/promociones.php',    'icono' => 'fa-gift'],
                ['titulo' => 'Simulador Promociones',         'url' => 'sfa/promociones/simulador.php',      'icono' => 'fa-gift'],
                ['titulo' => 'Asignación de Activos', 	      'url' => 'sfa/activos_control.php',            'icono' => 'fa-tags'],
                ['titulo' => 'Activos',                       'url' => 'sfa/activos.php',                    'icono' => 'fa-tags'],
                ['titulo' => 'Tipos de Activos',              'url' => 'sfa/activo_tipos.php',               'icono' => 'fa-tags'],
                ['titulo' => 'Propietario Activos',           'url' => 'sfa/activos_propiedad.php',          'icono' => 'fa-percent'],
                ['titulo' => 'Status Activos',                'url' => 'sfa/activo_estados.php',             'icono' => 'fa-gift'],
                ['titulo' => 'Condicion Activos',             'url' => 'sfa/activo_condicion.php',           'icono' => 'fa-layer-group'],
                ['titulo' => 'Encuestas',                     'url' => 'sfa/encuestas.php',                  'icono' => 'fa-layer-group'],
            ],

            // TMS
            'TMS' => [
                ['titulo' => 'Mantenimiento', 'url' => 'tms/mto_transportes.php', 'icono' => 'fa-tags'],
                ['titulo' => 'Ordenes de Servicio', 'url' => 'tms/mto_ordenes.php', 'icono' => 'fa-percent'],
            ],
            // TMS
            'YMS' => [
                ['titulo' => 'YMS Control de Patios', 'url' => 'control_patios/patios_admin.php', 'icono' => 'fa-tags'],
                ['titulo' => 'Planeación', 'url' => 'control_patios/planeacion.php', 'icono' => 'fa-percent'],
            ],


            // ADMINISTRACIÓN DE ALMACEN
            'Administración Almacen' => [
                ['titulo' => 'Config. de Almacén',              'url' => 'config_almacen/configuracion_almacen.php',      'icono' => 'fa-gear'],
                ['titulo' => 'Pallets y Contenedores',          'url' => 'config_almacen/license_plate.php',              'icono' => 'fa-right-left'],
                ['titulo' => 'Transacciones License Plate',     'url' => 'config_almacen/lp_pr_transaction.php',          'icono' => 'fa-right-left'],

            ],


            // PROCESOS
            'Procesos' => [
                ['titulo' => 'Control de Incidencias (PQRS)', 'url' => 'procesos/incidencias.php', 'icono' => 'fa-flag'],
                ['titulo' => 'Logística Inversa', 'url' => 'procesos/logistica_inversa.php', 'icono' => 'fa-rotate-left'],
                ['titulo' => 'Ajuste de Existencias', 'url' => 'procesos/ajuste_existencias', 'icono' => 'fa-rotate-left'],
            ],

            // INGRESOS
            'Ingresos' => [
                ['titulo' => 'Orden de Compra', 'url' => 'ingresos/orden_compra.php', 'icono' => 'fa-right-to-bracket'],
                ['titulo' => 'Recepción de Materiales', 'url' => 'ingresos/recepcion_materiales.php', 'icono' => 'fa-box-archive'],
                ['titulo' => 'Administración de Ingresos', 'url' => 'ingresos/ingresos_admin.php', 'icono' => 'fa-right-left'],
                ['titulo' => 'Importador', 'url' => 'ingresos/importador_ingresos.php', 'icono' => 'fa-box-archive'],
            ],

            // PUTAWAY
            'PutAway' => [
                ['titulo' => 'RTM (Ready To Move)',          'url' => 'ingresos/rtm_pendiente_acomodo.php',      'icono' => 'fa-right-left'],
                ['titulo' => 'PutAway (Acomodo)',            'url' => 'putaway/putaway_acomodo.php',             'icono' => 'fa-right-left'],
                ['titulo' => 'Traslado',                     'url' => 'putaway/traslado.php',                    'icono' => 'fa-box-archive'],
                ['titulo' => 'Traslado entre Almacenes',     'url' => 'putaway/traslado_entre_almacenes.php',    'icono' => 'fa-box-archive'],
                ['titulo' => 'Importador traslado entre almacenes', 'url' => 'importadores/importador_traslado_almacenes.php', 'icono' => 'fa-box-archive'],
            ],

            // PICKING
            'Picking' => [
                ['titulo' => 'Registro de Pedidos', 'url' => 'pedidos/registro_pedidos.php', 'icono' => 'fa-right-left'],
                ['titulo' => 'Administración de Pedidos', 'url' => 'pedidos/picking_admin.php', 'icono' => 'fa-right-left'],
                ['titulo' => 'Secuencia de Surtido', 'url' => 'pedidos/secuencia_surtido.php', 'icono' => 'fa-box-archive'],

            ],


            // MANUFACTURA KITTING
            'Manufactura' => [
                ['titulo' => 'Agregar Editar Componentes ',   'url' => 'manufactura/bom.php',                      'icono' => 'fa-industry'],
                ['titulo' => 'Planeacion OP',                 'url' => 'manufactura/orden_produccion.php',         'icono' => 'fa-flag'],
                ['titulo' => 'Importador OPs',                'url' => 'manufactura/importador_op.php',              'icono' => 'fa-industry'],
                ['titulo' => 'Monitor Producción',            'url' => 'manufactura/monitor_produccion.php',       'icono' => 'fa-flag'],
                
                             ],

            // QA AUDITORIA
            'QA Auditoría' => [
                ['titulo' => 'Auditoria y Empaque', 'url' => 'qa_auditoria/auditoriayempaque.php', 'icono' => 'fa-industry'],
                ['titulo' => 'Admin', 'url' => 'qa_auditoria/admin.php', 'icono' => 'fa-flag'],
                ['titulo' => 'Control de calidad', 'url' => 'qa_auditoria/movimientos.php', 'icono' => 'fa-flag'],
                ['titulo' => 'Admin QA', 'url' => 'qa_auditoria/listas_adminqa.php', 'icono' => 'fa-screwdriver-wrench'],
                ['titulo' => 'Listas QA Cuarentena', 'url' => 'qa_auditoria/listas_qacuarentena.php', 'icono' => 'fa-screwdriver-wrench'],
            ],

            // INVENTARIOS
            'Inventarios' => [
                ['titulo' => 'Planeación de Inventarios', 'url' => 'inventarios/planeacion/planificar_inventario.php', 'icono' => 'fa-industry'],
                ['titulo' => 'Ejecución de Inventarios', 'url' => 'inventarios/ejecucion/ejecucion_conteo.php', 'icono' => 'fa-flag'],
                ['titulo' => 'Administración Inventarios', 'url' => 'inventarios/administracion/admin_inventarios.php', 'icono' => 'fa-screwdriver-wrench'],
            ],

            // EMBARQUES
            'Embarques' => [
                ['titulo' => 'Planeación', 'url' => 'embarques/planeacion_embarques.php', 'icono' => 'fa-calendar-check'],
                ['titulo' => 'Administración', 'url' => 'embarques/admin_embarques.php', 'icono' => 'fa-briefcase'],
            ],

            // VAS
            'Modulo VAS' => [
                ['titulo' => 'Servicios', 'url' => 'vas/servicios_vas.php', 'icono' => 'fa-calendar-check'],
                ['titulo' => 'Clientes', 'url' => 'vas/vas_cliente.php', 'icono' => 'fa-calendar-check'],
                ['titulo' => 'Cobranza', 'url' => 'vas/vas_cobranza.php', 'icono' => 'fa-calendar-check'],
	        ['titulo' => 'Pedidos', 'url' => 'vas/vas_pedido.php', 'icono' => 'fa-calendar-check'],

            ],

            // REPORTES SFA
            'Reportes SFA' => [
                ['titulo' => 'Análisis de Ventas', 'url' => 'reportes_sfa/ventas_analisis.php', 'icono' => 'fa-arrow-right-arrow-left'],
                ['titulo' => 'Bitácora de Tiempos', 'url' => 'reportes_sfa/bitacora_tiempos.php', 'icono' => 'fa-timeline'],
                ['titulo' => 'CXC | Cobranza ', 'url' => 'reportes_sfa/cobranza_analitico.php', 'icono' => 'fa-clipboard-list'],
                ['titulo' => 'CXC | Cobranza Consolidado', 'url' => 'reportes_sfa/cobranza_consolidado', 'icono' => 'fa-right-left'],
                ['titulo' => 'Liquidación', 'url' => 'reportes_sfa/liquidacion.php', 'icono' => 'fa-right-left'],
                ['titulo' => 'CXC|Consolidado', 'url' => 'reportes_sfa/cxc_consolidado.php', 'icono' => 'fa-right-left'],



            ],

            // REPORTES
            'Reportes' => [
                ['titulo' => 'Existencias por Ubicacion', 'url' => 'reportes/existencias_ubicacion.php', 'icono' => 'fa-timeline'],
                ['titulo' => 'Existencia a Detalle', 'url' => 'reportes/existencias_ubicacion_total.php', 'icono' => 'fa-timeline'],
                ['titulo' => 'Log de Operaciones', 'url' => 'reportes/operaciones.php', 'icono' => 'fa-timeline'],
                ['titulo' => 'Salidas', 'url' => 'reportes/salidas.php', 'icono' => 'fa-arrow-right-arrow-left'],
                ['titulo' => 'Kardex | Trazabilidad', 'url' => 'reportes/kardex.php', 'icono' => 'fa-clipboard-list'],
                ['titulo' => 'Kardex | Movimientos', 'url' => 'reportes/kardexw.php', 'icono' => 'fa-right-left'],
            ],

            // CONFIGURACIÓN CORREO
            'Configuración Correos' => [
                ['titulo' => 'Configuración SMTP', 'url' => 'config/correo_config.php', 'icono' => 'fa-timeline'],
                ['titulo' => 'Plantillas', 'url' => 'config/correo_plantillas.php', 'icono' => 'fa-arrow-right-arrow-left'],
                ['titulo' => 'Automatización', 'url' => 'config/correo_jobs.php', 'icono' => 'fa-timeline'],
                ['titulo' => 'Testing', 'url' => 'config/correo_test.php', 'icono' => 'fa-timeline'],
            ],
            // DISPOSITIVOS
            'Dispositivos' => [
                ['titulo' => 'Terminales EDA HHC RFID', 'url' => 'dispositivos/dispositivos.php', 'icono' => 'fa-timeline'],
                ['titulo' => 'Impresoras', 'url' => 'dispositivos/impresoras.php', 'icono' => 'fa-arrow-right-arrow-left'],
            ],

            // UTILERÍAS
            'Utilerías' => [
                ['titulo' => 'Catálogo de Importadores',      'url' => 'utilerias/catalogo_importadores.php',    'icono' => 'fa-timeline'],              
                ['titulo' => 'Conexión SAP B1',               'url' => 'conexion_ws/config_conexion_ws.php',     'icono' => 'fa-timeline'],
                ['titulo' => 'Log de Operaciones',            'url' => 'utilerias/log_operaciones.php',          'icono' => 'fa-timeline'],
                ['titulo' => 'Log WebServices',               'url' => 'utilerias/log_ws.php',                   'icono' => 'fa-arrow-right-arrow-left'],
                ['titulo' => 'Browser ETL',                   'url' => 'etl/etl_browser.php',                    'icono' => 'fa-timeline'],
                ['titulo' => 'Administrador Procesos ',       'url' => 'etl/administrador_procesos.php',         'icono' => 'fa-arrow-right-arrow-left'],
                ['titulo' => 'Generador de Catálogos',        'url' => 'utilerias/generador.php', 		 'icono' => 'fa-clipboard-list'],
            ],
	    // PQRS
		'PQRS' => [
                ['titulo' => 'Control de incidencias', 'url' => 'pqrs/pqrs.php', 'icono' => 'fa-timeline'],
                
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

        <!-- CERRAR SESIÓN -->
        <div class="menu-header" onclick="window.location.href='<?= $baseUrl ?>logout.php'">
            <div class="left">
                <i class="fa fa-sign-out-alt"></i> Cerrar Sesión
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.menu-toggle').forEach(function (header) {
                header.addEventListener('click', function () {
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