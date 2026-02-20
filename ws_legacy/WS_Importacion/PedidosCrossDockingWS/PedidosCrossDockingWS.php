<?php

namespace PedidosCrossDockingWS;

class PedidosCrossDockingWS {

    const TABLE = 'th_consolidado';
    const TABLE_D = 'td_consolidado';
    var $identifier;

    public function __construct( $Fol_PedidoCon = false, $key = false ) {

        if( $Fol_PedidoCon ) {
            $this->Fol_PedidoCon = (int) $Fol_PedidoCon;
        }

        if($key) {

            $sql = sprintf('
          SELECT
            Fol_PedidoCon
          FROM
            %s
          WHERE
            Fol_PedidoCon = ?
        ',
                self::TABLE
            );

            $sth = \db()->prepare($sql);
            $sth->setFetchMode(\PDO::FETCH_CLASS, '\PedidosCrossDockingWS\PedidosCrossDockingWS');
            $sth->execute(array($key));

            $Fol_PedidoCon = $sth->fetch();

            $this->Fol_PedidoCon = $Fol_PedidoCon->Fol_PedidoCon;

        }

    }

    private function load() {

        $sql = sprintf('
        SELECT
          *
        FROM
          %s
        WHERE
          Fol_PedidoCon = ?
      ',
            self::TABLE
        );

        $sth = \db()->prepare( $sql );
        $sth->setFetchMode( \PDO::FETCH_CLASS, '\PedidosCrossDockingWS\PedidosCrossDockingWS' );
        $sth->execute( array( $this->Fol_PedidoCon ) );

        $this->data = $sth->fetch();

    }

    private function loadChangeStatus() {

        $sql = sprintf('
        SELECT
          status
        FROM
          %s
        WHERE
          Fol_PedidoCon = ?
      ',
            self::TABLE
        );

        $sth = \db()->prepare( $sql );
        $sth->setFetchMode( \PDO::FETCH_CLASS, '\PedidosCrossDockingWS\PedidosCrossDockingWS' );
        $sth->execute( array( $this->Fol_PedidoCon ) );

        $this->data = $sth->fetch();

    }

    function getStatus() {

        $sql = '
        SELECT
          *
        FROM
          cat_estados 
      ';

        $sth = \db()->prepare( $sql );
        $sth->setFetchMode( \PDO::FETCH_CLASS, '\PedidosCrossDockingWS\PedidosCrossDockingWS' );
        $sth->execute( array( ESTADO ) );

        return $sth->fetchAll();

    }

    function getAll() {

        $sql = '
        SELECT
          *
        FROM
          ' . self::TABLE . '
      ';

        $sth = \db()->prepare( $sql );
        $sth->setFetchMode( \PDO::FETCH_CLASS, '\PedidosCrossDockingWS\PedidosCrossDockingWS' );
        $sth->execute( array( Fol_PedidoCon ) );

        return $sth->fetchAll();

    }

    private function loadDetalle() {

        $sql = "SELECT
                td_pedido.Fol_PedidoCon,
                td_pedido.Cve_articulo,
                td_pedido.Num_cantidad,
                c_articulo.des_articulo
                FROM
                td_pedido
                INNER JOIN c_articulo ON td_pedido.Cve_articulo = c_articulo.cve_articulo WHERE td_pedido.Fol_PedidoCon = '".$this->Fol_PedidoCon."'";

        $rs = mysqli_query(\db2(), $sql) or die("Error description: " . mysqli_error(\db2()));

        $arr = array();

        while ($row = mysqli_fetch_array($rs)) {
            $arr[] = $row;
        }

        $this->dataDetalle = $arr;

    }

    function __get( $key ) {

        switch($key) {
            case 'Fol_PedidoCon':
                $this->load();
                return @$this->data->$key;
            default:
                return $this->key;
        }

    }

    function __getChangeStatus( $key ) {

        switch($key) {
            case 'ID_Pedido':
                $this->loadChangeStatus();
                return @$this->data->$key;
            default:
                return $this->key;
        }

    }

    function __getDetalle( $key ) {

        switch($key) {
            case 'Fol_PedidoCon':
                $this->loadDetalle();
                return @$this->dataDetalle->$key;
            default:
                return $this->key;
        }

    }

    function save( $_post ) {
        try {
			$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $sql = "Call SPWS_AgregaTH_Consolidado(
                    '". $_post["Almacen"] ."',
                    '". $_post["CodB_Prov"] ."',
					'". $_post["NIT_Prov"] ."',
					'". $_post["Nom_Prov"] ."',
					'". $_post["Cve_CteCon"] ."',
					'". $_post["CodB_CteCon"] ."',
					'". $_post["Nom_CteCon"] ."',
					'". $_post["Dir_CteCon"] ."',
					'". $_post["Cd_CteCon"] ."',
					'". $_post["NIT_CteCon"] ."',
					'". $_post["Cod_CteCon"] ."',
					'". $_post["CodB_CteEnv"] ."',
					'". $_post["Nom_CteEnv"] ."',
					'". $_post["Dir_CteEnv"] ."',
					'". $_post["Cd_CteEnv"] ."',
					'". $_post["Tel_CteEnv"] ."',
					'". $_post["Fec_Entrega"] ."',
					'". $_post["Tot_Cajas"] ."',
					'". $_post["Tot_Pzs"] ."',
					'". $_post["Placa_Trans"] ."',
					'". $_post["Sellos"] ."',
					'". $_post["Fol_PedidoCon"] ."',
					'". $_post["No_OrdComp"] ."');";
			if(!$result = $conn->query($sql))
			{
				$conn->close();
				return 'ERROR: ' . utf8_encode($conn->error);
			}
			$conn->close();
            if (!empty($_post["crossDetalle"])) {
                foreach ($_post["crossDetalle"] as $item) {
					$conn1 = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
					if(!isset($item['CPROWNUM']))
					{
						$CodB_Cte=$item["Cve_CteProv"];
					}
					else
					{
						$CodB_Cte=$item['CPROWNUM'];
					}
					$sql = "Call SPWS_AgregaTD_Consolidado(
							'". $_post["Fol_PedidoCon"] ."',
							'". $item["No_OrdComp"] ."',
							'". $item["Fec_OrdCom"] ."',
							'". $item["Cve_Articulo"] ."',
							'". $item["Item"] ."',
							'". $item["Cant_Pedida"] ."',
							'". $item["Unid_Empaque"] ."',
							'". $item["Tot_Cajas"] ."',
							'". $item["Fact_Madre"] ."',
							'". $item["Cve_Clte"] ."',
							'". $item["Cve_CteProv"] ."',
							'". $item["Fol_Folio"] ."',
							'". $item["CodB_Cte"] ."',
							'". $item["Cod_PV"] ."');";
					if(!$result1 = $conn1->query($sql))
					{
						$conn1->close();
						return 'ERROR: ' . utf8_encode($conn1->error);
					}
					$conn1->close();
                }
            }
			$conn3 = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $sql = "Call SPWS_TerminaCapturaConsolidado(
					'". $_post["Almacen"] ."',
					'". $_post["Fol_PedidoCon"] ."',
					'". $_post["user"] ."');";
			if(!$result3 = $conn3->query($sql))
			{
				$conn3->close();
				return 'ERROR: ' . utf8_encode($conn2->error);
			}
			$conn3->close();
        } catch(Exception $e) {
            return 'ERROR: ' . $e->getMessage();
        }
    }

    function borrarPedido( $data ) {
        $sql = '
        UPDATE
          ' . self::TABLE . '
        SET
          Activo = 0
        WHERE
          Fol_PedidoCon = ?
      ';
        $this->save = \db()->prepare($sql);
        $this->save->execute( array(
            $data['Fol_PedidoCon']
        ) );
    }

    function actualizarStatus( $_post ) {
        try {
            $sql = "UPDATE " . self::TABLE . " SET status='".$_post['status']."' WHERE ID_Pedido='".$_post['ID_Pedido']."'";
            $rs = mysqli_query(\db2(), $sql) or die("Error description: " . mysqli_error(\db2()));

        } catch(Exception $e) {
            return 'ERROR: ' . $e->getMessage();
        }
    }

    function actualizarPedido( $_post ) {
        try {
            $sql = "Delete From " . self::TABLE . " WHERE Fol_PedidoCon = '".$_post['Fol_PedidoCon']."'";
            $rs = mysqli_query(\db2(), $sql) or die("Error description: " . mysqli_error(\db2()));

            $sql = "INSERT INTO " . self::TABLE . " (Fol_PedidoCon, Cve_clte, cve_Vendedor, Fec_Entrada, Pick_Num, Observaciones, cve_almac, Activo)";
            $sql .= "Values (";
            $sql .= "'".$_post['Fol_PedidoCon']."',";
            $sql .= "'".$_post['Cve_clte']."',";
            $sql .= "'".$_post['cve_Vendedor']."',";
            $sql .= "now(),";
            $sql .= "'".$_post['Pick_Num']."',";
            $sql .= "'".$_post['Observaciones']."',";
            $sql .= "'".$_post['cve_almac']."', '1');";

            $rs = mysqli_query(\db2(), $sql) or die("Error description: " . mysqli_error(\db2()));

            if (!empty($_post["arrDetalle"])) {
                $sql = "Delete From td_pedido WHERE Fol_PedidoCon = '".$_post['Fol_PedidoCon']."'";
                $rs = mysqli_query(\db2(), $sql) or die("Error description: " . mysqli_error(\db2()));

                foreach ($_post["arrDetalle"] as $item) {
                    $sql = "INSERT INTO td_pedido (Cve_articulo, Num_cantidad, Fol_PedidoCon, Activo) Values ";
                    $sql .= "('".$item['codigo']."', '".$item['CantPiezas']."', '".$_post['Fol_PedidoCon']."', '1');";
                    $rs = mysqli_query(\db2(), $sql) or die("Error description: " . mysqli_error(\db2()));
                }
            }
        } catch(Exception $e) {
            return 'ERROR: ' . $e->getMessage();
        }
    }
}
