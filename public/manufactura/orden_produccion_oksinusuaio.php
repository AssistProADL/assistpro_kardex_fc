<?php
//@session_start();
require_once __DIR__ . '/../../app/db.php';
$pdo = db_pdo();

/* ============================================================
   HELPERS
============================================================ */

function first_existing_col(PDO $pdo, string $table, array $candidates)
{
    $in = implode("','", array_map('addslashes', $candidates));
    $sql = "
    SELECT COLUMN_NAME
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = ?
      AND COLUMN_NAME  IN ('$in')
    LIMIT 1
  ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$table]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['COLUMN_NAME'] : null;
}

/**
 * Stock disponible en un BL (vista v_existencias_por_ubicacion_ao)
 * BL se toma de CodigoCSD/codigocsd por convención.
 */
function stock_disponible_en_bl(PDO $pdo, string $zona_cve_almac, string $bl, string $cve_articulo): float
{
    static $colBL = null;
    if ($colBL === null) {
        $colBL = first_existing_col($pdo, 'v_existencias_por_ubicacion_ao', ['CodigoCSD', 'codigocsd', 'BL', 'bl']);
        if (!$colBL)
            $colBL = 'CodigoCSD';
    }

    $sql = "
    SELECT SUM(existencia) AS stock
    FROM v_existencias_por_ubicacion_ao
    WHERE cve_almac    = :alm
      AND $colBL       = :bl
      AND cve_articulo = :art
  ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':alm' => $zona_cve_almac,
        ':bl' => $bl,
        ':art' => $cve_articulo
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return (float) ($row['stock'] ?? 0);
}

function table_has_col(PDO $pdo, string $table, string $col): bool {
    $st = $pdo->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1");
    $st->execute([$table,$col]);
    return (bool)$st->fetchColumn();
}
function detect_folio_col(PDO $pdo): string {
    if (table_has_col($pdo,'t_ordenprod','Folio_Pro')) return 'Folio_Pro';
    if (table_has_col($pdo,'t_ordenprod','folio')) return 'folio';
    // fallback
    return 'Folio_Pro';
}

/**
 * Folio OT: OTAAAAMMDD-00001 (incremental por día)
 * Fuente: t_ordenprod.folio (si tu campo difiere, ajusta aquí).
 */
function next_folio_ot(PDO $pdo, string $pref = 'OT'): string
{
    $yyyymmdd = date('Ymd');
    $like = $pref . $yyyymmdd . '-%';

    $folioCol = detect_folio_col($pdo);

    $max = db_val("SELECT MAX($folioCol) FROM t_ordenprod WHERE $folioCol LIKE ?", [$like]);
    $seq = 1;

    if ($max) {
        $parts = explode('-', (string)$max);
        if (count($parts) === 2) {
            $n = (int)$parts[1];
            if ($n > 0) $seq = $n + 1;
        }
    }

    return sprintf('%s%s-%05d', $pref, $yyyymmdd, $seq);
}

/* ============================================================
   API AJAX
============================================================ */
$op = $_GET['op'] ?? $_POST['op'] ?? null;

$op = $_GET['op'] ?? $_POST['op'] ?? null;

if ($op) {

  // Folio siguiente (OTyyyymmdd-00001)
  if ($op === 'next_folio') {
    header('Content-Type: application/json; charset=utf-8');
    try {
      $hoy = date('Ymd');
      $pref = 'OT' . $hoy . '-';
      $max = db_val("SELECT MAX(Folio_Pro) FROM t_ordenprod WHERE Folio_Pro LIKE ?", [$pref . '%']);
      if (!$max) {
        $next = $pref . '00001';
      } else {
        $num = intval(substr($max, strlen($pref)));
        $next = $pref . str_pad((string)($num + 1), 5, '0', STR_PAD_LEFT);
      }
      echo json_encode(['ok'=>true,'folio'=>$next], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
  }

  // Usuarios (c_usuario) — para Cve_Usuario al guardar
  if ($op === 'usuarios') {
    header('Content-Type: application/json; charset=utf-8');
    try {
      $cia = $_GET['cve_cia'] ?? $_GET['cia'] ?? null;
      // Activo puede venir como int; status también existe en algunas instalaciones
      $sql = "SELECT id_user, cve_usuario, nombre_completo, cve_cia, Activo, status
              FROM c_usuario
              WHERE (Activo IS NULL OR Activo = 1)
                AND (status IS NULL OR status <> 'B')";
      $params = [];
      if ($cia !== null && $cia !== '' && $cia !== '0') {
        $sql .= " AND (cve_cia = ?)";
        $params[] = $cia;
      }
      $sql .= " ORDER BY nombre_completo";
      $rows = db_all($sql, $params);
      $out = array_map(function($r){
        return [
          'id_user' => $r['id_user'] ?? null,
          'cve_usuario' => $r['cve_usuario'] ?? null,
          'nombre_completo' => $r['nombre_completo'] ?? null,
          'cve_cia' => $r['cve_cia'] ?? null,
        ];
      }, $rows ?: []);
      echo json_encode(['ok'=>true,'data'=>$out], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
  }

  // Zonas (c_almacen) por almacén lógico
    // Zonas (c_almacen) por almacén lógico (cve_almacenp)
    // Nota: c_almacen.cve_almacenp es INT. El almacén lógico viene de c_almacenp.id (API filtros_almacenes.php).
    if ($op === 'zonas_by_almacenp') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $almacenp = trim($_GET['almacenp'] ?? $_POST['almacenp'] ?? '');
            if ($almacenp === '')
                throw new Exception('Almacén requerido');

            $rows = db_all("
        SELECT
          cve_almac,
          clave_almacen,
          des_almac
        FROM c_almacen
        WHERE (Activo = 1 OR Activo IS NULL)
          AND cve_almacenp = ?
        ORDER BY des_almac
      ", [(int) $almacenp]);

            echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // BL Manufactura por Zona (c_ubicacion) — solo AreaProduccion = 'S'
    // BL = CodigoCSD (Bin Location)
    if ($op === 'bl_by_zona') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $zona = trim($_GET['zona'] ?? $_POST['zona'] ?? '');
            if ($zona === '')
                throw new Exception('Zona requerida');

            $rows = db_all("
        SELECT
          idy_ubica,
          CodigoCSD AS bl
        FROM c_ubicacion
        WHERE cve_almac = ?
          AND AreaProduccion = 'S'
          AND (Activo = 1 OR Activo IS NULL)
          AND CodigoCSD IS NOT NULL AND CodigoCSD <> ''
        ORDER BY CodigoCSD
      ", [(int) $zona]);

            echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // Búsqueda realtime de productos compuestos (>=3 chars)
    if ($op === 'buscar_prod') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $q = trim($_GET['q'] ?? $_POST['q'] ?? '');
            if (mb_strlen($q) < 3) {
                echo json_encode(['ok' => true, 'data' => []], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $sql = "
        SELECT
          a.cve_articulo,
          a.des_articulo,
          a.unidadMedida,
          u.cve_umed AS cve_unidad
        FROM c_articulo a
        LEFT JOIN c_unimed u ON u.id_umed = a.unidadMedida
        WHERE COALESCE(a.Compuesto,'N')='S'
          AND (a.cve_articulo LIKE ? OR a.des_articulo LIKE ?)
        ORDER BY a.des_articulo
        LIMIT 20
      ";
            $rows = db_all($sql, ["%$q%", "%$q%"]);
            echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // Seleccionar producto compuesto por clave exacta
    if ($op === 'seleccionar_prod') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $cve = trim($_GET['cve'] ?? $_POST['cve'] ?? '');
            if ($cve === '')
                throw new Exception('Clave requerida');

            $row = db_row("
        SELECT
          a.cve_articulo,
          a.des_articulo,
          a.unidadMedida,
          u.cve_umed AS cve_unidad
        FROM c_articulo a
        LEFT JOIN c_unimed u ON u.id_umed = a.unidadMedida
        WHERE a.cve_articulo = ?
          AND COALESCE(a.Compuesto,'N')='S'
      ", [$cve]);

            if (!$row)
                throw new Exception('Producto compuesto no encontrado.');
            echo json_encode(['ok' => true, 'producto' => $row], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // Explosión de materiales (BOM: t_artcompuesto)
    

  // ---------- GENERAR FOLIO OT (OTAAAAMMDD-00001) ----------
  if ($op === 'next_folio') {
    header('Content-Type: application/json; charset=utf-8');
    try {
      $hoy = date('Ymd');
      $pref = "OT{$hoy}-";
      $max = db_val("SELECT MAX(Folio_Pro) FROM t_ordenprod WHERE Folio_Pro LIKE ?", [$pref . "%"]);
      $seq = 1;
      if ($max) {
        // Extrae la parte numérica final
        if (preg_match('/^OT\d{8}-(\d{5})$/', $max, $mm)) {
          $seq = ((int)$mm[1]) + 1;
        } else {
          // fallback: toma últimos 5 dígitos si existen
          $tail = substr($max, -5);
          if (ctype_digit($tail)) $seq = ((int)$tail) + 1;
        }
      }
      $folio = $pref . str_pad((string)$seq, 5, '0', STR_PAD_LEFT);
      echo json_encode(['ok' => true, 'folio' => $folio], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
      echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
  }

  // ---------- GUARDAR OT (t_ordenprod + td_ordenprod) ----------
  if ($op === 'guardar_ot') {
    header('Content-Type: application/json; charset=utf-8');
    try {
      $payload = $_POST['payload'] ?? '';
      if ($payload === '') throw new Exception('Payload requerido.');
      $data = json_decode($payload, true);
      if (!is_array($data)) throw new Exception('Payload inválido.');

      $folio   = trim($data['folio'] ?? '');
      $cveAlm  = trim($data['cve_almac'] ?? '');
      $zonaId  = trim($data['zona_id'] ?? '');
      $bl      = trim($data['bl'] ?? '');
      $prod    = trim($data['producto'] ?? '');
      $cant    = (float)($data['cantidad'] ?? 0);
      $otErp   = trim($data['ot_erp'] ?? '');
      $pedido  = trim($data['pedido'] ?? '');
      $fOT     = trim($data['fecha_ot'] ?? '');
      $fComp   = trim($data['fecha_comp'] ?? '');
      $umed    = $data['id_umed'] ?? null;

      $det = $data['detalles'] ?? [];
      if ($folio === '' || $cveAlm === '' || $zonaId === '' || $bl === '' || $prod === '' || !($cant > 0)) {
        throw new Exception('Faltan datos obligatorios (almacén, zona, BL, producto, cantidad, folio).');
      }
      if (!is_array($det) || count($det) === 0) {
        throw new Exception('No hay componentes para guardar.');
      }

      $pdo->beginTransaction();

      // Evitar duplicados por Folio_Pro
      $existe = db_val("SELECT COUNT(*) FROM t_ordenprod WHERE Folio_Pro = ?", [$folio]);
      if ((int)$existe > 0) {
        throw new Exception("El folio ya existe: {$folio}");
      }

      // Inserta encabezado
      $sqlH = "INSERT INTO t_ordenprod
        (Folio_Pro, FolioImport, cve_almac, Cve_Articulo, Cantidad, Fecha, FechaReg, Referencia, Status, id_umed, id_zona_almac)
        VALUES
        (:folio, :folioimp, :alm, :art, :cant, :fecha, NOW(), :ref, :status, :id_umed, :zona)";
      $stmtH = $pdo->prepare($sqlH);

      $fechaOT = $fOT ? (str_replace('T',' ', $fOT) . ' 00:00:00') : date('Y-m-d H:i:s');

      $stmtH->execute([
        ':folio'    => $folio,
        ':folioimp' => $pedido !== '' ? $pedido : null,
        ':alm'      => $cveAlm,
        ':art'      => $prod,
        ':cant'     => $cant,
        ':fecha'    => $fechaOT,
        ':ref'      => $otErp !== '' ? $otErp : null,
        ':status'   => 'P',
        ':id_umed'  => (is_numeric($umed) ? (int)$umed : null),
        ':zona'     => (is_numeric($zonaId) ? (int)$zonaId : null),
      ]);

      // Inserta detalle (componentes)
      $sqlD = "INSERT INTO td_ordenprod
        (Folio_Pro, Cve_Articulo, Fecha_Prod, Cantidad, Activo, Referencia, Cve_Almac_Ori)
        VALUES
        (:folio, :art, :fecha, :cant, 1, :ref, :almori)";
      $stmtD = $pdo->prepare($sqlD);

      foreach ($det as $r) {
        $artComp = trim($r['componente'] ?? $r['Cve_Articulo'] ?? '');
        $cantTot = (float)($r['cant_total'] ?? $r['Cantidad'] ?? 0);
        if ($artComp === '' || !($cantTot >= 0)) continue;
        $stmtD->execute([
          ':folio'  => $folio,
          ':art'    => $artComp,
          ':fecha'  => $fechaOT,
          ':cant'   => $cantTot,
          ':ref'    => $otErp !== '' ? $otErp : null,
          ':almori' => $cveAlm
        ]);
      }

      $pdo->commit();

      echo json_encode(['ok' => true, 'folio' => $folio], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
  }

if ($op === 'explosion') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $padre = trim($_POST['prod'] ?? '');
            $cantidad = (float) ($_POST['cantidad'] ?? 0);
            $zona = trim($_POST['zona'] ?? '');   // zona c_almacen.cve_almac
            $bl = trim($_POST['bl'] ?? '');

            if ($padre === '' || $cantidad <= 0)
                throw new Exception('Producto y cantidad requeridos.');
            if ($zona === '')
                throw new Exception('Zona requerida.');
            if ($bl === '')
                throw new Exception('BL Manufactura requerido.');

            $prod = db_row("
        SELECT a.cve_articulo, a.des_articulo, a.unidadMedida, u.cve_umed AS cve_unidad
        FROM c_articulo a
        LEFT JOIN c_unimed u ON u.id_umed = a.unidadMedida
        WHERE a.cve_articulo = ?
      ", [$padre]);
            if (!$prod)
                throw new Exception('Producto no encontrado.');

            $det = db_all("
        SELECT
          c.Cve_Articulo AS componente,
          a.des_articulo AS descripcion,
          COALESCE(c.cve_umed, a.unidadMedida) AS unidadMedida,
          u.cve_umed AS cve_unidad,
          COALESCE(c.Etapa,'') AS etapa,
          c.Cantidad AS cantidad_por_unidad
        FROM t_artcompuesto c
        LEFT JOIN c_articulo a ON a.cve_articulo = c.Cve_Articulo
        LEFT JOIN c_unimed u ON u.id_umed = COALESCE(c.cve_umed, a.unidadMedida)
        WHERE c.Cve_ArtComponente = ?
          AND (c.Activo IS NULL OR c.Activo = 1)
        ORDER BY c.Cve_Articulo
      ", [$padre]);

            $out = [];
            foreach ($det as $r) {
                $qty_unit = (float) ($r['cantidad_por_unidad'] ?? 0);
                $qty_total = $qty_unit * $cantidad;

                $stock = stock_disponible_en_bl($pdo, $zona, $bl, $r['componente']);

                $out[] = [
                    'componente' => $r['componente'],
                    'descripcion' => $r['descripcion'],
                    'unidadMedida' => $r['unidadMedida'],
                    'etapa' => $r['etapa'],
                    'cantidad_por_unidad' => $qty_unit,
                    'cantidad_total' => $qty_total,
                    'cantidad_solicitada' => $qty_total,     // placeholder (aquí luego amarramos reserva/consumo)
                    'stock_disponible' => $stock
                ];
            }

            echo json_encode(['ok' => true, 'producto' => $prod, 'detalles' => $out], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    
    // Guardar OT (t_ordenprod + td_ordenprod)
    if ($op === 'guardar_ot') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $folio = trim($_POST['folio'] ?? '');
            $empresa = trim($_POST['empresa'] ?? '');
            $almacen = trim($_POST['almacen'] ?? '');
            $zona    = trim($_POST['zona'] ?? '');
            $bl      = trim($_POST['bl'] ?? '');
            $prod    = trim($_POST['producto'] ?? '');
            $cant    = (float)($_POST['cantidad'] ?? 0);
            $ot_erp  = trim($_POST['ot_erp'] ?? '');
            $pedido  = trim($_POST['pedido'] ?? '');
            $f_ot    = trim($_POST['fecha_ot'] ?? date('Y-m-d'));
            $f_comp  = trim($_POST['fecha_compromiso'] ?? date('Y-m-d'));
            $detalle = $_POST['detalle'] ?? '[]';
            if (is_string($detalle)) {
                $detalle = json_decode($detalle, true);
            }
            if (!is_array($detalle)) $detalle = [];

            if ($folio === '') $folio = next_folio_ot($pdo, 'OT');
            if ($prod === '' || !($cant > 0)) throw new Exception('Producto y cantidad son obligatorios.');
            if (!$almacen || !$zona || !$bl) throw new Exception('Seleccione almacén, zona y BL.');
            if (count($detalle) === 0) throw new Exception('No hay componentes calculados para guardar.');

            $folioCol = detect_folio_col($pdo);

            $pdo->beginTransaction();

            // Insert header con mapeo flexible por columnas existentes
            $cols = [];
            $vals = [];
            $pars = [];

            $add = function($col, $val) use (&$cols,&$vals,&$pars,$pdo){
                if (table_has_col($pdo,'t_ordenprod',$col)) {
                    $cols[] = $col;
                    $ph = ':' . $col;
                    $vals[] = $ph;
                    $pars[$ph] = $val;
                }
            };

            $add($folioCol, $folio);
            $add('Folio_Pro', $folio); // por si detectó 'folio' pero existe también
            $add('folio', $folio);

            $add('Cve_Articulo', $prod);
            $add('producto', $prod);

            $add('Cantidad', $cant);
            $add('Cant_Prod', $cant);

            $add('cve_almac', $zona);
            $add('zona', $zona);
            $add('bl', $bl);
            $add('BL_Manu', $bl);

            $add('OT_ERP', $ot_erp);
            $add('Pedido', $pedido);
            $add('Fecha', $f_ot . ' 00:00:00');
            $add('FechaCompromiso', $f_comp . ' 00:00:00');
            $add('Status', 'T');

            // empresa si existe
            $add('empresa_id', $empresa);
            $add('cve_cia', $empresa);

            // usuario si existe
            $usr = $_SESSION['username'] ?? $_SESSION['usuario'] ?? $_SESSION['user'] ?? 'SYSTEM';
            $add('Cve_Usuario', $usr);

            if (count($cols) === 0) throw new Exception('No se pudo mapear columnas de t_ordenprod.');

            // Evitar duplicar mismo folio: si ya existe, aborta
            $exists = db_val("SELECT COUNT(*) FROM t_ordenprod WHERE $folioCol = ?", [$folio]);
            if ((int)$exists > 0) throw new Exception('El folio ya existe: ' . $folio);

            $sqlH = "INSERT INTO t_ordenprod (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
            $stH = $pdo->prepare($sqlH);
            $stH->execute($pars);

            // Insert detalle
            $folioDetCol = null;
            if (table_has_col($pdo,'td_ordenprod','Folio_Pro')) $folioDetCol = 'Folio_Pro';
            elseif (table_has_col($pdo,'td_ordenprod','folio')) $folioDetCol = 'folio';
            elseif (table_has_col($pdo,'td_ordenprod',$folioCol)) $folioDetCol = $folioCol;

            $colArt = table_has_col($pdo,'td_ordenprod','Cve_Articulo') ? 'Cve_Articulo' : (table_has_col($pdo,'td_ordenprod','cve_articulo') ? 'cve_articulo' : 'Cve_Articulo');
            $colCant= table_has_col($pdo,'td_ordenprod','Cantidad') ? 'Cantidad' : (table_has_col($pdo,'td_ordenprod','cantidad') ? 'cantidad' : 'Cantidad');

            foreach ($detalle as $d) {
                $c = trim($d['componente'] ?? $d['Cve_Articulo'] ?? $d['cve_articulo'] ?? '');
                $q = (float)($d['cant_solicitada'] ?? $d['cant_total'] ?? $d['Cantidad'] ?? $d['cantidad'] ?? 0);
                if ($c === '' || !($q > 0)) continue;

                if ($folioDetCol) {
                    $sqlD = "INSERT INTO td_ordenprod ($folioDetCol,$colArt,$colCant) VALUES (?,?,?)";
                    $pdo->prepare($sqlD)->execute([$folio,$c,$q]);
                } else {
                    // Si no hay folio en detalle, intentamos con id_ord/id_ordenprod si existe (no se infiere aquí)
                    $sqlD = "INSERT INTO td_ordenprod ($colArt,$colCant) VALUES (?,?)";
                    $pdo->prepare($sqlD)->execute([$c,$q]);
                }
            }

            $pdo->commit();
            echo json_encode(['ok' => true, 'folio' => $folio], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }


    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'msg' => 'Operación no válida'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ============================================================
   CARGA INICIAL (HTML)
============================================================ */
include __DIR__ . '/../bi/_menu_global.php';
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Orden de Producción</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/v/bs5/dt-2.1.8/datatables.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-size: 10px
        }

        .table-sm td,
        .table-sm th {
            padding: .3rem .4rem;
            vertical-align: middle
        }

        .dt-right {
            text-align: right
        }

        .dt-center {
            text-align: center
        }

        .card-header-title {
            font-size: 14px;
            font-weight: 600
        }

        .subtext {
            font-size: 9px;
            color: #6c757d
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-2">

        <div class="card mb-2">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <span class="card-header-title"><i class="bi bi-gear-wide-connected"></i> Orden de Producción</span>
                    <div class="subtext">Explosión de materiales / Manufactura</div>
                </div>
                <div class="subtext">Estado: <span class="badge bg-secondary">Borrador</span></div>
            </div>

            <div class="card-body">

                <!-- DATOS GENERALES OT -->
                <div class="row g-2 mb-2">
                    <div class="col-md-3">
                        <label class="form-label mb-0">No. OT (interno)</label>
                        <input type="text" class="form-control form-control-sm" id="txtOtInterno" value="OT..."
                            readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-0">OT ERP</label>
                        <input type="text" class="form-control form-control-sm" id="txtOtErp"
                            placeholder="Referencia en ERP (opcional)">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-0">Empresa</label>
                        <select id="selEmpresa" class="form-select form-select-sm">
                            <option value="">Seleccione empresa</option>
                        </select>
                    </div>
                    <div class=\"col-md-3\">
                    <label class=\"form-label mb-0\">Usuario (registra)</label>
                    <select id=\"selUsuario\" class=\"form-select form-select-sm\">
                        <option value=\"\">Seleccione usuario</option>
                    </select>
                </div>

                <div class="col-md-3">
                        <label class="form-label mb-0">Almacén</label>
                        <select id="selAlmacen" class="form-select form-select-sm">
                            <option value="">Seleccione almacén</option>
                        </select>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-3">
                        <label class="form-label mb-0">Zona de Almacenaje</label>
                        <select id="selZona" class="form-select form-select-sm">
                            <option value="">Seleccione zona de almacén</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label mb-0">BL Manufactura</label>
                        <select id="selBLManu" class="form-select form-select-sm">
                            <option value="">Seleccione BL de manufactura</option>
                        </select>
                        <div class="subtext">BL = Bin Location (c_ubicacion.CodigoCSD). Solo AreaProduccion='S'.</div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label mb-0">Fecha OT</label>
                        <input type="date" id="dtFechaOT" class="form-control form-control-sm"
                            value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-0">Fecha compromiso</label>
                        <input type="date" id="dtFechaCompromiso" class="form-control form-control-sm"
                            value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <!-- PRODUCTO COMPUESTO -->
                <div class="row g-2 mb-2">
                    <div class="col-md-5">
                        <label class="form-label mb-0">Producto compuesto (clave o descripción)</label>
                        <input id="txtProdBuscar" list="dlProductos" class="form-control form-control-sm"
                            placeholder="Escribe 3+ caracteres (realtime)">
                        <datalist id="dlProductos"></datalist>
                        <div class="subtext">Búsqueda en tiempo real (sin Enter). Solo Compues﻿to='S'.</div>
                    </div>

                    <div class="col-md-1 d-flex align-items-end">
                        <button id="btnSeleccionarProd" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label mb-0">Producto compuesto seleccionado</label>
                        <input type="text" id="txtProdSel" class="form-control form-control-sm" readonly>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label mb-0">Descripción</label>
                        <input type="text" id="txtProdSelDes" class="form-control form-control-sm" readonly>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-2">
                        <label class="form-label mb-0">UMed</label>
                        <input type="text" id="txtProdSelUmed" class="form-control form-control-sm" readonly>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label mb-0">Cantidad a producir</label>
                        <input type="number" step="0.0001" min="0" id="txtCantidadProducir"
                            class="form-control form-control-sm" value="0">
                    </div>

                    <div class="col-md-4 d-flex align-items-end">
                        <button id="btnCalcular" class="btn btn-success btn-sm w-100">
                            <span id="spCalcular" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span><i class="bi bi-calculator"></i> Calcular requerimientos
                        </button>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button id="btnExportarCsv" class="btn btn-outline-secondary btn-sm w-100" disabled>
                            <i class="bi bi-filetype-csv"></i> Exportar CSV
                        </button>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button id="btnGuardarOT" class="btn btn-success btn-sm w-100" disabled>
                            <i class="bi bi-save"></i> Guardar OT
                        </button>
                    </div>
                </div>

                <!-- TABLA COMPONENTES -->
                <div class="table-responsive">
                    <table id="tblComponentes" class="table table-sm table-striped table-hover w-100">
                        <thead>
                            <tr>
                                <th>Componente</th>
                                <th>Descripción</th>
                                <th>UMed</th>
                                <th>Etapa</th>
                                <th class="dt-right">Cantidad por unidad</th>
                                <th class="dt-right">Cantidad total</th>
                                <th class="dt-right">Cantidad solicitada</th>
                                <th class="dt-right">Stock disponible (BL)</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/v/bs5/dt-2.1.8/datatables.min.js"></script>

    <script>
        (function () {

            function toast(msg, type) {
                // toast simple (no cambia estilo global)
                const cls = (type === 'success') ? 'alert-success' : (type === 'warning') ? 'alert-warning' : (type === 'danger') ? 'alert-danger' : 'alert-secondary';
                const $a = $(`<div class="alert ${cls} py-1 px-2" style="position:fixed;right:12px;top:12px;z-index:99999;font-size:11px;min-width:260px">
      ${msg}
    </div>`);
                $('body').append($a);
                setTimeout(() => { $a.fadeOut(200, () => { $a.remove() }); }, 1800);
            }

            // DataTable
            const dtComp = $('#tblComponentes').DataTable({
                pageLength: 25,
                scrollX: true,
                autoWidth: false,
                columns: [
                    { data: 'componente' },
                    { data: 'descripcion' },
                    { data: 'unidad' },
                    { data: 'etapa' },
                    { data: 'cant_por_unidad', className: 'dt-right', render: (v) => Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 4, maximumFractionDigits: 4 }) },
                    { data: 'cant_total', className: 'dt-right', render: (v) => Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 4, maximumFractionDigits: 4 }) },
                    { data: 'cant_solicitada', className: 'dt-right', render: (v) => Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 4, maximumFractionDigits: 4 }) },
                    { data: 'stock_disponible', className: 'dt-right', render: (v) => Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 4, maximumFractionDigits: 4 }) }
                ]
            });

            let prodSel = null;
            let lastDetalles = [];
            let debounce = null;

            // --- Folio OT (preview inmediato)
            $.getJSON('orden_produccion.php', { op: 'next_folio' }, function (r) {
                if (r && r.ok) $('#txtOtInterno').val(r.folio);
            });

            // --- Empresa (desde filtros_assistpro)
            function loadEmpresas() {
                $.getJSON('../api/filtros_assistpro.php', { action: 'init', secciones: 'empresas' }, function (r) {
                    if (!r || !r.ok) { return; }
                    const $s = $('#selEmpresa').empty().append('<option value="">Seleccione empresa</option>');
                    (r.empresas || []).forEach(e => {
                        $s.append(`<option value="${String(e.cve_cia).replaceAll('"', '&quot;')}">${(e.des_cia || e.clave_empresa || e.cve_cia)}</option>`);
                    });
                });
            }

            function loadUsuarios() {
                const $u = $('#selUsuario');
                $u.empty().append('<option value="">Seleccione usuario</option>');
                $.getJSON('orden_produccion.php', { op: 'usuarios' }, function(resp){
                    if(!resp || !resp.ok){ return; }
                    (resp.data || []).forEach(r=>{
                        const val = r.cve_usuario || r.id_user || '';
                        const txt = (r.nombre_completo ? r.nombre_completo : val) + (r.cve_usuario ? ' ('+r.cve_usuario+')' : '');
                        $u.append(`<option value="${val}">${txt}</option>`);
                    });
                    // Si solo hay un usuario, seleccionarlo
                    if(($u.find('option').length-1) === 1){
                        $u.val($u.find('option').eq(1).val());
                    }
                });
            }


            // --- Almacenes (desde filtros_almacenes.php)
            function loadAlmacenes() {
                $.getJSON('../api/filtros_almacenes.php', function (r) {
                    const $s = $('#selAlmacen').empty().append('<option value="">Seleccione almacén</option>');
                    (r || []).forEach(a => {
                        $s.append(`<option value="${String(a.id).replaceAll('"', '&quot;')}">${(a.nombre || a.id)}</option>`);
                    });
                });
            }

            // --- Zonas por almacén lógico (cve_almacenp)
            function loadZonas() {
                const almacenp = $('#selAlmacen').val();
                const $z = $('#selZona').empty().append('<option value="">Seleccione zona de almacén</option>');
                $('#selBLManu').empty().append('<option value="">Seleccione BL de manufactura</option>');
                if (!almacenp) return;

                $.getJSON('orden_produccion.php', { op: 'zonas_by_almacenp', almacenp }, function (r) {
                    if (!r || !r.ok) { toast(r.msg || 'Error cargando zonas', 'danger'); return; }
                    (r.data || []).forEach(z => {
                        $z.append(`<option value="${z.cve_almac}">(${z.clave_almacen}) ${z.des_almac}</option>`);
                    });
                });
            }

            // --- BLs por zona (AreaProduccion='S')
            function loadBLs() {
                const zona = $('#selZona').val();
                const $b = $('#selBLManu').empty().append('<option value="">Seleccione BL de manufactura</option>');
                if (!zona) return;

                $.getJSON('orden_produccion.php', { op: 'bl_by_zona', zona }, function (r) {
                    if (!r || !r.ok) { toast(r.msg || 'Error cargando BLs', 'danger'); return; }
                    (r.data || []).forEach(x => {
                        $b.append(`<option value="${(x.bl || '').replaceAll('"', '&quot;')}">${x.bl}</option>`);
                    });
                });
            }

            // --- Productos compuestos realtime (>=3 chars, debounce)
            function buscarProductos() {
                const q = ($('#txtProdBuscar').val() || '').trim();
                const $dl = $('#dlProductos').empty();
                if (q.length < 3) return;

                $.getJSON('orden_produccion.php', { op: 'buscar_prod', q }, function (r) {
                    if (!r || !r.ok) return;
                    (r.data || []).forEach(p => {
                        // datalist: value=clave, label visual
                        $dl.append(`<option value="${(p.cve_articulo || '').replaceAll('"', '&quot;')}">${(p.des_articulo || '')}</option>`);
                    });
                });
            }

            function seleccionarProducto(cve) {
                if (!cve) return;
                $.getJSON('orden_produccion.php', { op: 'seleccionar_prod', cve }, function (r) {
                    if (!r || !r.ok) { toast(r.msg || 'No se pudo seleccionar', 'warning'); return; }
                    prodSel = r.producto;
                    $('#txtProdSel').val(prodSel.cve_articulo || '');
                    $('#txtProdSelDes').val(prodSel.des_articulo || '');
                    $('#txtProdSelUmed').val(prodSel.unidadMedida || prodSel.cve_umed || '');
                    toast('Producto compuesto seleccionado.', 'success');
                });
            }

            // Eventos
            loadEmpresas();
            loadUsuarios();
            loadAlmacenes();

            $('#selAlmacen').on('change', loadZonas);
            $('#selZona').on('change', loadBLs);

            $('#txtProdBuscar').on('input', function () {
                clearTimeout(debounce);
                debounce = setTimeout(buscarProductos, 220);
            });

            // Click lupa (mantengo tu botón)
            $('#btnSeleccionarProd').on('click', function () {
                const cve = ($('#txtProdBuscar').val() || '').trim();
                if (!cve) { toast('Captura una clave o busca 3+ caracteres.', 'warning'); return; }
                seleccionarProducto(cve);
            });

            // Si el usuario elige una opción del datalist, disparamos selección
            $('#txtProdBuscar').on('change', function () {
                const cve = ($('#txtProdBuscar').val() || '').trim();
                if (cve) seleccionarProducto(cve);
            });

            // Calcular requerimientos
            $('#btnCalcular').on('click', function () {
                const zona = $('#selZona').val();
                const bl = $('#selBLManu').val();
                const cant = parseFloat($('#txtCantidadProducir').val() || '0');

                if (!zona) { toast('Selecciona Zona de Almacenaje.', 'warning'); return; }
                if (!bl) { toast('Selecciona BL Manufactura.', 'warning'); return; }
                if (!prodSel) { toast('Selecciona un producto compuesto.', 'warning'); return; }
                if (!(cant > 0)) { toast('Cantidad a producir inválida.', 'warning'); return; }

                $('#btnCalcular').prop('disabled', true);
                $('#spCalcular').removeClass('d-none');

                $.post('orden_produccion.php', {
                    op: 'explosion',
                    prod: prodSel.cve_articulo,
                    cantidad: cant,
                    zona: zona,
                    bl: bl
                }, function (r) {
                    $('#btnCalcular').prop('disabled', false);
                    $('#spCalcular').addClass('d-none');

                    if (!r || !r.ok) { toast((r && r.msg) ? r.msg : 'Error en cálculo', 'danger'); return; }

                    lastDetalles = (r.detalles || []).map(x => ({
                        ...x,
                        // compat para DataTables si viniera con otras claves
                        unidad: x.unidad || x.unidadMedida || x.cve_unidad || '',
                        cant_por_unidad: x.cant_por_unidad ?? x.cantidad_por_unidad ?? 0,
                        cant_total: x.cant_total ?? x.cantidad_total ?? 0,
                        cant_solicitada: x.cant_solicitada ?? x.cantidad_solicitada ?? 0
                    }));

                    dtComp.clear().rows.add(lastDetalles).draw();

                    const has = lastDetalles.length > 0;
                    $('#btnExportarCsv').prop('disabled', !has);
                    $('#btnGuardarOT').prop('disabled', !has);

                    toast('Requerimientos calculados.', 'success');
                }, 'json').fail(function () {
                    $('#btnCalcular').prop('disabled', false);
                    $('#spCalcular').addClass('d-none');
                    toast('No se pudo calcular (error HTTP).', 'danger');
                });
            });

            // Exportar CSV
            $('#btnExportarCsv').on('click', function () {
                if (!prodSel) { toast('Selecciona un producto.', 'warning'); return; }
                const cant = parseFloat($('#txtCantidadProducir').val() || '0');
                const zona = $('#selZona').val();
                const bl = $('#selBLManu').val();
                const url = 'orden_produccion.php?op=export_csv'
                    + '&prod=' + encodeURIComponent(prodSel.cve_articulo)
                    + '&cantidad=' + encodeURIComponent(cant)
                    + '&cve_almac=' + encodeURIComponent(zona)
                    + '&bl=' + encodeURIComponent(bl);
                window.open(url, '_blank');
            });

            // Guardar OT
            $('#btnGuardarOT').on('click', function () {
                const folio = ($('#txtOtInterno').val() || '').trim();
                const zona = $('#selZona').val();
                const bl = $('#selBLManu').val();
                const cant = parseFloat($('#txtCantidadProducir').val() || '0');

                if (!folio) { toast('No hay folio de OT.', 'warning'); return; }
                if (!zona || !bl) { toast('Falta zona o BL.', 'warning'); return; }
                if (!prodSel) { toast('Falta producto.', 'warning'); return; }
                if (!(cant > 0)) { toast('Cantidad inválida.', 'warning'); return; }
                if (!lastDetalles || lastDetalles.length === 0) { toast('No hay componentes calculados.', 'warning'); return; }

                const payload = {
                    folio: folio,
                    cve_almac: zona,
                    zona_id: zona,
                    bl: bl,
                    producto: prodSel.cve_articulo,
                    cantidad: cant,
                    id_umed: (prodSel.unidadMedida || null),
                    ot_erp: ($('#txtOtErp').val() || '').trim(),
                    pedido: ($('#txtPedido').val() || '').trim(),
                    fecha_ot: ($('#dtFechaOT').val() || '').trim(),
                    fecha_comp: ($('#dtFechaCompromiso').val() || '').trim(),
                    detalles: lastDetalles
                };

                $('#btnGuardarOT').prop('disabled', true);

                $.post('orden_produccion.php', { op: 'guardar_ot', payload: JSON.stringify(payload) }, function (r) {
                    if (!r || !r.ok) {
                        $('#btnGuardarOT').prop('disabled', false);
                        toast((r && r.msg) ? r.msg : 'No se pudo guardar.', 'danger');
                        return;
                    }
                    toast('OT guardada: ' + r.folio, 'success');

                    // Limpia y genera nuevo folio
                    lastDetalles = [];
                    dtComp.clear().draw();
                    $('#btnExportarCsv').prop('disabled', true);

                    $.getJSON('orden_produccion.php', { op: 'next_folio' }, function (x) {
                        if (x && x.ok) $('#txtOtInterno').val(x.folio);
                        $('#btnGuardarOT').prop('disabled', true);
                    });
                }, 'json').fail(function () {
                    $('#btnGuardarOT').prop('disabled', false);
                    toast('Error HTTP al guardar.', 'danger');
                });
            });

        })();
    </script>

</body>

</html>
<?php include __DIR__ . '/../bi/_menu_global_end.php'; ?>