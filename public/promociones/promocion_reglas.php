<?php include '../bi/_menu_global.php'; ?>
<?php $promo_id = $_GET['id'] ?? null;

if (!$promo_id) {
    echo '<div class="alert alert-danger">Error: Promoción no especificada. <a href="promociones.php">Volver</a></div>';
    include '../bi/_menu_global_end.php';
    exit;
}
?>

<div class="container-fluid">
    <h4>Reglas / Escalones</h4>
    <p class="text-muted">Promoción ID: <?= $promo_id ?></p>

    <!-- Alta de regla -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-2">
                    <label>Nivel</label>
                    <input type="number" class="form-control" id="nivel" value="1">
                </div>
                <div class="col-md-3">
                    <label>Tipo objetivo</label>
                    <select class="form-control" id="trigger_tipo">
                        <option value="MONTO">Monto</option>
                        <option value="UNIDADES">Unidades</option>
                        <option value="MIXTO">Mixto</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Monto</label>
                    <input type="number" step="0.01" class="form-control" id="threshold_monto">
                </div>
                <div class="col-md-2">
                    <label>Unidades</label>
                    <input type="number" step="0.01" class="form-control" id="threshold_qty">
                </div>
                <div class="col-md-2">
                    <label>Acumula</label>
                    <select class="form-control" id="acumula">
                        <option value="S">Sí</option>
                        <option value="N">No</option>
                    </select>
                </div>
                <div class="col-md-3 mt-2">
                    <label>Periodo</label>
                    <select class="form-control" id="acumula_por">
                        <option value="TICKET">Ticket</option>
                        <option value="DIA">Día</option>
                        <option value="PERIODO">Periodo</option>
                    </select>
                </div>
            </div>
            <button id="btnAdd" class="btn btn-success mt-3">Agregar regla</button>
        </div>
    </div>

    <!-- Listado -->
    <table id="tblReglas" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Nivel</th>
                <th>Tipo</th>
                <th>Monto</th>
                <th>Unidades</th>
                <th>Acumula</th>
                <th>Periodo</th>
                <th></th>
            </tr>
        </thead>
    </table>
</div>

</div>

<!-- Scripts necesarios -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap5.min.js"></script>

<script>
    const promoId = '<?= $promo_id ?>';

    let tabla = $('#tblReglas').DataTable({
        ajax: {
            url: '../api/promociones/reglas.php?action=reglas_list&promo_id=' + promoId,
            dataSrc: 'data'
        },
        columns: [
            { data: 'nivel' },
            { data: 'trigger_tipo' },
            { data: 'threshold_monto' },
            { data: 'threshold_qty' },
            { data: 'acumula' },
            { data: 'acumula_por' },
            {
                render: r => `
        <a href="promocion_beneficios.php?id_rule=${r.id_rule}" class="btn btn-sm btn-primary">Beneficios</a>
        <button class="btn btn-sm btn-danger" onclick="del(${r.id_rule})">
          Eliminar
        </button>`
            }
        ]
    });

    $('#btnAdd').click(function () {
        fetch('../api/promociones/reglas.php?action=reglas_add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                promo_id: promoId,
                nivel: $('#nivel').val(),
                trigger_tipo: $('#trigger_tipo').val(),
                threshold_monto: $('#threshold_monto').val(),
                threshold_qty: $('#threshold_qty').val(),
                acumula: $('#acumula').val(),
                acumula_por: $('#acumula_por').val()
            })
        })
            .then(r => r.json())
            .then(r => {
                if (r.ok) {
                    tabla.ajax.reload();
                }
            });
    });

    function del(id) {
        if (!confirm('¿Eliminar regla?')) return;
        fetch('/api/promociones/reglas.php?action=reglas_delete&id=' + id)
            .then(() => tabla.ajax.reload());
    }
</script>

<?php include '../bi/_menu_global_end.php'; ?>