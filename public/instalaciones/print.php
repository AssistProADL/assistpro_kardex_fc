<?php
require_once("../../includes/db.php");

$id = $_GET['id'];

$inst = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT i.*, a.marca,a.modelo
FROM t_instalaciones i
INNER JOIN c_activos a ON i.id_activo=a.id_activo
WHERE i.id_instalacion=$id
"));
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Instalación <?= $inst['folio'] ?></title>
<style>
body{font-family:Arial;margin:40px}
h2{text-align:center}
</style>
</head>
<body>

<h2>VERIFICACIÓN DE INSTALACIÓN</h2>

<p><strong>Folio:</strong> <?= $inst['folio'] ?></p>
<p><strong>Unidad:</strong> <?= $inst['marca']." ".$inst['modelo'] ?></p>
<p><strong>Fecha:</strong> <?= $inst['fecha_instalacion'] ?></p>
<p><strong>Lugar:</strong> <?= $inst['lugar_instalacion'] ?></p>

<hr>

<p><strong>Condiciones Iniciales:</strong></p>
<p><?= $inst['condiciones_iniciales'] ?></p>

<script>
window.print();
</script>

</body>
</html>
