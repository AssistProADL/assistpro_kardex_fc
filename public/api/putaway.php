<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/db.php';

$pdo = db_pdo();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($action === '') { echo json_encode(['ok'=>false,'msg'=>'Falta parámetro action']); exit; }

function jexit($ok, $msg='', $data=[], $extra=[]) {
  echo json_encode(array_merge(['ok'=>$ok,'msg'=>$msg,'data'=>$data], $extra));
  exit;
}

try {

  if ($action !== 'confirm') {
    jexit(false, 'Acción no soportada', []);
  }

  // Inputs
  $folio        = (int)($_POST['folio'] ?? 0);
  $id_det       = (int)($_POST['id_det'] ?? 0);
  $art          = trim($_POST['cve_articulo'] ?? '');
  $lote         = trim($_POST['cve_lote'] ?? '');
  $qty          = (float)($_POST['qty'] ?? 0);
  $destino_bl   = trim($_POST['destino_bl'] ?? ''); // BL = c_ubicacion.CodigoCSD
  $usuario      = trim($_POST['usuario'] ?? 'SYSTEM');

  // Opcionales (contenedor/pallet)
  $id_contenedor = (int)($_POST['id_contenedor'] ?? 0); // c_charolas.IDContenedor
  $cont_clave    = trim($_POST['contenedor_clave'] ?? '');
  $cont_lp       = trim($_POST['contenedor_lp'] ?? '');
  $pallet_clave  = trim($_POST['pallet_clave'] ?? '');
  $pallet_lp     = trim($_POST['pallet_lp'] ?? '');

  if ($folio<=0 || $id_det<=0 || $art==='' || $lote==='' || $qty<=0 || $destino_bl==='') {
    jexit(false, 'Parámetros incompletos (folio,id_det,cve_articulo,cve_lote,qty,destino_bl)', []);
  }

  $pdo->beginTransaction();

  // 1) Header (proveedor + almacén texto)
  $sqlH = "SELECT Fol_Folio, Cve_Almac, Cve_Proveedor
           FROM th_entalmacen
           WHERE Fol_Folio=:folio
           LIMIT 1";
  $stH = $pdo->prepare($sqlH);
  $stH->execute([':folio'=>$folio]);
  $h = $stH->fetch(PDO::FETCH_ASSOC);
  if (!$h) { $pdo->rollBack(); jexit(false,'Folio no existe en th_entalmacen',[]); }

  $almac_txt   = (string)$h['Cve_Almac'];           // ej WH8
  $proveedorId = (int)$h['Cve_Proveedor'];

  // 2) Map almacén texto -> id int (c_almacenp)
  $sqlA = "SELECT id FROM c_almacenp WHERE clave=:clave AND IFNULL(Activo,1)=1 LIMIT 1";
  $stA = $pdo->prepare($sqlA);
  $stA->execute([':clave'=>$almac_txt]);
  $almac_int = (int)$stA->fetchColumn();
  if ($almac_int<=0) { $pdo->rollBack(); jexit(false,"No se encontró c_almacenp para clave $almac_txt",[]); }

  // 3) Detalle (validación disponible)
  $sqlD = "SELECT id, fol_folio, cve_articulo, cve_lote,
                  IFNULL(CantidadRecibida,0) recibida,
                  IFNULL(CantidadUbicada,0) ubicada
           FROM td_entalmacen
           WHERE id=:id AND fol_folio=:folio AND cve_articulo=:art AND cve_lote=:lote
           LIMIT 1
           FOR UPDATE";
  $stD = $pdo->prepare($sqlD);
  $stD->execute([':id'=>$id_det,':folio'=>$folio,':art'=>$art,':lote'=>$lote]);
  $d = $stD->fetch(PDO::FETCH_ASSOC);
  if (!$d) { $pdo->rollBack(); jexit(false,'Detalle no existe en td_entalmacen (id/folio/art/lote)',[]); }

  $disp = (float)$d['recibida'] - (float)$d['ubicada'];
  if ($qty > $disp + 0.0001) { $pdo->rollBack(); jexit(false,"Cantidad excede pendiente. Pendiente=$disp",[]); }

  // 4) Ubicación destino (BL -> idy_ubica y almacén técnico)
  $sqlU = "SELECT idy_ubica, cve_almac, IFNULL(Activo,1) Activo, IFNULL(AcomodoMixto,'N') AcomodoMixto
           FROM c_ubicacion
           WHERE CodigoCSD = :bl
           LIMIT 1";
  $stU = $pdo->prepare($sqlU);
  $stU->execute([':bl'=>$destino_bl]);
  $u = $stU->fetch(PDO::FETCH_ASSOC);
  if (!$u) { $pdo->rollBack(); jexit(false,'Destino BL no existe en c_ubicacion.CodigoCSD',[]); }
  if ((int)$u['Activo'] !== 1) { $pdo->rollBack(); jexit(false,'Ubicación destino inactiva',[]); }
  if ((int)$u['cve_almac'] !== $almac_int) { $pdo->rollBack(); jexit(false,'Destino no pertenece al almacén seleccionado',[]); }

  $idy_dest = (int)$u['idy_ubica'];

  // 5) Origen BL (retención) para kardex: tomamos primera retención activa del almacén
  $sqlR = "SELECT cve_ubicacion FROM tubicacionesretencion
           WHERE IFNULL(Activo,1)=1 AND cve_almacp=:almac
           ORDER BY id ASC LIMIT 1";
  $stR = $pdo->prepare($sqlR);
  $stR->execute([':almac'=>$almac_int]);
  $origen_bl = (string)$stR->fetchColumn();
  if ($origen_bl==='') $origen_bl = 'RETENCION';

  // 6) Stock inicial (destino) para t_cardex.stockinicial
  $sqlSI = "SELECT IFNULL(Existencia,0) FROM ts_existenciapiezas
            WHERE cve_almac=:almac AND idy_ubica=:idy AND cve_articulo=:art AND cve_lote=:lote AND ID_Proveedor=:prov
            LIMIT 1";
  $stSI = $pdo->prepare($sqlSI);
  $stSI->execute([':almac'=>$almac_int,':idy'=>$idy_dest,':art'=>$art,':lote'=>$lote,':prov'=>$proveedorId]);
  $stock_ini = (float)$stSI->fetchColumn();

  // 7) Update td_entalmacen (avance acomodo)
  $sqlUpd = "
    UPDATE td_entalmacen
    SET
      CantidadUbicada = IFNULL(CantidadUbicada,0) + :qty,
      cve_ubicacion   = :dest,
      cve_usuario     = :usr,
      fecha_inicio    = IFNULL(fecha_inicio, NOW()),
      fecha_fin       = CASE
                          WHEN (IFNULL(CantidadUbicada,0) + :qty) >= IFNULL(CantidadRecibida,0)
                          THEN NOW() ELSE fecha_fin
                        END
    WHERE id=:id AND fol_folio=:folio AND cve_articulo=:art AND cve_lote=:lote
  ";
  $stUpd = $pdo->prepare($sqlUpd);
  $stUpd->execute([
    ':qty'=>$qty, ':dest'=>$destino_bl, ':usr'=>$usuario,
    ':id'=>$id_det, ':folio'=>$folio, ':art'=>$art, ':lote'=>$lote
  ]);

  // 8) Insert Kardex (Tipo 2 Acomodo)
  $sqlK = "
    INSERT INTO t_cardex
    (cve_articulo,cve_lote,fecha,origen,destino,cantidad,ajuste,stockinicial,id_TipoMovimiento,cve_usuario,
     Cve_Almac,Cve_Almac_Origen,Cve_Almac_Destino,Activo,Fec_Ingreso,ID_Proveedor_Dueno,Referencia,
     contenedor_clave,contenedor_lp,pallet_clave,pallet_lp)
    VALUES
    (:art,:lote,NOW(),:ori,:des,:qty,0,:si,2,:usr,
     :alm,:alm,:alm,1,CURDATE(),:prov,:ref,
     :cc,:clp,:pc,:plp)
  ";
  $stK = $pdo->prepare($sqlK);
  $stK->execute([
    ':art'=>$art, ':lote'=>$lote, ':ori'=>$origen_bl, ':des'=>$destino_bl, ':qty'=>$qty, ':si'=>$stock_ini,
    ':usr'=>$usuario, ':alm'=>$almac_txt, ':prov'=>$proveedorId, ':ref'=>(string)$folio,
    ':cc'=>$cont_clave, ':clp'=>$cont_lp, ':pc'=>$pallet_clave, ':plp'=>$pallet_lp
  ]);
  $id_kardex = (int)$pdo->lastInsertId();

  // 9) Impacto existencias PIEZAS (sumar destino)
  // (Gobierno: lo recibido se vuelve stock hasta que se acomoda)
  $sqlChk = "SELECT Existencia FROM ts_existenciapiezas
             WHERE cve_almac=:almac AND idy_ubica=:idy AND cve_articulo=:art AND cve_lote=:lote AND ID_Proveedor=:prov
             LIMIT 1
             FOR UPDATE";
  $stChk = $pdo->prepare($sqlChk);
  $stChk->execute([':almac'=>$almac_int,':idy'=>$idy_dest,':art'=>$art,':lote'=>$lote,':prov'=>$proveedorId]);
  $exRow = $stChk->fetch(PDO::FETCH_ASSOC);

  if ($exRow) {
    $sqlUpEx = "UPDATE ts_existenciapiezas
                SET Existencia = IFNULL(Existencia,0) + :qty
                WHERE cve_almac=:almac AND idy_ubica=:idy AND cve_articulo=:art AND cve_lote=:lote AND ID_Proveedor=:prov
                LIMIT 1";
    $stUpEx = $pdo->prepare($sqlUpEx);
    $stUpEx->execute([':qty'=>$qty,':almac'=>$almac_int,':idy'=>$idy_dest,':art'=>$art,':lote'=>$lote,':prov'=>$proveedorId]);
  } else {
    $sqlInEx = "INSERT INTO ts_existenciapiezas
                (cve_almac, idy_ubica, id, cve_articulo, cve_lote, Existencia, ID_Proveedor, Cuarentena)
                VALUES
                (:almac,:idy,0,:art,:lote,:qty,:prov,0)";
    $stInEx = $pdo->prepare($sqlInEx);
    $stInEx->execute([':almac'=>$almac_int,':idy'=>$idy_dest,':art'=>$art,':lote'=>$lote,':qty'=>$qty,':prov'=>$proveedorId]);
  }

  // 10) Movimiento de charolas (si viene IDContenedor)
  if ($id_contenedor > 0) {
    $sqlMC = "INSERT INTO t_movcharolas
              (id_kardex, Cve_Almac, ID_Contenedor, Fecha, Origen, Destino, Id_TipoMovimiento, Cve_Usuario, Status, EsCaja)
              VALUES
              (:k,:alm,:idc,NOW(),:ori,:des,2,:usr,'A','N')";
    $stMC = $pdo->prepare($sqlMC);
    $stMC->execute([
      ':k'=>$id_kardex, ':alm'=>$almac_txt, ':idc'=>$id_contenedor,
      ':ori'=>$origen_bl, ':des'=>$destino_bl, ':usr'=>$usuario
    ]);
  }

  $pdo->commit();

  jexit(true, 'Acomodo confirmado', [
    'folio'=>$folio,
    'id_det'=>$id_det,
    'kardex_id'=>$id_kardex,
    'almac'=>$almac_txt,
    'origen'=>$origen_bl,
    'destino'=>$destino_bl,
    'qty'=>$qty
  ]);

} catch (Exception $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  jexit(false, $e->getMessage(), []);
}
