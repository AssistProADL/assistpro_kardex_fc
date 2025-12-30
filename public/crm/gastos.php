<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$db = db_pdo();
$mensaje_ok = '';
$mensaje_error = '';

// ============================
// Guardar gasto
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'guardar_gasto') {
    try {
        $db->beginTransaction();

        $id_opp      = (int)$_POST['id_opp'];
        $id_vendedor = (int)$_POST['id_vendedor'];
        $id_cliente  = $_POST['id_cliente'] !== '' ? (int)$_POST['id_cliente'] : null;
        $fecha       = $_POST['fecha_gasto'];
        $comentario  = $_POST['comentario'];
        $usuario     = $_SESSION['usuario'] ?? 'SISTEMA';

        dbq("
            INSERT INTO t_crm_gasto 
            (id_opp, id_vendedor, id_cliente, fecha_gasto, total, comentario, usuario_captura)
            VALUES (?,?,?,?,0,?,?)
        ", [$id_opp, $id_vendedor, $id_cliente, $fecha, $comentario, $usuario]);

        $id_gasto = $db->lastInsertId();
        $total = 0;

        foreach ($_POST['detalle'] as $d) {
            if ($d['importe'] <= 0) continue;

            dbq("
                INSERT INTO t_crm_gasto_det
                (id_gasto, id_tipo_gasto, descripcion, importe)
                VALUES (?,?,?,?)
            ", [$id_gasto, $d['tipo'], $d['desc'], $d['importe']]);

            $total += $d['importe'];
        }

        dbq("UPDATE t_crm_gasto SET total=? WHERE id_gasto=?", [$total, $id_gasto]);

        $db->commit();
        $mensaje_ok = "Gasto registrado correctamente.";
    } catch (Throwable $e) {
        $db->rollBack();
        $mensaje_error = $e->getMessage();
    }
}

// ============================
// Catálogos
// ============================
$opps = db_all("SELECT id_opp, titulo FROM t_crm_oportunidad ORDER BY id_opp DESC");
$vendedores = db_all("SELECT id_vendedor, nombre FROM t_vendedores WHERE activo=1");
$clientes = db_all("SELECT id_cliente, RazonSocial FROM c_cliente WHERE Activo=1");
$tipos = db_all("SELECT id_tipo_gasto, descripcion FROM c_gasto_tipo WHERE activo=1");

// ============================
// Gastos registrados
// ============================
$gastos = db_all("
    SELECT g.*, o.titulo, v.nombre AS vendedor, c.RazonSocial
    FROM t_crm_gasto g
    JOIN t_crm_oportunidad o ON o.id_opp = g.id_opp
    JOIN t_vendedores v ON v.id_vendedor = g.id_vendedor
    LEFT JOIN c_cliente c ON c.id_cliente = g.id_cliente
    ORDER BY g.fecha_crea DESC
    LIMIT 100
");
?>

<div class="container-fluid mt-3" style="font-size:0.82rem;">
<h4>CRM – Gastos Operativos</h4>

<?php if ($mensaje_ok): ?><div class="alert alert-success py-1"><?= $mensaje_ok ?></div><?php endif; ?>
<?php if ($mensaje_error): ?><div class="alert alert-danger py-1"><?= $mensaje_error ?></div><?php endif; ?>

<!-- ALTA DE GASTO -->
<div class="card mb-3">
<div class="card-header py-2">Nuevo gasto</div>
<div class="card-body">
<form method="post">
<input type="hidden" name="accion" value="guardar_gasto">

<div class="row g-2">
<div class="col-md-3">
<label>Oportunidad</label>
<select name="id_opp" class="form-select form-select-sm" required>
<?php foreach($opps as $o): ?>
<option value="<?= $o['id_opp'] ?>">#<?= $o['id_opp'] ?> - <?= $o['titulo'] ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-3">
<label>Vendedor</label>
<select name="id_vendedor" class="form-select form-select-sm" required>
<?php foreach($vendedores as $v): ?>
<option value="<?= $v['id_vendedor'] ?>"><?= $v['nombre'] ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-3">
<label>Cliente</label>
<select name="id_cliente" class="form-select form-select-sm">
<option value="">-- Opcional --</option>
<?php foreach($clientes as $c): ?>
<option value="<?= $c['id_cliente'] ?>"><?= $c['RazonSocial'] ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-3">
<label>Fecha</label>
<input type="date" name="fecha_gasto" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
</div>
</div>

<hr>

<?php for($i=0;$i<5;$i++): ?>
<div class="row g-2 mb-1">
<div class="col-md-3">
<select name="detalle[<?= $i ?>][tipo]" class="form-select form-select-sm">
<?php foreach($tipos as $t): ?>
<option value="<?= $t['id_tipo_gasto'] ?>"><?= $t['descripcion'] ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="col-md-5">
<input name="detalle[<?= $i ?>][desc]" class="form-control form-control-sm" placeholder="Descripción">
</div>
<div class="col-md-2">
<input name="detalle[<?= $i ?>][importe]" type="number" step="0.01" class="form-control form-control-sm" placeholder="Importe">
</div>
</div>
<?php endfor; ?>

<div class="mt-2">
<input name="comentario" class="form-control form-control-sm" placeholder="Comentario general">
</div>

<button class="btn btn-success btn-sm mt-2">Guardar gasto</button>
</form>
</div>
</div>

<!-- LISTADO -->
<div class="card">
<div class="card-header py-2">Gastos registrados</div>
<div class="card-body p-2">
<table class="table table-sm table-bordered table-striped">
<thead class="table-light">
<tr>
<th>ID</th><th>Fecha</th><th>Oportunidad</th><th>Vendedor</th><th>Total</th><th>Estatus</th>
</tr>
</thead>
<tbody>
<?php foreach($gastos as $g): ?>
<tr>
<td><?= $g['id_gasto'] ?></td>
<td><?= $g['fecha_gasto'] ?></td>
<td><?= $g['titulo'] ?></td>
<td><?= $g['vendedor'] ?></td>
<td>$<?= number_format($g['total'],2) ?></td>
<td><?= $g['estatus'] ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
