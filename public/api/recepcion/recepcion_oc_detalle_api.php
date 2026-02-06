<?php
// /public/api/recepcion/recepcion_oc_detalle_api.php
require_once __DIR__ . '/../../../app/db.php';
db_pdo();
global $pdo;

header('Content-Type: application/json; charset=utf-8');

function ok($data){ echo json_encode(['ok'=>true,'data'=>$data], JSON_UNESCAPED_UNICODE); exit; }
function err($m,$d=null,$c=400){ http_response_code($c); echo json_encode(['ok'=>false,'msg'=>$m,'detail'=>$d], JSON_UNESCAPED_UNICODE); exit; }

$id_oc = isset($_GET['id_oc']) ? (int)$_GET['id_oc'] : 0;
if($id_oc<=0) err('id_oc requerido');

try{
  // Nota: td_aduana.Ingresado se respeta como "cantidad ya recibida" (numérica).
  $sql = "
    SELECT
      d.Id_DetAduana,
      d.ID_Aduana,
      d.cve_articulo,
      d.cantidad,
      d.Cve_Lote,
      d.caducidad,
      CAST(COALESCE(d.Ingresado,0) AS DECIMAL(18,3)) AS ingresado,
      d.num_orden,
      d.Activo,

      a.des_articulo,
      a.cve_umed                               AS umed_base_id,
      ub.des_umed                              AS umed_base_nombre,
      a.empq_cveumed                           AS empq_cveumed,
      ue.des_umed                              AS empq_umed_nombre,
      COALESCE(NULLIF(a.num_multiplo,0),1)     AS factor_empaque

    FROM td_aduana d
    LEFT JOIN c_articulo a ON a.cve_articulo = d.cve_articulo
    LEFT JOIN c_unimed ub ON ub.id_umed = a.cve_umed
    LEFT JOIN c_unimed ue ON ue.id_umed = a.empq_cveumed
    WHERE d.ID_Aduana = :id_oc
      AND COALESCE(d.Activo,1)=1
    ORDER BY d.Id_DetAduana ASC
  ";
  $st = $pdo->prepare($sql);
  $st->execute([':id_oc'=>$id_oc]);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  // Derivados de negocio: pendiente
  foreach($rows as &$r){
    $cant = (float)$r['cantidad'];
    $ing  = (float)$r['ingresado'];
    $r['pendiente'] = max(0, $cant - $ing);
  }

  ok($rows);

}catch(Throwable $e){
  err('Error consultando detalle', $e->getMessage(), 500);
}
