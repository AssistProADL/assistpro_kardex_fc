<?php
require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');
$pdo = db_pdo();

$op = $_GET['op'] ?? $_POST['op'] ?? 'ping';
function j($a){ echo json_encode($a); exit; }
function s($v){ $v=trim((string)$v); return $v===''?null:$v; }

try {

  if ($op==='folio_preview' || $op==='folio_next') {
    $prefijo = s($_GET['prefijo'] ?? $_POST['prefijo'] ?? 'OT') ?? 'OT';
    $yyyymmdd = date('Ymd');

    $pdo->beginTransaction();

    // Asegura fila
    $stmt = $pdo->prepare("INSERT IGNORE INTO t_folios(prefijo, yyyymmdd, ultimo) VALUES(?,?,0)");
    $stmt->execute([$prefijo, $yyyymmdd]);

    // Bloquea fila y calcula
    $stmt = $pdo->prepare("SELECT ultimo FROM t_folios WHERE prefijo=? AND yyyymmdd=? FOR UPDATE");
    $stmt->execute([$prefijo, $yyyymmdd]);
    $ultimo = (int)$stmt->fetchColumn();

    $next = $ultimo + 1;
    $folio = sprintf("%s%s-%05d", $prefijo, $yyyymmdd, $next);

    if ($op==='folio_next') {
      // Reserva (incrementa)
      $up = $pdo->prepare("UPDATE t_folios SET ultimo=? WHERE prefijo=? AND yyyymmdd=?");
      $up->execute([$next, $prefijo, $yyyymmdd]);
      $pdo->commit();
      j(['ok'=>true,'folio'=>$folio,'reservado'=>true]);
    } else {
      // Preview (no reserva)
      $pdo->rollBack();
      j(['ok'=>true,'folio'=>$folio,'reservado'=>false]);
    }
  }

  if ($op==='bl_produccion') {
    // BLs desde c_ubicacion con AreaProduccion='S'
    $cve_almac = s($_GET['cve_almac'] ?? '');
    $zona      = s($_GET['zona'] ?? '');

    $w = ["COALESCE(AreaProduccion,'N')='S'"];
    $p = [];

    if ($cve_almac) { $w[]="cve_almac=?"; $p[]=$cve_almac; }
    // Si tu c_ubicacion tiene campo de zona (ej. cve_zona / zona), ajusta aquí:
    if ($zona) { $w[]="(cve_zona=? OR zona=? )"; $p[]=$zona; $p[]=$zona; }

    $where = "WHERE ".implode(" AND ", $w);

    // Ajusta columnas a tu tabla real: BL, descripción, etc.
    $rows = db_all("
      SELECT
        cve_ubicacion AS bl,
        des_ubicacion AS descripcion
      FROM c_ubicacion
      $where
      ORDER BY cve_ubicacion
      LIMIT 2000
    ", $p);

    j(['ok'=>true,'data'=>$rows]);
  }

  if ($op==='buscar_compuestos') {
    $q = s($_GET['q'] ?? '');
    $q = $q ?? '';

    // Regla: mínimo 3 caracteres para consultar
    if (mb_strlen($q) > 0 && mb_strlen($q) < 3) {
      j(['ok'=>true,'data'=>[]]);
    }

    // Búsqueda por coincidencia: clave prefix (rápido) + descripción contains
    $p = [];
    $sql = "
      SELECT cve_articulo, des_articulo, cve_umed
      FROM c_articulo
      WHERE COALESCE(Compuesto,'N')='S'
    ";

    if ($q !== '') {
      $sql .= " AND (cve_articulo LIKE ? OR des_articulo LIKE ?) ";
      $p[] = $q.'%';
      $p[] = '%'.$q.'%';
    }

    $sql .= " ORDER BY cve_articulo LIMIT 30";

    $rows = db_all($sql, $p);
    j(['ok'=>true,'data'=>$rows]);
  }

  j(['ok'=>true,'msg'=>'ok']);

} catch(Throwable $e){
  if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
  j(['ok'=>false,'msg'=>$e->getMessage()]);
}
