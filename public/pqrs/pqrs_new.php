<?php
// =====================================================
// PQRS - Nuevo Caso (v2)
// Ruta: /public/pqrs/pqrs_new.php
// =====================================================

// Importante: para que funcionen header() aunque el menú imprima HTML
ob_start();

require_once __DIR__ . '/../bi/_menu_global.php';
require_once __DIR__ . '/../../app/db.php';
db_pdo();
global $pdo;

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// API endpoints
$API_PQRS    = "pqrs_api.php";
$API_PEDIDOS = "../api/pedidos/pedidos_api.php"; // tu API de clientes (action=clientes)

// Almacenes (clave de negocio)
$almacenes = [];
try {
  $st = $pdo->query("SELECT clave, nombre FROM c_almacenp WHERE COALESCE(Activo,1)=1 ORDER BY nombre");
  $almacenes = $st->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e){ $almacenes=[]; }

// Catálogos PQRS (status/tipo/ref/motivos)
$cats = ['status'=>[], 'tipo'=>[], 'ref'=>[], 'motivos'=>['REGISTRO'=>[]]];
try {
  $json = @file_get_contents($API_PQRS . "?action=catalogos");
  $cats = json_decode($json ?: '{}', true) ?: $cats;
} catch(Throwable $e){}

// Mensajes PRG
$ok = (isset($_GET['ok']) && $_GET['ok']=='1');
$folio_ok = trim((string)($_GET['folio'] ?? ''));

// ------------------------------
// POST: Guardar (PRG)
// ------------------------------
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $payload = [
      'cve_clte'           => trim($_POST['cve_clte'] ?? ''),
      'cve_almacen'        => trim($_POST['cve_almacen'] ?? ''),
      'tipo'               => trim($_POST['tipo'] ?? ''),
      'ref_tipo'           => trim($_POST['ref_tipo'] ?? ''),
      'ref_folio'          => trim($_POST['ref_folio'] ?? ''),
      'reporta_nombre'     => trim($_POST['reporta_nombre'] ?? ''),
      'reporta_contacto'   => trim($_POST['reporta_contacto'] ?? ''),
      'reporta_cargo'      => trim($_POST['reporta_cargo'] ?? ''),
      'responsable_recibo' => trim($_POST['responsable_recibo'] ?? ''),
      'responsable_accion' => trim($_POST['responsable_accion'] ?? ''),
      'asunto'             => trim($_POST['asunto'] ?? ''),
      'descripcion'        => trim($_POST['descripcion'] ?? ''),
      'motivo_registro_id' => (int)($_POST['motivo_registro_id'] ?? 0),
      'motivo_registro_txt'=> trim($_POST['motivo_registro_txt'] ?? ''),
      'susceptible_cobro'  => (int)($_POST['susceptible_cobro'] ?? 0) ? 1 : 0,
      'monto_estimado'     => trim($_POST['monto_estimado'] ?? ''),
      'status_clave'       => trim($_POST['status_clave'] ?? 'NUEVA'),
    ];

    // Validación mínima UI (la API vuelve a validar)
    if ($payload['cve_clte']==='') $errors[] = "Cliente es obligatorio.";
    if ($payload['cve_almacen']==='') $errors[] = "Almacén/CEDIS es obligatorio.";
    if (!in_array($payload['tipo'], ['P','Q','R','S'], true)) $errors[] = "Tipo PQRS es obligatorio.";
    if ($payload['ref_tipo']==='') $errors[] = "Tipo de referencia es obligatorio.";
    if ($payload['ref_folio']==='') $errors[] = "Folio de referencia es obligatorio.";
    if ($payload['reporta_nombre']==='') $errors[] = "Quién reporta es obligatorio.";
    if ($payload['responsable_recibo']==='') $errors[] = "Responsable interno (recibe) es obligatorio.";
    if ($payload['descripcion']==='') $errors[] = "Descripción es obligatoria.";

    if (!$errors) {
      // Llamada server-side a la API PQRS (crear)
      $opts = [
        'http' => [
          'method'  => 'POST',
          'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
          'content' => http_build_query(array_merge($payload, ['action'=>'crear'])),
          'timeout' => 15
        ]
      ];
      $ctx = stream_context_create($opts);
      $resp = file_get_contents($API_PQRS, false, $ctx);
      $data = json_decode($resp ?: '{}', true);

      if (!$data || empty($data['ok'])) {
        $errors[] = ($data['error'] ?? 'No se pudo guardar.');
      } else {
        // PRG: redirige y deja form limpio
        $folio = $data['folio'] ?? '';
        header("Location: pqrs_new.php?ok=1&folio=" . urlencode($folio));
        exit;
      }
    }
  } catch(Throwable $e) {
    $errors[] = "Error al guardar: " . $e->getMessage();
  }
}

$usuario = (string)($_SESSION['usuario'] ?? 'Usuario WMS');
?>

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0">Nueva Incidencia (PQRS v2)</h3>
    <a class="btn btn-outline-secondary" href="pqrs.php"><i class="fas fa-arrow-left"></i> Regresar</a>
  </div>

  <?php if ($ok): ?>
    <div class="alert alert-success">
      <i class="fas fa-check-circle"></i>
      Guardado exitoso<?= $folio_ok ? (". Folio: <b>".h($folio_ok)."</b>") : "" ?>.
      <span class="ml-2 text-muted">Formulario listo para una nueva captura.</span>
    </div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <b>Validación</b>
      <ul class="mb-0">
        <?php foreach($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" id="frmPQRS">
    <!-- BLOQUE 1 -->
    <div class="card mb-3">
      <div class="card-header"><b>Datos base</b></div>
      <div class="card-body">

        <div class="row">
          <div class="col-md-4">
            <label class="font-weight-bold">Almacén / CEDIS <span class="text-danger">*</span></label>
            <select name="cve_almacen" class="form-control" required>
              <option value="">Seleccione</option>
              <?php foreach($almacenes as $a): ?>
                <option value="<?= h($a['clave']) ?>" <?= (($_POST['cve_almacen'] ?? '')===$a['clave']?'selected':'') ?>>
                  <?= h($a['clave']) ?> - <?= h($a['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-4">
            <label class="font-weight-bold">Tipo PQRS <span class="text-danger">*</span></label>
            <select name="tipo" class="form-control" required>
              <option value="">Seleccione</option>
              <option value="P" <?= (($_POST['tipo'] ?? '')==='P'?'selected':'') ?>>Petición</option>
              <option value="Q" <?= (($_POST['tipo'] ?? '')==='Q'?'selected':'') ?>>Queja</option>
              <option value="R" <?= (($_POST['tipo'] ?? '')==='R'?'selected':'') ?>>Reclamo</option>
              <option value="S" <?= (($_POST['tipo'] ?? '')==='S'?'selected':'') ?>>Sugerencia</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="font-weight-bold">Status <span class="text-danger">*</span></label>
            <select name="status_clave" class="form-control" required>
              <?php
                $selSt = $_POST['status_clave'] ?? 'NUEVA';
                $statusRows = $cats['status'] ?? [];
                if (!$statusRows) {
                  $statusRows = [
                    ['clave'=>'NUEVA','nombre'=>'Nueva'],
                    ['clave'=>'EN_PROCESO','nombre'=>'En proceso'],
                    ['clave'=>'EN_ESPERA','nombre'=>'En espera'],
                    ['clave'=>'CERRADA','nombre'=>'Cerrada'],
                    ['clave'=>'NO_PROCEDE','nombre'=>'No procede'],
                  ];
                }
                foreach($statusRows as $s):
              ?>
                <option value="<?= h($s['clave']) ?>" <?= ($selSt===$s['clave']?'selected':'') ?>>
                  <?= h($s['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <hr/>

        <div class="row">
          <div class="col-md-6">
            <label class="font-weight-bold">Cliente <span class="text-danger">*</span></label>
            <select class="form-control" name="cve_clte" id="cve_clte" required></select>
            <small class="text-muted">Escribe para buscar por clave / razón social / RFC.</small>
          </div>

          <div class="col-md-3">
            <label class="font-weight-bold">Referencia tipo <span class="text-danger">*</span></label>
            <select class="form-control" name="ref_tipo" id="ref_tipo" required>
              <?php
                $refRows = $cats['ref'] ?? [];
                if (!$refRows) $refRows = [
                  ['clave'=>'PEDIDO','nombre'=>'Pedido'],
                  ['clave'=>'OC','nombre'=>'Orden de compra'],
                  ['clave'=>'EMBARQUE','nombre'=>'Embarque'],
                  ['clave'=>'GARANTIA','nombre'=>'Garantía'],
                  ['clave'=>'OTRO','nombre'=>'Otro'],
                ];
                $selRef = $_POST['ref_tipo'] ?? 'PEDIDO';
              ?>
              <?php foreach($refRows as $r): ?>
                <option value="<?= h($r['clave']) ?>" <?= ($selRef===$r['clave']?'selected':'') ?>><?= h($r['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-3">
            <label class="font-weight-bold">Pedido / OC / Embarque <span class="text-danger">*</span></label>
            <!-- Select dependiente: pedidos del cliente si ref_tipo=PEDIDO -->
            <select class="form-control" name="ref_folio" id="ref_folio" required></select>
            <small class="text-muted" id="refHint">Primero elige cliente, luego selecciona la referencia.</small>
          </div>
        </div>

      </div>
    </div>

    <!-- BLOQUE 2 -->
    <div class="card mb-3">
      <div class="card-header"><b>Quién reporta y responsables</b></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4">
            <label class="font-weight-bold">Quién reporta <span class="text-danger">*</span></label>
            <input class="form-control" name="reporta_nombre" required value="<?= h($_POST['reporta_nombre'] ?? '') ?>" placeholder="Nombre de contacto / quien levanta la PQRS">
          </div>
          <div class="col-md-4">
            <label class="font-weight-bold">Contacto</label>
            <input class="form-control" name="reporta_contacto" value="<?= h($_POST['reporta_contacto'] ?? '') ?>" placeholder="Correo / Teléfono">
          </div>
          <div class="col-md-4">
            <label class="font-weight-bold">Cargo</label>
            <input class="form-control" name="reporta_cargo" value="<?= h($_POST['reporta_cargo'] ?? '') ?>" placeholder="Puesto / área">
          </div>
        </div>

        <div class="row mt-3">
          <div class="col-md-6">
            <label class="font-weight-bold">Responsable interno (recibe) <span class="text-danger">*</span></label>
            <input class="form-control" name="responsable_recibo" required value="<?= h($_POST['responsable_recibo'] ?? $usuario) ?>">
          </div>
          <div class="col-md-6">
            <label class="font-weight-bold">Responsable de la acción (atiende)</label>
            <input class="form-control" name="responsable_accion" value="<?= h($_POST['responsable_accion'] ?? '') ?>" placeholder="Se puede asignar después">
          </div>
        </div>
      </div>
    </div>

    <!-- BLOQUE 3 -->
    <div class="card mb-3">
      <div class="card-header"><b>Contenido del caso</b></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-8">
            <label class="font-weight-bold">Asunto</label>
            <input class="form-control" name="asunto" value="<?= h($_POST['asunto'] ?? '') ?>" placeholder="Resumen ejecutivo">
          </div>
          <div class="col-md-4">
            <label class="font-weight-bold">Motivo (catálogo)</label>
            <select class="form-control" name="motivo_registro_id">
              <option value="">Seleccione</option>
              <?php foreach(($cats['motivos']['REGISTRO'] ?? []) as $m): ?>
                <option value="<?= (int)$m['id_motivo'] ?>" <?= ((int)($_POST['motivo_registro_id'] ?? 0)===(int)$m['id_motivo']?'selected':'') ?>>
                  <?= h($m['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small class="text-muted">Si no está en catálogo, usa “Motivo texto”.</small>
          </div>
        </div>

        <div class="row mt-3">
          <div class="col-md-12">
            <label class="font-weight-bold">Motivo (texto)</label>
            <input class="form-control" name="motivo_registro_txt" value="<?= h($_POST['motivo_registro_txt'] ?? '') ?>" placeholder="Fallback / detalle adicional">
          </div>
        </div>

        <div class="row mt-3">
          <div class="col-md-12">
            <label class="font-weight-bold">Descripción <span class="text-danger">*</span></label>
            <textarea class="form-control" name="descripcion" rows="5" required placeholder="Hechos, evidencia, alcance, impacto..."><?= h($_POST['descripcion'] ?? '') ?></textarea>
          </div>
        </div>

        <hr/>

        <div class="row">
          <div class="col-md-4">
            <div class="custom-control custom-switch mt-2">
              <input type="checkbox" class="custom-control-input" id="swCobro" name="susceptible_cobro" value="1" <?= ((int)($_POST['susceptible_cobro'] ?? 0)===1?'checked':'') ?>>
              <label class="custom-control-label font-weight-bold" for="swCobro">Susceptible a cobro</label>
            </div>
          </div>
          <div class="col-md-4">
            <label class="font-weight-bold">Monto estimado</label>
            <input class="form-control" name="monto_estimado" value="<?= h($_POST['monto_estimado'] ?? '') ?>" placeholder="Ej. 1500.00">
          </div>
          <div class="col-md-4"></div>
        </div>

      </div>
    </div>

    <div class="d-flex justify-content-end mb-5">
      <a class="btn btn-outline-secondary mr-2" href="pqrs.php">Cancelar</a>
      <button class="btn btn-success" type="submit">
        <i class="fas fa-save"></i> Guardar
      </button>
    </div>

  </form>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
(function(){
  const API_PEDIDOS = <?= json_encode($API_PEDIDOS) ?>;
  const API_PQRS = <?= json_encode($API_PQRS) ?>;

  // Cliente: trae de pedidos_api.php?action=clientes
  $('#cve_clte').select2({
    placeholder: 'Escribe para buscar cliente...',
    allowClear: true,
    width: '100%',
    ajax: {
      url: API_PEDIDOS,
      dataType: 'json',
      delay: 250,
      data: function(params){
        return { action:'clientes', q: (params.term || ''), limit: 20 };
      },
      processResults: function(data){
        if(!data || !data.ok) return {results:[]};
        const results = (data.rows||[]).map(r => ({
          id: r.Cve_Clte,
          text: (r.Cve_Clte + ' - ' + (r.RazonSocial||'')).trim()
        }));
        return {results};
      }
    }
  });

  // Referencia: depende de ref_tipo
  function initRefSelect(){
    const refTipo = ($('#ref_tipo').val() || 'PEDIDO').toUpperCase();

    // destruir si ya existía
    if ($('#ref_folio').hasClass("select2-hidden-accessible")) {
      $('#ref_folio').select2('destroy');
    }
    $('#ref_folio').empty();

    if (refTipo === 'PEDIDO') {
      $('#refHint').text('Selecciona cliente y elige un pedido del cliente.');
      $('#ref_folio').select2({
        placeholder: 'Seleccione pedido...',
        allowClear: true,
        width:'100%',
        ajax:{
          url: API_PQRS,
          dataType:'json',
          delay: 250,
          data: function(params){
            const cve = ($('#cve_clte').val() || '').trim();
            return { action:'pedidos_by_cliente', cve_clte: cve, q: (params.term||''), limit: 30 };
          },
          processResults: function(data){
            if(!data || !data.ok) return {results:[]};
            const results = (data.rows||[]).map(r => ({
              id: r.Fol_folio,
              text: r.Fol_folio + (r.Fec_Pedido ? (' | ' + r.Fec_Pedido) : '') + (r.status ? (' | ' + r.status) : '')
            }));
            return {results};
          }
        }
      });
    } else {
      $('#refHint').text('Captura el folio de referencia (OC/Embarque/Garantía/Otro).');
      // Para no-PEDIDO usamos select2 como "tag" (captura libre)
      $('#ref_folio').select2({
        placeholder: 'Captura folio...',
        tags: true,
        width:'100',
        allowClear: true
      });
    }
  }

  initRefSelect();

  // Cuando cambia cliente: refrescar pedidos si ref_tipo=PEDIDO
  $('#cve_clte').on('change', function(){
    if ((($('#ref_tipo').val()||'').toUpperCase()) === 'PEDIDO') {
      $('#ref_folio').val(null).trigger('change');
    }
  });

  // Cuando cambia tipo de referencia
  $('#ref_tipo').on('change', function(){
    initRefSelect();
  });

  // Precarga si hubo error y se recargó con POST (opcional)
  <?php if (!empty($_POST['cve_clte'])): ?>
  (function(){
    const cve = <?= json_encode((string)($_POST['cve_clte'] ?? '')) ?>;
    const txt = <?= json_encode((string)($_POST['cve_clte_text'] ?? $_POST['cve_clte'] ?? '')) ?>;
    if (cve) {
      const opt = new Option(txt, cve, true, true);
      $('#cve_clte').append(opt).trigger('change');
    }
  })();
  <?php endif; ?>

})();
</script>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
