<?php
// /public/manufactura/iniciar_produccion.php
// Consolidado de OTs (1..N) + ejecución con mínimas instrucciones a BD.

declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';

$pdo = db_pdo();

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function f4($n): string { return number_format((float)$n, 4, '.', ','); }

// Unidades discretas (no fraccionables): se redondea hacia arriba la CRT.
// Ajusta este set si en tu operación existen otras UOM discretas.
function is_discrete_uom(?string $uom): bool {
  $u = strtoupper(trim((string)$uom));
  if ($u === '') return false;
  $discrete = ['PIEZA','PZ','PZA','PAR','UN','UNI','UNIDAD','UNIDADES'];
  return in_array($u, $discrete, true);
}

// Ceil robusto para flotantes (evita que 5.0000000001 suba accidentalmente)
function ceil_safe(float $x): float {
  $eps = 1e-9;
  return (float)ceil($x - $eps);
}

$usr = $_SESSION['cve_usuario'] ?? ($_SESSION['username'] ?? ($_SESSION['usuario'] ?? 'SISTEMA'));

/* ==========================================================
   API INLINE (JSON) — Ejecutar producción consolidada
   - 1 instrucción por artículo MP
   - Permite consumo negativo
   IMPORTANT: este bloque debe ejecutarse ANTES del layout.
   ========================================================== */
if (isset($_POST['action']) && $_POST['action'] === 'exec') {
  // Evitar que salga cualquier HTML previo
  if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
  header('Content-Type: application/json; charset=utf-8');

  $t0 = microtime(true);

  $foliosRaw = (string)($_POST['folios'] ?? '');
  $bl_mp = (int)($_POST['bl_mp'] ?? 0);
  $bl_pt = (int)($_POST['bl_pt'] ?? 0);

  $folios = array_values(array_unique(array_filter(array_map('trim', explode(',', $foliosRaw)))));
  if (!$folios) { echo json_encode(['ok'=>false,'error'=>'Folios inválidos']); exit; }
  if ($bl_mp <= 0) { echo json_encode(['ok'=>false,'error'=>'Selecciona BL de Materia Prima (MP)']); exit; }
  if ($bl_pt <= 0) { $bl_pt = $bl_mp; }

  // Leer OTs
  $in = implode(',', array_fill(0, count($folios), '?'));
  $sqlOT = "
    SELECT id, Folio_Pro, cve_almac, ID_Proveedor, Cve_Articulo, Cantidad, Status
    FROM t_ordenprod
    WHERE Folio_Pro IN ($in)
  ";
  $st = $pdo->prepare($sqlOT);
  $st->execute($folios);
  $ots = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
  if (!$ots) { echo json_encode(['ok'=>false,'error'=>'OT(s) no encontradas']); exit; }

  // Gobernanza mínima: mismas empresa/almacén y Planeadas
  $alm = (int)($ots[0]['cve_almac'] ?? 0);
  $prov = (int)($ots[0]['ID_Proveedor'] ?? 0);
  foreach ($ots as $r) {
    if ((string)($r['Status'] ?? '') !== 'P') {
      echo json_encode(['ok'=>false,'error'=>'Todas las OTs deben estar en status Planeada (P)']);
      exit;
    }
    if ((int)($r['cve_almac'] ?? 0) !== $alm) {
      echo json_encode(['ok'=>false,'error'=>'Consolidado inválido: OTs de distinto almacén']);
      exit;
    }
    if ((int)($r['ID_Proveedor'] ?? 0) !== $prov) {
      echo json_encode(['ok'=>false,'error'=>'Consolidado inválido: OTs de distinta empresa']);
      exit;
    }
  }

  // Consolidar BOM:
// IMPORTANTE: td_ordenprod.Cantidad se está manejando como CRT por OT (requerido TOTAL para esa OT).
// Por lo tanto, para consolidar 1..N OTs solo se SUMA Cantidad, sin volver a multiplicar por la cantidad OT.
$cantOT = [];
foreach ($ots as $r) {
  $cantOT[(string)$r['Folio_Pro']] = (float)($r['Cantidad'] ?? 0);
}

$sqlBom = "
  SELECT Folio_Pro, Cve_Articulo, Cantidad
  FROM td_ordenprod
  WHERE Folio_Pro IN ($in)
    AND (Activo = 1 OR Activo IS NULL)
";
$stb = $pdo->prepare($sqlBom);
$stb->execute($folios);
$rows = $stb->fetchAll(PDO::FETCH_ASSOC) ?: [];

$mp = []; // art => ['crt'=>float,'qty'=>float]
foreach ($rows as $ln) {
  $art = (string)($ln['Cve_Articulo'] ?? '');
  if ($art === '') continue;

  $folio = (string)($ln['Folio_Pro'] ?? '');
  $otQty = (float)($cantOT[$folio] ?? 0);

  $crtOt = (float)($ln['Cantidad'] ?? 0); // CRT por OT (ya total)

  if (!isset($mp[$art])) $mp[$art] = ['crt'=>0.0,'qty'=>0.0];
  $mp[$art]['crt'] += $crtOt;
  $mp[$art]['qty'] += $otQty;
}

// Normalizar a lista simple: $mpReq[art] = CRT consolidada
$mpReq = [];
foreach ($mp as $art => $v) {
  $mpReq[$art] = (float)($v['crt'] ?? 0);
}

// Regla operativa: si el componente es discreto (PIEZA/PAR/UN...), la CRT debe redondearse al entero superior.
if ($mpReq) {
  $arts = array_keys($mpReq);
  $inA = implode(',', array_fill(0, count($arts), '?'));
  try {
    $stU = $pdo->prepare("SELECT cve_articulo, unidadMedida AS UMed FROM c_articulo WHERE cve_articulo IN ($inA)");
    $stU->execute($arts);
    $urows = $stU->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $uMap = [];
    foreach ($urows as $ur) {
      $uMap[(string)($ur['cve_articulo'] ?? '')] = (string)($ur['UMed'] ?? '');
    }
    foreach ($mpReq as $art => $req) {
      $u = (string)($uMap[$art] ?? '');
      if (is_discrete_uom($u)) {
        $mpReq[$art] = ceil_safe((float)$req);
      }
    }
  } catch (Throwable $e) {
    // Si no existe c_articulo/unidadMedida, no aplicamos redondeo por UMed.
  }
}
$tx0 = microtime(true);
  try {
    $pdo->beginTransaction();

    // 1) Marcar OTs
    $stUp = $pdo->prepare("UPDATE t_ordenprod SET Status='E', Hora_Ini=NOW(), idy_ubica_dest=? WHERE Folio_Pro=?");
    foreach ($folios as $f) {
      $stUp->execute([$bl_pt, $f]);
    }

    // 2) Consumo consolidado MP — permite negativos
    $stCons = $pdo->prepare("UPDATE ts_existenciapiezas
      SET Existencia = COALESCE(Existencia,0) - ?
      WHERE cve_almac=? AND ID_Proveedor=? AND idy_ubica=? AND cve_articulo=?");

    $movs = 0;
    foreach ($mpReq as $art => $req) {
      $stCons->execute([$req, $alm, $prov, $bl_mp, (string)$art]);
      $movs++;
    }

    $pdo->commit();

    $tx1 = microtime(true);
    $t1 = microtime(true);

    echo json_encode([
      'ok' => true,
      'data' => [
        'ots' => count($folios),
        'mp_articulos' => count($mpReq),
        'movimientos_mp' => $movs,
        'bl_mp' => $bl_mp,
        'bl_pt' => $bl_pt,
        'ms_total' => round(($t1-$t0)*1000, 2),
        'ms_tx' => round(($tx1-$tx0)*1000, 2),
      ]
    ]);
    exit;

  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['ok'=>false,'error'=>'Fallo al ejecutar: '.$e->getMessage()]);
    exit;
  }
}

/* ==========================================================
   A partir de aquí: UI (layout)
   ========================================================== */
require_once __DIR__ . '/../bi/_menu_global.php';

// Soporta: ?folios=OT1,OT2 ó ?folio=OT ó ?id=...
$id = (int)($_GET['id'] ?? 0);
$folio = trim((string)($_GET['folio'] ?? ''));
$foliosRaw = trim((string)($_GET['folios'] ?? ''));

$folios = [];
if ($foliosRaw !== '') {
  $folios = array_values(array_unique(array_filter(array_map('trim', explode(',', $foliosRaw)))));
} elseif ($folio !== '') {
  $folios = [$folio];
} elseif ($id > 0) {
  $st = $pdo->prepare("SELECT Folio_Pro FROM t_ordenprod WHERE id=? LIMIT 1");
  $st->execute([$id]);
  $f = (string)($st->fetchColumn() ?: '');
  if ($f !== '') $folios = [$f];
}
if (!$folios) { die('OT no especificada'); }

$in = implode(',', array_fill(0, count($folios), '?'));
$sqlOT = "
SELECT
  t.id,
  t.Folio_Pro,
  t.cve_almac,
  t.ID_Proveedor,
  t.Cve_Articulo,
  t.Cantidad,
  t.Hora_Ini,
  t.Status,
  t.idy_ubica_dest,
  COALESCE(p.Nombre, CONCAT('Proveedor #', t.ID_Proveedor)) AS EmpresaNombre
FROM t_ordenprod t
LEFT JOIN c_proveedores p
  ON p.ID_Proveedor = t.ID_Proveedor
WHERE t.Folio_Pro IN ($in)
";
$st = $pdo->prepare($sqlOT);
$st->execute($folios);
$ots = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
if (!$ots) { die('Orden(es) de producción no encontrada(s)'); }

$esConsolidado = count($ots) > 1;
$ot0 = $ots[0];

// Gobernanza (UI)
$almSet = array_values(array_unique(array_map(fn($r)=>(string)($r['cve_almac'] ?? ''), $ots)));
$provSet = array_values(array_unique(array_map(fn($r)=>(string)($r['ID_Proveedor'] ?? ''), $ots)));
$almInt = (int)($ot0['cve_almac'] ?? 0);
$idProv = (int)($ot0['ID_Proveedor'] ?? 0);

// Labels (clave + descripción) para UI
$almLabel = (string)$almInt;
try {
  $stA = $pdo->prepare("SELECT Nombre FROM c_almacen WHERE cve_almac=? LIMIT 1");
  $stA->execute([$almInt]);
  $nm = trim((string)($stA->fetchColumn() ?: ''));
  if ($nm !== '') $almLabel = $almInt . ' - ' . $nm;
} catch (Throwable $e) {
  // Fallback: mostrar solo clave
}

$problemaGov = '';
if ($esConsolidado && (count($almSet) !== 1 || count($provSet) !== 1)) {
  $problemaGov = 'Consolidación no permitida: selecciona OTs de la misma Empresa y Almacén.';
}

$statusSet = array_values(array_unique(array_map(fn($r)=>(string)($r['Status'] ?? ''), $ots)));
$status = (count($statusSet) === 1) ? (string)$statusSet[0] : 'M';
$statusLabel = match($status){
  'P' => 'Planeada',
  'E' => 'En proceso',
  'T' => 'Terminada',
  'C' => 'Cancelada',
  'M' => 'Mixto',
  default => ($status ?: '—')
};

$otCantTotal = 0.0;
foreach($ots as $r){ $otCantTotal += (float)($r['Cantidad'] ?? 0); }

$ptArts = array_values(array_unique(array_map(fn($r)=>(string)($r['Cve_Articulo'] ?? ''), $ots)));
$ptArt = (count($ptArts) === 1) ? $ptArts[0] : 'MULTI';

// BLs Producción (por almacén)
$sqlBL = "
SELECT u.idy_ubica, u.CodigoCSD, u.Ubicacion
FROM c_ubicacion u
WHERE u.cve_almac = :alm
  AND u.AreaProduccion = 'S'
  AND (u.Activo = 1 OR u.Activo IS NULL)
ORDER BY (u.CodigoCSD IS NULL), u.CodigoCSD ASC, u.idy_ubica ASC
";
$stb = $pdo->prepare($sqlBL);
$stb->execute([':alm'=>$almInt]);
$bls = $stb->fetchAll(PDO::FETCH_ASSOC) ?: [];

$blDefault = (int)($ot0['idy_ubica_dest'] ?? 0);
if($blDefault <= 0 && count($bls)>0){ $blDefault = (int)$bls[0]['idy_ubica']; }

// Consolidar componentes (BOM)
$otCantByFolio = [];
foreach($ots as $r){ $otCantByFolio[(string)$r['Folio_Pro']] = (float)($r['Cantidad'] ?? 0); }

$sqlComp = "
  SELECT d.Folio_Pro, d.Cve_Articulo, d.Cantidad, d.Referencia
  FROM td_ordenprod d
  WHERE d.Folio_Pro IN ($in)
    AND (d.Activo = 1 OR d.Activo IS NULL)
";
$stc = $pdo->prepare($sqlComp);
$stc->execute($folios);
$raw = $stc->fetchAll(PDO::FETCH_ASSOC) ?: [];

$componentes = []; // art => ['Cve_Articulo','UMed','CUR','CRT','CRT_RAW','Referencia','_qty_sum']
foreach($raw as $ln){
  $art = (string)($ln['Cve_Articulo'] ?? '');
  if($art==='') continue;

  $folioLn = (string)($ln['Folio_Pro'] ?? '');
  $otQty   = (float)($otCantByFolio[$folioLn] ?? 0);

  // IMPORTANTE:
  // td_ordenprod.Cantidad se está manejando como CRT por OT (requerido TOTAL para esa OT),
  // por lo que NO se debe volver a multiplicar por la cantidad a fabricar.
  $crtOt = (float)($ln['Cantidad'] ?? 0);

  if(!isset($componentes[$art])){
    $componentes[$art] = [
      'Cve_Articulo'=>$art,
      'UMed'=>'',
      'CUR'=>0.0,
      'CRT'=>0.0,
      'CRT_RAW'=>0.0,
      'Referencia'=>(string)($ln['Referencia'] ?? ''),
      '_qty_sum'=>0.0
    ];
  }

  $componentes[$art]['CRT'] += $crtOt;
  $componentes[$art]['CRT_RAW'] += $crtOt;
  $componentes[$art]['_qty_sum'] += $otQty;
}

// UMed por componente (para reglas de redondeo y display)
if ($componentes) {
  $arts = array_keys($componentes);
  $inA = implode(',', array_fill(0, count($arts), '?'));
  try {
    // Nota: en tu UI de BOM ya usas c_articulo.unidadMedida como fuente.
    $stU = $pdo->prepare("SELECT cve_articulo, unidadMedida AS UMed FROM c_articulo WHERE cve_articulo IN ($inA)");
    $stU->execute($arts);
    $urows = $stU->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $uMap = [];
    foreach ($urows as $ur) {
      $uMap[(string)($ur['cve_articulo'] ?? '')] = (string)($ur['UMed'] ?? '');
    }
    foreach ($componentes as $a => $c) {
      $componentes[$a]['UMed'] = (string)($uMap[$a] ?? '');
    }
  } catch (Throwable $e) {
    // Fallback silencioso: si no existe c_articulo o unidadMedida, seguimos sin UMed.
  }
}

// Calcular CUR consolidada = CRT / ΣCantidadOT (ponderado)
foreach($componentes as $art => $c){
  $qty = (float)($c['_qty_sum'] ?? 0);
  $crt = (float)($c['CRT'] ?? 0);
  $componentes[$art]['CUR'] = ($qty > 0) ? ($crt / $qty) : 0.0;
  unset($componentes[$art]['_qty_sum']);
}

// Redondeo operativo: unidades discretas (piezas) deben pedirse en entero superior.
foreach ($componentes as $art => $c) {
  $u = (string)($c['UMed'] ?? '');
  if (is_discrete_uom($u)) {
    $componentes[$art]['CRT'] = ceil_safe((float)($c['CRT'] ?? 0));
  }
}

ksort($componentes);
$tieneBom = count($componentes) > 0;

// Stock informativo (almacén completo)
function sumExistAll(PDO $pdo, string $table, int $alm, int $prov, string|int $art): float {
  $sql = "SELECT COALESCE(SUM(existencia),0) FROM {$table} WHERE cve_almac=? AND ID_Proveedor=? AND cve_articulo=?";
  $st = $pdo->prepare($sql);
  $st->execute([$alm,$prov,(string)$art]);
  return (float)$st->fetchColumn();
}
function sumExist(PDO $pdo, string $table, int $alm, int $prov, int $bl, string|int $art): float {
  $sql = "SELECT COALESCE(SUM(existencia),0) FROM {$table} WHERE cve_almac=? AND ID_Proveedor=? AND idy_ubica=? AND cve_articulo=?";
  $st = $pdo->prepare($sql);
  $st->execute([$alm,$prov,$bl,(string)$art]);
  return (float)$st->fetchColumn();
}

$stockComp = [];
foreach($componentes as $a=>$c){
  $stockComp[$a] = [
    'pzas' => sumExistAll($pdo,'ts_existenciapiezas',$almInt,$idProv,$a),
    'tar'  => sumExistAll($pdo,'ts_existenciatarima',$almInt,$idProv,$a)
  ];
}

$ptPzasIni = 0.0; $ptTarIni = 0.0;
if($blDefault>0 && $ptArt!=='' && $ptArt!=='MULTI'){
  $ptPzasIni = sumExist($pdo,'ts_existenciapiezas',$almInt,$idProv,$blDefault,$ptArt);
  $ptTarIni  = sumExist($pdo,'ts_existenciatarima',$almInt,$idProv,$blDefault,$ptArt);
}
$ptIniTotal = $ptPzasIni + $ptTarIni;
$ptFinTeo   = ($ptArt==='MULTI') ? 0.0 : ($ptIniTotal + $otCantTotal);

$foliosSel = array_map(fn($r)=>(string)($r['Folio_Pro'] ?? ''), $ots);
$preview = implode(', ', array_slice($foliosSel, 0, 5));
$extra = count($foliosSel) > 5 ? (' … +' . (count($foliosSel)-5)) : '';
?>
<style>
  body{ background:#f6f8fb; }
  .ap-card{ background:#fff; border-radius:14px; border:1px solid #dbe3f0; padding:18px; box-shadow:0 6px 18px rgba(16,24,40,.08); }
  .ap-title{ font-size:18px; font-weight:800; color:#0F5AAD; letter-spacing:.2px; }
  .ap-sub{ font-size:12px; color:#667085; }
  .ap-label{ font-size:11px; color:#6c757d; }
  .ap-value{ font-size:13px; font-weight:700; color:#111827; }
  .pill{ display:inline-block; padding:.25rem .55rem; border-radius:999px; font-size:11px; font-weight:700; }
  .pill-warn{ background:#fff3cd; color:#7a5c00; border:1px solid #ffe69c; }
  .pill-ok{ background:#d1e7dd; color:#0f5132; border:1px solid #badbcc; }
  .pill-info{ background:#cff4fc; color:#055160; border:1px solid #b6effb; }
  table tbody td{ font-size:10px; white-space:nowrap; vertical-align:middle; }
  table thead th{ font-size:11px; }
</style>

<div class="container-fluid mt-4">
  <div class="ap-card mx-auto" style="max-width:1100px">

    <div class="d-flex align-items-center justify-content-between mb-2">
      <div>
        <div class="ap-title"><i class="fa fa-play-circle me-1"></i> Iniciar Producción</div>
        <div class="ap-sub">Consolidado de MP (mínimas instrucciones BD) y arranque de OTs seleccionadas. Consumo negativo permitido.</div>
      </div>
      <div class="text-end">
        <div class="ap-label">Usuario</div>
        <div class="ap-value"><?=h($usr)?></div>
      </div>
    </div>

    <?php if($problemaGov): ?>
      <div class="alert alert-danger mt-3"><i class="fa fa-times-circle"></i> <?=h($problemaGov)?></div>
    <?php endif; ?>

    <?php if(!$tieneBom): ?>
      <div class="alert alert-warning mt-3"><i class="fa fa-exclamation-triangle"></i> No se detectaron componentes en <b>td_ordenprod</b> para las OT(s) seleccionadas.</div>
    <?php endif; ?>

    <div class="alert alert-info mt-3 mb-3" style="font-size:12px;">
      <b>Métricas visibles:</b> total OTs, artículos MP consolidados, movimientos BD por artículo y tiempos (total / TX). Inventario MP puede quedar negativo.
    </div>

    <div class="row g-3 mb-3">
      <div class="col-md-3">
        <div class="ap-label">Folio</div>
        <div class="ap-value">
          <?php if(!$esConsolidado): ?>
            <?=h((string)$ot0['Folio_Pro'])?>
          <?php else: ?>
            Consolidado (<?=count($foliosSel)?> OTs): <?=h($preview . $extra)?>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-md-3">
        <div class="ap-label">Empresa</div>
        <div class="ap-value"><?=h((string)$ot0['EmpresaNombre'])?></div>
      </div>
      <div class="col-md-3">
        <div class="ap-label">Almacén</div>
        <div class="ap-value"><?=h($almLabel)?></div>
      </div>
      <div class="col-md-3">
        <div class="ap-label">Status</div>
        <div class="ap-value">
          <span class="pill <?= $status==='P'?'pill-warn':($status==='E'?'pill-info':'pill-ok') ?>"><?=h($statusLabel)?></span>
        </div>
      </div>

      <div class="col-md-4">
        <div class="ap-label">Producto</div>
        <div class="ap-value">
          <?php if($ptArt==='MULTI'): ?>
            Múltiple (<?=count($ptArts)?>)
          <?php else: ?>
            <?=h($ptArt)?>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-md-2">
        <div class="ap-label">Cantidad (total)</div>
        <div class="ap-value"><?=f4($otCantTotal)?></div>
      </div>
      <div class="col-md-3">
        <div class="ap-label">Hora Ini (referencia)</div>
        <div class="ap-value"><?=h((string)($ot0['Hora_Ini'] ?? '—'))?></div>
      </div>
      <div class="col-md-3">
        <div class="ap-label">Fecha/Hora (UI)</div>
        <div class="ap-value"><?=date('d/m/Y H:i:s')?></div>
      </div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label mb-0">BL Materia Prima (única)</label>
        <select id="selBLMP" class="form-select form-select-sm" <?=($status!=='P' || $problemaGov)?'disabled':''?>>
          <?php if(count($bls)===0): ?>
            <option value="0">No hay BLs con AreaProduccion='S' para este almacén</option>
          <?php else: ?>
            <?php foreach($bls as $b):
              $idb = (int)$b['idy_ubica'];
              $csd = trim((string)($b['CodigoCSD'] ?? ''));
              $txt = $csd !== '' ? $csd : ('UBI#'.$idb);
            ?>
              <option value="<?=h((string)$idb)?>" <?= $idb===$blDefault?'selected':''?>><?=h($txt)?></option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <div class="ap-label mt-1">MP debe consumirse desde una sola BL. Fuente: <b>c_ubicacion</b> (AreaProduccion='S').</div>
      </div>

      <div class="col-md-6">
        <div class="d-flex align-items-center justify-content-between">
          <label class="form-label mb-0">BL Producto Terminado (PT)</label>
          <div class="form-check" style="font-size:12px;">
            <input class="form-check-input" type="checkbox" id="chkPTSame" checked>
            <label class="form-check-label" for="chkPTSame">PT en la misma BL</label>
          </div>
        </div>
        <select id="selBLPT" class="form-select form-select-sm" disabled>
          <?php if(count($bls)===0): ?>
            <option value="0">No hay BLs disponibles</option>
          <?php else: ?>
            <?php foreach($bls as $b):
              $idb = (int)$b['idy_ubica'];
              $csd = trim((string)($b['CodigoCSD'] ?? ''));
              $txt = $csd !== '' ? $csd : ('UBI#'.$idb);
            ?>
              <option value="<?=h((string)$idb)?>" <?= $idb===$blDefault?'selected':''?>><?=h($txt)?></option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>

        <div class="ap-label mt-1">
          <?php if($ptArt==='MULTI'): ?>
            PT: múltiple producto, stock no consolidable en una sola línea.
          <?php else: ?>
            PT — Stock inicial en BL default: Total <?=f4($ptIniTotal)?> (Pzas <?=f4($ptPzasIni)?> | Tar <?=f4($ptTarIni)?>) → Final teórico <b><?=f4($ptFinTeo)?></b>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-sm table-striped align-middle">
        <thead>
          <tr>
            <th style="width:40px;">#</th>
            <th>Componente</th>
            <th class="text-center">UMed</th>
            <th class="text-end">CUR</th>
            <th class="text-end">CRT</th>
            <th class="text-end" title="CRT sin redondeo (referencia)">CRT Raw</th>
            <th class="text-end">Stock Pzas</th>
            <th class="text-end">Proj Pzas</th>
            <th class="text-end">Stock Tarima</th>
            <th class="text-end">Proj Tarima</th>
          </tr>
        </thead>
        <tbody>
          <?php if(!$tieneBom): ?>
            <tr><td colspan="10" class="text-center text-muted">Sin componentes.</td></tr>
          <?php else: ?>
            <?php $i=1; foreach($componentes as $a=>$c):
              $crt = (float)($c['CRT'] ?? 0);
              $crtRaw = (float)($c['CRT_RAW'] ?? $crt);
              $cur = (float)($c['CUR'] ?? 0);
              $uom = (string)($c['UMed'] ?? '');
              $sp = (float)($stockComp[$a]['pzas'] ?? 0);
              $stt = (float)($stockComp[$a]['tar'] ?? 0);
              $projP = $sp - $crt;
              $projT = $stt - $crt;
            ?>
              <tr>
                <td><?= $i++ ?></td>
                <td class="fw-semibold"><?=h((string)$a)?></td>
                <td class="text-center"><?=h($uom!==''?$uom:'—')?></td>
                <td class="text-end"><?=f4($cur)?></td>
                <td class="text-end"><?=f4($crt)?></td>
                <td class="text-end"><?=f4($crtRaw)?></td>
                <td class="text-end"><?=f4($sp)?></td>
                <td class="text-end <?=($projP<0?'text-danger fw-bold':'text-success fw-bold')?>"><?=f4($projP)?></td>
                <td class="text-end"><?=f4($stt)?></td>
                <td class="text-end <?=($projT<0?'text-danger fw-bold':'text-success fw-bold')?>"><?=f4($projT)?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="ap-label mt-1">
      * Proyección = <b>Stock - CRT</b>. Negativos permitidos. 
      Para unidades discretas (PIEZA/PAR/UN...), <b>CRT se redondea al entero superior</b> (ver CRT Raw).
    </div>

    <div id="execMetrics" class="mt-3" style="display:none;"></div>

    <div class="d-flex justify-content-end gap-2 mt-3">
      <a href="monitor_produccion.php" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i> Regresar</a>
      <button id="btnIniciar" class="btn btn-primary btn-sm" <?=($status!=='P' || $blDefault<=0 || count($bls)===0 || $problemaGov)?'disabled':''?>>
        <i class="fa fa-play"></i> Confirmar e Iniciar
      </button>
    </div>

  </div>
</div>

<script>
(function(){
  const btn = document.getElementById('btnIniciar');
  const selMP = document.getElementById('selBLMP');
  const selPT = document.getElementById('selBLPT');
  const chkSame = document.getElementById('chkPTSame');
  const metricsBox = document.getElementById('execMetrics');
  const folios = <?= json_encode(implode(',', $foliosSel), JSON_UNESCAPED_UNICODE) ?>;

  if(chkSame){
    chkSame.addEventListener('change', function(){
      if(!selPT) return;
      selPT.disabled = this.checked;
    });
  }
  if(!btn) return;

  btn.addEventListener('click', function(){
    const blMP = selMP ? (selMP.value || '0') : '0';
    const blPT = (chkSame && chkSame.checked) ? blMP : (selPT ? (selPT.value || '0') : '0');

    if(!folios){ alert('Folios inválidos.'); return; }
    if(!blMP || blMP === '0'){ alert('Selecciona BL MP válida.'); return; }
    if(!blPT || blPT === '0'){ alert('Selecciona BL PT válida.'); return; }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Ejecutando...';

    const body = new URLSearchParams();
    body.set('action','exec');
    body.set('folios', folios);
    body.set('bl_mp', blMP);
    body.set('bl_pt', blPT);

    fetch('iniciar_produccion.php', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
      body: body.toString()
    })
    .then(async r => {
      const txt = await r.text();
      try { return JSON.parse(txt); }
      catch(e){ throw new Error('Respuesta no JSON: ' + txt.substring(0,120)); }
    })
    .then(resp=>{
      if(resp && resp.ok){
        const d = resp.data || {};
        metricsBox.style.display = 'block';
        metricsBox.innerHTML = `
          <div class="alert alert-success" style="font-size:12px;">
            <b>Producción ejecutada.</b><br>
            OTs: <b>${d.ots ?? 0}</b> | Artículos MP: <b>${d.mp_articulos ?? 0}</b> | Movimientos MP: <b>${d.movimientos_mp ?? 0}</b><br>
            BL MP: <b>${d.bl_mp ?? ''}</b> | BL PT: <b>${d.bl_pt ?? ''}</b><br>
            Tiempo total: <b>${d.ms_total ?? 0} ms</b> | Tiempo TX: <b>${d.ms_tx ?? 0} ms</b>
          </div>
        `;
        btn.innerHTML = '<i class="fa fa-check"></i> Ejecutado';
      } else {
        alert((resp && resp.error) ? resp.error : 'Error al iniciar producción');
        location.reload();
      }
    })
    .catch(err=>{
      alert('Error de comunicación: ' + (err && err.message ? err.message : err));
      location.reload();
    });
  });
})();
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
