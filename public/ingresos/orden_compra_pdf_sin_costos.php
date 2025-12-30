<?php
// public/ingresos/orden_compra_pdf_sin_costos.php
require_once __DIR__ . '/../../app/db.php';

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Autoload flexible (dompdf + posible QR)
$autoloads = [
  __DIR__ . '/../../vendor/autoload.php',
  __DIR__ . '/../../app/vendor/autoload.php',
  __DIR__ . '/../../lib/vendor/autoload.php',
];
foreach ($autoloads as $a) { if (is_file($a)) { require_once $a; break; } }

use Dompdf\Dompdf;

// ==== QR (si existe) ====
function qr_png_datauri(string $text, int $size = 90): string {
    $text = trim($text);
    if ($text === '') return '';

    // Endroid QRCode (si está instalado)
    if (class_exists('\Endroid\QrCode\QrCode') && class_exists('\Endroid\QrCode\Writer\PngWriter')) {
        try {
            $qr = \Endroid\QrCode\QrCode::create($text)
                ->setSize($size)
                ->setMargin(6);

            $writer = new \Endroid\QrCode\Writer\PngWriter();
            $result = $writer->write($qr);
            $png = $result->getString();
            $b64 = base64_encode($png);
            return '<img alt="qr" style="width:'.$size.'px;height:'.$size.'px" src="data:image/png;base64,'.$b64.'">';
        } catch(Throwable $e) {
            // cae a fallback
        }
    }

    return ''; // si no hay QR, se decide fallback afuera
}

// ==== Code128 fallback (tu versión) ====
function code128_svg_datauri(string $text, int $height = 34, int $module = 1): string {
    $text = trim($text);
    if ($text === '') return '';

    $patterns = [
        "212222","222122","222221","121223","121322","131222","122213","122312","132212","221213","221312","231212",
        "112232","122132","122231","113222","123122","123221","223211","221132","221231","213212","223112","312131",
        "311222","321122","321221","312212","322112","322211","212123","212321","232121","111323","131123","131321",
        "112313","132113","132311","211313","231113","231311","112133","112331","132131","113123","113321","133121",
        "313121","211331","231131","213113","213311","213131","311123","311321","331121","312113","312311","332111",
        "314111","221411","431111","111224","111422","121124","121421","141122","141221","112214","112412","122114",
        "122411","142112","142211","241211","221114","413111","241112","134111","111242","121142","121241","114212",
        "124112","124211","411212","421112","421211","212141","214121","412121","111143","111341","131141","114113",
        "114311","411113","411311","113141","114131","311141","411131","211412","211214","211232","2331112"
    ];

    $values = [];
    $sum = 104; // Start B
    $values[] = 104;

    $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $pos = 1;
    foreach ($chars as $ch) {
        $ord = ord($ch);
        if ($ord < 32 || $ord > 127) { $ch = '?'; $ord = ord($ch); }
        $code = $ord - 32;
        $values[] = $code;
        $sum += $code * $pos;
        $pos++;
    }
    $check = $sum % 103;
    $values[] = $check;
    $values[] = 106;

    $x = 0; $bars = [];
    $quiet = 10; $x += $quiet;

    foreach ($values as $v) {
        $pat = $patterns[$v] ?? '';
        if ($pat === '') continue;
        $toggleBar = true;
        foreach (str_split($pat) as $d) {
            $w = (int)$d * $module;
            if ($toggleBar) $bars[] = ['x'=>$x, 'w'=>$w];
            $x += $w;
            $toggleBar = !$toggleBar;
        }
    }
    $x += $quiet;
    $width = max($x, 1);

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'">';
    $svg .= '<rect width="100%" height="100%" fill="#fff"/>';
    foreach ($bars as $b) $svg .= '<rect x="'.$b['x'].'" y="0" width="'.$b['w'].'" height="'.$height.'" fill="#111"/>';
    $svg .= '</svg>';

    $b64 = base64_encode($svg);
    return '<img alt="barcode" style="height:'.$height.'px" src="data:image/svg+xml;base64,'.$b64.'">';
}

// ==== Data ====
$pdo = db_pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id = (int)($_GET['id_aduana'] ?? 0);
if ($id <= 0) die('id_aduana inválido');

$h = $pdo->prepare("
  SELECT h.*, p.Nombre AS proveedor, pr.descripcion AS protocolo_desc,
         COALESCE(NULLIF(TRIM(h.Pedimento),''), NULLIF(TRIM(CAST(h.num_pedimento AS CHAR)),'')) AS FolioOC
  FROM th_aduana h
  LEFT JOIN c_proveedores p ON p.ID_Proveedor=h.ID_Proveedor
  LEFT JOIN t_protocolo pr ON pr.ID_Protocolo=h.ID_Protocolo
  WHERE h.ID_Aduana=?
  LIMIT 1
");
$h->execute([$id]);
$H = $h->fetch(PDO::FETCH_ASSOC);
if (!$H) die('OC no encontrada');

$d = $pdo->prepare("
  SELECT d.cve_articulo, d.cantidad, a.des_articulo, a.unidadMedida AS UOM
  FROM td_aduana d
  LEFT JOIN c_articulo a ON a.cve_articulo=d.cve_articulo
  WHERE d.ID_Aduana=?
  ORDER BY d.Id_DetAduana
");
$d->execute([$id]);
$D = $d->fetchAll(PDO::FETCH_ASSOC);

$folio = trim((string)($H['FolioOC'] ?? ''));
if ($folio === '') $folio = 'OC_'.$id;

$fecha = '';
try { $fecha = (new DateTime($H['fech_pedimento']))->format('Y-m-d'); } catch(Throwable $e) {}

$html = '
<!doctype html><html><head><meta charset="utf-8">
<style>
body{font-family:DejaVu Sans, Arial, sans-serif;font-size:11px;color:#111}
table{border-collapse:collapse;width:100%}
th,td{border:1px solid #cfd8e3;padding:6px}
th{background:#f1f5f9;font-weight:800;font-size:10px}
.small{font-size:10px;color:#555}
.right{text-align:right}
.center{text-align:center}
.no{border:none}
</style></head><body>
  <table class="no" style="border:none;margin-bottom:6px">
    <tr>
      <td class="no" style="border:none;width:70%">
        <div style="font-size:14px;font-weight:900;color:#0F5AAD;">OC · Recepción (Sin Costos)</div>
        <div class="small"><b>Folio OC:</b> '.h($folio).' &nbsp; <b>ID:</b> '.(int)$H['ID_Aduana'].'</div>
        <div class="small"><b>Proveedor:</b> '.h($H['proveedor'] ?? '').'</div>
        <div class="small"><b>Almacén:</b> '.h($H['Cve_Almac'] ?? '').' &nbsp; <b>Tipo:</b> '.h($H['ID_Protocolo'] ?? '').'</div>
        <div class="small"><b>Fecha:</b> '.h($fecha).'</div>
      </td>
      <td class="no" style="border:none;width:30%;text-align:right">
        <div class="small"><b>Escaneo rápido:</b></div>
        <div class="small">QR por SKU</div>
      </td>
    </tr>
  </table>

  <table>
    <thead>
      <tr>
        <th style="width:20px">#</th>
        <th style="width:95px">SKU</th>
        <th>Descripción</th>
        <th style="width:45px">UOM</th>
        <th style="width:70px" class="right">Cantidad</th>
        <th style="width:110px" class="center">QR</th>
      </tr>
    </thead>
    <tbody>
';

$i=0; $tot=0.0;
foreach ($D as $r){
  $i++;
  $sku = trim((string)$r['cve_articulo']);
  $qty = (float)($r['cantidad'] ?? 0);
  $tot += $qty;

  $qr = qr_png_datauri($sku, 90);
  if ($qr === '') {
      // fallback barcode si no hay librería QR
      $qr = code128_svg_datauri($sku, 34, 1);
  }

  $html .= '
    <tr>
      <td class="center">'.$i.'</td>
      <td>'.h($sku).'</td>
      <td>'.h($r['des_articulo'] ?? '').'</td>
      <td class="center">'.h($r['UOM'] ?? '').'</td>
      <td class="right">'.number_format($qty,4).'</td>
      <td class="center">'.$qr.'</td>
    </tr>
  ';
}

$html .= '
      <tr>
        <th colspan="4" class="right">Total</th>
        <th class="right">'.number_format($tot,4).'</th>
        <th></th>
      </tr>
    </tbody>
  </table>
</body></html>
';

$dompdf = new Dompdf(['isRemoteEnabled' => true]);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('letter', 'portrait');
$dompdf->render();
$dompdf->stream('OC-Recepcion-'.preg_replace('/[^A-Za-z0-9_\-]/','_', $folio).'.pdf', ['Attachment' => false]);
