<?php
/*****************************************************************
 * RUTAS PLANNING – ASSISTPRO
 * Arquitectura limpia / estable / sin warnings
 *****************************************************************/

require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

/* ===============================================================
   VARIABLES DE CONTROL
=============================================================== */
$empresa_id = $_GET['empresa'] ?? '';
$almacen_id = $_GET['almacen'] ?? '';
$accion     = $_GET['accion']  ?? '';
$ruta_id    = $_GET['ruta_id'] ?? '';

/* ===============================================================
   ACCIONES SOBRE RUTA (ANTES DE CUALQUIER OUTPUT)
=============================================================== */
if ($accion && $ruta_id) {
    try {
        $pdo->beginTransaction();

        switch ($accion) {
            case 'eliminar':
                $pdo->prepare("DELETE FROM relvendrutas WHERE IdRuta = ?")->execute([$ruta_id]);
                $pdo->prepare("DELETE FROM rel_ruta_transporte WHERE cve_ruta = ?")->execute([$ruta_id]);
                $pdo->prepare("DELETE FROM t_ruta WHERE ID_Ruta = ?")->execute([$ruta_id]);
                break;

            case 'inactivar':
                $pdo->prepare("UPDATE t_ruta SET Activo = 0 WHERE ID_Ruta = ?")->execute([$ruta_id]);
                $pdo->prepare("DELETE FROM relvendrutas WHERE IdRuta = ?")->execute([$ruta_id]);
                $pdo->prepare("DELETE FROM rel_ruta_transporte WHERE cve_ruta = ?")->execute([$ruta_id]);
                break;

            case 'recuperar':
                $pdo->prepare("UPDATE t_ruta SET Activo = 1 WHERE ID_Ruta = ?")->execute([$ruta_id]);
                break;
        }

        $pdo->commit();
        header("Location: rutas_planning.php");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error en acción: " . $e->getMessage());
    }
}

/* ===============================================================
   GUARDAR ASIGNACIÓN
=============================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $ruta     = $_POST['ruta'];
        $vendedor = $_POST['vendedor'];
        $vehiculo = $_POST['transporte'];
        $empresa  = $_POST['empresa'];

        // Un vendedor / vehículo solo en una ruta
        $pdo->prepare("DELETE FROM relvendrutas WHERE IdVendedor = ?")->execute([$vendedor]);
        $pdo->prepare("DELETE FROM rel_ruta_transporte WHERE id_transporte = ?")->execute([$vehiculo]);

        // Limpiar asignaciones de la ruta
        $pdo->prepare("DELETE FROM relvendrutas WHERE IdRuta = ?")->execute([$ruta]);
        $pdo->prepare("DELETE FROM rel_ruta_transporte WHERE cve_ruta = ?")->execute([$ruta]);

        // Insertar asignaciones
        $pdo->prepare("
            INSERT INTO relvendrutas (IdRuta, IdVendedor, IdEmpresa, Fecha)
            VALUES (?, ?, ?, NOW())
        ")->execute([$ruta, $vendedor, $empresa]);

        $pdo->prepare("
            INSERT INTO rel_ruta_transporte (cve_ruta, id_transporte)
            VALUES (?, ?)
        ")->execute([$ruta, $vehiculo]);

        $pdo->commit();
        header("Location: rutas_planning.php");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error al guardar: " . $e->getMessage());
    }
}

/* ===============================================================
   CARGA DE CATÁLOGOS
=============================================================== */

// Empresas
$empresas = $pdo->query("
    SELECT cve_cia, des_cia 
    FROM c_compania 
    WHERE Activo = 1
")->fetchAll(PDO::FETCH_ASSOC);

// Almacenes
$almacenes = [];
if ($empresa_id) {
    $stmt = $pdo->prepare("
        SELECT clave, nombre 
        FROM c_almacenp 
        WHERE cve_cia = ?
    ");
    $stmt->execute([$empresa_id]);
    $almacenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Rutas + asignaciones
$rutas = $pdo->query("
    SELECT 
        r.ID_Ruta,
        r.cve_ruta,
        r.descripcion,
        r.Activo,
        v.Id_Vendedor,
        v.Nombre AS vendedor,
        t.Nombre AS transporte
    FROM t_ruta r
    LEFT JOIN relvendrutas rv ON rv.IdRuta = r.ID_Ruta
    LEFT JOIN t_vendedores v ON v.Id_Vendedor = rv.IdVendedor
    LEFT JOIN rel_ruta_transporte rt ON rt.cve_ruta = r.ID_Ruta
    LEFT JOIN t_transporte t ON t.id = rt.id_transporte
    ORDER BY r.cve_ruta
")->fetchAll(PDO::FETCH_ASSOC);

// Vendedores
$vendedores = $pdo->query("
    SELECT Id_Vendedor, Nombre 
    FROM t_vendedores 
    WHERE Activo = 1
")->fetchAll(PDO::FETCH_ASSOC);

// Transportes
$transportes = $pdo->query("
    SELECT id, Nombre 
    FROM t_transporte 
    WHERE Activo = 1
")->fetchAll(PDO::FETCH_ASSOC);

/* ===============================================================
   INICIO DE VISTA
=============================================================== */
include __DIR__ . '/../bi/_menu_global.php';
?>

<div class="container-fluid mt-3">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <strong>Asignación de Vendedores y Vehículos a Rutas</strong>
        </div>

        <div class="card-body">

            <!-- FILTROS -->
            <form method="get" class="row mb-4">
                <div class="col-md-4">
                    <label>Empresa</label>
                    <select name="empresa" class="form-control" onchange="this.form.submit()">
                        <option value="">Seleccione</option>
                        <?php foreach ($empresas as $e): ?>
                            <option value="<?= $e['cve_cia'] ?>" <?= $empresa_id == $e['cve_cia'] ? 'selected' : '' ?>>
                                <?= $e['des_cia'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label>Almacén</label>
                    <select name="almacen" class="form-control" onchange="this.form.submit()">
                        <option value="">Seleccione</option>
                        <?php foreach ($almacenes as $a): ?>
                            <option value="<?= $a['clave'] ?>" <?= $almacen_id == $a['clave'] ? 'selected' : '' ?>>
                                <?= $a['nombre'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <!-- ASIGNACIÓN -->
            <form method="post" class="row mb-4">
                <input type="hidden" name="empresa" value="<?= $empresa_id ?>">

                <div class="col-md-3">
                    <label>Ruta</label>
                    <select name="ruta" class="form-control" required>
                        <?php foreach ($rutas as $r): if ($r['Activo']): ?>
                            <option value="<?= $r['ID_Ruta'] ?>">
                                <?= $r['cve_ruta'] ?> - <?= $r['descripcion'] ?>
                            </option>
                        <?php endif; endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label>Vendedor</label>
                    <select name="vendedor" class="form-control" required>
                        <?php foreach ($vendedores as $v): ?>
                            <option value="<?= $v['Id_Vendedor'] ?>"><?= $v['Nombre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label>Vehículo</label>
                    <select name="transporte" class="form-control" required>
                        <?php foreach ($transportes as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= $t['Nombre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-success w-100">Asignar</button>
                </div>
            </form>

            <!-- GRILLA -->
            <table class="table table-bordered table-hover table-striped">
                <thead class="table-light">
                    <tr>
                        <th>Acciones</th>
                        <th>Ruta</th>
                        <th>Descripción</th>
                        <th>Vendedor</th>
                        <th>Vehículo</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rutas as $r): ?>
                        <tr>
                            <td style="white-space:nowrap">
                                <a class="btn btn-danger btn-sm"
                                   href="?accion=eliminar&ruta_id=<?= $r['ID_Ruta'] ?>"
                                   onclick="return confirm('¿Eliminar la ruta definitivamente?')">🗑</a>

                                <?php if ($r['Activo']): ?>
                                    <a class="btn btn-warning btn-sm"
                                       href="?accion=inactivar&ruta_id=<?= $r['ID_Ruta'] ?>"
                                       onclick="return confirm('¿Inactivar la ruta?')">⏸</a>
                                <?php else: ?>
                                    <a class="btn btn-success btn-sm"
                                       href="?accion=recuperar&ruta_id=<?= $r['ID_Ruta'] ?>">▶</a>
                                <?php endif; ?>
                            </td>
                            <td><?= $r['cve_ruta'] ?></td>
                            <td><?= $r['descripcion'] ?></td>
                            <td><?= $r['vendedor'] ?></td>
                            <td><?= $r['transporte'] ?></td>
                            <td>
                                <span class="badge <?= $r['Activo'] ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= $r['Activo'] ? 'Activa' : 'Inactiva' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
