<?php
// public/api/control_patios/patios_planeador_api.php
require_once __DIR__ . '/../../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? 'calendar';

function jexit($ok, $msg='', $data=[]){
  echo json_encode(['ok'=>$ok,'msg'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE);
  exit;
}
function s($v){ $v=trim((string)$v); return $v===''?null:$v; }
function i0($v){ return ($v===''||$v===null)?0:(int)$v; }

try {

  /* ===============================================================
     CALENDAR
  =============================================================== */
  if ($action === 'calendar') {

    $cve_almac = i0($_GET['cve_almac'] ?? 0);
    $fecha     = s($_GET['fecha'] ?? date('Y-m-d'));
    if (!$cve_almac) jexit(false,'Falta cve_almac');

    $rampas = db_all("
        SELECT id,codigo,flujo_permitido,orden_visual
        FROM c_yms_posicion
        WHERE cve_almac=? AND tipo='RAMPA' AND activo=1
        ORDER BY IFNULL(orden_visual,999), codigo
    ", [$cve_almac]);

    $citas = db_all("
        SELECT c.id,c.folio,c.tipo_flujo,c.tipo_operacion,
               c.hora_inicio,c.hora_fin,
               c.rampa_posicion_id,c.estatus,c.canal,
               c.referencia,c.placa,c.operador,
               p.codigo AS rampa_codigo
        FROM th_yms_cita c
        LEFT JOIN c_yms_posicion p ON p.id=c.rampa_posicion_id
        WHERE c.cve_almac=? AND c.fecha=?
        ORDER BY c.hora_inicio, c.hora_fin
    ", [$cve_almac,$fecha]);

    jexit(true,'ok', ['rampas'=>$rampas,'citas'=>$citas]);
  }

  /* ===============================================================
     CREATE
  =============================================================== */
  if ($action === 'create') {

    $empresa_id = i0($_POST['empresa_id'] ?? 0);
    $cve_cia    = s($_POST['cve_cia'] ?? null);
    $cve_almac  = i0($_POST['cve_almac'] ?? 0);
    $tipo_flujo = s($_POST['tipo_flujo'] ?? '');
    $tipo_op    = s($_POST['tipo_operacion'] ?? null);
    $fecha      = s($_POST['fecha'] ?? '');
    $hini       = s($_POST['hora_inicio'] ?? '');
    $hfin       = s($_POST['hora_fin'] ?? '');
    $rampa_id   = i0($_POST['rampa_posicion_id'] ?? 0) ?: null;

    $canal      = s($_POST['canal'] ?? 'INTERNO');
    $proveedor  = i0($_POST['proveedor_id'] ?? 0) ?: null;
    $ref        = s($_POST['referencia'] ?? null);
    $placa      = s($_POST['placa'] ?? null);
    $operador   = s($_POST['operador'] ?? null);
    $tel        = s($_POST['telefono'] ?? null);

    if (!$empresa_id || !$cve_almac || !in_array($tipo_flujo,['IN','OUT'],true) || !$fecha || !$hini || !$hfin) {
      jexit(false,'Datos incompletos');
    }

    $folio = 'YMS'.date('YmdHis').'-'.random_int(100,999);

    // Validar traslape
    if ($rampa_id) {
      $over = db_val("
          SELECT COUNT(*)
          FROM th_yms_cita
          WHERE fecha=? AND rampa_posicion_id=?
            AND estatus NOT IN ('CANCELADA','FINALIZADA','NO_SHOW')
            AND NOT (hora_fin<=? OR hora_inicio>=?)
      ", [$fecha,$rampa_id,$hini,$hfin]);

      if ((int)$over > 0) {
        jexit(false,'Rampa ya ocupada en esa ventana');
      }
    }

    // Validar capacidad
    $cap = db_one("
        SELECT capacidad_total, capacidad_reservada
        FROM c_yms_capacidad_diaria
        WHERE cve_almac=? AND fecha=?
    ", [$cve_almac,$fecha]);

    if ($cap && (int)$cap['capacidad_reservada'] >= (int)$cap['capacidad_total']) {
      jexit(false,'Capacidad diaria saturada');
    }

    $estatus = ($canal==='PROVEEDOR') ? 'PROGRAMADA' : 'CONFIRMADA';

    db_tx(function() use (
        $folio,$empresa_id,$cve_cia,$cve_almac,$tipo_flujo,$tipo_op,
        $fecha,$hini,$hfin,$rampa_id,$estatus,$canal,$proveedor,
        $ref,$placa,$operador,$tel
    ){

        dbq("
            INSERT INTO th_yms_cita
            (folio,empresa_id,cve_cia,cve_almac,tipo_flujo,tipo_operacion,
             fecha,hora_inicio,hora_fin,rampa_posicion_id,
             estatus,canal,proveedor_id,referencia,placa,operador,telefono)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ", [
            $folio,$empresa_id,$cve_cia,$cve_almac,$tipo_flujo,$tipo_op,
            $fecha,$hini,$hfin,$rampa_id,
            $estatus,$canal,$proveedor,$ref,$placa,$operador,$tel
        ]);

        dbq("
            INSERT INTO c_yms_capacidad_diaria
            (cve_almac,fecha,capacidad_total,capacidad_reservada,capacidad_ocupada)
            VALUES (?,?,51,1,0)
            ON DUPLICATE KEY UPDATE capacidad_reservada=capacidad_reservada+1
        ", [$cve_almac,$fecha]);

    });

    jexit(true,'Cita creada', ['folio'=>$folio]);
  }

  /* ===============================================================
     APPROVE
  =============================================================== */
  if ($action === 'approve') {
    $cita_id = i0($_POST['cita_id'] ?? 0);
    if (!$cita_id) jexit(false,'Falta cita_id');

    dbq("UPDATE th_yms_cita SET estatus='CONFIRMADA', actualizado_el=NOW() WHERE id=?", [$cita_id]);

    jexit(true,'Aprobada');
  }

  /* ===============================================================
     CANCEL
  =============================================================== */
  if ($action === 'cancel') {
    $cita_id = i0($_POST['cita_id'] ?? 0);
    if (!$cita_id) jexit(false,'Falta cita_id');

    dbq("UPDATE th_yms_cita SET estatus='CANCELADA', actualizado_el=NOW() WHERE id=?", [$cita_id]);

    jexit(true,'Cancelada');
  }

  /* ===============================================================
     CHECKIN
  =============================================================== */
  if ($action === 'checkin') {

    $cita_id    = i0($_POST['cita_id'] ?? 0);
    $posicion_id = i0($_POST['posicion_id'] ?? 0);

    if (!$cita_id || !$posicion_id) jexit(false,'Falta cita_id/posicion_id');

    db_tx(function() use ($cita_id,$posicion_id){

        dbq("UPDATE th_yms_cita SET estatus='EN_PATIO', actualizado_el=NOW() WHERE id=?", [$cita_id]);

        dbq("INSERT INTO th_yms_visita (cita_id,estatus,posicion_actual_id)
             VALUES (?,?,?)", [$cita_id,'EN_PATIO',$posicion_id]);

        $visita_id = db_val("SELECT LAST_INSERT_ID()");

        dbq("INSERT INTO th_yms_movimiento (visita_id,posicion_id,nota)
             VALUES (?,?,?)", [$visita_id,$posicion_id,'CHECK-IN']);

        $c = db_one("SELECT cve_almac,fecha FROM th_yms_cita WHERE id=?", [$cita_id]);

        if ($c) {
            dbq("
                INSERT INTO c_yms_capacidad_diaria
                (cve_almac,fecha,capacidad_total,capacidad_reservada,capacidad_ocupada)
                VALUES (?,?,51,0,1)
                ON DUPLICATE KEY UPDATE capacidad_ocupada=capacidad_ocupada+1
            ", [$c['cve_almac'],$c['fecha']]);
        }

    });

    jexit(true,'Check-in OK');
  }

  /* ===============================================================
     MOVE
  =============================================================== */
  if ($action === 'move') {

    $visita_id   = i0($_POST['visita_id'] ?? 0);
    $posicion_id = i0($_POST['posicion_id'] ?? 0);
    $nota        = s($_POST['nota'] ?? null);

    if (!$visita_id || !$posicion_id) jexit(false,'Falta visita_id/posicion_id');

    db_tx(function() use ($visita_id,$posicion_id,$nota){

        dbq("UPDATE th_yms_movimiento SET fecha_salida=NOW()
             WHERE visita_id=? AND fecha_salida IS NULL", [$visita_id]);

        dbq("UPDATE th_yms_visita SET posicion_actual_id=? WHERE id=?",
            [$posicion_id,$visita_id]);

        dbq("INSERT INTO th_yms_movimiento (visita_id,posicion_id,nota)
             VALUES (?,?,?)", [$visita_id,$posicion_id,$nota]);

        $tipo = db_val("SELECT tipo FROM c_yms_posicion WHERE id=?", [$posicion_id]);

        if ($tipo === 'RAMPA') {
            dbq("UPDATE th_yms_visita SET estatus='EN_RAMPA' WHERE id=?", [$visita_id]);
            dbq("
                UPDATE th_yms_cita c
                JOIN th_yms_visita v ON v.cita_id=c.id
                SET c.estatus='EN_RAMPA', c.rampa_posicion_id=?
                WHERE v.id=?
            ", [$posicion_id,$visita_id]);
        }

    });

    jexit(true,'Movimiento OK');
  }

  /* ===============================================================
     CHECKOUT
  =============================================================== */
  if ($action === 'checkout') {

    $visita_id = i0($_POST['visita_id'] ?? 0);
    if (!$visita_id) jexit(false,'Falta visita_id');

    db_tx(function() use ($visita_id){

        dbq("UPDATE th_yms_movimiento SET fecha_salida=NOW()
             WHERE visita_id=? AND fecha_salida IS NULL", [$visita_id]);

        dbq("UPDATE th_yms_visita SET estatus='CERRADA', checkout_el=NOW()
             WHERE id=?", [$visita_id]);

        dbq("
            UPDATE th_yms_cita c
            JOIN th_yms_visita v ON v.cita_id=c.id
            SET c.estatus='FINALIZADA', c.actualizado_el=NOW()
            WHERE v.id=?
        ", [$visita_id]);

    });

    jexit(true,'Salida OK');
  }

  jexit(false,'Acción no soportada');

} catch (Throwable $e) {
  jexit(false, 'Error: '.$e->getMessage());
}
