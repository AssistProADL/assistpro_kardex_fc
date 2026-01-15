<?php
// /public/api/sfa/promociones_api.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../app/db.php';

function jexit($arr) { echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }
function ok($data = []) { jexit(array_merge(['ok'=>1], $data)); }
function err($msg, $detalle=null, $debug=null) { jexit(['ok'=>0,'error'=>$msg,'detalle'=>$detalle,'debug'=>$debug]); }

$action = $_REQUEST['action'] ?? '';

try {

  if ($action === 'almacenes') {
    // Ajusta si tu catálogo de almacenes está en otra tabla.
    // Recomendado: c_almacenp (tiene clave/nombre) para UI corporativa.
    $empresa = $_GET['empresa'] ?? null;

    $sql = "SELECT
              CAST(id AS CHAR) AS id,
              IFNULL(clave,'') AS clave,
              IFNULL(nombre,'') AS nombre
            FROM c_almacenp
            WHERE 1=1 ";
    $params = [];
    if ($empresa !== null && $empresa !== '') { $sql .= " AND empresa_id = ? "; $params[] = $empresa; }
    $sql .= " ORDER BY nombre ";

    $rows = db_all($sql, $params);
    ok(['rows'=>$rows]);
  }

  if ($action === 'list') {
    $almacen_id = $_GET['almacen_id'] ?? '';
    if ($almacen_id === '') err("Falta almacen_id");

    // ESQUEMA REAL (según tu phpMyAdmin):
    // listapromo: id, Lista, Descripcion, Caduca, FechaI, FechaF, Grupo, Activa, Tipo, Cve_Almac
    // Nota: por_depcont / por_depfical NO existen en este esquema -> los exponemos en 0 para compatibilidad UI.
    $rows = db_all("
      SELECT
        lp.id,
        IFNULL(lp.Lista,'')       AS clave,
        IFNULL(lp.Descripcion,'') AS descripcion,
        0                         AS por_depcont,
        0                         AS por_depfical,
        IFNULL(lp.Activa,0)       AS Activo,
        IFNULL(lp.Cve_Almac,0)    AS id_almacen,
        lp.FechaI                 AS fecha_inicio,
        lp.FechaF                 AS fecha_fin,
        IFNULL(lp.Tipo,'')        AS tipo,

        -- métricas rápidas (motor nuevo)
        (SELECT COUNT(*) FROM promo_scope ps WHERE ps.promo_id = CAST(lp.id AS CHAR) AND ps.activo=1) AS total_scope,
        (SELECT COUNT(*) FROM promo_rule  pr WHERE pr.promo_id = CAST(lp.id AS CHAR) AND pr.activo=1) AS total_rules
      FROM listapromo lp
      WHERE IFNULL(lp.Cve_Almac,0) = ?
      ORDER BY lp.id DESC
    ", [$almacen_id]);

    ok(['rows'=>$rows]);
  }

  if ($action === 'get') {
    $id = $_GET['id'] ?? '';
    if ($id==='') err("Falta id");

    // Encabezado normalizado para UI (alias a nombres legacy esperados por pantallas viejas)
    $promo = db_one(
      "SELECT
        lp.id,
        lp.Lista       AS cve_gpoart,
        lp.Descripcion AS des_gpoart,
        0              AS por_depcont,
        0              AS por_depfical,
        lp.Activa      AS Activo,
        lp.Cve_Almac   AS id_almacen,
        lp.Caduca,
        lp.FechaI,
        lp.FechaF,
        lp.Grupo,
        lp.Tipo
      FROM listapromo lp
      WHERE lp.id = ?",
      [$id]
    );
    if (!$promo) err("No existe promoción");

    // Nuevo motor (si está instalado)
    $rules  = db_all("SELECT * FROM promo_rule  WHERE promo_id = ? AND activo=1 ORDER BY nivel", [strval($id)]);
    $scope  = db_all("SELECT * FROM promo_scope WHERE promo_id = ? AND activo=1 ORDER BY scope_tipo, scope_id", [strval($id)]);

    // rewards por regla
    $rule_ids = array_map(fn($r)=>$r['id_rule'], $rules);
    $rewards = [];
    if (count($rule_ids) > 0) {
      $in = implode(',', array_fill(0, count($rule_ids), '?'));
      $rewards = db_all("SELECT * FROM promo_reward WHERE id_rule IN ($in) AND activo=1 ORDER BY id_rule, id_reward", $rule_ids);
    }

    ok(['promo'=>$promo,'rules'=>$rules,'scope'=>$scope,'rewards'=>$rewards]);
  }

  if ($action === 'save') {
    // Guarda encabezado en listapromo (legacy)
    $id         = $_POST['id'] ?? '';
    $almacen_id = $_POST['id_almacen'] ?? '';
    $clave      = trim($_POST['cve_gpoart'] ?? '');
    $desc       = trim($_POST['des_gpoart'] ?? '');
    $activo     = isset($_POST['Activo']) ? intval($_POST['Activo']) : 1;

    // opcionales
    $caduca = isset($_POST['Caduca']) ? intval($_POST['Caduca']) : 0;
    $fechaI = $_POST['FechaI'] ?? null;
    $fechaF = $_POST['FechaF'] ?? null;
    $tipo   = $_POST['Tipo'] ?? '';
    $grupo  = $_POST['Grupo'] ?? null;

    if ($almacen_id==='') err("Falta id_almacen");
    if ($clave==='') err("Falta clave");
    if ($desc==='') err("Falta descripción");

    $saved_id = db_tx(function() use ($id,$almacen_id,$clave,$desc,$activo,$caduca,$fechaI,$fechaF,$tipo,$grupo) {
      if ($id==='') {
        dbq("INSERT INTO listapromo (Lista, Descripcion, Caduca, FechaI, FechaF, Grupo, Activa, Tipo, Cve_Almac)
             VALUES (?,?,?,?,?,?,?,?,?)",
            [$clave,$desc,$caduca,$fechaI,$fechaF,$grupo,$activo,$tipo,$almacen_id]);
        return db_val("SELECT LAST_INSERT_ID()");
      } else {
        dbq("UPDATE listapromo
             SET Lista=?, Descripcion=?, Caduca=?, FechaI=?, FechaF=?, Grupo=?, Activa=?, Tipo=?, Cve_Almac=?
             WHERE id=?",
            [$clave,$desc,$caduca,$fechaI,$fechaF,$grupo,$activo,$tipo,$almacen_id,$id]);
        return $id;
      }
    });

    ok(['id'=>$saved_id]);
  }

  if ($action === 'toggle') {
    $id = $_POST['id'] ?? '';
    $activo = $_POST['Activo'] ?? null;
    if ($id==='' || $activo===null) err("Parámetros incompletos");
    dbq("UPDATE listapromo SET Activa=? WHERE id=?", [intval($activo), $id]);
    ok();
  }

  // ------------------------------
  // NUEVO MOTOR: RULE / SCOPE / REWARD
  // ------------------------------

  if ($action === 'rule_save') {
    $promo_id = $_POST['promo_id'] ?? '';
    if ($promo_id==='') err("Falta promo_id");

    $id_rule  = $_POST['id_rule'] ?? '';
    $nivel    = intval($_POST['nivel'] ?? 1);
    $trigger  = $_POST['trigger_tipo'] ?? 'MONTO';
    $th_monto = ($_POST['threshold_monto'] ?? '') !== '' ? floatval($_POST['threshold_monto']) : null;
    $th_qty   = ($_POST['threshold_qty'] ?? '') !== '' ? floatval($_POST['threshold_qty']) : null;
    $acumula  = $_POST['acumula'] ?? 'S';
    $acum_por = $_POST['acumula_por'] ?? 'PERIODO';
    $min_skus = ($_POST['min_items_distintos'] ?? '') !== '' ? intval($_POST['min_items_distintos']) : null;
    $obs      = trim($_POST['observaciones'] ?? '');

    if ($id_rule==='') {
      dbq("INSERT INTO promo_rule (promo_id,nivel,trigger_tipo,threshold_monto,threshold_qty,acumula,acumula_por,min_items_distintos,observaciones,activo)
           VALUES (?,?,?,?,?,?,?,?,?,1)",
        [strval($promo_id),$nivel,$trigger,$th_monto,$th_qty,$acumula,$acum_por,$min_skus,$obs]);
      $id_rule = db_val("SELECT LAST_INSERT_ID()");
    } else {
      dbq("UPDATE promo_rule
           SET nivel=?, trigger_tipo=?, threshold_monto=?, threshold_qty=?, acumula=?, acumula_por=?, min_items_distintos=?, observaciones=?, activo=1
           WHERE id_rule=? AND promo_id=?",
        [$nivel,$trigger,$th_monto,$th_qty,$acumula,$acum_por,$min_skus,$obs,$id_rule,strval($promo_id)]);
    }

    ok(['id_rule'=>$id_rule]);
  }

  if ($action === 'rule_del') {
    $id_rule = $_POST['id_rule'] ?? '';
    if ($id_rule==='') err("Falta id_rule");
    dbq("UPDATE promo_rule SET activo=0 WHERE id_rule=?", [$id_rule]);
    dbq("UPDATE promo_reward SET activo=0 WHERE id_rule=?", [$id_rule]);
    ok();
  }

  if ($action === 'scope_save') {
    $promo_id = $_POST['promo_id'] ?? '';
    $scope_tipo = $_POST['scope_tipo'] ?? '';
    $scope_id   = trim($_POST['scope_id'] ?? '');
    $exclusion  = $_POST['exclusion'] ?? 'N';
    if ($promo_id==='' || $scope_tipo==='' || $scope_id==='') err("Parámetros incompletos");

    dbq("INSERT INTO promo_scope (promo_id,scope_tipo,scope_id,exclusion,activo)
         VALUES (?,?,?,?,1)",
      [strval($promo_id),$scope_tipo,$scope_id,$exclusion]);

    ok();
  }

  if ($action === 'scope_del') {
    $id_scope = $_POST['id_scope'] ?? '';
    if ($id_scope==='') err("Falta id_scope");
    dbq("UPDATE promo_scope SET activo=0 WHERE id_scope=?", [$id_scope]);
    ok();
  }

  if ($action === 'reward_save') {
    $id_rule = $_POST['id_rule'] ?? '';
    if ($id_rule==='') err("Falta id_rule");

    $id_reward = $_POST['id_reward'] ?? '';
    $reward_tipo = $_POST['reward_tipo'] ?? '';
    $valor = ($_POST['valor'] ?? '') !== '' ? floatval($_POST['valor']) : null;
    $tope = ($_POST['tope_valor'] ?? '') !== '' ? floatval($_POST['tope_valor']) : null;
    $cve_articulo = trim($_POST['cve_articulo'] ?? '');
    $qty = ($_POST['qty'] ?? '') !== '' ? floatval($_POST['qty']) : null;
    $unimed = trim($_POST['unimed'] ?? '');
    $aplica_sobre = $_POST['aplica_sobre'] ?? 'TOTAL';
    $obs = trim($_POST['observaciones'] ?? '');

    if ($reward_tipo==='') err("Falta reward_tipo");

    if ($id_reward==='') {
      dbq("INSERT INTO promo_reward (id_rule,reward_tipo,valor,tope_valor,cve_articulo,qty,unimed,aplica_sobre,observaciones,activo)
           VALUES (?,?,?,?,?,?,?,?,?,1)",
        [$id_rule,$reward_tipo,$valor,$tope,$cve_articulo,$qty,$unimed,$aplica_sobre,$obs]);
      $id_reward = db_val("SELECT LAST_INSERT_ID()");
    } else {
      dbq("UPDATE promo_reward
           SET reward_tipo=?, valor=?, tope_valor=?, cve_articulo=?, qty=?, unimed=?, aplica_sobre=?, observaciones=?, activo=1
           WHERE id_reward=? AND id_rule=?",
        [$reward_tipo,$valor,$tope,$cve_articulo,$qty,$unimed,$aplica_sobre,$obs,$id_reward,$id_rule]);
    }

    ok(['id_reward'=>$id_reward]);
  }

  if ($action === 'reward_del') {
    $id_reward = $_POST['id_reward'] ?? '';
    if ($id_reward==='') err("Falta id_reward");
    dbq("UPDATE promo_reward SET activo=0 WHERE id_reward=?", [$id_reward]);
    ok();
  }

  // ------------------------------
  // SIMULADOR (monto/periodo) - MVP
  // ------------------------------
  if ($action === 'simulate') {
    $promo_id = $_GET['promo_id'] ?? '';
    $empresa  = $_GET['IdEmpresa'] ?? '';
    $cliente  = $_GET['CodCliente'] ?? '';
    $vendedor = $_GET['VendedorId'] ?? '';
    $ruta     = $_GET['RutaId'] ?? '';
    $fecha    = $_GET['Fecha'] ?? date('Y-m-d');
    $monto    = floatval($_GET['TOTAL'] ?? 0);

    if ($promo_id==='') err("Falta promo_id");

    // Validar que promo esté activa en listapromo
    $lp = db_one("SELECT id, Activa FROM listapromo WHERE id=?", [$promo_id]);
    if (!$lp || intval($lp['Activa'])!==1) err("Promoción inactiva o no existe");

    // Reglas activas
    $rules = db_all("SELECT * FROM promo_rule WHERE promo_id=? AND activo=1 ORDER BY nivel", [strval($promo_id)]);

    // Determinar mejor nivel aplicable por monto (MVP)
    $best = null;
    foreach ($rules as $r) {
      if ($r['trigger_tipo']==='MONTO' || $r['trigger_tipo']==='UNIDADES_Y_MONTO' || $r['trigger_tipo']==='MIXTO') {
        $th = $r['threshold_monto'] !== null ? floatval($r['threshold_monto']) : 0;
        if ($monto >= $th) $best = $r;
      }
    }

    if (!$best) ok(['aplica'=>0,'msg'=>'No cumple umbral','rewards'=>[]]);

    $rewards = db_all("SELECT * FROM promo_reward WHERE id_rule=? AND activo=1 ORDER BY id_reward", [$best['id_rule']]);
    ok(['aplica'=>1,'nivel'=>$best['nivel'],'id_rule'=>$best['id_rule'],'rewards'=>$rewards]);
  }

  err("Acción no soportada", null, ['action'=>$action]);

} catch (Throwable $e) {
  err("Error servidor", $e->getMessage(), ['action'=>$action, 'get'=>$_GET, 'post'=>$_POST]);
}
