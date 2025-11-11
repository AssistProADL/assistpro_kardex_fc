<?php
// /public/etl_browser.php
// Explorador ETL Global AssistPro

ini_set('display_errors', 1);
ini_set('max_execution_time', '0');
set_time_limit(0);
ini_set('memory_limit', '1024M');
ini_set('mysql.connect_timeout','60');
ini_set('default_socket_timeout','60');

error_reporting(E_ALL);

// === Conexión local (assistpro_etl_fc) ===

require_once dirname(__DIR__) . '/app/db.php';

 
$pdoLocal = db();

// Relajar fechas cero solo en esta sesión ETL
try {
    $pdoLocal->exec("
        SET SESSION sql_mode =
        REPLACE(REPLACE(@@sql_mode,'NO_ZERO_DATE',''),'NO_ZERO_IN_DATE','')
    ");
} catch (Throwable $e) {}

// === Tabla de metadatos de objetos (comentarios / procesos / estado ETL) ===
try {
    $pdoLocal->exec("
        CREATE TABLE IF NOT EXISTS etl_object_meta (
          id INT AUTO_INCREMENT PRIMARY KEY,
          alias VARCHAR(100) NOT NULL,
          remote_db VARCHAR(191) NOT NULL,
          object_name VARCHAR(191) NOT NULL,
          comment TEXT NULL,
          procesos TEXT NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NULL DEFAULT NULL,
          last_action_at DATETIME NULL,
          last_action_type VARCHAR(20) NULL,
          last_action_rows INT NULL,
          dest_table VARCHAR(191) NULL,
          auto_update TINYINT(1) NOT NULL DEFAULT 0,
          auto_update_cron VARCHAR(191) NULL,
          UNIQUE KEY uq_meta (alias, remote_db, object_name)
        ) ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");

    // Campos extra para instalaciones antiguas
    $pdoLocal->exec("ALTER TABLE etl_object_meta ADD COLUMN IF NOT EXISTS last_action_at DATETIME NULL");
    $pdoLocal->exec("ALTER TABLE etl_object_meta ADD COLUMN IF NOT EXISTS last_action_type VARCHAR(20) NULL");
    $pdoLocal->exec("ALTER TABLE etl_object_meta ADD COLUMN IF NOT EXISTS last_action_rows INT NULL");
    $pdoLocal->exec("ALTER TABLE etl_object_meta ADD COLUMN IF NOT EXISTS dest_table VARCHAR(191) NULL");
    $pdoLocal->exec("ALTER TABLE etl_object_meta ADD COLUMN IF NOT EXISTS auto_update TINYINT(1) NOT NULL DEFAULT 0");
    $pdoLocal->exec("ALTER TABLE etl_object_meta ADD COLUMN IF NOT EXISTS auto_update_cron VARCHAR(191) NULL");
} catch (Throwable $e) {}

// === Tablas de procesos funcionales ===
try {
    $pdoLocal->exec("
        CREATE TABLE IF NOT EXISTS etl_processes (
          id INT AUTO_INCREMENT PRIMARY KEY,
          name VARCHAR(191) NOT NULL,
          description TEXT NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");

    $pdoLocal->exec("
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
} catch (Throwable $e) {}

// === Conexiones guardadas (etl_connections) ===
$cons = $pdoLocal->query("
  SELECT alias,
         COALESCE(friendly_name, alias) AS fname,
         file_path,
         IFNULL(db_name,'') AS db_name
  FROM etl_connections
  ORDER BY fname
")->fetchAll(PDO::FETCH_ASSOC);

if (!$cons) {
  die("No hay conexiones guardadas. Ve a <a href='etl_setup.php'>ETL Setup</a>.");
}

$alias = $_GET['alias'] ?? $cons[0]['alias'];

// conexión actual (para nombre amigable)
$currentConn = null;
foreach ($cons as $c) {
    if ($c['alias'] === $alias) {
        $currentConn = $c;
        break;
    }
}

// === Empresas (solo referencia visual) ===
$empresas = [];
try {
  $empresas = $pdoLocal->query("
    SELECT id AS empresa_id, nombre AS name
    FROM empresas
    WHERE activo = 1
    ORDER BY nombre
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $empresas = [['empresa_id' => 1, 'name' => 'Empresa 1']];
}
$empresa_id = (int)($_GET['empresa_id'] ?? ($empresas[0]['empresa_id'] ?? 1));

// === Cargar conexión remota ===
$remoteFile = __DIR__ . "/connections/mysql_remote_{$alias}.php";
$fnRemote  = "db_{$alias}";

if (!file_exists($remoteFile)) {
    $fallbackFile = null;
    $fallbackFn   = null;

    foreach ($cons as $c) {
        $a = $c['alias'];
        $f = __DIR__ . "/public/connections/mysql_remote_{$a}.php";
        if (file_exists($f)) {
            $fallbackFile = $f;
            $fallbackFn   = "db_{$a}";
            break;
        }
    }

    if (!$fallbackFile) {
        die("No se encontró ningún archivo de conexión en <code>public/connections/mysql_remote_*.php</code>. Configura al menos una conexión en <a href='etl_setup.php'>ETL Setup</a>.");
    }

    $remoteFile = $fallbackFile;
    $fnRemote   = $fallbackFn;
}

require_once $remoteFile;
if (!function_exists($fnRemote)) {
  die("La función de conexión <code>{$fnRemote}()</code> no existe en el archivo de conexión.");
}
$pdoRemote = $fnRemote();

// === Helpers ===
function qAll($pdo, $sql, $params = []) {
  $st = $pdo->prepare($sql);
  $st->execute($params);
  return $st->fetchAll(PDO::FETCH_ASSOC);
}
function id_safe($s) {
  return preg_replace('~[^a-zA-Z0-9_]+~', '_', $s);
}

/**
 * Refrescar listado de tablas destino (y mapa case-insensitive + collation).
 */
function refreshDestTables($pdoLocal, &$destTables, &$destTableIndexLower, &$destTableCollations) {
    $destTables = [];
    $destTableIndexLower = [];
    $destTableCollations = [];

    try {
        $rows = qAll($pdoLocal, "SHOW TABLES");
        foreach ($rows as $row) {
            $name = array_values($row)[0];
            $destTables[] = $row;
            $destTableIndexLower[strtolower($name)] = $name;
        }

        $rows = qAll(
            $pdoLocal,
            "SELECT TABLE_NAME, TABLE_COLLATION
             FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE()"
        );
        foreach ($rows as $r) {
            $destTableCollations[$r['TABLE_NAME']] = $r['TABLE_COLLATION'];
        }
    } catch (Throwable $e) {
        $destTables = [];
        $destTableIndexLower = [];
        $destTableCollations = [];
    }
}

/**
 * Inserción en sublotes sin castear a string.
 */
function insertChunked($pdoLocal, $target, $cols, $rows, $maxParams = 60000) {
  if (!$rows) return;
  $colsCount = count($cols);
  $maxRowsPerStmt = max(1, intdiv($maxParams, $colsCount));

  for ($i = 0; $i < count($rows); $i += $maxRowsPerStmt) {
    $slice = array_slice($rows, $i, $maxRowsPerStmt);
    $colList = '`' . implode('`,`', $cols) . '`';
    $rowPH = '(' . implode(',', array_fill(0, $colsCount, '?')) . ')';
    $sql = "INSERT INTO `{$target}` ({$colList}) VALUES " .
           implode(',', array_fill(0, count($slice), $rowPH));

    $st = $pdoLocal->prepare($sql);
    $bind = [];
    foreach ($slice as $r) {
      foreach ($cols as $c) {
        $bind[] = array_key_exists($c, $r) ? $r[$c] : null;
      }
    }
    $pdoLocal->beginTransaction();
    $st->execute($bind);
    $pdoLocal->commit();
  }
}

/**
 * UPSERT en sublotes (para FULL merge o incremental).
 */
function upsertChunked($pdoLocal, $target, $cols, $rows, $pk, $maxParams = 60000) {
  if (!$rows) return;
  $colsCount = count($cols);
  $maxRowsPerStmt = max(1, intdiv($maxParams, $colsCount));

  $updPairs = [];
  foreach ($cols as $c) {
    if ($c === $pk) continue;
    $updPairs[] = "`$c`=VALUES(`$c`)";
  }

  for ($i = 0; $i < count($rows); $i += $maxRowsPerStmt) {
    $slice = array_slice($rows, $i, $maxRowsPerStmt);
    $colList = '`' . implode('`,`', $cols) . '`';
    $rowPH = '(' . implode(',', array_fill(0, $colsCount, '?')) . ')';
    $sql = "INSERT INTO `{$target}` ({$colList}) VALUES " .
           implode(',', array_fill(0, count($slice), $rowPH)) .
           " ON DUPLICATE KEY UPDATE " . implode(',', $updPairs);

    $st = $pdoLocal->prepare($sql);
    $bind = [];
    foreach ($slice as $r) {
      foreach ($cols as $c) {
        $bind[] = array_key_exists($c, $r) ? $r[$c] : null;
      }
    }
    $pdoLocal->beginTransaction();
    $st->execute($bind);
    $pdoLocal->commit();
  }
}

/**
 * Registrar última acción ETL en etl_object_meta.
 */
function registerEtlAction($pdoLocal, $alias, $db, $obj, $destTable, $actionType, $rows, $currentMeta = []) {
    $comment  = $currentMeta['comment']  ?? null;
    $procesos = $currentMeta['procesos'] ?? null;
    $autoUpd  = isset($currentMeta['auto_update']) ? (int)$currentMeta['auto_update'] : 0;
    $autoCron = $currentMeta['auto_update_cron'] ?? null;

    $st = $pdoLocal->prepare("
      INSERT INTO etl_object_meta
        (alias, remote_db, object_name,
         comment, procesos, created_at, updated_at,
         last_action_at, last_action_type, last_action_rows, dest_table,
         auto_update, auto_update_cron)
      VALUES
        (:alias, :db, :obj,
         :comment, :procesos, NOW(), NOW(),
         NOW(), :type, :rows, :dest,
         :auto_update, :auto_cron)
      ON DUPLICATE KEY UPDATE
        comment           = COALESCE(VALUES(comment), comment),
        procesos          = COALESCE(VALUES(procesos), procesos),
        updated_at        = NOW(),
        last_action_at    = VALUES(last_action_at),
        last_action_type  = VALUES(last_action_type),
        last_action_rows  = VALUES(last_action_rows),
        dest_table        = VALUES(dest_table),
        auto_update       = VALUES(auto_update),
        auto_update_cron  = VALUES(auto_update_cron)
    ");

    $st->execute([
        ':alias'       => $alias,
        ':db'          => $db,
        ':obj'         => $obj,
        ':comment'     => $comment,
        ':procesos'    => $procesos,
        ':type'        => $actionType,
        ':rows'        => (int)$rows,
        ':dest'        => $destTable,
        ':auto_update' => $autoUpd,
        ':auto_cron'   => $autoCron,
    ]);
}

// Inicializar estado de destino
$destTables = [];
$destTableIndexLower = [];
$destTableCollations = [];
refreshDestTables($pdoLocal, $destTables, $destTableIndexLower, $destTableCollations);

// === Inputs UI ===
$dbname   = $_GET['db']   ?? '';
$object   = $_POST['object'] ?? ($_GET['obj'] ?? '');
$spName   = $_GET['sp']   ?? '';
$action   = $_POST['action'] ?? '';
$autoOpenEtl = isset($_GET['openetl']);

$target   = id_safe($_POST['target'] ?? ($object ? "stg_{$object}" : ""));
$previewN = (int)($_POST['previewN'] ?? 20);
$sampleN  = (int)($_POST['sampleN']  ?? 200);
$batch    = (int)($_POST['batch']    ?? 500);
$pk       = $_POST['pk']     ?? '';
$updcol   = $_POST['updcol'] ?? '';

$fullMode = $_POST['full_mode'] ?? 'truncate';

// unicode_ci por defecto
$forceUnicode = isset($_POST['force_unicode']) ? ($_POST['force_unicode'] == '1') : true;

// Prefijo stg_ en modal
$useStg = isset($_POST['use_stg']) ? ($_POST['use_stg'] === '1') : true;

// Metadatos (comentario / procesos / auto-update) desde el modal
$metaComment      = $_POST['meta_comment']  ?? null;
$metaProcesos     = $_POST['meta_procesos'] ?? null;
$metaAutoUpdate   = isset($_POST['auto_update']) ? 1 : 0;
$metaAutoCron     = $_POST['auto_update_cron'] ?? null;

// Bulk (selección múltiple)
$bulkAction   = $_POST['bulk_action'] ?? '';
$bulkObjects  = isset($_POST['bulk_objects']) && is_array($_POST['bulk_objects'])
  ? $_POST['bulk_objects'] : [];
$bulkUseStg   = isset($_POST['bulk_use_stg']) && $_POST['bulk_use_stg'] === '0' ? 0 : 1;

// Selección de tablas destino para exportar estructura
$destTablesExport = isset($_POST['dest_tables']) && is_array($_POST['dest_tables'])
  ? array_values(array_unique($_POST['dest_tables'])) : [];

// Proceso funcional (modal)
$processAction     = $_POST['process_action']  ?? '';
$processName       = trim($_POST['process_name'] ?? '');
$processDesc       = trim($_POST['process_desc'] ?? '');
$processObjects    = isset($_POST['process_objects'])
    ? array_filter(array_map('trim', explode(',', $_POST['process_objects'])))
    : [];
$processExportTxt  = isset($_POST['process_export_txt']) ? (int)$_POST['process_export_txt'] : 0;

// Flag para limpiar selección tras crear proceso
$clearSelectionOnLoad = false;

// Paginación
$pageSize = 25;
$page     = max(1, (int)($_GET['page'] ?? 1));

$objSearch = trim($_GET['objsearch'] ?? '');
$objSearchLower = mb_strtolower($objSearch);

$showTbl  = isset($_GET['show_tbl'])  ? ($_GET['show_tbl'] === '1')  : true;
$showView = isset($_GET['show_view']) ? ($_GET['show_view'] === '1') : true;
$showSp   = isset($_GET['show_sp'])   ? ($_GET['show_sp'] === '1')   : true;

// Secciones: tb = tablas, vw = vistas, sp = stored procedures
$activeSection = $_GET['sec'] ?? 'tb';
if (!in_array($activeSection, ['tb','vw','sp'], true)) {
    $activeSection = 'tb';
}

// === Codificaciones (origen / destino) ===
$destCharset   = null;
$destCollation = null;
try {
    $row = qAll($pdoLocal, "SELECT @@character_set_database AS cs, @@collation_database AS co");
    if ($row) {
        $destCharset   = $row[0]['cs'];
        $destCollation = $row[0]['co'];
    }
} catch (Throwable $e) {}

$originCharset   = null;
$originCollation = null;

// === Catálogo BD y objetos ===
$bases = qAll($pdoRemote, "SHOW DATABASES");

$objects = [];
$sps = [];
$countTablesOrigin = 0;
$countViewsOrigin  = 0;
$countSPOrigin     = 0;
$tableCollations   = [];

// Metadatos por objeto
$metaByObject = [];

if ($dbname) {
  $pdoRemote->exec("USE `{$dbname}`");
  $objects = qAll($pdoRemote, "SHOW FULL TABLES");
  $sps = qAll(
    $pdoRemote,
    "SELECT ROUTINE_NAME, ROUTINE_TYPE
     FROM INFORMATION_SCHEMA.ROUTINES
     WHERE ROUTINE_SCHEMA = :db AND ROUTINE_TYPE = 'PROCEDURE'
     ORDER BY ROUTINE_NAME",
    [':db' => $dbname]
  );
  $countSPOrigin = count($sps);
  foreach ($objects as $row) {
    $type = strtoupper($row['Table_type'] ?? $row['TABLE_TYPE'] ?? '');
    if ($type === 'BASE TABLE' || $type === 'TABLE') {
      $countTablesOrigin++;
    } elseif ($type === 'VIEW') {
      $countViewsOrigin++;
    }
  }

  // codificación origen (schema)
  try {
      $row = qAll(
        $pdoRemote,
        "SELECT DEFAULT_CHARACTER_SET_NAME AS cs,
                DEFAULT_COLLATION_NAME      AS co
         FROM information_schema.SCHEMATA
         WHERE SCHEMA_NAME = :db",
        [':db'=>$dbname]
      );
      if ($row) {
          $originCharset   = $row[0]['cs'];
          $originCollation = $row[0]['co'];
      }
  } catch (Throwable $e) {}

  // codificación por tabla origen
  try {
      $rows = qAll(
        $pdoRemote,
        "SELECT TABLE_NAME, TABLE_COLLATION
         FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = :db",
        [':db'=>$dbname]
      );
      foreach ($rows as $r) {
          $tableCollations[$r['TABLE_NAME']] = $r['TABLE_COLLATION'];
      }
  } catch (Throwable $e) {}

  // Cargar metadatos locales de esta conexión + DB
  try {
    $rowsMeta = qAll(
      $pdoLocal,
      "SELECT object_name, comment, procesos,
              last_action_at, last_action_type, last_action_rows,
              dest_table, auto_update, auto_update_cron
       FROM etl_object_meta
       WHERE alias = :alias AND remote_db = :db",
      [':alias'=>$alias, ':db'=>$dbname]
    );
    foreach ($rowsMeta as $m) {
      $metaByObject[$m['object_name']] = [
        'comment'            => $m['comment'],
        'procesos'           => $m['procesos'],
        'last_action_at'     => $m['last_action_at'],
        'last_action_type'   => $m['last_action_type'],
        'last_action_rows'   => $m['last_action_rows'],
        'dest_table'         => $m['dest_table'],
        'auto_update'        => $m['auto_update'],
        'auto_update_cron'   => $m['auto_update_cron'],
      ];
    }
  } catch (Throwable $e) {
    $metaByObject = [];
  }
}

$msg = null;
$err = null;
$columns = [];
$preview = [];
$spDefinition = "";

// === Crear proceso funcional con la selección de objetos ===
if ($dbname && $processAction === 'create_process') {
    if ($processName === '' || !$processObjects) {
        $err = "Para crear un proceso necesitas nombre y al menos una tabla/vista seleccionada.";
    } else {
        try {
            // 1) Crear proceso
            $st = $pdoLocal->prepare("
              INSERT INTO etl_processes (name, description, created_at)
              VALUES (:name, :description, NOW())
            ");
            $st->execute([
              ':name'        => $processName,
              ':description' => $processDesc ?: null,
            ]);
            $pid = (int)$pdoLocal->lastInsertId();

            // 2) Relacionar cada objeto seleccionado
            $ins = $pdoLocal->prepare("
              INSERT IGNORE INTO etl_process_objects
                (process_id, alias, remote_db, object_name)
              VALUES
                (:pid, :alias, :db, :obj)
            ");

            foreach ($processObjects as $objName) {
                if ($objName === '') continue;

                $ins->execute([
                  ':pid'   => $pid,
                  ':alias' => $alias,
                  ':db'    => $dbname,
                  ':obj'   => $objName,
                ]);

                // 3) Actualizar campo 'procesos' en etl_object_meta
                $metaExisting = $metaByObject[$objName] ?? [];
                $oldProc = $metaExisting['procesos'] ?? '';
                $list    = array_filter(array_map('trim', explode(',', $oldProc)));
                if (!in_array($processName, $list, true)) {
                    $list[] = $processName;
                }
                $newProc = implode(', ', $list);

                $stMeta = $pdoLocal->prepare("
                  InserT INTO etl_object_meta
                    (alias, remote_db, object_name, procesos, created_at, updated_at)
                  VALUES
                    (:alias, :db, :obj, :proc_val, NOW(), NOW())
                  ON DUPLICATE KEY UPDATE
                    procesos   = :proc_upd,
                    updated_at = NOW()
                ");
                $stMeta->execute([
                  ':alias'    => $alias,
                  ':db'       => $dbname,
                  ':obj'      => $objName,
                  ':proc_val' => $newProc,
                  ':proc_upd' => $newProc,
                ]);

                $metaByObject[$objName]['procesos'] = $newProc;
            }

            // 4) Exportar documento TXT si se solicitó
            if ($processExportTxt) {
                $txt  = "AssistPro ETL - Documento de proceso\n";
                $txt .= "=======================================\n\n";
                $txt .= "Proceso: {$processName}\n";
                $txt .= "Descripción: ".($processDesc !== '' ? $processDesc : '(sin descripción)')."\n\n";
                $txt .= "Conexión: {$alias}\n";
                $txt .= "Base de datos origen: {$dbname}\n";
                $txt .= "Fecha generación: ".date('Y-m-d H:i:s')."\n\n";
                $txt .= "Objetos asociados (tablas/vistas):\n";

                foreach ($processObjects as $objName) {
                    $objType = 'Tabla/Vista';
                    foreach ($objects as $row) {
                        $nameRow = array_values($row)[0];
                        if ($nameRow === $objName) {
                            $type = strtoupper($row['Table_type'] ?? $row['TABLE_TYPE'] ?? '');
                            $objType = ($type === 'VIEW') ? 'Vista' : 'Tabla';
                            break;
                        }
                    }
                    $metaObj  = $metaByObject[$objName] ?? [];
                    $comment  = isset($metaObj['comment'])  && $metaObj['comment'] !== ''  ? preg_replace("/\r?\n/", " ", $metaObj['comment'])  : '(sin comentario)';
                    $procUsed = isset($metaObj['procesos']) && $metaObj['procesos'] !== '' ? preg_replace("/\r?\n/", " ", $metaObj['procesos']) : '(sin listado adicional)';

                    $txt .= "- {$objName} [{$objType}]\n";
                    $txt .= "  Comentario: {$comment}\n";
                    $txt .= "  Procesos/vistas relacionados: {$procUsed}\n\n";
                }

                header('Content-Type: text/plain; charset=utf-8');
                header('Content-Disposition: attachment; filename="proceso_'.id_safe($processName).'_'.date('Ymd_His').'.txt"');
                echo $txt;
                exit;
            }

            // 5) Limpiar selección en UI (señal a JS)
            $clearSelectionOnLoad = true;

            $msg = "Proceso <strong>".htmlspecialchars($processName)."</strong> creado y asociado a "
                 .count($processObjects)." tabla(s)/vista(s). "
                 ."<a href=\"administrador_procesos.php?id={$pid}\" class=\"alert-link\" target=\"_blank\">Ver en administrador</a>";

        } catch (Throwable $e) {
            $err = "Error creando proceso: ".$e->getMessage();
        }
    }
}

// === Exportar estructuras de tablas destino seleccionadas ===
if ($dbname && $bulkAction === 'export_dest' && $destTablesExport) {
    try {
        $out  = "AssistPro ETL - Estructuras de tablas destino\n";
        $out .= "Conexión: {$alias}\n";
        $out .= "BD remota (origen): {$dbname}\n";
        $out .= "BD local (destino): " . $pdoLocal->query("SELECT DATABASE()")->fetchColumn() . "\n";
        $out .= "Generado: " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($destTablesExport as $dt) {
            $dtClean = trim($dt);
            if ($dtClean === '') continue;

            // Buscar origen y metadatos asociados
            $originName = null;
            $meta = null;
            foreach ($metaByObject as $oname => $m) {
                if (!empty($m['dest_table']) && $m['dest_table'] === $dtClean) {
                    $originName = $oname;
                    $meta = $m;
                    break;
                }
            }

            $cols = [];
            try {
                $cols = qAll($pdoLocal, "SHOW FULL COLUMNS FROM `{$dtClean}`");
            } catch (Throwable $e) {}

            $out .= "=====================================================================\n";
            $out .= "Tabla destino: {$dtClean}\n";
            if ($originName) {
                $out .= "Origen asociado: {$originName}\n";
            }
            if ($meta && !empty($meta['comment'])) {
                $out .= "Comentario: " . preg_replace("/\r?\n/", " ", $meta['comment']) . "\n";
            }
            if ($meta && !empty($meta['procesos'])) {
                $out .= "Procesos donde se usa: " . preg_replace("/\r?\n/", " ", $meta['procesos']) . "\n";
            }
            if ($meta && !empty($meta['last_action_at'])) {
                $out .= "Última actualización ETL: {$meta['last_action_at']} ({$meta['last_action_type']} · filas={$meta['last_action_rows']})\n";
            }
            $out .= "Definición de columnas:\n";

            if ($cols) {
                foreach ($cols as $c) {
                    $def = sprintf(
                        "  - %-30s %-25s %-4s %-4s Default:%s Extra:%s Comment:%s",
                        $c['Field'],
                        $c['Type'],
                        $c['Null'],
                        $c['Key'],
                        ($c['Default'] === null ? 'NULL' : $c['Default']),
                        $c['Extra'],
                        isset($c['Comment']) ? $c['Comment'] : ''
                    );
                    $out .= $def . "\n";
                }
            } else {
                $out .= "  (No se pudo leer estructura en destino)\n";
            }

            $out .= "\n";
        }

        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="etl_destino_' . date('Ymd_His') . '.txt"');
        echo $out;
        exit;
    } catch (Throwable $e) {
        $err = "Error al exportar estructuras destino: " . $e->getMessage();
    }
}

// === BULK FULL (selección múltiple origen) ===
if ($dbname && $bulkAction === 'bulk_full' && $bulkObjects) {
    try {
        $pdoRemote->exec("USE `{$dbname}`");
        $totalTables = 0;
        $totalRows   = 0;

        foreach ($bulkObjects as $objName) {
            $objName = trim($objName);
            if ($objName === '') continue;

            $cols = qAll($pdoRemote, "SHOW COLUMNS FROM `{$objName}`");
            if (!$cols) continue;

            $targetName = $bulkUseStg ? id_safe('stg_'.$objName) : id_safe($objName);

            // crear tabla destino similar al origen
            $created = false;
            try {
                $row = qAll($pdoRemote, "SHOW CREATE TABLE `{$objName}`");
                if ($row) {
                    $create = $row[0]['Create Table'] ?? $row[0]['Create Table '] ?? null;
                    if ($create) {
                        $create = preg_replace(
                            '/CREATE TABLE `[^`]+`/i',
                            'CREATE TABLE IF NOT EXISTS `'.$targetName.'`',
                            $create,
                            1
                        );
                        $pdoLocal->exec($create);
                        $created = true;
                    }
                }
            } catch (Throwable $e) {}

            if (!$created) {
                $defs = [];
                foreach ($cols as $c) {
                    $name = $c['Field'];
                    $type = $c['Type'] ?: 'TEXT';
                    $null = (strtoupper($c['Null'] ?? '') === 'NO') ? 'NOT NULL' : 'NULL';
                    $defs[] = "`{$name}` {$type} {$null}";
                }
                $charsetClause = "DEFAULT CHARSET=utf8mb4";
                $sql = "CREATE TABLE IF NOT EXISTS `{$targetName}` (" .
                       implode(',', $defs) .
                       ") ENGINE=InnoDB {$charsetClause}";
                $pdoLocal->exec($sql);
            }

            if ($forceUnicode) {
                $pdoLocal->exec("ALTER TABLE `{$targetName}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }

            $colNames = array_map(fn($c) => $c['Field'], $cols);

            // TRUNCATE + copia FULL
            $pdoLocal->exec("TRUNCATE TABLE `{$targetName}`");
            $off = 0;
            $copied = 0;
            while (true) {
                $rows = qAll($pdoRemote, "SELECT * FROM `{$objName}` LIMIT {$batch} OFFSET {$off}");
                if (!$rows) break;
                insertChunked($pdoLocal, $targetName, $colNames, $rows);
                $copied += count($rows);
                $off    += $batch;
                if (count($rows) < $batch) break;
            }

            $metaExisting = $metaByObject[$objName] ?? [];
            registerEtlAction($pdoLocal, $alias, $dbname, $objName, $targetName, 'FULL', $copied, $metaExisting);
            $metaByObject[$objName]['last_action_at']   = date('Y-m-d H:i:s');
            $metaByObject[$objName]['last_action_type'] = 'FULL';
            $metaByObject[$objName]['last_action_rows'] = $copied;
            $metaByObject[$objName]['dest_table']       = $targetName;

            $totalTables++;
            $totalRows += $copied;
        }

        refreshDestTables($pdoLocal, $destTables, $destTableIndexLower, $destTableCollations);

        $msg = "Copia FULL masiva completada. Tablas procesadas: {$totalTables}, filas totales: {$totalRows}.";
    } catch (Throwable $e) {
        $err = "Error en copia FULL masiva: " . $e->getMessage();
    }
}

// === Detalle para Tablas/Vistas (acciones ETL individuales) ===
if ($dbname && $object) {
  try {
    $columns = qAll($pdoRemote, "SHOW COLUMNS FROM `{$object}`");
  } catch (Throwable $e) {
    $err = "No se pudieron obtener columnas de {$object}: " . $e->getMessage();
  }

  $ensureTarget = function() use ($pdoLocal, $pdoRemote, $object, $columns, &$target, $forceUnicode) {
    if (!$target) {
      throw new Exception("Nombre de tabla destino no especificado.");
    }

    $created = false;
    try {
      $row = qAll($pdoRemote, "SHOW CREATE TABLE `{$object}`");
      if ($row) {
        $create = $row[0]['Create Table'] ?? $row[0]['Create Table '] ?? null;
        if ($create) {
          $create = preg_replace(
            '/CREATE TABLE `[^`]+`/i',
            'CREATE TABLE IF NOT EXISTS `'.$target.'`',
            $create,
            1
          );
          $pdoLocal->exec($create);
          $created = true;
        }
      }
    } catch (Throwable $e) {}

    if (!$created) {
      $defs = [];
      foreach ($columns as $c) {
        $name = $c['Field'];
        $type = $c['Type'] ?: 'TEXT';
        $null = (strtoupper($c['Null'] ?? '') === 'NO') ? 'NOT NULL' : 'NULL';
        $defs[] = "`{$name}` {$type} {$null}";
      }
      $charsetClause = "DEFAULT CHARSET=utf8mb4";
      $sql = "CREATE TABLE IF NOT EXISTS `{$target}` (" .
             implode(',', $defs) .
             ") ENGINE=InnoDB {$charsetClause}";
      $pdoLocal->exec($sql);
    }

    if ($forceUnicode) {
      $pdoLocal->exec("ALTER TABLE `{$target}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    return array_map(fn($c) => $c['Field'], $columns);
  };

  if ($action === 'preview') {
    try {
      $preview = qAll($pdoRemote, "SELECT * FROM `{$object}` LIMIT {$previewN}");
      $msg = "Preview de <code>{$object}</code> (máx {$previewN} filas).";
    } catch (Throwable $e) {
      $err = "Error en preview: " . $e->getMessage();
    }
  }

  if ($action === 'copy_sample') {
    try {
      $cols = $ensureTarget();
      $pdoLocal->exec("TRUNCATE TABLE `{$target}`");

      $rows = qAll($pdoRemote, "SELECT * FROM `{$object}` LIMIT {$sampleN}");
      insertChunked($pdoLocal, $target, $cols, $rows);

      refreshDestTables($pdoLocal, $destTables, $destTableIndexLower, $destTableCollations);

      $metaExisting = $metaByObject[$object] ?? [];
      registerEtlAction($pdoLocal, $alias, $dbname, $object, $target, 'SAMPLE', count($rows), $metaExisting);
      $metaByObject[$object]['last_action_at']   = date('Y-m-d H:i:s');
      $metaByObject[$object]['last_action_type'] = 'SAMPLE';
      $metaByObject[$object]['last_action_rows'] = count($rows);
      $metaByObject[$object]['dest_table']       = $target;

      $msg = "Muestra representativa copiada de <code>{$object}</code> → <code>{$target}</code> ({$sampleN} filas máx).";
    } catch (Throwable $e) {
      $err = "Error en copia de muestra: " . $e->getMessage();
    }
  }

  if ($action === 'copy_full') {
    try {
      $cols = $ensureTarget();

      if ($fullMode === 'truncate') {
        $pdoLocal->exec("TRUNCATE TABLE `{$target}`");
      } elseif ($fullMode === 'merge' && $pk) {
        try {
          $pdoLocal->exec("ALTER TABLE `{$target}` ADD UNIQUE KEY uq_{$pk} (`{$pk}`(191))");
        } catch (Throwable $e) {}
      }

      $off = 0;
      $tot = 0;
      while (true) {
        $rows = qAll($pdoRemote, "SELECT * FROM `{$object}` LIMIT {$batch} OFFSET {$off}");
        if (!$rows) break;

        if ($fullMode === 'merge' && $pk) {
          upsertChunked($pdoLocal, $target, $cols, $rows, $pk);
        } else {
          insertChunked($pdoLocal, $target, $cols, $rows);
        }

        $tot += count($rows);
        $off += $batch;
        if (count($rows) < $batch) break;
      }

      refreshDestTables($pdoLocal, $destTables, $destTableIndexLower, $destTableCollations);

      $modoTxt = ($fullMode === 'merge' && $pk)
        ? "FULL (actualizar por PK {$pk})"
        : "FULL (reemplazar)";

      $metaExisting = $metaByObject[$object] ?? [];
      registerEtlAction($pdoLocal, $alias, $dbname, $object, $target, 'FULL', $tot, $metaExisting);
      $metaByObject[$object]['last_action_at']   = date('Y-m-d H:i:s');
      $metaByObject[$object]['last_action_type'] = 'FULL';
      $metaByObject[$object]['last_action_rows'] = $tot;
      $metaByObject[$object]['dest_table']       = $target;

      $msg = "Copiado {$modoTxt} de <code>{$object}</code> → <code>{$target}</code>: {$tot} filas.";
    } catch (Throwable $e) {
      $err = "Error en copia FULL: " . $e->getMessage();
    }
  }

  if ($action === 'copy_incremental') {
    try {
      if (!$pk)     throw new Exception("Indica la PK para upsert (ej. id, folio).");
      if (!$updcol) throw new Exception("Indica la columna incremental (ej. updated_at, fecha_modif).");

      $cols = $ensureTarget();
      try {
        $pdoLocal->exec("ALTER TABLE `{$target}` ADD UNIQUE KEY uq_{$pk} (`{$pk}`(191))");
      } catch (Throwable $e) {}

      $markRow = $pdoLocal->query("SELECT MAX(`{$updcol}`) AS m FROM `{$target}`")->fetch(PDO::FETCH_ASSOC);
      $mark = $markRow['m'] ?? '1970-01-01 00:00:00';

      $off = 0;
      $tot = 0;
      while (true) {
        $rows = qAll(
          $pdoRemote,
          "SELECT * FROM `{$object}`
           WHERE `{$updcol}` > :m
           ORDER BY `{$updcol}`
           LIMIT {$batch} OFFSET {$off}",
          [':m' => $mark]
        );
        if (!$rows) break;

        upsertChunked($pdoLocal, $target, $cols, $rows, $pk);
        $tot += count($rows);
        $off += $batch;
        if (count($rows) < $batch) break;
      }

      refreshDestTables($pdoLocal, $destTables, $destTableIndexLower, $destTableCollations);

      $metaExisting = $metaByObject[$object] ?? [];
      registerEtlAction($pdoLocal, $alias, $dbname, $object, $target, 'INCR', $tot, $metaExisting);
      $metaByObject[$object]['last_action_at']   = date('Y-m-d H:i:s');
      $metaByObject[$object]['last_action_type'] = 'INCR';
      $metaByObject[$object]['last_action_rows'] = $tot;
      $metaByObject[$object]['dest_table']       = $target;

      $msg = "Incremental de <code>{$object}</code> → <code>{$target}</code> aplicado. Marca usada: <code>{$mark}</code>. Filas procesadas: {$tot}.";
    } catch (Throwable $e) {
      $err = "Error incremental: " . $e->getMessage();
    }
  }

  // === Guardar metadatos (comentario / procesos / auto-update) si vienen del modal ===
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($metaComment !== null || $metaProcesos !== null)) {
    try {
      $mc = trim((string)$metaComment);
      $mp = trim((string)$metaProcesos);
      $st = $pdoLocal->prepare("
        INSERT INTO etl_object_meta
          (alias, remote_db, object_name,
           comment, procesos, created_at, updated_at,
           auto_update, auto_update_cron)
        VALUES
          (:alias, :db, :obj,
           :comment_val, :procesos_val, NOW(), NOW(),
           :auto_update_val, :auto_cron_val)
        ON DUPLICATE KEY UPDATE
          comment          = :comment_upd,
          procesos         = :procesos_upd,
          updated_at       = NOW(),
          auto_update      = :auto_update_upd,
          auto_update_cron = :auto_cron_upd
      ");
      $st->execute([
        ':alias'            => $alias,
        ':db'               => $dbname,
        ':obj'              => $object,
        ':comment_val'      => $mc,
        ':procesos_val'     => $mp,
        ':auto_update_val'  => $metaAutoUpdate,
        ':auto_cron_val'    => $metaAutoCron,
        ':comment_upd'      => $mc,
        ':procesos_upd'     => $mp,
        ':auto_update_upd'  => $metaAutoUpdate,
        ':auto_cron_upd'    => $metaAutoCron,
      ]);
      $metaByObject[$object]['comment']          = $mc;
      $metaByObject[$object]['procesos']         = $mp;
      $metaByObject[$object]['auto_update']      = $metaAutoUpdate;
      $metaByObject[$object]['auto_update_cron'] = $metaAutoCron;
    } catch (Throwable $e) {
      // ignoramos fallo de metadatos
    }
  }
}

// === SPs (solo listado + copiar) ===
if ($dbname && $spName) {
  try {
    $row = qAll($pdoRemote, "SHOW CREATE PROCEDURE `{$spName}`");
    if ($row && isset($row[0]['Create Procedure'])) {
      $spDefinition = $row[0]['Create Procedure'];
    } elseif ($row && isset($row[0]['Create Procedure '])) {
      $spDefinition = $row[0]['Create Procedure '];
    }
  } catch (Throwable $e) {
    $err = "No se pudo obtener definición de SP {$spName}: " . $e->getMessage();
  }

  if ($action === 'copy_sp') {
    try {
      if (!$spDefinition) {
        $row = qAll($pdoRemote, "SHOW CREATE PROCEDURE `{$spName}`");
        if ($row && isset($row[0]['Create Procedure'])) {
          $spDefinition = $row[0]['Create Procedure'];
        } elseif ($row && isset($row[0]['Create Procedure '])) {
          $spDefinition = $row[0]['Create Procedure '];
        }
      }
      if (!$spDefinition) throw new Exception("No se pudo obtener la definición del procedimiento.");

      $sqlCreate = preg_replace('/DEFINER=`[^`]+`@`[^`]+`\s*/', '', $spDefinition);

      $pdoLocal->exec("DROP PROCEDURE IF EXISTS `{$spName}`");
      $pdoLocal->exec($sqlCreate);

      $msg = "Stored Procedure <code>{$spName}</code> copiado a la base local.";
    } catch (Throwable $e) {
      $err = "Error al copiar SP: " . $e->getMessage();
    }
  }
}

// === Paginación de objetos según sección ===
$filteredObjects = [];
if ($dbname && $objects) {
    foreach ($objects as $row) {
        $objName = array_values($row)[0];
        $type = strtoupper($row['Table_type'] ?? $row['TABLE_TYPE'] ?? '');

        if ($activeSection === 'tb') {
            if (!in_array($type, ['BASE TABLE','TABLE'], true)) continue;
            if (!$showTbl) continue;
        } elseif ($activeSection === 'vw') {
            if ($type !== 'VIEW') continue;
            if (!$showView) continue;
        }

        if ($objSearchLower !== '' && mb_strpos(mb_strtolower($objName), $objSearchLower) === false) {
            continue;
        }

        $filteredObjects[] = $row;
    }
}
$totalRows  = count($filteredObjects);
$totalPages = max(1, (int)ceil($totalRows / $pageSize));
if ($page > $totalPages) $page = $totalPages;
$offsetRows = ($page - 1) * $pageSize;
$pageSlice  = array_slice($filteredObjects, $offsetRows, $pageSize);

// SPs filtrados + paginación
$spFiltered = [];
if ($dbname && $sps && $activeSection === 'sp' && $showSp) {
    foreach ($sps as $sp) {
        $n = $sp['ROUTINE_NAME'];
        if ($objSearchLower !== '' && mb_strpos(mb_strtolower($n), $objSearchLower) === false) {
            continue;
        }
        $spFiltered[] = $n;
    }
}
$spTotalRows  = count($spFiltered);
$spTotalPages = max(1, (int)ceil($spTotalRows / $pageSize));
if ($activeSection === 'sp' && $page > $spTotalPages) $page = $spTotalPages;
$spOffsetRows = ($page - 1) * $pageSize;
$spSlice      = array_slice($spFiltered, $spOffsetRows, $pageSize);

// URL base para paginación
$baseListUrl = '?alias='.urlencode($alias)
             .'&empresa_id='.$empresa_id
             .'&db='.urlencode($dbname)
             .'&show_tbl='.($showTbl?1:0)
             .'&show_view='.($showView?1:0)
             .'&show_sp='.($showSp?1:0)
             .'&objsearch='.urlencode($objSearch)
             .'&sec='.urlencode($activeSection);

?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>AssistPro ETL – Explorador Global</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
:root{
  --ap-blue:#0F5AAD;
  --ap-cyan:#00A3E0;
  --ap-bg:#f3f6fb;
  --ap-card:#ffffff;
  --ap-ink:#0b1020;
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
.card-header{
  border-radius:1rem 1rem 0 0 !important;
}
.mono{font-family:ui-monospace,Consolas,monospace;font-size:.9rem}
.small-label{font-size:.8rem;color:var(--ap-muted)}
.badge-dest-exists{font-size:.6rem;}
.table-grid-wrapper{
  max-height:420px;
  overflow:auto;
  border:1px solid #e5e5e5;
  border-radius:.4rem;
}
.table-grid{
  width:100%;
  border-collapse:separate;
  border-spacing:0;
  font-size:.8rem;
}
.table-grid thead th{
  position:sticky;
  top:0;
  z-index:2;
  background:#f7f7f7;
  border-bottom:1px solid #ddd;
  padding:6px 10px;
  font-weight:600;
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
.table-grid th.col-actions,
.table-grid td.col-actions{
  width:90px;
  text-align:center;
}
.table-grid th.col-check,
.table-grid td.col-check{
  width:30px;
  text-align:center;
}
.table-grid .text-truncate-sm{
  max-width:170px;
  overflow:hidden;
  text-overflow:ellipsis;
  white-space:nowrap;
}
.nav-tabs-sm .nav-link{
  padding:.25rem .75rem;
  font-size:.8rem;
}
#etlSpinner{
  background:rgba(0,0,0,.35);
}
.table-grid tbody tr.table-active{
  background:#dde9ff;
}
</style>
</head>
<body>
<div class="ap-topbar py-2 mb-4">
  <div class="container d-flex justify-content-between align-items-center">
    <div>
      <div class="d-flex align-items-center gap-2">
        <span class="badge bg-dark ap-badge">AssistPro ETL</span>
        <h1>Explorador Global de Orígenes</h1>
      </div>
      <div class="small">
        Mapeo Origen ↔ Destino · Comentarios funcionales · utf8mb4_unicode_ci por defecto
      </div>
    </div>
    <div class="text-end small">
      <div>Conexión: <span class="fw-semibold mono"><?=htmlspecialchars($alias)?></span></div>
      <?php if($currentConn): ?>
        <div>Nombre amigable: <span class="fw-semibold"><?=htmlspecialchars($currentConn['fname'])?></span></div>
      <?php endif; ?>
      <div>Destino: <span class="fw-semibold mono">assistpro_etl_fc</span></div>
      <div class="mt-1">
        <a href="administrador_procesos.php" class="btn btn-sm btn-outline-light">
          <i class="bi bi-diagram-3"></i> Administrador de procesos
        </a>
      </div>
    </div>
  </div>
</div>

<div class="container pb-4">
  <?php if($msg):?>
    <div class="alert alert-success shadow-sm"><?=$msg?></div>
  <?php endif;?>
  <?php if($err):?>
    <div class="alert alert-danger shadow-sm"><?=$err?></div>
  <?php endif;?>

  <form class="card mb-3" method="get">
    <div class="card-body">
      <div class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label small-label">Conexión remota</label>
          <select name="alias" class="form-select" onchange="this.form.submit()">
            <?php foreach($cons as $c): ?>
              <option value="<?=$c['alias']?>" <?=$alias===$c['alias']?'selected':''?>>
                <?=$c['fname']?><?= $c['db_name'] ? " ({$c['db_name']})" : "" ?> — <?=$c['alias']?>
              </option>
            <?php endforeach;?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small-label">Empresa (referencia)</label>
          <select name="empresa_id" class="form-select" onchange="this.form.submit()">
            <?php foreach($empresas as $e): ?>
              <option value="<?=$e['empresa_id']?>" <?=$empresa_id===$e['empresa_id']?'selected':''?>>
                <?=$e['name']?> (ID <?=$e['empresa_id']?>)
              </option>
            <?php endforeach;?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small-label">Base de datos remota</label>
          <select name="db" class="form-select" onchange="this.form.submit()">
            <option value="">(elige)</option>
            <?php foreach($bases as $b): $name = array_values($b)[0]; ?>
              <option value="<?=$name?>" <?=$dbname===$name?'selected':''?>><?=$name?></option>
            <?php endforeach;?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small-label d-block">Mostrar objetos</label>
          <div class="d-flex gap-2 flex-wrap">
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="checkbox" id="show_tbl" name="show_tbl" value="1" <?=$showTbl?'checked':''?> onchange="this.form.submit()">
              <label class="form-check-label small-label" for="show_tbl">Tablas</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="checkbox" id="show_view" name="show_view" value="1" <?=$showView?'checked':''?> onchange="this.form.submit()">
              <label class="form-check-label small-label" for="show_view">Vistas</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="checkbox" id="show_sp" name="show_sp" value="1" <?=$showSp?'checked':''?> onchange="this.form.submit()">
              <label class="form-check-label small-label" for="show_sp">SPs</label>
            </div>
          </div>
        </div>
      </div>

      <?php if($dbname): ?>
      <div class="row mt-3 small">
        <div class="col-md-6">
          <span class="small-label">Codificación origen (BD)</span><br>
          <span class="mono">
            <?=htmlspecialchars($originCharset ?: 'N/D')?> /
            <?=htmlspecialchars($originCollation ?: 'N/D')?>
          </span>
        </div>
        <div class="col-md-6 text-md-end">
          <span class="small-label">Codificación destino (BD)</span><br>
          <span class="mono">
            <?=htmlspecialchars($destCharset ?: 'N/D')?> /
            <?=htmlspecialchars($destCollation ?: 'N/D')?>
          </span>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </form>

  <?php if($dbname): ?>
  <div class="row g-3">
    <div class="col-12">
      <div class="card">
        <div class="card-header bg-white border-0">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
              <div class="small-label mb-1">Mapa de objetos origen</div>
              <div class="fw-semibold mono"><?=$dbname?></div>
              <div class="small text-muted mt-1">
                Tablas: <?=$countTablesOrigin?> · Vistas: <?=$countViewsOrigin?> · SP: <?=$countSPOrigin?>
              </div>
            </div>
            <span class="badge bg-primary-subtle text-primary border">Origen</span>
          </div>
          <ul class="nav nav-tabs nav-tabs-sm" role="tablist">
            <li class="nav-item" role="presentation">
              <a class="nav-link <?=$activeSection==='tb'?'active':''?>"
                 href="<?=$baseListUrl.'&page=1&sec=tb'?>">Tablas</a>
            </li>
            <li class="nav-item" role="presentation">
              <a class="nav-link <?=$activeSection==='vw'?'active':''?>"
                 href="<?=$baseListUrl.'&page=1&sec=vw'?>">Vistas</a>
            </li>
            <li class="nav-item" role="presentation">
              <a class="nav-link <?=$activeSection==='sp'?'active':''?>"
                 href="<?=$baseListUrl.'&page=1&sec=sp'?>">Stored Procedures</a>
            </li>
          </ul>
        </div>
        <div class="card-body pt-2">

          <?php if(in_array($activeSection,['tb','vw'],true)): ?>
          <!-- Sección Tablas / Vistas -->
          <form method="get" class="mb-2">
            <input type="hidden" name="alias" value="<?=htmlspecialchars($alias)?>">
            <input type="hidden" name="empresa_id" value="<?=$empresa_id?>">
            <input type="hidden" name="db" value="<?=htmlspecialchars($dbname)?>">
            <input type="hidden" name="show_tbl" value="<?=$showTbl?1:0?>">
            <input type="hidden" name="show_view" value="<?=$showView?1:0?>">
            <input type="hidden" name="show_sp" value="<?=$showSp?1:0?>">
            <input type="hidden" name="sec" value="<?=htmlspecialchars($activeSection)?>">
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input type="text" class="form-control mono" name="objsearch"
                     placeholder="Buscar objeto..." value="<?=htmlspecialchars($objSearch)?>">
            </div>
          </form>

          <!-- Formulario bulk -->
          <form id="bulkForm" method="post"
                action="<?=$baseListUrl.'&page='.$page?>">

            <input type="hidden" name="bulk_action" id="bulk_action" value="">
            <input type="hidden" name="force_unicode" value="<?=$forceUnicode?1:0?>">
            <input type="hidden" name="batch" value="<?=$batch?>">

            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
              <div class="small-label">
                Objetos (página <?=$page?> de <?=$totalPages?>)
                · <?=($activeSection==='tb'?'Tablas':'Vistas')?> encontradas: <?=$totalRows?>
              </div>
              <div class="d-flex gap-2 flex-wrap align-items-center">
                <div class="d-flex align-items-center gap-1">
                  <span class="small-label">Destino origen →</span>
                  <select name="bulk_use_stg" id="bulk_use_stg" class="form-select form-select-sm">
                    <option value="1" <?=$bulkUseStg? 'selected':''?>>Con prefijo stg_</option>
                    <option value="0" <?=!$bulkUseStg? 'selected':''?>>Sin prefijo</option>
                  </select>
                </div>
                <button type="button"
                        class="btn btn-sm btn-outline-primary"
                        onclick="submitBulkFull()">
                  Volcar FULL (origen seleccionadas)
                </button>
                <button type="button"
                        class="btn btn-sm btn-outline-secondary"
                        onclick="submitExportDest()">
                  Exportar destino (estructura)
                </button>
                <button type="button"
                        class="btn btn-sm btn-success"
                        onclick="openProcessModal()">
                  Crear proceso con selección
                </button>
              </div>
            </div>

            <div class="table-grid-wrapper">
              <table class="table-grid">
                <thead>
                  <tr>
                    <th class="col-check">
                      <input type="checkbox" onclick="toggleBulkAll(this)">
                    </th>
                    <th>Origen</th>
                    <th>Tipo</th>
                    <th>Codificación origen</th>
                    <th>Codificación destino</th>
                    <th>Destino equivalente</th>
                    <th class="col-check">Sel. destino</th>
                    <th>Última actualización</th>
                    <th>Actualización automática</th>
                    <th>Comentario</th>
                    <th>Procesos donde se usa</th>
                    <th class="col-actions">ETL</th>
                  </tr>
                </thead>
                <tbody>
                <?php
                if(!$pageSlice):
                ?>
                  <tr><td colspan="12" class="text-muted text-center">No se encontraron objetos.</td></tr>
                <?php
                else:
                  foreach($pageSlice as $row):
                    $objName = array_values($row)[0];
                    $type = strtoupper($row['Table_type'] ?? $row['TABLE_TYPE'] ?? '');
                    $typeLabel = ($type === 'VIEW') ? 'Vista' : 'Tabla';

                    // Mapeo destino (case-insensitive)
                    $variants = [
                      $objName,
                      id_safe($objName),
                      'stg_'.$objName,
                      'stg_'.id_safe($objName),
                    ];
                    $matched = [];
                    foreach ($variants as $vn) {
                      $lk = strtolower($vn);
                      if (isset($destTableIndexLower[$lk])) {
                        $matched[$destTableIndexLower[$lk]] = true;
                      }
                    }
                    $existsDest = !empty($matched);
                    $matchedNamesStr = implode(' / ', array_keys($matched));
                    $firstDest = null;
                    if ($existsDest) {
                        foreach ($matched as $name => $_tmp) { $firstDest = $name; break; }
                    }

                    // codificación por tabla origen/destino
                    $collOrigin = $tableCollations[$objName] ?? '—';
                    $destColl   = '—';
                    if ($firstDest && isset($destTableCollations[$firstDest])) {
                        $destColl = $destTableCollations[$firstDest] ?: '—';
                    }

                    $meta  = $metaByObject[$objName] ?? [];
                    $lastA = $meta['last_action_at'] ?? null;
                    $autoU = !empty($meta['auto_update']);
                    $autoC = $meta['auto_update_cron'] ?? '';
                ?>
                  <tr>
                    <td class="col-check">
                      <input type="checkbox"
                             class="chk-origin"
                             name="bulk_objects[]"
                             value="<?=htmlspecialchars($objName)?>">
                    </td>
                    <td class="mono">
                      <a href="<?=$baseListUrl.'&page='.$page.'&obj='.urlencode($objName).'&openetl=1'?>">
                        <?=$objName?>
                      </a>
                    </td>
                    <td><?=$typeLabel?></td>
                    <td class="mono small"><?=htmlspecialchars($collOrigin ?: '—')?></td>
                    <td class="mono small"><?=htmlspecialchars($destColl ?: '—')?></td>
                    <td>
                      <?php if($existsDest): ?>
                        <span class="badge bg-success-subtle text-success border badge-dest-exists">
                          <i class="bi bi-check-circle"></i> <?=htmlspecialchars($matchedNamesStr)?>
                        </span>
                      <?php else: ?>
                        <span class="text-muted">No encontrado</span>
                      <?php endif; ?>
                    </td>
                    <td class="col-check">
                      <?php if($firstDest): ?>
                        <input type="checkbox" name="dest_tables[]" value="<?=htmlspecialchars($firstDest)?>">
                      <?php else: ?>
                        <input type="checkbox" disabled>
                      <?php endif; ?>
                    </td>
                    <td class="small">
                      <?= $lastA ? htmlspecialchars($lastA) : '—' ?>
                    </td>
                    <td class="small">
                      <?php if($autoU): ?>
                        Sí<?= $autoC ? ' · '.htmlspecialchars($autoC) : '' ?>
                      <?php else: ?>
                        No
                      <?php endif; ?>
                    </td>
                    <td title="<?=htmlspecialchars((string)($meta['comment'] ?? ''))?>">
                      <span class="mono text-truncate-sm"><?= ($meta['comment'] ?? '') ?: '—' ?></span>
                    </td>
                    <td title="<?=htmlspecialchars((string)($meta['procesos'] ?? ''))?>">
                      <span class="mono text-truncate-sm"><?= ($meta['procesos'] ?? '') ?: '—' ?></span>
                    </td>
                    <td class="col-actions">
                      <a class="btn btn-sm btn-outline-primary"
                         href="<?=$baseListUrl.'&page='.$page.'&obj='.urlencode($objName).'&openetl=1'?>">
                        ETL / Volcar
                      </a>
                    </td>
                  </tr>
                <?php
                  endforeach;
                endif;
                ?>
                </tbody>
              </table>
            </div>

            <?php if($totalPages > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-2">
              <div class="small text-muted">
                Página <?=$page?> de <?=$totalPages?> · <?=$totalRows?> objetos
              </div>
              <nav>
                <ul class="pagination pagination-sm mb-0">
                  <li class="page-item <?=$page<=1?'disabled':''?>">
                    <a class="page-link"
                       href="<?=$page<=1?'#':htmlspecialchars($baseListUrl.'&page='.($page-1))?>">«</a>
                  </li>
                  <li class="page-item <?=$page>=$totalPages?'disabled':''?>">
                    <a class="page-link"
                       href="<?=$page>=$totalPages?'#':htmlspecialchars($baseListUrl.'&page='.($page+1))?>">»</a>
                  </li>
                </ul>
              </nav>
            </div>
            <?php endif; ?>

          </form>
          <!-- fin sección Tablas/Vistas -->
          <?php endif; ?>

          <?php if($activeSection === 'sp' && $showSp): ?>
          <!-- Sección Stored Procedures -->
          <form method="get" class="mb-2">
            <input type="hidden" name="alias" value="<?=htmlspecialchars($alias)?>">
            <input type="hidden" name="empresa_id" value="<?=$empresa_id?>">
            <input type="hidden" name="db" value="<?=htmlspecialchars($dbname)?>">
            <input type="hidden" name="show_tbl" value="<?=$showTbl?1:0?>">
            <input type="hidden" name="show_view" value="<?=$showView?1:0?>">
            <input type="hidden" name="show_sp" value="<?=$showSp?1:0?>">
            <input type="hidden" name="sec" value="sp">
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input type="text" class="form-control mono" name="objsearch"
                     placeholder="Buscar SP..." value="<?=htmlspecialchars($objSearch)?>">
            </div>
          </form>

          <div class="mt-2">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="small-label mb-0">Stored Procedures</h6>
              <div class="small text-muted">
                Página <?=$page?> de <?=$spTotalPages?> · <?=$spTotalRows?> SPs
              </div>
            </div>
            <div class="table-responsive" style="max-height:360px;overflow:auto">
              <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Nombre</th>
                    <th class="text-center">Copiar a destino</th>
                  </tr>
                </thead>
                <tbody class="small mono">
                  <?php if(!$spSlice): ?>
                    <tr><td colspan="2" class="text-muted text-center">No se encontraron SPs con el filtro actual.</td></tr>
                  <?php else:
                    foreach($spSlice as $n): ?>
                    <tr>
                      <td><?=$n?></td>
                      <td class="text-center">
                        <form method="post"
                              action="<?=$baseListUrl.'&page='.$page.'&sp='.urlencode($n).'&sec=sp'?>"
                              onsubmit="showSpinner('Copiando SP','Creando/actualizando el procedimiento en destino...')">
                          <button name="action" value="copy_sp" class="btn btn-sm btn-outline-primary">
                            Copiar
                          </button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>

            <?php if($spTotalPages>1): ?>
            <div class="d-flex justify-content-end align-items-center mt-2">
              <nav>
                <ul class="pagination pagination-sm mb-0">
                  <li class="page-item <?=$page<=1?'disabled':''?>">
                    <a class="page-link"
                       href="<?=$page<=1?'#':htmlspecialchars($baseListUrl.'&page='.($page-1))?>">«</a>
                  </li>
                  <li class="page-item <?=$page>=$spTotalPages?'disabled':''?>">
                    <a class="page-link"
                       href="<?=$page>=$spTotalPages?'#':htmlspecialchars($baseListUrl.'&page='.($page+1))?>">»</a>
                  </li>
                </ul>
              </nav>
            </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Modal ETL / Volcado individual -->
<div class="modal fade" id="etlModal" tabindex="-1" aria-labelledby="etlModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <form method="post"
          action="<?=$baseListUrl.'&page='.$page.'&openetl=1'?>"
          class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title" id="etlModalLabel">ETL / Volcado de tabla</h5>
          <div class="small text-muted">
            Origen: <span class="mono" id="modal_object_label"></span>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="object" id="modal_object">

        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label small-label">Tabla destino (local)</label>
            <input name="target" id="modal_target" class="form-control mono" value="<?=$target?>">
          </div>
          <div class="col-md-3">
            <label class="form-label small-label d-block">Prefijo destino</label>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="use_stg" id="use_stg_on" value="1" <?=$useStg?'checked':''?>>
              <label class="form-check-label small-label" for="use_stg_on">stg_ + nombre</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="use_stg" id="use_stg_off" value="0" <?=!$useStg?'checked':''?>>
              <label class="form-check-label small-label" for="use_stg_off">Sin prefijo</label>
            </div>
          </div>
          <div class="col-md-2">
            <label class="form-label small-label">Preview filas</label>
            <input name="previewN" id="modal_previewN" type="number" class="form-control" value="<?=$previewN?>">
          </div>
          <div class="col-md-2">
            <label class="form-label small-label">Muestra (N)</label>
            <input name="sampleN" id="modal_sampleN" type="number" class="form-control" value="<?=$sampleN?>">
          </div>
          <div class="col-md-1">
            <label class="form-label small-label">Batch</label>
            <input name="batch" id="modal_batch" type="number" class="form-control" value="<?=$batch?>">
          </div>

          <div class="col-md-3">
            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" id="modal_force_unicode" name="force_unicode" value="1" <?=$forceUnicode?'checked':''?>>
              <label class="form-check-label small-label" for="modal_force_unicode">
                Forzar destino utf8mb4_unicode_ci
              </label>
            </div>
          </div>

          <div class="col-md-3">
            <label class="form-label small-label">PK (para merge/INCR)</label>
            <input name="pk" id="modal_pk" class="form-control mono" value="<?=$pk?>" placeholder="id">
          </div>
          <div class="col-md-3">
            <label class="form-label small-label">Columna incremental</label>
            <input name="updcol" id="modal_updcol" class="form-control mono" value="<?=$updcol?>" placeholder="updated_at">
          </div>
          <div class="col-md-3">
            <label class="form-label small-label">Comportamiento FULL</label>
            <select name="full_mode" id="modal_full_mode" class="form-select form-select-sm">
              <option value="truncate" <?=$fullMode==='truncate'?'selected':''?>>
                Reemplazar (TRUNCATE + INSERT)
              </option>
              <option value="merge" <?=$fullMode==='merge'?'selected':''?>>
                Actualizar (UPSERT por PK)
              </option>
            </select>
          </div>
        </div>

        <hr>

        <div class="row g-2 mb-2">
          <div class="col-md-6">
            <label class="form-label small-label">Comentario / descripción funcional</label>
            <textarea name="meta_comment" id="modal_meta_comment" rows="3" class="form-control mono"
                      placeholder="Ej. Tabla de pedidos históricos, base de vistas legacy v_pedidos_cliente, v_backorder, etc."></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label small-label">Procesos / vistas donde se usa</label>
            <textarea name="meta_procesos" id="modal_meta_procesos" rows="3" class="form-control mono"
                      placeholder="Ej. Reporte ABC, job nocturno ETL_xx, vista legacy v_xx, integración con WMS, etc."></textarea>
          </div>
        </div>

        <div class="row g-2 mb-2">
          <div class="col-md-3">
            <label class="form-label small-label d-block">Actualización automática</label>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="modal_auto_update" name="auto_update" value="1">
              <label class="form-check-label small-label" for="modal_auto_update">
                Habilitar para esta tabla
              </label>
            </div>
          </div>
          <div class="col-md-9">
            <label class="form-label small-label">Expresión / frecuencia (cron)</label>
            <input name="auto_update_cron" id="modal_auto_cron" class="form-control mono"
                   placeholder="Ej. Diario 02:00 · CRON: 0 2 * * * · Cada 15 min, etc.">
          </div>
        </div>

        <?php if($columns): ?>
        <hr>
        <h6 class="small-label mb-2">Estructura de tabla origen</h6>
        <div class="table-responsive" style="max-height:260px;overflow:auto">
          <table class="table table-sm table-striped table-bordered mono mb-0">
            <thead class="table-light">
              <tr>
                <th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($columns as $c): ?>
              <tr>
                <td><?=$c['Field']?></td>
                <td><?=$c['Type']?></td>
                <td><?=$c['Null']?></td>
                <td><?=$c['Key']?></td>
                <td><?=htmlspecialchars((string)$c['Default'])?></td>
                <td><?=$c['Extra']?></td>
              </tr>
              <?php endforeach;?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>

        <?php if($preview): ?>
        <hr>
        <h6 class="small-label mb-2">Preview de datos (origen)</h6>
        <div class="table-responsive" style="max-height:260px;overflow:auto">
          <table class="table table-sm table-striped table-bordered mono mb-0">
            <thead class="table-light">
              <tr>
                <?php $heads = array_keys($preview[0] ?? []); foreach($heads as $h): ?>
                  <th class="mono"><?=$h?></th>
                <?php endforeach;?>
              </tr>
            </thead>
            <tbody>
              <?php foreach($preview as $r): ?>
              <tr>
                <?php foreach($heads as $h): $v = $r[$h] ?? null; ?>
                  <td class="mono"><?=htmlspecialchars((string)$v)?></td>
                <?php endforeach;?>
              </tr>
              <?php endforeach;?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>

        <div class="mt-3 small text-muted">
          <ul class="mb-0">
            <li><strong>Prefijo destino</strong>: define si la tabla se crea/actualiza como <code>stg_*</code> o con el mismo nombre de origen.</li>
            <li><strong>Preview</strong>: solo lectura, hasta N filas (en este mismo modal).</li>
            <li><strong>Muestra (N)</strong>: TRUNCATE destino + inserta N filas.</li>
            <li><strong>FULL</strong>:
              <ul>
                <li>Reemplazar: TRUNCATE + inserta todo.</li>
                <li>Actualizar: UPSERT por PK (no borra, requiere PK).</li>
              </ul>
            </li>
            <li><strong>Incremental</strong>: UPSERT por PK donde <code>updcol</code> &gt; máximo actual en destino.</li>
          </ul>
        </div>
      </div>
      <div class="modal-footer justify-content-between">
        <div class="small text-muted">
          Los comentarios y parámetros de actualización automática se guardan junto con la acción ETL para esta tabla.
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <button name="action" value="preview" class="btn btn-sm btn-outline-secondary"
                  onclick="showSpinner('Preview de datos','Leyendo filas desde la tabla origen...')">
            Preview
          </button>
          <button name="action" value="copy_sample" class="btn btn-sm btn-info text-white"
                  onclick="showSpinner('Copia de muestra','Truncando destino y copiando N filas representativas...')">
            Copiar muestra (N)
          </button>
          <button name="action" value="copy_full" class="btn btn-sm btn-primary"
                  onclick="showSpinner('Copia FULL','Truncando/actualizando destino y copiando toda la tabla...')">
            Copiar FULL
          </button>
          <button name="action" value="copy_incremental" class="btn btn-sm btn-warning"
                  onclick="showSpinner('Copia incremental','Aplicando UPSERT por PK usando columna incremental...')">
            Copiar INCREMENTAL
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Modal creación de proceso funcional -->
<div class="modal fade" id="processModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form method="post" class="modal-content"
          action="<?=$baseListUrl.'&page='.$page?>">
      <input type="hidden" name="process_action" value="create_process">
      <input type="hidden" name="process_objects" id="process_objects">
      <input type="hidden" name="process_export_txt" id="process_export_txt" value="0">

      <div class="modal-header">
        <h5 class="modal-title">Nuevo proceso funcional</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label small-label">Nombre del proceso</label>
          <input type="text" name="process_name" id="process_name"
                 class="form-control" placeholder="Ej. Reabasto por ubicación, Picking OTS, Corte nocturno WMS" required>
        </div>
        <div class="mb-2">
          <label class="form-label small-label">Descripción / objetivo</label>
          <textarea name="process_desc" id="process_desc" rows="3"
                    class="form-control"
                    placeholder="Ej. Proceso que genera el dashboard de existencias por ubicación y alimenta las vistas de existencias en AssistPro."></textarea>
        </div>
        <div class="mb-2">
          <label class="form-label small-label">
            Tablas / vistas origen asociadas
          </label>
          <div class="border rounded p-2" style="max-height:220px;overflow:auto">
            <ul class="mb-0 small mono" id="process_objects_list"></ul>
          </div>
          <div class="small text-muted mt-1">
            La selección se mantiene entre páginas; aquí se listan todas las tablas/vistas marcadas
            en la sección actual (Tablas/Vistas) para <strong><?=htmlspecialchars($dbname)?></strong>.
          </div>
        </div>
      </div>
      <div class="modal-footer justify-content-between">
        <div class="small text-muted">
          Al guardar, se actualiza también la columna “Procesos donde se usa” de cada tabla/vista seleccionada.
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <button type="submit" class="btn btn-success"
                  onclick="document.getElementById('process_export_txt').value='0'">
            Guardar proceso
          </button>
          <button type="submit" class="btn btn-outline-success"
                  onclick="document.getElementById('process_export_txt').value='1'">
            Guardar y exportar TXT
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Spinner global -->
<div id="etlSpinner" class="position-fixed top-0 start-0 w-100 h-100 d-none" style="z-index:2000;">
  <div class="d-flex flex-column align-items-center justify-content-center h-100 text-white">
    <div class="spinner-border mb-3" role="status"></div>
    <div id="etlSpinnerText" class="fw-semibold"></div>
    <div id="etlSpinnerHint" class="small text-center px-3 mt-1"></div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
window.etlMeta = <?=json_encode($metaByObject, JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?>;
window.currentObject = <?=json_encode($object ?? '', JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?>;
window.autoOpenEtl = <?=($autoOpenEtl && $object) ? 'true' : 'false'?>;
window.clearSelOnLoad = <?=$clearSelectionOnLoad ? 'true' : 'false'?>;

// Clave para selección persistente por conexión + BD + sección
const SEL_KEY = 'etlSel_' +
  <?=json_encode($alias, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?> + '_' +
  <?=json_encode($dbname, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?> + '_' +
  <?=json_encode($activeSection, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?>;

function loadSelectedObjects() {
  try {
    const raw = localStorage.getItem(SEL_KEY);
    if (!raw) return [];
    const arr = JSON.parse(raw);
    return Array.isArray(arr) ? arr : [];
  } catch (e) {
    return [];
  }
}

function saveSelectedObjects(list) {
  const uniq = Array.from(new Set(list.filter(x => x && x.trim() !== '')));
  localStorage.setItem(SEL_KEY, JSON.stringify(uniq));
}

function syncCheckboxesFromStorage() {
  const selected = loadSelectedObjects();
  const set = new Set(selected);
  document.querySelectorAll('.chk-origin').forEach(ch => {
    if (set.has(ch.value)) {
      ch.checked = true;
      ch.closest('tr')?.classList.add('table-active');
    } else {
      ch.checked = false;
      ch.closest('tr')?.classList.remove('table-active');
    }
  });
}

function openEtlModal(objectName){
  const obj = objectName || '';

  document.getElementById('modal_object_label').textContent = obj;
  document.getElementById('modal_object').value = obj;

  const useStgRadio = document.querySelector('input[name="use_stg"]:checked');
  const useStg = useStgRadio ? useStgRadio.value === '1' : true;

  const targetInput = document.getElementById('modal_target');
  if (!targetInput.value || targetInput.value === '' || targetInput.value === '<?=$target?>') {
    const baseName = obj.replace(/[^a-zA-Z0-9_]+/g,'_');
    targetInput.value = useStg ? ('stg_' + baseName) : baseName;
  }

  const meta = (window.etlMeta && window.etlMeta[obj]) ? window.etlMeta[obj] : null;
  document.getElementById('modal_meta_comment').value  = meta && meta.comment  ? meta.comment  : '';
  document.getElementById('modal_meta_procesos').value = meta && meta.procesos ? meta.procesos : '';
  document.getElementById('modal_auto_update').checked = meta && meta.auto_update == 1;
  document.getElementById('modal_auto_cron').value      = meta && meta.auto_update_cron ? meta.auto_update_cron : '';

  const modalEl = document.getElementById('etlModal');
  const modal = new bootstrap.Modal(modalEl);
  modal.show();
}

document.addEventListener('DOMContentLoaded', function(){
  // Si venimos de crear un proceso y se debe limpiar la selección
  if (window.clearSelOnLoad) {
    localStorage.removeItem(SEL_KEY);
  }

  // Sincronizar selección persistente
  syncCheckboxesFromStorage();

  // Escuchar cambios de cada checkbox de origen
  document.querySelectorAll('.chk-origin').forEach(ch => {
    ch.addEventListener('change', function() {
      let sel = loadSelectedObjects();
      if (this.checked) {
        sel.push(this.value);
      } else {
        sel = sel.filter(v => v !== this.value);
      }
      saveSelectedObjects(sel);
      syncCheckboxesFromStorage();
    });
  });

  // Rellenar modal ETL si viene openetl=1
  if (window.autoOpenEtl && window.currentObject) {
    openEtlModal(window.currentObject);
  }

  // Sincronizar radio de prefijo destino con target (en modal)
  document.querySelectorAll('input[name="use_stg"]').forEach(function(r){
    r.addEventListener('change', function(){
      const obj = document.getElementById('modal_object').value || '';
      if (!obj) return;
      const baseName = obj.replace(/[^a-zA-Z0-9_]+/g,'_');
      const useStg = this.value === '1';
      document.getElementById('modal_target').value = useStg ? ('stg_' + baseName) : baseName;
    });
  });
});

function toggleBulkAll(master){
  const form = document.getElementById('bulkForm');
  if (!form) return;
  const checks = form.querySelectorAll('.chk-origin');
  let sel = loadSelectedObjects();
  if (master.checked) {
    checks.forEach(ch => {
      if (!ch.checked) {
        ch.checked = true;
        sel.push(ch.value);
      }
    });
  } else {
    checks.forEach(ch => {
      if (ch.checked) {
        ch.checked = false;
      }
    });
    const pageValues = Array.from(checks).map(ch => ch.value);
    sel = sel.filter(v => !pageValues.includes(v));
  }
  saveSelectedObjects(sel);
  syncCheckboxesFromStorage();
}

function showSpinner(title, hint){
  document.getElementById('etlSpinnerText').textContent = title || 'Procesando...';
  document.getElementById('etlSpinnerHint').textContent = hint || '';
  document.getElementById('etlSpinner').classList.remove('d-none');
}

function submitBulkFull(){
  const form = document.getElementById('bulkForm');
  if (!form) return;
  const anyChecked = form.querySelector('input[name="bulk_objects[]"]:checked');
  if (!anyChecked) {
    alert('Selecciona al menos una tabla o vista de origen para volcar.');
    return;
  }
  document.getElementById('bulk_action').value = 'bulk_full';
  const sel = document.getElementById('bulk_use_stg');
  const withStg = !sel || sel.value === '1';
  const hint = withStg
    ? 'Se crearán/actualizarán tablas destino con prefijo stg_ copiando toda la información.'
    : 'Se crearán/actualizarán tablas destino usando el mismo nombre de origen (sin stg_) copiando toda la información.';
  showSpinner('Copia FULL masiva', hint);
  form.submit();
}

function submitExportDest(){
  const form = document.getElementById('bulkForm');
  if (!form) return;
  const anyDest = form.querySelector('input[name="dest_tables[]"]:checked');
  if (!anyDest) {
    alert('Selecciona al menos una tabla destino para exportar su estructura.');
    return;
  }
  document.getElementById('bulk_action').value = 'export_dest';
  showSpinner('Exportando estructuras destino','Generando archivo de texto con la definición de las tablas seleccionadas...');
  form.submit();
}

function openProcessModal(){
  const sel = loadSelectedObjects();
  if (!sel.length) {
    alert('Marca al menos una tabla o vista de origen para asociarla al proceso.');
    return;
  }
  const ul = document.getElementById('process_objects_list');
  ul.innerHTML = '';
  sel.forEach(name => {
    const li = document.createElement('li');
    li.textContent = name;
    ul.appendChild(li);
  });
  document.getElementById('process_objects').value = sel.join(',');

  const modalEl = document.getElementById('processModal');
  const modal   = new bootstrap.Modal(modalEl);
  modal.show();
}
</script>
</body>
</html>
