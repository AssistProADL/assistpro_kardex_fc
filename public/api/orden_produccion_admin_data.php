<?php
require_once __DIR__ . '/../../app/db.php';
$pdo=db_pdo();

$start=$_POST['start']??0;
$length=$_POST['length']??25;
$draw=$_POST['draw']??1;

$total=$pdo->query("SELECT COUNT(*) FROM t_ordenprod")->fetchColumn();

$sql="
SELECT id,Folio_Pro,Cve_Articulo,Cve_Lote,
Cantidad,Cant_Prod,Cve_Usuario,
DATE_FORMAT(Fecha,'%d/%m/%Y') Fecha,Status
FROM t_ordenprod
ORDER BY Fecha DESC
LIMIT :s,:l";

$q=$pdo->prepare($sql);
$q->bindValue(':s',$start,PDO::PARAM_INT);
$q->bindValue(':l',$length,PDO::PARAM_INT);
$q->execute();

$data=[];
while($r=$q->fetch(PDO::FETCH_ASSOC)){
$r['acciones']="
<button class='btn btn-sm btn-primary btnVerOT' data-id='{$r['id']}'>
<i class='fa fa-search'></i></button>
<button class='btn btn-sm btn-success'>
<i class='fa fa-play'></i></button>";
$data[]=$r;
}

echo json_encode([
"draw"=>$draw,
"recordsTotal"=>$total,
"recordsFiltered"=>$total,
"data"=>$data
]);
