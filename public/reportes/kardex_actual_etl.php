<?php
// ===== BOOTSTRAP (no UI changes) =====
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors','1');

// 1) PDO bootstrap — prefer your app/db.php
if (!isset($pdo) || !($pdo instanceof PDO)) {
  $dbPath = __DIR__ . '/../app/db.php';
  if (file_exists($dbPath)) {
    require_once $dbPath;
  }
}
if (!isset($pdo) || !($pdo instanceof PDO)) {
  try {
    $pdo = new PDO(
      'mysql:host=127.0.0.1;dbname=assistpro_etl_fc;charset=utf8mb4',
      'root', '',
      [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      ]
    );
  } catch (Throwable $e) { die('Error de conexión: ' . $e->getMessage()); }
}

// 2) Defaults to avoid undefined-variable warnings
$TITLE = $TITLE ?? '';
$TITLE_NAME = $TITLE_NAME ?? '';
$STITLE = $STITLE ?? '';
$TITLES = $TITLES ?? '';
$sqlDetalle = $sqlDetalle ?? '';
$sqlResumen = $sqlResumen ?? '';
$sqlFiltros = $sqlFiltros ?? '';
$rows = $rows ?? [];
$kpi = $kpi ?? [];
?>
<?php
// ===== ETL wiring: create compatibility views if missing (read-only) =====
try {
  // v_kardex_doble_partida — minimal analytics view if absent
  $v1 = $pdo->prepare("SELECT COUNT(*) FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'v_kardex_doble_partida'");
  $v1->execute();
  if ((int)$v1->fetchColumn() === 0) {
    $pdo->exec("
      CREATE OR REPLACE VIEW v_kardex_doble_partida AS
      SELECT
        CAST(tc.id AS UNSIGNED)                    AS tx_id,
        STR_TO_DATE(tc.fecha, '%Y-%m-%d %H:%i:%s') AS fecha_hora,
        UPPER(COALESCE(tm.nombre,'DESCONOCIDO'))   AS tipo_tx,
        NULL AS proyecto_id,
        tc.cve_articulo              AS producto_id,
        NULL AS uom,
        NULLIF(tc.cve_lote,'')       AS lote,
        NULL AS serie,
        NULL AS ajuste_id,
        NULL AS ajuste_motivo,
        NULL AS alm_ori_id, NULL AS ubi_ori_id,
        NULL AS alm_dst_id, NULL AS ubi_dst_id,
        CASE WHEN UPPER(tm.nombre) IN ('SALIDA','BAJA') THEN ABS(COALESCE(tc.cantidad,0))
             WHEN UPPER(tm.nombre) LIKE 'AJUSTE%' AND COALESCE(tc.cantidad,0) < 0 THEN ABS(tc.cantidad)
             WHEN UPPER(tm.nombre) IN ('TRANSFERENCIA','ACOMODO') THEN ABS(COALESCE(tc.cantidad,0))
             ELSE 0 END AS mov_ori,
        CASE WHEN UPPER(tm.nombre) IN ('ENTRADA') THEN ABS(COALESCE(tc.cantidad,0))
             WHEN UPPER(tm.nombre) LIKE 'AJUSTE%' AND COALESCE(tc.cantidad,0) > 0 THEN tc.cantidad
             WHEN UPPER(tm.nombre) IN ('TRANSFERENCIA','ACOMODO') THEN ABS(COALESCE(tc.cantidad,0))
             ELSE 0 END AS mov_dst,
        0 AS stock_ini_ori, 0 AS stock_fin_ori,
        0 AS stock_ini_dst, 0 AS stock_fin_dst,
        NULL AS referencia, NULL AS notas, NULL AS usuario_id,
        tc.empresa_id
      FROM stg_t_cardex tc
      LEFT JOIN stg_t_tipomovimiento tm
        ON tm.empresa_id = tc.empresa_id
       AND tm.id_TipoMovimiento = tc.id_TipoMovimiento
    ");
  }

  // t_cardex — wrapper sobre staging con firma compatible si falta
  $v2 = $pdo->prepare("SELECT COUNT(*) FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 't_cardex'");
  $v2->execute();
  if ((int)$v2->fetchColumn() === 0) {
    $pdo->exec("
      CREATE OR REPLACE VIEW t_cardex AS
      SELECT
        CAST(tc.id AS UNSIGNED)                AS id,
        tc.empresa_id                          AS empresa_id,
        STR_TO_DATE(tc.fecha, '%Y-%m-%d %H:%i:%s') AS fecha,
        tc.id_TipoMovimiento                   AS id_TipoMovimiento,
        tc.cve_articulo                        AS cve_articulo,
        COALESCE(NULLIF(tc.cve_lote,''), NULL) AS cve_lote,
        tc.cantidad                            AS cantidad,
        NULL AS Cve_Almac,
        NULL AS cve_usuario
      FROM stg_t_cardex tc
    ");
  }
} catch (Throwable $e) {
  // No frenar la UI; si las vistas ya existen en tu ETL, este bloque no hace nada.
}
// ========================================================================
?>

include '../../config.php';

error_reporting(0);

if($_POST['action'] == 'enter-view')
{
    //$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

    $id_user = $_POST['id_user'];
    $sql = "SELECT c_almacenp.id, c_almacenp.clave, CONVERT(c_almacenp.nombre USING utf8) AS nombre FROM c_almacenp LEFT JOIN t_usu_alm_pre ON c_almacenp.clave = t_usu_alm_pre.cve_almac WHERE c_almacenp.Activo = 1 AND t_usu_alm_pre.id_user = '{$id_user}'";

    $res = getArraySQL($sql);

    $array = [
        "almacen"=>$res
    ];

    echo json_encode($array);

/*
    //$sql = 'SELECT id, clave, nombre FROM c_almacenp WHERE Activo = 1';
    if (!($res = mysqli_query($conn, $sql))) {
        echo "Falló la preparación: (" . mysqli_error($conn) . ") ";
    }

    $rawdata = array();

    $i = 0;

    while($row = mysqli_fetch_array($res))
    {
        $rawdata[$i] = $row;
        $i++;
    }

    $array = [
        "almacen"=>$rawdata
    ];
    //echo var_dump($array);
    echo json_encode($array);
*/
}
else if ($_POST['action'] == 'loadDetalleCajas') 
{
  $page = $_POST['page']; // get the requested page
  $limit = $_POST['rows']; // get how many rows we want to have into the grid
  $sidx = $_POST['sidx']; // get index row - i.e. user click to sort
  $sord = $_POST['sord']; // get the direction
  $almacen = $_POST['almacen'];
  $lp      = $_POST['lp'];

  $start = $limit*$page - $limit; // do not put $limit*($page - 1)
  if(!$sidx)
  {
    $sidx =1;
  }


  // se conecta a la base de datos
  $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);


  $sql1 = "
SELECT DISTINCT u.CodigoCSD AS ubicacion, ch.CveLP AS LP, cj.CveLP AS Caja, a.cve_articulo, a.des_articulo, ec.cve_lote, 
       COALESCE(IF(IFNULL(a.Caduca, 'N') = 'S' AND IFNULL(ec.cve_lote,'') != '',IF(DATE_FORMAT(l.Caducidad,'%Y-%m-%d') = '0000-00-00','',DATE_FORMAT(l.Caducidad,'%d-%m-%Y')),'')) AS caducidad,
       k.cantidad as PiezasXCaja
FROM ts_existenciacajas ec
LEFT JOIN t_MovCharolas mch on mch.ID_Contenedor = ec.Id_Caja
LEFT JOIN t_cardex k ON k.id = mch.id_kardex
LEFT JOIN c_almacenp al ON al.id = ec.Cve_Almac
LEFT JOIN c_almacen z ON z.cve_almacenp = al.id
LEFT JOIN c_charolas ch ON ch.cve_almac = al.id AND ec.nTarima = ch.IDContenedor 
LEFT JOIN c_charolas cj ON cj.cve_almac = al.id AND ec.Id_Caja = cj.IDContenedor AND cj.tipo = 'Caja'
LEFT JOIN c_ubicacion u ON u.cve_almac = z.cve_almac AND ec.idy_ubica = u.idy_ubica
LEFT JOIN c_articulo a ON ec.cve_articulo = a.cve_articulo
LEFT JOIN Rel_Articulo_Almacen ra ON ra.Cve_Almac = al.id AND ra.Cve_Articulo = a.cve_articulo
LEFT JOIN c_lotes l ON l.cve_articulo = ra.Cve_Articulo AND l.Lote = ec.cve_lote
WHERE #ec.Cve_Almac = $almacen AND u.idy_ubica = '$idy_ubica' AND 
IFNULL(ch.CveLP, '') = '$lp' AND u.CodigoCSD IS NOT NULL #AND a.Cve_Articulo = '$clave' AND ec.cve_lote = '$lote'
      ";
  // hace una llamada previa al procedimiento almacenado Lis_Facturas
  if (!($res = mysqli_query($conn, $sql1))) 
  {
    echo "Falló la preparación: (" . mysqli_error($conn) . ") ";
  }
  $count = mysqli_num_rows($res);
  $sql1 .= " LIMIT $start, $limit ";
  if (!($res = mysqli_query($conn, $sql1))) 
  {
    echo "Falló la preparación: (" . mysqli_error($conn) . ") ";
  }


  if( $count >0 ) 
  {
    $total_pages = ceil($count/$limit);
  } 
  else 
  {
    $total_pages = 0;
  } 

  if ($page > $total_pages)
  {
    $page=$total_pages;  
  }

  $responce->page = $page;
  $responce->total = $total_pages;
  $responce->records = $count;
  $responce->sql = $sql1;

  $arr = array();
  $i = 0;
  $clave_actual = ""; $num_rows = 0; 
  $lote_actual = ""; $lote_comp = "";
  $clave_actual_pedido = "";

  $cant_pedida = 0; $cant_recibida = 0; $diferencia = 0;
  while ($row = mysqli_fetch_array($res)) 
  {
    //$row = array_map("utf8_encode", $row );
    extract($row);

    $responce->rows[$i]['id']=$i;
    $responce->rows[$i]['cell']=array(
                                      $ubicacion, 
                                      $LP,
                                      $Caja,
                                      $cve_articulo,
                                      $des_articulo, 
                                      $cve_lote,
                                      $caducidad,
                                      $PiezasXCaja
                                     );
    $i++;
  }
  echo json_encode($responce);
}
else if($_POST['action'] == 'getLotesArticulosKardex')
{
    if(isset($_POST['cve_articulo']))
    {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

        $cve_aritculo = $_POST['cve_articulo'];
        $sql = "SELECT DISTINCT cve_lote FROM t_cardex WHERE cve_articulo = '{$cve_aritculo}'";

        if (!($res = mysqli_query($conn, $sql))) {
            echo "Falló la preparación: (" . mysqli_error($conn) . ") ";
        }

        $i = 0;
        $option = "";
        while ($row = mysqli_fetch_array($res)) 
        {
            $option .= "<option value='".$row["cve_lote"]."'>".$row["cve_lote"]."</option>";
            $i++;
        }

        $options = "<option value=''>Seleccione Lote | Serie (".$i.")</option>".$option;
        $arr = array(
            "success" => true,
            "lotes" => $options
        );
        $arr = array_merge($arr);

        echo json_encode($arr);
    }
}
else if (isset($_POST) && !empty($_POST)) {
    $page = $_POST['page']; // get the requested page
    $limit = $_POST['rows']; // get how many rows we want to have into the grid
    $sidx = $_POST['sidx']; // get index row - i.e. user click to sort
    $sord = $_POST['sord']; // get the direction
    $almacen= $_POST['almacen'];
    $lote= $_POST['lote'];
    $cve_articulo= $_POST['cve_articulo'];
    $fecha_inicio= $_POST['fechaI'];
    $fecha_final= $_POST['fechaF'];
    $id_proveedor = $_POST['cve_proveedor'];
    $cve_cliente = $_POST['cve_cliente'];
    $movimiento = $_POST['movimiento'];
    $OCBusq = $_POST['OCBusq'];

    $kardex_consolidado = 0;
    if(isset($_POST['kardex_consolidado']))
      $kardex_consolidado = $_POST['kardex_consolidado'];
    
    //if (!empty($fecha_inicio)) $fecha_inicio = date("Y-m-d", strtotime($fecha_inicio));
    //if (!empty($fecha_final)) $fecha_final = date("Y-m-d", strtotime($fecha_final));

    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    $sql_charset = "SELECT DISTINCT IFNULL(Valor, 'latin1') as charset FROM t_configuraciongeneral WHERE cve_conf = 'charset'";
    if (!($res_charset = mysqli_query($conn, $sql_charset)))echo "Falló la preparación Charset: (" . mysqli_error($conn) . ") ";
    $charset = mysqli_fetch_array($res_charset)['charset'];
    mysqli_set_charset($conn , $charset);


    $sql_instancia = "SELECT DISTINCT IFNULL(Valor, '') as instancia FROM t_configuraciongeneral WHERE cve_conf = 'instancia'";
    if (!($res_instancia = mysqli_query($conn, $sql_instancia)))
        echo "Falló la preparación instancia: (" . mysqli_error($conn) . ") ";
    $instancia = mysqli_fetch_array($res_instancia)['instancia'];


    //////////////////////////////////////////////se recibe los parametros POST del grid////////////////////////////////////////////////
    $_criterio = $_POST['criterio'];

    $SQLCriterio = "";
    $SQLCriterioCajas = "";
    if($_criterio)
    {
        $SQLCriterio = " AND (k.cve_articulo LIKE '%".$_criterio."%' OR k.cve_lote LIKE '%".$_criterio."%' OR a.des_articulo LIKE '%".$_criterio."%' OR uo.CodigoCSD LIKE '%".$_criterio."%' OR ud.CodigoCSD LIKE '%".$_criterio."%' OR ch.clave_contenedor LIKE '%".$_criterio."%' OR ch.CveLP LIKE '%".$_criterio."%' OR ch_cj.CveLP LIKE '%".$_criterio."%' OR rd.desc_ubicacion LIKE '%".$_criterio."%' OR m.nombre LIKE '%".$_criterio."%' OR k.cve_usuario LIKE '%".$_criterio."%' OR rd.cve_ubicacion LIKE '%".$_criterio."%' OR k.origen LIKE '%".$_criterio."%' OR k.destino LIKE '%".$_criterio."%') ";

        $SQLCriterioCajas = " AND (k.cve_articulo LIKE '%".$_criterio."%' OR k.cve_lote LIKE '%".$_criterio."%' OR a.des_articulo LIKE '%".$_criterio."%' OR u_or.CodigoCSD LIKE '%".$_criterio."%' OR u_dest.CodigoCSD LIKE '%".$_criterio."%' OR ch.clave_contenedor LIKE '%".$_criterio."%' OR ch.CveLP LIKE '%".$_criterio."%' OR ch_cj.CveLP LIKE '%".$_criterio."%' OR k.cve_usuario LIKE '%".$_criterio."%') ";
    }

    $SQLOC = "";
    if($OCBusq)
        $SQLOC = " AND (oc.num_pedimento LIKE '%$OCBusq%' OR oc.Factura LIKE '%$OCBusq%' OR ent_ocompra.num_pedimento LIKE '%$OCBusq%' OR ent_ocompra.Factura LIKE '%$OCBusq%') ";

    $SQL_Mov = "";
    if($movimiento)
    {
        $SQL_Mov = " AND k.id_TipoMovimiento IN ($movimiento) ";
        if($movimiento == 6 || $movimiento == 12)
            $SQL_Mov = " AND k.id_TipoMovimiento IN (6,12) ";
    }

    $SQLArticulo = "";
    if($cve_articulo)
    {
        $SQLArticulo = " AND k.cve_articulo = '".$cve_articulo."' ";
    }

    $SQLLote = "";
    if($lote)
    {
        $SQLLote = " AND k.cve_lote = '".$lote."' ";
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    $SQLFecha = "";
    if($fecha_inicio && $fecha_final)
    {
        $SQLFecha = " AND k.fecha BETWEEN STR_TO_DATE('".$fecha_inicio."', '%d-%m-%Y') AND STR_TO_DATE('".$fecha_final."', '%d-%m-%Y')";
        if($fecha_inicio == $fecha_final)
        $SQLFecha = " AND DATE_FORMAT(k.fecha, '%Y-%m-%d') = STR_TO_DATE('".$fecha_inicio."', '%d-%m-%Y')";
    }
    else if(!$fecha_inicio && $fecha_final)
    {
        $SQLFecha = " AND k.fecha <= STR_TO_DATE('".$fecha_final."', '%d-%m-%Y')";
    }
    else if($fecha_inicio && !$fecha_final)
    {
        $SQLFecha = " AND k.fecha >= STR_TO_DATE('".$fecha_inicio."', '%d-%m-%Y')";
    }

    if(!$SQLArticulo && !$SQLLote && !$SQLFecha && !$OCBusq) //&& !$id_proveedor //&& !$_criterio
    {
        //$SQLFecha = " AND DATE_FORMAT(k.fecha, '%d-%m-%Y') = DATE_FORMAT((SELECT MAX(fecha) FROM t_cardex), '%d-%m-%Y') ";
        //$SQLFecha = " AND DATE_FORMAT(k.fecha, '%d-%m-%Y') = (SELECT MAX(DATE_FORMAT(fecha, '%d-%m-%Y')) FROM t_cardex) ";
        $SQLFecha = "";
        //$SQLFecha = " AND k.fecha >= (SELECT DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL -7 DAY), '%d-%m-%Y') AS fecha_semana FROM DUAL) ";

        //$SQLFecha = " AND k.fecha >= (SELECT DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL -7 DAY), '%d-%m-%Y') AS fecha_semana FROM DUAL) ";
        //if($instancia == 'foam') 
        $SQLFecha = " AND DATE_FORMAT(k.fecha, '%Y-%m-%d') = CURDATE() ";
    }

    $sqlSesionEmpresa = "";

    if($id_proveedor)
    {
        //INNER JOIN c_proveedores p ON p.ID_Proveedor = c.ID_Proveedor AND IFNULL(c.ID_Proveedor, 0) != 0 AND p.ID_Proveedor IN (ent_orig.Cve_Proveedor, ent_dest.Cve_Proveedor, c.ID_Proveedor) AND p.ID_Proveedor = {$id_proveedor}
        $sqlSesionEmpresa = "
        LEFT JOIN c_cliente c ON c.Cve_Clte IN (th_orig.Cve_clte, th_dest.Cve_clte)
        INNER JOIN c_proveedores p ON p.ID_Proveedor = {$id_proveedor} OR (ent_ocompra.procedimiento = p.cve_proveedor OR ent_orig.Cve_Proveedor = {$id_proveedor})
        ";
        //p.ID_Proveedor IN (ent_orig.Cve_Proveedor, ent_dest.Cve_Proveedor, c.ID_Proveedor) AND
    }

    if($cve_cliente)
    {
        $sqlSesionEmpresa = "
        INNER JOIN c_cliente c ON c.Cve_Clte IN (th_orig.Cve_clte, th_dest.Cve_clte) AND c.Cve_Clte = '$cve_cliente' 
        ";
    }

    $start = $limit*$page - $limit; // do not put $limit*($page - 1)

    if(!$sidx) $sidx =1;

    // se conecta a la base de datos
/*
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

    // prepara la llamada al procedimiento almacenado Lis_Facturas
    $sqlCount = "SELECT
            count(t_ruta.ID_Ruta) as cuenta,
            t_ruta.cve_ruta,
            t_ruta.descripcion,
            IF(t_ruta.status='A','Activo','Baja') as status,
            t_ruta.Activo
            FROM
            t_ruta
			WHERE 
			(t_ruta.descripcion LIKE '%".$_criterio."%' OR t_ruta.cve_ruta LIKE '%".$_criterio."%') AND t_ruta.Activo = 1 and t_ruta.cve_almacenp='".$almacen."' {$SQLRuta};";
    if (!($res = mysqli_query($conn, $sqlCount))) {
        echo "Falló la preparación: (" . mysqli_error($conn) . ") ";
    }

    $row = mysqli_fetch_array($res);
    $count = $row[0];

    mysqli_close();
*/

    $_page = 0;

    if (intval($page)>0) $_page = ($page-1)*$limit;

/*
            (SELECT COUNT(DISTINCT t_clientexruta.clave_cliente) FROM c_destinatarios d 
            LEFT JOIN c_cliente c ON d.Cve_Clte = c.Cve_Clte
            LEFT JOIN t_clientexruta ON t_clientexruta.clave_cliente = d.id_destinatario
            LEFT JOIN t_ruta tr ON tr.ID_Ruta = t_clientexruta.clave_ruta
            LEFT JOIN RelDayCli ON RelDayCli.Cve_Cliente = d.Cve_Clte AND tr.cve_ruta = RelDayCli.Cve_Ruta AND d.id_destinatario = RelDayCli.Id_Destinatario
            WHERE d.Activo = '1' AND c.Cve_Almacenp = '".$almacen."' AND tr.cve_ruta = t_ruta.cve_ruta) AS N_Clientes,
*/
            $fields_reporte_w = ""; $sql_ref_well = ""; $sql_pedimento_well = "";
            if(isset($_POST['reporte_w']))
            {
                $fields_reporte_w = "
                ###############################################################################################
                IFNULL(oc.Pedimento, '') AS Pedimento_well, IFNULL(oc.recurso, '') AS ref_well, 
                ###############################################################################################
                ";

                $refWell = $_POST['refWell'];
                if($refWell) $sql_ref_well = " AND oc.recurso LIKE '%$refWell%' "; 

                $pedimentoW = $_POST['pedimentoW'];
                if($pedimentoW) $sql_pedimento_well = " AND oc.Pedimento LIKE '%$pedimentoW%' "; 
            }

    $sql_cantidad = " k.ajuste ";
    $gb_instancia = " GROUP BY k.id ";
    if($instancia == 'repremundo')
    {
        $sql_cantidad = " SUM(k.cantidad) ";
        $gb_instancia = " GROUP BY id_articulo, cve_lote, almacen, origen, destino, Contenedor, movimiento ";
    }

    $sql = "SELECT DISTINCT 
    {$fields_reporte_w} 
    #TRUNCATE((k.cantidad/IF(IFNULL(a.num_multiplo, 0) = 0, 1, a.num_multiplo)), 0) AS cajas,
    #IF(ec.Id_Caja IS NOT NULL, CONCAT('(',(SELECT COUNT(*) FROM ts_existenciacajas exc WHERE exc.idy_ubica = k.destino AND k.cve_articulo = exc.cve_articulo AND IFNULL(exc.cve_lote, '') = IFNULL(k.cve_lote, '') AND k.cve_almac = exc.cve_almac AND k.id_TipoMovimiento IN (2, 12, 6)), ')'), '') AS cajas, 
    COUNT(DISTINCT cj.id) AS cajas,
    GROUP_CONCAT(DISTINCT IFNULL(IFNULL(CONCAT(IFNULL(ent_ocompra.num_pedimento, ''), IF(IFNULL(ent_ocompra.Factura, '') != '', ' | ', ''), IFNULL(ent_ocompra.Factura, '')), ''), CONCAT(IFNULL(oc.num_pedimento, ''), IF(IFNULL(oc.Factura, '') != '', ' | ', ''), IFNULL(oc.Factura, ''))) SEPARATOR ' ; ') AS oc,
IFNULL(IF(k.origen LIKE 'TR2%', alm_orig.clave, tr_origen.clave), IFNULL(ent_alm_orig.clave,  al.clave)) AS Almacen_Origen,
IFNULL(IF(k.destino LIKE 'TR2%', alm_dest.clave, tr_destino.clave), IFNULL(ent_alm_dest.clave, al.clave)) AS Almacen_Destino,
k.cve_articulo AS id_articulo, a.des_articulo AS des_articulo, k.cve_lote AS cve_lote, 
                IF(IFNULL(a.Caduca, 'N') = 'S' AND IFNULL(k.cve_lote, '') != '', IFNULL(IF(DATE_FORMAT(lote.Caducidad, '%Y-%m-%d') != DATE_FORMAT('0000-00-00', '%Y-%m-%d'),DATE_FORMAT(lote.Caducidad, '%d-%m-%Y'), DATE_FORMAT(lote.Caducidad, '%d-%m-%Y')),''), '') AS Caducidad, 
                DATE_FORMAT(k.fecha, '%d-%m-%Y | %H:%m:%i') AS fecha, 
                #IF(LEFT(k.origen, 4) != 'Inv_',IFNULL(IFNULL(uo.CodigoCSD, ro.desc_ubicacion), IF(LEFT(k.origen, 3) = 'PT_', k.origen, 'RTM')), k.origen) AS origen, 
                #IF(k.id_TipoMovimiento NOT IN (20,1, 21, 22, 25, 26, 27, 8) AND LEFT(k.origen, 4) != 'Inv_' AND k.origen != 'Inventario Inicial',IFNULL(IFNULL(uo.CodigoCSD, ro.desc_ubicacion), IF(LEFT(k.origen, 3) = 'PT_' OR LEFT(k.origen, 2) = 'OT', k.origen, IF(k.id_TipoMovimiento IN (20,1, 21, 22, 25, 26, 27, 8), k.origen, 'RTM'))), IF(k.id_TipoMovimiento = 8, uo.CodigoCSD,k.origen)) AS origen, 
                CASE 
                WHEN m.nombre = 'Salida' OR UPPER(m.nombre) LIKE '%TRASLADO%' OR m.nombre = 'Traspaso' THEN uo.CodigoCSD
                ELSE k.origen
                END as origen,

                #IFNULL(ch.clave_contenedor,'') AS clave_contenedor, 
                #IFNULL(ch.CveLP, '') AS CveLP, 
                IF(IFNULL(mch.EsCaja, 'N') = 'N', ch.clave_contenedor, '') as Pallet,
                IF(IFNULL(mch.EsCaja, 'N') = 'N', ch.CveLP, '') as LP_Pallet,
                IF(IFNULL(cj.EsCaja, 'N') = 'S', ch_cj.clave_contenedor, '') as Contenedor,
                IF(IFNULL(cj.EsCaja, 'N') = 'S', ch_cj.CveLP, '') as LP_Contenedor,
                IFNULL(uo.CodigoCSD, '') AS bl, 
                #k.cantidad, 
                #IFNULL(k.ajuste, '') as ajuste, 
                IF(m.nombre LIKE 'Salida%', CONCAT('<span style=\'color:red;\'>-',k.cantidad, '</span>'), k.cantidad) as ajuste,

                IFNULL(k.stockinicial, '') as stockinicial, 
                #IFNULL(ud.CodigoCSD, IFNULL(rd.desc_ubicacion, k.destino)) AS destino, 
                IFNULL(ud.CodigoCSD, IFNULL(rd.desc_ubicacion, IF(k.id_TipoMovimiento = 8, CONCAT(k.destino, IF(IFNULL(th_dest.Pick_Num, '') = '', '', ' | '), IFNULL(th_dest.Pick_Num, '')),k.destino))) AS destino, 
                #IF(m.nombre LIKE 'Salida%', CONCAT('<span style=\'color:red;\'>-',{$sql_cantidad}, '</span>'), {$sql_cantidad}) as cantidad,
                {$sql_cantidad} as cantidad,
                m.nombre AS movimiento, 
                k.cve_usuario, al.nombre AS almacen 
                FROM t_cardex k 
                LEFT JOIN c_articulo a ON a.cve_articulo = k.cve_articulo
                LEFT JOIN c_almacenp al ON al.id = k.Cve_Almac

                LEFT JOIN th_pedido th_orig ON th_orig.Fol_folio = k.origen
                LEFT JOIN th_pedido th_dest ON th_dest.Fol_folio = REPLACE(k.destino, '-1', '')
                LEFT JOIN td_pedidoxtarima pxt ON pxt.Fol_folio = th_dest.Fol_folio AND pxt.Cve_articulo = a.cve_articulo AND pxt.cve_lote = k.cve_lote
                LEFT JOIN c_almacenp alm_orig ON alm_orig.id = th_orig.cve_almac
                LEFT JOIN c_almacenp alm_dest ON alm_dest.id = th_dest.statusaurora
        
                LEFT JOIN th_entalmacen ent_orig ON ent_orig.Fol_Folio = k.origen
                LEFT JOIN th_entalmacen ent_dest ON ent_dest.Fol_Folio = k.destino
                LEFT JOIN c_almacenp ent_alm_orig ON ent_alm_orig.clave = ent_orig.cve_almac
                LEFT JOIN c_almacen aog ON aog.cve_almacenp = ent_alm_orig.id
                LEFT JOIN c_almacenp ent_alm_dest ON ent_alm_dest.clave = ent_dest.cve_almac

                LEFT JOIN t_MovCharolas mch ON k.id = mch.id_kardex #OR (k.origen = mch.Origen AND k.destino = mch.Destino AND k.id_TipoMovimiento = mch.Id_TipoMovimiento AND k.cve_usuario = mch.Cve_Usuario)
                LEFT JOIN c_charolas ch ON ch.IDContenedor = IFNULL(mch.ID_Contenedor, pxt.nTarima) 
                LEFT JOIN c_ubicacion uo ON uo.idy_ubica = k.origen #and aog.cve_almac = uo.cve_almac
                LEFT JOIN tubicacionesretencion ro ON ro.cve_ubicacion = k.origen 
                LEFT JOIN c_ubicacion ud ON ud.idy_ubica = k.destino 

                LEFT JOIN c_almacen tr_ori ON tr_ori.cve_almac = uo.cve_almac
                LEFT JOIN c_almacenp tr_origen ON tr_origen.id = tr_ori.cve_almacenp
                
                LEFT JOIN c_almacen tr_des ON tr_des.cve_almac = ud.cve_almac
                LEFT JOIN c_almacenp tr_destino ON tr_destino.id = tr_des.cve_almacenp

                LEFT JOIN tubicacionesretencion rd ON rd.cve_ubicacion = k.destino 
                LEFT JOIN t_tipomovimiento m ON m.id_TipoMovimiento = k.id_TipoMovimiento
                LEFT JOIN c_lotes lote ON lote.cve_articulo = k.cve_articulo AND lote.Lote = k.cve_lote

                LEFT JOIN th_aduana oc ON ent_orig.id_ocompra = oc.num_pedimento AND DATE_FORMAT(oc.fech_pedimento, '%Y-%m-%d') = DATE_FORMAT(k.fecha, '%Y-%m-%d')
                LEFT JOIN td_entalmacen ent_oc ON ent_oc.cve_articulo = k.cve_articulo AND ent_oc.cve_lote = k.cve_lote AND IFNULL(ent_oc.num_orden, 0) != 0
                LEFT JOIN th_aduana ent_ocompra ON ent_ocompra.num_pedimento = ent_oc.num_orden AND DATE_FORMAT(ent_ocompra.fech_pedimento, '%Y-%m-%d') = DATE_FORMAT(k.fecha, '%Y-%m-%d')

                #LEFT JOIN ts_existenciacajas ec ON ec.idy_ubica = k.destino AND k.cve_articulo = ec.cve_articulo AND IFNULL(ec.cve_lote, '') = IFNULL(k.cve_lote, '')  AND k.cve_almac = ec.cve_almac AND k.id_TipoMovimiento IN (2, 12, 6)
                LEFT JOIN t_MovCharolas cj ON cj.id_kardex = k.id AND cj.EsCaja = 'S'
                LEFT JOIN c_charolas ch_cj ON ch_cj.IDContenedor = IFNULL(cj.ID_Contenedor, '') 

                 {$sqlSesionEmpresa} 

                #WHERE k.Cve_Almac = {$almacen} {$SQLArticulo} {$SQLLote} {$SQLCriterio} {$SQLFecha}
                WHERE (k.Cve_Almac IN ({$almacen}, IF(tr_destino.id = tr_origen.id, {$almacen}, IF(tr_destino.id = {$almacen} OR tr_origen.id = {$almacen}, IF(tr_origen.id = {$almacen}, tr_destino.id, tr_origen.id), 0)))) {$SQLArticulo} {$SQLLote} {$SQLCriterio} {$SQLFecha} {$SQLOC} {$SQL_Mov} {$sql_ref_well} {$sql_pedimento_well}

                #GROUP BY id_articulo, cve_lote, almacen, origen, destino, clave_contenedor, CveLP, movimiento
                #GROUP BY ext.ID_Contenedor
                #GROUP BY k.id
                #GROUP BY id_articulo, cve_lote, almacen, origen, destino, clave_contenedor, movimiento
                {$gb_instancia}
                ORDER BY DATE(k.fecha) DESC, k.id DESC
            ";
            //echo $sql;exit;
            //INNER JOIN c_compania ON t_ruta.cve_cia = c_compania.cve_cia
            //WHERE
            //t_ruta.descripcion LIKE '%".$_criterio."%' AND t_ruta.Activo = 1;";
    // hace una llamada previa al procedimiento almacenado Lis_Facturas

    if($kardex_consolidado == 1)
    {
  /*
        $sql = "SELECT kar.Almacen_Origen, kar.Almacen_Destino, kar.id_articulo, kar.des_articulo, 
                       GROUP_CONCAT(DISTINCT kar.movimiento SEPARATOR ', ') AS movimiento, kar.cve_usuario, 
                       SUM(kar.cajas) AS cajas, kar.almacen, 
                       '' AS oc, '' AS cve_lote, '' AS Caducidad, '' AS fecha, kar.origen, '' AS clave_contenedor, 
                       '' AS CveLP, '' AS bl, '' AS ajuste, '' AS stockinicial, kar.destino, '' AS cantidad
                FROM (".$sql.") AS kar
                GROUP BY kar.id_articulo";
*/

        $sql = "SELECT  kar.al_id_or, kar.al_id_dest,kar.fecha, kar.Almacen_Origen, kar.Almacen_Destino, #kar.id_articulo, kar.des_articulo, 
                       GROUP_CONCAT(DISTINCT kar.movimiento SEPARATOR ', ') AS movimiento, kar.cve_usuario, 
                       COUNT(DISTINCT kar.cajas) AS cajas, 
                       kar.almacen, 
                       kar.ntarima, kar.ntarima2,
                       '' AS oc, '' AS cve_lote, '' AS Caducidad, kar.origen, kar.clave_contenedor AS clave_contenedor, 
                       kar.CveLP AS CveLP, kar.bl AS bl, '' AS ajuste, '' AS stockinicial, kar.destino, '' AS cantidad
                FROM (

                    SELECT alp_or.id as al_id_or, alp_dest.id as al_id_dest, k.fecha, alp_or.clave as Almacen_Origen, 
                           alp_dest.clave as Almacen_Destino, a.cve_articulo as id_articulo, a.des_articulo as des_articulo, 
                           'Traslado' AS movimiento, k.cve_usuario, 
                           ec.Id_Caja AS cajas, 
                           al_dest.des_almac as almacen, ch.IDContenedor as ntarima, mch.ID_Contenedor as ntarima2,
                           '' AS oc, '' AS cve_lote, '' AS Caducidad, u_or.CodigoCSD as origen, ch.Clave_Contenedor AS clave_contenedor, 
                           ch.CveLP AS CveLP, u_dest.CodigoCSD AS bl, '' AS ajuste, '' AS stockinicial, u_dest.CodigoCSD as destino, '' AS cantidad
                    FROM t_cardex k
                    LEFT JOIN t_MovCharolas mch ON mch.id_kardex = k.id #AND IFNULL(mch.EsCaja, '') = ''
                    LEFT JOIN c_ubicacion u_or ON u_or.idy_ubica = mch.Origen
                    LEFT JOIN c_ubicacion u_dest ON u_dest.idy_ubica = mch.Destino
                    LEFT JOIN c_almacen al_or ON al_or.cve_almac = u_or.cve_almac
                    LEFT JOIN c_almacen al_dest ON al_dest.cve_almac = u_dest.cve_almac
                    LEFT JOIN c_almacenp alp_or ON alp_or.id = al_or.cve_almacenp
                    LEFT JOIN c_almacenp alp_dest ON alp_dest.id = al_dest.cve_almacenp
                    LEFT JOIN c_articulo a ON a.cve_articulo = k.cve_articulo
                    LEFT JOIN c_charolas ch ON ch.IDContenedor = mch.ID_Contenedor 
                    #LEFT JOIN t_MovCharolas mch_cj ON mch.id_kardex = k.id AND mch_cj.EsCaja = 'S'
                    LEFT JOIN ts_existenciacajas ec ON ec.nTarima = mch.ID_Contenedor #AND ec.Id_Caja = mch_cj.ID_Contenedor
                    WHERE k.id_TipoMovimiento IN (6, 12) AND mch.Id_TipoMovimiento IN (6, 12) AND ec.Id_Caja is not null AND alp_dest.id != alp_or.id
                    AND IFNULL(ch.CveLP, '') != ''
                    AND (alp_or.id= $almacen OR alp_dest.id = $almacen)
                    {$SQLArticulo} {$SQLLote} {$SQLCriterioCajas} {$SQLFecha}
                  ) AS kar
                where kar.al_id_or = $almacen OR kar.al_id_dest = $almacen  #kar.cve_usuario = 'wmsmaster'
                GROUP BY kar.CveLP";
    }

    if (!($res = mysqli_query($conn, $sql))) {
        echo "Falló la preparación: (" . mysqli_error($conn) . ") ";
    }

    $count = mysqli_num_rows($res);
    $sql .= " LIMIT $_page, $limit;";
//echo $sql;
    if (!($res = mysqli_query($conn, $sql))) {
        echo "Falló la preparación: (" . mysqli_error($conn) . ") ";
    }

    if( $count >0 ) {
        $total_pages = ceil($count/$limit);
        //$total_pages = ceil($count/1);
    } else {
        $total_pages = 0;
    } if ($page > $total_pages)
        $page=$total_pages;

    $responce->page = $page;
    $responce->total = $total_pages;
    $responce->records = $count;
    $responce->sql = $sql;
    //$responce->fecha_inicio = $fecha_inicio;
    //$responce->fecha_final = $fecha_final;

    $arr = array();
    $i = 0;
    while ($row = mysqli_fetch_array($res)) {
        $row = array_map('utf8_encode', $row);
        $responce->rows[$i]['id_articulo']=$row['id_articulo'];
        if(isset($_POST['reporte_w']))
            $responce->rows[$i]['cell']=array(
                '',
                $row['fecha'],
                $row['id_articulo'], 
                $row['des_articulo'], 
                $row['cve_lote'], 
                $row['Caducidad'], 
                //$row['clave_contenedor'], 
                //$row['CveLP'], 
                $row['Pallet'], 
                $row['LP_Pallet'], 
                $row['Contenedor'], 
                $row['LP_Contenedor'], 
                $row['movimiento'], 
                $row['clave_origen'], 
                $row['Almacen_Origen'], 
                $row['origen'], 
                $row['Almacen_Destino'], 
                $row['destino'], 
                $row['bl'], 
                $row['stockinicial'], 
                $row['ajuste'], 
                $row['cantidad'], 
                $row['cve_usuario'],
                $row['oc'],
                $row['Pedimento_well'],
                $row['ref_well'],
                $row['cajas'],
                );
        else
            $responce->rows[$i]['cell']=array(
                '',
                $row['fecha'],
                $row['id_articulo'], 
                $row['des_articulo'], 
                $row['cve_lote'], 
                $row['Caducidad'], 
                //$row['clave_contenedor'], 
                //$row['CveLP'], 
                $row['Pallet'], 
                $row['LP_Pallet'], 
                $row['Contenedor'], 
                $row['LP_Contenedor'], 
                $row['movimiento'], 
                $row['clave_origen'], 
                $row['Almacen_Origen'], 
                $row['origen'], 
                $row['Almacen_Destino'], 
                $row['destino'], 
                $row['bl'], 
                $row['stockinicial'], 
                $row['ajuste'], 
                $row['cantidad'], 
                $row['cve_usuario'],
                $row['oc'],
                $row['cajas'],
                );
        $i++;
    }
    echo json_encode($responce);
}



if(isset($_GET) && !empty($_GET)){
    $page = $_GET['page'];
    $rows = $_GET['rows'];
    $search = $_GET['search'];

    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    /*Pedidos listos para embarcar*/
    $sql = "SELECT cve_ruta, descripcion FROM t_ruta WHERE Activo = 1";

    if(!empty($search) && $search != '%20'){
        $sql.= " AND descripcion like '%".$search."%'";
    }

    if (!($res = mysqli_query($conn, $sql))) {
        echo json_encode(array( "error" => "Error al procesar la petición: (" . mysqli_error($conn) . ") "));
    }

    while($row = mysqli_fetch_array($res)){
        extract($row);
        $result [] = array(
            'clave' => $cve_ruta,
            'descripcion' => $descripcion
        );
    };
    mysqli_close();
    header('Content-type: application/json');
    echo json_encode( $result);
}


function getArraySQL($sql){

    $conexion = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

    mysqli_set_charset($conexion, "utf8");

    if(!$result = mysqli_query($conexion, $sql)) 
        echo "Falló la preparación: (" . mysqli_error($conexion) . ") ".$sql;

    $rawdata = array();

    $i = 0;

    while($row = mysqli_fetch_assoc($result))
    {
        $rawdata[$i] = $row;
        $i++;
    }

    mysqli_close($conexion);

    return $rawdata;
}