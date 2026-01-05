<?php
// /public/api/putaway/rtm_api.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../app/db.php';
$pdo = db_pdo();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function jexit($ok, $msg = '', $data = [], $extra = []) {
  echo json_encode(array_merge(['ok' => $ok ? 1 : 0, 'msg' => $msg, 'data' => $data], $extra), JSON_UNESCAPED_UNICODE);
  exit;
}

function p($k, $def = null) {
  return $_GET[$k] ?? $_POST[$k] ?? $def;
}

/**
 * Helpers: localizar id_almacenp desde clave (WH8, etc.)
 */
function get_almacen_id(PDO $pdo, string $clave) {
  $st = $pdo->prepare("SELECT id FROM c_almacenp WHERE clave = :c LIMIT 1");
  $st->execute([':c' => $clave]);
  $id = $st->fetchColumn();
  return $id ? (int)$id : 0;
}

try {

  if ($action === '') {
    jexit(false, 'Falta parámetro action', []);
  }

  /**
   * EMPRESAS
   * Nota: en tu proyecto ya existe "empresas_api.php" legacy en otro lado,
   * aquí lo resolvemos directo por BD para que RTM no dependa de rutas externas.
   */
  if ($action === 'empresas') {
    // Intento 1: c_compania (común en tu ecosistema)
    try {
      $sql = "SELECT id, clave, nombre
              FROM c_compania
              WHERE IFNULL(activo,1)=1
              ORDER BY nombre";
      $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
      jexit(true, '', $rows);
    } catch (Throwable $e) {
      // Intento 2: estructura alternativa (cve_cia / des_cia)
      try {
        $sql = "SELECT cve_cia AS id, CAST(cve_cia AS CHAR) AS clave, des_cia AS nombre
                FROM c_compania
                WHERE IFNULL(Activo,1)=1
                ORDER BY des_cia";
        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        jexit(true, '', $rows);
      } catch (Throwable $e2) {
        jexit(false, 'No se pudo leer empresas (c_compania). Ajusta nombre/columnas.', [], ['detalle' => $e2->getMessage()]);
      }
    }
  }

  /**
   * ALMACENES (c_almacenp)
   * - Filtra por empresa si se manda empresa_id
   */
  if ($action === 'almacenes') {
    $empresa_id = (int)p('empresa_id', 0);

    $where = "WHERE IFNULL(Activo,1)=1";
    $bind  = [];
    if ($empresa_id > 0) {
      $where .= " AND IFNULL(cve_cia,0)=:cia";
      $bind[':cia'] = $empresa_id;
    }

    $sql = "SELECT id, clave, nombre, cve_cia
            FROM c_almacenp
            $where
            ORDER BY clave";
    $st = $pdo->prepare($sql);
    $st->execute($bind);
    jexit(true, '', $st->fetchAll(PDO::FETCH_ASSOC));
  }

  /**
   * ZONAS de recibo / staging
   * Fuente: tubicacionesretencion (tu tabla)
   * - Param: almacen (clave WH8)
   */
  if ($action === 'zonas') {
    $almacen = trim((string)p('almacen', ''));
    if ($almacen === '') jexit(false, 'Falta parámetro almacen (clave, ej. WH8)', []);

    $idAlm = get_almacen_id($pdo, $almacen);
    if ($idAlm <= 0) jexit(true, '', []); // sin tronar UI

    $sql = "SELECT
              cve_ubicacion AS clave,
              desc_ubicacion AS nombre,
              B_Devolucion,
              AreaStagging
            FROM tubicacionesretencion
            WHERE cve_almacp = :id
              AND IFNULL(Activo,1)=1
            ORDER BY cve_ubicacion";
    $st = $pdo->prepare($sql);
    $st->execute([':id' => $idAlm]);
    jexit(true, '', $st->fetchAll(PDO::FETCH_ASSOC));
  }

  /**
   * KPIs (sin límite) por filtro:
   * - empresa_id opcional
   * - almacen (clave) opcional
   * - zona_recibo (cve_ubicacion: ZRWH8) opcional
   *
   * Regla negocio: sólo folios donde SUM(recibida) <> SUM(pedida)
   */
  if ($action === 'kpis') {
    $empresa_id = (int)p('empresa_id', 0);
    $almacen    = trim((string)p('almacen', ''));      // clave WH8 (si tu th_entalmacen usa clave)
    $zona       = trim((string)p('zona_recibo', ''));  // ZRWH8...

    $where = [];
    $bind  = [];

    // Zona (en td_entalmacen.cve_ubicacion). OJO collation/binary:
    // Convertimos cve_ubicacion a CHAR utf8mb4 para compararlo seguro.
    if ($zona !== '') {
      $where[] = "CAST(d.cve_ubicacion AS CHAR(64) CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci = :zona";
      $bind[':zona'] = $zona;
    }

    // Almacén (si existe en th_entalmacen como clave)
    if ($almacen !== '') {
      $where[] = "IFNULL(h.Cve_Almac,'') = :alm";
      $bind[':alm'] = $almacen;
    }

    // Empresa: vía c_almacenp (si hay filtro empresa, amarramos por tabla de zonas -> almacenp)
    // join a tubicacionesretencion (zona->almacenp) y c_almacenp (almacenp->cia)
    $joinEmpresa = "";
    if ($empresa_id > 0) {
      $joinEmpresa = "
        JOIN tubicacionesretencion zr
          ON zr.cve_ubicacion = CAST(d.cve_ubicacion AS CHAR(64) CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci
        JOIN c_almacenp ap
          ON ap.id = zr.cve_almacp
      ";
      $where[] = "IFNULL(ap.cve_cia,0) = :cia";
      $bind[':cia'] = $empresa_id;
      $where[] = "IFNULL(zr.Activo,1)=1";
      $where[] = "IFNULL(ap.Activo,1)=1";
    }

    $wsql = (count($where) ? ("WHERE " . implode(" AND ", $where)) : "");

    // Folios KPI: agrupado por folio con regla Recibida != Pedida
    $sql = "
      SELECT
        COUNT(*) AS folios_pend,
        SUM(lineas_pend) AS lineas_pend,
        SUM(pendiente_qty) AS cantidad_pend,
        ROUND( (SUM(total_ubicada) / NULLIF(SUM(total_recibida),0)) * 100, 2) AS avance_prom
      FROM (
        SELECT
          h.Fol_Folio AS folio,
          COUNT(d.id) AS lineas_pend,
          SUM(IFNULL(d.CantidadPedida,0)) AS total_pedida,
          SUM(IFNULL(d.CantidadRecibida,0)) AS total_recibida,
          SUM(IFNULL(d.CantidadUbicada,0)) AS total_ubicada,
          (SUM(IFNULL(d.CantidadRecibida,0)) - SUM(IFNULL(d.CantidadUbicada,0))) AS pendiente_qty
        FROM th_entalmacen h
        JOIN td_entalmacen d ON d.fol_folio = h.Fol_Folio
        $joinEmpresa
        $wsql
        GROUP BY h.Fol_Folio
        HAVING SUM(IFNULL(d.CantidadRecibida,0)) <> SUM(IFNULL(d.CantidadPedida,0))
      ) x
    ";
    $st = $pdo->prepare($sql);
    $st->execute($bind);
    $k = $st->fetch(PDO::FETCH_ASSOC) ?: [];

    jexit(true, '', [
      'folios_pendientes'  => (int)($k['folios_pend'] ?? 0),
      'lineas_pendientes'  => (int)($k['lineas_pend'] ?? 0),
      'cantidad_pendiente' => (float)($k['cantidad_pend'] ?? 0),
      'avance_prom'        => (float)($k['avance_prom'] ?? 0),
    ]);
  }

  /**
   * FOLIOS (DataTables server-side)
   * - No precarga 100: debe paginar desde start/length
   * - Regla: SUM(recibida) <> SUM(pedida)
   * - Filtros: empresa_id, almacen, zona_recibo
   */
  if ($action === 'folios') {
    $empresa_id = (int)p('empresa_id', 0);
    $almacen    = trim((string)p('almacen', ''));
    $zona       = trim((string)p('zona_recibo', ''));

    // DataTables params
    $draw   = (int)p('draw', 1);
    $start  = max(0, (int)p('start', 0));
    $length = (int)p('length', 25);
    if ($length <= 0) $length = 25;
    $length = min(200, $length);

    $search = p('search', []);
    $q = '';
    if (is_array($search)) $q = trim((string)($search['value'] ?? ''));
    else $q = trim((string)p('search[value]', ''));

    $where = [];
    $bind  = [];

    if ($zona !== '') {
      $where[] = "CAST(d.cve_ubicacion AS CHAR(64) CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci = :zona";
      $bind[':zona'] = $zona;
    }
    if ($almacen !== '') {
      $where[] = "IFNULL(h.Cve_Almac,'') = :alm";
      $bind[':alm'] = $almacen;
    }

    $joinEmpresa = "";
    if ($empresa_id > 0) {
      $joinEmpresa = "
        JOIN tubicacionesretencion zr
          ON zr.cve_ubicacion = CAST(d.cve_ubicacion AS CHAR(64) CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci
        JOIN c_almacenp ap
          ON ap.id = zr.cve_almacp
      ";
      $where[] = "IFNULL(ap.cve_cia,0) = :cia";
      $bind[':cia'] = $empresa_id;
      $where[] = "IFNULL(zr.Activo,1)=1";
      $where[] = "IFNULL(ap.Activo,1)=1";
    }

    if ($q !== '') {
      $where[] = "(
        CAST(h.Fol_Folio AS CHAR) LIKE :q
        OR IFNULL(h.Proveedor,'') LIKE :q
        OR IFNULL(h.tipo,'') LIKE :q
        OR IFNULL(h.ID_Protocolo,'') LIKE :q
        OR IFNULL(h.Consec_protocolo,'') LIKE :q
      )";
      $bind[':q'] = "%$q%";
    }

    $wsql = (count($where) ? ("WHERE " . implode(" AND ", $where)) : "");

    // Total filtrado (recordsFiltered)
    $sqlCount = "
      SELECT COUNT(*) FROM (
        SELECT h.Fol_Folio
        FROM th_entalmacen h
        JOIN td_entalmacen d ON d.fol_folio = h.Fol_Folio
        $joinEmpresa
        $wsql
        GROUP BY h.Fol_Folio
        HAVING SUM(IFNULL(d.CantidadRecibida,0)) <> SUM(IFNULL(d.CantidadPedida,0))
      ) t
    ";
    $stc = $pdo->prepare($sqlCount);
    $stc->execute($bind);
    $filtered = (int)$stc->fetchColumn();

    // Total global (recordsTotal) con mismos filtros “duros” (empresa/almacen/zona) pero sin search
    $whereTotal = [];
    $bindTotal  = [];
    if ($zona !== '') {
      $whereTotal[] = "CAST(d.cve_ubicacion AS CHAR(64) CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci = :zona";
      $bindTotal[':zona'] = $zona;
    }
    if ($almacen !== '') {
      $whereTotal[] = "IFNULL(h.Cve_Almac,'') = :alm";
      $bindTotal[':alm'] = $almacen;
    }
    $joinEmpresaT = "";
    if ($empresa_id > 0) {
      $joinEmpresaT = "
        JOIN tubicacionesretencion zr
          ON zr.cve_ubicacion = CAST(d.cve_ubicacion AS CHAR(64) CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci
        JOIN c_almacenp ap
          ON ap.id = zr.cve_almacp
      ";
      $whereTotal[] = "IFNULL(ap.cve_cia,0) = :cia";
      $bindTotal[':cia'] = $empresa_id;
      $whereTotal[] = "IFNULL(zr.Activo,1)=1";
      $whereTotal[] = "IFNULL(ap.Activo,1)=1";
    }
    $wsqlT = (count($whereTotal) ? ("WHERE " . implode(" AND ", $whereTotal)) : "");

    $sqlTotal = "
      SELECT COUNT(*) FROM (
        SELECT h.Fol_Folio
        FROM th_entalmacen h
        JOIN td_entalmacen d ON d.fol_folio = h.Fol_Folio
        $joinEmpresaT
        $wsqlT
        GROUP BY h.Fol_Folio
        HAVING SUM(IFNULL(d.CantidadRecibida,0)) <> SUM(IFNULL(d.CantidadPedida,0))
      ) t
    ";
    $stt = $pdo->prepare($sqlTotal);
    $stt->execute($bindTotal);
    $total = (int)$stt->fetchColumn();

    // Orden
    $orderCol = (int)p('order[0][column]', 1);
    $orderDir = strtolower((string)p('order[0][dir]', 'desc')) === 'asc' ? 'ASC' : 'DESC';

    // Mapeo columnas DataTables (ajusta si tu UI manda otro orden)
    $colMap = [
      0 => 'h.Fol_Folio',
      1 => 'h.Fol_Folio',
      2 => 'h.tipo',
      3 => 'h.OC',
      4 => 'h.Factura',
      5 => 'h.Proveedor',
      6 => 'h.Proyecto',
      7 => 'h.ID_Protocolo',
      8 => 'partidas',
      9 => 'recibido',
      10 => 'acomodado',
      11 => 'pendiente',
      12 => 'avance'
    ];
    $orderBy = $colMap[$orderCol] ?? 'h.Fol_Folio';

    $sql = "
      SELECT
        h.Fol_Folio AS folio,
        h.tipo,
        h.OC AS oc,
        h.Factura AS factura,
        h.Proveedor AS proveedor,
        h.Proyecto AS proyecto,
        CONCAT(IFNULL(h.ID_Protocolo,''), IF(h.Consec_protocolo IS NULL,'',CONCAT('-',h.Consec_protocolo))) AS protocolo,
        COUNT(d.id) AS partidas,
        SUM(IFNULL(d.CantidadRecibida,0)) AS recibido,
        SUM(IFNULL(d.CantidadUbicada,0)) AS acomodado,
        (SUM(IFNULL(d.CantidadRecibida,0)) - SUM(IFNULL(d.CantidadUbicada,0))) AS pendiente,
        ROUND( (SUM(IFNULL(d.CantidadUbicada,0)) / NULLIF(SUM(IFNULL(d.CantidadRecibida,0)),0)) * 100, 2) AS avance
      FROM th_entalmacen h
      JOIN td_entalmacen d ON d.fol_folio = h.Fol_Folio
      $joinEmpresa
      $wsql
      GROUP BY h.Fol_Folio
      HAVING SUM(IFNULL(d.CantidadRecibida,0)) <> SUM(IFNULL(d.CantidadPedida,0))
      ORDER BY $orderBy $orderDir
      LIMIT $start, $length
    ";
    $st = $pdo->prepare($sql);
    $st->execute($bind);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    // Respuesta DataTables
    echo json_encode([
      'draw' => $draw,
      'recordsTotal' => $total,
      'recordsFiltered' => $filtered,
      'data' => $rows
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }

  /**
   * DETALLE por folio (para modal elegante)
   * - Corrige des_articulo (viene de c_articulo)
   */
  if ($action === 'detalle') {
    $folio = (int)p('folio', 0);
    if ($folio <= 0) jexit(false, 'Falta parámetro folio', []);

    $sql = "
      SELECT
        d.id,
        d.fol_folio AS folio,
        d.cve_articulo AS articulo,
        a.des_articulo AS descripcion,
        d.cve_lote AS lote,
        CAST(d.cve_ubicacion AS CHAR(64) CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS ubicacion_zona,
        IFNULL(d.CantidadPedida,0) AS pedida,
        IFNULL(d.CantidadRecibida,0) AS recibida,
        IFNULL(d.CantidadUbicada,0) AS acomodada,
        (IFNULL(d.CantidadRecibida,0) - IFNULL(d.CantidadUbicada,0)) AS pendiente,
        d.fecha_inicio
      FROM td_entalmacen d
      LEFT JOIN c_articulo a
        ON a.cve_articulo = d.cve_articulo
      WHERE d.fol_folio = :f
      ORDER BY d.cve_articulo, d.cve_lote, d.id
    ";
    $st = $pdo->prepare($sql);
    $st->execute([':f' => $folio]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    // Header resumido (proveedor, etc.)
    $hdr = [];
    try {
      $sth = $pdo->prepare("SELECT Fol_Folio AS folio, tipo, OC AS oc, Factura AS factura, Proveedor AS proveedor, Proyecto AS proyecto, ID_Protocolo, Consec_protocolo
                            FROM th_entalmacen WHERE Fol_Folio = :f LIMIT 1");
      $sth->execute([':f' => $folio]);
      $hdr = $sth->fetch(PDO::FETCH_ASSOC) ?: [];
      if ($hdr) {
        $hdr['protocolo'] = trim(($hdr['ID_Protocolo'] ?? '') . ($hdr['Consec_protocolo'] ? ('-' . $hdr['Consec_protocolo']) : ''));
      }
    } catch (Throwable $e) { /* no-op */ }

    jexit(true, '', ['header' => $hdr, 'lineas' => $rows]);
  }

  /**
   * Healthcheck rápido
   */
  if ($action === 'ping') {
    jexit(true, 'ok', ['ts' => date('Y-m-d H:i:s')]);
  }

  jexit(false, 'Acción no soportada', []);

} catch (Throwable $e) {
  jexit(false, 'Error servidor', [], [
    'detalle' => $e->getMessage(),
  ]);
}
