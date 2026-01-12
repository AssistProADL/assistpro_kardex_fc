<?php
// public/api/sfa/planeacion_rutas_save.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../app/db.php';

function out($arr, $code=200){ http_response_code($code); echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }

try{
  $raw = file_get_contents('php://input');
  if(!$raw) out(['ok'=>0,'error'=>'Body JSON requerido'], 400);

  $j = json_decode($raw, true);
  if(!is_array($j)) out(['ok'=>0,'error'=>'JSON inválido'], 400);

  $almacenId = trim((string)($j['almacen_id'] ?? ''));
  $rutaId    = trim((string)($j['ruta_id'] ?? ''));
  $items     = $j['items'] ?? [];

  if($almacenId==='') out(['ok'=>0,'error'=>'almacen_id requerido'], 400);
  if($rutaId==='') out(['ok'=>0,'error'=>'ruta_id requerido'], 400);
  if(!is_array($items) || !count($items)) out(['ok'=>0,'error'=>'items requerido'], 400);

  $cveRuta = db_val("SELECT cve_ruta FROM t_ruta WHERE ID_Ruta = ? OR cve_ruta = ? LIMIT 1", [$rutaId, $rutaId]);
  if(!$cveRuta) out(['ok'=>0,'error'=>'Ruta no encontrada'], 404);

  $ok = 0; $err = 0; $errs = [];

  db_tx(function() use ($items, $almacenId, $cveRuta, &$ok, &$err, &$errs){
    foreach($items as $it){
      try{
        $idDest = (int)($it['id_destinatario'] ?? 0);
        $cveClte= (string)($it['cve_clte'] ?? '');
        $days   = $it['days'] ?? [];

        if($idDest<=0 || $cveClte==='') throw new Exception('id_destinatario/cve_clte inválido');

        $Lu = !empty($days['Lu']) ? 1 : 0;
        $Ma = !empty($days['Ma']) ? 1 : 0;
        $Mi = !empty($days['Mi']) ? 1 : 0;
        $Ju = !empty($days['Ju']) ? 1 : 0;
        $Vi = !empty($days['Vi']) ? 1 : 0;
        $Sa = !empty($days['Sa']) ? 1 : 0;
        $Do = !empty($days['Do']) ? 1 : 0;

        $existsId = db_val("
          SELECT Id
          FROM reldaycli
          WHERE Cve_Ruta = ? AND Cve_Cliente = ? AND Id_Destinatario = ?
          LIMIT 1
        ", [$cveRuta, $cveClte, $idDest]);

        if($existsId){
          dbq("
            UPDATE reldaycli
            SET
              Cve_Almac = ?,
              Lu=?, Ma=?, Mi=?, Ju=?, Vi=?, Sa=?, Do=?
            WHERE Id = ?
          ", [$almacenId, $Lu,$Ma,$Mi,$Ju,$Vi,$Sa,$Do, $existsId]);
        }else{
          dbq("
            INSERT INTO reldaycli
              (Id_Destinatario, Cve_Ruta, Cve_Cliente, Cve_Almac, Lu, Ma, Mi, Ju, Vi, Sa, Do)
            VALUES
              (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
          ", [$idDest, $cveRuta, $cveClte, $almacenId, $Lu,$Ma,$Mi,$Ju,$Vi,$Sa,$Do]);
        }

        $ok++;
      }catch(Throwable $e){
        $err++;
        $errs[] = ['item'=>$it,'error'=>$e->getMessage()];
      }
    }

    if($err>0 && $ok===0){
      throw new Exception('No se pudo guardar ningún registro');
    }
  });

  out(['ok'=>1,'ok_count'=>$ok,'err_count'=>$err,'errs'=>$errs,'cve_ruta'=>$cveRuta]);
}catch(Throwable $e){
  out(['ok'=>0,'error'=>'Error servidor','detalle'=>$e->getMessage()], 500);
}
