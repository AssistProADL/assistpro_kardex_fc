<?php
// Header + menú global
include '../../bi/_menu_global.php';
?>

<div class="container-fluid mt-3">

    <h3 class="mb-4">📊 Dashboard Encuestas SFA</h3>

    <!-- KPIs -->
    <div class="row mb-4">

        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="text-muted">% Cumplimiento</h6>
                    <h2 class="text-success" id="kpiCumplimiento">0%</h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body">
                    <h6 class="text-muted">Pendientes Hoy</h6>
                    <h2 class="text-danger" id="kpiPendientes">0</h2>
                </div>
            </div>
        </div>

    </div>

    <!-- Gráfica -->
    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">Cumplimiento por Vendedor</h6>
            <canvas id="chartVendedores" height="120"></canvas>
        </div>
    </div>

</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    fetch('/api/sfa/encuestas/dashboard_resumen.php')
        .then(r => r.json())
        .then(data => {

            if (!data.ok) return;

            document.getElementById('kpiCumplimiento').innerText =
                data.kpis.cumplimiento + '%';

            document.getElementById('kpiPendientes').innerText =
                data.kpis.pendientes;

            const labels = data.vendedores.map(v => v.nombre);
            const values = data.vendedores.map(v => v.total);

            new Chart(document.getElementById('chartVendedores'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Encuestas contestadas',
                        data: values
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    }
                }
            });

        });
</script>

<?php
// Footer + cierre menú global
include '../../bi/_menu_global_end.php';
?>