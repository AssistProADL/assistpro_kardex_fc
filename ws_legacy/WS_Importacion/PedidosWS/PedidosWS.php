<?php

namespace PedidosWS;

class PedidosWS {

    const TABLE = 'th_pedido';
    const TABLE_D = 'td_pedido';
    const TABLE_C = 'c_cliente';
    // var $identifier;

    public function __construct( $Fol_folio = false, $key = false )
	{
        if( $Fol_folio )
		{
            $this->Fol_folio = (int) $Fol_folio;
        }
        if($key)
		{
            $sql = sprintf(' SELECT Fol_folio FROM %s WHERE Fol_folio = ?',self::TABLE);
            $sth = \db()->prepare($sql);
            $sth->setFetchMode(\PDO::FETCH_CLASS, '\PedidosWS\PedidosWS');
            $sth->execute(array($key));
            $Fol_folio = $sth->fetch();
            $this->Fol_folio = $Fol_folio->Fol_folio;
        }
    }

    private function load() {
        $sql = sprintf("SELECT * FROM %s WHERE Fol_folio = ? And Status Not In ('I','A')",self::TABLE);
        $sth = \db()->prepare( $sql );
        $sth->setFetchMode( \PDO::FETCH_CLASS, '\PedidosWS\PedidosWS' );
        $sth->execute( array( $this->Fol_folio ) );
        $this->data = $sth->fetch();
    }

    private function loadStatus() {
        $sql = sprintf("SELECT '".$this->cve_almac."' Cve_Almac,Fol_folio,Observaciones FROM %s WHERE Status In ('C','E','F','T') And Fol_folio = ?",self::TABLE);
        $sth = \db()->prepare( $sql );
        $sth->setFetchMode( \PDO::FETCH_CLASS, '\PedidosWS\PedidosWS' );
        $sth->execute( array( $this->Fol_folio ) );
        $this->data = $sth->fetch();
    }

    private function loadStatusMov() {
		if($this->TipoMov==1)
		{
			$sql = "Select 'true' success,IfNull(A.Factura,A.Pedimento) Referencia,'E' TipoMov From t_cardex X Join th_entalmacen E On X.Origen=E.Fol_Folio And X.Id_TipoMovimiento=1 Join th_aduana A On E.Id_OCompra=A.Num_Pedimento Where A.Factura='".$this->Referencia."' And A.Cve_Almac='".$this->cve_almac."' Limit 1;";
		}
		else
		{
			$sql = "Select 'true' success,E.Fol_Folio Referencia,'S' TipoMov From t_cardex X Join th_pedido E On X.Destino=E.Fol_Folio And X.Id_TipoMovimiento=8 Join c_almacenp A On A.Id=E.Cve_Almac Where E.Fol_Folio='".$this->Referencia."' And A.Clave='".$this->cve_almac."' Limit 1;";
		}
        $sth = \db()->prepare( $sql );
        $sth->setFetchMode( \PDO::FETCH_CLASS, '\PedidosWS\PedidosWS' );
        $sth->execute( array( $this->Referencia ) );
        $this->data = $sth->fetch();
    }

    private function loadChangeStatus()
	{
        $sql = sprintf('SELECT status FROM %s WHERE ID_Pedido = ?',self::TABLE);
        $sth = \db()->prepare( $sql );
        $sth->setFetchMode( \PDO::FETCH_CLASS, '\PedidosWS\PedidosWS' );
        $sth->execute( array( $this->ID_Pedido ) );
        $this->data = $sth->fetch();
    }

    function getStatus()
	{
        $sql = 'SELECT * FROM cat_estados';
        $sth = \db()->prepare( $sql );
        $sth->setFetchMode( \PDO::FETCH_CLASS, '\PedidosWS\PedidosWS' );
        $sth->execute( array( ESTADO ) );
        return $sth->fetchAll();
    }

    function getAll()
	{
        $sql = 'SELECT * FROM ' . self::TABLE . ';';
        $sth = \db()->prepare( $sql );
        $sth->setFetchMode( \PDO::FETCH_CLASS, '\PedidosWS\PedidosWS' );
        $sth->execute( array( Fol_folio ) );
        return $sth->fetchAll();
    }
	
    //Pedidos disponibles para embarque
    function getAllForShipment($page = 0, $limit = 0)
	{
        $sql = "SELECT th_pedido.Fol_folio AS folio, IFNULL(c_cliente.RazonSocial, '') as cliente, IFNULL(td_pedido.SurtidoXCajas, '0') as cajas, IFNULL(td_pedido.SurtidoXPiezas, '0') as piezas FROM th_pedido LEFT JOIN c_cliente ON c_cliente.Cve_Clte = th_pedido.Cve_clte LEFT JOIN td_pedido ON td_pedido.Fol_folio = th_pedido.Fol_folio WHERE th_pedido.status = 'C'";
        if($limit > 0 || $page > 0){
            $sql .= " LIMIT $page, $limit";
        }
        $sth = \db()->prepare( $sql );
        $sth->execute();
        return $sth->fetchAll(\PDO::FETCH_CLASS);
    }

    private function loadDetalle()
	{
        $sql = "SELECT td_pedido.Fol_folio,td_pedido.Cve_articulo,td_pedido.Num_cantidad,c_articulo.des_articulo " .
				"FROM td_pedido INNER JOIN c_articulo ON td_pedido.Cve_articulo = c_articulo.cve_articulo " .
				"WHERE td_pedido.Fol_folio = '".$this->Fol_folio."'";
        $rs = mysqli_query(\db2(), $sql) or die("Error description: " . mysqli_error(\db2()));
        $arr = array();
        while ($row = mysqli_fetch_array($rs))
		{
            $arr[] = array( "Fol_folio" => $row["Fol_folio"],
							"Cve_articulo" => $row["Cve_articulo"],
							"Num_cantidad" => $row["Num_cantidad"],
							"des_articulo" => $row["des_articulo"]);
        }
        $this->dataDetalle = $arr;
    }

    private function loadDetalleMov()
	{
		if($this->TipoMov==1)
		{
			$sql = "Select IfNull(A.Factura,A.Pedimento) Referencia,X.Cve_articulo,X.Cve_Lote Lote,SUM(X.Cantidad) Num_Cantidad From t_cardex X Join th_entalmacen E On X.Origen=E.Fol_Folio And X.Id_TipoMovimiento=1 Join th_aduana A On E.Id_OCompra=A.Num_Pedimento Where A.Factura='".$this->Referencia."' And A.Cve_Almac='".$this->cve_almac."' Group By A.Factura,A.Pedimento,A.Cve_Almac,X.Cve_Articulo,X.Cve_Lote;";
		}
		else
		{
			$sql = "Select E.Fol_Folio as Referencia,X.Cve_articulo,X.Cve_Lote Lote,SUM(X.Cantidad) Num_Cantidad From t_cardex X Join th_pedido E On X.Destino=E.Fol_Folio And X.Id_TipoMovimiento=8 Join c_almacenp A On A.Id=E.Cve_Almac Where E.Fol_Folio='".$this->Referencia."' And A.Clave='".$this->cve_almac."' Group By E.Fol_Folio,A.Clave,X.Cve_Articulo,X.Cve_Lote;";
		}
        $rs = mysqli_query(\db2(), $sql) or die("Error description: " . mysqli_error(\db2()));
        $arr = array();
        while ($row = mysqli_fetch_array($rs))
		{
            $arr[] = array( "Referencia" => $row["Referencia"],
							"Cve_articulo" => $row["Cve_articulo"],
							"Lote" => $row["Lote"],
							"Num_Cantidad" => $row["Num_Cantidad"]);
        }
        $this->dataDetalle = $arr;
    }

    function __get( $key ) {
        switch($key)
		{
            case 'Fol_folio':
                $this->load();
                return @$this->data->$key;
            default:
                return $this->key;
        }
    }

    function __getStatus( $key ) {
        switch($key)
		{
            case 'Fol_folio':
                $this->loadStatus();
                return @$this->data->$key;
            case 'Referencia':
                $this->loadStatusMov();
                return @$this->data->$key;
            default:
                return $this->key;
        }
    }

    function __getChangeStatus( $key )
	{
        switch($key)
		{
            case 'ID_Pedido':
                $this->loadChangeStatus();
                return @$this->data->$key;
            default:
                return $this->key;
        }
    }

    function __getDetalle( $key )
	{
        switch($key)
		{
            case 'Fol_folio':
                $this->loadDetalle();
                return @$this->dataDetalle->$key;
            default:
                return $this->key;
        }
    }

    function __getDetalleMov( $key )
	{
        switch($key)
		{
            case 'Referencia':
                $this->loadDetalleMov();
                return @$this->dataDetalle->$key;
            default:
                return $this->key;
        }
    }

    function save( $_post )
	{
		try
		{
            $Fol_folio=" ";
			if($_post['Fol_folio']) $Fol_folio = $_post['Fol_folio'];
            $Fec_Pedido="0000-00-00 00:00:00";
			if($_post['Fec_Pedido']) $Fec_Pedido = $_post['Fec_Pedido'];
            $Fec_Entrega="0000-00-00 00:00:00";
			if($_post["Fec_Entrega"]) $Fec_Entrega = $_post["Fec_Entrega"];
			date_default_timezone_set('America/Mexico_City');
            $Fec_Entrada=date("Y-m-d h:i:s");
			/*
            $Fec_Entrada="0000-00-00 00:00:00";
			if($_post["Fec_Entrada"]) $Fec_Entrada = $_post["Fec_Entrada"];*/
			$Cve_clte = $_post['Cve_clte'];
			if(!isset($_post['Cve_cteProv']))
			{
				$Cve_CteProv=$Cve_clte;
			}
			else
			{
				if($_post['Cve_cteProv']=='')
				{
					$Cve_CteProv=$Cve_clte;
				}
				else
				{
					$Cve_CteProv=$_post['Cve_cteProv'];
				}
			}
			$Bloqueado=0;
			if(!isset($_post['Bloqueado']))
			{
				$Bloqueado=0;
			}
			else
			{
				if(is_null($_post['Bloqueado']))
				{
					$Bloqueado=0;
				}
				else
				{
					$Bloqueado=$_post['Bloqueado'];
				}
			}
			$Fecha_Aprov=$Fec_Entrada;
			if(!isset($_post['Fec_Aprobacion']))
			{
				$Fecha_Aprov=$Fec_Entrada;
			}
			else
			{
				if(is_null($_post['Bloqueado']))
				{
					$Fecha_Aprov=$Fec_Entrada;
				}
				else
				{
					$Fecha_Aprov=$_post['Fec_Aprobacion'];
				}
			}
			$Tot_Fac=0;
			if(!isset($_post['Tot_Factura']))
			{
				$Tot_Fac=0;
			}
			else
			{
				if(is_null($_post['Tot_Factura']))
				{
					$Tot_Fac=0;
				}
				else
				{
					$Tot_Fac=$_post['Tot_Factura'];
				}
			}			
            $Almac_Ori=" ";
			if($_post['Almac_Ori']) $Almac_Ori = $_post['Almac_Ori'];
            $Docto_Ref=" ";
			if($_post['Docto_Ref']) $Docto_Ref = $_post['Docto_Ref'];
            $cve_Vendedor=" ";
			if($_post['cve_Vendedor']) $cve_Vendedor = $_post['cve_Vendedor'];
            $Pick_Num=" ";
			if($_post['Pick_Num']) $Pick_Num = $_post['Pick_Num'];
            $user=" ";
			if($_post['user']) $user = $_post['user'];
            $Observaciones=" ";
			if($_post['Observaciones']) $Observaciones = $_post['Observaciones'];
            $ID_Tipoprioridad=1;
			if($_post['ID_Tipoprioridad']) $ID_Tipoprioridad = $_post['ID_Tipoprioridad'];
            $cve_almac=" ";
			if($_post['cve_almac']) $cve_almac = $_post['cve_almac'];
            $Sku=" ";
			if($_post['Sku']) $Sku = $_post['Sku'];
			if($_post['DOSERIAL']) $Docto_Ref = $_post['DOSERIAL'];
			$sql = "SELECT id FROM c_almacenp WHERE clave = '".$cve_almac."';";
			$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
			$result = $conn->query($sql);
			$row = $result->fetch_array(MYSQLI_NUM);
			if(is_array($row))
			{
				$Almacen=$row[0];
			}
			$conn->close();			
            $sql = "Call SPWS_InsertaCabeceraPedido(";
            $sql .= "'".$Fol_folio."',";
            $sql .= "'".$Fec_Pedido."',";
            $sql .= "'".$Fec_Entrega."',";
            $sql .= "'".$Fec_Entrada."',";
            $sql .= "'".$Cve_clte."',";
            $sql .= "'".$cve_Vendedor."',";
            $sql .= "'".$Pick_Num."',";
            $sql .= "'".$user."',";
            $sql .= "'".$Observaciones."',";
            $sql .= "'".$ID_Tipoprioridad."',";
            $sql .= "'" .$cve_almac."',";
            $sql .= "'".$Cve_CteProv."',";
            $sql .= "'".$Almac_Ori."',";
            $sql .= "'".$Docto_Ref."');";
            mysqli_set_charset(\db2(), 'utf8');
            $rs = mysqli_query(\db2(), $sql) or die("Error description: " . mysqli_error(\db2()));
            $i = 1;
            if (!empty($_post["arrDetalle"])) {
                $sql = "DELETE FROM td_pedido WHERE Fol_folio = '".$_post['Fol_folio']."'";
                $rs = mysqli_query(\db2(), $sql) or die("Error description: " . mysqli_error(\db2()));
                $detalles = [];
                foreach ($_post["arrDetalle"] as $item)
                {
                    $codigo = $item['codigo'];
					$Item_Num = "1";
					$CveCot="";
					if(isset($item['Item']) And isset($item['CPROWNUM'])){
						if($item['Item']) $Item_Num = $item['Item'];
						if($item['CPROWNUM']) $CveCot = $item['CPROWNUM'];
					}
					else{
						if(isset($item['Item'])) $Item_Num = $item['Item'];
						if(isset($item['CPROWNUM'])) $Item_Num = $item['CPROWNUM'];
						if($item['DOSERIAL']) $CveCot = $item['DOSERIAL'];
					}
					$UniMed="";
					if($item['unidadMedida']) $UniMed = $item['unidadMedida'];
					$FecEntrega=$Fec_Entrega;
					if($item['Fec_Entrega']) $FecEntrega = $item['Fec_Entrega'];
					if(!isset($item['Precio'])){
						$Precio=0;
						$BanPromo=0;
					}
					else{
						$Precio=$item['Precio'];
						$BanPromo=1;
					}
					$folio = $_post['Fol_folio'];
					$sql = "Call SPWS_InsertaDetallePedidoWS "
							. "('" . $folio . "','"
							. $codigo . "',"
							. $item['CantPiezas'] . ","
							. $Item_Num . ",'"
							. $item['cve_lote'] . "','"
							. $FecEntrega . "','"
							. $UniMed . "','"
							. $CveCot . "',"
							. $Precio . ","
							. $BanPromo . ");";
					$rs = mysqli_query(\db2(), $sql) or die("Error description: " . mysqli_error(\db2()));
					$i++;
                }
            }
			$sql="Call SPWS_PreparaPedidoPTL('"
											. $cve_almac . "','"
											. $Fol_folio . "');";
            $rs = mysqli_query(\db2(), $sql) or die("Error description: " . mysqli_error(\db2()));
            // Por la API
            if (!empty($_post["destinatarios"])) {
                foreach ($_post["destinatarios"] as $key => $item) 
                {
                    $item = (array) $item;
					$sql = "Call SPWS_CreaDestinatarioPedido('"
						. $folio . "','"
						. $Cve_clte . "','"
						. $Cve_CteProv . "','"
						. $item['razonsocial'] . "','"
						. $item['direccion'] . "','"
						. $item['colonia'] . "','"
						. $item['postal'] . "','"
						. $item['ciudad'] . "','"
						. $item['estado'] . "','"
						. $item['contacto'] . "','"
						. $item['telefono'] . "','"
						. $_post['cve_almac'] . "');";
					//$rs = mysqli_query(\db2(), $sql) or die("Error description: " . mysqli_error(\db2()));
					$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
					$result = $conn->query($sql);
                }
                return true;
            }
			else{
				$sql = "Select P.Fol_Folio,D.Id_Destinatario " 
						. "From th_pedido P Join c_destinatarios D On P.Cve_clte=D.Cve_Clte And P.Cve_CteProv=D.clave_destinatario "
						. "Where P.Fol_Folio='".$_post['Fol_folio']."';";
				$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
				$result = $conn->query($sql);
				$row = $result->fetch_array(MYSQLI_NUM);
                if(is_array($row))
				{
					$sql = "INSERT INTO Rel_PedidoDest(Fol_Folio,Cve_Almac,Id_Destinatario) "
							. "VALUES('" . $row[0] . "',1," . $row[1] . ");";
					$conn->close();
					$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
					if(!$result = $conn->query($sql))
					{
						echo $conn->error;
					}
				}
                return true;
			}
             // Por la WEB
            if (!empty($_post["destinatario"])) {
                $destinatario = $_post["destinatario"];
                $sql = "INSERT INTO `Rel_PedidoDest`(
                            `Fol_Folio`, `Cve_Almac`, `Id_Destinatario`
                         ) VALUES (
                             '".$_post['Fol_folio']."',
                             '".$_post['cve_almac']."',
                             '".$destinatario."'
                        );";
                $rs = mysqli_query(\db2(), $sql) or die("Error description: " . mysqli_error(\db2()));
            }
		} catch(Exception $e) {
            return 'ERROR: ' . $e->getMessage();
        }
    }

    function save_Factura( $_post )
	{
		try
		{
			if(!isset($_post['OrdenCompra']))
			{
				$OrdenCompra="";
			}
			else
			{
				$OrdenCompra=$_post['OrdenCompra'];
			}
			$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $sql = "Call SPWS_CreaCabeceroFactura('"
             . $_post['Cve_Almacen'] . "',"
             . $_post['Id_Fac'] . ",'"
             . $_post['Folio_Fac'] . "','"
             . $_post['Tipo_Doc'] . "','"
             . $_post['FechaEmision'] . "','"
             . $_post['Cve_Clte'] . "','"
             . $_post['OrdenCompra'] . "');";
			if(!$result = $conn->query($sql))
			{
				$conn->close();
				return 'ERROR: ' . 'NO SE EJECUTO EL SP SPWS_CreaCabeceroFactura '.$conn->error;
			}
			while ($row = $result->fetch_array(MYSQLI_NUM)) {
				if($row[0]==-1){
					$_arr = array("ERROR" => $row[0],
									"MSG" => utf8_encode($row[1]),
									"Folio" => $row[2]);
					return 'Error: '.utf8_encode($row[1]);
				}
				else{
					$FolioFac=$row[2];
				}
			}
			$conn->close();
            $i = 1;
            if (!empty($_post["arrDetalle"])) {
                $detalles = [];
				$rs->mysqli_free_result;
                foreach ($_post["arrDetalle"] as $detalles)
                {
					$detalles = (array) $detalles;
                    $codigo = $detalles['Codigo'];
					$Lote="";
					if(isset($detalles['Lote'])){
						$Lote=$detalles['Lote'];
					}
					$Item_Num = "1";
					if($detalles['Item']) $Item_Num = $detalles['Item'];
					if($detalles['CPRowNum']) $CPRow = $detalles['CPRowNum'];
					$folio = $detalles['Fol_Folio'];
					$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
					$sql = "Call SPWS_CreaDetalleFactura "
							. "(" . $_post['Id_Fac'] . ",'"
							. $folio . "','"
							. $detalles['Referencia'] . "','"
							. $codigo . "','"
							. $lote . "',"
							. $Item_Num . ",'"
							. $CPRow . "',"
							. $detalles['Cantidad'] . ");";
					if(!$result = $conn->query($sql))
					{
						$conn->close();
						return 'ERROR: ' . 'NO SE EJECUTO EL SP SPWS_CreaCabeceroFactura '.$conn->error;
					}
					//$rs = mysqli_query(\db2(), $sql) or die("Error description: " . mysqli_error(\db2()));
					$conn->close();
					$i++;
                }
            }
			return true;
		} catch(Exception $e) {
            return 'ERROR: ' . $e->getMessage();
        }
    }

    function borrarPedido( $data ) {
		try {
			$ItemN="0";
			if($data['CPROWNUM']) $ItemN=$data['CPROWNUM'];
			$sql = "Call SPWS_CancelaPedidoWS('". $data['Fol_folio'] . "','" . $ItemN . "');";
			$rs = mysqli_query(\db2(), $sql) or die("Error description: " . mysqli_error(\db2()));
			return true;
		} catch(Exception $e) {
            return 'ERROR: ' . $e->getMessage();
        }
    }

    function actualizarStatus( $_post ) {
        try {
            $sql = "UPDATE " . self::TABLE . " SET status='".$_post['status']."' WHERE ID_Pedido='".$_post['ID_Pedido']."'";
            $rs = mysqli_query(\db2(), $sql) or die("Error description: " . mysqli_error(\db2()));

        } catch(Exception $e) {
            return 'ERROR: ' . $e->getMessage();
        }
    }

    function TerminaCorte() {
        try {
            $sql = "UPDATE TH_Pedido_PTL SET status='P' WHERE Status='I'";
            $rs = mysqli_query(\db2(), $sql) or die("Error description: " . mysqli_error(\db2()));

        } catch(Exception $e) {
            return 'ERROR: ' . $e->getMessage();
        }
    }
    
    function actualizarPedido( $_post ) {
        try {
            $sql = "Delete From " . self::TABLE . " WHERE Fol_folio = '".$_post['Fol_folio']."'";
            $rs = mysqli_query(\db2(), $sql) or die("Error description: " . mysqli_error(\db2()));
            $sql = "INSERT IGNORE INTO " . self::TABLE . " (Fol_folio, Cve_clte, cve_Vendedor, Fec_Entrada, Pick_Num, Observaciones, cve_almac, Activo)";
            $sql .= "Values (";
            $sql .= "'".$_post['Fol_folio']."',";
            $sql .= "'".$_post['Cve_clte']."',";
            $sql .= "'".$_post['cve_Vendedor']."',";
            $sql .= "now(),";
            $sql .= "'".$_post['Pick_Num']."',";
            $sql .= "'".$_post['Observaciones']."',";
            $sql .= "'".$_post['cve_almac']."', '1');";
            $rs = mysqli_query(\db2(), $sql) or die("Error description: " . mysqli_error(\db2()));
            if (!empty($_post["arrDetalle"])) {
                $sql = "Delete From td_pedido WHERE Fol_folio = '".$_post['Fol_folio']."'";
                $rs = mysqli_query(\db2(), $sql) or die("Error description: " . mysqli_error(\db2()));
                foreach ($_post["arrDetalle"] as $item) {
                    $sql = "INSERT INTO td_pedido (Cve_articulo, Num_cantidad, Fol_folio, Activo) Values ";
                    $sql .= "('".$item['codigo']."', '".$item['CantPiezas']."', '".$_post['Fol_folio']."', '1');";
                    $rs = mysqli_query(\db2(), $sql) or die("Error description: " . mysqli_error(\db2()));
                }
            }
        } catch(Exception $e) {
            return 'ERROR: ' . $e->getMessage();
        }
    }

    function get( $_post ) {
        try {
			$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
			if(isset($_post['status'])) {
				if($_post['status']=="abierta")
				{
					$Estatus='C';
				}
				else
				{
					$Estatus='T';
				}
			}
			else
			{
				$Estatus='C';
			}
            $sql = "Call SPAD_DameDatosPedidoWX('"
				. $_post['fecha_inicio'] . "','"
				. $_post['fecha_fin'] . "');";
			if(!$result = $conn->query($sql))
			{
				$_narr = array("succes" => false,
							"err" => utf8_encode($conn->error));
				$conn->close();
				return $_narr;
			}
			else
			{
				while ($row = $result->fetch_array(MYSQLI_NUM)) {
					if($row[0]==-1)
					{
						$_narr = array("succes" => false,
										"err" => utf8_encode($row[1]));
						$conn->close();
						return $_narr;
					}
					else
					{
						$_arr[] = array("ReferenciaWelldex" => $row[1],
									"NumeroPartida" => $row[2],
									"ConsecutivoFactura" => utf8_encode($row[3]),
									"Factura" => $row[4],
									"Parte" => $row[5],
									"Fraccion" => $row[6],
									"Subdivision" => $row[7],
									"DescripcionAA" => $row[8],
									"paisOrigenDestino" => $row[9],
									"PaisVendedorComprador" => utf8_encode($row[10]),
									"AplicaTL" => utf8_encode($row[11]),
									"Peso" => $row[12],
									"CantFactura" => $row[13],
									"UMComercial" => $row[14],
									"CantTarifa" => $row[15],
									"UMTarifa" => $row[16],
									"UMF" => $row[17],
									"CantUMF" => $row[18],
									"UM" => $row[19],
									"ES" => $row[20],
									"PrecioUnitario" => $row[21],
									"ValorFactura" => $row[22],
									"Vinculacion" => $row[23],
									"MetodoValoracion" => $row[24],
									"Agrupar" => $row[25],
									"ObservacionesPedimento" => $row[26],
									"UMCOVE" => $row[27],
									"CantidadCOVE" => $row[28],
									"ValorMercanciaCOVE" => $row[29],
									"PrecioUnitarioCOVE" => $row[30],
									"DescripcionFacturaCOVE" => $row[31],
									"MarcaProducto" => $row[32],
									"ModeloProducto" => $row[33],
									"ValorAgregado" => $row[34],
									"TipoFraccionAmericana" => $row[35],
									"FraccionAmericana" => $row[36],
									"DescripcionFraccionAmericana" => $row[37],
									"ProductoServicio" => $row[38],
									"UMCFDI" => $row[39],
									"UMEmbalaje" => $row[40],
									"Elimina" => $row[41]);
					}
				}
				$conn->close();
            }
			return $_arr;
        }
		catch(Exception $e) {
			$_narr = array("succes" => false,
							"err" => $e->getMessage());
            return $_narr;
        }
    }

    function getSalida( $_post ) {
        try {
			$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
			if(!isset($_post['status'])) {
				$Estatus='';
			}
			else {
				$Estatus=$_post['status'];
			}
            $sql = "Call SPAD_DameDatosSalidaWX('"
				. $_post['Almacen'] . "','"
				. $_post['fecha_inicio'] . "','"
				. $_post['fecha_fin'] . "','"
				. $Estatus . "');";
			if(!$result = $conn->query($sql))
			{
				$_narr = array("succes" => false,
							"err" => utf8_encode($conn->error));
				$conn->close();
				return $_narr;
			}
			else
			{
				while ($row = $result->fetch_array(MYSQLI_NUM)) {
					if($row[0]==-1)
					{
						$_narr = array("succes" => false,
										"err" => utf8_encode($row[1]));
						$conn->close();
						return $_narr;
					}
					else
					{
						$_arr[] = array("succes" => true,
									"DescriptionMercancia" => utf8_encode($row[1]),
									"ReferenciaWMS" => $row[2],
									"ReceivingDate" => utf8_encode($row[3]),
									"Days" => $row[4],
									"Skid/Pallet" => $row[5],
									"Quantity" => $row[6],
									"UMC" => utf8_encode($row[7]),
									"Location" => $row[8],
									"Folio" => $row[9],
									"Trailer" => utf8_encode($row[10]),
									"Proveedor" => utf8_encode($row[11]),
									"Series" => $row[12],
									"RackSpaces" => $row[13],
									"Status" => utf8_encode($row[14]),
									"SkidType" => $row[15],
									"TOM" => $row[16],
									"NumeroPedimento" => $row[17],
									"Referencia" => $row[18],
									"Aduana" => $row[19],
									"AduanaDespacho" => $row[20],
									"Parte" => $row[21],
									"ParteM3" => $row[22],
									"Destinatario" => utf8_encode($row[23]),
									"FechaSalida" => $row[24]);
					}
				}
				$conn->close();
            }
			return $_arr;
        }
		catch(Exception $e) {
			$_narr = array("succes" => false,
							"err" => $e->getMessage());
            return $_narr;
        }
    }
}