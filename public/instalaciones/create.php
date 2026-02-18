<?php
require_once(__DIR__ . "/../../app/db.php");
require_once(__DIR__ . "/../bi/_menu_global.php");

$pdo = db_pdo();

/* =====================================================
   PROCESAR FORMULARIO
===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $folio = "INS-" . date("YmdHis");
    $id_activo = $_POST['id_activo'] ?? null;
    $id_tecnico = $_POST['id_tecnico'] ?? null;
    $fecha = $_POST['fecha_instalacion'] ?? null;
    $lugar = $_POST['lugar_instalacion'] ?? null;
    $condiciones = $_POST['condiciones'] ?? null;

    $sql = "INSERT INTO t_instalaciones
            (folio, id_activo, id_tecnico, fecha_instalacion, lugar_instalacion, condiciones_iniciales, estado)
            VALUES
            (:folio, :id_activo, :id_tecnico, :fecha, :lugar, :condiciones, 'BORRADOR')";

    dbq($sql, [
        'folio' => $folio,
        'id_activo' => $id_activo,
        'id_tecnico' => $id_tecnico,
        'fecha' => $fecha,
        'lugar' => $lugar,
        'condiciones' => $condiciones
    ]);

    header("Location: index.php");
    exit;
}

/* =====================================================
   CARGAR CATÁLOGOS
===================================================== */

$activos = db_all("
    SELECT id, des_articulo 
    FROM c_articulo 
    ORDER BY des_articulo
");

$tecnicos = db_all("
    SELECT id_user, nombre_completo 
    FROM c_usuario 
    ORDER BY nombre_completo
");
?>

<div class="container-fluid">

    <div class="card">
        <div class="card-header">
            <h5>Nueva Instalación</h5>
        </div>

        <div class="card-body">

            <form method="POST">

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Folio</label>
                        <input type="text"
                               class="form-control"
                               value="<?= "INS-" . date("YmdHis") ?>"
                               disabled>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Fecha Instalación</label>
                        <input type="date"
                               name="fecha_instalacion"
                               class="form-control"
                               value="<?= date('Y-m-d') ?>"
                               required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Lugar Instalación</label>
                        <input type="text"
                               name="lugar_instalacion"
                               class="form-control">
                    </div>
                </div>

                <div class="row mb-3">

                    <div class="col-md-6">
                        <label class="form-label">Activo</label>
                        <select name="id_activo"
                                class="form-select"
                                required>
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
                        <select name="id_tecnico"
                                class="form-select"
                                required>
                            <option value="">Seleccione...</option>
                            <?php foreach($tecnicos as $t): ?>
                                <option value="<?= $t['id_user'] ?>">
                                    <?= htmlspecialchars($t['nombre_completo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

                <div class="mb-3">
                    <label class="form-label">Condiciones Iniciales</label>
                    <textarea name="condiciones"
                              class="form-control"
                              rows="3"></textarea>
                </div>

                <div class="text-end">
                    <a href="index.php"
                       class="btn btn-secondary">
                        Cancelar
                    </a>

                    <button type="submit"
                            class="btn btn-primary">
                        Guardar Instalación
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>

<?php require_once(__DIR__ . "/../bi/_menu_global_end.php"); ?>
