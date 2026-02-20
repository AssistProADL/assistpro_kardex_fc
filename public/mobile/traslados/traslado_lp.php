<?php
// public/mobile/traslados/traslado_lp.php
// public/mobile/traslados/traslado_lp.php
require_once __DIR__ . '/../../../app/auth_check.php';

// Intenta tomar usuario por sesión si existe, si no, JS tomará localStorage.
$usuario = $_SESSION['usuario'] ?? $_SESSION['user'] ?? '';
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Traslado LP - AssistPRO ER</title>

  <!-- Si ya tienes CSS global del mobile, puedes enlazarlo aquí.
       Si no, este estilo mínimo deja la UI usable y consistente. -->
  <style>
    :root {
      --bg: #f5f7fb;
      --card: #ffffff;
      --ink: #0f172a;
      --muted: #64748b;
      --pri: #1d4ed8;
      --dark: #0b1220;
      --line: #e2e8f0;
    }

    * {
      box-sizing: border-box;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial;
    }

    body {
      margin: 0;
      background: var(--bg);
      color: var(--ink);
    }

    .wrap {
      max-width: 430px;
      margin: 0 auto;
      padding: 14px;
    }

    .card {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 16px;
      box-shadow: 0 8px 22px rgba(15, 23, 42, .06);
      padding: 14px;
      margin: 10px 0;
    }

    .title {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 10px;
    }

    .title h1 {
      font-size: 18px;
      margin: 0;
    }

    .pill {
      font-size: 12px;
      color: #fff;
      background: var(--pri);
      padding: 6px 10px;
      border-radius: 999px;
      font-weight: 600;
    }

    .row {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .field {
      flex: 1;
      min-width: 160px;
    }

    label {
      display: block;
      font-size: 12px;
      color: var(--muted);
      margin: 6px 0;
    }

    input,
    select {
      width: 100%;
      padding: 10px 12px;
      border-radius: 12px;
      border: 1px solid var(--line);
      outline: none;
      background: #fff;
    }

    input:focus,
    select:focus {
      border-color: rgba(29, 78, 216, .55);
      box-shadow: 0 0 0 3px rgba(29, 78, 216, .12);
    }

    .btn {
      width: 100%;
      border: 0;
      border-radius: 14px;
      padding: 12px 14px;
      font-weight: 700;
      cursor: pointer;
    }

    .btnpri {
      background: var(--pri);
      color: #fff;
    }

    .btndk {
      background: var(--dark);
      color: #fff;
    }

    .btn:disabled {
      opacity: .5;
      cursor: not-allowed;
    }

    .kpis {
      display: flex;
      gap: 10px;
      margin-top: 10px;
    }

    .kpi {
      flex: 1;
      background: linear-gradient(180deg, #0b1220, #111827);
      color: #fff;
      border-radius: 14px;
      padding: 12px;
    }

    .kpi .v {
      font-size: 18px;
      font-weight: 800;
    }

    .kpi .t {
      font-size: 12px;
      opacity: .8;
    }

    .msg {
      font-size: 13px;
      color: var(--muted);
      margin-top: 8px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      font-size: 13px;
    }

    th,
    td {
      padding: 10px;
      border-bottom: 1px solid var(--line);
      vertical-align: top;
    }

    th {
      font-size: 12px;
      color: var(--muted);
      text-align: left;
    }

    .tag {
      display: inline-block;
      font-size: 11px;
      background: #eef2ff;
      color: #3730a3;
      padding: 4px 8px;
      border-radius: 999px;
      font-weight: 700;
    }

    .ok {
      color: #16a34a;
      font-weight: 700;
    }

    .bad {
      color: #dc2626;
      font-weight: 700;
    }

    .split {
      display: flex;
      gap: 10px;
    }

    .split>* {
      flex: 1;
    }
  </style>
</head>

<body>
  <div class="wrap">

    <div class="card">
      <div class="title">
        <h1>Traslado LP</h1>
        <div class="pill">WMS</div>
      </div>

      <div class="row">
        <div class="field">
          <label>LP Origen (CveLP)</label>
          <input id="lpOrigen" autocomplete="off" placeholder="Escanea / escribe CveLP y Enter" />
          <div class="msg">Tip: Enter ejecuta lookup inmediato.</div>
        </div>
      </div>

      <div id="panelOrigen" style="display:none;">
        <div class="kpis">
          <div class="kpi">
            <div class="v" id="kBlOri">—</div>
            <div class="t">BL Origen</div>
          </div>
          <div class="kpi">
            <div class="v" id="kTotal">—</div>
            <div class="t">Total</div>
          </div>
        </div>

        <div class="msg" style="margin-top:10px;">
          <span class="tag" id="tagTipo">LP</span>
          <span style="margin-left:8px;color:var(--muted);" id="txtLPInfo"></span>
        </div>

        <table>
          <thead>
            <tr>
              <th>Producto</th>
              <th>Lote</th>
              <th style="text-align:right;">Cant</th>
            </tr>
          </thead>
          <tbody id="tbDet"></tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="title">
        <h1>Destino</h1>
        <div class="pill">Acomodo Mixto</div>
      </div>

      <div class="row">
        <div class="field">
          <label>BL Destino (CodigoCSD)</label>
          <input id="blDestino" list="dlBL" autocomplete="off" placeholder="Escribe BL y selecciona" />
          <datalist id="dlBL"></datalist>
          <div class="msg">Solo se listan ubicaciones Activas con AcomodoMixto='S'.</div>
        </div>
      </div>

      <div class="row" style="align-items:center;margin-top:8px;">
        <div class="field" style="min-width:220px;">
          <label style="display:flex;gap:10px;align-items:center;">
            <input type="checkbox" id="chkMerge" style="width:auto;transform:scale(1.1);" />
            Fusionar (MERGE) a un LP destino
          </label>
          <div class="msg">Si no seleccionas LP destino, se mueve el LP completo al BL destino.</div>
        </div>
      </div>

      <div id="boxLpDestino" style="display:none;margin-top:8px;">
        <div class="row">
          <div class="field">
            <label>LP Destino (Escanea o Selecciona del BL)</label>
            <input id="lpDestino" list="dlLPDestino" autocomplete="off"
              placeholder="Escribe/Escanea LP o selecciona de lista" />
            <datalist id="dlLPDestino"></datalist>
            <div class="msg">Regla: solo se permite fusionar con LPs en el BL destino.</div>
          </div>
        </div>
      </div>

      <div class="split" style="margin-top:12px;">
        <button class="btn btndk" id="btnLimpiar" type="button">Limpiar</button>
        <button class="btn btnpri" id="btnCommit" type="button" disabled>Confirmar traslado</button>
      </div>

      <div class="msg" id="msg" style="margin-top:10px;"></div>
    </div>

    <div class="card">
      <button class="btn btndk" type="button" onclick="location.href='../menu.php'">← Volver al menú</button>
      <div class="msg" style="text-align:center;margin-top:10px;">Powered by Adventech Logística</div>
    </div>

  </div>

  <script>
    // Endpoints (desde /public/mobile/traslados/ hacia /public/api/stock/)
    const API_LOOKUP_LP = '../../api/stock/lookup_lp_origen.php';
    const API_UBICACIONES = '../../api/stock/ubicaciones_destino.php';
    const API_LPS_EN_BL = '../../api/stock/lps_en_bl.php';
    const API_COMMIT = '../../api/stock/traslado_lp_commit.php';

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
      msg.innerHTML = ok === null ? t : (ok ? `<span class="ok">${t}</span>` : `<span class="bad">${t}</span>`);
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
        <td style="text-align:right;"><b>${fmt4(r.cantidad)}</b></td>
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
        setMsg('No se detectó usuario. Valida sesión o localStorage (mobile_usuario).', false);
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
</body>

</html>