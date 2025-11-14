<?php
// public/sfa/lista_precios.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$alerta_error = null;

// ======================================================
// Filtros (GET)
// ======================================================
$almacen_id = isset($_GET['almacen']) && $_GET['almacen'] !== '' ? (int)$_GET['almacen'] : null;
$moneda_id  = isset($_GET['moneda'])  && $_GET['moneda']  !== '' ? (int)$_GET['moneda']  : null;
$vigencia   = $_GET['vigencia'] ?? 'vigentes'; // vigentes | vencidas | futuras | todas
$texto_busq = trim($_GET['q'] ?? '');

// Hoy (para vigencia)
$hoy = date('Y-m-d');

// ======================================================
// Catálogos para filtros
// ======================================================

// Almacenes principales (c_almacenp)
$almacenes = db_all("
    SELECT id, clave, nombre
    FROM c_almacenp
    WHERE Activo IS NULL OR Activo = 1
    ORDER BY clave, nombre
");

// Monedas
$monedas = db_all("
    SELECT Id_Moneda, Cve_Moneda, Des_Moneda
    FROM c_monedas
    WHERE Activo = 1
    ORDER BY Cve_Moneda
");

// ======================================================
// Consulta principal de listas (listap)
// ======================================================
$where  = [];
$params = [];

// Filtro almacén (por almacén padre c_almacenp)
if ($almacen_id !== null) {
    $where[]  = ' cap.id = ? ';
    $params[] = $almacen_id;
}

// Filtro moneda
if ($moneda_id !== null) {
    $where[]  = ' lp.id_moneda = ? ';
    $params[] = $moneda_id;
}

// Filtro vigencia
if ($vigencia === 'vigentes') {
    $where[]  = ' ( (lp.FechaIni IS NULL OR DATE(lp.FechaIni) <= ?) 
                  AND (lp.FechaFin IS NULL OR DATE(lp.FechaFin) >= ?) )';
    $params[] = $hoy;
    $params[] = $hoy;
} elseif ($vigencia === 'vencidas') {
    $where[]  = ' lp.FechaFin IS NOT NULL AND DATE(lp.FechaFin) < ? ';
    $params[] = $hoy;
} elseif ($vigencia === 'futuras') {
    $where[]  = ' lp.FechaIni IS NOT NULL AND DATE(lp.FechaIni) > ? ';
    $params[] = $hoy;
}

// Búsqueda por texto en nombre de lista
if ($texto_busq !== '') {
    $where[]  = ' lp.Lista LIKE ? ';
    $params[] = '%' . $texto_busq . '%';
}

$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Consulta encabezados
$sql = "
    SELECT
        lp.id,
        lp.Lista,
        lp.Tipo,
        lp.FechaIni,
        lp.FechaFin,
        lp.Cve_Almac,
        lp.TipoServ,
        lp.id_moneda,

        -- Almacén (c_almacen / c_almacenp)
        ca.clave_almacen,
        ca.des_almac,
        cap.id           AS id_almacenp,
        cap.clave        AS clave_almacenp,
        cap.nombre       AS nombre_almacenp,

        -- Moneda
        mon.Cve_Moneda,
        mon.Des_Moneda
    FROM listap lp
    LEFT JOIN c_almacen ca
        ON ca.cve_almac = lp.Cve_Almac
    LEFT JOIN c_almacenp cap
        ON cap.id = ca.cve_almacenp
    LEFT JOIN c_monedas mon
        ON mon.Id_Moneda = lp.id_moneda
    $where_sql
    ORDER BY lp.Lista ASC, lp.id ASC
";

$listas = db_all($sql, $params);

// Totales para cards
$total_listas      = count($listas);
$total_vigentes    = 0;
$total_vencidas    = 0;
$total_futuras     = 0;

function calcular_status_vigencia(?string $fechaIni, ?string $fechaFin, string $hoy): string {
    if (empty($fechaIni) && empty($fechaFin)) {
        return 'SIN FECHAS';
    }

    $ini = $fechaIni ? substr($fechaIni, 0, 10) : null;
    $fin = $fechaFin ? substr($fechaFin, 0, 10) : null;

    if (($ini === null || $ini <= $hoy) && ($fin === null || $fin >= $hoy)) {
        return 'VIGENTE';
    }

    if ($fin !== null && $fin < $hoy) {
        return 'VENCIDA';
    }

    if ($ini !== null && $ini > $hoy) {
        return 'FUTURA';
    }

    return 'SIN FECHAS';
}

// Precalcula status para totales
foreach ($listas as &$lp) {
    $status = calcular_status_vigencia($lp['FechaIni'] ?? null, $lp['FechaFin'] ?? null, $hoy);
    $lp['status_vig'] = $status;

    if ($status === 'VIGENTE') $total_vigentes++;
    elseif ($status === 'VENCIDA') $total_vencidas++;
    elseif ($status === 'FUTURA') $total_futuras++;
}
unset($lp);
?>
<div class="container-fluid mt-2">
    <!-- Título -->
    <div class="row mb-2">
        <div class="col-12">
            <h5 style="color:#0F5AAD;font-weight:bold;">
                Administración de Listas de Precios
            </h5>
            <div style="font-size:11px;color:#666;">
                Módulo AssistPro SFA — Listas de precios para fuerza de ventas.
            </div>
        </div>
    </div>

    <!-- Filtros + Cards -->
    <div class="row g-2 mb-2">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header py-1" style="background:#0F5AAD;color:#fff;">
                    <div class="d-flex justify-content-between align-items-center">
                        <span style="font-size:12px;font-weight:bold;">Filtros</span>
                        <span style="font-size:10px;">Listas de precios</span>
                    </div>
                </div>
                <div class="card-body py-2">
                    <form class="row g-2 align-items-end" method="get" action="">
                        <div class="col-12 col-md-4">
                            <label class="form-label mb-0" style="font-size:11px;">Almacén</label>
                            <select name="almacen" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <?php foreach ($almacenes as $a): ?>
                                    <option value="<?= htmlspecialchars((string)$a['id']) ?>"
                                        <?= $almacen_id === (int)$a['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(($a['clave'] ?? '') . ' - ' . ($a['nombre'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label mb-0" style="font-size:11px;">Moneda</label>
                            <select name="moneda" class="form-select form-select-sm">
                                <option value="">Todas</option>
                                <?php foreach ($monedas as $m): ?>
                                    <option value="<?= htmlspecialchars((string)$m['Id_Moneda']) ?>"
                                        <?= $moneda_id === (int)$m['Id_Moneda'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(($m['Cve_Moneda'] ?? '') . ' - ' . ($m['Des_Moneda'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label mb-0" style="font-size:11px;">Vigencia</label>
                            <select name="vigencia" class="form-select form-select-sm">
                                <option value="vigentes" <?= $vigencia === 'vigentes' ? 'selected' : '' ?>>Vigentes</option>
                                <option value="vencidas" <?= $vigencia === 'vencidas' ? 'selected' : '' ?>>Vencidas</option>
                                <option value="futuras"  <?= $vigencia === 'futuras'  ? 'selected' : '' ?>>Futuras</option>
                                <option value="todas"    <?= $vigencia === 'todas'    ? 'selected' : '' ?>>Todas</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-2">
                            <label class="form-label mb-0" style="font-size:11px;">Buscar</label>
                            <input type="text" name="q" value="<?= htmlspecialchars($texto_busq) ?>"
                                   class="form-control form-control-sm"
                                   placeholder="Lista">
                        </div>

                        <div class="col-12 col-md-12 d-flex justify-content-end" style="gap:.5rem;">
                            <button type="submit" class="btn btn-sm btn-primary">
                                Aplicar
                            </button>
                            <a href="lista_precios.php" class="btn btn-sm btn-outline-secondary">
                                Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Cards de resumen -->
        <div class="col-12 col-lg-4">
            <div class="row g-2">
                <div class="col-6 col-lg-12">
                    <div class="card" style="border-radius:.75rem;box-shadow:0 2px 4px rgba(0,0,0,.08);">
                        <div class="card-body py-2 px-3">
                            <div style="font-size:11px;text-transform:uppercase;color:#666;">Listas encontradas</div>
                            <div style="font-size:1.4rem;font-weight:bold;">
                                <?= number_format($total_listas) ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-12">
                    <div class="card" style="border-radius:.75rem;box-shadow:0 2px 4px rgba(0,0,0,.08);">
                        <div class="card-body py-2 px-3">
                            <div style="font-size:11px;text-transform:uppercase;color:#666;">Vigencia</div>
                            <div style="font-size:11px;">
                                <span class="badge bg-success me-1">Vigentes: <?= $total_vigentes ?></span>
                                <span class="badge bg-secondary me-1">Futuras: <?= $total_futuras ?></span>
                                <span class="badge bg-danger">Vencidas: <?= $total_vencidas ?></span>
                            </div>
                            <div style="font-size:10px;color:#999;" class="mt-1">
                                Hoy: <?= htmlspecialchars($hoy) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grilla + barra de acciones (Nuevo / Importar) -->
    <div class="card">
        <div class="card-header py-1" style="background:#f5f5f5;">
            <div class="d-flex justify-content-between align-items-center">
                <span style="font-size:11px;font-weight:bold;color:#555;">
                    Listas de precios — detalle
                </span>
                <div class="d-flex align-items-center" style="gap:.5rem;">
                    <a href="lista_precios_editar.php" class="btn btn-sm btn-primary">
                        + Nuevo
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            data-bs-toggle="modal" data-bs-target="#modalImportarLista">
                        Importar Lista de Precios
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-2">
            <div class="table-responsive" style="max-height:480px;overflow-y:auto;overflow-x:auto;">
                <table id="tabla_listap" class="table table-sm table-striped table-hover mb-0" style="font-size:10px;">
                    <thead class="table-light">
                    <tr>
                        <th style="width:90px;">Acciones</th>
                        <th>ID</th>
                        <th>Lista</th>
                        <th>Almacén</th>
                        <th>Moneda</th>
                        <th>Tipo</th>
                        <th>Tipo Serv.</th>
                        <th>Fecha Ini</th>
                        <th>Fecha Fin</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$listas): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted">
                                No se encontraron listas con los filtros aplicados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($listas as $lp): ?>
                            <?php
                            $status = $lp['status_vig'] ?? 'SIN FECHAS';
                            $badgeClass = 'bg-secondary';
                            if ($status === 'VIGENTE') $badgeClass = 'bg-success';
                            elseif ($status === 'VENCIDA') $badgeClass = 'bg-danger';
                            elseif ($status === 'FUTURA') $badgeClass = 'bg-warning text-dark';

                            // Texto de almacén: prioriza padre, luego hijo
                            if (!empty($lp['clave_almacenp']) || !empty($lp['nombre_almacenp'])) {
                                $txtAlm = trim(($lp['clave_almacenp'] ?? '') . ' - ' . ($lp['nombre_almacenp'] ?? ''));
                            } elseif (!empty($lp['clave_almacen']) || !empty($lp['des_almac'])) {
                                $txtAlm = trim(($lp['clave_almacen'] ?? '') . ' - ' . ($lp['des_almac'] ?? ''));
                            } else {
                                $txtAlm = (string)($lp['Cve_Almac'] ?? '');
                            }

                            $id = (int)$lp['id'];
                            ?>
                            <tr>
                                <td class="text-center">
                                    <a href="lista_precios_editar.php?id=<?= $id ?>" class="me-1" title="Editar">
                                        <i class="fa fa-pencil-alt"></i>
                                    </a>
                                    <!-- placeholders para más acciones futuras -->
                                    <a href="lista_precios_editar.php?id=<?= $id ?>&copiar=1" class="me-1" title="Duplicar">
                                        <i class="fa fa-copy"></i>
                                    </a>
                                    <!-- status visual -->
                                    <?php if ($status === 'VIGENTE'): ?>
                                        <span class="text-success" title="Vigente">
                                            <i class="fa fa-circle"></i>
                                        </span>
                                    <?php elseif ($status === 'VENCIDA'): ?>
                                        <span class="text-danger" title="Vencida">
                                            <i class="fa fa-circle"></i>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-secondary" title="<?= htmlspecialchars($status) ?>">
                                            <i class="fa fa-circle"></i>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?= $id ?></td>
                                <td><?= htmlspecialchars($lp['Lista'] ?? '') ?></td>
                                <td><?= htmlspecialchars($txtAlm) ?></td>
                                <td>
                                    <?= htmlspecialchars(($lp['Cve_Moneda'] ?? '') . ' ' . ($lp['Des_Moneda'] ?? '')) ?>
                                </td>
                                <td><?= htmlspecialchars((string)($lp['Tipo'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($lp['TipoServ'] ?? '') ?></td>
                                <td><?= htmlspecialchars($lp['FechaIni'] ?? '') ?></td>
                                <td><?= htmlspecialchars($lp['FechaFin'] ?? '') ?></td>
                                <td>
                                    <span class="badge <?= $badgeClass ?>" style="font-size:9px;">
                                        <?= htmlspecialchars($status) ?>
                                    </span>
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

<!-- Modal: Importar Lista -->
<div class="modal fade" id="modalImportarLista" tabindex="-1" aria-labelledby="modalImportarListaLabel" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-scrollable">
    <div class="modal-content">
      <form method="post" action="lista_precios_importar.php" enctype="multipart/form-data">
        <div class="modal-header py-2" style="background:#0F5AAD;color:#fff;">
          <h6 class="modal-title" id="modalImportarListaLabel" style="font-size:13px;">Importar lista de precios</h6>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" style="font-size:11px;">
          <div class="mb-2">
            <label class="form-label mb-0">Archivo Excel</label>
            <input type="file" name="archivo" class="form-control form-control-sm" accept=".xlsx,.xls,.csv" required>
          </div>
          <div class="mb-2">
            <label class="form-label mb-0">ID lista destino (opcional)</label>
            <input type="number" name="lista_id" class="form-control form-control-sm">
          </div>
          <div style="font-size:10px;color:#888;">
            Conecta este formulario a tu importador legacy.  
            Este archivo solo define el front.
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button type="submit" class="btn btn-sm btn-primary">Importar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
if (file_exists(__DIR__ . '/../bi/_menu_global_end.php')) {
    require_once __DIR__ . '/../bi/_menu_global_end.php';
}
?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.jQuery && $.fn.DataTable) {
        $('#tabla_listap').DataTable({
            pageLength: 25,
            lengthChange: false,
            searching: false,
            ordering: true,
            info: true,
            scrollX: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            }
        });
    }
});
</script>
