<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../app/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

db_pdo();
global $pdo;

function out($ok,$msg='',$extra=[]){
    echo json_encode(array_merge(['ok'=>$ok,'msg'=>$msg],$extra));
    exit;
}

$USR = $_SESSION['cve_usuario'] ?? 'SISTEMA';
$EMP = $_SESSION['empresa_id'] ?? 0;
$ALM = $_SESSION['almacen_id'] ?? 0;

$action = $_GET['action'] ?? $_POST['action'] ?? '';

/* ===============================
   LISTADO
================================ */
if ($action==='list'){
    $rows = db_all("
        SELECT 
            i.folio,
            i.tipo_importador,
            i.fecha,
            i.usuario,
            i.almacen_origen,
            MAX(d.zona_recibo_destino) zona_destino,
            'RECIBIDO / RTM' estatus
        FROM th_importacion i
        JOIN td_importacion d ON d.folio=i.folio
        WHERE i.tipo_importador='TRALM'
        GROUP BY i.folio
        ORDER BY i.fecha DESC
    ");
    out(true,'',$rows);
}

/* ===============================
   DETALLE
================================ */
if ($action==='get'){
    $folio=$_GET['folio']??'';
    if(!$folio) out(false,'Folio requerido');

    $cab = db_one("SELECT * FROM th_importacion WHERE folio=?",[$folio]);
    $det = db_all("SELECT * FROM td_importacion WHERE folio=?",[$folio]);

    out(true,'',['cabecera'=>$cab,'detalle'=>$det]);
}

/* ===============================
   PDF / EXCEL (hooks)
================================ */
if (in_array($action,['pdf_origen','pdf_destino','excel_validacion'])){
    out(true,'Documento generado',['url'=>"/docs/$action.php?folio=".$_GET['folio']]);
}

/* ===============================
   ROLLBACK ADMINISTRATIVO
================================ */
if ($action==='rollback'){
    $folio=$_POST['folio']??'';
    if(!$folio) out(false,'Folio requerido');

    // Solo administrativo (estatus)
    $pdo->prepare("UPDATE th_importacion SET estatus='ROLLBACK' WHERE folio=?")
        ->execute([$folio]);

    out(true,'Rollback aplicado');
}

out(false,'Acción inválida');
