<?php
// public/dashboard/bitacoratiempos.php
// Bitácora de Tiempos — con codesop (Operacion) en columna Cliente cuando aplique.

require_once __DIR__ . '/../../app/db.php';

/* =========================
 * 1) LECTURA DE FILTROS
 * ========================= */
$empresa   = trim($_GET['empresa'] ?? '');
$ruta      = trim($_GET['ruta']    ?? '');
$cliente   = trim($_GET['cliente'] ?? '');  // Cve_Clte
$vendedor  = trim($_GET['vend']    ?? '');
$desde     = trim($_GET['desde']   ?? '');
$hasta     = trim($_GET['hasta']   ?? '');

// Rango por HI (default: hoy-9 días a hoy => 10 días)
if ($desde === '' || $hasta === '') {
  $hasta = date('Y-m-d');                       // hoy
  $desde = date('Y-m-d', strtotime('-9 days')); // incluye hoy (10 días)
}
$d1 = $desde . ' 00:00:00';
$d2 = $hasta . ' 23:59:59';

/* =========================
 * 2) CATÁLOGOS (acotados por HI)
 * ========================= */
$empresas = db_all("
  SELECT DISTINCT b.IdEmpresa AS id
  FROM bitacoratiempos b
  WHERE b.HI BETWEEN :d1 AND :d2
  ORDER BY b.IdEmpresa
", ['d1'=>$d1,'d2'=>$d2]);

$rutas = db_all("
  SELECT DISTINCT b.RutaId AS id,
         COALESCE(NULLIF(TRIM(r.descripcion),''),'(Sin ruta)') AS txt
  FROM bitacoratiempos b
  LEFT JOIN t_ruta r ON r.ID_Ruta = b.RutaId
  WHERE b.HI BETWEEN :d1 AND :d2
    AND b.RutaId IS NOT NULL
  ORDER BY txt, id
", ['d1'=>$d1,'d2'=>$d2]);

// Cliente (Cve_Clte) vía destinatario
$clientes = db_all("
  SELECT DISTINCT c.Cve_Clte AS id,
         COALESCE(NULLIF(TRIM(c.RazonSocial),''), NULLIF(TRIM(c.RazonComercial),''), '(Sin cliente)') AS txt
  FROM bitacoratiempos b
  LEFT JOIN c_destinatarios d ON d.id_destinatario = b.Codigo
  LEFT JOIN c_cliente c       ON c.Cve_Clte       = d.Cve_Clte
  WHERE b.HI BETWEEN :d1 AND :d2
    AND c.Cve_Clte IS NOT NULL AND c.Cve_Clte <> ''
  ORDER BY txt, id
", ['d1'=>$d1,'d2'=>$d2]);

$vendedores = db_all("
  SELECT DISTINCT b.IdVendedor AS id,
         COALESCE(NULLIF(TRIM(v.Nombre),''),'(Sin vendedor)') AS txt
  FROM bitacoratiempos b
  LEFT JOIN t_vendedores v ON v.Id_Vendedor = b.IdVendedor
  WHERE b.HI BETWEEN :d1 AND :d2
    AND b.IdVendedor IS NOT NULL
  ORDER BY txt, id
", ['d1'=>$d1,'d2'=>$d2]);

/* =========================
 * 2.1) SANEAR FILTROS “FANTASMA”
 * ========================= */
$validEmp = array_column($empresas, 'id');
$validRut = array_map('intval', array_column($rutas, 'id'));
$validCli = array_column($clientes, 'id');
$validVen = array_map('intval', array_column($vendedores, 'id'));

if ($empresa  !== '' && !in_array($empresa,  $validEmp, true))        $empresa  = '';
if ($ruta     !== '' && !in_array((int)$ruta, $validRut, true))       $ruta     = '';
if ($cliente  !== '' && !in_array($cliente,   $validCli, true))       $cliente  = '';
if ($vendedor !== '' && !in_array((int)$vendedor, $validVen, true))   $vendedor = '';

/* =========================
 * 3) WHERE COMÚN
 * ========================= */
$where = ["b.HI BETWEEN :d1 AND :d2"];
$p = ['d1'=>$d1, 'd2'=>$d2];

if ($empresa  !== '') { $where[]="b.IdEmpresa  = :empresa";  $p['empresa']=$empresa; }
if ($ruta     !== '') { $where[]="b.RutaId     = :ruta";     $p['ruta']=(int)$ruta; }
if ($cliente  !== '') { $where[]="c.Cve_Clte   = :cliente";  $p['cliente']=$cliente; }
if ($vendedor !== '') { $where[]="b.IdVendedor = :vendedor"; $p['vendedor']=(int)$vendedor; }

$sqlWhere  = "WHERE " . implode(" AND ", $where);
$whereCore = "WHERE 1=1 AND " . implode(" AND ", $where);

/* =========================
 * 4) CONDICIONES BIT(1) ROBUSTAS
 * ========================= */
$condVisitado   = "( (b.Visita = 1) OR UPPER(COALESCE(CAST(b.Visita AS CHAR),'')) IN ('1','S','SI','Y','YES','TRUE') )";
$condProgramado = "( (b.Programado = 1) OR UPPER(COALESCE(CAST(b.Programado AS CHAR),'')) IN ('1','S','SI','Y','YES','TRUE') )";
$condCerrado    = "( (b.Cerrado = 1) OR UPPER(COALESCE(CAST(b.Cerrado AS CHAR),'')) IN ('1','S','SI','Y','YES','TRUE') )";

/* =========================
 * 5) KPIs Y TIEMPOS
 * ========================= */
$kpis = db_one("
  SELECT
    COUNT(*) AS total_reg,
    SUM(CASE WHEN $condProgramado THEN 1 ELSE 0 END) AS programados,
    SUM(CASE WHEN $condVisitado   THEN 1 ELSE 0 END) AS visitados,
    SUM(CASE WHEN $condCerrado    THEN 1 ELSE 0 END) AS cerrados
  FROM bitacoratiempos b
  LEFT JOIN c_destinatarios d ON d.id_destinatario = b.Codigo
  LEFT JOIN c_cliente c       ON c.Cve_Clte       = d.Cve_Clte
  $sqlWhere
", $p) ?: ['total_reg'=>0,'programados'=>0,'visitados'=>0,'cerrados'=>0];

$tmps = db_one("
  SELECT MIN(b.HI) AS ini_op, MAX(b.HF) AS fin_op
  FROM bitacoratiempos b
  LEFT JOIN c_destinatarios d ON d.id_destinatario = b.Codigo
  LEFT JOIN c_cliente c       ON c.Cve_Clte       = d.Cve_Clte
  $sqlWhere
", $p);

/* =========================
 * 6) PAGINACIÓN (25)
 * ========================= */
$PAGE_SIZE = 25;
$pv  = max(1, (int)($_GET['pv']  ?? 1));
$pnv = max(1, (int)($_GET['pnv'] ?? 1));

$totV  = (int) db_val("
  SELECT COUNT(*) 
  FROM bitacoratiempos b
  LEFT JOIN t_ruta r           ON r.ID_Ruta        = b.RutaId
  LEFT JOIN c_destinatarios d  ON d.id_destinatario= b.Codigo
  LEFT JOIN c_cliente c        ON c.Cve_Clte       = d.Cve_Clte
  LEFT JOIN codesop op         ON op.Codi          = b.Codigo
  LEFT JOIN t_vendedores v     ON v.Id_Vendedor    = b.IdVendedor
  $whereCore AND $condVisitado
", $p);

$totNV = (int) db_val("
  SELECT COUNT(*) 
  FROM bitacoratiempos b
  LEFT JOIN t_ruta r           ON r.ID_Ruta        = b.RutaId
  LEFT JOIN c_destinatarios d  ON d.id_destinatario= b.Codigo
  LEFT JOIN c_cliente c        ON c.Cve_Clte       = d.Cve_Clte
  LEFT JOIN codesop op         ON op.Codi          = b.Codigo
  LEFT JOIN t_vendedores v     ON v.Id_Vendedor    = b.IdVendedor
  $whereCore AND $condProgramado AND NOT ($condVisitado)
", $p);

$pagesV  = max(1, (int)ceil($totV  / $PAGE_SIZE));
$pagesNV = max(1, (int)ceil($totNV / $PAGE_SIZE));
$pv  = min($pv,  $pagesV);
$pnv = min($pnv, $pagesNV);
$offV  = ($pv  - 1) * $PAGE_SIZE;
$offNV = ($pnv - 1) * $PAGE_SIZE;

/* =========================
 * 7) COLUMNAS Y JOINS (DATA)
 * ========================= */
$cols = "
  b.IdEmpresa, b.RutaId, b.DiaO, b.Id, b.Codigo, b.Descripcion,
  b.HI, b.HF, b.HT, b.TS, b.Visita, b.Programado, b.Cerrado,
  b.IdV, b.IdVendedor, b.latitude, b.longitude,
  COALESCE(NULLIF(TRIM(r.descripcion),''),'(Sin ruta)') AS RutaDesc,
  COALESCE(NULLIF(TRIM(d.razonsocial),''),'(Sin destinatario)') AS DestinatarioNom,
  COALESCE(NULLIF(TRIM(c.RazonSocial),''), NULLIF(TRIM(c.RazonComercial),''), '(Sin cliente)') AS ClienteNom,
  /* ClienteMostrar: si hay código operativo, usar op.Operacion; si no, cliente */
  COALESCE(NULLIF(TRIM(op.Operacion),''), 
           NULLIF(TRIM(c.RazonSocial),''), NULLIF(TRIM(c.RazonComercial),''), '(Sin cliente)') AS ClienteMostrar,
  COALESCE(NULLIF(TRIM(v.Nombre),''),'(Sin vendedor)') AS VendedorNom
";

/* Visitados */
$rowsV = db_all("
  SELECT $cols
  FROM bitacoratiempos b
  LEFT JOIN t_ruta r           ON r.ID_Ruta        = b.RutaId
  LEFT JOIN c_destinatarios d  ON d.id_destinatario= b.Codigo
  LEFT JOIN c_cliente c        ON c.Cve_Clte       = d.Cve_Clte
  LEFT JOIN codesop op         ON op.Codi          = b.Codigo
  LEFT JOIN t_vendedores v     ON v.Id_Vendedor    = b.IdVendedor
  $whereCore AND $condVisitado
  ORDER BY b.HI DESC, b.RutaId, b.Codigo
  LIMIT $PAGE_SIZE OFFSET $offV
", $p);

/* No visitados */
$rowsNV = db_all("
  SELECT $cols
  FROM bitacoratiempos b
  LEFT JOIN t_ruta r           ON r.ID_Ruta        = b.RutaId
  LEFT JOIN c_destinatarios d  ON d.id_destinatario= b.Codigo
  LEFT JOIN c_cliente c        ON c.Cve_Clte       = d.Cve_Clte
  LEFT JOIN codesop op         ON op.Codi          = b.Codigo
  LEFT JOIN t_vendedores v     ON v.Id_Vendedor    = b.IdVendedor
  $whereCore AND $condProgramado AND NOT ($condVisitado)
  ORDER BY b.HI DESC, b.RutaId, b.Codigo
  LIMIT $PAGE_SIZE OFFSET $offNV
", $p);

/* =========================
 * 8) HELPERS UI
 * ========================= */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function qkeep($extra){ $base=$_GET; foreach($extra as $k=>$v){ $base[$k]=$v; } return '?'.http_build_query($base); }

?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Bitácora de Tiempos</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { font-size:.92rem; }
  .kpi-card{ border:1px solid #e9ecef; border-radius:12px; padding:14px; background:#fff; box-shadow:0 1px 2px rgba(0,0,0,.03); }
  .kpi-title{ color:#6c757d; font-weight:600; }
  .kpi-value{ font-size:1.4rem; font-weight:700; }
  .toolbar { gap:.5rem; }
  .table thead th { white-space: nowrap; position: sticky; top: 0; background:#f8f9fa; z-index:2; }
  .grid-wrap { max-height:55vh; overflow-y:auto; overflow-x:auto; }
</style>
</head>
<body class="bg-light">
<div class="container-fluid py-3">
  <h4 class="mb-3">Bitácora de Tiempos</h4>

  <!-- Filtros -->
  <form class="row g-2 align-items-end mb-3" method="get">
    <div class="col-12 col-md-2">
      <label class="form-label">Empresa</label>
      <select name="empresa" class="form-select">
        <option value="">(Todas)</option>
        <?php foreach($empresas as $e): ?>
        <option value="<?=h($e['id'])?>" <?= $empresa!=='' && $empresa==$e['id']?'selected':''?>>(<?=h($e['id'])?>)</option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-12 col-md-3">
      <label class="form-label">Ruta</label>
      <select name="ruta" class="form-select">
        <option value="">(Todas)</option>
        <?php foreach($rutas as $r): ?>
        <option value="<?=h($r['id'])?>" <?= $ruta!=='' && $ruta==$r['id']?'selected':''?>><?=h($r['txt'])?> (<?=h($r['id'])?>)</option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-6 col-md-2">
      <label class="form-label">Desde (HI)</label>
      <input type="date" name="desde" class="form-control" value="<?=h($desde)?>">
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label">Hasta (HI)</label>
      <input type="date" name="hasta" class="form-control" value="<?=h($hasta)?>">
    </div>

    <div class="col-12 col-md-3">
      <label class="form-label">Cliente</label>
      <select name="cliente" class="form-select">
        <option value="">(Todos)</option>
        <?php foreach($clientes as $c): ?>
        <option value="<?=h($c['id'])?>" <?= $cliente!=='' && $cliente==$c['id']?'selected':''?>><?=h($c['txt'])?> (<?=h($c['id'])?>)</option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-12 col-md-2">
      <label class="form-label">Vendedor</label>
      <select name="vend" class="form-select">
        <option value="">(Todos)</option>
        <?php foreach($vendedores as $v): ?>
        <option value="<?=h($v['id'])?>" <?= $vendedor!=='' && $vendedor==$v['id']?'selected':''?>><?=h($v['txt'])?> (<?=h($v['id'])?>)</option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-12 d-flex toolbar mt-2">
      <button class="btn btn-primary" type="submit">Aplicar</button>
      <a class="btn btn-outline-secondary" href="?">Limpiar</a>
      <button type="button" class="btn btn-success" id="btnCsv">Exportar CSV</button>
    </div>
  </form>

  <!-- KPIs -->
  <div class="row g-3 mb-3">
    <div class="col-12 col-md-3"><div class="kpi-card"><div class="kpi-title">Visitas Programadas</div><div class="kpi-value"><?=number_format((int)$kpis['programados'])?></div></div></div>
    <div class="col-12 col-md-3"><div class="kpi-card"><div class="kpi-title">Visitas Realizadas</div><div class="kpi-value"><?=number_format((int)$kpis['visitados'])?></div></div></div>
    <div class="col-12 col-md-3"><div class="kpi-card"><div class="kpi-title">% Efectividad Visitas</div><div class="kpi-value"><?= $kpis['programados']>0 ? number_format(100*$kpis['visitados']/$kpis['programados'],1).'%' : '—' ?></div></div></div>
    <div class="col-12 col-md-3"><div class="kpi-card"><div class="kpi-title">Cerrados</div><div class="kpi-value"><?=number_format((int)$kpis['cerrados'])?></div></div></div>
  </div>

  <!-- Ventana operativa -->
  <div class="row g-3 mb-3">
    <div class="col-12 col-md-6"><div class="kpi-card"><div class="kpi-title">Inicio Operativo</div><div class="kpi-value"><?= $tmps['ini_op'] ? h($tmps['ini_op']) : '—' ?></div></div></div>
    <div class="col-12 col-md-6"><div class="kpi-card"><div class="kpi-title">Fin Operativo</div><div class="kpi-value"><?= $tmps['fin_op'] ? h($tmps['fin_op']) : '—' ?></div></div></div>
  </div>

  <!-- Tabs -->
  <ul class="nav nav-tabs" id="tabsBT">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabVisitados" type="button">Visitados</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabNoVisitados" type="button">No Visitados</button></li>
  </ul>

  <div class="tab-content">

    <!-- Visitados -->
    <div class="tab-pane fade show active" id="tabVisitados">
      <div class="grid-wrap mt-2">
        <table class="table table-sm table-striped align-middle" id="tblVisitados">
          <thead>
            <tr>
              <th>Acciones</th><th>Ruta</th><th>DO</th><th>Código</th>
              <th>Cliente</th><th>Destinatario</th><th>Agente</th>
              <th>H. Inicial</th><th>H. Final</th><th>HT</th><th>TS</th>
              <th>Visita</th><th>Programado</th><th>Cerrado</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($rowsV as $r): ?>
            <tr>
              <td><button class="btn btn-sm btn-outline-primary" onclick="verGPS('<?=h($r['latitude'])?>','<?=h($r['longitude'])?>')">GPS</button></td>
              <td><?=h($r['RutaDesc'])?> (<?=h($r['RutaId'])?>)</td>
              <td><?=h($r['DiaO'])?></td>
              <td><?=h($r['Codigo'])?></td>
              <td><?=h($r['ClienteMostrar'])?></td>
              <td><?=h($r['DestinatarioNom'])?></td>
              <td><?=h($r['VendedorNom'])?></td>
              <td><?=h($r['HI'])?></td>
              <td><?=h($r['HF'])?></td>
              <td><?=h($r['HT'])?></td>
              <td><?=h($r['TS'])?></td>
              <td><?=h($r['Visita'])?></td>
              <td><?=h($r['Programado'])?></td>
              <td><?=h($r['Cerrado'])?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <nav class="d-flex justify-content-between align-items-center mt-2">
        <div>Mostrando <?= count($rowsV) ?> de <?= number_format($totV) ?> registros</div>
        <ul class="pagination pagination-sm mb-0">
          <li class="page-item <?= $pv<=1?'disabled':'' ?>"><a class="page-link" href="<?=h(qkeep(['pv'=>1]))?>">«</a></li>
          <li class="page-item <?= $pv<=1?'disabled':'' ?>"><a class="page-link" href="<?=h(qkeep(['pv'=>$pv-1]))?>">‹</a></li>
          <li class="page-item disabled"><span class="page-link"><?=$pv?> / <?=$pagesV?></span></li>
          <li class="page-item <?= $pv>=$pagesV?'disabled':'' ?>"><a class="page-link" href="<?=h(qkeep(['pv'=>$pv+1]))?>">›</a></li>
          <li class="page-item <?= $pv>=$pagesV?'disabled':'' ?>"><a class="page-link" href="<?=h(qkeep(['pv'=>$pagesV]))?>">»</a></li>
        </ul>
      </nav>
    </div>

    <!-- No visitados -->
    <div class="tab-pane fade" id="tabNoVisitados">
      <div class="grid-wrap mt-2">
        <table class="table table-sm table-striped align-middle" id="tblNoVisitados">
          <thead>
            <tr>
              <th>Acciones</th><th>Ruta</th><th>DO</th><th>Código</th>
              <th>Cliente</th><th>Destinatario</th><th>Agente</th>
              <th>Programado</th><th>Visita</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($rowsNV as $r): ?>
            <tr>
              <td><button class="btn btn-sm btn-outline-primary" onclick="verGPS('<?=h($r['latitude'])?>','<?=h($r['longitude'])?>')">GPS</button></td>
              <td><?=h($r['RutaDesc'])?> (<?=h($r['RutaId'])?>)</td>
              <td><?=h($r['DiaO'])?></td>
              <td><?=h($r['Codigo'])?></td>
              <td><?=h($r['ClienteMostrar'])?></td>
              <td><?=h($r['DestinatarioNom'])?></td>
              <td><?=h($r['VendedorNom'])?></td>
              <td><?=h($r['Programado'])?></td>
              <td><?=h($r['Visita'])?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <nav class="d-flex justify-content-between align-items-center mt-2">
        <div>Mostrando <?= count($rowsNV) ?> de <?= number_format($totNV) ?> registros</div>
        <ul class="pagination pagination-sm mb-0">
          <li class="page-item <?= $pnv<=1?'disabled':'' ?>"><a class="page-link" href="<?=h(qkeep(['pnv'=>1]))?>">«</a></li>
          <li class="page-item <?= $pnv<=1?'disabled':'' ?>"><a class="page-link" href="<?=h(qkeep(['pnv'=>$pnv-1]))?>">‹</a></li>
          <li class="page-item disabled"><span class="page-link"><?=$pnv?> / <?=$pagesNV?></span></li>
          <li class="page-item <?= $pnv>=$pagesNV?'disabled':'' ?>"><a class="page-link" href="<?=h(qkeep(['pnv'=>$pnv+1]))?>">›</a></li>
          <li class="page-item <?= $pnv>=$pagesNV?'disabled':'' ?>"><a class="page-link" href="<?=h(qkeep(['pnv'=>$pagesNV]))?>">»</a></li>
        </ul>
      </nav>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Export CSV del tab activo
function tablaCSV(idTabla){
  const rows = Array.from(document.querySelectorAll('#'+idTabla+' tr'));
  return rows.map(tr => Array.from(tr.children)
    .map(td => `"${td.innerText.replaceAll('"','""')}"`)
    .join(',')
  ).join('\n');
}
document.getElementById('btnCsv').addEventListener('click', ()=>{
  const act = document.querySelector('.tab-pane.active table')?.id || 'tblVisitados';
  const csv = tablaCSV(act);
  const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a'); a.href=url; a.download='bitacora_tiempos.csv'; a.click();
  URL.revokeObjectURL(url);
});
function verGPS(lat, lon){
  if(!lat || !lon){ alert('Sin coordenadas.'); return; }
  window.open(`https://www.google.com/maps?q=${encodeURIComponent(lat)},${encodeURIComponent(lon)}`,'_blank');
}
</script>
</body>
</html>
