<?php
require_once __DIR__ . '/../../app/db.php';

header('Content-Type: application/json; charset=utf-8');

function jerr($msg, $detalles = null)
{
  echo json_encode(['error' => $msg, 'detalles' => $detalles], JSON_UNESCAPED_UNICODE);
  exit;
}
function as_int01($v, $def = 1)
{
  if ($v === null || $v === '')
    return $def;
  $n = (int) $v;
  return ($n === 1) ? 1 : 0;
}
function clean($v)
{
  return trim((string) $v);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
  if ($action === 'list') {
    $q = clean($_GET['q'] ?? '');
    $inactivos = (int) ($_GET['inactivos'] ?? 0);
    $activoCard = $_GET['activoCard'] ?? 'ALL';

    $where = [];
    $params = [];

    if (!$inactivos) {
      $where[] = "IFNULL(Activo,1)=1";
    }
    if ($activoCard === '1') {
      $where[] = "IFNULL(Activo,1)=1";
    }
    if ($activoCard === '0') {
      $where[] = "IFNULL(Activo,1)=0";
    }

    if ($q !== '') {
      $where[] = "(clave_empresa LIKE ? OR des_cia LIKE ? OR des_rfc LIKE ? OR distrito LIKE ?)";
      $qp = "%$q%";
      $params = array_merge($params, [$qp, $qp, $qp, $qp]);
    }

    $sql = "SELECT cve_cia, clave_empresa, des_cia, distrito, des_rfc, des_telef, des_email, Activo, cve_tipcia, municipio, estado, imagen
            FROM c_compania";
    if ($where)
      $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY IFNULL(Activo,1) DESC, des_cia ASC LIMIT 2000";

    $rows = db_all($sql, $params);
    echo json_encode(['rows' => $rows], JSON_UNESCAPED_UNICODE);
    exit;
  }

  if ($action === 'tipos') {
    $rows = db_all("SELECT id, descripcion FROM c_tipo_empresa WHERE activo=1 ORDER BY descripcion");
    echo json_encode(['rows' => $rows], JSON_UNESCAPED_UNICODE);
    exit;
  }

  if ($action === 'get') {
    $id = (int) ($_GET['cve_cia'] ?? 0);
    if ($id <= 0)
      jerr('cve_cia inválido');
    $row = db_one("SELECT * FROM c_compania WHERE cve_cia=:id LIMIT 1", [':id' => $id]);
    if (!$row)
      jerr('No existe el registro');
    echo json_encode($row, JSON_UNESCAPED_UNICODE);
    exit;
  }

  if ($action === 'create' || $action === 'update') {
    $cve_cia = (int) ($_POST['cve_cia'] ?? 0);

    $clave_empresa = strtoupper(clean($_POST['clave_empresa'] ?? ''));
    $des_cia = clean($_POST['des_cia'] ?? '');
    if ($clave_empresa === '')
      jerr('Validación', ['Clave empresa es obligatoria.']);
    if ($des_cia === '')
      jerr('Validación', ['Nombre / Razón social es obligatoria.']);

    $data = [
      'clave_empresa' => $clave_empresa,
      'cve_tipcia' => (int) ($_POST['cve_tipcia'] ?? 0),
      'distrito' => clean($_POST['distrito'] ?? ''),
      'municipio' => clean($_POST['municipio'] ?? ''),
      'estado' => clean($_POST['estado'] ?? ''),
      'des_rfc' => clean($_POST['des_rfc'] ?? ''),
      'des_direcc' => clean($_POST['des_direcc'] ?? ''),
      'des_cp' => clean($_POST['des_cp'] ?? ''),
      'des_telef' => clean($_POST['des_telef'] ?? ''),
      'des_contacto' => clean($_POST['des_contacto'] ?? ''),
      'des_email' => clean($_POST['des_email'] ?? ''),
      'des_observ' => clean($_POST['des_observ'] ?? ''),
      'es_transportista' => ($_POST['es_transportista'] === '' ? null : (int) $_POST['es_transportista']),
      'des_cia' => $des_cia,
      'Activo' => as_int01($_POST['Activo'] ?? 1, 1),
    ];

    // Image Upload
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
      $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
      if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
        $name = 'emp_' . uniqid() . '.' . $ext;
        $path = __DIR__ . '/../img/empresas/';
        if (!is_dir($path))
          mkdir($path, 0777, true);
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $path . $name)) {
          $data['imagen'] = $name;
        }
      }
    }

    // Email básico si viene
    if ($data['des_email'] !== '' && !preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $data['des_email'])) {
      jerr('Validación', ['Email no tiene formato válido.']);
    }

    db_tx(function () use ($action, $cve_cia, $data) {
      if ($action === 'create') {
        // Evitar duplicados por clave_empresa
        $ex = db_val("SELECT cve_cia FROM c_compania WHERE clave_empresa=:c LIMIT 1", [':c' => $data['clave_empresa']]);
        if ($ex)
          throw new Exception("Ya existe una empresa con esa clave_empresa (cve_cia=$ex).");

        $cols = array_keys($data);
        $ins = "INSERT INTO c_compania (" . implode(',', $cols) . ") VALUES (:" . implode(',:', $cols) . ")";
        $params = [];
        foreach ($data as $k => $v)
          $params[":$k"] = $v;
        dbq($ins, $params);
      } else {
        if ($cve_cia <= 0)
          throw new Exception("cve_cia inválido");
        $set = [];
        $params = [':id' => $cve_cia];
        foreach ($data as $k => $v) {
          $set[] = "$k=:$k";
          $params[":$k"] = $v;
        }
        dbq("UPDATE c_compania SET " . implode(',', $set) . " WHERE cve_cia=:id", $params);
      }
    });

    echo json_encode(['ok' => 1], JSON_UNESCAPED_UNICODE);
    exit;
  }

  if ($action === 'delete' || $action === 'restore') {
    $id = (int) ($_POST['cve_cia'] ?? 0);
    if ($id <= 0)
      jerr('cve_cia inválido');
    $val = ($action === 'delete') ? 0 : 1;
    dbq("UPDATE c_compania SET Activo=:v WHERE cve_cia=:id", [':v' => $val, ':id' => $id]);
    echo json_encode(['ok' => 1], JSON_UNESCAPED_UNICODE);
    exit;
  }

  if ($action === 'export') {
    header_remove('Content-Type');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=empresas_export.csv');

    $q = clean($_GET['q'] ?? '');
    $inactivos = (int) ($_GET['inactivos'] ?? 0);
    $activoCard = $_GET['activoCard'] ?? 'ALL';

    $where = [];
    $params = [];
    if (!$inactivos)
      $where[] = "IFNULL(Activo,1)=1";
    if ($activoCard === '1')
      $where[] = "IFNULL(Activo,1)=1";
    if ($activoCard === '0')
      $where[] = "IFNULL(Activo,1)=0";
    if ($q !== '') {
      $where[] = "(clave_empresa LIKE ? OR des_cia LIKE ? OR des_rfc LIKE ? OR distrito LIKE ?)";
      $qp = "%$q%";
      $params = array_merge($params, [$qp, $qp, $qp, $qp]);
    }

    $sql = "SELECT cve_cia, clave_empresa, des_cia, cve_tipcia, distrito, des_rfc, des_direcc, des_cp, des_telef, des_contacto, des_email, des_observ, es_transportista, Activo
          FROM c_compania";
    if ($where)
      $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY cve_cia ASC";

    $rows = db_all($sql, $params);
    $out = fopen('php://output', 'w');

    // 🔥 Forzar Excel a reconocer UTF-8
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Human-readable headers
    $headers = [
      'ID',
      'Clave Empresa',
      'Nombre Empresa',
      'Tipo Empresa',
      'Distrito',
      'RFC',
      'Dirección',
      'Código Postal',
      'Teléfono',
      'Contacto',
      'Email',
      'Observaciones',
      'Es Transportista',
      'Activo'
    ];
    fputcsv($out, $headers);

    foreach ($rows as $r)
      fputcsv($out, $r);
    fclose($out);
    exit;
  }

  if ($action === 'layout') {
    header_remove('Content-Type');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=empresas_layout.csv');
    $out = fopen('php://output', 'w');

    // Human-readable headers
    $headers = [
      'ID',
      'Clave Empresa',
      'Nombre Empresa',
      'Tipo Empresa',
      'Distrito',
      'RFC',
      'Dirección',
      'Código Postal',
      'Teléfono',
      'Contacto',
      'Email',
      'Observaciones',
      'Es Transportista',
      'Activo'
    ];
    fputcsv($out, $headers);
    fputcsv($out, ['', 'EMP01', 'EMPRESA DEMO', 'Matriz', 'NORTE', 'XAXX010101000', 'CALLE 1', '64000', '8180000000', 'CONTACTO', 'correo@dominio.com', 'OBS', '0', '1']);
    fclose($out);
    exit;
  }

  if ($action === 'import_preview' || $action === 'import') {

    if (!isset($_FILES['csv']))
      jerr('No se recibió archivo CSV');

    $tmp = $_FILES['csv']['tmp_name'];
    if (!is_uploaded_file($tmp))
      jerr('Archivo inválido');

    $fh = fopen($tmp, 'r');
    if (!$fh)
      jerr('No se pudo leer el CSV');

    $header = fgetcsv($fh);
    // 🔥 Quitar BOM si existe
    $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
    if (!$header)
      jerr('CSV vacío');

    $header = array_map('trim', $header);

    // 🔥 Mapear encabezados legibles → columnas reales
    $headerMap = [
      'ID' => 'cve_cia',
      'Clave Empresa' => 'clave_empresa',
      'Nombre Empresa' => 'des_cia',
      'Tipo Empresa' => 'cve_tipcia',
      'Distrito' => 'distrito',
      'RFC' => 'des_rfc',
      'Dirección' => 'des_direcc',
      'Código Postal' => 'des_cp',
      'Teléfono' => 'des_telef',
      'Contacto' => 'des_contacto',
      'Email' => 'des_email',
      'Observaciones' => 'des_observ',
      'Es Transportista' => 'es_transportista',
      'Activo' => 'Activo'
    ];

    $normalizedHeader = [];
    foreach ($header as $h) {
      $normalizedHeader[] = $headerMap[$h] ?? $h;
    }
    $header = $normalizedHeader;

    $expected = [
      'cve_cia',
      'clave_empresa',
      'des_cia',
      'cve_tipcia',
      'distrito',
      'des_rfc',
      'des_direcc',
      'des_cp',
      'des_telef',
      'des_contacto',
      'des_email',
      'des_observ',
      'es_transportista',
      'Activo'
    ];

    $diff = array_diff($expected, $header);
    if ($diff)
      jerr('Layout incorrecto. Faltan columnas: ' . implode(', ', $diff));

    // Preview
    if ($action === 'import_preview') {
      fclose($fh);
      echo json_encode(['ok' => 1]);
      exit;
    }

    // 🔥 IMPORT REAL
    $inserted = 0;
    $updated = 0;
    $errors = 0;
    $errList = [];
    $line = 1;

    db_tx(function () use ($fh, $header, &$inserted, &$updated, &$errors, &$errList, &$line) {

      while (($r = fgetcsv($fh)) !== false) {

        $line++;
        $row = array_combine($header, $r);

        // 🔥 FORZAR UTF-8 en cada campo
        foreach ($row as $k => $v) {
          if ($v !== null) {
            $row[$k] = mb_convert_encoding($v, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
          }
        }

        $cve_cia = (int) trim($row['cve_cia'] ?? '0');
        $clave = strtoupper(trim($row['clave_empresa'] ?? ''));
        $nombre = trim($row['des_cia'] ?? '');

        if ($clave === '' || $nombre === '') {
          $errors++;
          $errList[] = "Línea $line: clave_empresa y des_cia obligatorias.";
          continue;
        }

        // 🔥 Convertir Tipo Empresa (acepta número o texto)
        $tipoRaw = trim($row['cve_tipcia'] ?? '');
        $idTipo = 0;

        if ($tipoRaw !== '') {

          // Si viene número (ej: 1)
          if (is_numeric($tipoRaw)) {
            $idTipo = (int)$tipoRaw;

            // Validar que exista en catálogo
            $exists = (int) db_val(
              "SELECT id FROM c_tipo_empresa WHERE id = :id LIMIT 1",
              [':id' => $idTipo]
            );

            if (!$exists) {
              $idTipo = 0;
            }
          } else {
            // Si viene texto (ej: Matriz)
            $idTipo = (int) db_val(
              "SELECT id FROM c_tipo_empresa 
       WHERE LOWER(descripcion) = LOWER(:d) 
       LIMIT 1",
              [':d' => $tipoRaw]
            );
          }
        }

        if ($idTipo <= 0) {
          $errors++;
          $errList[] = "Línea $line: Tipo Empresa inválido ($tipoRaw).";
          continue;
        }

        $data = [
          'clave_empresa' => $clave,
          'des_cia' => $nombre,
          'cve_tipcia' => $idTipo,
          'distrito' => trim($row['distrito'] ?? ''),
          'des_rfc' => trim($row['des_rfc'] ?? ''),
          'des_direcc' => trim($row['des_direcc'] ?? ''),
          'des_cp' => trim($row['des_cp'] ?? ''),
          'des_telef' => trim($row['des_telef'] ?? ''),
          'des_contacto' => trim($row['des_contacto'] ?? ''),
          'des_email' => trim($row['des_email'] ?? ''),
          'des_observ' => trim($row['des_observ'] ?? ''),
          'es_transportista' => (trim($row['es_transportista'] ?? '') === '' ? null : (int)$row['es_transportista']),
          'Activo' => ((int) trim($row['Activo'] ?? '1') === 1 ? 1 : 0),
        ];

        // 🔥 UPSERT
        $existsId = 0;

        if ($cve_cia > 0) {
          $existsId = (int) db_val(
            "SELECT cve_cia FROM c_compania WHERE cve_cia=:id LIMIT 1",
            [':id' => $cve_cia]
          );
        }

        if (!$existsId) {
          $existsId = (int) db_val(
            "SELECT cve_cia FROM c_compania WHERE clave_empresa=:c LIMIT 1",
            [':c' => $clave]
          );
        }

        if ($existsId) {
          $set = [];
          $params = [':id' => $existsId];

          foreach ($data as $k => $v) {
            $set[] = "$k=:$k";
            $params[":$k"] = $v;
          }

          dbq("UPDATE c_compania SET " . implode(',', $set) . " WHERE cve_cia=:id", $params);
          $updated++;
        } else {
          $cols = array_keys($data);
          $ins = "INSERT INTO c_compania (" . implode(',', $cols) . ") VALUES (:" . implode(',:', $cols) . ")";

          $params = [];
          foreach ($data as $k => $v)
            $params[":$k"] = $v;

          dbq($ins, $params);
          $inserted++;
        }
      }
    });

    fclose($fh);

    echo json_encode([
      'ok' => 1,
      'inserted' => $inserted,
      'updated' => $updated,
      'errors' => $errors,
      'detalles' => $errList
    ], JSON_UNESCAPED_UNICODE);

    exit;
  }

  jerr('Acción no soportada: ' . $action);
} catch (Throwable $e) {
  jerr('Error: ' . $e->getMessage());
}
