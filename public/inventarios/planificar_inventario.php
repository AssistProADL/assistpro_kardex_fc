<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';
?>

<div class="container-fluid">
<h4>Planeación de Inventario</h4>

<!-- PASO 1 -->
<section>
<h6>1️⃣ Crear inventario</h6>
<button class="btn btn-primary" onclick="crearInventario()">Crear</button>
</section>

<!-- PASO 2 -->
<section>
<h6>2️⃣ Seleccionar universo</h6>
<div id="seleccionUniverso"></div>
</section>

<!-- PASO 3 -->
<section>
<h6>3️⃣ Snapshot teórico</h6>
<button class="btn btn-warning" onclick="snapshot()">Generar snapshot</button>
</section>

<!-- PASO 4 -->
<section>
<h6>4️⃣ Asignar conteos</h6>
<div id="asignacionConteos"></div>
</section>

</div>
