<?php
// /public/administrador_procesos.php
// Administrador de procesos funcionales AssistPro ETL

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/../app/db.php';
$pdo = db();

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

    // Relación proceso - objetos (origen/destino)
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

    // Documentos extra
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

    // Compatibilidad MySQL 5.x: asegurar columnas sin usar IF NOT EXISTS
    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
    if ($dbName) {
        $colSql = "SELECT COUNT(*)
                   FROM INFORMATION_SCHEMA.COLUMNS
                   WHERE TABLE_SCHEMA = :db
                     AND TABLE_NAME   = :tbl
                     AND COLUMN_NAME  = :col";
        $chk = $pdo->prepare($colSql);

        // etl_processes.group_name
        $chk->execute([
            ':db'  => $dbName,
            ':tbl' => 'etl_processes',
            ':col' => 'group_name'
        ]);
        if (!$chk->fetchColumn()) {
            $pdo->exec("ALTER TABLE etl_processes ADD COLUMN group_name VARCHAR(191) NULL");
        }

        // etl_process_docs.file_name
        $chk->execute([
            ':db'  => $dbName,
            ':tbl' => 'etl_process_docs',
            ':col' => 'file_name'
        ]);
        if (!$chk->fetchColumn()) {
            $pdo->exec("ALTER TABLE etl_process_docs ADD COLUMN file_name VARCHAR(255) NULL");
        }

        // etl_process_docs.file_path
        $chk->execute([
            ':db'  => $dbName,
            ':tbl' => 'etl_process_docs',
            ':col' => 'file_path'
        ]);
        if (!$chk->fetchColumn()) {
            $pdo->exec("ALTER TABLE etl_process_docs ADD COLUMN file_path VARCHAR(500) NULL");
        }

        // etl_process_docs.mime_type
        $chk->execute([
            ':db'  => $dbName,
            ':tbl' => 'etl_process_docs',
            ':col' => 'mime_type'
        ]);
        if (!$chk->fetchColumn()) {
            $pdo->exec("ALTER TABLE etl_process_docs ADD COLUMN mime_type VARCHAR(191) NULL");
        }

        // etl_process_docs.file_size
        $chk->execute([
            ':db'  => $dbName,
            ':tbl' => 'etl_process_docs',
            ':col' => 'file_size'
        ]);
        if (!$chk->fetchColumn()) {
            $pdo->exec("ALTER TABLE etl_process_docs ADD COLUMN file_size BIGINT NULL");
        }
    }

} catch (Throwable $e) {
    die("Error inicializando catálogo de procesos: " . $e->getMessage());
}

// Helpers
function qAll($pdo, $sql, $params = []) {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}
function qOne($pdo, $sql, $params = []) {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetch(PDO::FETCH_ASSOC);
}
function id_safe($s) {
    return preg_replace('~[^a-zA-Z0-9_]+~', '_', $s);
}
function short_text($s, $max = 80) {
    $s = trim((string)$s);
    if ($s === '') return '';
    if (function_exists('mb_strlen')) {
        if (mb_strlen($s, 'UTF-8') <= $max) return $s;
        return mb_substr($s, 0, $max - 3, 'UTF-8') . '...';
    } else {
        if (strlen($s) <= $max) return $s;
        return substr($s, 0, $max - 3) . '...';
    }
}

// ==== Helper para generar TXT de un proceso =======================================
function etl_generate_process_txt(PDO $pdo, int $processId, ?string $dirParam, ?string &$relativePathOut, ?string &$errorOut): bool
{
    $relativePathOut = null;
    $errorOut        = null;

    try {
        $proc = qOne($pdo, "SELECT * FROM etl_processes WHERE id=:id", [':id'=>$processId]);
        if (!$proc) {
            $errorOut = "Proceso no encontrado.";
            return false;
        }

        // Carpeta base RELATIVA a /public
        $dirParam = trim((string)$dirParam);
        if ($dirParam === '') {
            $dirParam = 'etl';
        }
        $dirBaseSafe = preg_replace('~[^a-zA-Z0-9_/\-]+~', '_', $dirParam);
        $procDir     = id_safe($proc['name']);

        // Objetos asociados
        $objs = qAll(
            $pdo,
            "SELECT po.alias, po.remote_db, po.object_name,
                    m.dest_table,
                    m.comment, m.procesos,
                    m.last_action_at, m.last_action_type, m.last_action_rows
             FROM etl_process_objects po
             LEFT JOIN etl_object_meta m
               ON  m.alias      = po.alias
               AND m.remote_db  = po.remote_db
               AND m.object_name= po.object_name
             WHERE po.process_id = :id
             ORDER BY COALESCE(m.dest_table, po.object_name)",
            [':id'=>$processId]
        );

        // Documentos anexos
        $docs = qAll(
            $pdo,
            "SELECT title, url, file_name, file_path, mime_type, file_size, notes, created_at
             FROM etl_process_docs
             WHERE process_id = :id
             ORDER BY created_at",
            [':id'=>$processId]
        );

        // Schema local
        $dbRow = qOne($pdo, "SELECT DATABASE() AS db");
        $localSchema = $dbRow['db'] ?? null;

        $line = str_repeat('=', 80);
        $sub  = str_repeat('-', 80);

        $txt  = "{$line}\n";
        $txt .= "ASSISTPRO ETL  |  DOCUMENTO DE PROCESO\n";
        $txt .= "{$line}\n\n";

        $txt .= "1. INFORMACIÓN GENERAL\n";
        $txt .= "{$sub}\n";
        $txt .= "ID Proceso   : {$proc['id']}\n";
        $txt .= "Nombre       : {$proc['name']}\n";
        $txt .= "Grupo        : ".($proc['group_name'] ?: '(sin grupo)')."\n";
        $txt .= "Descripción  : ".($proc['description'] !== '' ? $proc['description'] : '(sin descripción)')."\n";
        $txt .= "Creado       : {$proc['created_at']}\n\n";

        $txt .= "2. TABLAS / VISTAS ASOCIADAS\n";
        $txt .= "{$sub}\n\n";

        if ($objs) {
            $nTabla = 1;
            foreach ($objs as $o) {
                $dest = $o['dest_table'] ?: '(sin tabla destino definida aún)';
                $comment  = $o['comment']  ? preg_replace("/\r?\n/", " ", $o['comment'])  : '(sin comentario)';
                $procs    = $o['procesos'] ? preg_replace("/\r?\n/", " ", $o['procesos']) : '(sin listado adicional)';
                $lastInfo = $o['last_action_at']
                    ? "{$o['last_action_at']} ({$o['last_action_type']} · filas={$o['last_action_rows']})"
                    : 'N/D';

                $txt .= "2.{$nTabla}) {$dest}\n";
                $txt .= str_repeat('.', 80)."\n";
                $txt .= "  Origen     : {$o['object_name']} [alias={$o['alias']} · db={$o['remote_db']}]\n";
                $txt .= "  Comentario : {$comment}\n";
                $txt .= "  Procesos   : {$procs}\n";
                $txt .= "  Última ETL : {$lastInfo}\n";
                $txt .= "  Estructura (local):\n";

                if ($localSchema && $o['dest_table']) {
                    $schema = $localSchema;
                    $tbl    = $o['dest_table'];
                    if (strpos($tbl, '.') !== false) {
                        [$schemaPart, $tablePart] = explode('.', $tbl, 2);
                        $schema = trim($schemaPart, '`');
                        $tbl    = trim($tablePart, '`');
                    }

                    try {
                        $cols = qAll(
                            $pdo,
                            "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE,
                                    COLUMN_DEFAULT, COLUMN_KEY, EXTRA
                             FROM INFORMATION_SCHEMA.COLUMNS
                             WHERE TABLE_SCHEMA = :s
                               AND TABLE_NAME   = :t
                             ORDER BY ORDINAL_POSITION",
                            [':s'=>$schema, ':t'=>$tbl]
                        );
                    } catch (Throwable $e) {
                        $cols = [];
                    }

                    if ($cols) {
                        $txt .= "    " . str_pad("Columna", 30)
                             . str_pad("Tipo", 25)
                             . str_pad("Null", 6)
                             . str_pad("Key", 6)
                             . str_pad("Default", 12)
                             . "Extra\n";
                        $txt .= "    " . str_repeat('-', 78) . "\n";

                        foreach ($cols as $c) {
                            $nullable = ($c['IS_NULLABLE'] === 'YES') ? 'YES' : 'NO';
                            $key      = $c['COLUMN_KEY'] ?: '';
                            $def      = $c['COLUMN_DEFAULT'] !== null ? (string)$c['COLUMN_DEFAULT'] : '';
                            $extra    = $c['EXTRA'] ?: '';

                            $txt .= "    "
                                 . str_pad($c['COLUMN_NAME'], 30)
                                 . str_pad($c['COLUMN_TYPE'], 25)
                                 . str_pad($nullable, 6)
                                 . str_pad($key, 6)
                                 . str_pad($def, 12)
                                 . $extra . "\n";
                        }
                    } else {
                        $txt .= "    (no se encontró la tabla en INFORMATION_SCHEMA)\n";
                    }
                } else {
                    $txt .= "    (sin tabla destino definida / sin schema local)\n";
                }

                $txt .= "\n";
                $nTabla++;
            }
        } else {
            $txt .= "(sin tablas asociadas al proceso)\n\n";
        }

        $txt .= "3. DOCUMENTOS ADICIONALES\n";
        $txt .= "{$sub}\n\n";
        if ($docs) {
            $nDoc = 1;
            foreach ($docs as $d) {
                $notes = $d['notes'] ? preg_replace("/\r?\n/", " ", $d['notes']) : '';
                $txt .= "3.{$nDoc}) {$d['title']} ({$d['created_at']})\n";
                if ($d['url']) {
                    $txt .= "   URL      : {$d['url']}\n";
                }
                if ($d['file_path']) {
                    $sizeKB = $d['file_size'] ? round($d['file_size'] / 1024, 1) : null;
                    $txt .= "   Archivo  : {$d['file_name']}  [{$d['file_path']}]";
                    if ($sizeKB !== null) {
                        $txt .= " ({$sizeKB} KB)";
                    }
                    if ($d['mime_type']) {
                        $txt .= "  MIME={$d['mime_type']}";
                    }
                    $txt .= "\n";
                }
                if ($notes !== '') {
                    $txt .= "   Notas    : {$notes}\n";
                }
                $txt .= "\n";
                $nDoc++;
            }
        } else {
            $txt .= "(sin documentos anexos)\n";
        }

        // Guardar archivo en /public/<carpeta_base>/<proceso>/
        $baseDir  = __DIR__ . '/' . $dirBaseSafe . '/' . $procDir;
        if (!is_dir($baseDir)) {
            @mkdir($baseDir, 0775, true);
        }
        // Un solo TXT por proceso (sobrescribe)
        $filename = 'proceso_'.id_safe($proc['name']).'.txt';
        $filePath = $baseDir . '/' . $filename;

        if (file_put_contents($filePath, $txt) === false) {
            $errorOut = "No se pudo escribir el archivo en {$filePath}. Revisa permisos de escritura.";
            return false;
        }

        // Ruta relativa desde /public
        $relativePathOut = $dirBaseSafe . '/' . $procDir . '/' . $filename;
        return true;

    } catch (Throwable $e) {
        $errorOut = $e->getMessage();
        return false;
    }
}

$msg = null;
$err = null;

$action      = $_GET['action'] ?? '';
$search      = trim($_GET['q'] ?? '');
$groupFilter = trim($_GET['group'] ?? '');
$processId   = (int)($_GET['id'] ?? 0);
$pageSize    = 25;
$page        = max(1, (int)($_GET['page'] ?? 1));

// ==== Catálogo de grupos (para filtro) ============================================
$groups = qAll(
    $pdo,
    "SELECT DISTINCT group_name
     FROM etl_processes
     WHERE group_name IS NOT NULL AND group_name <> ''
     ORDER BY group_name"
);

// ==== Acciones sobre documentos (único botón: documento + TXT) ===================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['doc_action'] ?? '') === 'add_doc') {
    $pid      = (int)($_POST['doc_process_id'] ?? 0);
    $docTitle = trim($_POST['doc_title'] ?? '');
    $docUrl   = trim($_POST['doc_url'] ?? '');
    $docNotes = trim($_POST['doc_notes'] ?? '');
    $txtDir   = trim($_POST['txt_dir'] ?? '');

    if ($pid <= 0 || $docTitle === '') {
        $err = "Debes indicar proceso y título del documento.";
    } else {
        try {
            // Info del proceso para nombrar carpeta
            $procRow = qOne($pdo, "SELECT name FROM etl_processes WHERE id=:id", [':id'=>$pid]);
            $procNameSafe = $procRow ? id_safe($procRow['name']) : 'proceso_'.$pid;

            // Carpeta base relativa donde van TXT y adjuntos
            if ($txtDir === '') {
                $txtDir = 'etl';
            }
            $dirBaseSafe = preg_replace('~[^a-zA-Z0-9_/\-]+~', '_', $txtDir);
            $docsBaseDir = __DIR__ . '/' . $dirBaseSafe . '/' . $procNameSafe;

            if (!is_dir($docsBaseDir)) {
                @mkdir($docsBaseDir, 0775, true);
            }

            $fileName = null;
            $filePath = null;
            $mime     = null;
            $size     = null;

            // === Manejo de archivo adjunto ===
            if (isset($_FILES['doc_file']) && $_FILES['doc_file']['name'] !== '') {

                $fileError = (int)$_FILES['doc_file']['error'];

                if ($fileError !== UPLOAD_ERR_OK) {
                    throw new Exception(
                        "Error al subir el archivo (código {$fileError}). " .
                        "Revisa tamaño máximo (upload_max_filesize / post_max_size) y permisos."
                    );
                }

                $original = $_FILES['doc_file']['name'];
                $tmp      = $_FILES['doc_file']['tmp_name'];
                $size     = (int)$_FILES['doc_file']['size'];
                $mime     = $_FILES['doc_file']['type'] ?? null;

                $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
                $allowed = ['pdf','doc','docx','xls','xlsx','csv','png','jpg','jpeg'];
                if (!in_array($ext, $allowed, true)) {
                    throw new Exception("Tipo de archivo no permitido: .$ext");
                }

                $clean  = id_safe(pathinfo($original, PATHINFO_FILENAME));
                $stored = $clean . '_' . date('Ymd_His') . '.' . $ext;
                $dest   = $docsBaseDir . '/' . $stored;

                if (!move_uploaded_file($tmp, $dest)) {
                    throw new Exception("No se pudo mover el archivo subido a {$dest}.");
                }

                $fileName = $original;
                // ruta relativa desde /public (misma carpeta que TXT)
                $filePath = $dirBaseSafe . '/' . $procNameSafe . '/' . $stored;
            }

            $st = $pdo->prepare("
              INSERT INTO etl_process_docs (process_id, title, url, file_name, file_path, mime_type, file_size, notes, created_at)
              VALUES (:pid, :title, :url, :fname, :fpath, :mime, :fsize, :notes, NOW())
            ");
            $st->execute([
                ':pid'   => $pid,
                ':title' => $docTitle,
                ':url'   => $docUrl !== '' ? $docUrl : null,
                ':fname' => $fileName,
                ':fpath' => $filePath,
                ':mime'  => $mime,
                ':fsize' => $size,
                ':notes' => $docNotes !== '' ? $docNotes : null,
            ]);

            // Después de guardar el documento, generamos/actualizamos el TXT del proceso
            $processId = $pid;
            $txtRelPath = null;
            $txtError   = null;
            if (etl_generate_process_txt($pdo, $pid, $txtDir, $txtRelPath, $txtError)) {
                $msg = "Documento agregado y TXT actualizado para el proceso. "
                     . "Archivo: <a href=\"".htmlspecialchars($txtRelPath)."\" target=\"_blank\">abrir / descargar TXT</a>";
            } else {
                $err = "Documento agregado, pero hubo un error al generar el TXT: " . $txtError;
            }

        } catch (Throwable $e) {
            $err = "Error guardando documento: " . $e->getMessage();
        }
    }
}

// Borrar documento
if ($action === 'del_doc' && isset($_GET['doc_id'])) {
    $docId = (int)$_GET['doc_id'];
    if ($docId > 0) {
        try {
            $row = qOne($pdo, "SELECT process_id, file_path FROM etl_process_docs WHERE id=:id", [':id'=>$docId]);
            if ($row) {
                $processId = (int)$row['process_id'];
                if (!empty($row['file_path'])) {
                    $absolute = __DIR__ . '/' . $row['file_path'];
                    if (is_file($absolute)) {
                        @unlink($absolute);
                    }
                }
            }
            $st = $pdo->prepare("DELETE FROM etl_process_docs WHERE id = :id");
            $st->execute([':id'=>$docId]);
            $msg = "Documento eliminado.";
        } catch (Throwable $e) {
            $err = "Error eliminando documento: " . $e->getMessage();
        }
    }
}

// ==== Acciones sobre tablas del proceso ==========================================

// Quitar tablas del proceso
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['obj_action'] ?? '') === 'delete_obj') {
    $pid = (int)($_POST['obj_process_id'] ?? 0);
    $ids = isset($_POST['obj_ids']) && is_array($_POST['obj_ids'])
        ? array_map('intval', $_POST['obj_ids'])
        : [];

    if ($pid <= 0) {
        $err = "Proceso inválido.";
    } elseif (!$ids) {
        $err = "Selecciona al menos una tabla/vista para quitar del proceso.";
    } else {
        try {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $params = $ids;
            array_unshift($params, $pid);
            $st = $pdo->prepare("
              DELETE FROM etl_process_objects
              WHERE process_id = ?
                AND id IN ($ph)
            ");
            $st->execute($params);
            $msg = "Se quitaron ".count($ids)." tabla(s)/vista(s) del proceso.";
            $processId = $pid;
        } catch (Throwable $e) {
            $err = "Error al quitar tablas del proceso: " . $e->getMessage();
        }
    }
}

// Agregar tablas migradas al proceso
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['obj_action'] ?? '') === 'add_objs') {
    $pid  = (int)($_POST['obj_process_id'] ?? 0);
    $keys = isset($_POST['obj_keys']) && is_array($_POST['obj_keys'])
        ? $_POST['obj_keys']
        : [];

    if ($pid <= 0) {
        $err = "Proceso inválido.";
    } elseif (!$keys) {
        $err = "Selecciona al menos una tabla migrada (destino) para agregar al proceso.";
    } else {
        try {
            $proc = qOne($pdo, "SELECT name FROM etl_processes WHERE id=:id", [':id'=>$pid]);
            $procName = $proc['name'] ?? '';

            $insObj = $pdo->prepare("
              INSERT IGNORE INTO etl_process_objects
                (process_id, alias, remote_db, object_name, created_at)
              VALUES
                (:pid, :alias, :db, :obj, NOW())
            ");

            $metaUpdate = $pdo->prepare("
              INSERT INTO etl_object_meta
                (alias, remote_db, object_name, procesos, created_at, updated_at)
              VALUES
                (:alias, :db, :obj, :proc, NOW(), NOW())
              ON DUPLICATE KEY UPDATE
                procesos   = :proc2,
                updated_at = NOW()
            ");

            $added = 0;
            foreach ($keys as $k) {
                $parts = explode('|', $k);
                if (count($parts) !== 3) continue;
                [$aliasK, $dbK, $objK] = $parts;

                $insObj->execute([
                    ':pid'   => $pid,
                    ':alias' => $aliasK,
                    ':db'    => $dbK,
                    ':obj'   => $objK,
                ]);
                if ($insObj->rowCount() > 0) $added++;

                $meta = qOne(
                    $pdo,
                    "SELECT procesos
                     FROM etl_object_meta
                     WHERE alias=:a AND remote_db=:d AND object_name=:o",
                    [':a'=>$aliasK, ':d'=>$dbK, ':o'=>$objK]
                );
                $oldP = $meta['procesos'] ?? '';
                $list = array_filter(array_map('trim', explode(',', $oldP)));
                if ($procName && !in_array($procName, $list, true)) {
                    $list[] = $procName;
                }
                $newP = implode(', ', $list);

                $metaUpdate->execute([
                    ':alias' => $aliasK,
                    ':db'    => $dbK,
                    ':obj'   => $objK,
                    ':proc'  => $newP,
                    ':proc2' => $newP,
                ]);
            }

            $msg = "Se agregaron {$added} tabla(s)/vista(s) migradas al proceso.";
            $processId = $pid;
        } catch (Throwable $e) {
            $err = "Error al agregar tablas al proceso: " . $e->getMessage();
        }
    }
}

// ==== Export TXT vía GET (compatibilidad, sin botón visible) =====================

if ($action === 'export_txt' && $processId > 0) {
    $dirParam   = $_REQUEST['dir'] ?? '';
    $txtRelPath = null;
    $txtError   = null;
    if (etl_generate_process_txt($pdo, $processId, $dirParam, $txtRelPath, $txtError)) {
        $msg = "TXT generado para el proceso. "
             . "<a href=\"".htmlspecialchars($txtRelPath)."\" target=\"_blank\">abrir / descargar TXT</a>";
    } else {
        $err = "Error al exportar TXT: " . $txtError;
    }
}

// ==== Listado de procesos + export CSV ===========================================

$whereParts = [];
$params     = [];

if ($search !== '') {
    $whereParts[] = "(p.name LIKE :q OR p.description LIKE :q OR p.group_name LIKE :q)";
    $params[':q'] = "%{$search}%";
}
if ($groupFilter !== '') {
    $whereParts[] = "p.group_name = :grp";
    $params[':grp'] = $groupFilter;
}
$whereSql = $whereParts ? ('WHERE '.implode(' AND ', $whereParts)) : '';

$allProcesses = qAll(
    $pdo,
    "SELECT p.*,
            COUNT(po.id) AS objetos_cnt
     FROM etl_processes p
     LEFT JOIN etl_process_objects po ON po.process_id = p.id
     {$whereSql}
     GROUP BY p.id
     ORDER BY p.created_at DESC",
    $params
);

// Export CSV
if ($action === 'export_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="procesos_etl_'.date('Ymd_His').'.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Consecutivo','ID','Fecha','Proceso','Descripción','Grupo','No. Objetos']);
    $i = 1;
    foreach ($allProcesses as $p) {
        fputcsv($out, [
            $i++,
            $p['id'],
            $p['created_at'],
            $p['name'],
            $p['description'],
            $p['group_name'],
            $p['objetos_cnt'],
        ]);
    }
    fclose($out);
    exit;
}

// Paginación
$totalProcesses = count($allProcesses);
$totalPages     = max(1, (int)ceil($totalProcesses / $pageSize));
if ($page > $totalPages) $page = $totalPages;
$offset         = ($page - 1) * $pageSize;
$processesPage  = array_slice($allProcesses, $offset, $pageSize);

// ==== Proceso seleccionado + detalle =============================================

if ($processId === 0 && $allProcesses) {
    $processId = (int)$allProcesses[0]['id'];
}

$procSel   = null;
$objsSel   = [];
$docsSel   = [];
$allMigrated = [];

if ($processId > 0) {
    $procSel = qOne($pdo, "SELECT * FROM etl_processes WHERE id=:id", [':id'=>$processId]);

    if ($procSel) {
        $objsSel = qAll(
            $pdo,
            "SELECT po.*,
                    m.dest_table,
                    m.comment, m.procesos,
                    m.last_action_at, m.last_action_type, m.last_action_rows
             FROM etl_process_objects po
             LEFT JOIN etl_object_meta m
               ON  m.alias      = po.alias
               AND m.remote_db  = po.remote_db
               AND m.object_name= po.object_name
             WHERE po.process_id = :id
             ORDER BY COALESCE(m.dest_table, po.object_name)",
            [':id'=>$processId]
        );

        $docsSel = qAll(
            $pdo,
            "SELECT * FROM etl_process_docs
             WHERE process_id = :id
             ORDER BY created_at",
            [':id'=>$processId]
        );

        $allMigrated = qAll(
            $pdo,
            "SELECT m.alias, m.remote_db, m.object_name, m.dest_table,
                    m.comment, m.procesos,
                    m.last_action_at, m.last_action_type, m.last_action_rows,
                    CASE WHEN po2.id IS NULL THEN 0 ELSE 1 END AS in_this_process
             FROM etl_object_meta m
             LEFT JOIN etl_process_objects po2
               ON  po2.alias      = m.alias
               AND po2.remote_db  = m.remote_db
               AND po2.object_name= m.object_name
               AND po2.process_id = :pid
             WHERE m.dest_table IS NOT NULL
               AND m.dest_table <> ''
             ORDER BY m.dest_table",
            [':pid'=>$processId]
        );
    }
}

$autoOpenModal = $procSel ? 'true' : 'false';

?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>AssistPro ETL – Administrador de procesos</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
:root{
  --ap-blue:#0F5AAD;
  --ap-cyan:#00A3E0;
  --ap-bg:#f3f6fb;
  --ap-muted:#6b7280;
}
body{
  background: radial-gradient(circle at top left, rgba(0,163,224,.16), transparent 55%),
              radial-gradient(circle at bottom right, rgba(15,90,173,.16), transparent 55%),
              var(--ap-bg);
}
.ap-topbar{
  background: linear-gradient(90deg,var(--ap-blue),var(--ap-cyan));
  color:#fff;
}
.ap-topbar h1{
  font-size:1.4rem;
  margin:0;
}
.ap-badge{
  font-size:.7rem;
  letter-spacing:.03em;
  text-transform:uppercase;
}
.card{
  border-radius:1rem;
  border:none;
  box-shadow:0 12px 30px rgba(15,90,173,.12);
}
.mono{font-family:ui-monospace,Consolas,monospace;font-size:.9rem}
.small-label{font-size:.8rem;color:var(--ap-muted)}
.table-grid-wrapper{
  max-height:440px;
  overflow:auto;
  border-radius:.6rem;
}
.table-grid{
  width:100%;
  border-collapse:separate;
  border-spacing:0;
  font-size:.83rem;
}
.table-grid thead th{
  position:sticky;
  top:0;
  z-index:2;
  background:#f7f7f7;
  border-bottom:1px solid #ddd;
  padding:6px 10px;
  white-space:nowrap;
}
.table-grid tbody td{
  padding:6px 10px;
  border-bottom:1px solid #f1f1f1;
  white-space:nowrap;
}
.table-grid tbody tr:hover{
  background:#eef5ff;
}
.table-grid tbody tr:last-child td{
  border-bottom:none;
}
</style>
</head>
<body>
<div class="ap-topbar py-2 mb-4">
  <div class="container d-flex justify-content-between align-items-center">
    <div>
      <div class="d-flex align-items-center gap-2">
        <span class="badge bg-dark ap-badge">AssistPro ETL</span>
        <h1>Administrador de procesos</h1>
      </div>
      <div class="small">
        Catálogo de procesos · Documentación funcional · Relación con tablas migradas (destino)
      </div>
    </div>
    <div class="text-end">
      <a href="etl_browser.php" class="btn btn-sm btn-outline-light">
        <i class="bi bi-arrow-left"></i> Volver al Explorador ETL
      </a>
    </div>
  </div>
</div>

<div class="container pb-4">
  <?php if($msg):?><div class="alert alert-success shadow-sm"><?=$msg?></div><?php endif;?>
  <?php if($err):?><div class="alert alert-danger shadow-sm"><?=$err?></div><?php endif;?>

  <div class="card">
    <div class="card-header bg-white border-0">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <div class="small-label mb-1">Procesos registrados</div>
          <div class="small text-muted">
            Total: <?=$totalProcesses?> procesos
          </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <form class="d-flex align-items-center gap-2" method="get">
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input type="text" class="form-control" name="q"
                     placeholder="Buscar por proceso, descripción o grupo..."
                     value="<?=htmlspecialchars($search)?>">
            </div>
            <select name="group" class="form-select form-select-sm">
              <option value="">[Todos los grupos]</option>
              <?php foreach($groups as $g): $gn=$g['group_name']; ?>
                <option value="<?=htmlspecialchars($gn)?>" <?=$groupFilter===$gn?'selected':''?>>
                  <?=$gn?>
                </option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-outline-secondary" type="submit">Aplicar</button>
          </form>
          <a href="?action=export_csv&q=<?=urlencode($search)?>&group=<?=urlencode($groupFilter)?>"
             class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-spreadsheet"></i> Exportar CSV / Excel
          </a>
        </div>
      </div>
    </div>
    <div class="card-body pt-0">
      <div class="table-grid-wrapper">
        <table class="table-grid">
          <thead>
            <tr>
              <th style="width:60px">Consec.</th>
              <th style="width:110px">Fecha</th>
              <th>Proceso</th>
              <th>Descripción</th>
              <th style="width:140px">Grupo</th>
              <th style="width:90px">No. objetos</th>
              <th style="width:100px">Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php if(!$processesPage): ?>
            <tr><td colspan="7" class="text-muted text-center">No se encontraron procesos con el filtro actual.</td></tr>
          <?php else:
            $i = $offset + 1;
            foreach ($processesPage as $p):
          ?>
            <tr>
              <td class="text-muted"><?=$i++?></td>
              <td class="mono"><?=htmlspecialchars($p['created_at'])?></td>
              <td class="fw-semibold"><?=htmlspecialchars($p['name'])?></td>
              <td class="text-truncate" style="max-width:320px;"
                  title="<?=htmlspecialchars($p['description'] ?? '')?>">
                <?=$p['description'] ?: '—'?>
              </td>
              <td class="text-truncate" style="max-width:160px;"
                  title="<?=htmlspecialchars($p['group_name'] ?? '')?>">
                <?=$p['group_name'] ?: '—'?>
              </td>
              <td class="text-center"><?=$p['objetos_cnt']?></td>
              <td>
                <a href="?id=<?=$p['id']?>&q=<?=urlencode($search)?>&group=<?=urlencode($groupFilter)?>&page=<?=$page?>"
                   class="btn btn-sm btn-outline-primary">
                  Detalle
                </a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <?php if($totalPages>1): ?>
      <div class="d-flex justify-content-between align-items-center mt-2">
        <div class="small text-muted">
          Página <?=$page?> de <?=$totalPages?> · <?=$totalProcesses?> procesos
        </div>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?=$page<=1?'disabled':''?>">
              <a class="page-link"
                 href="<?=$page<=1?'#':'?q='.urlencode($search).'&group='.urlencode($groupFilter).'&page='.($page-1)?>">«</a>
            </li>
            <li class="page-item <?=$page>=$totalPages?'disabled':''?>">
              <a class="page-link"
                 href="<?=$page>=$totalPages?'#':'?q='.urlencode($search).'&group='.urlencode($groupFilter).'&page='.($page+1)?>">»</a>
            </li>
          </ul>
        </nav>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if($procSel): ?>
<!-- Modal detalle de proceso -->
<div class="modal fade" id="processDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title">Detalle de proceso</h5>
          <div class="small text-muted">
            ID <?=$procSel['id']?> · Creado <?=$procSel['created_at']?>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <div class="small-label mb-1">Proceso</div>
          <div class="fw-semibold"><?=htmlspecialchars($procSel['name'])?></div>
          <div class="small-label mt-2 mb-1">Grupo</div>
          <div class="small"><?= $procSel['group_name'] ?: '<span class="text-muted">Sin grupo</span>' ?></div>
          <div class="small-label mt-2 mb-1">Descripción</div>
          <div class="small">
            <?=$procSel['description'] ? nl2br(htmlspecialchars($procSel['description'])) : '<span class="text-muted">Sin descripción.</span>'?>
          </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="small-label mb-0">Tablas migradas asociadas</h6>
          <button type="button"
                  class="btn btn-sm btn-outline-success"
                  data-bs-toggle="modal"
                  data-bs-target="#addTablesModal">
            <i class="bi bi-plus-circle"></i> Agregar tablas migradas
          </button>
        </div>

        <form method="post">
          <input type="hidden" name="obj_action" value="delete_obj">
          <input type="hidden" name="obj_process_id" value="<?=$procSel['id']?>">

          <div class="table-responsive" style="max-height:220px;overflow:auto">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead class="table-light small">
                <tr>
                  <th style="width:30px" class="text-center">
                    <input type="checkbox" onclick="toggleAllObj(this)">
                  </th>
                  <th>Tabla destino (local)</th>
                  <th>Origen (alias / BD / tabla)</th>
                  <th>Última acción ETL</th>
                  <th>Comentario</th>
                  <th>Procesos / vistas</th>
                </tr>
              </thead>
              <tbody class="small mono">
                <?php if(!$objsSel): ?>
                  <tr><td colspan="6" class="text-muted text-center">Aún no hay tablas migradas asociadas a este proceso.</td></tr>
                <?php else: foreach ($objsSel as $o): ?>
                  <tr>
                    <td class="text-center">
                      <input type="checkbox" name="obj_ids[]" value="<?=$o['id']?>">
                    </td>
                    <td><div class="fw-semibold"><?=$o['dest_table'] ?: '(sin tabla destino)'?></div></td>
                    <td>
                      <div><?=$o['object_name']?></div>
                      <div class="text-muted small"><?=$o['alias']?> · <?=$o['remote_db']?></div>
                    </td>
                    <td>
                      <?php if($o['last_action_at']): ?>
                        <div><?=$o['last_action_at']?></div>
                        <div class="text-muted small"><?=$o['last_action_type']?> · <?=$o['last_action_rows']?> filas</div>
                      <?php else: ?>
                        <span class="text-muted">N/D</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-truncate" style="max-width:150px"
                        title="<?=htmlspecialchars((string)($o['comment'] ?? ''))?>">
                      <?=$o['comment'] ?: '—'?>
                    </td>
                    <td class="text-truncate" style="max-width:150px"
                        title="<?=htmlspecialchars((string)($o['procesos'] ?? ''))?>">
                      <?=$o['procesos'] ?: '—'?>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>

          <div class="d-flex justify-content-between align-items-center mt-2 mb-3">
            <div class="small text-muted">
              <?=count($objsSel)?> tabla(s)/vista(s) migradas vinculadas.
            </div>
            <button type="submit" class="btn btn-sm btn-outline-danger"
                    onclick="return confirm('¿Quitar las tablas/vistas seleccionadas de este proceso?');">
              <i class="bi bi-trash"></i> Quitar seleccionadas
            </button>
          </div>
        </form>

        <hr>

        <div class="row g-3">
          <div class="col-md-6">
            <h6 class="small-label mb-2">Documentos anexos</h6>
            <div class="table-responsive" style="max-height:220px;overflow:auto">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light small">
                  <tr>
                    <th>Título</th>
                    <th>Recurso</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody class="small">
                  <?php if(!$docsSel): ?>
                    <tr><td colspan="3" class="text-muted text-center">Sin documentos anexos.</td></tr>
                  <?php else: foreach ($docsSel as $d): ?>
                    <tr>
                      <td>
                        <div class="fw-semibold"><?=$d['title']?></div>
                        <?php if($d['notes']): ?>
                          <div class="text-muted small text-truncate" style="max-width:220px"
                               title="<?=htmlspecialchars($d['notes'])?>">
                            <?=$d['notes']?>
                          </div>
                        <?php endif; ?>
                        <div class="text-muted small"><?=$d['created_at']?></div>
                      </td>
                      <td>
                        <?php if($d['url']): ?>
                          <div class="small">
                            <i class="bi bi-link-45deg"></i>
                            <a href="<?=$d['url']?>" target="_blank">Abrir URL</a>
                          </div>
                        <?php endif; ?>
                        <?php if($d['file_path']): ?>
                          <div class="small">
                            <i class="bi bi-paperclip"></i>
                            <a href="<?=htmlspecialchars($d['file_path'])?>" target="_blank">
                              <?=$d['file_name'] ?: basename($d['file_path'])?>
                            </a>
                          </div>
                        <?php endif; ?>
                        <?php if(!$d['url'] && !$d['file_path']): ?>
                          <span class="text-muted small">—</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-end">
                        <a href="?id=<?=$procSel['id']?>&action=del_doc&doc_id=<?=$d['id']?>&q=<?=urlencode($search)?>&group=<?=urlencode($groupFilter)?>&page=<?=$page?>"
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('¿Eliminar este documento?');">
                          <i class="bi bi-trash"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <div class="col-md-6">
            <h6 class="small-label mb-2">Guardar documentación (TXT + documentos)</h6>
            <?php
              $autoTitle = short_text($procSel['description'] ?: $procSel['name'], 80);
            ?>
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="doc_action" value="add_doc">
              <input type="hidden" name="doc_process_id" value="<?=$procSel['id']?>">
              <div class="mb-2">
                <label class="form-label small-label">Título del documento</label>
                <input type="text" name="doc_title"
                       class="form-control form-control-sm" required
                       value="<?=htmlspecialchars($autoTitle)?>"
                       placeholder="Ej. Vista general de ubicaciones, Layout inventarios, etc.">
              </div>
              <div class="mb-2">
                <label class="form-label small-label">URL (opcional)</label>
                <input type="url" name="doc_url"
                       class="form-control form-control-sm"
                       placeholder="https://... (SharePoint, Confluence, Jira, etc.)">
              </div>
              <div class="mb-2">
                <label class="form-label small-label">Archivo (opcional)</label>
                <input type="file" name="doc_file"
                       class="form-control form-control-sm"
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.png,.jpg,.jpeg">
                <div class="form-text small">
                  El archivo y el TXT se guardan en la misma carpeta base: <code>/public/&lt;carpeta&gt;/&lt;proceso&gt;</code>.
                </div>
              </div>
              <div class="mb-2">
                <label class="form-label small-label">Carpeta TXT (relativa a /public)</label>
                <input type="text" name="txt_dir"
                       class="form-control form-control-sm"
                       placeholder="Ej. template, kardex/template">
                <div class="form-text small">
                  Si se deja vacío, se usará <code>etl</code>. Dentro de esa carpeta se generará una subcarpeta con el nombre del proceso.
                </div>
              </div>
              <div class="mb-2">
                <label class="form-label small-label">Notas</label>
                <textarea name="doc_notes" rows="2"
                          class="form-control form-control-sm"
                          placeholder="Ej. Alcance, versión del documento, comentarios relevantes."></textarea>
              </div>
              <button type="submit" class="btn btn-sm btn-primary">
                <i class="bi bi-save"></i> Guardar documentación (TXT + documento)
              </button>
            </form>
          </div>
        </div>

      </div>
      <div class="modal-footer justify-content-between">
        <div class="small text-muted">
          Proceso registrado en AssistPro ETL. La documentación se basa en tablas migradas (destino) desde <code>etl_object_meta</code>.
        </div>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal agregar tablas migradas -->
<div class="modal fade" id="addTablesModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <form method="post" class="modal-content">
      <input type="hidden" name="obj_action" value="add_objs">
      <input type="hidden" name="obj_process_id" value="<?=$procSel['id']?>">

      <div class="modal-header">
        <h5 class="modal-title">Agregar tablas migradas al proceso</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <div class="small-label mb-1">
            Proceso: <strong><?=htmlspecialchars($procSel['name'])?></strong>
          </div>
          <div class="input-group input-group-sm mb-2">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="filterTablesInput" class="form-control"
                   placeholder="Buscar por tabla destino, origen o comentario...">
          </div>
        </div>

        <div class="table-responsive" style="max-height:420px;overflow:auto">
          <table class="table table-sm table-hover align-middle mb-0" id="tablesToAddTable">
            <thead class="table-light small">
              <tr>
                <th style="width:30px" class="text-center">
                  <input type="checkbox" onclick="toggleAllAdd(this)">
                </th>
                <th>Tabla destino (local)</th>
                <th>Origen (alias / BD / tabla)</th>
                <th>Última acción ETL</th>
                <th>Comentario</th>
                <th>Procesos / vistas</th>
                <th>Estado en este proceso</th>
              </tr>
            </thead>
            <tbody class="small mono">
              <?php if(!$allMigrated): ?>
                <tr><td colspan="7" class="text-muted text-center">
                  No se encontraron tablas migradas (destino) en etl_object_meta.
                </td></tr>
              <?php else: foreach ($allMigrated as $m): ?>
                <tr>
                  <td class="text-center">
                    <input type="checkbox"
                           name="obj_keys[]"
                           value="<?=htmlspecialchars($m['alias'].'|'.$m['remote_db'].'|'.$m['object_name'])?>">
                  </td>
                  <td><div class="fw-semibold"><?=$m['dest_table']?></div></td>
                  <td>
                    <div><?=$m['object_name']?></div>
                    <div class="text-muted small"><?=$m['alias']?> · <?=$m['remote_db']?></div>
                  </td>
                  <td>
                    <?php if($m['last_action_at']): ?>
                      <div><?=$m['last_action_at']?></div>
                      <div class="text-muted small"><?=$m['last_action_type']?> · <?=$m['last_action_rows']?> filas</div>
                    <?php else: ?>
                      <span class="text-muted">N/D</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-truncate" style="max-width:150px"
                      title="<?=htmlspecialchars((string)($m['comment'] ?? ''))?>">
                    <?=$m['comment'] ?: '—'?>
                  </td>
                  <td class="text-truncate" style="max-width:150px"
                      title="<?=htmlspecialchars((string)($m['procesos'] ?? ''))?>">
                    <?=$m['procesos'] ?: '—'?>
                  </td>
                  <td>
                    <?php if($m['in_this_process']): ?>
                      <span class="badge bg-success-subtle text-success border">Ya en este proceso</span>
                    <?php else: ?>
                      <span class="badge bg-secondary-subtle text-secondary border">Disponible</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>

        <div class="small text-muted mt-2">
          Solo se listan <strong>tablas destino (locales)</strong> que fueron migradas (tienen <code>dest_table</code> en <code>etl_object_meta</code>).
          La información de origen (alias/BD/tabla) es solo para referencia.
        </div>
      </div>
      <div class="modal-footer justify-content-between">
        <div class="small text-muted">
          Una misma tabla puede participar en MÚLTIPLES procesos. Dentro de este proceso, los duplicados se ignoran automáticamente.
        </div>
        <button type="submit" class="btn btn-success">
          <i class="bi bi-plus-circle"></i> Agregar seleccionadas
        </button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleAllObj(master){
  document.querySelectorAll('input[name="obj_ids[]"]').forEach(ch => ch.checked = master.checked);
}
function toggleAllAdd(master){
  document.querySelectorAll('#tablesToAddTable tbody tr').forEach(tr => {
    const ch = tr.querySelector('input[name="obj_keys[]"]');
    if (ch) ch.checked = master.checked;
  });
}
document.addEventListener('DOMContentLoaded', function(){
  const input = document.getElementById('filterTablesInput');
  if (input) {
    input.addEventListener('input', function(){
      const term = this.value.toLowerCase();
      document.querySelectorAll('#tablesToAddTable tbody tr').forEach(tr => {
        const txt = tr.innerText.toLowerCase();
        tr.style.display = txt.includes(term) ? '' : 'none';
      });
    });
  }
  if (<?=$autoOpenModal?>) {
    const el = document.getElementById('processDetailModal');
    if (el) new bootstrap.Modal(el).show();
  }
});
</script>
</body>
</html>
