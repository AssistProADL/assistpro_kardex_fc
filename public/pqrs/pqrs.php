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
foreach($pdo->query("SELECT status_clave, COUNT(*) total FROM pqrs_case GROUP BY status_clave") as $r){
  $kpi[$r['status_clave']] = (int)$r['total'];
}

/* ================= listado ================= */
$sql = "SELECT * FROM pqrs_case WHERE 1=1 ";
$p=[];

if($f_status){$sql.=" AND status_clave=?";$p[]=$f_status;}
if($f_clte){$sql.=" AND cve_clte=?";$p[]=$f_clte;}
if($f_ref){$sql.=" AND ref_folio LIKE ?";$p[]="%$f_ref%";}
if($f_desde){$sql.=" AND DATE(creado_en)>=?";$p[]=$f_desde;}
if($f_hasta){$sql.=" AND DATE(creado_en)<=?";$p[]=$f_hasta;}
if($f_q){
  $sql.=" AND (fol_pqrs LIKE ? OR ref_folio LIKE ? OR cve_clte LIKE ?)";
  $p[]="%$f_q%";$p[]="%$f_q%";$p[]="%$f_q%";
}

$sql.=" ORDER BY id_case DESC LIMIT 500";
$st=$pdo->prepare($sql);
$st->execute($p);
$rows=$st->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
/* ===============================
   ASSISTPRO UI – CORPORATIVO
================================ */

/* ===== TÍTULO (REFERENCIA VISUAL) ===== */
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

/* ===== BOTÓN NUEVA INCIDENCIA ===== */
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

/* ===== ICONO FILTRAR ===== */
.btn-filter i{
  color:#1e3a8a;
}

/* ===== KPI CARDS ===== */
.ap-kpi-grid{
  display:grid;
  grid-template-columns:repeat(5,1fr);
  gap:14px;
  margin-bottom:16px;
}
@media(max-width:1200px){
  .ap-kpi-grid{grid-template-columns:repeat(3,1fr);}
}
@media(max-width:768px){
  .ap-kpi-grid{grid-template-columns:repeat(2,1fr);}
}

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
.ap-kpi-noprocede::before{background:#dc2626;}

.ap-kpi-title{font-size:.72rem;color:#475569;font-weight:600;}
.ap-kpi-value{font-size:30px;font-weight:800;color:#0f172a;}

/* ===== Filters ===== */
.ap-filters label{font-size:.7rem;color:#6c757d;}
.ap-filters .form-control{height:34px;}

/* ===== Table ===== */
.ap-table-wrapper{max-height:260px;overflow:auto;}
.ap-table th{
  position:sticky;
  top:0;
  background:#fff;
  font-size:.75rem;
}
.ap-table td{font-size:.8rem;padding:6px 8px;}
</style>

<div class="container-fluid mt-4">

<!-- ===== HEADER ===== -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <h3 class="assistpro-title mb-0">
    <i class="bi bi-chat-left-dots"></i>
    Control de Incidencias PQRS
  </h3>

  <!-- ⭐ BOTÓN UNIFICADO EN COLOR ⭐ -->
  <a href="pqrs_new.php" class="btn btn-assistpro btn-sm">
    <i class="bi bi-plus-lg"></i> Nueva Incidencia
  </a>
</div>

<!-- ===== KPIs ===== -->
<div class="ap-kpi-grid">
<?php foreach($statusRows as $s):
  $total=$kpi[$s['clave']]??0;
  $class = match($s['clave']){
    'NUEVA'=>'ap-kpi-nueva',
    'EN_PROCESO'=>'ap-kpi-proceso',
    'EN_ESPERA'=>'ap-kpi-espera',
    'CERRADA'=>'ap-kpi-cerrada',
    'NO_PROCEDE'=>'ap-kpi-noprocede',
    default=>''
  };
?>
  <div class="ap-kpi-card <?= $class ?>">
    <div class="ap-kpi-title"><?=h(strtoupper($s['nombre']))?></div>
    <div class="ap-kpi-value"><?=$total?></div>
  </div>
<?php endforeach;?>
</div>

<!-- ===== Filters ===== -->
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

      <div class="col-md-2"><label>Cliente</label>
        <input class="form-control" name="cve_clte" value="<?=h($f_clte)?>">
      </div>

      <div class="col-md-2"><label>Referencia</label>
        <input class="form-control" name="ref_folio" value="<?=h($f_ref)?>">
      </div>

      <div class="col-md-2"><label>Búsqueda</label>
        <input class="form-control" name="q" value="<?=h($f_q)?>">
      </div>

      <div class="col-md-2"><label>Desde</label>
        <input type="date" class="form-control" name="desde" value="<?=h($f_desde)?>">
      </div>

      <div class="col-md-2 d-flex align-items-end gap-1">
        <!-- ⭐ ICONO FILTRAR UNIFICADO ⭐ -->
        <button class="btn btn-light btn-sm w-100 btn-filter">
          <i class="fas fa-filter"></i>
        </button>
        <a href="pqrs.php" class="btn btn-light btn-sm w-100">Limpiar</a>
      </div>
    </form>
  </div>
</div>

<!-- ===== Table ===== -->
<div class="card">
  <div class="card-body p-2">
    <div class="ap-table-wrapper">
      <table class="table table-hover table-sm ap-table">
        <thead>
          <tr>
            <th>Folio</th>
            <th>Cliente</th>
            <th>Referencia</th>
            <th>Tipo</th>
            <th>Status</th>
            <th>Fecha</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php if(!$rows):?>
          <tr><td colspan="7" class="text-muted">Sin registros</td></tr>
        <?php else: foreach($rows as $r):?>
          <tr>
            <td><b><?=h($r['fol_pqrs'])?></b></td>
            <td><?=h($r['cve_clte'])?></td>
            <td><?=h($r['ref_folio'])?></td>
            <td><?=h($r['tipo'])?></td>
            <td><?=h($r['status_clave'])?></td>
            <td><?=h($r['creado_en'])?></td>
            <td>
              <a href="pqrs_view.php?id_case=<?=(int)$r['id_case']?>" class="btn btn-outline-primary btn-sm">
                Ver
              </a>
            </td>
          </tr>
        <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>

</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
