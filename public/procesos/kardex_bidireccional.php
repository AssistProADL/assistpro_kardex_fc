<?php
// public/procesos/kardex_bidireccional.php
require_once __DIR__ . '/../bi/_menu_global.php';
$TITLE = "Kardex Bidireccional";
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<title><?= $TITLE ?></title>

<!-- Bootstrap + DataTables -->
<link rel="stylesheet" href="/assistpro_kardex_fc/assets/bootstrap.min.css">
<link rel="stylesheet" href="/assistpro_kardex_fc/assets/datatables.min.css">

<style>
:root{
    --azul:#0F5AAD;
    --slate:#334155;
}
body{background:#f3f4f6;font-family:Segoe UI,Roboto,Helvetica;font-size:10px}

.container-ap{padding:15px}
h2{color:var(--slate);font-size:16px;font-weight:700;margin-bottom:15px}

/* Filtros */
.filtros{
    display:grid;
    grid-template-columns:repeat(8,minmax(150px,1fr));
    gap:10px;
    background:#fff;
    padding:12px;
    border-radius:10px;
    border:1px solid #e2e8f0;
}
.filtros label{font-size:11px;font-weight:600;color:#475569}
.filtros input, .filtros select{
    width:100%;padding:5px 6px;font-size:11px;
    border:1px solid #cbd5e1;border-radius:6px;
}
.btn-ap{
    background:var(--azul);color:#fff;font-size:11px;
    padding:6px 12px;border-radius:6px;border:none;font-weight:600;
}

/* Cards KPI */
.cards{
    display:grid;
    grid-template-columns:repeat(4,minmax(180px,1fr));
    gap:10px;margin-top:15px;
}
.card-ap{
    background:#fff;border-radius:12px;
    border:1px solid #e2e8f0;padding:12px;
}
.card-ap h4{margin:0;font-size:12px;color:#475569}
.card-ap .big{font-size:20px;font-weight:700;margin-top:5px}
.card-ap .sub{color:#64748b;font-size:11px}

/* Tabla */
.tablewrap{
    margin-top:15px;background:#fff;
    border-radius:10px;border:1px solid #e2e8f0;
    padding:10px;
}
table.dataTable tbody tr{font-size:10px}
table{width:100%!important;min-width:2600px!important}
</style>
</head>

<body>
<div class="container-ap">

<h2><?= $TITLE ?></h2>

<!-- ======================= FILTROS ======================= -->
<form method="get">
<div class="filtros">

    <div>
        <label>Empresa</label>
        <select name="empresa_id">
            <option value="">Seleccione…</option>
        </select>
    </div>

    <div>
        <label>Producto / SKU</label>
        <input type="text" name="producto" placeholder="SKU / Código">
    </div>

    <div>
        <label>Lote</label>
        <input type="text" name="lote">
    </div>

    <div>
        <label>Serie</label>
        <input type="text" name="serie">
    </div>

    <div>
        <label>Almacén Origen</label>
        <select name="alm_ori_id"><option value="">Seleccione…</option></select>
    </div>

    <div>
        <label>Almacén Destino</label>
        <select name="alm_dst_id"><option value="">Seleccione…</option></select>
    </div>

    <div>
        <label>Fecha Inicio</label>
        <input type="date" name="f_ini">
    </div>

    <div>
        <label>Fecha Fin</label>
        <input type="date" name="f_fin">
    </div>

</div>

<div style="margin-top:10px;">
    <button class="btn-ap" type="submit">Aplicar Filtros</button>
</div>

</form>

<!-- ======================= CARDS ======================= -->
<div class="cards">

    <div class="card-ap">
        <h4>Entradas</h4>
        <div class="big">0</div>
        <div class="sub">Unidades</div>
    </div>

    <div class="card-ap">
        <h4>Salidas</h4>
        <div class="big">0</div>
        <div class="sub">Unidades</div>
    </div>

    <div class="card-ap">
        <h4>Movs Internos</h4>
        <div class="big">0</div>
        <div class="sub">Reubicaciones</div>
    </div>

    <div class="card-ap">
        <h4>Saldo Actual</h4>
        <div class="big">0</div>
        <div class="sub">En Sistema</div>
    </div>

</div>

<!-- ======================= TABLA ======================= -->
<div class="tablewrap">
<table id="tblKardex" class="table table-striped table-bordered display compact" style="width:100%">
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Movimiento</th>
            <th>Tipo</th>
            <th>Artículo</th>
            <th>Descripción</th>
            <th>Lote</th>
            <th>Serie</th>
            <th>Almacén Origen</th>
            <th>Ubicación Origen</th>
            <th>Almacén Destino</th>
            <th>Ubicación Destino</th>
            <th>Entrada</th>
            <th>Salida</th>
            <th>Saldo</th>
            <th>Documento</th>
            <th>Usuario</th>
        </tr>
    </thead>
    <tbody>
        <!-- Sin conexión: tabla vacía -->
    </tbody>
</table>
</div>

</div> <!-- /container -->

<script src="/assistpro_kardex_fc/assets/jquery.min.js"></script>
<script src="/assistpro_kardex_fc/assets/datatables.min.js"></script>

<script>
$(document).ready(function(){
    $('#tblKardex').DataTable({
        pageLength:25,
        scrollX:true,
        scrollY:'420px',
        order:[[0,'desc']]
    });
});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
