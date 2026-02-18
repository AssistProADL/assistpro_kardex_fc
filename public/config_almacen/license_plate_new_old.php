<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$errores   = [];
$exito     = '';
$generados = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $empresa_id = trim($_POST['empresa'] ?? '');
    $almacen_id = trim($_POST['almacen'] ?? '');
    $cantidad   = (int)($_POST['cantidad'] ?? 0);
    $prefijo    = strtoupper(trim($_POST['prefijo'] ?? 'LP'));
    $tipo       = trim($_POST['tipo'] ?? 'Pallet');
    $tipogen    = (int)($_POST['tipogen'] ?? 0);
    $permanente = isset($_POST['permanente']) ? 1 : 0;

    if ($empresa_id === '') {
        $errores[] = "Debe seleccionar una empresa.";
    }

    if ($cantidad <= 0) {
        $errores[] = "Cantidad inválida.";
    }

    if ($prefijo === '') {
        $errores[] = "Prefijo requerido.";
    }

    if (!$errores) {

        $fecha = date('Ymd'); // AAAAMMDD
        $base  = $prefijo . $fecha . '-';

        // Secuencia GLOBAL por prefijo+fecha (NO por almacén)
        $sqlMax = "
            SELECT MAX(sufijo) max_sufijo
            FROM c_charolas
            WHERE CveLP LIKE :patron
        ";

        $row = db_one($sqlMax, ['patron' => $base . '%']);
        $ultimo = (int)($row['max_sufijo'] ?? 0);

        $pdo = db();
        $pdo->beginTransaction();

        try {

            for ($i = 1; $i <= $cantidad; $i++) {

                $seq = $ultimo + $i;
                $seq_formateado = str_pad($seq, 2, '0', STR_PAD_LEFT);

                $lp = $base . $seq_formateado;

                $sqlInsert = "
                    INSERT INTO c_charolas
                    (empresa_id, cve_almac, Clave_Contenedor, descripcion,
                     Permanente, Pedido, sufijo, tipo,
                     Activo, CveLP, TipoGen)
                    VALUES
                    (:empresa_id, :cve_almac, :clave, :descripcion,
                     :permanente, NULL, :sufijo, :tipo,
                     1, :cvelp, :tipogen)
                ";

                $pdo->prepare($sqlInsert)->execute([
                    'empresa_id' => $empresa_id,
                    'cve_almac'  => $almacen_id !== '' ? $almacen_id : null,
                    'clave'      => $lp,
                    'descripcion'=> $tipo . ' generado',
                    'permanente' => $permanente,
                    'sufijo'     => $seq,
                    'tipo'       => $tipo,
                    'cvelp'      => $lp,
                    'tipogen'    => $tipogen
                ]);

                $generados[] = $lp;
            }

            $pdo->commit();
            $exito = "Se generaron " . count($generados) . " License Plates correctamente.";

        } catch (Exception $e) {
            $pdo->rollBack();
            $errores[] = "Error: " . $e->getMessage();
        }
    }
}
?>

<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Nuevo License Plate</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-size:10px; }
.card-header { background:#0F5AAD;color:#fff;font-weight:600; }
</style>
</head>

<body>
<div class="container-fluid mt-3">

<div class="card shadow-sm">
<div class="card-header d-flex justify-content-between align-items-center">
<span>Agregar License Plates</span>
<a href="license_plate.php" class="btn btn-outline-light btn-sm">Regresar</a>
</div>

<div class="card-body">

<?php if ($errores): ?>
<div class="alert alert-danger"><?php echo implode("<br>", $errores); ?></div>
<?php endif; ?>

<?php if ($exito): ?>
<div class="alert alert-success">
<?php echo $exito; ?>
<br>
<small>
<?php echo $generados[0]; ?> 
<?php if(count($generados)>1): ?>
 | <?php echo end($generados); ?>
<?php endif; ?>
</small>
</div>
<?php endif; ?>

<form method="post" class="row g-3">

<div class="col-md-4">
<label class="form-label">Empresa *</label>
<select name="empresa" id="empresa" class="form-select form-select-sm" required>
<option value="">Seleccione empresa...</option>
</select>
</div>

<div class="col-md-4">
<label class="form-label">Almacén (Opcional)</label>
<select name="almacen" id="almacen" class="form-select form-select-sm">
<option value="">Todos / No específico</option>
</select>
</div>

<div class="col-md-2">
<label class="form-label">Cantidad *</label>
<input type="number" name="cantidad" class="form-control form-control-sm" value="1" required>
</div>

<div class="col-md-2">
<label class="form-label">Prefijo *</label>
<input type="text" name="prefijo" class="form-control form-control-sm" value="LP" required>
</div>

<div class="col-md-3">
<label class="form-label">Crear Pallet o Contenedor</label>
<select name="tipo" class="form-select form-select-sm">
<option value="Pallet">Pallet</option>
<option value="Contenedor">Contenedor</option>
</select>
</div>

<div class="col-md-3">
<label class="form-label">Tipo (Genérico/No)</label>
<select name="tipogen" class="form-select form-select-sm">
<option value="0">No genérico</option>
<option value="1">Genérico</option>
</select>
</div>

<div class="col-md-3 d-flex align-items-end">
<div class="form-check">
<input class="form-check-input" type="checkbox" name="permanente">
<label class="form-check-label">License Plate permanente</label>
</div>
</div>

<div class="col-12">
<button class="btn btn-primary btn-sm">Generar LPs</button>
</div>

</form>

</div>
</div>
</div>

<script>
fetch('../api/api_empresas_almacenes_rutas.php')
.then(res => res.json())
.then(data => {

    const empresaSelect = document.getElementById('empresa');
    const almacenSelect = document.getElementById('almacen');

    data.empresas.forEach(emp => {
        let opt = document.createElement('option');
        opt.value = emp.cve_cia;
        opt.textContent = emp.des_cia;
        empresaSelect.appendChild(opt);
    });

    empresaSelect.addEventListener('change', function() {

        almacenSelect.innerHTML = '<option value="">Todos / No específico</option>';

        data.almacenes.forEach(al => {
            if(al.cve_cia == this.value){
                let opt = document.createElement('option');
                opt.value = al.cve_almac;
                opt.textContent = al.nombre;
                almacenSelect.appendChild(opt);
            }
        });

    });

});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
