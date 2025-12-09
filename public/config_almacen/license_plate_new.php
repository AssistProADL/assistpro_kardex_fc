<?php
// public/config_almacen/license_plate_new.php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$errores   = [];
$exito     = '';
$generados = [];

/* ===========================================================
   Catálogo de almacenes (c_almacenp)
   =========================================================== */
$sql_alm = "
    SELECT 
        TRIM(id)     AS id_almacen,
        TRIM(clave)  As clave,
        TRIM(nombre) AS nombre
    FROM c_almacenp
    WHERE (Activo IS NULL OR TRIM(Activo) <> '0')
    ORDER BY TRIM(clave)
";
$almacenes = function_exists('db_all') ? db_all($sql_alm) : [];

/* ===========================================================
   Procesar envío
   =========================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $almacen_id = isset($_POST['almacen'])   ? trim($_POST['almacen']) : '';
    $cantidad   = isset($_POST['cantidad'])  ? (int)$_POST['cantidad'] : 0;
    $prefijo    = isset($_POST['prefijo'])   ? strtoupper(trim($_POST['prefijo'])) : 'LP';
    $tipo       = isset($_POST['tipo'])      ? trim($_POST['tipo']) : 'Pallet';
    $tipogen    = isset($_POST['tipogen'])   ? (int)$_POST['tipogen'] : 0; // 0 = No genérico, 1 = Genérico
    $permanente = isset($_POST['permanente']) ? 1 : 0;

    if ($almacen_id === '') {
        $errores[] = 'Debe seleccionar un almacén.';
    }
    if ($cantidad <= 0) {
        $errores[] = 'La cantidad debe ser mayor a cero.';
    }
    if ($prefijo === '') {
        $errores[] = 'El prefijo no puede estar vacío.';
    }

    if (!$errores) {

        // Código de fecha en formato aammdd (25-12-01 => 251201)
        $codigo_fecha = date('ymd');    // aammdd
        $base_lp      = $prefijo . $codigo_fecha . '-';  // Ej: LPAO251201-

        // Buscamos la secuencia máxima YA usada hoy para ese prefijo + almacén
        $sql_max = "
            SELECT MAX(COALESCE(sufijo,0)) AS max_sufijo
            FROM c_charolas
            WHERE cve_almac = :almacen
              AND CveLP LIKE :patron
        ";
        $params_max = [
            'almacen' => $almacen_id,
            'patron'  => $base_lp . '%',   // LPAO251201-%
        ];
        $row_max    = function_exists('db_one') ? db_one($sql_max, $params_max) : null;
        $sufijo_base = (int)($row_max['max_sufijo'] ?? 0);

        try {
            for ($i = 1; $i <= $cantidad; $i++) {

                // Siguiente secuencia para HOY (día+prefijo+almacén)
                $seq = $sufijo_base + $i;           // 1..N, o continúa donde se quedó

                // CveLP con formato PREFIJO + aammdd + '-' + secuencia
                $lp = $base_lp . $seq;              // p.ej. LPAO251201-1

                // Clave_Contenedor “virtual” inicial = CveLP
                $claveCont   = $lp;

                $descripcion = ($tipo === 'Contenedor')
                    ? 'Contenedor genérico'
                    : 'Pallet genérico';

                $params_ins = [
                    'cve_almac'   => $almacen_id,
                    'clave'       => $claveCont,
                    'descripcion' => $descripcion,
                    'permanente'  => $permanente,
                    'sufijo'      => $seq,     // guardamos sólo el número (1..n)
                    'tipo'        => $tipo,
                    'cvelp'       => $lp,
                    'tipogen'     => $tipogen,
                ];

                $sql_ins = "
                    INSERT INTO c_charolas
                        (cve_almac, Clave_Contenedor, descripcion,
                         Permanente, Pedido, sufijo, tipo,
                         Activo, alto, ancho, fondo, peso, pesomax, capavol, Costo,
                         CveLP, TipoGen)
                    VALUES
                        (:cve_almac, :clave, :descripcion,
                         :permanente, NULL, :sufijo, :tipo,
                         1, NULL, NULL, NULL, NULL, NULL, NULL, NULL,
                         :cvelp, :tipogen)
                ";

                if (function_exists('dbq')) {
                    dbq($sql_ins, $params_ins);
                } else {
                    $pdo  = db();
                    $stmt = $pdo->prepare($sql_ins);
                    $stmt->execute($params_ins);
                }

                $generados[] = $lp;
            }

            $exito = 'Se generaron ' . count($generados) . ' License Plates correctamente.';

        } catch (Exception $e) {
            $errores[] = 'Error al generar los License Plates: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>Nuevo License Plate</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body { font-size: 10px; }
        .card-header {
            background:#0F5AAD;
            color:#fff;
            font-weight:600;
        }
        .form-label { font-size: 10px; }
    </style>
</head>
<body>
<div class="container-fluid mt-3">

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Agregar License Plates</span>
            <a href="license_plate.php" class="btn btn-outline-light btn-sm">
                <i class="fa fa-arrow-left"></i> Regresar
            </a>
        </div>
        <div class="card-body">

            <?php if ($errores): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errores as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($exito): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($exito); ?>
                    <?php if ($generados): ?>
                        <br><small>
                            Primer LP: <strong><?php echo htmlspecialchars($generados[0]); ?></strong>
                            <?php if (count($generados) > 1): ?>
                                &nbsp;|&nbsp; Último LP:
                                <strong><?php echo htmlspecialchars(end($generados)); ?></strong>
                            <?php endif; ?>
                        </small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="post" class="row g-3">

                <!-- Almacén -->
                <div class="col-12 col-md-4">
                    <label class="form-label">Almacén *</label>
                    <select name="almacen" class="form-select form-select-sm" required>
                        <option value="">Seleccione almacén...</option>
                        <?php foreach ($almacenes as $a): ?>
                            <?php
                            $val   = $a['id_almacen'];
                            $nom   = $a['nombre'] ?: $a['clave'];
                            $label = $a['clave'] . ' - ' . $nom;
                            ?>
                            <option value="<?php echo htmlspecialchars($val); ?>"
                                <?php echo (isset($almacen_id) && $almacen_id == $val) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Cantidad -->
                <div class="col-6 col-md-2">
                    <label class="form-label">Cantidad *</label>
                    <input type="number" min="1" name="cantidad"
                           class="form-control form-control-sm"
                           value="<?php echo isset($cantidad) ? (int)$cantidad : 1; ?>"
                           required>
                </div>

                <!-- Prefijo -->
                <div class="col-6 col-md-2">
                    <label class="form-label">Prefijo *</label>
                    <input type="text" name="prefijo"
                           class="form-control form-control-sm"
                           maxlength="10"
                           value="<?php echo htmlspecialchars($prefijo ?? 'LP'); ?>"
                           required>
                    <small class="text-muted">
                        Ejemplo resultado hoy: PREF<?php echo date('ymd'); ?>-1, PREF<?php echo date('ymd'); ?>-2, ...
                    </small>
                </div>

                <!-- Tipo Pallet/Contenedor -->
                <div class="col-6 col-md-2">
                    <label class="form-label">Crear Pallet o Contenedor</label>
                    <select name="tipo" class="form-select form-select-sm">
                        <option value="Pallet" <?php echo (isset($tipo) && $tipo === 'Pallet') ? 'selected' : ''; ?>>Pallet</option>
                        <option value="Contenedor" <?php echo (isset($tipo) && $tipo === 'Contenedor') ? 'selected' : ''; ?>>Contenedor</option>
                    </select>
                </div>

                <!-- Tipo Genérico / No Genérico -->
                <div class="col-6 col-md-2">
                    <label class="form-label">Tipo (Genérico/No)</label>
                    <select name="tipogen" class="form-select form-select-sm">
                        <option value="0" <?php echo (isset($tipogen) && $tipogen == 0) ? 'selected' : ''; ?>>No genérico</option>
                        <option value="1" <?php echo (isset($tipogen) && $tipogen == 1) ? 'selected' : ''; ?>>Genérico</option>
                    </select>
                </div>

                <!-- Flags -->
                <div class="col-12 col-md-4">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="permanente"
                               id="chkPermanente" <?php echo !empty($permanente) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="chkPermanente">
                            License Plate permanente
                        </label>
                    </div>
                </div>

                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa fa-save"></i> Generar LPs
                    </button>
                    <a href="license_plate.php" class="btn btn-outline-secondary btn-sm">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
