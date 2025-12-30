<?php
// /public/manufactura/iniciar_produccion.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../bi/_menu_global.php';

$pdo = db_pdo();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function f2($n){ return number_format((float)$n, 2, '.', ','); }

$id = (int)($_GET['id'] ?? 0);

// Defaults de filtros demo
$status = trim((string)($_GET['status'] ?? 'P'));
if ($status === '') $status = 'P';

$hoy = new DateTime();
$hasta = trim((string)($_GET['hasta'] ?? $hoy->format('Y-m-d')));
$desdeDt = new DateTime();
$desdeDt->modify('-120 days');
$desde = trim((string)($_GET['desde'] ?? $desdeDt->format('Y-m-d')));

$idProv = (int)($_GET['id_proveedor'] ?? 0);
$cveAlmac = trim((string)($_GET['cve_almac'] ?? ''));

/* ============================
   Helpers: detectar columna Nombre en c_proveedores
   ============================ */
$hasNombre = 0;
try {
  $stHN = $pdo->query("
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'c_proveedores'
      AND COLUMN_NAME = 'Nombre'
  ");
  $hasNombre = (int)$stHN->fetchColumn();
} catch (Throwable $e) {
  $hasNombre = 0;
}
$empresaExpr = $hasNombre > 0
  ? "COALESCE(p.Nombre, p.Empresa, CONCAT('Proveedor #', t.ID_Proveedor))"
  : "COALESCE(p.Empresa, CONCAT('Proveedor #', t.ID_Proveedor))";

/* ============================
   Si NO hay id -> LISTADO OTs
   ============================ */
$ots = [];
if ($id <= 0) {
  $where = [];
  $params = [];

  $where[] = "t.Status = :st";
  $params[':st'] = $status;

  $where[] = "DATE(COALESCE(t.FechaReg, t.Fecha, NOW())) BETWEEN :d1 AND :d2";
  $params[':d1'] = $desde;
  $params[':d2'] = $hasta;

  if ($idProv > 0) {
    $where[] = "t.ID_Proveedor = :pr";
    $params[':pr'] = $idProv;
  }
  if ($cveAlmac !== '' && $cveAlmac !== '0') {
    $where[] = "t.cve_almac = :alm";
    $params[':alm'] = $cveAlmac;
  }

  $sqlList = "
    SELECT
      t.id,
      t.Folio_Pro,
      t.Referencia,
      t.Cve_Articulo,
      t.Cantidad,
      t.cve_almac,
      t.Status,
      COALESCE(t.FechaReg, t.Fecha) AS FechaReg,
      {$empresaExpr} AS EmpresaNombre
    FROM t_ordenprod t
    LEFT JOIN c_proveedores p
      ON p.ID_Proveedor = t.ID_Proveedor
     AND (p.es_cliente = 1 OR p.es_cliente IS NULL)
    WHERE " . implode("\n AND ", $where) . "
    ORDER BY t.id DESC
    LIMIT 500
  ";
  $st = $pdo->prepare($sqlList);
  $st->execute($params);
  $ots = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/* ============================
   Si hay id -> DETALLE 3 capas
   ============================ */
$ot = null;
$componentes = [];
$bls = [];
$blDefault = 0;

$ptPzasIni = 0.0; $ptTarIni = 0.0;
$stockComp = []; // art => ['pzas'=>x,'tar'=>y]

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

if ($id > 0) {
  $sqlOT = "
    SELECT
      t.id,
      t.Folio_Pro,
      t.Referencia,
      t.Cve_Articulo,
      t.Cve_Lote,
      t.Cantidad,
      t.Cant_Prod,
      t.cve_almac,
      t.ID_Proveedor,
      t.Status,
      t.Hora_Ini,
      t.Hora_Fin,
      t.cronometro,
      t.idy_ubica,
      t.idy_ubica_dest,
      t.Usr_Armo,
      t.Cve_Usuario,
      COALESCE(t.FechaReg, t.Fecha) AS FechaReg,
      {$empresaExpr} AS EmpresaNombre
    FROM t_ordenprod t
    LEFT JOIN c_proveedores p
      ON p.ID_Proveedor = t.ID_Proveedor
     AND (p.es_cliente = 1 OR p.es_cliente IS NULL)
    WHERE t.id = :id
    LIMIT 1
  ";
  $st = $pdo->prepare($sqlOT);
  $st->execute([':id'=>$id]);
  $ot = $st->fetch(PDO::FETCH_ASSOC);
  if (!$ot) {
    $id = 0;
  } else {
    $folioBom = (string)($ot['Referencia'] ?? '');
    if ($folioBom !== '') {
      $stc = $pdo->prepare("
        SELECT d.id_ord, d.Cve_Articulo, d.Cantidad, d.Activo
        FROM td_ordenprod d
        WHERE d.Folio_Pro = :f
          AND (d.Activo = 1 OR d.Activo IS NULL)
        ORDER BY d.Cve_Articulo
      ");
      $stc->execute([':f'=>$folioBom]);
      $componentes = $stc->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // BLs por almacén (AreaProduccion='S')
    $almInt = (int)preg_replace('/\D+/', '', (string)($ot['cve_almac'] ?? '0'));
    if ($almInt <= 0) $almInt = (int)((string)($ot['cve_almac'] ?? '0'));

    $stb = $pdo->prepare("
      SELECT idy_ubica, CodigoCSD
      FROM c_ubicacion
      WHERE cve_almac = :alm
        AND AreaProduccion = 'S'
        AND (Activo = 1 OR Activo IS NULL)
      ORDER BY (CodigoCSD IS NULL), CodigoCSD ASC, idy_ubica ASC
    ");
    $stb->execute([':alm'=>$almInt]);
    $bls = $stb->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $blDefault = (int)($ot['idy_ubica_dest'] ?? 0);
    if ($blDefault <= 0 && count($bls)>0) $blDefault = (int)$bls[0]['idy_ubica'];

    // Stock inicial PT en BL
    $idProvOT = (int)($ot['ID_Proveedor'] ?? 0);
    $ptArt = (string)($ot['Cve_Articulo'] ?? '');
    if ($blDefault>0 && $ptArt!=='') {
      $ptPzasIni = sumExist($pdo,'ts_existenciapiezas',$almInt,$idProvOT,$blDefault,$ptArt);
      $ptTarIni  = sumExist($pdo,'ts_existenciatarima',$almInt,$idProvOT,$blDefault,$ptArt);
    }

    // Stock componentes total almacén
    foreach ($componentes as $c) {
      $a = (string)($c['Cve_Articulo'] ?? '');
      if ($a === '') continue;
      if (!isset($stockComp[$a])) {
        $stockComp[$a] = [
          'pzas' => sumExistAll($pdo,'ts_existenciapiezas',$almInt,$idProvOT,$a),
          'tar'  => sumExistAll($pdo,'ts_existenciatarima',$almInt,$idProvOT,$a),
        ];
      }
    }
  }
}

$statusLabel = function($st){
  $st = (string)$st;
  if ($st==='P') return 'Planeada';
  if ($st==='E') return 'En proceso';
  if ($st==='T') return 'Terminada';
  if ($st==='C') return 'Cancelada';
  return $st ?: '—';
};

?>
<style>
  .ap-card{ background:#fff; border-radius:14px; border:1px solid #dbe3f0; padding:16px; box-shadow:0 6px 18px rgba(16,24,40,.08); }
  .ap-title{ font-size:18px; font-weight:800; color:#0F5AAD; letter-spacing:.2px; }
  .ap-sub{ font-size:12px; color:#667085; }
  .kpi{ border:1px solid #e6edf7; border-radius:12px; padding:10px 12px; background:#fbfdff; }
  .kpi .n{ font-size:18px; font-weight:800; color:#111827; }
  .kpi .t{ font-size:11px; color:#667085; }
  table tbody td{ font-size:10px; white-space:nowrap; vertical-align:middle; }
  table thead th{ font-size:11px; }
  .pill{ display:inline-block; padding:.25rem .55rem; border-radius:999px; font-size:11px; font-weight:700; }
  .pill-warn{ background:#fff3cd; color:#7a5c00; border:1px solid #ffe69c; }
  .pill-info{ background:#cff4fc; color:#055160; border:1px solid #b6effb; }
  .pill-ok{ background:#d1e7dd; color:#0f5132; border:1px solid #badbcc; }
</style>

<div class="container-fluid mt-4">

<?php if ($id <= 0): ?>
  <div class="ap-card">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <div>
        <div class="ap-title"><i class="fa fa-industry me-1"></i> Iniciar Producción</div>
        <div class="ap-sub">Lista de OTs para arrancar. Default: <b>Planeadas (P)</b>. Selecciona una OT para ver su 3-capas y confirmar BL.</div>
      </div>
      <div class="text-end">
        <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
          <i class="fa fa-refresh"></i> Refrescar
        </button>
      </div>
    </div>

    <form class="row g-2 align-items-end mb-3" method="get">
      <div class="col-md-2">
        <label class="form-label mb-0">Status</label>
        <select name="status" class="form-select form-select-sm">
          <option value="P" <?= $status==='P'?'selected':'' ?>>Planeada (P)</option>
          <option value="E" <?= $status==='E'?'selected':'' ?>>En proceso (E)</option>
          <option value="T" <?= $status==='T'?'selected':'' ?>>Terminada (T)</option>
          <option value="C" <?= $status==='C'?'selected':'' ?>>Cancelada (C)</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label mb-0">Desde</label>
        <input type="date" name="desde" value="<?=h($desde)?>" class="form-control form-control-sm">
      </div>
      <div class="col-md-2">
        <label class="form-label mb-0">Hasta</label>
        <input type="date" name="hasta" value="<?=h($hasta)?>" class="form-control form-control-sm">
      </div>
      <div class="col-md-2">
        <label class="form-label mb-0">Almacén</label>
        <input type="text" name="cve_almac" value="<?=h($cveAlmac)?>" class="form-control form-control-sm" placeholder="Ej: 35">
      </div>
      <div class="col-md-2">
        <label class="form-label mb-0">Empresa (ID_Proveedor)</label>
        <input type="number" name="id_proveedor" value="<?=h((string)$idProv)?>" class="form-control form-control-sm" placeholder="Opcional">
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary btn-sm w-100"><i class="fa fa-filter"></i> Aplicar</button>
      </div>
    </form>

    <div class="row g-2 mb-3">
      <div class="col-md-3"><div class="kpi"><div class="n"><?=count($ots)?></div><div class="t">OTs en listado</div></div></div>
      <div class="col-md-3"><div class="kpi"><div class="n"><?=h($statusLabel($status))?></div><div class="t">Filtro Status</div></div></div>
      <div class="col-md-3"><div class="kpi"><div class="n"><?=h($desde)?></div><div class="t">Desde</div></div></div>
      <div class="col-md-3"><div class="kpi"><div class="n"><?=h($hasta)?></div><div class="t">Hasta</div></div></div>
    </div>

    <div class="table-responsive">
      <table id="tblOT" class="table table-sm table-striped align-middle">
        <thead>
          <tr>
            <th style="width:60px;">Acción</th>
            <th>Folio</th>
            <th>Empresa</th>
            <th>Producto</th>
            <th class="text-end">Cant</th>
            <th>Almacén</th>
            <th>Status</th>
            <th>Fecha</th>
            <th>Referencia (BOM)</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$ots): ?>
            <tr><td colspan="9" class="text-center text-muted">Sin resultados.</td></tr>
          <?php else: ?>
            <?php foreach($ots as $r):
              $st = (string)($r['Status'] ?? '');
              $pill = $st==='P'?'pill-warn':($st==='E'?'pill-info':'pill-ok');
            ?>
              <tr>
                <td>
                  <a class="btn btn-outline-primary btn-sm" href="iniciar_produccion.php?id=<?=h((string)$r['id'])?>">
                    <i class="fa fa-play"></i>
                  </a>
                </td>
                <td class="fw-semibold"><?=h((string)$r['Folio_Pro'])?></td>
                <td><?=h((string)$r['EmpresaNombre'])?></td>
                <td><?=h((string)$r['Cve_Articulo'])?></td>
                <td class="text-end"><?=f2((float)$r['Cantidad'])?></td>
                <td><?=h((string)$r['cve_almac'])?></td>
                <td><span class="pill <?=$pill?>"><?=h($statusLabel($st))?></span></td>
                <td><?=h((string)$r['FechaReg'])?></td>
                <td><?=h((string)$r['Referencia'])?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="ap-sub mt-2">
      * Para demo “impacto BD”: arranca OTs Planeadas, define BL Producción (por almacén) y deja MP en negativo si aplica.
    </div>
  </div>

<?php else: ?>
  <?php if (!$ot): ?>
    <div class="alert alert-danger">OT no encontrada.</div>
  <?php else:
    $st = (string)($ot['Status'] ?? '');
    $pill = $st==='P'?'pill-warn':($st==='E'?'pill-info':'pill-ok');

    $cantOT = (float)($ot['Cantidad'] ?? 0);
    $ptIniTotal = $ptPzasIni + $ptTarIni;
    $ptFinTeo = $ptIniTotal + $cantOT;

    $usr = (string)($ot['Usr_Armo'] ?? 'DEMO');
    if ($usr === '') $usr = 'DEMO';
  ?>
  <div class="ap-card mx-auto" style="max-width:1200px">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <div>
        <div class="ap-title"><i class="fa fa-play-circle me-1"></i> Arranque de Producción (3 capas)</div>
        <div class="ap-sub">Capa 1: Orden · Capa 2: Componentes (BOM) · Capa 3: Stock inicial/proyección. Sin consumo todavía.</div>
      </div>
      <div class="text-end">
        <div class="ap-sub">Usuario</div>
        <div class="fw-bold"><?=h($usr)?></div>
      </div>
    </div>

    <div class="alert alert-info" style="font-size:12px;">
      <b>Demo KPI Velocidad:</b> al iniciar se registra <b>Hora_Ini</b>. Posteriormente, al cerrar se registrará <b>Hora_Fin</b> y el “cronómetro” será el tiempo de BD.
      MP puede quedar negativa para evidenciar carga/impacto real.
    </div>

    <!-- CAPA 1: OT -->
    <div class="row g-3 mb-3">
      <div class="col-md-3">
        <div class="ap-sub">Folio</div>
        <div class="fw-bold"><?=h((string)$ot['Folio_Pro'])?></div>
      </div>
      <div class="col-md-3">
        <div class="ap-sub">Empresa</div>
        <div class="fw-bold"><?=h((string)$ot['EmpresaNombre'])?></div>
      </div>
      <div class="col-md-2">
        <div class="ap-sub">Almacén</div>
        <div class="fw-bold"><?=h((string)$ot['cve_almac'])?></div>
      </div>
      <div class="col-md-2">
        <div class="ap-sub">Status</div>
        <div><span class="pill <?=$pill?>"><?=h($statusLabel($st))?></span></div>
      </div>
      <div class="col-md-2">
        <div class="ap-sub">Fecha</div>
        <div class="fw-bold"><?=h((string)$ot['FechaReg'])?></div>
      </div>

      <div class="col-md-4">
        <div class="ap-sub">Producto</div>
        <div class="fw-bold"><?=h((string)$ot['Cve_Articulo'])?></div>
      </div>
      <div class="col-md-2">
        <div class="ap-sub">Cantidad Solicitada</div>
        <div class="fw-bold"><?=f2($cantOT)?></div>
      </div>
      <div class="col-md-3">
        <div class="ap-sub">Referencia (BOM)</div>
        <div class="fw-bold"><?=h((string)$ot['Referencia'])?></div>
      </div>
      <div class="col-md-3">
        <div class="ap-sub">Hora Inicio</div>
        <div class="fw-bold"><?=h((string)($ot['Hora_Ini'] ?? '—'))?></div>
      </div>
    </div>

    <!-- BL Producción -->
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label mb-0">BL Producción (por almacén, AreaProduccion='S')</label>
        <select id="selBL" class="form-select form-select-sm" <?= $st!=='P'?'disabled':'' ?>>
          <?php if (!$bls): ?>
            <option value="0">No hay BLs de Producción (AreaProduccion='S') para este almacén</option>
          <?php else: foreach($bls as $b):
            $bid = (int)$b['idy_ubica'];
            $txt = trim((string)($b['CodigoCSD'] ?? ''));
            if ($txt === '') $txt = 'UBI#'.$bid;
          ?>
            <option value="<?=h((string)$bid)?>" <?= $bid===$blDefault?'selected':'' ?>>
              <?=h($txt)?>
            </option>
          <?php endforeach; endif; ?>
        </select>
        <div class="ap-sub mt-1">Se muestra <b>CodigoCSD</b> (BL). Cada almacén puede tener múltiples BLs de producción.</div>
      </div>
      <div class="col-md-6">
        <div class="ap-sub">Producto Terminado — Stock inicial en BL seleccionado</div>
        <div class="fw-bold">
          Total: <?=f2($ptIniTotal)?> (Pzas: <?=f2($ptPzasIni)?> | Tarima: <?=f2($ptTarIni)?>)
          &nbsp; → Final teórico: <span class="text-primary"><?=f2($ptFinTeo)?></span>
        </div>
      </div>
    </div>

    <!-- CAPA 2: Componentes -->
    <div class="ap-sub mb-1"><b>Capa 2 — Componentes (BOM)</b></div>
    <div class="table-responsive mb-3">
      <table class="table table-sm table-striped align-middle">
        <thead>
          <tr>
            <th style="width:40px;">#</th>
            <th>Componente</th>
            <th class="text-end">Req</th>
            <th class="text-end">Stock Pzas</th>
            <th class="text-end">Proy Pzas</th>
            <th class="text-end">Stock Tarima</th>
            <th class="text-end">Proy Tarima</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$componentes): ?>
            <tr><td colspan="7" class="text-center text-muted">Sin componentes BOM para la Referencia indicada.</td></tr>
          <?php else:
            $i=1;
            foreach($componentes as $c):
              $a = (string)($c['Cve_Articulo'] ?? '');
              $req = (float)($c['Cantidad'] ?? 0);
              $sp = (float)($stockComp[$a]['pzas'] ?? 0);
              $stt= (float)($stockComp[$a]['tar']  ?? 0);
              // Demo: proyección consumo total
              $projP = $sp - $req;
              $projT = $stt - $req;
          ?>
            <tr>
              <td><?= $i++ ?></td>
              <td class="fw-semibold"><?=h($a)?></td>
              <td class="text-end"><?=f2($req)?></td>
              <td class="text-end"><?=f2($sp)?></td>
              <td class="text-end <?= $projP<0?'text-danger fw-bold':'text-success fw-bold' ?>"><?=f2($projP)?></td>
              <td class="text-end"><?=f2($stt)?></td>
              <td class="text-end <?= $projT<0?'text-danger fw-bold':'text-success fw-bold' ?>"><?=f2($projT)?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- CAPA 3: Stock / Reglas -->
    <div class="ap-sub">
      <b>Capa 3 — Inventarios:</b> se consulta <b>ts_existenciapiezas</b> y <b>ts_existenciatarima</b>. Negativos permitidos para demo de “impacto BD”.
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
      <a href="iniciar_produccion.php" class="btn btn-outline-secondary btn-sm">
        <i class="fa fa-arrow-left"></i> Regresar a listado
      </a>

      <button id="btnIniciar" class="btn btn-primary btn-sm"
        <?= ($st!=='P' || !$componentes || !$bls || $blDefault<=0) ? 'disabled' : '' ?>>
        <i class="fa fa-play"></i> Confirmar e Iniciar
      </button>
    </div>
  </div>

  <script>
  (function(){
    const btn = document.getElementById('btnIniciar');
    const sel = document.getElementById('selBL');
    if(!btn) return;

    btn.addEventListener('click', function(){
      const id = <?= (int)$ot['id'] ?>;
      const bl = sel ? (sel.value || '0') : '0';

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
        body: 'action=start&id=' + encodeURIComponent(id) + '&idy_ubica_dest=' + encodeURIComponent(bl)
      })
      .then(r => r.json())
      .then(resp => {
        if(resp && resp.ok){
          // Back to list for demo
          window.location.href = 'iniciar_produccion.php?status=E';
        }else{
          alert((resp && resp.error) ? resp.error : 'Error al iniciar');
          btn.disabled = false;
          btn.innerHTML = '<i class="fa fa-play"></i> Confirmar e Iniciar';
        }
      })
      .catch(err => {
        alert('Error de comunicación: ' + (err && err.message ? err.message : err));
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-play"></i> Confirmar e Iniciar';
      });
    });
  })();
  </script>
  <?php endif; ?>
<?php endif; ?>

</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
