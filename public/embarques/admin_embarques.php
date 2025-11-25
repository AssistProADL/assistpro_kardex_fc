<?php
// ======================================================================
//  ADMINISTRACIÓN DE EMBARQUES (Vista Corporativa, sin datos reales)
//  Ruta: /public/procesos/admin_embarques.php
// ======================================================================

require_once __DIR__ . '/../bi/_menu_global.php';

$hoy   = date('Y-m-d');
$hace7 = date('Y-m-d', strtotime('-7 days'));
?>

<style>
    :root{
        --azul-primario:#0F5AAD;
        --azul-acento:#00A3E0;
        --gris-borde:#dbe3f4;
        --gris-fondo:#f6f8fc;
        --gris-td:#f9fbff;
        --gris-header:#e5e6e7;
        --texto:#1f2937;
    }

    html, body{
        font-family: Calibri, "Segoe UI", Roboto, Arial, sans-serif;
        color: var(--texto);
        font-size: 12px;
    }

    .panel-admin{ padding:15px; }

    .ibox-title h3{
        font-weight:700;
        color:#1b2a52;
        margin:0;
    }

    .box-header-dark{
        background: var(--azul-primario);
        color:#fff;
        padding:8px 12px;
        font-size:12px;
        font-weight:700;
        border-radius:8px 8px 0 0;
        letter-spacing:.2px;
    }
    .box-body{
        background: var(--gris-fondo);
        border:1px solid var(--gris-borde);
        border-top:none;
        padding:12px;
        border-radius:0 0 8px 8px;
    }

    .filtros .form-group{ margin-bottom:8px; }
    .filtros label{
        font-size:10px; font-weight:700; color:#2b3e6b; margin-bottom:3px;
        text-transform:uppercase; letter-spacing:.2px;
    }
    .filtros .form-control{
        font-size:12px; height:32px; padding:4px 8px; border-radius:8px;
        border:1px solid var(--gris-borde); background:#fff;
    }
    .filtros .form-control:focus{
        outline:none; border-color: var(--azul-acento);
        box-shadow:0 0 0 3px rgba(0,163,224,.15);
    }
    .btn-primary{
        background: var(--azul-primario); border-color: var(--azul-primario);
        font-size:11px; padding:6px 14px; border-radius:999px;
    }
    .btn-primary:hover{ filter:brightness(.95); }

    .tabla{
        width:100%; border-collapse:separate; border-spacing:0;
        background:#fff; border:1px solid var(--gris-borde);
        border-radius:8px; overflow:hidden;
    }
    .tabla th, .tabla td{
        font-size:10px !important;
        white-space:nowrap; vertical-align:middle;
        padding:6px 8px;
    }
    .tabla thead th{
        background: var(--gris-header); color:#333; font-weight:700; border-bottom:0;
    }
    .tabla tbody tr:nth-child(even){ background: var(--gris-td); }
    .tabla tbody tr:hover{ background:#eef5ff; }

    .dataTables_wrapper .dataTables_filter input{
        height:30px; border-radius:8px; border:1px solid var(--gris-borde);
        font-size:12px;
    }
    .dataTables_wrapper .dataTables_length select{
        height:28px; border-radius:6px; border:1px solid var(--gris-borde);
        font-size:10px;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button{
        border:1px solid var(--gris-borde)!important; border-radius:6px!important;
        padding:2px 8px!important; margin:0 2px!important; background:#fff!important;
        color:#2b3e6b!important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current{
        background: var(--azul-primario)!important; color:#fff!important; border-color: var(--azul-primario)!important;
    }

    .acciones{
        white-space:nowrap;
    }
    .acciones .btn-icon{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        width:20px;
        height:20px;
        padding:0;
        margin-right:2px;
        border-radius:4px;
        border:1px solid transparent;
        background:transparent;
        color:#222;
        font-size:10px;
    }
    .acciones .btn-icon:hover{
        background:#eef5ff;
        border-color:var(--gris-borde);
    }

    .toolbar{ display:flex; align-items:flex-end; gap:10px; flex-wrap:wrap; }
</style>

<div class="panel-admin">
    <div class="wrapper wrapper-content animated fadeInRight">
        <div class="row">
            <div class="col-lg-12">
                <div class="ibox">
                    <div class="ibox-title">
                        <h3><i class="fa fa-truck"></i> Administración de Embarques</h3>
                    </div>

                    <div class="ibox-content">

                        <!-- FILTROS -->
                        <div class="box-header-dark">Filtros</div>
                        <div class="box-body">
                            <div class="row filtros">
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label>Almacén</label>
                                        <select class="form-control">
                                            <option>(100) Producto Terminado ADVL</option>
                                            <option>(200) Materia Prima</option>
                                            <option>(300) Refacciones</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label>Ruta</label>
                                        <select class="form-control">
                                            <option>Seleccione Ruta</option>
                                            <option>RT01</option>
                                            <option>RT02</option>
                                            <option>RT03</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-2 col-sm-6">
                                    <div class="form-group">
                                        <label>Estatus</label>
                                        <select class="form-control">
                                            <option>En Ruta</option>
                                            <option>Planeado</option>
                                            <option>Entregado</option>
                                            <option>Cancelado</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-2 col-sm-6">
                                    <div class="form-group">
                                        <label>Fecha Inicial</label>
                                        <input type="date" class="form-control" value="<?= $hace7 ?>">
                                    </div>
                                </div>

                                <div class="col-md-2 col-sm-6">
                                    <div class="form-group">
                                        <label>Fecha Final</label>
                                        <input type="date" class="form-control" value="<?= $hoy ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="toolbar">
                                <div class="form-group" style="flex:1; min-width:220px;">
                                    <label>Buscar…</label>
                                    <input type="text" class="form-control" placeholder="Folio, cliente, transportista, placas…">
                                </div>
                                <button class="btn btn-primary"><i class="fa fa-search"></i> Buscar</button>
                            </div>
                        </div>

                        <br>

                        <!-- TABLA -->
                        <div class="box-header-dark">Listado de Embarques</div>
                        <div class="box-body">
                            <div class="table-responsive">
                                <table id="tabla_admin_emb" class="table table-striped table-bordered table-hover tabla" style="width:100%;">
                                    <thead>
                                    <tr>
                                        <th>Acción</th>
                                        <th>Folio</th>
                                        <th>Ruta</th>
                                        <th>Stops</th>
                                        <th>Pedidos</th>
                                        <th>Fecha de Embarque</th>
                                        <th>Fecha Entrega</th>
                                        <th>Status</th>
                                        <th>Transporte</th>
                                        <th>Clave</th>
                                        <th>Placas</th>
                                        <th>Cap. Volumen</th>
                                        <th>Volumen Utilizado</th>
                                        <th>Peso Máximo</th>
                                        <th>Peso Utilizado</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $rows = [
                                        ['977','(1000)','1','1','2025-10-16 09:30:48','--','EN RUTA','Transportes Luna','LUN01','CMD354','0.0000','0.6801','50','60.00'],
                                        ['956','(1000)','1','1','2025-09-22 10:40:12','--','EN RUTA','Transportes Luna','LUN01','CMD354','0.1810','50','60.357',''],
                                        ['955','(1000)','1','1','2025-09-22 14:49:27','--','EN RUTA','Transportes Luna','LUN01','CMD354','0.0007','50','2.5',''],
                                        ['954','(1000)','1','1','2025-09-22 14:40:09','--','EN RUTA','Transportes Luna','LUN01','CMD354','0.0007','50','2.5',''],
                                        ['951','(1000)','1','1','2025-09-22 15:27:13','--','EN RUTA','Transportes Luna','LUN01','CMD354','0.0000','50','1',''],
                                        ['950','(1000)','1','2','2025-09-22 12:31:55','--','EN RUTA','Transportes Luna','LUN01','CMD354','0.0010','50','0.024',''],
                                        ['945','(6000)','2','2','2025-09-17 00:07:06','--','EN RUTA','Transportes Luna','LUN01','CMD354','3.0340','50','80.12',''],
                                        ['944','(1000)','1','1','2025-09-17 00:06:32','--','EN RUTA','Transportes Luna','LUN01','CMD354','0.0010','50','0.024',''],
                                        ['941','(1000)','1','1','2025-09-17 00:04:37','--','EN RUTA','Transportes Luna','LUN01','CMD354','0.0000','50','75',''],
                                        ['910','(1000)','1','1','2025-09-16 23:59:02','--','EN RUTA','Transportes Luna','LUN01','CMD354','0.0400','50','13.2',''],
                                    ];
                                    foreach($rows as $r):
                                        ?>
                                        <tr>
                                            <td class="acciones">
                                                <!-- Íconos del módulo original: Search + 4 Docs + Pin + Check + Up + Down -->
                                                <button class="btn-icon" title="Ver detalle"><i class="fa fa-search"></i></button>
                                                <button class="btn-icon" title="Reporte 1"><i class="fa-regular fa-file"></i></button>
                                                <button class="btn-icon" title="Reporte 2"><i class="fa-regular fa-file"></i></button>
                                                <button class="btn-icon" title="Reporte 3"><i class="fa-regular fa-file"></i></button>
                                                <button class="btn-icon" title="Reporte 4"><i class="fa-regular fa-file"></i></button>
                                                <button class="btn-icon" title="Ver en mapa"><i class="fa fa-location-dot"></i></button>
                                                <button class="btn-icon" title="Confirmar"><i class="fa fa-check"></i></button>
                                                <button class="btn-icon" title="Subir documento"><i class="fa fa-upload"></i></button>
                                                <button class="btn-icon" title="Descargar documento"><i class="fa fa-download"></i></button>
                                            </td>
                                            <td><?= htmlspecialchars($r[0]) ?></td>
                                            <td><?= htmlspecialchars($r[1]) ?></td>
                                            <td><?= htmlspecialchars($r[2]) ?></td>
                                            <td><?= htmlspecialchars($r[3]) ?></td>
                                            <td><?= htmlspecialchars($r[4]) ?></td>
                                            <td><?= htmlspecialchars($r[5]) ?></td>
                                            <td><?= htmlspecialchars($r[6]) ?></td>
                                            <td><?= htmlspecialchars($r[7]) ?></td>
                                            <td><?= htmlspecialchars($r[8]) ?></td>
                                            <td><?= htmlspecialchars($r[9]) ?></td>
                                            <td><?= htmlspecialchars($r[10]) ?></td>
                                            <td><?= htmlspecialchars($r[11]) ?></td>
                                            <td><?= htmlspecialchars($r[12]) ?></td>
                                            <td><?= htmlspecialchars($r[13]) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div><!-- /.ibox-content -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(function(){
        $('#tabla_admin_emb').DataTable({
            pageLength: 30,
            scrollX: true,
            order: [[1,'desc']],
            language: { url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json" },
            lengthChange:false
        });
    });
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
