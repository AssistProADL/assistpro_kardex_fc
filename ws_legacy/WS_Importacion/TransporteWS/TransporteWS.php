<?php

  namespace TransporteWS;

  class TransporteWS {

    const TABLE = 't_transporte';
    var $identifier;

    public function __construct( $ID_Transporte = false, $key = false )
	{
      if( $ID_Transporte ) {
        $this->ID_Transporte = (int) $ID_Transporte;
      }
      if($key) {
        $sql = sprintf('SELECT ID_Transporte FROM %s WHERE ID_Transporte = ?',self::TABLE);
        $sth = \db()->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, '\Transporte\Transporte');
        $sth->execute(array($key));
        $transporte = $sth->fetch();
        $this->ID_Transporte = $transporte->ID_Transporte;
      }
    }

    private function load()
	{
      $sql = sprintf('SELECT * FROM %s WHEREID_Transporte = ?',self::TABLE);
      $sth = \db()->prepare( $sql );
      $sth->setFetchMode( \PDO::FETCH_CLASS, '\Transporte\Transporte' );
      $sth->execute( array( $this->ID_Transporte ) );
      $this->data = $sth->fetch();
    }

    function getAll()
	{
        $sql = 'SELECT * FROM ' . self::TABLE . '';
        $sth = \db()->prepare( $sql );
        $sth->setFetchMode( \PDO::FETCH_CLASS, '\Transporte\Transporte' );
        @$sth->execute( array( ID_Transporte ) );
        return $sth->fetchAll();
    }

    function __get( $key ) {
      switch($key) {
        case 'ID_Transporte':
          $this->load();
          return @$this->data->$key;
        case 'Nombre':
        case 'Placas':
        case 'cve_cia':
        case 'tipo_transporte':
          $this->load();
          return @$this->data->$key;
        default:
          return $this->key;
      }
    }

	function save( $_post )
	{
		try
		{
			if (!IsSet($_post["Activo"])) {
				$Activo = 1;
			} else {
				$Activo = $_post["Activo"];
			}
			if ($Activo!="0" And $Activo!="1"){
				return 'ERROR: Valor invalido de Activo';
			}
			$sql = "Call SPWS_AgregaTransporte ('" . $_post['Cve_Vehiculo'] . "','"
					.utf8_decode($_post['Descripcion'])."','" 
					.utf8_decode($_post['Placas'])."','"
					.utf8_decode($_post['NoEconomico'])."','"
					.utf8_decode($_post['Almacen'])."',"
					.utf8_decode($_post['Alto']).","
					.utf8_decode($_post['Ancho']).","
					.utf8_decode($_post['Fondo']).","
					.utf8_decode($_post['Cap_Carga']).","
					.$Activo.");";
			$rs = mysqli_query(\db2(), $sql) or die(mysqli_error(\db2()));
			return "Guardado";
		}
		catch(PDOException $e) 
		{
			return 'ERROR: ' . $e->getMessage();
		}
	}

	function actualizarTransporte( $data )
	{
		$sql = sprintf('UPDATE ' . self::TABLE . ' SET Nombre = :Nombre, Placas = :Placas, cve_cia = :cve_cia, id_almac = :almacen, tipo_transporte = :tipo_transporte where ID_Transporte = :ID_Transporte');
		$this->save = \db()->prepare($sql);
		$this->save->bindValue( ':ID_Transporte', $data['ID_Transporte'], \PDO::PARAM_STR );
		$this->save->bindValue( ':Nombre', $data['Nombre'], \PDO::PARAM_STR );
		$this->save->bindValue( ':Placas', $data['Placas'], \PDO::PARAM_STR );
		$this->save->bindValue( ':cve_cia', $data['cve_cia'], \PDO::PARAM_STR );
		$this->save->bindValue( ':tipo_transporte', $data['tipo_transporte'], \PDO::PARAM_STR );
		$this->save->bindValue( ':almacen', $data['almacen'], \PDO::PARAM_STR );
		$this->save->execute();
    }

	function borrarTransporte( $data )
	{
          $sql = 'UPDATE ' . self::TABLE . 'SET Activo = 0 WHERE ID_Transporte = ?';
          $this->save = \db()->prepare($sql);
          $this->save->execute( array($data['ID_Transporte']) );
	}
	  
	function exist($clave_transporte)
	{
		$sql = sprintf('SELECT * FROM t_transporte WHERE ID_Transporte = ?',self::TABLE);
		$sth = \db()->prepare( $sql );
		$sth->setFetchMode( \PDO::FETCH_CLASS, '\Transporte\Transporte' );
		$sth->execute( array( $clave_transporte ) );
		$this->data = $sth->fetch();
		if(!$this->data)
			return false; 
		else 
			return true;
    }
	
	function existNombre($Nombre)
	{
		$sql = sprintf('SELECT * FROM t_transporte WHERE Nombre =  ?',self::TABLE);
		$sth = \db()->prepare( $sql );
		$sth->setFetchMode( \PDO::FETCH_CLASS, '\Transporte\Transporte' );
		$sth->execute( array( $Nombre ) );
		$this->data = $sth->fetch();
		if(!$this->data)
			return false; 
		else 
			return true;
    }

	function recoveryTransporte( $data )
	{
		$sql = "UPDATE " . self::TABLE . " SET Activo = 1 WHERE  ID_Transporte='".$data['ID_Transporte']."';";
		$this->delete = \db()->prepare($sql);
		$this->delete->execute( array($data['ID_Transporte']) );
    }
  }
