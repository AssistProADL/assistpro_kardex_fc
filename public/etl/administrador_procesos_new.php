<?php
// AssistPro ETL - Administrador de Procesos
// versión compatible con MySQL 5.7+ y 8.0+

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/../app/db.php';
$pdo = db();

function id_safe($s) {
    return preg_replace('/[^A-Za-z0-9_]+/', '_', trim($s));
}

function qAll($pdo, $sql, $p = []) {
    $st = $pdo->prepare($sql);
    $st->execute($p);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function qOne($pdo, $sql, $p = []) {
    $st = $pdo->prepare($sql);
    $st->execute($p);
    return $st->fetch(PDO::FETCH_ASSOC);
}

// ==== Esquema base =================================================================
try {
    // Procesos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS etl_processes (
          id INT AUTO_INCREMENT PRIMARY KEY,
          name VARCHAR(191) NOT NULL,
          description TEXT NULL,
          group_name VARCHAR(191) NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");

    // Relación proceso - objetos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS etl_process_objects (
          id INT AUTO_INCREMENT PRIMARY KEY,
          process_id INT NOT NULL,
          alias VARCHAR(100) NOT NULL,
          remote_db VARCHAR(191) NOT NULL,
          object_name VARCHAR(191) NOT NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          UNIQUE KEY uq_proc_obj (process_id, alias, remote_db, object_name),
          CONSTRAINT fk_proc_obj_process
            FOREIGN KEY (process_id) REFERENCES etl_processes(id)
            ON DELETE CASCADE
        ) ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");

    // Documentos anexos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS etl_process_docs (
          id INT AUTO_INCREMENT PRIMARY KEY,
          process_id INT NOT NULL,
          title VARCHAR(191) NOT NULL,
          url VARCHAR(500) NULL,
          file_name VARCHAR(255) NULL,
          file_path VARCHAR(500) NULL,
          mime_type VARCHAR(191) NULL,
          file_size BIGINT NULL,
          notes TEXT NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          CONSTRAINT fk_proc_docs_process
            FOREIGN KEY (process_id) REFERENCES etl_processes(id)
            ON DELETE CASCADE
        ) ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");

    // Compatibilidad para MySQL <8: revisar columnas
    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
    if ($dbName) {
        $sqlCol = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                   WHERE TABLE_SCHEMA = :db
                     AND TABLE_NAME   = :tbl
                     AND COLUMN_NAME  = :col";
        $chk = $pdo->prepare($sqlCol);

        $colCheck = [
            ['etl_processes', 'group_name', "ALTER TABLE etl_processes ADD COLUMN group_name VARCHAR(191) NULL"],
            ['etl_process_docs', 'file_name', "ALTER TABLE etl_process_docs ADD COLUMN file_name VARCHAR(255) NULL"],
            ['etl_process_docs', 'file_path', "ALTER TABLE etl_process_docs ADD COLUMN file_path VARCHAR(500) NULL"],
            ['etl_process_docs', 'mime_type', "ALTER TABLE etl_process_docs ADD COLUMN mime_type VARCHAR(191) NULL"],
            ['etl_process_docs', 'file_size', "ALTER TABLE etl_process_docs ADD COLUMN file_size BIGINT NULL"],
        ];

        foreach ($colCheck as $c) {
            [$tbl, $col, $sqlAdd] = $c;
            $chk->execute([':db' => $dbName, ':tbl' => $tbl, ':col' => $col]);
            if (!$chk->fetchColumn()) $pdo->exec($sqlAdd);
        }
    }
} catch (Throwable $e) {
    die("Error inicializando catálogo de procesos: " . $e->getMessage());
}

// ==== Función para generar TXT =====================================================
function etl_generate_process_txt($pdo, $pid, $dirBase = 'etl/template') {
    $proc = qOne($pdo, "SELECT * FROM etl_processes WHERE id=?", [$pid]);
    if (!$proc) return false;
    $objs = qAll($pdo, "SELECT * FROM etl_process_objects WHERE process_id=?", [$pid]);
    $docs = qAll($pdo, "SELECT * FROM etl_process_docs WHERE process_id=?", [$pid]);

    $txt  = "ASSISTPRO ETL - DOCUMENTACIÓN DE PROCESO\n";
    $txt .= "===========================================\n\n";
    $txt .= "Proceso: {$proc['name']}\n";
    $txt .= "Grupo: " . ($proc['group_name'] ?: '(Sin grupo)') . "\n";
    $txt .= "Descripción: " . ($proc['description'] ?: '(Sin descripción)') . "\n";
    $txt .= "Creado: {$proc['created_at']}\n\n";
    $txt .= "TABLAS MIGRADAS ASOCIADAS\n";
    $txt .= "--------------------------\n";
    foreach ($objs as $o) {
        $txt .= "- {$o['object_name']} (BD origen: {$o['remote_db']}, Alias: {$o['alias']})\n";
    }

    $txt .= "\nDOCUMENTOS ANEXOS\n";
    $txt .= "-----------------\n";
    foreach ($docs as $d) {
        $txt .= "- {$d['title']}";
        if ($d['file_path']) $txt .= " [{$d['file_path']}]";
        if ($d['url']) $txt .= " ({$d['url']})";
        $txt .= "\n";
    }

    $txt .= "\nNotas:\n";
    foreach ($docs as $d) {
        if ($d['notes']) $txt .= "* {$d['notes']}\n";
    }

    // Directorio base sin repetir "etl/"
    $dirBaseSafe = preg_replace('#^/?(etl/)?#', 'etl/', trim($dirBase, '/'));
    $dirProc = __DIR__ . '/' . $dirBaseSafe . '/' . id_safe($proc['name']);
    if (!is_dir($dirProc)) @mkdir($dirProc, 0775, true);

    $filename = 'proceso_' . id_safe($proc['name']) . '.txt';
    $filePath = $dirProc . '/' . $filename;

    if (file_put_contents($filePath, $txt) === false) {
        throw new Exception("No se pudo escribir el archivo {$filePath}");
    }

    return [
        'txt' => $txt,
        'path' => $dirBaseSafe . '/' . id_safe($proc['name']) . '/' . $filename
    ];
}

// ==== Guardar documento + TXT ======================================================
$msg = $err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['doc_action'] ?? '') === 'add_doc') {
    try {
        $pid   = (int)($_POST['process_id'] ?? 0);
        $title = trim($_POST['doc_title'] ?? '');
        $url   = trim($_POST['doc_url'] ?? '');
        $notes = trim($_POST['doc_notes'] ?? '');
        $dirBase = trim($_POST['doc_dir'] ?? 'etl/template');
        if (!$pid || !$title) throw new Exception("Proceso o título no especificado.");

        $fileName = $filePath = $mimeType = null;
        $fileSize = 0;

        // Subida de archivo
        if (isset($_FILES['doc_file']) && $_FILES['doc_file']['name'] !== '') {
            $errC = (int)$_FILES['doc_file']['error'];
            if ($errC !== UPLOAD_ERR_OK) throw new Exception("Error de subida (código $errC)");
            $original = $_FILES['doc_file']['name'];
            $tmp      = $_FILES['doc_file']['tmp_name'];
            $size     = (int)$_FILES['doc_file']['size'];
            $mime     = $_FILES['doc_file']['type'] ?? null;
            $ext      = strtolower(pathinfo($original, PATHINFO_EXTENSION));
            $allowed  = ['pdf','doc','docx','xls','xlsx','csv','png','jpg','jpeg'];
            if (!in_array($ext, $allowed)) throw new Exception("Extensión no permitida: .$ext");

            $dirBaseSafe = preg_replace('#^/?(etl/)?#', 'etl/', trim($dirBase, '/'));
            $dirProc = __DIR__ . '/' . $dirBaseSafe . '/' . id_safe(qOne($pdo,"SELECT name FROM etl_processes WHERE id=?",[$pid])['name']);
            if (!is_dir($dirProc)) @mkdir($dirProc, 0775, true);

            $stored = id_safe(pathinfo($original, PATHINFO_FILENAME)) . '_' . date('Ymd_His') . '.' . $ext;
            $dest   = $dirProc . '/' . $stored;
            if (!move_uploaded_file($tmp, $dest)) throw new Exception("No se pudo mover archivo a $dest");

            $fileName = $original;
            $filePath = $dirBaseSafe . '/' . id_safe(qOne($pdo,"SELECT name FROM etl_processes WHERE id=?",[$pid])['name']) . '/' . $stored;
            $mimeType = $mime;
            $fileSize = $size;
        }

        // Insertar documento
        $ins = $pdo->prepare("
            INSERT INTO etl_process_docs (process_id, title, url, file_name, file_path, mime_type, file_size, notes, created_at)
            VALUES (:pid, :title, :url, :fn, :fp, :mt, :fs, :nt, NOW())
        ");
        $ins->execute([
            ':pid' => $pid, ':title' => $title, ':url' => $url,
            ':fn' => $fileName, ':fp' => $filePath,
            ':mt' => $mimeType, ':fs' => $fileSize, ':nt' => $notes
        ]);

        // Regenerar TXT (solo uno por proceso)
        $gen = etl_generate_process_txt($pdo, $pid, $dirBase);
        $msg = "Documento agregado y TXT actualizado para el proceso. Archivo: <a href=\"{$gen['path']}\" target=\"_blank\">abrir / descargar TXT</a>";

    } catch (Throwable $e) {
        $err = "Error: " . $e->getMessage();
    }
}

// ==== Consultar procesos ============================================================
$procs = qAll($pdo, "SELECT * FROM etl_processes ORDER BY id DESC");
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Administrador de procesos ETL</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
body{background:#f7f9fc;font-size:13px}
.card{box-shadow:0 0 6px rgba(0,0,0,.08)}
.table-sm td,.table-sm th{padding:.3rem}
</style>
</head>
<body>
<div class="container my-3">
  <h5 class="mb-3 text-primary"><i class="bi bi-diagram-3"></i> ASSISTPRO ETL - Administrador de procesos</h5>

  <?php if($msg):?><div class="alert alert-success"><?=$msg?></div><?php endif;?>
  <?php if($err):?><div class="alert alert-danger"><?=$err?></div><?php endif;?>

  <div class="card p-2">
    <table class="table table-sm table-striped align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Consec.</th><th>Fecha</th><th>Proceso</th><th>Descripción</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($procs as $p): ?>
        <tr>
          <td><?=$p['id']?></td>
          <td><?=$p['created_at']?></td>
          <td><?=$p['name']?></td>
          <td><?=$p['description']?></td>
        </tr>
        <?php endforeach;?>
      </tbody>
    </table>
  </div>

</div>
</body>
</html>
  
