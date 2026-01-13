<?php
include '../bi/_menu_global.php';

$promo_id = $_GET['promo_id'] ?? 0;
?>

<div class="container-fluid mt-3">

    <h4>Asignación de Promoción</h4>

    <input type="hidden" id="promo_id" value="<?= $promo_id ?>">

    <!-- ===== FORM ASIGNACIÓN ===== -->
    <div class="card mb-3">
        <div class="card-body">

            <div class="row">

                <div class="col-md-3 mb-2">
                    <label>Tipo de asignación</label>
                    <select id="scope_tipo" class="form-control">
                        <option value="CLIENTE">Cliente</option>
                        <option value="RUTA">Ruta</option>
                        <option value="VENDEDOR">Vendedor</option>
                    </select>
                </div>

                <div class="col-md-6 mb-2">
                    <label>IDs a asignar (separados por coma)</label>
                    <input type="text" id="scope_ids" class="form-control" placeholder="Ej: C0001,C0002 o 1,2,3">
                </div>

            </div>

            <button class="btn btn-success mt-2" onclick="asignar()">
                Asignar
            </button>

        </div>
    </div>

    <!-- ===== LISTADO ===== -->
    <table class="table table-bordered table-sm">
        <thead class="table-light">
            <tr>
                <th>Tipo</th>
                <th>ID</th>
                <th width="80"></th>
            </tr>
        </thead>
        <tbody id="tablaScope"></tbody>
    </table>

</div>

<script>
    /* ===== CARGAR ===== */
    document.addEventListener('DOMContentLoaded', cargarAsignaciones);

    /* ===== LISTAR ===== */
    function cargarAsignaciones() {

        const promo_id = document.getElementById('promo_id').value;

        fetch(`/assistpro_kardex_fc/public/api/promociones/index.php?action=scope_list&promo_id=${promo_id}`)
            .then(r => r.json())
            .then(r => {

                const tbody = document.getElementById('tablaScope');
                tbody.innerHTML = '';

                if (!r.ok) return;

                r.data.forEach(row => {

                    const tr = document.createElement('tr');

                    tr.innerHTML = `
          <td>${row.scope_tipo}</td>
          <td>${row.scope_id}</td>
          <td>
            <button class="btn btn-danger btn-sm"
              onclick="eliminarAsignacion(${row.id_scope})">
              X
            </button>
          </td>
        `;

                    tbody.appendChild(tr);
                });
            });
    }

    /* ===== ASIGNAR ===== */
    function asignar() {

        const promo_id = document.getElementById('promo_id').value;
        const scope_tipo = document.getElementById('scope_tipo').value;
        const raw = document.getElementById('scope_ids').value;

        if (!promo_id || !raw) {
            alert('Datos incompletos');
            return;
        }

        const scope_ids = raw.split(',').map(v => v.trim()).filter(v => v);

        fetch('../api/promociones/index.php?action=scope_add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                promo_id,
                scope_tipo,
                scope_ids
            })
        })
            .then(r => r.json())
            .then(r => {
                if (r.ok) {
                    document.getElementById('scope_ids').value = '';
                    cargarAsignaciones();
                } else {
                    alert(r.msg || 'Error');
                }
            });
    }

    /* ===== ELIMINAR ===== */
    function eliminarAsignacion(id) {

        if (!confirm('Eliminar asignación?')) return;

        fetch(`../api/promociones/index.php?action=scope_delete&id=${id}`)
            .then(r => r.json())
            .then(r => {
                if (r.ok) cargarAsignaciones();
            });
    }
</script>

<?php
include '../bi/_menu_global_end.php';
?>