<?php
require_once __DIR__ . '/../../app/db.php';

$vendedores   = db_all("SELECT * FROM v_dashboard_creditos_vendedor");
$supervisores = db_all("SELECT * FROM v_dashboard_creditos_supervisor");
$clientes     = db_all("SELECT * FROM v_dashboard_creditos_cliente");

include __DIR__ . '/../bi/_menu_global.php';
?>

<style>
/* ======================================================
   ASSISTPRO DASHBOARD CREDITOS
====================================================== */

/* Offset interno del frame */
.content {
    padding: 15px 18px 20px 18px;
}

/* Títulos corporativos */
.assistpro-title {
    color: #0046ad;
    font-weight: 600;
    font-size: 14px;
}

.assistpro-title i {
    margin-right: 6px;
}

/* Tablas compactas */
.assistpro-table {
    font-size: 10px;
    line-height: 1.2;
    width: 100% !important;
}

.assistpro-table th,
.assistpro-table td {
    padding: 3px 6px !important;
    height: 22px;
    vertical-align: middle !important;
    white-space: nowrap;
}

.assistpro-table th {
    background: #f4f6f9;
    font-weight: 600;
    text-align: center;
}

/* Scroll interno */
.table-assistpro {
    max-height: 420px;
    overflow-y: auto;
    overflow-x: auto;
}

/* DataTables */
.dataTables_wrapper,
.dataTables_filter,
.dataTables_info,
.dataTables_paginate {
    font-size: 10px;
}

.dataTables_filter input {
    height: 20px;
    font-size: 10px;
    padding: 2px 6px;
}
</style>

<div class="content-wrapper">

    <!-- HEADER -->
    <section class="content-header">
        <h1>
            Dashboard de Créditos
            <small>Control por Vendedor / Supervisor / Cliente</small>
        </h1>
    </section>

    <section class="content">

        <!-- ================= CREDITOS POR VENDEDOR ================= -->
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title assistpro-title">
                    <i class="fa fa-user"></i> Créditos por Vendedor
                </h3>
            </div>

            <div class="box-body no-padding">
                <div class="table-responsive table-assistpro">
                    <table id="tblVendedores"
                           class="table table-bordered table-condensed table-hover assistpro-table">
                        <thead>
                        <tr>
                            <th>Vendedor</th>
                            <th>Clientes</th>
                            <th>Crédito Base</th>
                            <th>Crédito Extra</th>
                            <th>Crédito Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($vendedores as $v): ?>
                            <tr>
                                <td><?= htmlspecialchars($v['Vendedor']) ?></td>
                                <td class="text-center"><?= (int)$v['ClientesAsignados'] ?></td>
                                <td class="text-right">$<?= number_format($v['LimiteCreditoBase'] ?? 0, 2) ?></td>
                                <td class="text-right">$<?= number_format($v['LimiteCreditoExtra'] ?? 0, 2) ?></td>
                                <td class="text-right"><strong>$<?= number_format($v['LimiteCreditoTotal'] ?? 0, 2) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ================= CREDITOS POR SUPERVISOR ================= -->
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title assistpro-title">
                    <i class="fa fa-users"></i> Créditos por Supervisor
                </h3>
            </div>

            <div class="box-body no-padding">
                <div class="table-responsive table-assistpro">
                    <table id="tblSupervisores"
                           class="table table-bordered table-condensed table-hover assistpro-table">
                        <thead>
                        <tr>
                            <th>Supervisor</th>
                            <th>Vendedores</th>
                            <th>Crédito Base</th>
                            <th>Crédito Extra</th>
                            <th>Crédito Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$supervisores): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    No hay supervisores con crédito asignado
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($supervisores as $s): ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['Supervisor']) ?></td>
                                    <td class="text-center"><?= (int)$s['VendedoresAsignados'] ?></td>
                                    <td class="text-right">$<?= number_format($s['LimiteCreditoBase'] ?? 0, 2) ?></td>
                                    <td class="text-right">$<?= number_format($s['LimiteCreditoExtra'] ?? 0, 2) ?></td>
                                    <td class="text-right"><strong>$<?= number_format($s['LimiteCreditoTotal'] ?? 0, 2) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ================= CREDITOS POR CLIENTE ================= -->
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title assistpro-title">
                    <i class="fa fa-building"></i> Créditos por Cliente
                </h3>
            </div>

            <div class="box-body no-padding">
                <div class="table-responsive table-assistpro">
                    <table id="tblClientes"
                           class="table table-bordered table-condensed table-hover assistpro-table">
                        <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Base</th>
                            <th>Extra</th>
                            <th>Total</th>
                            <th>Saldo</th>
                            <th>Disponible</th>
                            <th>Riesgo %</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($clientes as $c): 
                            $riesgo = ($c['Riesgo'] ?? 0) * 100;
                            $label  = $riesgo >= 80 ? 'danger' : ($riesgo >= 50 ? 'warning' : 'success');
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($c['RazonSocial']) ?></td>
                                <td class="text-right">$<?= number_format($c['LimiteCreditoBase'] ?? 0, 2) ?></td>
                                <td class="text-right">$<?= number_format($c['LimiteCreditoExtra'] ?? 0, 2) ?></td>
                                <td class="text-right"><strong>$<?= number_format($c['LimiteCreditoTotal'] ?? 0, 2) ?></strong></td>
                                <td class="text-right">$<?= number_format($c['SaldoTotal'] ?? 0, 2) ?></td>
                                <td class="text-right">$<?= number_format($c['DisponibleCredito'] ?? 0, 2) ?></td>
                                <td class="text-center">
                                    <span class="label label-<?= $label ?>">
                                        <?= number_format($riesgo, 1) ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </section>
</div>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>

<script>
$(function () {

    const cfg = {
        pageLength: 25,
        lengthChange: false,
        autoWidth: false,
        scrollX: true,
        scrollY: '360px',
        scrollCollapse: true,
        ordering: true,
        language: {
            search: "Buscar:",
            zeroRecords: "Sin registros",
            info: "Mostrando _START_ a _END_ de _TOTAL_",
            paginate: {
                next: "Siguiente",
                previous: "Anterior"
            }
        }
    };

    $('#tblVendedores').DataTable(cfg);
    $('#tblSupervisores').DataTable(cfg);
    $('#tblClientes').DataTable(cfg);

});
</script>
