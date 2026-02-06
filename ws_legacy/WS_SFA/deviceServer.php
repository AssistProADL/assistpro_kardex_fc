<?php
class Sql2JSON
{
    var $idRuta;
    var $Ruta;
    var $ip_server;
    var $user;
    var $password;
    var $db;
    var $connectinfo;
    var $Vendedor;
	var $Folio;
	var $CadJSON;
	var $Proceso;
	
	function GuardaLogWS_old($Referencia,$Mensaje,$Respuesta,$Enviado,$Proceso,$Dispositivo)
	{
		$url_completa = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/api/webserviced_win_mysql.php";
		$_arr=array();
		$_arr[]=array("Referencia" => $Referencia,
						"Mensaje" => $Mensaje,
						"Enviado" => $Enviado,
						"Proceso" => $Proceso,
						"Dispositivo" => $Dispositivo);
		$JSON = json_encode($_arr);
		$metodo   = 'POST';
		//url contra la que atacamos
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL => $url_completa,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => $metodo,
			CURLOPT_POSTFIELDS =>$JSON,
			CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json'
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

    function Sql2JSONClientes()
    {
		$Proceso="Carga Clientes";
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameClientes(" . $this->idRuta . ");";
        $result = $conn->query($sql);
        $_arr = array();
        $c = 0;
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array("Email" => $row[0],
				"IdCli" => $row[1],
                "Nombre" => utf8_encode($row[2]),
                "NombreCorto" => utf8_encode($row[3]),
                "Direccion" => utf8_encode($row[4]),
                "Referencia" => utf8_encode($row[5]),
                "Telefono" => utf8_encode($row[6]),
				"CP" => $row[7],
				"RFC" => $row[8],
				"Ciudad" => utf8_encode($row[9]),
				"Estado" => utf8_encode($row[10]),
				"Colonia" => utf8_encode($row[11]),
                "Credito" => utf8_encode($row[12]),
                "LimiteCredito" => $row[13],
                "DiasCreedito" => $row[14],
                "VisitaObligada" => $row[15],
                "FirmaObligada" => $row[16],
                "Horario" => $row[17],
				"ValidarGPS" => utf8_encode($row[19]),
                "Saldo" => "0",
                "latitud" => $row[20],
                "longitud" => $row[21],
                "Generico" => $row[22]);
            $c++;
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function Sql2JSONConfig()
    {
		$Proceso="Carga Configuración";
        $conn =  new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql2 = "Call SPSFA_Config(" . $this->idRuta . ");";
        $result2 = $conn->query($sql2);
        while ($row2 =  $result2->fetch_array(MYSQLI_NUM)) {
            $_arr["Config"][] = array("RutaId" => $row2[0],
									"ModelPrinter" => $row2[1],
									"VelCom" => $row2[2],
									"COM" => $row2[3],
									"Server" => $row2[4],
									"Puerto" => $row2[5],
									"ServerGPS" => $row2[6],
									"GPS" => $row2[7],
									"PuertoG" => $row2[8],
									"PagoContado" => $row2[9],
									"CteNvo" => $row2[10],
									"CveCteNvo" => $row2[11],
									"IdEmpresa" => $row2[12],
									"SugerirCant" => $row2[13],
									"PromoEq" => $row2[14]);
        }
		if ($_arr[0]===null)
			$_arr["Config"][] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
		$conn->close();
        return $_arr;
    }

    function Sql2JSONProducts()
    {
		$Proceso="Carga Productos";
        $conn =  new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameProductos('".$this->IdEmpresa."');";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            //$varimg = base64_encode($row["Foto"]);
            //$data = base64_decode($varimg);
            $_arr[] = array("Id" => $row[0],
                "Clave" => $row[1],
                "Producto" => utf8_encode($row[2]),
                "CodBarras" => $row[3],
                "Granel" => $row[4],
                "IVA" => $row[5],
                "IEPS" => $row[6],
                "UniMed" => $row[7],
                "VBase" => $row[8],
                "Equivalente" => $row[9],
                "Sector" => '',
                "Ban_Envase" => $row[10],
                //"Foto" => $row["Foto"],
                "idclasp" => '',
                "IdEmpresa" => $row[11],
				"Grupo" => $row[14],
                "Foto" => "",
                "Status" => $row[13]);
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function Sql2JSONProductsPzas()
    {
		$Proceso="Carga Pzs por caja de Productos";
        $conn =  new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameProductosPzs('".$this->IdEmpresa."');";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array(
                "Producto" => $row[0],
                "PzaXCja" => $row[1],
                "StockxP" => $row[2]
            );
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function Sql2JSONProductEnvase()
    {
		$Proceso="Carga Envases";
        $conn =  new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameProductoEnvase();";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array(
                "Id" => $row[0],
                "Producto" => $row[1],
                "Envase" => $row[2],
                "Cant_Base" => $row[3],
                "Cant_Eq" => $row[4],
                "Status" => $row[5],
                "IdEmpresa" => $row[6]);
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function CargaCodigosOp()
    {
		$Proceso="Carga códigos operativos";
        $conn =  new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameCodigosOperacion('".$this->IdEmpresa."');";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array(
                "Codi" => $row[0],
                "Operacion" => utf8_encode($row[1]),
                "Tipo" => $row[2],
                "IdEmpresa" => $row[3],
                "EsRecarga" => $row[4],
                "Gasto" => $row[5]);
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function CeRelDMnv()
    {
        $conn = sqlsrv_connect($this->ip_server,$this->connectinfo);
        $sql = "SELECT * FROM CatMnv";
        $result = sqlsrv_query($conn, $sql);
        $_arr = array();
        while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
            $_arr["RelDMnv"][] = $row;
        }
		sqlsrv_close($conn);
        return $_arr;
    }

    function Sql2Cuotas()
    {
		$Proceso="Carga Cuotas";
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameCuotas(" . $this->idRuta . ");";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array(
                "Id" => $row[0],
                "Descripcion" => utf8_encode($row[1]),
                "UniMed" => $row[2],
                "Cantidad" => $row[3],
                "FechaF" => $row[4],
                "Producto" => $row[5],
                "Tipo" => $row[6]);
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function LineasCe()
    {
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameLineasCe('".$this->IdEmpresa."');";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array(
                "ID" => $row[0],
                "Linea1" => utf8_encode($row[1]),
                "Linea2" => utf8_encode($row[2]),
                "Linea3" => utf8_encode($row[3]),
                "Linea4" => utf8_encode($row[4]),
                "Mensaje" => utf8_encode($row[5]),
                "Tdv" => $row[6],
                "LOGO" => $row[7],
                "MLiq" => $row[8],
                "IdEmpresa" => $row[9]);
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function RegistraContinuity()
    {
		$Proceso="Carga Continuidad";
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_Continuidad(" . $this->idRuta . ");";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array(
                "RutaID" => $row[0],
                "DiaO" => $row[1],
                "FolVta" => $row[2],
                "FolPed" => $row[3],
                "FolDevol" => $row[4],
                "FolCob" => $row[5],
                "UDiaO" => $row[6],
                "CteNvo" => $row[7],
                "IdEmpresa" => $row[8],
                "FolGto" => $row[9],
                "FolServicio" => $row[10]);
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function Sql2JSONLpro()
    {
		$Proceso="Carga Promociones";
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameListaPromo('".$this->IdEmpresa."');";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array(
                "IdLm" => $row[0],
                "IdPromo" => $row[1],
                "Lista" => $row[2],
                "ListaMaster" => $row[3],
                "Articulo" => $row[4],
                "Cantidad" => $row[5],
                "Tipo" => $row[6],
                "Descripcion" => utf8_encode($row[7]),
                "Caduca" => $row[8],
                "FechaF" => $row[9],
                "FechaI" => $row[10],
                "Activa" => $row[11],
                "TipoProm" => $row[12],
                "Monto" => $row[13],
                "Volumen" => $row[14],
                "uniMed" => $row[15],
                "Nivel" => $row[16],
                "Grupo_Art" => $row[17]);
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function Sql2LisProMas()
    {
		$Proceso="Carga master de promociones";
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameListaPromoMaster('".$this->IdEmpresa."');";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array(
                "Id" => $row[0],
                "ListaMaster" => $row[1],
                "Promociones" => $row[2],
                "IdEmpresa" => $row[3]);
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function Sql2JSONLp()
    {
		$Proceso="Carga listas de precios";
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameListaPrecios(" . $this->idRuta . ");";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
            $sql2 = "Call SPSFA_DameDetalleLP(". $row[1].");";
            $result2 = $_conn->query($sql2);
            while ($row2 = $result2->fetch_array(MYSQLI_NUM)) {
                $_arr[] = array(
					"Id" => $row2[0],
					"ListaId" => $row2[1],
					"Cve_Articulo" => $row2[2],
					"PrecioMin" => $row2[3],
					"PrecioMax" => $row2[4],
					"ComisionPor" => $row2[5],
					"ComisionMon" => $row2[6],
					"IdEmpresa" => $row2[7]);
            }
            $_conn->close();
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function Sql2JSONLD()
    {
		$Proceso="Carga listas de descuentos";
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameListaDescuento(" . $this->idRuta . ");";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
			$_conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
            $sql = "Call SPSFA_DameDetalleLD(" . $this->idRuta . ");";
            $result0 = $_conn->query($sql);
            while ($row2 = $result0->fetch_array(MYSQLI_NUM)) {
                $_arr[] = array(
					"Articulo" => $row2[0],
					"ListaD" => $row2[1],
					"Factor" => $row2[2],
					"Tipo" => $row2[3],
					"Minimo" => $row2[4],
					"Maximo" => $row2[5],
					"TipoD" => $row2[6],
					"Caducidad" => $row2[7],
					"FechaIni" => $row2[8],
					"FechaFin" => $row2[9]);
            }
			$_conn->close();
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function Sql2JSONFormasp()
    {
		$Proceso="Carga formas de pago";
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameFormasP('".$this->IdEmpresa."')";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array("IdFpag" => $row[0],
                "Forma" => utf8_encode($row[1]),
                "Clave" => $row[2]);
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function Sql2JSONMensajes()
    {
		$Proceso="Carga mensajes";
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameMensajes('".$this->IdEmpresa."');";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array("Clave" => $row[1],
                "EnBaseA" => utf8_encode($row[2]),
                "Descripcion" => utf8_encode($row[3]),
                "Mensaje" => utf8_encode($row[4]),
                "FechaInicio" => $row[5],
                "FechaFinal" => $row[6],
                "Estado" => $row[7],
                "ID" => $row[0]);
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function Sql2JSONRelMens()
    {
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameRelMensajes('".$this->IdEmpresa."');";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array("IDRow" => $row[0],
                "MenId" => $row[1],
                "CodCliente" => $row[2],
                "IdCliente" => $row[3],
                "CodProducto" => $row[4],
                "IdProducto" => $row[5],
                "CodRuta" => $row[6],
                "IdRuta" => $row[7],
                "IdEmpresa" => $row[8]);
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function Sql2JSONActivos()
    {
		$Proceso="Carga activos";
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "SELECT * FROM Activos";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row2 = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array("IdActivos" => $row2["IdActivos"],
                "Descripcion" => utf8_encode($row2["Descripcion"]),
                "Modelo" => ($row2["Modelo"]),
                "Fecha" => $row2["Fecha"],
                //"Imagen" => $row2["Imagen"],
                "Serie" => $row2["Serie"],
                "CB" => $row2["CB"],
                "Status" => $row2["Status"],
                "IdEmpresa" => $row2["IdEmpresa"]);
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }
    
    /////////////////////////////////GG INCIDENCIAS ///////////////////////////
    function Sql2JSONServicios()
    {
		$Proceso="Carga servicios";
        $conn =  new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameServicios(" . $this->idRuta . ");";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr["Servicios"][] = array(
                "Producto" => $row[0],
                "PzaXCja" => $row[1],
                "StockxP" => $row[2]
            );
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }
    ///////////////////////////////////////////////////////////////////////////

    function Sql2JSONVendedores()
    {
		$Proceso="Carga vendedores";
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql2 = "Call SPSFA_DameVendedores(".$this->idRuta.",'".$this->IdEmpresa."');";
        $result2 = $conn->query($sql2);
        while ($row2 = $result2->fetch_array(MYSQLI_NUM)) {
			$_arr[] = array("Clave" => $row2[3],
				"IdVendedor" => $row2[0],
				"Vendedor" => utf8_encode($row2[1]),
				"Pwd" => $row2[2],
				"Ruta" => $this->Ruta,
				"IdRuta" => $this->idRuta);
		}
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function Sql2JSONStock()
    {
		$Proceso="Carga stock de ruta";
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameStock(" . $this->idRuta . ");";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array("Articulo" => $row[1],
				"Stock" => $row[2],
				"Ruta" => $row[3]);
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function Sql2JSONDay()
    {
		$Proceso="Carga dias de Visita";
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameDayCli(" . $this->idRuta . ");";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array("Id" => $row[1],
				"RutaId" => $row[2],
				"CodCli" => $row[3],
				"idVendedor" => $row[4],
				"Lunes" => $row[5],
				"Martes" => $row[6],
				"Miercoles" => $row[7],
				"Jueves" => $row[8],
				"Viernes" => $row[9],
				"Sabado" => $row[10],
				"Domingo" => $row[11],
				"IdEmpresa" => $row[12]);
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function Sql2JSONRelT()
    {
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameLisCli(" . $this->idRuta . ");";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array("CodCliente" => $row[5],
                "ListaP" => $row[1],
                "ListaD" => $row[2],
                "ListaPromo" => $row[3]);
            $row;
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function Sql2JSONmvodev()
    {
		$Proceso="Carga motivos de devolución";
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameMotivosDev();";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array("Id" => $row[0],
                "MvoDev" => utf8_encode($row[1]),
                "Clave" => $row[2],
                "Status" => $row[3],
                "IdEmpresa" => $this->IdEmpresa);
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function Sql2JSONEncuestas()
    {
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "SELECT P.Num_Preg, P.Des_Preg, R.Clave_Enc, E.Tipo_Enc FROM Rel_EncRut R JOIN Preg_Enc P ON P.Clave_Enc=R.Clave_Enc JOIN encuestas E ON E.Clave_Enc=R.Clave_Enc WHERE R.Id_Ruta='" . $this->idRuta . "' ORDER BY P.Num_Preg";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = $row;
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function Sql2JSONSaldoEnvase()
    {
		$Proceso="Carga saldo de envases";
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameSaldoEnvases(" . $this->idRuta . ");";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array("CodCli" => $row[0],
                "Docto" => utf8_encode($row[1]),
                "Articulo" => $row[2],
                "Saldo" => $row[3],
                "Envase" => $row[4],
                "Fecha" => $row[5]);
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function Sql2JSONEncuestasOpciones()
    {
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "SELECT P.Num_Preg, P.Des_Preg, R.Clave_Enc, E.Tipo_Enc 
                FROM Rel_EncRut R JOIN Preg_Enc P 
                ON P.Clave_Enc=R.Clave_Enc JOIN Encuestas E 
                ON E.Clave_Enc=R.Clave_Enc WHERE R.Id_Ruta='" . $this->idRuta . "' ORDER BY P.Num_Preg";
        $result = sqlsrv_query($conn, $sql);
        $_arr = array();
        while ($row = sqlsrv_fetch_array($result)) {
            $sql = "SELECT Num_Preg,Num_Resp,Desc_Resp,Clave_Enc FROM Opc_Enc WHERE Clave_Enc='" . $row["Clave_Enc"] . "' And Num_Preg=" . $row["Num_Preg"] . " ORDER BY Num_Preg";
            $result = sqlsrv_query($conn, $sql);
            $_arr = array();
            while ($row2 = sqlsrv_fetch_array($result)) {
                $_arr[] = $row2;
            }
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function Sql2JSONAyudantes()
    {
		$Proceso="Carga ayudantes";
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameAyudantes();";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row2 = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array("Nombre" => utf8_encode($row2[0]),
                "Direccion" => utf8_encode($row2[1]),
                "Telefono" => $row2[2],
                "IdEmpresa" => $row2[3],
                "Status" => $row2[4],
                "NumLicencia" => $row2[5],
                "VenceLic" => $row2[6],
                "Clave" => $row2[7],
                "id_ayudante" => $row2[8]);
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function Sql2JSONVisitas()
    {
		$Proceso="Carga dias de visita";
        $datetime = date('Y-m-d');
        $day_num = date('w', strtotime($datetime));
        $dias[] = "Domingo";
        $dias[] = "Lunes";
        $dias[] = "Martes";
        $dias[] = "Miercoles";
        $dias[] = "Jueves";
        $dias[] = "Viernes";
        $dias[] = "Sabado";
        $dia = $dias[$day_num];
        if (empty($this->strCodVend)) {
			$conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
            $sql = "Call SPSFA_DameDiasVisita(" . $this->idRuta . ",'".$this->IdEmpresa."'," . $this->Vendedor . ");";
            $result = $conn->query($sql);
            $_arr = array();
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                $_arr[] = array("Id" => $row[0],
                "Cve_Ruta" => utf8_encode($row[1]),
                "Cve_Cliente" => $row[2],
                "Cve_Vendedor" => $row[4],
                "Lu" => $row[5],
                "Ma" => $row[6],
                "Mi" => $row[7],
                "Ju" => $row[8],
                "Vi" => $row[9],
                "Sa" => $row[10],
                "Do" => $row[11]);
            }
			$conn->close();
        }
		else {
			$_arr = array();
            foreach ($this->strCodVend as $codven) {
                $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
                $sql = "Call SPSFA_DameDiasVisita(" . $this->idRuta . ",'".$this->IdEmpresa."',".$codven.");";
                $result = $conn->query($sql);
                while ($row = $result->fetch_array(MYSQLI_NUM)) {
                    $_arr[] = array("Id" => $row[0],
                "Cve_Ruta" => utf8_encode($row[1]),
                "Cve_Cliente" => $row[2],
                "Cve_Vendedor" => $row[4],
                "Lu" => $row[5],
                "Ma" => $row[6],
                "Mi" => $row[7],
                "Ju" => $row[8],
                "Vi" => $row[9],
                "Sa" => $row[10],
                "Do" => $row[11]);
                }
				$conn->close();
            }
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        return $_arr;
    }

    function Sql2JSONConteos()
    {
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_Continuidad(" . $this->idRuta . ");";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array(
                "DiaO" => $row[1],
                "FolVta" => $row[2],
                "FolPed" => $row[3],
                "FolDevol" => $row[4],
                "FolCob" => $row[5],
                "FolEntrega" => "1",
                "UDiaO" => $row[6],
                "CteNvo" => $row[7],
                "IdEmpresa" => $row[8]);
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function Sql2JSONBitac()
    {
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameBitacora(" . $this->idRuta . ");";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array(
                "Codigo" => $row[0],
                "Descripcion" => $row[1],
                "HI" => $row[2],
                "HF" => $row[3],
                "HT" => $row[4],
                "TS" => $row[5],
                "Visita" => $row[6],
                "Programado" => $row[7],
                "DiaO" => $row[8],
                "RutaId" => $row[9],
                "Cerrado" => $row[10],
                "IdV" => $row[11],
                "Tip" => $row[12],
                "latitude" => $row[13],
                "longitude" => $row[14],
                "pila" => $row[15],
                "IdEmpresa" => $row[16],
                "IdVendedor" => $row[17],
                "Id_Ayudante1" => $row[18],
                "Id_Ayudante2" => $row[19],
                "IdVehiculo" => $row[20]);
        }
/*		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;*/
        $_arr[] = array(
                "success" => true,
                "Msg" => 'Sincronizado Sql2JSONBitac');
        return $_arr;
    }

    function JSON2SqlClientes() {
		$Proceso="Actualiza Clientes";
        if (!empty($this->arr)) {
            foreach ($this->arr["Clientes"] as $arr) {
                $a = (array)$arr;
                $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
				$sql = "Call SPSFA_AddUpdateClientes('"
						. $a["CodCliente"] . "','"
						. $a["Nombre"] . "','"
						. $a["NombreC"] . "','"
						. $a["Direccion"] ."','"
						. $a["Referencia"] ."','"
						. $a["Tel"] . "','"
						. $a["CP"] . "',"
						. $a["LimiteCredito"] . ","
						. $a["DiasCredito"] . ",'"
						. $a["Colonia"] . "','"
						. $a["Tel2"] . "','"
						. $a["Email"] . "',"
						. $a["Saldo"] . ",'"
						. $this->IdEmpresa . "',"
						. $a["Latitude"] . ","
						. $a["Longitude"] . ",'"
						. $a["Email"] . "','"
						. $a["Ciudad"] . "','"
						. $a["Estado"] . "','"
						. $a["RFC"] . "');";
                $stmt = $conn->query($sql);
                if( $stmt === false )
                {
					$_arr[] = array(
							"success" => false,
							"Msg" => $mysqli->error);
					return $_arr;
                }
                sqlsrv_free_stmt( $stmt);
				$conn->close();
                $this->RegFolio("CteNvo", $this->idRuta, $a["Docto"]);
            }
        }
        $_arr[] = array(
                "success" => true,
                "Msg" => 'Sincronizado JSON2SqlClientes');
        return $_arr;
    }

    function JSON2SqlDetalleTicket() {
		$Proceso="Actualiza el detalle de la venta";
        if (!empty($this->arr)) {
            foreach ($this->arr["DetalleTicket"] as $arr) {
                $a = (array)$arr;
                $Pza = (empty($a["Pza"])) ? "0" : floatval($a["Pza"]);
                $Precio = (empty($a["Precio"])) ? "0" : floatval($a["Precio"]);
                $Descuento = (empty($a["DescMon"])) ? "0" : floatval($a["DescMon"]);
                $Importe = $a["Importe"];
                $_iva = $a["IVA"];
                $folio = $a["Docto"];
                $DiaO = $this->getDiaOperativoDocVenta($a["Docto"], $this->idRuta, $this->Vendedor);
				$a["Kg"] = str_replace(",", "", $a["Kg"]);
                $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
				$sql = "CALL SPSFA_ActualizaDetVentas('"
						. $a["Articulo"] . "','"
						. $a["Descripcion"] . "',"
						. $Precio . ","
						. $Pza . ","
						. $a["Kg"] . ","
						. $a["DescPorc"] . ","
						. $a["DescMon"] . ","
						. $a["Tipo"] . ","
						. $a["Docto"] . ","
						. $Importe . ","
						. $_iva . ","
						. $a["IEPS"] . ","
						. $this->idRuta . ","
						. $DiaO . ","
						. $a["PromoId"] . ",'"
						. $this->IdEmpresa . "');";
                $stmt = $conn->query($sql);
                if( $stmt === false )
                {
					$_arr[] = array(
							"success" => false,
							"Msg" => $mysqli->error);
                    // $this->registraErrores($mysqli->error, "Detalle Ventas");
					$conn->close();
					return $_arr;
                }
                $conn->close();
            }
        }
        $_arr[] = array(
                "success" => true,
                "Msg" => 'Sincronizado JSON2SqlDetalleTicket');
        return $_arr;
    }
	
	function getDiaOperativoDocVenta($Docto, $idRuta, $idVendedor) {
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "SELECT DiaO FROM Venta WHERE Documento = '".$Docto."' And RutaId='".$idRuta."' And VendedorId = '".$idVendedor."'; ";
        $result = $conn->query($sql);
        if( $result === false) {
            die( print_r($mysqli->error, true) );
        }
        $_arr = array();
		$row = $result->fetch_array(MYSQLI_NUM);
		$conn->close();
		return $row[0];
    }

    function JSON2SqlDetalleDevol() {
		$Proceso="Actualiza el detalle de la devolución";
        if (!empty($this->arr)) {
            foreach ($this->arr["DetalleDevol"] as $arr) {
                $a = (array)$arr;
                $a["Kg"] = 0;
                $a["DescMon"] = 0;
                $Pza = (empty($a["Pza"])) ? "0" : intval($a["Pza"]);
                $Precio = (empty($a["Precio"])) ? "0" : floatval($a["Precio"]);
                $Importe = (empty($a["Precio"])) ? "0" : floatval($a["Precio"]);
                $pu = $Importe / (1 + (floatval($a["IVA"])/100));
                $_iva = ($Importe - $pu) * $Pza;
                $Importe = $pu * $Pza;
                $conn = sqlsrv_connect($this->ip_server, $this->connectinfo);
                $folio = $a["Docto"];
                $a["Docto"] = (substr($folio,0, 2)=="20") ? substr($folio,2, strlen($folio)) : $folio;
                if (isset($a["Edo"])) $a["EDO"] = $a["Edo"];
                $edo = ($a["EDO"]=="true") ? 1 : 0;
				if (empty($this->DiaO)) $this->DiaO = $this->getDiaODevolucion($a["Docto"], $this->idRuta, $this->Vendedor);
                $r = $_SERVER["DOCUMENT_ROOT"]."/".basename(__DIR__)."/imagenes/Devoluciones/".$a["CodArticulo"]."_".$a["Docto"]."_".$this->idRuta."_".$this->DiaO."_".$this->Vendedor.".jpg";
                $url = "";
                if (file_exists($r)) $url = "http://".$_SERVER['SERVER_ADDR'].":8080/".basename(__DIR__)."/imagenes/Devoluciones/".$a["CodArticulo"]."_".$a["Docto"]."_".$a["idRuta"]."_".$this->DiaO."_".$this->Vendedor.".jpg";
				$sql = "CALL SPSFA_ActualizaDetDevoluciones('"
						. $a["CodArticulo"] . "',"
						. $a["Pza"] . ","
						. $a["Kg"] . ","
						. $a["Precio"] . ","
						. $a["Importe"] . ","
						. $edo . ",'"
						. $a["Motivo"] . "',"
						. $a["IVA"] . ","
						. $a["IEPS"] . ","
						. $a["Docto"] . ","
						. $a["idRuta"] . ","
						. $a["Tipo"] . ",'"
						. $this->IdEmpresa . "','"
						. $url . "');";
                $stmt = $conn->query($sql);
                if ($stmt === false) {
                    $this->registraErrores($mysqli->error, "Detalle Devolución");
                }
                sqlsrv_free_stmt($stmt);
                $conn->close();
            }
        }
        return "Sincronizado JSON2SqlDetalleDevol";
    }

    function getDiaODevolucion($Docto, $idRuta, $Vendedor) {
        $conn = sqlsrv_connect($this->ip_server, $this->connectinfo);
        if( $conn === false ) {
            die( print_r( sqlsrv_errors(), true));
        }
        $sql = "SELECT * FROM Devoluciones Where Ruta='" . $idRuta . "' And IdEmpresa='" . $this->IdEmpresa . "' And Docto='" . $Docto . "' And Vendedor='".$Vendedor."'";
        $result = sqlsrv_query($conn, $sql);
        if( $result === false) {
			sqlsrv_close($conn);
            die( print_r( sqlsrv_errors(), true) );
        }
        $_arr = array();
        if (sqlsrv_has_rows($result)) {
			sqlsrv_close($conn);
            $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
            return $row['DiaO'];
        }
		sqlsrv_close($conn);
        return "";
    }

    function JSON2SqlBitac() {
		Global $Proceso;
		$Proceso="Actualiza la bitácora";
		$conn =  new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        if (!empty($this->arr)) {
            foreach ($this->arr["Bitac"] as $arr) {
                $a = (array)$arr;
				if (strpos($a["HI"], "HH")!==false) {
					$a["HI"] = date("Y-m-d H:i:s");
				}
                $c = ($a["Cerrado"]=="True") ? "1" : "0";
                try {
                    $Id_Ayudante1 = $a["Id_Ayudante1"];
                    $Id_Ayudante2 = $a["Id_Ayudante2"];
                    if (empty($Id_Ayudante1)) $Id_Ayudante1 = "0";
                    if (empty($Id_Ayudante2)) $Id_Ayudante2 = "0";
                    $sql = "CALL SPSFA_ActualizaBitacora('"
							. $a["Codigo"] . "','"
//							. $this->SacoDescCodigo($a["Codigo"]). "','"
							. $a["HI"] . "','"
							. $a["HF"] . "',"
							. $a["Visita"] . ","
							. $a["Prog"] . ","
							. $a["DiaO"] . ","
							. $this->idRuta . ","
							. $c . ","
							. $a["IdV"] . ",'"
							. $a["Tip"] ."',"
							. $a["latitude"] . ","
							. $a["longitude"] . ","
							. $a["pila"] . ",'"
							. $this->IdEmpresa . "',"
							. $this->Vendedor . ","
							. $Id_Ayudante1 . ","
							. $Id_Ayudante2 .","
							. $a["Vehiculo"] . ");";
					$stmt1 = $conn->query($sql);
					if( $stmt1 === false )
					{
				        $_arr[] = array(
					                "success" => false,
					                "Msg" => $mysqli->error);
				        return $_arr;
					}
                } catch (Exception $e) {
                    $error = $e->getMessage();
					$_arr[] = array(
								"success" => false,
								"Msg" => $error);
					return $_arr;
                }
            }
			$conn->close();
        }
        $_arr[] = array(
                "success" => true,
                "Msg" => "Sincronizado JSON2SqlBitac");
        return $_arr;
    }

    function SacoDescCodigo($code)
    {
        $ret = "";
        switch ($code) {
            case "A18253":
                $ret = "INICIO DE DIA";
                break;
            default:
                $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
                if( $conn === false ) {
                    $this->registraErrores($mysqli->error, true);
                }
                $sql = "SELECT Operacion FROM CodesOp WHERE Codi='" . code . "';";
                $result = $conn->query($sql);
                if($result === false) {
                    $this->registraErrores($mysqli->error, true);
                }
                if ($row = $result->fetch_array(MYSQLI_NUM)) {
                    $ret = $row[0];
                } else {
                    $ret = "VISITA A CLIENTE";
                }
				$conn->close();
                break;
        }
        return $ret;
    }

    function dateDiff($start, $end) {
        $start_ts = strtotime($start);
        $end_ts = strtotime($end);
        $diff = $end_ts - $start_ts;
        return round($diff / 3600);
    }

    function JSON2SqlActualizaStock() {
		//if ($this->validaStock===1){
		$Proceso="Actualiza el stock";
			if (!empty($this->arr)) {
				foreach ($this->arr["StockHis"] as $arr) {
					$conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
					$a = (array)$arr;
					if (empty($a["StockA"])) {
						$StockA=0;
					}
					else{
						$StockA=$a["StockA"];
					}
					$sql = "CALL SPSFA_ActualizaStock('"
								. $a["Articulo"] . "',"
								. $this->idRuta . ","
								. $a["DiaO"] . ","
								. $a["Stock"] . ","
								. $StockA . ",'"
								. $this->IdEmpresa . "');";
					$stmt1 = $conn->query($sql);
					if( $stmt1 === false )
					{
						$this->registraErrores($mysqli->error, "Stock");
        				$_arr[] = array(
                			"success" => false,
                			"Msg" => $stmt1->error);
        				return $_arr;
					}
					$conn->close();

				}
			}
		//}
        $_arr[] = array(
                "success" => true,
                "Msg" => "Sincronizado JSON2SqlActualizaStock");
        return $_arr;
    }

    function JSON2SqlPedidos() {
		$Proceso="Actualiza los pedidos";
        if (!empty($this->arr)) {
			$Count=count($this->arr["Pedidos"]);
            for($i=0; $i<$Count; $i++) {
				$arr=(array)$this->arr["Pedidos"][$i];
                $a = (array)$arr;
                $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
                $c = ($a["Cancelado"]=="False") ? "0" : "1";
                $Id_Ayudante1 = (empty($a["Id_Ayudante1"])) ? "0" : $a["Id_Ayudante1"];
                $Id_Ayudante2 = (empty($a["Id_Ayudante2"])) ? "0" : $a["Id_Ayudante2"];
                $kg = (empty($a["KG"])) ? "0" : $a["KG"];
				if (strpos($a["Fecha"], "HH")!==false) {
					$a["Fecha"] = date("Y-m-d H:i:s");
				}
				if (strpos($a["FechaEntrega"], "HH")!==false) {
					$a["FechaEntrega"] = date("Y-m-d H:i:s");
				}
				if (strpos($a["FechaVence"], "HH")!==false) {
					$a["FechaVence"] = date("Y-m-d H:i:s");
				}
				$sql = "CALL SPSFA_ActualizaPedidos("
				. $a["Docto"] . ",'"
				. $a["Fecha"] . "','"
				. $a["CodCliente"] . "','"
				. $a["Tipo"] . "',"
				. $this->idRuta . ","
				. $this->Vendedor . ",'"
				. $a["FechaVence"] . "',"
				. $a["DiaO"] . ",'"
				. $a["DocSalida"] . "',"
				. $a["Cancelado"] . ",'"
				. $a["FechaEntrega"] . "','"
				. $this->IdEmpresa . "',"
				. $this->getFormaPag($a["FormaP"]) . ");";
                $stmt=$conn->query($sql);
                if( $stmt === false )
                {
					$_arr[] = array(
                		"success" => false,
                		"Msg" => $conn->error);
        			return $_arr;
                    /* $this->registraErrores($mysqli->error, "Encabezado Pedido") */;
                }
                $conn->close();
            }
        }
        $_arr[] = array(
                "success" => true,
                "Msg" => "Sincronizado JSON2SqlPedidos");
        return $_arr;
    }

    function JSON2SqlPedidosDev() {
		$Proceso="Actualiza las devoluciones de venta";
        if (!empty($this->arr)) {
			$Count=count($this->arr["PedidosServer"]);
            for($i=0; $i<$Count; $i++) {
				$arr=(array)$this->arr["PedidosServer"][$i];
                $a = (array)$arr;
                $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
				$sql = "CALL SPSFA_PedidosDev("
				. $this->idRuta . ","
				. $this->Vendedor . ",'"
				. $a["Docto"] . "','"
				. $this->IdEmpresa . "');";
                $stmt=$conn->query($sql);
                if( $stmt === false )
                {
					$_arr[] = array(
                		"success" => false,
                		"Msg" => $mysqli->error);
        			return $_arr;
                }
                $conn->close();
            }
        }
        $_arr[] = array(
                "success" => true,
                "Msg" => "Sincronizado JSON2SqlPedidosDev");
        return $_arr;
    }

    function getFormaPag($Clave) {
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "SELECT * FROM FormasPag WHERE Clave = '".$Clave."';";
        $result = $conn->query($sql);
        if( $result === false) {
            die( print_r($mysqli->error, true) );
        }
        $_arr = array();
        if ($row = $result->fetch_array(MYSQLI_NUM)) {
			$conn->close();
            return $row[0];
        }
    }

    function JSON2SqlDetallePed() {
		$Proceso="Actualiza el detalle de los pedidos";
        if (!empty($this->arr)) {
            foreach ($this->arr["DetallePed"] as $arr) {
                $a = (array)$arr;
                $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
                $c = ($a["Cancelada"] == "False") ? "0" : "1";
                $a["Kg"] = str_replace(",", "", $a["Kg"]);
                $a["Precio"] = str_replace(",", "", $a["Precio"]);
                $a["IVA"] = str_replace(",", "", $a["IVA"]);
                $a["DesctoV"] = str_replace(",", "", $a["DesctoV"]);
                $Descuento = (empty($a["DesctoV"])) ? 0 : floatval($a["DesctoV"]);
                $Pza = (empty($a["Pza"])) ? 0 : intval($a["Pza"]);
                $Precio = (empty($a["Precio"])) ? 0 : floatval($a["Precio"]);
                $IdIncidencia = (empty($a["IdIncidencia"])) ? NULL : $a["IdIncidencia"];
                $Tipo = (empty($a["Tipo"])) ? 0 : $a["Tipo"];
                $bp = (empty($a["SKU_Promo"])) ? "0" : "1";
                $_iva = floatval($a["IVA"]);
                $_pu = $Precio;
				$sql = "CALL SPSFA_ActualizaDetPedidos('"
						. $a["Articulo"] . "',"
						. $Pza . ","
						. $_pu . ","
						. $_iva . ",'"
						. $a["Folio"] . "',"
						. $this->idRuta . ","
						. $Descuento . ","
						. $Tipo . ");";
                $stmt = $conn->query($sql);
                if ($stmt === false) {
					$_arr[] = array(
                				"success" => false,
                				"Msg" => $stmt->error);
        			return $_arr;
                    /* $this->registraErrores($mysqli->error, "Detalle Pedido"); */
                }
                $conn->close();
            }
        }
        $_arr[] = array(
                "success" => true,
                "Msg" => "Sincronizado JSON2SqlDetallePedidos");
        return $_arr;
    }
	
	function LiberaPedidos() {
        $arr_tmp = array_fill( 0, 3, "?");
        $signos_interrogacion = join(",", $arr_tmp);
        $tsql_callSP = "{CALL SPAD_LiberarPedidos(".$signos_interrogacion.")}";
        $params = array(
            array(date("Y-m-d H:i:s"), SQLSRV_PARAM_IN),
            array($this->idRuta, SQLSRV_PARAM_IN),
            array($this->IdEmpresa, SQLSRV_PARAM_IN)
        );
        $conn = sqlsrv_connect($this->ip_server, $this->connectinfo);
        $stmt3 = sqlsrv_query($conn, $tsql_callSP, $params);
        if ($stmt3 === false) {
			sqlsrv_close( $conn);
            echo "Error in executing statement 3.\n";
            die(print_r(sqlsrv_errors(), true));
        }
        sqlsrv_free_stmt($stmt3);
        sqlsrv_close( $conn);
    }

    function JSON2SqlRelOperaciones()
    {
		$Proceso="Actualiza la relación de operaciones";
        if (!empty($this->arr)) {
            foreach ($this->arr["RelOperaciones"] as $arr) {
				$conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
                $a = (array)$arr;
                $folio = $a["Folio"];
                $a["Folio"] = (substr($folio,0, 2)=="20") ? substr($folio,2, strlen($folio)) : $folio;
				if (strpos($a["Fecha"], "HH")!==false) {
					$a["Fecha"] = date("Y-m-d H:i:s");
				}
				$sql = "CALL SPSFA_ActualizaOperaciones(" . $a["IdVisita"] . ",'"
						. $a["Folio"] . "',"
						. $this->idRuta . ","
						. $a["DiaO"] . ",'"
						. $a["CodCliente"] . "','"
						. $a["Tipo"] . "',"
						. $a["Total"] . ",'"
						. $a["Fecha"] . "','"
						. $this->IdEmpresa . "');";
				if ($a["Tipo"]==="Venta" or $a["Tipo"]==="Entrega" or $a["Tipo"]==="Devolucion")
				{
                    $this->ActualizaStock=1;
					$this->validaStock=1;
                }
                $stmt1 = $conn->query($sql);
                if( $stmt1 === false )
                {
                    $this->registraErrores($mysqli->error, "Operaciones");
                }
				$conn->close();
            }
        }
        $_arr[] = array(
                "success" => true,
                "Msg" => "Sincronizado JSON2SqlRelOperaciones");
        return $_arr;
    }

    function JSON2SqlVisitas()
    {
		$Proceso="Actualiza las visitas";
        $conn = sqlsrv_connect($this->ip_server, $this->connectinfo);
        $this->dropProcedure('SPAD_ActualizaVisitas_tmp');
        $sql = "CREATE PROCEDURE [dbo].[SPAD_ActualizaVisitas_tmp](
                @CodCliente		varchar(50),
                @DiaO			int,
                @FechaI			varchar(50),
                @EnSecuencia	bit,
                @FechaF			varchar(50),
                @Venta			Numeric(18,0),
                @Pedido			Numeric(18,0),
                @Devolucion		Numeric(18,0),
                @Cobranza		Numeric(18,0),
                @IdCe			Numeric(18,0),
                @ruta			int,
                @IdEmpresa	    varchar(50))
                AS
                Begin
                    set @FechaI = convert(datetime, @FechaI, 120)
                    set @FechaF = convert(datetime, @FechaF, 120)
                    if Not Exists(SELECT * FROM Visitas Where ruta=@ruta And IdEmpresa=@IdEmpresa And DiaO=@Diao And CodCliente=@CodCliente)
                    begin
                        Insert into Visitas(CodCliente,DiaO,FechaI,EnSecuencia,FechaF,Venta,Pedido,Devolucion,Cobranza,IdCe,ruta,IdEmpresa)
                        Values(@CodCliente,@DiaO,@FechaI,@EnSecuencia,@FechaF,@Venta,@Pedido,@Devolucion,@Cobranza,@IdCe,@ruta,@IdEmpresa)
                    end
                End";
        $conn = sqlsrv_connect($this->ip_server, $this->connectinfo);
        $stmt2 = sqlsrv_query( $conn, $sql);
        if( $stmt2 === false )
        {
			sqlsrv_close( $conn);
            echo "Error in executing statement 2.\n";
            die( print_r( sqlsrv_errors(), true));
        }
        sqlsrv_free_stmt( $stmt2);
        sqlsrv_close( $conn);
        $arr_tmp = array_fill( 0, 12, "?");
        $signos_interrogacion = join(",", $arr_tmp);
        $tsql_callSP = "{CALL SPAD_ActualizaVisitas_tmp(".$signos_interrogacion.")}";
        if (!empty($this->arr)) {
            foreach ($this->arr["Visitas"] as $arr) {
                $a = (array)$arr;
                $conn = sqlsrv_connect($this->ip_server, $this->connectinfo);
                $es = ($a["EnSecuencia"]=="True") ? "1" : "0";
                $v = ($a["Venta"]=="") ? "0" : $a["Venta"];
                $p = ($a["Pedido"]=="") ? "0" : $a["Pedido"];
                $d = ($a["Devolucion"]=="") ? "0" : $a["Devolucion"];
                $c = ($a["Cobranza"]=="") ? "0" : $a["Cobranza"];
                $params = array(
                    array($a["CodCliente"], SQLSRV_PARAM_IN),
                    array($a["DiaO"], SQLSRV_PARAM_IN),
                    array($a["FechaI"], SQLSRV_PARAM_IN),
                    array($es, SQLSRV_PARAM_IN),
                    array($a["FechaF"], SQLSRV_PARAM_IN),
                    array($v, SQLSRV_PARAM_IN),
                    array($p, SQLSRV_PARAM_IN),
                    array($d, SQLSRV_PARAM_IN),
                    array($c, SQLSRV_PARAM_IN),
                    array($a["Id"], SQLSRV_PARAM_IN),
                    array($this->idRuta, SQLSRV_PARAM_IN),
                    array($this->IdEmpresa, SQLSRV_PARAM_IN)
                );
                $stmt3 = sqlsrv_query( $conn, $tsql_callSP, $params);
                if( $stmt3 === false )
                {
					sqlsrv_close( $conn);
                    echo "Error in executing statement 3.\n";
                    die( print_r( sqlsrv_errors(), true));
                }
                sqlsrv_free_stmt( $stmt3);
                sqlsrv_close( $conn);
            }
        }
        $this->dropProcedure('SPAD_ActualizaVisitas_tmp');
        sqlsrv_free_stmt( $stmt2);
        sqlsrv_free_stmt( $stmt3);
        sqlsrv_close( $conn);
        return "Sincronizado JSON2SqlVisitas";
    }

    function getFormaFago($fp)
    {
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        if ($conn === false) {
            die(print_r($mysqli->error, true));
        }
        $sql = "SELECT * FROM FormasPag WHERE Clave = '" . $fp . "';";
        $result = $conn->query($sql);
        if ($result === false) {
			sqlsrv_close( $conn);
            die(print_r($mysqli->error, true));
        }
        $row = $result->fetch_array(MYSQLI_NUM);
        return $row[0];
        sqlsrv_close( $conn);
    }

    function JSON2SqlVentas()
    {
		$Proceso="Actualiza las ventas";
		if (!empty($this->arr)) {
            foreach ($this->arr["Ventas"] as $arr) {
                $a = (array)$arr;
                $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
                $c = ($a["Cancelada"]=="False") ? "0" : "1";
                $LimitCred = (empty($a["LimitCred"])) ? "0" : $a["LimitCred"];
                $kg = (empty($a["Kg"])) ? "0" : $a["Kg"];
                $Id_Ayudante1 = (empty($a["Id_Ayudante1"])) ? "" : $a["Id_Ayudante1"];
                $Id_Ayudante2 = (empty($a["Id_Ayudante2"])) ? "" : $a["Id_Ayudante2"];
                $fp = $this->getFormaFago($a["FormaP"]);
                $a["SubTotal"] = (empty($a["SubTotal"])) ? "0" : $a["SubTotal"];
				if (strpos($a["Fecha"], "HH")!==false) {
					$a["Fecha"] = date("Y-m-d H:i:s");
				}
				if (strpos($a["FechaVence"], "HH")!==false) {
					$a["FechaVence"] = date("Y-m-d H:i:s");
				}
				$sql = "Call SPSFA_ActualizaVentas("
						. $this->idRuta . ","
						. $a["Vendedor"] . ",'"
						. $a["CodCli"] . "',"
						. $a["Folio"] . ",'"
						. $a["Fecha"] . "','"
						. $a["Tipo"] . "',"
						. $a["DiasCred"] . ","
						. $LimitCred . ",'"
						. $a["FechaVence"] . "',"
						. $a["SubTotal"] . ","
						. $a["IVA"] . ","
						. $a["IEPS"] . ","
						. $a["Total"] . ",'"
						. $a["EnLetra"] . "',"
						. $a["Items"] . ","
						. $fp . ",'"
						. $a["DocSalida"] . "',"
						. $a["DiaO"] . ","
						. $c . ","
						. $kg . ",'"
						. $Id_Ayudante1 . "','"
						. $Id_Ayudante2 . "','"
						. $this->IdEmpresa . "');";
                $stmt = $conn->query($sql);
                if( $stmt === false )
                {
					$_arr[] = array(
							"success" => false,
							"Msg" => $mysqli->error);
					return $_arr;
					$conn->close();
                    // $this->registraErrores($mysqli->error, "Encabezado Ventas");
                }
                $conn->close();
            }
        }
        $_arr[] = array(
                "success" => true,
                "Msg" => 'Sincronizado JSON2SqlVentas');
        return $_arr;
    }

    function JSON2SqlPromociones()
    {
		$Proceso="Actualiza las promociones";
        if (!empty($this->arr)) {
            foreach ($this->arr["Promociones"] as $arr) {
                $a = (array)$arr;
                $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
				$a["DiaO"] = (empty($a["DiaO"])) ? $this->DiaO : $a["DiaO"];
				$sql = "CALL SPSFA_ActualizaPromos('"
						. $a["SKU"] . "',"
						. $this->idRuta . ","
						. $a["DiaO"] . ",'"
						. $a["Docto"] . "','"
						. $a["CodCliente"] . "',"
						. $a["Cant"] . ",'"
						. $a["TipMed"] . "','"
						. $this->IdEmpresa . "','"
						. $a["Tipo"] . "');";
                $stmt = $conn->query($sql);
                if( $stmt === false )
                {
                    $_arr[] = array(
                			"success" => false,
	                		"Msg" => $misqli->error);
			        return $_arr;
                }
                $conn->close();
            }
        }
        return "Sincronizado JSON2SqlPromociones";
    }

    function JSON2SqlDevEnvases()
    {
		$Proceso="Actualiza la devolución de envases";
        if (!empty($this->arr)) {
            foreach ($this->arr["DevEnvases"] as $arr) {
                $a = (array)$arr;
                $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
				$sql = "CALL SPSFA_ActualizaSaldoEnvases("
					. $this->idRuta . ","
					. $a["DiaO"] . ",'"
					. $a["CodCli"] . "','"
					. $a["Docto"] . "','"
					. $a["Articulo"] . "',"
					. $a["Cantidad"] . ","
					. $a["Devuelto"] . ",'"
					. $a["Tipo"] . "','"
					. $a["Envase"] . "','"
 					. $this->IdEmpresa . "');";
                $stmt = $conn->query($sql);
                if( $stmt === false )
                {
                    $_arr[] = array(
                			"success" => false,
	                		"Msg" => $misqli->error);
			        return $_arr;
                }
                $conn->close();
            }
        }
        return "Sincronizado JSON2SqlDevEnvases";
    }

    function JSON2SqlActualizaDevEnvases()
    {
		$Proceso="Actualiza la devolución de envases";
        if (!empty($this->arr)) {
            foreach ($this->arr["DevEnvases"] as $arr) {
                $a = (array)$arr;
                $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
				$sql = "CALL SPSFA_DevolucionEnvases('"
 					. $this->IdEmpresa . "',"
					. $this->idRuta . ","
					. $a["DiaO"] . ",'"
					. $a["Fecha"] . "','"
					. $a["CodCli"] . "','"
					. $a["DoctoRef"] . "','"
					. $a["FolioDev"] . "','"
					. $a["Envase"] . "',"
					. $a["Devuelto"] . ",'"
					. $a["Tipo"] . "');";
                $stmt = $conn->query($sql);
                if( $stmt === false )
                {
                    $_arr[] = array(
                			"success" => false,
	                		"Msg" => $misqli->error);
			        return $_arr;
                }
                $conn->close();
            }
        }
        return "Sincronizado JSON2SqlDevEnvases";
    }

    function JSON2SqlDevol()
    {
		$Proceso="Actualiza las devoluciones";
        $this->dropProcedure('SPAD_ActualizaDevoluciones_tmp');
        $sql = "CREATE PROCEDURE [dbo].[SPAD_ActualizaDevoluciones_tmp](
                @CodCliente		varchar(50),
                @Ruta			int,
                @Vendedor		int,
                @Fecha			varchar(50),
                @Items			int,
                @Kg				varchar(50),
                @IVA			money,
                @IEPS			money,
                @SubTotal		money,
                @TOTAL			money,
                @EnLetras		nvarchar(MAX),
                @DiaO			int,
                @Docto			int,
                @Cancelada		bit,
                @IdEmpresa		varchar(50))
                AS
                Begin
                    set @Fecha = convert(datetime, @Fecha, 120)
                    set @Kg = CAST(@Kg as numeric)
                    if Not Exists(SELECT * FROM Devoluciones Where Ruta=@Ruta And IdEmpresa=@IdEmpresa And DiaO=@Diao And CodCliente=@CodCliente And Docto=@Docto)
                    begin
                        Insert into Devoluciones(CodCliente,Devol,Fecha,Status,Ruta,Vendedor,Items,KG,IVA,IEPS,Subtotal,Total,EnLetras,DiaO,Docto,Cancelada,IdEmpresa)
                        Values(@CodCliente,@Docto,@Fecha,1,@Ruta,@Vendedor,@Items,@KG,@IVA,@IEPS,@Subtotal,@Total,@EnLetras,@DiaO,@Docto,@Cancelada,@IdEmpresa)
						UPDATE Continuidad SET FolDevol=@Docto WHERE RutaID=@Ruta And IdEmpresa=@IdEmpresa
                    end
					else
					begin
                    	UPDATE Devoluciones
                        SET    
                        CodCliente = @CodCliente, 
                        Devol = @Docto, 
                        Fecha = @Fecha, 
                        Status = 1, 
                        Ruta = @Ruta, 
                        Vendedor = @Vendedor, 
                        Items = @Items, 
                        KG = @KG, 
                        IVA = @IVA, 
                        IEPS = @IEPS, 
                        Subtotal = @Subtotal, 
                        Total = @Total, 
                        EnLetras = @EnLetras, 
                        DiaO = @DiaO, 
                        Docto = @Docto, 
                        Cancelada = @Cancelada, 
                        IdEmpresa = @IdEmpresa
                        WHERE  Ruta=@Ruta And IdEmpresa=@IdEmpresa And DiaO=@DiaO And CodCliente=@CodCliente And Docto=@Docto
                    end
                End";
        $conn = sqlsrv_connect($this->ip_server, $this->connectinfo);
        $stmt2 = sqlsrv_query( $conn, $sql);
        if( $stmt2 === false )
        {
			sqlsrv_close( $conn);
            echo "Error in executing statement 2.\n";
            die( print_r( sqlsrv_errors(), true));
        }
        sqlsrv_free_stmt( $stmt2);
        sqlsrv_close( $conn);
        $arr_tmp = array_fill( 0, 15, "?");
        $signos_interrogacion = join(",", $arr_tmp);
        $tsql_callSP = "{CALL SPAD_ActualizaDevoluciones_tmp(".$signos_interrogacion.")}";
        if (!empty($this->arr)) {
            foreach ($this->arr["Devol"] as $arr) {
                $a = (array)$arr;
                $conn = sqlsrv_connect($this->ip_server, $this->connectinfo);
                $kg = (empty($a["Kg"])) ? "0" : $a["Kg"];
                $Pza = (empty($a["Pza"])) ? "0" : intval($a["Pza"]);
                $Precio = (empty($a["Total"])) ? "0" : floatval($a["Total"]);
                $Importe = (empty($a["Total"])) ? "0" : floatval($a["Total"]);
                $pu = $Importe / (1 + (floatval($a["IVA"])/100));
                $_iva = ($Importe - $pu);
                $Importe = $pu * $Pza;
				$params = array(
                    array($a["CodCliente"], SQLSRV_PARAM_IN),
                    array($this->idRuta, SQLSRV_PARAM_IN),
                    array($this->Vendedor, SQLSRV_PARAM_IN),
                    array($a["Fecha"], SQLSRV_PARAM_IN),
                    array($a["Items"], SQLSRV_PARAM_IN),
                    array($kg, SQLSRV_PARAM_IN),
                    array($_iva, SQLSRV_PARAM_IN),
                    array($a["IEPS"], SQLSRV_PARAM_IN),
                    array($pu, SQLSRV_PARAM_IN),
                    array($a["Total"], SQLSRV_PARAM_IN),
                    array($a["EnLetras"], SQLSRV_PARAM_IN),
                    array($a["DiaO"], SQLSRV_PARAM_IN),
                    array($a["Docto"], SQLSRV_PARAM_IN),
                    array($a["Cancelada"], SQLSRV_PARAM_IN),
                    array($this->IdEmpresa, SQLSRV_PARAM_IN)
                );
                $stmt3 = sqlsrv_query( $conn, $tsql_callSP, $params);
                if( $stmt3 === false )
                {
					sqlsrv_close( $conn);
                    echo "Error in executing statement 3.\n";
                    die( print_r( sqlsrv_errors(), true));
                }
                sqlsrv_free_stmt( $stmt3);
                sqlsrv_close( $conn);
            }
        }
        $this->dropProcedure('SPAD_ActualizaDevoluciones_tmp');
        sqlsrv_close( $conn);
        return "Sincronizado JSON2SqlDevol";
    }

    function dropProcedure($n) {
        $conn = sqlsrv_connect($this->ip_server, $this->connectinfo);
        /* Drop the stored procedure if it already exists. */
        $tsql_dropSP = "IF OBJECT_ID('$n', 'P') IS NOT NULL  
                DROP PROCEDURE $n";
        $stmt1 = sqlsrv_query( $conn, $tsql_dropSP);
        if( $stmt1 === false )
        {
			sqlsrv_close( $conn);
            echo "Error in executing statement 1.\n";
            die( print_r( sqlsrv_errors(), true));
        }
        sqlsrv_free_stmt( $stmt1);
        sqlsrv_close( $conn);
    }

    function JSON2SqlEntregas() {
        if (!empty($this->arr)) {
		$Proceso="Actualiza las entregas";
            foreach ($this->arr["Entregas"] as $arr) {
                $a = (array)$arr;
                $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
                $c = ($a["Cancelado"]=="False") ? "0" : "1";
                $Id_Ayudante1 = (empty($a["Id_Ayudante1"])) ? "0" : $a["Id_Ayudante1"];
                $Id_Ayudante2 = (empty($a["Id_Ayudante2"])) ? "0" : $a["Id_Ayudante2"];
                $kg = (empty($a["Kg"])) ? "0" : $a["Kg"];
                $IdIncidencia = (empty($a["IdIncidencia"])) ? "0" : $a["IdIncidencia"];
                $folio = $a["Docto"];
                $a["Docto"] = (substr($folio,0, 2)=="20") ? substr($folio,2, strlen($folio)) : $folio;
				if (strpos($a["FechaPedido"], "HH")!==false) {
					$a["FechaPedido"] = date("Y-m-d H:i:s");
				}
				if (strpos($a["FechaEntrega"], "HH")!==false) {
					$a["FechaEntrega"] = date("Y-m-d H:i:s");
				}
				if (strpos($a["FechaLib"], "HH")!==false) {
					$a["FechaLib"] = date("Y-m-d H:i:s");
				}
				$sql = "CALL SPSFA_ActualizaEntregas('"
						. $a["Folio"] . "',"
						. $this->idRuta . ",'"
						. $a["CodCliente"] . "','"
						. $a["FechaPedido"] . "','"
						. $a["FechaEntrega"] . "',"
						. $c . ","
						. $IdIncidencia . ","
						. $a["IdVendedor"] . ","
						. $Id_Ayudante1 . ","
						. $Id_Ayudante2 . ",'"
						. $this->IdEmpresa . "',"
						. $this->idRuta . ","
						. $a["DiaO"] . ");";
                $stmt = $conn->query($sql);
                if( $stmt === false )
                {
                    $_arr[] = array(
                			"success" => false,
	                		"Msg" => $misqli->error);
			        return $_arr;
                }
                $conn->close();
            }
        }
        return "Sincronizado JSON2SqlEntregas";
    }

    function JSON2SqlDetalleEnt() {
		$Proceso="Actualiza el detalle de las entregas";
        if (!empty($this->arr)) {
            foreach ($this->arr["DetalleEnt"] as $arr) {
                $a = (array)$arr;
                $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
                $c = ($a["Cancelada"] == "False") ? "0" : "1";
                $kg = (empty($a["Kg"])) ? "0" : $a["Kg"];
                $IdIncidencia = (empty($a["IdIncidencia"])) ? NULL : $a["IdIncidencia"];
                $bp = (empty($a["SKU_Promo"])) ? "0" : "1";
                $Pza = (empty($a["Pza"])) ? "0" : intval($a["Pza"]);
                $Precio = (empty($a["Precio"])) ? "0" : floatval($a["Precio"]);
                $Descuento = (empty($a["Descuento"])) ? "0" : floatval($a["Descuento"]);
                $Importe = (empty($a["Precio"])) ? "0" : floatval($a["Precio"]);
                $pu = $Importe / (1 + (floatval($a["IVA"])/100));
                $Importe = floatval($a["Precio"]) * $Pza;
                $_iva = ($Importe) * (floatval($a["IVA"])/100);
				$sql = "CALL SPSFA_ActualizaDetalleEntregas('"
						. $a["Folio"] . "','"
						. $a["Articulo"] . "',"
						. $Pza . ","
						. $Pza . ","
						. $Precio . ","
						. $_iva . ","
						. $Descuento . ");";
                $stmt = $conn->query($sql);
                if ($stmt === false) {
                    $_arr[] = array(
                			"success" => false,
	                		"Msg" => $misqli->error);
					$conn->close();
			        return $_arr;
                }
                $conn->close();
            }
        }
        return "Sincronizado JSON2SqlDetalleEntregas";
    }

    function RegFolio($tipo, $rutaIdf, $foli) {
        $conn = sqlsrv_connect($this->ip_server, $this->connectinfo);
        try {
            switch ($tipo) {
                case "Ventas":
                    $sql = "UPDATE Continuidad SET FolVta='" . $foli . "' WHERE RutaId='" . $rutaIdf . "'";
                    $stmt = sqlsrv_query($conn, $sql);
                    if( $stmt === false ) { echo "Error in executing statement 2.\n"; die( print_r( sqlsrv_errors(), true)); }
                    break;
                /*case "Pedidos":
                    $sql = "UPDATE Continuidad SET FolPed='" . $foli . "' WHERE RutaId='" . $rutaIdf . "'";
                    $stmt = sqlsrv_query($conn, $sql);
                    if( $stmt === false ) { echo "Error in executing statement 2.\n"; die( print_r( sqlsrv_errors(), true)); }
                    break;*/
                case "Devoluciones":
                    $sql = "UPDATE Continuidad SET FolDevol='" . $foli . "' WHERE RutaId='" . $rutaIdf . "'";
                    $stmt = sqlsrv_query($conn, $sql);
                    if( $stmt === false ) { echo "Error in executing statement 2.\n"; die( print_r( sqlsrv_errors(), true)); }
                    break;
                case "Cobranza":
                    $sql = "UPDATE Continuidad SET FolCob='" . $foli . "' WHERE RutaId='" . $rutaIdf . "'";
                    $stmt = sqlsrv_query($conn, $sql);
                    if( $stmt === false ) { echo "Error in executing statement 2.\n"; die( print_r( sqlsrv_errors(), true)); }
                    break;
                case "CteNvo":
                    $sql = "UPDATE Continuidad SET CteNvo='" . $foli . "' WHERE RutaId='" . $rutaIdf . "'";
                    $stmt = sqlsrv_query($conn, $sql);
                    if( $stmt === false ) { echo "Error in executing statement 2.\n"; die( print_r( sqlsrv_errors(), true)); }
                    break;
            }
            sqlsrv_free_stmt($stmt);
            sqlsrv_close($conn);
        }
        catch (Exception $ex)
        {
            $_mensaje = "Registro Folio: " . $ex;
        }
    }
	
	function JSON2SqlMedidores() {
		$Proceso="Actualiza medidores";
        if (!empty($this->arr)) {
            foreach ($this->arr["Medidores"] as $arr) {
                $a = (array)$arr;
                $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
                $odIn = empty($a["OdI"]) ? 0 : floatval($a["OdI"]);
                $odFi = empty($a["OdF"]) ? 0 : floatval($a["OdF"]);
                $tkIn = empty($a["TkI"]) ? 0 : floatval($a["TkI"]);
                $tkFi = empty($a["TkF"]) ? 0 : floatval($a["TkF"]);
                $ltC = empty($a["Ltc"]) ? 0 : floatval($a["Ltc"]);
                $gl = $tkIn + $tkFi;
                $KmR = $odFi - $odIn;
                if ($gl == 0) {
                    $Rendimiento = 0;
                } else {
                    $Rendimiento = $KmR/(($tkIn+$ltC)-$tkFi);
                }
                $IdVehiculo = isset($a["IdVehiculo"]) ? $a["IdVehiculo"] : "0";
				$sql = "CALL SPSFA_ActualizaMedidores("
						. $this->idRuta . ","
						. $a["DiaO"] . ","
						. $odIn . ","
						. $odFi . ","
						. $tkIn . ","
						. $tkFi . ","
						. $a["Ltc"] . ","
						. $gl . ","
						. $KmR . ",'"
						. $this->IdEmpresa . "',"
						. $Rendimiento . ","
						. $IdVehiculo . ");";
                $stmt = $conn->query($sql);
                if( $stmt === false )
                {
                    //$this->registraErrores($mysqli->error, "Medidores");
                }
                $conn->close();
            }
        }
        return "Sincronizado JSON2SqlMedidores";
    }
	
	function JSON2SqlPagos() {
		$Proceso="Actualiza pagos cobranza";
        if (!empty($this->arr)) {
            foreach ($this->arr["Pagos"] as $arr) {
                $a = (array)$arr;
                $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
                $fp = $this->getFormaPag($a["FormaP"]);
				if (strpos($a["Fecha"], "HH")!==false) {
					$a["Fecha"] = date("Y-m-d H:i:s");
				}
				$c = ($a["Cancelada"] == "False") ? "0" : "1";
				$sql = "CALL SPSFA_ActualizaPagosCobranza("
				. $a["DiaO"] . ",'"
				. $a["CodCliente"] . "','"
				. $a["Cuenta"] . "',"
				. $a["Abono"] . ",'"
				. $a["Fecha"] . "',"
				. $this->idRuta . ","
				. $a["SaldoAnt"] . ","
				. $a["SaldoFinal"] . ","
				. $fp . ","
				. $c . ",'"
				. $this->IdEmpresa . "');";
                $stmt = $conn->query($sql);
                if( $stmt === false )
                {
                    $this->registraErrores($mysqli->error, "Pagos");
                }
                $conn->close();
            }
        }
        return "Sincronizado JSON2SqlPagos";
    }
	
	function JSON2SqlStockPaseado() {
		$Proceso="Actualiza el stock sobrante";
        $this->dropProcedure("SPAD_ActualizaProductoPaseado_tmp");
        $sql = "CREATE PROCEDURE SPAD_ActualizaProductoPaseado_tmp(
                @CodProd varchar(50),
				@RutaId int,
				@DiaO int,
				@Stock numeric,
				@IdEmpresa varchar(50))
                AS
                BEGIN
                    if Not Exists(Select * From ProductoPaseado Where CodProd=@CodProd And DiaO=@DiaO And IdEmpresa=@IdEmpresa And RutaId=@RutaId)
                    begin
                        INSERT INTO ProductoPaseado (CodProd,RutaId,DiaO,Stock,IdEmpresa) VALUES (@CodProd,@RutaId,@DiaO,@Stock,@IdEmpresa)
                    end
					else
                    begin
                        UPDATE ProductoPaseado
						SET 
							CodProd = @CodProd,
							RutaId = @RutaId,
							DiaO = @DiaO,
							Stock = @Stock,
							IdEmpresa = @IdEmpresa
						WHERE CodProd=@CodProd And  DiaO=@DiaO And IdEmpresa=@IdEmpresa And RutaId=@RutaId
                    end
                END";
        $conn = sqlsrv_connect($this->ip_server, $this->connectinfo);
        $stmt2 = sqlsrv_query( $conn, $sql);
        if( $stmt2 === false )
        {
			sqlsrv_close( $conn);
            echo "Error in executing statement 2.\n";
            die( print_r( sqlsrv_errors(), true));
        }
        sqlsrv_free_stmt( $stmt2);
        sqlsrv_close( $conn);
        $arr_tmp = array_fill( 0, 5, "?");
        $signos_interrogacion = join(",", $arr_tmp);
        $tsql_callSP = "{CALL SPAD_ActualizaProductoPaseado_tmp(".$signos_interrogacion.")}";
        if (!empty($this->arr)) {
            foreach ($this->arr["Stock"] as $arr) {
                $a = (array)$arr;
                $conn = sqlsrv_connect($this->ip_server, $this->connectinfo);
                $params = array(
                    array($a["SKU"], SQLSRV_PARAM_IN),
                    array($this->idRuta, SQLSRV_PARAM_IN),
                    array(intval($a["DiaO"]), SQLSRV_PARAM_IN),
                    array($a["Stock"], SQLSRV_PARAM_IN),
                    array($this->IdEmpresa, SQLSRV_PARAM_IN)
                );
                $stmt3 = sqlsrv_query( $conn, $tsql_callSP, $params);
                if( $stmt3 === false )
                {
					sqlsrv_close( $conn);
                    echo "Error in executing statement 3.\n";
                    die( print_r( sqlsrv_errors(), true));
                }
                sqlsrv_free_stmt( $stmt3);
                sqlsrv_close( $conn);
            }
        }
        $this->dropProcedure('SPAD_ActualizaProductoPaseado_tmp');
        sqlsrv_free_stmt( $stmt2);
        sqlsrv_free_stmt( $stmt3);
        sqlsrv_close( $conn);
        return "Sincronizado JSON2SqlStockPaseado";
    }
	
	function JSON2SqlDescargas() {
		$Proceso="Actualiza las descargas";
        if (!empty($this->arr)) {
            foreach ($this->arr["Descargas"] as $arr) {
                $a = (array)$arr;
                $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
				$sql = "CALL SPSFA_ActualizaDescarga("
						. $this->idRuta . ","
						. $this->Vendedor . ",'"
						. $a["Articulo"] . "',"
						. $a["Cantidad"] . ",'"
						. $a["Fecha"] . "',"
						. $a["DiaO"] . ",'"
						. $this->IdEmpresa . "','"
						. $a["Folio"] . "');";
                $stmt = $conn->query($sql);
                if($stmt === false)
                {
                    $_arr[] = array(
                			"success" => false,
                			"Msg" => $stmt1->error);
        				return $_arr;
                }
                $conn->close();
            }
        }
        $_arr[] = array(
			"success" => true,
			"Msg" => "Sincronizado JSON2SqlDescarga");
		return $_arr;
    }

	function JSON2SqlStockHistorico() {
		$Proceso="Actualiza el sock histórico";
		//if ($this->validaStock===1){
			if (!empty($this->arr)) {
				foreach ($this->arr["StockHistorico"] as $arr) {
					$conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
					$a = (array)$arr;
					if (empty($a["StockA"])) {
						$StockA=0;
					}
					else{
						if (!empty($a["StockA"])) {
							$StockA=0;
						}
						else{
							$StockA=$a["StockA"];
						}
					}
					$sql = "CALL SPSFA_ActualizaStock('"
								. $a["SKU"] . "',"
								. $this->idRuta . ","
								. $a["DiaO"] . ","
								. $a["Stock"] . ","
								. $StockA . ",'"
								. $this->IdEmpresa . "');";
					$stmt1 = $conn->query($sql);
					if( $stmt1 === false )
					{
						//$this->registraErrores($mysqli->error, "Stock");
        				$_arr[] = array(
                			"success" => false,
                			"Msg" => $stmt1->error);
        				return $_arr;
					}
					$conn->close();
				}
			}
		//}
        $_arr[] = array(
                "success" => true,
                "Msg" => "Sincronizado JSON2SqlActualizaStock");
        return $_arr;
    }
	
	function updateRecarga() {
		$Proceso="Garga las descargas de la ruta";
        $sql = "CALL SPSFA_DameRecargas("
				. $this->idRuta . ","
				. $this->DiaO . ",'"
				. $this->IdEmpresa . "');";
		$conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
		$stmt = $conn->query($sql);
		if( $stmt === false )
		{
			$conn->close();
			$_arr[] = array(
					"success" => false,
					"Msg" => "Error in executing SPSFA_DameRecargas.\n" . $stmt1->error);
			return $_arr;
		}
		$_arr = array();
        while ($row = $stmt->fetch_array(MYSQLI_NUM)) {
			if ($row[0]==="-1")
			{
				$conn->close();
				$_arr[] = array(
						"success" => false,
						"Msg" => "Aun no esta lista la recarga para la ruta.");
				return $_arr;
			}
            $_arr[] = array(
				"Articulo" => $row[1],
                "Cantidad" => $row[2]);
        }
        $conn->close();
		$arr[] = array("ruta"=>$this->idRuta,
					"DiaO"=>$this->DiaO,
					"Detalle"=>$_arr);
        return $arr;
    }
	
	function JSON2SqlRespuestas() {
        $this->dropProcedure("SPAD_Encuesta_tmp");
        $sql = "CREATE PROCEDURE SPAD_Encuesta_tmp(
                @Clave_Enc	varchar(50),
				@Num_Pregunta int,
				@IdRuta	int,
				@Des_Resp varchar(255),
				@IdCliente varchar(50),
				@Fecha varchar(50),
				@DiaO int,
				@IdEmpresa varchar(50))
                AS
                BEGIN
					set @Fecha = convert(datetime, @Fecha, 120)
                    if Not Exists(Select * From Resp_Enc Where Clave_Enc=@Clave_Enc AND Num_Pregunta=@Num_Pregunta And IdEmpresa=@IdEmpresa And IdRuta=@IdRuta And IdCliente=@IdCliente And DiaO=@DiaO And Fecha=@Fecha)
                    begin
                        INSERT INTO Resp_Enc (
							Clave_Enc,
							Num_Pregunta,
							IdRuta,
							Des_Resp,
							IdCliente,
							Fecha,
							DiaO,
							IdEmpresa
							) VALUES (
							@Clave_Enc,
							@Num_Pregunta,
							@IdRuta,
							@Des_Resp,
							@IdCliente,
							@Fecha,
							@DiaO,
							@IdEmpresa
							)
                    end
					else
                    begin
                        UPDATE Resp_Enc 
						SET 
							Clave_Enc = @Clave_Enc,
							Num_Pregunta = @Num_Pregunta,
							IdRuta = @IdRuta,
							Des_Resp = @Des_Resp,
							IdCliente = @IdCliente,
							Fecha = @Fecha,
							DiaO = @DiaO,
							IdEmpresa = @IdEmpresa
						WHERE Clave_Enc=@Clave_Enc AND Num_Pregunta=@Num_Pregunta And IdEmpresa=@IdEmpresa And IdRuta=@IdRuta And IdCliente=@IdCliente And DiaO=@DiaO And Fecha=@Fecha
                    end
                END";
        $conn = sqlsrv_connect($this->ip_server, $this->connectinfo);
        $stmt2 = sqlsrv_query( $conn, $sql);
        if( $stmt2 === false )
        {
			sqlsrv_close( $conn);
            echo "Error in executing statement 2.\n";
            die( print_r( sqlsrv_errors(), true));
        }
        sqlsrv_free_stmt( $stmt2);
        sqlsrv_close( $conn);
        $arr_tmp = array_fill( 0, 8, "?");
        $signos_interrogacion = join(",", $arr_tmp);
        $tsql_callSP = "{CALL SPAD_Encuesta_tmp(".$signos_interrogacion.")}";
        if (!empty($this->arr)) {
            foreach ($this->arr["Encuestas"] as $arr) {
                $a = (array)$arr;
                $conn = sqlsrv_connect($this->ip_server, $this->connectinfo);
                $t1 = date("Y-m-d", strtotime ( $a["Fecha"]) );
                $params = array(
                    array($a["Clave_Enc"], SQLSRV_PARAM_IN),
                    array($a["idPregunta"], SQLSRV_PARAM_IN),
                    array($this->idRuta, SQLSRV_PARAM_IN),
                    array($a["Respuesta"], SQLSRV_PARAM_IN),
                    array($a["IdCliente"], SQLSRV_PARAM_IN),
                    array($t1, SQLSRV_PARAM_IN),
                    array($a["DiaO"], SQLSRV_PARAM_IN),
                    array($this->IdEmpresa, SQLSRV_PARAM_IN)
                );
                $stmt3 = sqlsrv_query( $conn, $tsql_callSP, $params);
                if( $stmt3 === false )
                {
					sqlsrv_close( $conn);
                    echo "Error in executing statement 3.\n";
                    die( print_r( sqlsrv_errors(), true));
                }
                sqlsrv_free_stmt( $stmt3);
                sqlsrv_close( $conn);
            }
        }
        $this->dropProcedure('SPAD_Encuesta_tmp');
        sqlsrv_free_stmt( $stmt2);
        sqlsrv_free_stmt( $stmt3);
        sqlsrv_close( $conn);
        return "Sincronizado JSON2SqlRespuestas";
    }
	
	function JSON2SqlEncabezadoEncuestas() {
        $this->dropProcedure("SPAD_EncabezadoEncuestas_tmp");
        $sql = "CREATE PROCEDURE SPAD_EncabezadoEncuestas_tmp(
                @Clave_Enc	varchar(50),
				@Num_Preg int,
				@Des_Preg varchar(255),
				@Tipo_Preg varchar(255),
				@IdEmpresa varchar(50))
                AS
                BEGIN
                    if Not Exists(Select * From Preg_Enc Where Clave_Enc=@Clave_Enc AND Num_Preg=@Num_Preg And IdEmpresa=@IdEmpresa)
                    begin
						INSERT INTO Preg_Enc (
							Clave_Enc,
							Num_Preg,
							Des_Preg,
							Tipo_Preg,
							IdEmpresa
						) VALUES (
							@Clave_Enc,
							@Num_Preg,
							@Des_Preg,
							@Tipo_Preg,
							@IdEmpresa
						)
                    end
					else
                    begin
						UPDATE Preg_Enc 
						SET 
							Clave_Enc = @Clave_Enc,
							Num_Preg = @Num_Preg,
							Des_Preg = @Des_Preg,
							Tipo_Preg = @Tipo_Preg,
							IdEmpresa = @IdEmpresa
						WHERE Clave_Enc=@Clave_Enc AND Num_Preg=@Num_Preg And IdEmpresa=@IdEmpresa
                    end
                END";

        $conn = sqlsrv_connect($this->ip_server, $this->connectinfo);
        $stmt2 = sqlsrv_query( $conn, $sql);
        if( $stmt2 === false )
        {
			sqlsrv_close( $conn);
            echo "Error in executing statement 2.\n";
            die( print_r( sqlsrv_errors(), true));
        }
        sqlsrv_free_stmt( $stmt2);
        sqlsrv_close( $conn);
        $arr_tmp = array_fill( 0, 5, "?");
        $signos_interrogacion = join(",", $arr_tmp);
        $tsql_callSP = "{CALL SPAD_EncabezadoEncuestas_tmp(".$signos_interrogacion.")}";
        if (!empty($this->arr)) {
            foreach ($this->arr["Encuestas"] as $arr) {
                $a = (array)$arr;
                $conn = sqlsrv_connect($this->ip_server, $this->connectinfo);
                $params = array(
                    array($a["Clave_Enc"], SQLSRV_PARAM_IN),
                    array($a["idPregunta"], SQLSRV_PARAM_IN),
                    array($a["Pregunta"], SQLSRV_PARAM_IN),
                    array("Encuesta con Pregunta Abierta", SQLSRV_PARAM_IN),
                    array($this->IdEmpresa, SQLSRV_PARAM_IN)
                );
				/*
				                @Clave_Enc	varchar(50),
				@Num_Preg int,
				@Des_Preg varchar(255),
				@Tipo_Preg varchar(255),
				@IdEmpresa varchar(50))
				*/
                $stmt3 = sqlsrv_query( $conn, $tsql_callSP, $params);
                if( $stmt3 === false )
                {
					sqlsrv_close( $conn);
                    echo "Error in executing statement 3.\n";
                    die( print_r( sqlsrv_errors(), true));
                }
                sqlsrv_free_stmt( $stmt3);
                sqlsrv_close( $conn);
            }
        }
        $this->dropProcedure('SPAD_EncabezadoEncuestas_tmp');
        sqlsrv_free_stmt( $stmt2);
        sqlsrv_free_stmt( $stmt3);
        sqlsrv_close( $conn);
        return "Sincronizado JSON2SqlEncabezadoEncuestas";
    }
	
	function JSON2SqlEncuestas() {
        $this->dropProcedure("SPAD_Encuestas_tmp");
        $sql = "CREATE PROCEDURE SPAD_Encuestas_tmp(
                @Clave_Enc	varchar(50),
				@Desc_Enc varchar(255),
				@Tipo_Enc varchar(255),
				@IdEmpresa varchar(50))
                AS
                BEGIN
                    if Not Exists(Select * From Encuestas Where Clave_Enc=@Clave_Enc AND IdEmpresa=@IdEmpresa)
                    begin
						INSERT INTO Encuestas (
							Clave_Enc,
							Desc_Enc,
							Tipo_Enc,
							IdEmpresa
						) VALUES (
							@Clave_Enc,
							@Desc_Enc,
							@Tipo_Enc,
							@IdEmpresa
						)
                    end
					else
                    begin
						UPDATE Encuestas 
						SET 
							Clave_Enc = @Clave_Enc,
							Desc_Enc = @Desc_Enc,
							Tipo_Enc = @Tipo_Enc,
							IdEmpresa = @IdEmpresa
						WHERE Clave_Enc=@Clave_Enc AND IdEmpresa=@IdEmpresa
                    end
                END";
        $conn = sqlsrv_connect($this->ip_server, $this->connectinfo);
        $stmt2 = sqlsrv_query( $conn, $sql);
        if( $stmt2 === false )
        {
			sqlsrv_close( $conn);
            echo "Error in executing statement 2.\n";
            die( print_r( sqlsrv_errors(), true));
        }
        sqlsrv_free_stmt( $stmt2);
        sqlsrv_close( $conn);
        $arr_tmp = array_fill( 0, 4, "?");
        $signos_interrogacion = join(",", $arr_tmp);
        $tsql_callSP = "{CALL SPAD_Encuestas_tmp(".$signos_interrogacion.")}";
        if (!empty($this->arr)) {
            foreach ($this->arr["Encuestas"] as $arr) {
                $a = (array)$arr;
                $conn = sqlsrv_connect($this->ip_server, $this->connectinfo);
                $params = array(
                    array($a["Clave_Enc"], SQLSRV_PARAM_IN),
                    array($a["Des_Enc"], SQLSRV_PARAM_IN),
                    array("Encuesta con Respuesta Abierta", SQLSRV_PARAM_IN),
                    array($this->IdEmpresa, SQLSRV_PARAM_IN)
                );
				/*
				                @Clave_Enc	varchar(50),
				@Num_Preg int,
				@Des_Preg varchar(255),
				@Tipo_Preg varchar(255),
				@IdEmpresa varchar(50))
				*/
                $stmt3 = sqlsrv_query( $conn, $tsql_callSP, $params);
                if( $stmt3 === false )
                {
					sqlsrv_close( $conn);
                    echo "Error in executing statement 3.\n";
                    die( print_r( sqlsrv_errors(), true));
                }
                sqlsrv_free_stmt( $stmt3);
                sqlsrv_close( $conn);
            }
        }
        $this->dropProcedure('SPAD_Encuestas_tmp');
        sqlsrv_free_stmt( $stmt2);
        sqlsrv_free_stmt( $stmt3);
        sqlsrv_close( $conn);
        return "Sincronizado JSON2SqlEncuestas";
    }
	
	function JSON2SqlDescargoPedDiaSig() {
		$Proceso="Actualiza el pedido del día siguiente";
        if (!empty($this->arr)) {
            foreach ($this->arr["pedidodiasiguiente"] as $arr) {
                $a = (array)$arr;
                $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
				if (strpos($a["Hora"], "HH")!==false) {
					$a["Hora"] = date("Y-m-d H:i:s");
				}
                $hora = (!empty($a["Hora"])) ? $a["Hora"] : "";
				if ($a["Tipo"]=="R") {
                    $dt = new DateTime($a["Hora"]);
                    $hora = $dt->format('H:i:s');
                }
				$sql = "CALL SPSFA_ActualizaPDS('"
						. $a["Tipo"] . "',"
						. $this->idRuta . ",'"
						. $a["Articulo"] . "',"
						. $a["Cantidad"] . ","
						. $a["DiaO"] . ",'"
						. $a["Folio"] . "','"
						. $this->IdEmpresa . "',"
						. $this->Vendedor . ",'"
						. $a["Hora"] . "');";
                $stmt = $conn->query($sql);
                if( $stmt === false )
                {
					$conn->close();
                    return $stmt->error;
                }
                $conn->close();
            }
        }
        return "Sincronizado JSON2SqlDescargoPedDiaSig";
    }
	
	function JSON2SqlEntradasTransito() {
        if (!empty($this->arr)) {
            foreach ($this->arr["EntradasTransito"] as $arr) {
                $a = (array)$arr;
                $conn = sqlsrv_connect($this->ip_server, $this->connectinfo);
				$sqlU = "UPDATE PEDIDOSLIBERADOS 
						SET STATUS = '4' 
						WHERE PEDIDO = '". $a["Docto"] ."' AND
						RUTA = '". $this->idRuta ."' AND 
						IDEMPRESA = '".$this->IdEmpresa."' AND 
						IDVENDEDOR = '" . $this->Vendedor . "'";
                $stmt3 = sqlsrv_query( $conn, $sqlU);
                if( $stmt3 === false )
                {
					sqlsrv_close( $conn);
                    echo "Error in executing statement 3.\n";
                    die( print_r( sqlsrv_errors(), true));
                }
				$sqlU = "UPDATE Pedidos
						SET Status = '4' 
						WHERE Pedido = '". $a["Docto"] ."' AND
						Ruta = '". $this->idRuta ."' AND 
						IdEmpresa = '".$this->IdEmpresa."' AND 
						idVendedor = '" . $this->Vendedor . "'";
                $stmt3 = sqlsrv_query( $conn, $sqlU);
                if( $stmt3 === false )
                {
					sqlsrv_close( $conn);
                    echo "Error in executing statement 3.\n";
                    die( print_r( sqlsrv_errors(), true));
                }
                sqlsrv_free_stmt( $stmt3);
                sqlsrv_close( $conn);
            }
        }
        sqlsrv_close( $conn);
        return "Sincronizado JSON2SqlDescargoPedDiaSig";
    }
	
	function JSON2SqlActualizaNoVentas() {
		$Proceso="Actualiza las no ventas";
        if (!empty($this->arr)) {
            foreach ($this->arr["NoVentas"] as $arr) {
                $a = (array)$arr;
				$conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
				$sql = "Call SPSFA_ActualizaNoVentas('"
				. $a["Cliente"] . "',"
				. $a["MotivoId"] . ",'"
				. $a["Fecha"] . "',"
				. $a["DiaO"] . ","
				. $this->idRuta . ","
				. $this->Vendedor . ",'"
				. $this->IdEmpresa . "');";
                $stmt = $conn->query($sql);
                if( $stmt === false )
                {
					$conn->close();
                    echo "Error in executing statement.\n";
                }
                $conn->close();
            }
        }
        return "Sincronizado JSON2SqlActualizaNoVentas";
    }
	
	function JSON2SqlProcessUpload()
    {
        $ruta = $_SERVER["DOCUMENT_ROOT"] . "/".$this->rutaOpcional."/uploads/" . $this->Archivo;
        if (file_exists($ruta)) {
            $conn = sqlsrv_connect($this->ip_server,$this->connectinfo);
            $sql = "INSERT INTO ColaProcesamiento (
                      idVendedor, 
                      DiaO, 
                      idEmpresa, 
                      nombreArchivo, 
                      Fecha, 
                      Procesado, 
                      idRuta, 
                      FechaProcesado,
                      rutaOpcional) 
                      VALUES 
                      ('".$this->Vendedor."', 
                      '".$this->DiaO."', 
                      '".$this->IdEmpresa."', 
                      '".$this->Archivo."', 
                      GETDATE(), 
                      'N', 
                      '".$this->idRuta."', 
                      GETDATE(),
                      '".$this->rutaOpcional."')";
            $stmt2 = sqlsrv_query( $conn, $sql);
			if( $stmt2 === false )
			{
				sqlsrv_close( $conn);
				echo "Error in executing statement 2.\n";
				die( print_r( sqlsrv_errors(), true));
			}
			sqlsrv_free_stmt( $stmt2);
			sqlsrv_close( $conn);
            return "JSON2SqlProcessUpload";
        }
        return "Error de Transmision";
    }
    
    function ProcessMedidoresUpload()
    {
        $ruta = $_SERVER["DOCUMENT_ROOT"] . "/".$this->rutaOpcional."/uploads/" . $this->Archivo;
        if (file_exists($ruta)) {
            $conn = sqlsrv_connect($this->ip_server,$this->connectinfo);
            //SE AGREGA ESTADO M PARA PROCESAR SOLO MEDIDORES
            $sql = "INSERT INTO ColaProcesamiento (
                      idVendedor, 
                      DiaO, 
                      idEmpresa, 
                      nombreArchivo, 
                      Fecha, 
                      Procesado, 
                      idRuta, 
                      FechaProcesado,
                      rutaOpcional) 
                      VALUES 
                      ('".$this->Vendedor."', 
                      '".$this->DiaO."', 
                      '".$this->IdEmpresa."', 
                      '".$this->Archivo."', 
                      GETDATE(), 
                      'M',
                      '".$this->idRuta."', 
                      GETDATE(),
                      '".$this->rutaOpcional."')";
            $stmt2 = sqlsrv_query( $conn, $sql);
            if( $stmt2 === false )
            {
				sqlsrv_close( $conn);
                echo "Error in executing statement 2.\n";
                die( print_r( sqlsrv_errors(), true));
            }
            sqlsrv_free_stmt( $stmt2);
            sqlsrv_close( $conn);
            return "JSON2SqlProcessUploadM";
        }
        return "Error de Transmision";
    }
}

function GuardaLogWS($Referencia, $Mensaje, $Respuesta, $Enviado, $Proceso, $Dispositivo)
{
	include '../config.php';
	$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
		$MiSql[] = $detalle;
		$sql = "Call SPWS_GuardaLogWS ('"
			. $Referencia . "','"
			. $Mensaje . "','"
			. $Respuesta . "',"
			. $Enviado . ",'"
			. $Proceso . "','"
			. $Dispositivo . "');";
		$result = $conn->query($sql);
		$_arr = array();
		if(!$result)
		{
			$_arr[] = array("Error" => -1,
							"Msg" => utf8_encode($conn->error));
			$conn->close();
			return $_arr;
		}
		while ($row = $result->fetch_array(MYSQLI_NUM)) {
			$_arr["T_GuardaLogWS"][] = array("Error" => $row[0],
										"Msg" => $row[1]);
		$conn->close();
	}
	return $_arr;
}

// include_once($_SERVER["DOCUMENT_ROOT"]."/config/cfgsctp.php");
include '../config.php';

$json = file_get_contents('php://input');
$obj = json_decode($json);

if (!empty($obj)) {
    error_reporting(0);
    $t = new Sql2JSON();
	//$t->CadJSON = $json;
    $t->ip_server = DB_HOST;
    $t->db = DB_NAME;
    $t->user = DB_USER;
    $t->password = DB_PASSWORD;
    $t->connectinfo = array("Database"=>DB_NAME, "UID"=>DB_USER, "PWD"=>DB_PASSWORD, "CharacterSet"=>"UTF-8");

	$t->arr = (array) json_decode($obj->{'arreglo'});
    $conn =  new mysqli($t->ip_server, $t->user, $t->password, $t->db); //sqlsrv_connect($t->ip_server, $t->connectinfo);
    if( $conn->connect_errno ) {
		echo "Error: Fallo al conectarse a MySQL debido a: \n";
		echo "Errno: " . $mysqli->connect_errno . "\n";
		echo "Error: " . $mysqli->connect_error . "\n";
    }
	$Message = $json;
	$Process = "";
	$Disp = "APK SFA";
    $function = $obj->{'function'};
	$Ref = $function;
	if ($obj->{'function'}=='JSON2SqlPedidos' or $obj->{'function'}=='JSON2SqlBitac' or $obj->{'function'}=='JSON2SqlDetallePed' or $obj->{'function'}=='JSON2SqlEntregas' or $obj->{'function'}=='JSON2SqlDetalleEnt' or $obj->{'function'}=='JSON2SqlPromociones' ){
		$Ruta="CadJSON/JSON_".$obj->{'function'}.strftime("%Y%m%d_%H%M%S",time()).".txt";
		$File = fopen($Ruta,"w");
		fwrite($File,$json);
		fclose($File);
	}
	if (isset($obj->{'DiaO'})) $t->DiaO = $obj->{'DiaO'};
    if (isset($obj->{'Archivo'})) $t->Archivo = $obj->{'Archivo'};
    if (isset($obj->{'Folio'})) $t->Folio = $obj->{'Folio'};
    if (isset($obj->{'rutaOpcional'})) $t->rutaOpcional = $obj->{'rutaOpcional'};
	if (isset($obj->{'ruta'})){
		$t->Ruta = $obj->{'ruta'};
		$sql = "Select Z.ID_Ruta,Z.Cve_Ruta,V.Id_Vendedor,A.Clave From t_ruta Z Join Rel_Ruta_Agentes R On Z.ID_Ruta=R.Cve_Ruta Join t_vendedores V On V.Id_Vendedor=R.Cve_Vendedor Join c_almacenp A On Z.Cve_Almacenp=A.Id WHERE Z.cve_ruta='" . $obj->{'ruta'} . "' Limit 1;";
		$result = $conn->query($sql);
		$_arr = array();
		if ($row = $result->fetch_array(MYSQLI_NUM)) {
			$t->idRuta = $row[0];
			$t->Ruta = $row[1];
			if (isset($obj->{'idVendedor'})) {
				$t->Vendedor = $obj->{'idVendedor'}; 
			} else {
				$t->Vendedor = $row[2];
			}
			$t->IdEmpresa = $row[3];
		}
		$conn->close();
	}
	$ret = $t->$function();
    header('Content-type: application/json');
	$Resp=json_encode($ret);
	$Env="1";
	$Process=$t->Proceso;
	$Guarda=GuardaLogWS($Ref,$Message,$Resp,$Env,$Process,$Disp);
    echo $Resp;
}