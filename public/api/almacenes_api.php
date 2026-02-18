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
  return trim((string) $v);
}
function norm01($v, $def = '1')
{
  $v = clean($v);
  if ($v === '')
    return $def;
  return ($v === '1') ? '1' : '0';
}

// --- Detecta si existe empresa_id en c_almacenp ---
$hasEmpresaId = (int) db_val("
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'c_almacenp'
    AND COLUMN_NAME = 'empresa_id'
") > 0;

$empresaCol = $hasEmpresaId ? "empresa_id" : "cve_cia";

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
  // ===================== LIST =====================
  if ($action === 'list') {
    $q = clean($_GET['q'] ?? '');
    $inactivos = (int) ($_GET['inactivos'] ?? 0);
    $cve_cia = (int) ($_GET['cve_cia'] ?? 0);

    $where = [];
    $p = [];

    if (!$inactivos)
      $where[] = "IFNULL(Activo,'1')='1'";

    // Filtro Experto: Empresa
    if ($cve_cia > 0) {
      $where[] = "($empresaCol) = :cia";
      $p[':cia'] = $cve_cia;
    }

    if ($q !== '') {
      $where[] = "(($empresaCol) LIKE :q OR nombre LIKE :q OR direccion LIKE :q OR contacto LIKE :q OR correo LIKE :q)";
      $p[':q'] = "%$q%";
    }

    $sql = "SELECT
            ($empresaCol) AS clave_empresa,
            id,
            nombre,
            cve_talmacen AS tipo,
            direccion,
            contacto AS responsable,
            telefono,
            correo AS email,
            interno, -- 1=Interno(No 3PL), 0=Externo(Si 3PL)
            Activo
          FROM c_almacenp";
    if ($where)
      $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY IFNULL(Activo,'1') DESC, nombre ASC LIMIT 3000";

    $rows = db_all($sql, $p);

    foreach ($rows as &$r) {
      $r['es_3pl'] = ((int) $r['interno'] === 0) ? 'Si' : 'No';
    }
    unset($r);

    echo json_encode(['rows' => $rows, 'meta' => ['has_empresa_id' => $hasEmpresaId]], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ===================== GET COMPANIES (Combo) =====================
  if ($action === 'get_companies') {
    // Retornar lista simple para el combo: ID, Clave, Nombre. Sin filtros para ver todas.
    // Usamos cve_cia porque es la PK confirmada.
    $rows = db_all("SELECT cve_cia, clave_empresa, des_cia FROM c_compania ORDER BY clave_empresa");
    echo json_encode(['rows' => $rows], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ===================== GET =====================
  if ($action === 'get') {
    $emp = clean($_GET['clave_empresa'] ?? '');
    $id = clean($_GET['id'] ?? '');
    if ($id === '')
      jerr('Llave inválida: id es obligatorio');

    if ($hasEmpresaId) {
      if ($emp === '')
        jerr('Llave inválida: clave_empresa');
      $row = db_one("SELECT *, ($empresaCol) AS clave_empresa, empresa_id AS cve_cia FROM c_almacenp WHERE empresa_id=:e AND id=:i LIMIT 1", [':e' => $emp, ':i' => $id]);
    } else {
      if ($emp === '')
        jerr('Llave inválida: clave_empresa');
      $row = db_one("SELECT *, ($empresaCol) AS clave_empresa FROM c_almacenp WHERE cve_cia=:e AND id=:i LIMIT 1", [':e' => $emp, ':i' => $id]);
    }

    if (!$row)
      jerr('No existe el registro');
    echo json_encode($row, JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ===================== CREATE / UPDATE =====================
  if ($action === 'create' || $action === 'update') {

    $k_emp = clean($_POST['k_clave_empresa'] ?? '');
    $k_id  = clean($_POST['k_id'] ?? '');

    $clave_empresa = clean($_POST['clave_empresa'] ?? '');

    // 🔹 ID es técnico, NO obligatorio en create
    $id = clean($_POST['id'] ?? '');

    // 🔹 CLAVE funcional (alfanumérica)
    $clave = clean($_POST['clave'] ?? '');

    $nombre        = clean($_POST['nombre'] ?? '');
    $cve_talmacen  = clean($_POST['tipo'] ?? '');
    $direccion     = clean($_POST['direccion'] ?? '');
    $contacto      = clean($_POST['responsable'] ?? '');
    $telefono      = clean($_POST['telefono'] ?? '');
    $correo        = clean($_POST['email'] ?? '');

    $es_3pl_val = clean($_POST['es_3pl'] ?? '');
    $interno = ($es_3pl_val === 'Si' || $es_3pl_val === '1') ? 0 : 1;

    // ================= VALIDACIONES =================
    $det = [];

    if ($clave_empresa === '')
      $det[] = 'Clave Empresa es obligatoria.';

    if ($clave === '')
      $det[] = 'Clave del almacén es obligatoria.';

    if ($nombre === '')
      $det[] = 'Nombre es obligatorio.';

    if ($correo !== '' && !preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $correo))
      $det[] = 'Correo no tiene formato válido.';

    if ($det)
      jerr('Validación', $det);

    // ================= NORMALIZACIÓN CLAVE =================
    $clave = strtoupper($clave);
    $clave = preg_replace('/[^A-Z0-9_-]/', '', $clave);

    $data = [
      'clave'        => $clave,
      'nombre'       => $nombre,
      'cve_talmacen' => $cve_talmacen,
      'direccion'    => $direccion,
      'contacto'     => $contacto,
      'telefono'     => $telefono,
      'correo'       => $correo,
      'interno'      => $interno,
      'Activo'       => norm01($_POST['Activo'] ?? '1', '1'),
      'comentarios'  => clean($_POST['comentarios'] ?? ''),
      'rut'          => clean($_POST['rut'] ?? ''),
      'codigopostal' => clean($_POST['codigopostal'] ?? ''),
      'BL'           => clean($_POST['BL'] ?? ''),
    ];

    if ($hasEmpresaId) {
      $data['empresa_id'] = $clave_empresa;
    } else {
      $data['cve_cia'] = $clave_empresa;
    }

    db_tx(function () use ($action, $hasEmpresaId, $k_emp, $k_id, $clave_empresa, $clave, $data) {

      if ($action === 'create') {

        // 🔹 Validar duplicado por Empresa + CLAVE
        if ($hasEmpresaId) {
          $ex = db_val(
            "SELECT 1 FROM c_almacenp 
                      WHERE empresa_id=:e AND clave=:c LIMIT 1",
            [':e' => $clave_empresa, ':c' => $clave]
          );
        } else {
          $ex = db_val(
            "SELECT 1 FROM c_almacenp 
                      WHERE cve_cia=:e AND clave=:c LIMIT 1",
            [':e' => $clave_empresa, ':c' => $clave]
          );
        }

        if ($ex)
          throw new Exception("Ya existe un almacén con esa clave en esta empresa.");

        // 🔹 Insert SIN forzar id (autonumérico)
        $cols = array_keys($data);
        $ins  = "INSERT INTO c_almacenp (" . implode(',', $cols) . ")
               VALUES (:" . implode(',:', $cols) . ")";
        $p = [];
        foreach ($data as $k => $v)
          $p[":$k"] = $v;

        dbq($ins, $p);
      } else {

        if ($k_id === '')
          throw new Exception("Llave original inválida.");

        if ($hasEmpresaId) {
          $where = "WHERE empresa_id=:ke AND id=:ki";
          $p = [':ke' => $k_emp, ':ki' => $k_id];
        } else {
          $where = "WHERE cve_cia=:ke AND id=:ki";
          $p = [':ke' => $k_emp, ':ki' => $k_id];
        }

        $set = [];
        foreach ($data as $k => $v) {
          if ($k === 'empresa_id' || $k === 'cve_cia')
            continue;
          $set[] = "$k=:$k";
          $p[":$k"] = $v;
        }

        dbq("UPDATE c_almacenp SET " . implode(',', $set) . " $where", $p);
      }
    });

    echo json_encode(['ok' => 1], JSON_UNESCAPED_UNICODE);
    exit;
  }


  // ===================== DELETE / RESTORE =====================
  if ($action === 'delete' || $action === 'restore') {
    $emp = clean($_POST['clave_empresa'] ?? '');
    $id = clean($_POST['id'] ?? '');
    if ($emp === '' || $id === '')
      jerr('Llave inválida');

    $val = ($action === 'delete') ? '0' : '1';
    if ($hasEmpresaId) {
      dbq("UPDATE c_almacenp SET Activo=:v WHERE empresa_id=:e AND id=:i", [':v' => $val, ':e' => $emp, ':i' => $id]);
    } else {
      dbq("UPDATE c_almacenp SET Activo=:v WHERE cve_cia=:e AND id=:i", [':v' => $val, ':e' => $emp, ':i' => $id]);
    }

    echo json_encode(['ok' => 1], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ===================== EXPORT =====================
  if ($action === 'export') {
    header_remove('Content-Type');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=almacenes_export.csv');

    $q = clean($_GET['q'] ?? '');
    $inactivos = (int) ($_GET['inactivos'] ?? 0);
    $cve_cia = (int) ($_GET['cve_cia'] ?? 0);

    $where = [];
    $p = [];
    if (!$inactivos)
      $where[] = "IFNULL(Activo,'1')='1'";

    if ($cve_cia > 0) {
      $where[] = "($empresaCol) = :cia";
      $p[':cia'] = $cve_cia;
    }

    if ($q !== '') {
      $where[] = "(($empresaCol) LIKE :q OR nombre LIKE :q)";
      $p[':q'] = "%$q%";
    }

    $sql = "SELECT
            ($empresaCol) AS clave_empresa,
            nombre,
            cve_talmacen AS tipo,
            direccion,
            contacto AS responsable,
            telefono,
            correo AS email,
            interno,
            Activo
          FROM c_almacenp";
    if ($where)
      $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY nombre ASC";

    $rows = db_all($sql, $p);

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Clave de Empresa', 'Nombre', 'Tipo', 'Dirección', 'Responsable', 'Teléfono', 'Email', 'Es 3PL']);

    foreach ($rows as $r) {
      $es3pl = ((int) $r['interno'] === 0) ? 'Si' : 'No';
      fputcsv($out, [
        $r['clave_empresa'],
        $r['nombre'],
        $r['tipo'],
        $r['direccion'],
        $r['responsable'],
        $r['telefono'],
        $r['email'],
        $es3pl
      ]);
    }
    fclose($out);
    exit;
  }

  jerr('Acción no soportada: ' . $action);
} catch (Throwable $e) {
  jerr('Error: ' . $e->getMessage());
}
