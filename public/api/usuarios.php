<?php
ob_clean();
require_once __DIR__ . '/../../app/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();
if (!$pdo) {
  echo json_encode(['ok' => false, 'success' => false, 'msg' => 'PDO no inicializado', 'data' => []]);
  exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

function jexit($ok, $msg = '', $data = [], $extra = [])
{
  echo json_encode(array_merge([
    'ok' => (bool)$ok,
    'success' => (bool)$ok,
    'msg' => (string)$msg,
    'data' => $data
  ], $extra));
  exit;
}

try {

  switch ($action) {

    /* ================= SELECT (PARA COMBOS / SELECT2) =================
       - action=select
       - q=texto (opcional)
       - include_inactive=1 (opcional)
       Respuesta: ok/data[{id_user,cve_usuario,nombre_completo,text}]
    */
    case 'select':
      $includeInactive = (int)($_GET['include_inactive'] ?? 0);
      $q = trim((string)($_GET['q'] ?? ''));

      $where = [];
      $params = [];

      if (!$includeInactive) {
        $where[] = "COALESCE(Activo,1)=1";
      }

      if ($q !== '') {
        $where[] = "(cve_usuario LIKE ? OR nombre_completo LIKE ? OR email LIKE ? OR perfil LIKE ?)";
        $qp = "%{$q}%";
        $params = [$qp, $qp, $qp, $qp];
      }

      $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

      $stmt = $pdo->prepare("
        SELECT id_user,
               TRIM(cve_usuario)     AS cve_usuario,
               TRIM(nombre_completo) AS nombre_completo,
               email,
               perfil,
               status,
               COALESCE(Activo,1)    AS Activo
        FROM c_usuario
        $whereClause
        ORDER BY nombre_completo
        LIMIT 500
      ");
      $stmt->execute($params);
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

      // Enriquecemos con campo "text" para select2
      foreach ($rows as &$r) {
        $r['text'] = trim(($r['cve_usuario'] ?? '') . ' - ' . ($r['nombre_completo'] ?? ''));
      }
      unset($r);

      jexit(true, 'OK', $rows);
      break;


    /* ================= LIST (DATATABLES / ADMIN) =================
       - action=list
       - include_inactive=1 (opcional)
       - search[value]=texto (DataTables)
       Devuelve: success/data/recordsTotal/recordsFiltered (+ ok/msg por compat)
    */
    case 'list':
      $includeInactive = (int)($_GET['include_inactive'] ?? 0);
      $search = $_GET['search']['value'] ?? ($_GET['search'] ?? '');
      $search = trim((string)$search);

      $where = [];
      $params = [];

      if (!$includeInactive) {
        $where[] = "COALESCE(Activo,1)=1";
      }

      if ($search !== '') {
        $where[] = "(cve_usuario LIKE ? OR nombre_completo LIKE ? OR email LIKE ? OR perfil LIKE ?)";
        $sp = "%{$search}%";
        $params = array_merge($params, [$sp, $sp, $sp, $sp]);
      }

      $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

      $stmt = $pdo->prepare("
        SELECT id_user,
               TRIM(cve_usuario)      AS cve_usuario,
               TRIM(nombre_completo)  AS nombre_completo,
               email, perfil, status,
               COALESCE(Activo,1)     AS Activo
        FROM c_usuario
        $whereClause
        ORDER BY nombre_completo
      ");
      $stmt->execute($params);
      $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

      echo json_encode([
        'ok' => true,
        'success' => true,
        'msg' => 'OK',
        'data' => $data,
        'recordsTotal' => count($data),
        'recordsFiltered' => count($data)
      ]);
      exit;


    /* ================= GET ================= */
    case 'get':
      $id = (int)($_GET['id_user'] ?? 0);
      $stmt = $pdo->prepare("SELECT * FROM c_usuario WHERE id_user=?");
      $stmt->execute([$id]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      echo json_encode(['ok' => true, 'success' => true, 'row' => $row]);
      exit;


    /* ================= CREATE ================= */
    case 'create':
      $stmt = $pdo->prepare("
        INSERT INTO c_usuario
        (cve_usuario,nombre_completo,email,perfil,
         des_usuario,pwd_usuario,status,Activo,ban_usuario,fec_ingreso)
        VALUES (?,?,?,?,?,?,?,?,?,NOW())
      ");
      $stmt->execute([
        $_POST['cve_usuario'] ?? '',
        $_POST['nombre_completo'] ?? '',
        $_POST['email'] ?? '',
        $_POST['perfil'] ?? '',
        $_POST['des_usuario'] ?? '',
        '', // pwd no se usa aquí
        $_POST['status'] ?? 'A',
        $_POST['Activo'] ?? 1,
        1, // ban_usuario
      ]);

      echo json_encode([
        'ok' => true,
        'success' => true,
        'message' => 'Usuario creado correctamente',
        'id_user' => $pdo->lastInsertId()
      ]);
      exit;


    /* ================= UPDATE ================= */
    case 'update':
      $stmt = $pdo->prepare("
        UPDATE c_usuario SET
          cve_usuario=?,
          nombre_completo=?,
          email=?,
          perfil=?,
          des_usuario=?,
          status=?,
          Activo=?
        WHERE id_user=?
      ");
      $stmt->execute([
        $_POST['cve_usuario'] ?? '',
        $_POST['nombre_completo'] ?? '',
        $_POST['email'] ?? '',
        $_POST['perfil'] ?? '',
        $_POST['des_usuario'] ?? '',
        $_POST['status'] ?? '',
        $_POST['Activo'] ?? 1,
        (int)($_POST['id_user'] ?? 0),
      ]);

      echo json_encode(['ok' => true, 'success' => true, 'message' => 'Usuario actualizado correctamente']);
      exit;


    /* ================= SOFT DELETE ================= */
    case 'delete':
      $stmt = $pdo->prepare("UPDATE c_usuario SET Activo=0 WHERE id_user=?");
      $stmt->execute([(int)($_POST['id_user'] ?? 0)]);
      echo json_encode(['ok' => true, 'success' => true, 'message' => 'Usuario inactivado']);
      exit;


    /* ================= RECOVER ================= */
    case 'recover':
      $stmt = $pdo->prepare("UPDATE c_usuario SET Activo=1 WHERE id_user=?");
      $stmt->execute([(int)($_POST['id_user'] ?? 0)]);
      echo json_encode(['ok' => true, 'success' => true, 'message' => 'Usuario recuperado']);
      exit;


    /* ================= EXPORT CSV ================= */
    case 'export_csv':
      header('Content-Type: text/csv; charset=utf-8');
      header('Content-Disposition: attachment; filename=usuarios.csv');

      $out = fopen('php://output', 'w');
      fputcsv($out, [
        'cve_usuario',
        'nombre_completo',
        'email',
        'perfil',
        'des_usuario',
        'status',
        'Activo'
      ]);

      $stmt = $pdo->query("
        SELECT cve_usuario,nombre_completo,email,
               perfil,des_usuario,status,Activo
        FROM c_usuario
        ORDER BY nombre_completo
      ");
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, $row);
      }
      fclose($out);
      exit;


    /* ================= IMPORT CSV ================= */
    case 'import_csv':
      if (!isset($_FILES['file'])) {
        jexit(false, 'Archivo no recibido', []);
      }

      $fh = fopen($_FILES['file']['tmp_name'], 'r');
      if (!$fh) jexit(false, 'No se pudo leer el archivo', []);

      fgetcsv($fh); // header

      $sql = $pdo->prepare("
        INSERT INTO c_usuario
        (cve_usuario,nombre_completo,email,perfil,
         des_usuario,status,Activo,fec_ingreso)
        VALUES (?,?,?,?,?,?,?,NOW())
        ON DUPLICATE KEY UPDATE
          nombre_completo=VALUES(nombre_completo),
          email=VALUES(email),
          perfil=VALUES(perfil),
          des_usuario=VALUES(des_usuario),
          status=VALUES(status),
          Activo=VALUES(Activo)
      ");

      while (($row = fgetcsv($fh)) !== false) {
        // Si el CSV no trae las 7 columnas esperadas, evitamos reventar silenciosamente
        if (count($row) < 7) continue;
        $sql->execute($row);
      }
      fclose($fh);

      jexit(true, 'Importado', []);
      break;


    default:
      echo json_encode(['ok' => false, 'success' => false, 'message' => 'Acción no válida', 'action' => $action]);
      exit;
  }

} catch (Throwable $e) {
  echo json_encode(['ok' => false, 'success' => false, 'message' => $e->getMessage()]);
  exit;
}
