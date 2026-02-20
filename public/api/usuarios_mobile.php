<?php
// /public/sfa/mobile/index.php
session_start();
require_once __DIR__ . '/../../app/db.php';

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function upper($v){ return strtoupper(trim((string)$v)); }

$view = $_GET['view'] ?? 'login';
$err  = '';
$ok   = $_GET['ok'] ?? '';

/* =========================
   HELPERS: RUTA + CLIENTES
   Ajusta aquí cuando conectes tus APIs existentes.
========================= */
function sfa_get_ruta_usuario(PDO $pdo, string $cveUsuario): ?string {
  // Opción 1: si c_usuario.perfil/status no guarda ruta, puedes mapear por tabla de vendedores.
  // Por ahora, intentamos con reldaycli (si Cve_Vendedor = cve_usuario) y tomamos la primera ruta activa.
  $ruta = db_val("
    SELECT DISTINCT Cve_Ruta
    FROM reldaycli
    WHERE Cve_Vendedor = ?
    ORDER BY Cve_Ruta
    LIMIT 1
  ", [$cveUsuario]);
  return $ruta ?: null;
}

function sfa_clientes_por_ruta(PDO $pdo, string $ruta, string $cveUsuario): array {
  // Mostramos destinatarios asignados a esa ruta para el vendedor
  // reldaycli trae Id_Destinatario (tu “cliente operativo”)
  return db_all("
    SELECT
      r.Id_Destinatario,
      r.Cve_Cliente,
      r.Cve_Ruta,
      r.Cve_Vendedor,
      c.RazonSocial,
      c.RazonComercial,
      c.credito,
      c.limite_credito,
      c.saldo_actual,
      c.dias_credito,
      c.credito_actual,
      c.Activo
    FROM reldaycli r
    LEFT JOIN c_cliente c ON c.id_destinatario = r.Id_Destinatario
    WHERE r.Cve_Ruta = ?
      AND r.Cve_Vendedor = ?
    ORDER BY COALESCE(c.RazonComercial,c.RazonSocial), r.Id_Destinatario
    LIMIT 500
  ", [$ruta, $cveUsuario]);
}

/* =========================
   HELPERS: LISTA + PRECIOS
========================= */
function sfa_lista_activa_dest(PDO $pdo, int $idDest): ?array {
  return db_one("
    SELECT
      r.ListaP,
      l.Lista,
      l.FechaIni,
      l.FechaFin,
      l.id_moneda
    FROM relclilis r
    JOIN listap l ON l.id = r.ListaP
    WHERE r.Id_Destinatario = ?
      AND CURDATE() BETWEEN l.FechaIni AND l.FechaFin
    LIMIT 1
  ", [$idDest]);
}

function sfa_precio_art_dest(PDO $pdo, int $idDest, string $art): ?array {
  return db_one("
    SELECT d.PrecioMin, d.PrecioMax
    FROM relclilis r
    JOIN listap l     ON l.id = r.ListaP
    JOIN detallelp d  ON d.ListaId = r.ListaP
    WHERE r.Id_Destinatario = ?
      AND d.Cve_Articulo = ?
      AND CURDATE() BETWEEN l.FechaIni AND l.FechaFin
    LIMIT 1
  ", [$idDest, $art]);
}

function sfa_articulos_lista(PDO $pdo, int $listaId, string $q=''): array {
  $q = trim($q);
  $params = [$listaId];
  $where = "";
  if($q !== ''){
    $where = " AND d.Cve_Articulo LIKE ? ";
    $params[] = "%{$q}%";
  }
  // Nota: aquí solo devolvemos clave. Si tienes catálogo productos con descripción, lo unimos después.
  return db_all("
    SELECT d.Cve_Articulo, d.PrecioMin, d.PrecioMax
    FROM detallelp d
    WHERE d.ListaId = ?
    $where
    ORDER BY d.Cve_Articulo
    LIMIT 200
  ", $params);
}

/* =========================
   HELPERS: CREDITO
========================= */
function sfa_credito_bloqueado(array $cli): bool {
  // Regla simple con campos que vimos:
  // - credito (1/0)
  // - saldo_actual
  // - limite_credito
  // - credito_actual (si lo manejas como vencido/actual)
  // Aquí dejamos criterio: si credito=1 y saldo_actual > limite_credito => bloquea
  $credito = (int)($cli['credito'] ?? 0);
  if(!$credito) return false;

  $lim = (float)($cli['limite_credito'] ?? 0);
  $saldo = (float)($cli['saldo_actual'] ?? 0);

  // Si existe un flag/estado “vencido” en otra columna, lo conectamos después.
  return ($lim > 0 && $saldo > $lim);
}

/* =========================
   LOGIN REAL (c_usuario)
========================= */
if(isset($_POST['action']) && $_POST['action']==='login'){
  $u = upper($_POST['usuario'] ?? '');
  $p = (string)($_POST['password'] ?? '');

  $user = db_one("
    SELECT id_user, cve_usuario, nombre_completo, perfil, cve_cia, id_tipo_usuario, COALESCE(Activo,1) Activo
    FROM c_usuario
    WHERE cve_usuario = ?
      AND pwd_usuario = ?
      AND COALESCE(Activo,1)=1
    LIMIT 1
  ", [$u, $p]);

  if($user){
    $_SESSION['sfa_user'] = $user;

    $ruta = sfa_get_ruta_usuario(db_pdo(), $user['cve_usuario']);
    $_SESSION['sfa_ruta'] = $ruta ?: '';

    header("Location: index.php?view=clientes");
    exit;
  } else {
    $err = "Acceso inválido";
    $view = 'login';
  }
}

/* =========================
   LOGOUT
========================= */
if($view==='logout'){
  session_destroy();
  header("Location: index.php");
  exit;
}

/* =========================
   GUARDAR PREVENTA
========================= */
if(isset($_POST['action']) && $_POST['action']==='guardar_preventa'){
  if(empty($_SESSION['sfa_user'])){ header("Location: index.php"); exit; }

  $user = $_SESSION['sfa_user'];
  $idDest = (int)($_POST['Id_Destinatario'] ?? 0);
  $tipoPago = upper($_POST['tipo_pago'] ?? 'CONTADO'); // CONTADO|CREDITO
  $fecEntrega = $_POST['fec_entrega'] ?? date('Y-m-d');
  $ventana = trim((string)($_POST['ventana_horario'] ?? '')); // ej. "09:00-13:00"

  $arts = $_POST['art'] ?? [];
  $qtys = $_POST['qty'] ?? [];
  $precios = $_POST['precio'] ?? [];

  // Validaciones mínimas
  if($idDest<=0) { $err="Cliente inválido"; $view='clientes'; }
  if($ventana==='') { $err="Captura ventana/horario de entrega"; $view='preventa'; }

  // Cliente + crédito
  $cli = db_one("SELECT * FROM c_cliente WHERE id_destinatario=? LIMIT 1", [$idDest]) ?: [];
  $esCredito = (int)($cli['credito'] ?? 0) === 1;

  if($esCredito && $tipoPago==='CREDITO' && sfa_credito_bloqueado($cli)){
    $err = "Crédito vencido o excedido: no se permite la venta.";
    $view = 'preventa';
  }

  if(!$err){
    db_tx(function() use($idDest,$tipoPago,$fecEntrega,$ventana,$arts,$qtys,$precios,$user){

      // Folio (temporal). Luego lo amarramos a tu c_folios / SP de folio diario.
      $folio = "SFA".date("YmdHis");

      $total = 0;

      // Encabezado: status A Abierta | TipoPedido PREVENTA
      dbq("
        INSERT INTO th_pedido
        (Fol_folio, Fec_Pedido, Cve_clte, status,
         Fec_Entrega, cve_Vendedor, TipoPedido,
         ruta, cve_almac, Cve_Usuario,
         tipo_venta, Tot_Factura, Observaciones, Activo, Fec_Entrada)
        VALUES
        (?, CURDATE(), ?, 'A',
         ?, ?, 'PREVENTA',
         ?, ?, ?,
         ?, 0, ?, 1, NOW())
      ", [
        $folio,
        (string)$idDest,                 // tu pedido guarda Id_Destinatario
        $fecEntrega,
        $user['cve_usuario'],
        $_SESSION['sfa_ruta'] ?? '',
        '',                               // cve_almac no se evalúa en precio; si deseas guardar uno operativo, aquí
        $user['cve_usuario'],
        $tipoPago,                        // tipo_venta: CONTADO/CREDITO (tu regla)
        "VENTANA: {$ventana}"
      ]);

      foreach($arts as $i=>$art){
        $art = trim((string)$art);
        $qty = (float)($qtys[$i] ?? 0);
        $pv  = (float)($precios[$i] ?? 0);

        if($art==='' || $qty<=0) continue;

        // Regla ABSOLUTA contra lista asignada al destinatario
        $rg = sfa_precio_art_dest(db_pdo(), $idDest, $art);
        if(!$rg){
          throw new Exception("Artículo no permitido en lista: {$art}");
        }
        $min = (float)$rg['PrecioMin'];
        $max = (float)$rg['PrecioMax'];

        if($pv < $min || $pv > $max){
          throw new Exception("Precio fuera de rango para {$art} (min {$min} max {$max})");
        }

        $subtotal = $pv * $qty;
        $iva = $subtotal * 0.16;
        $total += ($subtotal + $iva);

        dbq("
          INSERT INTO td_pedido
          (Fol_folio, Cve_articulo, Num_cantidad,
           Precio_unitario, Desc_Importe, IVA,
           Valor_Comercial_MN, Activo, status)
          VALUES
          (?, ?, ?, ?, 0, ?, ?, 1, 'A')
        ", [
          $folio, $art, $qty, $pv, $iva, $subtotal
        ]);
      }

      dbq("UPDATE th_pedido SET Tot_Factura=? WHERE Fol_folio=?", [$total, $folio]);

      // Guardamos folio en sesión para ticket
      $_SESSION['sfa_last_folio'] = $folio;
    });

    header("Location: index.php?view=ticket");
    exit;
  }
}

/* =========================
   UI
========================= */
$logged = !empty($_SESSION['sfa_user']);
$user = $_SESSION['sfa_user'] ?? null;
$ruta = $_SESSION['sfa_ruta'] ?? '';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"/>
<title>SFA • AssistPro ER</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<style>
  :root{--blue:#0b3a82;--bg:#f3f6fb;--card:#fff;--muted:#6c757d;--shadow:0 10px 25px rgba(0,0,0,.12);}
  *{box-sizing:border-box;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;}
  body{margin:0;background:var(--bg);}
  .wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:14px;}
  .card{width:420px;max-width:100%;background:var(--card);border-radius:18px;box-shadow:var(--shadow);padding:16px 16px 14px;}
  .brand{display:flex;align-items:center;gap:10px;margin-bottom:8px;}
  .logo{width:44px;height:44px;border-radius:12px;background:var(--blue);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800}
  h1{font-size:18px;margin:0;color:#0b3a82}
  .sub{font-size:12px;color:var(--muted);margin-top:2px}
  .row{display:flex;gap:10px;align-items:center;justify-content:space-between;margin:10px 0;}
  .pill{font-size:12px;background:#eef2ff;color:var(--blue);padding:6px 10px;border-radius:999px;font-weight:700}
  .input, select{width:100%;padding:12px;border:1px solid #e6eaf2;border-radius:12px;font-size:14px;outline:none;background:#fff;}
  .btn{width:100%;padding:12px;border:none;border-radius:14px;font-weight:800;letter-spacing:.5px;cursor:pointer}
  .btn.primary{background:var(--blue);color:#fff}
  .btn.dark{background:#111827;color:#fff}
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
  .msg{font-size:12px;padding:10px;border-radius:12px;margin:10px 0}
  .msg.err{background:#ffe8e8;color:#b42318;border:1px solid #f5b5b5}
  .msg.ok{background:#e8fff0;color:#157f3b;border:1px solid #b4f0c8}
  .list{max-height:360px;overflow:auto;margin-top:10px;border-top:1px solid #eef1f7;padding-top:8px}
  .item{padding:10px;border:1px solid #eef1f7;border-radius:12px;margin-bottom:8px}
  .small{font-size:12px;color:var(--muted)}
  .actions{display:flex;gap:10px;margin-top:10px}
  .actions a{flex:1;text-align:center;text-decoration:none;display:block}
  .footer{margin-top:10px;text-align:center;font-size:11px;color:var(--muted)}
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <div class="brand">
      <div class="logo">A</div>
      <div>
        <h1>AssistPro ER • SFA</h1>
        <div class="sub"><?= $logged ? "Usuario: ".e($user['cve_usuario'])." • Ruta: ".e($ruta ?: '—') : "Acceso comercial (Preventa)" ?></div>
      </div>
      <?php if($logged): ?>
        <div class="pill">SFA</div>
      <?php endif; ?>
    </div>

    <?php if($err): ?><div class="msg err"><?= e($err) ?></div><?php endif; ?>
    <?php if($ok): ?><div class="msg ok">Operación realizada</div><?php endif; ?>

    <?php if(!$logged || $view==='login'): ?>
      <form method="post">
        <input type="hidden" name="action" value="login">
        <div class="row"><div class="small">Usuario</div></div>
        <input class="input" name="usuario" autocomplete="username" required>
        <div class="row"><div class="small">Password / PIN</div></div>
        <input class="input" name="password" type="password" autocomplete="current-password" required>
        <div style="height:10px"></div>
        <button class="btn primary">INGRESAR</button>
      </form>
      <div class="footer">Powered by Adventech Logística</div>

    <?php elseif($view==='clientes'): ?>
      <?php
        $clientes = $ruta ? sfa_clientes_por_ruta(db_pdo(), $ruta, $user['cve_usuario']) : [];
      ?>
      <button class="btn primary" onclick="location.href='?view=clientes'">CLIENTES DEL DÍA</button>
      <div class="list">
        <?php if(!$ruta): ?>
          <div class="msg err">Este usuario no tiene ruta asignada.</div>
        <?php endif; ?>
        <?php foreach($clientes as $c): ?>
          <?php
            $nombre = $c['RazonComercial'] ?: ($c['RazonSocial'] ?: ('Cliente '.$c['Id_Destinatario']));
            $bloq = sfa_credito_bloqueado($c) ? " • CRÉDITO BLOQUEADO" : "";
          ?>
          <div class="item" onclick="location.href='?view=preventa&dest=<?= (int)$c['Id_Destinatario'] ?>'">
            <div><b><?= e($nombre) ?></b></div>
            <div class="small">Dest: <?= (int)$c['Id_Destinatario'] ?> • Ruta: <?= e($c['Cve_Ruta']) ?><?= e($bloq) ?></div>
          </div>
        <?php endforeach; ?>
        <?php if(empty($clientes)): ?>
          <div class="small">Sin clientes asignados.</div>
        <?php endif; ?>
      </div>
      <div class="actions">
        <a class="btn dark" href="?view=logout">CERRAR SESIÓN</a>
      </div>

    <?php elseif($view==='preventa'):
      $idDest = (int)($_GET['dest'] ?? 0);
      $lista = $idDest ? sfa_lista_activa_dest(db_pdo(), $idDest) : null;
      $cli = $idDest ? (db_one("SELECT * FROM c_cliente WHERE id_destinatario=? LIMIT 1", [$idDest]) ?: []) : [];
      $nombre = $cli['RazonComercial'] ?? $cli['RazonSocial'] ?? ("Dest ".$idDest);
      $bloq = $cli ? sfa_credito_bloqueado($cli) : false;
      $esCredito = (int)($cli['credito'] ?? 0) === 1;
    ?>
      <div class="row">
        <div><b><?= e($nombre) ?></b><div class="small">Dest: <?= $idDest ?></div></div>
        <div class="pill">Ruta <?= e($ruta ?: '—') ?></div>
      </div>

      <?php if(!$lista): ?>
        <div class="msg err">Este destinatario no tiene lista vigente. No se puede vender.</div>
        <div class="actions">
          <a class="btn dark" href="?view=clientes">VOLVER</a>
        </div>
      <?php else: ?>
        <div class="small">Lista: <b><?= e($lista['Lista']) ?></b> • Vigencia <?= e($lista['FechaIni']) ?> a <?= e($lista['FechaFin']) ?></div>
        <?php if($bloq): ?>
          <div class="msg err">Cliente con crédito vencido/excedido. Solo se permite CONTADO (si aplica) o liberar crédito.</div>
        <?php endif; ?>

        <form method="post" id="frm">
          <input type="hidden" name="action" value="guardar_preventa">
          <input type="hidden" name="Id_Destinatario" value="<?= $idDest ?>">

          <div class="grid" style="margin-top:10px">
            <div>
              <div class="small">Fecha entrega</div>
              <input class="input" type="date" name="fec_entrega" value="<?= e(date('Y-m-d')) ?>" required>
            </div>
            <div>
              <div class="small">Ventana / horario</div>
              <input class="input" name="ventana_horario" placeholder="09:00-13:00" required>
            </div>
          </div>

          <div style="margin-top:10px">
            <div class="small">Tipo de venta</div>
            <select class="input" name="tipo_pago" id="tipo_pago">
              <option value="CONTADO">CONTADO</option>
              <?php if($esCredito): ?>
                <option value="CREDITO" <?= $bloq ? 'disabled' : '' ?>>CRÉDITO</option>
              <?php endif; ?>
            </select>
          </div>

          <div class="list" id="det"></div>

          <button type="button" class="btn primary" onclick="addRow()">AGREGAR ARTÍCULO</button>
          <div style="height:10px"></div>
          <button class="btn dark">GUARDAR PREVENTA</button>
        </form>

        <div class="actions">
          <a class="btn dark" href="?view=clientes">VOLVER</a>
        </div>

        <script>
          let idx=0;
          function addRow(){
            const det=document.getElementById('det');
            const div=document.createElement('div');
            div.className='item';
            div.innerHTML=`
              <div class="small">Artículo</div>
              <input class="input" name="art[]" placeholder="Cve_Articulo" required onblur="loadRango(this)">
              <div class="grid" style="margin-top:10px">
                <div>
                  <div class="small">Cantidad</div>
                  <input class="input" name="qty[]" type="number" step="0.01" min="0.01" value="1" required>
                </div>
                <div>
                  <div class="small">Precio (sugerido: MAX)</div>
                  <input class="input" name="precio[]" type="number" step="0.01" min="0" value="0" required>
                  <div class="small" data-range style="margin-top:4px"></div>
                </div>
              </div>
              <div style="margin-top:10px"><button type="button" class="btn dark" onclick="this.closest('.item').remove()">QUITAR</button></div>
            `;
            det.prepend(div);
            idx++;
          }

          async function loadRango(inp){
            const art = inp.value.trim();
            if(!art) return;
            const box = inp.closest('.item');
            const rangeEl = box.querySelector('[data-range]');
            const precioInp = box.querySelector('input[name="precio[]"]');

            const url = `index.php?view=ajax_rango&dest=<?= (int)$idDest ?>&art=${encodeURIComponent(art)}`;
            const r = await fetch(url);
            const j = await r.json();
            if(!j.ok){
              rangeEl.textContent = j.msg || 'No permitido';
              rangeEl.style.color = '#b42318';
              precioInp.value = 0;
              return;
            }
            const min = parseFloat(j.data.min);
            const max = parseFloat(j.data.max);
            rangeEl.textContent = `Rango permitido: ${min.toFixed(2)} - ${max.toFixed(2)}`;

            // sugerido: PrecioMax
            precioInp.min = min;
            precioInp.max = max;
            precioInp.value = max.toFixed(2);

            // fijo => readonly
            if(min === max){
              precioInp.readOnly = true;
            }else{
              precioInp.readOnly = false;
            }
          }
        </script>
      <?php endif; ?>

    <?php elseif($view==='ticket'):
      $folio = $_SESSION['sfa_last_folio'] ?? '';
      $h = $folio ? db_one("SELECT * FROM th_pedido WHERE Fol_folio=? LIMIT 1", [$folio]) : null;
      $d = $folio ? db_all("SELECT * FROM td_pedido WHERE Fol_folio=? ORDER BY id LIMIT 500", [$folio]) : [];
    ?>
      <div class="row">
        <div>
          <div class="small">Ticket preventa</div>
          <div style="font-size:18px;font-weight:900;color:var(--blue)"><?= e($folio ?: '—') ?></div>
        </div>
        <div class="pill">STATUS <?= e($h['status'] ?? '—') ?></div>
      </div>
      <?php if($h): ?>
        <div class="small">Cliente (Dest): <?= e($h['Cve_clte']) ?> • Entrega: <?= e($h['Fec_Entrega']) ?></div>
        <div class="small">Tipo venta: <?= e($h['tipo_venta']) ?> • Total: <b><?= e($h['Tot_Factura']) ?></b></div>
        <div class="small"><?= e($h['Observaciones'] ?? '') ?></div>
        <div class="list">
          <?php foreach($d as $r): ?>
            <div class="item">
              <div><b><?= e($r['Cve_articulo']) ?></b></div>
              <div class="small">Qty <?= e($r['Num_cantidad']) ?> • $<?= e($r['Precio_unitario']) ?> • IVA <?= e($r['IVA']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="msg err">No hay ticket.</div>
      <?php endif; ?>

      <div class="actions">
        <a class="btn primary" href="?view=clientes">NUEVA VENTA</a>
        <a class="btn dark" href="?view=logout">SALIR</a>
      </div>

    <?php elseif($view==='ajax_rango'):
      header('Content-Type: application/json; charset=utf-8');
      $idDest = (int)($_GET['dest'] ?? 0);
      $art = trim((string)($_GET['art'] ?? ''));
      if($idDest<=0 || $art===''){ echo json_encode(['ok'=>false,'msg'=>'Parámetros']); exit; }
      $rg = sfa_precio_art_dest(db_pdo(), $idDest, $art);
      if(!$rg){ echo json_encode(['ok'=>false,'msg'=>'Artículo no permitido']); exit; }
      echo json_encode(['ok'=>true,'data'=>['min'=>$rg['PrecioMin'],'max'=>$rg['PrecioMax']]]);
      exit;
    ?>
    <?php endif; ?>

    <div class="footer">Powered by Adventech Logística</div>
  </div>
</div>
</body>
</html>
