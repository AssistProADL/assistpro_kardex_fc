<?php

namespace ClientesWS;

class ClientesWS {

    const TABLE = 'c_cliente';
    var $identifier;

    public function __construct( $Cve_Clte = false, $key = false ) {
        if( $Cve_Clte ) {
            $this->Cve_Clte = (int) $Cve_Clte;
        }
        if($key) {
            $sql = sprintf('SELECT Cve_Clte FROM %s WHERE Cve_Clte = ?',self::TABLE);
            $sth = \db()->prepare($sql);
            $sth->setFetchMode(\PDO::FETCH_CLASS, '\ClientesWS\ClientesWS');
            $sth->execute(array($key));
            $Cve_Clte = $sth->fetch();
            $this->Cve_Clte = $Cve_Clte->Cve_Clte;
        }
    }

    private function load() {
        $cve = $this->Cve_Clte;
        $sql = "SELECT 	c.*,d.departamento,d.des_municipio,de.id_destinatario as id_destinatario 
				FROM 	c_cliente c LEFT JOIN c_dane d ON d.cod_municipio=c.CodigoPostal 
						LEFT JOIN c_destinatarios de ON de.Cve_Clte = c.Cve_Clte 
				WHERE 	c.Cve_Clte = '$cve';";
        $sth = \db()->prepare( $sql );
        $sth->setFetchMode( \PDO::FETCH_CLASS, '\ClientesWS\ClientesWS' );
        $sth->execute();
        $this->data = $sth->fetch();
        $cliente = $this->data->Cve_Clte;
        $sql = "SELECT 	id_destinatario,
						CONCAT(contacto,'-',direccion) AS texto,CONCAT(razonsocial,'|',direccion,'|',colonia,'|',postal,'|',ciudad,'|',estado,'|',contacto,'|',telefono) AS value 
				FROM 	c_destinatarios 
				WHERE 	Cve_Clte='$cliente';";
        $sth = \db()->prepare($sql);
        $sth->execute();
        $destinatarios = $sth->fetchAll(\PDO::FETCH_ASSOC);
        $this->data->destinatarios = $destinatarios;
    }

    function __get( $key ) {
        switch($key) {
            case 'Cve_Clte':
                $this->load();
                return @$this->data->$key;
            default:
                return $this->key;
        }
    }

    function actualizarClientes( $data ) {
		try{
			$almacen = 0;
			if(isset($data['almacenp']) && !empty($data['almacenp']))
			{
				$sql = "SELECT almac.id FROM c_almacenp WHERE cve_almac='" . $data['almacenp'] . "';";
				$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
				if (!($res = mysqli_query($conn, $sql))) 
				{
					echo "Falló la preparación: (" . mysqli_error($conn) . ") ";
				}
				$row = mysqli_fetch_array($res);
				$almacen = $row['id'];
			}
			else
			{
				$sql = "SELECT almac.id FROM t_usu_alm_pre prede,c_almacenp almac WHERE prede.cve_almac=almac.clave AND id_user=".$_SESSION['id_user'];
				$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
				if (!($res = mysqli_query($conn, $sql))) 
				{
					echo "Falló la preparación: (" . mysqli_error($conn) . ") ";
				}
				$row = mysqli_fetch_array($res);
				$almacen = $row['id'];
			}
			if($data['Cve_CteProv']=='' || is_null($data['Cve_CteProv']))
			{
				$CveCteProv=$data['Cve_Clte'];
			}
			else
			{
				$CveCteProv=$data['Cve_CteProv'];
			}
			if(!isset($data['Longitud']) || $data['Longitud']=='' || is_null($data['Longitud']))
			{
				$Longitud=0;
			}
			else
			{
				$Longitud=$data['Longitud'];
			}
			if(!isset($data['Latitud']) || $data['Latitud']=='' || is_null($data['Latitud']))
			{
				$Latitud=0;
			}
			else
			{
				$Latitud=$data['Latitud'];
			}
			if (!IsSet($data["Activo"])) {
				$Activo = 1;
			} else {
				$Activo = $data["Activo"];
			}
			if ($Activo!="0" And $Activo!="1"){
				return 'ERROR: Valor invalido de Activo';
			}
			$sql = '
			UPDATE
			  ' . self::TABLE . '
			SET
				RazonSocial = :RazonSocial,
				CalleNumero = :CalleNumero,
				Colonia = :Colonia,
				CodigoPostal = :CodigoPostal,
				Ciudad = :Ciudad,
				Estado = :Estado,
				Pais = :Pais,
				RFC = :RFC,
				Telefono1 = :Telefono1,
				Telefono2 = :Telefono2,
				CondicionPago = :CondicionPago,
				ID_Proveedor = :ID_Proveedor,
				ClienteTipo = :ClienteTipo,
				ZonaVenta = :ZonaVenta,
				Contacto = :Contacto,
				longitud = :Longitud,
				latitud = :Latitud,
				Activo= :Activo
			WHERE
				Cve_Clte = :Cve_Clte And Cve_CteProv =:Cve_CteProv;';
			$this->save = \db()->prepare($sql);
			$this->save->bindValue( ':Cve_Clte', $data['Cve_Clte'], \PDO::PARAM_STR );
			$this->save->bindValue( ':Cve_CteProv', $CveCteProv, \PDO::PARAM_STR );
			$this->save->bindValue( ':RazonSocial', $data['RazonSocial'], \PDO::PARAM_STR );
			$this->save->bindValue( ':CalleNumero', $data['CalleNumero'], \PDO::PARAM_STR );
			$this->save->bindValue( ':Colonia', $data['Colonia'], \PDO::PARAM_STR );
			$this->save->bindValue( ':CodigoPostal', $data['CodigoPostal'], \PDO::PARAM_STR );
			$this->save->bindValue( ':Ciudad', $data['Ciudad'], \PDO::PARAM_STR );
			$this->save->bindValue( ':Estado', $data['Estado'], \PDO::PARAM_STR );
			$this->save->bindValue( ':Pais', $data['Pais'], \PDO::PARAM_STR );
			$this->save->bindValue( ':RFC', $data['RFC'], \PDO::PARAM_STR );
			$this->save->bindValue( ':Telefono1', $data['Telefono1'], \PDO::PARAM_STR );
			$this->save->bindValue( ':Telefono2', $data['Telefono2'], \PDO::PARAM_STR );
			$this->save->bindValue( ':CondicionPago', $data['CondicionPago'], \PDO::PARAM_STR );
			$this->save->bindValue( ':ID_Proveedor', $data['ID_Proveedor'], \PDO::PARAM_STR );
			$this->save->bindValue( ':ClienteTipo', $data['ClienteTipo'], \PDO::PARAM_STR );
			$this->save->bindValue( ':ZonaVenta', $data['ZonaVenta'], \PDO::PARAM_STR );
			$this->save->bindValue( ':Cve_Almacenp', $data['almacenp'], \PDO::PARAM_STR );
			$this->save->bindValue( ':Contacto', $data['Contacto'], \PDO::PARAM_STR );
			$this->save->bindValue( ':Latitud', $Latitud, \PDO::PARAM_STR );
			$this->save->bindValue( ':Longitud', $Longitud, \PDO::PARAM_STR );
			$this->save->bindValue( ':Activo', $Activo, \PDO::PARAM_STR );
			$this->save->execute();
			if (isset($data["fromAPI"]))
			{
			  return "Actualizado";
			}
			/* $clave_cliente = $data['Cve_Clte'];
			$destinatarios = $data['destinatarios'];
			//mysqli_query(\db2(), "DELETE FROM c_destinatarios WHERE Cve_Clte = '{$clave_cliente}';");
			if(intval($data['usar_direccion']) === 1){
			$sql = "UPDATE c_cliente SET ID_Destinatario = 0 WHERE Cve_Clte = '{$clave_cliente}';";
			mysqli_query(\db2(), $sql);
			}else if(!empty($destinatarios)){
				foreach($destinatarios as $destinatario){
					list($razon, $direccion, $colonia, $codigop, $ciudad, $estado, $contacto, $telefono, $principal) = explode("|", $destinatario);
					$sql = "INSERT INTO c_destinatarios (Cve_Clte, razonsocial, direccion, colonia, postal, ciudad, estado, contacto, telefono) VALUES ('{$clave_cliente}', '{$razon}', '{$direccion}', '{$colonia}', '{$codigop}', '{$ciudad}', '{$estado}', '{$contacto}', '{$telefono}');";
					mysqli_query(\db2(), $sql);
					if(intval($principal) === 1){
						$sql = "UPDATE c_cliente SET ID_Destinatario = (SELECT MAX(id_destinatario) FROM c_destinatarios) WHERE Cve_Clte = {$clave_cliente};";
						mysqli_query(\db2(), $sql);
					}
				}
			}else{
				$sql = "UPDATE c_cliente SET ID_Destinatario = NULL WHERE Cve_Clte = '{$clave_cliente}';";
				mysqli_query(\db2(), $sql);
			}*/
		} catch(PDOException $e) {
			return 'ERROR: ' . $e->getMessage();
		}
    }

    function save($data) 
    {
		try 
		{
			$almacen = 0;
			$Actualiza = 0;
			if(isset($data['almacenp']) && !empty($data['almacenp']))
			{
				$sql = "SELECT id FROM c_almacenp WHERE clave='" . $data['almacenp'] . "';";
				$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
				if (!($res = mysqli_query($conn, $sql))) 
				{
					echo "Fallo la preparacion: (" . mysqli_error($conn) . ") ";
				}
				$row = mysqli_fetch_array($res);
				$almacen = $row['id'];
			}
			else
			{
				if(isset($data['Cve_Almacenp']) && !empty($data['Cve_Almacenp']))
				{
					$sql = "SELECT id FROM c_almacenp WHERE clave='" . $data['Cve_Almacenp'] . "';";
					$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
					if (!($res = mysqli_query($conn, $sql))) 
					{
						echo "Fallo la preparacion: (" . mysqli_error($conn) . ") ";
					}
					$row = mysqli_fetch_array($res);
					$almacen = $row['id'];
				}
				else {
					$sql = "SELECT almac.id FROM trel_us_alm prede,c_almacenp almac WHERE prede.cve_almac=almac.clave AND cve_usuario='" . $data['user'] . "';";
					$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
					if (!($res = mysqli_query($conn, $sql))) 
					{
						echo "Falló la preparación: (" . mysqli_error($conn) . ") ";
					}
					$row = mysqli_fetch_array($res);
					$almacen = $row['id'];
				}
			}
			if (!IsSet($data["Cve_CteProv"])) {
				$CveCteProv = $data['Cve_Clte'];
			}
			if($data['Cve_CteProv']=='' || is_null($data['Cve_CteProv']))
			{
				$CveCteProv=$data['Cve_Clte'];
			}
			else
			{
				$CveCteProv=$data['Cve_CteProv'];
			}
			if(!isset($data['Longitud']) || $data['Longitud']=='' || is_null($data['Longitud']))
			{
				$Longitud=0;
			}
			else
			{
				$Longitud=$data['Longitud'];
			}
			if(!isset($data['Latitud']) || $data['Latitud']=='' || is_null($data['Latitud']))
			{
				$Latitud=0;
			}
			else
			{
				$Latitud=$data['Latitud'];
			}
			if (!IsSet($data["Activo"])) {
				$Activo = 1;
			} else {
				$Activo = $data["Activo"];
			}
			if ($Activo!="0" And $Activo!="1"){
				return 'ERROR: Valor invalido de Activo';
			}
			if (!IsSet($data["Ban_Almacenes"])) {
				$BanAlmac = 0;
			} else {
				$BanAlmac = $data["Ban_Almacenes"];
			}
			$sql = "Call SPWS_AgregaClientes('" . $data['Cve_Clte'] . "',"
					. "'" . $CveCteProv . "',"
					. "'" . $data['RazonSocial'] . "',"
					. "'" . $data['RazonSocial'] . "',"
					. "'" . $data['CalleNumero'] . "',"
					. "'" . $data['Colonia'] . "',"
					. "'" . $data['Ciudad'] . "',"
					. "'" . $data['Estado'] . "',"
					. "'" . $data['Pais'] . "',"
					. "'" . $data['CodigoPostal'] . "',"
					. "'" . $data['RFC'] . "',"
					. "'" . $data['Telefono1'] . "',"
					. "'" . $data['Telefono2'] . "',"
					. "'" . $data['ClienteTipo'] . "',"
					. "'" . $data['CondicionPago'] . "',"
					. "'" . $data['ZonaVenta'] . "'"
					. ",'" . $data['ID_Proveedor'] . "'"
					. "," . $almacen
					. "," . $Longitud
					. "," . $Latitud
					. "," . $Activo
					. "," . $BanAlmac . ");";
			mysqli_query($conn, $sql);
			if (isset($data["fromAPI"]))
			{
				return "Guardado";
			}
		}
		catch(PDOException $e) 
		{
			return 'ERROR: ' . $e->getMessage();
		}
    }
	
    function saveDest($data) 
    {
		try 
		{
			$Actualiza = 0;
			if (!IsSet($data["clave_destinatario"])) {
				$CveCteProv = $data['Cve_Clte'];
			} else {
				$CveCteProv = $data["clave_destinatario"];
			}
			if (!IsSet($data["Activo"])) {
				$Activo = 1;
			} else {
				$Activo = $data["Activo"];
			}
			if ($Activo!="0" And $Activo!="1"){
				return 'ERROR: Valor invalido de Activo';
			}
			$sql = "Call SPWS_CreaDestinatario('" . $data['Cve_Clte'] . "',"
					. "'" . $CveCteProv . "',"
					. "'" . $data['razonsocial'] . "',"
					. "'" . $data['direccion'] . "',"
					. "'" . $data['colonia'] . "',"
					. "'" . $data['postal'] . "',"
					. "'" . $data['ciudad'] . "',"
					. "'" . $data['estado'] . "',"
					. "'" . $data['contacto'] . "',"
					. "'" . $data['telefono'] . "',"
					. $Activo . ");";
			$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
			mysqli_query($conn, $sql);
			if (isset($data["fromAPI"]))
			{
				return "Guardado";
			}
		}
		catch(PDOException $e) 
		{
			return 'ERROR: ' . $e->getMessage();
		}
    }
	
    function getAll() 
    {
        $sql = 'SELECT * FROM ' . self::TABLE . ' WHERE Activo=1 ORDER BY RazonSocial';
        $sth = \db()->prepare( $sql );
        $sth->setFetchMode( \PDO::FETCH_CLASS, '\ClientesWS\ClientesWS' );
        $sth->execute( array( Cve_Clte ) );
        return $sth->fetchAll();
    }	
	
	function traerRutas($id_cliente) {
        $sql = 'SELECT 	r.*,c_dane.des_municipio 
				FROM 	t_clientexruta cr,t_ruta r,c_cliente,c_dane
				WHERE 	cr.clave_cliente = "'.$id_cliente.'"
						and cr.clave_ruta = r.ID_Ruta 
						and cr.clave_cliente= c_cliente.id_cliente
						and c_dane.cod_municipio=c_cliente.CodigoPostal
						and r.activo= 1;';
        $sth = \db()->prepare( $sql );
        $sth->execute();
        return $sth->fetchAll();
    }	

	function traerClientesDeRuta ($id_ruta){
	$sql = 'SELECT c.* FROM t_clientexruta cr,c_cliente c WHERE cr.clave_ruta='.$id_ruta.' and cr.clave_cliente = c.id_cliente and c.activo= 1;';
	$sth = \db()->prepare( $sql );
	$sth->execute();
	return $sth->fetchAll();
	}

    function borrarCliente( $data ) {
        $sql = '
        UPDATE
          ' . self::TABLE . '
        SET
          Activo = 0
        WHERE
          Cve_Clte = ?
      ';
        $this->save = \db()->prepare($sql);
        $this->save->execute( array(
            $data['Cve_Clte']
        ) );
    }

    function asignarRutaCliente( $cliente, $idRuta ) {

        $sql = '
        UPDATE
          ' . self::TABLE . '
        SET            
            cve_ruta = ?
        WHERE
          Cve_Clte = ?
      ';
        $this->save = \db()->prepare($sql);
        $this->save->execute( array(
            $idRuta,
            $cliente
        ) );
    }

     function loadClienteRuta($ID_Ruta) {
        $sql = '
        SELECT
          c.*
        FROM
          c_cliente c, t_clientexruta cr      
        WHERE
          cr.clave_ruta = ? and c.id_cliente = cr.clave_cliente          
      ';
        $sth = \db()->prepare( $sql );
        $sth->setFetchMode( \PDO::FETCH_CLASS, '\ClientesWS\ClientesWS' );
        $sth->execute( array( id_cliente ) );
        return $sth->fetchAll();
    }
	
	function recoveryCliente( $data ) {

          $sql = "UPDATE c_cliente SET Activo = 1 WHERE  id_cliente='".$data['id_cliente']."';";
          $this->delete = \db()->prepare($sql);
          $this->delete->execute( array(
              $data['id_cliente']
          ) );
    }
	
  function exist($Cve_Clte) 
  {
   /* $sql = sprintf('
      SELECT
        *
      FROM
        c_cliente
      WHERE
        Cve_Clte = ?
    ',
      self::TABLE
    );*/
    $sql = "SELECT * FROM c_cliente WHERE Cve_Clte = '{$Cve_Clte}'";
    $sth = \db()->prepare( $sql );
    $sth->setFetchMode( \PDO::FETCH_CLASS, '\ClientesWS\ClientesWS' );
    $sth->execute( array( $Cve_Clte ) );
    $this->data = $sth->fetch();
    if(!$this->data)
    {
      return false; 
    }
    else 
    {
      return true;
    }
  }

	function getCliente( $_post ) {
        try {
			$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $sql = "Call SPWS_DameClientesWS('"
				. $_post['Clave'] . "');";
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
										"err" => 'No Hay informacion de clientes');
						$conn->close();
						return $_narr;
					}
					else
					{
						$_arr[] = array("succes" => true,
									"id_cliente" => $row[1],
									"Cve_Clte" => $row[2],
									"RazonSocial" => utf8_encode($row[3]),
									"CalleNumero" => utf8_encode($row[4]),
									"Colonia" => utf8_encode($row[5]),
									"Ciudad" => utf8_encode($row[6]),
									"Estado" => utf8_encode($row[7]),
									"Pais" => utf8_encode($row[8]),
									"CodigoPostal" => utf8_encode($row[9]),
									"RFC" => utf8_encode($row[10]),
									"Telefono1" => utf8_encode($row[11]),
									"Telefono2" => utf8_encode($row[12]),
									"Cve_CteProv" => $row[13],
									"Longitud" => $row[14],
									"Latitud" => $row[15]);
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
	  
	function getCliente2(){
		$sql= 'Select c.*, d.departamento, d.des_municipio, a.nombre as almacenp from c_cliente c  left join c_almacenp a on a.id=c.Cve_Almacenp
		left join c_dane d on  d.cod_municipio=c.CodigoPostal where c.Activo=1';
		  $sth = \db()->prepare( $sql );
          $sth->setFetchMode( \PDO::FETCH_CLASS, '\ClientesWS\ClientesWS' );
          $sth->execute( array( id_cliente ) );
          return $sth->fetchAll();
	}
  
    function asignarRutaACliente($data) 
    {
      $clientes = $data["clientes"];
      $ruta = $data["ruta"];
      foreach($clientes as $cliente)
      {
        $sql = "
          INSERT INTO `t_clientexruta`
            (`clave_cliente`, `clave_ruta`)
          VALUES (
            (SELECT id_cliente FROM c_cliente WHERE Cve_Clte = '{$cliente}'),
            (SELECT ID_Ruta FROM t_ruta WHERE cve_ruta = '{$ruta}')
          )
        ";
        $sth = \db()->prepare( $sql );
        $sth->setFetchMode( \PDO::FETCH_CLASS, '\ClientesWS\ClientesWS' );
        $sth->execute();
      }
    }	
  
  
    function getConsecutivo()
    {
      $sql = "SELECT COUNT(id_cliente)+1 as id_actual FROM `c_cliente`";

      $sth = \db()->prepare($sql);
      $sth->execute();
      $consecutivo = $sth->fetch();
      return($consecutivo["id_actual"]);
    }
	  
	  
	}
