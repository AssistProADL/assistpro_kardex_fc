<?php
include '../bi/_menu_global.php';
?>

<div class="container-fluid mt-3">
    <h4>Nueva Promoción</h4>

    <div class="row">
        <div class="col-md-6 mb-2">
            <label>Nombre</label>
            <input type="text" id="nombre" class="form-control">
        </div>

        <div class="col-md-3 mb-2">
            <label>Tipo</label>
            <select id="tipo" class="form-control">
                <option value="MONTO">Monto</option>
                <option value="UNIDADES">Unidades</option>
            </select>
        </div>
    </div>

    <div class="mb-2">
        <label>Descripción</label>
        <textarea id="descripcion" class="form-control"></textarea>
    </div>

    <div class="row">
        <div class="col-md-3 mb-2">
            <label>Inicio</label>
            <input type="date" id="inicio" class="form-control">
        </div>

        <div class="col-md-3 mb-2">
            <label>Fin</label>
            <input type="date" id="fin" class="form-control">
        </div>
    </div>

    <button id="btnGuardar" class="btn btn-success mt-3">
        Guardar
    </button>

    <pre id="out" class="mt-3"></pre>
</div>

<script>
    document.getElementById('btnGuardar')
        .addEventListener('click', guardarPromocion);

    function guardarPromocion() {

        const data = {
            nombre: document.getElementById('nombre').value.trim(),
            descripcion: document.getElementById('descripcion').value.trim(),
            tipo: document.getElementById('tipo').value,
            fecha_ini: document.getElementById('inicio').value,
            fecha_fin: document.getElementById('fin').value
        };

        fetch('../api/promociones/index.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
            .then(res => res.text())   // 👈 VER TEXTO CRUDO
            .then(txt => {
                console.log('RAW RESPONSE:', txt);
                const r = JSON.parse(txt);

                if (r.ok) {
                    alert('Promoción creada correctamente');
                    window.location.href = 'promociones.php';
                } else {
                    alert(r.msg || 'Error');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error de red');
            });
    }
</script>

<?php
include '../bi/_menu_global_end.php';
?>