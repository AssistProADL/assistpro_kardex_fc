<?php
// public/bi/resumen_basico.php
if (session_status() === PHP_SESSION_NONE) //@session_start();

  require_once __DIR__ . '/../../app/db.php';

function h($s)
{
  return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
function q($k, $d = null)
{
  return isset($_GET[$k]) ? trim($_GET[$k]) : $d;
}
function safe_val($sql, $p = [])
{
  try {
    return (int) db_val($sql, $p);
  } catch (Throwable $e) {
    return 0;
  }
}
function safe_all($sql, $p = [])
{
  try {
    return db_all($sql, $p) ?: [];
  } catch (Throwable $e) {
    return [];
  }
}

/* ===== Helpers de presentación ===== */
function is_true_like($v)
{
  $v = strtoupper(trim((string) $v));
  return in_array($v, ['1', 'S', 'Y', 'SI', 'TRUE', 'T', 'YES']);
}
function render_cell($col, $val)
{
  $lc = mb_strtolower($col, 'UTF-8');
  if ($lc === 'activo') {
    $on = is_true_like($val);
    return '<span class="badge ' . ($on ? 'bg-success' : 'bg-secondary') . '">' . ($on ? 'Activo' : 'Inactivo') . '</span>';
  }
  if ($lc === 'es_cliente') {
    $on = is_true_like($val);
    return '<span class="badge ' . ($on ? 'bg-primary' : 'bg-secondary') . '">' . ($on ? 'Cliente' : 'No cliente') . '</span>';
  }
  return h($val ?? '');
}

/* ====== Catálogos: empresa y perfiles ====== */
$companias = [];
foreach (safe_all("SELECT empresa_id, des_cia FROM c_compania") as $r) {
  $companias[(string) $r['empresa_id']] = $r['des_cia'];
}
$empresaNombre = fn($id) => $companias[(string) $id] ?? $id;

$perfiles = [];
foreach (safe_all("SELECT ID_PERFIL, PER_NOMBRE FROM t_perfilesusuarios") as $r) {
  $perfiles[(string) $r['ID_PERFIL']] = $r['PER_NOMBRE'];
}
$perfilNombre = fn($id) => $perfiles[(string) $id] ?? $id;

/* ====== Totales ====== */
$totalArticulos = safe_val("SELECT COUNT(*) FROM c_articulo");
$totalAlmacenes = safe_val("SELECT COUNT(*) FROM c_almacenp");
$totalUsuarios = safe_val("SELECT COUNT(*) FROM c_usuario");
$totalClientes = safe_val("SELECT COUNT(*) FROM c_proveedores WHERE COALESCE(es_cliente,'') IN ('1','S','s')");

/* ====== Parámetros ====== */
$dataset = q('dataset', 'almacenes');
$pg = max(1, (int) q('page', 1));
$limit = 50;
$offset = ($pg - 1) * $limit;

$title = 'Análisis inicial';
$cols = [];
$rows = [];
$countSql = null;

/* ====== Dataset ====== */
switch ($dataset) {
  case 'articulos':
    $countSql = "SELECT COUNT(*) FROM c_articulo";
    $cols = ['Empresa', 'cve_articulo', 'des_articulo', 'cve_umed', 'cve_almac', 'Activo'];
    try {
      $rows = db_all("SELECT empresa_id, cve_articulo, des_articulo, cve_umed, cve_almac, Activo
                      FROM c_articulo ORDER BY des_articulo LIMIT $limit OFFSET $offset");
      foreach ($rows as &$r) {
        $r = array_merge(['Empresa' => $empresaNombre($r['empresa_id'])], $r);
        unset($r['empresa_id']);
      }
    } catch (Throwable $e) {
      // Fallback sin multiempresa
      $cols = ['cve_articulo', 'des_articulo', 'cve_umed', 'cve_almac', 'Activo'];
      $rows = safe_all("SELECT cve_articulo, des_articulo, cve_umed, cve_almac, Activo
                        FROM c_articulo ORDER BY des_articulo LIMIT $limit OFFSET $offset");
    }
    break;

  case 'usuarios':
    $countSql = "SELECT COUNT(*) FROM c_usuario";
    $cols = ['Empresa', 'cve_usuario', 'nombre_completo', 'email', 'Rol', 'Activo'];
    try {
      $rows = db_all("SELECT empresa_id, cve_usuario, nombre_completo, email, perfil, Activo
                      FROM c_usuario ORDER BY nombre_completo LIMIT $limit OFFSET $offset");
      foreach ($rows as &$r) {
        $r = [
          'Empresa' => $empresaNombre($r['empresa_id']),
          'cve_usuario' => $r['cve_usuario'],
          'nombre_completo' => $r['nombre_completo'],
          'email' => $r['email'],
          'Rol' => $perfilNombre($r['perfil']),
          'Activo' => $r['Activo']
        ];
      }
    } catch (Throwable $e) {
      // Fallback sin multiempresa
      $cols = ['cve_usuario', 'nombre_completo', 'email', 'Rol', 'Activo'];
      $rows = safe_all("SELECT cve_usuario, nombre_completo, email, perfil, Activo
                        FROM c_usuario ORDER BY nombre_completo LIMIT $limit OFFSET $offset");
      foreach ($rows as &$r) {
        $r = [
          'cve_usuario' => $r['cve_usuario'],
          'nombre_completo' => $r['nombre_completo'],
          'email' => $r['email'],
          'Rol' => $perfilNombre($r['perfil']),
          'Activo' => $r['Activo']
        ];
      }
    }
    break;

  case 'clientes':
    $countSql = "SELECT COUNT(*) FROM c_proveedores WHERE COALESCE(es_cliente,'') IN ('1','S','s')";
    $cols = ['Empresa', 'ID_Proveedor', 'Nombre', 'RUT', 'direccion', 'telefono1', 'ciudad', 'estado', 'pais', 'es_cliente'];
    try {
      $rows = db_all("SELECT empresa_id,ID_Proveedor,Nombre,RUT,direccion,telefono1,ciudad,estado,pais,es_cliente
                      FROM c_proveedores
                      WHERE COALESCE(es_cliente,'') IN ('1','S','s')
                      ORDER BY Nombre LIMIT $limit OFFSET $offset");
      foreach ($rows as &$r) {
        $r = array_merge(['Empresa' => $empresaNombre($r['empresa_id'])], $r);
        unset($r['empresa_id']);
      }
    } catch (Throwable $e) {
      // Fallback sin multiempresa
      $cols = ['ID_Proveedor', 'Nombre', 'RUT', 'direccion', 'telefono1', 'ciudad', 'estado', 'pais', 'es_cliente'];
      $rows = safe_all("SELECT ID_Proveedor,Nombre,RUT,direccion,telefono1,ciudad,estado,pais,es_cliente
                        FROM c_proveedores
                        WHERE COALESCE(es_cliente,'') IN ('1','S','s')
                        ORDER BY Nombre LIMIT $limit OFFSET $offset");
    }
    break;

  case 'almacenes':
  default:
    $dataset = 'almacenes';
    $countSql = "SELECT COUNT(*) FROM c_almacenp";

    // Detectar columnas reales de c_almacenp
    $actualCols = [];
    try {
      $colDefs = db_all("SHOW COLUMNS FROM c_almacenp");
      foreach ($colDefs as $cdef) {
        $actualCols[] = $cdef['Field'];
      }
    } catch (Throwable $e) {
      $actualCols = [];
    }

    // Conjunto "ideal" (sin empresa_id)
    $desiredNoEmpresa = [
      'id',
      'clave',
      'nombre',
      'des_almac',
      'zona',
      'rut',
      'codigopostal',
      'direccion',
      'telefono',
      'contacto',
      'correo',
      'Activo'
    ];

    // Columnas que sí existen
    $present = array_values(array_intersect($desiredNoEmpresa, $actualCols));

    // Si la tabla no tiene nada de lo esperado, al menos usamos la primera columna que exista
    if (!$present && $actualCols) {
      $present = [$actualCols[0]];
    }

    $tieneEmpresa = in_array('empresa_id', $actualCols);

    if ($tieneEmpresa) {
      // Multiempresa
      $selectList = 'empresa_id,' . implode(',', $present);
      $cols = array_merge(['Empresa'], $present);

      try {
        $orderCol = in_array('des_almac', $present) ? 'des_almac' :
          (in_array('nombre', $present) ? 'nombre' : $present[0]);
        $rows = db_all("SELECT $selectList FROM c_almacenp
                        ORDER BY $orderCol
                        LIMIT $limit OFFSET $offset");
        foreach ($rows as &$r) {
          $empresaId = $r['empresa_id'] ?? null;
          unset($r['empresa_id']);
          $r = array_merge(['Empresa' => $empresaNombre($empresaId)], $r);
        }
      } catch (Throwable $e) {
        $rows = [];
      }
    } else {
      // Sin empresa_id
      $selectList = implode(',', $present);
      $cols = $present;

      $orderCol = in_array('des_almac', $present) ? 'des_almac' :
        (in_array('nombre', $present) ? 'nombre' : $present[0]);

      $rows = safe_all("SELECT $selectList FROM c_almacenp
                        ORDER BY $orderCol
                        LIMIT $limit OFFSET $offset");
    }

    break;
}

$totalRows = $countSql ? safe_val($countSql) : count($rows);
$totalPages = max(1, ceil($totalRows / $limit));
$now = date('Y-m-d H:i:s');

function url_with($changes)
{
  $q = $_GET;
  foreach ($changes as $k => $v) {
    $q[$k] = $v;
  }
  return '?' . http_build_query($q);
}
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <title><?= h($title) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root {
      --ap-blue: #0a2a6b;
      --ap-purple: #6f42c1;
      --ap-green: #198754;
      --ap-teal: #0aa2a2;
    }

    .kpi-card {
      border-radius: 12px;
      padding: 10px 14px;
      color: #fff;
      text-align: center;
      box-shadow: 0 6px 16px rgba(0, 0, 0, .12);
    }

    .kpi-value {
      font-size: 1.3rem;
      font-weight: 800;
    }

    .kpi-label {
      font-size: .8rem;
    }

    .kpi-art {
      background: var(--ap-purple);
    }

    .kpi-alm {
      background: var(--ap-blue);
    }

    .kpi-usr {
      background: var(--ap-green);
    }

    .kpi-cli {
      background: var(--ap-teal);
    }

    .grid-wrap {
      font-size: 10px;
      margin-top: 10px;
    }

    .scroll-box {
      overflow: auto;
      max-height: 60vh;
      border: 1px solid #e9ecef;
      border-radius: 8px;
    }

    thead th {
      position: sticky;
      top: 0;
      background: #f8f9fa;
    }

    .badge {
      font-size: .72rem;
    }
  </style>
</head>

<body>
  <?php include __DIR__ . '/../bi/_menu_global.php'; ?>

  <div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5 class="m-0">Análisis inicial</h5>
      <div class="btn-group btn-group-sm">
        <a class="btn btn-outline-secondary<?= $dataset === 'almacenes' ? ' active' : '' ?>"
          href="<?= url_with(['dataset' => 'almacenes', 'page' => 1]) ?>">Almacenes</a>
        <a class="btn btn-outline-secondary<?= $dataset === 'articulos' ? ' active' : '' ?>"
          href="<?= url_with(['dataset' => 'articulos', 'page' => 1]) ?>">Artículos</a>
        <a class="btn btn-outline-secondary<?= $dataset === 'usuarios' ? ' active' : '' ?>"
          href="<?= url_with(['dataset' => 'usuarios', 'page' => 1]) ?>">Usuarios</a>
        <a class="btn btn-outline-secondary<?= $dataset === 'clientes' ? ' active' : '' ?>"
          href="<?= url_with(['dataset' => 'clientes', 'page' => 1]) ?>">Clientes</a>
      </div>
    </div>

    <div class="row g-2 mb-2 text-center">
      <div class="col-6 col-md-3">
        <a href="<?= url_with(['dataset' => 'articulos', 'page' => 1]) ?>" class="text-decoration-none">
          <div class="kpi-card kpi-art">
            <div class="kpi-value"><?= number_format($totalArticulos) ?></div>
            <div class="kpi-label">Artículos</div>
          </div>
        </a>
      </div>
      <div class="col-6 col-md-3">
        <a href="<?= url_with(['dataset' => 'almacenes', 'page' => 1]) ?>" class="text-decoration-none">
          <div class="kpi-card kpi-alm">
            <div class="kpi-value"><?= number_format($totalAlmacenes) ?></div>
            <div class="kpi-label">Almacenes</div>
          </div>
        </a>
      </div>
      <div class="col-6 col-md-3">
        <a href="<?= url_with(['dataset' => 'usuarios', 'page' => 1]) ?>" class="text-decoration-none">
          <div class="kpi-card kpi-usr">
            <div class="kpi-value"><?= number_format($totalUsuarios) ?></div>
            <div class="kpi-label">Usuarios</div>
          </div>
        </a>
      </div>
      <div class="col-6 col-md-3">
        <a href="<?= url_with(['dataset' => 'clientes', 'page' => 1]) ?>" class="text-decoration-none">
          <div class="kpi-card kpi-cli">
            <div class="kpi-value"><?= number_format($totalClientes) ?></div>
            <div class="kpi-label">Clientes</div>
          </div>
        </a>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-1">
      <div class="small text-muted">
        Dataset: <strong><?= h(ucfirst($dataset)) ?></strong> ·
        Registros: <strong><?= number_format($totalRows) ?></strong>
      </div>
      <div>
        <button class="btn btn-sm btn-outline-success" onclick="exportTableToExcel('tblData')">
          <i class="bi bi-file-earmark-excel"></i> Exportar
        </button>
      </div>
    </div>

    <div class="grid-wrap">
      <div class="scroll-box">
        <table id="tblData" class="table table-striped table-hover table-sm mb-0">
          <thead>
            <tr>
              <?php foreach ($cols as $c): ?>
                <th><?= h($c) ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <?php foreach ($cols as $c): ?>
                  <td><?= render_cell($c, $r[$c] ?? '') ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr>
                <td colspan="<?= count($cols) ?>" class="text-center text-muted">
                  Sin registros
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-between align-items-center mt-2">
        <div class="small text-muted">
          Actualizado: <?= h($now) ?>
        </div>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <?php
            $prev = max(1, $pg - 1);
            $next = min($totalPages, $pg + 1);
            ?>
            <li class="page-item<?= $pg <= 1 ? ' disabled' : '' ?>">
              <a class="page-link" href="<?= url_with(['page' => $prev]) ?>">&laquo;</a>
            </li>
            <li class="page-item active">
              <span class="page-link"><?= $pg ?> / <?= $totalPages ?></span>
            </li>
            <li class="page-item<?= $pg >= $totalPages ? ' disabled' : '' ?>">
              <a class="page-link" href="<?= url_with(['page' => $next]) ?>">&raquo;</a>
            </li>
          </ul>
        </nav>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
  <script>
    function exportTableToExcel(id) {
      const t = document.getElementById(id);
      if (!t) return;
      const html = t.outerHTML.replace(/ /g, '%20');
      const a = document.createElement('a');
      a.href = 'data:application/vnd.ms-excel,' + html;
      a.download = 'resumen_<?= $dataset ?>_' + new Date().toISOString().slice(0, 19).replace(/[:T]/g, '_') + '.xls';
      a.click();
    }
  </script>
</body>

</html>