<?php

namespace PedidosPTLWS;

class PedidosPTLWS {

    function save( $_post ) {
        try {
			$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $sql = "Call SPWS_ActualizaPedidoPTL('"
				. $_post['F_Folio'] . "');";
			$a=(array)$_arr;
			if(!$result = $conn->query($sql))
			{
				$_arr[] = array("success" => false,
								"err" => utf8_encode($result->error));
				$conn->close();
			}
			else
			{
				$_arr[] = array("success" => true,
								"err" => '');
				$conn->close();
            }
			return $_arr;
        }
		catch(Exception $e) {
			$_arr[] = array("success" => false,
								"err" => $e->getMessage());
            return $_arr;
        }
    }

    function updateExist( $_post ) {
        try {
			$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $sql = "Call SPWS_ActualizaExistenciasPTL('"
				. $_post['Articulo'] . "','"
				. $_post['Location'] . "');";
			$a=(array)$_arr;
			if(!$result = $conn->query($sql))
			{
				$_arr[] = array("success" => false,
								"err" => utf8_encode($result->error));
				$conn->close();
			}
			else
			{
				while ($row = $result->fetch_array(MYSQLI_NUM)) {
					if($row[0] === "1"){ $Success=true; }
					else { $Success=false; }
					$_arr[] = array("success" => $Success,
									"err" => $row[1]);
				}
				$conn->close();
            }
			return $_arr;
        }
		catch(Exception $e) {
			$_arr[] = array("success" => false,
								"err" => $e->getMessage());
            return $_arr;
        }
    }

    function getExist( $_post ) {
        try {
			$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $sql = "Call SPWS_DameExistenciasPTL('"
				. $_post['Articulo'] . "','"
				. $_post['Location'] . "');";
			$a=(array)$_arr;
			if(!$result = $conn->query($sql))
			{
				$_arr[] = array("success" => false,
								"err" => utf8_encode($result->error));
				$conn->close();
			}
			else
			{
				while ($row = $result->fetch_array(MYSQLI_NUM)) {
					if($row[0] === "1"){
						$_arr[] = array("success" => true,
									"err" => $row[1]);
					}
					else {
						$_arr[] = array("success" => false,
									"err" => $row[1]);
					}
					
				}
				$conn->close();
            }
			return $_arr;
        }
		catch(Exception $e) {
			$_arr[] = array("success" => false,
								"err" => $e->getMessage());
            return $_arr;
        }
    }
	
    function close( $_post ) {
        try {
			$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $sql = "Call SPWS_TerminaPedidoPTL('"
				. $_post['F_Folio'] . "');";
			$a=(array)$_arr;
			if(!$result = $conn->query($sql))
			{
				$_arr[] = array("success" => false,
								"err" => utf8_encode($result->error));
				$conn->close();
			}
			else
			{
				$_arr[] = array("success" => true,
								"err" => '');
				$conn->close();
            }
			return $_arr;
        }
		catch(Exception $e) {
			$_arr[] = array("success" => false,
								"err" => $e->getMessage());
            return $_arr;
        }
    }

    function set( $_post ) {
        try {
			$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $sql = "Call SPWS_SurteArticuloPTL('"
				. $_post['F_Folio'] . "','"
				. $_post['Articulo'] . "','"
				. $_post['Location'] . "',"
				. $_post['Cantidad'] . ",'"
				. $_post['Fecha'] . "');";
			$a=(array)$_arr;
			if(!$result = $conn->query($sql))
			{
				$_arr[] = array("success" => false,
								"err" => utf8_encode($result->error));
				$conn->close();
			}
			else
			{
				while ($row = $result->fetch_array(MYSQLI_NUM)) {
					if($row[0] === "1"){ $Success=true; }
					else { $Success=false; }
					$_arr[] = array("success" => $Success,
									"err" => $row[1]);
				}
				$conn->close();
            }
			return $_arr;
        }
		catch(Exception $e) {
			$_arr[] = array("success" => false,
								"err" => $e->getMessage());
            return $_arr;
        }
    }

    function update( $_post ) {
        try {
			$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $sql = "Call SPWS_ActualizaPedidoPTL('"
				. $_post['F_Folio'] . "');";
			$a=(array)$_arr;
			if(!$result = $conn->query($sql))
			{
				$_arr[] = array("success" => false,
								"err" => utf8_encode($result->error));
				$conn->close();
			}
			else
			{
				$_arr[] = array("success" => true,
								"err" => '');
				$conn->close();
            }
			return $_arr;
        }
		catch(Exception $e) {
			$_arr[] = array("success" => false,
								"err" => $e->getMessage());
            return $_arr;
        }
    }

    function get($Almacen) {
		ini_set('mbstring.substitute_character', "none");
        $_arr = array();
		$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        if( !$conn ) {
            $_arr[] = array("success" => false,
							"err" => utf8_encode($conn->error));
        }
		mysqli_set_charset($conn,'utf8');
        $sql = "Call SPWS_DamePedidoPTL('" . $Almacen . "');";
        $result = $conn -> query($sql);
        if( !$result ) {
            $_arr[] = array("success" => false,
							"err" => utf8_encode($result->error));
			return $_arr;
        }
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
			if($row[0] === "1"){ $Success=true; }
			else { $Success=false; }
			if($row[2] === ""){ $Folio=""; }
			else { $Folio=$row[2]; }
			$_arr2 = array();
			if($Folio != ""){
				$conn2 = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
				$sql2 = "Call SPWS_DameDetallePedidoPTL('" . $Folio . "');";
				$result2 = $conn2 -> query($sql2);
				if( !$result2 ) {
					$_arr2[] = array("success" => false,
									"err" => utf8_encode($conn2->error));
				}
				else{
					while ($row2 = $result2->fetch_array(MYSQLI_NUM)) {
						$_arr2[] = array(
							"Almacen" => $row2[1],
							"Folio" => utf8_encode($row2[2]),
							"Articulo" => utf8_encode($row2[3]),
							"Cantidad" =>$row2[4],
							"Estado" => $row2[5]);
					}
				}
			}
            $_arr[] = array("success" => $Success,
							"Almacen" => $row[1],
							"Folio" => utf8_encode($row[2]),
							"Fec_Req" => utf8_encode($row[3]),
							"Fec_Carga" => utf8_encode($row[4]),
							"Status" => utf8_encode($row[5]),
							"Secuencia" =>$row[6],
							"Prioridad" => $row[7],
							"Detalle" => $_arr2);
        }
        return $_arr;
    }

    function getPrioridad($Almacen) {
		ini_set('mbstring.substitute_character', "none");
        $_arr = array();
		$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        if( !$conn ) {
            $_arr[] = array("success" => false,
							"err" => utf8_encode($conn->error));
        }
		mysqli_set_charset($conn,'utf8');
        $sql = "Call SPWS_DamePrioridadPedidoPTL('" . $Almacen . "');";
        $result = $conn -> query($sql);
        if( !$result ) {
            $_arr[] = array("success" => false,
							"err" => utf8_encode($result->error));
			return $_arr;
        }
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
			if($row[0] === "1"){ $Success=true; }
			else { $Success=false; }
			if($row[2] === ""){ $Folio=""; }
			else { $Folio=$row[2]; }
			if($row[3] === ""){ $Prioridad=0; }
			else { $Prioridad=$row[3]; }
			$_arr2 = array();
            $_arr[] = array("success" => $Success,
							"Almacen" => $row[1],
							"Folio" => utf8_encode($Folio),
							"Prioridad" => $Prioridad);
        }
        return $_arr;
    }

}
