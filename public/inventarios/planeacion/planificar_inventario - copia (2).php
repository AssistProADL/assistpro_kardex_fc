<?php
// public/inventarios/planeacion/planificar_inventario.php
require_once __DIR__ . '/../../../app/db.php';
include __DIR__ . '/../../bi/_menu_global.php';

$pdo = db_pdo();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function s($v){ $v = trim((string)$v); return $v==='' ? null : $v; }
function today(){ return date('Y-m-d'); }
function now_iso(){ return date('Y-m-d H:i:s'); }
function ymd_compact(){ return date('Ymd'); }

function table_exists(PDO $pdo, $table){
  $st = $pdo->prepare("
    SELECT 1
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = :t
    LIMIT 1
  ");
  $st->execute([':t'=>$table]);
  return (bool)$st->fetchColumn();
}

function column_exists(PDO $pdo, $table, $col){
  $st = $pdo->prepare("
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = :t
      AND column_name = :c
    LIMIT 1
  ");
  $st->execute([':t'=>$table, ':c'=>$col]);
  return (bool)$st->fetchColumn();
}

function ensure_col(PDO $pdo, $table, $colDefSql){
  // $colDefSql ejemplo: "ADD COLUMN cve_cia INT NULL AFTER folio"
  $pdo->exec("ALTER TABLE {$table} {$colDefSql}");
}

// =============================
// Asegurar columna fecha_programada en inventario (evita error 1364)
// =============================
if(table_exists($pdo, 'inventario') && !column_exists($pdo, 'inventario', 'fecha_programada')){
  // DATE o DATETIME según tu modelo; aquí DATE por ser "programada"
  $pdo->exec("ALTER TABLE inventario ADD COLUMN fecha_programada DATE NULL AFTER fecha_creacion");
}

// =============================
// Asegurar tabla de planeación (alineada a tu modelo real)
// - Empresa: cve_cia (como inventario)
// - Almacén principal: cve_almacenp (como inventario)
// - Usuario: id_usuario (como inventario_asignacion / usuarios internos)
// =============================
if(!table_exists($pdo, 'th_plan_inventarios')){
  $pdo->exec("
    CREATE TABLE th_plan_inventarios (
      id_plan        INT AUTO_INCREMENT PRIMARY KEY,
      id_inventario  INT NULL,
      folio          VARCHAR(30) NOT NULL,

      cve_cia        INT NULL,
      cve_almacenp   INT NULL,
      cve_almac      INT NULL,

      pasillo        VARCHAR(20) NULL,
      rack           VARCHAR(20) NULL,
      nivel          VARCHAR(20) NULL,

      tipo_inventario ENUM('CICLICO','FISICO') NOT NULL DEFAULT 'FISICO',
      producto_filtro VARCHAR(80) NULL,

      id_usuario     INT NULL,

      bls_json       LONGTEXT NOT NULL,

      created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at     DATETIME NULL,

      KEY ix_folio (folio),
      KEY ix_inv (id_inventario),
      KEY ix_cia (cve_cia),
      KEY ix_alm (cve_almacenp),
      KEY ix_zona (cve_almac)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ");
}else{
  // Tabla existe: garantizamos columnas mínimas sin depender de nombres viejos (compania_id, almacenp_id, etc.)
  if(!column_exists($pdo,'th_plan_inventarios','id_inventario')){
    ensure_col($pdo,'th_plan_inventarios',"ADD COLUMN id_inventario INT NULL AFTER id_plan");
  }
  if(!column_exists($pdo,'th_plan_inventarios','folio')){
    ensure_col($pdo,'th_plan_inventarios',"ADD COLUMN folio VARCHAR(30) NOT NULL DEFAULT '' AFTER id_inventario");
  }
  if(!column_exists($pdo,'th_plan_inventarios','cve_cia')){
    ensure_col($pdo,'th_plan_inventarios',"ADD COLUMN cve_cia INT NULL AFTER folio");
  }
  if(!column_exists($pdo,'th_plan_inventarios','cve_almacenp')){
    ensure_col($pdo,'th_plan_inventarios',"ADD COLUMN cve_almacenp INT NULL AFTER cve_cia");
  }
  if(!column_exists($pdo,'th_plan_inventarios','cve_almac')){
    ensure_col($pdo,'th_plan_inventarios',"ADD COLUMN cve_almac INT NULL AFTER cve_almacenp");
  }
  if(!column_exists($pdo,'th_plan_inventarios','pasillo')){
    ensure_col($pdo,'th_plan_inventarios',"ADD COLUMN pasillo VARCHAR(20) NULL AFTER cve_almac");
  }
  if(!column_exists($pdo,'th_plan_inventarios','rack')){
    ensure_col($pdo,'th_plan_inventarios',"ADD COLUMN rack VARCHAR(20) NULL AFTER pasillo");
  }
  if(!column_exists($pdo,'th_plan_inventarios','nivel')){
    ensure_col($pdo,'th_plan_inventarios',"ADD COLUMN nivel VARCHAR(20) NULL AFTER rack");
  }
  if(!column_exists($pdo,'th_plan_inventarios','tipo_inventario')){
    ensure_col($pdo,'th_plan_inventarios',"ADD COLUMN tipo_inventario ENUM('CICLICO','FISICO') NOT NULL DEFAULT 'FISICO' AFTER nivel");
  }
  if(!column_exists($pdo,'th_plan_inventarios','producto_filtro')){
    ensure_col($pdo,'th_plan_inventarios',"ADD COLUMN producto_filtro VARCHAR(80) NULL AFTER tipo_inventario");
  }
  if(!column_exists($pdo,'th_plan_inventarios','id_usuario')){
    ensure_col($pdo,'th_plan_inventarios',"ADD COLUMN id_usuario INT NULL AFTER producto_filtro");
  }
  if(!column_exists($pdo,'th_plan_inventarios','bls_json')){
    ensure_col($pdo,'th_plan_inventarios',"ADD COLUMN bls_json LONGTEXT NOT NULL AFTER id_usuario");
  }
  if(!column_exists($pdo,'th_plan_inventarios','created_at')){
    ensure_col($pdo,'th_plan_inventarios',"ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER bls_json");
  }
  if(!column_exists($pdo,'th_plan_inventarios','updated_at')){
    ensure_col($pdo,'th_plan_inventarios',"ADD COLUMN updated_at DATETIME NULL AFTER created_at");
  }
}

// =============================
// Catálogo almacenes
// =============================
$almacenesP = [];
try { $almacenesP = db_all("SELECT id, clave, nombre FROM c_almacenp ORDER BY nombre"); }
catch(Throwable $e){ $almacenesP = []; }

// =============================
// Guardado
// =============================
$ok=0; $error=''; $folio_creado=''; $id_inventario_creado=0;

if($_SERVER['REQUEST_METHOD']==='POST'){

  $tipo            = s($_POST['tipo'] ?? 'FISICO');
  $cve_cia         = s($_POST['cve_cia'] ?? null);
  $cve_almacenp    = s($_POST['cve_almacenp'] ?? null);
  $cve_almac       = s($_POST['cve_almac'] ?? null);
  $pasillo         = s($_POST['pasillo'] ?? null);
  $rack            = s($_POST['rack'] ?? null);
  $nivel           = s($_POST['nivel'] ?? null);
  $producto_filtro = s($_POST['producto_filtro'] ?? null);
  $observaciones   = s($_POST['observaciones'] ?? null);

  // fecha programada del plan (obligatoria si la columna es NOT NULL en tu BD)
  $fecha_programada = s($_POST['fecha_programada'] ?? null);

  // ✅ ahora usuario es id_user (INT)
  $id_usuario      = s($_POST['id_usuario'] ?? null);

  $bls_json = s($_POST['bls_json'] ?? null);
  if($bls_json){
    json_decode($bls_json, true);
    if(json_last_error() !== JSON_ERROR_NONE) $bls_json = null;
  }

  if(!$tipo || !in_array($tipo, ['FISICO','CICLICO'], true)) $error="Tipo inválido.";
  elseif(!$cve_almacenp) $error="Almacén principal requerido.";
  elseif(!$id_usuario) $error="Usuario requerido.";
  elseif(!$fecha_programada) $error="Fecha programada requerida.";
  elseif(!$bls_json) $error="Debes seleccionar al menos un BL.";
  elseif($tipo==='CICLICO' && !$producto_filtro) $error="En CÍCLICO debes capturar Producto (filtro).";

  if($error===''){
    try{
      $pdo->beginTransaction();

      // 1) Folio controlado
      $fecha_key = today();
      $st = $pdo->prepare("SELECT consecutivo FROM inventario_folios_control WHERE fecha=:f AND tipo_inventario=:t FOR UPDATE");
      $st->execute([':f'=>$fecha_key, ':t'=>$tipo]);
      $row = $st->fetch(PDO::FETCH_ASSOC);

      $consec = $row ? (int)$row['consecutivo'] : 0;
      if(!$row){
        $stI = $pdo->prepare("INSERT INTO inventario_folios_control (fecha, tipo_inventario, consecutivo) VALUES (:f,:t,0)");
        $stI->execute([':f'=>$fecha_key, ':t'=>$tipo]);
      }

      $consec++;
      $stU = $pdo->prepare("UPDATE inventario_folios_control SET consecutivo=:c WHERE fecha=:f AND tipo_inventario=:t");
      $stU->execute([':c'=>$consec, ':f'=>$fecha_key, ':t'=>$tipo]);

      $prefix = ($tipo==='FISICO') ? 'INVF-' : 'INVC-';
      $folio  = $prefix . ymd_compact() . '-' . $consec;

      // 2) Insert inventario (encabezado real)
      $stInv = $pdo->prepare("
        INSERT INTO inventario
          (folio, tipo_inventario, estado, cve_cia, cve_almacenp, fecha_creacion, fecha_programada, usuario_creo, observaciones)
        VALUES
          (:folio, :tipo, 'CREADO', :cia, :alm, :fc, :fp, :usr, :obs)
      ");
      $stInv->execute([
        ':folio'=>$folio,
        ':tipo'=>$tipo,
        ':cia'=>($cve_cia===null? null : (int)$cve_cia),
        ':alm'=>(int)$cve_almacenp,
        ':fc'=>now_iso(),
        ':fp'=>$fecha_programada,
        ':usr'=>(int)$id_usuario,
        ':obs'=>$observaciones,
      ]);
      $id_inventario = (int)$pdo->lastInsertId();

      // 3) Insert planeación (alcance BLs + filtros) - con nombres alineados a tu BD
      $stPlan = $pdo->prepare("
        INSERT INTO th_plan_inventarios
          (id_inventario, folio, cve_cia, cve_almacenp, cve_almac,
           pasillo, rack, nivel, tipo_inventario, producto_filtro, id_usuario, bls_json, created_at)
        VALUES
          (:id_inv, :folio, :cia, :alm, :zona,
           :pasillo, :rack, :nivel, :tipo, :prod, :usr, :bls, :ca)
      ");
      $stPlan->execute([
        ':id_inv'=>$id_inventario,
        ':folio'=>$folio,
        ':cia'=>($cve_cia===null? null : (int)$cve_cia),
        ':alm'=>(int)$cve_almacenp,
        ':zona'=>($cve_almac===null? null : (int)$cve_almac),
        ':pasillo'=>$pasillo,
        ':rack'=>$rack,
        ':nivel'=>$nivel,
        ':tipo'=>$tipo,
        ':prod'=>$producto_filtro,
        ':usr'=>(int)$id_usuario,
        ':bls'=>$bls_json,
        ':ca'=>now_iso(),
      ]);

      $pdo->commit();

      $ok=1;
      $folio_creado=$folio;
      $id_inventario_creado=$id_inventario;

    } catch(Throwable $e){
      if($pdo->inTransaction()) $pdo->rollBack();
      $error = "Error creando plan: ".$e->getMessage();
    }
  }
}

// APIs en /public/api/
$api_empresas = "../../api/empresas_api.php";
$api_usuarios = "../../api/usuarios.php";
$api_zonas    = "../../api/zonas_api.php";
$api_ubic     = "../../api/ubicaciones_por_almacen.php";

$today = today();

// =============================
// KPIs ejecutivos (últimos 7 días) — solo lectura, no impacta operación
// =============================
$kpi = [
  'TOTAL'=>0,
  'CREADO'=>0,
  'PLANEADO'=>0,
  'EN_CONTEO'=>0,
  'CERRADO'=>0,
];
$kpi_daily = []; // ['Y-m-d'=>count]
$kpi_from = date('Y-m-d', strtotime('-6 days'));
$kpi_to   = today();

try{
  if(table_exists($pdo,'inventario') && column_exists($pdo,'inventario','fecha_creacion')){
    $st = $pdo->prepare("
      SELECT DATE(fecha_creacion) AS d, estado, COUNT(*) AS c
        FROM inventario
       WHERE fecha_creacion >= :d1
         AND fecha_creacion <  DATE_ADD(:d2, INTERVAL 1 DAY)
       GROUP BY DATE(fecha_creacion), estado
       ORDER BY d ASC
    ");
    $st->execute([':d1'=>$kpi_from, ':d2'=>$kpi_to]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    // inicializa días
    $dt = new DateTime($kpi_from);
    $end = new DateTime($kpi_to);
    while($dt <= $end){
      $kpi_daily[$dt->format('Y-m-d')] = 0;
      $dt->modify('+1 day');
    }

    foreach($rows as $r){
      $d = $r['d'];
      $estado = strtoupper((string)$r['estado']);
      $c = (int)$r['c'];
      $kpi['TOTAL'] += $c;
      if(isset($kpi[$estado])) $kpi[$estado] += $c;
      if(isset($kpi_daily[$d])) $kpi_daily[$d] += $c;
    }
  }
}catch(Throwable $e){
  // KPIs no deben bloquear la operación
}

$kpi_labels = array_keys($kpi_daily);
$kpi_series = array_values($kpi_daily);

?>

<style>
  :root{ --ap-blue:#0b3a6e; --ap-blue2:#145aa8; --ap-soft:#f4f8ff; --ap-border:#d7e8ff; }
  .ap-wrap{ padding:14px 18px; font-size:10px; }
  .ap-title{ font-weight:900; font-size:16px; color:var(--ap-blue); margin:4px 0 8px; }
  .ap-sub{ color:#5b6b7a; margin-bottom:10px; }
  .ap-card{ border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,.06); border:1px solid #eef2f7; }
  .ap-label{ font-weight:800; font-size:10px; color:#2c3e50; }
  .ap-btn{ font-weight:800; }
  .ap-note{ font-size:10px; color:#667; }
  .ap-table td,.ap-table th{ font-size:10px; white-space:nowrap; }
  .ap-pill{ display:inline-flex; align-items:center; gap:6px; padding:2px 10px; border-radius:999px; border:1px solid var(--ap-border); background:var(--ap-soft); color:var(--ap-blue); font-weight:800; }
  .ap-kpi{ background:linear-gradient(180deg,#f8fbff,#fff); border:1px solid var(--ap-border); border-radius:14px; padding:10px 12px; min-height:56px; }
  .ap-kpi .k{ font-weight:900; color:var(--ap-blue); font-size:12px; }
  .ap-kpi .v{ font-weight:900; color:#111; font-size:12px; }
  .ap-kpi .s{ color:#6b7c8f; font-size:10px; }
  .ap-divider{ height:1px; background:#eef2f7; margin:8px 0; }
  .btn-ap{ background:var(--ap-blue2); border-color:var(--ap-blue2); color:#fff; }
  .btn-ap:hover{ background:var(--ap-blue); border-color:var(--ap-blue); }
</style>

<div class="ap-wrap">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:10px;">
    <div>
      <div class="ap-title">Planeación de Inventarios · Crear Plan</div>
      <div class="ap-sub">Genera folio real (inventario) y guarda alcance por BL. El teórico se construye en el detalle.</div>
    </div>
    <div class="ap-pill">AssistPro · Inventarios</div>
  </div>

  
  <!-- KPIs Ejecutivos (últimos 7 días) -->
  <div class="row g-2 mb-3">
    <div class="col-12 col-md-3">
      <div class="ap-kpi">
        <div class="k">Inventarios (7 días)</div>
        <div class="v"><?= (int)($kpi['TOTAL'] ?? 0) ?></div>
        <div class="s">Creación total en periodo</div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="ap-kpi">
        <div class="k">Creados</div>
        <div class="v"><?= (int)($kpi['CREADO'] ?? 0) ?></div>
        <div class="s">Listos para asignación</div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="ap-kpi">
        <div class="k">En conteo</div>
        <div class="v"><?= (int)($kpi['EN_CONTEO'] ?? 0) ?></div>
        <div class="s">Operación activa</div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="ap-kpi">
        <div class="k">Cerrados</div>
        <div class="v"><?= (int)($kpi['CERRADO'] ?? 0) ?></div>
        <div class="s">Completados</div>
      </div>
    </div>

    <div class="col-12">
      <div class="card ap-card">
        <div class="card-header" style="font-weight:900;color:var(--ap-blue); display:flex; justify-content:space-between; flex-wrap:wrap; gap:10px;">
          <span>Volumen de inventarios por día (última semana)</span>
          <span class="ap-note">Rango: <?=h($kpi_from)?> a <?=h($kpi_to)?></span>
        </div>
        <div class="card-body" style="height:170px;">
          <canvas id="chartInv7d" height="120"></canvas>
        </div>
      </div>
    </div>
  </div>


  <?php if($error): ?>
    <div class="alert alert-danger ap-card" role="alert" style="border-left:6px solid #dc3545;">
      <strong>Atención:</strong> <?=h($error)?>
    </div>
  <?php endif; ?>

  <?php if($ok): ?>
    <div class="alert alert-success ap-card" role="alert" style="border-left:6px solid #198754;">
      <div class="d-flex align-items-center justify-content-between flex-wrap">
        <div>
          <div style="font-weight:900;font-size:12px;">Inventario creado correctamente</div>
          <div class="ap-note">Folio: <strong><?=h($folio_creado)?></strong> · ID Inventario: <strong><?=h($id_inventario_creado)?></strong></div>
        </div>
        <div class="mt-2 mt-md-0">
          <a class="btn btn-ap ap-btn btn-sm" href="plan_inventario_detalle.php?folio=<?=urlencode($folio_creado)?>">Continuar a Detalle / Teórico</a>
          <a class="btn btn-outline-secondary ap-btn btn-sm" href="planificar_inventario.php">Nuevo</a>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="card ap-card">
    <div class="card-header" style="font-weight:900;color:var(--ap-blue);">Datos del Plan</div>
    <div class="card-body">

      <form method="post" id="frmPlan" autocomplete="off">
        <input type="hidden" name="bls_json" id="bls_json" value="">
        <input type="hidden" name="pasillo" id="pasillo" value="">
        <input type="hidden" name="rack" id="rack" value="">
        <input type="hidden" name="nivel" id="nivel" value="">

        <div class="row g-2 mb-2">
          <div class="col-12 col-md-3"><div class="ap-kpi"><div class="k">Almacén</div><div class="v" id="kpiAlmacen">—</div><div class="s">c_almacenp seleccionado</div></div></div>
          <div class="col-12 col-md-3"><div class="ap-kpi"><div class="k">Zona</div><div class="v" id="kpiZona">—</div><div class="s">Dependiente del almacén</div></div></div>
          <div class="col-12 col-md-3"><div class="ap-kpi"><div class="k">Visibles / Totales</div><div class="v"><span id="kpiVisibles">0</span> / <span id="kpiTotales">0</span></div><div class="s">BLs filtrados vs cargados</div></div></div>
          <div class="col-12 col-md-3"><div class="ap-kpi"><div class="k">Alcance</div><div class="v" id="kpiAlcance">—</div><div class="s">ZONA COMPLETA o FILTRADO</div></div></div>
          <div class="col-12 col-md-3"><div class="ap-kpi"><div class="k">Filtros</div><div class="v" id="kpiFiltros">Sin filtros</div><div class="s">Pasillo/Rack/Nivel</div></div></div>
          <div class="col-12 col-md-3"><div class="ap-kpi"><div class="k">BLs seleccionados</div><div class="v"><span id="selCount">0</span></div><div class="s">Se ajusta a filtros</div></div></div>
        </div>

        <div class="ap-divider"></div>

        <div class="row g-2">
          <div class="col-md-3">
            <label class="ap-label">Tipo</label>
            <select name="tipo" id="tipo" class="form-select form-select-sm">
              <option value="FISICO" selected>FÍSICO</option>
              <option value="CICLICO">CÍCLICO</option>
            </select>
            <div class="ap-note">Default: FÍSICO. CÍCLICO se acota por producto.</div>
          </div>

          <div class="col-md-3">
            <label class="ap-label">Fecha Programada</label>
            <input type="date" name="fecha_programada" class="form-control form-control-sm" value="<?=h($today)?>">
          </div>

          <div class="col-md-3">
            <label class="ap-label">Usuario</label>
            <!-- ✅ value = id_user -->
            <select name="id_usuario" id="id_usuario" class="form-select form-select-sm" required>
              <option value="">Cargando...</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="ap-label">Compañía</label>
            <select name="cve_cia" id="cve_cia" class="form-select form-select-sm">
              <option value="">Cargando...</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="ap-label">Almacén Principal (c_almacenp)</label>
            <select name="cve_almacenp" id="cve_almacenp" class="form-select form-select-sm" required>
              <option value="">-- Selecciona --</option>
              <?php foreach($almacenesP as $ap): ?>
                <option value="<?=h($ap['id'])?>"><?=h(($ap['clave'] ? $ap['clave'].' · ' : '').$ap['nombre'])?></option>
              <?php endforeach; ?>
            </select>
            <div class="ap-note">Gobierna zonas y BLs.</div>
          </div>

          <div class="col-md-3">
            <label class="ap-label">Zona</label>
            <select name="cve_almac" id="cve_almac" class="form-select form-select-sm">
              <option value="">-- Todas --</option>
            </select>
          </div>

          <div class="col-md-3" id="wrapProducto" style="display:none;">
            <label class="ap-label">Producto (solo CÍCLICO)</label>
            <input type="text" name="producto_filtro" id="producto_filtro" class="form-control form-control-sm" placeholder="Clave / SKU / texto">
          </div>

          <div class="col-md-12">
            <label class="ap-label">Observaciones</label>
            <input type="text" name="observaciones" class="form-control form-control-sm" maxlength="255"
                   placeholder="Alcance, reglas, notas operativas...">
          </div>

          <div class="col-md-12 d-flex gap-2 justify-content-end mt-2">
            <a href="../" class="btn btn-outline-secondary btn-sm ap-btn">Regresar</a>
            <button type="submit" class="btn btn-ap btn-sm ap-btn">Generar Folio y Crear Plan</button>
          </div>
        </div>

        <div class="card ap-card mt-3" id="cardBL" style="display:none;">
          <div class="card-header" style="font-weight:900;color:var(--ap-blue);">
            Selección de BLs (Bin Locations) <span class="ap-pill">alcance real</span>
          </div>

          <div class="card-body">
            <div class="row g-2 align-items-end mb-2">
              <div class="col-md-4">
                <label class="ap-label">Buscar</label>
                <input type="text" id="blSearch" class="form-control form-control-sm" placeholder="BL / Sección / Pasillo / Rack / Nivel">
              </div>

              <div class="col-md-2">
                <label class="ap-label">Pasillo</label>
                <select id="f_pasillo" class="form-select form-select-sm"><option value="">-- Todos --</option></select>
              </div>

              <div class="col-md-2">
                <label class="ap-label">Rack</label>
                <select id="f_rack" class="form-select form-select-sm"><option value="">-- Todos --</option></select>
              </div>

              <div class="col-md-2">
                <label class="ap-label">Nivel</label>
                <select id="f_nivel" class="form-select form-select-sm"><option value="">-- Todos --</option></select>
              </div>

              <div class="col-md-2">
                <button type="button" class="btn btn-outline-secondary btn-sm ap-btn w-100 mb-1" id="btnSelectAllView">Seleccionar todos (vista)</button>
                <button type="button" class="btn btn-outline-secondary btn-sm ap-btn w-100 mb-1" id="btnSelectAllAll">Seleccionar todos (almacén/zona)</button>
                <button type="button" class="btn btn-outline-secondary btn-sm ap-btn w-100" id="btnClearAll">Limpiar selección</button>
              </div>
            </div>

            <div class="table-responsive" style="max-height:380px; overflow:auto;">
              <table class="table table-sm table-hover ap-table">
                <thead class="table-light" style="position:sticky; top:0; z-index:3;">
                  <tr>
                    <th style="width:34px;"><input type="checkbox" id="chkAllVisible" class="form-check-input" title="Seleccionar visibles"></th>
                    <th>BL</th><th>Sección</th><th>Pasillo</th><th>Rack</th><th>Nivel</th><th>Posición</th>
                  </tr>
                </thead>
                <tbody id="tbodyBL">
                  <tr><td colspan="7" class="text-muted">Selecciona un almacén para cargar BLs...</td></tr>
                </tbody>
              </table>
            </div>

          </div>
        </div>

      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const API_EMPRESAS = <?= json_encode($api_empresas, JSON_UNESCAPED_SLASHES); ?>;
const API_USUARIOS = <?= json_encode($api_usuarios, JSON_UNESCAPED_SLASHES); ?>;
const API_ZONAS    = <?= json_encode($api_zonas, JSON_UNESCAPED_SLASHES); ?>;
const API_UBIC     = <?= json_encode($api_ubic, JSON_UNESCAPED_SLASHES); ?>;


const KPI_LABELS = <?= json_encode($kpi_labels, JSON_UNESCAPED_SLASHES); ?>;
const KPI_SERIES = <?= json_encode($kpi_series, JSON_UNESCAPED_SLASHES); ?>;

(function initChart7d(){
  const el = document.getElementById('chartInv7d');
  if(!el || typeof Chart === 'undefined') return;
  new Chart(el, {
    type: 'line',
    data: {
      labels: KPI_LABELS,
      datasets: [{
        label: 'Inventarios',
        data: KPI_SERIES,
        tension: 0.25,
        fill: true,
        pointRadius: 2
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display:false } },
        y: { beginAtZero: true, ticks: { precision: 0 } }
      }
    }
  });
})();


let BL_DATA = [];
let BL_VIEW = [];
let BL_SELECTED = new Set();

function uniq(arr){
  return Array.from(new Set(arr.filter(x => x !== null && x !== undefined && String(x).trim() !== '')))
              .sort((a,b)=>String(a).localeCompare(String(b)));
}
function fillSelect(selId, values, ph){
  const sel = document.getElementById(selId);
  sel.innerHTML = `<option value="">${ph}</option>`;
  values.forEach(v=>{
    const opt = document.createElement('option');
    opt.value = v;
    opt.textContent = v;
    sel.appendChild(opt);
  });
}
function updateKpis(){
  const apSel = document.getElementById('cve_almacenp');
  const apTxt = apSel && apSel.selectedIndex>0 ? apSel.options[apSel.selectedIndex].text : '—';
  document.getElementById('kpiAlmacen').textContent = apTxt || '—';

  const zSel = document.getElementById('cve_almac');
  const zTxt = zSel && zSel.value ? zSel.options[zSel.selectedIndex].text : 'Todas';
  document.getElementById('kpiZona').textContent = zTxt || '—';

  const fp = document.getElementById('f_pasillo')?.value || '';
  const fr = document.getElementById('f_rack')?.value || '';
  const fn = document.getElementById('f_nivel')?.value || '';
  const parts = [];
  if(fp) parts.push(`P:${fp}`);
  if(fr) parts.push(`R:${fr}`);
  if(fn) parts.push(`N:${fn}`);
  document.getElementById('kpiFiltros').textContent = parts.length ? parts.join(' · ') : 'Sin filtros';

  document.getElementById('kpiVisibles').textContent = String((BL_VIEW||[]).length);
  document.getElementById('kpiTotales').textContent  = String((BL_DATA||[]).length);

  const hayFiltros = !!(fp || fr || fn || (document.getElementById('blSearch')?.value || '').trim());
  document.getElementById('kpiAlcance').textContent = hayFiltros ? 'FILTRADO' : 'ZONA COMPLETA';
}
function setSelCount(){
  document.getElementById('selCount').textContent = String(BL_SELECTED.size);
  document.getElementById('bls_json').value = JSON.stringify(Array.from(BL_SELECTED));
  updateKpis();
}
function renderBL(rows){
  const tb = document.getElementById('tbodyBL');
  tb.innerHTML = '';
  if(!rows.length){
    tb.innerHTML = `<tr><td colspan="7" class="text-muted">Sin BLs para mostrar con los filtros actuales.</td></tr>`;
    document.getElementById('chkAllVisible').checked = false;
    return;
  }
  rows.forEach(r=>{
    const bl = r.bl ?? '';
    const checked = BL_SELECTED.has(bl) ? 'checked' : '';
    tb.insertAdjacentHTML('beforeend', `
      <tr>
        <td><input type="checkbox" class="form-check-input blchk" value="${String(bl).replace(/"/g,'&quot;')}" ${checked}></td>
        <td><strong>${bl}</strong></td>
        <td>${r.seccion ?? ''}</td>
        <td>${r.pasillo ?? ''}</td>
        <td>${r.rack ?? ''}</td>
        <td>${r.nivel ?? ''}</td>
        <td>${r.posicion ?? ''}</td>
      </tr>
    `);
  });
  document.getElementById('chkAllVisible').checked = false;
}

// Ajuste: selección se recorta a BLs visibles al cambiar filtros
function applyBLFilters(){
  const q = (document.getElementById('blSearch').value || '').toLowerCase().trim();
  const fp = document.getElementById('f_pasillo').value || '';
  const fr = document.getElementById('f_rack').value || '';
  const fn = document.getElementById('f_nivel').value || '';

  document.getElementById('pasillo').value = fp;
  document.getElementById('rack').value    = fr;
  document.getElementById('nivel').value   = fn;

  BL_VIEW = BL_DATA.filter(r=>{
    if(fp && String(r.pasillo||'') !== fp) return false;
    if(fr && String(r.rack||'') !== fr) return false;
    if(fn && String(r.nivel||'') !== fn) return false;

    if(!q) return true;
    return String(r.bl||'').toLowerCase().includes(q)
        || String(r.seccion||'').toLowerCase().includes(q)
        || String(r.pasillo||'').toLowerCase().includes(q)
        || String(r.rack||'').toLowerCase().includes(q)
        || String(r.nivel||'').toLowerCase().includes(q)
        || String(r.posicion||'').toLowerCase().includes(q);
  });

  const visibles = new Set((BL_VIEW||[]).map(x=>x.bl).filter(Boolean));
  BL_SELECTED = new Set([...BL_SELECTED].filter(bl => visibles.has(bl)));

  renderBL(BL_VIEW);
  setSelCount();
}

async function loadEmpresas(){
  const sel = document.getElementById('cve_cia');
  try{
    const res = await fetch(`${API_EMPRESAS}?action=list&solo_activas=1`);
    const j = await res.json();
    const rows = j.rows || j.data || [];
    sel.innerHTML = `<option value="">-- Selecciona --</option>`;
    rows.forEach(e=>{
      sel.insertAdjacentHTML('beforeend',
        `<option value="${e.cve_cia}">${(e.clave_empresa? e.clave_empresa+' · ' : '')}${e.des_cia}</option>`
      );
    });
  }catch(e){
    sel.innerHTML = `<option value="">(sin empresas)</option>`;
  }
}

async function loadUsuarios(){
  const sel = document.getElementById('id_usuario');
  try{
    const res = await fetch(`${API_USUARIOS}?action=list`);
    const j = await res.json();
    const rows = j.data || [];
    sel.innerHTML = `<option value="">-- Selecciona --</option>`;
    rows.forEach(u=>{
      // ✅ value id_user, display cve_usuario + nombre
      sel.insertAdjacentHTML('beforeend',
        `<option value="${u.id_user}">${u.cve_usuario} · ${u.nombre_completo}</option>`
      );
    });
  }catch(e){
    sel.innerHTML = `<option value="">(sin usuarios)</option>`;
  }
}

async function loadZonas(almacenp_id){
  const sel = document.getElementById('cve_almac');
  sel.innerHTML = `<option value="">Cargando...</option>`;
  try{
    const res = await fetch(`${API_ZONAS}?almacenp_id=${encodeURIComponent(almacenp_id)}&solo_activas=1`);
    const j = await res.json();
    sel.innerHTML = `<option value="">-- Todas --</option>`;
    (j.data || []).forEach(z=>{
      sel.insertAdjacentHTML('beforeend', `<option value="${z.cve_almac}">${z.cve_almac} · ${(z.des_almac||'')}</option>`);
    });
  }catch(e){
    sel.innerHTML = `<option value="">-- Todas --</option>`;
  }
}

async function loadBL(almacenp_id, cve_almac){
  BL_DATA=[]; BL_VIEW=[]; BL_SELECTED=new Set(); setSelCount();

  document.getElementById('blSearch').value='';
  fillSelect('f_pasillo', [], '-- Todos --');
  fillSelect('f_rack', [], '-- Todos --');
  fillSelect('f_nivel', [], '-- Todos --');

  const card = document.getElementById('cardBL');
  const tb = document.getElementById('tbodyBL');

  if(!almacenp_id){ card.style.display='none'; updateKpis(); return; }
  card.style.display='';
  tb.innerHTML=`<tr><td colspan="7" class="text-muted">Cargando BLs...</td></tr>`;

  try{
    let url = `${API_UBIC}?almacenp_id=${encodeURIComponent(almacenp_id)}`;
    if(cve_almac) url += `&cve_almac=${encodeURIComponent(cve_almac)}`;
    const res = await fetch(url);
    const rows = await res.json();
    BL_DATA = Array.isArray(rows) ? rows : [];

    fillSelect('f_pasillo', uniq(BL_DATA.map(x=>x.pasillo)), '-- Todos --');
    fillSelect('f_rack',    uniq(BL_DATA.map(x=>x.rack)),    '-- Todos --');
    fillSelect('f_nivel',   uniq(BL_DATA.map(x=>x.nivel)),   '-- Todos --');

    applyBLFilters();
  }catch(e){
    tb.innerHTML = `<tr><td colspan="7" class="text-danger">Error cargando BLs</td></tr>`;
  }
}

function selectRows(rows){
  rows.forEach(r=>{ if(r.bl) BL_SELECTED.add(r.bl); });
  applyBLFilters();
}
function clearSelection(){
  BL_SELECTED = new Set();
  applyBLFilters();
}

document.getElementById('cve_almacenp').addEventListener('change', async function(){
  const ap = this.value || '';
  if(!ap){ document.getElementById('cardBL').style.display='none'; updateKpis(); return; }
  await loadZonas(ap);
  await loadBL(ap, document.getElementById('cve_almac').value || '');
});
document.getElementById('cve_almac').addEventListener('change', function(){
  const ap = document.getElementById('cve_almacenp').value || '';
  if(ap) loadBL(ap, this.value || '');
});

document.getElementById('blSearch').addEventListener('input', applyBLFilters);
document.getElementById('f_pasillo').addEventListener('change', applyBLFilters);
document.getElementById('f_rack').addEventListener('change', applyBLFilters);
document.getElementById('f_nivel').addEventListener('change', applyBLFilters);

document.getElementById('btnSelectAllView').addEventListener('click', ()=>selectRows(BL_VIEW));
document.getElementById('btnSelectAllAll').addEventListener('click', ()=>selectRows(BL_DATA));
document.getElementById('btnClearAll').addEventListener('click', clearSelection);

document.getElementById('chkAllVisible').addEventListener('change', function(){
  const checked = this.checked;
  (BL_VIEW||[]).forEach(r=>{
    if(!r.bl) return;
    if(checked) BL_SELECTED.add(r.bl); else BL_SELECTED.delete(r.bl);
  });
  applyBLFilters();
});
document.addEventListener('change', function(ev){
  if(!ev.target.classList.contains('blchk')) return;
  const bl = ev.target.value;
  if(ev.target.checked) BL_SELECTED.add(bl); else BL_SELECTED.delete(bl);
  setSelCount();
});

document.getElementById('tipo').addEventListener('change', function(){
  const isC = this.value === 'CICLICO';
  document.getElementById('wrapProducto').style.display = isC ? '' : 'none';
  if(!isC) document.getElementById('producto_filtro').value = '';
});

document.getElementById('frmPlan').addEventListener('submit', function(e){
  if(BL_SELECTED.size<=0){ e.preventDefault(); alert('Selecciona al menos un BL.'); return; }
  if(document.getElementById('tipo').value==='CICLICO' && !document.getElementById('producto_filtro').value.trim()){
    e.preventDefault(); alert('En inventario CÍCLICO captura Producto (filtro).'); return;
  }
  setSelCount();
});

loadEmpresas(); loadUsuarios();
document.getElementById('tipo').dispatchEvent(new Event('change'));
updateKpis();
</script>

<?php include __DIR__ . '/../../bi/_menu_global_end.php'; ?>
