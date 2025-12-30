<?php
// public/api/activo_condicion_api.php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo    = db_pdo();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

$table = "c_activo_condicion";
$pk    = "id_condicion";

function jok($data = []) {
  echo json_encode(array_merge(['success' => true], $data), JSON_UNESCAPED_UNICODE);
  exit;
}
function jerr($msg, $extra = []) {
  echo json_encode(array_merge(['success' => false, 'error' => $msg], $extra), JSON_UNESCAPED_UNICODE);
  exit;
}
function clean($v) {
  $v = trim((string)$v);
  return $v === '' ? null : $v;
}
function i01($v) {
  if ($v === '' || $v === null) return 0;
  return (int)$v ? 1 : 0;
}
function csv_out($filename, $csv) {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="'.$filename.'"');
  echo $csv;
  exit;
}

try {

  if ($action === 'kpis') {
    $total  = (int)$pdo->query("SELECT COUNT(*) FROM $table WHERE deleted_at IS NULL")->fetchColumn();
    $activos= (int)$pdo->query("SELECT COUNT(*) FROM $table WHERE deleted_at IS NULL AND activo=1")->fetchColumn();
    jok(['total' => $total, 'activos' => $activos]);
  }

  if ($action === 'list') {
    $q   = clean($_GET['q'] ?? '');
    $sql = "SELECT id_condicion, clave, nombre, activo
            FROM $table
            WHERE deleted_at IS NULL";
    $p = [];
    if ($q) {
      $sql .= " AND (clave LIKE :q OR nombre LIKE :q)";
      $p[':q'] = "%$q%";
    }
    $sql .= " ORDER BY nombre";

    $st = $pdo->prepare($sql);
    $st->execute($p);
    jok(['rows' => $st->fetchAll(PDO::FETCH_ASSOC)]);
  }

  if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jerr("ID inválido");

    $st = $pdo->prepare("SELECT id_condicion, clave, nombre, activo
                         FROM $table
                         WHERE $pk = :id AND deleted_at IS NULL");
    $st->execute([':id' => $id]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    if (!$r) jerr("No encontrado");
    jok(['data' => $r]);
  }

  if ($action === 'save') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!is_array($data)) jerr("JSON inválido");

    $id     = (int)($data['id_condicion'] ?? 0);
    $clave  = clean($data['clave'] ?? '');
    $nombre = clean($data['nombre'] ?? '');
    $activo = (int)($data['activo'] ?? 1) ? 1 : 0;

    if (!$clave || !$nombre) jerr("Obligatorios: clave, nombre");

    // Normalización corporativa: claves en MAYÚSCULAS sin espacios
    $clave = strtoupper(preg_replace('/\s+/', '', $clave));

    if ($id > 0) {
      $st = $pdo->prepare("UPDATE $table
                           SET clave=:clave, nombre=:nombre, activo=:activo, updated_at=NOW()
                           WHERE $pk=:id");
      $st->execute([
        ':clave' => $clave,
        ':nombre'=> $nombre,
        ':activo'=> $activo,
        ':id'    => $id
      ]);
      jok(['id' => $id]);
    } else {
      $st = $pdo->prepare("INSERT INTO $table (clave, nombre, activo, created_at)
                           VALUES (:clave, :nombre, :activo, NOW())");
      $st->execute([
        ':clave' => $clave,
        ':nombre'=> $nombre,
        ':activo'=> $activo
      ]);
      jok(['id' => (int)$pdo->lastInsertId()]);
    }
  }

  if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    if (!$id) jerr("ID inválido");

    $st = $pdo->prepare("UPDATE $table
                         SET deleted_at=NOW(), updated_at=NOW(), activo=0
                         WHERE $pk=:id");
    $st->execute([':id' => $id]);
    jok();
  }

  if ($action === 'layout') {
    csv_out("activo_condicion_layout.csv", "clave,nombre,activo\n");
  }

  if ($action === 'export') {
    $st = $pdo->query("SELECT clave, nombre, activo
                       FROM $table
                       WHERE deleted_at IS NULL
                       ORDER BY nombre");
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $csv = "clave,nombre,activo\n";
    foreach ($rows as $r) {
      $csv .= sprintf("%s,%s,%s\n",
        str_replace([",","\n","\r"],[" "," "," "], (string)$r['clave']),
        str_replace([",","\n","\r"],[" "," "," "], (string)$r['nombre']),
        (string)($r['activo'] ?? 1)
      );
    }
    csv_out("activo_condicion_export.csv", $csv);
  }

  if ($action === 'import') {
    if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) jerr("Archivo requerido");

    $txt   = file_get_contents($_FILES['file']['tmp_name']);
    $lines = preg_split("/\r\n|\n|\r/", $txt);
    if (!$lines || count($lines) < 2) jerr("CSV vacío");

    $head = str_getcsv(array_shift($lines));
    $map  = array_flip($head);

    foreach (['clave','nombre'] as $req) {
      if (!isset($map[$req])) jerr("Falta columna $req");
    }

    $ok=0; $err=0; $errs=[];
    $pdo->beginTransaction();

    // Nota: tu tabla trae UNIQUE en nombre (uk_condicion). Con eso el upsert funciona por nombre.
    $up = $pdo->prepare("INSERT INTO $table (clave, nombre, activo, created_at)
                         VALUES (:clave, :nombre, :activo, NOW())
                         ON DUPLICATE KEY UPDATE
                           clave=VALUES(clave),
                           activo=VALUES(activo),
                           updated_at=NOW(),
                           deleted_at=NULL");

    $n=1;
    foreach ($lines as $ln) {
      $n++;
      $ln = trim($ln);
      if ($ln==='') continue;

      $c = str_getcsv($ln);
      $clave  = clean($c[$map['clave']] ?? '');
      $nombre = clean($c[$map['nombre']] ?? '');
      $activo = i01($c[$map['activo']] ?? 1);

      if (!$clave || !$nombre) { $err++; $errs[]="L$n: clave/nombre requerido"; continue; }

      $clave = strtoupper(preg_replace('/\s+/', '', $clave));

      try {
        $up->execute([':clave'=>$clave, ':nombre'=>$nombre, ':activo'=>$activo]);
        $ok++;
      } catch (Exception $e) {
        $err++; $errs[]="L$n: ".$e->getMessage();
      }
    }

    $pdo->commit();
    jok(['ok'=>$ok,'err'=>$err,'errs'=>$errs]);
  }

  jerr("Acción no soportada: $action");

} catch (Exception $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  jerr("Error servidor", ['detalle' => $e->getMessage()]);
}
