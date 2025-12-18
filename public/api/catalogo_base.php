<?php
ob_clean();
require_once __DIR__ . '/../../app/db.php';

$pdo = db_pdo();
if (!$pdo) {
    header('Content-Type: application/json');
    echo json_encode(['error'=>'PDO no inicializado']);
    exit;
}

/*
PARAMETROS OBLIGATORIOS:
- table      (nombre tabla)
- pk         (llave primaria)
- fields[]   (campos editables)
- action
*/

$table  = $_REQUEST['table'] ?? '';
$pk     = $_REQUEST['pk'] ?? 'id';
$fields = $_REQUEST['fields'] ?? [];
$action = $_REQUEST['action'] ?? 'list';

if (!$table || !is_array($fields)) {
    echo json_encode(['error'=>'Parámetros inválidos']);
    exit;
}

try {

    switch ($action) {

        /* ========== LIST ========== */
        case 'list':
            $sql = "SELECT $pk," . implode(',', $fields) . ",Activo
                    FROM $table
                    ORDER BY $pk DESC";
            echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
            break;

        /* ========== GET ========== */
        case 'get':
            $stmt = $pdo->prepare("SELECT * FROM $table WHERE $pk=?");
            $stmt->execute([$_GET[$pk]]);
            echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
            break;

        /* ========== CREATE ========== */
        case 'create':
            $cols = implode(',', $fields);
            $vals = implode(',', array_fill(0, count($fields), '?'));

            $stmt = $pdo->prepare(
                "INSERT INTO $table ($cols,Activo) VALUES ($vals,1)"
            );
            $stmt->execute(array_map(fn($f)=>$_POST[$f] ?? null, $fields));
            echo json_encode(['success'=>true]);
            break;

        /* ========== UPDATE ========== */
        case 'update':
            $set = implode(',', array_map(fn($f)=>"$f=?", $fields));

            $stmt = $pdo->prepare(
                "UPDATE $table SET $set WHERE $pk=?"
            );

            $data = array_map(fn($f)=>$_POST[$f], $fields);
            $data[] = $_POST[$pk];
            $stmt->execute($data);

            echo json_encode(['success'=>true]);
            break;

        /* ========== DELETE (BAJA) ========== */
        case 'delete':
            $stmt = $pdo->prepare("UPDATE $table SET Activo=0 WHERE $pk=?");
            $stmt->execute([$_POST[$pk]]);
            echo json_encode(['success'=>true]);
            break;

        /* ========== RECOVER ========== */
        case 'recover':
            $stmt = $pdo->prepare("UPDATE $table SET Activo=1 WHERE $pk=?");
            $stmt->execute([$_POST[$pk]]);
            echo json_encode(['success'=>true]);
            break;

        /* ========== EXPORT CSV ========== */
        case 'export_csv':
            header('Content-Type:text/csv');
            header("Content-Disposition:attachment;filename=$table.csv");

            $out = fopen('php://output','w');
            fputcsv($out, $fields);

            $stmt = $pdo->query(
                "SELECT ".implode(',',$fields)." FROM $table"
            );
            while($r=$stmt->fetch(PDO::FETCH_ASSOC)){
                fputcsv($out,$r);
            }
            fclose($out);
            exit;

        default:
            echo json_encode(['error'=>'Acción no válida','action'=>$action]);
    }

} catch (Throwable $e) {
    echo json_encode(['error'=>$e->getMessage()]);
}
