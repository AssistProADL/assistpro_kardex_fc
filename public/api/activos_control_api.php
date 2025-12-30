<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

function out(bool $ok, array $extra=[]): void {
  echo json_encode(array_merge(['ok'=>$ok?1:0], $extra), JSON_UNESCAPED_UNICODE);
  exit;
}
function s($v): ?string { $v=trim((string)$v); return $v===''?null:$v; }
function i0($v): int { return ($v===''||$v===null)?0:(int)$v; }

/* =========================================
   DESTINATARIOS (combo)
   - regresa id + clave + nombre (para mostrar CLAVE | NOMBRE)
========================================= */
if($action==='destinatarios'){
  try{
    $sql = "
      SELECT
        d.id_destinatario,
        COALESCE(NULLIF(d.clave_destinatario,''), NULLIF(d.Cve_Clte,''), CONCAT('DEST-',d.id_destinatario)) AS clave,
        d.razonsocial
      FROM c_destinatarios d
      WHERE IFNULL(d.Activo,'1') IN ('1',1)
      ORDER BY d.razonsocial
      LIMIT 2000
    ";
    $rows = db_all($sql);
    out(true, ['rows'=>$rows]);
  }catch(Throwable $e){
    out(false, ['error'=>'Error consultando destinatarios', 'detalle'=>$e->getMessage()]);
  }
}

/* =========================================
   KPIS
========================================= */
if($action==='kpis'){
  try{
    $total = (int)$pdo->query("SELECT COUNT(*) FROM c_activos WHERE deleted_at IS NULL AND activo=1")->fetchColumn();
    $asignados = (int)$pdo->query("SELECT COUNT(DISTINCT id_activo) FROM t_activo_ubicacion WHERE vigencia=1 AND deleted_at IS NULL")->fetchColumn();
    $disponibles = max(0, $total - $asignados);
    $alertas = (int)$pdo->query("SELECT COUNT(*) FROM c_activos WHERE deleted_at IS NULL AND activo=1 AND IFNULL(estatus,'ACTIVO')<>'ACTIVO'")->fetchColumn();

    out(true, compact('total','asignados','disponibles','alertas'));
  }catch(Throwable $e){
    out(false, ['error'=>'Error KPIs', 'detalle'=>$e->getMessage()]);
  }
}

/* =========================================
   LIST (grid)  -> disponibles + asignados
   Filtros:
     q, estatus (DISPONIBLE/ASIGNADO), id_destinatario
========================================= */
if($action==='list'){
  $q = s($_GET['q'] ?? null);
  $estatus = s($_GET['estatus'] ?? null); // DISPONIBLE | ASIGNADO | null
  $id_destinatario = s($_GET['id_destinatario'] ?? null); // puede venir num o clave
  $solo_activos = i0($_GET['solo_activos'] ?? 1);

  $where = "a.deleted_at IS NULL";
  $params = [];

  if($solo_activos===1){
    $where .= " AND a.activo=1";
  }

  if($q){
    $where .= " AND (
      a.num_serie LIKE :q OR a.marca LIKE :q OR a.modelo LIKE :q OR a.descripcion LIKE :q OR a.tipo_activo LIKE :q
    )";
    $params[':q']="%$q%";
  }

  if($estatus==='DISPONIBLE'){
    $where .= " AND u.id IS NULL";
  }elseif($estatus==='ASIGNADO'){
    $where .= " AND u.id IS NOT NULL";
  }

  // filtro destinatario: si viene num => por id_destinatario, si viene texto => por clave_destinatario/Cve_Clte
  if($id_destinatario){
    if(ctype_digit($id_destinatario)){
      $where .= " AND u.id_destinatario = :id_dest";
      $params[':id_dest'] = (int)$id_destinatario;
    }else{
      $where .= " AND (d.clave_destinatario=:c OR d.Cve_Clte=:c)";
      $params[':c'] = $id_destinatario;
    }
  }

  $sql = "
    SELECT
      a.id_activo,
      a.num_serie AS clave_activo,
      a.tipo_activo,
      a.marca,
      a.modelo,
      a.descripcion,
      a.estatus AS estado_activo,

      alm.clave  AS almacen_clave,
      alm.nombre AS almacen_nombre,

      u.id AS id_asignacion,
      u.fecha_desde AS fecha_inicio,
      u.fecha_hasta,

      d.id_destinatario,
      COALESCE(NULLIF(d.clave_destinatario,''), NULLIF(d.Cve_Clte,''), '') AS destinatario_clave,
      d.razonsocial AS destinatario_nombre,

      CASE WHEN u.id IS NULL THEN 'DISPONIBLE' ELSE 'ASIGNADO' END AS estatus_asignacion
    FROM c_activos a
    LEFT JOIN c_almacenp alm ON alm.id = a.id_almacen
    LEFT JOIN t_activo_ubicacion u
      ON u.id_activo = a.id_activo
     AND u.vigencia = 1
     AND u.deleted_at IS NULL
    LEFT JOIN c_destinatarios d
      ON d.id_destinatario = u.id_destinatario
    WHERE $where
    ORDER BY a.id_activo DESC
    LIMIT 1000
  ";

  try{
    $st=$pdo->prepare($sql);
    $st->execute($params);
    out(true, ['rows'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
  }catch(Throwable $e){
    out(false, ['error'=>'Error listando', 'detalle'=>$e->getMessage()]);
  }
}

/* =========================================
   ASIGNAR
   Regla negocio: 1 vigente por activo
   Guarda en: t_activo_ubicacion
========================================= */
if($action==='asignar'){
  try{
    $id_activo = i0($_POST['id_activo'] ?? 0);
    $id_destinatario_raw = s($_POST['id_destinatario'] ?? null); // puede venir id o clave
    $obs = s($_POST['observaciones'] ?? null);

    if($id_activo<=0) out(false, ['error'=>'id_activo requerido']);
    if(!$id_destinatario_raw) out(false, ['error'=>'Destinatario requerido']);

    // Resolver destinatario
    $id_destinatario = 0;
    if(ctype_digit($id_destinatario_raw)){
      $id_destinatario = (int)$id_destinatario_raw;
    }else{
      $row = db_one("
        SELECT id_destinatario
        FROM c_destinatarios
        WHERE (clave_destinatario=:c OR Cve_Clte=:c)
        LIMIT 1
      ", [':c'=>$id_destinatario_raw]);
      $id_destinatario = (int)($row['id_destinatario'] ?? 0);
    }
    if($id_destinatario<=0) out(false, ['error'=>'Destinatario no válido (no se pudo resolver)']);

    // datos del activo (cve_cia / almacen)
    $a = db_one("
      SELECT id_activo, id_compania, id_almacen
      FROM c_activos
      WHERE id_activo=:id AND deleted_at IS NULL
      LIMIT 1
    ", [':id'=>$id_activo]);
    if(!$a) out(false, ['error'=>'Activo no existe']);

    $cve_cia = (int)$a['id_compania'];
    $id_almacenp = (int)$a['id_almacen'];

    // transacción: cerrar vigente + insertar nuevo
    $pdo->beginTransaction();

    // cerrar vigente (si existe)
    $st = $pdo->prepare("
      UPDATE t_activo_ubicacion
      SET vigencia=0, fecha_hasta=NOW(), updated_at=NOW()
      WHERE id_activo=? AND vigencia=1 AND deleted_at IS NULL
    ");
    $st->execute([$id_activo]);

    // insertar nueva asignación
    $st = $pdo->prepare("
      INSERT INTO t_activo_ubicacion
        (id_activo, cve_cia, id_almacenp, id_cliente, id_destinatario, latitud, longitud, vigencia, fecha_desde, created_at)
      VALUES
        (?, ?, ?, NULL, ?, NULL, NULL, 1, NOW(), NOW())
    ");
    $st->execute([$id_activo, $cve_cia, $id_almacenp, $id_destinatario]);

    $new_id = (int)$pdo->lastInsertId();

    // guardar observaciones en t_activo_tenencia si existe (opcional, sin romper)
    // si no quieres esto, bórralo.
    try{
      // tabla opcional (si no existe, ignora)
      $pdo->query("SELECT 1 FROM t_activo_tenencia LIMIT 1");
      if($obs){
        $st = $pdo->prepare("
          INSERT INTO t_activo_tenencia (id_activo, id_destinatario, observaciones, created_at)
          VALUES (?, ?, ?, NOW())
        ");
        $st->execute([$id_activo, $id_destinatario, $obs]);
      }
    }catch(Throwable $e){ /* noop */ }

    $pdo->commit();

    out(true, ['id_asignacion'=>$new_id]);
  }catch(Throwable $e){
    if($pdo->inTransaction()) $pdo->rollBack();
    out(false, ['error'=>'Error asignando', 'detalle'=>$e->getMessage()]);
  }
}

/* =========================================
   DESASIGNAR (cierra vigencia)
========================================= */
if($action==='desasignar'){
  try{
    $id_activo = i0($_POST['id_activo'] ?? 0);
    if($id_activo<=0) out(false, ['error'=>'id_activo requerido']);

    $st = $pdo->prepare("
      UPDATE t_activo_ubicacion
      SET vigencia=0, fecha_hasta=NOW(), updated_at=NOW()
      WHERE id_activo=? AND vigencia=1 AND deleted_at IS NULL
    ");
    $st->execute([$id_activo]);

    out(true, ['msg'=>'Desasignado']);
  }catch(Throwable $e){
    out(false, ['error'=>'Error desasignando', 'detalle'=>$e->getMessage()]);
  }
}

out(false, ['error'=>'Acción no soportada']);
