<?php
// UI: Beneficios (Rewards) por regla
// Ruta: /public/sfa/promociones/promocion_beneficios.php

include '../../bi/_menu_global.php';

$promo_id   = $_GET['promo_id']   ?? null;
$almacen_id = $_GET['almacen_id'] ?? null;
$id_rule    = $_GET['id_rule']    ?? null; // opcional (deep-link)

if (!$promo_id) {
  echo '<div class="alert alert-danger">Error: Promoción no especificada. <a href="promociones.php">Volver</a></div>';
  include '../../bi/_menu_global_end.php';
  exit;
}
?>
<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <h3 class="mb-0">Beneficios / Rewards</h3>
      <div class="text-muted small">Promoción #<?= htmlspecialchars($promo_id) ?><?= $almacen_id ? ' · Almacén #' . htmlspecialchars($almacen_id) : '' ?></div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="promociones.php<?= $almacen_id ? '?almacen_id=' . urlencode($almacen_id) : '' ?>">Volver</a>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-6">
          <label class="form-label">Regla</label>
          <select id="ruleSelect" class="form-select">
            <option value="">Cargando reglas...</option>
          </select>
          <div class="form-text">Selecciona la regla para ver/administrar sus beneficios.</div>
        </div>

        <div class="col-md-6 text-md-end">
          <button id="btnAdd" class="btn btn-primary">
            + Agregar beneficio
          </button>
        </div>
      </div>

      <hr class="my-3">

      <div class="row g-2">
        <div class="col-md-3">
          <label class="form-label">Tipo beneficio</label>
          <select id="reward_tipo" class="form-select">
            <option value="BONIF_PRODUCTO">Bonificación de producto</option>
            <option value="DESC_PCT">Descuento (%)</option>
            <option value="DESC_MONTO">Descuento ($)</option>
          </select>
        </div>

        <div class="col-md-2 reward-descuento">
          <label class="form-label">Valor</label>
          <input id="valor" type="number" step="0.0001" class="form-control" placeholder="0.00">
        </div>
        <div class="col-md-2 reward-descuento">
          <label class="form-label">Tope</label>
          <input id="tope_valor" type="number" step="0.0001" class="form-control" placeholder="(opcional)">
        </div>
        <div class="col-md-3 reward-producto">
          <label class="form-label">Modo beneficio</label>
          <select id="reward_modo" class="form-select">
            <option value="PRODUCTO">Producto específico</option>
            <option value="GRUPO">Grupo de productos</option>
          </select>
        </div>

        <div class="col-md-4 reward-producto">
          <label class="form-label">Artículo / Grupo</label>

          <div class="input-group input-group-sm">
            <input id="rw_q" class="form-control"
              placeholder="Clave o descripción (Enter = seleccionar)">
            <button class="btn btn-outline-primary"
              type="button"
              onclick="buscarReward(true)">
              Buscar
            </button>
          </div>

          <div class="ap-result mt-1">
            <div id="rw_res" class="list-group list-group-flush"></div>
          </div>

          <div class="mt-1">
            <div id="rw_sel"></div>
            <input type="hidden" id="rw_tipo_sel">
            <input type="hidden" id="rw_val_sel">
            <input type="hidden" id="rw_label_sel">
          </div>
        </div>


        <div class="col-md-2 reward-producto">
          <label class="form-label">Cantidad de regalo</label>
          <input id="qty" type="number" step="0.0001" class="form-control" placeholder="Ej. 1">
        </div>

        <div class="col-md-3 reward-producto">
          <label class="form-label">Unidad de medida (UM)</label>
          <select id="unimed" class="form-select">
            <option value="">Seleccione</option>
            <option value="PZA">Pieza</option>
            <option value="CAJ">Caja</option>
            <option value="PAQ">Paquete</option>
            <option value="TAR">Tarima</option>
          </select>
        </div>

        <div class="col-md-3 reward-descuento">
          <label class="form-label">Aplica sobre</label>
          <select id="aplica_sobre" class="form-select">
            <option value="TOTAL">TOTAL</option>
            <option value="SUBTOTAL">SUBTOTAL</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Observaciones</label>
          <input id="observaciones" type="text" class="form-control" placeholder="(opcional)">
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <table id="tablaRewards" class="table table-striped table-hover w-100">
        <thead>
          <tr>
            <th>Acciones</th>
            <th>ID</th>
            <th>Tipo</th>
            <th>Valor</th>
            <th>Tope</th>
            <th>Artículo</th>
            <th>Qty</th>
            <th>UM</th>
            <th>Aplica</th>
            <th>Obs</th>
            <th>Activo</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
      <div class="small text-muted mt-2">
        Nota: Los beneficios están ligados a una regla (promo_rule). Si no hay reglas, primero crea una en “Rules”.
      </div>
    </div>
  </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>
  // Desde /public/sfa/promociones/ => /public/api/promociones/
  const API = '../../api/promociones/promociones_api.php';

  const promoId = <?= json_encode($promo_id) ?>;
  const almacenId = <?= json_encode($almacen_id) ?>;
  const presetRuleId = <?= json_encode($id_rule) ?>;

  let tabla = null;
  let rewardToDeleteId = null;


  // =========================================================
  //  RUTAS CORRECTAS A API (AUTO-DETECTA BASE /public)
  // =========================================================
  const PATH = window.location.pathname;
  const basePublic = PATH.includes('/public/') ?
    PATH.split('/public/')[0] + '/public' :
    '/public';

  const API_ARTICULOS = basePublic + '/api/articulos_api.php';
  const API_GRUPOS = basePublic + '/api/api_grupos.php';

  // Config UX
  const LIVE_MIN_CHARS = 2;
  const LIVE_DEBOUNCE = 220;
  const MAX_ROWS = 25;

  function escapeHtml(s) {
    return (s || '').toString().replace(/[&<>"']/g, m => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    } [m]));
  }

  async function fetchJson(url) {
    const r = await fetch(url, {
      credentials: 'same-origin',
      cache: 'no-store'
    });
    const txt = await r.text();

    if (!r.ok) {
      throw new Error(`HTTP ${r.status} ${r.statusText}: ${txt.slice(0,200)}`);
    }
    try {
      return JSON.parse(txt);
    } catch (e) {
      throw new Error('Respuesta no JSON: ' + txt.slice(0, 200));
    }
  }

  function rowToPick(r, tipo) {
    let val = '',
      label = '';

    if (tipo === 'PRODUCTO') {
      val = (r.cve_articulo ?? r.Clave ?? r.clave ?? r.id ?? '').toString();
      const des = (r.des_articulo ?? r.Descripcion ?? r.descripcion ?? '').toString();
      label = des ? `${val} - ${des}` : `${val}`;
    } else {
      val = (r.cve_gpoart ?? r.Clave ?? r.clave ?? r.id ?? '').toString();
      const des = (r.des_gpoart ?? r.Descripcion ?? r.descripcion ?? '').toString();
      label = des ? `${val} - ${des}` : `${val}`;
    }

    return {
      val,
      label
    };
  }

  function renderList(scope, rows, tipo, onPick) {
    const box = document.getElementById(scope + '_res');
    box.innerHTML = '';

    if (!rows.length) {
      box.innerHTML = '<div class="list-group-item small text-muted">Sin resultados</div>';
      return;
    }

    rows.slice(0, MAX_ROWS).forEach(r => {
      const pick = rowToPick(r, tipo);
      const a = document.createElement('a');
      a.className = 'list-group-item list-group-item-action';
      a.innerHTML = `
      <div class="small">
        <b>${escapeHtml(pick.val)}</b>
        <span class="text-muted">
          ${escapeHtml(pick.label.replace(pick.val + ' - ', ''))}
        </span>
      </div>`;
      a.addEventListener('click', () => onPick(pick));
      box.appendChild(a);
    });
  }


  function normalizeRows(j) {
    if (j && Array.isArray(j.rows)) return j.rows;
    if (j && Array.isArray(j.data)) return j.data;
    if (j && j.data && Array.isArray(j.data.rows)) return j.data.rows;
    return [];
  }



  async function apiGet(url) {
    const r = await fetch(url, {
      credentials: 'same-origin'
    });
    const j = await r.json().catch(() => null);
    if (!j) throw new Error('Respuesta inválida del servidor');
    if (j.ok === false) throw new Error(j.detalle || j.error || 'Error servidor');
    return j;
  }

  let allRules = [];
  let allRewards = [];

  async function loadPromoData() {
    const j = await apiGet(`${API}?action=get&id=${promoId}`);

    allRules = j.rules || [];
    allRewards = j.rewards || [];

    const sel = document.getElementById('ruleSelect');
    sel.innerHTML = '';

    if (!allRules.length) {
      sel.innerHTML = '<option value="">(Sin reglas)</option>';
      return;
    }

    allRules.forEach(r => {
      const opt = document.createElement('option');
      opt.value = r.id_rule;
      opt.textContent = `Nivel ${r.nivel} · ${r.trigger_tipo}`;
      sel.appendChild(opt);
    });

    if (presetRuleId) sel.value = presetRuleId;
  }


  function getCurrentRuleId() {
    const v = document.getElementById('ruleSelect').value;
    return v ? v : null;
  }

  function initTable() {
    const ruleId = getCurrentRuleId();

    if (!ruleId) {
      if (tabla) tabla.clear().draw();
      return;
    }

    const rows = allRewards.filter(r => String(r.id_rule) === String(ruleId));

    if (!tabla) {
      tabla = $('#tablaRewards').DataTable({
        data: rows,
        columns: [{
            data: null,
            orderable: false,
            render: function(r) {
              return `
              <button class="btn btn-sm btn-outline-danger"
                onclick="delReward(${r.id_reward})">
                Eliminar
              </button>`;
            }
          },
          {
            data: 'id_reward'
          },
          {
            data: 'reward_tipo'
          },
          {
            data: 'valor'
          },
          {
            data: 'tope_valor'
          },
          {
            data: 'cve_articulo'
          },
          {
            data: 'qty'
          },
          {
            data: 'unimed'
          },
          {
            data: 'aplica_sobre'
          },
          {
            data: 'observaciones'
          },
          {
            data: 'activo'
          }
        ],
        order: [
          [1, 'desc']
        ]
      });
    } else {
      tabla.clear().rows.add(rows).draw();
    }
  }

  function buscarReward(allowAutoPick = false) {
    const modo = document.getElementById('reward_modo').value;
    const q = document.getElementById('rw_q').value.trim();

    if (!q) return;

    let url = '';
    if (modo === 'PRODUCTO') {
      url = API_ARTICULOS + '?action=list&q=' + encodeURIComponent(q);
    } else {
      url = API_GRUPOS + '?action=list&q=' + encodeURIComponent(q);
    }

    fetchJson(url).then(j => {
      const rows = normalizeRows(j);

      const onPick = (pick) => {
        document.getElementById('rw_tipo_sel').value = modo;
        document.getElementById('rw_val_sel').value = pick.val;
        document.getElementById('rw_label_sel').value = pick.label;

        document.getElementById('rw_sel').innerHTML = `
        <span class="ap-chip">
          <span class="text-muted">${modo}</span>
          <b>${escapeHtml(pick.label)}</b>
        </span>
      `;

        document.getElementById('rw_res').innerHTML = '';
        document.getElementById('rw_q').value = '';
      };

      if (rows.length === 1 && allowAutoPick) {
        onPick(rowToPick(rows[0], modo));
        return;
      }

      renderList('rw', rows, modo, onPick);
    });
  }

  // =========================
  // AUTOCOMPLETE LIVE (igual que promo_design)
  // =========================
  const rwTimers = {};
  const RW_LIVE_MIN_CHARS = 2;
  const RW_LIVE_DEBOUNCE = 220;

  const rwInput = document.getElementById('rw_q');
  if (rwInput) {

    rwInput.addEventListener('input', () => {
      const v = rwInput.value.trim();

      if (v.length < RW_LIVE_MIN_CHARS) {
        document.getElementById('rw_res').innerHTML = '';
        return;
      }

      clearTimeout(rwTimers.live);
      rwTimers.live = setTimeout(() => {
        buscarReward(false); // 🔹 NO autopick
      }, RW_LIVE_DEBOUNCE);
    });

    rwInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        buscarReward(true); // 🔹 autopick si solo hay uno
      }
      if (e.key === 'Escape') {
        document.getElementById('rw_res').innerHTML = '';
      }
    });
  }



  async function addReward() {
    const ruleId = getCurrentRuleId();
    if (!ruleId) {
      alert('Selecciona una regla.');
      return;
    }

    const rewardTipo = document.getElementById('reward_tipo').value;

    // =========================
    // Validaciones
    // =========================
    if (rewardTipo === 'BONIF_PRODUCTO') {
      if (!document.getElementById('rw_val_sel').value) {
        alert('Selecciona un producto o grupo para el beneficio.');
        return;
      }
      if (!document.getElementById('qty').value) {
        alert('Indica la cantidad del producto regalo.');
        return;
      }
      if (!document.getElementById('unimed').value) {
        alert('Selecciona la unidad de medida.');
        return;
      }
    } else {
      if (!document.getElementById('valor').value) {
        alert('Indica el valor del descuento.');
        return;
      }
    }

    // =========================
    // Payload base
    // =========================
    const payload = new URLSearchParams();
    payload.set('action', 'reward_save');
    payload.set('id_rule', ruleId);
    payload.set('reward_tipo', rewardTipo);

    // =========================
    // Descuentos
    // =========================
    if (rewardTipo !== 'BONIF_PRODUCTO') {
      payload.set('valor', document.getElementById('valor').value);
      payload.set('tope_valor', document.getElementById('tope_valor').value || '');
      payload.set(
        'aplica_sobre',
        document.getElementById('aplica_sobre').value || 'TOTAL'
      );
    } else {
      // Producto regalo → valor siempre 0
      payload.set('valor', '0');
    }

    // =========================
    // Producto / Grupo
    // =========================
    const tipoSel = document.getElementById('rw_tipo_sel').value;
    const valSel = document.getElementById('rw_val_sel').value;

    if (tipoSel === 'PRODUCTO') {
      payload.set('cve_articulo', valSel);
    } else if (tipoSel === 'GRUPO') {
      payload.set('cve_gpoart', valSel);
    }

    // =========================
    // Cantidades
    // =========================
    payload.set('qty', document.getElementById('qty').value || '');
    payload.set('unimed', document.getElementById('unimed').value || '');

    payload.set(
      'observaciones',
      document.getElementById('observaciones').value || ''
    );

    // =========================
    // Envío
    // =========================
    const r = await fetch(API, {
      method: 'POST',
      body: payload,
      credentials: 'same-origin'
    });

    const j = await r.json().catch(() => null);

    if (!j || j.ok === false) {
      alert(
        (j && (j.detalle || j.error)) ?
        (j.detalle || j.error) :
        'Error al guardar beneficio'
      );
      return;
    }

    // =========================
    // Limpieza UI
    // =========================
    document.getElementById('valor').value = '';
    document.getElementById('tope_valor').value = '';
    document.getElementById('aplica_sobre').value = 'TOTAL';

    document.getElementById('rw_q').value = '';
    document.getElementById('rw_sel').innerHTML = '';
    document.getElementById('rw_tipo_sel').value = '';
    document.getElementById('rw_val_sel').value = '';
    document.getElementById('rw_label_sel').value = '';

    document.getElementById('qty').value = '';
    document.getElementById('unimed').value = '';
    document.getElementById('observaciones').value = '';

    // Reaplica visibilidad correcta
    if (typeof setRewardUI === 'function') {
      setRewardUI();
    }

    await loadPromoData();
    initTable();
  }


  function delReward(id) {
    rewardToDeleteId = id;

    const modal = new bootstrap.Modal(
      document.getElementById('modalConfirmRewardDelete')
    );
    modal.show();
  }



  window.delReward = delReward;

  (async function() {
    await loadPromoData();
    initTable();

    document.getElementById('ruleSelect').addEventListener('change', function() {
      initTable();
    });
    document.getElementById('btnAdd').addEventListener('click', addReward);
  })();



  document.addEventListener('DOMContentLoaded', () => {

    document.getElementById('btnConfirmDeleteReward')
      .addEventListener('click', async () => {

        if (!rewardToDeleteId) return;

        try {
          const payload = new URLSearchParams({
            action: 'reward_del',
            id_reward: rewardToDeleteId
          });

          const r = await fetch(API, {
            method: 'POST',
            body: payload,
            credentials: 'same-origin'
          });

          const j = await r.json();

          if (!j || j.ok === false) {
            throw new Error(j?.error || 'Error al eliminar');
          }

          rewardToDeleteId = null;

          bootstrap.Modal.getInstance(
            document.getElementById('modalConfirmRewardDelete')
          ).hide();

          await loadPromoData();
          initTable();

        } catch (e) {
          console.error(e);
          alert('No se pudo eliminar el beneficio');
        }
      });


    // ENTER en autocomplete
    const rwInput = document.getElementById('rw_q');
    if (rwInput) {
      rwInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          buscarReward(true);
        }
      });
    }

    function setRewardUI() {
      const tipo = document.getElementById('reward_tipo').value;

      // 1. Ocultar todo
      document.querySelectorAll('.reward-producto')
        .forEach(el => el.style.display = 'none');

      document.querySelectorAll('.reward-descuento')
        .forEach(el => el.style.display = 'none');

      // 2. Mostrar según tipo
      if (tipo === 'BONIF_PRODUCTO') {

        document.querySelectorAll('.reward-producto')
          .forEach(el => el.style.display = 'block');

        const valor = document.getElementById('valor');
        if (valor) {
          valor.value = '0';
          valor.disabled = true;
        }

      } else {

        document.querySelectorAll('.reward-descuento')
          .forEach(el => el.style.display = 'block');

        const valor = document.getElementById('valor');
        if (valor) valor.disabled = false;

        // limpiar autocomplete
        ['rw_q', 'rw_tipo_sel', 'rw_val_sel', 'rw_label_sel'].forEach(id => {
          const el = document.getElementById(id);
          if (el) el.value = '';
        });

        const rwSel = document.getElementById('rw_sel');
        if (rwSel) rwSel.innerHTML = '';

        // limpiar qty y UM
        ['qty', 'unimed'].forEach(id => {
          const el = document.getElementById(id);
          if (el) el.value = '';
        });
      }
    }

    // 🔹 cambio de tipo
    const rewardTipo = document.getElementById('reward_tipo');
    if (rewardTipo) {
      rewardTipo.addEventListener('change', setRewardUI);
    }

    // 🔹 estado inicial
    setRewardUI();
  });

  window.buscarReward = buscarReward;
</script>
<div class="modal fade" id="modalConfirmRewardDelete" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title text-danger">
          <i class="fa fa-trash"></i> Eliminar beneficio
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        ¿Deseas eliminar este beneficio?
        <div class="text-muted small mt-1">
          El beneficio se desactivará y dejará de aplicar.
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline-secondary btn-sm"
          data-bs-dismiss="modal">
          Cancelar
        </button>
        <button class="btn btn-danger btn-sm" id="btnConfirmDeleteReward">
          Eliminar
        </button>
      </div>

    </div>
  </div>
</div>

<?php include '../../bi/_menu_global_end.php'; ?>