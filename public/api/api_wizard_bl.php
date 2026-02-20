<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();

function jexit(bool $ok, string $msg = '', $data = null): void {
  echo json_encode(['ok'=>$ok,'msg'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE);
  exit;
}

function s(?string $v): string { return trim((string)$v); }
function yn($v): string { return !empty($v) ? 'S' : 'N'; }

/** padding autodetectado con base en "desde" (si es numérico con ceros) */
function pad_auto(int $num, string $ref): string {
  $ref = trim($ref);
  $w = strlen($ref);
  if ($w > 1 && ctype_digit($ref)) return str_pad((string)$num, $w, '0', STR_PAD_LEFT);
  return (string)$num;
}

/** Código BL: PASILLO-RACK-NIVEL-SECCION-POS (pasillo/rack opcionales) */
function build_codigocsd(?string $pasillo, ?string $rack, string $nivel, string $seccion, string $pos): string {
  $parts = [];
  $pasillo = trim((string)$pasillo);
  $rack    = trim((string)$rack);
  if ($pasillo !== '') $parts[] = $pasillo;
  if ($rack !== '')    $parts[] = $rack;
  $parts[] = $nivel;
  $parts[] = $seccion;
  $parts[] = $pos;
  return implode('-', $parts);
}

$action = $_POST['action'] ?? $_GET['action'] ?? 'preview';

try {

  /* =========================
     PREVIEW
  ========================== */
  if ($action === 'preview') {
    $cve_almac = (int)($_POST['cve_almac'] ?? 0);
    if ($cve_almac <= 0) jexit(false, 'Selecciona un almacén operativo válido (cve_almac).');

    $pasillo = s($_POST['pasillo'] ?? '');
    $rack    = s($_POST['rack'] ?? '');

    $nivel_desde = (string)($_POST['nivel_desde'] ?? '1');
    $nivel_hasta = (string)($_POST['nivel_hasta'] ?? $nivel_desde);

    $sec_desde   = (string)($_POST['sec_desde'] ?? '1');
    $sec_hasta   = (string)($_POST['sec_hasta'] ?? $sec_desde);

    $pos_desde   = (string)($_POST['pos_desde'] ?? '1');
    $pos_hasta   = (string)($_POST['pos_hasta'] ?? $pos_desde);

    $n1 = (int)$nivel_desde; $n2 = (int)$nivel_hasta;
    $s1 = (int)$sec_desde;   $s2 = (int)$sec_hasta;
    $p1 = (int)$pos_desde;   $p2 = (int)$pos_hasta;

    if ($n2 < $n1) jexit(false, 'Rango de NIVEL inválido.');
    if ($s2 < $s1) jexit(false, 'Rango de SECCIÓN inválido.');
    if ($p2 < $p1) jexit(false, 'Rango de POSICIÓN inválido.');

    // Flags (S/N) + Activo
    $activo         = (int)($_POST['activo'] ?? 1);
    $picking        = yn($_POST['picking'] ?? null);
    $ptl            = yn($_POST['ptl'] ?? null);
    $acomodoMixto   = yn($_POST['mixto'] ?? null);
    $areaProd       = yn($_POST['prod'] ?? null);
    $areaStagging   = yn($_POST['piso'] ?? null);
    $reabasto       = yn($_POST['reab'] ?? null);

    $rows = [];
    for ($n=$n1; $n<=$n2; $n++) {
      $nFmt = pad_auto($n, $nivel_desde);
      for ($se=$s1; $se<=$s2; $se++) {
        $sFmt = pad_auto($se, $sec_desde);
        for ($p=$p1; $p<=$p2; $p++) {
          $pFmt = pad_auto($p, $pos_desde);

          $codigo = build_codigocsd($pasillo, $rack, $nFmt, $sFmt, $pFmt);

          $rows[] = [
            'cve_almac'      => $cve_almac,
            'CodigoCSD'      => $codigo,
            'cve_pasillo'    => $pasillo,
            'cve_rack'       => $rack,
            'cve_nivel'      => $nFmt,
            'Seccion'        => $sFmt,
            'Ubicacion'      => $pFmt,
            'Status'         => ($activo === 1 ? 'A' : 'B'),
            'Activo'         => $activo,
            'picking'        => $picking,
            'Ptl'            => $ptl,
            'AcomodoMixto'   => $acomodoMixto,
            'AreaProduccion' => $areaProd,
            'AreaStagging'   => $areaStagging,
            'Reabasto'       => $reabasto,
          ];
        }
      }
    }

    // conteo de duplicados (precheck)
    $dup = 0;
    if ($rows) {
      $chk = $pdo->prepare("SELECT 1 FROM c_ubicacion WHERE cve_almac=? AND CodigoCSD=? LIMIT 1");
      foreach ($rows as $r) {
        $chk->execute([(int)$r['cve_almac'], (string)$r['CodigoCSD']]);
        if ($chk->fetchColumn()) $dup++;
      }
    }

    $summary = [
      'total' => count($rows),
      'niv'   => ($n2 - $n1 + 1),
      'sec'   => ($s2 - $s1 + 1),
      'pos'   => ($p2 - $p1 + 1),
      'dup'   => $dup,
    ];

    jexit(true, '', ['summary'=>$summary, 'rows'=>$rows]);
  }

  /* =========================
     SAVE
  ========================== */
  if ($action === 'save') {
    $payload = json_decode((string)($_POST['payload'] ?? ''), true);
    if (!is_array($payload) || empty($payload['rows']) || !is_array($payload['rows'])) {
      jexit(false, 'Payload inválido. Genera previsualización primero.');
    }

    $rows = $payload['rows'];

    // Tomamos un consecutivo para idy_ubica por seguridad (si tu columna NO es auto_increment)
    $nextId = (int)$pdo->query("SELECT COALESCE(MAX(idy_ubica),0) + 1 AS nx FROM c_ubicacion")->fetchColumn();

    $sqlIns = "
      INSERT INTO c_ubicacion
      (idy_ubica, cve_almac, cve_pasillo, cve_rack, cve_nivel, Seccion, Ubicacion,
       Status, picking, Reabasto, Activo, AcomodoMixto, AreaProduccion, AreaStagging, Ptl, CodigoCSD)
      VALUES
      (:idy_ubica, :cve_almac, :cve_pasillo, :cve_rack, :cve_nivel, :Seccion, :Ubicacion,
       :Status, :picking, :Reabasto, :Activo, :AcomodoMixto, :AreaProduccion, :AreaStagging, :Ptl, :CodigoCSD)
    ";
    $ins = $pdo->prepare($sqlIns);
    $chk = $pdo->prepare("SELECT 1 FROM c_ubicacion WHERE cve_almac=? AND CodigoCSD=? LIMIT 1");

    $insertados = 0;
    $duplicados = 0;

    $pdo->beginTransaction();

    foreach ($rows as $r) {
      $cve_almac = (int)($r['cve_almac'] ?? 0);
      $codigo    = (string)($r['CodigoCSD'] ?? '');

      if ($cve_almac <= 0 || $codigo === '') continue;

      $chk->execute([$cve_almac, $codigo]);
      if ($chk->fetchColumn()) { $duplicados++; continue; }

      $ins->execute([
        ':idy_ubica'      => $nextId++,
        ':cve_almac'      => $cve_almac,
        ':cve_pasillo'    => (string)($r['cve_pasillo'] ?? ''),
        ':cve_rack'       => (string)($r['cve_rack'] ?? ''),
        ':cve_nivel'      => (string)($r['cve_nivel'] ?? ''),
        ':Seccion'        => (string)($r['Seccion'] ?? ''),
        ':Ubicacion'      => (string)($r['Ubicacion'] ?? ''),
        ':Status'         => (string)($r['Status'] ?? 'A'),
        ':picking'        => (string)($r['picking'] ?? 'N'),
        ':Reabasto'       => (string)($r['Reabasto'] ?? 'N'),
        ':Activo'         => (int)($r['Activo'] ?? 1),
        ':AcomodoMixto'   => (string)($r['AcomodoMixto'] ?? 'N'),
        ':AreaProduccion' => (string)($r['AreaProduccion'] ?? 'N'),
        ':AreaStagging'   => (string)($r['AreaStagging'] ?? 'N'),
        ':Ptl'            => (string)($r['Ptl'] ?? 'N'),
        ':CodigoCSD'      => $codigo,
      ]);

      $insertados++;
    }

    $pdo->commit();
    jexit(true, "Guardado completado. Insertados: $insertados. Duplicados omitidos: $duplicados.", [
      'insertados'=>$insertados,
      'duplicados'=>$duplicados
    ]);
  }

  jexit(false, 'Acción no soportada: '.$action);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  jexit(false, $e->getMessage());
}
