<?php
// public/ingresos/recepcion_api.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../app/db.php';

function json_ok($data = []) { echo json_encode(["ok"=>1] + $data); exit; }
function json_err($msg, $detail=null, $code=400) {
  http_response_code($code);
  echo json_encode(["ok"=>0,"error"=>$msg,"detail"=>$detail]);
  exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? $_POST['accion'] ?? $_GET['accion'] ?? '';

$action = trim((string)$action);
$action = strtolower($action);
// Aliases legacy para compatibilidad con vistas antiguas
if ($action === 'zonas') $action = 'zonas_recepcion';
if ($action === 'bl') $action = 'bl_destino';
/**
 * Helpers de introspección (para sobrevivir a variaciones legacy)
 */
function has_col($table, $col){
  $sql = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?";
  return (int)db_val($sql, [$table, $col]) > 0;
}

function first_col($table, $candidates){
  foreach($candidates as $c){
    if(has_col($table,$c)) return $c;
  }
  return null;
}

/**
 * Catálogos
 */
if ($action === 'almacenes') {
  // Preferido: c_almacenp (id, clave, nombre, Activo)
  if (db_val("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='c_almacenp'") > 0) {
    $rows = db_all("SELECT id, clave, nombre, IFNULL(Activo,1) AS Activo
                    FROM c_almacenp
                    WHERE IFNULL(Activo,1)=1
                    ORDER BY nombre");
    json_ok(["data"=>$rows]);
  }

  // Fallback: c_almacen
  $rows = db_all("SELECT cve_almac AS id, cve_almac AS clave, des_almac AS nombre, IFNULL(Activo,1) AS Activo
                  FROM c_almacen
                  WHERE IFNULL(Activo,1)=1
                  ORDER BY des_almac");
  json_ok(["data"=>$rows]);
}

if ($action === 'proveedores') {
  // Preferido: c_proveedor (ID_Proveedor, Nombre, Activo)
  if (db_val("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='c_proveedor'") > 0) {
    $rows = db_all("SELECT ID_Proveedor AS id, Nombre AS nombre, IFNULL(Activo,1) AS Activo
                    FROM c_proveedor
                    WHERE IFNULL(Activo,1)=1
                    ORDER BY Nombre");
    json_ok(["data"=>$rows]);
  }
  // Fallback
  json_ok(["data"=>[]]);
}

if ($action === 'zonas_recepcion') {
  $alm = $_GET['almacen'] ?? '';
  if ($alm === '') json_err("Falta parámetro almacen");

  // Intento: c_ubicacion tiene columna 'zona' / 'Zona' / 'desc_zona'
  if (db_val("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='c_ubicacion'") == 0) {
    json_ok(["data"=>[]]);
  }

  $colZona = first_col('c_ubicacion', ['zona','Zona','desc_zona','des_zona','zona_alm','ZonaAlmacenaje']);
  $colTipo = first_col('c_ubicacion', ['tipo','Tipo','cve_tipo','tipo_ubic','EsRecepcion']);

  // Si existe un flag de recepción úsalo, si no, listamos todas las zonas del almacén.
  $where = "WHERE u.cve_almac = ?";
  $params = [$alm];

  if ($colTipo && has_col('c_ubicacion',$colTipo)) {
    // Criterio flexible: tipo contiene 'RECEP' o EsRecepcion=1
    $where .= " AND (UPPER(CAST(u.$colTipo AS CHAR)) LIKE '%RECEP%' OR u.$colTipo = 1)";
  }

  if (!$colZona) {
    // Si no hay zona, devolvemos lista vacía controlada
    json_ok(["data"=>[]]);
  }

  $rows = db_all("SELECT DISTINCT TRIM(u.$colZona) AS zona
                  FROM c_ubicacion u
                  $where
                  AND TRIM(IFNULL(u.$colZona,'')) <> ''
                  ORDER BY zona", $params);
  json_ok(["data"=>$rows]);
}

if ($action === 'zonas_destino') {
  $alm = $_GET['almacen'] ?? '';
  if ($alm === '') json_err("Falta parámetro almacen");

  if (db_val("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='c_ubicacion'") == 0) {
    json_ok(["data"=>[]]);
  }

  $colZona = first_col('c_ubicacion', ['zona','Zona','desc_zona','des_zona','zona_alm','ZonaAlmacenaje']);
  if (!$colZona) json_ok(["data"=>[]]);

  $rows = db_all("SELECT DISTINCT TRIM(u.$colZona) AS zona
                  FROM c_ubicacion u
                  WHERE u.cve_almac = ?
                  AND TRIM(IFNULL(u.$colZona,'')) <> ''
                  ORDER BY zona", [$alm]);
  json_ok(["data"=>$rows]);
}

if ($action === 'bl_destino') {
  $alm = $_GET['almacen'] ?? '';
  $zona = $_GET['zona'] ?? '';
  if ($alm === '') json_err("Falta parámetro almacen");

  // BL = codigocsd (regla acordada)
  if (db_val("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='c_ubicacion'") == 0) {
    json_ok(["data"=>[]]);
  }

  $colBL   = first_col('c_ubicacion', ['codigoCSD','codigocsd','BL','bl']);
  $colZona = first_col('c_ubicacion', ['zona','Zona','desc_zona','des_zona','zona_alm','ZonaAlmacenaje']);

  if (!$colBL) json_ok(["data"=>[]]);

  $sql = "SELECT u.idy_ubica AS idy_ubica, u.$colBL AS bl
          FROM c_ubicacion u
          WHERE u.cve_almac = ?
            AND IFNULL(u.Activo,1)=1";
  $params = [$alm];

  if ($zona !== '' && $colZona) {
    $sql .= " AND TRIM(u.$colZona)=?";
    $params[] = $zona;
  }

  $sql .= " ORDER BY u.$colBL";
  $rows = db_all($sql, $params);
  json_ok(["data"=>$rows]);
}

/**
 * OC / Recepción
 * Se asume estructura:
 * - th_entalmacen (encabezado) + td_entalmacen (detalle)
 * - "tipo" = 'OC' y status = 'A' (activo) o similar
 */
if ($action === 'ocs') {
  $alm = $_GET['almacen'] ?? '';
  $prov = $_GET['proveedor'] ?? '';
  $q = trim($_GET['q'] ?? '');

  if (db_val("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='th_entalmacen'") == 0) {
    json_ok(["data"=>[]]);
  }

  $colFolio   = first_col('th_entalmacen', ['Fol_Folio','fol_folio','folio']);
  $colTipo    = first_col('th_entalmacen', ['tipo','Tipo']);
  $colStatus  = first_col('th_entalmacen', ['STATUS','status','Estatus']);
  $colProv    = first_col('th_entalmacen', ['ID_Proveedor','id_proveedor','Cve_Proveedor','cve_proveedor']);
  $colAlm     = first_col('th_entalmacen', ['Cve_Almac','cve_almac','Cve_AlmacP','cve_almacp']);
  $colFecha   = first_col('th_entalmacen', ['Fec_Entrada','fec_entrada','Fecha','fecha']);

  if (!$colFolio) json_ok(["data"=>[]]);

  $where = "WHERE 1=1";
  $params = [];

  if ($colTipo) { $where .= " AND UPPER($colTipo)='OC'"; }
  if ($colStatus) {
    // Activa = A o 1 o 'ABIERTO' (flexible)
    $where .= " AND (UPPER(CAST($colStatus AS CHAR)) IN ('A','ABIERTO','OPEN') OR $colStatus=1)";
  }
  if ($alm !== '' && $colAlm) { $where .= " AND $colAlm=?"; $params[]=$alm; }
  if ($prov !== '' && $colProv) { $where .= " AND $colProv=?"; $params[]=$prov; }
  if ($q !== '') { $where .= " AND $colFolio LIKE ?"; $params[] = "%$q%"; }

  $sql = "SELECT
            $colFolio AS folio,
            ".($colFecha ? "$colFecha AS fecha" : "NULL AS fecha").",
            ".($colProv ? "$colProv AS id_proveedor" : "NULL AS id_proveedor")."
          FROM th_entalmacen
          $where
          ORDER BY ".($colFecha ? "$colFecha DESC" : "$colFolio DESC")."
          LIMIT 200";

  $rows = db_all($sql, $params);
  json_ok(["data"=>$rows]);
}

if ($action === 'oc_detalle') {
  $folio = $_GET['folio'] ?? '';
  if ($folio === '') json_err("Falta parámetro folio");

  if (db_val("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='th_entalmacen'") == 0) {
    json_err("No existe th_entalmacen");
  }
  if (db_val("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='td_entalmacen'") == 0) {
    json_err("No existe td_entalmacen");
  }

  $hFolio  = first_col('th_entalmacen', ['Fol_Folio','fol_folio','folio']);
  $hProv   = first_col('th_entalmacen', ['ID_Proveedor','id_proveedor','Cve_Proveedor','cve_proveedor']);
  $hAlm    = first_col('th_entalmacen', ['Cve_Almac','cve_almac','Cve_AlmacP','cve_almacp']);

  $dFolio  = first_col('td_entalmacen', ['fol_folio','Fol_Folio','folio']);
  $dArt    = first_col('td_entalmacen', ['cve_articulo','Cve_Articulo','c_articulo']);
  $dLote   = first_col('td_entalmacen', ['cve_lote','Cve_Lote','lote']);
  $dPed    = first_col('td_entalmacen', ['CantidadPedida','cantidad_pedida','cant_pedida']);
  $dRec    = first_col('td_entalmacen', ['CantidadRecibida','cantidad_recibida','cant_recibida']);

  $head = db_one("SELECT
                    $hFolio AS folio,
                    ".($hAlm ? "$hAlm AS almacen" : "NULL AS almacen").",
                    ".($hProv ? "$hProv AS id_proveedor" : "NULL AS id_proveedor")."
                  FROM th_entalmacen
                  WHERE $hFolio = ?
                  LIMIT 1", [$folio]);

  if (!$head) json_err("OC no encontrada", $folio, 404);

  $items = db_all("SELECT
                    $dArt AS cve_articulo,
                    ".($dLote ? "$dLote AS cve_lote" : "NULL AS cve_lote").",
                    ".($dPed ? "$dPed AS cant_pedida" : "NULL AS cant_pedida").",
                    ".($dRec ? "$dRec AS cant_recibida" : "NULL AS cant_recibida")."
                  FROM td_entalmacen
                  WHERE $dFolio = ?
                  ORDER BY $dArt", [$folio]);

  json_ok(["head"=>$head, "items"=>$items]);
}

/**
 * Guardar recepción (líneas)
 * - Inserta encabezado en th_aduana (como folio de recepción) + detalle en td_aduana (si existe)
 * - Actualiza existencias: piezas / tarima según LP
 * NOTA: Esto deja “operable” la UI; el posteo a Kardex bidireccional lo conectamos en la siguiente capa.
 */
if ($action === 'guardar_recepcion') {
  $payload = json_decode(file_get_contents('php://input'), true);
  if (!$payload) json_err("JSON inválido");

  $tipo = strtoupper(trim($payload['tipo'] ?? 'OC')); // OC | RL | CD
  $empresa = $payload['empresa'] ?? null;
  $almacen = $payload['almacen'] ?? null;
  $zona_recepcion = $payload['zona_recepcion'] ?? null;
  $zona_destino = $payload['zona_destino'] ?? null;
  $bl_destino = $payload['bl_destino'] ?? null;
  $proveedor = $payload['proveedor'] ?? null;
  $oc_folio = $payload['oc_folio'] ?? null;
  $factura = $payload['factura'] ?? null;
  $proyecto = $payload['proyecto'] ?? null;
  $usuario = $payload['usuario'] ?? 'WMS';

  $lineas = $payload['lineas'] ?? [];
  if (!$almacen) json_err("Falta almacén");
  if (!$zona_recepcion) json_err("Falta zona de recepción");
  if (!is_array($lineas) || count($lineas)===0) json_err("No hay líneas");

  // BL -> idy_ubica
  $idy = null;
  if ($bl_destino) {
    $colBL = first_col('c_ubicacion', ['codigoCSD','codigocsd','BL','bl']);
    $idy = db_val("SELECT idy_ubica FROM c_ubicacion WHERE $colBL=? LIMIT 1", [$bl_destino]);
  }

  db_tx(function() use ($tipo,$almacen,$proveedor,$oc_folio,$factura,$proyecto,$usuario,$lineas,$idy) {

    // Crear folio de recepción (th_aduana si existe, si no, solo deja detalle en td_entalmacen si aplica)
    $folioRec = null;

    if (db_val("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='th_aduana'") > 0) {
      // Generación simple incremental con timestamp + random
      $folioRec = "RC".date('YmdHis').substr((string)mt_rand(1000,9999),0,4);

      // Columnas típicas de th_aduana
      $cols = [];
      $vals = [];
      $params = [];

      // Campos “seguros”
      $map = [
        'num_pedimento' => $folioRec,
        'fech_pedimento'=> date('Y-m-d H:i:s'),
        'Factura'       => $factura,
        'status'        => 'A',
        'ID_Proveedor'  => $proveedor,
        'ID_Protocolo'  => ($tipo==='OC' ? 'OC' : ($tipo==='CD' ? 'CD' : 'RL')),
        'cve_usuario'   => $usuario,
        'Cve_Almac'     => $almacen,
        'Activo'        => 1,
        'Proyecto'      => $proyecto
      ];

      foreach($map as $c=>$v){
        if (has_col('th_aduana',$c)) {
          $cols[] = $c;
          $vals[] = '?';
          $params[] = $v;
        }
      }

      if (count($cols)>0) {
        dbq("INSERT INTO th_aduana (".implode(',',$cols).") VALUES (".implode(',',$vals).")", $params);
      }
    }

    // Afectación de existencias
    foreach($lineas as $ln){
      $art = trim($ln['cve_articulo'] ?? '');
      $lote = trim($ln['cve_lote'] ?? '');
      $cad  = trim($ln['caducidad'] ?? '');
      $cant = (float)($ln['cantidad'] ?? 0);

      $contenedor = trim($ln['contenedor'] ?? ''); // clave charola
      $lp_contenedor = trim($ln['lp_contenedor'] ?? '');
      $pallet = trim($ln['pallet'] ?? '');
      $lp_pallet = trim($ln['lp_pallet'] ?? '');

      if ($art==='' || $cant<=0) continue;

      // Si viene pallet => ts_existenciatarima; si no => ts_existenciapiezas.
      if ($lp_pallet !== '') {
        // Asegurar c_charolas pallet
        if (db_val("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='c_charolas'") > 0) {
          $colTipo = first_col('c_charolas', ['tipo','Tipo']);
          $colLP   = first_col('c_charolas', ['CveLP','cveLP','lp']);
          $colClave= first_col('c_charolas', ['Clave_Contenedor','clave_contenedor','clave','Clave']);
          $colAlm  = first_col('c_charolas', ['cve_almac','Cve_Almac','cve_almacp']);

          if ($colLP && $colTipo) {
            $exists = db_val("SELECT COUNT(*) FROM c_charolas WHERE $colLP=? LIMIT 1", [$lp_pallet]);
            if ((int)$exists===0) {
              $insCols = [];
              $insVals = [];
              $insPar  = [];

              $insCols[]=$colLP;   $insVals[]='?'; $insPar[]=$lp_pallet;
              $insCols[]=$colTipo; $insVals[]='?'; $insPar[]='PALLET';
              if ($colClave && $pallet!==''){ $insCols[]=$colClave; $insVals[]='?'; $insPar[]=$pallet; }
              if ($colAlm){ $insCols[]=$colAlm; $insVals[]='?'; $insPar[]=$almacen; }

              dbq("INSERT INTO c_charolas (".implode(',',$insCols).") VALUES (".implode(',',$insVals).")", $insPar);
            }
          }
        }

        // Upsert ts_existenciatarima
        if (db_val("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='ts_existenciatarima'") > 0) {
          $colLote = first_col('ts_existenciatarima', ['lote','cve_lote','Cve_Lote']);
          $colArt  = first_col('ts_existenciatarima', ['cve_articulo','Cve_Articulo']);
          $colExist= first_col('ts_existenciatarima', ['existencia','Existencia']);
          $colAlm  = first_col('ts_existenciatarima', ['cve_almac','Cve_Almac']);
          $colUbi  = first_col('ts_existenciatarima', ['idy_ubica','Idy_Ubica']);
          $colNTar = first_col('ts_existenciatarima', ['ntarima','nTarima','Id_Tarima']);
          if ($colArt && $colExist && $colAlm && $colUbi && $colNTar) {

            // ntarima: si viene clave pallet numérica úsala; si no, deja 0
            $nt = is_numeric($pallet) ? (int)$pallet : 0;

            $where = "WHERE $colAlm=? AND $colUbi=? AND $colArt=? AND $colNTar=?";
            $par = [$almacen, (int)$idy, $art, $nt];
            if ($colLote) { $where .= " AND $colLote=?"; $par[] = $lote; }

            $exists = db_val("SELECT COUNT(*) FROM ts_existenciatarima $where", $par);

            if ((int)$exists>0) {
              dbq("UPDATE ts_existenciatarima
                   SET $colExist = IFNULL($colExist,0) + ?
                   $where", array_merge([$cant], $par));
            } else {
              // Insert
              $cols=[]; $vals=[]; $p=[];
              $cols[]=$colAlm;  $vals[]='?'; $p[]=$almacen;
              $cols[]=$colUbi;  $vals[]='?'; $p[]=(int)$idy;
              $cols[]=$colArt;  $vals[]='?'; $p[]=$art;
              if ($colLote){ $cols[]=$colLote; $vals[]='?'; $p[]=$lote; }
              $cols[]=$colNTar; $vals[]='?'; $p[]=$nt;
              $cols[]=$colExist;$vals[]='?'; $p[]=$cant;

              dbq("INSERT INTO ts_existenciatarima (".implode(',',$cols).") VALUES (".implode(',',$vals).")", $p);
            }
          }
        }

      } else {
        // Piezas sueltas o en contenedor sin pallet (existenciapiezas)
        if (db_val("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='ts_existenciapiezas'") > 0) {
          $colLote = first_col('ts_existenciapiezas', ['cve_lote','Cve_Lote','lote']);
          $colArt  = first_col('ts_existenciapiezas', ['cve_articulo','Cve_Articulo']);
          $colExist= first_col('ts_existenciapiezas', ['Existencia','existencia']);
          $colAlm  = first_col('ts_existenciapiezas', ['cve_almac','Cve_Almac']);
          $colUbi  = first_col('ts_existenciapiezas', ['idy_ubica','Idy_Ubica']);

          if ($colArt && $colExist && $colAlm && $colUbi) {
            $where = "WHERE $colAlm=? AND $colUbi=? AND $colArt=?";
            $par = [$almacen, (int)$idy, $art];
            if ($colLote) { $where .= " AND $colLote=?"; $par[]=$lote; }

            $exists = db_val("SELECT COUNT(*) FROM ts_existenciapiezas $where", $par);
            if ((int)$exists>0) {
              dbq("UPDATE ts_existenciapiezas
                   SET $colExist = IFNULL($colExist,0) + ?
                   $where", array_merge([$cant], $par));
            } else {
              $cols=[]; $vals=[]; $p=[];
              $cols[]=$colAlm;  $vals[]='?'; $p[]=$almacen;
              $cols[]=$colUbi;  $vals[]='?'; $p[]=(int)$idy;
              $cols[]=$colArt;  $vals[]='?'; $p[]=$art;
              if ($colLote){ $cols[]=$colLote; $vals[]='?'; $p[]=$lote; }
              $cols[]=$colExist;$vals[]='?'; $p[]=$cant;

              dbq("INSERT INTO ts_existenciapiezas (".implode(',',$cols).") VALUES (".implode(',',$vals).")", $p);
            }
          }
        }

        // Si viene LP contenedor, asegurar c_charolas contenedor
        if ($lp_contenedor !== '' && db_val("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='c_charolas'") > 0) {
          $colTipo = first_col('c_charolas', ['tipo','Tipo']);
          $colLP   = first_col('c_charolas', ['CveLP','cveLP','lp']);
          $colClave= first_col('c_charolas', ['Clave_Contenedor','clave_contenedor','clave','Clave']);
          $colAlm  = first_col('c_charolas', ['cve_almac','Cve_Almac','cve_almacp']);
          if ($colLP && $colTipo) {
            $exists = db_val("SELECT COUNT(*) FROM c_charolas WHERE $colLP=? LIMIT 1", [$lp_contenedor]);
            if ((int)$exists===0) {
              $insCols = [];
              $insVals = [];
              $insPar  = [];

              $insCols[]=$colLP;   $insVals[]='?'; $insPar[]=$lp_contenedor;
              $insCols[]=$colTipo; $insVals[]='?'; $insPar[]='CONTENEDOR';
              if ($colClave && $contenedor!==''){ $insCols[]=$colClave; $insVals[]='?'; $insPar[]=$contenedor; }
              if ($colAlm){ $insCols[]=$colAlm; $insVals[]='?'; $insPar[]=$almacen; }

              dbq("INSERT INTO c_charolas (".implode(',',$insCols).") VALUES (".implode(',',$insVals).")", $insPar);
            }
          }
        }
      }
    }
  });

  json_ok(["folio_recepcion"=>"OK"]);
}

json_err("Acción no válida", $action, 404);
