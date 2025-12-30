<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();

/* ========= Helpers ========= */
function jexit($arr){ echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }
function s($v){ $v = trim((string)$v); return $v==='' ? null : $v; }
function i0($v){ return ($v==='' || $v===null) ? 0 : (int)$v; }

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

/* ========= Backward-compat (acciones viejas) ========= */
if ($action === 'export_csv') $action = 'export';
if ($action === 'import_csv') $action = 'import';
if ($action === 'create')     $action = 'save';
if ($action === 'update')     $action = 'save';

/* ========= Router ========= */
try {

  /* ===== LIST ===== */
  if ($action === 'list') {
    $q = s($_GET['q'] ?? '');
    $show_inactivos = i0($_GET['show_inactivos'] ?? 0);

    $where = "deleted_at IS NULL";
    $params = [];

    if (!$show_inactivos) {
      $where .= " AND activo = 1";
    }
    if ($q) {
      $where .= " AND nombre LIKE :q";
      $params[':q'] = "%$q%";
    }

    $sql = "SELECT id_estado, nombre, semaforo, activo
            FROM c_activo_estado
            WHERE $where
            ORDER BY id_estado ASC";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    jexit([
      "success" => true,
      "data"    => $rows
    ]);
  }

  /* ===== GET ===== */
  if ($action === 'get') {
    $id = i0($_GET['id'] ?? $_GET['id_estado'] ?? 0);
    if ($id <= 0) jexit(["success"=>false, "error"=>"id inválido"]);

    $st = $pdo->prepare("SELECT id_estado, nombre, semaforo, activo
                         FROM c_activo_estado
                         WHERE id_estado=? AND deleted_at IS NULL");
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) jexit(["success"=>false, "error"=>"No encontrado"]);

    jexit(["success"=>true, "data"=>$row]);
  }

  /* ===== SAVE (create/update) ===== */
  if ($action === 'save') {

    // UI manda JSON; compat: si vienen POST form-data también lo aceptamos.
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    $src = is_array($json) ? $json : $_POST;

    $id       = isset($src['id_estado']) ? (int)$src['id_estado'] : 0;
    $nombre   = s($src['nombre'] ?? '');
    $semaforo = s($src['semaforo'] ?? 'VERDE') ?? 'VERDE';
    $activo   = isset($src['activo']) ? (int)$src['activo'] : 1;

    if (!$nombre) jexit(["success"=>false, "error"=>"Nombre es obligatorio"]);
    if (!in_array($semaforo, ['VERDE','AMARILLO','ROJO'], true)) $semaforo = 'VERDE';
    $activo = ($activo ? 1 : 0);

    if ($id > 0) {
      $sql = "UPDATE c_activo_estado
              SET nombre=:nombre, semaforo=:semaforo, activo=:activo, updated_at=NOW()
              WHERE id_estado=:id AND deleted_at IS NULL";
      $st = $pdo->prepare($sql);
      $st->execute([
        ':nombre'=>$nombre,
        ':semaforo'=>$semaforo,
        ':activo'=>$activo,
        ':id'=>$id
      ]);
      jexit(["success"=>true, "id"=>$id]);
    } else {
      $sql = "INSERT INTO c_activo_estado (nombre, semaforo, activo, created_at)
              VALUES (:nombre,:semaforo,:activo,NOW())";
      $st = $pdo->prepare($sql);
      $st->execute([
        ':nombre'=>$nombre,
        ':semaforo'=>$semaforo,
        ':activo'=>$activo
      ]);
      jexit(["success"=>true, "id"=>(int)$pdo->lastInsertId()]);
    }
  }

  /* ===== DELETE (soft) ===== */
  if ($action === 'delete') {
    // UI manda id por querystring, tu versión vieja mandaba id_estado por POST
    $id = i0($_GET['id'] ?? $_POST['id'] ?? $_POST['id_estado'] ?? 0);
    if ($id<=0) jexit(["success"=>false,"error"=>"id inválido"]);

    $st = $pdo->prepare("UPDATE c_activo_estado
                         SET deleted_at=NOW()
                         WHERE id_estado=? AND deleted_at IS NULL");
    $st->execute([$id]);

    jexit(["success"=>true]);
  }

  /* ===== EXPORT CSV ===== */
  if ($action === 'export') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=activo_estados_export.csv');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['id_estado','nombre','semaforo','activo']);

    $st = $pdo->query("SELECT id_estado, nombre, semaforo, activo
                       FROM c_activo_estado
                       WHERE deleted_at IS NULL
                       ORDER BY id_estado ASC");
    while($r = $st->fetch(PDO::FETCH_ASSOC)){
      fputcsv($out, [$r['id_estado'],$r['nombre'],$r['semaforo'],$r['activo']]);
    }
    fclose($out);
    exit;
  }

  /* ===== IMPORT CSV ===== */
  if ($action === 'import') {
    if (!isset($_FILES['file']) || $_FILES['file']['error']!==UPLOAD_ERR_OK) {
      jexit(["success"=>false,"error"=>"CSV no recibido"]);
    }

    $tmp = $_FILES['file']['tmp_name'];
    $fh = fopen($tmp,'r');
    if (!$fh) jexit(["success"=>false,"error"=>"No se pudo leer el CSV"]);

    $header = fgetcsv($fh);
    if (!$header) jexit(["success"=>false,"error"=>"CSV sin encabezado"]);

    $map = [];
    foreach($header as $i=>$h){ $map[strtolower(trim($h))] = $i; }

    foreach(['nombre','semaforo','activo'] as $req){
      if (!isset($map[$req])) jexit(["success"=>false,"error"=>"Falta columna '$req'"]);
    }

    $ok=0; $err=0;

    $pdo->beginTransaction();
    $ins = $pdo->prepare("INSERT INTO c_activo_estado (nombre,semaforo,activo,created_at)
                          VALUES (?,?,?,NOW())");
    $upd = $pdo->prepare("UPDATE c_activo_estado
                          SET semaforo=?, activo=?, updated_at=NOW()
                          WHERE nombre=? AND deleted_at IS NULL");

    while(($row = fgetcsv($fh)) !== false){
      $nombre   = s($row[$map['nombre']] ?? '');
      $semaforo = s($row[$map['semaforo']] ?? 'VERDE') ?? 'VERDE';
      $activo   = i0($row[$map['activo']] ?? 1) ? 1 : 0;

      if(!$nombre){ $err++; continue; }
      if (!in_array($semaforo, ['VERDE','AMARILLO','ROJO'], true)) $semaforo='VERDE';

      // Estrategia: si existe por nombre, actualiza; si no, inserta
      $upd->execute([$semaforo,$activo,$nombre]);
      if ($upd->rowCount() > 0) { $ok++; continue; }

      $ins->execute([$nombre,$semaforo,$activo]);
      $ok++;
    }

    fclose($fh);
    $pdo->commit();

    jexit(["success"=>true,"ok"=>$ok,"err"=>$err]);
  }

  jexit(["success"=>false,"error"=>"Acción no soportada: $action"]);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  jexit(["success"=>false,"error"=>$e->getMessage()]);
}
