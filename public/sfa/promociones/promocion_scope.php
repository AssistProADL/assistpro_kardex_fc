<?php
require_once '../../../app/db.php';

$pdo = db_pdo();

$promo_id = $_GET['promo_id'] ?? null;

if (!$promo_id) {
    die("Promoción no válida");
}

/* =========================================================
   1. Obtener promoción
========================================================= */
$stmt = $pdo->prepare("
    SELECT id, Lista, Descripcion
    FROM listapromo
    WHERE id = ?
");
$stmt->execute([$promo_id]);
$promo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$promo) {
    die("Promoción no encontrada");
}

/* =========================================================
   2. Obtener clientes
========================================================= */
$clientes = $pdo->query("
    SELECT Id_Destinatario, Nombre
    FROM c_destinatarios
    WHERE Activo = 1
    ORDER BY Nombre
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   3. Obtener clientes ya asignados
========================================================= */
$stmt = $pdo->prepare("
    SELECT Id_Destinatario
    FROM relclilis
    WHERE ListaPromo = ?
");
$stmt->execute([$promo_id]);

$asignados = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Asignar Clientes</title>

<link rel="stylesheet" href="../../assets/bootstrap.min.css">
<script src="../../assets/jquery.min.js"></script>

<style>
body {
    font-size: 12px;
}
.table-container {
    max-height: 450px;
    overflow-y: auto;
}
</style>
</head>
<body>

<div class="container mt-3">

    <h5>
        Asignar Clientes a Promoción:
        <strong><?= htmlspecialchars($promo['Lista']) ?></strong>
        - <?= htmlspecialchars($promo['Descripcion']) ?>
    </h5>

    <input type="hidden" id="promo_id" value="<?= $promo_id ?>">

    <div class="mb-2">
        <input type="text" id="buscarCliente" class="form-control form-control-sm" placeholder="Buscar cliente...">
    </div>

    <div class="table-container border">
        <table class="table table-sm table-striped">
            <thead>
                <tr>
                    <th width="40">
                        <input type="checkbox" id="chkTodos">
                    </th>
                    <th>Cliente</th>
                </tr>
            </thead>
            <tbody id="tablaClientes">
                <?php foreach ($clientes as $c): ?>
                    <tr>
                        <td>
                            <input type="checkbox"
                                   class="chkCliente"
                                   value="<?= $c['Id_Destinatario'] ?>"
                                   <?= in_array($c['Id_Destinatario'], $asignados) ? 'checked' : '' ?>>
                        </td>
                        <td><?= htmlspecialchars($c['Nombre']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        <button class="btn btn-primary btn-sm" id="btnGuardarClientes">
            Guardar Asignación
        </button>
    </div>

</div>

<script>
/* =========================================================
   Seleccionar todos
========================================================= */
$('#chkTodos').on('change', function(){
    $('.chkCliente').prop('checked', this.checked);
});

/* =========================================================
   Filtro búsqueda
========================================================= */
$('#buscarCliente').on('keyup', function(){
    let value = $(this).val().toLowerCase();
    $("#tablaClientes tr").filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });
});

/* =========================================================
   Guardar asignación
========================================================= */
$('#btnGuardarClientes').on('click', function(){

    let promo_id = $('#promo_id').val();
    let clientes = [];

    $('.chkCliente:checked').each(function(){
        clientes.push($(this).val());
    });

    if(clientes.length === 0){
        if(!confirm('No hay clientes seleccionados. ¿Desea limpiar asignación?')){
            return;
        }
    }

    $.post('../../api/promociones/promociones_api.php', {
        action: 'guardar_clientes',
        promo_id: promo_id,
        clientes: clientes
    }, function(resp){

        if(resp.ok){
            alert('Asignación guardada correctamente');
        } else {
            alert('Error al guardar');
        }

    }, 'json');

});
</script>

</body>
</html>
