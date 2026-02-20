<?php
namespace EntradaAlmacenWS;
class EntradaAlmacenWS {
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

    function save( $_post ) {
        try {
			$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
			$DocEntry="";
			if(isset($_post['DocEntry'])) {
				$DocEntry=$_post['DocEntry'];
			}
			if(isset($_post['DOSERIAL'])) {
				$DocEntry=$_post['DOSERIAL'];
			}
			$AlmacOri="";
			if(isset($_post['AlmacenOri'])) {
				$AlmacOri=$_post['AlmacenOri'];
			}
			$TipoC=1;
			if(isset($_post['TipoC'])) {
				$TipoC=$_post['TipoC'];
			}
			if($TipoC=="") {
				$TipoC=1;
			}
			$Almacen=$_post['Almacen'];
            $sql = "Call SPWS_CreaAduanaSufijo('"
				. $_post['NPedimento'] . "','"
				. $_post['FechaPedimento'] . "','"
				. $DocEntry . "','"
				. $_post['OrdCompra'] . "','"
				. $_post['Proveedor'] . "','"
				. $_post['Empresa'] . "','"
				. $_post['Protocolo'] . "','"
				. $_post['Almacen'] . "','"
				. $AlmacOri . "',"
				. $TipoC . ","
				. "'N');"; //Cambiar a 'S' para DICOISA
			if(!$result = $conn->query($sql))
			{
				$conn->close();
				return 'ERROR: ' . utf8_encode($conn->error);
			}
			else
			{
				while ($row = $result->fetch_array(MYSQLI_NUM)) {
					$_arr = array("ERROR" => $row[0],
									"MSG" => $row[1],
									"ID_Aduana" => $row[2]);
					if($row[0]==-1)
					{
						$_narr = array("ERROR" => $row[0],
										"MSG" => $row[1]);
						$conn->close();
						return 'ERROR: '.$row[1];
					}
				}
				$conn->close();
				$a=(array)$_arr;
				if (!empty($_post["arrDetalle"])) {
					foreach ($_post["arrDetalle"] as $item) {
						$DoSerial="";
						if(isset($_post['DOSERIAL'])) {
							$DoSerial=$_post['DOSERIAL'];
						}
						$AlmacOri="01";
						if(isset($item['AlmacOri'])) {
							$AlmacOri=$item['AlmacOri'];
						}
						if(isset($item['Almac'])){
							$Almacen=$item['Almac'];
						}
						$CPROWNUM='';
						if(isset($item['CPROWNUM'])){
							$CPROWNUM=$item['CPROWNUM'];
						}
						$OrdC='';
						$Suma=0;
						if(isset($item['OC'])){
							$OrdC=$item['OC'];
							$Suma=1;
						}
						$connd = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
						$sql = "Call SPWS_AgregaDetalleAduana( ";
						$sql .= $a["ID_Aduana"] . ",'"
							. $item['codigo'] . "',"
							. $item['CantPiezas'] . ",'"
							. $item['Lote'] . "','"
							. $item['Caducidad'] . "','"
							. $item['Temperatura'] . "','"
							. $item['ItemNum'] . "',"
							. $Suma . ",'"
							. $Almacen . "','"
							. $AlmacOri . "','"
							. $DoSerial . "','"
							. $CPROWNUM . "','"
							. $OrdC . "');";
						if(!$res = $connd->query($sql))
						{
							$connd->close();
							return 'ERROR: ' . utf8_encode($connd->error);
						}
						else
						{
							while ($row1 = $res->fetch_array(MYSQLI_NUM)) {
								if($row1[0]==-1)
								{
									$Mensaje=$row1[1];
									$connd->close();
									return 'ERROR: '.$Mensaje;
								}
							}
							$connd->close();
						}
					}
				}
            }
			return 'Guardado';
        }
		catch(Exception $e) {
            return 'ERROR: ' . $e->getMessage();
        }
    }
}
