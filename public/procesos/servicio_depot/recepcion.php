<?php
// public/procesos/servicio_depot/recepcion.php
declare(strict_types=1);

require_once __DIR__ . '/../../../app/db.php';

// Iniciar sesión ANTES de cualquier salida (evita warning)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../bi/_menu_global.php';

$TITLE = 'Servicio – Recepción Depot';

$pdo = db_pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$usuarioActual = $_SESSION['usuario'] ?? 'SYSTEM';

$mensajeOk  = null;
$mensajeError = null;
$linkPdfIngreso = null;

/**
 * Generar folio simple SRG-YYYYMMDD-NNN
 */
function generarFolioServicio(PDO $pdo): string
{
    $base = 'SRG-' . date('Ymd') . '-';
    $rand = random_int(100, 999);
    return $base . $rand;
}

// =======================
// Catálogo de servicios (BD)
// =======================

$servicios = [];
try {
    $sqlServ = "SELECT id, clave, descripcion 
                FROM c_servicio
                WHERE activo = 1
                ORDER BY descripcion";
    $servicios = $pdo->query($sqlServ)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $mensajeError = 'Error al cargar catálogo de servicios: ' . $e->getMessage();
}

// =======================
// Procesar alta de caso
// =======================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'crear_caso') {
    $origen_almacen_id = isset($_POST['origen_almacen_id']) ? trim((string)$_POST['origen_almacen_id']) : '';
    $cliente_id        = isset($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : 0;
    $articulo          = trim($_POST['articulo'] ?? '');
    $serie             = trim($_POST['serie'] ?? '');
    $motivo            = $_POST['motivo'] ?? 'GARANTIA';
    $servicio_id       = isset($_POST['servicio_id']) ? (int)$_POST['servicio_id'] : null;
    $observacion       = trim($_POST['observacion_inicial'] ?? '');

    $es_garantia = ($motivo === 'GARANTIA') ? 1 : 0;

    // Validaciones simples
    $errores = [];
    if ($origen_almacen_id === '') {
        $errores[] = 'Debe seleccionar un almacén (Depot).';
    }
    if ($cliente_id <= 0) {
        $errores[] = 'Debe seleccionar un cliente.';
    }
    if ($articulo === '') {
        $errores[] = 'Debe seleccionar el artículo.';
    }
    if ($serie === '') {
        $errores[] = 'Debe capturar el número de serie.';
    }

    if (empty($errores)) {
        try {
            $pdo->beginTransaction();

            $folio = generarFolioServicio($pdo);

            $sqlIns = "INSERT INTO th_servicio_caso (
                            folio, fecha_alta,
                            origen_tipo, origen_almacen_id, destino_almacen_id,
                            cliente_id, articulo, serie,
                            motivo, es_garantia,
                            servicio_id, precio_lista_id, cotizacion_id, laboratorio_id,
                            status, observacion_inicial,
                            token_publico,
                            created_at, created_by
                       ) VALUES (
                            :folio, NOW(),
                            'DEPOT', :origen_almacen_id, NULL,
                            :cliente_id, :articulo, :serie,
                            :motivo, :es_garantia,
                            :servicio_id, NULL, NULL, NULL,
                            'RECIBIDO_DEPOT', :observacion_inicial,
                            NULL,
                            NOW(), :created_by
                       )";

            $st = $pdo->prepare($sqlIns);
            $st->execute([
                ':folio'              => $folio,
                ':origen_almacen_id'  => $origen_almacen_id,
                ':cliente_id'         => $cliente_id,
                ':articulo'           => $articulo,
                ':serie'              => $serie,
                ':motivo'             => $motivo,
                ':es_garantia'        => $es_garantia,
                ':servicio_id'        => $servicio_id ?: null,
                ':observacion_inicial'=> $observacion ?: null,
                ':created_by'         => $usuarioActual,
            ]);

            $servicioId = (int)$pdo->lastInsertId();

            // Primer registro en bitácora
            $sqlLog = "INSERT INTO td_servicio_caso_log (
                            servicio_id, fecha, usuario, evento, detalle, created_at, created_by
                       ) VALUES (
                            :servicio_id, NOW(), :usuario, 'CREACION',
                            :detalle, NOW(), :created_by
                       )";
            $stLog = $pdo->prepare($sqlLog);
            $detalle = 'Caso creado en Depot con folio ' . $folio;
            $stLog->execute([
                ':servicio_id' => $servicioId,
                ':usuario'     => $usuarioActual,
                ':detalle'     => $detalle,
                ':created_by'  => $usuarioActual,
            ]);

            $pdo->commit();

            // Manejo de fotos de ENTRADA
            if (!empty($_FILES['fotos']) && is_array($_FILES['fotos']['name'])) {
                $uploadBaseDir = __DIR__ . '/../../uploads/servicio/' . $servicioId;
                if (!is_dir($uploadBaseDir)) {
                    @mkdir($uploadBaseDir, 0775, true);
                }

                $fileCount = count($_FILES['fotos']['name']);
                for ($i = 0; $i < $fileCount; $i++) {
                    $name     = $_FILES['fotos']['name'][$i] ?? '';
                    $tmpName  = $_FILES['fotos']['tmp_name'][$i] ?? '';
                    $error    = $_FILES['fotos']['error'][$i] ?? UPLOAD_ERR_NO_FILE;

                    if ($error !== UPLOAD_ERR_OK || $tmpName === '') {
                        continue;
                    }

                    $ext = pathinfo($name, PATHINFO_EXTENSION);
                    $ext = $ext ? ('.' . strtolower($ext)) : '';
                    $fileName = 'ENTRADA_' . date('Ymd_His') . '_' . $i . $ext;
                    $destPath = $uploadBaseDir . '/' . $fileName;

                    if (@move_uploaded_file($tmpName, $destPath)) {
                        $rutaRel = 'uploads/servicio/' . $servicioId . '/' . $fileName;

                        $sqlFoto = "INSERT INTO t_servicio_foto (
                                        servicio_id, etapa, ruta, nota, created_at, created_by
                                    ) VALUES (
                                        :servicio_id, 'ENTRADA', :ruta, NULL, NOW(), :created_by
                                    )";
                        $stFoto = $pdo->prepare($sqlFoto);
                        $stFoto->execute([
                            ':servicio_id' => $servicioId,
                            ':ruta'        => $rutaRel,
                            ':created_by'  => $usuarioActual,
                        ]);

                        $sqlLogFoto = "INSERT INTO td_servicio_caso_log (
                                            servicio_id, fecha, usuario, evento, detalle, created_at, created_by
                                       ) VALUES (
                                            :servicio_id, NOW(), :usuario, 'FOTO_ADJUNTA',
                                            :detalle, NOW(), :created_by
                                       )";
                        $stLogFoto = $pdo->prepare($sqlLogFoto);
                        $detalleFoto = 'Foto de entrada registrada: ' . $fileName;
                        $stLogFoto->execute([
                            ':servicio_id' => $servicioId,
                            ':usuario'     => $usuarioActual,
                            ':detalle'     => $detalleFoto,
                            ':created_by'  => $usuarioActual,
                        ]);
                    }
                }
            }

            // Link al PDF de ingreso (vista Dompdf que ya tienes)
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $base   = rtrim(dirname($_SERVER['REQUEST_URI']), '/\\');
            $linkPdfIngreso = $scheme . $host . $base . '/servicio_ingreso_pdf.php?id=' . $servicioId;

            $mensajeOk = "Caso creado correctamente con folio <strong>{$folio}</strong>. "
                       . "Puedes imprimir el ingreso en PDF "
                       . "<a href=\"{$linkPdfIngreso}\" target=\"_blank\">aquí</a>.";

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $mensajeError = 'Error al crear el caso: ' . $e->getMessage();
        }
    } else {
        $mensajeError = implode('<br>', $errores);
    }
}

?>
<div class="container-fluid mt-3">
    <div class="row mb-2">
        <div class="col-12 d-flex justify-content-between align-items-end">
            <div>
                <h4 class="mb-0">Recepción de Equipos – Depot</h4>
                <small class="text-muted">Registro inicial de casos de servicio y garantía.</small>
            </div>
            <div class="text-end" style="font-size:0.8rem;">
                <a href="admin_ingenieria_servicio.php" class="btn btn-outline-primary btn-sm">
                    Panel de ingeniería / seguimiento
                </a>
            </div>
        </div>
    </div>

    <?php if ($mensajeOk): ?>
        <div class="alert alert-success alert-sm py-2">
            <?= $mensajeOk ?>
        </div>
    <?php endif; ?>

    <?php if ($mensajeError): ?>
        <div class="alert alert-danger alert-sm py-2">
            <?= $mensajeError ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Solo panel de captura, pantalla limpia -->
        <div class="col-lg-6 col-xl-5">
            <div class="card shadow-sm mb-3">
                <div class="card-header py-2">
                    <strong>Nuevo caso</strong>
                </div>
                <div class="card-body" style="font-size: 0.85rem;">
                    <form method="post" autocomplete="off" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="crear_caso">

                        <div class="mb-2">
                            <label class="form-label mb-1">Almacén (Depot)</label>
                            <select id="selAlmacenDepot" name="origen_almacen_id"
                                    class="form-select form-select-sm" required>
                                <option value="">Cargando almacenes...</option>
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label mb-1">Cliente</label>
                            <select id="selCliente" name="cliente_id"
                                    class="form-select form-select-sm" required>
                                <option value="">Cargando clientes...</option>
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label mb-1">Artículo</label>
                            <select id="selArticulo" name="articulo"
                                    class="form-select form-select-sm" required>
                                <option value="">Cargando productos...</option>
                            </select>
                            <small class="text-muted">
                                Catálogo de artículos desde c_articulo (API filtros_assistpro).
                            </small>
                        </div>

                        <div class="mb-2">
                            <label class="form-label mb-1">Número de serie</label>
                            <input type="text" name="serie" class="form-control form-control-sm"
                                   maxlength="100" required>
                        </div>

                        <div class="mb-2">
                            <label class="form-label mb-1 d-block">Motivo</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio"
                                       name="motivo" id="motivo_gar" value="GARANTIA" checked>
                                <label class="form-check-label" for="motivo_gar">Garantía</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio"
                                       name="motivo" id="motivo_srv" value="SERVICIO">
                                <label class="form-check-label" for="motivo_srv">Servicio con cobro</label>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label mb-1">Servicio</label>
                            <select name="servicio_id" class="form-select form-select-sm">
                                <option value="">-- Seleccione --</option>
                                <?php foreach ($servicios as $srv): ?>
                                    <option value="<?= (int)$srv['id'] ?>">
                                        <?= htmlspecialchars($srv['descripcion']) ?>
                                        (<?= htmlspecialchars($srv['clave']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label mb-1">Observaciones iniciales</label>
                            <textarea name="observacion_inicial" rows="3"
                                      class="form-control form-control-sm"></textarea>
                        </div>

                        <div class="mb-2">
                            <label class="form-label mb-1">Fotos de entrada</label>
                            <input type="file" name="fotos[]" class="form-control form-control-sm"
                                   accept="image/*" multiple>
                            <small class="text-muted">
                                Puedes adjuntar una o varias fotos como evidencia de recepción.
                            </small>
                        </div>

                        <div class="d-grid mt-3">
                            <button type="submit" class="btn btn-primary btn-sm">
                                Registrar caso
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Columna derecha queda libre para futuro info contextual / instrucciones -->
        <div class="col-lg-6 col-xl-7">
            <div class="card shadow-sm mb-3">
                <div class="card-header py-2">
                    <strong>Ayuda rápida</strong>
                </div>
                <div class="card-body" style="font-size:0.8rem;">
                    <ul class="mb-1">
                        <li>Esta vista está pensada solo para <strong>recepción en Depot</strong>.</li>
                        <li>El seguimiento completo se realiza en
                            <a href="admin_ingenieria_servicio.php">Administración de ingeniería de servicio</a>.
                        </li>
                        <li>Posteriormente se integrarán:
                            <ul>
                                <li>Consulta de historial por serie.</li>
                                <li>Validación automática de garantía por fecha de compra.</li>
                                <li>Cola de correos automáticos (ingreso, envío a laboratorio, cierre, etc.).</li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Cargar almacenes, clientes y productos desde public/api/filtros_assistpro.php
document.addEventListener('DOMContentLoaded', function () {
    const apiUrl = '../../api/filtros_assistpro.php?action=init';

    fetch(apiUrl, { method: 'GET' })
        .then(resp => resp.json())
        .then(data => {
            if (!data || data.ok === false) {
                console.error('Error en filtros_assistpro:', data && data.error);
                return;
            }

            // Almacenes
            const selAlm = document.getElementById('selAlmacenDepot');
            if (selAlm) {
                selAlm.innerHTML = '<option value="">-- Seleccione --</option>';
                if (Array.isArray(data.almacenes)) {
                    data.almacenes.forEach(a => {
                        const opt = document.createElement('option');
                        opt.value = a.cve_almac;
                        opt.textContent = a.des_almac || a.clave_almacen || a.cve_almac;
                        selAlm.appendChild(opt);
                    });
                }
            }

            // Clientes
            const selCli = document.getElementById('selCliente');
            if (selCli) {
                selCli.innerHTML = '<option value="">-- Seleccione --</option>';
                if (Array.isArray(data.clientes)) {
                    data.clientes.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.id_cliente;
                        opt.textContent = '[' + c.Cve_Clte + '] ' + c.RazonSocial;
                        selCli.appendChild(opt);
                    });
                }
            }

            // Productos
            const selArt = document.getElementById('selArticulo');
            if (selArt) {
                selArt.innerHTML = '<option value="">-- Seleccione --</option>';
                if (Array.isArray(data.productos)) {
                    data.productos.forEach(p => {
                        const opt = document.createElement('option');
                        opt.value = p.cve_articulo;
                        opt.textContent = '[' + p.cve_articulo + '] ' + p.des_articulo;
                        selArt.appendChild(opt);
                    });
                }
            }
        })
        .catch(err => {
            console.error('Error cargando filtros_assistpro:', err);
        });
});
</script>

<?php
require_once __DIR__ . '/../../bi/_menu_global_end.php';
