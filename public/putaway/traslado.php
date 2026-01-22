<?php
/* ===========================================================
   public/putaway/traslado.php
   PutAway – Traslado de LP (Lógica Mobile Portada)
   =========================================================== */

require_once __DIR__ . '/../../app/db.php';
// session middleware handles session start if needed, but we rely on auth_check.php implicitly included in menu?
// The original file used _menu_global.php which includes auth_check.php.

/* ================= Frame (menú global) =================== */
$activeSection = 'operaciones';
$activeItem = 'putaway_traslado';
$pageTitle = 'PutAway · Traslado de LP';
include __DIR__ . '/../bi/_menu_global.php'; // This usually handles auth

/* ================= Parámetros de Sesión =================== */
// JS will also try localStorage, but getting it from PHP session is robust for web
$usuario = $_SESSION['usuario'] ?? $_SESSION['user'] ?? $_SESSION['cve_usuario'] ?? 'WEB_USER';

?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    --ap-bg: #f8fafc;
    /* Slate 50 */
    --ap-card: #ffffff;
    --ap-text-main: #0f172a;
    /* Slate 900 */
    --ap-text-muted: #64748b;
    /* Slate 500 */
    --ap-primary: #2563eb;
    /* Blue 600 */
    --ap-primary-dark: #1e40af;
    --ap-border: #e2e8f0;
    /* Slate 200 */
    /* Densidad de la fuente acordada */
    --ap-font-size: 13px;
    --ap-input-height: 38px;
  }

  body {
    background-color: var(--ap-bg);
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    color: var(--ap-text-main);
    font-size: var(--ap-font-size);
  }

  /* Override del menu global si es necesario, pero nos enfocamos en el contenido */

  .ap-container {
    max-width: 900px;
    /* Ancho cómodo para web desktop */
    margin: 2rem auto;
    padding: 0 1rem;
  }

  .ap-card {
    background: var(--ap-card);
    border: 1px solid var(--ap-border);
    border-radius: 12px;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
    /* Sombra sutil elegante */
    padding: 1.5rem;
    margin-bottom: 1.5rem;
  }

  .ap-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.25rem;
  }

  .ap-title {
    font-size: 1.1rem;
    font-weight: 700;
    letter-spacing: -0.025em;
    color: var(--ap-text-main);
    margin: 0;
  }

  .ap-badge {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 4px 10px;
    border-radius: 99px;
    background: var(--ap-primary);
    color: #fff;
  }

  /* Labels & Inputs */
  .ap-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--ap-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.02em;
    margin-bottom: 0.35rem;
  }

  .form-control,
  .form-select {
    font-size: 0.9rem;
    /* Un poco mas grande que el label para legibilidad */
    border-radius: 8px;
    border-color: var(--ap-border);
    padding: 0.5rem 0.75rem;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    transition: all 0.2s;
  }

  .form-control:focus,
  .form-select:focus {
    border-color: var(--ap-primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  }

  .input-group-text {
    background: #f1f5f9;
    border-color: var(--ap-border);
    color: var(--ap-text-muted);
    border-radius: 8px 0 0 8px;
  }

  /* KPIs Grid */
  .ap-kpi-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-top: 1rem;
  }

  .ap-kpi-box {
    background: #fff;
    border: 1px solid var(--ap-border);
    border-radius: 10px;
    padding: 1rem;
    text-align: center;
    /* Gradiente sutil para darle volumen */
    background: linear-gradient(to bottom, #ffffff, #f8fafc);
  }

  .ap-kpi-box.dark {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    color: #fff;
    border: none;
  }

  .ap-kpi-val {
    font-size: 1.4rem;
    font-weight: 700;
    line-height: 1.2;
    margin-bottom: 2px;
  }

  .ap-kpi-lbl {
    font-size: 0.7rem;
    font-weight: 500;
    text-transform: uppercase;
    color: var(--ap-text-muted);
  }

  .ap-kpi-box.dark .ap-kpi-lbl {
    opacity: 0.7;
    color: #fff;
  }

  /* Msg Box */
  .ap-msg-box {
    border-radius: 8px;
    padding: 0.75rem 1rem;
    font-size: 0.85rem;
    font-weight: 500;
    margin-top: 1rem;
    display: none;
    /* Hidden by default */
  }

  .ap-msg-box.success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
    display: block;
  }

  .ap-msg-box.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
    display: block;
  }

  /* Table override */
  .table {
    margin-bottom: 0;
  }

  .table thead th {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--ap-text-muted);
    background: #f8fafc;
    border-bottom: 1px solid var(--ap-border);
  }

  .table tbody td {
    font-size: 0.85rem;
    vertical-align: middle;
    color: var(--ap-text-main);
  }

  /* Checkbox Merge */
  .ap-switch-box {
    background: #f1f5f9;
    border: 1px solid var(--ap-border);
    border-radius: 10px;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
  }

  .form-check-input:checked {
    background-color: var(--ap-primary);
    border-color: var(--ap-primary);
  }

  /* Buttons */
  .btn-primary {
    background-color: var(--ap-primary);
    border-color: var(--ap-primary);
    font-weight: 600;
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
  }

  .btn-primary:hover {
    background-color: var(--ap-primary-dark);
  }

  .btn-primary:disabled {
    background-color: #94a3b8;
    border-color: #94a3b8;
    box-shadow: none;
  }
</style>

<div class="ap-container">

  <div class="row">
    <!-- Main Form -->
    <div class="col-12">

      <!-- Card: Origen -->
      <div class="ap-card">
        <div class="ap-header">
          <h2 class="ap-title">Origen de Mercancía</h2>
          <span class="ap-badge">WMS Stock</span>
        </div>

        <div class="mb-4">
          <label class="ap-label">Licencia (LPC) / Código de Barra</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
            <input type="text" id="lpOrigen" class="form-control" autocomplete="off"
              placeholder="Escanea o escribe el CveLP...">
          </div>
        </div>

        <div id="panelOrigen" style="display:none;" class="animate__animated animate__fadeIn">

          <div class="table-responsive rounded border mb-3">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Producto</th>
                  <th>Lote</th>
                  <th class="text-end">Cantidad</th>
                </tr>
              </thead>
              <tbody id="tbDet"></tbody>
            </table>
          </div>

          <div class="ap-kpi-grid">
            <div class="ap-kpi-box dark">
              <div class="ap-kpi-val" id="kBlOri">—</div>
              <div class="ap-kpi-lbl">Ubicación Actual</div>
            </div>
            <div class="ap-kpi-box">
              <div class="ap-kpi-val text-primary" id="kTotal">—</div>
              <div class="ap-kpi-lbl">Total Unidades</div>
            </div>
            <div class="ap-kpi-box">
              <div class="ap-kpi-val text-dark" id="tagTipo" style="font-size:1.1rem;">LP</div>
              <div class="ap-kpi-lbl">Tipo Contenedor</div>
            </div>
          </div>

          <div class="text-end mt-2">
            <small class="text-muted fw-bold" id="txtLPInfo"></small>
          </div>
        </div>
      </div>

      <!-- Card: Destino -->
      <div class="ap-card border-top-primary">
        <div class="ap-header">
          <h2 class="ap-title">Destino de Transferencia</h2>
          <span class="ap-badge" style="background:#10b981;">Acomodo</span>
        </div>

        <div class="mb-4">
          <label class="ap-label">Ubicación Destino (BL)</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-geo-alt-fill"></i></span>
            <input id="blDestino" class="form-control" list="dlBL" autocomplete="off"
              placeholder="Busca la ubicación destino..." />
          </div>
          <datalist id="dlBL"></datalist>
          <small class="text-muted mt-1 d-block" style="font-size:11px;">Solo se muestran ubicaciones activas con
            Acomodo Mixto habilitado.</small>
        </div>

        <div class="ap-switch-box">
          <input class="form-check-input" type="checkbox" id="chkMerge" style="transform: scale(1.2);">
          <div>
            <label class="form-check-label fw-bold text-dark" for="chkMerge" style="font-size:0.9rem;">Fusionar con otro
              LP</label>
            <div class="text-muted" style="font-size:0.8rem; line-height:1.2;">Activa esta opción para agregar el
              contenido a una licencia existente en el destino.</div>
          </div>
        </div>

        <div id="boxLpDestino" style="display:none;" class="mb-4 animate__animated animate__fadeIn">
          <label class="ap-label text-primary">LP Destino (Fusionar)</label>
          <div class="input-group">
            <span class="input-group-text bg-primary text-white"><i class="bi bi-box-seam"></i></span>
            <input id="lpDestino" class="form-control border-primary" list="dlLPDestino" autocomplete="off"
              placeholder="Escanea o selecciona el LP destino..." />
          </div>
          <datalist id="dlLPDestino"></datalist>
        </div>

        <hr class="border-light my-4">

        <div class="d-flex gap-3">
          <button class="btn btn-light text-muted border flex-grow-1" id="btnLimpiar" type="button"
            style="font-weight:600;">Limpiar Campos</button>
          <button class="btn btn-primary flex-grow-1 py-3" id="btnCommit" type="button" disabled>
            <i class="bi bi-check2-circle me-1"></i> Confirmar Transferencia
          </button>
        </div>

        <div id="msg"></div>
      </div>

    </div>
  </div>
</div>

<script>
  // Endpoints (adjusting paths for public/putaway context)
  // ../api/stock/ matches public/api/stock/
  const API_LOOKUP_LP = '../api/stock/lookup_lp_origen.php';
  const API_UBICACIONES = '../api/stock/ubicaciones_destino.php';
  const API_LPS_EN_BL = '../api/stock/lps_en_bl.php';
  const API_COMMIT = '../api/stock/traslado_lp_commit.php';

  const elLP = document.getElementById('lpOrigen');
  const elBL = document.getElementById('blDestino');
  const dlBL = document.getElementById('dlBL');

  // LP Destino (Combo)
  const elLPDest = document.getElementById('lpDestino');
  const dlLPDest = document.getElementById('dlLPDestino');

  const chk = document.getElementById('chkMerge');
  const boxD = document.getElementById('boxLpDestino');
  const btnC = document.getElementById('btnCommit');
  const btnL = document.getElementById('btnLimpiar');

  const panelO = document.getElementById('panelOrigen');
  const tbDet = document.getElementById('tbDet');
  const kBlOri = document.getElementById('kBlOri');
  const kTotal = document.getElementById('kTotal');
  const tagTipo = document.getElementById('tagTipo');
  const txtLPInfo = document.getElementById('txtLPInfo');
  const msg = document.getElementById('msg');

  let state = {
    lpOrigen: '',
    idContO: null,
    tipoLP: '',
    blOrigen: '',
    total: 0,
    rows: []
  };

  function fmt4(x) {
    const n = Number(x || 0);
    return n.toLocaleString('en-US', { minimumFractionDigits: 4, maximumFractionDigits: 4 });
  }

  function setMsg(t, ok = null) {
    if (ok === null) {
      msg.innerHTML = `<div class="ap-msg-box" style="display:block; background:#f1f5f9; color:#64748b;">${t}</div>`;
    } else {
      msg.innerHTML = ok 
        ? `<div class="ap-msg-box success"><i class="bi bi-check-circle-fill me-2"></i>${t}</div>` 
        : `<div class="ap-msg-box error"><i class="bi bi-exclamation-triangle-fill me-2"></i>${t}</div>`;
    }
  }

  async function fetchJson(url, opt) {
    const r = await fetch(url, opt);
    return await r.json();
  }

  function canCommit() {
    // Requerimos Origen
    if (!state.lpOrigen || !state.blOrigen || !state.rows.length) return false;

    // Requerimos BL Destino (Input)
    const bl = elBL.value.trim();
    if (!bl) return false;

    // Si es Fusión, requerimos INPUT del LP destino
    if (chk.checked) {
      return elLPDest.value.trim().length > 0;
    }
    return true;
  }

  function refreshCommitBtn() {
    btnC.disabled = !canCommit();
  }

  async function loadBLDatalist(q) {
    const url = `${API_UBICACIONES}?q=${encodeURIComponent(q || '')}`;
    const j = await fetchJson(url);
    dlBL.innerHTML = '';
    if (j && j.ok && Array.isArray(j.rows)) {
      j.rows.forEach(r => {
        const opt = document.createElement('option');
        opt.value = r.CodigoCSD;
        dlBL.appendChild(opt);
      });
    }
  }

  async function loadLPDestinoDatalist() {
    const bl = elBL.value.trim();
    dlLPDest.innerHTML = ''; // Limpiar siempre

    if (!bl) return;

    const url = `${API_LPS_EN_BL}?bl=${encodeURIComponent(bl)}&tipo=${encodeURIComponent(state.tipoLP || '')}`;
    const j = await fetchJson(url);

    if (j && j.ok && Array.isArray(j.rows)) {
      j.rows.forEach(r => {
        const opt = document.createElement('option');
        opt.value = r.CveLP;
        dlLPDest.appendChild(opt);
      });
    }
  }

  async function lookupOrigen() {
    const lp = elLP.value.trim();
    if (lp.length < 2) { setMsg('Captura/escanea un CveLP válido.', false); return; }

    setMsg('Consultando LP origen…');
    const url = `${API_LOOKUP_LP}?CveLP=${encodeURIComponent(lp)}`;
    const j = await fetchJson(url);

    if (!j || !j.ok) {
      panelO.style.display = 'none';
      setMsg(j?.error || 'No se pudo consultar LP.', false);
      state = { lpOrigen: '', idContO: null, tipoLP: '', blOrigen: '', total: 0, rows: [] };
      refreshCommitBtn();
      return;
    }

    state.lpOrigen = lp;
    state.idContO = j.lp?.IDContenedor || null;
    state.tipoLP = (j.lp?.tipo || '').toString();
    state.blOrigen = j.bl_actual || '';
    state.total = Number(j.total || 0);
    state.rows = Array.isArray(j.rows) ? j.rows : [];

    kBlOri.textContent = state.blOrigen || '—';
    kTotal.textContent = fmt4(state.total);

    tagTipo.textContent = state.tipoLP ? state.tipoLP.toUpperCase() : 'LP';
    txtLPInfo.textContent = `LP: ${lp} · Items: ${state.rows.length}`;

    tbDet.innerHTML = '';
    state.rows.forEach(r => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><b>${r.cve_articulo || ''}</b></td>
        <td>${r.cve_lote || '—'}</td>
        <td class="text-end"><b>${fmt4(r.cantidad)}</b></td>
      `;
      tbDet.appendChild(tr);
    });

    panelO.style.display = 'block';
    setMsg('LP origen listo. Selecciona BL destino.', true);

    // Auto-load BL datalist context if something is already there
    if (elBL.value.trim()) await loadBLDatalist(elBL.value.trim());

    refreshCommitBtn();
  }

  // --- Event Listeners ---

  // Toggle Merge/Move
  chk.addEventListener('change', async () => {
    boxD.style.display = chk.checked ? 'block' : 'none';
    setMsg(chk.checked ? 'Modo FUSIÓN activo: escribe/selecciona LP destino.' : 'Modo TRASLADO: mover LP completo al BL destino.', true);

    if (chk.checked) {
      await loadLPDestinoDatalist();
      elLPDest.focus();
    } else {
      elLPDest.value = '';
    }
    refreshCommitBtn();
  });

  // BL Input Logic
  elBL.addEventListener('input', async () => {
    const q = elBL.value.trim();
    if (q.length >= 2) {
      await loadBLDatalist(q);
    }
    refreshCommitBtn();
  });

  elBL.addEventListener('change', async () => {
    // Al confirmar un BL, cargamos sus LPs para el combo
    if (chk.checked) {
      await loadLPDestinoDatalist();
    }
    refreshCommitBtn();
  });

  elBL.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      if (chk.checked) elLPDest.focus();
    }
  });

  // LP Destino Input Logic
  elLPDest.addEventListener('input', () => refreshCommitBtn());
  elLPDest.addEventListener('change', () => refreshCommitBtn());

  // LP Origen Logic
  elLP.addEventListener('keydown', async (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      await lookupOrigen();
    }
  });

  // Limpiar
  btnL.addEventListener('click', () => {
    elLP.value = '';
    elBL.value = '';
    elLPDest.value = '';
    dlBL.innerHTML = '';
    dlLPDest.innerHTML = '';

    chk.checked = false;
    boxD.style.display = 'none';
    panelO.style.display = 'none';

    state = { lpOrigen: '', idContO: null, tipoLP: '', blOrigen: '', total: 0, rows: [] };
    setMsg('Listo para nuevo traslado.');
    refreshCommitBtn();
    elLP.focus();
  });

  // Commit
  btnC.addEventListener('click', async () => {

    if (!canCommit()) {
      setMsg('Completa los campos requeridos.', false);
      return;
    }

    const usuarioPHP = <?php echo json_encode($usuario); ?>;
    const usuarioLS = (localStorage.getItem('mobile_usuario') || localStorage.getItem('usuario') || '').trim();
    const usuario = (usuarioPHP || usuarioLS || '').trim();
    if (!usuario) {
      setMsg('No se detectó usuario. Valida sesión web o localStorage (mobile_usuario).', false);
      return;
    }

    const payload = {
      CveLP_origen: state.lpOrigen,
      bl_destino: elBL.value.trim(),
      usuario: usuario
    };

    if (chk.checked) {
      // En FUSION, mandamos el valor del INPUT LP Destino
      payload.CveLP_destino = elLPDest.value.trim();
    }

    setMsg('Ejecutando traslado…');
    btnC.disabled = true;

    const j = await fetchJson(API_COMMIT, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    if (!j || !j.ok) {
      setMsg(j?.error || 'Error al ejecutar traslado.', false);
      refreshCommitBtn();
      return;
    }

    setMsg(`Aplicado ✅ Folio: ${j.folio} · Modo: ${j.modo}`, true);

    // Post-commit
    await lookupOrigen();

    // Limpiar destino
    elBL.value = '';
    elLPDest.value = '';
    dlBL.innerHTML = '';
    dlLPDest.innerHTML = '';
    refreshCommitBtn();
  });

  // inicio
  setMsg('Listo. Escanea LP Origen.');
  elLP.focus();
</script>

<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>