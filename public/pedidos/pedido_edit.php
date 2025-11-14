<?php
// public/pedido_edit.php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../app/db.php'; // Debe exponer function pdo():PDO

$pdo = pdo();

// ------- Helpers simples (NO cambian tu modelo) ------- //
function h(?string $s): string { return htmlspecialchars((string)$s ?? '', ENT_QUOTES, 'UTF-8'); }
function money($n): string { return number_format((float)$n, 2, '.', ','); }
function dtYmd(?string $d): string { return $d ? (new DateTime($d))->format('Y-m-d') : ''; }
function pad($txt, $len, $align = 'L'): string {
    $txt = (string)$txt;
    $w = max(0, $len - mb_strlen($txt));
    if ($align === 'R') return str_repeat(' ', $w) . $txt;
    if ($align === 'C') { $l = intdiv($w,2); $r = $w - $l; return str_repeat(' ', $l) . $txt . str_repeat(' ', $r); }
    return $txt . str_repeat(' ', $w);
}

// ESC/POS minimal (Epson 3")
class EscPos {
    const ESC = "\x1B"; const GS = "\x1D";
    static function init(): string { return self::ESC."@"; }                          // Initialize
    static function align($c='L'): string { $m = ['L'=>0,'C'=>1,'R'=>2][$c] ?? 0; return self::ESC."a".chr($m); }
    static function bold($on=true): string { return self::ESC."E".chr($on?1:0); }
    static function dbl($on=true): string { return self::GS."!".chr($on?0x11:0x00);} // Double width+height
    static function cut(): string { return self::GS."V\x41\x00"; }                   // Full cut
    static function feed($n=1): string { return str_repeat("\n", max(0,(int)$n)); }
    static function line($text=''): string { return $text . "\n"; }
    static function hr($w=42): string { return str_repeat('-', $w) . "\n"; }
}

// ---------- Carga del pedido (solo lectura) ---------- //
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo "<h3>Pedido no especificado</h3>";
    exit;
}

$hdrSql = "SELECT h.*, 
                 e.nombre AS empresa, a.nombre AS almacen, c.nombre AS cliente
          FROM th_pedido h
          LEFT JOIN c_empresa e ON e.id = h.empresa_id
          LEFT JOIN c_almacen a ON a.id = h.almacen_id
          LEFT JOIN c_cliente c ON c.id = h.cliente_id
          WHERE h.id = ?";
$hdr = $pdo->prepare($hdrSql);
$hdr->execute([$id]);
$H = $hdr->fetch(PDO::FETCH_ASSOC);

if (!$H) {
    http_response_code(404);
    echo "<h3>Pedido $id no encontrado</h3>";
    exit;
}

// Partidas (con clave, uom y tasa IVA si aplica)
$detSql = "SELECT d.id, d.producto_id, p.clave, p.nombre AS producto,
                  COALESCE(d.uom_clave, p.uom_base) AS uom,
                  d.cantidad, d.precio_unit,
                  i.tasa AS iva_tasa
           FROM td_pedido d
           INNER JOIN c_producto p ON p.id = d.producto_id
           LEFT JOIN c_impuesto i ON i.id = p.impuesto_id
           WHERE d.pedido_id = ?
           ORDER BY d.id";
$det = $pdo->prepare($detSql);
$det->execute([$id]);
$rows = $det->fetchAll(PDO::FETCH_ASSOC);

// Totales
$subtotal = 0.0; $iva = 0.0; $articulos = 0; $pzas = 0.0;
foreach ($rows as $r) {
    $lineSub = (float)$r['cantidad'] * (float)$r['precio_unit'];
    $subtotal += $lineSub;
    $tasa = (float)($r['iva_tasa'] ?? 0);
    if ($tasa > 0) { $iva += $lineSub * $tasa; }
    $articulos++;
    $pzas += (float)$r['cantidad'];
}
$total = $subtotal + $iva;

// ---------- Acción: imprimir ticket ESC/POS ---------- //
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'print_ticket') {
    $W = 42; // caracteres de ancho (3”)
    $out  = EscPos::init();
    // Encabezado
    $out .= EscPos::align('C') . EscPos::bold(true) . EscPos::line(pad($H['empresa'] ?: 'EMPRESA', $W, 'C'));
    $out .= EscPos::bold(false);
    $out .= EscPos::line(pad($H['almacen'] ?: '', $W, 'C'));
    $out .= EscPos::line(pad("PEDIDO: " . ($H['folio'] ?? $H['id']), $W, 'C'));
    $out .= EscPos::line(pad("FECHA: " . dtYmd($H['fecha'] ?? date('Y-m-d')), $W, 'C'));
    if (!empty($H['cliente'])) $out .= EscPos::line(pad("CLIENTE: " . $H['cliente'], $W, 'C'));
    $out .= EscPos::hr($W);

    // Encabezados de columnas
    $out .= EscPos::align('L');
    $out .= EscPos::line(
        pad("CLAVE", 8) .
        pad("DESC", 18) .
        pad("CANT", 6, 'R') .
        pad("P.U.", 10, 'R')
    );
    $out .= EscPos::hr($W);

    foreach ($rows as $r) {
        $clave = mb_strimwidth((string)$r['clave'], 0, 8, '', 'UTF-8');
        $desc  = mb_strimwidth((string)$r['producto'], 0, 18, '', 'UTF-8');
        $cant  = money((float)$r['cantidad']);
        $pu    = money((float)$r['precio_unit']);
        $out .= EscPos::line(
            pad($clave, 8) .
            pad($desc, 18) .
            pad($cant, 6, 'R') .
            pad($pu, 10, 'R')
        );
    }
    $out .= EscPos::hr($W);

    // Totales
    $out .= EscPos::line(pad("Artículos: $articulos  Pzas: ".money($pzas), $W));
    $out .= EscPos::line(pad("SUBTOTAL:", 32, 'R') . pad(money($subtotal), 10, 'R'));
    $out .= EscPos::line(pad("IVA:", 32, 'R') . pad(money($iva), 10, 'R'));
    $out .= EscPos::bold(true);
    $out .= EscPos::line(pad("TOTAL:", 32, 'R') . pad(money($total), 10, 'R'));
    $out .= EscPos::bold(false);

    if (!empty($H['comentarios'])) {
        $out .= EscPos::hr($W);
        $out .= EscPos::line(mb_strimwidth("Notas: ".str_replace(["\r","\n"], ' ', (string)$H['comentarios']), 0, $W, '', 'UTF-8'));
    }

    $out .= EscPos::feed(3) . EscPos::cut();

    // 1) Descargar como archivo binario:
    $fname = 'ticket_' . ($H['folio'] ?? $H['id']) . '.bin';
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.$fname.'"');
    header('Content-Length: ' . strlen($out));
    echo $out;
    exit;

    /*
    // 2) Enviar directo a impresora compartida (opcional, Windows):
    //    Configura una ruta compartida válida (\\PC\IMPRESORA) y descomenta.
    $printerPath = '\\\\MI_PC\\EPSON_TM';
    $fh = @fopen($printerPath, 'wb');
    if ($fh) { fwrite($fh, $out); fclose($fh); }
    // (Si no hay permisos de spool, usa la descarga del .bin como arriba)
    */
}

// ---------- UI (mismo look & feel de OC) ---------- //
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>AssistPro SFA — Editar | Crear Pedido</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root{ --primary:#000F9F;--muted:#EEF1F4;--text:#191817;--ok:#15a34a;--warn:#ef4444; }
*{box-sizing:border-box} html,body{height:100%;margin:0;font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;color:var(--text)}
.wrap{padding:10px 14px}
.hd{display:flex;align-items:center;gap:10px;margin-bottom:8px}
.hd h1{font-size:18px;margin:0;color:var(--primary);font-weight:800}
.card{background:#fff;border:1px solid var(--muted);border-radius:10px;padding:10px;overflow:auto}
.grid{display:grid;grid-template-columns:repeat(6,minmax(160px,1fr));gap:8px}
.lbl{font-size:11px;color:#667085;margin-bottom:2px}
.inp, select, textarea{width:100%;padding:6px 8px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;background:#fff}
.ro{background:#f6f7fb}
.tbl{width:100%;border-collapse:collapse;font-size:12px;margin-top:8px;min-width:920px}
.tbl th,.tbl td{border-bottom:1px solid #eef2f7;padding:6px 8px;text-align:left;white-space:nowrap}
.tbl th{font-size:11px;color:#667085}
.actions .btn{margin-right:6px}
.btn{border:0;border-radius:8px;padding:6px 10px;font-size:13px;cursor:pointer}
.btn-blue{background:#0ea5e9;color:#fff} .btn-green{background:#16a34a;color:#fff}
.btn-gray{background:#e5e7eb} .btn-outline{background:#fff;border:1px solid #d1d5db}
.totals{margin-top:8px;display:flex;gap:16px;flex-wrap:wrap}
.kv{padding:8px 10px;border:1px dashed #e5e7eb;border-radius:8px;background:#fcfcff}
.kv b{display:block;color:#111827}
@media (max-width:1200px){ .grid{grid-template-columns:repeat(3,minmax(160px,1fr));} }
@media (max-width:700px){ .grid{grid-template-columns:repeat(2,minmax(140px,1fr));} .tbl{font-size:11px;}}
</style>
</head>
<body>
<div class="wrap">
  <div class="hd">
    <h1>AssistPro SFA — Editar | Crear Pedido</h1>
    <form method="post" style="margin-left:auto">
      <input type="hidden" name="action" value="print_ticket">
      <button class="btn btn-green" title="Imprimir Ticket ESC/POS">🧾 Imprimir Ticket</button>
    </form>
  </div>

  <div class="card">
    <div class="grid">
      <div><div class="lbl">Folio</div><input class="inp ro" value="<?=h($H['folio'] ?: ('P'.str_pad((string)$H['id'],6,'0',STR_PAD_LEFT)))?>" readonly></div>
      <div><div class="lbl">Fecha</div><input class="inp ro" value="<?=h(dtYmd($H['fecha'] ?? date('Y-m-d')))?>" readonly></div>
      <div><div class="lbl">Empresa</div><input class="inp ro" value="<?=h($H['empresa'])?>" readonly></div>
      <div><div class="lbl">Almacén</div><input class="inp ro" value="<?=h($H['almacen'])?>" readonly></div>
      <div><div class="lbl">Cliente</div><input class="inp ro" value="<?=h($H['cliente'] ?: 'PÚBLICO GENERAL')?>" readonly></div>
      <div><div class="lbl">Tipo</div><input class="inp ro" value="<?=h($H['tipo'])?>" readonly></div>
      <div><div class="lbl">Estatus</div><input class="inp ro" value="<?=h($H['estatus'])?>" readonly></div>
      <div><div class="lbl">Forma de pago</div><input class="inp ro" value="<?=h($H['forma_pago'] ?: '')?>" readonly></div>
      <div style="grid-column:1/-1"><div class="lbl">Comentarios</div><textarea class="inp ro" rows="2" readonly><?=h($H['comentarios'] ?? '')?></textarea></div>
    </div>

    <table class="tbl">
      <thead>
        <tr>
          <th>Acciones</th>
          <th>Clave</th>
          <th>Producto</th>
          <th>UOM</th>
          <th style="text-align:right">Cantidad</th>
          <th style="text-align:right">Precio</th>
          <th style="text-align:right">Subtotal</th>
          <th style="text-align:right">IVA</th>
          <th style="text-align:right">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="9">Sin partidas.</td></tr>
        <?php else: foreach ($rows as $r):
            $lineSub = (float)$r['cantidad'] * (float)$r['precio_unit'];
            $tasa = (float)($r['iva_tasa'] ?? 0);
            $lineIva = $tasa > 0 ? $lineSub * $tasa : 0;
            $lineTot = $lineSub + $lineIva;
        ?>
        <tr>
          <td class="actions">
            <a class="btn btn-outline" href="pedido_linea_edit.php?id=<?= (int)$r['id'] ?>">✏️</a>
            <a class="btn btn-outline" href="pedido_linea_del.php?id=<?= (int)$r['id'] ?>" onclick="return confirm('¿Eliminar línea?')">🗑️</a>
          </td>
          <td><?=h($r['clave'])?></td>
          <td><?=h($r['producto'])?></td>
          <td><?=h($r['uom'])?></td>
          <td style="text-align:right"><?=money($r['cantidad'])?></td>
          <td style="text-align:right"><?=money($r['precio_unit'])?></td>
          <td style="text-align:right"><?=money($lineSub)?></td>
          <td style="text-align:right"><?=money($lineIva)?></td>
          <td style="text-align:right"><?=money($lineTot)?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>

    <div class="totals">
      <div class="kv"><b># Artículos</b><?=h((string)$articulos)?></div>
      <div class="kv"><b>Total Piezas</b><?=money($pzas)?></div>
      <div class="kv"><b>Subtotal</b><?=money($subtotal)?></div>
      <div class="kv"><b>IVA</b><?=money($iva)?></div>
      <div class="kv"><b>Total</b><?=money($total)?></div>
    </div>
  </div>
</div>
</body>
</html>
