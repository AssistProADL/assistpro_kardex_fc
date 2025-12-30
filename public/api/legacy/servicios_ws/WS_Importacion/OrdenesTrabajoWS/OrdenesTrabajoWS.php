<?php

namespace OrdenesTrabajoWS;

class OrdenesTrabajoWS {

    function save( $_post ) {
        try {
			$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $sql = "Call SPWS_AgregaOrdenProd('"
				. $_post['Almacen'] . "','"
				. $_post['Proveedor'] . "','"
				. $_post['NumeroOT'] . "','"
				. $_post['FechaOT'] . "','"
				. $_post['FechaEnt'] . "','"
				. $_post['Articulo'] . "','"
				. $_post['Lote'] . "','"
				. $_post['FCaducidad'] . "',"
				. $_post['Prioridad'] . ","
				. $_post['Cantidad'] . ",'"
				. $_post['Almacen_Ori'] . "','"
				. $_post['Referencia'] . "','"
				. $_post['Cliente'] . "');";
			if(!$result = $conn->query($sql))
			{
				$conn->close();
				return 'ERROR: ' . 'NO SE EJECUTO EL SP SPWS_AgregaOrdenProd :(';
			}
			else
			{
				while ($row = $result->fetch_array(MYSQLI_NUM)) {
					$_arr = array("ERROR" => $row[0],
									"MSG" => utf8_encode($row[1]));
				}
				$conn->close();
				$a=(array)$_arr;
				if($a['ERROR']==-1)
				{
					return 'ERROR: '.$a['MSG'];
				}
				if (!empty($_post["arrDetalle"])) {
					foreach ($_post["arrDetalle"] as $item) {
						$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
						$sql = "Call SPWS_AgregaDetOrdenProd( '";
						$sql .= $_post["NumeroOT"] . "','"
							. $_post['Proveedor'] . "','"
							. $item['codigo'] . "','"
							. $item['loteA'] . "',"
							. $item['CantPiezas'] . ","
							. $item['ItemNum'] . ",'"
							. $item['AlmacOri'] . "');";
						if(!$res = $conn->query($sql))
						{
							$conn->close();
							return 'ERROR: ' . 'NO SE EJECUTO EL SP SPWS_AgregaDetOrdenProd :(';
						}
						else
						{
							$conn->close();
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
