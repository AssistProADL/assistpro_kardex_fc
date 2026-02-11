<?php
// public/dashboard/lta/index.php
require_once __DIR__ . '/../../../app/db.php';
require_once __DIR__ . '/../../bi/_menu_global.php';

$pdo = db_pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ================== CATÁLOGOS PARA FILTROS ==================
$empresas = [];
$almacenes = [];
$proveedores = [];

try {
    // Empresas (si no existe catálogo, se deja fijo)
    $empresas = [
        ['id'=>1,'nombre'=>'Empresa Principal']
    ];

    // Almacenes
    $almacenes = $pdo->query("
        SELECT clave, nombre 
        FROM c_almacenp 
        ORDER BY nombre
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Proveedores
    $proveedores = $pdo->query("
        SELECT ID_Proveedor, Nombre 
        FROM c_proveedores 
        WHERE Activo=1 OR Activo IS NULL
        ORDER BY Nombre
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch(Throwable $e){
    // silencioso: dashboard no debe caerse
}
?>

<link rel="stylesheet" href="/assets/dashboard.css">

<div class="container-fluid py-3">

    <!-- ================== HEADER ================== -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">LTA · Lead Time Analysis</h4>
        <span class="text-muted" style="font-size:12px;">
            Seguimiento integral de Órdenes de Compra
        </span>
    </div>

    <!-- ================== FILTROS ================== -->
    <div class="card mb-3">
        <div class="card-body p-2">
            <div class="row g-2 align-items-end">

                <div class="col-md-2">
                    <label class="form-label">Empresa</label>
                    <select id="f_empresa" class="form-select form-select-sm">
                        <?php foreach($empresas as $e): ?>
                            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Almacén</label>
                    <select id="f_almacen" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach($almacenes as $a): ?>
                            <option value="<?= $a['clave'] ?>">
                                <?= $a['clave'].' - '.$a['nombre'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Proveedor</label>
                    <select id="f_proveedor" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach($proveedores as $p): ?>
                            <option value="<?= $p['ID_Proveedor'] ?>">
                                <?= htmlspecialchars($p['Nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Tipo OC</label>
                    <select id="f_tipo_oc" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <option value="GENERAL">General</option>
                        <option value="XD">XD · Pedidos internos</option>
                        <option value="OTS">OTS · Órdenes de trabajo</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Desde</label>
                    <input type="date" id="f_desde" class="form-control form-control-sm">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Hasta</label>
                    <input type="date" id="f_hasta" class="form-control form-control-sm">
                </div>

            </div>
        </div>
    </div>

    <!-- ================== KPI RESUMEN ================== -->
    <div class="row g-2 mb-3">

        <div class="col-md-3">
            <div class="card kpi-card">
                <div class="kpi-title">OC Abiertas</div>
                <div class="kpi-value" id="kpi_oc_abiertas">—</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card kpi-card">
                <div class="kpi-title">En Tránsito</div>
                <div class="kpi-value" id="kpi_transito">—</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card kpi-card">
                <div class="kpi-title">Lead Time Promedio</div>
                <div class="kpi-value" id="kpi_leadtime">—</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card kpi-card">
                <div class="kpi-title">Retrasos</div>
                <div class="kpi-value text-danger" id="kpi_retrasos">—</div>
            </div>
        </div>

    </div>

    <!-- ================== NAVEGACIÓN INTERNA ================== -->
    <ul class="nav nav-tabs mb-3" id="ltaTabs">
        <li class="nav-item">
            <a class="nav-link active" data-target="resumen">Resumen</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-target="oc">Órdenes de Compra</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-target="proveedor">Proveedores</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-target="transito">Tránsito</a>
        </li>
    </ul>

    <!-- ================== CONTENEDOR DASHBOARD ================== -->
    <div id="ltaContent" class="card">
        <div class="card-body" id="ltaBody">
            <div class="text-muted text-center py-5">
                Selecciona un dashboard
            </div>
        </div>
    </div>

</div>

<script src="/assets/dashboard.js"></script>

<script>
/* ================== LTA JS CORE ================== */

const LTA = {
    filtros: () => ({
        empresa: document.getElementById('f_empresa').value,
        almacen: document.getElementById('f_almacen').value,
        proveedor: document.getElementById('f_proveedor').value,
        tipo_oc: document.getElementById('f_tipo_oc').value,
        desde: document.getElementById('f_desde').value,
        hasta: document.getElementById('f_hasta').value
    }),

    cargarVista: async (vista) => {
        const body = document.getElementById('ltaBody');
        body.innerHTML = '<div class="text-center py-4">Cargando...</div>';

        // aquí después se cargan lta_resumen.php, lta_oc.php, etc
        body.innerHTML = `
            <div class="text-center py-5 text-muted">
                Dashboard <b>${vista}</b> en construcción
            </div>
        `;
    }
};

// Tabs
document.querySelectorAll('#ltaTabs .nav-link').forEach(tab=>{
    tab.addEventListener('click', ()=>{
        document.querySelectorAll('#ltaTabs .nav-link')
            .forEach(t=>t.classList.remove('active'));
        tab.classList.add('active');
        LTA.cargarVista(tab.dataset.target);
    });
});

// Inicial
LTA.cargarVista('resumen');
</script>

<?php require_once __DIR__ . '/../../bi/_menu_global_end.php'; ?>
