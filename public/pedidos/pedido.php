<?php
// public/pedido.php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: text/html; charset=utf-8');

include_once __DIR__ . '/../../app/db.php'; // conexión central

/* ========= Helpers ligeros (sin cambios de consultas) ========= */
function rows(PDO $pdo, string $sql, array $p = []): array {
  $st = $pdo->prepare($sql);
  $st->execute($p);
  return $st->fetchAll(PDO::FETCH_ASSOC);
}
function one(PDO $pdo, string $sql, array $p = []) {
  $st = $pdo->prepare($sql);
  $st->execute($p);
  return $st->fetchColumn();
}
function sel(array $data, $value, string $valueKey='id', string $textKey='nombre'): string {
  $h = '';
  foreach ($data as $r) {
    $v = (string)$r[$valueKey];
    $t = htmlspecialchars((string)$r[$textKey]);
    $h .= '<option value="'.$v.'"'.($v==(string)$value?' selected':'').'>'.$t.'</option>';
  }
  return $h;
}

/* ========= Encabezado (sin cambios de lógica) ========= */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$enc = [
  'empresa_id' => (int)($_SESSION['empresa_id'] ?? 1),
  'almacen_id' => (int)($_SESSION['almacen_id'] ?? 1),
  'tipo_id'    => 1,
  'status_id' => 1,
  'cliente_id' => null,
  'ruta_id'    => null,
  'proyecto_id'=> null,
  'folio'      => '',
  'fecha'      => date('Y-m-d'),
  'comentarios'=> ''
];

if ($id > 0) {
  $enc = rows($pdo,
    "SELECT id, empresa_id, almacen_id, tipo_id, status_id, cliente_id, ruta_id, proyecto_id,
            folio, fecha, comentarios
     FROM th_pedido WHERE id=:id", [':id'=>$id])[0] ?? $enc;
}

/* ========= Catálogos (consultas SIN CAMBIOS) ========= */
$empresas  = rows($pdo, "SELECT id, nombre FROM c_empresa ORDER BY nombre");
$almacenes = rows($pdo,
  "SELECT a.id, CONCAT(a.clave,' — ',a.nombre) AS nombre
   FROM c_almacen a
   WHERE (a.activo=1 OR a.activo IS NULL)
     AND (a.empresa_id=:e1 OR :e2=0)
   ORDER BY a.nombre",
  [':e1'=>$enc['empresa_id'], ':e2'=>$enc['empresa_id']]
);
$clientes  = rows($pdo,
  "SELECT id, CONCAT(clave,' — ',nombre) AS nombre
   FROM c_cliente
   WHERE (activo=1 OR activo IS NULL)
   ORDER BY nombre");
$rutas     = rows($pdo,
  "SELECT id, CONCAT(clave,' — ',nombre) AS nombre
   FROM c_ruta
   WHERE (activo=1 OR activo IS NULL)
   ORDER BY nombre");
$proyectos = rows($pdo,
  "SELECT id, CONCAT(clave,' — ',nombre) AS nombre
   FROM c_proyecto
   WHERE (activo=1 OR activo IS NULL)
     AND (empresa_id=:e1 OR :e2=0)
   ORDER BY nombre",
  [':e1'=>$enc['empresa_id'], ':e2'=>$enc['empresa_id']]
);
$tipos     = rows($pdo,
  "SELECT id, CONCAT(clave,' — ',nombre) AS nombre
   FROM c_pedido_tipo
   WHERE (activo=1 OR activo IS NULL)
   ORDER BY nombre");
$status   = rows($pdo,
  "SELECT id, CONCAT(clave,' — ',nombre) AS nombre
   FROM c_pedido_status
   WHERE (activo=1 OR activo IS NULL)
   ORDER BY id");

/* ========= Productos para el select (sin tocar consulta; usa tu vista/tabla actual) =========
   Si ya tenías un endpoint para autocompletar, puedes ignorar este listado y dejar el <select> vacío:
   aquí solo aseguramos que el control exista visualmente.
*/
$productos = rows($pdo,
  "SELECT id, CONCAT(clave,' — ',nombre) AS nombre
   FROM c_producto
   ORDER BY nombre
   LIMIT 500"); // solo para que cargue rápido el UI
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>AssistPro SFA — Editar | Crear Pedido</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root{ --primary:#000F9F; --muted:#EEF1F4; --text:#191817; --ink:#1f2328; }
*{box-sizing:border-box}
html,body{height:100%;margin:0;font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;color:var(--text)}
body{background:#fafbfc}
h1{margin:10px 12px;font-size:20px;color:var(--primary);font-weight:800}
.card{background:#fff;border:1px solid var(--muted);border-radius:10px;margin:10px 12px;padding:10px}
.grid{display:grid;grid-template-columns:repeat(12,1fr);gap:8px}
.col-2{grid-column:span 2}.col-3{grid-column:span 3}.col-4{grid-column:span 4}.col-6{grid-column:span 6}.col-12{grid-column:span 12}
label{display:block;font-size:12px;color:#667085;margin-bottom:4px}
input,select,textarea{width:100%;padding:8px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;font-size:13px}
.badge{display:inline-block;font-size:12px;padding:4px 8px;border-radius:999px;background:#eef2ff;color:#1d4ed8}
.row{display:flex;gap:8px;align-items:center}
.btn{border:0;border-radius:10px;padding:8px 12px;font-weight:700;cursor:pointer}
.btn-primary{background:#1d4ed8;color:#fff}
.btn-secondary{background:#e5e7eb}
.btn-ghost{background:transparent;border:1px solid #e5e7eb}
.btn-xs{padding:4px 8px;font-size:12px;border-radius:8px}
.notice{font-size:12px;color:#6b7280}
.table{width:100%;border-collapse:collapse}
.table th,.table td{padding:8px;border-bottom:1px solid #f1f5f9;font-size:12px;vertical-align:middle}
.table th{color:#6b7280;text-align:left}
.actions{display:flex;gap:6px}
.hr{height:1px;background:#f1f5f9;margin:8px 0}
</style>
</head>
<body>

<h1>AssistPro SFA — <span class="badge"><?= $id>0?'Editar':'Crear'?> Pedido</span></h1>

<!-- Encabezado compacto -->
<div class="card">
  <div class="grid">
    <div class="col-2">
      <label>Folio</label>
      <input type="text" value="<?= htmlspecialchars((string)$enc['folio']) ?>" readonly>
    </div>
    <div class="col-2">
      <label>Fecha</label>
      <input type="date" value="<?= htmlspecialchars((string)$enc['fecha']) ?>">
    </div>
    <div class="col-4">
      <label>Empresa</label>
      <select id="empresa_id"><?= sel($empresas,$enc['empresa_id']) ?></select>
    </div>
    <div class="col-4">
      <label>Almacén</label>
      <select id="almacen_id"><?= sel($almacenes,$enc['almacen_id']) ?></select>
    </div>

    <div class="col-3">
      <label>Tipo de Pedido</label>
      <select id="tipo_id"><?= sel($tipos,$enc['tipo_id']) ?></select>
    </div>
    <div class="col-3">
      <label>status</label>
      <select id="status_id"><?= sel($status,$enc['status_id']) ?></select>
    </div>
    <div class="col-3">
      <label>Cliente</label>
      <select id="cliente_id">
        <option value="">— PÚBLICO GENERAL —</option>
        <?= sel($clientes,$enc['cliente_id']) ?>
      </select>
    </div>
    <div class="col-3">
      <label>Ruta</label>
      <select id="ruta_id">
        <option value="">— Sin ruta —</option>
        <?= sel($rutas,$enc['ruta_id']) ?>
      </select>
    </div>

    <div class="col-4">
      <label>Proyecto</label>
      <select id="proyecto_id">
        <option value="">— Sin proyecto —</option>
        <?= sel($proyectos,$enc['proyecto_id']) ?>
      </select>
    </div>
    <div class="col-8">
      <label>Comentarios</label>
      <input type="text" id="comentarios" value="<?= htmlspecialchars((string)($enc['comentarios']??'')) ?>">
    </div>
  </div>
  <div class="row" style="margin-top:8px">
    <button class="btn btn-primary" id="btn-guardar">Guardar Encabezado</button>
    <a class="btn btn-ghost" href="pedido_pdf.php?id=<?= (int)$id ?>" target="_blank" <?= $id? '':'style="pointer-events:none;opacity:.5"' ?>>PDF</a>
    <span class="notice">Títulos en azul, layout compacto (10px) y scroll natural del navegador.</span>
  </div>
</div>

<!-- Captura de detalle (Producto/Cliente visibles) -->
<div class="card">
  <div class="grid">
    <div class="col-6">
      <label>Producto</label>
      <!-- visible: select directo (si usas autocomplete vía JS/endpoint, reemplázalo; no cambié consultas) -->
      <select id="producto_id">
        <option value="">— Selecciona un producto —</option>
        <?= sel($productos, '', 'id', 'nombre') ?>
      </select>
    </div>
    <div class="col-2">
      <label>UOM</label>
      <input type="text" id="uom" placeholder="PZA" readonly>
    </div>
    <div class="col-2">
      <label>Cantidad</label>
      <input type="number" id="cantidad" step="1" min="0">
    </div>
    <div class="col-2">
      <label>Precio (Neto)</label>
      <input type="number" id="precio" step="0.0001" min="0">
    </div>
    <div class="col-12 row" style="justify-content:flex-end">
      <button class="btn btn-secondary btn-xs" id="btn-agregar" disabled>Agregar</button>
    </div>
  </div>

  <div class="hr"></div>

  <table class="table">
    <thead>
      <tr>
        <th style="width:120px">Acciones</th>
        <th>#</th>
        <th>Clave</th>
        <th>Producto</th>
        <th>UOM</th>
        <th style="text-align:right">Cantidad</th>
        <th style="text-align:right">Precio</th>
        <th style="text-align:right">Subtotal</th>
      </tr>
    </thead>
    <tbody id="grid-detalle">
      <tr><td colspan="8" class="notice">Sin partidas aún.</td></tr>
    </tbody>
  </table>
</div>

<script>
// Activación de "Agregar" solo para UI (no toca endpoints/consultas)
const prodSel = document.getElementById('producto_id');
const qtyInp  = document.getElementById('cantidad');
const priceInp= document.getElementById('precio');
const addBtn  = document.getElementById('btn-agregar');

function toggleAdd(){
  addBtn.disabled = !(prodSel.value && (+qtyInp.value>0));
}
['change','input'].forEach(ev=>{
  prodSel.addEventListener(ev,toggleAdd);
  qtyInp.addEventListener(ev,toggleAdd);
  priceInp.addEventListener(ev,toggleAdd);
});
toggleAdd();

// Guardar encabezado (igual que antes)
document.getElementById('btn-guardar')?.addEventListener('click', async () => {
  const payload = {
    id: <?= (int)$id ?>,
    empresa_id: +document.getElementById('empresa_id').value || 0,
    almacen_id: +document.getElementById('almacen_id').value || 0,
    tipo_id: +document.getElementById('tipo_id').value || 0,
    status_id: +document.getElementById('status_id').value || 0,
    cliente_id: document.getElementById('cliente_id').value || null,
    ruta_id: document.getElementById('ruta_id').value || null,
    proyecto_id: document.getElementById('proyecto_id').value || null,
    comentarios: document.getElementById('comentarios').value || ''
  };
  try{
    const r = await fetch('pedido_guardar.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
    const j = await r.json();
    if(!j.ok) throw new Error(j.msg||'Error al guardar');
    alert('Encabezado guardado');
    if(j.id && <?= (int)$id ?>===0){ location.href='pedido.php?id='+j.id; }
  }catch(err){ alert(err.message); }
});

// Grid de ejemplo con acciones (solo visual; integra tu fuente real de líneas)
function renderRow(idx,data){
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td>
      <div class="actions">
        <button class="btn btn-ghost btn-xs">✏️ Editar</button>
        <a class="btn btn-secondary btn-xs" href="pedido_pdf.php?id=<?= (int)$id ?>" target="_blank" <?= $id? '':'style="pointer-events:none;opacity:.5"' ?>>PDF</a>
      </div>
    </td>
    <td>${idx}</td>
    <td>${data.clave||''}</td>
    <td>${data.nombre||''}</td>
    <td>${data.uom||''}</td>
    <td style="text-align:right">${Number(data.cantidad||0).toLocaleString()}</td>
    <td style="text-align:right">${Number(data.precio||0).toFixed(2)}</td>
    <td style="text-align:right">${(Number(data.cantidad||0)*Number(data.precio||0)).toFixed(2)}</td>
  `;
  return tr;
}
</script>

</body>
</html>
