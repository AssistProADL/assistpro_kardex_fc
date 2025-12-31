<?php
include '../bi/_menu_global.php';

$id_rule = $_GET['id_rule'] ?? 0;
?>

<div class="container-fluid mt-3">

    <h4>Beneficios de la Regla</h4>

    <input type="hidden" id="id_rule" value="<?= $id_rule ?>">

    <!-- ===== FORM BENEFICIO ===== -->
    <div class="card mb-3">
        <div class="card-body">

            <div class="row">

                <div class="col-md-3 mb-2">
                    <label>Tipo de beneficio</label>
                    <select id="reward_tipo" class="form-control">
                        <option value="DESC_PCT">Descuento %</option>
                        <option value="DESC_MONTO">Descuento $</option>
                        <option value="BONIF_PRODUCTO">Producto gratis</option>
                    </select>
                </div>

                <div class="col-md-2 mb-2">
                    <label>Valor</label>
                    <input type="number" id="valor" class="form-control">
                </div>

                <div class="col-md-3 mb-2">
                    <label>Artículo (si aplica)</label>
                    <input type="text" id="cve_articulo" class="form-control">
                </div>

                <div class="col-md-2 mb-2">
                    <label>Cantidad</label>
                    <input type="number" id="qty" class="form-control">
                </div>

                <div class="col-md-2 mb-2">
                    <label>Unidad</label>
                    <input type="text" id="unimed" class="form-control">
                </div>

            </div>

            <button class="btn btn-success mt-2" onclick="guardarBeneficio()">
                Agregar Beneficio
            </button>

        </div>
    </div>

    <!-- ===== LISTADO ===== -->
    <table class="table table-bordered table-sm">
        <thead class="table-light">
            <tr>
                <th>Tipo</th>
                <th>Valor</th>
                <th>Artículo</th>
                <th>Cantidad</th>
                <th>Unidad</th>
                <th width="80"></th>
            </tr>
        </thead>
        <tbody id="tablaBeneficios"></tbody>
    </table>

</div>

<script>
    /* ===== CARGAR ===== */
    document.addEventListener('DOMContentLoaded', cargarBeneficios);

    /* ===== LISTAR ===== */
    function cargarBeneficios() {

        const id_rule = document.getElementById('id_rule').value;

        fetch(`../api/promociones/index.php?action=rewards_list&id_rule=${id_rule}`)
            .then(r => r.json())
            .then(r => {

                const tbody = document.getElementById('tablaBeneficios');
                tbody.innerHTML = '';

                if (!r.ok) return;

                r.data.forEach(row => {

                    const tr = document.createElement('tr');

                    const tipos = {
                        'BONIF_PRODUCTO': 'Producto gratis',
                        'DESC_PCT': 'Descuento %',
                        'DESC_MONTO': 'Descuento $',
                        'CUPON_PCT_NEXT': 'Cupón %',
                        'CUPON_MONTO_NEXT': 'Cupón $'
                    };

                    tr.innerHTML = `
          <td>${tipos[row.reward_tipo] || row.reward_tipo}</td>
          <td>${row.valor ? parseFloat(row.valor).toFixed(2) : ''}</td>
          <td>${row.cve_articulo ?? ''}</td>
          <td>${row.qty ? parseFloat(row.qty).toFixed(2) : ''}</td>
          <td>${row.unimed ?? ''}</td>
          <td>
            <button class="btn btn-danger btn-sm"
              onclick="eliminarBeneficio(${row.id_reward})">
              X
            </button>
          </td>
        `;

                    tbody.appendChild(tr);
                });
            });
    }

    /* ===== GUARDAR ===== */
    function guardarBeneficio() {

        const data = {
            id_rule: document.getElementById('id_rule').value,
            reward_tipo: document.getElementById('reward_tipo').value,
            valor: document.getElementById('valor').value || null,
            cve_articulo: document.getElementById('cve_articulo').value || null,
            qty: document.getElementById('qty').value || null,
            unimed: document.getElementById('unimed').value || null
        };

        if (!data.id_rule) {
            alert('id_rule requerido');
            return;
        }

        fetch('../api/promociones/index.php?action=rewards_add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
            .then(r => r.json())
            .then(r => {
                if (r.ok) {
                    cargarBeneficios();
                } else {
                    alert(r.msg || 'Error');
                }
            });
    }

    /* ===== ELIMINAR ===== */
    function eliminarBeneficio(id) {

        if (!confirm('Eliminar beneficio?')) return;

        fetch(`../api/promociones/index.php?action=rewards_delete&id=${id}`)
            .then(r => r.json())
            .then(r => {
                if (r.ok) cargarBeneficios();
            });
    }
</script>

<?php
include '../bi/_menu_global_end.php';
?>