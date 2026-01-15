<?php include '../bi/_menu_global.php'; ?>
<?php
$promo_id = $_GET['id'] ?? null;
if (!$promo_id) {
    echo '<div class="alert alert-danger">Error: Promoción no especificada. <a href="promociones.php">Volver</a></div>';
    include '../bi/_menu_global_end.php';
    exit;
}

?>

<div class="container-fluid">
    <h4 id="tituloPage">Asignar Clientes</h4>

    <table id="tblClientes" class="table table-striped">
        <thead>
            <tr>
                <th></th>
                <th>Cliente</th>
                <th>Nombre</th>
            </tr>
        </thead>
    </table>

    <button id="btnAsignar" class="btn btn-primary mt-2">
        Asignar promoción
    </button>

    <!-- Modal Confirmación -->
    <div class="modal fade" id="modalConfirm" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Asignación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Selecciona la promoción a asignar:</p>
                    <select id="selPromo" class="form-select mb-3"></select>
                    <p>Se asignará a <span id="lblCount" class="fw-bold"></span> clientes seleccionados.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmar">Aplicar</button>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Scripts necesarios -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    let tabla = $('#tblClientes').DataTable({
        ajax: {
            url: '../api/clientes.php?action=list',
            dataSrc: ''
        },
        columns: [
            {
                data: null,
                render: function (data, type, row) {
                    return `<input type="checkbox" value="${row.id_cliente}">`;
                }
            },
            { data: 'Cve_Clte' },
            { data: 'RazonSocial' }
        ]
    });

    const currentPromoId = '<?= $promo_id ?>';
    let promosCargadas = false;

    // Cargar nombre en titulo init
    if (currentPromoId) {
        fetch(`../api/promociones/promociones.php?action=get&id=${currentPromoId}`)
            .then(r => r.json())
            .then(r => {
                if (r.ok && r.data) {
                    document.getElementById('tituloPage').innerHTML =
                        `Asignar Clientes a: <span class="text-primary">${r.data.Lista}</span>`;
                }
            });
    }

    // Cargar lista de promociones para el modal
    function cargarPromociones() {
        if (promosCargadas) return;

        fetch('../api/promociones/promociones.php?action=list')
            .then(r => r.json())
            .then(r => {
                if (r.ok) {
                    const sel = document.getElementById('selPromo');
                    sel.innerHTML = '';
                    r.data.forEach(p => {
                        const opt = document.createElement('option');
                        opt.value = p.id;
                        opt.text = p.Lista;
                        if (p.id == currentPromoId) opt.selected = true;
                        sel.appendChild(opt);
                    });
                    promosCargadas = true;
                }
            });
    }

    let selectedIds = [];

    $('#btnAsignar').click(function () {
        selectedIds = [];
        $('#tblClientes input:checked').each(function () {
            selectedIds.push(this.value);
        });

        if (selectedIds.length === 0) {
            alert('Selecciona al menos un cliente.');
            return;
        }

        // Cargar promos antes de mostrar
        cargarPromociones();

        $('#lblCount').text(selectedIds.length);

        const modal = new bootstrap.Modal(document.getElementById('modalConfirm'));
        modal.show();
    });

    $('#btnConfirmar').click(function () {
        const promoDestino = $('#selPromo').val();
        if (!promoDestino) {
            alert('Selecciona una promoción');
            return;
        }

        fetch('../api/promociones/index.php?action=scope_add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                promo_id: promoDestino,
                scope_tipo: 'CLIENTE',
                scope_ids: selectedIds
            })
        }).then(r => r.json()).then(r => {
            // Cerrar modal
            const modalEl = document.getElementById('modalConfirm');
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal.hide();

            alert('Asignación completa');
        });
    });
</script>

<?php include '../bi/_menu_global_end.php'; ?>