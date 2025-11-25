<?php
// public/templates/plantillas_filtros.php
// Administrador de plantillas de filtros AssistPro usando catálogo de módulos

declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$pdo = db_pdo();

// ==============================
// 1. Leer módulos desde ap_plantillas_modulos
// ==============================

$modulos = [];
try {
    $modulos = db_all("
        SELECT id, clave_modulo, nombre, vista_sql, descripcion
        FROM ap_plantillas_modulos
        WHERE activo = 1
        ORDER BY nombre
    ");
} catch (Throwable $e) {
    $modulos = [];
}

if (empty($modulos)) {
    ?>
    <div class="container-fluid" style="font-size:11px;">
        <div class="row mt-2">
            <div class="col-12">
                <h5>Plantillas de filtros – AssistPro</h5>
                <div class="alert alert-warning py-1 mt-2" style="font-size:11px;">
                    No hay módulos registrados en <code>ap_plantillas_modulos</code>.<br>
                    Primero da de alta módulos en
                    <a href="plantillas_modulos.php" class="alert-link">plantillas_modulos.php</a>.
                </div>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../_menu_global_end.php';
    exit;
}

// Resolver módulo seleccionado
$moduloClaveSel = $_GET['modulo'] ?? '';
if ($moduloClaveSel === '') {
    $moduloClaveSel = (string)$modulos[0]['clave_modulo'];
}

// Buscar la fila del módulo seleccionado
$modCfg = null;
foreach ($modulos as $m) {
    if ((string)$m['clave_modulo'] === (string)$moduloClaveSel) {
        $modCfg = $m;
        break;
    }
}
if (!$modCfg) {
    $modCfg = $modulos[0];
    $moduloClaveSel = (string)$modCfg['clave_modulo'];
}

$vistaSql = $modCfg['vista_sql'] ?? null;

// Plantilla seleccionada explícitamente (botón "Usar")
$tplIdSel = $_GET['tpl_id'] ?? '';

// ==============================
// 2. Cargar plantillas existentes del módulo
// ==============================

$plantillas = [];
try {
    $plantillas = db_all("
        SELECT id, nombre, descripcion, vista_sql, es_default, creado_en
        FROM ap_plantillas_filtros
        WHERE modulo = ? AND activo = 1
        ORDER BY es_default DESC, nombre
    ", [$moduloClaveSel]);
} catch (Throwable $e) {
    $plantillas = [];
}

// ==============================
// 3. Elegir plantilla a aplicar (default / primera) si no viene tpl_id
// ==============================

if ($tplIdSel === '' && !empty($plantillas)) {
    $defaultId = null;

    foreach ($plantillas as $p) {
        if ((int)$p['es_default'] === 1) {
            $defaultId = (int)$p['id'];
            break;
        }
    }

    if ($defaultId === null) {
        $defaultId = (int)$plantillas[0]['id'];
    }

    $tplIdSel = (string)$defaultId;
}

// ==============================
// 4. Aplicar plantilla seleccionada a $_GET (hidratar filtros)
// ==============================

$nombreTplActual = '';
if ($tplIdSel !== '') {
    $tplSeleccionada = db_one("
        SELECT *
        FROM ap_plantillas_filtros
        WHERE id = ? AND modulo = ? AND activo = 1
    ", [$tplIdSel, $moduloClaveSel]);

    if ($tplSeleccionada) {
        $nombreTplActual = (string)$tplSeleccionada['nombre'];
        $cfg = json_decode($tplSeleccionada['filtros_json'] ?? '', true);
        if (is_array($cfg)) {
            if (!empty($cfg['use']) && is_array($cfg['use'])) {
                foreach ($cfg['use'] as $k => $v) {
                    $_GET['use_'.$k] = $v ? '1' : '0';
                }
            }
            if (!empty($cfg['defaults']) && is_array($cfg['defaults'])) {
                foreach ($cfg['defaults'] as $k => $v) {
                    if (!isset($_GET[$k]) || $_GET[$k] === '') {
                        $_GET[$k] = (string)$v;
                    }
                }
            }
        }
    }
}

// ==============================
// 5. Render UI
// ==============================

?>
<div class="container-fluid" style="font-size:11px;">

    <div class="row mt-2">
        <div class="col-12">
            <h5>Plantillas de filtros – AssistPro</h5>
            <p class="text-muted mb-1">
                Módulo actual:
                <strong><?= htmlspecialchars($modCfg['nombre'], ENT_QUOTES, 'UTF-8') ?></strong>
                <span class="text-muted">
                    (clave: <code><?= htmlspecialchars($moduloClaveSel, ENT_QUOTES, 'UTF-8') ?></code>
                    <?= $vistaSql ? ', vista: <code>'.htmlspecialchars($vistaSql, ENT_QUOTES, 'UTF-8').'</code>' : '' ?>)
                </span><br>
                <?php if ($nombreTplActual !== ''): ?>
                    Plantilla aplicada: <strong><?= htmlspecialchars($nombreTplActual, ENT_QUOTES, 'UTF-8') ?></strong>
                    (ID <?= (int)$tplIdSel ?>)
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Selector de módulo + acceso a catálogo -->
    <div class="row mb-2">
        <div class="col-md-7">
            <form method="get" class="row g-2 align-items-center mb-1">
                <div class="col-auto">
                    <label for="modulo" class="col-form-label col-form-label-sm">
                        <strong>Proceso / módulo:</strong>
                    </label>
                </div>
                <div class="col-auto">
                    <select name="modulo" id="modulo" class="form-select form-select-sm"
                            onchange="this.form.submit()" style="min-width:260px;">
                        <?php foreach ($modulos as $m): ?>
                            <option value="<?= htmlspecialchars($m['clave_modulo'], ENT_QUOTES, 'UTF-8') ?>"
                                <?= ((string)$m['clave_modulo'] === (string)$moduloClaveSel ? 'selected' : '') ?>>
                                <?= htmlspecialchars($m['nombre'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($tplIdSel !== ''): ?>
                    <input type="hidden" name="tpl_id" value="<?= htmlspecialchars($tplIdSel, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>
            </form>
        </div>
        <div class="col-md-5 text-end">
            <a href="plantillas_modulos.php" class="btn btn-outline-secondary btn-sm">
                Catálogo de módulos
            </a>
        </div>
    </div>

    <!-- Lista de plantillas existentes -->
    <div class="row mb-2">
        <div class="col-12">
            <div class="card mb-2">
                <div class="card-header py-1" style="font-size:11px;">
                    <strong>Plantillas registradas para este módulo</strong>
                </div>
                <div class="card-body p-2" style="max-height:200px;overflow:auto;font-size:11px;">
                    <?php if (empty($plantillas)): ?>
                        <span class="text-muted">No hay plantillas definidas aún para este módulo.</span>
                    <?php else: ?>
                        <table class="table table-sm table-hover mb-0" style="font-size:10px;">
                            <thead>
                                <tr>
                                    <th style="width:40px;">ID</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th style="width:110px;">Vista SQL</th>
                                    <th style="width:70px;">Default</th>
                                    <th style="width:130px;">Creado en</th>
                                    <th style="width:80px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($plantillas as $p): ?>
                                <tr>
                                    <td><?= (int)$p['id'] ?></td>
                                    <td><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($p['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($p['vista_sql'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= $p['es_default'] ? '<span class="badge bg-success">Sí</span>' : '' ?></td>
                                    <td><?= htmlspecialchars((string)$p['creado_en'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <a href="?modulo=<?= urlencode($moduloClaveSel) ?>&tpl_id=<?= (int)$p['id'] ?>"
                                           class="btn btn-outline-primary btn-xs btn-sm"
                                           title="Usar como base">
                                            Usar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel maestro de filtros -->
    <div class="row mb-3">
        <div class="col-12">
            <?php
            require_once __DIR__ . '/../partials/filtros_assistpro.php';
            ?>
        </div>
    </div>

    <!-- Formulario para guardar nueva plantilla -->
    <div class="row mb-3">
        <div class="col-md-7">
            <div class="card">
                <div class="card-header py-1" style="font-size:11px;">
                    <strong>Guardar plantilla con los filtros actuales</strong>
                </div>
                <div class="card-body p-2" style="font-size:11px;">
                    <form method="post" action="plantillas_filtros_guardar.php" class="row g-2">

                        <input type="hidden" name="modulo"
                               value="<?= htmlspecialchars($moduloClaveSel, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="vista_sql"
                               value="<?= htmlspecialchars($vistaSql ?? '', ENT_QUOTES, 'UTF-8') ?>">

                        <div class="col-12">
                            <label class="form-label form-label-sm mb-0">
                                <strong>Nombre de la plantilla</strong>
                            </label>
                            <input type="text" name="nombre" class="form-control form-control-sm" required
                                   placeholder="Ej. Encabezado Generación de OTs">
                        </div>

                        <div class="col-12">
                            <label class="form-label form-label-sm mb-0">Descripción</label>
                            <input type="text" name="descripcion" class="form-control form-control-sm"
                                   placeholder="Descripción breve del uso de la plantilla">
                        </div>

                        <div class="col-12">
                            <hr class="my-2">
                            <p class="text-muted mb-1">
                                Se guardarán los filtros tal como están aplicados ahora (checks y valores).
                            </p>
                        </div>

                        <?php
                        $namesSimple = [
                            'empresa','almacen','zona','bl',
                            'lp','producto','lote',
                            'ruta','cliente','proveedor',
                            'vendedor','usuario',
                            'zona_recep','zona_qa','zona_emb',
                            'proyecto',
                            'ubica_mfg',
                        ];

                        $namesUse = [
                            'empresa','almacen','zona','bl',
                            'lp','producto','lote',
                            'ruta','cliente','proveedor',
                            'vendedor','usuario',
                            'zona_recep','zona_qa','zona_emb',
                            'proyecto',
                            'ubica_mfg',
                        ];

                        foreach ($namesSimple as $n):
                            $val = $_GET[$n] ?? '';
                        ?>
                            <input type="hidden"
                                   name="<?= htmlspecialchars($n, ENT_QUOTES, 'UTF-8') ?>"
                                   value="<?= htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') ?>">
                        <?php endforeach; ?>

                        <?php foreach ($namesUse as $n):
                            $key = 'use_'.$n;
                            $val = $_GET[$key] ?? '1';
                        ?>
                            <input type="hidden"
                                   name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                                   value="<?= htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') ?>">
                        <?php endforeach; ?>

                        <div class="col-12 mt-1">
                            <button type="submit" class="btn btn-success btn-sm">
                                Guardar plantilla
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <!-- Ayuda -->
        <div class="col-md-5">
            <div class="card">
                <div class="card-header py-1" style="font-size:11px;">
                    <strong>Cómo usar este constructor</strong>
                </div>
                <div class="card-body p-2" style="font-size:11px;">
                    <ol class="mb-1">
                        <li>Selecciona un <strong>módulo</strong> desde el catálogo.</li>
                        <li>
                            Ajusta los filtros (checkbox y valores). Al volver a este módulo,
                            se aplicará automáticamente la plantilla default o la primera registrada.
                        </li>
                        <li>Captura el <strong>nombre</strong> y la descripción de la plantilla.</li>
                        <li>Da clic en <strong>Guardar plantilla</strong>.</li>
                        <li>Las vistas de negocio (reportes, OT, reabasto) solo leen estas
                            plantillas desde BD usando la clave de módulo.
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
require_once __DIR__ . '/../bi/_menu_global_end.php';
