<?php
require_once(__DIR__ . "/../../app/db.php");
require_once(__DIR__ . "/../bi/_menu_global.php");

$id = (int)($_GET['id'] ?? 0);
?>

<div class="container-fluid">
    <h4 class="text-primary">Editar Instalación #<?= $id ?></h4>

    <div class="card p-4 shadow-sm">

        <div id="info"></div>

        <div class="mt-3">
            <label>Estado</label>
            <select id="estado" class="form-select">
                <option value="BORRADOR">BORRADOR</option>
                <option value="EN_PROCESO">EN_PROCESO</option>
                <option value="COMPLETADO">COMPLETADO</option>
                <option value="CANCELADO">CANCELADO</option>
            </select>
        </div>

        <div class="mt-3">
            <button class="btn btn-success" onclick="guardar()">Guardar</button>
        </div>

    </div>
</div>

<script>
    const id = <?= $id ?>;

    async function cargar() {
        const r = await fetch('../api/instalaciones/api_instalaciones.php?action=list');
        const data = await r.json();
        const inst = data.data.find(i => i.id_instalacion == id);

        document.getElementById('info').innerHTML = `
        <strong>Folio:</strong> ${inst.folio}<br>
        <strong>Técnico:</strong> ${inst.tecnico}<br>
        <strong>Avance:</strong> ${inst.porcentaje_avance}%`;
    }

    async function guardar() {
        alert('Aquí conectas con update API');
    }

    cargar();
</script>

<?php require_once(__DIR__ . "/../bi/_menu_global_end.php"); ?>