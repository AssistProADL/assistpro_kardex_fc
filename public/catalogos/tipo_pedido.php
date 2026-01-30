<?php
// /public/catalogos/tipo_pedido.php
// Catálogo de Tipos de Pedido (sin API) - PDO db_pdo()

declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/db.php'; // correcto: app/db.php al nivel de public
$pdo = db_pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ===== Helpers =====
function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'; }
function post($k, $default=null) { return $_POST[$k] ?? $default; }
function get($k, $default=null) { return $_GET[$k] ?? $default; }

$flash_ok = '';
$flash_err = '';

// ===== Actions =====
try {
    // EXPORT CSV
    if (get('action') === 'export') {
        $stmt = $pdo->query("SELECT tipo_pedido, descripcion, grupo, subgrupo, activo FROM c_tipo_pedido ORDER BY grupo, subgrupo, tipo_pedido");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=cat_tipos_pedido.csv');

        $out = fopen('php://output', 'w');
        // BOM UTF-8 para Excel
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['tipo_pedido','descripcion','grupo','subgrupo','activo']);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['tipo_pedido'],
                $r['descripcion'],
                $r['grupo'],
                $r['subgrupo'],
                (int)$r['activo']
            ]);
        }
        fclose($out);
        exit;
    }

    // POST actions
    if (is_post()) {
        $action = (string)post('action', '');

        // CREATE / UPDATE
        if ($action === 'save') {
            $id = trim((string)post('id_tipo_pedido', ''));
            $tipo_pedido = strtoupper(trim((string)post('tipo_pedido', '')));
            $descripcion = trim((string)post('descripcion', ''));
            $grupo = strtoupper(trim((string)post('grupo', 'INTERNO')));
            $subgrupo = trim((string)post('subgrupo', ''));
            $activo = (int)post('activo', 1);

            if ($tipo_pedido === '' || $descripcion === '') {
                throw new RuntimeException("Tipo y Descripción son obligatorios.");
            }
            if (!in_array($grupo, ['INTERNO','EXTERNO'], true)) {
                throw new RuntimeException("Grupo inválido (INTERNO/EXTERNO).");
            }

            if ($id === '') {
                // INSERT
                $sql = "INSERT INTO c_tipo_pedido (tipo_pedido, descripcion, grupo, subgrupo, activo)
                        VALUES (:tipo_pedido, :descripcion, :grupo, :subgrupo, :activo)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':tipo_pedido' => $tipo_pedido,
                    ':descripcion' => $descripcion,
                    ':grupo'       => $grupo,
                    ':subgrupo'    => $subgrupo,
                    ':activo'      => $activo
                ]);
                $flash_ok = "Tipo de pedido creado.";
            } else {
                // UPDATE
                $sql = "UPDATE c_tipo_pedido
                        SET tipo_pedido = :tipo_pedido,
                            descripcion = :descripcion,
                            grupo       = :grupo,
                            subgrupo    = :subgrupo,
                            activo      = :activo
                        WHERE id_tipo_pedido = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':tipo_pedido' => $tipo_pedido,
                    ':descripcion' => $descripcion,
                    ':grupo'       => $grupo,
                    ':subgrupo'    => $subgrupo,
                    ':activo'      => $activo,
                    ':id'          => (int)$id
                ]);
                $flash_ok = "Tipo de pedido actualizado.";
            }
        }

        // TOGGLE ACTIVO
        if ($action === 'toggle') {
            $id = (int)post('id_tipo_pedido', 0);
            if ($id <= 0) throw new RuntimeException("ID inválido.");
            $pdo->prepare("UPDATE c_tipo_pedido SET activo = IF(activo=1,0,1) WHERE id_tipo_pedido = ?")
                ->execute([$id]);
            $flash_ok = "Estatus actualizado.";
        }

        // DELETE
        if ($action === 'delete') {
            $id = (int)post('id_tipo_pedido', 0);
            if ($id <= 0) throw new RuntimeException("ID inválido.");
            $pdo->prepare("DELETE FROM c_tipo_pedido WHERE id_tipo_pedido = ?")->execute([$id]);
            $flash_ok = "Registro eliminado.";
        }

        // IMPORT CSV (UPSERT por tipo_pedido)
        if ($action === 'import') {
            if (!isset($_FILES['csv']) || ($_FILES['csv']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new RuntimeException("No se recibió archivo CSV.");
            }

            $tmp = $_FILES['csv']['tmp_name'];
            $fh = fopen($tmp, 'r');
            if (!$fh) throw new RuntimeException("No se pudo abrir el CSV.");

            // Leer encabezado
            $header = fgetcsv($fh);
            if (!$header) throw new RuntimeException("CSV vacío.");

            // Normalizar header
            $header = array_map(fn($x) => strtolower(trim((string)$x)), $header);
            $need = ['tipo_pedido','descripcion','grupo','subgrupo','activo'];
            foreach ($need as $n) {
                if (!in_array($n, $header, true)) {
                    throw new RuntimeException("CSV inválido. Debe incluir columnas: " . implode(', ', $need));
                }
            }
            $idx = array_flip($header);

            $pdo->beginTransaction();
            $ins = $pdo->prepare("INSERT INTO c_tipo_pedido (tipo_pedido, descripcion, grupo, subgrupo, activo)
                                  VALUES (:tipo, :desc, :grupo, :sub, :act)
                                  ON DUPLICATE KEY UPDATE
                                    descripcion = VALUES(descripcion),
                                    grupo = VALUES(grupo),
                                    subgrupo = VALUES(subgrupo),
                                    activo = VALUES(activo)");

            $count = 0;
            while (($row = fgetcsv($fh)) !== false) {
                $tipo = strtoupper(trim((string)($row[$idx['tipo_pedido']] ?? '')));
                $desc = trim((string)($row[$idx['descripcion']] ?? ''));
                $grp  = strtoupper(trim((string)($row[$idx['grupo']] ?? 'INTERNO')));
                $sub  = trim((string)($row[$idx['subgrupo']] ?? ''));
                $act  = (int)($row[$idx['activo']] ?? 1);

                if ($tipo === '' || $desc === '') continue;
                if (!in_array($grp, ['INTERNO','EXTERNO'], true)) $grp = 'INTERNO';
                $act = ($act === 0) ? 0 : 1;

                $ins->execute([
                    ':tipo'  => $tipo,
                    ':desc'  => $desc,
                    ':grupo' => $grp,
                    ':sub'   => $sub,
                    ':act'   => $act
                ]);
                $count++;
            }
            fclose($fh);
            $pdo->commit();
            $flash_ok = "Importación OK. Filas procesadas: {$count}.";
        }
    }
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    $flash_err = $e->getMessage();
}

// ===== Data =====
$only_inactive = (int)get('inactive', 0) === 1;
$search = trim((string)get('q', ''));

$where = [];
$params = [];

if ($only_inactive) $where[] = "activo = 0";
if ($search !== '') {
    $where[] = "(tipo_pedido LIKE :q OR descripcion LIKE :q OR grupo LIKE :q OR subgrupo LIKE :q)";
    $params[':q'] = "%{$search}%";
}

$sql = "SELECT id_tipo_pedido, tipo_pedido, descripcion, grupo, subgrupo, activo, created_at, updated_at
        FROM c_tipo_pedido" . (count($where) ? " WHERE " . implode(" AND ", $where) : "") . "
        ORDER BY grupo, subgrupo, tipo_pedido";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// KPIs
$k = $pdo->query("SELECT
        COUNT(*) total,
        SUM(activo=1) activos,
        SUM(activo=0) inactivos
    FROM c_tipo_pedido")->fetch(PDO::FETCH_ASSOC);

$total = (int)($k['total'] ?? 0);
$activos = (int)($k['activos'] ?? 0);
$inactivos = (int)($k['inactivos'] ?? 0);

// ===== UI =====
include __DIR__ . '/../bi/_menu_global.php';
?>
<style>
    .kpi-card{border-radius:14px; box-shadow:0 8px 22px rgba(0,0,0,.06); border:1px solid #eef1f5;}
    .kpi-title{font-size:12px; color:#6c757d; letter-spacing:.3px;}
    .kpi-value{font-size:22px; font-weight:800;}
    .chip{display:inline-block; padding:3px 10px; border-radius:999px; font-size:12px; border:1px solid #e9ecef;}
    .chip-ok{background:#e9fbf0; border-color:#c7f1d6; color:#157347;}
    .chip-off{background:#fff2e6; border-color:#ffe0bf; color:#b45309;}
    .table thead th{font-size:12px; color:#6c757d; text-transform:uppercase; letter-spacing:.4px;}
    .btn-soft{border:1px solid #e9ecef; background:#fff;}
</style>

<div class="container-fluid py-3">
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
        <div>
            <h3 class="mb-1">📦 Catálogo de Tipos de Pedido</h3>
            <div class="text-muted small">Gobierno operativo: clave controlada, grupos/subgrupos, activación por estatus. Sin texto libre.</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-soft" href="?"><i class="fa fa-rotate"></i> Refrescar</a>
            <button class="btn btn-primary" onclick="openNew()"><i class="fa fa-plus"></i> Nuevo</button>
        </div>
    </div>

    <div class="row g-3 mt-2">
        <div class="col-md-4">
            <div class="p-3 kpi-card bg-white">
                <div class="kpi-title">TOTAL</div>
                <div class="d-flex align-items-end justify-content-between">
                    <div class="kpi-value"><?= h($total) ?></div>
                    <span class="chip">Registros</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 kpi-card bg-white">
                <div class="kpi-title">ACTIVOS</div>
                <div class="d-flex align-items-end justify-content-between">
                    <div class="kpi-value"><?= h($activos) ?></div>
                    <span class="chip chip-ok">Operando</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 kpi-card bg-white">
                <div class="kpi-title">INACTIVOS</div>
                <div class="d-flex align-items-end justify-content-between">
                    <div class="kpi-value"><?= h($inactivos) ?></div>
                    <span class="chip chip-off">Depurados</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card kpi-card mt-3">
        <div class="card-body">
            <form class="row g-2 align-items-center" method="get">
                <div class="col-auto">
                    <a class="btn btn-soft" href="?inactive=1"><i class="fa fa-eye"></i> Ver inactivos</a>
                </div>
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-magnifying-glass"></i></span>
                        <input class="form-control" name="q" value="<?= h($search) ?>" placeholder="Buscar por clave, descripción, grupo, subgrupo...">
                        <?php if ($only_inactive): ?>
                            <input type="hidden" name="inactive" value="1">
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-auto ms-auto d-flex gap-2">
                    <a class="btn btn-soft" href="?action=export"><i class="fa fa-file-export"></i> Exportar CSV</a>
                    <button class="btn btn-success" type="button" onclick="openImport()"><i class="fa fa-file-import"></i> Importar CSV</button>
                </div>
            </form>

            <?php if ($flash_ok): ?>
                <div class="alert alert-success mt-3 mb-0"><?= h($flash_ok) ?></div>
            <?php endif; ?>
            <?php if ($flash_err): ?>
                <div class="alert alert-danger mt-3 mb-0"><?= h($flash_err) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card kpi-card mt-3">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:120px;">Acciones</th>
                            <th>Clave</th>
                            <th>Descripción</th>
                            <th>Grupo</th>
                            <th>Subgrupo</th>
                            <th style="width:110px;">Activo</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$rows): ?>
                        <tr><td colspan="6" class="text-muted py-4">Sin registros.</td></tr>
                    <?php else: foreach ($rows as $r): ?>
                        <tr>
                            <td class="text-nowrap">
                                <button class="btn btn-sm btn-soft" title="Editar"
                                    onclick='openEdit(<?= (int)$r["id_tipo_pedido"] ?>, <?= json_encode($r["tipo_pedido"]) ?>, <?= json_encode($r["descripcion"]) ?>, <?= json_encode($r["grupo"]) ?>, <?= json_encode($r["subgrupo"]) ?>, <?= (int)$r["activo"] ?>)'>
                                    <i class="fa fa-pen"></i>
                                </button>

                                <form method="post" class="d-inline">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id_tipo_pedido" value="<?= (int)$r['id_tipo_pedido'] ?>">
                                    <button class="btn btn-sm btn-soft" title="Activar/Desactivar"><i class="fa fa-power-off"></i></button>
                                </form>

                                <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar este tipo de pedido?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id_tipo_pedido" value="<?= (int)$r['id_tipo_pedido'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="fa fa-trash"></i></button>
                                </form>
                            </td>
                            <td class="fw-bold"><?= h($r['tipo_pedido']) ?></td>
                            <td><?= h($r['descripcion']) ?></td>
                            <td><span class="chip"><?= h($r['grupo']) ?></span></td>
                            <td><?= h($r['subgrupo']) ?></td>
                            <td>
                                <?php if ((int)$r['activo'] === 1): ?>
                                    <span class="chip chip-ok">SI</span>
                                <?php else: ?>
                                    <span class="chip chip-off">NO</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL SAVE -->
<div class="modal fade" id="mdlSave" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px;">
      <div class="modal-header">
        <h5 class="modal-title" id="mdlTitle">Tipo de Pedido</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id_tipo_pedido" id="f_id_tipo_pedido" value="">
        <div class="modal-body">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Clave</label>
                    <input class="form-control" name="tipo_pedido" id="f_tipo_pedido" maxlength="5" required>
                    <div class="form-text">Ej: T, P, R, RI, RT</div>
                </div>
                <div class="col-md-9">
                    <label class="form-label">Descripción</label>
                    <input class="form-control" name="descripcion" id="f_descripcion" maxlength="150" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Grupo</label>
                    <select class="form-select" name="grupo" id="f_grupo" required>
                        <option value="INTERNO">INTERNO</option>
                        <option value="EXTERNO">EXTERNO</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Subgrupo</label>
                    <input class="form-control" name="subgrupo" id="f_subgrupo" maxlength="40">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Activo</label>
                    <select class="form-select" name="activo" id="f_activo">
                        <option value="1">SI</option>
                        <option value="0">NO</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-soft" type="button" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL IMPORT -->
<div class="modal fade" id="mdlImport" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px;">
      <div class="modal-header">
        <h5 class="modal-title">Importar CSV</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="import">
        <div class="modal-body">
            <div class="alert alert-info">
                Layout CSV (encabezados): <b>tipo_pedido, descripcion, grupo, subgrupo, activo</b><br>
                Upsert por <b>tipo_pedido</b>. Grupo válido: INTERNO/EXTERNO. Activo: 1/0.
            </div>
            <input class="form-control" type="file" name="csv" accept=".csv,text/csv" required>
        </div>
        <div class="modal-footer">
          <button class="btn btn-soft" type="button" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-success" type="submit"><i class="fa fa-upload"></i> Importar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openNew(){
    document.getElementById('mdlTitle').innerText = 'Nuevo Tipo de Pedido';
    document.getElementById('f_id_tipo_pedido').value = '';
    document.getElementById('f_tipo_pedido').value = '';
    document.getElementById('f_descripcion').value = '';
    document.getElementById('f_grupo').value = 'INTERNO';
    document.getElementById('f_subgrupo').value = '';
    document.getElementById('f_activo').value = '1';
    new bootstrap.Modal(document.getElementById('mdlSave')).show();
}
function openEdit(id, tipo, desc, grupo, subgrupo, activo){
    document.getElementById('mdlTitle').innerText = 'Editar Tipo de Pedido';
    document.getElementById('f_id_tipo_pedido').value = id;
    document.getElementById('f_tipo_pedido').value = (tipo || '').toString().toUpperCase();
    document.getElementById('f_descripcion').value = (desc || '').toString();
    document.getElementById('f_grupo').value = (grupo || 'INTERNO').toString().toUpperCase();
    document.getElementById('f_subgrupo').value = (subgrupo || '').toString();
    document.getElementById('f_activo').value = (activo==1 ? '1' : '0');
    new bootstrap.Modal(document.getElementById('mdlSave')).show();
}
function openImport(){
    new bootstrap.Modal(document.getElementById('mdlImport')).show();
}
</script>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
