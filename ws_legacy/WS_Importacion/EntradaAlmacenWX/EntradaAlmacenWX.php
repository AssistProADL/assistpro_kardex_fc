<?php
namespace EntradaAlmacenWX;
class EntradaAlmacenWX {
    const TABLE = 'th_entalmacen';
    const TABLE_D = 'td_entalmacen';
    var $identifier;
    public function __construct( $fol_folio = false, $key = false ) {
        if( $fol_folio ) {
            $this->fol_folio = (int) $fol_folio;
        }
        if($key) {
            $sql = sprintf('SELECT fol_folio FROM %s WHERE fol_folio=?',self::TABLE);
            $sth = \db()->prepare($sql);
            $sth->setFetchMode(\PDO::FETCH_CLASS, '\EntradaAlmacenWS\EntradaAlmacenWS');
            $sth->execute(array($key));
            $fol_folio = $sth->fetch();
            $this->fol_folio = $fol_folio->fol_folio;
        }
    }

    private function load() {
        $sql = sprintf('SELECT * FROM %s WHERE td_entalmacen.fol_folio=?',self::TABLE);
        $sth = \db()->prepare( $sql );
        $sth->setFetchMode( \PDO::FETCH_CLASS, '\EntradaAlmacenWS\EntradaAlmacenWS' );
        $sth->execute( array( $this->fol_folio ) );
        $this->data = $sth->fetch();
    }

    private function loadDetalle() {
        $sql = "SELECT td_entalmacen.fol_folio,td_entalmacen.cve_articulo,td_entalmacen.cve_lote,td_entalmacen.CantidadPedida,td_entalmacen.CantidadRecivida,
					td_entalmacen.CantidadDisponible,td_entalmacen.CantidadUbicada,c_articulo.des_articulo,c_lotes.LOTE,c_lotes.CADUCIDAD
				FROM td_entalmacen INNER JOIN c_articulo ON td_entalmacen.cve_articulo=c_articulo.cve_articulo
					INNER JOIN c_lotes ON c_articulo.cve_articulo=c_lotes.cve_articulo
				WHERE td_entalmacen.id_ocompra='".$this->fol_folio."'";
        $rs = mysqli_query(\db2(), $sql) or die("Error description: " . mysqli_error(\db2()));
        $arr = array();
        while ($row = mysqli_fetch_array($rs)) {
            $arr[] = $row;
        }
        $this->dataDetalle = $arr;
    }

    function __get( $key ) {
        switch($key) {
            case 'fol_folio':
                $this->load();
                return @$this->data->$key;
            default:
                return $this->key;
        }
    }

    function __getDetalle( $key ) {
        switch($key) {
            case 'fol_folio':
                $this->loadDetalle();
                return @$this->dataDetalle->$key;
            default:
                return $this->key;
        }
    }

    function actualizarOrden( $_post ) {
		try {
            if (!empty($_post["arreglo"])) {
                foreach ($_post["arreglo"] as $item) {
                    $a = (array) $item;
                    $sql = "Select SPAD_AgregaArticuloEntrada(
                            '" . $a["Folio"] . "',
                            '" . $a["Producto"] . "',
                            '" . $a["Lote"] . "',
                            '" . $a["CantidadRecivida"] . "');";
                    $rs = mysqli_query(\db2(), $sql) or die("Error description: " . mysqli_error(\db2()));
                    $row = mysqli_fetch_array($rs);
                }
            }
		} catch(Exception $e) {
            return 'ERROR: ' . $e->getMessage();
        }
    }

    function borrarOrden( $data ) {
        try {
			$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
			$ItemN="0";
			if(isset($_post['CPROWNUM'])) {
				$ItemN=$_post['CPROWNUM'];
			}
            $sql = "Call SPWS_CancelaEntradWS('"
				. $_post['OrdCompra'] . "','"
				. $ItemN . "');";
			if(!$result = $conn->query($sql))
			{
				$conn->close();
				return 'ERROR: ' . utf8_encode($conn->error);
			}
			else
			{
				while ($row = $result->fetch_array(MYSQLI_NUM)) {
					$_arr = array("ERROR" => $row[0],
									"MSG" => $row[1]);
					if($row[0]==-1)
					{
						$_narr = array("ERROR" => $row[0],
										"MSG" => $row[1]);
						$conn->close();
						return 'ERROR: '.$row[1];
					}
				}
				$conn->close();
            }
			return 'Guardado';
        }
		catch(Exception $e) {
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
			if(isset($_post['Folio'])) {
				$Folio=_post['Folio'];
			}
			else
			{
				$Folio=0;
			}
            $sql = "Call SPAD_DameDatosAduanaWX('"
				. $_post['Almacen'] . "','"
				. $_post['fecha_inicio'] . "','"
				. $_post['fecha_fin'] . "','"
				. $Estatus . "','"
				. $_post['Articulo'] . "','"
				. $_post['Ref_Well'] . "',"
				. $Folio . ",'"
				. $_post['Pedimento'] . "','"
				. $_post['Aduana'] . "');";
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
									"Customer" => $row[1],
									"ProductID" => $row[2],
									"DescriptionMercancia" => utf8_encode($row[3]),
									"ReceivingDate" => $row[4],
									"Days" => $row[5],
									"Skid/Pallet" => $row[6],
									"Quantity" => $row[7],
									"UMC" => $row[8],
									"Location" => $row[9],
									"Folio" => $row[10],
									"Trailer" => utf8_encode($row[11]),
									"Proveedor" => utf8_encode($row[12]),
									"Series" => $row[13],
									"RackSpaces" => $row[14],
									"Status" => $row[15],
									"SkidType" => $row[16],
									"TOM" => $row[17],
									"NumeroPedimento" => $row[18],
									"Referencia" => $row[19],
									"Aduana" => $row[20],
									"AduanaDespacho" => $row[21],
									"Parte" => $row[22],
									"ParteM3" => $row[23],
									"LP" => $row[24]);
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

    function save( $_post ) {
        try {
			if(isset($_post['BL'])) {
				$BL=$_post['BL'];
			}
			else
			{
				$BL='';
			}
			if(isset($_post['LP'])) {
				$LP=_post['LP'];
			}
			else
			{
				$LP='';
			}
			if(isset($_post['Lote'])) {
				$Lote=_post['Lote'];
			}
			else
			{
				$Lote='';
			}
			if(isset($_post['Caducidad'])) {
				$Caducidad=_post['Caducidad'];
			}
			else
			{
				$Caducidad='';
			}
			$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $sql = "Call SPWS_CreaAduanaWX('"
				. $_post['Almacen'] . "','"
				. $_post['ClaveArticuloSKU'] . "',"
				. $_post['Existencia'] . ",'"
				. $_post['ReferenciaWell'] . "','"
				. $_post['NumeroPedimento'] . "','"
				. $_post['TipoOperacion'] . "','"
				. $_post['ClavePedimento'] . "','"
				. $_post['Cove'] . "',"
				. $_post['AduanaES'] . ","
				. $_post['AduanaDespacho'] . ","
				. $_post['PesoBruto'] . ",'"
				. $_post['MarcasNumerosTotalBultos'] . "',"
				. $_post['ClaveImportadorExportador'] . ",'"
				. $_post['ImportadorExportador'] . "','"
				. $_post['ClaveCliente'] . "','"
				. $_post['Cliente'] . "','"
				. $_post['ClaveDestinatario'] . "','"
				. $_post['Destinatario'] . "','"
				. $_post['OrdenCompra'] . "','"
				. $_post['BlMaster'] . "','"
				. $_post['BlHouse'] . "','"
				. $_post['Contenedores'] . "',"
				. $_post['NumeroPartida'] . ","
				. $_post['ConsecutivoFactura'] . ",'"
				. $_post['Factura'] . "','"
				. $_post['FechaFactura'] . "',"
				. $_post['ClaveProveedor'] . ",'"
				. $_post['Proveedor'] . "','"
				. $_post['ParteM3'] . "','"
				. $_post['Parte'] . "',"
				. $_post['Fraccion'] . ","
				. $_post['Nico'] . ",'"
				. $_post['DescripcionAA'] . "','"
				. $_post['PaisOrigenDestino'] . "','"
				. $_post['PaisVendedorComprador'] . "',"
				. $_post['CantidadFactura'] . ","
				. $_post['UMComercializacion'] . ","
				. $_post['CantidadTarifa'] . ","
				. $_post['UMTarifa'] . ","
				. $_post['PrecioUnitario'] . ","
				. $_post['ValorFactura'] . ",'"
				. $_post['UMCOVE'] . "',"
				. $_post['CantidadCOVE'] . ","
				. $_post['ValorMercanciaCOVE'] . ","
				. $_post['PrecioUnitarioCOVE'] . ",'"
				. $_post['DescripcionFacturaCOVE'] . "','"
				. $_post['PlacasTranposte'] . "','"
				. $BL . "','"
				. $LP . "','"
				. $Lote . "','"
				. $Caducidad . "');";
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
					$_arr = array("succes" => true,
								"ordenCompra" => $row[2],
								"status" => "abierta");
					if($row[0]==-1)
					{
						$_narr = array("succes" => false,
										"err" => $row[1]);
						$conn->close();
						return $_narr;
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
