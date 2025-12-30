<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();

function jinput(): array {
  $raw = file_get_contents('php://input');
  if (!$raw) return [];
  $j = json_decode($raw, true);
  return is_array($j) ? $j : [];
}
function s($v){ $v = trim((string)$v); return $v==='' ? null : $v; }
function i0($v){ return ($v===''||$v===null) ? 0 : (int)$v; }

$req = array_merge($_GET, $_POST, jinput());
$action = $req['action'] ?? 'preview';

$IdEmpresa    = s($req['IdEmpresa'] ?? $req['idEmpresa'] ?? $req['almacen'] ?? null); // varchar(50)
$IdRutaDestino= i0($req['IdRutaDestino'] ?? $req['idRutaDestino'] ?? $req['IdRuta'] ?? null);

$clientes = $req['clientes'] ?? [];
if (!is_array($clientes)) $clientes = [];
$clientes = array_values(array_unique(array_filter(array_map(function($x){
  $x = trim((string)$x);
  return $x==='' ? null : $x;
}, $clientes))));

if ($action === 'preview') {
  if (!$IdEmpresa) { echo json_encode(['error'=>'IdEmpresa requerido']); exit; }
  if (count($clientes)===0) { echo json_encode(['ok'=>true,'clientes'=>[],'kpis'=>['clientes'=>0]]); exit; }

  $in = implode(',', array_fill(0, count($clientes), '?'));

  // Traemos cliente + destinatario + ruta actual (relclirutas) + nombre comercial
  $sql = "
    SELECT
      c.id_cliente,
      COALESCE(NULLIF(c.RazonComercial,''), NULLIF(c.RazonSocial,''), d.razonsocial) AS nombre,
      c.latitud, c.longitud,
      c.credito, c.dias_credito, c.saldo_actual, c.saldo_inicial, c.limite_credito,
      c.id_destinatario,
      (SELECT rc.IdRuta
         FROM relclirutas rc
        WHERE rc.IdCliente = CAST(c.id_cliente AS CHAR)
          AND rc.IdEmpresa = ?
        ORDER BY rc.Fecha DESC, rc.Id DESC
        LIMIT 1
      ) AS ruta_actual
    FROM c_cliente c
    LEFT JOIN c_destinatarios d ON d.id_destinatario = c.id_destinatario
    WHERE c.IdEmpresa = ?
      AND c.id_cliente IN ($in)
  ";
  $params = array_merge([$IdEmpresa, $IdEmpresa], $clientes);
  $st = $pdo->prepare($sql);
  $st->execute($params);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  $k = [
    'clientes' => count($rows),
    'con_gps'  => 0,
    'credito_total' => 0.0,
    'saldo_total'   => 0.0
  ];
  foreach ($rows as $r) {
    if (trim((string)$r['latitud'])!=='' && trim((string)$r['longitud'])!=='') $k['con_gps']++;
    $k['credito_total'] += (float)($r['limite_credito'] ?? 0);
    $k['saldo_total']   += (float)($r['saldo_actual'] ?? 0);
  }

  echo json_encode(['ok'=>true,'clientes'=>$rows,'kpis'=>$k]); exit;
}

/* ===========================
   APPLY (reasignación)
   =========================== */
if ($action === 'apply') {
  if (!$IdEmpresa) { echo json_encode(['error'=>'IdEmpresa requerido']); exit; }
  if ($IdRutaDestino<=0) { echo json_encode(['error'=>'IdRutaDestino requerido']); exit; }
  if (count($clientes)===0) { echo json_encode(['error'=>'clientes[] requerido']); exit; }

  $pdo->beginTransaction();
  try {
    // 1) Mapeo cliente -> id_destinatario
    $in = implode(',', array_fill(0, count($clientes), '?'));
    $stMap = $pdo->prepare("
      SELECT id_cliente,
             COALESCE(id_destinatario, id_cliente) AS id_destinatario
      FROM c_cliente
      WHERE IdEmpresa = ?
        AND id_cliente IN ($in)
    ");
    $stMap->execute(array_merge([$IdEmpresa], $clientes));
    $map = $stMap->fetchAll(PDO::FETCH_ASSOC);

    $destinatarios = [];
    $clientes_ok = [];
    foreach ($map as $m) {
      $clientes_ok[] = (string)$m['id_cliente'];
      if ($m['id_destinatario']!==null && $m['id_destinatario']!=='') {
        $destinatarios[] = (int)$m['id_destinatario'];
      }
    }
    $destinatarios = array_values(array_unique($destinatarios));

    // 2) relclirutas: update masivo
    // Nota: IdCliente en relclirutas es varchar(50); guardamos id_cliente como string.
    $stUpd = $pdo->prepare("
      UPDATE relclirutas
      SET IdRuta = ?, Fecha = CURDATE()
      WHERE IdEmpresa = ?
        AND IdCliente = ?
    ");
    $stIns = $pdo->prepare("
      INSERT INTO relclirutas (IdCliente, IdRuta, IdEmpresa, Fecha)
      VALUES (?, ?, ?, CURDATE())
    ");

    $u=0; $ins=0;
    foreach ($clientes_ok as $cli) {
      $stUpd->execute([$IdRutaDestino, $IdEmpresa, $cli]);
      $rows = $stUpd->rowCount();
      if ($rows>0) { $u += $rows; }
      else {
        $stIns->execute([$cli, $IdRutaDestino, $IdEmpresa]);
        $ins++;
      }
    }

    // 3) reldaycli: heredar ruta (repoint) manteniendo días
    $rd = 0;
    if (count($destinatarios)>0) {
      $inD = implode(',', array_fill(0, count($destinatarios), '?'));
      $stRd = $pdo->prepare("
        UPDATE reldaycli
        SET Cve_Ruta = ?
        WHERE Cve_Almac = ?
          AND Id_Destinatario IN ($inD)
      ");
      $stRd->execute(array_merge([$IdRutaDestino, $IdEmpresa], $destinatarios));
      $rd = $stRd->rowCount();
    }

    $pdo->commit();

    echo json_encode([
      'ok'=>true,
      'IdEmpresa'=>$IdEmpresa,
      'IdRutaDestino'=>$IdRutaDestino,
      'kpis'=>[
        'clientes_enviados'=>count($clientes),
        'clientes_validos'=>count($clientes_ok),
        'relclirutas_updates'=>$u,
        'relclirutas_inserts'=>$ins,
        'reldaycli_updates'=>$rd
      ]
    ]);
    exit;

  } catch (Throwable $e) {
    $pdo->rollBack();
    echo json_encode(['error'=>$e->getMessage()]);
    exit;
  }
}

echo json_encode(['error'=>'action no válido']);
