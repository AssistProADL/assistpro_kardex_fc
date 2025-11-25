<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$doc = $_GET['doc'] ?? '';
if (!$doc) {
    echo json_encode(['ok'=>false,'mensaje'=>'Documento no especificado']); 
    exit;
}

$fecha  = $_POST['fecha_cobro'] ?? '';
$monto  = isset($_POST['monto']) ? (float)$_POST['monto'] : 0;
$forma  = $_POST['forma_pago'] ?? '';   // ID de forma de pago
$ref    = $_POST['referencia'] ?? '';   // se guarda en ClaveBco
$obs    = $_POST['obs'] ?? '';          // solo informativo por ahora

// Validaciones básicas
if (!$fecha || !$monto || !$forma) {
    echo json_encode(['ok'=>false,'mensaje'=>'Campos obligatorios incompletos']);
    exit;
}
if ($monto <= 0) {
    echo json_encode(['ok'=>false,'mensaje'=>'El monto debe ser mayor a cero']);
    exit;
}

// Info del documento desde vista analítica
$info = db_one("SELECT * FROM v_cobranza_analitico WHERE Documento = ? LIMIT 1", [$doc]);
if (!$info) {
    echo json_encode(['ok'=>false,'mensaje'=>'No se encontró el documento en la vista de cobranza']);
    exit;
}

$idCobranza     = $info['id'];              // PK de cobranza
$rutaId         = $info['RutaId'];
$idEmpresa      = $info['IdEmpresa'];
$diaO           = isset($info['DiaO']) ? (int)$info['DiaO'] : 0;
$idDestinatario = $info['IdDestinatario'] ?? $info['Cliente'] ?? '';
$saldoCobranza  = (float)$info['Saldo'];    // monto original del documento

// Último registro en detallecob
$last = db_one(
    "SELECT Saldo, SaldoAnt, Abono, Fecha 
     FROM detallecob 
     WHERE IdCobranza = ? 
     ORDER BY Fecha DESC 
     LIMIT 1",
    [$idCobranza]
);

if ($last) {
    $saldoAnt = (float)$last['Saldo'];   // saldo actual se vuelve saldo anterior
} else {
    $saldoAnt = $saldoCobranza;         // primer abono: usamos monto original
}

// Validar que el abono no exceda el saldo
if ($monto > $saldoAnt) {
    echo json_encode([
        'ok'      => false,
        'mensaje' => "El abono ($monto) no puede ser mayor al saldo actual ($saldoAnt)"
    ]);
    exit;
}

// Nuevo saldo (controlado solo en detallecob)
$saldoNuevo = $saldoAnt - $monto;

// INSERT en detallecob
$sqlIns = "INSERT INTO detallecob 
           (IdCobranza, RutaId, Documento, Abono, Fecha, FormaP, DiaO, Cliente,
            IdEmpresa, Cancelada, SaldoAnt, Saldo, ClaveBco)
           VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";

$paramsIns = [
    $idCobranza,      // IdCobranza
    $rutaId,          // RutaId
    $doc,             // Documento
    $monto,           // Abono
    $fecha,           // Fecha
    $forma,           // FormaP (id forma de pago)
    $diaO,            // DiaO
    $idDestinatario,  // Cliente (id_destinatario)
    $idEmpresa,       // IdEmpresa
    1,                // Cancelada = 1
    $saldoAnt,        // SaldoAnt
    $saldoNuevo,      // Saldo
    $ref              // ClaveBco (referencia / folio)
];

try {
    dbq($sqlIns, $paramsIns);

    // ===== Actualización de ESTATUS solamente (1=Abierto, 2=Pagado) =====
    $statusDoc = ($saldoNuevo <= 0) ? 2 : 1;  // 2 = pagado, 1 = abierto

    dbq("UPDATE cobranza SET Status = ? WHERE id = ?", [
        $statusDoc,
        $idCobranza
    ]);

    echo json_encode([
        'ok'      => true,
        'mensaje' => "Cobro registrado correctamente. Nuevo saldo de seguimiento: " . number_format($saldoNuevo,2)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'Error al guardar el cobro: ' . $e->getMessage()
    ]);
}
