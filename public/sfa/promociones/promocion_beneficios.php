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
            <option value="BONIF_PRODUCTO">BONIF_PRODUCTO</option>
            <option value="DESC_PCT">DESC_PCT</option>
            <option value="DESC_MONTO">DESC_MONTO</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Valor</label>
          <input id="valor" type="number" step="0.0001" class="form-control" placeholder="0.00">
        </div>
        <div class="col-md-2">
          <label class="form-label">Tope</label>
          <input id="tope_valor" type="number" step="0.0001" class="form-control" placeholder="(opcional)">
        </div>
        <div class="col-md-3">
          <label class="form-label">Artículo (si aplica)</label>
          <input id="cve_articulo" type="text" class="form-control" placeholder="SKU / Clave">
        </div>
        <div class="col-md-2">
          <label class="form-label">Qty</label>
          <input id="qty" type="number" step="0.0001" class="form-control" placeholder="(opcional)">
        </div>

        <div class="col-md-3">
          <label class="form-label">UM</label>
          <input id="unimed" type="text" class="form-control" placeholder="(opcional)">
        </div>
        <div class="col-md-3">
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


  async function addReward() {
    const ruleId = getCurrentRuleId();
    if (!ruleId) {
      alert('Selecciona una regla.');
      return;
    }

    const payload = new URLSearchParams();
    payload.set('action', 'reward_save');
    payload.set('id_rule', ruleId);
    payload.set('reward_tipo', document.getElementById('reward_tipo').value);
    payload.set('valor', document.getElementById('valor').value || '0');
    payload.set('tope_valor', document.getElementById('tope_valor').value || '');
    payload.set('cve_articulo', document.getElementById('cve_articulo').value || '');
    payload.set('qty', document.getElementById('qty').value || '');
    payload.set('unimed', document.getElementById('unimed').value || '');
    payload.set('aplica_sobre', document.getElementById('aplica_sobre').value || 'TOTAL');
    payload.set('observaciones', document.getElementById('observaciones').value || '');

    const r = await fetch(API, {
      method: 'POST',
      body: payload,
      credentials: 'same-origin'
    });
    const j = await r.json().catch(() => null);

    if (!j || j.ok === false) {
      alert((j && (j.detalle || j.error)) ? (j.detalle || j.error) : 'Error al guardar beneficio');
      return;
    }

    // Limpieza rápida
    document.getElementById('valor').value = '';
    document.getElementById('tope_valor').value = '';
    document.getElementById('cve_articulo').value = '';
    document.getElementById('qty').value = '';
    document.getElementById('unimed').value = '';
    document.getElementById('observaciones').value = '';

    await loadPromoData();
    initTable();

  }

  async function delReward(id) {
    if (!id) return;
    if (!confirm('¿Eliminar beneficio?')) return;
    await fetch(`${API}?action=reward_del&id_reward=${encodeURIComponent(id)}`);
    await loadPromoData();
    initTable();

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
</script>

<?php include '../../bi/_menu_global_end.php'; ?>