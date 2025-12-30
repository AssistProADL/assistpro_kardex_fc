<?php
// public/ingresos/orden_compra.php
require_once __DIR__ . '/../../app/db.php';

$pdo = db_pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// ======================= Defaults (primer load) =======================
$isFirstLoad = empty($_GET);

$fechaDesde = $isFirstLoad ? date('Y-m-d', strtotime('-7 days')) : trim((string)($_GET['fecha_desde'] ?? ''));
$fechaHasta = $isFirstLoad ? date('Y-m-d') : trim((string)($_GET['fecha_hasta'] ?? ''));
$status     = $isFirstLoad ? 'A' : trim((string)($_GET['status'] ?? ''));
$almacen    = trim((string)($_GET['almacen'] ?? ''));
$moneda     = trim((string)($_GET['moneda'] ?? ''));
$protocolo  = trim((string)($_GET['protocolo'] ?? ''));

// ======================= Paginación =======================
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 25);
if ($perPage < 10) $perPage = 10;
if ($perPage > 200) $perPage = 200;
$offset = ($page - 1) * $perPage;

// ======================= Combos =======================
$almacenes = [];
$protocolos = [];

try {
  $almacenes = $all = $pdo->query("SELECT DISTINCT Cve_Almac AS clave FROM th_aduana WHERE TRIM(Cve_Almac)<>'' ORDER BY Cve_Almac")->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e){ $almacenes = []; }

try {
  $protocolos = $pdo->query("SELECT ID_Protocolo, descripcion FROM t_protocolo WHERE Activo=1 ORDER BY ID_Protocolo")->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e){ $protocolos = []; }

// ======================= Filtros =======================
$where=[]; $params=[];
if ($almacen!==''){ $where[]="h.Cve_Almac=:alm"; $params[':alm']=$almacen; }
if ($fechaDesde!==''){ $where[]="DATE(h.fech_pedimento)>=:fd"; $params[':fd']=$fechaDesde; }
if ($fechaHasta!==''){ $where[]="DATE(h.fech_pedimento)<=:fh"; $params[':fh']=$fechaHasta; }
if ($status!==''){ $where[]="h.status=:st"; $params[':st']=$status; }
if ($moneda!==''){ $where[]="h.Id_moneda=:mon"; $params[':mon']=(int)$moneda; }
if ($protocolo!==''){ $where[]="h.ID_Protocolo=:prot"; $params[':prot']=$protocolo; }

$sqlWhere = $where ? ('WHERE '.implode(' AND ', $where)) : '';

// ======================= Count total (para paginador real) =======================
$sqlCount = "SELECT COUNT(*) FROM th_aduana h $sqlWhere";
$stc = $pdo->prepare($sqlCount);
$stc->execute($params);
$totalRows = (int)$stc->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

// ======================= Query principal =======================
$sql = "
SELECT
  h.ID_Aduana,
  COALESCE(NULLIF(TRIM(h.Pedimento),''), NULLIF(TRIM(CAST(h.num_pedimento AS CHAR)),'')) AS FolioOC,
  h.fech_pedimento,
  h.status,
  h.Cve_Almac,
  h.ID_Protocolo,
  p.Nombre AS proveedor,
  pr.ID_Protocolo AS protocolo_clave,
  COALESCE(x.partidas,0) AS partidas,
  COALESCE(x.cant_total,0) AS cant_total
FROM th_aduana h
LEFT JOIN c_proveedores p ON p.ID_Proveedor=h.ID_Proveedor
LEFT JOIN t_protocolo pr ON pr.ID_Protocolo=h.ID_Protocolo
LEFT JOIN (
  SELECT ID_Aduana, COUNT(*) partidas, SUM(COALESCE(cantidad,0)) cant_total
  FROM td_aduana
  GROUP BY ID_Aduana
) x ON x.ID_Aduana=h.ID_Aduana
$sqlWhere
ORDER BY h.ID_Aduana DESC
LIMIT :lim OFFSET :off
";

$st = $pdo->prepare($sql);
foreach($params as $k=>$v){ $st->bindValue($k, $v); }
$st->bindValue(':lim', $perPage, PDO::PARAM_INT);
$st->bindValue(':off', $offset, PDO::PARAM_INT);
$st->execute();
$ocs = $st->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../bi/_menu_global.php';
?>

<style>
.ap-oc-table{font-size:10px}
.ap-oc-table th,.ap-oc-table td{white-space:nowrap;padding:4px 6px}
.ap-oc-table thead th{background:#f4f6fb;font-weight:700}
</style>

<div class="ap-wrapper">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="mb-0" style="font-size:13px;font-weight:800;color:#0F5AAD;">Órdenes de Compra (OCN/OCI)</h5>
    <a href="orden_compra_edit.php" class="btn btn-sm btn-primary">+ Nueva OC</a>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <form method="get" class="row g-2 align-items-end">
        <div class="col-md-2">
          <label style="font-size:10px;font-weight:700;">Almacén</label>
          <select class="form-select form-select-sm" name="almacen">
            <option value="">Todos</option>
            <?php foreach($almacenes as $a): $c=trim((string)$a['clave']); ?>
              <option value="<?php echo h($c); ?>" <?php echo ($almacen===$c)?'selected':''; ?>><?php echo h($c); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-2">
          <label style="font-size:10px;font-weight:700;">Desde</label>
          <input type="date" class="form-control form-control-sm" name="fecha_desde" value="<?php echo h($fechaDesde); ?>">
        </div>

        <div class="col-md-2">
          <label style="font-size:10px;font-weight:700;">Hasta</label>
          <input type="date" class="form-control form-control-sm" name="fecha_hasta" value="<?php echo h($fechaHasta); ?>">
        </div>

        <div class="col-md-2">
          <label style="font-size:10px;font-weight:700;">Status</label>
          <select class="form-select form-select-sm" name="status">
            <option value="">Todos</option>
            <option value="A" <?php echo ($status==='A')?'selected':''; ?>>Activa</option>
            <option value="T" <?php echo ($status==='T')?'selected':''; ?>>Pendiente (T)</option>
            <option value="K" <?php echo ($status==='K')?'selected':''; ?>>Proceso (K)</option>
            <option value="C" <?php echo ($status==='C')?'selected':''; ?>>Cancelada</option>
            <option value="I" <?php echo ($status==='I')?'selected':''; ?>>Cerrada (I)</option>
          </select>
        </div>

        <div class="col-md-2">
          <label style="font-size:10px;font-weight:700;">Protocolo</label>
          <select class="form-select form-select-sm" name="protocolo">
            <option value="">Todos</option>
            <?php foreach($protocolos as $p): ?>
              <option value="<?php echo h((string)$p['ID_Protocolo']); ?>" <?php echo ($protocolo===(string)$p['ID_Protocolo'])?'selected':''; ?>>
                <?php echo h((string)$p['descripcion']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-1">
          <button class="btn btn-sm btn-primary w-100">Buscar</button>
        </div>
        <div class="col-md-1">
          <a class="btn btn-sm btn-outline-secondary w-100" href="orden_compra.php">Limpiar</a>
        </div>

        <!-- paginación mantiene estado -->
        <input type="hidden" name="page" value="<?php echo (int)$page; ?>">
        <input type="hidden" name="per_page" value="<?php echo (int)$perPage; ?>">
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm table-striped ap-oc-table">
          <thead>
            <tr>
              <th style="width:280px">Acciones</th>
              <th>ID</th>
              <th>Folio OC</th>
              <th>Fecha</th>
              <th>Proveedor</th>
              <th>Protocolo</th>
              <th>Almacén</th>
              <th>Partidas</th>
              <th class="text-end">Cant</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
          <?php if(!$ocs): ?>
            <tr><td colspan="10" class="text-center text-muted">Sin resultados.</td></tr>
          <?php else: foreach($ocs as $r):
            $fecha = '';
            try{ $fecha=(new DateTime($r['fech_pedimento']))->format('d/m/Y'); } catch(Throwable $e){}
          ?>
            <tr>
              <td>
                <div class="btn-group btn-group-sm">
                  <button type="button" class="btn btn-outline-primary btn-ver" data-id="<?php echo (int)$r['ID_Aduana']; ?>">Ver</button>
                  <a class="btn btn-outline-secondary" href="orden_compra_edit.php?id_aduana=<?php echo (int)$r['ID_Aduana']; ?>">Editar</a>
                  <a class="btn btn-outline-success" target="_blank" href="orden_compra_pdf.php?id_aduana=<?php echo (int)$r['ID_Aduana']; ?>">PDF</a>
                  <a class="btn btn-outline-success" target="_blank" href="orden_compra_pdf_sin_costos.php?id_aduana=<?php echo (int)$r['ID_Aduana']; ?>">PDF s/c</a>
                  <a class="btn btn-outline-dark" href="recepcion_materiales.php?id_aduana=<?php echo (int)$r['ID_Aduana']; ?>">Recibir</a>
                </div>
              </td>
              <td><?php echo (int)$r['ID_Aduana']; ?></td>
              <td><?php echo h((string)$r['FolioOC']); ?></td>
              <td><?php echo h($fecha); ?></td>
              <td><?php echo h((string)$r['proveedor']); ?></td>
              <td><?php echo h((string)$r['protocolo_clave']); ?></td>
              <td><?php echo h((string)$r['Cve_Almac']); ?></td>
              <td><?php echo (int)$r['partidas']; ?></td>
              <td class="text-end"><?php echo number_format((float)$r['cant_total'],4); ?></td>
              <td><?php echo h((string)$r['status']); ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <?php
        // conserva filtros para links del paginador
        $qs = $_GET;
      ?>
      <div class="d-flex justify-content-between align-items-center mt-2" style="font-size:11px;">
        <div class="text-muted">
          Mostrando página <b><?php echo (int)$page; ?></b> de <b><?php echo (int)$totalPages; ?></b> — Total: <b><?php echo (int)$totalRows; ?></b>
        </div>

        <div class="d-flex gap-1 align-items-center">
          <?php
            $qs['page'] = 1;
            $firstUrl = 'orden_compra.php?' . http_build_query($qs);

            $qs['page'] = max(1,$page-1);
            $prevUrl = 'orden_compra.php?' . http_build_query($qs);

            $qs['page'] = min($totalPages,$page+1);
            $nextUrl = 'orden_compra.php?' . http_build_query($qs);

            $qs['page'] = $totalPages;
            $lastUrl = 'orden_compra.php?' . http_build_query($qs);
          ?>
          <a class="btn btn-sm btn-outline-secondary" href="<?php echo h($firstUrl); ?>">«</a>
          <a class="btn btn-sm btn-outline-secondary" href="<?php echo h($prevUrl); ?>">‹</a>

          <form method="get" class="d-flex align-items-center gap-1" style="margin:0;">
            <?php foreach($_GET as $k=>$v):
              if ($k==='page' || $k==='per_page') continue; ?>
              <input type="hidden" name="<?php echo h($k); ?>" value="<?php echo h($v); ?>">
            <?php endforeach; ?>
            <input type="number" name="page" min="1" max="<?php echo (int)$totalPages; ?>" value="<?php echo (int)$page; ?>"
                   class="form-control form-control-sm" style="width:70px;">
            <select name="per_page" class="form-select form-select-sm" style="width:90px;">
              <?php foreach([25,50,100,200] as $pp): ?>
                <option value="<?php echo (int)$pp; ?>" <?php echo ($perPage===$pp)?'selected':''; ?>><?php echo (int)$pp; ?>/p</option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-primary">Ir</button>
          </form>

          <a class="btn btn-sm btn-outline-secondary" href="<?php echo h($nextUrl); ?>">›</a>
          <a class="btn btn-sm btn-outline-secondary" href="<?php echo h($lastUrl); ?>">»</a>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Modal Ver (sin iframe) -->
<div class="modal fade" id="mdlOc" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header py-2">
        <div>
          <div style="font-weight:900;color:#0F5AAD;font-size:14px;">Resumen OC</div>
          <div class="text-muted" style="font-size:11px;">Encabezado + partidas (vista operativa)</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" style="min-height:60vh;">
        <div id="mdlBodyOc" style="font-size:11px;">
          <div class="text-muted">Cargando...</div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', ()=>{
  const modal = new bootstrap.Modal(document.getElementById('mdlOc'));
  const body  = document.getElementById('mdlBodyOc');

  document.querySelectorAll('.btn-ver').forEach(btn=>{
    btn.addEventListener('click', async ()=>{
      const id = btn.getAttribute('data-id');
      body.innerHTML = '<div class="text-muted">Cargando...</div>';
      modal.show();
      try{
        const res = await fetch('orden_compra_ver.php?id_aduana='+encodeURIComponent(id), {cache:'no-store'});
        const html = await res.text();
        body.innerHTML = html;
      }catch(e){
        body.innerHTML = '<div class="text-danger">No se pudo cargar el resumen.</div>';
      }
    });
  });
});
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
