<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

function jerr($msg, $extra = []) {
  echo json_encode(array_merge(['ok'=>0,'error'=>$msg], $extra), JSON_UNESCAPED_UNICODE);
  exit;
}
function s($v){ $v = trim((string)$v); return $v==='' ? null : $v; }
function i1($v){ return ($v===''||$v===null) ? 1 : (int)$v; }

try {

  if ($action === 'list') {
    $q = s($_GET['q'] ?? '');
    $sql = "SELECT id_propiedad, clave, nombre, activo
            FROM c_activo_propiedad
            WHERE deleted_at IS NULL ";
    $p = [];
    if ($q) { $sql .= " AND (clave LIKE ? OR nombre LIKE ?) "; $p[]="%$q%"; $p[]="%$q%"; }
    $sql .= " ORDER BY id_propiedad ASC";
    $rows = db_all($sql, $p);
    echo json_encode(['ok'=>1,'data'=>$rows], JSON_UNESCAPED_UNICODE);
    exit;
  }

  if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $row = db_one("SELECT id_propiedad, clave, nombre, activo
                   FROM c_activo_propiedad
                   WHERE id_propiedad=? AND deleted_at IS NULL", [$id]);
    if (!$row) jerr("No encontrado");
    echo json_encode(['ok'=>1,'data'=>$row], JSON_UNESCAPED_UNICODE);
    exit;
  }

  if ($action === 'save') {
    $id     = (int)($_POST['id_propiedad'] ?? 0);
    $clave  = strtoupper(trim((string)($_POST['clave'] ?? '')));
    $nombre = s($_POST['nombre'] ?? '');
    $activo = i1($_POST['activo'] ?? 1);

    if ($clave==='') jerr("Clave es obligatoria");
    if (!preg_match('/^[A-Z0-9_]+$/', $clave)) jerr("Clave inválida (solo A-Z,0-9,_)");
    if (!$nombre) jerr("Nombre es obligatorio");

    if ($id > 0) {
      dbq("UPDATE c_activo_propiedad
          SET clave=?, nombre=?, activo=?, updated_at=NOW()
          WHERE id_propiedad=? AND deleted_at IS NULL",
          [$clave,$nombre,$activo,$id]);
      echo json_encode(['ok'=>1,'msg'=>'Actualizado'], JSON_UNESCAPED_UNICODE);
      exit;
    } else {
      dbq("INSERT INTO c_activo_propiedad (clave,nombre,activo,created_at)
           VALUES (?,?,?,NOW())", [$clave,$nombre,$activo]);
      echo json_encode(['ok'=>1,'msg'=>'Creado','id'=>$pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);
      exit;
    }
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id<=0) jerr("ID inválido");
    dbq("UPDATE c_activo_propiedad SET deleted_at=NOW(), updated_at=NOW()
         WHERE id_propiedad=? AND deleted_at IS NULL", [$id]);
    echo json_encode(['ok'=>1,'msg'=>'Eliminado'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  if ($action === 'export_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="c_activo_propiedad.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['id_propiedad','clave','nombre','activo']);
    $rows = db_all("SELECT id_propiedad,clave,nombre,activo
                    FROM c_activo_propiedad WHERE deleted_at IS NULL
                    ORDER BY id_propiedad ASC");
    foreach($rows as $r){
      fputcsv($out, [$r['id_propiedad'],$r['clave'],$r['nombre'],$r['activo']]);
    }
    fclose($out);
    exit;
  }

  if ($action === 'import_csv') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) jerr("Archivo CSV inválido");
    $fh = fopen($_FILES['file']['tmp_name'], 'r');
    if (!$fh) jerr("No se pudo leer el archivo");
    $hdr = fgetcsv($fh);
    if (!$hdr) jerr("CSV vacío");
    $map = array_flip($hdr);

    $ok=0; $err=0; $errs=[];
    db_tx(function() use ($fh,$map,&$ok,&$err,&$errs){
      while(($row=fgetcsv($fh))!==false){
        $clave  = isset($map['clave']) ? strtoupper(trim((string)($row[$map['clave']] ?? ''))) : '';
        $nombre = isset($map['nombre']) ? trim((string)($row[$map['nombre']] ?? '')) : '';
        $activo = isset($map['activo']) ? (int)($row[$map['activo']] ?? 1) : 1;

        if ($clave===''){ $err++; $errs[]="Fila sin clave"; continue; }
        if (!preg_match('/^[A-Z0-9_]+$/', $clave)){ $err++; $errs[]="Clave inválida: $clave"; continue; }
        if ($nombre===''){ $err++; $errs[]="Fila $clave sin nombre"; continue; }

        // UPSERT por clave (debe existir UNIQUE uk_activo_propiedad_clave)
        dbq("INSERT INTO c_activo_propiedad (clave,nombre,activo,created_at)
             VALUES (?,?,?,NOW())
             ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), activo=VALUES(activo), updated_at=NOW(), deleted_at=NULL",
          [$clave,$nombre,$activo]);
        $ok++;
      }
    });

    echo json_encode(['ok'=>1,'total_ok'=>$ok,'total_err'=>$err,'errs'=>$errs], JSON_UNESCAPED_UNICODE);
    exit;
  }

  jerr("Acción no soportada: $action");

} catch (Throwable $e) {
  jerr("Error servidor", ['detalle'=>$e->getMessage()]);
}
