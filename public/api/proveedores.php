<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../app/db.php';

$pdo = db_pdo();
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

function only_digits($v){ return preg_replace('/[^0-9]/','', (string)$v); }
function s($v){ $v = trim((string)$v); return $v === '' ? null : $v; }
function i0($v){ return ($v==='' || $v===null) ? 0 : (int)$v; }
function i1($v){ return ($v==='' || $v===null) ? 1 : (int)$v; }
function fnull($v){ return ($v==='' || $v===null) ? null : (float)$v; }

function validar_obligatorios($data){
    $cve = trim((string)($data['cve_proveedor'] ?? ''));
    $emp = trim((string)($data['Empresa'] ?? ''));
    $nom = trim((string)($data['Nombre'] ?? ''));
    $pais= trim((string)($data['pais'] ?? ''));

    $errs = [];
    if ($cve === '') $errs[] = 'cve_proveedor es obligatorio';
    if ($pais === '') $errs[] = 'pais es obligatorio';
    if ($emp === '' && $nom === '') $errs[] = 'Debe capturar Empresa o Nombre';

    return $errs;
}

/* =====================================================
 * EXPORT CSV (LAYOUT O DATOS) FULL
 * ===================================================== */
if ($action === 'export_csv') {

    $tipo = $_GET['tipo'] ?? 'layout'; // layout | datos

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=proveedores_'.$tipo.'.csv');

    $out = fopen('php://output', 'w');

    $headers = [
        'Empresa','Nombre','RUT','direccion','cve_dane','ID_Externo','Activo','cve_proveedor',
        'colonia','ciudad','estado','pais','telefono1','telefono2','es_cliente',
        'longitud','latitud','es_transportista','envio_correo_automatico'
    ];
    fputcsv($out, $headers);

    if ($tipo === 'datos') {
        $sql = "SELECT ".implode(',', $headers)." FROM c_proveedores WHERE IFNULL(Activo,1)=1 ORDER BY Nombre";
        foreach ($pdo->query($sql) as $row) fputcsv($out, $row);
    }

    fclose($out);
    exit;
}

/* =====================================================
 * IMPORT CSV (UPSERT POR cve_proveedor) + VALIDACIÓN
 * ===================================================== */
if ($action === 'import_csv') {

    if (!isset($_FILES['file'])) { echo json_encode(['error'=>'Archivo no recibido']); exit; }
    $fh = fopen($_FILES['file']['tmp_name'], 'r');
    if (!$fh) { echo json_encode(['error'=>'No se pudo leer el archivo']); exit; }

    $headers = fgetcsv($fh);

    $esperadas = [
        'Empresa','Nombre','RUT','direccion','cve_dane','ID_Externo','Activo','cve_proveedor',
        'colonia','ciudad','estado','pais','telefono1','telefono2','es_cliente',
        'longitud','latitud','es_transportista','envio_correo_automatico'
    ];

    if ($headers !== $esperadas) {
        echo json_encode(['error'=>'Layout incorrecto','esperado'=>$esperadas,'recibido'=>$headers]);
        exit;
    }

    $sqlFind = "SELECT ID_Proveedor FROM c_proveedores WHERE cve_proveedor = ? LIMIT 1";

    $sqlIns = "
        INSERT INTO c_proveedores
        (Empresa,Nombre,RUT,direccion,cve_dane,ID_Externo,Activo,cve_proveedor,
         colonia,ciudad,estado,pais,telefono1,telefono2,es_cliente,longitud,latitud,es_transportista,envio_correo_automatico)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ";

    $sqlUpd = "
        UPDATE c_proveedores SET
            Empresa=?,Nombre=?,RUT=?,direccion=?,cve_dane=?,ID_Externo=?,Activo=?,
            colonia=?,ciudad=?,estado=?,pais=?,telefono1=?,telefono2=?,es_cliente=?,
            longitud=?,latitud=?,es_transportista=?,envio_correo_automatico=?
        WHERE cve_proveedor=?
        LIMIT 1
    ";

    $stFind = $pdo->prepare($sqlFind);
    $stIns  = $pdo->prepare($sqlIns);
    $stUpd  = $pdo->prepare($sqlUpd);

    $rows_ok=0; $rows_err=0; $errores=[];
    $pdo->beginTransaction();

    try {
        $linea=1;
        while(($r=fgetcsv($fh))!==false){
            $linea++;

            if(!$r || count($r)<19){
                $rows_err++;
                $errores[]=['fila'=>$linea,'motivo'=>'Fila incompleta','data'=>$r];
                continue;
            }

            $data = [
                'Empresa'=>$r[0],'Nombre'=>$r[1],'RUT'=>$r[2],'direccion'=>$r[3],'cve_dane'=>$r[4],
                'ID_Externo'=>$r[5],'Activo'=>$r[6],'cve_proveedor'=>$r[7],
                'colonia'=>$r[8],'ciudad'=>$r[9],'estado'=>$r[10],'pais'=>$r[11],
                'telefono1'=>$r[12],'telefono2'=>$r[13],'es_cliente'=>$r[14],
                'longitud'=>$r[15],'latitud'=>$r[16],'es_transportista'=>$r[17],'envio_correo_automatico'=>$r[18]
            ];

            $errs = validar_obligatorios($data);
            if($errs){
                $rows_err++;
                $errores[]=['fila'=>$linea,'motivo'=>implode('; ',$errs),'data'=>$r];
                continue;
            }

            $Empresa = s($data['Empresa']);
            $Nombre  = s($data['Nombre']);
            $RUT     = s($data['RUT']);
            $direccion = s($data['direccion']);
            $cve_dane  = s($data['cve_dane']);
            $ID_Externo = ($data['ID_Externo']===''? null : (int)$data['ID_Externo']);
            $Activo   = i1($data['Activo']);
            $cve_proveedor = trim((string)$data['cve_proveedor']);

            $colonia = s($data['colonia']);
            $ciudad  = s($data['ciudad']);
            $estado  = s($data['estado']);
            $pais    = trim((string)$data['pais']);
            $telefono1 = only_digits($data['telefono1']);
            $telefono2 = only_digits($data['telefono2']);

            $es_cliente = i0($data['es_cliente']);
            $longitud = fnull($data['longitud']);
            $latitud  = fnull($data['latitud']);
            $es_transportista = i0($data['es_transportista']);
            $envio_correo_automatico = i0($data['envio_correo_automatico']);

            $stFind->execute([$cve_proveedor]);
            $existe = $stFind->fetchColumn();

            if($existe){
                $stUpd->execute([
                    $Empresa,$Nombre,$RUT,$direccion,$cve_dane,$ID_Externo,$Activo,
                    $colonia,$ciudad,$estado,$pais,$telefono1,$telefono2,$es_cliente,
                    $longitud,$latitud,$es_transportista,$envio_correo_automatico,
                    $cve_proveedor
                ]);
            }else{
                $stIns->execute([
                    $Empresa,$Nombre,$RUT,$direccion,$cve_dane,$ID_Externo,$Activo,$cve_proveedor,
                    $colonia,$ciudad,$estado,$pais,$telefono1,$telefono2,$es_cliente,
                    $longitud,$latitud,$es_transportista,$envio_correo_automatico
                ]);
            }

            $rows_ok++;
        }

        $pdo->commit();
        echo json_encode(['success'=>true,'rows_ok'=>$rows_ok,'rows_err'=>$rows_err,'errores'=>$errores]);

    } catch(Throwable $e){
        $pdo->rollBack();
        echo json_encode(['error'=>$e->getMessage()]);
    }
    exit;
}

/* =====================================================
 * LISTAR + BUSCAR (q) + activos/inactivos
 * ===================================================== */
if ($action === 'list') {

    $inactivos = (int)($_GET['inactivos'] ?? 0);
    $q = trim((string)($_GET['q'] ?? ''));

    $where = "WHERE IFNULL(Activo,1) = :activo";
    if ($q !== '') {
        $where .= " AND (
            cve_proveedor LIKE :q OR Empresa LIKE :q OR Nombre LIKE :q OR RUT LIKE :q OR
            ciudad LIKE :q OR estado LIKE :q OR pais LIKE :q OR
            telefono1 LIKE :q OR telefono2 LIKE :q OR
            direccion LIKE :q OR colonia LIKE :q OR cve_dane LIKE :q OR
            CAST(ID_Externo AS CHAR) LIKE :q
        )";
    }

    $sql = "
        SELECT
            ID_Proveedor, Empresa, Nombre, RUT, direccion, cve_dane, ID_Externo, Activo, cve_proveedor,
            colonia, ciudad, estado, pais, telefono1, telefono2, es_cliente,
            longitud, latitud, es_transportista, envio_correo_automatico
        FROM c_proveedores
        $where
        ORDER BY Nombre
        LIMIT 25
    ";

    $st = $pdo->prepare($sql);
    $st->bindValue(':activo', $inactivos ? 0 : 1, PDO::PARAM_INT);
    if ($q !== '') $st->bindValue(':q', "%$q%", PDO::PARAM_STR);
    $st->execute();

    echo json_encode($st->fetchAll());
    exit;
}

/* =====================================================
 * GET / CREATE / UPDATE / DELETE / RESTORE (FULL + VALIDACIÓN)
 * ===================================================== */
switch ($action) {

    case 'get': {
        $id = $_GET['id'] ?? null;
        if (!$id) { echo json_encode(['error'=>'id requerido']); exit; }
        $st = $pdo->prepare("SELECT * FROM c_proveedores WHERE ID_Proveedor=?");
        $st->execute([$id]);
        echo json_encode($st->fetch());
        break;
    }

    case 'create': {
        $data = $_POST;
        $errs = validar_obligatorios($data);
        if($errs){
            echo json_encode(['error'=>'Validación','detalles'=>$errs]);
            exit;
        }

        $sql = "
            INSERT INTO c_proveedores
            (Empresa,Nombre,RUT,direccion,cve_dane,ID_Externo,Activo,cve_proveedor,colonia,ciudad,estado,pais,telefono1,telefono2,
             es_cliente,longitud,latitud,es_transportista,envio_correo_automatico)
            VALUES
            (:Empresa,:Nombre,:RUT,:direccion,:cve_dane,:ID_Externo,:Activo,:cve_proveedor,:colonia,:ciudad,:estado,:pais,:telefono1,:telefono2,
             :es_cliente,:longitud,:latitud,:es_transportista,:envio_correo_automatico)
        ";
        $st = $pdo->prepare($sql);

        $st->execute([
            ':Empresa' => s($data['Empresa'] ?? null),
            ':Nombre'  => s($data['Nombre'] ?? null),
            ':RUT'     => s($data['RUT'] ?? null),
            ':direccion' => s($data['direccion'] ?? null),
            ':cve_dane' => s($data['cve_dane'] ?? null),
            ':ID_Externo' => ($data['ID_Externo'] ?? '') === '' ? null : (int)$data['ID_Externo'],
            ':Activo' => i1($data['Activo'] ?? 1),
            ':cve_proveedor' => trim((string)($data['cve_proveedor'] ?? '')),
            ':colonia' => s($data['colonia'] ?? null),
            ':ciudad'  => s($data['ciudad'] ?? null),
            ':estado'  => s($data['estado'] ?? null),
            ':pais'    => trim((string)($data['pais'] ?? '')),
            ':telefono1' => only_digits($data['telefono1'] ?? ''),
            ':telefono2' => only_digits($data['telefono2'] ?? ''),
            ':es_cliente' => i0($data['es_cliente'] ?? 0),
            ':longitud' => fnull($data['longitud'] ?? null),
            ':latitud'  => fnull($data['latitud'] ?? null),
            ':es_transportista' => i0($data['es_transportista'] ?? 0),
            ':envio_correo_automatico' => i0($data['envio_correo_automatico'] ?? 0)
        ]);

        echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]);
        break;
    }

    case 'update': {
        $id = $_POST['ID_Proveedor'] ?? null;
        if (!$id) { echo json_encode(['error'=>'ID_Proveedor requerido']); exit; }

        $data = $_POST;
        $errs = validar_obligatorios($data);
        if($errs){
            echo json_encode(['error'=>'Validación','detalles'=>$errs]);
            exit;
        }

        $sql = "
            UPDATE c_proveedores SET
                Empresa=:Empresa,
                Nombre=:Nombre,
                RUT=:RUT,
                direccion=:direccion,
                cve_dane=:cve_dane,
                ID_Externo=:ID_Externo,
                Activo=:Activo,
                cve_proveedor=:cve_proveedor,
                colonia=:colonia,
                ciudad=:ciudad,
                estado=:estado,
                pais=:pais,
                telefono1=:telefono1,
                telefono2=:telefono2,
                es_cliente=:es_cliente,
                longitud=:longitud,
                latitud=:latitud,
                es_transportista=:es_transportista,
                envio_correo_automatico=:envio_correo_automatico
            WHERE ID_Proveedor=:ID_Proveedor
        ";
        $st = $pdo->prepare($sql);

        $st->execute([
            ':Empresa' => s($data['Empresa'] ?? null),
            ':Nombre'  => s($data['Nombre'] ?? null),
            ':RUT'     => s($data['RUT'] ?? null),
            ':direccion' => s($data['direccion'] ?? null),
            ':cve_dane' => s($data['cve_dane'] ?? null),
            ':ID_Externo' => ($data['ID_Externo'] ?? '') === '' ? null : (int)$data['ID_Externo'],
            ':Activo' => i1($data['Activo'] ?? 1),
            ':cve_proveedor' => trim((string)($data['cve_proveedor'] ?? '')),
            ':colonia' => s($data['colonia'] ?? null),
            ':ciudad'  => s($data['ciudad'] ?? null),
            ':estado'  => s($data['estado'] ?? null),
            ':pais'    => trim((string)($data['pais'] ?? '')),
            ':telefono1' => only_digits($data['telefono1'] ?? ''),
            ':telefono2' => only_digits($data['telefono2'] ?? ''),
            ':es_cliente' => i0($data['es_cliente'] ?? 0),
            ':longitud' => fnull($data['longitud'] ?? null),
            ':latitud'  => fnull($data['latitud'] ?? null),
            ':es_transportista' => i0($data['es_transportista'] ?? 0),
            ':envio_correo_automatico' => i0($data['envio_correo_automatico'] ?? 0),
            ':ID_Proveedor' => $id
        ]);

        echo json_encode(['success'=>true]);
        break;
    }

    case 'delete': {
        $id = $_POST['id'] ?? null;
        if (!$id) { echo json_encode(['error'=>'id requerido']); exit; }
        $pdo->prepare("UPDATE c_proveedores SET Activo=0 WHERE ID_Proveedor=?")->execute([$id]);
        echo json_encode(['success'=>true]);
        break;
    }

    case 'restore': {
        $id = $_POST['id'] ?? null;
        if (!$id) { echo json_encode(['error'=>'id requerido']); exit; }
        $pdo->prepare("UPDATE c_proveedores SET Activo=1 WHERE ID_Proveedor=?")->execute([$id]);
        echo json_encode(['success'=>true]);
        break;
    }

    default:
        echo json_encode(['error'=>'Acción no válida']);
}
