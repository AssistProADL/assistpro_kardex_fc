<?php
// public/templates/plantillas_modulos.php
// Catálogo simple de módulos de filtros AssistPro

declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';

$pdo = db_pdo();

$mensajeError = '';
$mensajeOk    = '';

// --------------------------------------------------
// 1. Guardar módulo (alta / edición)
// --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'guardar_modulo') {

    $id          = trim($_POST['id'] ?? '');
    $clave       = trim($_POST['clave_modulo'] ?? '');
    $nombre      = trim($_POST['nombre'] ?? '');
    $tipo        = trim($_POST['tipo_modulo'] ?? 'REPORTE');
    $vistaSql    = trim($_POST['vista_sql'] ?? '');
    $ruta        = trim($_POST['ruta_sugerida'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $activo      = isset($_POST['activo']) ? 1 : 0;

    if ($clave === '' || $nombre === '') {
        $mensajeError = 'La clave de módulo y el nombre son obligatorios.';
    } else {
        try {
            if ($id !== '') {
                // UPDATE (ajusta las columnas a las que realmente existan en tu tabla)
                $sql = "
                    UPDATE ap_plantillas_modulos
                       SET clave_modulo  = ?,
                           nombre        = ?,
                           vista_sql     = ?,
                           tipo_modulo   = ?,
                           ruta_sugerida = ?,
                           descripcion   = ?,
                           activo        = ?
                     WHERE id = ?
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $clave,
                    $nombre,
                    ($vistaSql !== '' ? $vistaSql : null),
                    $tipo,
                    ($ruta !== '' ? $ruta : null),
                    ($descripcion !== '' ? $descripcion : null),
                    $activo,
                    (int)$id
                ]);
                $mensajeOk = 'Módulo actualizado correctamente.';
            } else {
                // INSERT
                $sql = "
                    INSERT INTO ap_plantillas_modulos
                        (clave_modulo, nombre, vista_sql, tipo_modulo,
                         ruta_sugerida, descripcion, activo, creado_en)
                    VALUES (?,?,?,?,?,?,?, NOW())
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $clave,
                    $nombre,
                    ($vistaSql !== '' ? $vistaSql : null),
                    $tipo,
                    ($ruta !== '' ? $ruta : null),
                    ($descripcion !== '' ? $descripcion : null),
                    $activo
                ]);
                $id = (string)$pdo->lastInsertId();
                $mensajeOk = 'Módulo creado correctamente.';
            }

            header('Location: plantillas_modulos.php?id=' . urlencode($id));
            exit;

        } catch (Throwable $e) {
            $mensajeError = 'Error al guardar el módulo: ' . $e->getMessage();
        }
    }
}

// --------------------------------------------------
// 2. Cargar módulos existentes
//    (ajusta el SELECT a las columnas que sí tengas)
// --------------------------------------------------
try {
    $modulos = db_all("
        SELECT
            id,
            clave_modulo,
            nombre,
            vista_sql,
            tipo_modulo,
            ruta_sugerida,
            descripcion,
            activo,
            creado_en
      FROM ap_plantillas_modulos
     ORDER BY nombre
    ");
} catch (Throwable $e) {
    $modulos      = [];
    $mensajeError = 'No fue posible leer ap_plantillas_modulos: ' . $e->getMessage();
}

$nuevo     = isset($_GET['nuevo']);
$idSel     = $_GET['id'] ?? '';
$moduloSel = null;

if (!$nuevo && !empty($modulos)) {
    if ($idSel !== '') {
        foreach ($modulos as $m) {
            if ((string)$m['id'] === (string)$idSel) {
                $moduloSel = $m;
                break;
            }
        }
    }
    if (!$moduloSel) {
        $moduloSel = $modulos[0];
        $idSel     = (string)$moduloSel['id'];
    }
}

require_once __DIR__ . '/../bi/_menu_global.php';
?>
<div class="container-fluid" style="font-size:11px;">

    <div class="row mt-2">
        <div class="col-8">
            <h5>Catálogo de módulos de filtros – AssistPro</h5>
            <p class="text-muted mb-1">
                Lista y edición simple de módulos que usan el template general de filtros.
            </p>
        </div>
        <div class="col-4 text-end">
            <a href="plantillas_modulos.php?nuevo=1" class="btn btn-success btn-sm">
                + Nuevo módulo
            </a>
        </div>
    </div>

    <?php if ($mensajeError): ?>
        <div class="row mt-1">
            <div class="col-12">
                <div class="alert alert-danger py-1" style="font-size:11px;">
                    <?= htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($mensajeOk): ?>
        <div class="row mt-1">
            <div class="col-12">
                <div class="alert alert-success py-1" style="font-size:11px;">
                    <?= htmlspecialchars($mensajeOk, ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row mt-2">
        <!-- Lista -->
        <div class="col-md-4">
            <div class="card mb-2">
                <div class="card-header py-1" style="font-size:11px;">
                    <strong>Módulos registrados</strong>
                </div>
                <div class="card-body p-2" style="font-size:10px; max-height:400px; overflow:auto;">
                    <?php if (empty($modulos)): ?>
                        <div class="text-muted">
                            No hay módulos registrados en <code>ap_plantillas_modulos</code>.
                        </div>
                    <?php else: ?>
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                            <tr>
                                <th>Clave</th>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($modulos as $m): ?>
                                <tr<?= (!$nuevo && (string)$m['id'] === (string)$idSel ? ' class="table-primary"' : '') ?>>
                                    <td>
                                        <a href="plantillas_modulos.php?id=<?= (int)$m['id'] ?>">
                                            <?= htmlspecialchars($m['clave_modulo'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($m['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($m['tipo_modulo'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?php if ((int)$m['activo'] === 1): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Formulario -->
        <div class="col-md-8">
            <div class="card mb-2">
                <div class="card-header py-1" style="font-size:11px;">
                    <strong><?= $nuevo ? 'Nuevo módulo' : 'Editar módulo' ?></strong>
                </div>
                <div class="card-body p-2" style="font-size:11px;">
                    <?php
                    $form = [
                        'id'           => '',
                        'clave_modulo' => '',
                        'nombre'       => '',
                        'tipo_modulo'  => 'REPORTE',
                        'vista_sql'    => '',
                        'ruta_sugerida'=> '',
                        'descripcion'  => '',
                        'activo'       => 1,
                    ];
                    if (!$nuevo && $moduloSel) {
                        $form['id']           = (int)$moduloSel['id'];
                        $form['clave_modulo'] = (string)$moduloSel['clave_modulo'];
                        $form['nombre']       = (string)$moduloSel['nombre'];
                        $form['tipo_modulo']  = (string)($moduloSel['tipo_modulo'] ?? 'REPORTE');
                        $form['vista_sql']    = (string)($moduloSel['vista_sql'] ?? '');
                        $form['ruta_sugerida']= (string)($moduloSel['ruta_sugerida'] ?? '');
                        $form['descripcion']  = (string)($moduloSel['descripcion'] ?? '');
                        $form['activo']       = (int)$moduloSel['activo'];
                    }
                    ?>

                    <form method="post" class="row g-2">
                        <input type="hidden" name="accion" value="guardar_modulo">
                        <input type="hidden" name="id" value="<?= htmlspecialchars((string)$form['id'], ENT_QUOTES, 'UTF-8') ?>">

                        <div class="col-md-6">
                            <label class="form-label form-label-sm">Clave del módulo</label>
                            <input type="text"
                                   name="clave_modulo"
                                   class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($form['clave_modulo'], ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="ej. existencias_ubicacion">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label form-label-sm">Nombre del módulo</label>
                            <input type="text"
                                   name="nombre"
                                   class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($form['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="ej. Reporte de existencias por ubicación">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label form-label-sm">Tipo</label>
                            <?php $tipoSel = $form['tipo_modulo'] ?: 'REPORTE'; ?>
                            <select name="tipo_modulo" class="form-select form-select-sm">
                                <option value="PROCESO" <?= $tipoSel === 'PROCESO' ? 'selected' : '' ?>>Proceso</option>
                                <option value="REPORTE" <?= $tipoSel === 'REPORTE' ? 'selected' : '' ?>>Reporte</option>
                                <option value="API"      <?= $tipoSel === 'API'      ? 'selected' : '' ?>>API</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label form-label-sm">Vista SQL base (opcional)</label>
                            <input type="text"
                                   name="vista_sql"
                                   class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($form['vista_sql'], ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="ej. v_existencias_por_ubicacion_ao">
                        </div>

                        <div class="col-md-5">
                            <label class="form-label form-label-sm">Ruta sugerida (opcional)</label>
                            <input type="text"
                                   name="ruta_sugerida"
                                   class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($form['ruta_sugerida'], ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="ej. /public/reportes/existencias_ubicacion.php">
                        </div>

                        <div class="col-12">
                            <label class="form-label form-label-sm">Descripción (opcional)</label>
                            <textarea name="descripcion"
                                      rows="2"
                                      class="form-control form-control-sm"
                                      placeholder="Notas del módulo, uso, cliente, etc."><?= htmlspecialchars($form['descripcion'], ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-check-sm">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="activo"
                                       id="chk_activo"
                                       value="1"
                                       <?= ($form['activo'] ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="chk_activo">
                                    Módulo activo
                                </label>
                            </div>
                        </div>

                        <div class="col-12 mt-2 text-end">
                            <button type="submit" class="btn btn-primary btn-sm">
                                Guardar módulo
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
