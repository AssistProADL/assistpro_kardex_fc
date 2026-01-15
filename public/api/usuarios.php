<?php
ob_clean();
require_once __DIR__ . '/../../app/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();
if (!$pdo) {
  echo json_encode(['ok' => false, 'msg' => 'PDO no inicializado', 'data' => []]);
  exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

function jexit($ok, $msg = '', $data = [], $extra = [])
{
  echo json_encode(array_merge(['ok' => $ok, 'msg' => $msg, 'data' => $data], $extra));
  exit;
}

try {

  switch ($action) {

    /* ================= LIST (OPERATIVO) =================
       - Soporta filtro de inactivos
       - Devuelve ok/data
       - Orden por nombre
    */
    case 'list':
      $includeInactive = $_GET['include_inactive'] ?? 0;
      $search = $_GET['search']['value'] ?? '';

      $where = [];
      $params = [];

      if (!$includeInactive) {
        $where[] = "COALESCE(Activo,1)=1";
      }

      if ($search) {
        $where[] = "(cve_usuario LIKE ? OR nombre_completo LIKE ? OR email LIKE ? OR perfil LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
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

      // Return in DataTables format
      echo json_encode([
        'success' => true,
        'data' => $data,
        'recordsTotal' => count($data),
        'recordsFiltered' => count($data)
      ]);
      exit;
      break;

    /* ================= GET ================= */
    case 'get':
      $stmt = $pdo->prepare("SELECT * FROM c_usuario WHERE id_user=?");
      $stmt->execute([$_GET['id_user'] ?? 0]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      echo json_encode(['success' => true, 'row' => $row]);
      exit;
      break;

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
        1, // ban_usuario siempre es 1 según comentario en DB
      ]);
      echo json_encode(['success' => true, 'message' => 'Usuario creado correctamente', 'id_user' => $pdo->lastInsertId()]);
      exit;
      break;

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
        $_POST['id_user'] ?? 0,
      ]);
      echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente']);
      exit;
      break;

    /* ================= SOFT DELETE ================= */
    case 'delete':
      $stmt = $pdo->prepare("UPDATE c_usuario SET Activo=0 WHERE id_user=?");
      $stmt->execute([$_POST['id_user'] ?? 0]);
      echo json_encode(['success' => true, 'message' => 'Usuario inactivado']);
      exit;
      break;

    /* ================= RECOVER ================= */
    case 'recover':
      $stmt = $pdo->prepare("UPDATE c_usuario SET Activo=1 WHERE id_user=?");
      $stmt->execute([$_POST['id_user'] ?? 0]);
      echo json_encode(['success' => true, 'message' => 'Usuario recuperado']);
      exit;
      break;

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
      if (!isset($_FILES['file']))
        jexit(false, 'Archivo no recibido', []);

      $fh = fopen($_FILES['file']['tmp_name'], 'r');
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
        $sql->execute($row);
      }
      fclose($fh);
      jexit(true, 'Importado', []);
      break;

    default:
      echo json_encode(['success' => false, 'message' => 'Acción no válida', 'action' => $action]);
      exit;
  }

} catch (Throwable $e) {
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
  exit;
}
