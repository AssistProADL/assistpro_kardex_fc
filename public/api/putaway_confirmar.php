<?php
ob_clean();
require_once __DIR__ . '/../../app/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();
if (!$pdo) {
  echo json_encode(['ok'=>false,'msg'=>'PDO no inicializado','data'=>[]]);
  exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function jexit($ok,$msg='',$data=[],$extra=[]){
  echo json_encode(array_merge(['ok'=>$ok,'msg'=>$msg,'data'=>$data],$extra));
  exit;
}

function pick_user(PDO $pdo): string {
  $u = trim((string)($_SESSION['cve_usuario'] ?? ($_SESSION['username'] ?? '')));
  if ($u !== '') return $u;

  $x = '';
  try {
    $x = $pdo->query("
      SELECT TRIM(cve_usuario)
      FROM c_usuario
      WHERE COALESCE(Activo,1)=1
      ORDER BY nombre_completo
      LIMIT 1
    ")->fetchColumn();
  } catch(Throwable $e) {}
  return trim((string)$x);
}

try {

  if ($action === '') jexit(false,'Falta parámetro action',[]);
  if ($action !== 'confirm') jexit(false,'Acción no soportada',[]);

  // ================= INPUTS =================
  $folio      = (int)($_POST['folio'] ?? 0);          // th_entalmacen.Fol_Folio
  $id_det     = (int)($_POST['id_det'] ?? 0);         // td_entalmacen.id
  $art        = trim((string)($_POST['cve_articulo'] ?? ''));
  $lote       = trim((string)($_POST['cve_lote'] ?? ''));
  $qty        = (float)($_POST['qty'] ?? 0);

  // BL destino (C_UBICACION.CodigoCSD) - tu estándar BL
  $destino_bl = trim((string)($_POST['destino_bl'] ?? ''));

  // usuario: puede venir de select; si no, fallback
  $usuario    = trim((string)($_POST['cve_usuario'] ?? ''));
  if ($usuario === '') $usuario = pick_user($pdo);
  if ($usuario === '') jexit(false,'No se pudo determinar usuario del movimiento',[]);

  // Opcionales: trazabilidad física
  $id_contenedor = (int)($_POST['id_contenedor'] ?? 0);
  $cont_clave    = trim((string)($_POST['contenedor_clave'] ?? ''));
  $cont_lp       = trim((string)($_POST['contenedor_lp'] ?? ''));
  $pallet_clave  = trim((string)($_POST['pallet_clave'] ?? ''));
  $pallet_lp     = trim((string)($_POST['pallet_lp'] ?? ''));

  if ($folio<=0 || $id_det<=0 || $art==='' || $qty<=0 || $destino_bl==='') {
    jexit(false,'Parámetros incompletos (folio,id_det,cve_articulo,qty,destino_bl)',[]);
  }

  $pdo->beginTransaction();

  // ================= HEADER: trazabilidad heredada =================
  $h = db_one("
    SELECT
      Fol_Folio,
      Cve_Almac,
      Fol_OEP,
      Fact_Prov,
      Proveedor,
      Proyecto,
      Cve_Proveedor,
      ID_Protocolo,
      Consec_protocolo,
      tipo
    FROM th_entalmacen
    WHERE Fol_Folio = :folio
    LIMIT 1
  ", [':folio'=>$folio]);

  if (!$h) { $pdo->rollBack(); jexit(false,'Folio no existe en th_entalmacen',[]); }

  $almac_txt   = trim((string)$h['Cve_Almac']);   // clave (varchar) ej WH8
  $proveedorId = (int)($h['Cve_Proveedor'] ?? 0);

  if ($almac_txt==='') { $pdo->rollBack(); jexit(false,'th_entalmacen sin Cve_Almac',[]); }
  if ($proveedorId<=0) { $pdo->rollBack(); jexit(false,'th_entalmacen sin Cve_Proveedor (dueño)',[]); }

  // Map almacén clave -> id int
  $almac_int = (int)db_val("
    SELECT id
    FROM c_almacenp
    WHERE clave=:c AND COALESCE(Activo,1)=1
    LIMIT 1
  ", [':c'=>$almac_txt]);

  if ($almac_int<=0) { $pdo->rollBack(); jexit(false,"No existe c_almacenp para almacén $almac_txt",[]); }

  // ================= DETALLE: pendiente =================
  $d = db_one("
    SELECT id, fol_folio, cve_articulo, cve_lote,
           COALESCE(CantidadRecibida,0) AS recibida,
           COALESCE(CantidadUbicada,0)  AS ubicada
    FROM td_entalmacen
    WHERE id=:id AND fol_folio=:folio
      AND TRIM(cve_articulo)=TRIM(:art)
      AND IFNULL(TRIM(cve_lote),'')=IFNULL(TRIM(:lote),'')
    LIMIT 1
    FOR UPDATE
  ", [':id'=>$id_det,':folio'=>$folio,':art'=>$art,':lote'=>$lote]);

  if (!$d) { $pdo->rollBack(); jexit(false,'Detalle no existe en td_entalmacen (id/folio/art/lote)',[]); }

  $pend = (float)$d['recibida'] - (float)$d['ubicada'];
  if ($pend <= 0) { $pdo->rollBack(); jexit(false,'No hay pendiente en esta línea',[]); }
  if ($qty > $pend + 0.0001) { $pdo->rollBack(); jexit(false,"Cantidad excede pendiente ($pend)",[]); }

  // ================= DESTINO: BL real =================
  $u = db_one("
    SELECT idy_ubica, cve_almac, COALESCE(Activo,1) Activo, COALESCE(AcomodoMixto,'N') AcomodoMixto
    FROM c_ubicacion
    WHERE CodigoCSD = :bl
    LIMIT 1
  ", [':bl'=>$destino_bl]);

  if (!$u) { $pdo->rollBack(); jexit(false,'BL destino no existe (c_ubicacion.CodigoCSD)',[]); }
  if ((int)$u['Activo'] !== 1) { $pdo->rollBack(); jexit(false,'BL destino inactivo',[]); }
  if ((int)$u['cve_almac'] !== $almac_int) { $pdo->rollBack(); jexit(false,'BL destino no pertenece al almacén',[]); }

  $idy_dest = (int)$u['idy_ubica'];

  // ================= ORIGEN: zona recibo del almacén =================
  $origen_bl = trim((string)db_val("
    SELECT cve_ubicacion
    FROM tubicacionesretencion
    WHERE COALESCE(Activo,1)=1 AND cve_almacp=:a
    ORDER BY id ASC
    LIMIT 1
  ", [':a'=>$almac_int]));

  if ($origen_bl==='') $origen_bl = 'RETENCION';

  // ================= STOCK INICIAL (destino) =================
  $stock_ini = (float)db_val("
    SELECT COALESCE(Existencia,0)
    FROM ts_existenciapiezas
    WHERE cve_almac=:alm AND idy_ubica=:idy
      AND TRIM(cve_articulo)=TRIM(:art)
      AND IFNULL(TRIM(cve_lote),'')=IFNULL(TRIM(:lote),'')
      AND ID_Proveedor=:prov
    LIMIT 1
  ", [':alm'=>$almac_int,':idy'=>$idy_dest,':art'=>$art,':lote'=>$lote,':prov'=>$proveedorId]);

  // ================= 1) K A R D E X (ANCLA) =================
  // Referencia = Fol_Folio  -> con esto v_kardex_enriquecido_v3 puede traer OC,Factura,Proyecto,Proveedor,etc.
  dbq("
    INSERT INTO t_cardex
    (cve_articulo,cve_lote,fecha,origen,destino,cantidad,ajuste,stockinicial,id_TipoMovimiento,cve_usuario,
     Cve_Almac,Cve_Almac_Origen,Cve_Almac_Destino,Activo,Fec_Ingreso,ID_Proveedor_Dueno,Referencia,
     contenedor_clave,contenedor_lp,pallet_clave,pallet_lp)
    VALUES
    (:art,:lote,NOW(),:ori,:des,:qty,0,:si,2,:usr,
     :alm,:alm,:alm,1,CURDATE(),:prov,:ref,
     :cc,:clp,:pc,:plp)
  ", [
    ':art'=>$art, ':lote'=>$lote,
    ':ori'=>$origen_bl, ':des'=>$destino_bl,
    ':qty'=>$qty, ':si'=>$stock_ini,
    ':usr'=>$usuario,
    ':alm'=>$almac_txt,
    ':prov'=>$proveedorId,
    ':ref'=>(string)$folio,
    ':cc'=>$cont_clave, ':clp'=>$cont_lp,
    ':pc'=>$pallet_clave, ':plp'=>$pallet_lp
  ]);

  $id_kardex = (int)$pdo->lastInsertId();

  // ================= 2) EXISTENCIAS DESTINO (PIEZAS) =================
  // Nota: para contenedor/pallet completos se implementa rama tarima/charolas; aquí queda base robusta.
  $ex = db_one("
    SELECT Existencia
    FROM ts_existenciapiezas
    WHERE cve_almac=:alm AND idy_ubica=:idy
      AND TRIM(cve_articulo)=TRIM(:art)
      AND IFNULL(TRIM(cve_lote),'')=IFNULL(TRIM(:lote),'')
      AND ID_Proveedor=:prov
    LIMIT 1
    FOR UPDATE
  ", [':alm'=>$almac_int,':idy'=>$idy_dest,':art'=>$art,':lote'=>$lote,':prov'=>$proveedorId]);

  if ($ex) {
    dbq("
      UPDATE ts_existenciapiezas
      SET Existencia = COALESCE(Existencia,0) + :qty
      WHERE cve_almac=:alm AND idy_ubica=:idy
        AND TRIM(cve_articulo)=TRIM(:art)
        AND IFNULL(TRIM(cve_lote),'')=IFNULL(TRIM(:lote),'')
        AND ID_Proveedor=:prov
      LIMIT 1
    ", [':qty'=>$qty,':alm'=>$almac_int,':idy'=>$idy_dest,':art'=>$art,':lote'=>$lote,':prov'=>$proveedorId]);
  } else {
    dbq("
      INSERT INTO ts_existenciapiezas
      (cve_almac,idy_ubica,id,cve_articulo,cve_lote,Existencia,ID_Proveedor,Cuarentena)
      VALUES
      (:alm,:idy,0,:art,:lote,:qty,:prov,0)
    ", [':alm'=>$almac_int,':idy'=>$idy_dest,':art'=>$art,':lote'=>$lote,':qty'=>$qty,':prov'=>$proveedorId]);
  }

  // ================= 3) ACTUALIZAR PENDIENTE (td_entalmacen) =================
  dbq("
    UPDATE td_entalmacen
    SET
      CantidadUbicada = COALESCE(CantidadUbicada,0) + :qty,
      cve_ubicacion   = :dest,
      cve_usuario     = :usr,
      fecha_inicio    = COALESCE(fecha_inicio, NOW()),
      fecha_fin       = CASE
                          WHEN (COALESCE(CantidadUbicada,0) + :qty) >= COALESCE(CantidadRecibida,0)
                          THEN NOW() ELSE fecha_fin
                        END
    WHERE id=:id AND fol_folio=:folio
      AND TRIM(cve_articulo)=TRIM(:art)
      AND IFNULL(TRIM(cve_lote),'')=IFNULL(TRIM(:lote),'')
    LIMIT 1
  ", [
    ':qty'=>$qty, ':dest'=>$destino_bl, ':usr'=>$usuario,
    ':id'=>$id_det, ':folio'=>$folio, ':art'=>$art, ':lote'=>$lote
  ]);

  // ================= 4) MOVIMIENTO CONTENEDOR (si aplica) =================
  if ($id_contenedor > 0) {
    dbq("
      INSERT INTO t_movcharolas
      (id_kardex,Cve_Almac,ID_Contenedor,Fecha,Origen,Destino,Id_TipoMovimiento,Cve_Usuario,Status,EsCaja)
      VALUES
      (:k,:alm,:idc,NOW(),:ori,:des,2,:usr,'A','N')
    ", [
      ':k'=>$id_kardex,
      ':alm'=>$almac_txt,
      ':idc'=>$id_contenedor,
      ':ori'=>$origen_bl,
      ':des'=>$destino_bl,
      ':usr'=>$usuario
    ]);
  }

  // ================= OUTPUT TRAZABLE PARA VALIDACIÓN =================
  $out = [
    'kardex_id'   => $id_kardex,
    'usuario'     => $usuario,
    'timestamp'   => date('Y-m-d H:i:s'),
    'tipo_mov'    => 2,
    'folio'       => $folio,
    'almacen'     => $almac_txt,
    'origen'      => $origen_bl,
    'destino'     => $destino_bl,
    'articulo'    => $art,
    'lote'        => $lote,
    'cantidad'    => $qty,

    // trazabilidad heredada (OC/Factura/Proyecto/Proveedor/Protocolo)
    'oc'          => $h['Fol_OEP'] ?? null,
    'factura'     => $h['Fact_Prov'] ?? null,
    'proveedor'   => $h['Proveedor'] ?? null,
    'proveedor_id'=> $proveedorId,
    'proyecto'    => $h['Proyecto'] ?? null,
    'protocolo'   => $h['ID_Protocolo'] ?? null,
    'consec'      => $h['Consec_protocolo'] ?? null,
    'tipo_doc'    => $h['tipo'] ?? null,

    // LPs
    'contenedor'  => ['id'=>$id_contenedor,'clave'=>$cont_clave,'lp'=>$cont_lp],
    'pallet'      => ['clave'=>$pallet_clave,'lp'=>$pallet_lp],
  ];

  $pdo->commit();
  jexit(true,'Movimiento registrado (Acomodo tipo 2)', $out);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  jexit(false,$e->getMessage(),[]);
}
