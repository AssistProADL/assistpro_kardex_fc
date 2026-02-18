<?php
require_once("../../includes/db.php");
require_once("../../includes/_menu_global.php");

$id = $_GET['id'];

if($_POST){
    mysqli_query($conn,"INSERT INTO t_instalacion_detalle
        (id_instalacion,id_articulo,cantidad)
        VALUES ($id,".$_POST['id_articulo'].",".$_POST['cantidad'].")");
}

$articulos = mysqli_query($conn,"SELECT id,des_articulo FROM c_articulo WHERE Activo=1");

$detalle = mysqli_query($conn,"
    SELECT d.*, a.des_articulo
    FROM t_instalacion_detalle d
    INNER JOIN c_articulo a ON d.id_articulo=a.id
    WHERE d.id_instalacion=$id
");
?>

<div class="container-fluid">

<h4>Artículos Instalados</h4>

<div class="card mb-3">
<div class="card-body">

<form method="POST" class="row g-2">

<div class="col-md-6">
<select name="id_articulo" class="form-select">
<?php while($a=mysqli_fetch_assoc($articulos)): ?>
<option value="<?= $a['id'] ?>"><?= $a['des_articulo'] ?></option>
<?php endwhile; ?>
</select>
</div>

<div class="col-md-2">
<input type="number" name="cantidad" value="1"
       class="form-control">
</div>

<div class="col-md-2">
<button class="btn btn-success w-100">Agregar</button>
</div>

</form>

</div>
</div>

<div class="card">
<div class="card-body table-responsive">

<table class="table table-striped">
<?php while($d=mysqli_fetch_assoc($detalle)): ?>
<tr>
<td><?= $d['des_articulo'] ?></td>
<td><?= $d['cantidad'] ?></td>
</tr>
<?php endwhile; ?>
</table>

</div>
</div>

<a href="checklist.php?id=<?= $id ?>" 
   class="btn btn-primary mt-3">
   Continuar al Checklist →
</a>

</div>

<?php require_once("../../includes/_menu_global_end.php"); ?>
