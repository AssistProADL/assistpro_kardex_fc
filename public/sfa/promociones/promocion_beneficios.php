<?php include '../bi/_menu_global.php'; ?>
<?php
$id_rule = $_GET['id_rule'] ?? null;
if (!$id_rule) {
    echo '<div class="alert alert-danger">Error: Regla no especificada. <a href="promociones.php">Volver</a></div>';
    include '../bi/_menu_global_end.php';
    exit;
}
?>

<div class="container-fluid">
    <h4>Beneficios de la Regla</h4>
    <p class="text-muted">Regla ID: <?= $id_rule ?></p>

    <!-- Alta beneficio -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label>Tipo beneficio</label>
                    <select class="form-control" id="reward_tipo">
                        <option value="BONIF_PRODUCTO">Producto gratis</option>
                        <option value="DESC_PCT">% Descuento</option>
                        <option value="DESC_MONTO">$ Descuento</option>
                        <option value="CUPON_PCT_NEXT">% Próx. compra</option>
                        <option value="CUPON_MONTO_NEXT">$ Próx. compra</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label>Valor</label>
                    <input type="number" step="0.01" class="form-control" id="valor">
                </div>

                <div class="col-md-3">
                    <label>Artículo</label>
                    <input class="form-control" id="cve_articulo">
                </div>

                <div class="col-md-2">
                    <label>Cantidad</label>
                    <input type="number" step="0.01" class="form-control" id="qty">
                </div>

                <div class="col-md-2">
                    <label>U. Med</label>
                    <input class="form-control" id="unimed">
                </div>
            </div>

            <button id="btnAdd" class="btn btn-success mt-3">
                Agregar beneficio
            </button>
        </div>
    </div>

    <!-- Listado -->
    <table id="tblRewards" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Valor</th>
                <th>Artículo</th>
                <th>Cant.</th>
                <th>UM</th>
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
    const ruleId = '<?= $id_rule ?>';

    let tabla = $('#tblRewards').DataTable({
        ajax: {
            url: '../api/promociones/rewards.php?action=rewards_list&id_rule=' + ruleId,
            dataSrc: 'data'
        },
        columns: [
            {
                data: 'reward_tipo',
                render: function (data) {
                    const tipos = {
                        'BONIF_PRODUCTO': 'Producto gratis',
                        'DESC_PCT': 'Descuento %',
                        'DESC_MONTO': 'Descuento $',
                        'CUPON_PCT_NEXT': 'Cupón %',
                        'CUPON_MONTO_NEXT': 'Cupón $'
                    };
                    return tipos[data] || data;
                }
            },
            {
                data: 'valor',
                render: function (data) {
                    return data ? parseFloat(data).toFixed(2) : '';
                }
            },
            { data: 'cve_articulo' },
            {
                data: 'qty',
                render: function (data) {
                    return data ? parseFloat(data).toFixed(2) : '';
                }
            },
            { data: 'unimed' },
            {
                data: null,
                render: function (data, type, row) {
                    return `
                      <button class="btn btn-sm btn-danger" onclick="del(${row.id_reward})">
                        Eliminar
                      </button>`;
                }
            }
        ]
    });

    $('#btnAdd').click(function () {
        fetch('../api/promociones/rewards.php?action=rewards_add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_rule: ruleId,
                reward_tipo: $('#reward_tipo').val(),
                valor: $('#valor').val(),
                cve_articulo: $('#cve_articulo').val(),
                qty: $('#qty').val(),
                unimed: $('#unimed').val()
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
        if (!confirm('¿Eliminar beneficio?')) return;
        fetch('/api/promociones/rewards.php?action=rewards_delete&id=' + id)
            .then(() => tabla.ajax.reload());
    }
</script>

<?php include '../bi/_menu_global_end.php'; ?>