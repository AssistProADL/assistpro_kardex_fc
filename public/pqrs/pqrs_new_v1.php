<?php
// /public/pqrs/pqrs_new.php
require_once __DIR__ . '/../bi/_menu_global.php';
require_once __DIR__ . '/../../app/db.php';

db_pdo();
global $pdo;

// ===============================
// Helpers
// ===============================
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function next_clave_pqrs(PDO $pdo): string {
  // Genera: PQRS-YYYYMMDD-0001 (respetando UNIQUE(clave))
  $ymd = date('Ymd');
  $pref = "PQRS-$ymd-";
  $st = $pdo->prepare("SELECT COUNT(*) FROM th_incidencia WHERE clave LIKE ?");
  $st->execute([$pref.'%']);
  $n = (int)$st->fetchColumn() + 1;
  return sprintf("%s%04d", $pref, $n);
}

// ===============================
// Catálogos (mínimos para UI)
// ===============================
$TIPO_REPORTE = [
  'P' => 'Petición',
  'Q' => 'Queja',
  'R' => 'Reclamo',
  'S' => 'Sugerencia',
];

$STATUS = [
  'A' => 'Abierto',
  'P' => 'En proceso',
  'E' => 'En espera',
  'C' => 'Cerrado',
];

// Almacenes/CEDIS desde c_almacenp (tu tabla)
$almacenes = [];
try{
  $st = $pdo->query("SELECT id, clave, nombre FROM c_almacenp WHERE COALESCE(Activo,1)=1 ORDER BY nombre");
  $almacenes = $st->fetchAll(PDO::FETCH_ASSOC);
}catch(Throwable $e){
  $almacenes = [];
}

// ===============================
// POST: Guardar incidencia
// ===============================
$ok = null; $err = null; $new_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    // Campos
    $ID_Incidencia = null;

    $clave = trim($_POST['clave'] ?? '');
    if ($clave === '') $clave = next_clave_pqrs($pdo);

    $Fecha = trim($_POST['Fecha'] ?? '');
    if ($Fecha === '') $Fecha = date('Y-m-d H:i:s');

    $Fol_folio = trim($_POST['Fol_folio'] ?? '');
    $centro_distribucion = trim($_POST['centro_distribucion'] ?? '');

    $cliente = trim($_POST['cliente'] ?? ''); // aquí guardamos Cve_Clte o texto (recomendado Cve_Clte)
    $cliente_texto = trim($_POST['cliente_texto'] ?? ''); // para UI, opcional

    $reportador = trim($_POST['reportador'] ?? '');
    $cargo_reportador = trim($_POST['cargo_reportador'] ?? '');

    $responsable_recibo = trim($_POST['responsable_recibo'] ?? '');
    $responsable_caso = trim($_POST['responsable_caso'] ?? '');

    $ReportadoCas = trim($_POST['ReportadoCas'] ?? ''); // legacy
    $Descripcion = trim($_POST['Descripcion'] ?? '');
    $Respuesta = trim($_POST['Respuesta'] ?? '');

    $plan_accion = trim($_POST['plan_accion'] ?? '');
    $responsable_plan = trim($_POST['responsable_plan'] ?? '');
    $Fecha_accion = trim($_POST['Fecha_accion'] ?? '');
    $responsable_verificacion = trim($_POST['responsable_verificacion'] ?? '');

    $tipo_reporte = trim($_POST['tipo_reporte'] ?? ''); // P/Q/R/S
    $status = trim($_POST['status'] ?? 'A');

    $id_motivo_registro = (int)($_POST['id_motivo_registro'] ?? 0);
    $desc_motivo_registro = trim($_POST['desc_motivo_registro'] ?? '');

    $id_motivo_cierre = (int)($_POST['id_motivo_cierre'] ?? 0);
    $desc_motivo_cierre = trim($_POST['desc_motivo_cierre'] ?? '');

    // Validación ejecutiva mínima
    if ($tipo_reporte === '') throw new RuntimeException("Selecciona Tipo de Reporte.");
    if ($cliente === '' && $cliente_texto === '') throw new RuntimeException("Selecciona/indica Cliente.");
    if ($Descripcion === '') throw new RuntimeException("La descripción es obligatoria.");
    if (!isset($STATUS[$status])) $status = 'A';

    // En BD guardamos cliente en campo `cliente` (ideal: Cve_Clte)
    // Si por UI mandan cliente_texto, lo usamos como fallback.
    $cliente_final = ($cliente !== '') ? $cliente : $cliente_texto;

    // Insert
    $sql = "INSERT INTO th_incidencia
      (Fol_folio, ReportadoCas, Descripcion, Respuesta, status, Fecha, Activo, clave,
       centro_distribucion, cliente, reportador, cargo_reportador, responsable_recibo, responsable_caso,
       plan_accion, responsable_plan, Fecha_accion, responsable_verificacion,
       tipo_reporte, id_motivo_registro, desc_motivo_registro, id_motivo_cierre, desc_motivo_cierre)
      VALUES
      (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

    $st = $pdo->prepare($sql);
    $st->execute([
      ($Fol_folio !== '' ? $Fol_folio : null),
      ($ReportadoCas !== '' ? $ReportadoCas : null),
      $Descripcion,
      ($Respuesta !== '' ? $Respuesta : null),
      $status,
      $Fecha,
      1,
      $clave,
      ($centro_distribucion !== '' ? $centro_distribucion : null),
      ($cliente_final !== '' ? $cliente_final : null),
      ($reportador !== '' ? $reportador : null),
      ($cargo_reportador !== '' ? $cargo_reportador : null),
      ($responsable_recibo !== '' ? $responsable_recibo : null),
      ($responsable_caso !== '' ? $responsable_caso : null),
      ($plan_accion !== '' ? $plan_accion : null),
      ($responsable_plan !== '' ? $responsable_plan : null),
      ($Fecha_accion !== '' ? $Fecha_accion : null),
      ($responsable_verificacion !== '' ? $responsable_verificacion : null),
      $tipo_reporte,
      ($id_motivo_registro > 0 ? $id_motivo_registro : null),
      ($desc_motivo_registro !== '' ? $desc_motivo_registro : null),
      ($id_motivo_cierre > 0 ? $id_motivo_cierre : null),
      ($desc_motivo_cierre !== '' ? $desc_motivo_cierre : null),
    ]);

    $new_id = (int)$pdo->lastInsertId();
    $ok = "Guardado exitoso. Folio PQRS: {$clave}";
  } catch (Throwable $e) {
    $err = $e->getMessage();
  }
}

// Valores por defecto en pantalla
$default_clave = next_clave_pqrs($pdo);
$default_fecha = date('Y-m-d H:i:s');
$usuario_sesion = $_SESSION['usuario'] ?? 'Usuario WMS Admin';
?>
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0">Nueva Incidencia (PQRS)</h3>
    <a class="btn btn-outline-secondary" href="pqrs.php"><i class="fas fa-arrow-left"></i> Regresar</a>
  </div>

  <?php if ($ok): ?>
    <div class="alert alert-success d-flex justify-content-between align-items-center">
      <div><i class="fas fa-check-circle"></i> <?= h($ok) ?></div>
      <?php if ($new_id): ?>
        <a class="btn btn-sm btn-success" href="pqrs_view.php?id=<?= (int)$new_id ?>">
          Ver incidencia <i class="fas fa-eye"></i>
        </a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($err): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= h($err) ?></div>
  <?php endif; ?>

  <form method="POST" id="frmPQRS">
    <!-- Encabezado (legacy-like) -->
    <div class="card mb-3">
      <div class="card-body">
        <div class="row">
          <div class="col-md-3">
            <label class="font-weight-bold">Clave PQRS</label>
            <input type="text" class="form-control" name="clave" value="<?= h($_POST['clave'] ?? $default_clave) ?>" />
          </div>

          <div class="col-md-3">
            <label class="font-weight-bold">Fecha Reporte</label>
            <input type="text" class="form-control" name="Fecha" value="<?= h($_POST['Fecha'] ?? $default_fecha) ?>" />
            <small class="text-muted">Formato: YYYY-mm-dd HH:ii:ss</small>
          </div>

          <div class="col-md-3">
            <label class="font-weight-bold">Almacén / CEDIS</label>
            <select class="form-control" name="centro_distribucion" id="centro_distribucion">
              <option value="">Seleccione</option>
              <?php foreach($almacenes as $a): 
                $val = trim(($a['clave'] ?? '').' - '.($a['nombre'] ?? ''));
                $sel = (($val === ($_POST['centro_distribucion'] ?? '')) ? 'selected' : '');
              ?>
                <option <?= $sel ?> value="<?= h($val) ?>"><?= h($val) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-3">
            <label class="font-weight-bold">Responsable de recibir la PQRS</label>
            <input type="text" class="form-control" name="responsable_recibo"
                   value="<?= h($_POST['responsable_recibo'] ?? $usuario_sesion) ?>" />
          </div>
        </div>

        <hr/>

        <!-- Cliente + Pedido -->
        <div class="row">
          <div class="col-md-4">
            <label class="font-weight-bold">Cliente</label>
            <select class="form-control" name="cliente" id="cliente"></select>
            <input type="hidden" name="cliente_texto" id="cliente_texto"
                   value="<?= h($_POST['cliente_texto'] ?? '') ?>">
            <small class="text-muted">Busca por clave, razón social o RFC.</small>
          </div>

          <div class="col-md-4">
            <label class="font-weight-bold">Número de Folio / Pedido</label>
            <div class="input-group">
              <input type="text" class="form-control" name="Fol_folio" id="Fol_folio"
                     value="<?= h($_POST['Fol_folio'] ?? '') ?>" placeholder="Ej. PED-20260128-000123" />
              <div class="input-group-append">
                <button type="button" class="btn btn-primary" id="btnBuscarPedido">
                  <i class="fas fa-search"></i> Buscar Pedido
                </button>
              </div>
            </div>
            <small class="text-muted">Opción 2: consulta directa al API de pedidos.</small>
          </div>

          <div class="col-md-4">
            <label class="font-weight-bold">Tipo de Reporte</label>
            <select class="form-control" name="tipo_reporte" required>
              <option value="">Seleccione</option>
              <?php foreach($TIPO_REPORTE as $k=>$txt):
                $sel = (($k === ($_POST['tipo_reporte'] ?? '')) ? 'selected' : '');
              ?>
                <option value="<?= h($k) ?>" <?= $sel ?>><?= h($txt) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

      </div>
    </div>

    <!-- Registro Incidencia -->
    <div class="card mb-3">
      <div class="card-header"><strong>Registro de Incidencia</strong></div>
      <div class="card-body">

        <div class="row">
          <div class="col-md-4">
            <label class="font-weight-bold">Motivo de Registro (ID)</label>
            <input type="number" class="form-control" name="id_motivo_registro"
                   value="<?= h($_POST['id_motivo_registro'] ?? '') ?>" placeholder="Ej. 10" />
          </div>

          <div class="col-md-8">
            <label class="font-weight-bold">Motivo de Registro (Descripción)</label>
            <input type="text" class="form-control" name="desc_motivo_registro"
                   value="<?= h($_POST['desc_motivo_registro'] ?? '') ?>" placeholder="Ej. Faltante / Daño / Retraso..." />
          </div>
        </div>

        <div class="mt-3">
          <label class="font-weight-bold">Descripción de Registro de Incidencia</label>
          <textarea class="form-control" name="Descripcion" rows="4" required
                    placeholder="Describe el caso con hechos, evidencia y alcance."><?= h($_POST['Descripcion'] ?? '') ?></textarea>
        </div>

        <div class="row mt-3">
          <div class="col-md-6">
            <label class="font-weight-bold">Reportador</label>
            <input type="text" class="form-control" name="reportador"
                   value="<?= h($_POST['reportador'] ?? '') ?>" />
          </div>
          <div class="col-md-6">
            <label class="font-weight-bold">Cargo del Reportador</label>
            <input type="text" class="form-control" name="cargo_reportador"
                   value="<?= h($_POST['cargo_reportador'] ?? '') ?>" />
          </div>
        </div>

      </div>
    </div>

    <!-- Plan de acción / Seguimiento -->
    <div class="card mb-3">
      <div class="card-header"><strong>Acciones | Solución</strong></div>
      <div class="card-body">

        <div class="row">
          <div class="col-md-6">
            <label class="font-weight-bold">Responsable del Caso</label>
            <input type="text" class="form-control" name="responsable_caso"
                   value="<?= h($_POST['responsable_caso'] ?? $usuario_sesion) ?>" />
          </div>
          <div class="col-md-6">
            <label class="font-weight-bold">Responsable del Plan de Acción</label>
            <input type="text" class="form-control" name="responsable_plan"
                   value="<?= h($_POST['responsable_plan'] ?? '') ?>" />
          </div>
        </div>

        <div class="mt-3">
          <label class="font-weight-bold">Plan de Acción</label>
          <textarea class="form-control" name="plan_accion" rows="3"
                    placeholder="Acción correctiva, preventiva y responsable."><?= h($_POST['plan_accion'] ?? '') ?></textarea>
        </div>

        <div class="row mt-3">
          <div class="col-md-4">
            <label class="font-weight-bold">Fecha Acción</label>
            <input type="text" class="form-control" name="Fecha_accion"
                   value="<?= h($_POST['Fecha_accion'] ?? '') ?>" placeholder="YYYY-mm-dd HH:ii:ss" />
          </div>
          <div class="col-md-4">
            <label class="font-weight-bold">Responsable Verificación</label>
            <input type="text" class="form-control" name="responsable_verificacion"
                   value="<?= h($_POST['responsable_verificacion'] ?? '') ?>" />
          </div>
          <div class="col-md-4">
            <label class="font-weight-bold">Status</label>
            <select class="form-control" name="status">
              <?php foreach($STATUS as $k=>$txt):
                $sel = (($k === ($_POST['status'] ?? 'A')) ? 'selected' : '');
              ?>
                <option value="<?= h($k) ?>" <?= $sel ?>><?= h($txt) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="mt-3">
          <label class="font-weight-bold">Respuesta / Cierre Operativo</label>
          <textarea class="form-control" name="Respuesta" rows="3"
                    placeholder="Qué se resolvió, evidencia, compromiso y fecha."><?= h($_POST['Respuesta'] ?? '') ?></textarea>
        </div>

      </div>
    </div>

    <!-- Cierre -->
    <div class="card mb-4">
      <div class="card-header"><strong>Validación | Resultados</strong></div>
      <div class="card-body">

        <div class="row">
          <div class="col-md-4">
            <label class="font-weight-bold">Motivo de Cierre (ID)</label>
            <input type="number" class="form-control" name="id_motivo_cierre"
                   value="<?= h($_POST['id_motivo_cierre'] ?? '') ?>" />
          </div>

          <div class="col-md-8">
            <label class="font-weight-bold">Motivo de Cierre (Descripción)</label>
            <input type="text" class="form-control" name="desc_motivo_cierre"
                   value="<?= h($_POST['desc_motivo_cierre'] ?? '') ?>" />
          </div>
        </div>

        <small class="text-muted d-block mt-2">
          Nota: si dejas status en “Abierto”, el motivo de cierre puede ir vacío.
        </small>

      </div>
    </div>

    <div class="d-flex justify-content-end mb-5">
      <a class="btn btn-outline-secondary mr-2" href="pqrs.php">Cerrar</a>
      <button class="btn btn-success" type="submit">
        <i class="fas fa-save"></i> Guardar
      </button>
    </div>

  </form>
</div>

<!-- JS: Opción 2 (Pedido -> autollenar cliente) + Cliente autocomplete -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
(function(){
  // Rutas
  const API_PEDIDOS = "../api/pedidos/pedidos_api.php"; // contiene action=clientes/consultar/listar etc. :contentReference[oaicite:3]{index=3}

  // Select2 Cliente (busca en c_cliente)
  $('#cliente').select2({
    placeholder: 'Seleccione / busque cliente',
    allowClear: true,
    width: '100%',
    ajax: {
      url: API_PEDIDOS,
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return { action:'clientes', q: params.term || '', limit: 20 };
      },
      processResults: function (data) {
        if (!data || !data.ok) return { results: [] };
        const results = (data.rows || []).map(r => ({
          id: r.Cve_Clte,
          text: `${r.Cve_Clte} - ${r.RazonSocial || ''}`.trim(),
          raw: r
        }));
        return { results };
      }
    }
  }).on('select2:select', function(e){
    const d = e.params.data;
    $('#cliente_texto').val(d.text || '');
  }).on('select2:clear', function(){
    $('#cliente_texto').val('');
  });

  // Precarga si viene por POST (cuando falla validación y recarga)
  <?php if (!empty($_POST['cliente'])): ?>
    (function(){
      const cve = <?= json_encode((string)$_POST['cliente']) ?>;
      const txt = <?= json_encode((string)($_POST['cliente_texto'] ?? $_POST['cliente'])) ?>;
      if (cve) {
        const opt = new Option(txt, cve, true, true);
        $('#cliente').append(opt).trigger('change');
      }
    })();
  <?php endif; ?>

  // Buscar Pedido (Opción 2): consultar header por Folio
  $('#btnBuscarPedido').on('click', async function(){
    const folio = ($('#Fol_folio').val() || '').trim();
    if (!folio) { alert('Captura un Folio/Pedido.'); return; }

    try{
      const url = API_PEDIDOS + '?action=consultar&Fol_folio=' + encodeURIComponent(folio);
      const res = await fetch(url, { credentials:'same-origin' });
      const data = await res.json();

      if (!data || !data.ok) {
        alert((data && data.error) ? data.error : 'No se pudo consultar el pedido.');
        return;
      }

      const h = data.header || {};
      // Header trae Cve_clte y (si existe join en API listar, aquí no) tal vez no traiga razón social.
      const cve = (h.Cve_clte || '').trim();
      if (cve) {
        // Para mostrar texto bonito, hacemos búsqueda rápida de ese cliente
        const cUrl = API_PEDIDOS + '?action=clientes&q=' + encodeURIComponent(cve) + '&limit=5';
        const cRes = await fetch(cUrl, { credentials:'same-origin' });
        const cData = await cRes.json();

        let text = cve;
        if (cData && cData.ok && (cData.rows||[]).length){
          const r = cData.rows.find(x => (x.Cve_Clte||'') === cve) || cData.rows[0];
          if (r) text = `${r.Cve_Clte} - ${r.RazonSocial || ''}`.trim();
        }

        const opt = new Option(text, cve, true, true);
        $('#cliente').append(opt).trigger('change');
        $('#cliente_texto').val(text);
      }

      // Sugerir almacén si el pedido trae cve_almac
      // Nota: tu th_incidencia guarda texto en centro_distribucion, aquí solo sugerimos.
      // Si quieres mapear cve_almac -> nombre, lo hacemos en una iteración.
      if (h.cve_almac) {
        // no forzamos, solo dejamos comentario visual
        console.log('Pedido almacén:', h.cve_almac);
      }

      alert('Pedido encontrado. Cliente autocompletado.');

    }catch(err){
      console.error(err);
      alert('Error consultando pedido.');
    }
  });

})();
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
