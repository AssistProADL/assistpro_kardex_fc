<?php
// /public/ingresos/ingresos_admin.php

if (isset($_GET['ajax'])) {
    require_once __DIR__ . '/../../app/db.php';
    header('Content-Type: application/json; charset=utf-8');

    $act = $_GET['ajax'] ?? '';

    try {
        $pdo = db_pdo();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'error' => 'Sin conexión a BD: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ✅ Empresas: tomamos cve_cia desde c_almacenp (evita empresa_id inexistente en algunos entornos)
    if ($act === 'empresas') {
        try {
            $rows = $pdo->query("
                SELECT DISTINCT
                    TRIM(COALESCE(cve_cia,'')) AS id,
                    TRIM(COALESCE(cve_cia,'')) AS cve_cia,
                    CONCAT('CIA ', TRIM(COALESCE(cve_cia,''))) AS nombre
                FROM c_almacenp
                WHERE COALESCE(cve_cia,'') <> ''
                ORDER BY TRIM(COALESCE(cve_cia,''))
            ")->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'error' => 'Error al cargar empresas: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // ✅ Almacenes filtrados por cve_cia
    if ($act === 'almacenes') {
        $cia = trim($_GET['empresa_id'] ?? ''); // (mantenemos nombre del parámetro para no tocar el JS)
        $sql = "
            SELECT
                TRIM(COALESCE(id,''))    AS id,
                TRIM(COALESCE(clave,'')) AS clave,
                TRIM(COALESCE(nombre,'')) AS nombre,
                TRIM(COALESCE(cve_cia,'')) AS cve_cia
            FROM c_almacenp
            WHERE 1=1
        ";
        $params = [];
        if ($cia !== '') {
            $sql .= " AND TRIM(COALESCE(cve_cia,'')) = :cia ";
            $params[':cia'] = $cia;
        }
        $sql .= " ORDER BY TRIM(COALESCE(clave,'')) ";

        try {
            $st = $pdo->prepare($sql);
            $st->execute($params);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'error' => 'Error al cargar almacenes: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // ✅ Buscar ingresos (evita cc.nombre / ap.empresa_id)
    if ($act === 'buscar') {
        $tipo      = trim($_GET['tipo'] ?? '');
        $cia       = trim($_GET['empresa_id'] ?? '');
        $almacen   = trim($_GET['almacen_id'] ?? '');
        $q         = trim($_GET['q'] ?? '');
        $fini      = trim($_GET['fini'] ?? '');
        $ffin      = trim($_GET['ffin'] ?? '');

        $where = [];
        $params = [];

        if ($tipo !== '') { $where[] = 'h.tipo = :tipo'; $params[':tipo'] = $tipo; }

        // join a c_almacenp por clave/id para poder filtrar cia/almacen de forma robusta
        if ($cia !== '') { $where[] = "TRIM(COALESCE(ap.cve_cia,'')) = :cia"; $params[':cia'] = $cia; }

        if ($almacen !== '') {
            $where[] = "(TRIM(COALESCE(ap.id,'')) = :alm OR TRIM(COALESCE(ap.clave,'')) = :alm OR TRIM(COALESCE(h.Cve_Almac,'')) = :alm)";
            $params[':alm'] = $almacen;
        }

        if ($q !== '') {
            $where[] = "(h.Fol_Folio LIKE :q OR h.Factura LIKE :q OR h.Cve_Proveedor LIKE :q OR ap.nombre LIKE :q OR ap.clave LIKE :q)";
            $params[':q'] = "%{$q}%";
        }

        if ($fini !== '') { $where[] = "DATE(h.Fec_Entrada) >= STR_TO_DATE(:fini,'%Y-%m-%d')"; $params[':fini'] = $fini; }
        if ($ffin !== '') { $where[] = "DATE(h.Fec_Entrada) <= STR_TO_DATE(:ffin,'%Y-%m-%d')"; $params[':ffin'] = $ffin; }

        $w = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Nota: th_aduana es tu encabezado de ingresos (OC/RL/CD en legacy).
        // Si tu tabla real cambia, solo ajustamos FROM/joins sin tocar el front.
        $sql = "
            SELECT
                h.ID_Aduana AS id,
                h.Fol_Folio AS folio,
                DATE_FORMAT(h.Fec_Entrada, '%Y-%m-%d %H:%i:%s') AS fecha,
                h.tipo AS tipo_entrada,
                h.STATUS AS estatus,
                TRIM(COALESCE(ap.cve_cia,'')) AS empresa_id,
                CONCAT('CIA ', TRIM(COALESCE(ap.cve_cia,''))) AS empresa_nombre,
                CONCAT(TRIM(COALESCE(ap.clave,'')),' / ',TRIM(COALESCE(ap.nombre,''))) AS almacen_nombre,
                COALESCE(h.Cve_Proveedor, '') AS proveedor,
                COALESCE(h.Factura,'') AS factura,
                COALESCE(h.cve_usuario,'') AS usuario_crea,
                (
                    SELECT COALESCE(SUM(d.cantidad),0)
                    FROM td_aduana d
                    WHERE d.ID_Aduana = h.ID_Aduana
                ) AS total_pzas,
                (
                    SELECT COALESCE(SUM(d.cantidad * COALESCE(d.costo,0)),0)
                    FROM td_aduana d
                    WHERE d.ID_Aduana = h.ID_Aduana
                ) AS total_importe
            FROM th_aduana h
            LEFT JOIN c_almacenp ap
              ON (TRIM(COALESCE(ap.clave,'')) = TRIM(COALESCE(h.Cve_Almac,'')) OR TRIM(COALESCE(ap.id,'')) = TRIM(COALESCE(h.Cve_Almac,'')))
            $w
            ORDER BY h.ID_Aduana DESC
            LIMIT 500
        ";

        try {
            $st = $pdo->prepare($sql);
            $st->execute($params);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'error' => 'Error al buscar ingresos: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    echo json_encode(['ok' => false, 'error' => 'Acción no soportada'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ================= UI =================
include __DIR__ . '/../bi/_menu_global.php';
?>
<div class="container-fluid" style="font-size:10px;">

  <div class="card shadow-sm mt-2">
    <div class="card-header d-flex justify-content-between align-items-center" style="background:#0F5AAD;color:#fff;">
      <div>
        <div class="fw-semibold">Administración de Ingresos</div>
        <div style="font-size:9px;opacity:.85;">Control de ingresos por OC, RL, CrossDocking y otros tipos de entrada.</div>
      </div>
      <button id="btnRefrescar" class="btn btn-outline-light btn-sm">Refrescar</button>
    </div>

    <div class="card-body">

      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label mb-0">Empresa</label>
          <select id="cboEmpresa" class="form-select form-select-sm">
            <option value="">(opcional)</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label mb-0">Almacén</label>
          <select id="cboAlmacen" class="form-select form-select-sm">
            <option value="">Todos</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label mb-0">Tipo de ingreso</label>
          <select id="tipo" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option value="OC">OC</option>
            <option value="RL">RL</option>
            <option value="CD">CrossDocking</option>
            <option value="TR">Traslados</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label mb-0">Desde</label>
          <input id="fini" type="date" class="form-control form-control-sm">
        </div>

        <div class="col-md-2">
          <label class="form-label mb-0">Hasta</label>
          <input id="ffin" type="date" class="form-control form-control-sm">
        </div>

        <div class="col-md-9">
          <label class="form-label mb-0">Buscar</label>
          <input id="txtBuscar" class="form-control form-control-sm" placeholder="Folio, proveedor, proyecto...">
        </div>

        <div class="col-md-3 d-flex gap-2">
          <button id="btnBuscar" class="btn btn-primary btn-sm w-100">Buscar</button>
          <button class="btn btn-outline-secondary btn-sm w-100" onclick="location.href='recepcion_materiales.php'">+ Nueva Recepción</button>
        </div>
      </div>

      <hr class="my-2">

      <div class="row g-2">
        <div class="col-md-4">
          <div class="card shadow-sm" style="border-left:4px solid #0F5AAD;">
            <div class="card-body py-2">
              <div class="text-muted">Ingresos encontrados</div>
              <div class="fw-bold" id="kpiCount">0</div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card shadow-sm" style="border-left:4px solid #22c55e;">
            <div class="card-body py-2">
              <div class="text-muted">Total piezas</div>
              <div class="fw-bold" id="kpiPzas">0</div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card shadow-sm" style="border-left:4px solid #f59e0b;">
            <div class="card-body py-2">
              <div class="text-muted">Importe total</div>
              <div class="fw-bold" id="kpiImp">0.00</div>
            </div>
          </div>
        </div>
      </div>

      <div class="table-responsive mt-2">
        <table class="table table-sm table-bordered" id="tblIngresos" style="font-size:10px; width:100%;">
          <thead class="table-light">
          <tr>
            <th>Folio</th>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Estatus</th>
            <th>Empresa</th>
            <th>Almacén</th>
            <th class="text-end">Total piezas</th>
            <th class="text-end">Importe total</th>
            <th>Usuario</th>
            <th>Acciones</th>
          </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

    </div>
  </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script>
let dtIngresos = null;

$(document).ready(function () {
  dtIngresos = $('#tblIngresos').DataTable({
    pageLength: 25,
    lengthChange: false,
    searching: false,
    ordering: true,
    info: true,
    scrollX: true,
    language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-MX.json' },
    columnDefs: [{ targets: [6,7], className:'text-end' }, { targets:[9], orderable:false }]
  });

  cargarEmpresas();
  cargarAlmacenes();
  buscarIngresos();

  $('#btnBuscar').on('click', buscarIngresos);
  $('#btnRefrescar').on('click', buscarIngresos);
  $('#cboEmpresa').on('change', function(){ cargarAlmacenes(); });
  $('#txtBuscar').on('keyup', function(e){ if(e.key==='Enter') buscarIngresos(); });
});

function cargarEmpresas(){
  $.getJSON('ingresos_admin.php', {ajax:'empresas'}, function(r){
    if(!r || !r.ok) return;
    const $cbo = $('#cboEmpresa');
    $cbo.empty().append('<option value="">(opcional)</option>');
    (r.data||[]).forEach(x=>{
      $cbo.append($('<option>',{ value:x.id, text:x.nombre }));
    });
  });
}

function cargarAlmacenes(){
  $.getJSON('ingresos_admin.php', {ajax:'almacenes', empresa_id:$('#cboEmpresa').val()}, function(r){
    if(!r || !r.ok) return;
    const $cbo = $('#cboAlmacen');
    $cbo.empty().append('<option value="">Todos</option>');
    (r.data||[]).forEach(x=>{
      $cbo.append($('<option>',{ value:x.id || x.clave, text:(x.clave||'')+' / '+(x.nombre||'') }));
    });
  });
}

function buscarIngresos(){
  const tipo = $('#tipo').val();
  const empresa_id = $('#cboEmpresa').val();
  const almacen_id = $('#cboAlmacen').val();
  const q = $('#txtBuscar').val();
  const fini = $('#fini').val();
  const ffin = $('#ffin').val();

  $.getJSON('ingresos_admin.php', {
    ajax:'buscar', tipo, empresa_id, almacen_id, q, fini, ffin
  }, function(r){
    dtIngresos.clear();
    let count=0, pzas=0, imp=0;

    if(r && r.ok){
      (r.data||[]).forEach(row=>{
        count++;
        pzas += Number(row.total_pzas||0);
        imp  += Number(row.total_importe||0);

        dtIngresos.row.add([
          row.folio||'',
          row.fecha||'',
          '<span class="badge bg-primary">'+(row.tipo_entrada||'')+'</span>',
          row.estatus||'',
          row.empresa_nombre||'',
          row.almacen_nombre||'',
          Number(row.total_pzas||0).toLocaleString(),
          Number(row.total_importe||0).toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2}),
          row.usuario_crea||'',
          '<a class="btn btn-outline-primary btn-sm" href="recepcion_materiales.php"><i class="fa fa-eye"></i></a>'
        ]);
      });
    }

    dtIngresos.draw();

    $('#kpiCount').text(count.toLocaleString());
    $('#kpiPzas').text(pzas.toLocaleString());
    $('#kpiImp').text(imp.toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2}));
  }).fail(function(xhr){
    alert('Error al buscar ingresos: '+(xhr.responseText||''));
  });
}
</script>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>
