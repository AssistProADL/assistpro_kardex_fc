<?php
// Conexión BD (está en /app)
require_once(__DIR__ . "/../../app/db.php");

// Menú global (está en /public/bi)
require_once(__DIR__ . "/../bi/_menu_global.php");

$id = $_GET['id'];

if($_POST){
    mysqli_query($conn,"UPDATE t_instalaciones SET
        fecha_instalacion='".$_POST['fecha_instalacion']."',
        lugar_instalacion='".$_POST['lugar_instalacion']."',
        condiciones_iniciales='".$_POST['condiciones']."'
        WHERE id_instalacion=$id");
}

$inst = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM t_instalaciones WHERE id_instalacion=$id"));
?>

<div class="container-fluid">

<h4>Editar Instalación</h4>

<div class="card mb-3">
<div class="card-body">

<form method="POST">

<div class="row mb-3">
    <div class="col-md-3">
        <label>Fecha</label>
        <input type="date" name="fecha_instalacion"
               class="form-control"
               value="<?= $inst['fecha_instalacion'] ?>">
    </div>
</div>

<div class="mb-3">
    <label>Lugar</label>
    <input type="text" name="lugar_instalacion"
           class="form-control"
           value="<?= $inst['lugar_instalacion'] ?>">
</div>

<div class="mb-3">
    <label>Condiciones</label>
    <textarea name="condiciones"
              class="form-control"><?= $inst['condiciones_iniciales'] ?></textarea>
</div>

<button class="btn btn-primary">Actualizar</button>

</form>

</div>
</div>

<div class="card">
<div class="card-body d-flex gap-3">

<a href="detalle_articulos.php?id=<?= $id ?>" 
   class="btn btn-outline-primary">Artículos</a>

<a href="checklist.php?id=<?= $id ?>" 
   class="btn btn-outline-success">Checklist</a>

<a href="print.php?id=<?= $id ?>" 
   class="btn btn-outline-dark" target="_blank">Imprimir</a>

</div>
</div>

</div>

<?php require_once(__DIR__ . "/../bi/_menu_global_end.php"); ?>
