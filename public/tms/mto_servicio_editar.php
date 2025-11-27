<?php
// public/procesos/mto_servicio_editar.php
declare(strict_types=1);

$TITLE = 'Servicio de Mantenimiento';

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$pdo = db_pdo();

$id          = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mensajeOk   = null;
$mensajeError = null;
$errores     = [];

// Catálogos
$tipos    = [];
$familias = [];

// Valores del formulario (default)
$data = [
    'cve_cia'             => '',
    'CVE_ACT'             => '',
    'descripcion'         => '',
    'tipo_id'             => '',
    'familia_id'          => '',
    'km_frecuencia'       => '',
    'dias_frecuencia'     => '',
    'horas_frecuencia'    => '',
    'tiempo_estimado_min' => '',
    'tarifa_mano_obra'    => '',
    'tarifa_fija'         => '',
    'activo'              => 1,
];

try {
    // Catálogo tipos
    $tipos = db_all("
        SELECT id, CVE_MTO_TIPO, descripcion, clase
        FROM c_mto_tipo
        WHERE activo = 1
        ORDER BY clase, descripcion
    ");

    // Catálogo familias
    $familias = db_all("
        SELECT id, cve_cia, CVE_FAM_SERV, descripcion
        FROM c_mto_familia_servicio
        WHERE activo = 1
        ORDER BY descripcion
    ");

    // Si es edición, cargar datos
    if ($id > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        $row = db_one("SELECT * FROM c_mto_actividad WHERE id = :id", [':id' => $id]);
        if (!$row) {
            throw new RuntimeException('El servicio indicado no existe.');
        }
        foreach ($data as $k => $v) {
            if (array_key_exists($k, $row)) {
                $data[$k] = $row[$k];
            }
        }
    }

    // POST: guardar cambios
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Tomar valores
        foreach ($data as $k => $v) {
            if (isset($_POST[$k])) {
                $data[$k] = is_string($_POST[$k]) ? trim($_POST[$k]) : $_POST[$k];
            }
        }

        $data['activo'] = isset($_POST['activo']) ? 1 : 0;

        // Validaciones
        if ($data['cve_cia'] === '' || !is_numeric($data['cve_cia'])) {
            $errores[] = 'La compañía (cve_cia) es obligatoria y debe ser numérica.';
        }

        if ($data['CVE_ACT'] === '') {
            $errores[] = 'El código de servicio (CVE_ACT) es obligatorio.';
        }

        if ($data['descripcion'] === '') {
            $errores[] = 'La descripción del servicio es obligatoria.';
        }

        if ($data['tipo_id'] === '' || !is_numeric($data['tipo_id'])) {
            $errores[] = 'Debes seleccionar un tipo de mantenimiento válido.';
        }

        // Frecuencias numéricas
        foreach (['km_frecuencia', 'dias_frecuencia', 'horas_frecuencia', 'tiempo_estimado_min'] as $campo) {
            if ($data[$campo] !== '' && !is_numeric($data[$campo])) {
                $errores[] = "El campo {$campo} debe ser numérico.";
            }
        }

        // Tarifas numéricas
        foreach (['tarifa_mano_obra', 'tarifa_fija'] as $campo) {
            if ($data[$campo] !== '' && !is_numeric($data[$campo])) {
                $errores[] = "El campo {$campo} debe ser numérico.";
            }
        }

        if (empty($errores)) {
            $sql = '';
            $params = [
                ':cve_cia'             => (int)$data['cve_cia'],
                ':CVE_ACT'             => $data['CVE_ACT'],
                ':descripcion'         => $data['descripcion'],
                ':tipo_id'             => (int)$data['tipo_id'],
                ':familia_id'          => ($data['familia_id'] !== '' ? (int)$data['familia_id'] : null),
                ':km_frecuencia'       => ($data['km_frecuencia'] !== '' ? (int)$data['km_frecuencia'] : null),
                ':dias_frecuencia'     => ($data['dias_frecuencia'] !== '' ? (int)$data['dias_frecuencia'] : null),
                ':horas_frecuencia'    => ($data['horas_frecuencia'] !== '' ? (int)$data['horas_frecuencia'] : null),
                ':tiempo_estimado_min' => ($data['tiempo_estimado_min'] !== '' ? (int)$data['tiempo_estimado_min'] : null),
                ':tarifa_mano_obra'    => ($data['tarifa_mano_obra'] !== '' ? (float)$data['tarifa_mano_obra'] : null),
                ':tarifa_fija'         => ($data['tarifa_fija'] !== '' ? (float)$data['tarifa_fija'] : null),
                ':activo'              => (int)$data['activo'],
            ];

            if ($id > 0) {
                $sql = "
                    UPDATE c_mto_actividad
                    SET
                        cve_cia             = :cve_cia,
                        CVE_ACT             = :CVE_ACT,
                        descripcion         = :descripcion,
                        tipo_id             = :tipo_id,
                        familia_id          = :familia_id,
                        km_frecuencia       = :km_frecuencia,
                        dias_frecuencia     = :dias_frecuencia,
                        horas_frecuencia    = :horas_frecuencia,
                        tiempo_estimado_min = :tiempo_estimado_min,
                        tarifa_mano_obra    = :tarifa_mano_obra,
                        tarifa_fija         = :tarifa_fija,
                        activo              = :activo
                    WHERE id = :id
                ";
                $params[':id'] = $id;
            } else {
                $sql = "
                    INSERT INTO c_mto_actividad (
                        cve_cia,
                        CVE_ACT,
                        descripcion,
                        tipo_id,
                        familia_id,
                        km_frecuencia,
                        dias_frecuencia,
                        horas_frecuencia,
                        tiempo_estimado_min,
                        tarifa_mano_obra,
                        tarifa_fija,
                        activo
                    ) VALUES (
                        :cve_cia,
                        :CVE_ACT,
                        :descripcion,
                        :tipo_id,
                        :familia_id,
                        :km_frecuencia,
                        :dias_frecuencia,
                        :horas_frecuencia,
                        :tiempo_estimado_min,
                        :tarifa_mano_obra,
                        :tarifa_fija,
                        :activo
                    )
                ";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            if ($id === 0) {
                $id = (int)$pdo->lastInsertId();
            }

            $mensajeOk = 'Servicio guardado correctamente.';
        }
    }

} catch (Throwable $e) {
    $mensajeError = 'Error al procesar el servicio: ' . $e->getMessage();
}

?>
<div class="container-fluid py-3">

    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">
                    <i class="fa fa-cog me-2"></i>
                    <?php echo $id > 0 ? 'Editar Servicio de Mantenimiento' : 'Nuevo Servicio de Mantenimiento'; ?>
                </h4>
                <small class="text-muted">
                    Definición de parámetros de servicio: tipo, frecuencia y tarifas.
                </small>
            </div>
            <div>
                <a href="mto_servicios.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fa fa-arrow-left me-1"></i> Regresar
                </a>
            </div>
        </div>
    </div>

    <?php if ($mensajeError): ?>
        <div class="alert alert-danger py-2 small">
            <i class="fa fa-exclamation-circle me-1"></i>
            <?php echo htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errores)): ?>
        <div class="alert alert-warning py-2 small">
            <i class="fa fa-exclamation-triangle me-1"></i>
            <?php foreach ($errores as $err): ?>
                <div><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($mensajeOk): ?>
        <div class="alert alert-success py-2 small">
            <i class="fa fa-check-circle me-1"></i>
            <?php echo htmlspecialchars($mensajeOk, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header py-2">
                    <strong>Datos del Servicio</strong>
                </div>
                <div class="card-body py-2">
                    <form method="post" autocomplete="off">
                        <div class="row g-2">
                            <div class="col-4 col-md-3">
                                <label class="form-label form-label-sm mb-1">
                                    Cía <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       name="cve_cia"
                                       class="form-control form-control-sm"
                                       value="<?php echo htmlspecialchars((string)$data['cve_cia'], ENT_QUOTES, 'UTF-8'); ?>"
                                       placeholder="Ej. 78">
                            </div>
                            <div class="col-8 col-md-5">
                                <label class="form-label form-label-sm mb-1">
                                    Código servicio <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       name="CVE_ACT"
                                       class="form-control form-control-sm"
                                       maxlength="30"
                                       value="<?php echo htmlspecialchars((string)$data['CVE_ACT'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label form-label-sm mb-1">
                                    Activo
                                </label>
                                <div class="form-check form-switch mt-1">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="activo"
                                           id="chk_activo"
                                           <?php echo ((int)$data['activo'] === 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label small" for="chk_activo">
                                        Disponible para programación y OT
                                    </label>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label form-label-sm mb-1">
                                    Descripción <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       name="descripcion"
                                       class="form-control form-control-sm"
                                       maxlength="200"
                                       value="<?php echo htmlspecialchars((string)$data['descripcion'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label form-label-sm mb-1">
                                    Tipo de mantenimiento <span class="text-danger">*</span>
                                </label>
                                <select name="tipo_id" class="form-select form-select-sm">
                                    <option value="">-- Selecciona --</option>
                                    <?php foreach ($tipos as $t): ?>
                                        <?php
                                        $sel = ((int)$data['tipo_id'] === (int)$t['id']) ? 'selected' : '';
                                        $label = sprintf(
                                            '%s - %s (%s)',
                                            $t['CVE_MTO_TIPO'],
                                            $t['descripcion'],
                                            $t['clase']
                                        );
                                        ?>
                                        <option value="<?php echo (int)$t['id']; ?>" <?php echo $sel; ?>>
                                            <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label form-label-sm mb-1">
                                    Familia
                                </label>
                                <select name="familia_id" class="form-select form-select-sm">
                                    <option value="">(Sin familia)</option>
                                    <?php foreach ($familias as $f): ?>
                                        <?php
                                        $sel = ((int)$data['familia_id'] === (int)$f['id']) ? 'selected' : '';
                                        $label = sprintf(
                                            '%s - %s',
                                            $f['CVE_FAM_SERV'],
                                            $f['descripcion']
                                        );
                                        ?>
                                        <option value="<?php echo (int)$f['id']; ?>" <?php echo $sel; ?>>
                                            <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <hr class="my-2">
                            </div>

                            <div class="col-12">
                                <label class="form-label form-label-sm mb-1">
                                    Frecuencia sugerida
                                </label>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label form-label-sm mb-1">Cada (km)</label>
                                <input type="text"
                                       name="km_frecuencia"
                                       class="form-control form-control-sm text-end"
                                       value="<?php echo htmlspecialchars((string)$data['km_frecuencia'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label form-label-sm mb-1">Cada (días)</label>
                                <input type="text"
                                       name="dias_frecuencia"
                                       class="form-control form-control-sm text-end"
                                       value="<?php echo htmlspecialchars((string)$data['dias_frecuencia'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label form-label-sm mb-1">Cada (horas motor)</label>
                                <input type="text"
                                       name="horas_frecuencia"
                                       class="form-control form-control-sm text-end"
                                       value="<?php echo htmlspecialchars((string)$data['horas_frecuencia'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label form-label-sm mb-1">Tiempo estimado (min)</label>
                                <input type="text"
                                       name="tiempo_estimado_min"
                                       class="form-control form-control-sm text-end"
                                       value="<?php echo htmlspecialchars((string)$data['tiempo_estimado_min'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>

                            <div class="col-12">
                                <hr class="my-2">
                            </div>

                            <div class="col-12">
                                <label class="form-label form-label-sm mb-1">
                                    Tarifas de servicio
                                </label>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label form-label-sm mb-1">Tarifa mano de obra</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">$</span>
                                    <input type="text"
                                           name="tarifa_mano_obra"
                                           class="form-control form-control-sm text-end"
                                           value="<?php echo htmlspecialchars((string)$data['tarifa_mano_obra'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label form-label-sm mb-1">Tarifa fija (paquete)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">$</span>
                                    <input type="text"
                                           name="tarifa_fija"
                                           class="form-control form-control-sm text-end"
                                           value="<?php echo htmlspecialchars((string)$data['tarifa_fija'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>

                        </div>

                        <div class="mt-3 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fa fa-save me-1"></i>
                                Guardar servicio
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <!-- Panel de ayuda -->
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header py-2">
                    <strong>Guía rápida</strong>
                </div>
                <div class="card-body py-2 small">
                    <p class="mb-1">
                        • Este servicio se usará en la programación preventiva y en las órdenes de mantenimiento.
                    </p>
                    <p class="mb-1">
                        • Puedes definir frecuencia por kilómetros, días y/u horas de motor.
                    </p>
                    <p class="mb-1">
                        • La tarifa de mano de obra sirve como referencia para el costo de OT;
                          la tarifa fija aplica cuando el servicio es un paquete cerrado.
                    </p>
                    <p class="mb-1">
                        • Si el servicio es de tipo <strong>PREVENTIVO</strong>, se recomienda
                          definir al menos una frecuencia (km, días u horas).
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
