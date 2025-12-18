<?php
require_once __DIR__ . '/../../../app/db.php';
require_once __DIR__ . '/../../bi/_menu_global.php';

$usuario = $_SESSION['id_usuario'];

// Inventario activo del usuario
$stmt = $pdo->prepare("
    SELECT i.id_inventario, i.folio, i.tipo_inventario, ia.conteo_num
    FROM inventario i
    JOIN inventario_asignacion ia 
        ON ia.id_inventario = i.id_inventario
    WHERE ia.id_usuario = ?
      AND ia.rol = 'CONTADOR'
      AND i.estado = 'EN_CONTEO'
    LIMIT 1
");
$stmt->execute([$usuario]);
$inv = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inv) {
    echo "<div class='alert alert-info'>No tienes inventarios asignados.</div>";
    exit;
}
?>
<div class="container-fluid">

<h5 class="mb-3">
Inventario <?= $inv['folio'] ?> |
Conteo #<?= $inv['conteo_num'] ?>
</h5>

<table class="table table-sm table-hover" id="tablaObjetos">
<thead>
<tr>
    <th>Objeto</th>
    <th>Descripción</th>
    <th>Acción</th>
</tr>
</thead>
<tbody></tbody>
</table>

</div>

<script>
const idInventario = <?= $inv['id_inventario'] ?>;
const conteoNum = <?= $inv['conteo_num'] ?>;

fetch(`/api/objetos_inventario.php?id_inventario=${idInventario}`)
.then(r => r.json())
.then(data => {
    let html = '';
    data.forEach(o => {
        html += `
        <tr>
            <td>${o.codigo}</td>
            <td>${o.descripcion}</td>
            <td>
              <button class="btn btn-sm btn-primary"
                onclick="capturar(${o.id},'${o.tipo}')">
                Capturar
              </button>
            </td>
        </tr>`;
    });
    document.querySelector('#tablaObjetos tbody').innerHTML = html;
});

function capturar(idRef, tipo) {
    const cantidad = prompt('Cantidad contada:');
    if (!cantidad) return;

    fetch('/api/registrar_conteo_inventario.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({
            id_inventario: idInventario,
            conteo_num: conteoNum,
            tipo_objeto: tipo,
            id_referencia: idRef,
            cantidad: cantidad,
            tipo_captura: 'MANUAL',
            id_usuario: <?= $usuario ?>
        })
    })
    .then(r=>r.json())
    .then(resp=>{
        alert(resp.message || 'Registrado');
    });
}
</script>
