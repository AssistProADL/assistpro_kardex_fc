<?php
include '../bi/_menu_global.php';

$promo_id = $_GET['promo_id'] ?? 0;
?>

<div class="container-fluid mt-3">

    <h4>Reglas de Promoción</h4>

    <input type="hidden" id="promo_id" value="<?= $promo_id ?>">

    <!-- ===== FORM REGLA ===== -->
    <div class="card mb-3">
        <div class="card-body">

            <div class="row">
                <div class="col-md-2 mb-2">
                    <label>Nivel</label>
                    <input type="number" id="nivel" class="form-control">
                </div>

                <div class="col-md-3 mb-2">
                    <label>Tipo disparador</label>
                    <select id="trigger_tipo" class="form-control">
                        <option value="MONTO">Monto</option>
                        <option value="UNIDADES">Unidades</option>
                    </select>
                </div>

                <div class="col-md-3 mb-2">
                    <label>Monto mínimo</label>
                    <input type="number" id="threshold_monto" class="form-control">
                </div>

                <div class="col-md-3 mb-2">
                    <label>Cantidad mínima</label>
                    <input type="number" id="threshold_qty" class="form-control">
                </div>
            </div>

            <button class="btn btn-success mt-2" onclick="guardarRegla()">
                Agregar Regla
            </button>

        </div>
    </div>

    <!-- ===== LISTADO ===== -->
    <table class="table table-bordered table-sm">
        <thead class="table-light">
            <tr>
                <th>Nivel</th>
                <th>Tipo</th>
                <th>Monto</th>
                <th>Cantidad</th>
                <th width="80"></th>
            </tr>
        </thead>
        <tbody id="tablaReglas"></tbody>
    </table>

</div>

<script>
    /* ===== CARGAR ===== */
    document.addEventListener('DOMContentLoaded', cargarReglas);

    /* ===== LISTAR ===== */
    function cargarReglas() {

        const promo_id = document.getElementById('promo_id').value;

        fetch(`../api/promociones/index.php?action=reglas_list&promo_id=${promo_id}`)
            .then(r => r.json())
            .then(r => {

                const tbody = document.getElementById('tablaReglas');
                tbody.innerHTML = '';

                if (!r.ok) return;

                r.data.forEach(row => {

                    const tr = document.createElement('tr');

                    tr.innerHTML = `
          <td>${row.nivel}</td>
          <td>${row.trigger_tipo}</td>
          <td>${row.threshold_monto ?? ''}</td>
          <td>${row.threshold_qty ?? ''}</td>
          <td>
            <button class="btn btn-danger btn-sm"
              onclick="eliminarRegla(${row.id_rule})">
              X
            </button>
          </td>
        `;

                    tbody.appendChild(tr);
                });
            });
    }

    /* ===== GUARDAR ===== */
    function guardarRegla() {

        const data = {
            promo_id: document.getElementById('promo_id').value,
            nivel: document.getElementById('nivel').value,
            trigger_tipo: document.getElementById('trigger_tipo').value,
            threshold_monto: document.getElementById('threshold_monto').value || null,
            threshold_qty: document.getElementById('threshold_qty').value || null
        };

        if (!data.nivel) {
            alert('Nivel requerido');
            return;
        }

        fetch('../api/promociones/index.php?action=reglas_add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
            .then(r => r.json())
            .then(r => {
                if (r.ok) {
                    cargarReglas();
                } else {
                    alert(r.msg || 'Error');
                }
            });
    }

    /* ===== ELIMINAR ===== */
    function eliminarRegla(id) {

        if (!confirm('Eliminar regla?')) return;

        fetch(`../api/promociones/index.php?action=reglas_delete&id=${id}`)
            .then(r => r.json())
            .then(r => {
                if (r.ok) cargarReglas();
            });
    }
</script>

<?php
include '../bi/_menu_global_end.php';
?>