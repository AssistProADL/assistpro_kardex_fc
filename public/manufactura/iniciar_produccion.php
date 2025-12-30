<?php
// /public/manufactura/iniciar_produccion.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$pdo = db_pdo();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function f2($n){ return number_format((float)$n, 2, '.', ','); }

$usr = 'DEMO'; // sin sesión por ahora

/* =============================
   Resolver OT (id / folio / folios)
   ============================= */
$id     = (int)($_GET['id'] ?? 0);
$folio  = trim((string)($_GET['folio'] ?? ''));
$folios = trim((string)($_GET['folios'] ?? ''));

if ($id <= 0) {
  $buscar = $folio !== '' ? $folio : $folios;
  if ($buscar !== '') {
    $st = $pdo->prepare("
      SELECT id
      FROM t_ordenprod
      WHERE Folio_Pro = :f
      LIMIT 1
    ");
    $st->execute([':f'=>$buscar]);
    $id = (int)($st->fetchColumn() ?: 0);
  }
}

if ($id <= 0) { die('OT no especificada'); }

/* =============================
   DATOS DE LA OT (cabecera) - SEGÚN TU t_ordenprod real
   ============================= */
$sqlOT = "
SELECT
  t.id,
  t.Folio_Pro,
  t.FolioImport,
  t.cve_almac,
  t.ID_Proveedor,
  t.Cve_Articulo,
  t.Cve_Lote,
  t.Cantidad,
  t.Cant_Prod,
  t.Cve_Usuario,
  t.Fecha,
  t.FechaReg,
  t.Hora_Ini,
  t.Hora_Fin,
  t.cronometro,
  t.id_umed,
  t.Status,
  t.Referencia,
  t.Cve_Almac_Ori,
  t.Tipo,
  t.id_zona_almac,
  t.idy_ubica,
  t.idy_ubica_dest,
  COALESCE(p.Nombre, CONCAT('Proveedor #', t.ID_Proveedor)) AS EmpresaNombre
FROM t_ordenprod t
LEFT JOIN c_proveedores p
  ON p.ID_Proveedor = t.ID_Proveedor
 AND (p.es_cliente = 1 OR p.es_cliente IS NULL)
WHERE t.id = :id
LIMIT 1
";
$stmt = $pdo->prepare($sqlOT);
$stmt->execute([':id'=>$id]);
$ot = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$ot){ die('Orden de producción no encontrada'); }

$status = (string)($ot['Status'] ?? '');
$statusLabel = match($status){
  'P' => 'Planeada',
  'E' => 'En proceso',
  'T' => 'Terminada',
  'C' => 'Cancelada',
  default => ($status ?: '—')
};

/* =============================
   COMPONENTES (BOM)
   En tu BD: td_ordenprod.Folio_Pro = t_ordenprod.Folio_Pro
   Cantidad requerida REAL = (td.Cantidad * OT.Cantidad)
   ============================= */
$componentes = [];
$sqlComp = "
  SELECT
    d.Folio_Pro,
    d.Cve_Articulo,
    d.Cantidad,
    d.Activo,
    d.Referencia,
    d.Cve_Lote
  FROM td_ordenprod d
  WHERE d.Folio_Pro = :folio
    AND (d.Activo = 1 OR d.Activo IS NULL)
  ORDER BY d.Cve_Articulo
";
$stc = $pdo->prepare($sqlComp);
$stc->execute([':folio'=>(string)$ot['Folio_Pro']]);
$componentes = $stc->fetchAll(PDO::FETCH_ASSOC) ?: [];
$tieneBom = count($componentes) > 0;

/* =============================
   BLs de Producción por ALMACÉN
   Reglas:
   - c_ubicacion.AreaProduccion='S'
   - Mostrar CodigoCSD (tu estándar BL)
   ============================= */
$almInt = (int)($ot['cve_almac'] ?? 0);

$sqlBL = "
SELECT
  u.idy_ubica,
  u.CodigoCSD,
  u.Ubicacion
FROM c_ubicacion u
WHERE u.cve_almac = :alm
  AND u.AreaProduccion = 'S'
  AND (u.Activo = 1 OR u.Activo IS NULL)
ORDER BY (u.CodigoCSD IS NULL), u.CodigoCSD ASC, u.idy_ubica ASC
";
$stb = $pdo->prepare($sqlBL);
$stb->execute([':alm'=>$almInt]);
$bls = $stb->fetchAll(PDO::FETCH_ASSOC) ?: [];

$blDefault = (int)($ot['idy_ubica_dest'] ?? 0);
if($blDefault <= 0 && count($bls)>0){
  $blDefault = (int)$bls[0]['idy_ubica'];
}

/* =============================
   STOCK (2 tablas finales)
   - ts_existenciapiezas
   - ts_existenciatarima
   Nota: medimos impacto real incluso si MP queda negativa.
   ============================= */
$idProv = (int)($ot['ID_Proveedor'] ?? 0);

function sumExist(PDO $pdo, string $table, int $alm, int $prov, int $bl, string $art): float {
  $sql = "SELECT COALESCE(SUM(existencia),0)
          FROM {$table}
          WHERE cve_almac=? AND ID_Proveedor=? AND idy_ubica=? AND cve_articulo=?";
  $st = $pdo->prepare($sql);
  $st->execute([$alm,$prov,$bl,$art]);
  return (float)$st->fetchColumn();
}
function sumExistAll(PDO $pdo, string $table, int $alm, int $prov, string $art): float {
  $sql = "SELECT COALESCE(SUM(existencia),0)
          FROM {$table}
          WHERE cve_almac=? AND ID_Proveedor=? AND cve_articulo=?";
  $st = $pdo->prepare($sql);
  $st->execute([$alm,$prov,$art]);
  return (float)$st->fetchColumn();
}

$otCant = (float)($ot['Cantidad'] ?? 0);
$ptArt  = (string)($ot['Cve_Articulo'] ?? '');

$ptPzasIni = 0.0;
$ptTarIni  = 0.0;
if($blDefault>0 && $ptArt!==''){
  $ptPzasIni = sumExist($pdo,'ts_existenciapiezas',$almInt,$idProv,$blDefault,$ptArt);
  $ptTarIni  = sumExist($pdo,'ts_existenciatarima',$almInt,$idProv,$blDefault,$ptArt);
}
$ptIniTotal = $ptPzasIni + $ptTarIni;
$ptFinTeo   = $ptIniTotal + $otCant;

/* Stock de componentes (total en almacén) */
$stockComp = []; // art => [pzas, tar]
if($tieneBom){
  foreach($componentes as $c){
    $a = (string)($c['Cve_Articulo'] ?? '');
    if($a==='') continue;
    if(!isset($stockComp[$a])){
      $stockComp[$a] = [
        'pzas' => sumExistAll($pdo,'ts_existenciapiezas',$almInt,$idProv,$a),
        'tar'  => sumExistAll($pdo,'ts_existenciatarima',$almInt,$idProv,$a),
      ];
    }
  }
}

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
        <div class="ap-sub">Arranque controlado de OT. Registra <b>Hora_Ini</b> y define <b>BL Producción</b> (sin consumo).</div>
      </div>
      <div class="text-end">
        <div class="ap-label">Usuario</div>
        <div class="ap-value"><?=h($usr)?></div>
      </div>
    </div>

    <?php if(!$tieneBom): ?>
      <div class="alert alert-warning mt-3">
        <i class="fa fa-exclamation-triangle"></i>
        No se detectaron componentes en <b>td_ordenprod</b> para el folio <b><?=h((string)$ot['Folio_Pro'])?></b>.
        (Para demo puedes iniciar igual, pero para productivo conviene BOM siempre).
      </div>
    <?php endif; ?>

    <div class="alert alert-info mt-3 mb-3" style="font-size:12px;">
      <b>Impacto real (BD):</b> mostramos stock inicial vs proyección. Materia prima puede quedar negativa para evidenciar velocidad/impacto.
    </div>

    <!-- Capa 1: Orden -->
    <div class="row g-3 mb-3">
      <div class="col-md-3">
        <div class="ap-label">Folio</div>
        <div class="ap-value"><?=h((string)$ot['Folio_Pro'])?></div>
      </div>
      <div class="col-md-3">
        <div class="ap-label">Empresa</div>
        <div class="ap-value"><?=h((string)$ot['EmpresaNombre'])?></div>
      </div>
      <div class="col-md-3">
        <div class="ap-label">Almacén</div>
        <div class="ap-value"><?=h((string)$ot['cve_almac'])?></div>
      </div>
      <div class="col-md-3">
        <div class="ap-label">Status</div>
        <div class="ap-value">
          <span class="pill <?= $status==='P'?'pill-warn':($status==='E'?'pill-info':'pill-ok') ?>"><?=h($statusLabel)?></span>
        </div>
      </div>

      <div class="col-md-4">
        <div class="ap-label">Producto</div>
        <div class="ap-value"><?=h((string)$ot['Cve_Articulo'])?></div>
      </div>
      <div class="col-md-2">
        <div class="ap-label">Cantidad</div>
        <div class="ap-value"><?=f2($otCant)?></div>
      </div>
      <div class="col-md-3">
        <div class="ap-label">Hora Ini</div>
        <div class="ap-value"><?=h((string)($ot['Hora_Ini'] ?? '—'))?></div>
      </div>
      <div class="col-md-3">
        <div class="ap-label">Fecha/Hora (UI)</div>
        <div class="ap-value"><?=date('d/m/Y H:i:s')?></div>
      </div>
    </div>

    <!-- BL Producción -->
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label mb-0">BL de Producción (por almacén)</label>
        <select id="selBL" class="form-select form-select-sm" <?=($status!=='P')?'disabled':''?>>
          <?php if(count($bls)===0): ?>
            <option value="0">No hay BLs con AreaProduccion='S' para este almacén</option>
          <?php else: ?>
            <?php foreach($bls as $b):
              $idb = (int)$b['idy_ubica'];
              $csd = trim((string)($b['CodigoCSD'] ?? ''));
              $txt = $csd !== '' ? $csd : ('UBI#'.$idb);
            ?>
              <option value="<?=h((string)$idb)?>" <?= $idb===$blDefault?'selected':''?>>
                <?=h($txt)?>
              </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <div class="ap-label mt-1">Fuente: <b>c_ubicacion</b> (AreaProduccion='S') mostrando <b>CodigoCSD</b>.</div>
      </div>

      <div class="col-md-6">
        <div class="ap-label">Producto Terminado — Stock inicial en BL seleccionado</div>
        <div class="ap-value">
          Total: <?=f2($ptIniTotal)?> (Pzas: <?=f2($ptPzasIni)?> | Tarima: <?=f2($ptTarIni)?>)
          &nbsp; → Final teórico: <b><?=f2($ptFinTeo)?></b>
        </div>
      </div>
    </div>

    <!-- Capa 2: Componentes -->
    <div class="table-responsive">
      <table class="table table-sm table-striped align-middle">
        <thead>
          <tr>
            <th style="width:40px;">#</th>
            <th>Componente</th>
            <th class="text-end">Factor BOM</th>
            <th class="text-end">Req Total</th>
            <th class="text-end">Stock Pzas</th>
            <th class="text-end">Proj Pzas</th>
            <th class="text-end">Stock Tarima</th>
            <th class="text-end">Proj Tarima</th>
          </tr>
        </thead>
        <tbody>
          <?php if(!$tieneBom): ?>
            <tr><td colspan="8" class="text-center text-muted">Sin componentes.</td></tr>
          <?php else: ?>
            <?php $i=1; foreach($componentes as $c):
              $a = (string)($c['Cve_Articulo'] ?? '');
              $factor = (float)($c['Cantidad'] ?? 0);
              $req = $factor * $otCant; // requerido real
              $sp = (float)($stockComp[$a]['pzas'] ?? 0);
              $st = (float)($stockComp[$a]['tar'] ?? 0);
              $projP = $sp - $req;
              $projT = $st - $req;
            ?>
              <tr>
                <td><?= $i++ ?></td>
                <td class="fw-semibold"><?=h($a)?></td>
                <td class="text-end"><?=f2($factor)?></td>
                <td class="text-end"><?=f2($req)?></td>
                <td class="text-end"><?=f2($sp)?></td>
                <td class="text-end <?=($projP<0?'text-danger fw-bold':'text-success fw-bold')?>"><?=f2($projP)?></td>
                <td class="text-end"><?=f2($st)?></td>
                <td class="text-end <?=($projT<0?'text-danger fw-bold':'text-success fw-bold')?>"><?=f2($projT)?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="ap-label mt-1">
      * Proyección = <b>Stock - Requerido Total</b>. Negativos permitidos (impacto real en BD).
    </div>

    <div class="d-flex justify-content-end gap-2 mt-3">
      <a href="monitor_produccion.php" class="btn btn-outline-secondary btn-sm">
        <i class="fa fa-arrow-left"></i> Regresar
      </a>

      <button id="btnIniciar" class="btn btn-primary btn-sm"
        <?=($status!=='P' || $blDefault<=0 || count($bls)===0)?'disabled':''?>>
        <i class="fa fa-play"></i> Confirmar e Iniciar
      </button>
    </div>

  </div>
</div>

<script>
(function(){
  const btn = document.getElementById('btnIniciar');
  const sel = document.getElementById('selBL');
  if(!btn) return;

  btn.addEventListener('click', function(){
    const id  = <?= (int)$ot['id'] ?>;
    const bl  = sel ? (sel.value || '0') : '0';

    if(!id || id <= 0){
      alert('OT inválida.');
      return;
    }
    if(!bl || bl === '0'){
      alert('Selecciona un BL de Producción válido.');
      return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Iniciando...';

    fetch('../api/iniciar_produccion.php', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
      body: 'id=' + encodeURIComponent(id) + '&idy_ubica_dest=' + encodeURIComponent(bl)
    })
    .then(r => r.json())
    .then(resp => {
      if(resp && resp.ok){
        window.location.href = 'monitor_produccion.php';
      }else{
        alert((resp && resp.error) ? resp.error : 'Error al iniciar producción');
        location.reload();
      }
    })
    .catch(err => {
      alert('Error de comunicación: ' + (err && err.message ? err.message : err));
      location.reload();
    });
  });
})();
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
