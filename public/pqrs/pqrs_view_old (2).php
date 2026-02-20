<?php
require_once __DIR__ . '/../bi/_menu_global.php';
require_once __DIR__ . '/../../app/db.php';

db_pdo();
global $pdo;

$id_case = (int)($_GET['id_case'] ?? 0);
if ($id_case <= 0) {
    echo "<div class='alert alert-danger'>Caso no válido</div>";
    require_once __DIR__ . '/../bi/_menu_global_end.php';
    exit;
}

/* =========================
   HELPERS SEGUROS
========================= */
function h($v) {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

/* =========================
   FLASH
========================= */
$flash_ok = false;
$flash_error = null;

/* =========================
   GUARDAR SEGUIMIENTO
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'seguimiento') {
    try {
        $pdo->beginTransaction();

        $nuevo_status = $_POST['status'] ?? '';
        $comentario   = trim($_POST['comentario'] ?? '');
        $usuario      = 'system';

        $status_anterior = $pdo->prepare("SELECT status FROM pqrs_case WHERE id_case=?");
        $status_anterior->execute([$id_case]);
        $status_anterior = $status_anterior->fetchColumn() ?? '';

        if ($nuevo_status !== '' && $nuevo_status !== $status_anterior) {
            $pdo->prepare("UPDATE pqrs_case SET status=? WHERE id_case=?")
                ->execute([$nuevo_status, $id_case]);
            $detalle = "Cambio de status a: {$nuevo_status}";
        } else {
            $detalle = $comentario !== '' ? $comentario : 'Nota';
        }

        $pdo->prepare("
            INSERT INTO pqrs_event
            (id_case, evento, detalle, status_anterior, status_nuevo, usuario)
            VALUES (?,?,?,?,?,?)
        ")->execute([
            $id_case, 'STATUS', $detalle, $status_anterior, $nuevo_status, $usuario
        ]);

        $pdo->commit();
        $flash_ok = true;

    } catch (Throwable $e) {
        $pdo->rollBack();
        $flash_error = $e->getMessage();
    }
}

/* =========================
   SUBIR ARCHIVO
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'archivo') {
    try {
        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Archivo inválido');
        }

        $f = $_FILES['archivo'];
        $nombre = $f['name'];
        $mime   = $f['type'];
        $size   = $f['size'];
        $usuario = 'system';

        $dir = __DIR__ . '/uploads/' . $id_case;
        if (!is_dir($dir)) mkdir($dir, 0775, true);

        $ruta_rel = 'uploads/' . $id_case . '/' . time() . '_' . basename($nombre);
        if (!move_uploaded_file($f['tmp_name'], __DIR__ . '/' . $ruta_rel)) {
            throw new Exception('No se pudo guardar archivo');
        }

        $pdo->beginTransaction();

        $pdo->prepare("
            INSERT INTO pqrs_file
            (id_case, nombre_original, ruta, mime, size_bytes, subido_por)
            VALUES (?,?,?,?,?,?)
        ")->execute([$id_case, $nombre, $ruta_rel, $mime, $size, $usuario]);

        $pdo->prepare("
            INSERT INTO pqrs_event (id_case, evento, detalle, usuario)
            VALUES (?,?,?,?)
        ")->execute([$id_case, 'ARCHIVO', "Archivo adjunto: {$nombre}", $usuario]);

        $pdo->commit();
        $flash_ok = true;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $flash_error = $e->getMessage();
    }
}

/* =========================
   DATA
========================= */
$case = $pdo->prepare("SELECT * FROM pqrs_case WHERE id_case=?");
$case->execute([$id_case]);
$case = $case->fetch(PDO::FETCH_ASSOC) ?: [];

$eventos = $pdo->prepare("SELECT * FROM pqrs_event WHERE id_case=? ORDER BY creado_en DESC");
$eventos->execute([$id_case]);
$eventos = $eventos->fetchAll(PDO::FETCH_ASSOC);

$files = $pdo->prepare("SELECT * FROM pqrs_file WHERE id_case=? ORDER BY subido_en DESC");
$files->execute([$id_case]);
$files = $files->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid assistpro-container">

<?php if ($flash_ok): ?>
<div class="alert alert-success assistpro-alert">
    <i class="fa fa-check-circle"></i> Operación realizada correctamente
</div>
<?php endif; ?>

<?php if ($flash_error): ?>
<div class="alert alert-danger assistpro-alert">
    <i class="fa fa-times-circle"></i> <?= h($flash_error) ?>
</div>
<?php endif; ?>

<div class="card assistpro-card mb-3">
    <div class="card-header assistpro-card-header">
        <i class="fa fa-ticket-alt"></i>
        Detalle PQRS – <?= h($case['folio'] ?? '') ?>
    </div>

    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3"><b>Cliente:</b> <?= h($case['cliente'] ?? $case['cliente_clave'] ?? '-') ?></div>
            <div class="col-md-3"><b>Tipo:</b> <?= h($case['tipo'] ?? '-') ?></div>
            <div class="col-md-3">
                <b>Estatus:</b>
                <span class="badge badge-primary"><?= h($case['status'] ?? '-') ?></span>
            </div>
            <div class="col-md-3"><b>Fecha:</b> <?= h($case['creado_en'] ?? '') ?></div>
        </div>

        <div class="assistpro-section mb-3">
            <h6>Descripción</h6>
            <div class="assistpro-box"><?= nl2br(h($case['descripcion'] ?? '')) ?></div>
        </div>

        <div class="assistpro-section mb-3">
            <h6>Seguimiento / Actualización</h6>
            <form method="post">
                <input type="hidden" name="accion" value="seguimiento">
                <div class="row">
                    <div class="col-md-4">
                        <label>Estatus</label>
                        <select name="status" class="form-control">
                            <?php foreach (['NUEVA','EN_PROCESO','EN_ESPERA','CERRADA'] as $st): ?>
                                <option value="<?= $st ?>" <?= (($case['status'] ?? '') === $st) ? 'selected':'' ?>><?= $st ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label>Comentario</label>
                        <textarea name="comentario" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="text-right mt-2">
                    <button class="btn btn-primary">
                        <i class="fa fa-save"></i> Registrar actualización
                    </button>
                </div>
            </form>
        </div>

        <div class="assistpro-section mb-3">
            <h6>Archivos / Evidencias</h6>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="archivo">
                <div class="d-flex">
                    <input type="file" name="archivo" class="form-control mr-2" required>
                    <button class="btn btn-outline-primary"><i class="fa fa-upload"></i> Subir</button>
                </div>
            </form>

            <?php foreach ($files as $f): ?>
                <div class="mt-2">
                    <a href="<?= h($f['ruta']) ?>" target="_blank"><?= h($f['nombre_original']) ?></a>
                    <small class="text-muted"><?= h($f['subido_en']) ?></small>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="assistpro-section">
            <h6>Bitácora de eventos</h6>
            <table class="table table-sm table-striped assistpro-table">
                <thead>
                    <tr>
                        <th>Fecha</th><th>Evento</th><th>Detalle</th><th>Antes</th><th>Después</th><th>Usuario</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($eventos as $e): ?>
                    <tr>
                        <td><?= h($e['creado_en']) ?></td>
                        <td><?= h($e['evento']) ?></td>
                        <td><?= h($e['detalle']) ?></td>
                        <td><?= h($e['status_anterior']) ?></td>
                        <td><?= h($e['status_nuevo']) ?></td>
                        <td><?= h($e['usuario']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
