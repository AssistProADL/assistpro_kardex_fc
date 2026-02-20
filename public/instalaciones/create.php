<?php
require_once(__DIR__ . "/../../app/db.php");
require_once(__DIR__ . "/../bi/_menu_global.php");
?>

<style>
    .ap-title {
        font-size: 20px;
        font-weight: 600;
        color: #0B3C8C;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
    }

    .ap-card {
        background: #f7f9fc;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .08);
    }

    .ap-section-title {
        font-size: 14px;
        font-weight: 600;
        color: #0B3C8C;
        margin-bottom: 15px;
        text-transform: uppercase;
    }

    .autocomplete-box {
        position: absolute;
        z-index: 1000;
        width: 100%;
        max-height: 220px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        display: none;
    }

    .autocomplete-item {
        padding: 8px 10px;
        cursor: pointer;
    }

    .autocomplete-item:hover {
        background: #f1f4f9;
    }

    .autocomplete-box {
        position: absolute;
        z-index: 1000;
        width: 100%;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        max-height: 200px;
        overflow-y: auto;
        display: none;
    }

    .autocomplete-item {
        padding: 8px;
        cursor: pointer;
    }

    .autocomplete-item:hover {
        background: #f1f4f9;
    }
</style>

<div class="container-fluid">

    <h4 class="mb-4 text-primary">
        <i class="fa fa-screwdriver-wrench me-2"></i>
        Nueva Instalación
    </h4>

    <div class="card shadow-sm p-4">

        <form id="formInstalacion">

            <!-- ================= PEDIDO ================= -->
            <h6 class="text-primary mb-3">Pedido</h6>

            <div class="row mb-3">

                <div class="col-md-4 position-relative">
                    <label class="form-label">Buscar Pedido</label>
                    <input type="text"
                        id="inputPedido"
                        class="form-control"
                        placeholder="Escriba folio..."
                        autocomplete="off"
                        required>
                    <input type="hidden" id="id_pedido" name="id_pedido">
                    <div id="listaPedidos" class="autocomplete-box"></div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Cliente</label>
                    <input type="text" id="cliente" class="form-control" readonly>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Ubicación</label>
                    <input type="text" id="ubicacion" class="form-control" readonly>
                </div>

            </div>

            <!-- ================= TECNICO ================= -->
            <h6 class="text-primary mb-3">Asignación</h6>

            <div class="row mb-3">

                <div class="col-md-6 position-relative">
                    <label class="form-label">Técnico</label>
                    <input type="text"
                        id="inputTecnico"
                        class="form-control"
                        placeholder="Buscar técnico..."
                        autocomplete="off"
                        required>
                    <input type="hidden" id="id_tecnico" name="id_tecnico">
                    <div id="listaTecnicos" class="autocomplete-box"></div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Fecha Compromiso</label>
                    <input type="date"
                        name="fecha_compromiso"
                        class="form-control"
                        required>
                </div>

            </div>

            <!-- ================= PARTIDAS ================= -->
            <h6 class="text-primary mb-3">Partidas del Pedido</h6>

            <div class="table-responsive mb-4">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px;"></th>
                            <th>Artículo</th>
                            <th>Cantidad Pedido</th>
                            <th>Cantidad a Instalar</th>
                        </tr>
                    </thead>
                    <tbody id="tablaPartidas">
                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                Seleccione un pedido...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="text-end">
                <a href="index.php" class="btn btn-outline-secondary me-2">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save me-1"></i>
                    Guardar Instalación
                </button>
            </div>

        </form>

    </div>
</div>

<script>
    // ======================== AUTOCOMPLETE PEDIDO ========================

    const inputPedido = document.getElementById('inputPedido');
    const listaPedidos = document.getElementById('listaPedidos');
    const idPedidoInput = document.getElementById('id_pedido');

    inputPedido.addEventListener('input', function() {

        const q = this.value.trim();
        if (q.length < 2) return;

        fetch(`../api/pedidos/pedidos_api.php?action=listar&desde=2000-01-01&hasta=2100-01-01`)
            .then(r => r.json())
            .then(data => {

                let html = '';
                data.rows.forEach(p => {
                    if (p.Fol_folio.includes(q)) {
                        html += `<div class="autocomplete-item"
                            data-id="${p.id_pedido}"
                            data-folio="${p.Fol_folio}">
                            ${p.Fol_folio}
                        </div>`;
                    }
                });

                listaPedidos.innerHTML = html;
                listaPedidos.style.display = 'block';
            });

    });

    listaPedidos.addEventListener('click', function(e) {
        if (!e.target.classList.contains('autocomplete-item')) return;

        const id = e.target.dataset.id;
        const folio = e.target.dataset.folio;

        inputPedido.value = folio;
        idPedidoInput.value = id;
        listaPedidos.style.display = 'none';

        cargarPedido(id);
    });


    // ======================== CARGAR PEDIDO ========================

    function cargarPedido(id) {

        fetch(`../api/pedidos/pedidos_api.php?action=consultar&id_pedido=${id}`)
            .then(r => r.json())
            .then(data => {

                document.getElementById('cliente').value = data.header.Cve_clte;
                document.getElementById('ubicacion').value = data.header.cve_ubicacion ?? '';

                let html = '';

                data.detail.forEach(d => {
                    html += `
                <tr>
                    <td>
                        <input type="checkbox"
                            class="chkPartida"
                            data-id="${d.id}">
                    </td>
                    <td>${d.Cve_articulo}</td>
                    <td>${d.Num_cantidad}</td>
                    <td>
                        <input type="number"
                            class="form-control form-control-sm cantidadInstalar"
                            value="${d.Num_cantidad}"
                            min="1">
                    </td>
                </tr>
            `;
                });

                document.getElementById('tablaPartidas').innerHTML = html;
            });
    }


    // ======================== AUTOCOMPLETE TECNICO ========================
    function configurarAutocomplete(inputId, hiddenId, listId, url, textField, valueField) {

        const input = document.getElementById(inputId);
        const hidden = document.getElementById(hiddenId);
        const list = document.getElementById(listId);

        input.addEventListener('input', function() {

            const q = this.value.trim();
            if (q.length < 2) return;

            fetch(url + '&q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {

                    if (!data.data && !data.rows) return;

                    const items = data.data || data.rows;

                    let html = '';

                    items.forEach(item => {

                        html += `<div class="autocomplete-item"
                        data-id="${item[valueField]}"
                        data-text="${item[textField]}">
                        ${item[textField]}
                    </div>`;
                    });

                    list.innerHTML = html;
                    list.style.display = 'block';
                });
        });

        list.addEventListener('click', function(e) {

            if (!e.target.classList.contains('autocomplete-item')) return;

            input.value = e.target.dataset.text;
            hidden.value = e.target.dataset.id;
            list.style.display = 'none';
        });
    }

    configurarAutocomplete(
        'inputTecnico',
        'id_tecnico',
        'listaTecnicos',
        '../api/catalogos/usuarios.php?action=select',
        'text',
        'id_user'
    );


    // ======================== GUARDAR ========================

    document.getElementById('formInstalacion')
        .addEventListener('submit', async function(e) {

            e.preventDefault();

            const idPedido = document.getElementById('id_pedido').value;
            const idTecnico = document.getElementById('id_tecnico').value;
            const fechaCompromiso = this.fecha_compromiso.value;

            if (!idPedido || !idTecnico || !fechaCompromiso) {
                alert("Datos incompletos");
                return;
            }

            /* ================= RECOLECTAR PARTIDAS ================= */

            const partidas = [];

            document.querySelectorAll('.chkPartida:checked').forEach(chk => {
                const row = chk.closest('tr');
                const cantidad = row.querySelector('.cantidadInstalar').value;

                partidas.push({
                    id_pedido_detalle: chk.dataset.id,
                    cantidad: cantidad
                });
            });

            if (partidas.length === 0) {
                alert("Seleccione al menos una partida");
                return;
            }

            try {

                /* ================= 1️⃣ GENERAR FOLIO ================= */

                const folioResponse = await fetch('../api/folios/generar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        empresa_id: 1,
                        modulo: 'INSTALACIONES',
                        codigo: 'INS'
                    })
                });

                const folioData = await folioResponse.json();

                if (!folioData.folio) {
                    alert("Error generando folio");
                    return;
                }

                const folioGenerado = folioData.folio;

                /* ================= 2️⃣ CREAR INSTALACIÓN ================= */

                const payload = {
                    folio: folioGenerado,
                    id_pedido: idPedido,
                    id_tecnico: idTecnico,
                    fecha_compromiso: fechaCompromiso,
                    partidas: partidas
                };

                const response = await fetch('../api/api_instalaciones.php?action=create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (!data.success) {
                    alert(data.error);
                    return;
                }

                alert("Instalación creada correctamente: " + folioGenerado);
                window.location.href = "index.php";

            } catch (err) {
                console.error(err);
                alert("Error inesperado");
            }

        });
</script>

<?php require_once(__DIR__ . "/../bi/_menu_global_end.php"); ?>