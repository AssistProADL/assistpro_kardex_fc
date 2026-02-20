<?php
require_once __DIR__ . '/../../bi/_menu_global.php';
?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Simulador de Promociones</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:Arial,Helvetica,sans-serif;margin:0;background:#f5f6fa}
    .wrap{padding:18px}
    .card{background:#fff;border:1px solid #ddd;border-radius:8px;padding:14px;margin-bottom:14px}
    .row{display:flex;gap:12px;flex-wrap:wrap}
    label{font-size:12px;color:#444;font-weight:600}
    input,select,button{padding:8px;border:1px solid #ccc;border-radius:6px;width:100%}
    .col{flex:1;min-width:220px}
    button{background:#0b3d91;color:#fff;border:none;cursor:pointer;font-weight:600}
    button:hover{opacity:.95}
    pre{background:#111;color:#eaeaea;padding:12px;border-radius:8px;overflow:auto;max-height:420px}
    .hint{font-size:12px;color:#666;margin-top:6px}
    h3{margin:0 0 10px 0}
  </style>
</head>
<body>

<div class="wrap">

  <div class="card">
    <h3>Simulador de Promociones</h3>

    <div class="row">
      <div class="col">
        <label>Promo ID *</label>
        <input id="promo_id" placeholder="Ej: PROMO_TEST_001">
        <div class="hint">Debe existir en <b>promo_rule</b> y <b>promo_reward</b></div>
      </div>

      <div class="col">
        <label>Cliente (opcional)</label>
        <input id="cliente" placeholder="CVE Cliente">
        <div class="hint">Aún no se valida scope</div>
      </div>
    </div>

    <div class="row" style="margin-top:12px;">
      <div class="col">
        <label>Monto simulado</label>
        <input id="monto_simulado" type="number" step="0.01" value="0">
        <div class="hint">Para reglas <b>MONTO</b> o <b>MIXTO</b></div>
      </div>

      <div class="col">
        <label>Cantidad simulada</label>
        <input id="qty_simulada" type="number" step="0.01" value="0">
        <div class="hint">Para reglas <b>UNIDADES</b> o <b>MIXTO</b></div>
      </div>

      <div class="col">
        <label>Unidad de Medida</label>
        <select id="unimed_simulada">
          <option value="">(opcional)</option>
          <option value="Pieza">Pieza</option>
          <option value="Caja">Caja</option>
          <option value="Paquete">Paquete</option>
          <option value="Tarima">Tarima</option>
        </select>
        <div class="hint">No se hace conversión de UM</div>
      </div>
    </div>

    <div class="row" style="margin-top:14px;">
      <div class="col">
        <button onclick="simular()">Simular promoción</button>
      </div>
    </div>

  </div>

  <div class="card">
    <h3>Resultado</h3>
    <pre id="out">{}</pre>
  </div>

</div>

<script>
async function simular(){
  const promo_id = document.getElementById('promo_id').value.trim();
  if(!promo_id){
    alert('Capture Promo ID');
    return;
  }

  const payload = {
    promo_id,
    cliente: document.getElementById('cliente').value.trim(),
    monto_simulado: parseFloat(document.getElementById('monto_simulado').value || '0'),
    qty_simulada: parseFloat(document.getElementById('qty_simulada').value || '0'),
    unimed_simulada: document.getElementById('unimed_simulada').value
  };

  document.getElementById('out').textContent = 'Procesando...';

  try{
    const res = await fetch('./api/promociones/simulate.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify(payload)
    });
    const js = await res.json();
    document.getElementById('out').textContent = JSON.stringify(js, null, 2);
  }catch(e){
    document.getElementById('out').textContent = 'Error: ' + (e.message || e);
  }
}
</script>

</body>
</html>

<?php
require_once __DIR__ . '/../../bi/_menu_global_end.php';
?>
