<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

db_pdo();
global $pdo;

$id_case = (int)($_GET['id_case'] ?? 0);
if ($id_case <= 0) {
  die('Caso inválido');
}

/* ===================== CONSULTA PRINCIPAL ===================== */
$sql = "
SELECT
  c.*,

  m_ap.nombre AS motivo_apertura,
  m_ci.nombre AS motivo_cierre,

  cia.imagen AS compania_imagen,
  prov.imagen AS proveedor_imagen,
  cia.Es_3PL

FROM pqrs_case c

LEFT JOIN pqrs_cat_motivo m_ap
  ON m_ap.id_motivo = c.motivo_registro_id
 AND m_ap.tipo = 'APERTURA'

LEFT JOIN pqrs_cat_motivo m_ci
  ON m_ci.id_motivo = c.motivo_cierre_id
 AND m_ci.tipo = 'CIERRE'

LEFT JOIN c_compania cia
  ON cia.cve_cia = c.cve_almacen

LEFT JOIN c_proveedores prov
  ON prov.ID_Proveedor = cia.Id_Proveedor

WHERE c.id_case = ?
LIMIT 1
";

$st = $pdo->prepare($sql);
$st->execute([$id_case]);
$case = $st->fetch(PDO::FETCH_ASSOC);

if (!$case) {
  die('Caso no encontrado');
}

/* ===================== LOGO ===================== */

/**
 * DOCUMENT_ROOT detectado:
 * C:/xampp/htdocs
 *
 * Proyecto:
 * C:/xampp/htdocs/assistpro_kardex_fc/
 */

$baseImg = $_SERVER['DOCUMENT_ROOT']
         . '/assistpro_kardex_fc/public/img/';

// Logo default
$logoAbs = $baseImg . 'logo.png';

// DEBUG INFO
$logoDebug = [
  'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'],
  'baseImg'       => $baseImg,
  'default'       => $logoAbs,
  'usado'         => 'default'
];

/*
Reglas:
1) Si es 3PL y proveedor tiene imagen → proveedor
2) Si no, y compañía tiene imagen → compañía
3) Si ninguno → default
*/

// 1️⃣ Proveedor (3PL)
if (
  $case['Es_3PL'] === 'S' &&
  !empty($case['proveedor_imagen'])
) {
  $tmp = $baseImg . 'proveedores/' . $case['proveedor_imagen'];
  if (file_exists($tmp)) {
    $logoAbs = $tmp;
    $logoDebug['usado'] = 'proveedor';
    $logoDebug['path']  = $tmp;
  } else {
    $logoDebug['proveedor_missing'] = $tmp;
  }
}

// 2️⃣ Compañía
elseif (!empty($case['compania_imagen'])) {
  $tmp = $baseImg . 'companias/' . $case['compania_imagen'];
  if (file_exists($tmp)) {
    $logoAbs = $tmp;
    $logoDebug['usado'] = 'compania';
    $logoDebug['path']  = $tmp;
  } else {
    $logoDebug['compania_missing'] = $tmp;
  }
}

// Validación final
if (!file_exists($logoAbs)) {
  $logoDebug['ERROR_FINAL'] = 'NO EXISTE LOGO FINAL';
}

/* ===================== TIMELINE ===================== */
$stE = $pdo->prepare("
  SELECT evento, detalle, usuario, creado_en
  FROM pqrs_event
  WHERE id_case = ?
  ORDER BY creado_en ASC
");
$stE->execute([$id_case]);
$events = $stE->fetchAll(PDO::FETCH_ASSOC);

/* ===================== HTML ===================== */
ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
body{
  font-family: DejaVu Sans, sans-serif;
  font-size:12px;
  color:#1f2937;
}
.header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  border-bottom:2px solid #e5e7eb;
  padding-bottom:10px;
  margin-bottom:18px;
}
.logo img{
  max-height:60px;
}
h1{font-size:18px;margin:0}
.badge{
  display:inline-block;
  padding:4px 10px;
  border-radius:12px;
  background:#e0e7ff;
  color:#1e3a8a;
  font-size:11px;
}
.section{margin-top:18px}
.section h3{
  color:#1e3a8a;
  border-bottom:1px solid #e5e7eb;
  padding-bottom:4px;
}
table{width:100%;border-collapse:collapse}
th,td{border:1px solid #e5e7eb;padding:6px}
th{background:#f8fafc;text-align:left;width:30%}
</style>
</head>
<body>

<!-- HEADER -->
<div class="header">
  <div class="logo">
    <img src="<?= $logoAbs ?>">
  </div>
  <div>
    <h1>Incidencia PQRS</h1>
    <div><b>Folio:</b> <?= htmlspecialchars($case['fol_pqrs']) ?></div>
    <span class="badge"><?= htmlspecialchars($case['status_clave']) ?></span>
  </div>
</div>

<!-- DEBUG (ELIMINAR EN PRODUCCIÓN) -->
<div style="font-size:10px;color:#b91c1c;margin-bottom:10px;">
  <pre><?= htmlspecialchars(print_r($logoDebug, true)) ?></pre>
</div>

<div class="section">
  <h3>Resumen ejecutivo</h3>
  <table>
    <tr><th>Cliente</th><td><?= htmlspecialchars($case['cve_clte']) ?></td></tr>
    <tr><th>Almacén</th><td><?= htmlspecialchars($case['cve_almacen']) ?></td></tr>
    <tr><th>Tipo</th><td><?= htmlspecialchars($case['tipo']) ?></td></tr>
    <tr><th>Referencia</th><td><?= htmlspecialchars($case['ref_tipo'].' '.$case['ref_folio']) ?></td></tr>
    <tr><th>Susceptible a cobro</th><td><?= $case['susceptible_cobro']?'Sí':'No' ?></td></tr>
  </table>
</div>

<div class="section">
  <h3>Detalle del caso</h3>
  <table>
    <tr><th>Motivo de apertura</th><td><?= htmlspecialchars($case['motivo_apertura'] ?? '—') ?></td></tr>
    <tr><th>Descripción</th><td><?= nl2br(htmlspecialchars($case['descripcion'])) ?></td></tr>
    <?php if ($case['motivo_cierre']): ?>
      <tr><th>Motivo de cierre</th><td><?= htmlspecialchars($case['motivo_cierre']) ?></td></tr>
    <?php endif; ?>
  </table>
</div>

<div class="section">
  <h3>Timeline / Auditoría</h3>
  <table>
    <tr><th>Fecha</th><th>Evento</th><th>Detalle</th><th>Usuario</th></tr>
    <?php foreach ($events as $e): ?>
      <tr>
        <td><?= htmlspecialchars($e['creado_en']) ?></td>
        <td><?= htmlspecialchars($e['evento']) ?></td>
        <td><?= nl2br(htmlspecialchars($e['detalle'])) ?></td>
        <td><?= htmlspecialchars($e['usuario']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

</body>
</html>
<?php
$html = ob_get_clean();

/* ===================== DOMPDF ===================== */
$options = new Options();
$options->set('defaultFont','DejaVu Sans');
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4','portrait');
$dompdf->render();

$dompdf->stream(
  'PQRS_'.$case['fol_pqrs'].'.pdf',
  ['Attachment'=>false] // ← para ver debug
);
