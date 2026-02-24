<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

function jerr($msg, $det = null)
{
  echo json_encode(['error' => $msg, 'detalles' => $det], JSON_UNESCAPED_UNICODE);
  exit;
}
function clean($v)
{
  return trim((string)$v);
}
function norm01($v, $def = '1')
{
  $v = clean($v);
  if ($v === '') return $def;
  return ($v === '1') ? '1' : '0';
}

function fecha_mysql($f)
{
  $f = trim($f);
  if ($f === '') return null;

  # Si viene en formato dd/mm/yyyy
  if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $f, $m)) {
    return $m[3] . '-' . $m[2] . '-' . $m[1];
  }

  # Si ya viene yyyy-mm-dd lo dejamos igual
  return $f;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {

  # =========================================================
  # LIST
  # =========================================================
  if ($action === 'list') {

    $q = clean($_GET['q'] ?? '');
    $inactivos = (int)($_GET['inactivos'] ?? 0);

    $where = [];
    $p = [];

    if (!$inactivos) {
      $where[] = "p.activo = 1";
    }

    if ($q !== '') {
      $where[] = "(
            p.clave_perfil LIKE :q
            OR p.nombre_perfil LIKE :q
            OR c.clave_empresa LIKE :q
            OR c.des_cia LIKE :q
        )";
      $p[':q'] = "%$q%";
    }

    $sql = "
        SELECT 
            p.id_perfil,
            p.clave_perfil,
            p.nombre_perfil,
            p.id_compania,
            p.activo,
            p.inicio_perfil,
            p.fin_perfil,
            c.clave_empresa,
            c.des_cia
        FROM t_perfilesusuarios p
        LEFT JOIN c_compania c
            ON c.cve_cia = p.id_compania
    ";

    if ($where) {
      $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY p.activo DESC, p.nombre_perfil ASC LIMIT 3000";

    echo json_encode(['rows' => db_all($sql, $p)], JSON_UNESCAPED_UNICODE);
    exit;
  }

  # =========================================================
  # GET
  # =========================================================
  if ($action === 'get') {

    $id = (int)($_GET['id_perfil'] ?? 0);
    if (!$id) jerr('Llave inválida (id_perfil)');

    $row = db_one("
        SELECT *
        FROM t_perfilesusuarios
        WHERE id_perfil = :id
        LIMIT 1
    ", [':id' => $id]);

    if (!$row) jerr('No existe el registro');

    echo json_encode($row, JSON_UNESCAPED_UNICODE);
    exit;
  }

  # =========================================================
  # CREATE / UPDATE
  # =========================================================
  if ($action === 'create' || $action === 'update') {

    $id_perfil = (int)($_POST['id_perfil'] ?? 0);

    $clave_perfil   = clean($_POST['clave_perfil'] ?? '');
    $nombre_perfil  = clean($_POST['nombre_perfil'] ?? '');
    $id_compania    = (int)($_POST['id_compania'] ?? 0);
    $activo         = norm01($_POST['activo'] ?? '1', '1');
    $inicio_perfil  = clean($_POST['inicio_perfil'] ?? '');
    $fin_perfil     = clean($_POST['fin_perfil'] ?? '');

    $det = [];
    if ($clave_perfil === '')  $det[] = 'clave_perfil obligatorio.';
    if ($nombre_perfil === '') $det[] = 'nombre_perfil obligatorio.';
    if (!$id_compania)       $det[] = 'id_compania obligatorio.';
    if ($det) jerr('Validación', $det);

    db_tx(function () use (
      $action,
      $id_perfil,
      $clave_perfil,
      $nombre_perfil,
      $id_compania,
      $activo,
      $inicio_perfil,
      $fin_perfil
    ) {

      if ($action === 'create') {

        $ex = db_val("
                SELECT 1 
                FROM t_perfilesusuarios 
                WHERE id_compania=:c 
                  AND clave_perfil=:k 
                LIMIT 1
            ", [':c' => $id_compania, ':k' => $clave_perfil]);

        if ($ex) throw new Exception("Ya existe esa clave para la compañía.");

        dbq("
                INSERT INTO t_perfilesusuarios
                (clave_perfil,nombre_perfil,id_compania,activo,inicio_perfil,fin_perfil)
                VALUES
                (:k,:n,:c,:a,:i,:f)
            ", [
          ':k' => $clave_perfil,
          ':n' => $nombre_perfil,
          ':c' => $id_compania,
          ':a' => $activo,
          ':i' => ($inicio_perfil ?: null),
          ':f' => ($fin_perfil ?: null)
        ]);
      } else {

        if (!$id_perfil) throw new Exception("id_perfil inválido.");

        dbq("
                UPDATE t_perfilesusuarios
                SET clave_perfil=:k,
                    nombre_perfil=:n,
                    id_compania=:c,
                    activo=:a,
                    inicio_perfil=:i,
                    fin_perfil=:f
                WHERE id_perfil=:id
            ", [
          ':k' => $clave_perfil,
          ':n' => $nombre_perfil,
          ':c' => $id_compania,
          ':a' => $activo,
          ':i' => ($inicio_perfil ?: null),
          ':f' => ($fin_perfil ?: null),
          ':id' => $id_perfil
        ]);
      }
    });

    echo json_encode(['ok' => 1], JSON_UNESCAPED_UNICODE);
    exit;
  }

  # =========================================================
  # DELETE / RESTORE (lógico)
  # =========================================================
  if ($action === 'delete' || $action === 'restore') {

    $id = (int)($_POST['id_perfil'] ?? 0);
    if (!$id) jerr('Llave inválida (id_perfil)');

    $val = ($action === 'delete') ? 0 : 1;

    dbq("
        UPDATE t_perfilesusuarios
        SET activo = :v
        WHERE id_perfil = :id
    ", [':v' => $val, ':id' => $id]);

    echo json_encode(['ok' => 1], JSON_UNESCAPED_UNICODE);
    exit;
  }

  # =========================================================
  # EXPORT
  # =========================================================
  if ($action === 'export') {

    header_remove('Content-Type');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=perfiles_export.csv');

    $rows = db_all("
        SELECT 
            p.clave_perfil,
            p.nombre_perfil,
            c.clave_empresa,
            c.des_cia,
            p.activo,
            p.inicio_perfil,
            p.fin_perfil
        FROM t_perfilesusuarios p
        LEFT JOIN c_compania c
            ON c.cve_cia = p.id_compania
        ORDER BY p.nombre_perfil
    ");

    $out = fopen('php://output', 'w');
    fputcsv($out, array_keys($rows ? $rows[0] : [
      'clave_perfil' => '',
      'nombre_perfil' => '',
      'clave_empresa' => '',
      'des_cia' => '',
      'activo' => '',
      'inicio_perfil' => '',
      'fin_perfil' => ''
    ]));
    foreach ($rows as $r) fputcsv($out, $r);
    fclose($out);
    exit;
  }

  # =========================================================
  # IMPORT PREVIEW / IMPORT
  # =========================================================
  if ($action === 'import_preview' || $action === 'import') {

    if (!isset($_FILES['csv'])) jerr('No se recibió CSV');

    $tmp = $_FILES['csv']['tmp_name'];
    if (!is_uploaded_file($tmp)) jerr('Archivo inválido');

    $fh = fopen($tmp, 'r');
    if (!$fh) jerr('No se pudo leer');

    $header = fgetcsv($fh);
    if (!$header) jerr('CSV vacío');

    $header = array_map('trim', $header);

    $expected = [
      'clave_perfil',
      'nombre_perfil',
      'clave_empresa',
      'des_cia',
      'activo',
      'inicio_perfil',
      'fin_perfil'
    ];

    $diff = array_diff($expected, $header);
    if ($diff) jerr('Layout incorrecto. Faltan columnas: ' . implode(', ', $diff));

    # PREVIEW
    if ($action === 'import_preview') {
      fclose($fh);
      echo json_encode(['ok' => 1]);
      exit;
    }

    rewind($fh);
    fgetcsv($fh);

    $inserted = 0;
    $updated = 0;

    db_tx(function () use ($fh, $header, &$inserted, &$updated) {

      while (($r = fgetcsv($fh)) !== false) {

        $row = array_combine($header, $r);

        $clave_perfil  = trim($row['clave_perfil'] ?? '');
        $nombre_perfil = trim($row['nombre_perfil'] ?? '');
        $clave_empresa = trim($row['clave_empresa'] ?? '');
        $activo        = ((int)($row['activo'] ?? 1) === 1 ? 1 : 0);
        $inicio = fecha_mysql($row['inicio_perfil'] ?? '');
        $fin    = fecha_mysql($row['fin_perfil'] ?? '');

        if ($clave_perfil === '' || $nombre_perfil === '' || $clave_empresa === '') {
          continue;
        }

        # Obtener id_compania por clave_empresa
        $id_compania = db_val("
            SELECT cve_cia 
            FROM c_compania 
            WHERE clave_empresa = :c 
            LIMIT 1
        ", [':c' => $clave_empresa]);

        if (!$id_compania) continue;

        # Verificar si ya existe
        $ex = db_val("
            SELECT id_perfil
            FROM t_perfilesusuarios
            WHERE id_compania = :c
              AND clave_perfil = :k
            LIMIT 1
        ", [
          ':c' => $id_compania,
          ':k' => $clave_perfil
        ]);

        if ($ex) {

          dbq("
              UPDATE t_perfilesusuarios
              SET nombre_perfil=:n,
                  activo=:a,
                  inicio_perfil=:i,
                  fin_perfil=:f
              WHERE id_perfil=:id
          ", [
            ':n' => $nombre_perfil,
            ':a' => $activo,
            ':i' => ($inicio ?: null),
            ':f' => ($fin ?: null),
            ':id' => $ex
          ]);

          $updated++;
        } else {

          dbq("
              INSERT INTO t_perfilesusuarios
              (clave_perfil,nombre_perfil,id_compania,activo,inicio_perfil,fin_perfil)
              VALUES
              (:k,:n,:c,:a,:i,:f)
          ", [
            ':k' => $clave_perfil,
            ':n' => $nombre_perfil,
            ':c' => $id_compania,
            ':a' => $activo,
            ':i' => ($inicio ?: null),
            ':f' => ($fin ?: null)
          ]);

          $inserted++;
        }
      }
    });

    fclose($fh);

    echo json_encode([
      'ok' => 1,
      'inserted' => $inserted,
      'updated' => $updated
    ], JSON_UNESCAPED_UNICODE);

    exit;
  }

  jerr('Acción no soportada: ' . $action);
} catch (Throwable $e) {
  jerr('Error: ' . $e->getMessage());
}
