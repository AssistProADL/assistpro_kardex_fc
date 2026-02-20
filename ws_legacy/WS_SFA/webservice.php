<?php
class Sql2JSON
{
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
    var $idsClientes;
    var $strCodVend;
	var $arrTbl;

    function Sql2JSONClientes()
    {
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
	
	function Sql2JSONEntregas()
    {
		error_reporting(0);
        $conn =  new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_PedidosLib('".$this->IdEmpresa."',". $this->idRuta .");";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array(
                "PEDIDO" => $row[0],
                "RUTA" => $row[1],
                "COD_CLIENTE" => $row[2],
                "FOLIO_BO" => $row[3],
                "FECHA_PEDIDO" => $row[4],
                "FECHA_LIBERACION" => $row[5],
                "FECHA_ENTREGA" => $row[6],
                "TIPO" => $row[7],
                "FORMAP" => $row[21],
                "STATUS" => $row[8],
                "CANCELADA" => $row[9],
                "INCIDENCIA" => utf8_encode($row[10]),
                "SUBTOTAL" => $row[11],
                "IVA" => $row[12],
                "IEPS" => $row[13],
                "TOTAL" => $row[14],
                "KG" => $row[15],
                "IDVENDEDOR" => $row[16],
                "ID_AYUDANTE1" => $row[17],
                "ID_AYUDANTE2" => $row[18],
                "IDEMPRESA" => $row[19],
                "RutaEnt" => $row[20],
                "DocSalida" => $row[23],
                "FechaVe" => $row[22],
                "DiasCred" => $row[24]
            );
        }
/*		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');*/
        $conn->close();
        return $_arr;
    }

    function Sql2JSONDetalleEntregas()
    {
		error_reporting(0);
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
		$sql = "Call SPSFA_DetallePedidosPromoLib('".$this->IdEmpresa."',". $this->idRuta .");";
		$result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $precio = floatval($row[9]);
            $precio = Round($precio,1,PHP_ROUND_HALF_UP);
            $iva = floatval($row[11]);
            $iva = Round($iva,3,PHP_ROUND_HALF_UP);
            $_arr[] = array(
                "PEDIDO" => $row[0],
                "FOLIO_BO" => $row[1],
                "RUTA" => $row[2],
                "ARTICULO" => $row[3],
                "PZA" => intval($row[4]),
                "PZA_LIBERADAS" => $row[6],
                "TIPO" => $row[7],
                "KG" => $row[8],
                "PRECIO" => Round($precio,2,PHP_ROUND_HALF_UP),
                "IMPORTE" => $row[10],
                "IVA" => Round($iva,2,PHP_ROUND_HALF_UP),
                "IEPS" => floatval($row[12]),
                "DESCUENTO" => floatval($row[13]),
                "BAN_PROMO" => $row[14],
                "STATUS" => $row[15],
                "CANCELADA" => $row[16],
                "INCIDENCIA" => $row[17],
                "IDEMPRESA" => $row[18],
                "DESCRIPCION" => $row[19]
            );
        }
/*		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');*/
        $conn->close();
        return $_arr;
    }

    function Sql2JSONSaldoEnvaseEntregas()
    {
		error_reporting(0);
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
		$sql = "Call SPSFA_EnviaSaldoEnvasesEntregas(". $this->idRuta .",'". $this->IdEmpresa ."');";
		$result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $Error = intval($row[0]);
			if($Error==1){
				$_arr[] = array(
					"Documento" => $row[1],
					"IdEmpresa" => $row[2],
					"Ruta" => $row[3],
					"Cantidad" => floatval($row[4]),
					"Cant_eq" => intval($row[5]),
					"Grupo" => $row[6],
					"Des_GpoArt" => $row[7],
					"Envase" => $row[8],
					"Cant_Envase" => intval($row[9])
				);
			}
			else{
				$_arr[] = array(
					"Error" => 'Error',
					"Msg" => 'No hay registros por regresar');
			}
		}
        $conn->close();
        return $_arr;
    }

    function Sql2JSONConfig()
    {
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
        $conn =  new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameProductos('" . $this->IdEmpresa . "');";
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
        $conn =  new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameProductosPzs('" . $this->IdEmpresa . "');";
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
    
     /////////////////////////////////GG INCIDENCIAS ///////////////////////////
    function Sql2JSONServicios()
    {
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
	/*	if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');*/
        $conn->close();
        return $_arr;
    }
    ///////////////////////////////////////////////////////////////////////////

    function Sql2JSONProductEnvase()
    {
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
/*		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');*/
        $conn->close();
        return $_arr;
    }

    function CargaCodigosOp()
    {
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
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "SELECT * FROM CatMnv";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = $row;
        }
        $conn->close();
        return $_arr;
    }

    function Sql2Cuotas()
    {
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
/*		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');*/
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
/*		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');*/
        $conn->close();
        return $_arr;
    }

    function Sql2LisProMas()
    {
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
/*		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');*/
        $conn->close();
        return $_arr;
    }

    function Sql2JSONLp()
    {
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
/*		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');*/
        $conn->close();
        return $_arr;
    }

    function Sql2JSONLD()
    {
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameListaDescuento(" . $this->idRuta . ");";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
			$_conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
            $sql = "Call SPSFA_DameDetalleLD(" . $row[1] . ");";
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
/*		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');*/
        $conn->close();
        return $_arr;
    }

    function Sql2JSONFormasp()
    {
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
/*		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');*/
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
/*		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');*/
        $conn->close();
        return $_arr;
    }

    function Sql2JSONActivos()
    {
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
/*		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');*/
        $conn->close();
        return $_arr;
    }

    function Sql2JSONVendedores()
    {
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
/*		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');*/
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
/*		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');*/
        $conn->close();
        return $_arr;
    }

    function Sql2JSONmvodev()
    {
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
/*		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');*/
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
/*		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');*/
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
/*		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');*/
        $conn->close();
        return $_arr;
    }

    function Sql2JSONCTickets()
    {
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "SELECT Linea1, Linea2, Linea3, Linea4, Mensaje, LOGO FROM CTiket Where IdEmpresa='".$this->IdEmpresa."'";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array(
                "Linea1" => utf8_encode($row[0]),
                "Linea2" => utf8_encode($row[1]),
                "Linea3" => utf8_encode($row[2]),
                "Linea4" => utf8_encode($row[3]),
                "Mensaje" => utf8_encode($row[4]),
                "LOGO" => "",
                "idEmpresa" => $this->IdEmpresa,
                "dbName" => $this->db
            );
        }
        $conn->close();
        return $_arr;
    }

    function Sql2JSONVisitas()
    {
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
                "CodCli" => $row[2],
                "idVendedor" => $row[4],
                "Lunes" => $row[5],
                "Martes" => $row[6],
                "Miercoles" => $row[7],
                "Jueves" => $row[8],
                "Viernes" => $row[9],
                "Sabado" => $row[10],
                "Domingo" => $row[11]);
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
                "CodCli" => $row[2],
                "idVendedor" => $row[4],
                "Lunes" => $row[5],
                "Martes" => $row[6],
                "Miercoles" => $row[7],
                "Jueves" => $row[8],
                "Viernes" => $row[9],
                "Sabado" => $row[10],
                "Domingo" => $row[11]);
                }
				$conn->close();
            }
        }
/*		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');*/
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
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function Sql2JSONCobranzas()
    {
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
		$sql = "Call SPSFA_DameCobranzaRuta(" . $this->idRuta . ",'" . $this->IdEmpresa . "');";
		$result = $conn->query($sql);
		while ($row = $result->fetch_array(MYSQLI_NUM)) {
			$_arr[] = array(
				"Documento" => $row[0],
				"SaldoAct" => number_format($row[1], 2, '.', ''),
				"FechaVence" => $row[2],
				"Cliente" => $row[3],
				"TipoDoc" => $row[4],
				"FolioInterno" => $row[5],
				"Saldo" => number_format($row[6], 2, '.', ''));
		}
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function Sql2JSONListaPromo()
    {
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameListaPromo('" . $this->IdEmpresa . "');";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $FechaF = $row[9]->date;
            $FechaI = $row[10]->date;
            $_arr[] = array("IdLm" => $row[0],
                "IdPromo" => $row[1],
                "Lista" => $row[2],
                "ListaMaster" => $row[3],
                "Articulo" => $row[4],
                "Cantidad" => intval($row[5]),
                "Tipo" => $row[6],
                "Descripcion" => $row[7],
                "Caduca" => $row[8],
                "FechaF" => $FechaF,
                "FechaI" => $FechaI,
                "Activa" => $row[11],
                "TipoProm" => $row[12],
                "Monto" => $row[13],
                "Volumen" => $row[14],
                "TipMed" => $row[15],
                "Nivel" => $row[16],
                "Grupo" => $row[17]);
        }
/*		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');*/
        $conn->close();
        return $_arr;
    }

    function Sql2JSONRelCliT()
    {
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameLisCli(" . $this->idRuta . ");";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array("IdRuta" => $row[0],
                "ListaP" => $row[1],
                "ListaD" => $row[2],
                "ListaPromo" => $row[3],
                "DiaVisita" => $row[4],
                "CodCliente" => $row[5]);
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

	function Sql2JSONStockIniFinal()
    {
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

/*        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameStockIni(" . $this->idRuta . ");";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $stock = empty($row[2]) ? "0" : $row["Stock"];
            $_arr[] = array(
                "IdStock" => $row[0],
                "Articulo" => utf8_encode($row[1]),
                "Stock" => $stock,
                "Ruta" =>  $row[3]);
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
*/
    }

	function Sql2JSONActualizaStockHistorico() {
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "select UDiaO from continuidad where idempresa='".$this->IdEmpresa."' and Rutaid='".$this->idRuta."';";
        $stmt = $conn->query($sql);
        if( $stmt === false )
        {
			$conn->close();
            echo "Error in executing statement 1.\n";
            die( print_r( $mysqli->error, true));
        }
        $this->UDiaO = "";
        $_arr = array();
        if ($stmt) {
            $row = $stmt->fetch_array(MYSQLI_NUM);
            $this->UDiaO = $row['UDiaO'];
        }
        $conn->close();
        $sql = "Call SPSFA_DameStockIni(" . $this->idRuta . ");";
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $result = $conn->query($sql);
        if( $result === false )
        {
			$conn->close();
            echo "Error in executing statement 2.\n";
            die( print_r( $mysqli->error, true));
        }
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
			$_conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
			$sql = "Call SPSFA_ActualizaStockH('" . $row[1] . "'," . $row[2] . "," . $this->idRuta . ",GET_DATE(),'".$this->IdEmpresa."',".$this->UDiaO.");";
			$stmt2 = $_conn->query($sql);
			if( $stmt2 === false )
			{
				$_conn->close();
				echo "Error in executing SPSFA_ActualizaStockH.\n";
				die( print_r( $mysqli->error, true));
			}
			$_conn->close();
        }
		$conn->close();
        return "Sincronizado Sql2JSONActualizaStockHistorico";
    }

	function Sql2JSONActualizaIdFCM()
    {
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
		$sql = "Call SPSFA_ActualizaIdFCM("
			. $this->Vendedor . ",'"
			. $this->arrTbl['IdFCM'] . "');";
		$result = $conn->query($sql);
		$_arr = array();
		while ($row = $result->fetch_array(MYSQLI_NUM)) {
			$_arr[] = array("Error" => $row[0],
							"Msg" => $row[1]);
		}
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => '-1',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
		return $_arr;
    }

	function Sql2JSONActualizaUbicacionVehiculo()
    {
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
		$MiSql[] = $detalle;
		$sql = "Call SPSFA_ActualizaUbicacionVehiculo("
			. $this->Vendedor . ","
			. $this->arrTbl['Vehiculo'] . ","
			. $this->arrTbl['Ruta'] . ",'"
			. $this->arrTbl['Fecha'] . "',"
			. $this->arrTbl['Longitud'] . ","
			. $this->arrTbl['Latitud'] . ");";
		$result = $conn->query($sql);
        if( $result === false )
        {
			$_arr[] = array(
                "Error" => '-1',
                "Msg" => $result->error);
			$conn->close();
			return $_arr;
        }
		$_arr = array();
		while ($row = $result->fetch_array(MYSQLI_NUM)) {
			$_arr[] = array("Error" => $row[0],
							"Msg" => $row[1]);
		}
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => '-1',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }

    function ifExistsEnStockHistorico($codigo) {
        $ret = false;
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Select * from StockHistorico where IdEmpresa='".$this->IdEmpresa."' and RutaID=".$this->idRuta." And Articulo='".$codigo."' And  DiaO=".$this->UDiaO.";";
        $stmt = $conn->query($sql);
        if( $stmt === false )
        {
			$conn->close();
            echo "Error in executing statement 2.\n";
            die( print_r($mysqli->error, true));
        }
        if ($stmt) {
            $row = $stmt->fetch_array(MYSQLI_NUM);
            if (!empty($row["DiaO"])) {
                $ret = true;
            }
        }
        $conn->close();
        return $ret;
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

    function Sql2JSONListaP()
    {
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameListaPrecios(" . $this->idRuta . ");";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $tipo = $row[2];
            $conn2 = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
            $sql2 = "Call SPSFA_DameDetalleLP(" . $row[1] . ");";
            $result2 = $conn2->query($sql2);
            while ($row2 = $result2->fetch_array(MYSQLI_NUM)) {
                $_arr[] = array("Articulo" => $row2[2],
                    "PrecioMin" => $row2[3],
                    "PrecioMax" => $row2[4],
                    "ListaId" => $row2[1],
                    "Tipo" => $tipo,
                    "ComisionesPer" => $row2[5],
                    "ComisionesDol" => $row2[6]);
            }
            $conn2->close();
        }
        $conn->close();
        return $_arr;
    }
	
	function Sql2JSONCatGrupos()
    {
        $conn =  new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameProGrupos('".$this->IdEmpresa."');";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
			$_arr[] = array(
				"IdGrupo" => $row[0],
				"Clave" => $row[1],
				"Status" => $row[3],
				"TipoGrupo" => $row[4],
				"IdEmpresa" => $row[5],
				"Descripcion" => utf8_encode($row[2]));
        }
        $conn->close();
        return $_arr;
    }
	
    function Sql2JSONRelProGrupos()
    {
        $conn =  new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameRelProGrupos('".$this->IdEmpresa."')";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
			$_arr[] = array(
				"ProductoId" => $row[0],
                "IdGrupo" =>  $row[1],
                "IdEmpresa" =>  $row[2]);
        }
        $conn->close();
        return $_arr;
    }
	
	function Sql2JSONGetCliente()
    {
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
		$sql = "Call SPSFA_DameCliente(" . $this->idRuta . ",'".$this->idCliente."');";
        $result = $conn->query($sql);
        $_arr = array();
        $c = 0;
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array("IdCli" => $row[1],
                "Nombre" => utf8_encode($row[2]),
                "NombreCorto" => utf8_encode($row[3]),
                "Direccion" => utf8_encode($row[4]),
                "Telefono" => $row[6],
                "Referencia" => utf8_encode($row[5]),
                "Credito" => $row[12],
                "DiasCreedito" => $row[14],
                "LimiteCredito" => $row[13],
                "VisitaObligada" => $row[15],
                "FirmaObligada" => $row[16],
                "Saldo" => "0",
                "Horario" => $row[17],
                "latitud" => $row[20],
                "longitud" => $row[21],
				"Email" => $row[0],
				"CP" => utf8_encode($row[7]),
				"RFC" => utf8_encode($row[8]),
				"Ciudad" => utf8_encode($row[9]),
				"Estado" => utf8_encode($row[10]),
				"Colonia" => utf8_encode($row[11])
            );
            $c++;
        }
        $conn->close();
        return $_arr;
    }

    function strPedidos2sql($str)
    {
        $html = "<!DOCTYPE html>
                <html>
                <head>
                    <title>Pedido</title>
                    <style>
                        table {
                            font-family: Calibri; width: 100%; font-size: 1em;	}		
                        table.one { width: 100%;
                        margin-bottom: 1em;	
                        width: 100%;
                        font-family: Calibri; font-size: 1em;	}	
                        table.one td { 
                        text-align: center;     
                        padding: 1px; font-family: Calibri; font-size: 1em;		}		
                        table.one th {
                        text-align: center;					
                        padding: 3px;
                        background: url('http://SCTP.samenlinea.com/bg.gif') repeat-x scroll left top white;		
                        color: white; font-family: Calibri; font-size: 1em;		}			      
                        table.one tr {
                        padding-right: 5px;
                        background-color: #E5F1F4;
                        font-family: Calibri; font-size: 1em;	}
                        table.one tr:nth-child(even) {			
                        background-color: #eee;	font-family: Calibri; font-size: 1em;	}
                        table.one tr:nth-child(odd) {			
                        background-color:#fff;	font-family: Calibri; font-size: 1em;	}
                </style>
                </head>
                <body>";
        $arrRows = explode("^", $str);
        foreach ($arrRows as $lineas) {
            if ($lineas != "") {
                preg_match("/^.*(\[.*\]).*(\[.*\]).*$/", $lineas, $res);
                $strClientes = str_replace(array("[", "]"), "", $res[1]);
                $arrClientes = explode("|", $strClientes);
                $strProductos = str_replace(array("[", "]"), "", $res[2]);
                $arrProductos0 = explode("::::", $strProductos);
                $arrCols = explode("|", $lineas);
                $pedido = $arrCols[0];
                $IVA = $arrCols[6];
                $TOT = $arrCols[7];
                $lat = $arrCols[8];
                $lon = $arrCols[9];
                $SUBT = $arrCols[10];
                $IEPS = $arrCols[11];
                $_items = $arrCols[12];
                $KG = $arrCols[13];
                $idRuta = $arrCols[14];
                $CodigoCliente = $arrCols[15];
                $NombreCliente = $arrCols[16];
                $DireccionCliente = $arrCols[17];
                $Ruta = $arrCols[18];
                $Vendedor = $arrCols[19];
                $NuevaFechaVence = $arrCols[20];
                $Folio = $arrCols[21];
                $NuevaFechaEntrega = $arrCols[22];
                $_email = $arrCols[23];
                $Linea1 = $arrCols[24];
                $Linea2 = $arrCols[25];
                $Linea3 = $arrCols[26];
                $Linea4 = $arrCols[27];
                $CondicionVenta = $arrCols[28];
                $FormaPagoSeleccionadaDescripcion = $arrCols[29];
                $DocSalida = $arrCols[30];
                $image = "<img width='80' height='80' style='padding:5px;' src='http://13.65.40.156/avatar/1/logo_ticket.jpg' />";
                $html .= "<div id='tb-mproducto-grid' class='grid-view' style='padding-top:10px'>";
                $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center' valign='middle'><center>".$image."</center></td>" .
                    "    <td align='left' valign='middle' height='20%' style='font-family: Calibri; font-size: 1em;'><b>Preventa - (Ticket)</b><br>" .
                    "    <b>".$Linea1."</b><br>" .
                    "    <b>".$Linea2."</b><br>" .
                    "    <b>".$Linea3."</b><br>" .
                    "    <b>".$Linea4."</b>" .
                    "    </td>" .
                    "  </tr>" .
                    "</table><div style='width:100%; height:.5px; background-color:#ccc; margin-top:2px; margin-bottom:2px; float:left;'></div>";
                $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center' style='font-family: Calibri; font-size: 1em;'><b>Ruta:</b> " . $idRuta . "</td>" .
                    "    <td align='center' style='font-family: Calibri; font-size: 1em;'><b>Cliente:</b> " . $CodigoCliente . "</td>" .
                    "    <td align='center' style='font-family: Calibri; font-size: 1em;'><b>Folio:</b> " . $Folio . "</td>" .
                    "  </tr>
                    </table>";
                $dateString = date("d-m-Y");
                $dateStringH = date("H:i:s");
                $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center'><b>Fecha:</b>" . $dateString . "</td>" .
                    "    <td align='center'><b>Hora:</b>" . $dateStringH . "</td>" .
                    "  </tr>" .
                    "</table>";
                $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center'></td>" .
                    "    <td align='center'>" . $NombreCliente . "</td>" .
                    "    <td align='center'></td>" .
                    "  </tr>" .
                    "  <tr>" .
                    "    <td align='center'></td>" .
                    "    <td align='center'>" . $DireccionCliente . "</td>" .
                    "    <td align='center'></td>" .
                    "  </tr>" .
                    "</table><div style='width:100%; height:.5px; background-color:#ccc; margin-top:2px; margin-bottom:2px; float:left;'></div>";
                $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center' style='font-family: Calibri; font-size: 1em;'><b>Ticket de Pedido / ORIGINAL</b></td>" .
                    "  </tr>" .
                    "</table>";
                $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center' style='font-family: Calibri; font-size: 1em;'><b>Vendedor:</b> " . $Ruta . " - " . $Vendedor . "</td>" .
                    "  </tr>" .
                    "</table><div style='width:100%; height:.5px; background-color:#ccc; margin-top:2px; margin-bottom:2px; float:left;'></div>";
                $html .= "<table class='one'>
                            <tr>
                                <th>Condici&oacute;n de Venta</th>
                                <th>Fecha de Entrega</th>
                                <th>Vencimiento</th>
                            </tr>
                            <tr>
                                <td>" . $CondicionVenta . "</td>
                                <td>" . $arrCols[4] . "</td>
                                <td>" . $arrCols[3] . "</td>
                            </tr>
                        </table>";
                $html .= "    <table class='one'> " .
                    "        <tr> " .
                    "            <th>Forma de Pago</th> " .
                    "            <th>Documento</th> " .
                    "        <tr class='odd'> " .
                    "            <td>".$FormaPagoSeleccionadaDescripcion."</td> " .
                    "            <td>".$DocSalida."</td> " .
                    "        </tr> " .
                    "    </table> ";
                $html .= "<table class='one'> " .
                    "<tr> " .
                    "<th>SKU</th> " .
                    "<th>Descripci&oacute;n</th> " .
                    "<th>Cant</th> " .
                    "<th>UM</th> " .
                    "<th>Precio</th> " .
                    "<th>Importe</th></tr> ";
                " ";
                $arrPromos = array();
				$descuento = 0;
                foreach ($arrProductos0 as $lineProductos) {
                    //writeLog("Numero Pedido: ".$nropedidobb.", Productos: ".$lineProductos, 1);
                    if (!empty($lineProductos)) {
                        $arrColsP = explode("|", $lineProductos);
                        $sku = $arrColsP[0];
                        $nombre = $arrColsP[1];
                        $cantidad = $arrColsP[2];
                        $preciobase = $arrColsP[3];
                        $um = $arrColsP[4];
                        $tp = $arrColsP[5];
                        $desc = $arrColsP[6];
                        $preciodes = $preciobase;
                        $precioimp = $preciobase * ($iva / 100);
                        $totalIva = $totalIva + $precioimp;
                        $sTotal = floatval($preciobase) * intval($cantidad);
                        $subtotal = $subtotal + $sTotal;
                        $_subtotal = $_subtotal + ($cantidad * $preciobase);
                        $preciobase = '$' . number_format($preciobase, 2, '.', ',');
                        $sTotal = '$' . number_format($sTotal, 2, '.', ',');
                        if ($tp == "promo") {
                            $arrPromos[] = array("sku" => $sku, "nombre" => $nombre, "cantidad" => $cantidad, "preciobase" => $preciobase, "um" => $um, "Total" => $sTotal);
                            continue;
                        }
                        $html .= "<tr> " .
                            "<td>" . $sku . "</td> " .
                            "<td>" . $nombre . "</td> " .
                            "<td align='center'>" . $cantidad . "</td> " .
                            "<td align='center' style='text-align: center;'>" . $um . "</td> " .
                            "<td align='right' style='text-align: right;'>" . $preciobase . "</td> " .
                            "<td align='right' style='text-align: right;'>" . $sTotal . "</td> " .
                            "</tr>";
						$descuento = $descuento + $desc;
                    }
                }
                $html .= "</table><br>";
            }
        }
		$TOT = $TOT - $descuento;
		$descuento = number_format($descuento, 2, '.', ',');
        $html .= "<table class='one' style='font-family: Calibri; font-size: 1em;width: 100%'>";
        $html .= "<tr><td align='right'  style='text-align: right;' width='80%'><b>Lt/Kg.:</b></td><td align='right' width='20%' style='text-align: right;'>" . $KG . "</td></tr>";
        $html .= "<tr><td align='right' style='text-align: right;'><b>Productos.:</b></td><td align='right' style='text-align: right;'>" . $_items . "</td></tr>";
        $html .= "<tr><td align='right' style='text-align: right;'><b>IVA.:</b></td><td align='right' style='text-align: right;'>\$" . number_format($IVA, 2, '.', ',') . "</td></tr>";
        $html .= "<tr><td align='right' style='text-align: right;'><b>IEPS.:</b></td><td align='right' style='text-align: right;'>\$" . number_format($IEPS, 2, '.', ',') ."</td></tr>";
        $html .= "<tr><td align='right' style='text-align: right;'><b>Sub Total:</b></td><td align='right' style='text-align: right;'>\$" . number_format($_subtotal, 2, '.', ',') . "</td></tr>";
        $html .= "<tr><td align='right' style='text-align: right;'><b>Descuento:</b></td><td align='right' style='text-align: right;'>\$".$descuento."</td></tr>";
        $html .= "<tr><td align='right' style='text-align: right;'><b>Total Pedido.:</b></td><td align='right' style='text-align: right;'>\$". number_format($TOT, 2, '.', ',') ."</td></tr>";
        $html .= "</table>";
        if (!empty($arrPromos)) {
            $html .= "<table  class='one'> " .
                "         " .
                "        <tr> " .
                "            <th id='tb-mproducto-grid_c0'>Promociones</th> " .
                "        </tr> " .
                "    </table> " .
                "    <table class='one'> " .
                " " .
                "        <tr> " .
                "            <th id='tb-mproducto-grid_c0'>SKU</th> " .
                "            <th id='tb-mproducto-grid_c0'>Descripci&oacute;n</th> " .
                "            <th id='tb-mproducto-grid_c1'>Cant</th> " .
                "            <th id='tb-mproducto-grid_c2'>UM</th></tr> ";
            foreach ($arrPromos as $l) {
                $html .= "<tr class='odd'> " .
                    "<td>" . $l["sku"] . "</td> " .
                    "<td>" . $l["nombre"] . "</td> " .
                    "<td align='center'>" . $l["cantidad"] . "</td> " .
                    "<td align='center'>" . $l["um"] . "</td> " .
                    "</tr>";
            }
            $html .= "</table>";
        }
        //body += "</div>";
        $html .= "<center><img width='100%' src='http://maps.googleapis.com/maps/api/staticmap?center=".$lat.",".$lon."&zoom=13&scale=2&size=600x300&maptype=roadmap&format=png&visual_refresh=true&markers=size:mid%7Ccolor:0xff0000%7Clabel:%7C".$lat.",".$lon."'></center>";
        $html .= "</body></html>";
        $subject = "Pedido $pedido";
        $this->sendMail_by_SendGrid($_email, $subject, $html);
        return "Pedido $pedido Enviado...";
    }

    function sendMail_by_SendGrid($_email, $subject, $html) {
        $url = 'https://api.sendgrid.com/';
        $user = 'azure_c6177841f12624a9a601069a8fae1619@azure.com';
        $pass = '@advlaspi2017@';
        $params = array(
            'api_user'  => $user,
            'api_key'   => $pass,
            'to'        => $_email,
            'subject'   => $subject,
            'html'      => $html,
            'text'      => '',
            'from'      => 'aolivares@adventech-logistica.com',
        );
        $request =  $url.'api/mail.send.json';
        // Generate curl request
        $session = curl_init($request);
        // Tell curl to use HTTP POST
        curl_setopt ($session, CURLOPT_POST, true);
        // Tell curl that this is the body of the POST
        curl_setopt ($session, CURLOPT_POSTFIELDS, $params);
        // Tell curl not to return headers, but do return the response
        curl_setopt($session, CURLOPT_HEADER, false);
        // Tell PHP not to use SSLv3 (instead opting for TLS)
        //curl_setopt($session, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        //Turn off SSL
        curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);//New line
        curl_setopt($session, CURLOPT_SSL_VERIFYHOST, false);//New line
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        // obtain response
        $response = curl_exec($session);
        // print everything out
        var_dump($response,curl_error($session),curl_getinfo($session));
        curl_close($session);
    }

    function strVentas2sql($str)
    {
        $arrRows = explode("^", $str);
        foreach ($arrRows as $lineas) {
            if ($lineas != "") {
                $html = "<!DOCTYPE html>
                <html>
                <head>
                    <title>Pedido</title>
                    <style>
                        table {
                            font-family: Calibri; width: 100%; font-size: 1em;	}		
                        table.one { width: 100%;
                        margin-bottom: 1em;	
                        width: 100%;
                        font-family: Calibri; font-size: 1em;	}	
                        table.one td { 
                        text-align: center;     
                        padding: 1px; font-family: Calibri; font-size: 1em;		}		
                        table.one th {
                        text-align: center;					
                        padding: 3px;
                        background: url('http://demo.samenlinea.com/bg.gif') repeat-x scroll left top white;		
                        color: white; font-family: Calibri; font-size: 1em;		}			      
                        table.one tr {
                        padding-right: 5px;
                        background-color: #E5F1F4;
                        font-family: Calibri; font-size: 1em;	}
                        table.one tr:nth-child(even) {			
                        background-color: #eee;	font-family: Calibri; font-size: 1em;	}
                        table.one tr:nth-child(odd) {			
                        background-color:#fff;	font-family: Calibri; font-size: 1em;	}
                </style>
                </head>
                <body>";
                preg_match("/^.*(\[.*\]).*(\[.*\]).*$/", $lineas, $res);
                $strClientes = str_replace(array("[", "]"), "", $res[1]);
                $arrClientes = explode("|", $strClientes);
                $strProductos = str_replace(array("[", "]"), "", $res[2]);
                $arrProductos0 = explode("::::", $strProductos);
                $arrCols = explode("|", $lineas);
                $pedido = $arrCols[0];
                $IVA = $arrCols[6];
                $TOT = $arrCols[7];
                $lat = $arrCols[8];
                $lon = $arrCols[9];
                $SUBT = $arrCols[10];
                $IEPS = $arrCols[11];
                $_items = $arrCols[12];
                $KG = $arrCols[13];
                $idRuta = $arrCols[14];
                $CodigoCliente = $arrCols[15];
                $NombreCliente = $arrCols[16];
                $DireccionCliente = $arrCols[17];
                $Ruta = $arrCols[18];
                $Vendedor = $arrCols[19];
                $NuevaFechaVence = $arrCols[20];
                $Folio = $arrCols[21];
                //$NuevaFechaEntrega = $arrCols[22];
                $_email = $arrCols[22];
                $image = "<img width='80' height='80' style='padding:5px;' src='http://demo.samenlinea.com/logo_mails.png' />";
                $html .= "<div id='tb-mproducto-grid' class='grid-view' style='padding-top:10px'>";
                $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center' valign='middle'><center>".$image."</center></td>" .
                    "    <td align='left' valign='middle' height='20%' style='font-family: Calibri; font-size: 1em;'><b>Venta - (Ticket)</b><br>" .
                    "    <b>Sociedad Cooperativa Trabajadores de Pascual SCL</b><br>" .
                    "    <b>Sucursal Puebla  TEL. 2860641/2860642</b><br>" .
                    "    <b>Cerrada de San Joaquin No. 5 Col. Chachapa</b><br>" .
                    "    <b>Puebla Pue. C.P. 72990</b>" .
                    "    </td>" .
                    "  </tr>" .
                    "</table><div style='width:100%; height:.5px; background-color:#ccc; margin-top:2px; margin-bottom:2px; float:left;'></div>";
                $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center' style='font-family: Calibri; font-size: 1em;'><b>Ruta:</b> " . $idRuta . "</td>" .
                    "    <td align='center' style='font-family: Calibri; font-size: 1em;'><b>Cliente:</b> " . $CodigoCliente . "</td>" .
                    "    <td align='center' style='font-family: Calibri; font-size: 1em;'><b>Folio:</b> " . $Folio . "</td>" .
                    "  </tr>" .
                    "</table>" .
                    "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center'></td>" .
                    "    <td align='center' style='font-family: Calibri; font-size: 1em;'>" . $NombreCliente . "</td>" .
                    "    <td align='center'></td>" .
                    "  </tr>" .
                    "</table>" .
                    "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center'></td>" .
                    "    <td align='center' style='font-family: Calibri; font-size: 1em;'>" . $DireccionCliente . "</td>" .
                    "    <td align='center'></td>" .
                    "  </tr>" .
                    "</table><div style='width:100%; height:.5px; background-color:#ccc; margin-top:2px; margin-bottom:2px; float:left;'></div>";
                $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center' style='font-family: Calibri; font-size: 1em;'><b>Ticket de Venta / ORIGINAL</b></td>" .
                    "  </tr>" .
                    "</table>";
                $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center' style='font-family: Calibri; font-size: 1em;'><b>Vendedor:</b> " . $Ruta . " - " . $Vendedor . "</td>" .
                    "  </tr>" .
                    "</table><div style='width:100%; height:.5px; background-color:#ccc; margin-top:2px; margin-bottom:2px; float:left;'></div>";                $html .= "<table class='one'>
                            <tr>
                                <th>Forma de Pago</th>
                                <th>Fecha de Entrega</th>
                            </tr>
                            <tr>
                                <td>" . $arrCols[2] . "</td>
                                <td>" . $NuevaFechaVence . "</td>
                            </tr>
                        </table>";
                $html .= "<table class='one'> " .
                    "<tr> " .
                    "<th>SKU</th> " .
                    "<th>Art&iacute;culos</th> " .
                    "<th>Cant</th> " .
                    "<th>UM</th> " .
                    "<th>Precio</th> " .
                    "<th>Importe</th></tr> ";
                $arrPromos = array();
                $arrEnvases = array();
                foreach ($arrProductos0 as $lineProductos) {
                    if (!empty($lineProductos)) {
                        $arrColsP = explode("|", $lineProductos);
                        $sku = $arrColsP[0];
                        $nombre = $arrColsP[1];
                        $cantidad = $arrColsP[2];
                        $preciobase = $arrColsP[3];
                        $um = $arrColsP[4];
                        $tp = $arrColsP[5];
                        $preciodes = $preciobase;
                        $precioimp = $preciobase * ($iva / 100);
                        $totalIva = $totalIva + $precioimp;
                        $sTotal = floatval($preciobase) * intval($cantidad);
                        $subtotal = $subtotal + $sTotal;
                        $_subtotal = $_subtotal + ($cantidad * $preciobase);
                        $preciobase = '$' . number_format($preciobase, 2, '.', ',');
                        $sTotal = '$' . number_format($sTotal, 2, '.', ',');
                        if ($tp == "promo") {
                            $arrPromos[] = array("sku" => $sku, "nombre" => $nombre, "cantidad" => $cantidad, "preciobase" => $preciobase, "um" => $um, "Total" => $sTotal);
                            continue;
                        }
                        if ($tp == "envases") {
                            $arrEnvases[] = array("sku" => $sku, "nombre" => $nombre, "cantidad" => $cantidad, "preciobase" => $preciobase, "um" => $um, "Total" => $sTotal);
                            continue;
                        }
                        $html .= "<tr> " .
                            "<td>" . $sku . "</td> " .
                            "<td>" . $nombre . "</td> " .
                            "<td align='center'>" . $cantidad . "</td> " .
                            "<td align='center' style='text-align: center;'>" . $um . "</td> " .
                            "<td align='right' style='text-align: right;'>" . $preciobase . "</td> " .
                            "<td align='right' style='text-align: right;'>" . $sTotal . "</td> " .
                            "</tr>";
                    }
                }
                $html .= "</table><br>";
                $html .= "<table class='one' style='font-family: Calibri; font-size: 1em;width: 100%'>";
                $html .= "<tr><td align='right'  style='text-align: right;' width='80%'><b>Lt/Kg.:</b></td><td align='right' width='20%' style='text-align: right;'>" . $KG . "</td></tr>";
                $html .= "<tr><td align='right' style='text-align: right;'><b>Productos.:</b></td><td align='right' style='text-align: right;'>" . $_items . "</td></tr>";
                $html .= "<tr><td align='right' style='text-align: right;'><b>IVA.:</b></td><td align='right' style='text-align: right;'>\$" . number_format($IVA, 2, '.', ',') . "</td></tr>";
                $html .= "<tr><td align='right' style='text-align: right;'><b>IEPS.:</b></td><td align='right' style='text-align: right;'>\$" . number_format($IEPS, 2, '.', ',') ."</td></tr>";
                $html .= "<tr><td align='right' style='text-align: right;'><b>Sub Total:</b></td><td align='right' style='text-align: right;'>\$" . number_format($_subtotal, 2, '.', ',') . "</td></tr>";
                $html .= "<tr><td align='right' style='text-align: right;'><b>Descuento:</b></td><td align='right' style='text-align: right;'>\$0.00</td></tr>";
                $html .= "<tr><td align='right' style='text-align: right;'><b>Total Pedido.:</b></td><td align='right' style='text-align: right;'>\$". number_format($TOT, 2, '.', ',') ."</td></tr>";
                $html .= "</table>";
                if (!empty($arrPromos)) {
                    $html .= "<table  class='one'> " .
                        "         " .
                        "        <tr> " .
                        "            <th id='tb-mproducto-grid_c0'>Promociones</th> " .
                        "        </tr> " .
                        "    </table> " .
                        "    <table class='one'> " .
                        " " .
                        "        <tr> " .
                        "            <th id='tb-mproducto-grid_c0'>SKU</th> " .
                        "            <th id='tb-mproducto-grid_c0'>Descripci&oacute;n</th> " .
                        "            <th id='tb-mproducto-grid_c1'>Cant</th> " .
                        "            <th id='tb-mproducto-grid_c2'>UM</th></tr> ";
                    foreach ($arrPromos as $l) {
                        $html .= "<tr class='odd'> " .
                            "<td>" . $l["sku"] . "</td> " .
                            "<td>" . $l["nombre"] . "</td> " .
                            "<td align='center'>" . $l["cantidad"] . "</td> " .
                            "<td align='center'>" . $l["um"] . "</td> " .
                            "</tr>";
                    }
                    $html .= "</table>";
                }
                if (!empty($arrEnvases)) {
                    $html .= "<table  class='one'> " .
                        "         " .
                        "        <tr> " .
                        "            <th id='tb-mproducto-grid_c0'>Promociones de Envase</th> " .
                        "        </tr> " .
                        "    </table> " .
                        "    <table class='one'> " .
                        " " .
                        "        <tr> " .
                        "            <th id='tb-mproducto-grid_c0'>SKU</th> " .
                        "            <th id='tb-mproducto-grid_c1'>Descripci&oacute;n</th> " .
                        "            <th id='tb-mproducto-grid_c1'>Cant</th> " .
                        "            <th id='tb-mproducto-grid_c2'>UM</th></tr> ";
                    foreach ($arrPromos as $l) {
                        $html .= "<tr class='odd'> " .
                            "<td>" . $l["sku"] . "</td> " .
                            "<td align='center'>" . $l["nombre"] . "</td> " .
                            "<td align='center'>" . $l["cantidad"] . "</td> " .
                            "<td align='center'>" . $l["um"] . "</td> " .
                            "</tr>";
                    }
                    $html .= "</table>";
                }
                //body += "</div>";
                $html .= "<center><img width='100%' src='http://maps.googleapis.com/maps/api/staticmap?center=".$lat.",".$lon."&zoom=13&scale=2&size=600x300&maptype=roadmap&format=png&visual_refresh=true&markers=size:mid%7Ccolor:0xff0000%7Clabel:%7C".$lat.",".$lon."'></center>";
                $html .= "</body></html>";
                $email_address = "luisfraino@gmail.com";
                include("class.phpmailer.php");
                $mail = new PHPMailer();
                $mail->IsSMTP();                                   // send via SMTP
                $mail->Host     = "localhost"; // SMTP servers
                $mail->SMTPAuth = false;     // turn on SMTP authentication
                $mail->Username = "";  // SMTP username
                $mail->Password = ""; // SMTP password
                $mail->From     = "info@demo.samenlinea.com";
                $mail->FromName = "WebMaster";
                $mail->AddAddress($email_address, ' ');
                $mail->AddAddress("aolivares242@gmail.com", ' ');               // optional name, $firstname." ".$lastname
                $mail->AddAddress("aolivares@adventech-logistica.com", ' ');
                $mail->AddAddress($_email, ' ');
                //$mail->AddReplyTo("info@site.com","Information");
                $mail->WordWrap = 50;                              // set word wrap
                //$mail->AddAttachment("/var/tmp/file.tar.gz");      // attachment
                //$mail->AddAttachment("/tmp/image.jpg", "new.jpg");
                $mail->IsHTML(true);                               // send as HTML
                $mail->Subject  =  "Venta N: $pedido";
                $mail->Body     =  $html;
                $mail->AltBody  =  "This is the text-only body";
                $mail->Send();
            }
        }
        return "Pedido $pedido Enviado...";
    }

    function strAvisoVisita($str)
    {
        $conn = mysqli_connect($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "SELECT Linea1, Linea2, Linea3, Linea4, Mensaje, LOGO FROM ctiket";
        $result = mysqli_query($conn, $sql);
        $_arr = array();
        $row = mysqli_fetch_array($result);
        $Linea1 = $row["Linea1"];
        $Linea2 = $row["Linea2"];
        $Linea3 = $row["Linea3"];
        $Linea4 = $row["Linea4"];
        $html = "<!DOCTYPE html>
		<html>
		<head>
			<title>Pedido</title>
			<style>
				table {
					font-family: Calibri; width: 100%; font-size: 1em;	}		
				table.one { width: 100%;
				margin-bottom: 1em;	
				width: 100%;
				font-family: Calibri; font-size: 1em;	}	
				table.one td { 
				text-align: center;     
				padding: 1px; font-family: Calibri; font-size: 1em;		}		
				table.one th {
				text-align: center;					
				padding: 3px;
				background: url('http://demo.samenlinea.com/bg.gif') repeat-x scroll left top white;		
				color: white; font-family: Calibri; font-size: 1em;		}			      
				table.one tr {
				padding-right: 5px;
				background-color: #E5F1F4;
				font-family: Calibri; font-size: 1em;	}
				table.one tr:nth-child(even) {			
				background-color: #eee;	font-family: Calibri; font-size: 1em;	}
				table.one tr:nth-child(odd) {			
				background-color:#fff;	font-family: Calibri; font-size: 1em;	}
		</style>
		</head>
		<body>
		";
        $arrCols = explode("|", $str);
        $lat = $arrCols[7];
        $lon = $arrCols[8];
        $idRuta = $arrCols[0];
        $CodigoCliente = $arrCols[1];
        $NombreCliente = $arrCols[2];
        $DireccionCliente = $arrCols[3];
        $Ruta = $arrCols[4];
        $Vendedor = $arrCols[5];
        //$NuevaFechaEntrega = $arrCols[23];
        $_email = $arrCols[6];
        $image = "<img width='80' height='80' style='padding:5px;' src='http://demo.samenlinea.com/logo_mails.png' />";
        $html .= "<div id='tb-mproducto-grid' class='grid-view' style='padding-top:10px'>";
        $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
            "  <tr>" .
            "    <td align='center' valign='middle'><center>".$image."</center></td>" .
            "    <td align='left' valign='middle' height='20%' style='font-family: Calibri; font-size: 1em;'><b>Aviso Visita</b><br>" .
            "    <b>".$Linea1."</b><br>" .
            "    <b>".$Linea2."</b><br>" .
            "    <b>".$Linea3."</b><br>" .
            "    <b>".$Linea4."</b>" .
            "    </td>" .
            "  </tr>" .
            "</table><div style='width:100%; height:.5px; background-color:#ccc; margin-top:2px; margin-bottom:2px; float:left;'></div>";
        $dateString = date("d-m-Y");
        $dateStringH = date("H:i:s");
        $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
            "  <tr>" .
            "    <td align='center'><b>Fecha:</b>" . $dateString . "</td>" .
            "    <td align='center'><b>Hora:</b>" . $dateStringH . "</td>" .
            "  </tr>" .
            "</table>";
        $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
            "  <tr>" .
            "    <td align='center' style='font-family: Calibri; font-size: 1em;'><b>Ruta:</b> " . $idRuta . "</td>" .
            "  </tr>" .
            "</table><div style='width:100%; height:.5px; background-color:#ccc; margin-top:2px; margin-bottom:2px; float:left;'></div>";
        $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
            "  <tr>" .
            "    <td align='center' style='font-family: Calibri; font-size: 1em;'><b>Vendedor:</b> " . $Ruta . " - " . $Vendedor . "</td>" .
            "  </tr>" .
            "</table><div style='width:100%; height:.5px; background-color:#ccc; margin-top:2px; margin-bottom:2px; float:left;'></div>";
        $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>";
        $html .= "<tr><td align='center' style='font-family: Calibri; font-size: 2em;'><b>El d&iacute;a de hoy pasamos a visitarle y estuvo cerrado, favor de comunicarse a nuestras oficinas si requiere servicio inmediato o espere nuestra pr&oacute;xima visita , esperamos servirle pronto.</b></td></tr>";
        $html .= "</table>";
        //$html .= "<center><img width='100%' src='http://maps.googleapis.com/maps/api/staticmap?center=".$lat.",".$lon."&zoom=13&scale=2&size=600x300&maptype=roadmap&format=png&visual_refresh=true&markers=size:mid%7Ccolor:0xff0000%7Clabel:%7C".$lat.",".$lon."'></center>";
        $html .= "</body></html>";
        $email_address = "luisfraino@gmail.com";
        include("class.phpmailer.php");
        $mail = new PHPMailer();
        $mail->IsSMTP();                                   // send via SMTP
        $mail->Host     = "localhost"; // SMTP servers
        $mail->SMTPAuth = false;     // turn on SMTP authentication
        $mail->Username = "";  // SMTP username
        $mail->Password = ""; // SMTP password
        $mail->From     = "info@demo.samenlinea.com";
        $mail->FromName = "WebMaster";
        $mail->AddAddress("aolivares242@gmail.com", ' ');               // optional name, $firstname." ".$lastname
        $mail->AddAddress("aolivares@adventech-logistica.com", ' ');
        $mail->AddAddress($email_address, ' ');
        //$mail->AddAddress($_email, ' ');
        //$mail->AddReplyTo("info@site.com","Information");
        $mail->WordWrap = 50;                              // set word wrap
        //$mail->AddAttachment("/var/tmp/file.tar.gz");      // attachment
        //$mail->AddAttachment("/tmp/image.jpg", "new.jpg");
        $mail->IsHTML(true);                               // send as HTML
        $mail->Subject  =  "Aviso Visita";
        $mail->Body     =  $html;
        $mail->AltBody  =  "This is the text-only body";
        $mail->Send();
        return "Enviado...";
    }

    function strDevolucion2sql($str)
    {
        $arrRows = explode("^", $str);
        foreach ($arrRows as $lineas) {
            if ($lineas != "") {
                $html = "<!DOCTYPE html>
                <html>
                <head>
                    <title>Pedido</title>
                    <style>
                        table {
                            font-family: Calibri; width: 100%; font-size: 1em;	}		
                        table.one { width: 100%;
                        margin-bottom: 1em;	
                        width: 100%;
                        font-family: Calibri; font-size: 1em;	}	
                        table.one td { 
                        text-align: center;     
                        padding: 1px; font-family: Calibri; font-size: 1em;		}		
                        table.one th {
                        text-align: center;					
                        padding: 3px;
                        background: url('http://demo.samenlinea.com/bg.gif') repeat-x scroll left top white;		
                        color: white; font-family: Calibri; font-size: 1em;		}			      
                        table.one tr {
                        padding-right: 5px;
                        background-color: #E5F1F4;
                        font-family: Calibri; font-size: 1em;	}
                        table.one tr:nth-child(even) {			
                        background-color: #eee;	font-family: Calibri; font-size: 1em;	}
                        table.one tr:nth-child(odd) {			
                        background-color:#fff;	font-family: Calibri; font-size: 1em;	}
                </style>
                </head>
                <body>
                ";
                preg_match("/^.*(\[.*\]).*(\[.*\]).*$/", $lineas, $res);
                $strClientes = str_replace(array("[", "]"), "", $res[1]);
                $arrClientes = explode("|", $strClientes);
                $strProductos = str_replace(array("[", "]"), "", $res[2]);
                $arrProductos0 = explode("::::", $strProductos);
                $arrCols = explode("|", $lineas);
                $pedido = $arrCols[0];
                $IVA = $arrCols[6];
                $TOT = $arrCols[7];
                $lat = $arrCols[8];
                $lon = $arrCols[9];
                $SUBT = $arrCols[10];
                $IEPS = $arrCols[11];
                $_items = $arrCols[12];
                $KG = $arrCols[13];
                $idRuta = $arrCols[14];
                $CodigoCliente = $arrCols[15];
                $NombreCliente = $arrCols[16];
                $DireccionCliente = $arrCols[17];
                $Ruta = $arrCols[18];
                $Vendedor = $arrCols[19];
                $NuevaFechaVence = $arrCols[20];
                $Folio = $arrCols[21];
                $NuevaFechaEntrega = $arrCols[22];
                //$NuevaFechaEntrega = $arrCols[23];
                $_email = $arrCols[22];
                $image = "<img width='80' height='80' style='padding:5px;' src='http://demo.samenlinea.com/logo_mails.png' />";
                $html .= "<div id='tb-mproducto-grid' class='grid-view' style='padding-top:10px'>";
                $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center' valign='middle'><center>".$image."</center></td>" .
                    "    <td align='left' valign='middle' height='20%' style='font-family: Calibri; font-size: 1em;'><b>Devoluci&oacute;n - (Ticket)</b><br>" .
                    "    <b>Sociedad Cooperativa Trabajadores de Pascual SCL</b><br>" .
                    "    <b>Sucursal Puebla  TEL. 2860641/2860642</b><br>" .
                    "    <b>Cerrada de San Joaquin No. 5 Col. Chachapa</b><br>" .
                    "    <b>Puebla Pue. C.P. 72990</b>" .
                    "    </td>" .
                    "  </tr>" .
                    "</table><div style='width:100%; height:.5px; background-color:#ccc; margin-top:2px; margin-bottom:2px; float:left;'></div>";
                $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center' style='font-family: Calibri; font-size: 1em;'><b>Ruta:</b> " . $idRuta . "</td>" .
                    //"    <td align='center' style='font-family: Calibri; font-size: 1em;'><b>Cliente:</b> " . $CodigoCliente . "</td>" .
                    "    <td align='center' style='font-family: Calibri; font-size: 1em;'><b>Folio:</b> " . $Folio . "</td>" .
                    "  </tr>" .
                    "</table><div style='width:100%; height:.5px; background-color:#ccc; margin-top:2px; margin-bottom:2px; float:left;'></div>";
                $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center' style='font-family: Calibri; font-size: 1em;'><b>Ticket de Devoluci&oacute;n / ORIGINAL</b></td>" .
                    "  </tr>" .
                    "</table>";
                $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center' style='font-family: Calibri; font-size: 1em;'><b>Vendedor:</b> " . $Ruta . " - " . $Vendedor . "</td>" .
                    "  </tr>" .
                    "</table><div style='width:100%; height:.5px; background-color:#ccc; margin-top:2px; margin-bottom:2px; float:left;'></div>";
                $html .= "<table class='one'> " .
                    "<tr> " .
                    "<th>SKU</th> " .
                    "<th>Descripci&oacute;n</th> " .
                    "<th>Cant</th> " .
                    "<th>UM</th> " .
                    "<th>Precio</th> " .
                    "<th>Importe</th></tr> ";
                $arrPromos = array();
                foreach ($arrProductos0 as $lineProductos) {
                    //writeLog("Numero Pedido: ".$nropedidobb.", Productos: ".$lineProductos, 1);
                    if (!empty($lineProductos)) {
                        $arrColsP = explode("|", $lineProductos);
                        $sku = $arrColsP[0];
                        $nombre = $arrColsP[1];
                        $cantidad = $arrColsP[2];
                        $preciobase = $arrColsP[3];
                        $um = $arrColsP[4];
                        $tp = $arrColsP[5];
                        $preciodes = $preciobase;
                        $precioimp = $preciobase * ($iva / 100);
                        $totalIva = $totalIva + $precioimp;
                        $sTotal = floatval($preciobase) * intval($cantidad);
                        $subtotal = $subtotal + $sTotal;
                        $_subtotal = $_subtotal + ($cantidad * $preciobase);
                        $preciobase = '$' . number_format($preciobase, 2, '.', ',');
                        $sTotal = '$' . number_format($sTotal, 2, '.', ',');
                        if ($tp == "promo") {
                            $arrPromos[] = array("sku" => $sku, "nombre" => $nombre, "cantidad" => $cantidad, "preciobase" => $preciobase, "um" => $um, "Total" => $sTotal);
                            continue;
                        }
                        $html .= "<tr> " .
                            "<td>" . $sku . "</td> " .
                            "<td>" . $nombre . "</td> " .
                            "<td align='center'>" . $cantidad . "</td> " .
                            "<td align='center' style='text-align: center;'>" . $um . "</td> " .
                            "<td align='right' style='text-align: right;'>" . $preciobase . "</td> " .
                            "<td align='right' style='text-align: right;'>" . $sTotal . "</td> " .
                            "</tr>";
                    }
                }
                $html .= "</table><br>";
                $html .= "<table class='one' style='font-family: Calibri; font-size: 1em;width: 100%'>";
                $html .= "<tr><td align='right'  style='text-align: right;' width='80%'><b>Lt/Kg.:</b></td><td align='right' width='20%' style='text-align: right;'>" . $KG . "</td></tr>";
                $html .= "<tr><td align='right' style='text-align: right;'><b>Productos.:</b></td><td align='right' style='text-align: right;'>" . $_items . "</td></tr>";
                $html .= "<tr><td align='right' style='text-align: right;'><b>IVA.:</b></td><td align='right' style='text-align: right;'>\$" . number_format($IVA, 2, '.', ',') . "</td></tr>";
                $html .= "<tr><td align='right' style='text-align: right;'><b>IEPS.:</b></td><td align='right' style='text-align: right;'>\$" . number_format($IEPS, 2, '.', ',') ."</td></tr>";
                $html .= "<tr><td align='right' style='text-align: right;'><b>Sub Total:</b></td><td align='right' style='text-align: right;'>\$" . number_format($_subtotal, 2, '.', ',') . "</td></tr>";
                $html .= "<tr><td align='right' style='text-align: right;'><b>Descuento:</b></td><td align='right' style='text-align: right;'>\$0.00</td></tr>";
                $html .= "<tr><td align='right' style='text-align: right;'><b>Total Pedido.:</b></td><td align='right' style='text-align: right;'>\$". number_format($TOT, 2, '.', ',') ."</td></tr>";
                $html .= "</table>";
                if (!empty($arrPromos)) {
                    $html .= "<table  class='one'> " .
                        "         " .
                        "        <tr> " .
                        "            <th id='tb-mproducto-grid_c0'>Promociones</th> " .
                        "        </tr> " .
                        "    </table> " .
                        "    <table class='one'> " .
                        " " .
                        "        <tr> " .
                        "            <th id='tb-mproducto-grid_c0'>SKU</th> " .
                        "            <th id='tb-mproducto-grid_c0'>Descripci&oacute;n</th> " .
                        "            <th id='tb-mproducto-grid_c1'>Cant</th> " .
                        "            <th id='tb-mproducto-grid_c2'>UM</th></tr> ";
                    foreach ($arrPromos as $l) {
                        $html .= "<tr class='odd'> " .
                            "<td>" . $l["sku"] . "</td> " .
                            "<td>" . $l["nombre"] . "</td> " .
                            "<td align='center'>" . $l["cantidad"] . "</td> " .
                            "<td align='center'>" . $l["um"] . "</td> " .
                            "</tr>";
                    }
                    $html .= "</table>";
                }
                //body += "</div>";
                $html .= "<center><img width='100%' src='http://maps.googleapis.com/maps/api/staticmap?center=".$lat.",".$lon."&zoom=13&scale=2&size=600x300&maptype=roadmap&format=png&visual_refresh=true&markers=size:mid%7Ccolor:0xff0000%7Clabel:%7C".$lat.",".$lon."'></center>";
                $html .= "</body></html>";
                $email_address = "luisfraino@gmail.com";
                include("class.phpmailer.php");
                $mail = new PHPMailer();
                $mail->IsSMTP();                                   // send via SMTP
                $mail->Host     = "localhost"; // SMTP servers
                $mail->SMTPAuth = false;     // turn on SMTP authentication
                $mail->Username = "";  // SMTP username
                $mail->Password = ""; // SMTP password
                $mail->From     = "info@demo.samenlinea.com";
                $mail->FromName = "WebMaster";
                $mail->AddAddress($email_address, ' ');
                $mail->AddAddress("aolivares242@gmail.com", ' ');               // optional name, $firstname." ".$lastname
                $mail->AddAddress("aolivares@adventech-logistica.com", ' ');
                $mail->AddAddress($_email, ' ');
                //$mail->AddReplyTo("info@site.com","Information");
                $mail->WordWrap = 50;                              // set word wrap
                //$mail->AddAttachment("/var/tmp/file.tar.gz");      // attachment
                //$mail->AddAttachment("/tmp/image.jpg", "new.jpg");
                $mail->IsHTML(true);                               // send as HTML
                $mail->Subject  =  "Devolucion N: $pedido";
                $mail->Body     =  $html;
                $mail->AltBody  =  "This is the text-only body";
                $mail->Send();
            }
        }
        return "Pedido $pedido Enviado...";
    }

    function strDevolucionEnvases2sql($str)
    {
        $arrRows = explode("^", $str);
        foreach ($arrRows as $lineas) {
            if ($lineas != "") {
                $html = "<!DOCTYPE html>
                <html>
                <head>
                    <title>Devolucion de Envases</title>
                    <style>
                        table {
                            font-family: Calibri; width: 100%; font-size: 1em;	}		
                        table.one { width: 100%;
                        margin-bottom: 1em;	
                        width: 100%;
                        font-family: Calibri; font-size: 1em;	}	
                        table.one td { 
                        text-align: center;     
                        padding: 1px; font-family: Calibri; font-size: 1em;		}		
                        table.one th {
                        text-align: center;					
                        padding: 3px;
                        background: url('http://demo.samenlinea.com/bg.gif') repeat-x scroll left top white;		
                        color: white; font-family: Calibri; font-size: 1em;		}			      
                        table.one tr {
                        padding-right: 5px;
                        background-color: #E5F1F4;
                        font-family: Calibri; font-size: 1em;	}
                        table.one tr:nth-child(even) {			
                        background-color: #eee;	font-family: Calibri; font-size: 1em;	}
                        table.one tr:nth-child(odd) {			
                        background-color:#fff;	font-family: Calibri; font-size: 1em;	}
                </style>
                </head>
                <body>";
                preg_match("/^.*(\[.*\]).*(\[.*\]).*$/", $lineas, $res);
                $strClientes = str_replace(array("[", "]"), "", $res[1]);
                $arrClientes = explode("|", $strClientes);
                $strProductos = str_replace(array("[", "]"), "", $res[2]);
                $arrProductos0 = explode("::::", $strProductos);
                $arrCols = explode("|", $lineas);
                $pedido = $arrCols[0];
                $IVA = $arrCols[6];
                $TOT = $arrCols[7];
                $lat = $arrCols[8];
                $lon = $arrCols[9];
                $SUBT = $arrCols[10];
                $IEPS = $arrCols[11];
                $_items = $arrCols[12];
                $KG = $arrCols[13];
                $idRuta = $arrCols[14];
                $CodigoCliente = $arrCols[15];
                $NombreCliente = $arrCols[16];
                $DireccionCliente = $arrCols[17];
                $Ruta = $arrCols[18];
                $Vendedor = $arrCols[19];
                $NuevaFechaVence = $arrCols[20];
                $Folio = $arrCols[21];
                //$NuevaFechaEntrega = $arrCols[22];
                $_email = $arrCols[22];
                $image = "<img width='80' height='80' style='padding:5px;' src='http://demo.samenlinea.com/logo_mails.png' />";
                $html .= "<div id='tb-mproducto-grid' class='grid-view' style='padding-top:10px'>";
                $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center' valign='middle'><center>".$image."</center></td>" .
                    "    <td align='left' valign='middle' height='20%' style='font-family: Calibri; font-size: 1em;'><b>Devolucion de Envases - (Ticket)</b><br>" .
                    "    <b>Sociedad Cooperativa Trabajadores de Pascual SCL</b><br>" .
                    "    <b>Sucursal Puebla  TEL. 2860641/2860642</b><br>" .
                    "    <b>Cerrada de San Joaquin No. 5 Col. Chachapa</b><br>" .
                    "    <b>Puebla Pue. C.P. 72990</b>" .
                    "    </td>" .
                    "  </tr>" .
                    "</table><div style='width:100%; height:.5px; background-color:#ccc; margin-top:2px; margin-bottom:2px; float:left;'></div>";
                /*$html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center'><b>Ruta:</b> " . $idRuta . "</td>" .
                    "    <td align='center'><b>Cliente:</b> " . $CodigoCliente . "</td>" .
                    "  </tr>" .
                    "</table>" .
                    "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center'><b>Folio(s):</b> " . $Folios . "</td>" .
                    "  </tr>" .
                    "</table>" .
                    "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    //"    <td align='center'></td>" .
                    "    <td align='center'>" . $NombreCliente . "</td>" .
                    //"    <td align='center'></td>" .
                    "  </tr>" .
                    "</table>" .
                    "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    //"    <td align='center'></td>" .
                    "    <td align='center'>" . $DireccionCliente . "</td>" .
                    //"    <td align='center'></td>" .
                    "  </tr>" .
                    "</table><div style='width:100%; height:.5px; background-color:#ccc; margin-top:2px; margin-bottom:2px; float:left;'></div>";*/
                $dateString = date("d-m-Y");
                $dateStringH = date("H:i:s");
                $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center'><b>Ruta:</b>" . $idRuta . "</td>" .
                    "    <td align='center'><b>Cliente:</b>" . $CodigoCliente . "</td>" .
                    //"    <td align='center'><b>Folio(s):</b> " . _Folio . "</td>" .
                    "  </tr>" .
                    "</table>" .
                    "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center'><b>Fecha:</b>" . $dateString . "</td>" .
                    "    <td align='center'><b>Hora:</b>" . $dateStringH . "</td>" .
                    "  </tr>" .
                    "</table>" .
                    "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center'><b>Comodato REF:</b>" . $arrCols[0] . "</td>" .
                    "    <td align='center'><b>Folio(s):</b>" . $arrCols[3] . "</td>" .
                    "  </tr>" .
                    "</table>" .
                    "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    //"    <td align='center'></td>" .
                    "    <td align='center'>" . $NombreCliente . "</td>" .
                    //"    <td align='center'></td>" .
                    "  </tr>" .
                    "</table>" .
                    "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    //"    <td align='center'></td>" .
                    "    <td align='center'>" . $DireccionCliente . "</td>" .
                    //"    <td align='center'></td>" .
                    "  </tr>" .
                    "</table><div style='width:100%; height:.5px; background-color:#ccc; margin-top:2px; margin-bottom:2px; float:left;'></div>";
                $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center' style='font-family: Calibri; font-size: 1em;'><b>Devoluci&oacute;n Envases / ORIGINAL</b></td>" .
                    "  </tr>" .
                    "</table>";
                $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center' style='font-family: Calibri; font-size: 1em;'><b>Vendedor:</b> " . $Ruta . " - " . $Vendedor . "</td>" .
                    "  </tr>" .
                    "</table><div style='width:100%; height:.5px; background-color:#ccc; margin-top:2px; margin-bottom:2px; float:left;'></div>";
                $html .= "<table class='one'> " .
                    "<tr>" .
                    "<th id='tb-mproducto-grid_c0'>SKU</th> " .
                    "<th id='tb-mproducto-grid_c0'>Descripci&oacute;n</th> " .
                    "<th id='tb-mproducto-grid_c1'>UM</th> " .
                    "<th id='tb-mproducto-grid_c3'>Importe</th> " .
                    "<th id='tb-mproducto-grid_c3'>Cant</th> " .
                    "<th id='tb-mproducto-grid_c3'>Lt/Kg</th> " .
                    "</tr>";
                $arrPromos = array();
                $_items = 0;
                foreach ($arrProductos0 as $lineProductos) {
                    //writeLog("Numero Pedido: ".$nropedidobb.", Productos: ".$lineProductos, 1);
                    if (!empty($lineProductos)) {
                        $arrColsP = explode("|", $lineProductos);
                        $sku = $arrColsP[0];
                        $nombre = $arrColsP[1];
                        $cantidad = $arrColsP[2];
                        $preciobase = $arrColsP[3];
                        $um = $arrColsP[4];
                        $tp = $arrColsP[5];
                        $kglt = $arrColsP[6];
                        $preciodes = $preciobase;
                        $precioimp = $preciobase * ($iva / 100);
                        $totalIva = $totalIva + $precioimp;
                        $sTotal = floatval($preciobase) * intval($cantidad);
                        $subtotal = $subtotal + $sTotal;
                        $_subtotal = $_subtotal + ($cantidad * $preciobase);
                        $preciobase = '$' . number_format($preciobase, 2, '.', ',');
                        $sTotal = '$' . number_format($sTotal, 2, '.', ',');
                        if ($tp == "promo") {
                            $arrPromos[] = array("sku" => $sku, "nombre" => $nombre, "cantidad" => $cantidad, "preciobase" => $preciobase, "um" => $um, "Total" => $sTotal);
                            continue;
                        }
                        $html .= "<tr> " .
                            "<td>" . $sku . "</td> " .
                            "<td>" . $nombre . "</td> " .
                            "<td align='center' style='text-align: center;'>" . $um . "</td> " .
                            "<td align='right' style='text-align: right;'>" . $preciobase . "</td> " .
                            "<td align='center'>" . $cantidad . "</td> " .
                            "<td align='right' style='text-align: right;'>" . $kglt . "</td> " .
                            "</tr>";
                        $_items++;
                    }
                }
                $html .= "</table><br>";
                $html .= "<table class='one' style='font-family: Calibri; font-size: 1em;width: 100%'>";
                $html .= "<tr><td align='right'  style='text-align: right;' width='80%'><b>Total Lt/Kg.:</b></td><td align='right' width='20%' style='text-align: right;'>" . $KG . "</td></tr>";
                $html .= "<tr><td align='right' style='text-align: right;'><b>Total Piezas.:</b></td><td align='right' style='text-align: right;'>" . $_items . "</td></tr>";
                $html .= "<tr><td align='right' style='text-align: right;'><b>IEPS.:</b></td><td align='right' style='text-align: right;'>\$" . number_format($IEPS, 2, '.', ',') ."</td></tr>";
                $html .= "<tr><td align='right' style='text-align: right;'><b>IVA.:</b></td><td align='right' style='text-align: right;'>\$" . number_format($IVA, 2, '.', ',') . "</td></tr>";
                $html .= "<tr><td align='right' style='text-align: right;'><b>Total Pedido.:</b></td><td align='right' style='text-align: right;'>\$". number_format($TOT, 2, '.', ',') ."</td></tr>";
                $html .= "</table>";
                if (!empty($arrPromos)) {
                    $html .= "<table  class='one'> " .
                        "         " .
                        "        <tr> " .
                        "            <th id='tb-mproducto-grid_c0'>Promociones</th> " .
                        "        </tr> " .
                        "    </table> " .
                        "    <table class='one'> " .
                        " " .
                        "        <tr> " .
                        "            <th id='tb-mproducto-grid_c0'>SKU</th> " .
                        "            <th id='tb-mproducto-grid_c0'>Descripci&oacute;n</th> " .
                        "            <th id='tb-mproducto-grid_c1'>Cant</th> " .
                        "            <th id='tb-mproducto-grid_c2'>UM</th></tr> ";
                    foreach ($arrPromos as $l) {
                        $html .= "<tr class='odd'> " .
                            "<td>" . $l["sku"] . "</td> " .
                            "<td>" . $l["nombre"] . "</td> " .
                            "<td align='center'>" . $l["cantidad"] . "</td> " .
                            "<td align='center'>" . $l["um"] . "</td> " .
                            "</tr>";
                    }
                    $html .= "</table>";
                }
                //body += "</div>";
                $html .= "<center><img width='100%' src='http://maps.googleapis.com/maps/api/staticmap?center=".$lat.",".$lon."&zoom=13&scale=2&size=600x300&maptype=roadmap&format=png&visual_refresh=true&markers=size:mid%7Ccolor:0xff0000%7Clabel:%7C".$lat.",".$lon."'></center>";
                $html .= "</body></html>";
                $email_address = "luisfraino@gmail.com";
                include("class.phpmailer.php");
                $mail = new PHPMailer();
                $mail->IsSMTP();                                   // send via SMTP
                $mail->Host     = "localhost"; // SMTP servers
                $mail->SMTPAuth = false;     // turn on SMTP authentication
                $mail->Username = "";  // SMTP username
                $mail->Password = ""; // SMTP password
                $mail->From     = "info@demo.samenlinea.com";
                $mail->FromName = "WebMaster";
                $mail->AddAddress($email_address, ' ');
                $mail->AddAddress("aolivares242@gmail.com", ' ');               // optional name, $firstname." ".$lastname
                $mail->AddAddress("aolivares@adventech-logistica.com", ' ');               // optional name, $firstname." ".$lastname
                $mail->AddAddress($_email, ' ');               // optional name, $firstname." ".$lastname
                //$mail->AddReplyTo("info@site.com","Information");
                $mail->WordWrap = 50;                              // set word wrap
                //$mail->AddAttachment("/var/tmp/file.tar.gz");      // attachment
                //$mail->AddAttachment("/tmp/image.jpg", "new.jpg");
                $mail->IsHTML(true);                               // send as HTML
                $mail->Subject  =  "Devolución Envases $pedido";
                $mail->Body     =  $html;
                $mail->AltBody  =  "This is the text-only body";
                $mail->Send();
            }
        }
        return "Pedido $pedido Enviado...";
    }
	
	function Sql2JSONGetUltimoMedidor()
    {
        $conn =  new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "SELECT IdRow,DiaO,OdometroInicial,OdometroFinal,TanqueInicial,TanqueFinal,LitrosCargados,GastoLitros,Rendimiento,KmR,IdEmpresa,IdVehiculo,IdRuta FROM Medidores WHERE IdEmpresa = '" . $this->IdEmpresa . "' AND IdRuta = '" . $this->idRuta . "' ORDER BY DiaO DESC Limit 1;";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
			$_arr[] = array(
				"IdRow" => $row[0],
				"DiaO" => $row[1],
				"OdometroInicial" => $row[2],
				"OdometroFinal" => $row[3],
				"TanqueInicial" => $row[4],
				"TanqueFinal" => $row[5],
				"LitrosCargados" => $row[6],
				"GastoLitros" => $row[7],
				"Rendimiento" => $row[8],
				"KmR" => $row[9],
				"IdEmpresa" => $row[10],
				"IdVehiculo" => $row[11],
				"IdRuta" => $row[12]
				);
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }
	
	function Sql2JSONVehiculos()
    {
        $conn =  new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameVehiculoRuta('" . $this->IdEmpresa . "'," . $this->idRuta . ");";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
			$_arr[] = array(
				"Clave" => $row[0],
				"Modelo" => "",
				"Placas" => $row[5],
				"Marcas" => utf8_encode($row[2]),
				"Descripcion" => utf8_encode($row[3]),
				"Status" => "",
				"NumeroEco" => $row[4],
				"Asignado" => "",
				"Poliza" => "",
				"TelSeguro" => "",
				"MesVerifica" => "",
				"Kilometraje" => "",
				"KilometrajeSem" => "",
				"Aseguradora" => "",
				"FechaVencSeguro" => "",
				"FechaUltVerif" => "",
				"IdEmpresa" => $row[11],
				"Modelo_year" => "",
				"IdVehiculo" => $row[1],
                "OdometroInicial" => $row[7],
                "OdometroFinal" => $row[8],
                "TanqueInicial" => $row[9],
                "TanqueFinal" => $row[10],
                "LitrosCargados" => $row[13],
                "GastoLitros" => $row[14],
                "Rendimiento" => $row[15],
                "KmR" => $row[16]
				);
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }
	
	function Sql2JSONEmpresaMadre()
    {
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "select nombre as Empresa,Telefono,Contacto,correo as Email,rut as RFC,Direccion,'' as NoExterior,'' as NoInterior, codigopostal as CP,'' as NombreComercial from c_almacenp Where clave='" . $this->IdEmpresa . "';";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array(
                "Empresa" => utf8_encode($row[0]),
                "Telefono" => utf8_encode($row[1]),
                "Contacto" => utf8_encode($row[2]),
                "Email" => utf8_encode($row[3]),
                "RFC" => utf8_encode($row[4]),
                "Direccion" => utf8_encode($row[5]),
                "NoExterior" => utf8_encode($row[6]),
                "NoInterior" => utf8_encode($row[7]),
                "CP" => utf8_encode($row[8]),
                "NombreComercial" => utf8_encode($row[9])
            );
        }
		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');
        $conn->close();
        return $_arr;
    }
	
	function Sql2JSONMotivosNoVenta()
    {
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "SELECT IdMot,Motivo,Clave,Status FROM MotivosNoVenta;";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array(
                "IdMot" => $row[0],
                "Motivo" => utf8_encode($row[1]),
                "Clave" => utf8_encode($row[2]),
                "Status" => $row[3]
            );
        }
/*		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');*/
        $conn->close();
        return $_arr;
    }
	
	function Sql2JSONIncidencias()
    {
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "SELECT IdMot,Motivo,Clave,Status FROM MotivosNoVenta;";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array(
                "Idincid" => $row[0],
                "Descripcion" => utf8_encode($row[1]),
                "Clave" => utf8_encode($row[2]),
               "Status" => $row[3]
            );
        }
/*		if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');*/
        $conn->close();
        return $_arr;

    }	
	
	function Sql2JSONPRegalado()
    {
        $conn = new mysqli($this->ip_server, $this->user, $this->password, $this->db);
        $sql = "Call SPSFA_DameProductoRegalado('" . $this->IdEmpresa . "'," . $this->idRuta . ");";
        $result = $conn->query($sql);
        $_arr = array();
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $_arr[] = array(
                "SKU" => $row[0],
                "TipMed" => utf8_encode($row[1]),
                "CodCliente" => utf8_encode($row[2]),
                "Docto" => utf8_encode($row[3]),
                "Folio" => utf8_encode($row[3]),
                "Cant" => utf8_encode($row[4]),
                "DiaO" => utf8_encode($row[5]),
                "Tipo" => utf8_encode($row[6])
            );
        }
/*        if ($_arr[0]===null)
			$_arr[] = array(
                "Error" => 'Error',
                "Msg" => 'No hay registros por regresar');*/
        $conn->close();
        return $_arr;
    }
	
	function strSendDevol($str)
    {
        $html = "<!DOCTYPE html>
                <html>
                <head>
                    <title>Pedido</title>
                    <style>
                        table {
                            font-family: Calibri; width: 100%; font-size: 1em;	}		
                        table.one { width: 100%;
                        margin-bottom: 1em;	
                        width: 100%;
                        font-family: Calibri; font-size: 1em;	}	
                        table.one td { 
                        text-align: center;     
                        padding: 1px; font-family: Calibri; font-size: 1em;		}		
                        table.one th {
                        text-align: center;					
                        padding: 3px;
                        background: url('http://SCTP.samenlinea.com/bg.gif') repeat-x scroll left top white;		
                        color: white; font-family: Calibri; font-size: 1em;		}			      
                        table.one tr {
                        padding-right: 5px;
                        background-color: #E5F1F4;
                        font-family: Calibri; font-size: 1em;	}
                        table.one tr:nth-child(even) {			
                        background-color: #eee;	font-family: Calibri; font-size: 1em;	}
                        table.one tr:nth-child(odd) {			
                        background-color:#fff;	font-family: Calibri; font-size: 1em;	}
                </style>
				<meta http-equiv='Content-type' content='text/html; charset=utf-8' />
                </head>
                <body>";
        //$str = "1009|2016-10-26 04:35:28|Credito|2016-11-10|26-10-2016|Desde - 16:34 Hasta - 16:34|0.0|3100.0|10.220255|-67.87516090000003|3100.0|[2406|XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX]|[10|LECHE U.P. PASCUAL TB 1 LT C/12 PZS|10|160.0|::::13|BOING LATA 340 ML NECTAR C/24 PZS|10|150.0|::::]^";
        $arrRows = explode("^", $str);
        foreach ($arrRows as $lineas) {
            if ($lineas != "") {
                preg_match("/^.*(\[.*\]).*(\[.*\]).*$/", $lineas, $res);
                $strClientes = str_replace(array("[", "]"), "", $res[1]);
                $arrClientes = explode("|", $strClientes);
                $strProductos = str_replace(array("[", "]"), "", $res[2]);
                $arrProductos0 = explode("::::", $strProductos);
                $arrCols = explode("|", $lineas);
                $pedido = $arrCols[0];
                $IVA = $arrCols[6];
                $TOT = $arrCols[7];
                $lat = $arrCols[8];
                $lon = $arrCols[9];
                $SUBT = $arrCols[10];
                $IEPS = $arrCols[11];
                $_items = $arrCols[12];
                $KG = $arrCols[13];
                $idRuta = $arrCols[14];
                $CodigoCliente = $arrCols[15];
                $NombreCliente = $arrCols[16];
                $DireccionCliente = $arrCols[17];
                $Ruta = $arrCols[18];
                $Vendedor = $arrCols[19];
                $NuevaFechaVence = $arrCols[20];
                $Folio = $arrCols[21];
                $NuevaFechaEntrega = $arrCols[22];
                $_email = $arrCols[23];
                $Linea1 = $arrCols[24];
                $Linea2 = $arrCols[25];
                $Linea3 = $arrCols[26];
                $Linea4 = $arrCols[27];
                $CondicionVenta = $arrCols[28];
                $FormaPagoSeleccionadaDescripcion = $arrCols[29];
                $DocSalida = $arrCols[30];
                $conn = sqlsrv_connect($this->ip_server, $this->connectinfo);
                if( $conn === false ) {
                    die( print_r( sqlsrv_errors(), true));
                }
                $sql = "SELECT * FROM Rutas WHERE Ruta='" . $idRuta . "'";
                $result = sqlsrv_query($conn, $sql);
                $_arr = array();
                if ($result) {
                    $row = sqlsrv_fetch_array($result);
                    $this->idRuta = $row['IdRutas'];
                    $this->Ruta = $row["Ruta"];
                    $this->Vendedor = $row["Vendedor"];
                    $this->IdEmpresa = $row["IdEmpresa"];
                }
				sqlsrv_close( $conn);
                $image = "<img width='80' height='80' style='padding:5px;' src='http://".$_SERVER['SERVER_ADDR'].":8080/".basename(__DIR__)."/avatar/".$this->IdEmpresa."/logo_ticket.jpg' />";
                $html .= "<div id='tb-mproducto-grid' class='grid-view' style='padding-top:10px'>";
                $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center' valign='middle'><center>".$image."</center></td>" .
                    "    <td align='left' valign='middle' height='20%' style='font-family: Calibri; font-size: 1em;'><b>Devoluci&oacute;n - (Ticket)</b><br>" .
                    "    <b>".utf8_decode($Linea1)."</b><br>" .
                    "    <b>".utf8_decode($Linea2)."</b><br>" .
                    "    <b>".utf8_decode($Linea3)."</b><br>" .
                    "    <b>".utf8_decode($Linea4)."</b>" .
                    "    </td>" .
                    "  </tr>" .
                    "</table><div style='width:100%; height:.5px; background-color:#ccc; margin-top:2px; margin-bottom:2px; float:left;'></div>";

                $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center' style='font-family: Calibri; font-size: 1em;'><b>Ruta:</b> " . $idRuta . "</td>" .
                    "    <td align='center' style='font-family: Calibri; font-size: 1em;'><b>Cliente:</b> " . $CodigoCliente . "</td>" .
                    "    <td align='center' style='font-family: Calibri; font-size: 1em;'><b>Folio:</b> " . $Folio . "</td>" .
                    "  </tr>
                    </table>";

                $dateString = date("d-m-Y");
                $dateStringH = date("H:i:s");
                $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center'><b>Fecha:</b>" . $dateString . "</td>" .
                    "    <td align='center'><b>Hora:</b>" . $dateStringH . "</td>" .
                    "  </tr>" .
                    "</table>";
				require_once("ForceUTF8/Encoding.php");
                $NombreCliente = Encoding::fixUTF8($NombreCliente);
                $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center'></td>" .
                    "    <td align='center'>" . utf8_decode($NombreCliente) . "</td>" .
                    "    <td align='center'></td>" .
                    "  </tr>" .
                    "  <tr>" .
                    "    <td align='center'></td>" .
                    "    <td align='center'>" . utf8_decode($DireccionCliente) . "</td>" .
                    "    <td align='center'></td>" .
                    "  </tr>" .
                    "</table><div style='width:100%; height:.5px; background-color:#ccc; margin-top:2px; margin-bottom:2px; float:left;'></div>";
                $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center' style='font-family: Calibri; font-size: 1em;'><b>Ticket de Devoluci&oacute;n / ORIGINAL</b></td>" .
                    "  </tr>" .
                    "</table>";

                $html .= "<table style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                    "  <tr>" .
                    "    <td align='center' style='font-family: Calibri; font-size: 1em;'><b>Vendedor:</b> " . $Ruta . " - " . utf8_decode($Vendedor) . "</td>" .
                    "  </tr>" .
                    "</table><div style='width:100%; height:.5px; background-color:#ccc; margin-top:2px; margin-bottom:2px; float:left;'></div>";
                $html .= "<table class='one'>
                            <tr>
                                <th>Fecha de Entrega</th>
                            </tr>
                            <tr>
                                <td>" . $arrCols[4] . "</td>
                            </tr>
                        </table>";
                $html .= "    <table class='one'> " .
                    "        <tr> " .
                    "            <th>Documento</th> " .
                    "        <tr class='odd'> " .
                    "            <td>".$DocSalida."</td> " .
                    "        </tr> " .
                    "    </table> ";
                $html .= "<table class='one'> " .
                    "<tr> " .
                    "<th>SKU</th> " .
                    "<th>Descripci&oacute;n</th> " .
                    "<th>Cant</th> " .
                    "<th>UM</th> " .
                    "<th>Precio</th> " .
                    "<th>Importe</th></tr> ";
                " ";
                $arrPromos = array();
                $arrFotos = array();
                $descuento = 0;
                foreach ($arrProductos0 as $lineProductos) {
                    //writeLog("Numero Pedido: ".$nropedidobb.", Productos: ".$lineProductos, 1);
                    if (!empty($lineProductos)) {
                        $arrColsP = explode("|", $lineProductos);
                        $sku = $arrColsP[0];
                        $nombre = $arrColsP[1];
                        $cantidad = $arrColsP[2];
                        $preciobase = $arrColsP[3];
                        $um = $arrColsP[4];
                        $tp = $arrColsP[5];
                        $desc = $arrColsP[6];
                        $preciodes = $preciobase;
                        $precioimp = $preciobase * ($iva / 100);
                        //$totalIva = $totalIva + $precioimp;
                        $sTotal = floatval($preciobase) * intval($cantidad);
                        //$subtotal = $subtotal + $sTotal;
                        //$_subtotal = $_subtotal + ($cantidad * $preciobase);
                        $preciobase = '$' . number_format($preciobase, 2, '.', ',');
                        $sTotal = '$' . number_format($sTotal, 2, '.', ',');
                        /*if ($tp == "promo") {
                            $arrPromos[] = array("sku" => $sku, "nombre" => $nombre, "cantidad" => $cantidad, "preciobase" => $preciobase, "um" => $um, "Total" => $sTotal);
                            continue;
                        }*/
                        $html .= "<tr> " .
                            "<td>" . $sku . "</td> " .
                            "<td>" . $nombre . "</td> " .
                            "<td align='center'>" . $cantidad . "</td> " .
                            "<td align='center' style='text-align: center;'>" . $um . "</td> " .
                            "<td align='right' style='text-align: right;'>" . $preciobase . "</td> " .
                            "<td align='right' style='text-align: right;'>" . $sTotal . "</td> " .
                            "</tr>";
                        $descuento = $descuento + $desc;
                    }
                    //SQLiteAdapter.__codProdDetalle + "_" +mySQLiteAdapter.numeroDevolucion + "_" + mySQLiteAdapter.idRuta + "_" + mySQLiteAdapter.DiaO + "_" + mySQLiteAdapter.idVendedor + ".jpg"
                    $r = $_SERVER["DOCUMENT_ROOT"]."/".basename(__DIR__)."/imagenes/Devoluciones/".$sku."_".$Folio."_".$this->idRuta."_".$this->DiaO."_".$this->Vendedor.".jpg";
                    if (file_exists($r)) {
						if (!in_array($sku, $arrFotos, true)) {
							$arrFotos[] = array(
								"CodArticulo" => $sku,
								"Descripcion" => $nombre,
								"ruta" => "http://".$_SERVER['SERVER_ADDR'].":8080/".basename(__DIR__)."/imagenes/Devoluciones/".$sku."_".$Folio."_".$this->idRuta."_".$this->DiaO."_".$this->Vendedor.".jpg"
							);
						}
                    }
                }
                $html .= "</table><br>";
            }
        }
        //$TOT = $TOT - $descuento;
        $descuento = number_format($descuento, 2, '.', ',');
        $html .= "<table class='one' style='font-family: Calibri; font-size: 1em;width: 100%'>";
        $html .= "<tr><td align='right'  style='text-align: right;' width='80%'><b>Lt/Kg.:</b></td><td align='right' width='20%' style='text-align: right;'>" . $KG . "</td></tr>";
        $html .= "<tr><td align='right' style='text-align: right;'><b>Productos.:</b></td><td align='right' style='text-align: right;'>" . $_items . "</td></tr>";
        $html .= "<tr><td align='right' style='text-align: right;'><b>Sub Total:</b></td><td align='right' style='text-align: right;'>\$" . number_format($SUBT, 2, '.', ',') . "</td></tr>";
		$html .= "<tr><td align='right' style='text-align: right;'><b>IVA.:</b></td><td align='right' style='text-align: right;'>\$" . number_format($IVA, 2, '.', ',') . "</td></tr>";
        $html .= "<tr><td align='right' style='text-align: right;'><b>IEPS.:</b></td><td align='right' style='text-align: right;'>\$" . number_format($IEPS, 2, '.', ',') ."</td></tr>";        
        $html .= "<tr><td align='right' style='text-align: right;'><b>Descuento:</b></td><td align='right' style='text-align: right;'>\$".$descuento."</td></tr>";
        $html .= "<tr><td align='right' style='text-align: right;'><b>Total Pedido.:</b></td><td align='right' style='text-align: right;'>\$". number_format($TOT, 2, '.', ',') ."</td></tr>";
        $html .= "</table>";
		if (!empty($arrFotos)) {
            $html .= "<br><table class='one' style='font-family: Calibri; font-size: 1em;width: 100%'>" .
                "  <tr>" .
                "    <td align='center'><b>Im&aacute;genes Productos en Mal Estado</b></td>" .
                "  </tr>" .
                "</table>" .
                "<div style='width:100%; height:.5px; background-color:#ccc; margin-top:2px; margin-bottom:2px; float:left;'></div>" .
                "<div style='width:100%; height:.5px; background-color:#ccc; margin-top:2px; margin-bottom:2px; float:left;'></div>";
			$items[] = array();
            foreach ($arrFotos as $a) {
				if (in_array($a["CodArticulo"], $items)) continue;
				$items[] = $a["CodArticulo"];
                $html .= "<table class='items'>" .
                    "<tr class='one'> " .
                    "<td align='center'>" . $a["CodArticulo"] . " - " . $a["Descripcion"] .
                    "<br>" .
                    "<img width='250' height='150' src='".$a["ruta"]."' /></td> " .
                    "</tr></table>";
            }
            $html .= "<br>";
        }
        $html .= "<center><img width='100%' src='http://maps.googleapis.com/maps/api/staticmap?center=".$lat.",".$lon."&zoom=13&scale=2&size=600x300&maptype=roadmap&format=png&visual_refresh=true&markers=size:mid%7Ccolor:0xff0000%7Clabel:%7C".$lat.",".$lon."'></center>";
        $html .= "</body></html>";
        //$email_address = "luisfraino@gmail.com";
        include("class.phpmailer.php");
        include("class.smtp.php");
        $mail = new PHPMailer();
        $mail->isSMTP();                                   // send via SMTP
        $mail->Host     = "raz2.dnsprivado.net"; // SMTP servers
        $mail->SMTPAuth = true;     // turn on SMTP authentication
        $mail->Username = "assistpro@adventech-logistica.com";  // SMTP username
        $mail->Password = "wMo_5auGpl"; // SMTP password
        $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 465;
        $mail->setFrom('assistpro@adventech-logistica.com', 'Devolucion N: '.$Folio);
        $mail->addAddress($email_address);
        $mail->addAddress("aolivares242@gmail.com", ' ');               // optional name, $firstname." ".$lastname
        $mail->addAddress("aolivares@adventech-logistica.com", ' ');
        $mail->addAddress($_email, ' ');
        //$mail->AddReplyTo("info@site.com","Information");
        $mail->WordWrap = 50;                              // set word wrap
        //$mail->AddAttachment("/var/tmp/file.tar.gz");      // attachment
        //$mail->AddAttachment("/tmp/image.jpg", "new.jpg");
        $mail->isHTML(true);                               // send as HTML
        //$mail->Subject  =  "Devolucion N: ".$pedido;
        //$mail->Body     =  $html;
        $mail->AltBody  =  "This is the text-only body";
        $mail->Subject  =  "Devolucion N: ".$Folio;
        $mail->msgHTML($html, __DIR__);
        $mail->send();
        $subject = "Devolucion N: $pedido";
        //$this->sendMail_by_SendGrid($_email, $subject, $html);
        return "Devolucion N: $pedido";
    }
	
	function Sustituto_Cadena($rb){ 
        ## Sustituyo caracteres en la cadena final
        $rb = str_replace("ÃƒÂ¡", "&aacute;", $rb);
        $rb = str_replace("ÃƒÂ©", "&eacute;", $rb);
        $rb = str_replace("Ã‚Â®", "&reg;", $rb);
        $rb = str_replace("ÃƒÂ­", "&iacute;", $rb);
        $rb = str_replace("Ã¯Â¿Â½", "&iacute;", $rb);
        $rb = str_replace("ÃƒÂ³", "&oacute;", $rb);
        $rb = str_replace("ÃƒÂº", "&uacute;", $rb);
        $rb = str_replace("n~", "&ntilde;", $rb);
        $rb = str_replace("Ã‚Âº", "&ordm;", $rb);
        $rb = str_replace("Ã‚Âª", "&ordf;", $rb);
        $rb = str_replace("ÃƒÆ’Ã‚Â¡", "&aacute;", $rb);
        $rb = str_replace("ÃƒÂ±", "&ntilde;", $rb);
        $rb = str_replace("Ãƒâ€˜", "&Ntilde;", $rb);
        $rb = str_replace("ÃƒÆ’Ã‚Â±", "&ntilde;", $rb);
        $rb = str_replace("n~", "&ntilde;", $rb);
        $rb = str_replace("ÃƒÅ¡", "&Uacute;", $rb);
        return $rb;
    } 
}
include '../config.php';
$json = file_get_contents('php://input');
$obj = json_decode($json);
if (!empty($obj)) {
    error_reporting(0);
    $t = new Sql2JSON();
    $t->ip_server = DB_HOST;
    $t->db = DB_NAME;
    $t->user = DB_USER;
    $t->password = DB_PASSWORD;
    $t->connectinfo = array("Database"=>DB_NAME, "UID"=>DB_USER, "PWD"=>DB_PASSWORD, "CharacterSet"=>"UTF-8", "ReturnDatesAsStrings" => true, 'LoginTimeout'=>60);
	$t->arrTbl = array();
    switch ($obj->{'function'}) {
        case "strPedidos2sql":
            $function = $obj->{'function'};
            $str = $function = $obj->{'str'};
            $ret = $t->strPedidos2sql($str);
            echo json_encode($ret);
            exit();
            break;
		case "strSendDevol":
		    if (!empty($obj->{'idRuta'})) $t->idRuta = $obj->{'idRuta'};
            if (!empty($obj->{'DiaO'})) $t->DiaO = $obj->{'DiaO'};
            if (!empty($obj->{'idVendedor'})) $t->Vendedor = $obj->{'idVendedor'};
            $function = $obj->{'function'};
            $str = $function = $obj->{'str'};
            $ret = $t->strSendDevol($str);
            echo json_encode($ret);
            exit();
            break;
        case "strVentas2sql":
            $function = $obj->{'function'};
            $str = $function = $obj->{'str'};
            $ret = $t->strVentas2sql($str);
            echo json_encode($ret);
            exit();
            break;
        case "strDevolucionEnvases2sql":
            $function = $obj->{'function'};
            $str = $function = $obj->{'str'};
            $ret = $t->strDevolucionEnvases2sql($str);
            echo json_encode($ret);
            exit();
            break;
        case "strDevolucion2sql":
            $function = $obj->{'function'};
            $str = $function = $obj->{'str'};
            $ret = $t->strDevolucion2sql($str);
            echo json_encode($ret);
            exit();
            break;
        case "strAvisoVisita":
            $function = $obj->{'function'};
            $str = $obj->{'str'};
            $ret = $t->strAvisoVisita($str);
            echo json_encode($ret);
            exit();
            break;
        case "Sql2JSONActualizaIdFCM":
            $function = $obj->{'function'};
			$t->Vendedor = $obj->{'IdVendedor'};
			$t->arrTbl = (array)$obj;
            $ret = $t->$function();
			header('Content-type: application/json; Charset=UTF-8'); //<---- Se agrega Charset para caracteres especiales
            echo json_encode($ret);
            exit();
            break;
        case "Sql2JSONActualizaUbicacionVehiculo":
            $function = $obj->{'function'};
			$t->Vendedor = $obj->{'IdVendedor'};
			$t->arrTbl = array();
			$t->arrTbl = (array)$obj;
            $ret = $t->$function();
			header('Content-type: application/json; Charset=UTF-8'); //<---- Se agrega Charset para caracteres especiales
            echo json_encode($ret);
            exit();
            break;
    }
    $conn =  new mysqli($t->ip_server, $t->user, $t->password, $t->db);
    if( $conn->connect_errno ) {
		$_arr[] = array("Error" => "Fallo al conectarse a MySQL",
						"Errno" => $mysqli->connect_errno,
						"Error" => $mysqli->connect_error);
        header('Content-type: application/json');
        echo json_encode($_arr);
        exit();
    }
    if (isset($obj->{'idCli'})) $t->idCliente = $obj->{'idCli'};
    if (isset($obj->{'idClis'})) $t->idsClientes = explode("|", $obj->{'idClis'});
    if (isset($obj->{'strCodVend'})) $t->strCodVend = explode("|", $obj->{'strCodVend'});
    $function = $obj->{'function'};
    $sql = "Select Distinct Z.ID_Ruta,Z.Cve_Ruta,V.Id_Vendedor,A.Clave From t_ruta Z Join Rel_Ruta_Agentes R On Z.Id_Ruta=R.Cve_Ruta Join t_vendedores V On V.Id_Vendedor=R.Cve_Vendedor Join c_almacenp A On Z.cve_almacenp=A.Id WHERE Z.cve_Ruta='" . $obj->{'ruta'} . "';";
    $result = $conn->query($sql);
    if( $result === false || $result === NULL) {
        $_arr[] = array("ERROR" => "No Existe la Ruta");
        header('Content-type: application/json; Charset=UTF-8');
        echo json_encode($_arr);
        exit();
    }
    if(!$result) {
        $_arr[] = array("ERROR" => "No Existe la Ruta");
        header('Content-type: application/json; Charset=UTF-8');
        echo json_encode($_arr);
        exit();
    }
    if($result->{'num_rows'} === 0) {
        $_arr[] = array("ERROR" => "No Existe la Ruta o el vendedor");
        header('Content-type: application/json; Charset=UTF-8');
        echo json_encode($_arr);
        exit();
    }
    $_arr = array();
    if ($row = $result->fetch_array(MYSQLI_NUM)) {
        $t->idRuta = $row[0];
        $t->Ruta = $row[1];
        $t->Vendedor = $row[2];
        $t->IdEmpresa = $row[3];
        $ret = $t->$function();
    }
	$conn->close();
    header('Content-type: application/json; Charset=UTF-8'); //<---- Se agrega Charset para caracteres especiales
    echo json_encode($ret);
}