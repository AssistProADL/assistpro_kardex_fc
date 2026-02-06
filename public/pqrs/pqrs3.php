<?php 
// =====================================================
// PQRS - Listado AssistPro (UI unificada)
// =====================================================
require_once __DIR__ . '/../bi/_menu_global.php';
require_once __DIR__ . '/../../app/db.php';
db_pdo();
global $pdo;

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* ================= filtros ================= */
$f_status = trim($_GET['status'] ?? '');
$f_clte   = trim($_GET['cve_clte'] ?? '');
$f_ref    = trim($_GET['ref_folio'] ?? '');
$f_desde  = trim($_GET['desde'] ?? '');
$f_hasta  = trim($_GET['hasta'] ?? '');
$f_q      = trim($_GET['q'] ?? '');

/* ================= status ================= */
$statusRows = $pdo->query("
  SELECT clave,nombre,orden 
  FROM pqrs_cat_status 
  WHERE activo=1 
  ORDER BY orden
")->fetchAll(PDO::FETCH_ASSOC);

/* ================= KPIs ================= */
$kpi = [];

foreach(
  $pdo->query("
    SELECT c.status_clave, COUNT(*) total 
    FROM pqrs_case c
    GROUP BY c.status_clave
  ") as $r
){
  if ($r['status_clave'] === 'NO_PROCEDE') {
    // 👉 NO_PROCEDE cuenta como CERRADA
    $kpi['CERRADA'] = ($kpi['CERRADA'] ?? 0) + (int)$r['total'];
  } else {
    $kpi[$r['status_clave']] = (int)$r['total'];
  }
}

/* ================= listado (JOIN a c_cliente por clave exacta) ================= */
$sql = "
  SELECT 
    c.*,
    cl.RazonSocial AS cliente_razon_social
  FROM pqrs_case c
  LEFT JOIN c_cliente cl
    ON cl.Cve_Clte = c.cve_clte
  WHERE 1=1
";
$p=[];

if($f_status){ $sql.=" AND c.status_clave=?"; $p[]=$f_status; }
if($f_clte){   $sql.=" AND c.cve_clte=?";     $p[]=$f_clte; }
if($f_ref){    $sql.=" AND c.ref_folio LIKE ?"; $p[]="%$f_ref%"; }
if($f_desde){  $sql.=" AND DATE(c.creado_en)>=?"; $p[]=$f_desde; }
if($f_hasta){  $sql.=" AND DATE(c.creado_en)<=?"; $p[]=$f_hasta; }
if($f_q){
  $sql.=" AND (c.fol_pqrs LIKE ? OR c.ref_folio LIKE ? OR c.cve_clte LIKE ?)";
  $p[]="%$f_q%"; $p[]="%$f_q%"; $p[]="%$f_q%";
}

$sql.=" ORDER BY c.id_case DESC LIMIT 500";
$st=$pdo->prepare($sql);
$st->execute($p);
$rows=$st->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
/* ===============================
   ASSISTPRO UI – CORPORATIVO
================================ */
body, table{font-size:10px;}

.assistpro-title{
  font-weight:700;
  color:#1e3a8a;
  display:flex;
  align-items:center;
  gap:.5rem;
}
.assistpro-title i{
  font-size:1.6rem;
  color:#2563eb;
}

.btn-assistpro{
  background:#1e3a8a;
  border:none;
  color:#fff;
  font-weight:600;
}
.btn-assistpro:hover{
  background:#1e40af;
  color:#fff;
}

.btn-filter i{color:#1e3a8a;}

.ap-kpi-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
  gap:14px;
  margin-bottom:16px;
  justify-content:center;
}

@media(max-width:1200px){.ap-kpi-grid{grid-template-columns:repeat(3,1fr);}}
@media(max-width:768px){.ap-kpi-grid{grid-template-columns:repeat(2,1fr);}}

.ap-kpi-card{
  background:#fff;
  border-radius:14px;
  border:1px solid #e5eaf3;
  padding:16px 10px;
  text-align:center;
  position:relative;
}
.ap-kpi-card::before{
  content:'';
  position:absolute;
  top:0;left:0;
  width:100%;
  height:4px;
  border-radius:14px 14px 0 0;
}
.ap-kpi-nueva::before{background:#2563eb;}
.ap-kpi-proceso::before{background:#0ea5e9;}
.ap-kpi-espera::before{background:#f59e0b;}
.ap-kpi-cerrada::before{background:#16a34a;}

.ap-kpi-title{font-size:.72rem;color:#475569;font-weight:600;}
.ap-kpi-value{font-size:30px;font-weight:800;color:#0f172a;}

.ap-filters label{font-size:.7rem;color:#6c757d;}
.ap-filters .form-control{height:34px;}

.ap-table-wrapper{max-height:260px;overflow:auto;}
.ap-table th{
  position:sticky;
  top:0;
  background:#fff;
  font-size:.75rem;
}
.ap-table td{font-size:.8rem;padding:6px 8px;}

/* ===== ACCIONES ===== */
.ap-action-btn{
  padding:4px 6px;
  font-size:1rem;
}
</style>

<div class="container-fluid mt-4">

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <h3 class="assistpro-title mb-0">
    <i class="bi bi-chat-left-dots"></i>
    Control de Incidencias PQRS
  </h3>

  <a href="pqrs_new.php" class="btn btn-assistpro btn-sm">
    <i class="bi bi-plus-lg"></i> Nueva Incidencia
  </a>
</div>

<!-- KPIs (NO TOCAR) -->
<div class="ap-kpi-grid">
<?php foreach($statusRows as $s):

  // ❌ NO mostrar KPI NO_PROCEDE
  if ($s['clave'] === 'NO_PROCEDE') {
    continue;
  }

  $total=$kpi[$s['clave']]??0;
  $class = match($s['clave']){
    'NUEVA'=>'ap-kpi-nueva',
    'EN_PROCESO'=>'ap-kpi-proceso',
    'EN_ESPERA'=>'ap-kpi-espera',
    'CERRADA'=>'ap-kpi-cerrada',
    default=>''
  };
?>
  <div class="ap-kpi-card <?= $class ?>">
    <div class="ap-kpi-title"><?=h(strtoupper($s['nombre']))?></div>
    <div class="ap-kpi-value"><?=$total?></div>
  </div>
<?php endforeach;?>
</div>

<!-- FILTERS -->
<div class="card ap-filters mb-2">
  <div class="card-body p-2">
    <form class="row g-2" method="get">
      <div class="col-md-2">
        <label>Status</label>
        <select class="form-control" name="status">
          <option value="">Todos</option>
          <?php foreach($statusRows as $s):?>
            <option value="<?=h($s['clave'])?>" <?=$f_status===$s['clave']?'selected':''?>>
              <?=h($s['nombre'])?>
            </option>
          <?php endforeach;?>
        </select>
      </div>

      <div class="col-md-2">
        <label>Cliente</label>
        <input type="text" class="form-control" name="cve_clte" value="<?=h($f_clte)?>" placeholder="CL0000000003">
      </div>

      <div class="col-md-2">
        <label>Referencia</label>
        <input type="text" class="form-control" name="ref_folio" value="<?=h($f_ref)?>" placeholder="MO-xxxx">
      </div>

      <div class="col-md-2">
        <label>Desde</label>
        <input type="date" class="form-control" name="desde" value="<?=h($f_desde)?>">
      </div>

      <div class="col-md-2">
        <label>Hasta</label>
        <input type="date" class="form-control" name="hasta" value="<?=h($f_hasta)?>">
      </div>

      <div class="col-md-2">
        <label>Buscar</label>
        <input type="text" class="form-control" name="q" value="<?=h($f_q)?>" placeholder="Folio / Ref / Cliente">
      </div>

      <div class="col-12 d-flex gap-2 mt-1">
        <button class="btn btn-assistpro btn-sm" type="submit">
          <i class="bi bi-funnel"></i> Filtrar
        </button>
        <a class="btn btn-outline-secondary btn-sm" href="pqrs.php">
          <i class="bi bi-x-circle"></i> Limpiar
        </a>
      </div>
    </form>
  </div>
</div>

<!-- TABLA -->
<div class="card">
  <div class="card-body p-2">
    <div class="ap-table-wrapper">
      <table class="table table-hover table-sm ap-table mb-0">
        <thead>
          <tr>
            <th style="width:110px;">Acciones</th>
            <th>Folio</th>
            <th>Cliente</th>
            <th>Referencia</th>
            <th>Tipo</th>
            <th>Reportó</th>
            <th>Cierre</th>
            <th>Fecha</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($rows as $r): 
          $clave = (string)($r['cve_clte'] ?? '');
          $rs    = (string)($r['cliente_razon_social'] ?? '');
          $clienteTxt = $rs !== '' ? ($clave.' – '.$rs) : $clave; // si no existe en maestro, NO inventar
          $fecha = '';
          if(!empty($r['creado_en'])){
            $ts = strtotime($r['creado_en']);
            $fecha = $ts ? date('d/m/Y', $ts) : '';
          }
        ?>
          <tr>
            <td>
              <a class="btn btn-outline-primary btn-sm ap-action-btn" href="pqrs_view.php?id_case=<?=(int)$r['id_case']?>">Ver</a>
              <a class="btn btn-outline-danger btn-sm ap-action-btn" target="_blank" href="pqrs_pdf.php?id_case=<?=(int)$r['id_case']?>">PDF</a>
            </td>
            <td><b><?=h($r['fol_pqrs'] ?? '')?></b></td>
            <td><?=h($clienteTxt)?></td>
            <td><?=h($r['ref_folio'] ?? '')?></td>
            <td><?=h($r['tipo'] ?? '')?></td>
            <td><?=h($r['reporta_nombre'] ?? '')?></td>
            <td><?=h($r['status_clave'] ?? '')?></td>
            <td><?=h($fecha)?></td>
          </tr>
        <?php endforeach; ?>

        <?php if(!$rows): ?>
          <tr><td colspan="8" class="text-center text-muted">Sin registros</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
