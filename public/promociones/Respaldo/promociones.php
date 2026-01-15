<?php include '../bi/_menu_global.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between mb-3">
        <h4>Promociones</h4>
        <a href="promocion_form.php" class="btn btn-primary">
            Nueva promoción
        </a>
    </div>

    <table id="tblPromos" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Lista</th>
                <th>Tipo</th>
                <th>Vigencia</th>
                <th>Activa</th>
                <th>Acciones</th>
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
    $(function () {
        $('#tblPromos').DataTable({
            ajax: {
                url: '../api/promociones/index.php?action=list',
                dataSrc: 'data'
            },
            columns: [
                { data: 'id' },
                { data: 'Lista' },
                { data: 'Tipo' },
                {
                    data: null,
                    render: function (data, type, row) {
                        return row.FechaI + ' / ' + row.FechaF;
                    }
                },
                {
                    data: null,
                    render: function (data, type, row) {
                        return row.Activa == 1 ? 'Sí' : 'No';
                    }
                },
                {
                    data: null,
                    render: function (data, type, row) {
                        return `
                          <a href="promocion_reglas.php?id=${row.id}" class="btn btn-sm btn-warning">Reglas</a>
                          <a href="promocion_form.php?id=${row.id}" class="btn btn-sm btn-info">Editar</a>
                          <a href="promocion_scope.php?id=${row.id}" class="btn btn-sm btn-secondary">Asignar</a>
                        `;
                    }
                }
            ]
        });
    });
</script>

<?php include '../bi/_menu_global_end.php'; ?>