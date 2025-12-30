<?php
class Licencia
{
	var $no_licencias;
	var $server;
	var $datab;
	var $user_id;
	var $psswd;
	var $url;
	var $Cad_JSON;
	var $SAP_Usr;
	var $SAP_pswd;
	var $SAP_DB_1;
	var $SAP_DB_2;
	var $SAP_Empresa_1;
	var $SAP_Empresa_2;

	function GuardaLicencia()
	{
		$codifica1 = base64_encode($this->no_licencias);
		$conn =  new mysqli($this->server, $this->user_id, $this->psswd, $this->datab);
		$sql = "CALL SPWS_ActualizaLicenciaMovil ('" . $codifica1 . "');";
		if(!$result = $conn->query($sql))
		{
			$valor = "";
		}
		$conn->close();
	}

	function ObtenLicencia()
	{
		$conn =  new mysqli($this->server, $this->user_id, $this->psswd, $this->datab);
		$sql = "Call SPWS_ObtieneLicenciaMovil();";
		if(!$resultado = $conn->query($sql))
		{
			$valor = 0;
		}
		else
		{
			if ($row = $resultado->fetch_array(MYSQLI_NUM)) {
				$codifica1 = $row[0];
			}
			$valor = base64_decode($codifica1);
		}
		$conn->close();
		return $valor;
	}
}

class JSON2Sql
{
	var $ip_server;
	var $user;
	var $password;
	var $cia;
	var $db;
	var $connectinfo;
	var $arrTbl;
	var $uid;
	var $pswd;
	var $Cant;
	var $SAP_URL;
	var $SessionId;
	var $errorSAP;
	var $Token;
	var $refreshToken;

	function JSONObtenLicencia()
	{
		$_arr = array();
		$_arr["Licencia"][] = array("Licencias" => $this->Cant);
		return $_arr;
	}

	function Sql2JSONValidaUsuario()
	{
		$_arr = array();
		//$conn =  new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$sql = "CALL SPRF_ValidaUsuario ('" . $this->user . "','" . $this->password . "');";
		if(!$result = $conn->query($sql)) 
		{
			$_arr["T_ValidaUsuario"][] = array("ERROR" => -1,"MSG" =>  utf8_encode($conn->error));
		}
		else
		{
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ValidaUsuario"][] = array("ERROR" => $row[0],
									"NOMBRE" => utf8_encode($row[1]),
									"USUARIO" => $row[2],
									"CIA" => $row[3]);
			}
		}
		$conn->close();
		return $_arr;
	}

	function Sql2JSONFirmaUsuario()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "CALL SPWS_FirmaUsuario ('" . $this->user . "','" . $MiSql[$i]->{'IMEIc'} . "','" . $MiSql[$i]->{'Proc'} . "'," . $this->Cant . ");";
			$result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["T_FirmaUsuario"][] = array("ERROR" => -1,
											"Message" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				if($row[0]=="Error")
				{
					$_arr["T_FirmaUsuario"][] = array("Error" => 0,
										"Message" => utf8_encode($row[2]));
				}
				else
				{
					$_arr["T_FirmaUsuario"][] = array("ERROR" => $row[0],
										"Message" => $row[1]);
				}
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

	function Sql2JSONGuardaMovApk()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "CALL SPWS_GuardaMovApk ('"
											. $MiSql[$i]->{'Modulo'} . "','"
											. $this->user . "','"
											. $MiSql[$i]->{'Operacion'} . "','"
											. $MiSql[$i]->{'Observaciones'} . "');";
			$result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["T_ActualizaMovApk"][] = array("ERROR" => -1,
											"Message" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ActualizaMovApk"][] = array("ERROR" => $row[0]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

	function Sql2JSONActualizaSesionUsuario()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "CALL SPWS_ActualizaSesionUsuario ('" . $this->user . "','" . $MiSql[$i]->{'IMEIc'} . "','" . $MiSql[$i]->{'Proc'} . "','" . $MiSql[$i]->{'CveAlmacen'} . "');";
			$result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["T_FirmaUsuario"][] = array("ERROR" => -1,
											"Message" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_FirmaUsuario"][] = array("ERROR" => $row[0],
									"Message" => $row[1]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

	function Sql2JSONCierraSesion()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "CALL SPWS_CierraSesion ('" . $this->user . "','" . $MiSql[$i]->{'IMEIc'} . "');";
			$result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["T_FirmaUsuario"][] = array("ERROR" => -1,
											"Message" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_FirmaUsuario"][] = array("ERROR" => $row[0],
									"Message" => $row[1]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

	function Sql2JSONAlmacenesUsuario()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "CALL SP_DameAlmacenesUsuarios ('" . $this->user . "'," . $this->cia . ");";
			$result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["T_AlmacenesUsuario"][] = array("ERROR" => -1,
											"Message" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_AlmacenesUsuario"][] = array("CLAVE" => $row[0],
									"NOMBRE" => utf8_encode($row[1]),
									"CIA" => $row[2],
									"Id_Almacen" => $row[3]);
			}
			$i++;
			$conn->close();
		}
        return $_arr;
	}

	function Sql2JSONDameAlmacenes()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "CALL SP_DameAlmacenes ('" . $MiSql[$i]->{'Almacen'} . "');";
			$result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["C_Almacenes"][] = array("ERROR" => -1,
											"Message" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Almacenes"][] = array("ERROR" => $row[0],
									"Message" => utf8_encode($row[1]),
									"IdAlmacen" => $row[2],
									"CLAVE" => $row[3],
									"NOMBRE" => utf8_encode($row[4]),
									"CIA" => $row[5]);
			}
			$i++;
			$conn->close();
		}
        return $_arr;
	}

	function Sql2JSONDameReferencias()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "CALL SPWS_DameReferencias ('" . $MiSql[$i]->{'Almacen'} . "');";
			$result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["C_Almacenes"][] = array("Error" => -1,
											"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Almacenes"][] = array("Error" => $row[0],
									"Msg" => utf8_encode($row[1]),
									"Cve_Cia" => $row[2],
									"Id_Ref" => $row[3],
									"Cve_Ref" => $row[4],
									"Des_ref" => utf8_encode($row[5]));
			}
			$i++;
			$conn->close();
		}
        return $_arr;
	}

	function Sql2JSONDamePedidoPendReqMat()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "CALL SPWS_DamePedidoPendReqMat ('"
				. $MiSql[$i]->{'Almacen'} . "','"
				. $MiSql[$i]->{'Usuario'} . "');";
			$result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["T_Pedido"][] = array("Error" => -1,
											"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_Pedido"][] = array("Error" => $row[0],
									"Msg" => "",
									"Folio" => $row[1],
									"Referencia" => $row[2]);
			}
			$i++;
			$conn->close();
		}
        return $_arr;
	}

	function Sql2JSONApartaPedidoReqMat()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "CALL SPWS_ApartaPedidoReqMat ('"
				. $MiSql[$i]->{'Almacen'} . "','"
				. $MiSql[$i]->{'Usuario'} . "','"
				. $MiSql[$i]->{'Ref'} . "');";
			$result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["C_Articulo"][] = array("Error" => -1,
											"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Articulo"][] = array("Error" => $row[0],
									"Msg" => "",
									"Fol_Folio" => $row[1]);
			}
			$i++;
			$conn->close();
		}
        return $_arr;
	}

	function Sql2JSONValidaArticuloReqMat()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "CALL SPWS_ValidaArticuloReqMat ('"
				. $MiSql[$i]->{'Almacen'} . "','"
				. $MiSql[$i]->{'Barras'} . "');";
			$result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["C_Articulo"][] = array("Error" => -1,
											"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Articulo"][] = array("Error" => $row[0],
									"Msg" => $row[1],
									"Nombre" => $row[2],
									"Articulo" => $row[3],
									"Barras" => $row[4],
									"Barras_C" => $row[5],
									"PideLote" => $row[6],
									"PideSerie" => $row[7],
									"PzsXCaja" => $row[8],
									"UM_Base" => $row[9],
									"UM_Eq" => $row[10]);
			}
			$i++;
			$conn->close();
		}
        return $_arr;
	}

	function Sql2JSONGuardaArticuloReqMat()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "CALL SPWS_GuardaArticuloReqMat ('"
				. $MiSql[$i]->{'Folio'} . "','"
				. $MiSql[$i]->{'Articulo'} . "','"
				. $MiSql[$i]->{'CLote'} . "',"
				. $MiSql[$i]->{'NCantidad'} . ");";
			$result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["TD_Pedido"][] = array("Error" => -1,
											"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["TD_Pedido"][] = array("Error" => $row[0],
									"Msg" => $row[1],
									"Folio" => $row[2],
									"Articulo" => $row[3],
									"Descripcion" => $row[4],
									"Lote" => $row[5],
									"Cantidad" => $row[6]);
			}
			$i++;
			$conn->close();
		}
        return $_arr;
	}

	function Sql2JSONDameRelAlmacenesSAP()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "CALL SPWS_CargaAlmacenesSAP();";
			$result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["C_Almacen_SAP"][] = array("ERROR" => -1,
											"Message" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Almacen_SAP"][] = array("ERROR" => $row[0],
									"Message" => "",
									"ID_Almac" => $row[1],
									"Cve_Almac" => $row[2],
									"Cve_Almac_SAP" => $row[3]);
			}
			$i++;
			$conn->close();
		}
        return $_arr;
	}

	function Sql2JSONDamePermisosUsuario()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "CALL SPWS_DamePermisosUsuario ('" . $MiSql[$i]->{'Almacen'} . "','" . $this->user . "');";
			$_arr = array();
			if(!$result = $conn->query($sql))
			{
				$_arr["T_PermisosUsuario"][] = array("Error" => -1,
											"msgError" => utf8_encode($conn->error));
			}
			else
			{
				while ($row = $result->fetch_array(MYSQLI_NUM)) {
					$_arr["T_PermisosUsuario"][] = array("Error" => $row[0],
										"msgError" => $row[1],
										"ID_PERMISO" => $row[2],
										"user" => $row[3],
										"OrdenMenu" => $row[4],
										"Id_Tipo" => $row[5]);
				}
				$i++;
			}
			$conn->close();
        }
		return $_arr;
	}

	function Sql2JSONTienePermiso()
	{
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "CALL SPWS_TienePermisos ('" . $this->user . "'," . $MiSql[$i]->{'IdPermiso'} . ");";
			$_arr = array();
			if(!$result = $conn->query($sql))
			{
				$_arr["T_PermisosUsuario"][] = array("Error" => -1,
											"msgError" => utf8_encode($conn->error));
			}
			else
			{
				while ($row = $result->fetch_array(MYSQLI_NUM)) {
					$_arr["T_PermisoUsuario"][] = array("Error" => $row[0],
										"msgError" => $row[1]);
				}
				$i++;
			}
			$conn->close();
        }
		return $_arr;
	}

    function Sql2JSONCargaUM()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CargaUnidadesMedida ();";
            $result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["C_UniMed"][] = array("Error" => -1,
											"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_UniMed"][] = array("Error" => $row[0],
									"Msg" => $row[1],
									"id_umed" => $row[2],
									"cve_umed" => $row[3],
									"des_umed" => $row[4]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameDatosWS()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "CALL SPWS_DameDatosWS ();";
			$result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["C_Datos_WS"][] = array("Error" => -1,
											"Message" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				if($row[0]=="Error")
				{
					$_arr["C_Datos_WS"][] = array("Error" => 0,
										"Message" => utf8_encode($row[2]));
				}
				else
				{
					$_arr["C_Datos_WS"][] = array("Error" => $row[0],
										"Url" => $row[1],
										"Servicio" => $row[2],
										"User" => $row[3],
										"Pswd" => $row[4]);
				}
			}
			$i++;
			$conn->close();
		}
        return $_arr;
	}

	function Sql2JSONDameDatosServicioWS()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "CALL SPWS_DameDatosServicioWS ('"
					. $MiSql[$i]->{'Serv'} . "');";
			$result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["C_Datos_WS"][] = array("Error" => -1,
											"Message" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Almacenes"][] = array("Error" => $row[0],
									"Url" => $row[1],
									"Servicio" => $row[2],
									"User" => $row[3],
									"Pswd" => $row[4],
									"Puerto" => $row[5]);
			}
			$i++;
			$conn->close();
		}
        return $_arr;
	}

	function Sql2JSONDameDatosWSSAP()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "CALL SPWS_DameDatosWSSAP ('"
					. $MiSql[$i]->{'Folio'} . "');";
			$result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["C_Datos_WS"][] = array("Error" => -1,
											"Message" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Almacenes"][] = array("Error" => $row[0],
									"Url" => $row[1],
									"User" => $row[2],
									"Pswd" => $row[3],
									"BaseD" => $row[4],
									"Empresa" => $row[5]);
			}
			$i++;
			$conn->close();
		}
        return $_arr;
	}

	function Sql2JSONDameDatosWSSAPT()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "CALL SPWS_DameDatosWSSAPT ("
					. $MiSql[$i]->{'CveProv'} . ");";
			$result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["C_Datos_WS"][] = array("Error" => -1,
											"Message" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Almacenes"][] = array("Error" => $row[0],
									"Url" => $row[1],
									"User" => $row[2],
									"Pswd" => $row[3],
									"BaseD" => $row[4],
									"Empresa" => $row[5]);
			}
			$i++;
			$conn->close();
		}
        return $_arr;
	}

    function Sql2JSONDameExistenciasProducto()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameExistenciasProducto ('"
					. $MiSql[$i]->{'CAlmac'} . "','"
					. $MiSql[$i]->{'Clave'} . "','"
					. $MiSql[$i]->{'Lote'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["C_ExistProd"][] = array("Error" => -1,
											"Message" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_ExistProd"][] = array("Error" => $row[0],
									"cve_articulo" => $row[1],
									"Cve_Lote" => utf8_encode($row[2]),
									"Existencia" => $row[3],
									"UniMed" => $row[4],
									"Cve_Almac" => $row[5],
									"Fecha" => $row[6]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameTiposCaracteristicas()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameTiposCaracteristicas ('"
					. $MiSql[$i]->{'Almacen'} . "','"
					. $MiSql[$i]->{'Articulo'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["C_TiposCaracteristicas"][] = array("Error" => -1,
											"Message" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_TiposCaracteristicas"][] = array("Error" => $row[0],
									"Message" => "",
									"Id_Tipo_car" => $row[1],
									"TipoCar_Desc" => utf8_encode($row[2]));
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameCaracteristicas()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameCaracteristicas ('"
					. $MiSql[$i]->{'Almacen'} . "','"
					. $MiSql[$i]->{'Articulo'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["C_Caracteristicas"][] = array("Error" => -1,
											"Message" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Caracteristicas"][] = array("Error" => $row[0],
									"Message" => "",
									"Id_Carac" => $row[1],
									"Cve_Carac" => $row[2],
									"Des_Carac" => utf8_encode($row[3]),
									"Id_Tipo_car" => $row[4]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONCargaMotivos()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CargaMotivos ('" . $MiSql[$i]->{'Tipo'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["C_Motivo"][] = array("Error" => -1,
											"Message" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Motivo"][] = array("Error" => $row[0],
									"id" => $row[1],
									"Tipo_Cat" => $row[2],
									"Des_Motivo" => $row[3]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONCargaMonedas()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameMonedas ();";
            $result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["C_Moneda"][] = array("Error" => -1,
											"Message" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Moneda"][] = array("Error" => $row[0],
									"Id_Moneda" => $row[1],
									"Cve_Moneda" => $row[2],
									"Des_Moneda" => $row[3],
									"Ban_M_Nacional" => $row[4]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONCargaTransportes()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CargaTransportes ();";
            $result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["T_Transporte"][] = array("Error" => -1,
											"Message" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_Transporte"][] = array("Error" => $row[0],
									"Id_Trans" => $row[1],
									"Cve_Trans" => $row[2],
									"Descripcion" => $row[3],
									"Placas" => $row[4],
									"Cve_TipoT" => $row[5],
									"Tipo_Trans" => $row[6],
									"Alto" => $row[7],
									"Ancho" => $row[8],
									"Fondo" => $row[9],
									"peso" => $row[10]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONCargaRutas()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CargaRutas ('" . $MiSql[$i]->{'Almacen'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["C_Ruta"][] = array("Error" => -1,
										"Message" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Ruta"][] = array("Error" => $row[0],
									"ID_Ruta" => $row[1],
									"cve_ruta" => $row[2],
									"descripcion" => $row[3]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONCargaUbicacionesImpresion()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CargaUbicacionesImpresion ('"
				. $MiSql[$i]->{'Almacen'} . "','"
				. $MiSql[$i]->{'TipoU'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["T_UbicacionesRev"][] = array("Error" => -1,
										"Message" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_UbicacionesRev"][] = array("Error" => $row[0],
									"Cve_Ubicacion" => $row[1],
									"Desc_Ubicacion" => $row[2]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONCargaUbicAlmacImpresion()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CargaUbicAlmacImpresion ('"
				. $MiSql[$i]->{'Almacen'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["T_UbicDisponibles"][] = array("Error" => -1,
										"Message" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_UbicDisponibles"][] = array("Error" => $row[0],
									"Idy_Ubica" => $row[1],
									"Cve_Ubicacion" => $row[2],
									"Pasillo" => $row[3],
									"Rack" => $row[4],
									"Nivel" => $row[5],
									"Seccion" => $row[6],
									"Ubicacion" => $row[7]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONCargaUbicLibres()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CargaUbicLibres ('"
				. $MiSql[$i]->{'Almacen'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["T_UbicDisponibles"][] = array("Error" => -1,
										"Message" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_UbicDisponibles"][] = array("Error" => $row[0],
									"Idy_Ubica" => $row[1],
									"Cve_Ubicacion" => $row[2],
									"Pasillo" => $row[3],
									"Rack" => $row[4],
									"Nivel" => $row[5],
									"Seccion" => $row[6],
									"Ubicacion" => $row[7]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameSupervisoresEnt()
	{
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "CALL SPWS_DameSupervisoresEnt ();";
			$_arr = array();
			if(!$result = $conn->query($sql))
			{
				$_arr["C_Supervisor"][] = array("Error" => -1,
										"Message" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			else
			{
				while ($row = $result->fetch_array(MYSQLI_NUM)) {
					$_arr["C_Supervisor"][] = array("Usuario" => utf8_encode($row[0]),
										"Nombre" => utf8_encode($row[1]),
										"Pswd" => utf8_encode($row[2]));
				}
				$i++;
			}
			$conn->close();
        }
		return $_arr;
	}

	function Sql2JSONTieneEntradaPendiente()
	{
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "CALL SPWS_TieneEntradaPendiente ('" . $this->user . "','" . $MiSql[$i]->{'Cve_Almac'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_EntradaPendiente"][] = array("ERROR" => -1,
										"Message" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_EntradaPendiente"][] = array("ERROR" => $row[0],
									"Fol_Folio" => $row[1],
									"fol_oep" => $row[2],
									"Proveedor" => $row[3],
									"Cve_Prov" => $row[4],
									"Ubicacion" => $row[5],
									"Id_Contenedor" => $row[6],
									"Cve_Contenedor" => $row[7],
									"Contenedor" => $row[8],
									"TipoE" => $row[9],
									"BanCrossD" => $row[10]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
	}

    function Sql2JSONDameEntradasPendientes()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "CALL SPWS_DameEntradasPendientes ('"
                . $MiSql[$i]->{'Cve_Almac'} . "','"
                . $this->user . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_OCPendientes"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_OCPendientes"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Fol_Folio" => $row[2],
									"FOLIO" => $row[3],
									"ID_Proveedor" => $row[4],
									"Empresa" => $row[5]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONApartaFolioEntrada()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "CALL SPWS_ApartaFolioEntrada ('"
                . $this->user . "','"
                . $MiSql[$i]->{'Folio'} . "','"
                . $MiSql[$i]->{'Cve_Almac'} . "','"
                . $MiSql[$i]->{'Cve_Ubica'} . "','"
                . $MiSql[$i]->{'Fecha'} . "','"
                . $MiSql[$i]->{'BanCD'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ApartaFolioEnt"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ApartaFolioEnt"][] = array("ERROR" => $row[0],
									"MSG" => "No existe la Entrada o no esta pendiente",
									"Fol_Folio" => $row[1],
									"Tipo_Ent" => $row[2]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONAgregaFacturaProvEnt()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "CALL SPWS_AgregaFacturaProvEnt ("
                . $MiSql[$i]->{'Folio'} . ",'"
                . $MiSql[$i]->{'NFactura'} . "','"
                . $MiSql[$i]->{'FFactura'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_GuardaFacturaEnt"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_GuardaFacturaEnt"][] = array("ERROR" => $row[0],
									"MSG" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameFacturaProvEnt()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "CALL SPWS_DameFacturaProvEnt ("
                . $MiSql[$i]->{'Folio'} . ",'"
                . $MiSql[$i]->{'Articulo'} . "','"
                . $MiSql[$i]->{'CLote'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_GuardaFacturaEnt"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_GuardaFacturaEnt"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"FactProveedor" => $row[2],
									"Fec_FactProveedor" => $row[3],
									"Item" => $row[4],
									"Ref_Docto" => $row[5]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONValidaArticuloEntrada()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "CALL SPWS_ValidaArticuloEntrada ("
                . $MiSql[$i]->{'folio'} . ",'"
                . $MiSql[$i]->{'barras'} . "','"
                . $MiSql[$i]->{'bodega'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["TMP_Entrada"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["TMP_Entrada"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"NOMBRE" => $row[2],
									"BARRAS_C" => $row[3],
									"CAJAS" => $row[4],
									"ARTICULO" => $row[5],
									"BARRAS" => $row[6],
									"PideLote" => $row[7],
									"Lote" => $row[8],
									"FechaCad" => $row[9],
									"CodIngresado" => $row[10],
									"PideFecha" => $row[11],
									"PideSerie" => $row[12],
									"BARRAS_T" => $row[13],
									"TipoCap" => $row[14],
									"PzsXCaja" => $row[15],
									"UM_Base" => $row[16],
									"UM_E" => $row[17],
									"IDContenedor" => $row[18],
									"Clave_Contenedor" => $row[19],
									"CveLP" => $row[20],
									"Item" => $row[21]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONValidaDatosQREntrada()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "CALL SPAD_ValidaDatosQREntrada ("
                . $MiSql[$i]->{'Folio'} . ",'"
                . $MiSql[$i]->{'Articulo'} . "','"
                . $MiSql[$i]->{'CLote'} . "',"
                . $MiSql[$i]->{'CantidadA'} . ",'"
                . $MiSql[$i]->{'CodigoLP'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["TMP_Entrada"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["TMP_Entrada"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"NOMBRE" => $row[2],
									"BARRAS_C" => $row[3],
									"CAJAS" => $row[4],
									"ARTICULO" => $row[5],
									"BARRAS" => $row[6],
									"PideLote" => $row[7],
									"Lote" => $row[8],
									"FechaCad" => $row[9],
									"PideFecha" => $row[10],
									"PideSerie" => $row[11],
									"BARRAS_T" => $row[12],
									"TipoCap" => $row[13],
									"PzsXCaja" => $row[14],
									"UM_Base" => $row[15],
									"UM_E" => $row[16],
									"IDContenedor" => $row[17],
									"Clave_Contenedor" => $row[18],
									"CveLP" => $row[19],
									"Item" => $row[20]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONValidaTarimaAduana()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "CALL SPWS_ValidaTarimaAduana ("
                . $MiSql[$i]->{'Folio'} . ",'"
                . $MiSql[$i]->{'Codigo'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ContContenedor"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ContContenedor"][] = array("Error" => $row[0],
									"Msg" => $row[1],
									"IDContenedor" => $row[2],
									"Clave_Contenedor" => $row[3],
									"CveLP" => $row[4],
									"Cve_Articulo" => $row[5],
									"Des_Articulo" => $row[6],
									"Cve_Lote" => $row[7],
									"Cantidad" => $row[8],
									"Registros" => $row[9]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameLotesArticulo()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameLotesArticulo ('"
                . $MiSql[$i]->{'articulo'} . "','"
                . $MiSql[$i]->{'Almacen'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["C_Lote"][] = array("ERROR" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Lote"][] = array("ERROR" => $row[0],
									"Cve_Articulo" => $row[1],
									"LOTE" => $row[2],
									"CADUCIDAD" => $row[3],
									"NOMBRE" => $row[4],
									"Caduca" => $row[5]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameLotesArticuloEsp()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameLotesArticuloEsp ('"
                . $MiSql[$i]->{'articulo'} . "','"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Bandera'} . "','"
                . $MiSql[$i]->{'valor'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["C_Lote"][] = array("ERROR" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Lote"][] = array("ERROR" => $row[0],
									"Cve_Articulo" => $row[1],
									"LOTE" => $row[2],
									"CADUCIDAD" => $row[3],
									"NOMBRE" => $row[4],
									"Caduca" => $row[5]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameLotesArticuloSurtido()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameLotesArticuloSurtido ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'ISufijo'} . ","
                . $MiSql[$i]->{'Ubicacion'} . ",'"
                . $MiSql[$i]->{'Articulo'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["C_Lote"][] = array("ERROR" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Lote"][] = array("ERROR" => $row[0],
									"Cve_Articulo" => $row[1],
									"LOTE" => $row[2],
									"CADUCIDAD" => $row[3],
									"NOMBRE" => $row[4],
									"Caduca" => $row[5]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameSeriesArticulo()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameSeriesArticulo ('"
                . $MiSql[$i]->{'articulo'} . "','"
                . $MiSql[$i]->{'Almacen'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["C_Lote"][] = array("ERROR" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Lote"][] = array("ERROR" => $row[0],
									"Cve_Articulo" => $row[1],
									"Serie" => $row[2],
									"Fec_Ingreso" => $row[3],
									"NOMBRE" => $row[4]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameSeriesArticuloEsp()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameSeriesArticuloEsp ('"
                . $MiSql[$i]->{'articulo'} . "','"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Bandera'} . "','"
                . $MiSql[$i]->{'valor'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["C_Lote"][] = array("ERROR" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Lote"][] = array("ERROR" => $row[0],
									"Cve_Articulo" => $row[1],
									"Serie" => $row[2],
									"Fec_Ingreso" => $row[3],
									"NOMBRE" => $row[4]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONBuscaSerieArticulo()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_BuscaSerieArticulo ('"
                . $MiSql[$i]->{'Articulo'} . "','"
                . $MiSql[$i]->{'Serie'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["C_Lote"][] = array("ERROR" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Lote"][] = array("ERROR" => $row[0],
									"Serie" => $row[1],
									"Fec_Ingreso" => $row[2]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONAgregaLote()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_AgregaLote ('"
                . $MiSql[$i]->{'articulo'} . "','"
                . $MiSql[$i]->{'lote'} . "','"
                . $MiSql[$i]->{'caducidad'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["C_Lote"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Lote"][] = array("ERROR" => $row[0],
									"MSG" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONAgregaSerie()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_AgregaSerie ('"
                . $MiSql[$i]->{'Articulo'} . "','"
                . $MiSql[$i]->{'Serie'} . "','"
                . $MiSql[$i]->{'Activo'} . "','"
                . $MiSql[$i]->{'Fec_Ing'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["C_Serie"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Serie"][] = array("ERROR" => $row[0],
									"MSG" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONValidaLoteArticuloEntrada()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "CALL SPWS_ValidaLoteArticuloEntrada ("
                . $MiSql[$i]->{'folio'} . ",'"
                . $MiSql[$i]->{'Articulo'} . "','"
                . $MiSql[$i]->{'ILote'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["TMP_LoteEntrada"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["TMP_LoteEntrada"][] = array("ERROR" => $row[0],
									"MSG" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONValidaCantArticuloEntrada()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "CALL SPWS_ValidaCantArticuloEntrada ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Articulo'} . "',"
                . $MiSql[$i]->{'Cantidad'} . ",'"
                . $MiSql[$i]->{'Folio'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["TMP_LoteEntrada"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["TMP_LoteEntrada"][] = array("Error" => $row[0],
									"Msg" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameBufferEntrada()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameBufferEntrada ('" . $MiSql[$i]->{'Cve_Almac'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_UbicacionesRec"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_UbicacionesRec"][] = array("Muelle" => $row[0],
									"cve_ubicacion" => $row[1],
									"Descripcion" => $row[2]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameProductosEntrada()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameProductosEntrada ("
                . $MiSql[$i]->{'Fol_Folio'} . ","
				. $MiSql[$i]->{'BanRec'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ProdsEntrada"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ProdsEntrada"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"cve_articulo" => $row[2],
									"des_articulo" => $row[3],
									"Lote" => $row[4],
									"CantidadRecibida" => $row[5],
									"PzsXCaja" => $row[6],
									"TotP" => $row[7]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameContenedoresEntrada()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameContenedoresEntrada ("
                . $MiSql[$i]->{'Fol_Folio'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ContenedoresEntrada"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ContenedoresEntrada"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"IdTarima" => $row[2],
									"CveTarima" => $row[3],
									"LPTarima" => $row[4],
									"DesTarima" => $row[5],
									"cve_articulo" => $row[6],
									"des_articulo" => $row[7],
									"Lote" => $row[8],
									"CantidadRecibida" => $row[9]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONValidaCharolaEntrada()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ValidaCharolaEntrada ('" . $MiSql[$i]->{'Cve_Almac'} . "','"
                . $MiSql[$i]->{'Id_Cont'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_Contenedor"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_Contenedor"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"IdContenedor" => $row[2],
									"DescCont" => $row[3],
									"Cve_Cont" => $row[4]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameContenedoresDisponibles()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameContenedoresDisponibles ('"
                . $MiSql[$i]->{'Almacen'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_Contenedor"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_Contenedor"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"IdContenedor" => $row[2],
									"DescCont" => $row[4],
									"Cve_Cont" => $row[3],
									"ALto_Cont" => $row[5],
									"Ancho_Cont" => $row[6],
									"Fondo_Cont" => $row[7],
									"CapVol_Cont" => $row[8],
									"PesoM_Cont" => $row[9],
									"BanPermanente" => $row[10],
									"Tipo_Cont" => $row[11],
									"Generico" => $row[12],
									"CodLP" => $row[13]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameContenedoresAlmacen()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameContenedoresAlmacen ('"
                . $MiSql[$i]->{'Almacen'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["C_Contenedor"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Contenedor"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"IdContenedor" => $row[2],
									"Clave_Contenedor" => $row[3],
									"Descripcion" => $row[4],
									"Alto" => $row[5],
									"Ancho" => $row[6],
									"Fondo" => $row[7],
									"CapaVol" => $row[8],
									"PesoMax" => $row[9],
									"CveLP" => $row[10],
									"Cve_Ubica" => $row[11],
									"CveBL" => $row[12]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameTiposContenedores()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameTiposContenedores ();";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_Contenedor"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_Contenedor"][] = array("ERROR" => $row[0],
									"Cve_TipoCont" => $row[1],
									"Des_TipoCont" => $row[2],
									"Ancho" => $row[3],
									"Largo" => $row[4],
									"ALto" => $row[5],
									"Peso" => $row[6],
									"CapVol" => $row[7],
									"PesoMax" => $row[8]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameLPSinAsignar()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameLPSinAsignar ();";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_LPLibres"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_LPLibres"][] = array("ERROR" => $row[0],
									"IdContenedor" => $row[1],
									"clave_contenedor" => $row[2],
									"CveLP" => $row[3]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONAgregaContenedor()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_AgregaContenedor ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Contenedor'} . "','"
                . $MiSql[$i]->{'Descripcion'} . "','"
                . $MiSql[$i]->{'Tipo'} . "',"
                . $MiSql[$i]->{'Ancho'} . ","
                . $MiSql[$i]->{'Largo'} . ","
                . $MiSql[$i]->{'Alto'} . ","
                . $MiSql[$i]->{'Peso_Max'} . ","
                . $MiSql[$i]->{'BanPer'} . ",'"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'CapVolCont'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ContenedorNew"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ContenedorNew"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"IdContenedor" => $row[2],
									"DescCont" => $row[4],
									"Cve_Cont" => $row[3],
									"ALto_Cont" => $row[5],
									"Ancho_Cont" => $row[6],
									"Fondo_Cont" => $row[7],
									"PesoM_Cont" => $row[8],
									"BanPermanente" => $row[9],
									"Tipo_Cont" => $row[10],
									"CveLP" => $row[11]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONAgregaContenedorLP()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_AgregaContenedorLP ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'LP'} . "','"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'CantPT'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ContenedorNew"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ContenedorNew"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"IdContenedor" => $row[2],
									"DescCont" => $row[4],
									"Cve_Cont" => $row[3],
									"ALto_Cont" => $row[5],
									"Ancho_Cont" => $row[6],
									"Fondo_Cont" => $row[7],
									"PesoM_Cont" => $row[8],
									"BanPermanente" => $row[9],
									"Tipo_Cont" => $row[10],
									"CveLP" => $row[11]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONAsignaLPLibre()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_AsignaLPLibre ('"
                . $MiSql[$i]->{'Almacen'} . "',"
                . $MiSql[$i]->{'IdCont'} . ","
                . $MiSql[$i]->{'IdLP'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ContenedorNew"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ContenedorNew"][] = array("ERROR" => $row[0],
									"MSG" => utf8_encode($row[1]),
									"IdContenedor" => $row[2],
									"DescCont" => utf8_encode($row[4]),
									"Cve_Cont" => $row[3],
									"ALto_Cont" => $row[5],
									"Ancho_Cont" => $row[6],
									"Fondo_Cont" => $row[7],
									"PesoM_Cont" => $row[8],
									"BanPermanente" => $row[9],
									"Tipo_Cont" => $row[10],
									"CveLP" => $row[11]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameProdSinPaletizarEntrada()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameProdSinPaletizarEntrada ('"
                . $MiSql[$i]->{'Almacen'} . "',"
                . $MiSql[$i]->{'Folio'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CajasEnt_SinPalet"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CajasEnt_SinPalet"][] = array("ERROR" => $row[0],
									"MSG" => utf8_encode($row[1]),
									"Fol_Folio" => $row[2],
									"Cve_Articulo" => utf8_encode($row[3]),
									"Cve_Lote" => utf8_encode($row[4]),
									"Clave_Contenedor" => $row[5],
									"IdContenedor" => $row[6],
									"CveLP" => $row[7],
									"Tipo" => $row[8],
									"Cantidad" => $row[9],
									"Paletizar" => 0);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONPaletizarEntrada()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_PaletizarEntrada ('"
                . $MiSql[$i]->{'Almacen'} . "',"
                . $MiSql[$i]->{'Folio'} . ",'"
                . $MiSql[$i]->{'Articulo'} . "','"
                . $MiSql[$i]->{'CLote'} . "',"
                . $MiSql[$i]->{'Cantidad'} . ","
                . $MiSql[$i]->{'IdCaja'} . ","
                . $MiSql[$i]->{'IdTarima'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CajasEnt_SinPalet"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CajasEnt_SinPalet"][] = array("ERROR" => $row[0],
									"MSG" => utf8_encode($row[1]));
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONAgregaContenedores()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_AgregaContenedor ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Contenedor'} . "','"
                . $MiSql[$i]->{'Descripcion'} . "','"
                . $MiSql[$i]->{'Tipo'} . "',"
                . $MiSql[$i]->{'Ancho'} . ","
                . $MiSql[$i]->{'Largo'} . ","
                . $MiSql[$i]->{'Alto'} . ","
                . $MiSql[$i]->{'Peso_Max'} . ","
                . $MiSql[$i]->{'BanPer'} . ",'"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'CapVolCont'} . ","
                . $MiSql[$i]->{'CantCont'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ContenedorNew"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ContenedorNew"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"IdContenedor" => $row[2],
									"DescCont" => $row[4],
									"Cve_Cont" => $row[3],
									"ALto_Cont" => $row[5],
									"Ancho_Cont" => $row[6],
									"Fondo_Cont" => $row[7],
									"PesoM_Cont" => $row[8],
									"BanPermanente" => $row[9],
									"Tipo_Cont" => $row[10],
									"CveLP" => $row[11]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONValidaArticuloContenedor()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ValidaArticuloContenedor ('" . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Articulo'} . "',"
                . $MiSql[$i]->{'Cantidad'} . ","
                . $MiSql[$i]->{'ICont'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArtContenedor"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ArtContenedor"][] = array("ERROR" => $row[0],
									"MSG" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONValidaCajaContenedor()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ValidaCajaContenedor ('" . $MiSql[$i]->{'Almacen'} . "',"
                . $MiSql[$i]->{'CajaMix'} . ","
                . $MiSql[$i]->{'ICont'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CajaContenedor"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CajaContenedor"][] = array("ERROR" => $row[0],
									"MSG" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONAsignaCajaTarima()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_AsignaCajaTarima ("
                . $MiSql[$i]->{'CajaMix'} . ","
                . $MiSql[$i]->{'ICont'} . ",'"
                . $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_AsignaCajaContenedor"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_AsignaCajaContenedor"][] = array("ERROR" => $row[0]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameDetalleContenedorE()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameDetalleContenedorE ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Contenedor'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_DetalleContenedor"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_DetalleContenedor"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Cve_Almac" => $row[2],
									"nTarima" => $row[3],
									"Cve_Articulo" => $row[4],
									"Des_Articulo" => $row[5],
									"Cve_lote" => $row[6],
									"Existencia" => $row[7],
									"Fec_Caducidad" => $row[8],
									"PesoOcup" => $row[9]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONCierraContenedorE()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CierraContenedorE ('" . $MiSql[$i]->{'Almacen'} . "',"
                . $MiSql[$i]->{'Folio'} . ",'"
                . $MiSql[$i]->{'Contenedor'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArtContenedor"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ArtContenedor"][] = array("ERROR" => $row[0],
									"MSG" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameDetalleContenedorI()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameDetalleContenedorI ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Contenedor'} . "',"
                . $MiSql[$i]->{'Inventario'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_DetalleContenedor"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_DetalleContenedor"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Cve_Almac" => $row[2],
									"nTarima" => $row[3],
									"Cve_Articulo" => $row[4],
									"Des_Articulo" => $row[5],
									"Cve_lote" => $row[6],
									"Existencia" => $row[7],
									"Fec_Caducidad" => $row[8],
									"PesoOcup" => $row[9]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameDetalleContenedorIC()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameDetalleContenedorIC ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Contenedor'} . "',"
                . $MiSql[$i]->{'Inventario'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_DetalleContenedor"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_DetalleContenedor"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Cve_Almac" => $row[2],
									"nTarima" => $row[3],
									"Cve_Articulo" => $row[4],
									"Des_Articulo" => $row[5],
									"Cve_lote" => $row[6],
									"Existencia" => $row[7],
									"Fec_Caducidad" => $row[8],
									"PesoOcup" => $row[9]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONCierraContenedorI()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CierraContenedorI ('" . $MiSql[$i]->{'Almacen'} . "',"
                . $MiSql[$i]->{'Inventario'} . ",'"
                . $MiSql[$i]->{'Contenedor'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CierraContenedor"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CierraContenedor"][] = array("ERROR" => $row[0],
									"MSG" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONCierraContenedorIC()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CierraContenedorIC ('" . $MiSql[$i]->{'Almacen'} . "',"
                . $MiSql[$i]->{'Inventario'} . ",'"
                . $MiSql[$i]->{'Contenedor'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CierraContenedor"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CierraContenedor"][] = array("ERROR" => $row[0],
									"MSG" => utf8_encode($row[1]));
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONEliminaLP()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_EliminaLP ('" . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'LP'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_EliminaContenedor"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_EliminaContenedor"][] = array("ERROR" => $row[0],
									"MSG" => utf8_encode($row[1]));
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDespaletizaLP()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DespaletizaLP ('" . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'LP'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_EliminaContenedor"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_EliminaContenedor"][] = array("ERROR" => $row[0],
									"MSG" => utf8_encode($row[1]));
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONTrasladaPiezasLP()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_TrasladaPiezasLP ('"
				. $MiSql[$i]->{'Almacen'} . "','"
				. $MiSql[$i]->{'Articulo'} . "','"
				. $MiSql[$i]->{'CLote'} . "',"
				. $MiSql[$i]->{'Ubicacion'} . ",'"
				. $MiSql[$i]->{'Cve_Caja'} . "',"
				. $MiSql[$i]->{'IdTarima'} . ","
				. $MiSql[$i]->{'Proveedor'} . ","
                . $MiSql[$i]->{'Cantidad'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_TrasladaPiezasLP"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_TrasladaPiezasLP"][] = array("ERROR" => $row[0],
									"MSG" => utf8_encode($row[1]));
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONTrasladaPaletizaLP()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_TrasladaPaletizaLP ('"
				. $MiSql[$i]->{'Almacen'} . "','"
				. $MiSql[$i]->{'UbicaOri'} . "','"
				. $MiSql[$i]->{'Articulo'} . "','"
				. $MiSql[$i]->{'CLote'} . "','"
				. $MiSql[$i]->{'Cve_Caja'} . "',"
				. $MiSql[$i]->{'Ubicacion'} . ","
				. $MiSql[$i]->{'IdTarima'} . ",'"
				. $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_TrasladaPiezasLP"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				if($row[0]=="Error")
				{
					$_arr["T_TrasladaPiezasLP"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($row[2]));
				}
				else
				{
					$_arr["T_TrasladaPiezasLP"][] = array("ERROR" => $row[0],
										"MSG" => utf8_encode($row[1]));
				}
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONTrasladaLPOriaLPdest()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_TrasladaLPOriaLPdest ('"
				. $MiSql[$i]->{'Almacen'} . "','"
				. $MiSql[$i]->{'Articulo'} . "','"
				. $MiSql[$i]->{'CLote'} . "',"
				. $MiSql[$i]->{'Ubicacion'} . ",'"
				. $MiSql[$i]->{'IdTarOri'} . "',"
				. $MiSql[$i]->{'IdTarDest'} . ","
                . $MiSql[$i]->{'Cantidad'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_TrasladaTarLP"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_TrasladaTarLP"][] = array("ERROR" => $row[0],
									"MSG" => utf8_encode($row[1]));
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONSGrabaCajasEnt()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_GrabaCajasEnt ('"
                . $MiSql[$i]->{'bodega'} . "',"
				. $MiSql[$i]->{'folio'} . ",'"
				. $MiSql[$i]->{'articulo'} . "','"
				. $MiSql[$i]->{'lote'} . "',"
				. $MiSql[$i]->{'piexas'} . ","
				. $MiSql[$i]->{'cajas'} . ","
				. $MiSql[$i]->{'banPzs'} . ",'"
				. $this->user . "','"
				. $MiSql[$i]->{'fechai'} . "','"
				. $MiSql[$i]->{'ICont'} . "',"
				. $MiSql[$i]->{'Costo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_GrabaCajasEntrada"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				if($row[0]=="Error")
				{
					$_arr["T_GrabaCajasEntrada"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($row[2]));
				}
				else
				{
				$_arr["T_GrabaCajasEntrada"][] = array("ERROR" => $row[0],
									"IdDetEntrada" => $row[1]);
				}
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONSGuardaCajasEnt()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_GuardaCajasEnt ('"
                . $MiSql[$i]->{'Almacen'} . "',"
				. $MiSql[$i]->{'Folio'} . ",'"
				. $MiSql[$i]->{'Articulo'} . "','"
				. $MiSql[$i]->{'CLote'} . "',"
				. $MiSql[$i]->{'PzsXCja'} . ","
				. $MiSql[$i]->{'Cajas'} . ",'"
				. $MiSql[$i]->{'CveTarima'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_GuardaCajasEntrada"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error),
										"IdCaja" => 0);
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_GuardaCajasEntrada"][] = array("ERROR" => $row[0],
									"MSG" => utf8_encode($row[1]),
									"IdCaja" => $row[2]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONSDameCajasEnt()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameCajasEnt ('"
                . $MiSql[$i]->{'Almacen'} . "',"
				. $MiSql[$i]->{'Folio'} . ",'"
				. $MiSql[$i]->{'Articulo'} . "','"
				. $MiSql[$i]->{'CLote'} . "',"
				. $MiSql[$i]->{'PzsXCja'} . ","
				. $MiSql[$i]->{'Cajas'} . ",'"
				. $MiSql[$i]->{'IdCja'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CajasTarima"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error),
										"IdCaja" => 0);
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CajasTarima"][] = array("ERROR" => $row[0],
									"MSG" => utf8_encode($row[1]),
									"IdCaja" => $row[2]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONGrabaTarimaAduanaEnt()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_GrabaTarimaAduanaEnt ("
				. $MiSql[$i]->{'folio'} . ",'"
				. $this->user . "','"
				. $MiSql[$i]->{'ICont'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_GrabaCajasEntrada"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_GrabaCajasEntrada"][] = array("Error" => $row[0],
									"Msg" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONGrabaSeriesEnt()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_GrabaSeriesEnt ('"
                . $MiSql[$i]->{'bodega'} . "',"
				. $MiSql[$i]->{'folio'} . ",'"
				. $MiSql[$i]->{'articulo'} . "','"
				. $MiSql[$i]->{'Serie1'} . "','"
				. $this->user . "','"
				. $MiSql[$i]->{'fechai'} . "','"
				. $MiSql[$i]->{'ICont'} . "',"
				. $MiSql[$i]->{'Costo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_GrabaSeriesEntrada"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_GrabaSeriesEntrada"][] = array("ERROR" => $row[0],
									"IdDetEntrada" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONActualizaComentarioContEnt()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ActualizaComentarioContEnt ("
				. $MiSql[$i]->{'folio'} . ",'"
				. $MiSql[$i]->{'ICont'} . "','"
				. $MiSql[$i]->{'Coment'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ActualizaComentContEntrada"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ActualizaComentContEntrada"][] = array("ERROR" => $row[0],
									"MSG" => utf8_encode($row[1]));
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONGuardaCaracteristicasEnt()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_GuardaCaracteristicasEnt ("
				. $MiSql[$i]->{'Folio'} . ",'"
				. $MiSql[$i]->{'Articulo'} . "','"
				. $MiSql[$i]->{'Lote'} . "',"
				. $MiSql[$i]->{'Caract'} . ","
				. $MiSql[$i]->{'Cantidad'} . ",'"
				. $MiSql[$i]->{'CveTarima'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ActualizaCantCaracEntrada"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ActualizaCantCaracEntrada"][] = array("ERROR" => $row[0],
									"MSG" => utf8_encode($row[1]));
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONGuardaPesosEnt()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_GuardaPesosEnt ("
				. $MiSql[$i]->{'Folio'} . ",'"
				. $MiSql[$i]->{'Articulo'} . "','"
				. $MiSql[$i]->{'Lote'} . "',"
				. $MiSql[$i]->{'PesoB'} . ","
				. $MiSql[$i]->{'PesoT'} . ","
				. $MiSql[$i]->{'PesoN'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ActualizaPesoEntrada"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ActualizaPesoEntrada"][] = array("ERROR" => $row[0],
									"MSG" => utf8_encode($row[1]));
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONValidaDiferenciasEntrada()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ValidaDiferenciasEntrada ("
                . $MiSql[$i]->{'folio'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_DifEntrada"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_DifEntrada"][] = array("ERROR" => $row[0],
									"MSG" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONTerminaEntrada()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_TerminaEntrada ('"
                . $MiSql[$i]->{'bodega'} . "',"
				. $MiSql[$i]->{'folio'} . ",'"
                . $MiSql[$i]->{'buffer'}. "','"
                . $MiSql[$i]->{'Autorizo'}. "','"
				. $MiSql[$i]->{'fecha'}. "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_TerminaEntrada"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_TerminaEntrada"][] = array("ERROR" => $row[0],
									"MSG" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameDetalleContenedor()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameDetalleContenedor ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Contenedor'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_DetalleContenedor"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_DetalleContenedor"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Cve_Almac" => $row[2],
									"nTarima" => $row[3],
									"Cve_Articulo" => $row[4],
									"Des_Articulo" => $row[5],
									"Cve_lote" => $row[6],
									"Existencia" => $row[7],
									"Fec_Caducidad" => $row[8],
									"PesoOcup" => $row[9],
									"CveLP" => $row[10],
									"Folio" => $row[11],
									"OEP" => $row[12],
									"Proveedor" => $row[13],
									"Fecha" => $row[14],
									"Tipo" => $row[15]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameProveedores()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameProveedores ();";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_Proveedores"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_Proveedores"][] = array("Id_Proveedor" => $row[0],
									"Nombre" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameProveedoresOC()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameProveedoresOC ('"
                . $MiSql[$i]->{'Almacen'} . "',"
				. $MiSql[$i]->{'Prov'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_Proveedores"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_Proveedores"][] = array("Id_Proveedor" => $row[0],
									"Nombre" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameProtocolos()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$sql = "Call SPWS_DameProtEntrada();";
		$_arr = array();
		if(!$result = $conn->query($sql))
		{
				$_arr["T_Protocolos"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
		}
		else
		{
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_Protocolos"][] = array("ID_Protocolo" => $row[0],
									"descripcion" => utf8_encode($row[1]),
									"FOLIO" => $row[2]);
			}
			$conn->close();
		}
        return $_arr;
    }

    function Sql2JSONDameClientes()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameClientes ();";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["C_Cliente"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Cliente"][] = array("Cve_Clte" => $row[0],
									"cve_CteProv" => $row[1],
									"Nombre" => $row[2]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameProyectos()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameProyectos ('"
                . $MiSql[$i]->{'Almacen'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["C_Proyecto"][] = array("Error" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Proyecto"][] = array("Error" => $row[0],
									"Cve_Proyecto" => $row[1],
									"Des_Proyecto" => $row[2]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONCreaEntrada()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
			if(!isset($MiSql[$i]->{'Proyecto'})){
				$Proyecto="";
			}
			else{
				$Proyecto=$MiSql[$i]->{'Proyecto'};
			}
			if(!isset($MiSql[$i]->{'Moneda'})){
				$Moneda=0;
			}
			else{
				if($MiSql[$i]->{'Moneda'}===''){
					$Moneda=0;
				}
				else{
					$Moneda=$MiSql[$i]->{'Moneda'};
				}
			}
			if(!isset($MiSql[$i]->{'TipoCambio'})){
				$TipoCambio=1;
			}
			else{
				$TipoCambio=$MiSql[$i]->{'TipoCambio'};
			}
            $sql = "Call SPWS_CreaEntrada ('"
                . $MiSql[$i]->{'bodega'} . "','"
				. $this->user . "','"
				. $MiSql[$i]->{'Fecha'} . "',"
				. $MiSql[$i]->{'ID_Proveedor'} . ",'"
				. $MiSql[$i]->{'Protocolo'} . "',"
				. $MiSql[$i]->{'ConsecProt'} . ",'"
				. $MiSql[$i]->{'cve_ubicacion'} . "','"
				. $MiSql[$i]->{'BanCD'} . "','"
				. $MiSql[$i]->{'TipoE'} . "','"
				. $Proyecto . "',"
				. $Moneda . ","
				. $TipoCambio . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CreaEntrada"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CreaEntrada"][] = array("ERROR" => $row[0],
										"MSG" => $row[1],
										"Fol_Folio" => $row[2]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONAgregaTransporteEntrada()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_AgregaTransporteEntrada ("
                . $MiSql[$i]->{'Folio'} . ",'"
				. $MiSql[$i]->{'Chofer'} . "','"
				. $MiSql[$i]->{'IdChofer'} . "','"
				. $MiSql[$i]->{'Unidad'} . "','"
				. $MiSql[$i]->{'Placa'} . "','"
				. $MiSql[$i]->{'Trans'} . "','"
				. $MiSql[$i]->{'Obs'} . "','"
				. $MiSql[$i]->{'Sellos'} . "','"
				. $MiSql[$i]->{'Ingreso'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_AgregaTransEntrada"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_AgregaTransEntrada"][] = array("ERROR" => $row[0],
										"MSG" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameTransportistas()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameTransportistas ();";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_Transportistas"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_Transportistas"][] = array("ERROR" => $row[0],
										"MSG" => $row[1],
										"IdTransportistas" => $row[2],
										"cve_Transportista" => $row[3],
										"Transportista" => $row[4]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONAgregaFotoEntrada()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
			$Foto=addslashes(file_get_contents("../".$MiSql[$i]->{'Archivo'}));
			/*$stmt = $conn->prepare("Call SPWS_AgregaFotoEntrada (?,?,?,?,?);");
			$stmt->bind_param('isssb',$Folio,$Arch,$Descrip,$Tipo,$Foto);*/
            $sql = "Call SPWS_AgregaFotoEntrada ("
                . $MiSql[$i]->{'Folio'} . ",'"
				. $MiSql[$i]->{'Archivo'} . "','"
				. $MiSql[$i]->{'Descrip'} . "','"
				. $MiSql[$i]->{'Tipo'} . "','"
				. $Foto . "');";
			/*$Folio=$MiSql[$i]->{'Folio'};
			$Arch=$MiSql[$i]->{'Archivo'};
			$Descrip=$MiSql[$i]->{'Descrip'};
			$Tipo=$MiSql[$i]->{'Tipo'};
            $result = $stmt->execute();*/
			$conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_AgregaFotoEntrada"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_AgregaFotoEntrada"][] = array("ERROR" => $row[0],
										"MSG" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONAgregaFotoDetEntrada()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_AgregaFotoDetEntrada ("
                . $MiSql[$i]->{'IdDet'} . ",'"
				. $MiSql[$i]->{'Archivo'} . "','"
				. $MiSql[$i]->{'Descrip'} . "','"
				. $MiSql[$i]->{'Tipo'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_AgregaFotoDetEntrada"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_AgregaFotoDetEntrada"][] = array("ERROR" => $row[0],
										"MSG" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONEliminaFotoEntrada()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_EliminaFotoEntrada ("
                . $MiSql[$i]->{'Folio'} . ",'"
				. $MiSql[$i]->{'Archivo'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_EliminaFotoEntrada"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_EliminaFotoEntrada"][] = array("ERROR" => $row[0],
										"MSG" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONEliminaFotoDetEntrada()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_EliminaFotoDetEntrada ("
                . $MiSql[$i]->{'IdDet'} . ",'"
				. $MiSql[$i]->{'Archivo'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_EliminaFotoDetEntrada"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_EliminaFotoDetEntrada"][] = array("ERROR" => $row[0],
										"MSG" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONValidaOEPendienteAcomodo()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ValidaOEPendienteAcomodo ('"
                . $MiSql[$i]->{'Almac'} . "','"
				. $MiSql[$i]->{'Codigo'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_OEPendienteAcomodo"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_OEPendienteAcomodo"][] = array("Fol_OEP" => $row[0],
										"bufer" => $row[1],
										"Fol_Folio" => $row[2],
										"Fec_Entrada" => $row[3],
										"Cve_Articulo" => $row[4],
										"Cve_Lote" => $row[5],
										"Recibida" => $row[6],
										"Ubicada" => $row[7],
										"Cantidad" => $row[8],
										"Id_Proveedor" => $row[9],
										"Proveedor" => $row[10],
										"Observacion" => $row[11],
										"DOSERIAL" => $row[12],
										"ID_Protocolo" => $row[13]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameOEPendienteAcomodo()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameOEPendienteAcomodo ('"
                . $MiSql[$i]->{'Almac'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_OEPendienteAcomodo"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_OEPendienteAcomodo"][] = array("Fol_OEP" => $row[0],
										"bufer" => $row[1],
										"Fol_Folio" => $row[2],
										"Fec_Entrada" => $row[3],
										"Cve_Articulo" => $row[4],
										"Cve_Lote" => $row[5],
										"Recibida" => $row[6],
										"Ubicada" => $row[7],
										"Cantidad" => $row[8],
										"Id_Proveedor" => $row[9],
										"Proveedor" => $row[10],
										"Observacion" => $row[11],
										"DOSERIAL" => $row[12],
										"ID_Protocolo" => $row[13]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONCargaMotivosCuarentena()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CargaMotivosCuarentena ();";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["C_Motivo"][] = array("Error" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Motivo"][] = array("Error" => $row[0],
										"Tipo_Cat" => $row[1],
										"Id_Motivo" => $row[2],
										"Des_Motivo" => $row[3]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameProductosAlmacen()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameProductosAlmacen ('"
                . $MiSql[$i]->{'Almacen'} . "',"
                . $MiSql[$i]->{'BanExis'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["C_Articulo"][] = array("Error" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Articulo"][] = array("Error" => $row[0],
										"Cve_Articulo" => utf8_encode($row[1]),
										"Des_Articulo" => utf8_encode($row[2]),
										"Barras" => $row[3],
										"Barras_Caja" => $row[4]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameProductosOC()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameProductosOC ('"
                . $MiSql[$i]->{'Almacen'} . "',"
                . $MiSql[$i]->{'FolioOC'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["C_Articulo"][] = array("Error" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Articulo"][] = array("Error" => $row[0],
										"MSG" => utf8_encode($row[1]),
										"Cve_Articulo" => utf8_encode($row[2]),
										"Des_Articulo" => utf8_encode($row[3]),
										"Barras" => $row[4],
										"Barras_Caja" => $row[5]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameProductosCompuestos()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameProductosCompuestos ('"
                . $MiSql[$i]->{'Almacen'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["C_Articulo"][] = array("Error" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Articulo"][] = array("Error" => $row[0],
										"Cve_Articulo" => utf8_encode($row[1]),
										"Des_Articulo" => utf8_encode($row[2]),
										"Barras" => $row[3],
										"Barras_Caja" => $row[4]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }


    function Sql2JSONDameImpresorasEtiqueta()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameImpresorasEtiqueta ('"
				. $MiSql[$i]->{'Almacen'} . "','"
				. $MiSql[$i]->{'MODULO'} . "','"
				. $MiSql[$i]->{'NOMBRE'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_Impresoras"][] = array("Error" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_Impresoras"][] = array("IP" => $row[0],
										"Descripcion" => $row[1],
										"Puerto" => $row[2],
										"Tipo_Conexion" => $row[3],
										"Densidad_Imp" => $row[4]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameRegistrosEtiqueta()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameRegistrosEtiqueta ('"
				. $MiSql[$i]->{'Almacen'} . "','"
				. $MiSql[$i]->{'MODULO'} . "','"
				. $MiSql[$i]->{'NOMBRE'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_RegsEtiqueta"][] = array("Error" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_RegsEtiqueta"][] = array("Error" => $row[0],
										"RegsEtiqueta" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameEtiquetaImpresora()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameEtiquetaImpresora ('"
				. $MiSql[$i]->{'MODULO'} . "','"
				. $MiSql[$i]->{'NOMBRE'} . "','"
				. $MiSql[$i]->{'IP'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ImpresoraEtiqueta"][] = array("Error" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ImpresoraEtiqueta"][] = array("CADENA" => $row[0]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameUbicEntradaPendAcomodo()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameUbicEntradaPendAcomodo ('"
                . $MiSql[$i]->{'Almac'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_UbicEacionesRec"][] = array("Error" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_UbicEacionesRec"][] = array("bufer" => $row[0],
										"desc_ubicacion" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONSugiereUbicacionProd()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_SugiereUbicacionProd ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Articulo'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_UbicDisponibles"][] = array("Error" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_UbicDisponibles"][] = array("Idy_Ubica" => $row[0],
									"Cve_Almac" => $row[1],
									"cve_pasillo" => $row[2],
									"cve_rack" => $row[3],
									"Cve_Nivel" => $row[4],
									"Seccion" => $row[5],
									"Posicion" => $row[6],
									"CodigoCSD" => $row[7],
									"PorVolOcupado" => $row[8],
									"PorPesoOcupado" => $row[9],
									"Existencia" => $row[10],
									"Picking" => $row[11],
									"AcomodoMixto" => $row[12],
									"TipoUbicacion" => $row[13],
									"Id_Zona" => $row[14],
									"Cve_Zona" => $row[15],
									"Des_Zona" => $row[16],
									"Rotacion" => $row[17],
									"Secuencia" => $row[18]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameUbicacionDestino()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameUbicacionDestino ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'CveBL'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_UbicDisponibles"][] = array("Error" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_UbicDisponibles"][] = array("Error" => $row[0],
									"MSG" => utf8_encode($row[1]),
									"Idy_Ubica" => $row[2],
									"Cve_Almac" => $row[3],
									"cve_pasillo" => $row[4],
									"cve_rack" => $row[5],
									"Cve_Nivel" => $row[6],
									"Seccion" => $row[7],
									"Posicion" => $row[8],
									"CodigoCSD" => $row[9],
									"PorVolOcupado" => $row[10],
									"PorPesoOcupado" => $row[11],
									"Existencia" => $row[12],
									"Picking" => $row[13],
									"AcomodoMixto" => $row[14],
									"TipoUbicacion" => $row[15],
									"Id_Zona" => $row[16],
									"Cve_Zona" => $row[17],
									"Des_Zona" => $row[18],
									"Rotacion" => $row[19],
									"Secuencia" => $row[20]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONSugiereUbicacionAlmDest()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_SugiereUbicacionAlmDest ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Articulo'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_UbicDisponibles"][] = array("Error" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_UbicDisponibles"][] = array("Idy_Ubica" => $row[0],
									"Cve_Almac" => $row[1],
									"cve_pasillo" => $row[2],
									"cve_rack" => $row[3],
									"Cve_Nivel" => $row[4],
									"Seccion" => $row[5],
									"Posicion" => $row[6],
									"CodigoCSD" => $row[7],
									"PorVolOcupado" => $row[8],
									"PorPesoOcupado" => $row[9],
									"Existencia" => $row[10],
									"Picking" => $row[11],
									"AcomodoMixto" => $row[12],
									"TipoUbicacion" => $row[13],
									"Id_Zona" => $row[14],
									"Cve_Zona" => $row[15],
									"Des_Zona" => $row[16],
									"Rotacion" => $row[17],
									"Secuencia" => $row[18]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONValidaUbicacion()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ValidaUbicacion ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'CodigoBL'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ValidaUbic"][] = array("Error" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ValidaUbic"][] = array("Error" => $row[0],
									"Idy_Ubica" => $row[1],
									"CodigoCSD" => $row[2]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameProductosUbicacion()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameProductosUbicacion ('"
                . $MiSql[$i]->{'almac'} . "','"
                . $MiSql[$i]->{'ubicacion'} . "',"
                . $MiSql[$i]->{'FolioE'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ProdsUbicRecibo"][] = array("Error" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ProdsUbicRecibo"][] = array("cve_articulo" => $row[0],
									"des_articulo" => utf8_encode($row[1]),
									"cve_lote" => utf8_encode($row[2]),
									"Cantidad" => $row[3],
									"Ubicada" => $row[4],
									"Pendiente" => $row[5],
									"Cve_Ubicacion" => $row[6],
									"ManejaLote" => $row[7],
									"Barras" => $row[8],
									"PzsXCaja" => $row[9],
									"Id_Proveedor" => $row[10],
									"Proveedor" => utf8_encode($row[11]),
									"Cve_Contenedor" => $row[12],
									"Cuarentena" => $row[13],
									"ManejaSerie" => $row[14],
									"CB_Caja" => $row[15],
									"unidadMedida" => $row[16],
									"UM_Empaque" => $row[17],
									"Caduca" => $row[18],
									"Fec_Caducidad" => $row[19],
									"ClaveLP" => strtoupper($row[20]),
									"FolioOT" => $row[21],
									"ReferenciaOT" => $row[22],
									"TipoCont" => $row[23],
									"Id_Caja" => $row[24],
									"Cve_Caja" => $row[25],
									"Cant_Caja" => $row[26],
									"IdPzs" => $row[27]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONValidaCodigoPaletizar()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ValidaCodigoPaletizar ('"
                . $MiSql[$i]->{'almac'} . "','"
                . $MiSql[$i]->{'Codigo'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ProdsUbicRecibo"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ProdsUbicRecibo"][] = array("Error" => $row[0],
									"Msg" => $row[1],
									"Cve_Ubicacion" => $row[2],
									"cve_articulo" => $row[3],
									"des_articulo" => utf8_encode($row[4]),
									"cve_lote" => utf8_encode($row[5]),
									"Cantidad" => $row[6],
									"Ubicada" => $row[7],
									"Pendiente" => $row[8],
									"ManejaLote" => $row[9],
									"Barras" => $row[10],
									"PzsXCaja" => $row[11],
									"Id_Proveedor" => $row[12],
									"Proveedor" => utf8_encode($row[13]),
									"Cve_Contenedor" => $row[14],
									"Cuarentena" => $row[15],
									"ManejaSerie" => $row[16],
									"CB_Caja" => $row[17],
									"unidadMedida" => $row[18],
									"UM_Empaque" => $row[19],
									"Caduca" => $row[20],
									"Fec_Caducidad" => $row[21],
									"ClaveLP" => strtoupper($row[22]),
									"FolioOT" => $row[23],
									"ReferenciaOT" => $row[24],
									"TipoCont" => $row[25],
									"CveLPOri" => $row[26],
									"Id_Caja" => $row[27],
									"Cve_Caja" => $row[28],
									"Cant_Caja" => $row[29],
									"IdPzs" => $row[30]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONValidaCodigoLP()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ValidaCodigoLP ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Codigo'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_LPUbicRecibo"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_LPUbicRecibo"][] = array("Error" => $row[0],
									"Msg" => $row[1],
									"Cve_Ubicacion" => $row[2],
									"Idy_Ubica" => $row[3]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameCajasUbicacion()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameCajasUbicacion ('"
                . $MiSql[$i]->{'almac'} . "','"
                . $MiSql[$i]->{'ubicacion'} . "',"
                . $MiSql[$i]->{'FolioE'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CajasUbicacion"][] = array("Error" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CajasUbicacion"][] = array(
									"Error" => $row[0],
									"Id_Caja" => $row[1],
									"CodCaja" => $row[2],
									"IDContenedor" => $row[3],
									"Cve_Contenedor" => $row[4],
									"ClaveLP" => $row[5],
									"cve_articulo" => $row[6],
									"cve_lote" => $row[7],
									"PzsXCaja" => $row[8],
									"cve_ubicacion" => $row[9]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameUbicacionesProducto()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameUbicacionesProducto ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Barras'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ProdsUbicRecibo"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ProdsUbicRecibo"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"cve_articulo" => $row[2],
									"des_articulo" => $row[3],
									"cve_lote" => $row[4],
									"Cantidad" => $row[5],
									"Ubicada" => $row[6],
									"Pendiente" => $row[7],
									"Cve_Ubicacion" => $row[8],
									"ManejaLote" => $row[9],
									"Barras" => $row[10],
									"PzsXCaja" => $row[11],
									"Id_Proveedor" => $row[12],
									"Proveedor" => $row[13],
									"Cve_Contenedor" => $row[14],
									"Cuarentena" => $row[15],
									"ManejaSerie" => $row[16],
									"CB_Caja" => $row[17],
									"unidadMedida" => $row[18],
									"UM_Empaque" => $row[19],
									"Caduca" => $row[20],
									"Fec_Caducidad" => $row[21],
									"ClaveLP" => $row[22],
									"Id_Caja" => $row[23],
									"CveCaja" => $row[24],
									"Cant_Caja" => $row[25]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONConvierteContenedorEnPzs()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ConvierteContenedorEnPzs ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'ClaveLP'} . "','"
                . $MiSql[$i]->{'Articulo'} . "','"
                . $MiSql[$i]->{'ILote'} . "',"
                . $MiSql[$i]->{'Ubica'} . ",'"
                . $MiSql[$i]->{'Usuario'} . "',"
                . $MiSql[$i]->{'NewTarima'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ConvierteCont"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				if($row[0]=="Error")
				{
					$_arr["T_ConvierteCont"][] = array("Error" => -1,
										"Msg" => utf8_encode($row[2]));
				}
				else
				{
					$_arr["T_ConvierteCont"][] = array("Error" => $row[0],
										"Msg" => utf8_encode($row[1]));
				}
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameContenidoContenedorUbi()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameContenidoContenedorUbi ('"
                . $MiSql[$i]->{'almac'} . "','"
                . $MiSql[$i]->{'Cont'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ContContenedor"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ContContenedor"][] = array("Error" => $row[0],
									"Msg" => $row[1],
									"idy_ubica" => $row[2],
									"CodigoBL" => $row[3],
									"cve_articulo" => $row[4],
									"des_articulo" => $row[5],
									"Lote" => $row[6],
									"Cuarentena" => $row[7],
									"Existencia" => $row[8],
									"Id_Contenedor" => $row[9],
									"Clave_Contenedor" => $row[10],
									"Contenedor" => $row[11],
									"CveLP" => $row[12],
									"Rotacion" => $row[13],
									"UniMed" => $row[14],
									"ItemPos" => $row[15],
									"Caducidad" => $row[16],
									"Cant_Cajas" => $row[17]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameCajasContenedorUbi()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameCajasContenedorUbi ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Cont'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ProdsUbicRecibo"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ProdsUbicRecibo"][] = array("Error" => $row[0],
									"Msg" => $row[1],
									"Id_Caja" => $row[2],
									"Clave_Caja" => $row[3],
									"Cve_Articulo" => $row[4],
									"Descripcion" => $row[5],
									"Cve_Lote" => $row[6],
									"Id_Contenedor" => $row[7],
									"Clave_LP" => $row[8],
									"Cantidad_Cajas" => $row[9],
									"idy_ubica" => $row[10],
									"Clave_BL" => $row[11]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONPaletsUbicacion()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_PaletsUbicacion ('"
                . $MiSql[$i]->{'Almacen'} . "',"
                . $MiSql[$i]->{'Ubicacion'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ContenedorUbicacion"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ContenedorUbicacion"][] = array("Error" => $row[0],
									"Msg" => $row[1],
									"Id_Contenedor" => $row[2],
									"Clave_Contenedor" => $row[3],
									"CveLP" => $row[4],
									"cve_articulo" => $row[5],
									"Lote" => $row[6],
									"Cantidad" => $row[7]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONValidaEtiquetaAcomodo()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ValidaEtiquetaAcomodo ('"
                . $MiSql[$i]->{'ClaveEtiqueta'} . "','"
                . $MiSql[$i]->{'ubicacion'} . "','"
                . $MiSql[$i]->{'Almac'} . "','"
                . $MiSql[$i]->{'Lote'} . "',"
                . $MiSql[$i]->{'FolioE'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArtEnUbicacion"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ArtEnUbicacion"][] = array("ERROR" => $row[0],
									"MSG" => utf8_encode($row[1]),
									"Cve_Articulo" => $row[2],
									"Des_Articulo" => utf8_encode($row[3]),
									"Lote" => $row[4],
									"Tipo" => $row[5],
									"Caduca" => $row[6],
									"Cantidad" => $row[7],
									"PzsXCaja" => $row[8],
									"Restringido" => $row[9],
									"Caducidad" => $row[10],
									"Series" => $row[11],
									"UM" => $row[12],
									"UME" => $row[13],
									"Rotacion" => $row[14],
									"ItemPos" => $row[15],
									"Cuarentena" => $row[16],
									"IdContenedor" => $row[17],
									"Clave_Contendor" => $row[18],
									"CveLP" => $row[19]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameLotesArticuloUbica()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameLotesArticuloUbica ('"
                . $MiSql[$i]->{'articulo'} . "','"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'ubicacion'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["C_Lotes"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Lotes"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Lote" => $row[2],
									"Caducidad" => $row[3]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONValidaActivo()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ValidaActivo ('"
                . $MiSql[$i]->{'Activo'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["C_Activo"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Activo"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Cve_Articulo" => $row[2],
									"Descripcion" => $row[3]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONValidaSerieActivo()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ValidaSerieActivo ('"
                . $MiSql[$i]->{'Articulo'} . "','"
                . $MiSql[$i]->{'NSerie'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["C_Activo"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Activo"][] = array("ERROR" => $row[0],
									"MSG" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONGuardaSerieActivo()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_GuardaSerieActivo ('"
                . $MiSql[$i]->{'Activo'} . "','"
                . $MiSql[$i]->{'NSerie'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["C_Activo"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_Activo"][] = array("ERROR" => $row[0]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONAcomodaProducto()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
			if(!IsSet($MiSql[$i]->{'IdTarimaDest'})){
				$IdTarDest=0;
			}
			else {
				$IdTarDest=$MiSql[$i]->{'IdTarimaDest'};
			}
            $sql = "Call SPWS_AcomodaProducto ('"
                . $MiSql[$i]->{'Almacen'} . "',"
                . $MiSql[$i]->{'BanAcomodo'} . ",'"
                . $MiSql[$i]->{'UbiOri'} . "',"
                . $MiSql[$i]->{'UbiDest'} . ",'"
                . $MiSql[$i]->{'Articulo'} . "','"
                . $MiSql[$i]->{'Clote'} . "',"
                . $MiSql[$i]->{'Cant'} . ","
                . $MiSql[$i]->{'PzsXCaja'} . ","
                . $MiSql[$i]->{'BanPzs'} . ",'"
                . $MiSql[$i]->{'Usuario'} . "',"
                . $MiSql[$i]->{'FolioEnt'} . ","
                . $MiSql[$i]->{'BCuarentena'} . ","
                . $MiSql[$i]->{'Motivo'} . ","
                . $MiSql[$i]->{'Fusionar'} . ","
                . $MiSql[$i]->{'CajaId'} . ","
                . $IdTarDest . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArtUbicado"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				if($row[0]=="Error")
				{
					$_arr["T_ArtUbicado"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($row[2]));
				}
				else
				{
					$_arr["T_ArtUbicado"][] = array("ERROR" => $row[0],
										"MSG" => utf8_encode($row[1]));
				}
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONAcomodaTraslado()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_AcomodaTraslado ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'AlmacenD'} . "','"
                . $MiSql[$i]->{'UbiOri'} . "',"
                . $MiSql[$i]->{'UbiDest'} . ",'"
                . $MiSql[$i]->{'Articulo'} . "','"
                . $MiSql[$i]->{'Clote'} . "',"
                . $MiSql[$i]->{'Cant'} . ",'"
                . $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArtUbicado"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				if($row[0]=="Error")
				{
					$_arr["T_ArtUbicado"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($row[2]));
				}
				else
				{
					$_arr["T_ArtUbicado"][] = array("ERROR" => $row[0],
										"MSG" => utf8_encode($row[1]));
				}
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONAcomodaContenedor()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
			if(!IsSet($MiSql[$i]->{'IdTarimaDest'})){
				$IdTarDest=0;
			}
			else {
				$IdTarDest=$MiSql[$i]->{'IdTarimaDest'};
			}
            $sql = "Call SPWS_AcomodaContenedor ('"
                . $MiSql[$i]->{'Almacen'} . "',"
                . $MiSql[$i]->{'BanAcomodo'} . ",'"
                . $MiSql[$i]->{'UbiOri'} . "',"
                . $MiSql[$i]->{'UbiDest'} . ",'"
                . $MiSql[$i]->{'CContenedor'} . "','"
                . $MiSql[$i]->{'Usuario'} . "',"
                . $MiSql[$i]->{'FolioEnt'} . ","
                . $MiSql[$i]->{'BCuarentena'} . ","
                . $MiSql[$i]->{'Motivo'} . ","
                . $MiSql[$i]->{'Fusionar'} . ","
                . $IdTarDest . ");"; // 
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ContUbicado"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				if($row[0]=="Error")
				{
					$_arr["T_ContUbicado"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($row[2]));
				}
				else
				{
					$_arr["T_ContUbicado"][] = array("ERROR" => $row[0],
										"MSG" => utf8_encode($row[1]));
				}
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONAcomodaContenedorTras()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_AcomodaContenedorTras ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'AlmacenD'} . "','"
                . $MiSql[$i]->{'UbiOri'} . "',"
                . $MiSql[$i]->{'UbiDest'} . ",'"
                . $MiSql[$i]->{'CContenedor'} . "','"
                . $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ContUbicado"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				if($row[0]=="Error")
				{
					$_arr["T_ContUbicado"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($row[2]));
				}
				else
				{
					$_arr["T_ContUbicado"][] = array("ERROR" => $row[0],
										"MSG" => utf8_encode($row[1]));
				}
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDamePedidoPendiente()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DamePedidoPendiente ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Usuario'} . "','"
                . $MiSql[$i]->{'Fecha'} . "','"
                . $MiSql[$i]->{'Estatus'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_PedidoPendiente"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_PedidoPendiente"][] = array("ERROR" => $row[0],
									"Pend" => $row[1],
									"Surt" => $row[2],
									"FOLIO" => $row[3],
									"SUFIJO" => $row[4],
									"TipoCaja" => $row[5],
									"RazonSocial" => utf8_encode($row[6]),
									"Contenedor" => $row[7],
									"CveCont" => $row[8],
									"CveLP" => $row[9],
									"DescCont" => utf8_encode($row[10]),
									"Proyecto" => $row[10]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameRutasSurtido()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameRutasSurtido ('"
                . $MiSql[$i]->{'Almacen'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_RutaSurtido"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_RutaSurtido"][] = array("Error" => $row[0],
									"Msg" => utf8_encode($row[1]),
									"IdR" => $row[2],
									"Nombre" => utf8_encode($row[3]));
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONAsignaUsuarioRutaSurtido()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_AsignaUsuarioRutaSurtido ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Usuario'} . "',"
                . $MiSql[$i]->{'Ruta'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_RutaSurtido"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_RutaSurtido"][] = array("Error" => $row[0],
									"Msg" => utf8_encode($row[1]));
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDamePedidoAsigUsuario()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DamePedidoAsigUsuario ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_PedidosPendientes"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_PedidosPendientes"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Lineas" => $row[2],
									"Fol_Folio" => $row[3],
									"RazonSocial" => $row[4],
									"Pick_Num" => $row[5],
									"Proyecto" => $row[6]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONBuscaPedidoPendiente()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_BuscaPedidoPendiente ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "','"
                . $MiSql[$i]->{'Estatus'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_BuscaPedido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_BuscaPedido"][] = array("ERROR" => $row[0],
												"Proyecto" => $row[1],
												"TipoPedido" => $row[2],
												"Folio" => $row[3]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDamePedidosPendientes()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DamePedidosPendientes ('"
                . $MiSql[$i]->{'Almacen'} . "',"
                . $MiSql[$i]->{'Pagina'} . ",'"
                . $MiSql[$i]->{'Estatus'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_PedidosPendientes"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_PedidosPendientes"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Lineas" => $row[2],
									"Fol_Folio" => $row[3],
									"RazonSocial" => $row[4],
									"Proceso" => $row[5],
									"Pick_Num" => $row[6],
									"Observaciones" => $row[7]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameCabeceroPedidoXContenedor()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameCabeceroPedidoXContenedor ('"
                . $MiSql[$i]->{'CveTarima'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["TD_PedidoContenedor"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["TD_PedidoContenedor"][] = array("Error" => $row[0],
									"Msg" => $row[1],
									"Fol_Folio" => $row[2],
									"Cve_Clte" => $row[3],
									"Cliente" => $row[4],
									"Clave_BL" => $row[5]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameDetallePedidoXContenedor()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameDetallePedidoXContenedor ('"
                . $MiSql[$i]->{'Folio'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["TD_PedidoContenedor"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["TD_PedidoContenedor"][] = array("Error" => $row[0],
									"Msg" => $row[1],
									"Fol_Folio" => $row[2],
									"CveLP" => $row[3],
									"Cve_Articulo" => $row[4],
									"Des_Articulo" => $row[5],
									"Lote" => $row[6],
									"Surtidas" => $row[7]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDamePedidosPendientesUsr()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DamePedidosPendientesUsr ('"
                . $MiSql[$i]->{'Almacen'} . "',"
                . $MiSql[$i]->{'Pagina'} . ",'"
                . $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_PedidosPendientes"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_PedidosPendientes"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Line" => $row[2],
									"Fol_Folio" => $row[3],
									"RazonSocial" => utf8_encode($row[4]),
									"Pick_Num" => $row[5],
									"Observaciones" => $row[6],
									"Proyecto" => $row[7]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONPreparaPedidoSurtDriveIn()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_PreparaPedidoSurtDriveIn ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "','"
                . $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_PedidoSurtido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_PedidoSurtido"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Sufijo" => $row[2],
									"TipoPedido" => $row[3]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONPreparaPedidoSurtido()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_PreparaPedidoSurtido ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "','"
                . $MiSql[$i]->{'Usuario'} . "','"
                . $MiSql[$i]->{'Fecha'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_PedidoSurtido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_PedidoSurtido"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Id_Caja" => $row[2],
									"Caja" => $row[3],
									"Dimension" => $row[4],
									"Clave" => $row[5],
									"Sufijo" => $row[6],
									"TipoPedido" => $row[7]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONPreparaPedidoProyecto()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_PreparaPedidoProyecto ("
                . $MiSql[$i]->{'Almacen'} . ",'"
                . $MiSql[$i]->{'Folio'} . "','"
                . $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_PedidoSurtido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_PedidoSurtido"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Id_Caja" => $row[2],
									"Caja" => $row[3],
									"Dimension" => $row[4],
									"Clave" => $row[5],
									"Sufijo" => $row[6],
									"TipoPedido" => $row[7]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONPreparaPedidoTras()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
			$Hoy = getdate();
			$Fecha = date_format($Hoy,'Y-m-d');
            $sql = "Call SPWS_PreparaPedidoSurtidoTras ("
                . $MiSql[$i]->{'Almacen'} . ",'"
                . $MiSql[$i]->{'Folio'} . "','"
                . $MiSql[$i]->{'Usuario'} . "','"
                . $Fecha . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_PedidoSurtido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				if($row[0]=="Error")
				{
					$_arr["T_ArticuloSurtido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($row[2]));
				}
				else
				{
				$_arr["T_PedidoSurtido"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Id_Caja" => $row[2],
									"Caja" => $row[3],
									"Dimension" => $row[4],
									"Clave" => $row[5],
									"Sufijo" => $row[6],
									"TipoPedido" => $row[7]);
				}
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONPreparaPedidoSurtidoNikken()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_PreparaPedidoSurtidoNikken ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "','"
                . $MiSql[$i]->{'Usuario'} . "','"
                . $MiSql[$i]->{'Fecha'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_PedidoSurtidoNik"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_PedidoSurtidoNik"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Id_Caja" => $row[2],
									"Caja" => $row[3],
									"Dimension" => $row[4],
									"Clave" => $row[5]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONPreparaPedidoSurtidoNik_2()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "Call SPWS_PreparaPedidoSurtidoNik_2 ('"
				. $MiSql[$i]->{'Almacen'} . "','"
				. $MiSql[$i]->{'Folio'} . "','"
				. $MiSql[$i]->{'Usuario'} . "','"
				. $MiSql[$i]->{'Fecha'} . "');";
			$result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_PedidoSurtidoNik"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_PedidoSurtidoNik"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Id_Caja" => $row[2],
									"Caja" => $row[3],
									"Dimension" => $row[4],
									"Clave" => $row[5],
									"CantCajas" => $row[6],
									"Id_CajaC" => $row[7],
									"CajaC" => $row[8],
									"DimC" => $row[9],
									"ClaveC" => $row[10],
									"VolPedido" => $row[11],
									"CantProd" => $row[12]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

	function Sql2JSONCargaCajasEmp()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "Call SPWS_CargaCajasEmp ();";
			$result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["C_TipoCaja"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["C_TipoCaja"][] = array("Id_TipoCaja" => $row[0],
									"Clave" => $row[1],
									"ancho" => $row[2],
									"largo" => $row[3],
									"alto" => $row[4],
									"descripcion" => $row[5]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

	function Sql2JSONDameCajasEmpaque()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "Call SPWS_DameCajasEmpaque ('"
				. $MiSql[$i]->{'Almacen'} . "');";
			$result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CajaEmpaque"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CajaEmpaque"][] = array("Id_TipoCaja" => $row[0],
									"Clave" => $row[1],
									"Descripcion" => $row[2],
									"Dimension" => $row[3],
									"ancho" => $row[4],
									"largo" => $row[5],
									"alto" => $row[6]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

	function Sql2JSONDameCajasPedido()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "Call SPWS_DameCajasPedido ('"
				. $MiSql[$i]->{'Almacen'} . "','"
				. $MiSql[$i]->{'Folio'} . "',"
				. $MiSql[$i]->{'ISufijo'} . ");";
			$result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CajaPedido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CajaPedido"][] = array("Fol_Folio" => $row[0],
									"Total" => $row[1],
									"Cerradas" => $row[2],
									"Pendientes" => $row[3]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

	function Sql2JSONDameProductosDelPedido()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "Call SPWS_DameProductosDelPedido ('"
				. $MiSql[$i]->{'Almacen'} . "','"
				. $MiSql[$i]->{'Folio'} . "',"
				. $MiSql[$i]->{'Isufijo'} . ");";
			$result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_DetallaPedido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_DetallaPedido"][] = array("Cve_Articulo" => $row[0],
									"Des_Articulo" => $row[1],
									"Num_Cantidad" => $row[2]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

	function Sql2JSONActualizaCajaEmpaque()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "Call SPWS_ActualizaCajaEmpaque ('"
				. $MiSql[$i]->{'Almacen'} . "','"
				. $MiSql[$i]->{'Folio'} . "',"
				. $MiSql[$i]->{'Isufijo'} . ","
				. $MiSql[$i]->{'CajaAnt'} . ","
				. $MiSql[$i]->{'NewCaja'} . ");";
			$result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ActualizaCaja"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ActualizaCaja"][] = array("ERROR" => $row[0]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONDameDetalleContenedorP()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameDetalleContenedorP ('"
                . $MiSql[$i]->{'Almacen'} . "',"
                . $MiSql[$i]->{'Contenedor'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_DetalleContenedor"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_DetalleContenedor"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Cve_Almac" => $row[2],
									"nTarima" => $row[3],
									"Cve_Articulo" => $row[4],
									"Des_Articulo" => $row[5],
									"Cve_lote" => $row[6],
									"Existencia" => $row[7],
									"Fec_Caducidad" => $row[8],
									"PesoOcup" => $row[9]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONCierraContenedorP()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CierraContenedorP ('" . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "','"
                . $MiSql[$i]->{'Contenedor'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CierraContenedor"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CierraContenedor"][] = array("ERROR" => $row[0],
									"MSG" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameArticulosPedidoNikken()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameArticulosPedidoNikken ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Fol_Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArticuloPedido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ArticuloPedido"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Folio" => $row[2],
									"Sufijo" => $row[3],
									"Cve_articulo" => $row[4],
									"des_articulo" => $row[5],
									"Cantidad" => $row[6],
									"Surtido" => $row[7],
									"Cve_Lote" => $row[8],
									"PiezasXCaja" => $row[9],
									"Imagen" => $row[10],
									"CB" => $row[11],
									"Pendientes" => $row[12],
									"Surtidas" => $row[13]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameArticuloPedidoNikken()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameArticuloPedidoNikken ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Fol_Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArticuloPedido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ArticuloPedido"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Cve_articulo" => $row[2],
									"des_articulo" => $row[3],
									"Cantidad" => $row[4],
									"Cve_Lote" => $row[5],
									"PiezasXCaja" => $row[6],
									"Imagen" => $row[7],
									"CB" => $row[8],
									"Pendientes" => $row[9],
									"Surtidas" => $row[10]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameArticulosPedido()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameArticulosPedido ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Fol_Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArticuloPedido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				if($row[0]=="Error")
				{
					$_arr["T_ArticuloPedido"][] = array("ERROR" => 0,
										"MSG" => utf8_encode($row[2]));
				}
				else
				{
					$_arr["T_ArticuloPedido"][] = array("ERROR" => $row[0],
										"MSG" => $row[1],
										"Cve_articulo" => $row[2],
										"des_articulo" => utf8_encode($row[3]),
										"Cantidad" => $row[4],
										"Cve_Lote" => $row[5],
										"PiezasXCaja" => $row[6],
										"Imagen" => $row[7],
										"CB" => $row[8],
										"Pendientes" => $row[9],
										"Surtidas" => $row[10],
										"Ubicacion" => $row[11],
										"Idy_Ubica" => $row[12],
										"CB_Caja" => $row[13],
										"ManejaLote" => $row[14],
										"ManejaSerie" => $row[15],
										"Meses" => $row[16],
										"UnidadMedida" => $row[17],
										"UniMedEmpaque" => $row[18],
										"ExisTarima" => $row[19],
										"Id_Contendor" => $row[20],
										"CveTarima" => $row[21],
										"desTarima" => utf8_encode($row[22]),
										"LP" => $row[23]);
				}
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameArticulosPedidoProyecto()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameArticulosPedidoProyecto ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Fol_Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArticuloPedido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ArticuloPedido"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Cve_articulo" => $row[2],
									"des_articulo" => utf8_encode($row[3]),
									"Cantidad" => $row[4],
									"Cve_Lote" => $row[5],
									"PiezasXCaja" => $row[6],
									"Imagen" => $row[7],
									"CB" => $row[8],
									"Pendientes" => $row[9],
									"Surtidas" => $row[10],
									"Ubicacion" => $row[11],
									"Idy_Ubica" => $row[12],
									"CB_Caja" => $row[13],
									"ManejaLote" => $row[14],
									"ManejaSerie" => $row[15],
									"Meses" => $row[16],
									"UnidadMedida" => $row[17],
									"UniMedEmpaque" => $row[18],
									"ExisTarima" => $row[19],
									"Id_Contendor" => $row[20],
									"CveTarima" => $row[21],
									"desTarima" => utf8_encode($row[22]),
									"LP" => $row[23]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameArticuloPedido()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameArticuloPedido ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Fol_Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArticuloPedido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ArticuloPedido"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Cve_articulo" => $row[2],
									"des_articulo" => utf8_encode($row[3]),
									"Cantidad" => $row[4],
									"Cve_Lote" => $row[5],
									"PiezasXCaja" => $row[6],
									"Imagen" => $row[7],
									"CB" => $row[8],
									"Pendientes" => $row[9],
									"Surtidas" => $row[10],
									"Ubicacion" => $row[11],
									"Idy_Ubica" => $row[12],
									"CB_Caja" => $row[13],
									"ManejaLote" => $row[14],
									"ManejaSerie" => $row[15],
									"Meses" => $row[16],
									"UnidadMedida" => $row[17],
									"UniMedEmpaque" => $row[18],
									"ExisTarima" => $row[19],
									"Id_Contendor" => $row[20],
									"CveTarima" => $row[21],
									"desTarima" => $row[22],
									"LP" => $row[23],
									"ItemPos" => $row[24],
									"Cve_Cot" => $row[25]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameCajasArticuloPedido()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameCajasArticuloPedido ("
                . $MiSql[$i]->{'Almacen'} . ","
                . $MiSql[$i]->{'Ubicacion'} . ",'"
                . $MiSql[$i]->{'Articulo'} . "','"
                . $MiSql[$i]->{'CLote'} . "',"
                . $MiSql[$i]->{'Cantidad'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CajaArticuloPedido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CajaArticuloPedido"][] = array("ERROR" => $row[0],
									"MSG" => "",
									"Cve_CajaMix" => $row[1],
									"Guia" => $row[2],
									"CodCaja" =>$row[3],
									"nTarima" => $row[4],
									"CveLP" => $row[5],
									"Cve_Articulo" => $row[6],
									"Cve_Lote" => $row[7],
									"Cant" => $row[8]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameCajasUbicacionPedido()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameCajasUbicacionPedido ("
                . $MiSql[$i]->{'Almacen'} . ","
                . $MiSql[$i]->{'Ubicacion'} . ",'"
                . $MiSql[$i]->{'Folio'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CajaArticuloPedido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CajaArticuloPedido"][] = array("ERROR" => $row[0],
									"MSG" => "",
									"Cve_CajaMix" => $row[1],
									"Guia" => $row[2],
									"CodCaja" =>$row[3],
									"nTarima" => $row[4],
									"CveLP" => $row[5],
									"Cve_Articulo" => $row[6],
									"Cve_Lote" => $row[7],
									"Cant" => $row[8]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameArticulosSurtidos()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameArticulosSurtidos ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Fol_Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArticuloPedido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ArticuloPedido"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Cve_articulo" => $row[2],
									"des_articulo" => utf8_encode($row[3]),
									"Cantidad" => $row[4],
									"Cve_Lote" => $row[5],
									"PiezasXCaja" => $row[6],
									"Imagen" => $row[7],
									"CB" => $row[8],
									"Pendientes" => $row[9],
									"Surtidas" => $row[10],
									"Ubicacion" => $row[11],
									"Idy_Ubica" => $row[12],
									"CB_Caja" => $row[13],
									"ManejaLote" => $row[14],
									"ManejaSerie" => $row[15],
									"Meses" => $row[16],
									"UnidadMedida" => $row[17],
									"UniMedEmpaque" => $row[18],
									"ExisTarima" => $row[19],
									"Id_Contendor" => $row[20],
									"CveTarima" => $row[21],
									"desTarima" => utf8_encode($row[22]),
									"LP" => $row[23]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONValidaUbicacionPedido()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ValidaUbicacionPedido ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Fol_Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ","
                . $MiSql[$i]->{'UbiOri'} . ",'"
                . $MiSql[$i]->{'UbiNew'} . "','"
                . $MiSql[$i]->{'Articulo'} . "','"
                . $MiSql[$i]->{'ILote'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArticuloPedido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ArticuloPedido"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Cve_articulo" => $row[2],
									"des_articulo" => utf8_encode($row[3]),
									"Cantidad" => $row[4],
									"Cve_Lote" => utf8_encode($row[5]),
									"PiezasXCaja" => $row[6],
									"Imagen" => $row[7],
									"CB" => $row[8],
									"Pendientes" => $row[9],
									"Surtidas" => $row[10],
									"Ubicacion" => $row[11],
									"Idy_Ubica" => $row[12],
									"CB_Caja" => $row[13],
									"ManejaLote" => $row[14],
									"ManejaSerie" => $row[15],
									"Meses" => $row[16],
									"UnidadMedida" => $row[17],
									"UniMedEmpaque" => $row[18],
									"ExisTarima" => $row[19],
									"Id_Contendor" => $row[20],
									"CveTarima" => $row[21],
									"desTarima" => $row[22],
									"LP" => $row[23]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONValidaLotePedido()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ValidaLotePedido ('"
                . $MiSql[$i]->{'Almacen'} . "',"
                . $MiSql[$i]->{'Ubica'} . ",'"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ",'"
                . $MiSql[$i]->{'Articulo'} . "','"
                . $MiSql[$i]->{'CLote'} . "','"
                . $MiSql[$i]->{'OldLote'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ValidaLotePedido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ValidaLotePedido"][] = array("ERROR" => $row[0],
									"MSG" => utf8_encode($row[1]),
									"Cantidad" => $row[2]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONSurteArticulo()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_SurteArticulo ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ",'"
                . $MiSql[$i]->{'Articulo'} . "',"
                . $MiSql[$i]->{'Cantidad1'} . ","
                . $MiSql[$i]->{'BanPzs'} . ","
                . $MiSql[$i]->{'PzsXCaja'} . ","
				. $MiSql[$i]->{'UbiOri'} . ",'"
                . $MiSql[$i]->{'Usuario'} . "','"
                . $MiSql[$i]->{'CLote'} . "','"
                . $MiSql[$i]->{'IContenedor'} . "','"
                . $MiSql[$i]->{'ITarima'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArticuloSurtido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				if($row[0]=="Error")
				{
					$_arr["T_ArticuloSurtido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($row[2]));
				}
				else
				{
					$_arr["T_ArticuloSurtido"][] = array("ERROR" => $row[0],
										"MSG" => utf8_encode($row[1]));
				}
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONSurteArticuloDriveIn()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_SurteArticuloDriveIn ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ",'"
                . $MiSql[$i]->{'Articulo'} . "',"
                . $MiSql[$i]->{'Cantidad1'} . ","
                . $MiSql[$i]->{'BanPzs'} . ","
                . $MiSql[$i]->{'PzsXCaja'} . ","
				. $MiSql[$i]->{'UbiOri'} . ",'"
                . $MiSql[$i]->{'Usuario'} . "','"
                . $MiSql[$i]->{'CLote'} . "','"
                . $MiSql[$i]->{'IContenedor'} . "','"
                . $MiSql[$i]->{'ITarima'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArticuloSurtido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				if($row[0]=="Error")
				{
					$_arr["T_ArticuloSurtido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($row[2]));
				}
				else
				{
					$_arr["T_ArticuloSurtido"][] = array("ERROR" => $row[0],
										"MSG" => utf8_encode($row[1]));
				}
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONSurteCajaArticulo()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_SurteCajaArticulo ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ","
				. $MiSql[$i]->{'UbiOri'} . ",'"
                . $MiSql[$i]->{'Usuario'} . "',"
                . $MiSql[$i]->{'ICaja'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArticuloSurtido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				if($row[0]=="Error")
				{
					$_arr["T_ArticuloSurtido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($row[2]));
				}
				else
				{
					$_arr["T_ArticuloSurtido"][] = array("ERROR" => $row[0],
										"MSG" => utf8_encode($row[1]));
				}
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONSurteArticuloNikken()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_SurteArticuloNikken ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Fol_Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ",'"
                . $MiSql[$i]->{'Articulo'} . "',"
                . $MiSql[$i]->{'Cantidad1'} . ",'"
                . $MiSql[$i]->{'Usuario'} . "','"
                . $MiSql[$i]->{'Cve_Lote'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArticuloSurtido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ArticuloSurtido"][] = array("ERROR" => $row[0],
									"MSG" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONSurteArticulosNikken()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_SurteArticulosNikken ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Fol_Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArticuloSurtido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ArticuloSurtido"][] = array("ERROR" => $row[0],
									"MSG" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONEliminaProductoSurtido0()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_EliminaProductoSurtido0 ('"
                . $MiSql[$i]->{'Almacen'} . "',"
				. $MiSql[$i]->{'Ubica'} . ",'"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ",'"
                . $MiSql[$i]->{'Articulo'} . "','"
                . $MiSql[$i]->{'CLote'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArticuloSurtido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ArticuloSurtido"][] = array("ERROR" => $row[0],
									"MSG" => "");
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONTerminaPedido()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_TerminaPedido ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Fol_Folio'} . "',"
                . (int)$MiSql[$i]->{'Isufijo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_TerminaSurtido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				if($row[0]=="Error")
				{
					$_arr["T_TerminaSurtido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($row[2]));
				}
				else
				{
				$_arr["T_TerminaSurtido"][] = array("ERROR" => $row[0],
													"EsOT" => $row[1]);
				}
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONCambiaPedidoASurtiendo()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CambiaPedidoASurtiendo ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "',"
                . (int)$MiSql[$i]->{'Isufijo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_TerminaSurtido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				if($row[0]=="Error")
				{
					$_arr["T_TerminaSurtido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($row[2]));
				}
				else
				{
				$_arr["T_TerminaSurtido"][] = array("ERROR" => $row[0],
													"MSG" => utf8_encode($row[1]));
				}
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONDameUbicacionesManufactura()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameUbicionesManufactura ('"
                . $MiSql[$i]->{'Almacen'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_UbicDisponibles"][] = array("Error" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_UbicDisponibles"][] = array("Error" => $row[0],
													"Idy_Ubica" => $row[1],
													"CodigoCSD" => $row[2],
													"Volumen" => $row[3],
													"Peso" => $row[4],
													"Picking" => $row[5],
													"AcomodoMixto" => $row[6],
													"Tipo" => $row[7]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONDameUbicionesSalida()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameUbicionesSalida ('"
                . $MiSql[$i]->{'Almacen'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_UbicDisponibles"][] = array("Error" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_UbicDisponibles"][] = array("Error" => $row[0],
													"Idy_Ubica" => $row[1],
													"CodigoCSD" => $row[2]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

	function Sql2JSONDameArtPendUbicOT()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameProdPendPedidoOT ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ProdsUbicRecibo"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ProdsUbicRecibo"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Cve_articulo" => $row[2],
									"des_articulo" => $row[3],
									"Cve_Lote" => $row[4],
									"PiezasXCaja" => $row[5],
									"CB" => $row[6],
									"CB_Caja" => $row[7],
									"Ubicadas" => $row[8],
									"Surtidas" => $row[9],
									"ManejaLote" => $row[10],
									"BanCaduca" => $row[11],
									"ManejaSerie" => $row[12],
									"UnidadMedida" => $row[13],
									"UniMedEmpaque" => $row[14],
									"CveContenedor" => $row[15],
									"CveLP" => $row[16]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONUbicaPedidoOT()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_UbicaPedidoOT ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Fol_Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ","
                . $MiSql[$i]->{'Ubica'} . ",'"
                . $MiSql[$i]->{'LP'} . "','"
                . $MiSql[$i]->{'Articulo'} . "','"
                . $MiSql[$i]->{'CLote'} . "',"
                . $MiSql[$i]->{'NCantidad'} . ",'"
                . $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_UbicaPedidoOT"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_UbicaPedidoOT"][] = array("ERROR" => $row[0]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONUbicaOT()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_UbicaOT ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ","
                . $MiSql[$i]->{'Ubicacion'} . ",'"
                . $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_UbicaPedidoOT"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			$_arr["T_UbicaPedidoOT"][] = array("ERROR" => 1,
										"MSG" => '');
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONUbicaPedSal()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_UbicaPedSal ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ","
                . $MiSql[$i]->{'Ubicacion'} . ",'"
                . $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_UbicaPedidoOT"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			//while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_UbicaPedidoOT"][] = array("ERROR" => 1,
										"MSG" => "");
			//}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameOlasPendientes()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameOlasPendientes ('"
                . $MiSql[$i]->{'Almacen'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_Consolidado"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_Consolidado"][] = array("ERROR" => $row[0],
									"FOLIO" => $row[1],
									"Nom_CteCon" => $row[2],
									"AreaStagging" => $row[3],
									"Fol_Consolidado" => $row[4]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONTieneOlaPendiente()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_TieneOlaPendiente ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $this->user . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_PedidoPendOla"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_PedidoPendOla"][] = array("ERROR" => $row[0],
									"Folio_Consolidado" => $row[1],
									"FOLIO" => $row[2],
									"Orden_Compra" => $row[3],
									"Cve_clte" => $row[4],
									"RazonSocial" => $row[5],
									"CalleNumero" => $row[6],
									"Colonia" => $row[7],
									"Ciudad" => $row[8],
									"Estado" => $row[9],
									"CodigoPostal" => $row[10],
									"Tienda" => $row[11],
									"Nom_CteCon" => $row[12],
									"AreaStagging" => $row[13]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONDamePedidosOlaPend()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DamePedidosOlaPend ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'F_Madre'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_PedidosConsolidado"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_PedidosConsolidado"][] = array("ERROR" => $row[0],
									"Folio_Consolidado" => $row[1],
									"FOLIO" => $row[2],
									"Orden_Compra" => $row[3],
									"Cve_clte" => $row[4],
									"RazonSocial" => $row[5],
									"CalleNumero" => $row[6],
									"Colonia" => $row[7],
									"Ciudad" => $row[8],
									"Estado" => $row[9],
									"CodigoPostal" => $row[10],
									"Tienda" => $row[11]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONApartaPedidoOlaPend()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ApartaPedidoOlaPend ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'F_Madre'} . "','"
                . $MiSql[$i]->{'Folio'} . "','"
                . $this->user . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_PedidoCon"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_PedidoCon"][] = array("ERROR" => $row[0],
									"MSG" => $row[1]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONDameDetPedidoOla()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameDetPedidoOla ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'F_Madre'} . "','"
                . $MiSql[$i]->{'Folio'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_DetPedidoPendOla"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_DetPedidoPendOla"][] = array("ERROR" => $row[0],
									"Fol_Folio" => $row[1],
									"Cve_Articulo" => $row[2],
									"Cantidad" => $row[3],
									"ManejaLote" => $row[4],
									"ManejaSerie" => $row[5],
									"Descripcion" => utf8_encode($row[6]),
									"Barras" => utf8_encode($row[7]),
									"CB_Caja" => utf8_encode($row[8]),
									"PzsXCaja" => $row[9]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONSurteArtPedidoOla()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_SurteArtPedidoOla ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'F_Madre'} . "','"
                . $MiSql[$i]->{'Folio'} . "','"
                . $MiSql[$i]->{'Articulo'} . "','"
                . $MiSql[$i]->{'LoteS'} . "',"
                . $MiSql[$i]->{'CantidadS'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_SurtPedidoPendOla"][] = array("ERROR" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_SurtPedidoPendOla"][] = array("ERROR" => $row[0],
									"Msg" => utf8_encode($row[1]));
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONDameProdSurtidosPedidoOla()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameProdSurtidosPedidoOla ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'F_Madre'} . "','"
                . $MiSql[$i]->{'Folio'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_TPedidoCon"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_TPedidoCon"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Cve_articulo" => $row[2],
									"Des_Articulo" => $row[3],
									"Pedidas" => $row[4],
									"Surtidas" => $row[5],
									"Faltantes" => $row[6]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONDameUbicaEmbPedidoOla()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameUbicaEmbPedidoOla ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'F_Madre'} . "','"
                . $MiSql[$i]->{'Folio'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_TPedidoCon"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_TPedidoCon"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"cve_ubicacion" => $row[2],
									"descripcion" => $row[3]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONTerminaPedidoOlaPend()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_TerminaPedidoOlaPend ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'F_Madre'} . "','"
                . $MiSql[$i]->{'Folio'} . "','"
                . $this->user . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_TPedidoCon"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_TPedidoCon"][] = array("ERROR" => $row[0],
									"MSG" => $row[1]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONCreaHeaderSalida()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CreaHeaderSalida ("
                . $MiSql[$i]->{'TipoSal'} . ",'"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $this->user . "',"
                . $MiSql[$i]->{'Motivo'} . ",'"
                . $MiSql[$i]->{'Ref1'} . "','"
                . $MiSql[$i]->{'Ref2'} . "','"
                . $MiSql[$i]->{'RefF'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CreaSalida"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CreaSalida"][] = array("Error" => $row[0],
									"Msg" => $row[1],
									"Fol_Folio" => $row[2]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONDameSalidaPend()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameSalidaPend ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $this->user . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_DameSalida"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_DameSalida"][] = array("Error" => $row[0],
									"Fol_Folio" => $row[1],
									"Tipo_Salida" => $row[2],
									"Nombre" => $row[3],
									"FolioRef" => $row[4]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONDamePedPendSal()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DamePedPend_Sal ('"
                . $MiSql[$i]->{'Almacen'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_DamePedSalida"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_DamePedSalida"][] = array("Error" => $row[0],
									"Fol_folio" => $row[1],
									"Cve_clte" => $row[2],
									"Cve_CteProv" => $row[3],
									"RazonSocial" => $row[4],
									"Fec_Pedido" => $row[5],
									"TipoPedido" => $row[6],
									"Pedidas" => $row[7],
									"Surtidas" => $row[8]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONDameOCSal()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameOC_Sal ('"
                . $MiSql[$i]->{'Almacen'} . "',"
				. $MiSql[$i]->{'Prov'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_OCSalida"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_OCSalida"][] = array("Error" => $row[0],
									"Num_Pedimento" => $row[1],
									"Factura" => $row[2],
									"Fol_Folio" => $row[3],
									"ID_Proveedor" => $row[4],
									"Cve_Proveedor" => $row[5],
									"Nombre" => $row[6]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONValidaContenedorUbicacion()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ValidaContenedorUbicacion ('"
                . $MiSql[$i]->{'Almacen'} . "','"
				. $MiSql[$i]->{'Ubicacion'} . "','"
                . $MiSql[$i]->{'ClaveEtiqueta'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_TarimaUbicacion"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				if($row[0]=="1")
				{
					$_arr["T_TarimaUbicacion"][] = array("Error" => $row[0],
									"Msg" => utf8_encode($row[1]),
									"IdTarima" => $row[2],
									"CveTarima" => $row[3]);
				}
				else
				{
					$_arr["T_TarimaUbicacion"][] = array("Error" => $row[0],
									"Msg" => $row[1]);
				}
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONSurteArticuloSalida()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_SurteArticuloSalida ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "','"
                . $MiSql[$i]->{'Articulo'} . "',"
				. $MiSql[$i]->{'Ubica'} . ","
                . $MiSql[$i]->{'Cantidad'} . ",'"
                . $MiSql[$i]->{'Usuario'} . "','"
                . $MiSql[$i]->{'CLote'} . "',"
                . $MiSql[$i]->{'Id_Tarima'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArticuloSalida"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ArticuloSalida"][] = array("ERROR" => $row[0],
									"MSG" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONTerminaPedidoSalida()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_TerminaPedidoSalida ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_TerminaSurtido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_TerminaSurtido"][] = array("ERROR" => $row[0]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONHayReabasto()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_HayReabasto ('"
                . $MiSql[$i]->{'Almacen'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_HayReabasto"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_HayReabasto"][] = array("Error" => $row[0]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONDameProductosReabasto()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameProductosReabasto ('"
                . $MiSql[$i]->{'Almacen'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ProdReabasto"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ProdReabasto"][] = array("Error" => $row[0],
									"Articulo" => $row[1],
									"Rabasto" => $row[2],
									"Descripcion" => $row[3],
									"CB" => $row[4],
									"CB_Caja" => $row[5],
									"Folio" => $row[6]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONDameProductosAgregadosReabasto()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameProductosAgregadosReabasto ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $this->user . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArtTomadoReab"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ArtTomadoReab"][] = array("Error" => $row[0],
									"Articulo" => $row[1],
									"Cve_Lote" => $row[2],
									"Descripcion" => utf8_encode($row[3]),
									"CB" => $row[4],
									"CB_Caja" => $row[5],
									"Existencia" => $row[6],
									"Reabasto" => $row[7],
									"Ubicacion" => $row[8],
									"CveBL" => $row[9],
									"FechaCad" => $row[10],
									"BSerie" => $row[11],
									"BLote" => $row[12],
									"BCaduca" => $row[13],
									"Folio" => $row[14]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONCreaRecorridoReabasto()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CreaRecorridoReabasto ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "','"
                . $this->user . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArtTomadoReab"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ArtTomadoReab"][] = array("Error" => $row[0],
									"Msg" => utf8_encode($row[1]),
									"Folio" => $row[2],
									"Idy_Ubica" => $row[3],
									"CodigoCSD" => $row[4],
									"Secuencia" => $row[5],
									"Cve_Articulo" => $row[6],
									"Des_Articulo" => utf8_encode($row[7]),
									"BSerie" => $row[8],
									"BLote" => $row[9],
									"BCaduca" => $row[10],
									"CB" => $row[11],
									"CB_Caja" => $row[12],
									"Cve_Lote" => $row[13],
									"Caducidad" => $row[14],
									"Existencia" => $row[15],
									"Reabastecer" => $row[16],
									"Tomadas" => $row[17],
									"IdTarima" => $row[18],
									"CveTarima" => $row[19],
									"LP" => $row[20]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONAgregaProductoReabasto()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_AgregaProductoReabasto ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Articulo'} . "','"
                . $MiSql[$i]->{'Fol_Folio'} . "','"
                . $this->user . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArtTomadoReab"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ArtTomadoReab"][] = array("Error" => $row[0],
									"Articulo" => $row[1],
									"Cve_Lote" => $row[2],
									"Descripcion" => $row[3],
									"CB" => $row[4],
									"CB_Caja" => $row[5],
									"Existencia" => $row[6],
									"Reabasto" => $row[7],
									"Ubicacion" => $row[8],
									"CveBL" => $row[9],
									"FechaCad" => $row[10],
									"BSerie" => $row[11],
									"BLote" => $row[12],
									"BCaduca" => $row[13],
									"Folio" => $row[14]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONTomaProductoReabasto()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_TomaProductoReabasto ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Fol_Folio'} . "','"
                . $MiSql[$i]->{'Articulo'} . "','"
                . $MiSql[$i]->{'CLote'} . "',"
                . $MiSql[$i]->{'ICant'} . ","
                . $MiSql[$i]->{'Ubicacion'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArtTomadoReab"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ArtTomadoReab"][] = array("Error" => $row[0],
									"Msg" => utf8_encode($row[1]));
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONColocaProductoReabasto()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ColocaProductoReabasto ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Articulo'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ProdsTomadosReab"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ProdsTomadosReab"][] = array("Error" => $row[0],
									"Articulo" => $row[1],
									"Descripcion" => $row[2],
									"CB" => $row[3],
									"CB_Caja" => $row[4],
									"Existencia" => $row[5],
									"Reabasto" => $row[6],
									"Ubicacion" => $row[7],
									"CveBL" => $row[8],
									"BSerie" => $row[9],
									"BLote" => $row[10],
									"BCaduca" => $row[11],
									"Maximo" => $row[12],
									"Secuencia" => $row[13]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONDamePedidoPendienteRev()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DamePedidoPendienteRev ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_PedidoPendienteRev"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_PedidoPendienteRev"][] = array("ERROR" => $row[0],
									"FOLIO" => $row[1],
									"SUFIJO" => $row[2],
									"RazonSocial" => $row[3],
									"Contenedor" => $row[4],
									"CveCont" => $row[5],
									"CveLP" => $row[6],
									"DescCont" => $row[7],
									"IdRev" => $row[8],
									"CveRev" => $row[9],
									"DescRev" => $row[10]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDamePedidosPendientesRev()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DamePedidosPendientesRev ('"
                . $MiSql[$i]->{'Almacen'} . "',"
                . $MiSql[$i]->{'Pagina'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_PedidosPendientes"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_PedidosPendientes"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Lineas" => $row[2],
									"Fol_Folio" => $row[3],
									"RazonSocial" => $row[4],
									"Sufijo" => $row[5]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONCargaUbicacionesRev()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "Call SPWS_CargaUbicacionesRev ('"
                . $MiSql[$i]->{'Almacen'} . "');";
			$result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_Ubicaciones_Rev"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_Ubicaciones_Rev"][] = array("Error" => $row[0],
									"ID_URevision" => $row[1],
									"cve_ubicacion" => $row[2],
									"descripcion" => $row[3]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONBuscaPedidoPendienteRev()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_BuscaPedidoPendienteRev ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_BuscaPedido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_BuscaPedido"][] = array("ERROR" => $row[0],
									"Sufijo" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONApartaPedidoRevision()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "Call SPWS_ApartaPedidoRevision ('"
				. $MiSql[$i]->{'Almacen'} . "','"
				. $MiSql[$i]->{'Folio'} . "',"
				. $MiSql[$i]->{'Isufijo'} . ",'"
				. $MiSql[$i]->{'Usuario'} . "','"
				. $MiSql[$i]->{'Fecha'} . "',"
				. $MiSql[$i]->{'UbicRev'} . ");";
			$result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ApartaPedidoRevision"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ApartaPedidoRevision"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Id_Caja" => $row[2],
									"Caja" => $row[3],
									"Dimension" => $row[4],
									"ClaveCaja" => $row[5]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

	function Sql2JSONDameCajasPedidoRev()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "Call SPWS_DameCajasPedidoRev ('"
				. $MiSql[$i]->{'Folio'} . "',"
				. $MiSql[$i]->{'Isufijo'} . ");";
			$result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CajaPedido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CajaPedido"][] = array("id_TipoCaja" => $row[0],
									"clave" => $row[1],
									"ancho" => $row[2],
									"largo" => $row[3],
									"alto" => $row[4],
									"descripcion" => $row[5],
									"Total" => $row[6]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

	function Sql2JSONSPWS_ApartaPedidoRev_2()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "Call SPWS_ApartaPedidoRev_2 ('"
				. $MiSql[$i]->{'Almacen'} . "','"
				. $MiSql[$i]->{'Folio'} . "',"
				. $MiSql[$i]->{'Isufijo'} . ",'"
				. $MiSql[$i]->{'Usuario'} . "','"
				. $MiSql[$i]->{'Fecha'} . "');";
			$result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["TH_CajaMix_Rev"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["TH_CajaMix_Rev"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"fol_folio" => $row[2],
									"Id_Caja" => $row[3],
									"Cajas" => $row[4],
									"clave" => $row[5]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONDameArticulosRevNikken()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameArticulosRevNikken ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArticuloRevision"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ArticuloRevision"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Cve_articulo" => $row[2],
									"des_articulo" => $row[3],
									"Cantidad" => $row[4],
									"revisadas" => $row[5],
									"Cve_Lote" => $row[6],
									"PiezasXCaja" => $row[7],
									"Imagen" => $row[8],
									"CB" => $row[9],
									"Pendientes" => $row[10],
									"Surtidas" => $row[11]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameArticulosRev()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameArticulosRev ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArticuloRevision"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ArticuloRevision"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Cve_articulo" => $row[2],
									"des_articulo" => $row[3],
									"Cantidad" => $row[4],
									"revisadas" => $row[5],
									"Cve_Lote" => $row[6],
									"PiezasXCaja" => $row[7],
									"Imagen" => $row[8],
									"CB" => $row[9],
									"CB_Caja" => $row[10],
									"BanLote" => $row[11],
									"BanSeries" => $row[12]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameArticuloRevisionNikken()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameArticuloRevisionNikken ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Fol_Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArticuloRevision"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ArticuloRevision"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Cve_articulo" => $row[2],
									"des_articulo" => $row[3],
									"Cantidad" => $row[4],
									"Cve_Lote" => $row[5],
									"PiezasXCaja" => $row[6],
									"Imagen" => $row[7],
									"CB" => $row[8],
									"Pendientes" => $row[9],
									"Surtidas" => $row[10]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONValidaArticuloRevision()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "Call SPWS_ValidaArticuloRevision ('"
				. $MiSql[$i]->{'Almacen'} . "', '"
				. $MiSql[$i]->{'Fol_Folio'} . "', "
				. (int)$MiSql[$i]->{'Isufijo'} . ", '"
				. $MiSql[$i]->{'Articulo'} . "', '"
				. $MiSql[$i]->{'ILote'} . "', "
				. (int)$MiSql[$i]->{'ICantidad'} . ");";
			$result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArticuloRevisado"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ArticuloRevisado"][] = array("ERROR" => $row[0],
									"MSG" => $row[1]);
			}
			$i++;
			mysqli_close($conn);
		}
		return $_arr;
	}

    function Sql2JSONRevisaArticulo()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_RevisaArticulo ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ",'"
                . $MiSql[$i]->{'Articulo'} . "',"
                . $MiSql[$i]->{'Cantidad1'} . ",'"
                . $MiSql[$i]->{'CLote'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArticuloRevisado"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ArticuloRevisado"][] = array("ERROR" => $row[0],
									"MSG" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONRevisaArticulosComp()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "Call SPWS_RevisaArticulosComp ('"
				. $MiSql[$i]->{'Almacen'} . "', '"
				. $MiSql[$i]->{'Folio'} . "', "
				. (int)$MiSql[$i]->{'Isufijo'} . ");";
			$result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArticuloRevisado"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ArticuloRevisado"][] = array("ERROR" => $row[0],
									"MSG" => $row[1]);
			}
			$i++;
			mysqli_close($conn);
		}
		return $_arr;
	}

    function Sql2JSONAgregaErrorUsuario()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_AgregaErrorUsuario ('"
                . $MiSql[$i]->{'Usuario'} . "','"
                . $MiSql[$i]->{'Cometido_En'} . "','"
                . $MiSql[$i]->{'Descrip'} . "','"
                . $MiSql[$i]->{'Ref'} . "','"
                . $MiSql[$i]->{'Fecha'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ErrorUsuario"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ErrorUsuario"][] = array("ERROR" => $row[0]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONDameArtsRevisados()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameArtsRevisados ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ","
                . $MiSql[$i]->{'Revisados'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ArticulosRevisados"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ArticulosRevisados"][] = array("fol_folio" => $row[0],
									"sufijo" => $row[1],
									"cve_articulo" => $row[2],
									"Des_Articulo" => $row[3],
									"Cantidad" => $row[4]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDamePedidosPendEmp()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DamePedidosPendEmp ('"
                . $MiSql[$i]->{'Almacen'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_PendientesEmpaque"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_PendientesEmpaque"][] = array("ERROR" => $row[0],
									"Fol_folio" => $row[1],
									"Sufijo" => $row[2],
									"Cve_clte" => $row[3],
									"Cve_CteProv" => $row[4],
									"RazonSocial" => $row[5],
									"Pend" => $row[6],
									"Emp" => $row[7],
									"VolumenPed" => $row[8]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONEmpacaPedidoEnCajas()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_EmpacaPedidoEnCajas ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_Empacados"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_Empacados"][] = array("Error" => $row[0],
									"Cajas" => $row[1],
									"IdCajaS" => $row[2],
									"DesCajaS" => $row[3],
									"DimCajaS" => $row[4],
									"CveCajaS" => $row[5],
									"NumCajasS" => $row[6],
									"IdCaja2" => $row[7],
									"DesCaja2" => $row[8],
									"DimCaja2" => $row[9],
									"CveCaja2" => $row[10]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONSeparaFoliosOla()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_SeparaFoliosOla ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'FolioWS'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_FoliosWaveSet"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_FoliosWaveSet"][] = array("Error" => $row[0],
									"Msg" => $row[1],
									"Fol_PedidoCon" => $row[2],
									"Fol_Consolidado" => $row[3]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONEmpacaCrossDockEnCajas()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_EmpacaCrossDockEnCajas ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_EmpacadosCD"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_EmpacadosCD"][] = array("Error" => $row[0],
									"Msg" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameUbicacionesEmbarque()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameUbicacionesEmbarque ('"
                . $MiSql[$i]->{'Almacen'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_UbicacionesEmbarque"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_UbicacionesEmbarque"][] = array("ERROR" => $row[0],
									"cve_ubicacion" => $row[1],
									"Descripcion" => $row[2],
									"largo" => $row[3],
									"ancho" => $row[4],
									"alto" => $row[5],
									"VolumenUbic" => $row[6],
									"VolumenPed" => $row[7]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameUbicacionesEmbarquePedido()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameUbicacionesEmbarquePedido ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "','"
                . $MiSql[$i]->{'Staggin'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_UbicacionesEmbarque"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_UbicacionesEmbarque"][] = array("ERROR" => $row[0],
									"cve_ubicacion" => $row[1],
									"Descripcion" => $row[2]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDamePedidosPendEmb()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DamePedidosPendEmb ('"
                . $MiSql[$i]->{'Almacen'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_PendientesEmpaque"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_PendientesEmpaque"][] = array("ERROR" => $row[0],
									"Fol_folio" => $row[1],
									"Sufijo" => $row[2],
									"Cve_clte" => $row[3],
									"Cve_CteProv" => $row[4],
									"RazonSocial" => $row[5],
									"Pend" => $row[6],
									"Emp" => $row[7],
									"VolumenPed" => $row[8]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameProdsPendEmpaque()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameProdsPendEmpaque ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ProdsPendEmpaque"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ProdsPendEmpaque"][] = array("ERROR" => $row[0],
									"Fol_Folio" => $row[1],
									"Sufijo" => $row[2],
									"Cve_Articulo" => $row[3],
									"Des_Articulo" => $row[4],
									"Cantidad" => $row[5]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

	function Sql2JSONDameArtsPendEmpacar()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameArtsPendEmpacar ('"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ProdsRevision"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ProdsRevision"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Fol_Folio" => $row[2],
									"Sufijo" => $row[3],
									"Cve_Articulo" => $row[4],
									"Des_Articulo" => $row[5],
									"CB" => $row[6],
									"Pendientes" => $row[7],
									"lote" => $row[8]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

	function Sql2JSONDameCajasGuiasPedido()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameCajasGuiasPedido ('"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CajasGenPedido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CajasGenPedido"][] = array("Total_Guias" => $row[0],
									"Total_Cajas" => $row[1],
									"Cajas_Pendientes" => $row[2]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

	function Sql2JSONDameCajasPendientesPedido()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameCajasPendientesPedido ('"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CajasGenPedido"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CajasGenPedido"][] = array("Total_Guias" => $row[0],
									"Total_Cajas" => $row[1],
									"Cajas_Pendientes" => $row[2]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONBuscaContenedorPAbierto()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_BuscaContenedorPAbierto ('" . $MiSql[$i]->{'Almacen'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CierraContenedor"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CierraContenedor"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"IdContenedor" => $row[2],
									"Cve_Contenedor" => $row[3],
									"Desc_Contenedor" => $row[4],
									"Cve_LP" => $row[5]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameCajasPendientesTarima()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameCajasPendientesTarima ('"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CajasPenTarima"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CajasPenTarima"][] = array("Error" => $row[0],
									"Cve_CajaMix" => $row[1],
									"Cve_Articulo" => $row[2],
									"Des_Articulo" => $row[3],
									"Cantidad" => $row[4],
									"Guia" => $row[5],
									"ntarima" => $row[6],
									"CveLP" => $row[7]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

	function Sql2JSONEliminaCajasPedido()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_EliminaCajasPedido ('"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ECajasPedido"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ECajasPedido"][] = array("Error" => $row[0]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

	function Sql2JSONCreaCajasPedido()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CreaCajasPedido ('"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ","
                . $MiSql[$i]->{'Cajas'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CajasPedido"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CajasPedido"][] = array("Error" => $row[0]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

	function Sql2JSONEmpacaArtEnCaja()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "Call SPWS_EmpacaArtEnCaja ('"
				. $MiSql[$i]->{'Almacen'} . "','"
				. $MiSql[$i]->{'Folio'} . "',"
				. $MiSql[$i]->{'Isufijo'} . ","
				. $MiSql[$i]->{'NumCaja'} . ",'"
				. $MiSql[$i]->{'Articulo'} . "','"
				. $MiSql[$i]->{'ILote'} . "',"
				. $MiSql[$i]->{'ICantidad'} . ");";
			$result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_EmpacaEnCaja"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_EmpacaEnCaja"][] = array("ERROR" => $row[0],
									"MSG" => $row[1]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

	function Sql2JSONCierraCajaPedido()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "Call SPWS_CierraCajaPedido ('"
				. $MiSql[$i]->{'Folio'} . "',"
				. $MiSql[$i]->{'Isufijo'} . ");";
			$result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_EmpacaEnCaja"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_EmpacaEnCaja"][] = array("ERROR" => $row[0]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

	function Sql2JSONTerminaPedidoRevision()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "Call SPWS_TerminaPedidoRevision ('"
				. $MiSql[$i]->{'Almacen'} . "','"
				. $MiSql[$i]->{'Folio'} . "',"
				. $MiSql[$i]->{'Isufijo'} . ",'"
				. $MiSql[$i]->{'Fecha'} . "');";
			$result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_TerminaRevision"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_TerminaRevision"][] = array("ERROR" => $row[0]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

	function Sql2JSONDameStatusGuia()
	{
		$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$i=0;
		foreach((array)$this->arrTbl as $detalle)
		{
			$MiSql[] = $detalle;
			$sql = "Call SPWS_DameStatusGuia ('"
				. $MiSql[$i]->{'Folio'} . "');";
			$result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_StatusGuia"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_StatusGuia"][] = array("Cve_CajaMix" => $row[0],
									"Cve_TipoCaja" => $row[1],
									"Status_Guia" => $row[2]);
			}
			$i++;
			$conn->close();
		}
		return $_arr;
	}

    function Sql2JSONBuscaPedidoPendEmb()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_BuscaPedidoPendEmb ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_BuscaPedidoEmp"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_BuscaPedidoEmp"][] = array("ERROR" => $row[0],
									"Fol_Folio" => $row[1],
									"Sufijo" => $row[2]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONAsignaPedidoAUbicEmb()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_AsignaPedidoAUbicEmb ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ",'"
                . $MiSql[$i]->{'AreaEmb'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_AsignaEnEmb"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_AsignaEnEmb"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"cve_ubicacion" => $row[2]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameDatosPedidoAEmb()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameDatosPedidoAEmb ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_DatosPedidoEmb"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_DatosPedidoEmb"][] = array("fol_folio" => $row[0],
									"Peso" => $row[1],
									"Cajas" => $row[2],
									"Guias" => $row[3]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameCajasPedidoAEmp()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameCajasPedidoAEmp ('"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CajasPedidoEmp"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CajasPedidoEmp"][] = array("Error" => $row[0],
									"Fol_Folio" => $row[1],
									"Sufijo" => $row[2],
									"Cve_CajaMix" => $row[3],
									"Peso" => $row[4],
									"Embarcada" => $row[5],
									"Guia" => $row[6]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameDetPedidoAEmbarcar()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameDetPedidoAEmb ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_DetPedidoEmb"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_DetPedidoEmb"][] = array("fol_folio" => $row[0],
									"Cve_Articulo" => $row[1],
									"Des_Articulo" => $row[2],
									"LOTE" => $row[3],
									"Cve_CajaMix" => $row[4],
									"Guia" => $row[5],
									"Cantidad" => $row[6],
									"Peso" => $row[7]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameDatosPedidosEnEmb()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameDatosPedidosEnEmb ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'AreaEmb'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_PedidosEnEmb"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_PedidosEnEmb"][] = array("ERROR" => $row[0],
									"cve_ubicacion" => $row[1],
									"TotFolios" => $row[2],
									"TotCajas" => $row[3],
									"fol_folio" => $row[4],
									"Peso" => $row[5],
									"Cajas" => $row[6]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDamePedidosEmb()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DamePedidosEmb ('"
                . $MiSql[$i]->{'Almacen'} . "',"
                . $MiSql[$i]->{'IdEmb'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_PedidoEmbarque"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_PedidoEmbarque"][] = array("Error" => $row[0],
									"Msg" => $row[1],
									"Cliente" => $row[2],
									"Fol_Folio" => $row[3],
									"TotCajas" => $row[4],
									"Peso" => $row[5],
									"Secuencia" => $row[6],
									"Volumen" => $row[7]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONCierraUbicEmb()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CierraUbicEmb ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'AreaEmb'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CierraUbicEmb"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CierraUbicEmb"][] = array("ERROR" => $row[0]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameUbicEmbarqueOcupadas()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameUbicEmbarqueOcupadas ('"
                . $MiSql[$i]->{'Almacen'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_UbicacEmbarqueOcupadas"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_UbicacEmbarqueOcupadas"][] = array("ERROR" => $row[0],
									"cve_ubicacion" => $row[1],
									"Descripcion" => $row[2],
									"largo" => $row[3],
									"ancho" => $row[4],
									"alto" => $row[5],
									"VolumenUbic" => $row[6],
									"VolumenPed" => $row[7]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONCreaEmbarque()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CreaEmbarque ('"
				. $MiSql[$i]->{'Almacen'} . "','"
                . $this->user . "','"
                . $MiSql[$i]->{'Dest'} . "','"
				. $MiSql[$i]->{'Chofer'} . "','"
				. $MiSql[$i]->{'Coments'} . "',"
				. $MiSql[$i]->{'Ruta'} . ","
				. $MiSql[$i]->{'Transporte'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["TH_Embarque"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["TH_Embarque"][] = array("Error" => $row[0],
									"Id_Embarque" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameEmbarques()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameEmbarques ('"
                . $MiSql[$i]->{'Almacen'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["TH_Embarque"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["TH_Embarque"][] = array("Error" => $row[0],
									"Id_Embarque" => $row[1],
									"destino" => $row[2],
									"comentarios" => $row[3],
									"embarcador" => $row[4],
									"Transporte" => $row[5],
									"cve_ruta" => $row[6]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameEmbarquePendiente()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameEmbarquePendiente ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Libre'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["TH_Embarque"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_Embarque"][] = array("Error" => $row[0],
									"Id_Embarque" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameDatosEmbarque()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameDatosEmbarque ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'AreaEmb'} . "','"
				. $this->user . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_DatosEmb"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_DatosEmb"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Total_Pedidos" => $row[2],
									"Pedidos_Pend_Embarque" => $row[3],
									"Total_Guias" => $row[4],
									"Guias_Pend_Embarque" => $row[5]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameDatosEmbarqueP()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameDatosEmbarqueP ('"
                . $MiSql[$i]->{'Almacen'} . "',"
                . $MiSql[$i]->{'IdEmb'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_DatosEmb"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_DatosEmb"][] = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"Total_Pedidos" => $row[2],
									"Pedidos_Pend_Embarque" => $row[3],
									"Total_Guias" => $row[4],
									"Guias_Pend_Embarque" => $row[5],
									"Total_Peso" => $row[6]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONAsignaPedidoEmb()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_AsignaPedidoEmb ('"
                . $MiSql[$i]->{'Almacen'} . "',"
                . $MiSql[$i]->{'IdEmb'} . ",'"
                . $MiSql[$i]->{'Folio'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_AsignaEmb"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_AsignaEmb"][] = array("ERROR" => $row[0],
									"MSG" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameCajasEmbarque()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameCajasEmbarque ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'AreaEmb'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CajasEmb"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CajasEmb"][] = array("Cve_Ubicacion" => $row[0],
									"Fol_Folio" => $row[1],
									"Sufijo" => $row[2],
									"Cve_CajaMix" => $row[3],
									"Peso" => $row[4],
									"Embarcada" => $row[5],
									"Guia" => $row[6]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONConsultaCajasEmbarque()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ConsultaCajasEmbarque ("
                . $MiSql[$i]->{'IdEmb'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["Rel_UEmbarqueCaja"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["Rel_UEmbarqueCaja"][] = array(
									"Fol_Folio" => $row[0],
									"Sufijo" => $row[1],
									"Cve_CajaMix" => $row[2],
									"Peso" => $row[3],
									"Embarcada" => $row[4],
									"Guia" => $row[5]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameCajasPedidoEmbarque()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameCajasPedidoEmbarque ('"
                . $MiSql[$i]->{'Almacen'} . "',"
                . $MiSql[$i]->{'FolEmb'} . ",'"
                . $MiSql[$i]->{'Folio'} . "','"
                . $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CajasPedEmb"][] = array("Error" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CajasPedEmb"][] = array("Error" => $row[0],
									"MSG" => $row[1],
									"Fol_Folio" => $row[2],
									"Cve_CajaMix" => $row[3],
									"Peso" => $row[4],
									"Embarcada" => $row[5],
									"Guia" => $row[6],
									"NTarima" => $row[7],
									"Cve_Contenedor" => $row[8],
									"CveLP" => $row[9]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONEmbarcaCajaMix()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_EmbarcaCajaMix ("
                . $MiSql[$i]->{'IdCajaMix'} . ",'"
                . $MiSql[$i]->{'AreaEmb'} . "','"
				. $this->user . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_EmbarcaCajaMix"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_EmbarcaCajaMix"][] = array("ERROR" => $row[0]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONEmbarcaCodCaja()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_EmbarcaCodCaja ('"
				. $MiSql[$i]->{'Codigo'} . "',"
                . $MiSql[$i]->{'IdEmb'} . ",'"
                . $MiSql[$i]->{'Folio'} . "','"
				. $this->user . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_EmbarcaCajaMix"][] = array("ERROR" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_EmbarcaCajaMix"][] = array("ERROR" => $row[0],
											"Msg" => $row[1],
											"IdContenedor" => $row[2],
											"Cve_Contenedor" => $row[3]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONLiberaUbicEmb()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_LiberaUbicEmb ('"
                . $MiSql[$i]->{'Almacen'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_LiberaUbicEmb"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_LiberaUbicEmb"][] = array("ERROR" => $row[0]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONTerminaEmbarque()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_TerminaEmbarque ("
                . $MiSql[$i]->{'IdOEmb'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_TerminaEmb"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_TerminaEmb"][] = array("Error" => $row[0]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameEmbarquesEntrega()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameEmbarquesEntrega ('"
                . $MiSql[$i]->{'Almacen'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["TH_OEmbarqueEnt"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["TH_OEmbarqueEnt"][] = array("Error" => $row[0],
									"Id_Embarque" => $row[1],
									"destino" => $row[2],
									"comentarios" => $row[3],
									"embarcador" => $row[4],
									"Transporte" => $row[5],
									"cve_ruta" => $row[6]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameEmbEntXRutaTrans()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameEmbEntregaPorRuta ('"
                . $MiSql[$i]->{'Almacen'} . "','"
				. $MiSql[$i]->{'Transporte'} . "','"
				. $MiSql[$i]->{'Ruta'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["TH_OEmbarqueEnt"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["TH_OEmbarqueEnt"][] = array("Error" => $row[0],
									"Msg" => $row[1],
									"Id_Embarque" => $row[2],
									"destino" => $row[3],
									"comentarios" => $row[4],
									"embarcador" => $row[5],
									"Transporte" => $row[6],
									"cve_ruta" => $row[7]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameDameDetalleEmbarqueE()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameDetalleEmbarqueE ('"
                . $MiSql[$i]->{'Almacen'} . "',"
				. $MiSql[$i]->{'IdEmbarque'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["TD_OEmbarqueEnt"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["TD_OEmbarqueEnt"][] = array("Error" => $row[0],
									"Msg" => $row[1],
									"Id_Embarque" => $row[2],
									"Fol_Folio" => $row[3],
									"Orden_Stop" => $row[4],
									"Cve_clte" => $row[5],
									"Cve_CteProv" => $row[6],
									"RazonSocial" => $row[7],
									"Direccion" => $row[8],
									"Colonia" => $row[9],
									"Ciudad" => $row[10],
									"CP" => $row[11],
									"Estado" => $row[12],
									"Telefono" => $row[13],
									"Latitud" => $row[14],
									"Longitud" => $row[15],
									"Total_Cajas_Pedido" => $row[16],
									"Total_Pedidos" => $row[17],
									"Total_Guias" => $row[18],
									"Total_Peso" => $row[19],
									"BanEntregado" => $row[20]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONAgregaFotoEmbarque()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
			$Foto=addslashes(file_get_contents("../".$MiSql[$i]->{'Archivo'}));
            $sql = "Call SPWS_AgregaFotoEmbarque ("
                . $MiSql[$i]->{'Folio'} . ",'"
				. $MiSql[$i]->{'Pedido'} . "','"
				. $MiSql[$i]->{'Archivo'} . "','"
				. $MiSql[$i]->{'Descrip'} . "','"
				. $MiSql[$i]->{'Tipo'} . "','"
				. $Foto . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_AgregaFotoEmbarque"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_AgregaFotoEmbarque"][] = array("ERROR" => $row[0],
										"MSG" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONEliminaFotoEmbarque()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_EliminaFotoEmbarque ("
                . $MiSql[$i]->{'Folio'} . ",'"
				. $MiSql[$i]->{'Archivo'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_EliminaFotoEmbarque"][] = array("ERROR" => -1,
										"MSG" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_EliminaFotoEmbarque"][] = array("ERROR" => $row[0],
										"MSG" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameCajasOrdenEmbarqueEnt()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameCajasOrdenEmbarqueEnt ('"
                . $MiSql[$i]->{'Almacen'} . "',"
				. $MiSql[$i]->{'IdEmbarque'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["TD_OEmbarqueEnt"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["TD_OEmbarqueEnt"][] = array("Error" => $row[0],
									"Fol_Folio" => $row[1],
									"Cve_CajaMix" => $row[2],
									"Peso" => $row[3],
									"Embarcada" => $row[4],
									"Guia" => $row[5]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONTerminaEntrega()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_TerminaEntrega ("
                . $MiSql[$i]->{'IdOEmb'} . ",'"
				. $MiSql[$i]->{'Usuario'} . "','"
				. $MiSql[$i]->{'Folio'} . "','"
				. $MiSql[$i]->{'Recibe'} . "','"
				. $MiSql[$i]->{'Fec_Ent'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CierraEmbarqueEnt"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CierraEmbarqueEnt"][] = array("Error" => $row[0]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameOTPendientes()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameOrdenProdPend ('"
                . $MiSql[$i]->{'Almacen'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["TH_Produccion"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["TH_Produccion"][] = array("Error" => $row[0],
									"Msg" => $row[1],
									"Folio_Pro" => $row[2],
									"Cve_Articulo" => $row[3],
									"Cve_Lote" => $row[4],
									"Caducidad" => $row[5],
									"Cantidad" => $row[6],
									"CveLP" => $row[7],
									"clave_contenedor" => $row[8],
									"IDContenedor" => $row[9],
									"Cant_Tarima" => $row[10],
									"Referencia" => $row[11]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameCantOrdenProd()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameCantOrdenProd ('"
                . $MiSql[$i]->{'Folio'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["TH_Produccion"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["TH_Produccion"][] = array("Error" => $row[0],
									"Msg" => $row[1],
									"Solicitada" => $row[2],
									"Producida" => $row[3],
									"Faltante" => $row[4]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameCantDetOrdenProd()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameCantDetOrdenProd ('"
                . $MiSql[$i]->{'Folio'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["TD_Produccion"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["TD_Produccion"][] = array("Error" => $row[0],
									"Msg" => $row[1],
									"Folio_Pro" => $row[2],
									"Cve_Articulo" => $row[3],
									"Des_Articulo" => $row[4],
									"Cve_Lote" => $row[5],
									"Caducidad" => $row[6],
									"Cantidad" => $row[7],
									"Solicitada" => $row[8],
									"Consumida" => $row[9],
									"Faltante" => $row[10]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONActualizaCantOrdenProd()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ActualizaCantOrdenProd ('"
                . $MiSql[$i]->{'Folio'} . "','"
                . $MiSql[$i]->{'Usuario'} . "',"
                . $MiSql[$i]->{'Tarima'} . ","
                . $MiSql[$i]->{'Ubica'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["TH_Produccion"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["TH_Produccion"][] = array("Error" => $row[0],
									"Msg" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONReporteThPedido()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ReporteThPedido ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["Th_Pedido"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["Th_Pedido"][] = array("Fol_Folio" => $row[0],
											"Cve_clte" => $row[1],
											"RazonSocialR" => $row[2],
											"DireccionR" => $row[3],
											"ColoniaR" => $row[4],
											"CiudadR" => $row[5],
											"EstadoR" => $row[6],
											"PostalR" => $row[7],
											"RazonSocialD" => $row[8],
											"DireccionD" => $row[9],
											"ColoniaD" => $row[10],
											"CiudadD" => $row[11],
											"EstadoD" => $row[12],
											"postalD" => $row[13],
											"Orden_Compra" => $row[14]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONReporteTdPedido()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ReporteTdPedido ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Folio'} . "',"
                . $MiSql[$i]->{'Isufijo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["Td_Pedido"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["Td_Pedido"][] = array("Fol_Folio" => $row[0],
											"Cve_CajaMix" => $row[1],
											"NCaja" => $row[2],
											"Cve_articulo" => $row[3],
											"des_articulo" => $row[4],
											"Cve_Lote" => $row[5],
											"Cantidad" => $row[6],
											"Item_No" => $row[7],
											"Comentario" => $row[8],
											"TotPzs" => $row[9]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameInventarioFisico()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameInventarioFisico ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["Th_inventario"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["Th_inventario"][] = array("Error" => $row[0],
											"Msg" => utf8_encode($row[1]),
											"Id_Inventario" => $row[2],
											"Descripcion" => utf8_encode($row[3]),
											"NConteo" => $row[4]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameInventarioInicial()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameInventarioInicial ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["Th_inventario"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["Th_inventario"][] = array("Error" => $row[0],
											"Msg" => $row[1],
											"Id_Inventario" => $row[2],
											"Descripcion" => $row[3],
											"NConteo" => $row[4],
											"Ubicacion" => $row[5],
											"CodigoBL" => $row[6]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameInventarioCiclico()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameInventarioCiclico ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if (!$result) {
    			$_arr["Th_inventario"][] = array("Error" => -1,
											"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["Th_inventario"][] = array("Error" => $row[0],
											"Msg" => utf8_encode($row[1]),
											"Id_Inventario" => $row[2],
											"Articulo" => $row[3],
											"Descripcion" => utf8_encode($row[4]),
											"NConteo" => $row[5],
											"Ubicacion" => $row[6],
											"CodigoBL" => $row[7]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONCreaConteoInventario()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CreaConteoInventario ("
                . $MiSql[$i]->{'Inventario'} . ",'"
                . $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ConteoInventario"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ConteoInventario"][] = array("Error" => $row[0],
											"Msg" => $row[1],
											"NConteo" => $row[2]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONCreaConteoInventarioCiclico()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CreaConteoInvCiclico ("
                . $MiSql[$i]->{'Inventario'} . ",'"
                . $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ConteoInventario"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ConteoInventario"][] = array("Error" => $row[0],
											"Msg" => $row[1],
											"NConteo" => $row[2]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONCreaConteoInventarioUbica()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CreaConteoInventarioUbica ("
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Ubicacion'} . ",'"
                . $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ConteoInventario"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ConteoInventario"][] = array("Error" => $row[0],
											"Msg" => $row[1],
											"NConteo" => $row[2]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONCreaConteoInvCiclicoUbica()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CreaConteoInvCiclicoUbica ("
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Ubicacion'} . ",'"
                . $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ConteoInventario"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ConteoInventario"][] = array("Error" => $row[0],
											"Msg" => $row[1],
											"NConteo" => $row[2]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameUltimaUbicacionInvFis()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameUltimaUbicacionInvFis ("
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Conteo'} . ",'"
                . $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_UltimaUbicInv"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_UltimaUbicInv"][] = array("Error" => $row[0],
											"Idy_ubica" => $row[1],
											"CodigoBL" => $row[2]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameUltimaUbicacionInvCic()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameUltimaUbicacionInvCic ("
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Conteo'} . ",'"
                . $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_UltimaUbicInv"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_UltimaUbicInv"][] = array("Error" => $row[0],
											"Idy_ubica" => $row[1],
											"CodigoBL" => $row[2]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameUltimoArticuloInvFis()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameUltimoArticuloInvFis ("
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Conteo'} . ","
                . $MiSql[$i]->{'Ubica'} . ",'"
                . $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_UltimoArtInv"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_UltimoArtInv"][] = array("Error" => $row[0],
											"Cve_Articulo" => $row[1],
											"Des_Articulo" => $row[2],
											"NTarima" => $row[3],
											"Cve_Contenedor" => $row[4],
											"Des_Contenedor" => $row[5]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONValidaUbicacionInvFisSinCont()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ValidaUbicacionInvFisSinCont ("
                . $MiSql[$i]->{'Inventario'} . ",'"
                . $MiSql[$i]->{'Ubicacion'} . "',"
                . $MiSql[$i]->{'UbicAnterior'} . ",'"
                . $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ValidaUbicInv"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ValidaUbicInv"][] = array("Error" => $row[0],
											"Msg" => $row[1],
											"IDU" => $row[2],
											"ConteoA" => $row[3],
											"IConteo" => $row[4]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONValidaUbicacionInvCicSinCont()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ValidaUbicacionInvCicSinCont ("
                . $MiSql[$i]->{'Inventario'} . ",'"
                . $MiSql[$i]->{'Ubicacion'} . "',"
                . $MiSql[$i]->{'UbicAnterior'} . ",'"
                . $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ValidaUbicInv"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ValidaUbicInv"][] = array("Error" => $row[0],
											"Msg" => $row[1],
											"IDU" => $row[2],
											"ConteoA" => $row[3],
											"IConteo" => $row[4]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONValidaUbicacionInvFis()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ValidaUbicacionInvFis ("
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Conteo'} . ",'"
                . $MiSql[$i]->{'Ubicacion'} . "',"
                . $MiSql[$i]->{'UbicAnterior'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ValidaUbicInv"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ValidaUbicInv"][] = array("Error" => $row[0],
											"Msg" => $row[1],
											"IDU" => $row[2]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONValidaUbicacionInvCic()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ValidaUbicacionInvCic ("
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Conteo'} . ",'"
                . $MiSql[$i]->{'Ubicacion'} . "',"
                . $MiSql[$i]->{'UbicAnterior'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ValidaUbicInv"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ValidaUbicInv"][] = array("Error" => $row[0],
											"Msg" => $row[1],
											"IDU" => $row[2]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONValidaContenedorInvFis()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ValidaContenedorInvFis ("
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Conteo'} . ","
                . $MiSql[$i]->{'Ubicacion'} . ","
                . $MiSql[$i]->{'IdTarima'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ValidaUbicInv"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ValidaUbicInv"][] = array("Error" => $row[0],
											"Msg" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONValidaContenedorInvCic()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ValidaContenedorInvCic ("
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Conteo'} . ","
                . $MiSql[$i]->{'Ubicacion'} . ","
                . $MiSql[$i]->{'IdTarima'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ValidaUbicInv"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ValidaUbicInv"][] = array("Error" => $row[0],
											"Msg" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONValidaCodigoCajaInvFis()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ValidaCodigoCajaInvFis ('"
                . $MiSql[$i]->{'Almacen'} . "','"
                . $MiSql[$i]->{'barrasCaja'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ValidaCajaInv"][] = array("ERROR" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ValidaCajaInv"][] = array("ERROR" => $row[0],
											"Msg" => $row[1],
											"CveArticulo" => $row[2],
											"PzsXCaja" => $row[3],
											"Lote" => $row[4],
											"Nombre" => $row[5],
											"CADUCIDAD" => $row[6],
											"Ban_Serie" => $row[7],
											"unidadMedida" => $row[8],
											"UniMedEq" => $row[9],
											"IdTarima" => $row[10],
											"Cve_Tarima" => $row[11],
											"DesTarima" => $row[12],
											"LP" => $row[13]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONValidaCodigoCajaInvFis1()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ValidaCodigoCajaInvFis1 ('"
                . $MiSql[$i]->{'Almacen'} . "',"
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Conteo'} . ","
                . $MiSql[$i]->{'Ubica'} . ",'"
                . $MiSql[$i]->{'barrasCaja'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ValidaCajaInv"][] = array("ERROR" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ValidaCajaInv"][] = array("ERROR" => $row[0],
											"Msg" => utf8_encode($row[1]),
											"CveArticulo" => $row[2],
											"PzsXCaja" => $row[3],
											"Lote" => utf8_encode($row[4]),
											"Nombre" => utf8_encode($row[5]),
											"CADUCIDAD" => $row[6],
											"Ban_Serie" => $row[7],
											"unidadMedida" => $row[8],
											"UniMedEq" => $row[9],
											"IdTarima" => $row[10],
											"Cve_Tarima" => $row[11],
											"DesTarima" => $row[12],
											"LP" => $row[13],
											"LoteProv" => $row[14],
											"IdProv" => $row[15],
											"Proveedor" => $row[16],
											"Inventario" => $row[17],
											"Ubica" => $row[18],
											"TipoTarima" => $row[19],
											"IdCaja" => $row[20],
											"CveCaja" => $row[21],
											"LPCaja" => $row[22]);

			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONValidaCodigoCajaInvCic()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ValidaCodigoCajaInvCic ('"
                . $MiSql[$i]->{'Almacen'} . "',"
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Conteo'} . ",'"
                . $MiSql[$i]->{'barrasCaja'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ValidaCajaInv"][] = array("ERROR" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ValidaCajaInv"][] = array("ERROR" => $row[0],
											"Msg" => $row[1],
											"CveArticulo" => $row[2],
											"PzsXCaja" => $row[3],
											"Lote" => $row[4],
											"Nombre" => $row[5],
											"CADUCIDAD" => $row[6],
											"Ban_Serie" => $row[7],
											"unidadMedida" => $row[8],
											"UniMedEq" => $row[9],
											"IdTarima" => $row[10],
											"Cve_Tarima" => $row[11],
											"DesTarima" => $row[12],
											"LP" => $row[13]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONValidaProdCajaInvFis()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ValidaProdCajaInvFis ("
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Conteo'} . ","
                . $MiSql[$i]->{'Ubicacion'} . ","
                . $MiSql[$i]->{'IdTarima'} . ","
                . $MiSql[$i]->{'IdCja'} . ",'"
                . $MiSql[$i]->{'barrasCaja'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_ValidaProdCajaInv"][] = array("Erro" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_ValidaProdCajaInv"][] = array("Error" => $row[0],
											"Msg" => $row[1],
											"CveArticulo" => $row[2],
											"DesArticulo" => utf8_encode($row[3]),
											"PzsXCaja" => $row[4],
											"unidadMedida" => $row[5],
											"UniMedEq" => $row[6],
											"Ban_Lote" => $row[7],
											"Ban_Serie" => $row[8]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONInvFUbicacionVacia()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_InvFUbicacionVacia ("
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Conteo'} . ","
                . $MiSql[$i]->{'Ubica'} . ",'"
                . $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_UbicInvVacia"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_UbicInvVacia"][] = array("Error" => $row[0]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONInvCUbicacionVacia()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_InvCUbicacionVacia ("
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Conteo'} . ","
                . $MiSql[$i]->{'Ubica'} . ",'"
                . $MiSql[$i]->{'Usuario'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_UbicInvVacia"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_UbicInvVacia"][] = array("Error" => $row[0]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONAgregaInventarioFisico()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_AgregaInventarioFisico ("
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Conteo'} . ","
                . $MiSql[$i]->{'Ubica'} . ",'"
                . $MiSql[$i]->{'Articulo'} . "','"
                . $MiSql[$i]->{'ILote'} . "',"
                . $MiSql[$i]->{'PzsXCaja'} . ","
                . $MiSql[$i]->{'ICantidad'} . ",'"
                . $MiSql[$i]->{'Usuario'} . "',"
                . $MiSql[$i]->{'Tarima'} . ",'"
                . $MiSql[$i]->{'FCaduca'} . "',"
                . $MiSql[$i]->{'IProv'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_UbicInvVacia"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_UbicInvVacia"][] = array("Error" => $row[0],
											"Msg" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONAgregaInventarioCiclico()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_AgregaInventarioCiclico ("
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Conteo'} . ","
                . $MiSql[$i]->{'Ubica'} . ",'"
                . $MiSql[$i]->{'Articulo'} . "','"
                . $MiSql[$i]->{'ILote'} . "',"
                . $MiSql[$i]->{'PzsXCaja'} . ","
                . $MiSql[$i]->{'ICantidad'} . ",'"
                . $MiSql[$i]->{'Usuario'} . "',"
                . $MiSql[$i]->{'Tarima'} . ",'"
                . $MiSql[$i]->{'FCaduca'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_UbicInvVacia"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_UbicInvVacia"][] = array("Error" => $row[0],
											"Msg" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONCierraUbicacionInventario()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CierraUbicacionInventario ("
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Conteo'} . ","
                . $MiSql[$i]->{'Ubica'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CierraUbicInv"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CierraUbicInv"][] = array("Error" => $row[0],
											"Msg" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONCierraUbicacionInvCic()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CierraUbicacionInvCic ("
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Conteo'} . ","
                . $MiSql[$i]->{'Ubica'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CierraUbicInv"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CierraUbicInv"][] = array("Error" => $row[0],
											"Msg" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameDatosInventarioF()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameDatosInventarioF ("
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Conteo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_InvFisico"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_InvFisico"][] = array("Error" => $row[0],
											"Msg" => $row[1],
											"Id_Inventario" => $row[2],
											"NConteo" => $row[3],
											"Idy_Ubica" => $row[4],
											"CodigoBL" => $row[5],
											"Cve_Articulo" => $row[6],
											"Des_Articulo" => $row[7],
											"Lote_Serie" => $row[8],
											"Cantidad" => $row[9],
											"Fecha" => $row[10],
											"Status" => $row[11],
											"Vacia" => $row[12],
											"CveLP" => $row[13]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameDatosInventarioC()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameDatosInventarioC ("
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Conteo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_InvFisico"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_InvFisico"][] = array("Error" => $row[0],
											"Msg" => $row[1],
											"Id_Inventario" => $row[2],
											"NConteo" => $row[3],
											"Idy_Ubica" => $row[4],
											"CodigoBL" => $row[5],
											"Cve_Articulo" => $row[6],
											"Des_Articulo" => $row[7],
											"Lote_Serie" => $row[8],
											"Cantidad" => $row[9],
											"Fecha" => $row[10],
											"Status" => $row[11],
											"Vacia" => $row[12],
											"CveLP" => $row[13]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameDatosUbicInventarioF()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameDatosUbicInventarioF ("
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Conteo'} . ","
                . $MiSql[$i]->{'Ubicacion'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_NoInvFis"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_NoInvFis"][] = array("Error" => $row[0],
											"Msg" => $row[1],
											"Id_Inventario" => $row[2],
											"NConteo" => $row[3],
											"Idy_Ubica" => $row[4],
											"CodigoBL" => $row[5],
											"Cve_Articulo" => $row[6],
											"Des_Articulo" => $row[7],
											"Lote_Serie" => $row[8],
											"Cantidad" => $row[9]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameDatosUbicInventarioC()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameDatosUbicInventarioC ("
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Conteo'} . ","
                . $MiSql[$i]->{'Ubicacion'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_NoInvFis"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_NoInvFis"][] = array("Error" => $row[0],
											"Msg" => utf8_encode($row[1]),
											"Id_Inventario" => $row[2],
											"NConteo" => $row[3],
											"Idy_Ubica" => $row[4],
											"CodigoBL" => $row[5],
											"Cve_Articulo" => $row[6],
											"Des_Articulo" => utf8_encode($row[7]),
											"Lote_Serie" => $row[8],
											"Cantidad" => $row[9]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameUbicInvCiclico()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameUbicInvCiclico ("
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Conteo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_NoInvFis"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_NoInvFis"][] = array("Error" => $row[0],
											"Msg" => utf8_encode($row[1]),
											"Id_Inventario" => $row[2],
											"NConteo" => $row[3],
											"Idy_Ubica" => $row[4],
											"CodigoBL" => $row[5],
											"Cve_Articulo" => $row[6],
											"Des_Articulo" => utf8_encode($row[7]),
											"Cantidad" => $row[8]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONCierraConteoInventario()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CierraConteoInventario ("
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Conteo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CierraUbicInv"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CierraUbicInv"][] = array("Error" => $row[0],
											"Msg" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONCierraConteoInvCiclico()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CierraConteoInvCiclico ("
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Conteo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CierraUbicInv"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CierraUbicInv"][] = array("Error" => $row[0],
											"Msg" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONCierraUbicacionesVacias()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CierraUbicacionesVacias ("
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Conteo'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CierraUbicInv"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CierraUbicInv"][] = array("Error" => $row[0],
											"Msg" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONCierraInventario()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CierraInventario ("
                . $MiSql[$i]->{'Inventario'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CierraInv"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CierraInv"][] = array("Error" => $row[0],
											"Msg" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONCierraInventarioC()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_CierraInventarioC ("
                . $MiSql[$i]->{'Inventario'} . ");";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_CierraInv"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_CierraInv"][] = array("Error" => $row[0],
											"Msg" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

    function Sql2JSONAplicaInventarioI()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "CALL SPWS_AplicaInventarioI ("
                . $MiSql[$i]->{'Inventario'} . ",'"
                . $this->user . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_AplicaInventarioI"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_AplicaInventarioI"][] = array("Error" => $row[0],
									"Msg" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONAgregaUsuarioConteoInventario()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_AgregaUsuarioConteoInventario ("
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Conteo'} . ",'"
                . $this->user . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_AgregaUsuarioInv"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_AgregaUsuarioInv"][] = array("Error" => $row[0],
											"Msg" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONAgregaUsuarioConteoInvCiclico()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_AgregaUsuarioConteoInvCiclico ("
                . $MiSql[$i]->{'Inventario'} . ","
                . $MiSql[$i]->{'Conteo'} . ",'"
                . $this->user . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_AgregaUsuarioInv"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_AgregaUsuarioInv"][] = array("Error" => $row[0],
											"Msg" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONDameCadenasJSON()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_DameCadenasJSON ();";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_Log_WS"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_Log_WS"][] = array("Error" => $row[0],
											"Msg" => '',
											"Id" => $row[1],
											"Referencia" => $row[2],
											"Mensaje" => $row[3]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONActualizaCadenaJSON()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_ActualizaCadenaJSON ("
                . $MiSql[$i]->{'IdCad'} . ",'"
                . $MiSql[$i]->{'Resp'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_Log_WS"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_Log_WS"][] = array("Error" => $row[0],
											"Msg" => '');
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONGuardaLogWS()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_GuardaLogWS ('"
                . $MiSql[$i]->{'Referencia'} . "','"
                . $MiSql[$i]->{'Mensaje'} . "','"
                . $MiSql[$i]->{'Respuesta'} . "',"
                . $MiSql[$i]->{'Enviado'} . ",'"
                . $MiSql[$i]->{'Proceso'} . "','"
                . $MiSql[$i]->{'Dispositivo'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_GuardaLogWS"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_GuardaLogWS"][] = array("Error" => $row[0],
											"Msg" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONGuardaLogSAP()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
            $sql = "Call SPWS_GuardaLogSAP ('"
                . $MiSql[$i]->{'CadenaJSON'} . "','"
                . $MiSql[$i]->{'Response'} . "','"
                . $MiSql[$i]->{'ModuloWMS'} . "','"
                . $MiSql[$i]->{'F_Folio'} . "','"
                . $MiSql[$i]->{'FuncionSAP'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_GuardaLogWS"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$_arr["T_GuardaLogWS"][] = array("Error" => $row[0],
											"Msg" => $row[1]);
			}
            $i++;
			$conn->close();
        }
        return $_arr;
    }

	function Sql2JSONMovsAlmacE()
    {
        $conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
			$folio_ot=$MiSql[$i]->{'Referencia'};
            $sql = "Call SPWS_DameCabeceraMovAlmacen ('"
                . $MiSql[$i]->{'Referencia'} . "','"
                . $MiSql[$i]->{'cve_almac'} . "');";
            $result = $conn->query($sql);
			$_arr = array();
			if(!$result)
			{
				$_arr["T_AgregaUsuarioInv"][] = array("Error" => -1,
										"Msg" => utf8_encode($conn->error));
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$connD =  new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
				$sql = "Call SPWS_DameDetalleMovAlmacen ('"
					. $MiSql[$i]->{'Referencia'} . "','"
					. $MiSql[$i]->{'cve_almac'} . "');";
				$resultD = $connD->query($sql);
				$_arrd= array();
				while ($rowD = $resultD->fetch_array(MYSQLI_NUM)) {
					$connL =  new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
					$sql = "Call SPWS_DameLoteMovAlmacen ('"
						. $MiSql[$i]->{'Referencia'} . "','"
						. $MiSql[$i]->{'cve_almac'} . "','"
						. $rowD[3] . "');";
					$resultL = $connL->query($sql);
					$_arrl= array();
					while ($rowL = $resultL->fetch_array(MYSQLI_NUM)) {
						$_arrl[] = array("BatchNumber" => $rowL[0],
									"Quantity" => $rowL[1],
									"ExpiryDate" => $rowL[2]);
					}
					$_arrd[] = array("BaseEntry" => $rowD[0],
								"BaseType" => $rowD[1],
								"BaseLine" => $rowD[2],
								"ItemCode" => $rowD[3],
								"Quantity" => $rowD[4],
								"WarehouseCode" => $rowD[5],
								"BatchNumbers" => $_arrl);
					$connL->close();
				}
				$empresa = $row[6];
				$_arr[] = array("DocDate" => $row[0],
								"DocDueDate" => $row[1],
								"CardCode" => $row[5],
								"DocType" => "dDocument_Items",
								"DocumentLines" => $_arrd);
				$connD->close();
			}
            $i++;
        }
		// Envía las peticiones
		$Json = json_encode($_arr);
		// Logueo al Services Layer
		$connS = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$sql = "SELECT * FROM c_datos_sap WHERE Empresa = (SELECT cve_proveedor FROM c_proveedores WHERE ID_Proveedor = (SELECT ID_Proveedor FROM t_ordenprod WHERE Folio_Pro = '{$folio_ot}')) AND Activo = 1;";
		if (!($res = mysqli_query($conn, $sql))){
			echo "Falló la preparación 1: (" . mysqli_error($conn) . ") ";
		}
		$row = $res->fetch_array(MYSQLI_NUM);
		$endPoint = $row['Url'].$funcion;
		$usuario  = $row['User'];
		$password = $row['Pswd'];
		$BD       = $row['BaseD'];
		$this->url = $this->url . "Login";
		if ($empresa === $this->SAP_Empresa_1) {
			$_arr1[] = array("CompanyDB" => $this->SAP_DB_1,
						"Password" => $this->SAP_pswd,
						"UserName" => $this->SAP_Usr);
			//$this->CAD_Json = '{"CompanyDB":"' . $this->SAP_DB_1 . '","Password":"' . $this->SAP_pswd . '","UserName":"' . $this->SAP_Usr . '"}';
		} else {
			$_arr1[] = array("CompanyDB" => $this->SAP_DB_2,
						"Password" => $this->SAP_pswd,
						"UserName" => $this->SAP_Usr);
			//$this->CAD_Json = '{"CompanyDB":"' . $this->SAP_DB_2 . '","Password":"' . $this->SAP_pswd . '","UserName":"' . $this->SAP_Usr . '"}';
		}
		$this->CAD_Json = json_encode($_arr1);
		$resultado = $this -> sendPost();
		$conn->close();
        return $_arr;
    }

	function Sql2JSONDameSurtOT()
    {
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
			$folio_ot=$MiSql[$i]->{'Folio'};
			// Logueo al Services Layer
			$connS = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
			$sql = "SELECT cve_proveedor FROM c_proveedores WHERE ID_Proveedor = (SELECT ID_Proveedor FROM t_ordenprod WHERE Folio_Pro = '{$folio_ot}');";
			if (!($res = mysqli_query($connS, $sql))){
				echo "Falló la preparación 1: (" . mysqli_error($conn) . ") ";
			}
			$row =  mysqli_fetch_array($res);
			$Empresa_P = $row['0'];
			$connS->close();
			$resultado = array();
			$_arr = array();
			$resultado = (array)$this -> ConectarSAP($Empresa_P, 'Login');
			if (!isset($resultado)) {
				$_arr["error"][] = array("code" => -1,
										"message" => "Sin conexion a SAP");
				$connS->close();
				return $_arr;
			}
			if (isset($this->errorSAP)) {
				return $this->errorSAP;
			}
			if ($this->SessionId==="") {
				$_arr["error"][] = array("code" => -1,
										"message" => "Sin conexion a SAP");
				return $_arr;
			}
			$IdSession=$this->SessionId;
			$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
            $sql = "Call SPWS_DameCabeceraSurtOT ('"
                . $MiSql[$i]->{'Folio'} . "','"
                . $MiSql[$i]->{'cve_almac'} . "');";
            $result = $conn->query($sql);
			if(!$result)
			{
				$_arr["error"][] = array("code" => -1,
										"message" => utf8_encode($conn->error));
				$conn->close();
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$connD =  new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
				$sql = "Call SPWS_DameDetalleSurtOT ('"
					. $MiSql[$i]->{'Folio'} . "','"
					. $MiSql[$i]->{'cve_almac'} . "');";
				$resultD = $connD->query($sql);
				$_arrd= array();
				while ($rowD = $resultD->fetch_array(MYSQLI_NUM)) {
					if($rowD[6]==='S') {
						$connL =  new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
						$sql = "Call SPWS_DameLoteSurtOT ('"
							. $MiSql[$i]->{'Folio'} . "','"
							. $MiSql[$i]->{'cve_almac'} . "','"
							. $rowD[5] . "','"
							. $rowD[3] . "');";
						$resultL = $connL->query($sql);
						$_arrl= array();
						while ($rowL = $resultL->fetch_array(MYSQLI_NUM)) {
							$_arrl[] = array("BatchNumber" => $rowL[0],
										"Quantity" => $rowL[1],
										"ExpiryDate" => $rowL[2]);
						}
						$_arrd[] = array("BaseEntry" => $rowD[0],
									"BaseType" => $rowD[1],
									"Quantity" => $rowD[2],
									"BaseLine" => $rowD[3],
									"WarehouseCode" => $rowD[4],
									"BatchNumbers" => $_arrl);
						$connL->close();
					}
					else {
						$_arrd[] = array("BaseEntry" => $rowD[0],
									"BaseType" => $rowD[1],
									"Quantity" => $rowD[2],
									"BaseLine" => $rowD[3],
									"WarehouseCode" => $rowD[4]);
					}
				}
				$empresa = $row[6];
				$_arr = array("DocDate" => $row[0],
								"DocDueDate" => $row[1],
								"DocType" => $row[2],
								"DocumentLines" => $_arrd);
				$connD->close();
			}
            $i++;
        }
		// Envía las peticiones
		$connS->close();
		$this->CAD_Json = json_encode($_arr);
		$resultado = $this -> sendPost($this->SAP_URL.'InventoryGenExits');
		$TextoMsg = json_encode($resultado);
		$Ruta="Response_SAP/".$folio_ot."_".strftime("%Y%m%d_%H%M%S",time()).".txt";
		$File = fopen($Ruta,"w");
		fwrite($File,$TextoMsg);
		fclose($File);
		$arrT = array();
		$arrT = (array)$resultado;
		if (IsSet($arrT['error'])){
			$arrE = array();
			$arrE = (array)$arrT["error"];
			$arrM = array();
			$arrM = (array)$arrE["message"];
			$_arr1["error"][] = array("code" => -1,
									"message" => $arrM["value"]);
			$connE =  new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
			$sql = "Call SPWS_GuardaLogWS ('"
				. $folio_ot . "','"
				. $arrM["value"] . "');";
			$resultE = $connE->query($sql);
			return $_arr1;
		}
		$connA =  new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$sql = "Update th_pedido Set Enviado=1 Where Fol_Folio='"
			. $folio_ot . "';";
		$resultA = $connA->query($sql);
		$connA->close();
        return $resultado;
    }

	function Sql2JSONDameSurtOV()
    {
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
			$folio_ot=$MiSql[$i]->{'Folio'};
			// Logueo al Services Layer
			$connS = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
			$sql = "Select P.cve_proveedor From th_pedido E Join c_proveedores P On E.Pick_Num=P.ID_Proveedor Where E.Fol_Folio = '{$folio_ot}';";
			if (!($res = mysqli_query($connS, $sql))){
				echo "Falló la preparación 1: (" . mysqli_error($conn) . ") ";
			}
			$row =  mysqli_fetch_array($res);
			$Empresa_P = $row['0'];
			$connS->close();
			$resultado = array();
			$_arr = array();
			$resultado = (array)$this -> ConectarSAP($Empresa_P, 'Login');
			if (!isset($resultado)) {
				$_arr["error"][] = array("code" => -1,
										"message" => "Sin conexion a SAP");
				return $_arr;
			}
			if (isset($this->errorSAP)) {
				return $this->errorSAP;
			}
			if ($this->SessionId==="") {
				$_arr["error"][] = array("code" => -1,
										"message" => "Sin conexion a SAP");
				return $_arr;
			}
			$IdSession=$this->SessionId;
			$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
            $sql = "Call SPWS_DameCabeceraSurtOV ('"
                . $MiSql[$i]->{'Folio'} . "','"
                . $MiSql[$i]->{'cve_almac'} . "',"
                . $MiSql[$i]->{'ISufijo'} . ");";
            $result = $conn->query($sql);
			if(!$result)
			{
				$_arr["error"][] = array("code" => -1,
										"message" => utf8_encode($conn->error));
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$connD =  new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
				$sql = "Call SPWS_DameDetalleSurtOV ('"
					. $MiSql[$i]->{'Folio'} . "','"
					. $MiSql[$i]->{'cve_almac'} . "');";
				$resultD = $connD->query($sql);
				$_arrd= array();
				$j=0;
				while ($rowD = $resultD->fetch_array(MYSQLI_NUM)) {
					$Base_Entry=$rowD[0];
					if($rowD[6]==='S') {
						$connL =  new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
						$sql = "Call SPWS_DameLoteSurtOV ('"
							. $MiSql[$i]->{'Folio'} . "','"
							. $MiSql[$i]->{'cve_almac'} . "','"
							. $rowD[3] . "','"
							. $rowD[2] . "');";
						$resultL = $connL->query($sql);
						$_arrl= array();
						while ($rowL = $resultL->fetch_array(MYSQLI_NUM)) {
							$_arrl[] = array("BatchNumber" => $rowL[0],
										"Quantity" => $rowL[1],
										"ExpiryDate" => $rowL[2]);
						}
						$_arrd[] = array("BaseEntry" => $rowD[0],
									"BaseType" => $rowD[1],
									"BaseLine" => $j, //$rowD[2],
//									"ItemCode" => $rowD[3],
//									"Quantity" => $rowD[4],
//									"WarehouseCode" => $rowD[5],
									"BatchNumbers" => $_arrl);
						$connL->close();
					}
					else {
						$_arrd[] = array("BaseEntry" => $rowD[0],
									"BaseType" => $rowD[1],
									"BaseLine" => $j); //$rowD[2],
//									"ItemCode" => $rowD[3],
//									"WarehouseCode" => $rowD[5],
//									"Quantity" => $rowD[4]);
					}
					$j++;
				}
				$empresa = $row[6];
				$_arr = array( //"DocDate" => $row[0],
								"DocDueDate" => $row[1],
								"CardCode" => $row[2],
								"DocType" => $row[3],
								"DocumentLines" => $_arrd);
				$connD->close();
			}
            $i++;
        }
		// Envía las peticiones
		$connS->close();
		$this->CAD_Json = json_encode($_arr);
//var_dump($this->CAD_Json);
		$resultado = $this -> sendPost($this->SAP_URL.'DeliveryNotes');
		$TextoMsg = json_encode($resultado);
		$Ruta="Response_SAP/".$folio_ot."_".strftime("%Y%m%d_%H%M%S",time()).".txt";
		$File = fopen($Ruta,"w");
		fwrite($File,$TextoMsg);
		fclose($File);
		$arrT = array();
		$arrT = (array)$resultado;
		if (IsSet($arrT['error'])){
			$arrE = array();
			$arrE = (array)$arrT["error"];
			$arrM = array();
			$arrM = (array)$arrE["message"];
			$_arr1["error"][] = array("code" => -1,
									"message" => $arrM["value"]);
			$connE =  new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
			$sql = "Call SPWS_GuardaLogWS ('"
				. $folio_ot . "','"
				. $arrM["value"] . "');";
			$resultE = $connE->query($sql);
			return $_arr1;
		}
		//cierra la orden de venta
//		$resultado = $this -> sendPost($this->SAP_URL.'Orders('.$Base_Entry.')');
//		$TextoMsg = json_encode($resultado);
		$connA =  new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$sql = "Update th_pedido Set Enviado=1 Where Fol_Folio='"
			. $folio_ot . "';";
		$sql = $sql."\n"."Insert Into t_log_sap(fecha,cadena,respuesta,modulo,folio,funcion) Values(GET_DATE(),'"
			. $this->CAD_Json . "','"
			. $TextoMsg. "','Traslado entre Almacenes SAP','"
			. $folio_ot . "','StockTransfers');";
		$resultA = $connA->query($sql);
		$connA->close();
        return $resultado;
    }

	function Sql2JSONDameTrasEntreAlmacenes()
    {
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
			$Empresa_P=$MiSql[$i]->{'Empresa'};
			$Almac_Ori="";
			if(isset($MiSql[$i]->{'Almacen_Ori'})){
				$Almac_Ori=$MiSql[$i]->{'Almacen_Ori'};
			}
			// Logueo al Services Layer
			$resultado = array();
			$_arr = array();
			$resultado = (array)$this -> ConectarSAP($Empresa_P, 'Login');
			if (!isset($resultado)) {
				$_arr["error"][] = array("code" => -1,
										"message" => "Sin conexion a SAP");
				return $_arr;
			}
			if (isset($this->errorSAP)) {
				return $this->errorSAP;
			}
			if ($this->SessionId==="") {
				$_arr["error"][] = array("code" => -1,
										"message" => "Sin conexion a SAP");
				return $_arr;
			}
			$IdSession=$this->SessionId;
			$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
            $sql = "Call SPWS_DameCabeceraTraslados ('"
                . $MiSql[$i]->{'AlmacenOri'} . "','"
				. $MiSql[$i]->{'Clave'} . "','"
				. $MiSql[$i]->{'CLote'} . "');";
            $result = $conn->query($sql);
			if(!$result)
			{
				$_arr["error"][] = array("code" => -1,
										"message" => utf8_encode($conn->error));
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				$connD =  new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
				$sql = "Call SPWS_DameDetalleTraslados ('"
					. $MiSql[$i]->{'AlmacenOri'} . "','"
					. $MiSql[$i]->{'AlmacenDest'} . "','"
					. $MiSql[$i]->{'Clave'} . "','"
					. $MiSql[$i]->{'CLote'} . "',"
					. $MiSql[$i]->{'Cantidad'} . ");";
				$resultD = $connD->query($sql);
				$_arrd= array();
				while ($rowD = $resultD->fetch_array(MYSQLI_NUM)) {
					if($rowD[3]==='S') {
						$connL =  new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
						$sql = "Call SPWS_DameLoteSurtTraslados ('"
							. $MiSql[$i]->{'Clave'} . "','"
							. $rowD[0] . "','"
							. $MiSql[$i]->{'CLote'} . "',"
							. $MiSql[$i]->{'Cantidad'} . ");";
						$resultL = $connL->query($sql);
						$_arrl= array();
						while ($rowL = $resultL->fetch_array(MYSQLI_NUM)) {
							$_arrl[] = array("BatchNumber" => $rowL[0],
										"Quantity" => $rowL[1],
										"ExpiryDate" => $rowL[2]);
						}
						$_arrd[] = array("ItemCode" => $rowD[0],
									"Quantity" => $rowD[1],
									"WarehouseCode" => $rowD[2],
									"BatchNumbers" => $_arrl);
						$connL->close();
					}
					else {
						$_arrd[] = array("ItemCode" => $rowD[0],
									"Quantity" => $rowD[1],
									"WarehouseCode" => $rowD[2]);
					}
				}
				$empresa = $Empresa_P;
				if($Almac_Ori==""){
					$Almac_Ori=$row[0];
				}
				$_arr = array("FromWarehouse" => $Almac_Ori,
								"StockTransferLines" => $_arrd);
				$connD->close();
			}
            $i++;
        }
		// Envía las peticiones
		// $connS->close();
		$this->CAD_Json = json_encode($_arr);
		$resultado = $this -> sendPost($this->SAP_URL.'StockTransfers');
		$TextoMsg = json_encode($resultado);
		$Ruta="Response_SAP/".$folio_ot."_".strftime("%Y%m%d_%H%M%S",time()).".txt";
		$File = fopen($Ruta,"w");
		fwrite($File,"Request: ".$this->CAD_Json." Response: ".$TextoMsg);
		fclose($File);
		$arrT = array();
		$arrT = (array)$resultado;
		if (IsSet($arrT['error'])){
			$arrE = array();
			$arrE = (array)$arrT["error"];
			$arrM = array();
			$arrM = (array)$arrE["message"];
			$_arr1["error"][] = array("code" => -1,
									"message" => $arrM["value"]);
			return $_arr1;
		}
		$connA =  new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$sql = "Insert Into t_log_sap(fecha,cadena,respuesta,modulo,funcion) Values(GET_DATE(),'".$this->CAD_Json."','".$TextoMsg."','Traslado entre Almacenes SAP','StockTransfers');";
		$resultA = $connA->query($sql);
        return $resultado;
    }

	function Sql2JSONDameExistenciaProdSAP()
    {
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
			$Empresa_P=$MiSql[$i]->{'Empresa'};
			$Articulo=$MiSql[$i]->{'Articulo'};
			// Logueo al Services Layer
			$resultado = array();
			$_arr = array();
			$resultado = (array)$this -> ConectarSAP($Empresa_P, 'Login');
			if (!isset($resultado)) {
				$_arr["error"][] = array("code" => -1,
										"message" => "Sin conexion a SAP");
				return $_arr;
			}
			if (isset($this->errorSAP)) {
				return $this->errorSAP;
			}
			if ($this->SessionId==="") {
				$_arr["error"][] = array("code" => -1,
										"message" => "Sin conexion a SAP");
				return $_arr;
			}
			$IdSession=$this->SessionId;
			$i++;
		}
		// Envía las peticiones
		$URL=$this->SAP_URL."Items('".$Articulo."')?%24select=ItemCode%2C%20ItemWarehouseInfoCollection";
		$resultado = $this -> sendGet($URL);
		$TextoMsg = json_encode($resultado);
		/*
		$Ruta="Response_SAP/".$folio_ot."_".strftime("%Y%m%d_%H%M%S",time()).".txt";
		$File = fopen($Ruta,"w");
		fwrite($File,"Request: ".$this->CAD_Json." Response: ".$TextoMsg);
		fclose($File);
		*/
		$arrT = array();
		$arrT = (array)$resultado;
		if (IsSet($arrT['error'])){
			$arrE = array();
			$arrE = (array)$arrT["error"];
			$arrM = array();
			$arrM = (array)$arrE["message"];
			$_arr1["error"][] = array("code" => -1,
									"message" => $arrM["value"]);
			return $_arr1;
		}
		$connA =  new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
		$sql = "Insert Into t_log_sap(fecha,cadena,respuesta,modulo,funcion) Values(GET_DATE(),'".addslashes($URL)."','".$TextoMsg."','Existencia Almacenes SAP','GET');";
		$resultA = $connA->query($sql);
        return $resultado;
    }

	public function sendPost($endPoint)
	{
		$json2=$this->CAD_Json;
		$metodo   = 'POST';
		//url contra la que atacamos
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL => $endPoint,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => $metodo,
			CURLOPT_POSTFIELDS =>$json2,
			CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json',
			'Cookie: B1SESSION='.$this->SessionId.'; ROUTEID=.node2'
			),
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false
		));
		// Send the request
		$response = curl_exec($ch);
		// Se cierra el recurso CURL y se liberan los recursos del sistema
		curl_close($ch);
		if(!$response) {
			$arr["error"][] = array(   
                "code" => -1,
                "message" => "Fallo en el envio"
            );
			return $arr;
		}
		else {
			return (array)json_decode($response);
		}
	}

	public function sendGet($endPoint)
	{
		$metodo   = 'GET';
		//url contra la que atacamos
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL => $endPoint,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => $metodo,
			CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json',
			'Cookie: B1SESSION='.$this->SessionId.'; ROUTEID=.node2'
			),
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false
		));
		// Send the request
		$response = curl_exec($ch);
		// Se cierra el recurso CURL y se liberan los recursos del sistema
		curl_close($ch);
		if(!$response) {
			$arr["error"][] = array(   
                "code" => -1,
                "message" => "Fallo en el envio"
            );
			return $arr;
		}
		else {
			return (array)json_decode($response);
		}
	}

	public function ConectarSAP($Empresa, $Funcion_SAP){
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
			$folio_ot=$Empresa;
			$funcion =$Funcion_SAP;
			$endPoint = '';
			$json = '';
			$metodo   = 'POST';
			$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
			$sql = "SELECT * FROM c_datos_sap WHERE Empresa = '{$folio_ot}' AND Activo = 1;";
			if (!($res = mysqli_query($conn, $sql))){
				$arr["error"][] = array(
					"code" => -1,
					"message" => "Fallo en el envio"
				);
				return $arr;
			}
			$row = mysqli_fetch_array($res);
			$this->SAP_URL = $row['Url'];
			$endPoint = $row['Url'].$funcion;
			$usuario  = $row['User'];
			$password = $row['Pswd'];
			$BD       = $row['BaseD'];
			$BKSLSHarr = explode('\\',$usuario);
			$BKSLSHcon=Count($BKSLSHarr);
			if($BKSLSHcon === 2){
				$BKSLS = $BKSLSHarr[0].'\\\\'.$BKSLSHarr[1];
			}
			else{
				$BKSLS = $usuario;
			}
			$json = '{
				"CompanyDB": "'.$BD.'",
				"UserName": "'.$BKSLS.'",
				"Password": "'.$password.'"
				}';
			$curl = curl_init();
			curl_setopt_array($curl, array(
				CURLOPT_URL => $endPoint,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => $metodo,
				CURLOPT_POSTFIELDS => $json,
				CURLOPT_HTTPHEADER => array(
					'Content-Type: application/json',
					'Cookie: B1SESSION=e148fc02-6d94-11ec-8000-0a244a1700f3; ROUTEID=.node2'
				),
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_SSL_VERIFYPEER => false
			));
			$response = curl_exec($curl);
//var_dump($response);
			if (!$response){
				echo 'curl_error: '.curl_error($curl);
				echo '\r';
			}
			curl_close($curl);
			$Resp=json_decode($response);
			$_arr=array();
			$_arr=(array)$Resp;
			if (isset($Resp->{'SessionId'})) {
				$this->SessionId=$Resp->{'SessionId'};
			}
			else{
				$this->SessionId="";
				$this->errorSAP=(array)$Resp->{'error'};
			}
            $i++;
		}
		return $_arr;
	}

	function ConectaFinnegans(){
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://api.teamplace.finneg.com/api/oauth/token?grant_type=client_credentials&client_id=aad635f1d96c64578a0baa987c20b751&client_secret=03c7ddaf37a9502d77657a2afae48125',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'GET',
		));
		$response = curl_exec($curl);
		curl_close($curl);
		return $response;
	}

	function ObtenProductosFinnegans(){
		$IdToken = $this -> ConectaFinnegans();
		$URL = "https://api.teamplace.finneg.com/api/producto/list?ACCESS_TOKEN=".$IdToken;
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => $URL,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'GET',
		));
		$response = json_decode(curl_exec($curl));
		curl_close($curl);
		return (array)$response;
	}

	function SAP_OT(){
		//************************************************************************************
		//  FUNCION 1 : InventoryGenEntries
		//************************************************************************************
		$json2 = '{';
		//  $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
		$sql = "SELECT * FROM c_datos_sap WHERE Empresa = (SELECT cve_proveedor FROM c_proveedores WHERE ID_Proveedor = (SELECT ID_Proveedor FROM t_ordenprod WHERE Folio_Pro = '{$folio_ot}')) AND Activo = 1;";
		if (!($res = mysqli_query($conn, $sql))) 
		{
			echo "Falló la preparación 1: (" . mysqli_error($conn) . ") ";
		}
		$row = mysqli_fetch_array($res);
		$endPoint = $row['Url'].$funcion;
		//***********************************************************************************************************
		$sql = "SELECT DATE_FORMAT(Hora_Ini, '%Y-%m-%d') AS HoraInicio, Referencia, Cve_Articulo, Cve_Lote, Cant_Prod, Cve_Almac_Ori FROM t_ordenprod WHERE Folio_Pro = '{$folio_ot}';";
		if (!($res = mysqli_query($conn, $sql))) 
		{
			echo "Falló la preparación 2: (" . mysqli_error($conn) . ") ";
		}
		$row = mysqli_fetch_array($res);
		$HoraInicio    = $row['HoraInicio'];
		$Referencia    = $row['Referencia'];
		$Cve_Articulo  = $row['Cve_Articulo'];
		$Cve_Lote      = $row['Cve_Lote'];
		$Cant_Prod     = $row['Cant_Prod'];
		$Cve_Almac_Ori = $row['Cve_Almac_Ori'];
		$json2 .= '"DocDate":"'.$HoraInicio.'","DocDueDate":"'.$HoraInicio.'",';
		$json2 .= '"DocumentLines":[';
		$json2 .= '{';
		$json2 .= '"BaseEntry":"'.$Referencia.'","BaseType":"202","Quantity":"'.$Cant_Prod.'","WarehouseCode":"'.$Cve_Almac_Ori.'", 
		"BatchNumbers":[';
		$sql = "SELECT Caducidad FROM c_lotes WHERE Lote = '{$Cve_Lote}'";
		if (!($res = mysqli_query($conn, $sql))) 
		{
		  echo "Falló la preparación 4: (" . mysqli_error($conn) . ") ";
		}
		$row2 = mysqli_fetch_array($res);
		$Caducidad = $row2['Caducidad'];
		$json2 .= '{"BatchNumber":"'.$Cve_Lote.'","Quantity":"'.$Cant_Prod.'","ExpiryDate":"'.$Caducidad.'"},';
		$json2[strlen($json2)-1] = ' ';
		$json2 .= ']},';
		$json2[strlen($json2)-1] = ' ';
		$json2 .= ']}';
		$sesion_id = $_POST['sesion_id'];
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $endPoint,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => $metodo,
			CURLOPT_POSTFIELDS =>$json2,
			CURLOPT_HTTPHEADER => array(
			'Content-Type: text/plain',
			'Cookie: B1SESSION='.$sesion_id.'; ROUTEID=.node2'
			),
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false
		));
		$response = curl_exec($curl);
		curl_close($curl);
		echo $response;
	}

	function EnviaEntrada_Intelisis()
	{
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
			$_arr = array();
			$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
            $sql = "Call SPWS_DameEncOC ('"
                . $MiSql[$i]->{'Almacen'} . "',"
				. $MiSql[$i]->{'Folio'} . ");";
            $result = $conn->query($sql);
			if(!$result)
			{
				$_arr["error"][] = array("code" => -1,
										"message" => utf8_encode($conn->error));
				return $_arr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				if($row[0]==-1)
				{
					$_arr["error"][] = array("code" => -1,
											"message" => utf8_encode($row[1]));
					return $_arr;
				}
				$connD =  new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
				$sql = "Call SPWS_DameDetOC ('"
					. $MiSql[$i]->{'Almacen'} . "',"
					. $MiSql[$i]->{'Folio'} . ",'"
					. $row[2] . "');";
				$resultD = $connD->query($sql);
				while ($rowD = $resultD->fetch_array(MYSQLI_NUM)) {
					if($rowD[0]==-1)
					{
						$_arr["error"][] = array("code" => -1,
												"message" => utf8_encode($rowD[1]));
						return $_arr;
					}
					$_arrd[]= array();
					$_arrd[] = array("ID" => (int)$row[3],
								"Renglon" => $rowD[4],
								"RenglonSub" => $rowD[5],
								"Codigo" => $rowD[6],
								"Cantidad" => (double)$rowD[7]);
				}
				$_arr = array("ID" => (int)$row[3],
								"Detalle" => $_arrd);
				$connD->close();
			}
			$Resp=json_encode($_arr);
//var_dump($Resp);
			// Envía al Web Service de intelisis
			$resultado = array();
			$resultado = (array)$this -> Envia_Intelisis('WMSSetVenta', $Resp);
			if (!isset($resultado)) {
				$_arr["error"][] = array("code" => -1,
										"message" => "Sin conexion al Web Service");
				return $_arr;
			}
			if (isset($resultado["error"])) {
				return $resultado;
			}
            $i++;
        }
		return $resultado;
	}

	function EnviaVenta_Intelisis()
	{
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
			$_arr = array();
			$_arrerr = array();
			$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
            $sql = "Call SPWS_DameEncPedidoSurtido ('"
                . $MiSql[$i]->{'Almacen'} . "','"
				. $MiSql[$i]->{'Folio'} . "');";
            $result = $conn->query($sql);
			if(!$result)
			{
				$_arrerr["error"][] = array("code" => -1,
										"message" => utf8_encode($conn->error));
				return $_arrerr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				if($row[0]==-1)
				{
					$_arrerr["error"][] = array("code" => -1,
											"message" => utf8_encode($row[1]));
					return $_arrerr;
				}
				$connD =  new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
				$sql = "Call SPWS_DameDetPedidoSurtido ('"
					. $MiSql[$i]->{'Almacen'} . "','"
					. $MiSql[$i]->{'Folio'} . "');";
				$resultD = $connD->query($sql);
				while ($rowD = $resultD->fetch_array(MYSQLI_NUM)) {
					if($rowD[0]==-1)
					{
						$_arrerr["error"][] = array("code" => -1,
												"message" => utf8_encode($rowD[1]));
						return $_arrerr;
					}
					$_arrd[]= array();
					$_arrd[] = array("ID" => (int)$rowD[2],
								"Renglon" => (double)$rowD[4],
								"RenglonSub" => $rowD[5],
								"Codigo" => $rowD[3],
								"Cantidad" => (double)$rowD[6]);
				}
				$_arr = array("ID" => (int)$row[2],
								"Detalle" => $_arrd);
				$connD->close();
			}
			$Resp=json_encode($_arr);
			// Envía al Web Service de intelisis
			$resultado = array();
			$resultado = (array)$this -> Envia_Intelisis('WMSSetVenta', $Resp);
			if (!isset($resultado)) {
				$_arrerr["error"][] = array("code" => -1,
										"message" => "Sin conexion al Web Service");
				return $_arrerr;
			}
			if (isset($resultado["Ok"])) {
				$_arrerr["error"][] = array("code" => -1,
										"message" => $resultado["OkRef"]);
				return $_arrerr;
			}
            $i++;
        }
		$_arrresp= array();
		$_arrresp[]=array("code" => 1,
						"message" => "OK");
		return $_arrresp; //$resultado;
	}

	function EnviaEmbarque_Intelisis()
	{
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
			$_arr = array();
			$_arrerr = array();
			$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
            $sql = "Call SPWS_DameEncPedidoEmb ('"
                . $MiSql[$i]->{'Almacen'} . "',"
				. $MiSql[$i]->{'IdEmb'} . ");";
            $result = $conn->query($sql);
			if(!$result)
			{
				$_arrerr["error"][] = array("code" => -1,
										"message" => utf8_encode($conn->error));
				return $_arrerr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				if($row[0]==-1)
				{
					$_arrerr["error"][] = array("code" => -1,
											"message" => utf8_encode($row[1]));
					return $_arrerr;
				}
				$connD =  new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
				$sql = "Call SPWS_DameDetPedidoEmb ('"
					. $MiSql[$i]->{'Almacen'} . "',"
					. $MiSql[$i]->{'IdEmb'} . ",'"
					. $MiSql[$i]->{'Folio'} . "');";
				$resultD = $connD->query($sql);
				while ($rowD = $resultD->fetch_array(MYSQLI_NUM)) {
					if($rowD[0]==-1)
					{
						$_arrerr["error"][] = array("code" => -1,
												"message" => utf8_encode($rowD[1]));
						return $_arrerr;
					}
					$_arrd= array();
					$_arrd[] = array("Codigo" => $rowD[2],
								"Cantidad" => (double)$rowD[6],
								"Lote" => $rowD[4],
								"Caducidad" => $rowD[5],
								"Precio" => (double)$rowD[7],
								"ItemNum" => $rowD[8],
								"Observaciones" => $rowD[9],
								"Estado" => $rowD[10]);
				}
				$_arr = array("ID" => (int)$row[3],
								"Vehiculo" => $row[4],
								"Ruta" => $row[5],
								"Almacen" => $row[6],
								"Referencia" => "",
								"FechaEmision" => $row[7],
								"Detalle" => $_arrd);
				$connD->close();
			}
			$Resp=json_encode($_arr);
			// Envía al Web Service de intelisis
			$resultado = array();
			$resultado = (array)$this -> Envia_Intelisis('WMSSetEmbarques', $Resp);
			if (!isset($resultado)) {
				$_arrerr["error"][] = array("code" => -1,
										"message" => "Sin conexion al Web Service");
				return $_arrerr;
			}
			if (isset($resultado["Ok"])) {
				$_arrerr["error"][] = array("code" => -1,
										"message" => $resultado["OkRef"]);
				return $_arrerr;
			}
            $i++;
        }
		$_arrresp= array();
		$_arrresp[]=array("code" => 1,
						"message" => "OK");
		return $_arrresp; //$resultado;
	}

	function EnviaMovs_Intelisis()
	{
        $i=0;
        foreach((array)$this->arrTbl as $detalle)
        {
            $MiSql[] = $detalle;
			$_arr = array();
			$_arrerr = array();
			$conn = new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
            $sql = "Call SPWS_DameEncMovAlmac ('"
                . $MiSql[$i]->{'Almacen'} . "',"
				. $MiSql[$i]->{'IdCar'} . ");";
            $result = $conn->query($sql);
			if(!$result)
			{
				$_arrerr["error"][] = array("code" => -1,
										"message" => utf8_encode($conn->error));
				return $_arrerr;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				if($row[0]==-1)
				{
					$_arrerr["error"][] = array("code" => -1,
											"message" => utf8_encode($row[1]));
					return $_arrerr;
				}
				$connD =  new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
				$sql = "Call SPWS_DameDetMovAlmac('"
					. $MiSql[$i]->{'Almacen'} . "','"
					. $MiSql[$i]->{'IdCar'} . "');";
				$resultD = $connD->query($sql);
				while ($rowD = $resultD->fetch_array(MYSQLI_NUM)) {
					if($rowD[0]==-1)
					{
						$_arrerr["error"][] = array("code" => -1,
												"message" => utf8_encode($rowD[1]));
						return $_arrerr;
					}
					$_arrd[]= array();
					$_arrd[] = array("Referencia" => $rowD[4],
								"Articulo" => $rowD[5],
								"Cantidad" => (double)$rowD[6]);
				}
				$connL =  new mysqli($this->ip_server, $this->uid, $this->pswd, $this->db);
				$sql = "Call SPWS_DameLoteMovAlmac('"
					. $MiSql[$i]->{'Almacen'} . "','"
					. $MiSql[$i]->{'IdCar'} . "');";
				$resultL = $connL->query($sql);
				while ($rowL = $resultL->fetch_array(MYSQLI_NUM)) {
					if($rowL[0]==-1)
					{
						$_arrerr["error"][] = array("code" => -1,
												"message" => utf8_encode($rowD[1]));
						return $_arrerr;
					}
					$_arrl[]= array();
					$_arrl[] = array("Codigo" => $rowD[3],
								"Lote" => $rowD[4],
								"Cantidad" => (double)$rowD[5],
								"Caducidad" => $rowD[6]);
				}
				$_arr = array("ID" => (int)$row[2],
								"Almacen" => $row[3],
								"Referencia" => $row[4],
								"TipoMov" => $row[5],
								"Detalle" => $_arrd,
								"Lotes" => $_arrl);
				$connD->close();
				$connL->close();
			}
			$Resp=json_encode($_arr);
			// Envía al Web Service de intelisis
			$resultado = array();
			$resultado = (array)$this -> Envia_Intelisis('WMSSetVenta', $Resp);
			if (!isset($resultado)) {
				$_arrerr["error"][] = array("code" => -1,
										"message" => "Sin conexion al Web Service");
				return $_arrerr;
			}
			if (isset($resultado["Ok"])) {
				$_arrerr["error"][] = array("code" => -1,
										"message" => $resultado["OkRef"]);
				return $_arrerr;
			}
            $i++;
        }
		$_arrresp= array();
		$_arrresp[]=array("code" => 1,
						"message" => "OK");
		return $_arrresp; //$resultado;
	}

	function Envia_Intelisis($Servicio, $JSON)
	{
		// Logueo al Web Services
		$resultado = array();
		$resultado = (array)$this -> Conectar_Intelisis();
		if (!isset($resultado)) {
			$_arr["error"][] = array("code" => -1,
									"message" => "Sin conexion a intelisis");
			return $_arr;
		}
		if (isset($resultado["error"])) {
			return $resultado;
		}
		if ($this->Token==="") {
			return $resultado;
		}
		$endPoint = $this->SAP_URL.$Servicio;
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => $endPoint,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS =>$JSON,
		  CURLOPT_HTTPHEADER => array(
			'Authorization: bearer '.$this->Token,
			'Content-Type: application/json'
		  ),
		));
		$response = curl_exec($curl);
		$Resp=json_decode($response);
		curl_close($curl);
		return $Resp;
	}

	function Conectar_Intelisis()
	{
/*$url_completa = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
var_dump($_SERVER[HTTP_HOST]);
var_dump($_SERVER[REQUEST_URI]);
var_dump($url_completa);*/
		$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
		$sql = "SELECT Url,IfNull(Puerto,0) Puerto,Servicio,User,Pswd,Empresa,IfNull(Token,'') Token,IfNull(Refresh_Token,'') Refresh_Token FROM c_datos_ws WHERE Servicio = 'login' AND Activo = 1;";
		if (!($res = mysqli_query($conn, $sql))){
			$arr["error"][] = array(
				"code" => -1,
				"message" => "Fallo en el envio: ".$conn->error
			);
			$conn->Close();
			return $arr;
		}
		$funcion ='login';
		$endPoint = '';
		$json = '';
		$metodo   = 'POST';
		$row = mysqli_fetch_array($res);
		$this->SAP_URL = "";
		if($row['Puerto']!=0){
			$this->SAP_URL = $row['Url'].":".$row['Puerto']."/";
		}
		else{
			$this->SAP_URL = $row['Url']."/";
		}
		$endPoint = $this->SAP_URL.$funcion;
		$usuario  = $row['User'];
		$password = $row['Pswd'];
		$this->Token = $row['Token'];
		$this->refreshToken = $row['Refresh_Token'];
		$conn->Close();
		$salir=0;
		While(!$salir)
		{
			$curl = curl_init();
			if($this->Token==""){
				curl_setopt_array($curl, array(
				  CURLOPT_URL => $endPoint,
				  CURLOPT_RETURNTRANSFER => true,
				  CURLOPT_ENCODING => '',
				  CURLOPT_MAXREDIRS => 10,
				  CURLOPT_TIMEOUT => 0,
				  CURLOPT_FOLLOWLOCATION => true,
				  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				  CURLOPT_CUSTOMREQUEST => 'POST',
				  CURLOPT_POSTFIELDS => 'username='.$usuario.'&password='.$password.'&grant_type=password',
				  CURLOPT_HTTPHEADER => array(
					'Content-Type: application/x-www-form-urlencoded'
				  ),
				  CURLOPT_SSL_VERIFYHOST => false,
				  CURLOPT_SSL_VERIFYPEER => false
				));
				$salir=1;
			}
			else // Ya hay token generado
			{
				curl_setopt_array($curl, array(
				  CURLOPT_URL => $endPoint,
				  CURLOPT_RETURNTRANSFER => true,
				  CURLOPT_ENCODING => '',
				  CURLOPT_MAXREDIRS => 10,
				  CURLOPT_TIMEOUT => 0,
				  CURLOPT_FOLLOWLOCATION => true,
				  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				  CURLOPT_CUSTOMREQUEST => 'POST',
				  CURLOPT_POSTFIELDS => 'refres_token='.$this->refreshToken.'&grant_type=refresh_token',
				  CURLOPT_HTTPHEADER => array(
					'Content-Type: application/x-www-form-urlencoded'
				  ),
				  CURLOPT_SSL_VERIFYHOST => false,
				  CURLOPT_SSL_VERIFYPEER => false
				));
				$salir=0;
			}
			$response = curl_exec($curl);
			$Resp=json_decode($response);
			$NoError=curl_errno($curl);
			$MsgError=curl_error($curl);
			curl_close($curl);
			if ($NoError!=0)
			{
				$arr["error"][] = array(
					"code" => -1,
					"message" => "Fallo en el envio: ".$MsgError
				);
				return $arr;
			}
			if (isset($Resp->{'access_token'}))
			{
				$this->Token=$Resp->{'access_token'};
				$this->refreshToken=$Resp->{'refresh_token'};
				$salir=1;
			}
			else{
				$this->Token="";
				$this->refreshToken="";
				$this->errorSAP=(array)$Resp->{'error'};
			}
			$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
			$sql = "Call SPWS_ActualizaTokenWS('"
				. $this->Token . "','"
				. $this->refreshToken . "');";
			mysqli_query($conn, $sql);
			$conn->Close();
		}
		return $Resp;
	}

	function Prueba_Infinity()
	{
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => $endpoint.$Servicio,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS =>'[{"item":"40003-100-03", "batch": "","qty":5964,"um":"PIEZA","typeMov":"T","warehouse":"WH1","dataOpe":"2025-01-18 00:02:16"} ]',
		  CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json',
			'Authorization: Basic YXNzaXRwcm86MmQ0YmE1ZWQtOGNhMS00NTQyLWI4YTYtOWRkYzllZTc1Nzky'
		  ),
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false
		));

		$response = curl_exec($curl);
		curl_close($curl);
		return (array)$response;
	}
}

function getUser($Usuario,$Passwd) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    $sql = "SELECT id_user,pwd_usuario FROM c_usuario Where cve_usuario='" . $Usuario . "' And pwd_usuario='" . $Passwd . "';";
	$result = $conn->query($sql);
    $_arr = array();
    while ($row = $result->fetch_array(MYSQLI_NUM)) {
        if( $Passwd == $row[1] ) {
			$conn->close();
            return true;
        }
    }
	$conn->close();
    return false;
}

include '../config.php';
//require_once '../app/load.php';
$json = file_get_contents('php://input');
$obj = json_decode($json);

if (!empty($obj)) {
    error_reporting(0);
	// clase de licenciamiento
	$u = new Licencia();
	$u->server = DB_HOST;
	$u->datab = DB_NAME;
	$u->user_id = DB_USER;
	$u->psswd = DB_PASSWORD;
	// Clase para las funciones de los WS
	$t = new JSON2Sql();
	$t->ip_server = "p:".DB_HOST;
	$t->db = DB_NAME;
	$t->uid = DB_USER;
	$t->pswd = DB_PASSWORD;

	// Interfaz con SAP Service Layer
	$t->url = SAP_URL;
	$t->SAP_Usr = SAP_Usuario;
	$t->SAP_pswd = SAP_Clave;
	$t->SAP_DB_1 = SAP_DB1;
	$t->SAP_DB_2 = SAP_DB2;
	$t->SAP_Empresa_1 = SAP_Empresa1;
	$t->SAP_Empresa_2 = SAP_Empresa2;
	$intentos=0;
	While($intentos<3) {
    $conn = new mysqli($t->ip_server, $t->uid, $t->pswd, $t->db);
		if( $conn->connect_errno ) {
			if ($intentos==2) {
				$arr = array("Error"=>-1,
							"Error"=>"Fallo al conectarse a MySQL",
							"Errno" => $conn->connect_errno,
							"ErrorMsg" => $conn->connect_error);
				$intentos=3;
				$ret = $arr;
				echo json_encode($ret);
				// Se guarda el log de errores
				if (!file_exists("ErrorLog/")){
					mkdir("ErrorLog",0777);
				}
				$Ruta="ErrorLog/Log_".strftime("%Y%m%d_%H%M%S",time()).".txt";
				$File = fopen($Ruta,"w");
				fwrite($File,$json);
				fclose($File);
				exit();
			}
			else{
				$intentos+=1;
				sleep(3);
				$conn->close();
			}
		}
		else {
			$intentos=3;
		}
	}
	$t->arrTbl = array();
	$t->arrTbl = (array)$obj;
	$t->user = $t->arrTbl[0]->{'user'};
	$t->password = $t->arrTbl[0]->{"password"};
	$t->cia = $t->arrTbl[0]->{"cia"};
	$t->Cant = $u->ObtenLicencia();
    if (isset($t->arrTbl[0]->{'user'}) && isset($t->arrTbl[0]->{'pwd'})) {
        if (!getUser($t->arrTbl[0]->{'user'},$t->arrTbl[0]->{'pwd'})) {
            $arr = array(
                "success" => false,
                "err" => "usuario no existe"
            );
            $ret = $arr;
			echo json_encode($ret);
        } else {
			/* if( !isset( $_SESSION['id_user'] ) AND !$_SESSION['id_user'] ) {
				$arr = array(
					"success" => false,
					"err" => "usuario o password incorrectos"
				);
				$ret = $arr;
				header('Content-type: application/json; Charset=UTF-8');
				echo json_encode($ret);
				exit();
			} */
			if(isset($t->arrTbl[0]->{'TipoMov'})) {
				if($t->arrTbl[0]->{'TipoMov'} === 'E'){
					$function = 'Sql2JSONMovsAlmacE';
				} else {
					$function = 'Sql2JSONMovsAlmacS';
				}
			} else {
				$arr = array(
					"success" => false,
					"err" => "No esta definido el movimiento"
				);
				$ret = $arr;
				echo json_encode($ret);
			}
		}
    } else {
		$function = $t->arrTbl[0]->{"function"};
	}
	$ret = $t->$function();
    header('Content-type: application/json; Charset=UTF-8');
    echo json_encode($ret);
}