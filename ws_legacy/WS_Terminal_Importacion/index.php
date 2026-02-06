<?php
//mi commit
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
}

class Sql2JSONApi {
    var $idRuta;
    var $idCliente;
    var $Ruta;
    var $ip_server;
    var $user;
    var $password;
    var $db;
    var $connectinfo;
    var $Vendedor;
    var $IdEmpresa;

    function getAlmacen() {
        $conn = mysqli_connect($this->ip_server, $this->user, $this->password, $this->db);
        if( $conn === false ) {
            return mysqli_error();
        }
        $logged = false;
        $sql = "SELECT
            c_almacen.cve_almac,
            c_almacen.cve_cia,
            c_almacen.des_almac,
            c_almacen.des_direcc,
            c_almacen.ManejaCajas,
            c_almacen.ManejaPiezas,
            c_almacen.MaxXPedido,
            c_almacen.Maneja_Maximos,
            c_almacen.MANCC,
            c_almacen.Compromiso,
            c_almacen.Activo,
            c_compania.cve_cia,
            c_compania.des_cia
            FROM
            c_almacen INNER JOIN c_compania ON c_almacen.cve_cia = c_compania.cve_cia;";
        $result2 = mysqli_query($conn, $sql);
        $_arr = array();
        while ($row2 = mysqli_fetch_array($result2)) {
            $_arr[] = array("cve_almac" => $row2["cve_almac"],
                "cve_cia" => $row2["cve_cia"],
                "des_almac" => $row2["des_almac"],
                "des_direcc" => $row2["des_direcc"],
                "ManejaCajas" => $row2["ManejaCajas"],
                "ManejaPiezas" => $row2["ManejaPiezas"],
                "MaxXPedido" => $row2["MaxXPedido"],
                "Maneja_Maximos" => $row2["Maneja_Maximos"],
                "MANCC" => $row2["MANCC"],
                "Compromiso" => $row2["Compromiso"],
                "Activo" => $row2["Activo"],
                "cve_cia" => $row2["cve_cia"],
                "des_cia" => $row2["des_cia"]);
        }
        return $_arr;
    }

    function setAlmacen($POST) {
        $conn = mysqli_connect($this->ip_server, $this->user, $this->password, $this->db);
        if( $conn === false ) {
            return mysqli_error();
        }
        $logged = false;
        $sql = "Call SPWS_AgregaAlmacen('"
				. $POST['clave'] . "','"
				. $POST['nombre'] . "','"
				. $POST['direccion'] . "','"
				. $POST['distrito'] . "','"
				. $POST['codigopostal'] . "','"
				. $POST['telefono'] . "','"
				. $POST['contacto'] . "','"
				. $POST['correo'] . "',"
				. $POST['Activo'] . ");";
        $result2 = mysqli_query($conn, $sql);
		if($result2){
			$_arr = array(
				"success" => true,
				"err" => "");
		}
		else {
			$_arr = array(
				"success" => false,
				"err" => $conn->error);
			return $_arr;
		}
        return $_arr;
    }

    function setStock($POST) {
		ini_set('mbstring.substitute_character', "none");
        $conn = mysqli_connect($this->ip_server, $this->user, $this->password, $this->db);
        if( $conn === false ) {
            return mysqli_error();
        }
		$BL="";
		if (isset($POST['CVE_BL'])) {
			$BL=$POST['CVE_BL'];
		}
		mysqli_set_charset($conn,'utf8');
        $sql = "Call SPWS_ActualizaStock('" . $POST['cve_almac'] . "','"
				. $POST['cve_articulo'] . "','"
				. $POST['unidadMedida'] . "',"
				. $POST['num_cantidad'] . ",'"
				. $POST['Tipo_Mov'] . "','"
				. $POST['Lote'] . "','"
				. $POST['Caducidad'] . "','"
				. $BL . "',"
				. "1);"; //BanSalida --> 1 - Debe ser ubicación de tipo salida, 0 - Puede ser cualquier ubicación
        $_arr = array();
        $result2 = mysqli_query($conn, $sql);
		if($result2){
			while ($row2 = mysqli_fetch_array($result2)) {
				if($row2[0]==="1"){
					$_arr = array(
						"success" => true,
						"err" => "");
				}
				else{
					$_arr = array(
						"success" => false,
						"err" => utf8_encode($row2[1]));
				}
			}
		}
		else {
			$_arr = array(
				"success" => false,
				"err" => $conn->error);
			return $_arr;
		}
        return $_arr;
    }

    function getStockAlmacen($POST) {
		ini_set('mbstring.substitute_character', "none");
        $conn = mysqli_connect($this->ip_server, $this->user, $this->password, $this->db);
        if( $conn === false ) {
            return mysqli_error();
        }
		mysqli_set_charset($conn,'utf8');
        $logged = false;
        $sql = "Select	A.clave Almacen,V.cve_articulo Articulo,SUM(V.Existencia) Cantidad
				From	V_ExistenciaGral V Join c_almacenp A On V.Cve_Almac=A.Id
				Where	V.Cuarentena=0 And V.Tipo!='area' And A.clave='".$POST['cve_almac']."'
				Group By V.cve_almac,V.cve_articulo;";
        $result2 = mysqli_query($conn, $sql);
        $_arr = array();
        while ($row2 = mysqli_fetch_array($result2)) {
            $_arr["StockAlmac"][] = array("Almacen" => $row2["Almacen"],
                "Articulo" => utf8_encode($row2["Articulo"]),
                "Cantidad" => utf8_encode($row2["Cantidad"]));
        }
        return $_arr;
    }

    function setRutas($POST) {
		ini_set('mbstring.substitute_character', "none");
        $conn = mysqli_connect($this->ip_server, $this->user, $this->password, $this->db);
        if( $conn === false ) {
            return mysqli_error();
        }
		$Almacen="";
		if (!isset($POST['cve_almacenp'])) {
			$Almacen=$POST['Almacen'];
		}
		else{
			$Almacen=$POST['cve_almacenp'];
		}
		$BL="";
		if (isset($POST['CVE_BL'])) {
			$BL=$POST['CVE_BL'];
		}
		mysqli_set_charset($conn,'utf8');
        $sql = "Call SPWS_AgregaRuta('" . $Almacen . "','"
				. $POST['cve_ruta'] . "','"
				. $POST['descripcion'] . "',"
				. $POST['venta_preventa'] . ","
				. $POST['Activo'] . ");";
        $_arr = array();
        $result2 = mysqli_query($conn, $sql);
		if($result2){
			$_arr = array(
				"success" => true,
				"err" => "");
		}
		else {
			$_arr = array(
				"success" => false,
				"err" => $conn->error);
			return $_arr;
		}
        return $_arr;
    }

    function getInventario($POST) { //Inventario Welldex
		ini_set('mbstring.substitute_character', "none");
        $conn = mysqli_connect($this->ip_server, $this->user, $this->password, $this->db);
        if( $conn === false ) {
            return mysqli_error();
        }
		if (!isset($POST['Cte'])) {
			$Cte="";
		}
		else {
			$Cte=$POST['Cte'];
		}
		mysqli_set_charset($conn,'utf8');
        $logged = false;
        $sql = "Call SPAD_DameDatosinventarioWX('"
				. $POST['Almacen'] . "','"
				. $POST['fecha'] . "','" 
				. $POST['Ref_Well'] . "','" 
				. $Cte . "');";
        $result2 = mysqli_query($conn, $sql);
		if(!$result2)
		{
			$_arr[] = array("succes" => false,
							"err" => utf8_encode($conn->error));
			return $_arr;
		}
        $_arr = array();
        while ($row2 = mysqli_fetch_array($result2)) {
			if($row2["Codigo BL"]=="-1")
			{
				$_arr[] = array("success" => false,
								"err" => "No hay datos por regresar");
			}
			else {
				$_arr[] = array("success" => true,
					"CodigoBL" => $row2["Codigo BL"],
					"LicensePlate" => utf8_encode($row2["License Plate"]),
					"Clave" => utf8_encode($row2["Clave"]),
					"Descripcion" => utf8_encode($row2["Descripcion"]),
					"Lote/Serie" => utf8_encode($row2["Lote | Serie"]),
					"Disponible" => utf8_encode($row2["Disponible"]),
					"FechaIngreso" => utf8_encode($row2["Fecha Ingreso"]),
					"Proveedor" => utf8_encode($row2["Proveedor"]),
					"Orden Compra" => utf8_encode($row2["Orden Compra"]),
					"Referencia Well" => utf8_encode($row2["Referencia Well"]),
					"Clave Cliente" => utf8_encode($row2["Clave Cliente"]),
					"Cliente" => utf8_encode($row2["Cliente"]),
					"Pedimento" => utf8_encode($row2["Pedimento"]));
			}
        }
        return $_arr;
    }

    function getProtocolo($POST) {
		ini_set('mbstring.substitute_character', "none");
        $conn = mysqli_connect($this->ip_server, $this->user, $this->password, $this->db);
        if( $conn === false ) {
            return mysqli_error();
        }
		mysqli_set_charset($conn,'utf8');
        $logged = false;
        $sql = "Select SPWS_BuscaProtocolo('"
				. $POST['Cadena'] . "') AS Cve_Prot;";
        $result2 = mysqli_query($conn, $sql);
		if(!$result2)
		{
			$_arr = array("succes" => false,
							"err" => utf8_encode($conn->error));
			return $_arr;
		}
        $_arr = array();
        while ($row2 = mysqli_fetch_array($result2)) {
			if($row2["Cve_Prot"]=="")
			{
				$_arr = array("success" => false,
								"err" => "No existe el protocolo");
			}
			else {
				$_arr = array("success" => true,
					"Cve_Protocolo" => $row2["Cve_Prot"]);
			}
        }
        return $_arr;
    }

    function setUniMed($POST) {
		ini_set('mbstring.substitute_character', "none");
        $conn = mysqli_connect($this->ip_server, $this->user, $this->password, $this->db);
        if( $conn === false ) {
            return mysqli_error();
        }
		mysqli_set_charset($conn,'utf8');
        $logged = false;
        $sql = "Call SPWS_GuardaUnidadMedida('"
				. $POST['Clave'] . "','"
				. $POST['Descrip'] . "');";
        $result2 = mysqli_query($conn, $sql);
		if(!$result2)
		{
			$_arr = array("succes" => false,
							"err" => utf8_encode($conn->error));
			return $_arr;
		}
        $_arr = array();
        while ($row2 = mysqli_fetch_array($result2)) {
			if($row2["Id_Umed"]=="")
			{
				$_arr = array("success" => false,
								"err" => "No se puede guardar la Unidad de Medida");
			}
			else {
				$_arr = array("success" => true,
					"Id_UMed" => $row2["Id_Umed"]);
			}
        }
        return $_arr;
    }

    function getEntradasAlmacen() {
		ini_set('mbstring.substitute_character', "none");
        $conn = mysqli_connect($this->ip_server, $this->user, $this->password, $this->db);
        if( $conn === false ) {
            return mysqli_error();
        }
		mysqli_set_charset($conn,'utf8');
        $logged = false;
        $sql = "SELECT fol_oep, id_ocompra, Empresa
            FROM
            vw_th_entalmacen;";
        $result2 = mysqli_query($conn, $sql);
        $_arr = array();
        while ($row2 = mysqli_fetch_array($result2)) {
            $_arr[] = array("fol_oep" => $row2["fol_oep"],
                "id_ocompra" => utf8_encode($row2["id_ocompra"]),
                "Empresa" => utf8_encode($row2["Empresa"]));
        }
        return $_arr;
    }

    function getCostosEntrada($POST) {
		ini_set('mbstring.substitute_character', "none");
        $conn = mysqli_connect($this->ip_server, $this->user, $this->password, $this->db);
        if( $conn === false ) {
            return mysqli_error();
        }
		mysqli_set_charset($conn,'utf8');
        $logged = false;
        $sql = "SELECT Cve_Articulo,SUM(CantidadRecibida),costoUnitario
            FROM td_entalmacen
			WHERE Fol_Folio=".$POST['Folio']."
			Group By Fol_FOlio,Cve_Articulo,costoUnitario;";
        $result2 = mysqli_query($conn, $sql);
        $_arr = array();
        while ($row2 = mysqli_fetch_array($result2)) {
			$conn2 = sqlsrv_connect($this->ip_server_remote,$this->connectinfo_remote);
			$sqlU = "Update Productos SET VBase=" . $row2["costoUnitario"] . " Where Clave='" . $row2["Cve_Articulo"] . "'";
			$reslt = sqlsrv_query($conn2, $sqlU);
			sqlsrv_close($conn2);
        }
		$arr = array(
			"success" => true
		);
        return $_arr;
    }
}

function valAlmacen($POST) {
    include '../config.php';
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	if (isset($_POST['cve_almac'])){
		$Almac = $POST['cve_almac'];
	}
	else if(isset($_POST['Cve_Almacenp'])){
		$Almac = $POST['Cve_Almacenp'];
	}
	else if(isset($_POST['Almacen'])){
		$Almac = $POST['Almacen'];
	}
    $sql = "SELECT * FROM c_almacenp Where clave='" . $Almac . "';";
    $result = mysqli_query($conn, $sql);
    $_arr = array();
    if (mysqli_num_rows($result)>0) {
        $row = mysqli_fetch_array($result);
		return true;
    }
    return false;
}

function getUser($POST) {
    include '../config.php';
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    $sql = "SELECT * FROM c_usuario Where cve_usuario='" . $POST['user'] . "' And pwd_usuario='".$POST['pwd']."';";
    $result = mysqli_query($conn, $sql);
    $_arr = array();
    if (mysqli_num_rows($result)>0) {
        $row = mysqli_fetch_array($result);
        if( $_POST['pwd'] == $row["pwd_usuario"] ) {
            session_start();
            $_SESSION['id_user'] = $row['id_user'];
            $_SESSION['identifier'] = $row['identifier'];
            $_SESSION['subdomain'] = $row['subdomain'];
            $_SESSION['cve_cia'] = $row['cve_cia'];
            return true;
        }
    }
    return false;
}

function PedidoUPS($POST) {
    include '../config.php';
	foreach ($POST as $arr) {
		$usuario='WSUser';
		$TipoP='P';
		$a = (array)$arr;
		if (strlen($a['orddte'])==8){
			$fecha=SubStr($a['orddte'],0,4)."-".SubStr($a['orddte'],4,2)."-".SubStr($a['orddte'],6,2);
		}
		else {
			$fecha=$a['orddte'];
		}
		$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
		$sql = "Call SPWS_CreaHeaderPedidoUPS('"
											. $a["ordnum"] . "','"
											. $fecha . "','"
											. $a["stpersonname"] . "','"
											. $a["stadrLn1"] . "','"
											. $a["stadrLn2"] . "','"
											. $a["stadrcty"] . "','"
											. $a["stadrstc"] . "','"
											. $a["stctry"] . "','"
											. $a["stship_phnnum"] . "','"
											. $a["spcinstr"] . "','"
											. $a["ws_id"] . "','"
											. $usuario . "','"
											. $TipoP . "');";
		$result = mysqli_query($conn, $sql);
		$_arr = array();
		if( $result === false )
		{
            $arr = array(   
                "success" => false,
                "err" => $result->error
            );
			$conn->close();
			return $arr;
		}
		$conn->close();
		$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
		$sql = "CALL SPWS_CreaDetallePedidoUPS('"
				. $a["ordnum"] . "','"
				. $a["prtnum"] . "',"
				. $a["ordqty"] . ","
				. $a["codamount"] . ","
				. $a["ordlin"] . ");";
		$stmt = $conn->query($sql);
		if( $stmt === false )
		{
			$conn->close();
            $arr = array(
                "success" => false,
                "err" => $stmt->error
            );
			return $arr;
		}
        while ($row2 = mysqli_fetch_array($stmt)) {
			if ($row2["Error"]==1){
				$arr = array(
					"success" => true,
					"err" => 'OK'
				);
			}
			else{
				$arr = array(
					"success"  => false,
					"err" => $row2["Msg"]
				);
			}
        }
		$conn->close();
	}
    return $arr;
}

set_time_limit(0);
$json = file_get_contents('php://input');
$_POST = (empty($HTTP_POST_FILES)) ? (array) json_decode($json) : $HTTP_POST_FILES;

if (isset($_POST) && !empty($_POST)) {
	if (isset($_POST['i_ifd_tmx_ord'])){
		$t_arr = (array)$_POST['i_ifd_tmx_ord']->{'ord_seg'};
		$ret=PedidoUPS($t_arr);
		echo json_encode($ret);
		exit();
	}
    if (isset($_POST['user']) && isset($_POST['pwd'])) {
        if (!getUser($_POST)) {
            $arr = array(   
                "success" => false,
                "err" => "usuario no existe"
            );
            echo json_encode($arr);
			exit();
        }
    } else {
        $arr = array(
            "success" => false,
            "err" => "usuario no existe"
        );
        echo json_encode($arr);
        exit();
    }
	if (isset($_POST['cve_almac'])||isset($_POST['Cve_Almacenp'])||isset($_POST['Almacen'])){
		if (!valAlmacen($_POST)) {
            $arr = array(   
                "success" => false,
                "err" => "Almacen no existe"
            );
            echo json_encode($arr);
			exit();
        }
	}
    switch ($_POST['func']) {
        case "setProds":

			//$Ruta="./CadJSON/JSON_".$_POST['func'].strftime("%Y%m%d_%H%M%S",time()).".txt";
			//$File = fopen($Ruta,"w");
			//fwrite($File,$json);
			//fclose($File);

            include '../app/load.php';
            $app = new \Slim\Slim();
            if( !isset( $_SESSION['id_user'] ) AND !$_SESSION['id_user'] ) {
                exit();
            }
            $a = new \ArticulosWS\ArticulosWS();	
            if( $_POST['action'] == 'add' ) {
                $ret = $a->saveFromAPI($_POST);
                if ( $ret != FALSE) {
                    $arr = array(
                        "success" => true
                    );
                    echo json_encode($arr);
                    exit();
                } else {
					$ret = $a->actualizarArticulos($_POST);
					if ( $ret != FALSE) {
						$arr = array(
							"success" => true
						);
						echo json_encode($arr);
						exit();
					} else {
						$arr = array(
							"success" => false,
							"err" => $ret
						);
					}
                    echo json_encode($arr);
                    exit();
                }
            }
			else{
				$arr = array(
						"success" => false,
						"err" => 'Action no definida'
					);
				echo json_encode($arr);
				exit();
			}
            break;
        case "getProds":
            include '../app/load.php';
            $app = new \Slim\Slim();
            if( !isset( $_SESSION['id_user'] ) AND ! $_SESSION['id_user'] ) {
                exit();
            }
            $c = new \ArticulosWS\ArticulosWS();
			$_POST['fromAPI'] = true;
			$ret = $c->get($_POST);
			echo json_encode($ret);
			exit();
            break;
        case "setProveedores":
		
			//$Ruta="./CadJSON/JSON_".$_POST['func'].strftime("%Y%m%d_%H%M%S",time()).".txt";
			//$File = fopen($Ruta,"w");
			//fwrite($File,$json);
			//fclose($File);

            include '../app/load.php';
            $app = new \Slim\Slim();
            if( !isset( $_SESSION['id_user'] ) AND ! $_SESSION['id_user'] ) {
                exit();
            }
            $c = new \ProveedoresWS\ProveedoresWS();
            if( $_POST['action'] == 'add' ) {
                $_POST['fromAPI'] = true;
                $ret = $c->save($_POST);
                if ($ret == "Guardado") {
                    $arr = array(
                        "success" => true
                    );
                    echo json_encode($arr);
                    exit();
                }
                else {
					$arr = array(
						"success" => false,
						"err" => $ret
					);
					echo json_encode($arr);
					exit();
                }
            }
			else{
				$arr = array(
						"success" => false,
						"err" => 'Action no definida'
					);
				echo json_encode($arr);
				exit();
			}
            break;
        case "getProveedores":
            include '../app/load.php';
            $app = new \Slim\Slim();
            if( !isset( $_SESSION['id_user'] ) AND ! $_SESSION['id_user'] ) {
                exit();
            }
            $c = new \ProveedoresWS\ProveedoresWS();
			$_POST['fromAPI'] = true;
			$ret = $c->get($_POST);
			echo json_encode($ret);
			exit();
            break;
        case "setVehiculos":
            include '../app/load.php';
            $app = new \Slim\Slim();
            if( !isset( $_SESSION['id_user'] ) AND ! $_SESSION['id_user'] ) {
                exit();
            }
            $c = new \TransporteWS\TransporteWS();
            if( $_POST['action'] == 'add' ) {
                $_POST['fromAPI'] = true;
                $ret = $c->save($_POST);
                if ($ret == "Guardado") {
                    $arr = array(
                        "success" => true
                    );
                    echo json_encode($arr);
                    exit();
                }
                else {
					$arr = array(
						"success" => false,
						"err" => $ret
					);
					echo json_encode($arr);
					exit();
                }
            }
			else{
				$arr = array(
						"success" => false,
						"err" => 'Action no definida'
					);
				echo json_encode($arr);
				exit();
			}
            break;
        case "setClientes":

			//$Ruta="./CadJSON/JSON_".$_POST['func'].strftime("%Y%m%d_%H%M%S",time()).".txt";
			//$File = fopen($Ruta,"w");
			//fwrite($File,$json);
			//fclose($File);

            include '../app/load.php';
            $app = new \Slim\Slim();
            if( !isset( $_SESSION['id_user'] ) AND ! $_SESSION['id_user'] ) {
                exit();
            }
            $c = new \ClientesWS\ClientesWS();
            if( $_POST['action'] == 'add' ) {
                $_POST['fromAPI'] = true;
                $ret = $c->save($_POST);
                if ($ret == "Guardado") {
                    $arr = array(
                        "success" => true
                    );
                    echo json_encode($arr);
                    exit();
                }
                else {
					$ret = $c->actualizarClientes($_POST);
					if ($ret == "Actualizado") {
						$arr = array(
							"success" => true
						);
						echo json_encode($arr);
						exit();
					}
					else {
						$arr = array(
							"success" => false,
							"err" => "Error"
						);
						echo json_encode($arr);
						exit();
					}
                }
            }
			else{
				$arr = array(
						"success" => false,
						"err" => 'Action no definida'
					);
				echo json_encode($arr);
				exit();
			}
            break;
        case "updateClientes":
            include '../app/load.php';
            $app = new \Slim\Slim();
            if( !isset( $_SESSION['id_user'] ) AND ! $_SESSION['id_user'] ) {
                exit();
            }
            $c = new \ClientesWS\ClientesWS();
            if( $_POST['action'] == 'add' ) {
                $_POST['fromAPI'] = true;
                $ret = $c->actualizarClientes($_POST);
                if ($ret == "Actualizado") {
                    $arr = array(
                        "success" => true
                    );
                    echo json_encode($arr);
                    exit();
                }
                else {
                    $arr = array(
                        "success" => false,
                        "err" => "Error"
                    );
                    echo json_encode($arr);
                    exit();
                }
            }
			else{
				$arr = array(
						"success" => false,
						"err" => 'Action no definida'
					);
				echo json_encode($arr);
				exit();
			}
            break;
        case "getClientes":
            include '../app/load.php';
            $app = new \Slim\Slim();
            if( !isset( $_SESSION['id_user'] ) AND ! $_SESSION['id_user'] ) {
                exit();
            }
            $c = new \ClientesWS\ClientesWS();
			$_POST['fromAPI'] = true;
			$ret = $c->getCliente($_POST);
			echo json_encode($ret);
			exit();
            break;
        case "setDestinatarios":
            include '../app/load.php';
            $app = new \Slim\Slim();
            if( !isset( $_SESSION['id_user'] ) AND ! $_SESSION['id_user'] ) {
                exit();
            }
            $c = new \ClientesWS\ClientesWS();
            if( $_POST['action'] == 'add' ) {
                $_POST['fromAPI'] = true;
                $ret = $c->saveDest($_POST);
                if ($ret == "Guardado") {
                    $arr = array(
                        "success" => true
                    );
                    echo json_encode($arr);
                    exit();
                }
                else {
					$ret = $c->actualizarDest($_POST);
					if ($ret == "Actualizado") {
						$arr = array(
							"success" => true
						);
						echo json_encode($arr);
						exit();
					}
					else {
						$arr = array(
							"success" => false,
							"err" => "Error"
						);
						echo json_encode($arr);
						exit();
					}
                }
            }
			else{
				$arr = array(
						"success" => false,
						"err" => 'Action no definida'
					);
				echo json_encode($arr);
				exit();
			}
            break;
        case "setPedidos":

			//$Ruta="./CadJSON/JSON_".$_POST['func'].strftime("%Y%m%d_%H%M%S",time()).".txt";
			//$File = fopen($Ruta,"w");
			//fwrite($File,$json);
			//fclose($File);

            if( $_POST['action'] == 'add' ) {
                include '../app/load.php';
                $app = new \Slim\Slim();
                if( !isset( $_SESSION['id_user'] ) AND !$_SESSION['id_user'] ) {
                    exit();
                }
                $p = new \PedidosWS\PedidosWS();
                $p->Fol_folio = $_POST["Fol_folio"];
                $p->__get("Fol_folio");
                $success = true;
                if (!empty($p->data->Fol_folio)) {
                    $success = false;
                }
                if (!$success) {
                    $arr = array(
                        "success" => $success,
                        "err" => "El Pedido ya se ha comenzado a surtir y no es posible modificarlo"
                    );
                    echo json_encode($arr);
                    exit();
                }
                $arrd = array();
                $arrdestinatarios = array();
                foreach ($_POST["arrDetalle"] as $det) {
                    $a = (array) $det;
					if(isset($a["Precio"])){
						$arrd[] = array(
									"codigo" => $a["codigo"],
									"descripcion" => $a["descripcion"],
									"CantPiezas" => $a["CantPiezas"],
									"cve_lote" => $a["cve_lote"],
									"Item" => $a["Item"],
									"Fec_Entrega" => $a["Fec_Entrega"],
									"DOSERIAL" => $a["DOSERIAL"],
									"CPROWNUM" => $a["CPROWNUM"],
									"unidadMedida" => $a["unidadMedida"],
									"Precio" => $a["Precio"]);
					}
					else{
						$arrd[] = array(
									"codigo" => $a["codigo"],
									"descripcion" => $a["descripcion"],
									"CantPiezas" => $a["CantPiezas"],
									"cve_lote" => $a["cve_lote"],
									"Item" => $a["Item"],
									"Fec_Entrega" => $a["Fec_Entrega"],
									"DOSERIAL" => $a["DOSERIAL"],
									"CPROWNUM" => $a["CPROWNUM"],
									"unidadMedida" => $a["unidadMedida"]);
					}
                }
                if( ! isset($_POST["destinatarios"]) or count($_POST["destinatarios"]) < 1 ) {
                    $_POST["destinatarios"] = [];
                }
                $_POST["arrDetalle"] = $arrd;
                $p->save($_POST);
                $arr = array(
                    "success" => true
                );
                echo json_encode($arr);exit();
            }
			else{
				if( $_POST['action'] == 'del' ) {
					include '../app/load.php';
					$app = new \Slim\Slim();
					if( !isset( $_SESSION['id_user'] ) AND !$_SESSION['id_user'] ) {
						exit();
					}
					$p = new \PedidosWS\PedidosWS();
					$p->Fol_folio = $_POST["Fol_folio"];
					$p->__get("Fol_folio");
					$success = true;
					if (!empty($p->data->Fol_folio)) {
						$success = false;
					}
					if (!$success) {
						$arr = array(
							"success" => $success,
							"err" => "El Pedido ya se ha comenzado a surtir y no es posible modificarlo/cancelarlo"
						);
						echo json_encode($arr);
						exit();
					}
					$p->borrarPedido($_POST);
					$arr = array(
						"success" => true
					);
					echo json_encode($arr);exit();
				}
				else{
					$arr = array(
							"success" => false,
							"err" => 'Action no definida'
						);
					echo json_encode($arr);
					exit();
				}
                $p->borrarPedido($_POST);
                $arr = array(
                    "success" => true
                );
                echo json_encode($arr);exit();
			}
            break;
        case "setCross":
            if( $_POST['action'] == 'add' ) {
                include '../app/load.php';
                $app = new \Slim\Slim();
                if( !isset( $_SESSION['id_user'] ) AND !$_SESSION['id_user'] ) {
                    exit();
                }
                $p = new \PedidosCrossDockingWS\PedidosCrossDockingWS();
                $arrd = array();
                foreach ($_POST["crossDetalle"] as $det) {
                    $a = (array) $det;
                    $_POST["Fol_PedidoCon"] = $a["Fact_Madre"];
                    $arrd[] = array("Fol_PedidoCon" => $_POST["Fol_PedidoCon"],
                        "No_OrdComp" => $a["No_OrdComp"],
                        "Fec_OrdCom" => $a["Fec_OrdCom"],
                        "Cve_Articulo" => $a["Cve_Articulo"],
                        "Cant_Pedida" => $a["Cant_Pedida"],
                        "Unid_Empaque" => $a["Unid_Empaque"],
                        "Tot_Cajas" => $a["Tot_Cajas"],
                        "Fact_Madre" => $a["Fact_Madre"],
                        "Cve_Clte" => $a["Cve_Clte"],
                        "Cve_CteProv" => $a["Cve_CteProv"],
                        "Fol_Folio" => $a["Fol_Folio"],
                        "CodB_Cte" => $a["CodB_Cte"],
                        "Cod_PV" => $a["Cod_PV"]);
                }
                $_POST["crossDetalle"] = $arrd;
                $p->Fol_PedidoCon = $_POST["Fol_PedidoCon"];
                /*$p->__get("Fol_PedidoCon");
                $success = true;
                if (!empty($p->data->Fol_PedidoCon)) {
                    $success = false;
                }
                if (!$success) {
                    $arr = array(
                        "success" => $success,
                        "err" => "El Número del Folio ya se Ha Introducido"
                    );
                    echo json_encode($arr);
                    exit();
                }*/
                $p->save($_POST);
                $arr = array(
                    "success" => true
                );
                echo json_encode($arr);
                exit();
            }
			else{
				$arr = array(
						"success" => false,
						"err" => 'Action no definida'
					);
				echo json_encode($arr);
				exit();
			}
            break;
        case "getPedidos":
            if ($_POST['action'] == 'load') {
                include '../app/load.php';
                $app = new \Slim\Slim();
                if( !isset( $_SESSION['id_user'] ) AND !$_SESSION['id_user'] ) {
                    exit();
                }
                $p = new \PedidosWS\PedidosWS();
                $p->Fol_folio = $_POST["Fol_folio"];
				$p->cve_almac = $_POST["cve_almac"];
                $p->__getStatus("Fol_folio");
                $success = true;
                if (empty($p->data->Fol_folio)) {
                    $success = false;
                }
                if (!$success) {
                    $arr = array(
                        "success" => $success,
                        "err" => "El pedido no esta listo para ser enviado"
                    );
                    echo json_encode($arr);
                    exit();
                }
                $arr = array();
                $p->__getDetalle("Fol_folio");
                foreach ($p->data as $nombre => $valor) $arr2[$nombre] = $valor;
                $arr2["detalle"] = $p->dataDetalle;
                $arr["getPedidos"][] = array_merge($arr, $arr2);
                echo json_encode($arr);
            }
			else{
				$arr = array(
						"success" => false,
						"err" => 'Action no definida'
					);
				echo json_encode($arr);
				exit();
			}
            break;
        case "setFacturas":
            if( $_POST['action'] == 'add' ) {
                include '../app/load.php';
                $app = new \Slim\Slim();
                if( !isset( $_SESSION['id_user'] ) AND !$_SESSION['id_user'] ) {
                    exit();
                }
                $p = new \PedidosWS\PedidosWS();
                $arrd = array();
                foreach ($_POST["arrDetalle"] as $det) {
                    $a = (array) $det;
					$arrd[] = array(
								"Item" => $a["Item"],
								"CPRowNum" => $a["CPRowNum"],
								"codigo" => $a["codigo"],
								"Cantidad" => $a["Cantidad"],
								"Fol_Folio" => $a["Fol_Folio"],
								"Referencia" => $a["Referencia"],
								"Lote" => $a["Lote"]);
                }
                $p->save_Factura($_POST);
                $arr = array(
                    "success" => true
                );
                echo json_encode($arr);exit();
            }
			else{
				$arr = array(
						"success" => false,
						"err" => 'Action no definida'
					);
				echo json_encode($arr);
				exit();
			}
            break;
        case "TerminaCorte":
            if ($_POST['action'] == 'load') {
                include '../app/load.php';
                $app = new \Slim\Slim();
                if( !isset( $_SESSION['id_user'] ) AND !$_SESSION['id_user'] ) {
                    exit();
                }
                $p = new \PedidosWS\PedidosWS();
                $p->TerminaCorte();
                $arr = array(
                    "success" => true
                );
                echo json_encode($arr);
            }
			else{
				$arr = array(
						"success" => false,
						"err" => 'Action no definida'
					);
				echo json_encode($arr);
				exit();
			}
            break;
        case "getMovsAlmac":
            if ($_POST['action'] == 'load') {
                include '../app/load.php';
                $app = new \Slim\Slim();
                if( !isset( $_SESSION['id_user'] ) AND !$_SESSION['id_user'] ) {
                    exit();
                }
                $p = new \PedidosWS\PedidosWS();
                $p->Referencia = $_POST["Referencia"];
				$p->cve_almac = $_POST["cve_almac"];
				if($_POST["TipoMov"]=='E'){$p->TipoMov = 1;}
				if($_POST["TipoMov"]=='S'){$p->TipoMov = 8;}
                $p->__getStatus("Referencia");
                $success = true;
                if (empty($p->data->Referencia)) {
                    $success = false;
                }
                if (!$success) {
                    $arr = array(
                        "success" => $success,
                        "err" => "Aun no esta listo el movimiento"
                    );
                    echo json_encode($arr);
                    exit();
                }
                $arr = array();
                $p->__getDetalleMov("Referencia");
                foreach ($p->data as $nombre => $valor) $arr2[$nombre] = $valor;
                $arr2["detalle"] = $p->dataDetalle;
                $arr = array_merge($arr, $arr2);
                echo json_encode($arr);
            }
			else{
				$arr = array(
						"success" => false,
						"err" => 'Action no definida'
					);
				echo json_encode($arr);
				exit();
			}
            break;
        case "setUbicacion":
            if ($_POST['action'] == 'add') {
                include '../app/load.php';
                $app = new \Slim\Slim();
                if( !isset( $_SESSION['id_user'] ) AND !$_SESSION['id_user'] ) {
                    exit();
                }
                $p = new \UbicacionAlmacenaje\UbicacionAlmacenaje();
                $p->save($_POST);
                $arr = array(
                    "success" => true
                );
                echo json_encode($arr);
                exit();
            }
			else{
				$arr = array(
						"success" => false,
						"err" => 'Action no definida'
					);
				echo json_encode($arr);
				exit();
			}
            break;
        case "getUbicacion":
            if ($_POST['action'] == 'load') {
                error_reporting(0);
                include '../config.php';
                $page = $_POST['page']; // get the requested page
                $limit = $_POST['rows']; // get how many rows we want to have into the grid
                $sidx = $_POST['sidx']; // get index row - i.e. user click to sort
                $sord = $_POST['sord']; // get the direction
                //////////////////////////////////////////////se recibe los parametros POST del grid////////////////////////////////////////////////
                $_criterio = $_POST['criterio'];
                ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                if (!empty($_FecIni)) $_FecIni = date("Y-m-d", strtotime($_FecIni));
                if (!empty($_FecFin)) $_FecFin = date("Y-m-d", strtotime($_FecFin));
                $start = $limit*$page - $limit; // do not put $limit*($page - 1)
                if(!$sidx) $sidx =1;
                // se conecta a la base de datos
                $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
                //mysqli_set_charset($conn, 'utf8');
                // prepara la llamada al procedimiento almacenado Lis_Facturas
                $sqlCount = "Select * from c_ubicacion Where Activo = '1';";
                if (!($res = mysqli_query($conn, $sqlCount))) {
                    echo "Falló la preparación: (" . mysqli_error($conn) . ") ";
                }
                $row = mysqli_fetch_array($res);
                $count = $row[0];
                $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
                $_page = 0;
                if (intval($page)>0) $_page = ($page-1)*$limit;
                $sql = "SELECT
						c_ubicacion.idy_ubica,
						c_ubicacion.cve_almac,
						c_ubicacion.cve_pasillo,
						c_ubicacion.cve_rack,
						c_ubicacion.cve_nivel,
						c_ubicacion.num_ancho,
						c_ubicacion.num_largo,
						c_ubicacion.num_alto,
						c_ubicacion.num_volumenDisp,
						c_ubicacion.`Status`,
						c_ubicacion.picking,
						c_ubicacion.Seccion,
						c_ubicacion.Ubicacion,
						c_ubicacion.orden_secuencia,
						c_ubicacion.PesoMaximo,
						c_ubicacion.PesoOcupado,
						c_ubicacion.claverp,
						c_ubicacion.CodigoCSD,
						c_ubicacion.TECNOLOGIA,
						c_ubicacion.Maneja_Cajas,
						c_ubicacion.Maneja_Piezas,
						c_ubicacion.Reabasto,
						c_ubicacion.Activo,
						c_almacen.cve_almac,
						c_almacen.des_almac
						FROM
						c_ubicacion
						INNER JOIN c_almacen ON c_ubicacion.cve_almac = c_almacen.cve_almac Where c_ubicacion.Ubicacion like '%".$_criterio."%' and c_ubicacion.Activo = '1' GROUP BY c_ubicacion.cve_almac LIMIT $_page, $limit;";
                // hace una llamada previa al procedimiento almacenado Lis_Facturas
                if (!($res = mysqli_query($conn, $sql))) {
                    echo "Falló la preparación: (" . mysqli_error($conn) . ") ";
                }
                if( $count >0 ) {
                    $total_pages = ceil($count/$limit);
                } else {
                    $total_pages = 0;
                } if ($page > $total_pages)
                    $page=$total_pages;
                $responce["page"] = $page;
                $responce["total"] = $total_pages;
                $responce["records"] = $count;
                $arr = array();
                $i = 0;
                while ($row = mysqli_fetch_array($res)) {
                    $arr[] = $row;
                    $responce["rows"][$i]['id']=$row['idy_ubica'];
                    $responce["rows"][$i]['cell']=array($row['cve_almac'], $row['idy_ubica'], $row['des_almac'], $row['Ubicacion']);
                    $i++;
                }
                echo json_encode($responce);
                exit();
            }
			else{
				$arr = array(
						"success" => false,
						"err" => 'Action no definida'
					);
				echo json_encode($arr);
				exit();
			}
            break;
        case "getAlmacen":
            if( $_POST['action'] == 'load' ) {
                include '../config.php';
                $t = new Sql2JSONApi();
                $t->ip_server = DB_HOST;
                $t->db = DB_NAME;
                $t->user = DB_USER;
                $t->password = DB_PASSWORD;
                $function = "getAlmacen";
                $ret = $t->$function();
                echo json_encode($ret);
            }
			else{
				$arr = array(
						"success" => false,
						"err" => 'Action no definida'
					);
				echo json_encode($arr);
				exit();
			}
            break;
        case "setAlmacenes":
            if( $_POST['action'] == 'add' ) {
                include '../config.php';
                $t = new Sql2JSONApi();
                $t->ip_server = DB_HOST;
                $t->db = DB_NAME;
                $t->user = DB_USER;
                $t->password = DB_PASSWORD;
                $function = "setAlmacen";
                $ret = $t->$function($_POST);
                echo json_encode($ret);
            }
			else{
				$arr = array(
						"success" => false,
						"err" => 'Action no definida'
					);
				echo json_encode($arr);
				exit();
			}
            break;
        case "getStockAlmacen":
            if( $_POST['action'] == 'load' ) {
                include '../config.php';
                $t = new Sql2JSONApi();
                $t->ip_server = DB_HOST;
                $t->db = DB_NAME;
                $t->user = DB_USER;
                $t->password = DB_PASSWORD;
                $function = "getStockAlmacen";
                $ret = $t->$function($_POST);
                echo json_encode($ret);
            }
			else{
				$arr = array(
						"success" => false,
						"err" => 'Action no definida'
					);
				echo json_encode($arr);
				exit();
			}
            break;
        case "setStock":

			//$Ruta="./CadJSON/JSON_".$_POST['func'].strftime("%Y%m%d_%H%M%S",time()).".txt";
			//$File = fopen($Ruta,"w");
			//fwrite($File,$json);
			//fclose($File);

            if( $_POST['action'] == 'add' ) {
                include '../config.php';
                $t = new Sql2JSONApi();
                $t->ip_server = DB_HOST;
                $t->db = DB_NAME;
                $t->user = DB_USER;
                $t->password = DB_PASSWORD;
                $function = "setStock";
                $ret = $t->$function($_POST);
                echo json_encode($ret);
            }
			else{
				$arr = array(
						"success" => false,
						"err" => 'Action no definida'
					);
				echo json_encode($arr);
				exit();
			}
            break;
        case "setRutas":
            if( $_POST['action'] == 'add' ) {
                include '../config.php';
                $t = new Sql2JSONApi();
                $t->ip_server = DB_HOST;
                $t->db = DB_NAME;
                $t->user = DB_USER;
                $t->password = DB_PASSWORD;
                $function = "setRutas";
                $ret = $t->$function($_POST);
                echo json_encode($ret);
            }
			else{
				$arr = array(
						"success" => false,
						"err" => 'Action no definida'
					);
				echo json_encode($arr);
				exit();
			}
            break;
        case "setUniMed":
			include '../config.php';
			$t = new Sql2JSONApi();
			$t->ip_server = DB_HOST;
			$t->db = DB_NAME;
			$t->user = DB_USER;
			$t->password = DB_PASSWORD;
			$function = "setUniMed";
			$ret = $t->$function($_POST);
			echo json_encode($ret);
            break;
        case "getEntradasAlmacen":
            if( $_POST['action'] == 'load' ) {
                include '../config.php';
                $t = new Sql2JSONApi();
                $t->ip_server = DB_HOST;
                $t->db = DB_NAME;
                $t->user = DB_USER;
                $t->password = DB_PASSWORD;
                $function = "getEntradasAlmacen";
                $ret = $t->$function();
                echo json_encode($ret);
            }
			else{
				$arr = array(
						"success" => false,
						"err" => 'Action no definida'
					);
				echo json_encode($arr);
				exit();
			}
            break;
        case "getCostosEntrada":
            if( $_POST['action'] == 'load' ) {
                include '../config.php';
                $t = new Sql2JSONApi();
                $t->ip_server = DB_HOST;
                $t->db = DB_NAME;
                $t->user = DB_USER;
                $t->password = DB_PASSWORD;
				// Datos remotos
				$t->ip_remote_server = DB_REMOTE_HOST;
				$t->db_remote = DB_REMOTE_NAME;
				$t->user_remote = DB_REMOTE_USER;
				$t->password_remote = DB_REMOTE_PASSWORD;
				$t->connectinfo_remote = array("Database"=>DB_REMOTE_NAME, "UID"=>DB_REMOTE_USER, "PWD"=>DB_REMOTE_PASSWORD, "CharacterSet"=>"UTF-8");
                $function = "getCostosEntrada";
                $ret = $t->$function();
                echo json_encode($ret);
            }
			else{
				$arr = array(
						"success" => false,
						"err" => 'Action no definida'
					);
				echo json_encode($arr);
				exit();
			}
            break;
        case "getInventario": //Inventario Welldex
			if (!isset( $_POST['Almacen'])) {
				$arr = array(   
					"success" => false,
					"err" => "Falta el Almacen"
				);
				echo json_encode($arr);
				exit();
			}
			include '../config.php';
			$t = new Sql2JSONApi();
			$t->ip_server = DB_HOST;
			$t->db = DB_NAME;
			$t->user = DB_USER;
			$t->password = DB_PASSWORD;
			$function = "getInventario";
			$ret = $t->$function($_POST);
			echo json_encode($ret);
            break;
        case "getProtocolo":
			include '../config.php';
			$t = new Sql2JSONApi();
			$t->ip_server = DB_HOST;
			$t->db = DB_NAME;
			$t->user = DB_USER;
			$t->password = DB_PASSWORD;
			$function = "getProtocolo";
			$ret = $t->$function($_POST);
			echo json_encode($ret);
            break;
		case "setEntrada": // OC de Welldex
			include '../app/load.php';
			$app = new \Slim\Slim();
			if( !isset( $_SESSION['id_user'] ) AND !$_SESSION['id_user'] ) {
				exit();
			}
			$p = new \EntradaAlmacenWX\EntradaAlmacenWX();
			$ret=$p->save($_POST);
			echo json_encode($ret);
			exit();
            break;
		case "getEntrada": // Consulta OC de Welldex
			include '../app/load.php';
			$app = new \Slim\Slim();
			if( !isset( $_SESSION['id_user'] ) AND !$_SESSION['id_user'] ) {
				exit();
			}
			if (!isset( $_POST['Almacen'])) {
				$arr = array(   
					"success" => false,
					"err" => "Falta el Almacen"
				);
				echo json_encode($arr);
				exit();
			}
			$p = new \EntradaAlmacenWX\EntradaAlmacenWX();
			$ret=$p->get($_POST);
			echo json_encode($ret);
			exit();
            break;
		case "setEntradasAlmacen":
            if( $_POST['action'] == 'add' ) {

				//$Ruta="./CadJSON/JSON_".$_POST['func'].strftime("%Y%m%d_%H%M%S",time()).".txt";
				//$File = fopen($Ruta,"w");
				//fwrite($File,$json);
				//fclose($File);

                include '../app/load.php';
                $app = new \Slim\Slim();
                if( !isset( $_SESSION['id_user'] ) AND !$_SESSION['id_user'] ) {
                    exit();
                }
                $p = new \EntradaAlmacenWS\EntradaAlmacenWS();
				$arrd = array();
                foreach ($_POST["arrDetalle"] as $det) {
                    $a = (array) $det;
                    $arrd[] = array(
								"codigo" => $a["codigo"],
								"CantPiezas" => $a["CantPiezas"],
								"Lote" => $a["Lote"],
								"Caducidad" => $a["Caducidad"],
								"Temperatura" => $a["Temperatura"],
								"ItemNum" => $a["ItemNum"],
								"Almac" => $a["Almac"],
								"AlmacOri" => $a["AlmacOri"],
								"DOSERIAL" => $a["DOSERIAL"],
								"CPROWNUM" => $a["CPROWNUM"],
								"unidadMedida" => $a["unidadMedida"],
								"Fec_Entrega" => $a["Fec_Entrega"],
								"OC" => $a["OC"]);
                }
                $_POST["arrDetalle"] = $arrd;
                $ret=$p->save($_POST);
				if ($ret == "Guardado") {
                    $arr = array(
                        "success" => true
                    );
                    echo json_encode($arr);
                    exit();
                }
                else {
                    $arr = array(
                        "success" => false,
                        "err" => $ret
                    );
                    echo json_encode($arr);
                    exit();
                }
            }
			else{
				if( $_POST['action'] == 'del' ) {
					include '../app/load.php';
					$app = new \Slim\Slim();
					if( !isset( $_SESSION['id_user'] ) AND !$_SESSION['id_user'] ) {
						exit();
					}
					$p = new \EntradaAlmacenWS\EntradaAlmacenWS();
					$ret=$p->borrarOrden($_POST);
					if ($ret == "Guardado") {
						$arr = array(
							"success" => true
						);
						echo json_encode($arr);
						exit();
					}
					else {
						$arr = array(
							"success" => false,
							"err" => $ret
						);
						echo json_encode($arr);
						exit();
					}
				}
				else{
					$arr = array(
							"success" => false,
							"err" => 'Action no definida'
						);
					echo json_encode($arr);
					exit();
				}
			}
            break;
		case "setOrdenesTrabajo":
            if( $_POST['action'] == 'add' ) {
                include '../app/load.php';
                $app = new \Slim\Slim();
                if( !isset( $_SESSION['id_user'] ) AND !$_SESSION['id_user'] ) {
                    exit();
                }
                $p = new \OrdenesTrabajoWS\OrdenesTrabajoWS();
				$arrd = array();
                foreach ($_POST["arrDetalle"] as $det) {
                    $a = (array) $det;
                    $arrd[] = array("codigo" => $a["codigo"], "loteA" => $a["loteA"], "CantPiezas" => $a["CantPiezas"], "ItemNum" => $a["ItemNum"], "AlmacOri" => $a["AlmacOri"]);
                }
                $_POST["arrDetalle"] = $arrd;
                $ret=$p->save($_POST);
				if ($ret == "Guardado") {
                    $arr = array(
                        "success" => true
                    );
                    echo json_encode($arr);
                    exit();
                }
                else {
                    $arr = array(
                        "success" => false,
                        "err" => "Error ".$ret
                    );
                    echo json_encode($arr);
                    exit();
                }
            }
			else{
				$arr = array(
						"success" => false,
						"err" => 'Action no definida'
					);
				echo json_encode($arr);
				exit();
			}
            break;
		case "setPedidosPTL":
            if( $_POST['action'] == 'update' ) {
                include '../app/load.php';
                $app = new \Slim\Slim();
                if( !isset( $_SESSION['id_user'] ) AND !$_SESSION['id_user'] ) {
                    exit();
                }
                $p = new \PedidosPTLWS\PedidosPTLWS();
				$arrd = array();
                $ret=$p->update($_POST);
				echo json_encode($ret);
				exit();
            }
            if( $_POST['action'] == 'add' ) {
                include '../app/load.php';
                $app = new \Slim\Slim();
                if( !isset( $_SESSION['id_user'] ) AND !$_SESSION['id_user'] ) {
                    exit();
                }
                $p = new \PedidosPTLWS\PedidosPTLWS();
				$arrd = array();
                $ret=$p->set($_POST);
				echo json_encode($ret);
				exit();
            }
            if( $_POST['action'] == 'close' ) {
                include '../app/load.php';
                $app = new \Slim\Slim();
                if( !isset( $_SESSION['id_user'] ) AND !$_SESSION['id_user'] ) {
                    exit();
                }
                $p = new \PedidosPTLWS\PedidosPTLWS();
				$arrd = array();
                $ret=$p->close($_POST);
				echo json_encode($ret);
				exit();
            }
            break;
		case "getOrdenSalida": // Pedidos de Welldex
			include '../app/load.php';
			$app = new \Slim\Slim();
			if( !isset( $_SESSION['id_user'] ) AND !$_SESSION['id_user'] ) {
				exit();
			}
			$p = new \PedidosWS\PedidosWS();
			$arrd = array();
			$ret=$p->get($_POST);
			echo json_encode($ret);
			exit();
            break;
		case "getSalida": // Salidas de Welldex
			include '../app/load.php';
			$app = new \Slim\Slim();
			if( !isset( $_SESSION['id_user'] ) AND !$_SESSION['id_user'] ) {
				exit();
			}
			$p = new \PedidosWS\PedidosWS();
			$arrd = array();
			$ret=$p->getSalida($_POST);
			echo json_encode($ret);
			exit();
            break;
		case "getPedidosPTL":
            if( $_POST['action'] == 'get' ) {
				$Almacen=$_POST['Almacen'];
                include '../app/load.php';
                $app = new \Slim\Slim();
                if( !isset( $_SESSION['id_user'] ) AND !$_SESSION['id_user'] ) {
                    exit();
                }
                $p = new \PedidosPTLWS\PedidosPTLWS();
				$arrd = array();
                $ret=$p->get($Almacen);
				echo json_encode($ret);
				exit();
            }
			else{
				$arr = array(
						"success" => false,
						"err" => 'Action no definida'
					);
				echo json_encode($arr);
				exit();
			}
            break;
		case "getPrioridadPedidosPTL":
            if( $_POST['action'] == 'get' ) {
				$Almacen=$_POST['Almacen'];
                include '../app/load.php';
                $app = new \Slim\Slim();
                if( !isset( $_SESSION['id_user'] ) AND !$_SESSION['id_user'] ) {
                    exit();
                }
                $p = new \PedidosPTLWS\PedidosPTLWS();
				$arrd = array();
                $ret=$p->getPrioridad($Almacen);
				echo json_encode($ret);
				exit();
            }
			else{
				$arr = array(
						"success" => false,
						"err" => 'Action no definida'
					);
				echo json_encode($arr);
				exit();
			}
            break;
		case "setExistenciaPTL":
            if( $_POST['action'] == 'add' ) {
                include '../app/load.php';
                $app = new \Slim\Slim();
                if( !isset( $_SESSION['id_user'] ) AND !$_SESSION['id_user'] ) {
                    exit();
                }
                $p = new \PedidosPTLWS\PedidosPTLWS();
				$arrd = array();
                $ret=$p->updateExist($_POST);
				echo json_encode($ret);
				exit();
            }
			else{
				$arr = array(
						"success" => false,
						"err" => 'Action no definida'
					);
				echo json_encode($arr);
				exit();
			}
            break;
		default:
			$arr = array(
					"success" => false,
					"err" => 'function no definida'
				);
			echo json_encode($arr);
			exit();
		break;
    }
}