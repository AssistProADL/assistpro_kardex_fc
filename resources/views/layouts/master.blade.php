@php
    // Incluir el menú global (legacy)
    // Se usa include directo para que se ejecute en el contexto actual
    // Ajustamos la ruta para que sea absoluta desde la raíz del proyecto
    $rootPath = realpath(__DIR__ . '/../../../public');
    include $rootPath . '/bi/_menu_global.php';
@endphp

<!-- Estilos Globales Extra -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet" />
<link href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" rel="stylesheet" />

<!-- Sección para Estilos Personalizados -->
@yield('styles')

<!-- Estructura Principal -->
<div class="ap-shell">
    <main>
        <div class="container-fluid">
            @yield('content')
        </div>
    </main>
</div>

<!-- Scripts Globales Extra -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<!-- Sección para Scripts Personalizados -->
@yield('scripts')

@php
    // Incluir el cierre global (legacy)
    include $rootPath . '/bi/_menu_global_end.php';
@endphp