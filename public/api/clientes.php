<?php
require_once __DIR__ . '/../../app/db.php';

$pdo = db_pdo();
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

function only_digits($v){ return preg_replace('/[^0-9]/','', (string)$v); }
function s($v){ $v = trim((string)$v); return $v === '' ? null : $v; }
function i0($v){ return ($v==='' || $v===null) ? 0 : (int)$v; }
function i1($v){ return ($v==='' || $v===null) ? 1 : (int)$v; }
function is_email($v){
    $v = trim((string)$v);
    if($v==='') return true; // email puede venir vacío
    return (bool)filter_var($v, FILTER_VALIDATE_EMAIL);
}

function validar_obligatorios($data){
    $errs = [];

    $cve = trim((string)($data['Cve_Clte'] ?? ''));
    $raz = trim((string)($data['RazonSocial'] ?? ''));
    // obligatorios: Cve_Clte y RazonSocial
    if($cve==='') $errs[] = 'Cve_Clte es obligatorio';
    if($raz==='') $errs[] = 'RazonSocial es obligatorio';

    $email = (string)($data['email_cliente'] ?? '');
    if(!is_email($email)) $errs[] = 'email_cliente inválido';

    return $errs;
}

/* =====================================================
 * EXPORT CSV (layout / datos)
 * ===================================================== */
if($action==='export_csv'){
    $tipo = $_GET['tipo'] ?? 'layout'; // layout|datos

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=clientes_'.$tipo.'.csv');

    $out = fopen('php://output','w');

    // Layout (mismo que usamos desde el inicio)
    $headers = [
        'Cve_Clte','RazonSocial','RazonComercial','RFC','Ciudad','Estado','Telefono1','email_cliente','credito'
    ];
    fputcsv($out, $headers);

    if($tipo==='datos'){
        $sql = "SELECT ".implode(',', $headers)." FROM c_cliente WHERE IFNULL(Activo,1)=1 ORDER BY RazonSocial";
        foreach($pdo->query($sql) as $row) fputcsv($out, $row);
    }

    fclose($out);
    exit;
}

/* =====================================================
 * IMPORT CSV con UPSERT por Cve_Clte + reporte
 * ===================================================== */
if($action==='import_csv'){
    header('Content-Type: application/json; charset=utf-8');

    if(!isset($_FILES['file'])){
        echo json_encode(['error'=>'Archivo no recibido']); exit;
    }

    $fh = fopen($_FILES['file']['tmp_name'],'r');
    if(!$fh){
        echo json_encode(['error'=>'No se pudo leer el archivo']); exit;
    }

    $headers = fgetcsv($fh);

    $esperadas = [
        'Cve_Clte','RazonSocial','RazonComercial','RFC','Ciudad','Estado','Telefono1','email_cliente','credito'
    ];

    if($headers !== $esperadas){
        echo json_encode(['error'=>'Layout incorrecto','esperado'=>$esperadas,'recibido'=>$headers]);
        exit;
    }

    $stFind = $pdo->prepare("SELECT id_cliente FROM c_cliente WHERE Cve_Clte=? LIMIT 1");

    $stIns = $pdo->prepare("
        INSERT INTO c_cliente
        (Cve_Clte,RazonSocial,RazonComercial,RFC,Ciudad,Estado,Telefono1,email_cliente,credito,Activo)
        VALUES (?,?,?,?,?,?,?,?,?,1)
    ");

    $stUpd = $pdo->prepare("
        UPDATE c_cliente SET
            RazonSocial=?,
            RazonComercial=?,
            RFC=?,
            Ciudad=?,
            Estado=?,
            Telefono1=?,
            email_cliente=?,
            credito=?,
            Activo=1
        WHERE Cve_Clte=?
        LIMIT 1
    ");

    $rows_ok=0; $rows_err=0; $errores=[];
    $pdo->beginTransaction();
    try{
        $linea=1;
        while(($r=fgetcsv($fh))!==false){
            $linea++;
            if(!$r || count($r)<9){
                $rows_err++;
                $errores[]=['fila'=>$linea,'motivo'=>'Fila incompleta','data'=>$r];
                continue;
            }

            $data = [
                'Cve_Clte'=>$r[0],'RazonSocial'=>$r[1],'RazonComercial'=>$r[2],'RFC'=>$r[3],
                'Ciudad'=>$r[4],'Estado'=>$r[5],'Telefono1'=>$r[6],'email_cliente'=>$r[7],'credito'=>$r[8]
            ];

            $errs = validar_obligatorios($data);
            if($errs){
                $rows_err++;
                $errores[]=['fila'=>$linea,'motivo'=>implode('; ',$errs),'data'=>$r];
                continue;
            }

            $cve = trim((string)$data['Cve_Clte']);
            $tel = only_digits($data['Telefono1']);
            $email = trim((string)$data['email_cliente']);
            $cred = ($data['credito']===''? 0 : (float)$data['credito']);

            $stFind->execute([$cve]);
            $existe = $stFind->fetchColumn();

            if($existe){
                $stUpd->execute([
                    trim((string)$data['RazonSocial']),
                    s($data['RazonComercial']),
                    s($data['RFC']),
                    s($data['Ciudad']),
                    s($data['Estado']),
                    $tel,
                    $email===''? null : $email,
                    $cred,
                    $cve
                ]);
            }else{
                $stIns->execute([
                    $cve,
                    trim((string)$data['RazonSocial']),
                    s($data['RazonComercial']),
                    s($data['RFC']),
                    s($data['Ciudad']),
                    s($data['Estado']),
                    $tel,
                    $email===''? null : $email,
                    $cred
                ]);
            }

            $rows_ok++;
        }

        $pdo->commit();
        echo json_encode(['success'=>true,'rows_ok'=>$rows_ok,'rows_err'=>$rows_err,'errores'=>$errores]);
    }catch(Throwable $e){
        $pdo->rollBack();
        echo json_encode(['error'=>$e->getMessage()]);
    }
    exit;
}

/* =====================================================
 * LIST + BUSCAR
 * ===================================================== */
if($action==='list'){
    header('Content-Type: application/json; charset=utf-8');

    $inactivos = (int)($_GET['inactivos'] ?? 0);
    $q = trim((string)($_GET['q'] ?? ''));

    $where = "WHERE IFNULL(Activo,1)=:activo";
    if($q!==''){
        $where .= " AND (
            Cve_Clte LIKE :q OR
            RazonSocial LIKE :q OR
            RazonComercial LIKE :q OR
            RFC LIKE :q OR
            Ciudad LIKE :q OR
            Estado LIKE :q OR
            Telefono1 LIKE :q OR
            email_cliente LIKE :q
        )";
    }

    $sql = "
        SELECT
            id_cliente,Cve_Clte,RazonSocial,RazonComercial,RFC,
            Ciudad,Estado,Telefono1,email_cliente,credito,Activo
        FROM c_cliente
        $where
        ORDER BY RazonSocial
        LIMIT 25
    ";

    $st = $pdo->prepare($sql);
    $st->bindValue(':activo', $inactivos?0:1, PDO::PARAM_INT);
    if($q!=='') $st->bindValue(':q', "%$q%", PDO::PARAM_STR);
    $st->execute();

    echo json_encode($st->fetchAll());
    exit;
}

/* =====================================================
 * CRUD + VALIDACIÓN DURA
 * ===================================================== */
header('Content-Type: application/json; charset=utf-8');

switch($action){

    case 'get':
        $id = $_GET['id_cliente'] ?? null;
        if(!$id){ echo json_encode(['error'=>'id_cliente requerido']); exit; }
        $st=$pdo->prepare("SELECT * FROM c_cliente WHERE id_cliente=?");
        $st->execute([$id]);
        echo json_encode($st->fetch());
        break;

    case 'create':
        $errs = validar_obligatorios($_POST);
        if($errs){ echo json_encode(['error'=>'Validación','detalles'=>$errs]); exit; }

        $st=$pdo->prepare("
            INSERT INTO c_cliente
            (Cve_Clte,RazonSocial,RazonComercial,RFC,Ciudad,Estado,Telefono1,email_cliente,credito,Activo)
            VALUES (?,?,?,?,?,?,?,?,?,1)
        ");

        $tel = only_digits($_POST['Telefono1'] ?? '');
        $email = trim((string)($_POST['email_cliente'] ?? ''));
        $cred = ($_POST['credito'] ?? '')==='' ? 0 : (float)$_POST['credito'];

        $st->execute([
            trim((string)$_POST['Cve_Clte']),
            trim((string)$_POST['RazonSocial']),
            s($_POST['RazonComercial'] ?? null),
            s($_POST['RFC'] ?? null),
            s($_POST['Ciudad'] ?? null),
            s($_POST['Estado'] ?? null),
            $tel,
            $email===''? null : $email,
            $cred
        ]);

        echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]);
        break;

    case 'update':
        if(empty($_POST['id_cliente'])){ echo json_encode(['error'=>'id_cliente requerido']); exit; }

        $errs = validar_obligatorios($_POST);
        if($errs){ echo json_encode(['error'=>'Validación','detalles'=>$errs]); exit; }

        $st=$pdo->prepare("
            UPDATE c_cliente SET
            Cve_Clte=?,RazonSocial=?,RazonComercial=?,RFC=?,
            Ciudad=?,Estado=?,Telefono1=?,email_cliente=?,credito=?
            WHERE id_cliente=?
        ");

        $tel = only_digits($_POST['Telefono1'] ?? '');
        $email = trim((string)($_POST['email_cliente'] ?? ''));
        $cred = ($_POST['credito'] ?? '')==='' ? 0 : (float)$_POST['credito'];

        $st->execute([
            trim((string)$_POST['Cve_Clte']),
            trim((string)$_POST['RazonSocial']),
            s($_POST['RazonComercial'] ?? null),
            s($_POST['RFC'] ?? null),
            s($_POST['Ciudad'] ?? null),
            s($_POST['Estado'] ?? null),
            $tel,
            $email===''? null : $email,
            $cred,
            (int)$_POST['id_cliente']
        ]);

        echo json_encode(['success'=>true]);
        break;

    case 'delete':
        if(empty($_POST['id_cliente'])){ echo json_encode(['error'=>'id_cliente requerido']); exit; }
        $pdo->prepare("UPDATE c_cliente SET Activo=0 WHERE id_cliente=?")->execute([(int)$_POST['id_cliente']]);
        echo json_encode(['success'=>true]);
        break;

    case 'restore':
        if(empty($_POST['id_cliente'])){ echo json_encode(['error'=>'id_cliente requerido']); exit; }
        $pdo->prepare("UPDATE c_cliente SET Activo=1 WHERE id_cliente=?")->execute([(int)$_POST['id_cliente']]);
        echo json_encode(['success'=>true]);
        break;

    default:
        echo json_encode(['error'=>'Acción no válida']);
}
