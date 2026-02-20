<?php
// public/procesos/servicio_depot/admin_ingenieria_servicio.php
declare(strict_types=1);

require_once __DIR__ . '/../../../app/db.php';

if (session_status() === PHP_SESSION_NONE) {
    //@session_start();
}
require_once __DIR__ . '/../../bi/_menu_global.php';

$TITLE = 'Ingeniería de Servicio – Administración';

$pdo = db_pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// =======================
// Parámetros de filtro (GET)
// =======================

$f_almacen = trim($_GET['almacen'] ?? '');
$f_status = trim($_GET['status'] ?? '');
$f_motivo = trim($_GET['motivo'] ?? '');
$f_cliente = trim($_GET['cliente'] ?? '');
$f_articulo = trim($_GET['articulo'] ?? '');
$f_serie = trim($_GET['serie'] ?? '');
$f_usuario = trim($_GET['usuario'] ?? '');
$f_fi = trim($_GET['fi'] ?? '');
$f_ff = trim($_GET['ff'] ?? '');

// Normalizar fechas: si vienen dd/mm/yyyy las convertimos a yyyy-mm-dd
function parse_fecha(string $d): ?string
{
    $d = trim($d);
    if ($d === '')
        return null;
    if (preg_match('~^(\d{2})/(\d{2})/(\d{4})$~', $d, $m)) {
        return "{$m[3]}-{$m[2]}-{$m[1]}";
    }
    if (preg_match('~^\d{4}-\d{2}-\d{2}$~', $d)) {
        return $d;
    }
    return null;
}

$fi_sql = parse_fecha($f_fi);
$ff_sql = parse_fecha($f_ff);

// =======================
// Construir WHERE dinámico
// =======================

$where = [];
$params = [];

if ($f_almacen !== '') {
    $where[] = 's.origen_almacen_id = :almacen';
    $params[':almacen'] = $f_almacen;
}
if ($f_status !== '') {
    $where[] = 's.status = :status';
    $params[':status'] = $f_status;
}
if ($f_motivo !== '') {
    $where[] = 's.motivo = :motivo';
    $params[':motivo'] = $f_motivo;
}
if ($f_cliente !== '') {
    $where[] = '(c.id_cliente = :cliente_id OR c.RazonSocial LIKE :cliente_txt OR c.Cve_Clte LIKE :cliente_txt)';
    $params[':cliente_id'] = (int) $f_cliente;
    $params[':cliente_txt'] = '%' . $f_cliente . '%';
}
if ($f_articulo !== '') {
    $where[] = 's.articulo LIKE :articulo';
    $params[':articulo'] = '%' . $f_articulo . '%';
}
if ($f_serie !== '') {
    $where[] = 's.serie LIKE :serie';
    $params[':serie'] = '%' . $f_serie . '%';
}
if ($f_usuario !== '') {
    $where[] = 's.created_by LIKE :usuario';
    $params[':usuario'] = '%' . $f_usuario . '%';
}
if ($fi_sql !== null) {
    $where[] = 'DATE(s.fecha_alta) >= :fi';
    $params[':fi'] = $fi_sql;
}
if ($ff_sql !== null) {
    $where[] = 'DATE(s.fecha_alta) <= :ff';
    $params[':ff'] = $ff_sql;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// =======================
// Resumen para cards
// =======================

$sqlResumen = "
    SELECT
        COUNT(*)                                           AS total_casos,
        SUM(CASE WHEN s.status NOT IN ('ENTREGADO','CANCELADO') THEN 1 ELSE 0 END) AS total_abiertos,
        SUM(CASE WHEN s.es_garantia = 1 THEN 1 ELSE 0 END)                         AS total_garantia,
        SUM(CASE WHEN s.es_garantia = 0 THEN 1 ELSE 0 END)                         AS total_servicio
    FROM th_servicio_caso s
    $whereSql
";
$stRes = $pdo->prepare($sqlResumen);
$stRes->execute($params);
$resumen = $stRes->fetch(PDO::FETCH_ASSOC) ?: [
    'total_casos' => 0,
    'total_abiertos' => 0,
    'total_garantia' => 0,
    'total_servicio' => 0,
];

// Top por status (para combos e info)
$sqlStatus = "SELECT DISTINCT s.status FROM th_servicio_caso s ORDER BY s.status";
$statusRows = $pdo->query($sqlStatus)->fetchAll(PDO::FETCH_COLUMN) ?: [];

// Distintos motivos
$sqlMotivo = "SELECT DISTINCT s.motivo FROM th_servicio_caso s ORDER BY s.motivo";
$motivos = $pdo->query($sqlMotivo)->fetchAll(PDO::FETCH_COLUMN) ?: [];

// =======================
// Traer casos (grilla principal)
// =======================

$sqlCasos = "
    SELECT s.id, s.folio, s.fecha_alta, s.articulo, s.serie,
           s.motivo, s.es_garantia, s.status,
           s.created_by,
           a.clave AS almacen_clave,
           a.nombre AS almacen_nombre,

           c.RazonSocial AS cliente_nombre,
           c.Cve_Clte    AS cliente_clave
    FROM th_servicio_caso s
    LEFT JOIN c_almacenp a ON a.id = s.origen_almacen_id
    LEFT JOIN c_cliente c ON c.id_cliente = s.cliente_id
    $whereSql
    ORDER BY s.fecha_alta DESC
    LIMIT 200
";
$stCasos = $pdo->prepare($sqlCasos);
$stCasos->execute($params);
$casos = $stCasos->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="container-fluid mt-3">
    <div class="row mb-2">
        <div class="col-12 d-flex justify-content-between align-items-end">
            <div>
                <h4 class="mb-0">Ingeniería de Servicio – Administración</h4>
                <small class="text-muted">
                    Seguimiento de casos por cliente, producto, status, almacén y usuario.
                </small>
            </div>
            <div class="text-end" style="font-size:0.8rem;">
                <a href="recepcion.php" class="btn btn-outline-secondary btn-sm">
                    &laquo; Recepción Depot
                </a>
                <a href="laboratorio_servicio.php" class="btn btn-outline-primary btn-sm">
                    Laboratorio
                </a>
            </div>
        </div>
    </div>

    <!-- Cards resumen -->
    <div class="row mb-3">
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card shadow-sm border-primary">
                <div class="card-body py-2" style="font-size:0.85rem;">
                    <div class="text-muted">Total casos</div>
                    <div class="fw-bold" style="font-size:1.2rem;">
                        <?= (int) $resumen['total_casos'] ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card shadow-sm border-warning">
                <div class="card-body py-2" style="font-size:0.85rem;">
                    <div class="text-muted">Casos abiertos</div>
                    <div class="fw-bold" style="font-size:1.2rem;">
                        <?= (int) $resumen['total_abiertos'] ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card shadow-sm border-success">
                <div class="card-body py-2" style="font-size:0.85rem;">
                    <div class="text-muted">Garantía</div>
                    <div class="fw-bold" style="font-size:1.2rem;">
                        <?= (int) $resumen['total_garantia'] ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card shadow-sm border-info">
                <div class="card-body py-2" style="font-size:0.85rem;">
                    <div class="text-muted">Servicio con cobro</div>
                    <div class="fw-bold" style="font-size:1.2rem;">
                        <?= (int) $resumen['total_servicio'] ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-3">
        <div class="card-header py-2">
            <strong>Filtros de búsqueda</strong>
        </div>
        <div class="card-body" style="font-size:0.8rem;">
            <form method="get" class="row g-2">
                <div class="col-md-2">
                    <label class="form-label mb-1">Almacén</label>
                    <!-- Se llenará vía API como en recepción (por clave) -->
                    <select id="filtroAlmacen" name="almacen" class="form-select form-select-sm">
                        <option value="">[Todos]</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">[Todos]</option>
                        <?php foreach ($statusRows as $st): ?>
                            <option value="<?= htmlspecialchars($st) ?>" <?= $f_status === $st ? 'selected' : '' ?>>
                                <?= htmlspecialchars($st) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Motivo</label>
                    <select name="motivo" class="form-select form-select-sm">
                        <option value="">[Todos]</option>
                        <?php foreach ($motivos as $m): ?>
                            <option value="<?= htmlspecialchars($m) ?>" <?= $f_motivo === $m ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Cliente (ID / nombre / clave)</label>
                    <input type="text" name="cliente" value="<?= htmlspecialchars($f_cliente) ?>"
                        class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Artículo</label>
                    <input type="text" name="articulo" value="<?= htmlspecialchars($f_articulo) ?>"
                        class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Serie</label>
                    <input type="text" name="serie" value="<?= htmlspecialchars($f_serie) ?>"
                        class="form-control form-control-sm">
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">Usuario</label>
                    <input type="text" name="usuario" value="<?= htmlspecialchars($f_usuario) ?>"
                        class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Fecha inicio</label>
                    <input type="text" name="fi" value="<?= htmlspecialchars($f_fi) ?>"
                        class="form-control form-control-sm" placeholder="dd/mm/aaaa">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Fecha fin</label>
                    <input type="text" name="ff" value="<?= htmlspecialchars($f_ff) ?>"
                        class="form-control form-control-sm" placeholder="dd/mm/aaaa">
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm me-1">Buscar</button>
                    <a href="admin_ingenieria_servicio.php" class="btn btn-outline-secondary btn-sm">
                        Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Grilla de casos -->
    <div class="card shadow-sm mb-3">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong>Casos (máx. 200 resultados)</strong>
            <small class="text-muted">Ordenados por fecha de alta descendente</small>
        </div>
        <div class="card-body p-2" style="font-size:0.8rem;">
            <div class="table-responsive" style="max-height:480px; overflow:auto;">
                <table class="table table-striped table-hover table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Almacén</th>
                            <th>Cliente</th>
                            <th>Artículo</th>
                            <th>Serie</th>
                            <th>Motivo</th>
                            <th>Garantía</th>
                            <th>Status</th>
                            <th>Usuario</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($casos)): ?>
                            <tr>
                                <td colspan="11" class="text-center text-muted py-3">
                                    No se encontraron casos con los filtros aplicados.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($casos as $c): ?>
                                <tr>
                                    <td><?= htmlspecialchars($c['folio']) ?></td>
                                    <td><?= htmlspecialchars($c['fecha_alta']) ?></td>
                                    <td>
                                        <?= htmlspecialchars(($c['almacen_clave'] ?? '') .
                                                (isset($c['almacen_nombre']) ? ' - ' . $c['almacen_nombre'] : '')
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= '[' . htmlspecialchars($c['cliente_clave'] ?? '') . '] ' .
                                            htmlspecialchars($c['cliente_nombre'] ?? '') ?>
                                    </td>
                                    <td><?= htmlspecialchars($c['articulo']) ?></td>
                                    <td><?= htmlspecialchars($c['serie']) ?></td>
                                    <td><?= htmlspecialchars($c['motivo']) ?></td>
                                    <td>
                                        <?php if ((int) $c['es_garantia'] === 1): ?>
                                            <span class="badge bg-success">Sí</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($c['status']) ?></td>
                                    <td><?= htmlspecialchars($c['created_by']) ?></td>
                                    <td>
                                        <a href="servicio_ingreso_pdf.php?id=<?= (int) $c['id'] ?>"
                                            class="btn btn-outline-primary btn-sm btn-icon" title="Ver ingreso PDF"
                                            target="_blank">
                                            PDF
                                        </a>
                                        <?php if (isset($c['motivo']) && strtoupper($c['motivo']) === 'SERVICIO'): ?>
                                            <a href="servicio_generar_cotizacion.php?id=<?= (int) $c['id'] ?>"
                                                class="btn btn-warning btn-sm btn-icon mt-1" title="Generar cotización CRM">
                                                Cotizar
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Cargar catálogo de almacenes desde el mismo API de filtros
    document.addEventListener('DOMContentLoaded', function() {
        const apiUrl = '../../api/filtros_assistpro.php?action=init';
        const valorActual = '<?= htmlspecialchars($f_almacen, ENT_QUOTES) ?>';

        fetch(apiUrl, {
                method: 'GET'
            })
            .then(resp => resp.json())
            .then(data => {
                if (!data || data.ok === false) {
                    console.error('Error filtros_assistpro:', data && data.error);
                    return;
                }
                const sel = document.getElementById('filtroAlmacen');
                if (!sel) return;

                const valorTodos = sel.querySelector('option[value=""]').outerHTML;
                sel.innerHTML = valorTodos;

                if (Array.isArray(data.almacenes)) {
                    data.almacenes.forEach(a => {

                        const opt = document.createElement('option');

                        // MISMO VALUE QUE RECEPCION
                        opt.value = a.idp;

                        // CLAVE - NOMBRE
                        const clave = a.cve_almac || '';
                        const nombre = a.nombre || '';

                        opt.textContent = nombre ?
                            clave + ' - ' + nombre :
                            clave;

                        if (valorActual !== '' && valorActual === String(opt.value)) {
                            opt.selected = true;
                        }

                        sel.appendChild(opt);
                    });
                }


            })
            .catch(err => console.error('Error cargando almacenes:', err));
    });
</script>

<?php
require_once __DIR__ . '/../../bi/_menu_global_end.php';
