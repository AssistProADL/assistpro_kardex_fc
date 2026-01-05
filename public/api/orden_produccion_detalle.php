<?php
// /public/api/orden_produccion_detalle.php
require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

$folio = $_GET['folio'] ?? '';
$folio = trim($folio);

if ($folio === '') {
  echo "<span class='text-danger'>Folio no recibido.</span>";
  exit;
}

// Encabezado
$h = $pdo->prepare("SELECT Folio_Pro, Cve_Articulo, IFNULL(Cve_Lote,'') Cve_Lote, IFNULL(Cantidad,0) Cantidad, IFNULL(Status,'') Status
                    FROM t_ordenprod WHERE Folio_Pro=? LIMIT 1");
$h->execute([$folio]);
$head = $h->fetch(PDO::FETCH_ASSOC);

if(!$head){
  echo "<span class='text-muted'>Sin detalle disponible.</span>";
  exit;
}

// Detalle: ojo -> en td_ordenprod la fecha suele ser Fecha_Prod (no 'Fecha')
$sql = "SELECT
          IFNULL(Cve_Articulo,'') Articulo,
          IFNULL(Cve_Lote,'') Lote,
          IFNULL(Cantidad,0) Cantidad,
          DATE_FORMAT(IFNULL(Fecha_Prod, Fecha),'%d/%m/%Y') Fecha,
          IFNULL(Usr_Armo,'') Usuario
        FROM td_ordenprod
        WHERE Folio_Pro=?
        ORDER BY Articulo";
$d = $pdo->prepare($sql);
$d->execute([$folio]);
$rows = $d->fetchAll(PDO::FETCH_ASSOC);

$folioTxt = htmlspecialchars($head['Folio_Pro'], ENT_QUOTES, 'UTF-8');
$artTxt   = htmlspecialchars($head['Cve_Articulo'], ENT_QUOTES, 'UTF-8');
$loteTxt  = htmlspecialchars($head['Cve_Lote'], ENT_QUOTES, 'UTF-8');
$stTxt    = htmlspecialchars($head['Status'], ENT_QUOTES, 'UTF-8');
$cantTxt  = number_format((float)$head['Cantidad'],4,'.','');

echo "<div class='mb-2'>";
echo "<b>Folio:</b> {$folioTxt} &nbsp;&nbsp; ";
echo "<b>Artículo:</b> {$artTxt} &nbsp;&nbsp; ";
echo "<b>Lote:</b> {$loteTxt} &nbsp;&nbsp; ";
echo "<b>Cantidad:</b> {$cantTxt} &nbsp;&nbsp; ";
echo "<b>Status:</b> {$stTxt}";
echo "</div>";

if(!$rows){
  echo "<span class='text-muted'>Sin detalle disponible.</span>";
  exit;
}

echo "<div class='table-responsive'>";
echo "<table class='table table-sm table-striped' style='font-size:10px;'>";
echo "<thead><tr>
        <th>Artículo</th>
        <th>Lote</th>
        <th class='text-end'>Cantidad</th>
        <th>Fecha</th>
        <th>Usuario</th>
      </tr></thead><tbody>";

foreach($rows as $r){
  $a = htmlspecialchars($r['Articulo'], ENT_QUOTES, 'UTF-8');
  $l = htmlspecialchars($r['Lote'], ENT_QUOTES, 'UTF-8');
  $u = htmlspecialchars($r['Usuario'], ENT_QUOTES, 'UTF-8');
  $f = htmlspecialchars($r['Fecha'] ?? '', ENT_QUOTES, 'UTF-8');
  $c = number_format((float)$r['Cantidad'],4,'.','');
  echo "<tr>
          <td>{$a}</td>
          <td>{$l}</td>
          <td class='text-end'>{$c}</td>
          <td>{$f}</td>
          <td>{$u}</td>
        </tr>";
}

echo "</tbody></table></div>";
