<?php
require_once(__DIR__ . "/../../app/db.php");
require_once(__DIR__ . "/../bi/_menu_global.php");
?>

<div class="container-fluid">

    <h4 class="mb-3 text-primary">
        <i class="fa fa-plus-circle"></i> Nueva Instalación
    </h4>

    <div class="card p-4 shadow-sm">

        <form id="formInstalacion">

            <div class="row mb-3">
                <div class="col-md-4">
                    <label>Buscar Pedido</label>
                    <input type="text" id="buscarPedido" class="form-control">
                    <input type="hidden" id="id_pedido">
                    <div id="listaPedidos" class="border mt-1 bg-white"></div>
                </div>

                <div class="col-md-4">
                    <label>Técnico</label>
                    <input type="number" id="id_tecnico" class="form-control" placeholder="ID técnico">
                </div>

                <div class="col-md-4">
                    <label>Fecha Compromiso</label>
                    <input type="date" id="fecha_compromiso" class="form-control">
                </div>
            </div>

            <hr>

            <h6>Partidas Embarcadas</h6>

            <table class="table table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Artículo</th>
                        <th>Pedida</th>
                        <th>Embarcada</th>
                        <th>Instalar</th>
                    </tr>
                </thead>
                <tbody id="tablaDetalle"></tbody>
            </table>

            <button type="submit" class="btn btn-primary">
                Guardar Instalación
            </button>

        </form>
    </div>
</div>

<script>
    let detallePedido = [];

    document.getElementById('buscarPedido').addEventListener('input', function() {
        const q = this.value;

        if (q.length < 2) return;

        fetch(`../api/instalaciones/api_pedidos_instalaciones.php?action=buscar&q=${q}`)
            .then(r => r.json())
            .then(data => {
                const lista = document.getElementById('listaPedidos');
                lista.innerHTML = '';
                data.data.forEach(p => {
                    lista.innerHTML += `
            <div class="p-2 border-bottom itemPedido"
                 data-id="${p.id_pedido}">
                ${p.Fol_folio} - ${p.RazonSocial}
            </div>`;
                });
            });
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('itemPedido')) {
            const id = e.target.dataset.id;
            document.getElementById('id_pedido').value = id;
            cargarPedido(id);
            document.getElementById('listaPedidos').innerHTML = '';
        }
    });

    function cargarPedido(id) {
        fetch(`../api/instalaciones/api_instalaciones.php?action=consultar_pedido&id_pedido=${id}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;

                detallePedido = data.detail;
                const tbody = document.getElementById('tablaDetalle');
                tbody.innerHTML = '';

                detallePedido.forEach(d => {
                    if (d.cantidad_embarcada <= 0) return;

                    tbody.innerHTML += `
            <tr>
                <td>${d.Cve_articulo}</td>
                <td>${d.cantidad_pedida}</td>
                <td>${d.cantidad_embarcada}</td>
                <td>
                    <input type="number"
                           class="form-control form-control-sm instalar"
                           data-id="${d.id}"
                           max="${d.cantidad_embarcada}"
                           min="0">
                </td>
            </tr>`;
                });
            });
    }

    document.getElementById('formInstalacion').addEventListener('submit', async function(e) {
        e.preventDefault();

        const partidas = [];
        document.querySelectorAll('.instalar').forEach(i => {
            const qty = Number(i.value);
            if (qty > 0) {
                partidas.push({
                    id_pedido_detalle: i.dataset.id,
                    cantidad: qty
                });
            }
        });

        const payload = {
            folio: 'TEMP-' + Date.now(),
            id_pedido: document.getElementById('id_pedido').value,
            id_tecnico: document.getElementById('id_tecnico').value,
            fecha_compromiso: document.getElementById('fecha_compromiso').value,
            partidas: partidas
        };

        const r = await fetch('../api/instalaciones/api_instalaciones.php?action=create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await r.json();
        if (data.success) {
            alert('Instalación creada');
            window.location = 'index.php';
        }
    });
</script>

<?php require_once(__DIR__ . "/../bi/_menu_global_end.php"); ?>