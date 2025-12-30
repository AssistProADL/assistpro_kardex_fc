<?php
// /public/api/iniciar_produccion.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db_pdo();

function out(bool $ok, array $extra = []): void {
  echo json_encode(array_merge(['ok' => $ok ? 1 : 0], $extra), JSON_UNESCAPED_UNICODE);
  exit;
}
function i0($v): int { return ($v === '' || $v === null) ? 0 : (int)$v; }
function s($v): string { return trim((string)($v ?? '')); }

$action = s($_POST['action'] ?? $_GET['action'] ?? 'list');

try {
  // Detecta si existe c_proveedores.Nombre
  $hasNombre = 0;
  try {
    $hasNombre = (int)$pdo->prepare("
      SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'c_proveedores'
        AND COLUMN_NAME = 'Nombre'
    ")->execute() ?: 0;
  } catch (Throwable $e) {
    $hasNombre = 0; // fallback
  }
  if ($hasNombre === 0) {
    try {
      $stHN = $pdo->query("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'c_proveedores'
          AND COLUMN_NAME = 'Nombre'
      ");
      $hasNombre = (int)$stHN->fetchColumn();
    } catch (Throwable $e) {
      $hasNombre = 0;
    }
  }

  $empresaExpr = $hasNombre > 0
    ? "COALESCE(p.Nombre, p.Empresa, CONCAT('Proveedor #', t.ID_Proveedor))"
    : "COALESCE(p.Empresa, CONCAT('Proveedor #', t.ID_Proveedor))";

  if ($action === 'list') {
    $status = s($_GET['status'] ?? 'P');
    if ($status === '') $status = 'P';

    $desde = s($_GET['desde'] ?? '');
    $hasta = s($_GET['hasta'] ?? '');

    // Defaults demo: últimos 120 días
    if ($desde === '' || $hasta === '') {
      $h = new DateTime();
      $hasta = $h->format('Y-m-d');
      $d = new DateTime();
      $d->modify('-120 days');
      $desde = $d->format('Y-m-d');
    }

    $idProv = i0($_GET['id_proveedor'] ?? 0);
    $alm    = s($_GET['cve_almac'] ?? ''); // en tabla viene varchar

    $where = [];
    $params = [];

    $where[] = "t.Status = :st";
    $params[':st'] = $status;

    $where[] = "DATE(COALESCE(t.FechaReg, t.Fecha, NOW())) BETWEEN :d1 AND :d2";
    $params[':d1'] = $desde;
    $params[':d2'] = $hasta;

    if ($idProv > 0) {
      $where[] = "t.ID_Proveedor = :pr";
      $params[':pr'] = $idProv;
    }
    if ($alm !== '' && $alm !== '0') {
      $where[] = "t.cve_almac = :alm";
      $params[':alm'] = $alm;
    }

    $sql = "
      SELECT
        t.id,
        t.Folio_Pro,
        t.Referencia,
        t.Cve_Articulo,
        t.Cantidad,
        t.cve_almac,
        t.ID_Proveedor,
        t.Status,
        t.FechaReg,
        t.Fecha,
        {$empresaExpr} AS EmpresaNombre
      FROM t_ordenprod t
      LEFT JOIN c_proveedores p
        ON p.ID_Proveedor = t.ID_Proveedor
       AND (p.es_cliente = 1 OR p.es_cliente IS NULL)
      WHERE " . implode("\n AND ", $where) . "
      ORDER BY t.id DESC
      LIMIT 500
    ";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    out(true, ['rows' => $rows, 'desde' => $desde, 'hasta' => $hasta, 'status' => $status]);
  }

  if ($action === 'detail') {
    $id = i0($_GET['id'] ?? 0);
    if ($id <= 0) out(false, ['error' => 'OT inválida']);

    $sqlOT = "
      SELECT
        t.id,
        t.Folio_Pro,
        t.Referencia,
        t.Cve_Articulo,
        t.Cve_Lote,
        t.Cantidad,
        t.Cant_Prod,
        t.cve_almac,
        t.ID_Proveedor,
        t.Status,
        t.Hora_Ini,
        t.Hora_Fin,
        t.cronometro,
        t.idy_ubica,
        t.idy_ubica_dest,
        t.Usr_Armo,
        t.Cve_Usuario,
        t.FechaReg,
        {$empresaExpr} AS EmpresaNombre
      FROM t_ordenprod t
      LEFT JOIN c_proveedores p
        ON p.ID_Proveedor = t.ID_Proveedor
       AND (p.es_cliente = 1 OR p.es_cliente IS NULL)
      WHERE t.id = :id
      LIMIT 1
    ";
    $st = $pdo->prepare($sqlOT);
    $st->execute([':id' => $id]);
    $ot = $st->fetch(PDO::FETCH_ASSOC);
    if (!$ot) out(false, ['error' => 'OT no encontrada']);

    $folioBom = (string)($ot['Referencia'] ?? '');
    $componentes = [];
    if ($folioBom !== '') {
      $stc = $pdo->prepare("
        SELECT
          d.id_ord,
          d.Folio_Pro,
          d.Cve_Articulo,
          d.Cantidad,
          d.Activo
        FROM td_ordenprod d
        WHERE d.Folio_Pro = :f
          AND (d.Activo = 1 OR d.Activo IS NULL)
        ORDER BY d.Cve_Articulo
      ");
      $stc->execute([':f' => $folioBom]);
      $componentes = $stc->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    out(true, ['ot' => $ot, 'componentes' => $componentes]);
  }

  if ($action === 'bls') {
    $cve_almac = i0($_GET['cve_almac'] ?? 0);
    if ($cve_almac <= 0) out(true, ['rows' => []]);

    $stb = $pdo->prepare("
      SELECT idy_ubica, CodigoCSD, Ubicacion
      FROM c_ubicacion
      WHERE cve_almac = :alm
        AND AreaProduccion = 'S'
        AND (Activo = 1 OR Activo IS NULL)
      ORDER BY (CodigoCSD IS NULL), CodigoCSD ASC, idy_ubica ASC
    ");
    $stb->execute([':alm' => $cve_almac]);
    $rows = $stb->fetchAll(PDO::FETCH_ASSOC) ?: [];
    out(true, ['rows' => $rows]);
  }

  if ($action === 'start') {
    $id = i0($_POST['id'] ?? 0);
    $bl = i0($_POST['idy_ubica_dest'] ?? 0);
    if ($id <= 0) out(false, ['error' => 'OT inválida']);
    if ($bl <= 0) out(false, ['error' => 'BL de producción inválido']);

    $usr = 'DEMO';

    $pdo->beginTransaction();

    // Lock OT
    $st = $pdo->prepare("SELECT id, Status, cve_almac FROM t_ordenprod WHERE id = ? FOR UPDATE");
    $st->execute([$id]);
    $ot = $st->fetch(PDO::FETCH_ASSOC);

    if (!$ot) {
      $pdo->rollBack();
      out(false, ['error' => 'OT no encontrada']);
    }

    $status = (string)($ot['Status'] ?? '');
    if ($status !== 'P') {
      $pdo->rollBack();
      out(false, ['error' => "La OT no está en Planeada (P). Status actual: {$status}"]);
    }

    // Validar BL por almacén y AreaProduccion='S'
    $alm = (int)preg_replace('/\D+/', '', (string)($ot['cve_almac'] ?? '0'));
    if ($alm <= 0) {
      // Si viene como varchar numérico, intenta cast directo
      $alm = (int)((string)($ot['cve_almac'] ?? '0'));
    }

    $vb = $pdo->prepare("
      SELECT COUNT(*)
      FROM c_ubicacion
      WHERE idy_ubica = :bl
        AND cve_almac = :alm
        AND AreaProduccion = 'S'
    ");
    $vb->execute([':bl' => $bl, ':alm' => $alm]);
    if ((int)$vb->fetchColumn() <= 0) {
      $pdo->rollBack();
      out(false, ['error' => 'El BL no pertenece al almacén o no es AreaProduccion=S']);
    }

    // Arranque: P -> E, set Hora_Ini, BL destino, usuario (Usr_Armo)
    $up = $pdo->prepare("
      UPDATE t_ordenprod
      SET
        Status        = 'E',
        Hora_Ini      = NOW(),
        idy_ubica_dest = :bl,
        Usr_Armo      = :usr,
        Cve_Usuario   = :usr
      WHERE id = :id
        AND Status = 'P'
    ");
    $up->execute([':bl' => $bl, ':usr' => $usr, ':id' => $id]);

    if ($up->rowCount() <= 0) {
      $pdo->rollBack();
      out(false, ['error' => 'No se pudo iniciar (concurrencia o status cambió).']);
    }

    $pdo->commit();
    out(true, ['id' => $id, 'idy_ubica_dest' => $bl, 'status' => 'E']);
  }

  out(false, ['error' => 'Acción no soportada']);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  out(false, ['error' => $e->getMessage()]);
}
