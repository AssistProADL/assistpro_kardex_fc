<?php
// public/config_almacen/license_plate_new.php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$errores   = [];
$exito     = '';
$generados = [];

/* =========================
   Defaults para mantener estado UI
========================= */
$empresa_id  = 0;
$almacen_id  = null; // idp (c_almacenp.id) o NULL
$cantidad    = 1;
$prefijo     = 'LP';
$tipo        = 'Pallet';
$tipogen     = 0;
$permanente  = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $empresa_id = isset($_POST['empresa']) ? (int)$_POST['empresa'] : 0;

    // Almacén opcional: guardamos idp (numérico) o NULL
    $almacen_id = (isset($_POST['almacen']) && $_POST['almacen'] !== '')
        ? (int)$_POST['almacen']
        : null;

    $cantidad   = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 0;
    $prefijo    = isset($_POST['prefijo']) ? strtoupper(trim($_POST['prefijo'])) : 'LP';
    $tipo       = $_POST['tipo'] ?? 'Pallet';
    $tipogen    = isset($_POST['tipogen']) ? (int)$_POST['tipogen'] : 0;
    $permanente = isset($_POST['permanente']) ? 1 : 0;

    if (!$empresa_id)     $errores[] = "Debe seleccionar empresa.";
    if ($cantidad <= 0)   $errores[] = "Cantidad inválida.";
    if ($prefijo === '')  $errores[] = "Prefijo requerido.";

    if (!$errores) {

        // Formato requerido: PREFIJO + AAAAMMDD + '-' + 01..N
        $fecha = date('Ymd'); // AAAAMMDD
        $base  = $prefijo . $fecha . '-';

        // Último sufijo por empresa + día + prefijo (NO depende de almacén)
        $sql_max = "
            SELECT MAX(COALESCE(sufijo,0)) AS max_sufijo
            FROM c_charolas
            WHERE empresa_id = :empresa_id
              AND CveLP LIKE :patron
        ";

        $row = db_one($sql_max, [
            'empresa_id' => $empresa_id,
            'patron'     => $base . '%'
        ]);

        $inicio = (int)($row['max_sufijo'] ?? 0);

        // Pad dinámico: mínimo 2, y si crece a 3/4 dígitos, lo respeta
        $maxSeq = $inicio + max(0, $cantidad);
        $padLen = max(2, strlen((string)$maxSeq));

        try {
            for ($i = 1; $i <= $cantidad; $i++) {

                $seq = $inicio + $i;
                $seq_form = str_pad((string)$seq, $padLen, '0', STR_PAD_LEFT);

                $lp = $base . $seq_form; // Ej: LP20260217-01

                $descripcion = ($tipo === 'Contenedor')
                    ? 'Contenedor generado'
                    : 'Pallet generado';

                dbq("
                    INSERT INTO c_charolas
                    (
                        empresa_id,
                        cve_almac,
                        Clave_Contenedor,
                        descripcion,
                        Permanente,
                        Pedido,
                        sufijo,
                        tipo,
                        Activo,
                        CveLP,
                        TipoGen
                    )
                    VALUES
                    (
                        :empresa_id,
                        :cve_almac,
                        :clave,
                        :descripcion,
                        :permanente,
                        NULL,
                        :sufijo,
                        :tipo,
                        1,
                        :cvelp,
                        :tipogen
                    )
                ", [
                    'empresa_id' => $empresa_id,
                    'cve_almac'  => $almacen_id, // NULL o idp
                    'clave'      => $lp,
                    'descripcion'=> $descripcion,
                    'permanente' => $permanente,
                    'sufijo'     => $seq,        // numérico, para MAX()
                    'tipo'       => $tipo,
                    'cvelp'      => $lp,
                    'tipogen'    => $tipogen
                ]);

                $generados[] = $lp;
            }

            $exito = "Se generaron " . count($generados) . " License Plates correctamente.";

        } catch (Throwable $e) {
            $errores[] = $e->getMessage();
        }
    }
}

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Nuevo License Plate</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{font-size:11px}
.card-header{background:#0F5AAD;color:#fff;font-weight:600}
</style>
</head>

<body>
<div class="container-fluid mt-3">

<div class="card shadow-sm">
<div class="card-header d-flex justify-content-between">
  <span>Agregar License Plates</span>
  <a href="license_plate.php" class="btn btn-light btn-sm">Regresar</a>
</div>

<div class="card-body">

<?php if($errores): ?>
  <div class="alert alert-danger">
    <?php foreach($errores as $e) echo "<div>".h($e)."</div>"; ?>
  </div>
<?php endif; ?>

<?php if($exito): ?>
  <div class="alert alert-success">
    <?= h($exito) ?><br>
    <?php if(!empty($generados)): ?>
      Primer LP: <strong><?= h($generados[0]) ?></strong>
      <?php if(count($generados)>1): ?>
        | Último LP: <strong><?= h(end($generados)) ?></strong>
      <?php endif; ?>
    <?php endif; ?>
  </div>
<?php endif; ?>

<form method="post" class="row g-3">

  <div class="col-md-4">
    <label>Empresa *</label>
    <select name="empresa" id="empresa" class="form-select form-select-sm" required>
      <option value="">Seleccione empresa...</option>
    </select>
  </div>

  <div class="col-md-4">
    <label>Almacén (Opcional)</label>
    <select name="almacen" id="almacen" class="form-select form-select-sm" disabled>
      <option value="">Todos / No específico</option>
    </select>
  </div>

  <div class="col-md-2">
    <label>Cantidad *</label>
    <input type="number" name="cantidad" min="1" value="<?= h($cantidad ?: 1) ?>" class="form-control form-control-sm" required>
  </div>

  <div class="col-md-2">
    <label>Prefijo *</label>
    <input type="text" name="prefijo" value="<?= h($prefijo ?: 'LP') ?>" class="form-control form-control-sm" required>
  </div>

  <div class="col-md-2">
    <label>Tipo</label>
    <select name="tipo" class="form-select form-select-sm">
      <option value="Pallet" <?= ($tipo==='Pallet'?'selected':'') ?>>Pallet</option>
      <option value="Contenedor" <?= ($tipo==='Contenedor'?'selected':'') ?>>Contenedor</option>
    </select>
  </div>

  <div class="col-md-2">
    <label>Genérico</label>
    <select name="tipogen" class="form-select form-select-sm">
      <option value="0" <?= ($tipogen==0?'selected':'') ?>>No genérico</option>
      <option value="1" <?= ($tipogen==1?'selected':'') ?>>Genérico</option>
    </select>
  </div>

  <div class="col-md-4 mt-4">
    <label class="d-flex align-items-center gap-2" style="user-select:none">
      <input type="checkbox" name="permanente" <?= ($permanente ? 'checked' : '') ?>>
      <span>License Plate permanente</span>
    </label>
  </div>

  <div class="col-12">
    <button class="btn btn-primary btn-sm">Generar LPs</button>
  </div>

</form>

</div>
</div>
</div>

<script>
(() => {
  const CURRENT_EMPRESA = <?= json_encode((string)$empresa_id, JSON_UNESCAPED_UNICODE) ?>;
  const CURRENT_ALMACEN = <?= json_encode($almacen_id === null ? '' : (string)$almacen_id, JSON_UNESCAPED_UNICODE) ?>;

  fetch('../api/api_empresas_almacenes.php')
    .then(res => res.json())
    .then(data => {

      if (!data || !data.ok) {
        console.error((data && data.error) ? data.error : 'API sin respuesta ok');
        return;
      }

      const empresaSelect = document.getElementById('empresa');
      const almacenSelect = document.getElementById('almacen');

      if (!empresaSelect || !almacenSelect) {
        console.error('No existe #empresa o #almacen en el DOM');
        return;
      }

      // ===== EMPRESAS =====
      empresaSelect.innerHTML = '<option value="">Seleccione empresa...</option>';
      (data.empresas || []).forEach(emp => {
        const opt = document.createElement('option');
        opt.value = emp.cve_cia;
        opt.textContent = (emp.clave_empresa ? (emp.clave_empresa + ' - ') : '') + (emp.des_cia || '');
        empresaSelect.appendChild(opt);
      });

      // ===== Render almacenes dependientes por empresa (cve_cia) =====
      const renderAlmacenes = (cveCia) => {
        const cve = String(cveCia || '').trim();

        almacenSelect.innerHTML = '<option value="">Todos / No específico</option>';

        if (!cve) {
          almacenSelect.disabled = true;
          return;
        }

        const lista = (data.almacenes || []).filter(a => String(a.cve_cia) === cve);

        lista.forEach(al => {
          const opt = document.createElement('option');
          opt.value = al.idp; // Guardamos idp
          opt.textContent = (al.cve_almac ? (al.cve_almac + ' - ') : '') + (al.nombre || '');
          almacenSelect.appendChild(opt);
        });

        almacenSelect.disabled = false;
      };

      empresaSelect.addEventListener('change', function () {
        renderAlmacenes(this.value);
      });

      // ===== Restaurar selección si venimos de POST =====
      if (CURRENT_EMPRESA && CURRENT_EMPRESA !== '0') {
        empresaSelect.value = CURRENT_EMPRESA;
        renderAlmacenes(CURRENT_EMPRESA);

        if (CURRENT_ALMACEN) {
          almacenSelect.value = CURRENT_ALMACEN;
        }
      } else {
        almacenSelect.disabled = true;
      }

    })
    .catch(err => console.error('Error fetch empresas/almacenes:', err));
})();
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
</body>
</html>
