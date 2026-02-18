<?php
require_once("../../includes/db.php");
require_once("../../includes/_menu_global.php");

$id = $_GET['id'];

$sections = mysqli_query($conn,"SELECT * FROM t_checklist_sections ORDER BY orden");
?>

<div class="container-fluid">

<h4>Checklist de Instalación</h4>

<form method="POST">

<div class="accordion" id="accordionChecklist">

<?php while($s=mysqli_fetch_assoc($sections)): ?>

<div class="accordion-item">
<h2 class="accordion-header">
<button class="accordion-button collapsed"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#sec<?= $s['id'] ?>">
<?= $s['nombre'] ?>
</button>
</h2>

<div id="sec<?= $s['id'] ?>"
     class="accordion-collapse collapse"
     data-bs-parent="#accordionChecklist">

<div class="accordion-body">

<?php
$items = mysqli_query($conn,"
SELECT * FROM t_checklist_items
WHERE section_id=".$s['id']."
ORDER BY orden");
?>

<?php while($i=mysqli_fetch_assoc($items)): ?>
<div class="form-check mb-2">
<input class="form-check-input"
       type="checkbox"
       name="item[<?= $i['id'] ?>]"
       value="1"
       id="chk<?= $i['id'] ?>">
<label class="form-check-label"
       for="chk<?= $i['id'] ?>">
<?= $i['descripcion'] ?>
</label>
</div>
<?php endwhile; ?>

</div>
</div>
</div>

<?php endwhile; ?>

</div>

<button class="btn btn-success mt-3">
Guardar Checklist
</button>

</form>

</div>

<?php require_once("../../includes/_menu_global_end.php"); ?>
