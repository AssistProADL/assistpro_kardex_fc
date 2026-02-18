<?php
<<<<<<< Updated upstream
require_once(__DIR__ . "/../../app/db.php");

$pdo = db();

/* ==============================
   PROCESAR FORMULARIO
================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $folio = $_POST['folio'] ?? null;
    $id_activo = $_POST['id_activo'] ?? null;
    $id_tecnico = $_POST['id_tecnico'] ?? null;
    $fecha = $_POST['fecha_instalacion'] ?? null;
    $lugar = $_POST['lugar_instalacion'] ?? null;

    $sql = "INSERT INTO t_instalaciones 
            (folio, id_activo, id_tecnico, fecha_instalacion, lugar_instalacion, estado)
            VALUES (?, ?, ?, ?, ?, 'BORRADOR')";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $folio,
        $id_activo,
        $id_tecnico,
        $fecha,
        $lugar
    ]);

    header("Location: index.php");
    exit;
}

/* ==============================
   CARGAR CATÁLOGOS
================================= */

// Activos
$activos = $pdo->query("
    SELECT id, des_articulo 
    FROM c_articulo 
    ORDER BY des_articulo
")->fetchAll(PDO::FETCH_ASSOC);

// Técnicos
$tecnicos = $pdo->query("
    SELECT id_user, nombre_completo 
    FROM c_usuario 
    ORDER BY nombre_completo
")->fetchAll(PDO::FETCH_ASSOC);

require_once(__DIR__ . "/../bi/_menu_global.php");
=======
// Conexión BD (está en /app)
require_once(__DIR__ . "/../../app/db.php");

// Menú global (está en /public/bi)
require_once(__DIR__ . "/../bi/_menu_global.php");

if($_POST){

    $folio = "INS-".date("YmdHis");
    $id_activo = $_POST['id_activo'];
    $fecha = $_POST['fecha_instalacion'];
    $lugar = $_POST['lugar_instalacion'];
    $condiciones = $_POST['condiciones'];
    $id_tecnico = $_SESSION['id_user'];

    mysqli_query($conn,"INSERT INTO t_instalaciones
        (folio,id_activo,id_tecnico,fecha_instalacion,lugar_instalacion,condiciones_iniciales)
        VALUES
        ('$folio','$id_activo','$id_tecnico','$fecha','$lugar','$condiciones')");

    $id = mysqli_insert_id($conn);
    header("Location: detalle_articulos.php?id=".$id);
    exit;
}

$activos = mysqli_query($conn,"SELECT id_activo, marca, modelo FROM c_activos WHERE activo=1");
>>>>>>> Stashed changes
?>

<div class="container-fluid">

<<<<<<< Updated upstream
    <div class="card">
        <div class="card-header">
            <h5>Nueva Instalación</h5>
        </div>

        <div class="card-body">

            <form method="POST">

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Folio</label>
                        <input type="text" name="folio" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Fecha Instalación</label>
                        <input type="date" name="fecha_instalacion" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Lugar Instalación</label>
                        <input type="text" name="lugar_instalacion" class="form-control">
                    </div>
                </div>

                <div class="row mb-3">

                    <div class="col-md-6">
                        <label class="form-label">Activo</label>
                        <select name="id_activo" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <?php foreach($activos as $a): ?>
                                <option value="<?= $a['id'] ?>">
                                    <?= htmlspecialchars($a['des_articulo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Técnico</label>
                        <select name="id_tecnico" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <?php foreach($tecnicos as $t): ?>
                                <option value="<?= $t['id_user'] ?>">
                                    <?= htmlspecialchars($t['nombre_completo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

                <div class="text-end">
                    <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        Guardar Instalación
                    </button>
                </div>

            </form>

        </div>
    </div>

=======
<h4 class="mb-3">Nueva Instalación</h4>

<div class="card">
<div class="card-body">

<form method="POST">

<div class="row mb-3">
    <div class="col-md-6">
        <label class="form-label">Unidad</label>
        <select name="id_activo" class="form-select" required>
            <option value="">Seleccione</option>
            <?php while($a=mysqli_fetch_assoc($activos)): ?>
            <option value="<?= $a['id_activo'] ?>">
                <?= $a['marca']." ".$a['modelo'] ?>
            </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label">Fecha</label>
        <input type="date" name="fecha_instalacion"
               class="form-control"
               value="<?= date('Y-m-d') ?>" required>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Lugar Instalación</label>
    <input type="text" name="lugar_instalacion" class="form-control">
</div>

<div class="mb-3">
    <label class="form-label">Condiciones Iniciales</label>
    <textarea name="condiciones" class="form-control" rows="3"></textarea>
</div>

<button class="btn btn-success">
    Guardar y continuar
</button>

</form>

</div>
</div>
>>>>>>> Stashed changes
</div>

<?php require_once(__DIR__ . "/../bi/_menu_global_end.php"); ?>
