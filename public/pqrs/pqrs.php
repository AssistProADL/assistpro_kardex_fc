<?php
require_once __DIR__ . '/../bi/_menu_global.php';
require_once __DIR__ . '/../../app/db.php';
db_pdo();
global $pdo;

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

/* ================= DEFAULT FECHAS (ÚLTIMA SEMANA) ================= */
$hoy = date('Y-m-d');
$semana = date('Y-m-d', strtotime('-7 days'));

$f_desde = $_GET['desde'] ?? $semana;
$f_hasta = $_GET['hasta'] ?? $hoy;
$f_status = $_GET['status'] ?? '';
$f_tipo   = $_GET['tipo'] ?? '';

/* ================= KPIs ================= */
$kpis = ['P'=>0,'Q'=>0,'R'=>0,'S'=>0];
foreach($pdo->query("SELECT tipo, COUNT(*) t FROM pqrs_case GROUP BY tipo") as $r){
  if(isset($kpis[$r['tipo']])) $kpis[$r['tipo']] = (int)$r['t'];
}

/* ================= LISTADO ================= */
$sql = "
  SELECT 
    c.*,
    cl.RazonSocial
  FROM pqrs_case c
  LEFT JOIN c_cliente cl
    ON cl.Cve_Clte = c.cve_clte
  WHERE DATE(c.creado_en) BETWEEN ? AND ?
";
$params = [$f_desde, $f_hasta];

if($f_status){
  $sql .= " AND c.status_clave = ? ";
  $params[] = $f_status;
}
if($f_tipo){
  $sql .= " AND c.tipo = ? ";
  $params[] = $f_tipo;
}

$sql .= " ORDER BY c.id_case DESC LIMIT 500";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= STATUS CATALOGO ================= */
$statusCat = $pdo->query("
  SELECT DISTINCT status_clave 
  FROM pqrs_case 
  ORDER BY status_clave
")->fetchAll(PDO::FETCH_COLUMN);
?>

<style>
body, table{ font-size:10px; }

/* HEADER */
.ap-header{
  display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;
}
.ap-title{font-size:18px;font-weight:700;color:#1e3a8a;display:flex;gap:8px;}

/* KPIS */
.ap-kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:12px;}
@media(max-width:1200px){.ap-kpi-grid{grid-template-columns:repeat(2,1fr);}}
.ap-kpi{background:#fff;border-radius:14px;border:1px solid #e5e7eb;padding:14px;text-align:center;cursor:pointer;position:relative;}
.ap-kpi::before{content:'';position:absolute;top:0;left:0;width:100%;height:4px;border-radius:14px 14px 0 0;}
.kpi-p::before{background:#2563eb;}
.kpi-q::before{background:#0ea5e9;}
.kpi-r::before{background:#f59e0b;}
.kpi-s::before{background:#16a34a;}
.ap-kpi small{font-size:11px;color:#64748b;font-weight:600;}
.ap-kpi b{font-size:28px;color:#0f172a;}

/* FILTROS */
.ap-filters .form-control{font-size:10px;height:32px;}
.ap-filters label{font-size:10px;color:#475569;font-weight:600;}

/* TABLA */
.ap-table-wrap{max-height:calc(100vh - 380px);overflow:auto;}
.ap-table{min-width:1600px;}
.ap-table th{position:sticky;top:0;background:#fff;font-size:11px;}
.ap-table td{padding:6px 8px;vertical-align:middle;}

/* STATUS SPINNER */
.ap-status{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;font-weight:600;font-size:10px;}
.ap-dot{width:8px;height:8px;border-radius:50%;}
.st-nueva{background:#e0f2fe;color:#0369a1;} .st-nueva .ap-dot{background:#0284c7;}
.st-proceso{background:#fef3c7;color:#92400e;} .st-proceso .ap-dot{background:#f59e0b;}
.st-espera{background:#ffedd5;color:#9a3412;} .st-espera .ap-dot{background:#fb923c;}
.st-cerrada{background:#dcfce7;color:#166534;} .st-cerrada .ap-dot{background:#22c55e;}
.st-error{background:#fee2e2;color:#991b1b;} .st-error .ap-dot{background:#ef4444;}

.btn-xs{padding:4px 6px;font-size:11px;}
</style>

<div class="container-fluid mt-4">

<!-- HEADER -->
<div class="ap-header">
  <div class="ap-title" onclick="location.href='pqrs.php'" style="cursor:pointer">
    <i class="bi bi-chat-left-text"></i> Control PQRS
  </div>
  <a href="pqrs_new.php" class="btn btn-primary btn-sm">
    <i class="bi bi-plus-circle"></i> Nueva Incidencia
  </a>
</div>

<!-- KPIS -->
<div class="ap-kpi-grid">
  <div class="ap-kpi kpi-p" onclick="setTipo('P')"><small>Peticiones</small><b><?=$kpis['P']?></b></div>
  <div class="ap-kpi kpi-q" onclick="setTipo('Q')"><small>Quejas</small><b><?=$kpis['Q']?></b></div>
  <div class="ap-kpi kpi-r" onclick="setTipo('R')"><small>Reclamos</small><b><?=$kpis['R']?></b></div>
  <div class="ap-kpi kpi-s" onclick="setTipo('S')"><small>Sugerencias</small><b><?=$kpis['S']?></b></div>
</div>

<!-- FILTROS -->
<div class="card ap-filters mb-2">
<div class="card-body p-2">
<form class="row g-2">
  <div class="col-md-2">
    <label>Desde</label>
    <input type="date" name="desde" value="<?=h($f_desde)?>" class="form-control">
  </div>
  <div class="col-md-2">
    <label>Hasta</label>
    <input type="date" name="hasta" value="<?=h($f_hasta)?>" class="form-control">
  </div>
  <div class="col-md-2">
    <label>Status</label>
    <select name="status" class="form-control">
      <option value="">Todos</option>
      <?php foreach($statusCat as $s): ?>
        <option value="<?=$s?>" <?=$f_status===$s?'selected':''?>><?=$s?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2 d-flex align-items-end">
    <button class="btn btn-primary btn-sm">Filtrar</button>
  </div>
</form>
</div>
</div>

<!-- TABLA -->
<div class="card">
<div class="card-body p-2">
<div class="ap-table-wrap">
<table class="table table-hover table-sm ap-table">
<thead>
<tr>
  <th>Acciones</th>
  <th>Status</th>
  <th>Fecha</th>
  <th>Folio</th>
  <th>Clave Cliente</th>
  <th>Cliente</th>
  <th>Referencia</th>
  <th>Tipo</th>
  <th>Reportó</th>
</tr>
</thead>
<tbody>
<?php foreach($rows as $r):
  $st = $r['status_clave'];
  $cls = match($st){
    'NUEVA'=>'st-nueva','EN_PROCESO'=>'st-proceso','EN_ESPERA'=>'st-espera','CERRADA'=>'st-cerrada',default=>'st-error'
  };
?>
<tr data-tipo="<?=h($r['tipo'])?>">
  <td>
    <a class="btn btn-outline-primary btn-xs" href="pqrs_view.php?id_case=<?=$r['id_case']?>">Ver</a>
    <a class="btn btn-outline-danger btn-xs" target="_blank" href="pqrs_pdf.php?id_case=<?=$r['id_case']?>">PDF</a>
  </td>
  <td><span class="ap-status <?=$cls?>"><span class="ap-dot"></span><?=h($st)?></span></td>
  <td><?=date('d/m/Y', strtotime($r['creado_en']))?></td>
  <td><b><?=h($r['fol_pqrs'])?></b></td>
  <td><?=h($r['cve_clte'])?></td>
  <td><?=h($r['RazonSocial'] ?? '')?></td>
  <td><?=h($r['ref_folio'])?></td>
  <td><?=h($r['tipo'])?></td>
  <td><?=h($r['reporta_nombre'])?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>

</div>

<script>
function setTipo(t){
  const url = new URL(window.location.href);
  url.searchParams.set('tipo', t);
  window.location.href = url.toString();
}
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
