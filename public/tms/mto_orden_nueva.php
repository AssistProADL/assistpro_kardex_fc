<?php
// public/procesos/mto_orden_nueva.php

declare(strict_types=1);

$TITLE = 'Nueva Orden de Mantenimiento';

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$pdo = db_pdo();

$mensajeError   = null;
$mensajeOk      = null;
$erroresForm    = [];

// Valores por defecto de formulario
$selTransporte  = $_POST['transporte_id']    ?? '';
$selTipo        = $_POST['tipo_id']          ?? '';
$selTaller      = $_POST['taller_id']        ?? '';
$selOrigen      = $_POST['origen']           ?? 'PROGRAMADO';
$valFechaProg   = $_POST['fecha_programada'] ?? '';
$valKmProg      = $_POST['km_programados']   ?? '';

try {
    // --- Catálogos para combos ---
    $transportes = db_all("
        SELECT 
            id,
            cve_cia,
            ID_Transporte,
            Nombre,
            Placas
        FROM t_transporte
        ORDER BY cve_cia, ID_Transporte
    ");

    $tiposMto = db_all("
        SELECT 
            id,
            CVE_MTO_TIPO,
            descripcion,
            clase
        FROM c_mto_tipo
        WHERE activo = 1
        ORDER BY clase, descripcion
    ");

    $talleres = db_all("
        SELECT 
            id,
            CVE_TALLER,
            nombre,
            tipo
        FROM c_mto_taller
        WHERE activo = 1
        ORDER BY tipo, nombre
    ");

    // --- POST: guardar nueva orden ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Validaciones básicas
        if (empty($selTransporte)) {
            $erroresForm[] = 'Debes seleccionar un transporte.';
        }
        if (empty($selTipo)) {
            $erroresForm[] = 'Debes seleccionar un tipo de mantenimiento.';
        }
        if (!empty($valKmProg) && !is_numeric($valKmProg)) {
            $erroresForm[] = 'Los kilómetros programados deben ser numéricos.';
        }

        // Intentar parsear fecha programada si viene llena
        $fechaProgramadaDb = null;
        if (!empty($valFechaProg)) {
            $dt = \DateTime::createFromFormat('Y-m-d\TH:i', $valFechaProg);
            if ($dt === false) {
                $erroresForm[] = 'La fecha programada no tiene un formato válido.';
            } else {
                $fechaProgramadaDb = $dt->format('Y-m-d H:i:s');
            }
        }

        if (empty($erroresForm)) {
            // Obtener datos del transporte seleccionado para sacar cve_cia
            $stmt = $pdo->prepare("
                SELECT 
                    cve_cia,
                    ID_Transporte
                FROM t_transporte
                WHERE id = :id
            ");
            $stmt->execute([':id' => $selTransporte]);
            $rowTrans = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$rowTrans) {
                throw new RuntimeException('El transporte seleccionado no existe.');
            }

            $cveCia        = (int)$rowTrans['cve_cia'];
            $idTransporte  = $rowTrans['ID_Transporte'];

            // Generar folio (puedes luego cambiar a tu generador global)
            $folio = sprintf(
                'MTO-%s-%s',
                $idTransporte,
                date('YmdHis')
            );

            $kmProgNum     = !empty($valKmProg) ? (float)$valKmProg : null;
            $tallerId      = !empty($selTaller) ? (int)$selTaller : null;
            $tipoId        = (int)$selTipo;
            $origen        = $selOrigen;

            $usuarioCrea   = $_SESSION['usuario'] ?? 'SYSTEM';

            $sqlIns = "
                INSERT INTO th_mto_orden (
                    cve_cia,
                    transporte_id,
                    folio,
                    tipo_id,
                    taller_id,
                    origen,
                    fecha_programada,
                    km_programados,
                    estatus,
                    usuario_crea
                ) VALUES (
                    :cve_cia,
                    :transporte_id,
                    :folio,
                    :tipo_id,
                    :taller_id,
                    :origen,
                    :fecha_programada,
                    :km_programados,
                    'ABIERTA',
                    :usuario_crea
                )
            ";

            $stmtIns = $pdo->prepare($sqlIns);
            $stmtIns->execute([
                ':cve_cia'          => $cveCia,
                ':transporte_id'    => (int)$selTransporte,
                ':folio'            => $folio,
                ':tipo_id'          => $tipoId,
                ':taller_id'        => $tallerId,
                ':origen'           => $origen,
                ':fecha_programada' => $fechaProgramadaDb,
                ':km_programados'   => $kmProgNum,
                ':usuario_crea'     => $usuarioCrea,
            ]);

            $mensajeOk = "Orden creada correctamente con folio: {$folio}";

            // Limpiar formulario después de guardar
            $selTransporte = '';
            $selTipo       = '';
            $selTaller     = '';
            $selOrigen     = 'PROGRAMADO';
            $valFechaProg  = '';
            $valKmProg     = '';
        }
    }

} catch (Throwable $e) {
    $mensajeError = 'Error al procesar la nueva orden: ' . $e->getMessage();
}
?>

<div class="container-fluid py-3">

    <!-- Encabezado -->
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">
                    <i class="fa fa-plus-circle me-2"></i>
                    Nueva Orden de Mantenimiento
                </h4>
                <small class="text-muted">
                    Captura de órdenes de mantenimiento preventivo y correctivo por transporte.
                </small>
            </div>
            <div>
                <a href="mto_ordenes.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fa fa-arrow-left me-1"></i> Regresar a Órdenes
                </a>
            </div>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if ($mensajeError): ?>
        <div class="alert alert-danger py-2 small">
            <i class="fa fa-exclamation-circle me-1"></i>
            <?php echo htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($erroresForm)): ?>
        <div class="alert alert-warning py-2 small">
            <i class="fa fa-exclamation-triangle me-1"></i>
            <?php foreach ($erroresForm as $err): ?>
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

    <!-- Layout en cards -->
    <div class="row g-3">
        <!-- Datos principales -->
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header py-2">
                    <strong>Datos de la Orden</strong>
                </div>
                <div class="card-body py-2">
                    <form method="post" autocomplete="off" id="form-nueva-ot">
                        <div class="row g-2">
                            <!-- Transporte -->
                            <div class="col-12 col-md-6">
                                <label class="form-label form-label-sm mb-1">
                                    Transporte <span class="text-danger">*</span>
                                </label>
                                <select name="transporte_id" class="form-select form-select-sm" required>
                                    <option value="">-- Selecciona --</option>
                                    <?php foreach ($transportes as $t): ?>
                                        <?php
                                        $id     = (int)$t['id'];
                                        $label  = sprintf(
                                            '%s - %s (%s) [Cía %s]',
                                            $t['ID_Transporte'],
                                            $t['Nombre'],
                                            $t['Placas'],
                                            $t['cve_cia']
                                        );
                                        ?>
                                        <option value="<?php echo $id; ?>" <?php echo ($selTransporte == $id ? 'selected' : ''); ?>>
                                            <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Tipo de mantenimiento -->
                            <div class="col-12 col-md-6">
                                <label class="form-label form-label-sm mb-1">
                                    Tipo de Mantenimiento <span class="text-danger">*</span>
                                </label>
                                <select name="tipo_id" class="form-select form-select-sm" required>
                                    <option value="">-- Selecciona --</option>
                                    <?php foreach ($tiposMto as $tm): ?>
                                        <?php
                                        $idTipo = (int)$tm['id'];
                                        $labelTipo = sprintf(
                                            '%s - %s (%s)',
                                            $tm['CVE_MTO_TIPO'],
                                            $tm['descripcion'],
                                            $tm['clase']
                                        );
                                        ?>
                                        <option value="<?php echo $idTipo; ?>" <?php echo ($selTipo == $idTipo ? 'selected' : ''); ?>>
                                            <?php echo htmlspecialchars($labelTipo, ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Taller -->
                            <div class="col-12 col-md-6">
                                <label class="form-label form-label-sm mb-1">
                                    Taller
                                </label>
                                <select name="taller_id" class="form-select form-select-sm">
                                    <option value="">(Sin especificar)</option>
                                    <?php foreach ($talleres as $tl): ?>
                                        <?php
                                        $idTaller = (int)$tl['id'];
                                        $labelT = sprintf(
                                            '%s - %s (%s)',
                                            $tl['CVE_TALLER'],
                                            $tl['nombre'],
                                            $tl['tipo']
                                        );
                                        ?>
                                        <option value="<?php echo $idTaller; ?>" <?php echo ($selTaller == $idTaller ? 'selected' : ''); ?>>
                                            <?php echo htmlspecialchars($labelT, ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Origen -->
                            <div class="col-12 col-md-6">
                                <label class="form-label form-label-sm mb-1">
                                    Origen
                                </label>
                                <select name="origen" class="form-select form-select-sm">
                                    <option value="PROGRAMADO" <?php echo ($selOrigen === 'PROGRAMADO' ? 'selected' : ''); ?>>Programado</option>
                                    <option value="REPORTE_FALLA" <?php echo ($selOrigen === 'REPORTE_FALLA' ? 'selected' : ''); ?>>Reporte de falla</option>
                                    <option value="INSPECCION" <?php echo ($selOrigen === 'INSPECCION' ? 'selected' : ''); ?>>Inspección</option>
                                    <option value="OTRO" <?php echo ($selOrigen === 'OTRO' ? 'selected' : ''); ?>>Otro</option>
                                </select>
                            </div>

                            <!-- Fecha programada -->
                            <div class="col-12 col-md-6">
                                <label class="form-label form-label-sm mb-1">
                                    Fecha programada
                                </label>
                                <input
                                    type="datetime-local"
                                    name="fecha_programada"
                                    class="form-control form-control-sm"
                                    value="<?php echo htmlspecialchars($valFechaProg, ENT_QUOTES, 'UTF-8'); ?>"
                                >
                            </div>

                            <!-- Km programados -->
                            <div class="col-12 col-md-6">
                                <label class="form-label form-label-sm mb-1">
                                    Km programados
                                </label>
                                <input
                                    type="text"
                                    name="km_programados"
                                    class="form-control form-control-sm text-end"
                                    placeholder="0"
                                    value="<?php echo htmlspecialchars($valKmProg, ENT_QUOTES, 'UTF-8'); ?>"
                                >
                            </div>
                        </div>

                        <div class="mt-3 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fa fa-save me-1"></i> Guardar Orden
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Ayuda / Resumen -->
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header py-2">
                    <strong>Resumen</strong>
                </div>
                <div class="card-body py-2 small">
                    <p class="mb-1">
                        • La compañía (<code>cve_cia</code>) se toma desde el transporte seleccionado.
                    </p>
                    <p class="mb-1">
                        • El folio se genera automáticamente con formato:
                        <code>MTO-[ID_Transporte]-YYYYMMDDHHMMSS</code>.
                    </p>
                    <p class="mb-1">
                        • La orden se crea en estatus <strong>ABIERTA</strong>.
                    </p>
                    <p class="mb-1">
                        • En etapas posteriores se agregarán:
                    </p>
                    <ul class="mb-0">
                        <li>Actividades detalladas de la OT</li>
                        <li>Refacciones consumidas</li>
                        <li>Lectura de km/horas al inicio y cierre</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
