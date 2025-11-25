<?php
// public/config/correo_plantillas.php
session_start();

require_once __DIR__ . '/../bi/_menu_global.php';
require_once __DIR__ . '/../../app/bootstrap.php';

$logger = app_logger();

// --- POST: guardar plantilla ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $codigo      = trim($_POST['codigo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $asunto      = trim($_POST['asunto'] ?? '');
    $cuerpo_html = $_POST['cuerpo_html'] ?? '';
    $cuerpo_txt  = $_POST['cuerpo_texto'] ?? '';
    $vars        = trim($_POST['variables_json'] ?? '');
    $activo      = isset($_POST['activo']) ? 1 : 0;

    if ($codigo === '' || $asunto === '') {
        $error = 'Código y asunto son obligatorios.';
    } else {
        if ($vars === '') {
            $vars = null;
        }

        if ($id > 0) {
            $sql = "UPDATE c_correo_plantilla
                    SET codigo = :codigo,
                        descripcion = :descripcion,
                        asunto = :asunto,
                        cuerpo_html = :html,
                        cuerpo_texto = :texto,
                        variables_json = :vars,
                        activo = :activo
                    WHERE id = :id";
            db_exec($sql, [
                ':codigo'      => $codigo,
                ':descripcion' => $descripcion,
                ':asunto'      => $asunto,
                ':html'        => $cuerpo_html,
                ':texto'       => $cuerpo_txt,
                ':vars'        => $vars,
                ':activo'      => $activo,
                ':id'          => $id,
            ]);
            $logger->info('Plantilla correo actualizada', ['id' => $id, 'codigo' => $codigo]);
        } else {
            $sql = "INSERT INTO c_correo_plantilla
                    (codigo, descripcion, asunto, cuerpo_html, cuerpo_texto, variables_json, activo)
                    VALUES
                    (:codigo, :descripcion, :asunto, :html, :texto, :vars, :activo)";
            db_exec($sql, [
                ':codigo'      => $codigo,
                ':descripcion' => $descripcion,
                ':asunto'      => $asunto,
                ':html'        => $cuerpo_html,
                ':texto'       => $cuerpo_txt,
                ':vars'        => $vars,
                ':activo'      => $activo,
            ]);
            $logger->info('Plantilla correo creada', ['codigo' => $codigo]);
        }

        header('Location: correo_plantillas.php?ok=1');
        exit;
    }
}

// --- GET: cargar plantilla para edición ---
$editId  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editRow = null;
if ($editId > 0) {
    $editRow = db_one("SELECT * FROM c_correo_plantilla WHERE id = :id", [':id' => $editId]);
}

// --- Listado de plantillas ---
$rows = db_all("SELECT * FROM c_correo_plantilla ORDER BY codigo ASC, id ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Plantillas de Correo - AssistPro</title>
    <link rel="stylesheet" href="/assistpro_kardex_fc/public/assets/bootstrap.min.css">
    <style>
        body { font-size: 12px; }
        .container-main { padding: 15px; }
        textarea { font-size: 11px; }
        .editor-small { height: 150px; }
    </style>
</head>
<body>
<div class="container-main">
    <h4>Plantillas de Correo</h4>
    <hr>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif (isset($_GET['ok'])): ?>
        <div class="alert alert-success">Plantilla guardada correctamente.</div>
    <?php endif; ?>

    <div class="row">
        <!-- Formulario -->
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <?= $editRow ? 'Editar plantilla de correo' : 'Nueva plantilla de correo' ?>
                </div>
                <div class="card-body">
                    <form method="post" action="correo_plantillas.php">
                        <input type="hidden" name="id" value="<?= $editRow['id'] ?? 0 ?>">

                        <div class="mb-2">
                            <label class="form-label">Código</label>
                            <input type="text" name="codigo" class="form-control form-control-sm"
                                   required
                                   placeholder="EJ: FACTURA_VENCIMIENTO"
                                   value="<?= htmlspecialchars($editRow['codigo'] ?? '') ?>">
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Descripción</label>
                            <input type="text" name="descripcion" class="form-control form-control-sm"
                                   placeholder="Uso de la plantilla"
                                   value="<?= htmlspecialchars($editRow['descripcion'] ?? '') ?>">
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Asunto</label>
                            <input type="text" name="asunto" class="form-control form-control-sm"
                                   required
                                   placeholder="EJ: Recordatorio de factura {NOMBRE}"
                                   value="<?= htmlspecialchars($editRow['asunto'] ?? '') ?>">
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Cuerpo HTML</label>
                            <textarea name="cuerpo_html" class="form-control form-control-sm editor-small"
                                      placeholder="<p>Estimado(a) {NOMBRE}</p>"><?= htmlspecialchars($editRow['cuerpo_html'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Cuerpo Texto Plano</label>
                            <textarea name="cuerpo_texto" class="form-control form-control-sm editor-small"
                                      placeholder="Estimado(a) {NOMBRE}, ..."><?= htmlspecialchars($editRow['cuerpo_texto'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Variables JSON (opcional)</label>
                            <textarea name="variables_json" class="form-control form-control-sm"
                                      placeholder='["{NOMBRE}","{SALDO}","{FECHA}"]'><?= htmlspecialchars($editRow['variables_json'] ?? '') ?></textarea>
                        </div>

                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" id="chkActivoTpl" name="activo"
                                   value="1" <?= (isset($editRow['activo']) ? (int)$editRow['activo'] : 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="chkActivoTpl">Activa</label>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
                            <a href="correo_plantillas.php" class="btn btn-secondary btn-sm">Nuevo</a>
                        </div>
                    </form>
                    <hr>
                    <small class="text-muted">
                        <strong>Uso de variables:</strong><br>
                        Puedes usar marcadores como <code>{NOMBRE}</code>, <code>{SALDO}</code>, <code>{FECHA}</code>.
                        El motor reemplaza por los campos del destinatario / consulta.
                    </small>
                </div>
            </div>
        </div>

        <!-- Lista de plantillas -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Plantillas registradas</div>
                <div class="card-body" style="max-height: 480px; overflow:auto;">
                    <table class="table table-sm table-striped table-bordered">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Asunto</th>
                            <th>Activa</th>
                            <th>Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><?= (int)$r['id'] ?></td>
                                <td><?= htmlspecialchars($r['codigo']) ?></td>
                                <td><?= htmlspecialchars($r['descripcion']) ?></td>
                                <td><?= htmlspecialchars($r['asunto']) ?></td>
                                <td><?= (int)$r['activo'] ? 'Sí' : 'No' ?></td>
                                <td>
                                    <a href="correo_plantillas.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        Editar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$rows): ?>
                            <tr><td colspan="6" class="text-center">No hay plantillas registradas.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
