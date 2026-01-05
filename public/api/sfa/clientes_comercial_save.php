<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../app/db.php';

try {
    $raw = file_get_contents("php://input");
    $j = json_decode($raw, true);

    $ids = $j['ids'] ?? [];
    if (!is_array($ids) || empty($ids)) {
        echo json_encode(["ok"=>0,"error"=>"Sin destinatarios"]);
        exit;
    }

    $overwrite   = !empty($j['overwrite']) ? 1 : 0;
    $ListaP      = ($j['ListaP']      ?? '') !== '' ? (int)$j['ListaP']      : null;
    $ListaPromo  = ($j['ListaPromo']  ?? '') !== '' ? (int)$j['ListaPromo']  : null;
    $ListaD      = ($j['ListaD']      ?? '') !== '' ? (int)$j['ListaD']      : null;

    if ($ListaP===null && $ListaPromo===null && $ListaD===null) {
        echo json_encode(["ok"=>0,"error"=>"Sin cambios"]);
        exit;
    }

    $idsInt = [];
    foreach ($ids as $v) {
        $v = (int)$v;
        if ($v > 0) $idsInt[] = $v;
    }
    $idsInt = array_unique($idsInt);

    $pdo = $GLOBALS['pdo'];
    $pdo->beginTransaction();

    $sel = $pdo->prepare("SELECT Id, ListaP, ListaPromo, ListaD FROM relclilis WHERE Id_Destinatario=? LIMIT 1");
    $ins = $pdo->prepare("INSERT INTO relclilis (Id_Destinatario) VALUES (?)");
    $upd = $pdo->prepare("UPDATE relclilis SET ListaP=?, ListaPromo=?, ListaD=? WHERE Id=?");

    $ok=0; $skip=0;

    foreach ($idsInt as $idDest) {
        $sel->execute([$idDest]);
        $row = $sel->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $ins->execute([$idDest]);
            $sel->execute([$idDest]);
            $row = $sel->fetch(PDO::FETCH_ASSOC);
        }

        $newLP = $row['ListaP'];
        $newPR = $row['ListaPromo'];
        $newDS = $row['ListaD'];

        if ($ListaP !== null && ($overwrite || !$newLP))     $newLP = $ListaP;
        if ($ListaPromo !== null && ($overwrite || !$newPR)) $newPR = $ListaPromo;
        if ($ListaD !== null && ($overwrite || !$newDS))     $newDS = $ListaD;

        if (
            (string)$newLP === (string)$row['ListaP'] &&
            (string)$newPR === (string)$row['ListaPromo'] &&
            (string)$newDS === (string)$row['ListaD']
        ) {
            $skip++;
            continue;
        }

        $upd->execute([$newLP, $newPR, $newDS, $row['Id']]);
        $ok++;
    }

    $pdo->commit();

    echo json_encode([
        "ok"=>1,
        "mensaje"=>"Asignación comercial aplicada",
        "actualizados"=>$ok,
        "sin_cambio"=>$skip
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["ok"=>0,"error"=>"Error servidor","detalle"=>$e->getMessage()]);
}
