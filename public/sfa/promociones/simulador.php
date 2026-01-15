<?php
require_once __DIR__ . '/../../bi/_menu_global.php';
?>

<div class="container-fluid mt-3">

    <h4>Simulador de Promociones</h4>

    <!-- ===== FORM SIMULACIÓN ===== -->
    <div class="card mb-3">
        <div class="card-body">

            <div class="row">

                <div class="col-md-3 mb-2">
                    <label>ID Promoción</label>
                    <input type="number" id="promo_id" class="form-control">
                </div>

                <div class="col-md-3 mb-2">
                    <label>Cliente</label>
                    <input type="text" id="cliente" class="form-control" placeholder="C0001">
                </div>

                <div class="col-md-3 mb-2">
                    <label>Monto simulado</label>
                    <input type="number" id="monto" class="form-control">
                </div>

            </div>

            <button class="btn btn-primary mt-2" onclick="simular()">
                Simular
            </button>

        </div>
    </div>

    <!-- ===== RESULTADO ===== -->
    <div class="card">
        <div class="card-body">
            <h5>Resultado</h5>
            <pre id="resultado"></pre>
        </div>
    </div>

</div>

<script>
    function simular() {

        const data = {
            promo_id: document.getElementById('promo_id').value,
            cliente: document.getElementById('cliente').value,
            monto_simulado: document.getElementById('monto').value
        };

        if (!data.promo_id || !data.cliente || !data.monto_simulado) {
            alert('Todos los campos son obligatorios');
            return;
        }

        fetch('../api/promociones/simulate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
            .then(r => r.text())
            .then(txt => {
                console.log('RAW:', txt);
                const res = JSON.parse(txt);
                document.getElementById('resultado').textContent =
                    JSON.stringify(res, null, 2);
            })
            .catch(err => {
                console.error(err);
                alert('Error al simular');
            });
    }
</script>

<?php
require_once __DIR__ . '/../../bi/_menu_global_end.php';
?>