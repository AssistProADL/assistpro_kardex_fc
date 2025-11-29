<?php


// ===== Clases legacy embebidas para módulo Manufactura =====

namespace AlmacenP;


class AlmacenP
{


    const TABLE = 'c_almacenp';

    var $identifier;


    public function __construct($id = false, $key = false)
    {


        if ($id) {

            $this->id = (int)$id;

        }


        if ($key) {


            $sql = sprintf('

          SELECT

            id

          FROM

            %s

          WHERE

            id = ?

        ',

                self::TABLE

            );


            $sth = \db()->prepare($sql);

            $sth->setFetchMode(\PDO::FETCH_CLASS, '\Almacen\Almacen');

            $sth->execute(array($key));


            $almacenp = $sth->fetch();


            $this->id = $almacenp->id;


        }


    }


    private function load()
    {


        $sql = sprintf('

        SELECT

          *

        FROM

          %s

        WHERE

          id = ?

      ',

            self::TABLE

        );


        $sth = \db()->prepare($sql);

        $sth->setFetchMode(\PDO::FETCH_CLASS, '\Almacen\Almacen');

        $sth->execute(array($this->id));


        $this->data = $sth->fetch();


    }


    function exist($clave)
    {


        $sql = sprintf('

        SELECT

          *

        FROM

          %s

        WHERE

          clave = ?

      ',

            self::TABLE

        );


        $sth = \db()->prepare($sql);


        $sth->setFetchMode(\PDO::FETCH_CLASS, '\AlmacenP\AlmacenP');

        $sth->execute(array($clave));


        $this->data = $sth->fetch();


        if (!$this->data)

            return false;

        else

            return true;

    }


    function getAll($filtro = "")
    {

        $id_user = $_SESSION['id_user'];
        if ($filtro == 'traslado')
            $sql = "SELECT * FROM " . self::TABLE . " WHERE Activo=1";
        else if ($_SESSION['es_cliente'] == 1)
            $sql = "SELECT * FROM " . self::TABLE . " WHERE Activo=1 {$filtro}";
        else
            $sql = '
        SELECT c_almacenp.* FROM ' . self::TABLE . ' 
      LEFT JOIN t_usu_alm_pre ON c_almacenp.clave = t_usu_alm_pre.cve_almac 
      WHERE Activo=1 AND id_user = ' . $id_user . '
      ' . $filtro . '

      ';
//echo $sql; exit;
        /*
      UNION

      SELECT a.* FROM c_almacenp a
      LEFT JOIN trel_us_alm t ON t.cve_almac = a.clave
      LEFT JOIN c_usuario u ON u.cve_usuario = t.cve_usuario
      WHERE a.Activo=1 AND u.id_user = '.$id_user.' AND a.interno = 1
*/

        //$sql = 'SELECT * FROM ' . self::TABLE . ' WHERE Activo=1';
        $sth = \db()->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, '\AlmacenP\AlmacenP');
        $sth->execute();

        return $sth->fetchAll();

    }


    function __get($key)
    {


        switch ($key) {

            case 'id':

            case 'clave':

            case 'nombre':

            case 'rut':

            case 'codigopostal':

            case 'distrito':

            case 'telefono':

            case 'contacto':

            case 'correo_electronico':

            case 'comentarios':

            case 'cve_talmacen':

            case 'BL':


                $this->load();

                return @$this->data->$key;

            default:

                return $this->key;

        }


    }


    function save($_post)

    {

        if (!$_post['clave']) {
            throw new \ErrorException('clave is required.');
        }


        $sql = sprintf('

        INSERT INTO

          ' . self::TABLE . '

        SET

       clave= :clave

      ,nombre= :nombre

      ,rut= :rut 

      ,codigopostal= :codigopostal 

      ,distrito= :distrito 

      ,direccion= :direccion 

      ,telefono= :telefono 

      ,contacto= :contacto 

      ,correo = :correo 

      ,comentarios= :comentarios 

      ,cve_talmacen= :cve_talmacen 

      ,cve_cia= :cve_cia

      ,BL = :code

      ,BL_Pasillo = :pasillo

      ,BL_Rack = :rack

      ,BL_Nivel = :nivel

      ,BL_Seccion =:seccion

      ,BL_Posicion = :posicion

      ,longitud= :longitud

      ,latitud= :latitud

      ,interno= :interno

      ,tipolp_traslado= :tipolp_traslado 
      ');


        $this->save = \db()->prepare($sql);


        //$this->save->bindValue( ':ID_Proveedor', $this->ID_Proveedor, \PDO::PARAM_STR );

        $this->save->bindValue(':clave', $_post['clave'], \PDO::PARAM_STR);

        $this->save->bindValue(':rut', $_post['rut'], \PDO::PARAM_STR);

        $this->save->bindValue(':nombre', $_post['nombre'], \PDO::PARAM_STR);

        $this->save->bindValue(':codigopostal', $_post['codigopostal'], \PDO::PARAM_STR);

        $this->save->bindValue(':distrito', $_post['distrito'], \PDO::PARAM_STR);

        $this->save->bindValue(':telefono', $_post['telefono'], \PDO::PARAM_STR);

        $this->save->bindValue(':contacto', $_post['contacto'], \PDO::PARAM_STR);

        $this->save->bindValue(':correo', $_post['correo'], \PDO::PARAM_STR);

        $this->save->bindValue(':comentarios', $_post['comentarios'], \PDO::PARAM_STR);

        $this->save->bindValue(':direccion', $_post['direccion'], \PDO::PARAM_STR);

        $this->save->bindValue(':cve_talmacen', $_post['cve_talmacen'], \PDO::PARAM_STR);

        $this->save->bindValue(':cve_cia', $_post['cve_cia'], \PDO::PARAM_STR);


        $this->save->bindValue(':code', $_post['code'], \PDO::PARAM_STR);

        $this->save->bindValue(':pasillo', $_post['pasillo'], \PDO::PARAM_STR);

        $this->save->bindValue(':rack', $_post['rack'], \PDO::PARAM_STR);

        $this->save->bindValue(':nivel', $_post['nivel'], \PDO::PARAM_STR);

        $this->save->bindValue(':seccion', $_post['seccion'], \PDO::PARAM_STR);

        $this->save->bindValue(':posicion', $_post['posicion'], \PDO::PARAM_STR);


        $this->save->bindValue(':longitud', $_post['longitud'], \PDO::PARAM_STR);

        $this->save->bindValue(':latitud', $_post['latitud'], \PDO::PARAM_STR);

        $this->save->bindValue(':interno', $_post['almacen_local'], \PDO::PARAM_STR);

        $this->save->bindValue(':tipolp_traslado', $_post['tipolp_traslado'], \PDO::PARAM_STR);


        $this->save->execute();

    }


    /*function password( $data ) {



      if( !$data['password'] ) { throw new \ErrorException( 'Unfortuantly you wont get far without a password.' ); }



      $sql = sprintf('

        UPDATE

          ' . self::TABLE . '

        SET

          password = :password

        WHERE

          id_user = ' . $this->id_user . '

      ');



      $this->save = \db()->prepare($sql);



      $password = password_hash( $data['password'], PASSWORD_BCRYPT, array( 'cost' => 11 ) );



      $this->save->bindValue( ':password', $password, \PDO::PARAM_STR );

      $this->save->execute();



    }*/


    function actualizarAlmacenP($_post)
    {


        //echo var_dump($_post);

        //die();


        if (!$_post['clave']) {
            throw new \ErrorException('clave is required.');
        }


        $sql = sprintf('

            UPDATE 

              ' . self::TABLE . '

            SET

            clave= :clave

            ,nombre= :nombre

            ,rut= :rut 

            ,codigopostal= :codigopostal 

            ,distrito= :distrito 

            ,telefono= :telefono 

            ,contacto= :contacto 

            ,correo = :correo 

            ,comentarios= :comentarios 

            ,cve_talmacen= :cve_talmacen 

            ,direccion= :direccion 

            ,cve_cia= :cve_cia 

            ,BL = :code

            ,BL_Pasillo = :pasillo

            ,BL_Rack = :rack

            ,BL_Nivel = :nivel

            ,BL_Seccion =:seccion

            ,BL_Posicion = :posicion

            ,longitud = :longitud

            ,latitud = :latitud

            ,interno= :interno

            ,tipolp_traslado =:tipolp_traslado 


    		where id= :id



          ');


        $this->save = \db()->prepare($sql);


        //$this->save->bindValue( ':ID_Proveedor', $this->ID_Proveedor, \PDO::PARAM_STR );

        $this->save->bindValue(':clave', $_post['clave'], \PDO::PARAM_STR);

        $this->save->bindValue(':nombre', $_post['nombre'], \PDO::PARAM_STR);

        $this->save->bindValue(':rut', $_post['rut'], \PDO::PARAM_STR);

        $this->save->bindValue(':codigopostal', $_post['codigopostal'], \PDO::PARAM_STR);

        $this->save->bindValue(':distrito', $_post['distrito'], \PDO::PARAM_STR);

        $this->save->bindValue(':telefono', $_post['telefono'], \PDO::PARAM_STR);

        $this->save->bindValue(':contacto', $_post['contacto'], \PDO::PARAM_STR);

        $this->save->bindValue(':correo', $_post['correo'], \PDO::PARAM_STR);

        $this->save->bindValue(':comentarios', $_post['comentarios'], \PDO::PARAM_STR);

        $this->save->bindValue(':direccion', $_post['direccion'], \PDO::PARAM_STR);


        $this->save->bindValue(':cve_talmacen', $_post['cve_talmacen'], \PDO::PARAM_STR);

        $this->save->bindValue(':cve_cia', $_post['cve_cia'], \PDO::PARAM_STR);


        $this->save->bindValue(':code', $_post['code'], \PDO::PARAM_STR);

        $this->save->bindValue(':pasillo', $_post['pasillo'], \PDO::PARAM_STR);

        $this->save->bindValue(':rack', $_post['rack'], \PDO::PARAM_STR);

        $this->save->bindValue(':nivel', $_post['nivel'], \PDO::PARAM_STR);

        $this->save->bindValue(':seccion', $_post['seccion'], \PDO::PARAM_STR);

        $this->save->bindValue(':posicion', $_post['posicion'], \PDO::PARAM_STR);


        $this->save->bindValue(':longitud', $_post['longitud'], \PDO::PARAM_STR);

        $this->save->bindValue(':latitud', $_post['latitud'], \PDO::PARAM_STR);

        $this->save->bindValue(':interno', $_post['almacen_local'], \PDO::PARAM_STR);

        $this->save->bindValue(':tipolp_traslado', $_post['tipolp_traslado'], \PDO::PARAM_STR);

        $this->save->bindValue(':id', $_post['id'], \PDO::PARAM_STR);

        $this->save->execute();

    }


    function borrarAlmacenP($data)
    {

        $sql = '

        UPDATE

          ' . self::TABLE . '

        SET

          Activo = 0

        WHERE

          id = ?

      ';

        $this->save = \db()->prepare($sql);

        $this->save->execute(array(

            $data['id']

        ));

    }


    function loadZonas($clave)
    {


        $sql = '

       SELECT 

        p.nombre as almacen, 

        a.des_almac as nombre_zona, 

            a.cve_almac as clave_zona,

          a.clave_almacen as clave_zona2

        FROM c_almacenp p, c_almacen a 

        where a.cve_almacenp = p.id 

        and p.clave = "' . $clave . '" 

        and p.activo = 1 and a.activo =1 order by clave_zona asc;

    ';


        $sth = \db()->prepare($sql);

        $sth->execute();

        return $sth->fetchAll();

    }


    function loadProveedores($clave_almacen)
    {


        $sql = '

          SELECT 

                  c_proveedores.Nombre,

                  ts_existenciapiezas.ID_Proveedor

          FROM ts_existenciapiezas

          inner join c_proveedores on c_proveedores.ID_Proveedor = ts_existenciapiezas.ID_Proveedor

          where cve_almac = (select id from c_almacenp where clave = "' . $clave_almacen . '")

          group by ts_existenciapiezas.ID_Proveedor

          order by c_proveedores.Nombre

                ';

        $sth = \db()->prepare($sql);

        $sth->execute();

        return $sth->fetchAll();

    }


    function tieneEmpresa()
    {

        $sql = sprintf('

            SELECT

              cr.*

            FROM

              c_almacenp cr, c_compania cc

            WHERE

              cc.cve_cia = cr.cve_cia

              and cr.Activo = 1 and cc.Activo = 1

              and cr.id = ?

            ',

            self::TABLE

        );


        $sth = \db()->prepare($sql);

        $sth->setFetchMode(\PDO::FETCH_CLASS, '\AlmacenP\AlmacenP');

        $sth->execute(array($this->id));


        $this->data = $sth->fetch();


    }

    /**
     * @param {object}.
     * @author Ricardo Delgado.
     * Actualiza el almacen predeterminado para el usuario.
     */

    function actualizarAlmaPre($data)
    {

        $sql = 'DELETE FROM t_usu_alm_pre WHERE id_user =:id';

        $delete = \db()->prepare($sql);

        $delete->bindValue(':id', $data['idUser'], \PDO::PARAM_STR);

        $delete->execute();


        $sql = 'INSERT INTO t_usu_alm_pre SET id_user= :id, cve_almac= :alma';

        $insert = \db()->prepare($sql);

        $insert->bindValue(':id', $data['idUser'], \PDO::PARAM_STR);

        $insert->bindValue(':alma', $data['alma'], \PDO::PARAM_STR);

        $insert->execute();

    }


    /**
     * @param {object} id del usuario.
     * @returns {json}.
     * @author Ricardo Delgado.
     * Obtiene el almacen predeterminado del usuario.
     */

    function getAlmaPre($data)
    {

        $sql = 'SELECT 

                  alm.id, alm.clave

                FROM t_usu_alm_pre alm_pre, c_almacenp alm 

                WHERE 

                  alm_pre.cve_almac = alm.clave and id_user = :id';

        $search = \db()->prepare($sql);

        $search->bindValue(':id', $data['idUser'], \PDO::PARAM_STR);

        $search->execute();

        return $search->fetchAll();

    }


    function getUser($data)

    {

        $sql = 'SELECT id_user, cve_usuario, nombre_completo

              FROM c_usuario

              WHERE id_user = :id';

        $search = \db()->prepare($sql);

        $search->bindValue(':id', $data['idUser'], \PDO::PARAM_STR);

        $search->execute();

        return $search->fetchAll();

    }


    function isInUse()
    {

        /* Condiciones a comprobar

      * No tener Entradas Pendientes th_aduana -> (Cve_Almac [clave], status = C|I [Pendiente, Recibiendo])

      * Surtidos Pendientes (Descartado por Efrain)

      * Embarques Pendientes -> Pedidos del embarque -> th_pedido (cve_almac [id], status = C)

      * Inventarios Pendientes th_inventario -> (cve_almacen [clave], status = A)

      * Sin productos en sus ubicaciones   V_ExistenciaGral -> cve_almac [clave], c_ubicacion -> cve_almac [clave zona almacenaje]

      * Sin productos en zona de almacenaje _ExistenciaGral -> cve_almac [clave], c_ubicacion -> cve_almac [clave zona almacenaje]

      * Sin productos en zona de recepcion td_entalmacen -> cve_ubicacion [ubicacion de recepcion, -> tubicacionesretencion -> cve_ubicacion, c_almacenp[id]]

      * Sin productos en zona de embarque th_entalmacen -> Cve_Almac[clave], STATUS -> C

      * c_articulo -> cve_almac [id]

      */

        $sql = "SELECT

                    (SELECT COUNT(ID_Aduana) FROM th_aduana WHERE Cve_Almac = a.clave AND (status = 'I' || status = 'C')) AS entradas,

                    (SELECT COUNT(Fol_folio) FROM th_pedido WHERE cve_almac = a.id AND status = 'C') AS embarques,

                    (SELECT COUNT(ID_Inventario) FROM th_inventario WHERE cve_almacen = a.clave AND status = 'A') AS inventarios,

                    (SELECT COUNT(DISTINCT cve_articulo) FROM V_ExistenciaGral WHERE cve_almac = a.id OR cve_ubicacion IN (SELECT idy_ubica FROM c_ubicacion WHERE cve_almac IN (SELECT clave_almacen FROM c_almacen WHERE cve_almacenp = a.id))) AS ubicaciones_zonas,

                    (SELECT COUNT(DISTINCT fol_folio) FROM td_entalmacen WHERE cve_ubicacion IN (SELECT cve_ubicacion FROM tubicacionesretencion WHERE cve_almacp = a.id AND Activo = 1)) AS recepcion,

                    (SELECT COUNT(Fol_Folio) FROM th_entalmacen WHERE Cve_Almac = a.clave AND STATUS = 'C') AS embarque

              FROM c_almacenp a

              WHERE id = ?";

        $result = \db()->prepare($sql);

        $result->execute(array($this->id));

        $data = $result->fetch(\PDO::FETCH_ASSOC);

        $total = 0;

        foreach ($data as $exists) {

            $total += intval($exists);

        }

        return $total > 0;

    }


    function recovery($data)
    {


        $sql = "UPDATE c_almacenp SET Activo = 1 WHERE  id='" . $data['id'] . "';";

        $this->delete = \db()->prepare($sql);

        $this->delete->execute(array(

            $data['id']

        ));

    }


}


namespace Companias;

class Companias
{

    const TABLE    = 'c_almacen';
    const COMPANIA = 'c_compania';
    const TIPO     = 'c_tipocia';

    var $identifier;
    var $clavecomp;

    public function __construct($cve_almac = false, $key = false)
    {

        if ($cve_almac) {
            $this->cve_almac = (int)$cve_almac;
        }

        if ($key) {

            $sql = sprintf('
          SELECT
            cve_almac
          FROM
            %s
          WHERE
            cve_almac = ?
        ',
                self::TABLE
            );

            $sth = \db()->prepare($sql);
            $sth->setFetchMode(\PDO::FETCH_CLASS, '\Companias\Companias');
            $sth->execute(array($key));

            $almacen = $sth->fetch();

            $this->cve_almac = $almacen->cve_almac;

        }

    }

    private function load()
    {

        $sql = sprintf('
        SELECT
          *
        FROM
          c_compania
        WHERE
          cve_cia = ?
      ',
            self::TABLE
        );

        $sth = \db()->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, '\Companias\Companias');
        $sth->execute(array($this->cve_cia));

        $this->data = $sth->fetch();

    }

    function exist($clave_empresa)
    {

        $sql = sprintf('
        SELECT
          *
        FROM
          c_compania
        WHERE
          clave_empresa = ?
      ',
            self::TABLE
        );

        $sth = \db()->prepare($sql);

        $sth->setFetchMode(\PDO::FETCH_CLASS, '\Companias\Companias');
        $sth->execute(array($clave_empresa));

        $this->data = $sth->fetch();

        if (!$this->data)
            return false;
        else
            return true;
    }


    function loadcomp()
    {

        $sql = sprintf('
        SELECT
          *
        FROM
          c_compania
        WHERE
          cve_cia = ?
      ',
            self::TABLE
        );

        $sth = \db()->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, '\Companias\Companias');
        $sth->execute(array($this->cve_cia));

        $this->data = $sth->fetch();

    }

    function getAll()
    {

        $sql = '
        SELECT
          *
        FROM
          c_compania
		  where Activo=1
      ';

        $sth = \db()->prepare($sql);
        //  $sth->setFetchMode( \PDO::FETCH_CLASS, '\Companias\Companias' );
        $sth->execute();

        return $sth->fetchAll();

    }

    function getPoblacion()
    {

        $sql = '
        SELECT
          *
        FROM
          c_poblacion
      ';

        $sth = \db()->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, '\Companias\Companias');
        @$sth->execute(array(cve_pobla));

        return $sth->fetchAll();

    }

    function borrarCompania($data)
    {
        $sql = '
        UPDATE
          ' . self::COMPANIA . '
        SET
          Activo = 0
        WHERE
          cve_cia = ?
      ';
        $this->save = \db()->prepare($sql);
        $this->save->execute(array(
            $data['cve_cia']
        ));
    }

    function getCompania()
    {

        $sql = '
        SELECT
          *
        FROM
          c_tipocia
		  where Activo=1
      ';

        $sth = \db()->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, '\Companias\Companias');
        @$sth->execute(array(cve_cia));

        return $sth->fetchAll();

    }

    function getComp($es_transportadora = 0)
    {

        $sql_transportadora = "";
        /*
        if($es_transportadora == 1)
        {
          //$sql_transportadora = " AND cve_tipcia IN (SELECT cve_tipcia FROM c_tipocia WHERE es_transportista = 1) ";
           $sql_transportadora = " AND es_transportista = 1 ";
        }
*/
        $sql = '
        SELECT
          *
        FROM
          c_compania
		  where Activo=1  
      ' . $sql_transportadora;

        $sth = \db()->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, '\Companias\Companias');
        @$sth->execute(array(cve_cia));

        return $sth->fetchAll();

    }

    function getNCompania()
    {

        $sql = sprintf('
        SELECT
          *
        FROM
          c_compania
        WHERE
          cve_cia = ?
      ',
            self::COMPANIA
        );

        $sth = \db()->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, '\Companias\Companias');
        $sth->execute(array($this->clavecomp));

        $this->data = $sth->fetch();

    }

    public function getTipos()
    {
        $sql = 'SELECT cve_cia, des_tipcia FROM ' . self::TIPO . ' WHERE Activo = 1';
        $sth = \db()->prepare($sql);
        $sth->execute();
        return $sth->fetchAll();
    }

    function __get($key)
    {

        switch ($key) {
            case 'cve_cia':
                $this->load();
                return @$this->data->$key;
            default:
                return $this->key;
        }

    }

    function save($data)
    {

        $sql2 = "INSERT INTO c_compania
              SET
          clave_empresa = '{$data['cve_empresa']}'
        , distrito = '{$data['distrito']}'
        , cve_tipcia = '{$data['cve_tipcia']}'
        , des_cia = '{$data['des_cia']}'
        , des_rfc = '{$data['des_rfc']}'
        , des_direcc = '{$data['des_direcc']}'
        , des_cp = '{$data['des_cp']}'
        , des_telef = '{$data['des_telef']}'
        , des_contacto = '{$data['des_contacto']}'
        , des_email = '{$data['des_email']}'
        , des_observ = '{$data['des_observ']}'
		    , imagen = '" . '/img/compania/' . $data['imagen'] . "';";
        //,es_transportista= '{$data['transportista']}'


        /* $sql = sprintf('
        INSERT INTO
          ' . self::COMPANIA . '
        SET
          clave_empresa = :clave_empresa
        , distrito = :distrito
        , cve_tipcia = :tipocia
        , cve_cia = :cve_cia
        , des_cia = :des_cia
        , des_rfc = :des_rfc
        , des_direcc = :des_direcc
        , des_cp = :des_cp
        , des_telef = :des_telef
        , des_contacto = :des_contacto
        , des_email = :des_email
        , des_observ = :des_observ
		, imagen = :"/img/compania/'.$data["imagen"].'"
      ');*/

        $this->save = \db()->prepare($sql2);
        $this->save->execute();
        /*
      $codDaneSql = \db()->prepare("SELECT COUNT(*) as existe  FROM c_dane WHERE cod_municipio='{$data['des_cp']}'");
      $codDaneSql->execute();
      $codDane = $codDaneSql->fetchAll(PDO::FETCH_ASSOC);
      $existe = $codDane[0]['existe'];
*/
        //if(!$existe)
        //{
        $sql3 = "INSERT IGNORE INTO c_dane
                  SET
              cod_municipio = '{$data['des_cp']}'
            , departamento = '{$data['txtDepart']}'
            , des_municipio = '{$data['txtMunicipio']}'";

        $this->save = \db()->prepare($sql3);
        $this->save->execute();
        //}

        /*
      //$this->save->bindValue( ':ID_Proveedor', $this->ID_Proveedor, \PDO::PARAM_STR );
      $this->save->bindValue( ':clave_empresa', $data['cve_empresa'], \PDO::PARAM_STR );
      $this->save->bindValue( ':distrito', $data['distrito'], \PDO::PARAM_STR );
      $this->save->bindValue( ':tipocia', $data['cve_tipcia'], \PDO::PARAM_STR );
      $this->save->bindValue( ':cve_cia', $data['cve_cia'], \PDO::PARAM_STR );
      $this->save->bindValue( ':des_cia', $data['des_cia'], \PDO::PARAM_STR );
      $this->save->bindValue( ':des_rfc', $data['des_rfc'], \PDO::PARAM_STR );
      $this->save->bindValue( ':des_direcc', $data['des_direcc'], \PDO::PARAM_STR );
      $this->save->bindValue( ':des_cp', $data['des_cp'], \PDO::PARAM_STR );
      $this->save->bindValue( ':des_telef', $data['des_telef'], \PDO::PARAM_STR );
      $this->save->bindValue( ':des_contacto', $data['des_contacto'], \PDO::PARAM_STR );
      $this->save->bindValue( ':des_email', $data['des_email'], \PDO::PARAM_STR );
      $this->save->bindValue( ':des_observ', $data['des_observ'], \PDO::PARAM_STR );
	    $this->save->bindValue( ':imagen', $data['imagen'], \PDO::PARAM_STR );
*/

    }

    /*function password( $data ) {

      if( !$data['password'] ) { throw new \ErrorException( 'Unfortuantly you wont get far without a password.' ); }

      $sql = sprintf('
        UPDATE
          ' . self::TABLE . '
        SET
          password = :password
        WHERE
          id_user = ' . $this->id_user . '
      ');

      $this->save = \db()->prepare($sql);

      $password = password_hash( $data['password'], PASSWORD_BCRYPT, array( 'cost' => 11 ) );

      $this->save->bindValue( ':password', $password, \PDO::PARAM_STR );
      $this->save->execute();

    }*/

    function actualizarCompa($data)
    {
        //var_dump($data); exit;
        $sql = '
        UPDATE
          ' . self::COMPANIA . '
        SET
          clave_empresa = ?
        , distrito = ?
        , cve_cia = ?
        , cve_tipcia = ?
        , des_cia = ?
        , des_rfc = ?
        , des_direcc = ?
        , des_cp = ?
        , des_telef = ?
        , des_contacto = ?
        , des_email = ?												
        , des_observ = ?  
		    , imagen = "/' . $data["imagen"] . '"  		
        WHERE
          cve_cia = ?
      ';
        //, es_transportista = ?

        $this->save = \db()->prepare($sql);
        $this->save->execute(array(
            $data['cve_empresa']
            , $data['distrito']
            , $data['cve_cia']
            , $data['cve_tipcia']
            , $data['des_cia']
            , $data['des_rfc']
            , $data['des_direcc']
            , $data['des_cp']
            , $data['des_telef']
            , $data['des_contacto']
            , $data['des_email']
            , $data['des_observ']
            , $data['cve_cia']
        ));
        //#, $data['transportista']
    }

    function tieneAlmacen()
    {
        $sql = sprintf('
        SELECT
          r.*
        FROM
          c_compania cr, c_almacenp r
        WHERE
          cr.cve_cia = r.cve_cia 
          and cr.Activo = 1 and r.Activo = 1
          and cr.cve_cia = ?
      ',
            self::TABLE
        );

        $sth = \db()->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, '\Companias\Companias');
        $sth->execute(array($this->cve_cia));

        $this->data = $sth->fetch();

    }


    function recovery($data)
    {

        $sql = "UPDATE c_compania SET Activo = 1 WHERE  cve_cia='" . $data['cve_cia'] . "';";
        $this->delete = \db()->prepare($sql);
        $this->delete->execute(array(
            $data['cve_cia']
        ));
    }
}


namespace Usuarios;

class Usuarios
{

    const TABLE          = 'c_usuario';
    const VENDEDOR       = 't_vendedores';
    const DESTINATARIOS  = 'c_destinatarios';
    const TABLE_Profiles = 't_perfilesusuarios';
    const TABLE_P_T      = 't_usuariosperfil';

    var $identifier;

    public function __construct($id_user = false, $key = false)
    {

        if ($id_user) {
            $this->id_user = (int)$id_user;
        }

        if ($key) {

            $sql = sprintf('
          SELECT
            id_user
          FROM
            %s
          WHERE
            id_user = ?
        ',
                self::TABLE
            );

            $sth = \db()->prepare($sql);
            $sth->setFetchMode(\PDO::FETCH_CLASS, '\Usuarios\Usuarios');
            $sth->execute(array($key));

            $id_user = $sth->fetch();

            $this->id_user = $id_user->id_user;

        }

    }

    private function load()
    {

        $sql = sprintf('
    SELECT
      *
    FROM
      %s
    WHERE
      id_user = ?
  ',
            self::TABLE
        );

        $sth = \db()->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, '\Usuarios\Usuarios');
        $sth->execute(array($this->id_user));

        $this->data = $sth->fetch();

    }

    function exist($cve_usuario)
    {

        $sql = sprintf('
        SELECT
          *
        FROM
          %s
        WHERE
          cve_usuario = ?
      ',
            self::TABLE
        );

        $sth = \db()->prepare($sql);

        $sth->setFetchMode(\PDO::FETCH_CLASS, '\Almacen\Almacen');
        $sth->execute(array($cve_usuario));

        $this->data = $sth->fetch();

        if (!$this->data)
            return false;
        else
            return true;
    }

    private function loadPerRoles()
    {
        $sql1 = "select ID_PERFIL, PER_NOMBRE, cve_cia from t_perfilesusuarios where ID_PERFIL not in(select ID_PERFIL from v_permisosusuario where cve_usuario='" . $this->cve_usuario . "');";
        $rs1 = mysqli_query(\db2(), $sql1) or die("Error description: " . mysqli_error(\db2()));
        $arr1 = array();
        while ($row1 = mysqli_fetch_array($rs1)) {
            $arr1[] = $row1;
        }
        $this->dataOrigen = $arr1;

        $sql2 = "select distinct ID_PERFIL,	DES_PERFIL as PER_NOMBRE,cve_cia from v_permisosusuario	where cve_usuario='" . $this->cve_usuario . "';";
        $rs2 = mysqli_query(\db2(), $sql2) or die("Error description: " . mysqli_error(\db2()));
        $arr2 = array();
        while ($row2 = mysqli_fetch_array($rs2)) {
            $arr2[] = $row2;
        }
        $this->dataDestino = $arr2;
    }

    function getAll($surtidores = 0)
    {
        $sql_surtidores = "";
        if ($surtidores == 1)
            $sql_surtidores = " AND cve_usuario != 'wmsmaster' AND es_cliente = 0";
        $sql = '
        SELECT
          *
        FROM
          ' . self::TABLE . '
		  where Activo=1 ' . $sql_surtidores . '
      ORDER BY nombre_completo
      ';

        $sth = \db()->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, '\Usuarios\Usuarios');
        @$sth->execute(array(id_user));

        return $sth->fetchAll();

    }

    function getAllVendedor($cve_almacen = "")
    {

        $SQL_Almacen = "";
        if ($cve_almacen)
            $SQL_Almacen = " WHERE Id_Vendedor IN (SELECT VendedorId FROM Venta WHERE IdEmpresa = '$cve_almacen') ";

        $sql = '
        SELECT
          *
        FROM
          ' . self::VENDEDOR . $SQL_Almacen;

        $sth = \db()->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, '\Usuarios\Usuarios');
        @$sth->execute(array(cve_Vendedor));

        return $sth->fetchAll();

    }

    function getAllVendedorRutas($cve_almacen = "")
    {

        $sql = "SELECT DISTINCT tv.cve_usuario AS cve_Vendedor, tv.nombre_completo AS Nombre
                FROM c_usuario tv
                LEFT JOIN trel_us_alm ru ON ru.cve_usuario = tv.cve_usuario
                WHERE ru.cve_almac = '$cve_almacen' AND tv.es_cliente = 3";

        $sth = \db()->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, '\Usuarios\Usuarios');
        @$sth->execute(array(cve_Vendedor));

        return $sth->fetchAll();

    }

    function getAllVendedorPlaneacion($id_almacen)
    {

        $sql = "SELECT DISTINCT tv.cve_usuario AS cve_Vendedor, tv.nombre_completo AS Nombre
                FROM c_usuario tv
                INNER JOIN Rel_Ruta_Agentes ra ON ra.cve_vendedor = tv.id_user
                INNER JOIN t_ruta r ON r.ID_Ruta = ra.cve_ruta AND r.cve_almacenp = $id_almacen";

        $sth = \db()->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, '\Usuarios\Usuarios');
        @$sth->execute(array(cve_Vendedor));

        return $sth->fetchAll();

    }


    function getAllDestinatarios()
    {

        $sql = '
        SELECT
          *
        FROM
          ' . self::DESTINATARIOS . '
      ';

        $sth = \db()->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, '\Usuarios\Usuarios');
        $sth->execute(array(Cve_Dest));

        return $sth->fetchAll();

    }

    function __get($key)
    {

        switch ($key) {
            case 'id_user':
                $this->load();
                return @$this->data->$key;
            default:
                return $this->key;
        }

    }

    function __getPerRoles($key)
    {

        switch ($key) {
            case 'cve_usuario':
                $this->loadPerRoles();
                return @$this->data->$key;
            default:
                return $this->key;
        }

    }

    function saveUser($_post)
    {

        $usuario_web_apk = 'AW';

        if ($_post['es_cliente'] == 'AW') {
            $_post['es_cliente'] = 0;
        } else if ($_post['es_cliente'] == 'A') {
            $_post['es_cliente'] = 0;
            $usuario_web_apk = 'A';
        } else
            $usuario_web_apk = 'W';


        if ($_post['es_cliente'] != 0 && $_post['es_cliente'] != 3) $_post['perfil'] = 1;
        if ($_post['almacen_asignado'] == "0") $_post['almacen_asignado'] = "";
        if ($_post['es_cliente'] == 0) {
            $_post['cliente_asignado'] = "";
            $_post['proveedor_asignado'] = "";
        }

        $sql = "INSERT IGNORE INTO " . self::TABLE . " (cve_usuario, cve_cia,nombre_completo,email,perfil, des_usuario, fec_ingreso, pwd_usuario, ban_usuario, status, Activo, timestamp, identifier, image_url, es_cliente, cve_almacen, cve_cliente, cve_proveedor, web_apk)";
        $sql .= "Values (";
        $sql .= "'" . $_post['cve_usuario'] . "',";
        $sql .= "'" . $_post['cve_cia'] . "',";
        $sql .= "'" . $_post['nombre_completo'] . "',";
        $sql .= "'" . $_post['email'] . "',";
        $sql .= "'" . $_post['perfil'] . "',";
        $sql .= "'" . $_post['des_usuario'] . "',";
        $sql .= "NOW(),";
        $sql .= "'" . $_post['pwd_usuario'] . "',";
        $sql .= "'1','',";
        $sql .= "'1', '1480436303','',";
        $sql .= "'/img/imageperfil/" . $_post['imagen'] . "', " . $_post['es_cliente'] . ", '" . $_post['almacen_asignado'] . "', '" . $_post['cliente_asignado'] . "', '" . $_post['proveedor_asignado'] . "', '" . $usuario_web_apk . "')";

        $this->save = \db()->prepare($sql);
        $this->save->execute();

        $sql = "INSERT IGNORE INTO t_usuariosperfil (ID_PERFIL, cve_usuario, Activo)";
        $sql .= "VALUES (";
        $sql .= "'" . $_post['perfil'] . "',";
        $sql .= "'" . $_post['cve_usuario'] . "',";
        $sql .= "'1')";
        $this->save = \db()->prepare($sql);
        $this->save->execute();


        if ($_post['es_cliente'] != 0) {
            $sql = 'SELECT id_user, cve_almacen FROM c_usuario WHERE cve_usuario = "' . $_post['cve_usuario'] . '" ';

            $res_user = $this->getArraySQL($sql);

            $id_user = $res_user[0]["id_user"];
            $cve_almacen = $res_user[0]["cve_almacen"];

            $sql = "INSERT IGNORE INTO t_usu_alm_pre (id_user, cve_almac)";
            $sql .= "VALUES (";
            $sql .= "'" . $id_user . "',";
            $sql .= "'" . $_post['almacen_asignado'] . "')";

            $this->save = \db()->prepare($sql);
            $this->save->execute();

            if ($_post['es_cliente'] == 3) {
                $sql = "INSERT IGNORE INTO t_vendedores(Id_Vendedor, Nombre, Cve_Vendedor, Psswd_EDA) ";
                $sql .= "VALUES (";
                $sql .= "'" . $id_user . "',";
                $sql .= "'" . $_post['nombre_completo'] . "',";
                $sql .= "'" . $_post['cve_usuario'] . "',";
                $sql .= "'" . $_post['pwd_usuario'] . "')";

                $this->save = \db()->prepare($sql);
                $this->save->execute();
            }

        }

        $modulo_log = "Administración->Usuarios->Usuarios";
        $usuario_sesion = $_POST['usuario_log'];
        $operacion_log = "El usuario {$usuario_sesion} registró al usuario " . $_post['cve_usuario'];
        $observaciones_log = "";
        $sql_log = "INSERT INTO t_log_operaciones(modulo, usuario, fecha, operacion, dispositivo, observaciones) VALUES ('{$modulo_log}', '{$usuario_sesion}', NOW(), '{$operacion_log}', 'web', '{$observaciones_log}')";
        $this->save = \db()->prepare($sql_log);
        $this->save->execute();

    }


    function addRolUser($data)
    {
        $arr = $_post['arrAdd'];
        foreach ($arr as $id) {
            $sql = "INSERT IGNORE into t_usuariosperfil(ID_PERFIL,cve_usuario) values ('" . $id . "','" . $_post['cve_usuario'] . "');";
            $this->save = \db()->prepare($sql);
            $this->save->execute(array(
                $data['ID_PERFIL']
            ));
        }
    }

    function BorrarRolUser($data)
    {
        $arr = $_post['arrDelete'];
        foreach ($arr as $id) {
            $sql = "Delete from t_usuariosperfil where ID_PERFIL='" . $id . "' and cve_usuario='" . $_post['cve_usuario'] . "';";
            $this->delete = \db()->prepare($sql);
            $this->delete->execute(array(
                $data['ID_PERFIL']
            ));
        }
    }


    function actualizarUser($_post)
    {

        $usuario_web_apk = 'AW';

        if ($_post['es_cliente'] == 'AW') {
            $_post['es_cliente'] = 0;
        } else if ($_post['es_cliente'] == 'A') {
            $_post['es_cliente'] = 0;
            $usuario_web_apk = 'A';
        } else
            $usuario_web_apk = 'W';

        if ($_post['es_cliente'] != 0 && $_post['es_cliente'] != 3) $_post['perfil'] = 1;

        $sql = 'SELECT a.cve_usuario FROM t_usuariosperfil a, c_usuario b WHERE a.cve_usuario = b.cve_usuario and b.id_user = "' . $_post['Clave'] . '" ';

        $validate = $this->getArraySQL($sql);

        if (!empty($validate) && is_array($validate)) {
            $sql = "UPDATE IGNORE t_usuariosperfil SET cve_usuario = '" . $_post['cve_usuario'] . "', ID_PERFIL = '" . $_post['perfil'] . "' WHERE cve_usuario = '" . $validate[0]["cve_usuario"] . "'";
            $this->save = \db()->prepare($sql);
            $this->save->execute();
        } else {
            $sql = "INSERT IGNORE INTO t_usuariosperfil (ID_PERFIL, cve_usuario, Activo)";
            $sql .= "Values (";
            $sql .= "'" . $_post['perfil'] . "',";
            $sql .= "'" . $_post['cve_usuario'] . "',";
            $sql .= "'1')";
            $this->save = \db()->prepare($sql);
            $this->save->execute();
        }

        $sql = 'SELECT id_user, cve_almacen FROM c_usuario WHERE cve_usuario = "' . $_post['cve_usuario'] . '" ';
        $res_user = $this->getArraySQL($sql);
        $id_user = $res_user[0]["id_user"];
        $cve_almacen = $res_user[0]["cve_almacen"];

        if ($_post['es_cliente'] == 0) {
            $_post['almacen_asignado'] = "";
            $sql = "DELETE FROM t_usu_alm_pre WHERE id_user = '{$id_user}' AND cve_almac = '{$cve_almacen}'";
        } else {
            $sql = "UPDATE t_usu_alm_pre SET cve_almac = '" . $_post['almacen_asignado'] . "' WHERE id_user = '" . $id_user . "'";
        }
        $this->save = \db()->prepare($sql);
        $this->save->execute();


        $sql = "
          UPDATE 
          " . self::TABLE . "
           SET 
            cve_usuario = '" . $_post['cve_usuario'] . "',
            cve_cia = '" . $_post['cve_cia'] . "', 
            nombre_completo = '" . $_post['nombre_completo'] . "', 
            email = '" . $_post['email'] . "', 
            perfil = '" . $_post['perfil'] . "', 
            des_usuario = '" . $_post['des_usuario'] . "', 
            pwd_usuario = '" . $_post['pwd_usuario'] . "',
            es_cliente = '" . $_post['es_cliente'] . "',
            cve_almacen = '" . $_post['almacen_asignado'] . "',
            cve_cliente = '" . $_post['cliente_asignado'] . "',
            cve_proveedor = '" . $_post['proveedor_asignado'] . "',
            image_url   = '" . $_post['imagen'] . "',
            web_apk    = '{$usuario_web_apk}'
            WHERE 
            id_user = '" . $_post['Clave'] . "'";
        $this->save = \db()->prepare($sql);
        $this->save->execute();

        if ($_post['es_cliente'] == 3) {
            $sql = 'SELECT COUNT(*) as existe FROM t_vendedores WHERE Cve_Vendedor = "' . $_post['cve_usuario'] . '" ';
            $validate = $this->getArraySQL($sql);
            $existe = $validate[0]["existe"];

            if ($existe) {
                $sql = "UPDATE t_vendedores SET Nombre = '" . $_post['nombre_completo'] . "', 
                                                Psswd_EDA = '" . $_post['pwd_usuario'] . "' 
                                            WHERE Id_Vendedor = " . $id_user . "";
            } else {
                $sql = "INSERT IGNORE INTO t_vendedores(Id_Vendedor, Nombre, Cve_Vendedor, Psswd_EDA) ";
                $sql .= "VALUES (";
                $sql .= "'" . $id_user . "',";
                $sql .= "'" . $_post['nombre_completo'] . "',";
                $sql .= "'" . $_post['cve_usuario'] . "',";
                $sql .= "'" . $_post['pwd_usuario'] . "')";
            }
            $this->save = \db()->prepare($sql);
            $this->save->execute();
        }

        $modulo_log = "Administración->Usuarios->Usuarios";
        $usuario_sesion = $_POST['usuario_log'];
        $operacion_log = "El usuario {$usuario_sesion} editó al usuario " . $_post['cve_usuario'];
        $observaciones_log = "";
        $sql_log = "INSERT INTO t_log_operaciones(modulo, usuario, fecha, operacion, dispositivo, observaciones) VALUES ('{$modulo_log}', '{$usuario_sesion}', NOW(), '{$operacion_log}', 'web', '{$observaciones_log}')";
        $this->save = \db()->prepare($sql_log);
        $this->save->execute();

    }

    function borrarUser($data)
    {

        $sql = "UPDATE c_usuario SET Activo = 0 WHERE id_user='" . $data['id_user'] . "';";
        $this->delete = \db()->prepare($sql);
        $this->delete->execute(array(
            $data['id_user']
        ));
    }

    function recoveryUser($data)
    {

        $sql = "UPDATE c_usuario SET Activo = 1 WHERE  id_user='" . $data['id_user'] . "';";
        $this->delete = \db()->prepare($sql);
        $this->delete->execute(array(
            $data['id_user']
        ));
    }

    function tieneAlmacen()
    {
        $sql = sprintf('
        SELECT
          cr.*
        FROM
          trel_us_alm r, c_usuario cr
        WHERE
          cr.cve_usuario = r.cve_usuario 
          and cr.Activo = 1
          and cr.id_user = ?
      ',
            self::TABLE
        );

        $sth = \db()->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, '\Usuarios\Usuarios');
        $sth->execute(array($this->id_user));

        $this->data = $sth->fetch();

    }

    function getArraySQL($sql)
    {

        $conexion = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

        mysqli_set_charset($conexion, "utf8");

        if (!$result = mysqli_query($conexion, $sql))
            echo "Falló la preparación: (" . mysqli_error($conexion) . ") ";;

        $rawdata = array();

        $i = 0;

        while ($row = mysqli_fetch_assoc($result)) {
            $rawdata[$i] = $row;
            $i++;
        }

        mysqli_close($conexion);

        return $rawdata;
    }
}


namespace TipoCliente;

class TipoCliente
{

    const TABLE = 'c_tipocliente';
    var $identifier;

    public function __construct($tipocliente = false, $key = false)
    {

        if ($tipocliente) {
            $this->tipocliente = (int)$tipocliente;
        }

        if ($key) {

            $sql = sprintf('
          SELECT
            Cve_TipoCte
          FROM
            %s
          WHERE
            Cve_TipoCte = ?
        ',
                self::TABLE
            );

            $sth = \db()->prepare($sql);
            $sth->setFetchMode(\PDO::FETCH_CLASS, '\TipoCliente\TipoCliente');
            $sth->execute();

            $Cve_TipoCte = $sth->fetch();

            $this->tipocliente = $Cve_TipoCte->tipocliente;

        }

    }

    function getAll()
    {

        $sql = '
        SELECT
          *
        FROM
          ' . self::TABLE . '
      ';

        $sth = \db()->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, '\TipoCliente\TipoCliente');
        $sth->execute();

        return $sth->fetchAll();

    }
}


namespace Zona;

class Zona
{

    const TABLE = 'c_zonacliente';
    var $identifier;

    public function __construct($Cve_ZonaCte = false, $key = false)
    {

        if ($Cve_ZonaCte) {
            $this->Cve_ZonaCte = (int)$Cve_ZonaCte;
        }

        if ($key) {

            $sql = sprintf('
          SELECT
            Cve_ZonaCte
          FROM
            %s
          WHERE
            Cve_ZonaCte = ?
        ',
                self::TABLE
            );

            $sth = \db()->prepare($sql);
            $sth->setFetchMode(\PDO::FETCH_CLASS, '\Zona\Zona');
            $sth->execute();

            $Zona = $sth->fetch();

            $this->Cve_ZonaCte = $Zona->Cve_ZonaCte;

        }

    }

    function getAll()
    {

        $sql = '
        SELECT
          *
        FROM
          ' . self::TABLE . '
      ';

        $sth = \db()->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, '\Zona\Zona');
        $sth->execute();

        return $sth->fetchAll();

    }
}


namespace Proveedores;

class Proveedores
{

    const TABLE = 'c_proveedores';
    var $identifier;

    public function __construct($cve_proveedor = false, $key = false)
    {
        if ($cve_proveedor) {
            $this->cve_proveedor = (int)$cve_proveedor;
        }

        if ($key) {
            $sql = sprintf('SELECT cve_proveedor FROM %s WHERE cve_proveedor = ? ', self::TABLE);

            $sth = \db()->prepare($sql);
            $sth->setFetchMode(\PDO::FETCH_CLASS, '\Proveedores\Proveedores');
            $sth->execute(array($key));

            $proveedor = $sth->fetch();
            $this->cve_proveedor = $proveedor->cve_proveedor;
        }
    }

    function getAll($filtro = "")
    {
        $sql = ' SELECT * FROM ' . self::TABLE . ' where Activo=1 ' . $filtro;

        $sth = \db()->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, '\Proveedores\Proveedores');
        $sth->execute();

        return $sth->fetchAll();
    }

    function getAllProvRTM($id_almacen)
    {
        $sql = "SELECT * FROM c_proveedores WHERE ID_Proveedor IN (SELECT ID_Proveedor FROM V_ExistenciaGral WHERE tipo = 'area' AND cve_almac = $id_almacen)";

        $sth = \db()->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, '\Proveedores\Proveedores');
        $sth->execute();

        return $sth->fetchAll();
    }

    function getTransportadoras()
    {
        $sql = "SELECT * FROM c_proveedores WHERE es_transportista = 1";

        $sth = \db()->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, '\Proveedores\Proveedores');
        $sth->execute();

        return $sth->fetchAll();
    }

    private function load()
    {
        $sql = sprintf(' SELECT * FROM %s WHERE ID_Proveedor = ? ', self::TABLE);

        $sth = \db()->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, '\Proveedores\Proveedores');
        $sth->execute(array($this->ID_Proveedor));

        $this->data = $sth->fetch();
    }

    function __get($key)
    {
        switch ($key) {
            case 'ID_Proveedor':
            case 'cve_proveedor':
            case 'Nombre':
            case 'direccion':
            case 'colonia':
            case 'cve_dane':
            case 'ciudad':
            case 'estado':
            case 'pais':
            case 'RUT':
            case 'telefono1':
            case 'telefono2':
            case 'departamento':
            case 'des_municipio':
                $this->load();
                return @$this->data->$key;
            default:
                return $this->key;
        }
    }

    function save($_post)
    {
        //if( !$_post['nombre_proveedor'] ) { throw new \ErrorException( 'Full name is required.' ); }
        $sql = "
            INSERT INTO " . self::TABLE . "
            SET
                cve_proveedor = '" . $_post['cve_proveedor'] . "', 
                Nombre = '" . utf8_decode($_post['nombre_proveedor']) . "', 
                direccion = '" . utf8_decode($_post['direccion']) . "', 
                colonia = '" . utf8_decode($_post['colonia']) . "', 
                cve_dane = '" . utf8_decode($_post['cve_dane']) . "', 
                ciudad = '" . utf8_decode($_post['ciudad']) . "', 
                estado = '" . utf8_decode($_post['estado']) . "', 
                pais = '" . utf8_decode($_post['pais']) . "'  , 
                RUT = '" . utf8_decode($_post['RUT']) . "', 
                telefono1 = '" . $_post['telefono1'] . "', 
                telefono2 = '" . $_post['telefono2'] . "',
                es_cliente = '" . $_post['cliente_proveedor'] . "',
                es_transportista = '" . $_post['transportista'] . "',
                envio_correo_automatico = " . (isset($_post['envio_automatico']) ? $_post['envio_automatico'] : 0) . ",
                Empresa = '" . $_SESSION["cve_cia"] . "'
        ";
        $rs = mysqli_query(\db2(), $sql) or die(mysqli_error(\db2()));

        $sql3 = "INSERT IGNORE INTO c_dane
                  SET
              cod_municipio = '{$_post['cve_dane']}'
            , departamento = '{$_post['ciudad']}'
            , des_municipio = '{$_post['estado']}'";

        $rs = mysqli_query(\db2(), $sql3) or die(mysqli_error(\db2()));

        if (isset($_post['envio_automatico']) && $_post['envio_automatico'] == 'true') {
            // Guardar correos en la tabla envios_correos
            foreach ($_post['correos'] as $correo) {
                $sql = "INSERT INTO envios_correos (tipo_destinatario, destinatario_id, correo,almacen) VALUES ('proveedor', '" . mysqli_insert_id(\db2()) . "', '" . $correo['correo'] . "', '" . $_post['almacen'] . "')";
                $rs = mysqli_query(\db2(), $sql) or die(mysqli_error(\db2()));

                // Obtener el ID del correo recién insertado
                $envio_id = mysqli_insert_id(\db2());

                // Inicializar los valores de días como 0 (no seleccionados)
                $lunes = '0';
                $martes = '0';
                $miercoles = '0';
                $jueves = '0';
                $viernes = '0';
                $sabado = '0';
                $domingo = '0';

                // Si el día está en el arreglo de días, marcarlo como 1 (seleccionado)
                if (isset($correo['dias']) && is_array($correo['dias'])) {
                    if (in_array('lunes', $correo['dias'])) $lunes = '1';
                    if (in_array('martes', $correo['dias'])) $martes = '1';
                    if (in_array('miercoles', $correo['dias'])) $miercoles = '1';
                    if (in_array('jueves', $correo['dias'])) $jueves = '1';
                    if (in_array('viernes', $correo['dias'])) $viernes = '1';
                    if (in_array('sabado', $correo['dias'])) $sabado = '1';
                    if (in_array('domingo', $correo['dias'])) $domingo = '1';
                }

                // Insertar en la tabla periodicidad usando el ID obtenido
                $sql = "INSERT INTO periodicidad (envio_id, lunes, martes, miercoles, jueves, viernes, sabado, domingo, hora_envio) VALUES ('"
                    . $envio_id . "', '"
                    . $lunes . "', '"
                    . $martes . "', '"
                    . $miercoles . "', '"
                    . $jueves . "', '"
                    . $viernes . "', '"
                    . $sabado . "', '"
                    . $domingo . "', '"
                    . (isset($correo['horaEnvio']) ? $correo['horaEnvio'] : '08:00') . "')";

                $rs = mysqli_query(\db2(), $sql) or die(mysqli_error(\db2()));

                foreach ($correo['reportes'] as $reporte) {
                    $sql = "INSERT INTO envios_reportes (envio_id, reporte_id) VALUES ('" . $envio_id . "', '" . $reporte . "')";
                    $rs = mysqli_query(\db2(), $sql) or die(mysqli_error(\db2()));
                }
            }
        }
    }

    function actualizarProveedor($data)
    {
        $sql = "
UPDATE " . self::TABLE . " 
SET
    Nombre = '" . $data['nombre_proveedor'] . "', 
    direccion = '" . $data['direccion'] . "',
    colonia = '" . $data['colonia'] . "',
    cve_dane = '" . $data['cve_dane'] . "',         
    ciudad = '" . $data['ciudad'] . "',
    estado = '" . $data['estado'] . "',
    pais = '" . $data['pais'] . "',         
    RUT = '" . $data['RUT'] . "',     
    telefono1 = '" . $data['telefono1'] . "',
    telefono2 = '" . $data['telefono2'] . "',
    es_transportista = '" . $data['transportista'] . "',
    es_cliente = " . $data['cliente_proveedor'] . ",
    envio_correo_automatico = " . (isset($data['envio_automatico']) ? $data['envio_automatico'] : 0) . "
WHERE ID_Proveedor = '" . $data['ID_Proveedor'] . "'
";
        $rs = mysqli_query(\db2(), $sql) or die(mysqli_error(\db2()));

        //si existe envio_automatico = true y correos no esta vacio entonces guardo en la tabla de correos
        if ((isset($data['envio_automatico']) && $data['envio_automatico'] == 'true') && (isset($data['correos']) && count($data['correos']) > 0)) {
            //eliminamos los envios_reportes
            $sql = "DELETE FROM envios_reportes WHERE envio_id IN (SELECT id FROM envios_correos WHERE tipo_destinatario = 'proveedor' AND destinatario_id = '" . $data['ID_Proveedor'] . "' AND almacen = '" . $data['almacen'] . "')";
            $rs = mysqli_query(\db2(), $sql) or die(mysqli_error(\db2()));

            // Primero eliminar registros existentes para evitar duplicados
            $sql = "DELETE FROM periodicidad WHERE envio_id IN (SELECT id FROM envios_correos WHERE tipo_destinatario = 'proveedor' AND destinatario_id = '" . $data['ID_Proveedor'] . "' AND almacen = '" . $data['almacen'] . "')";
            $rs = mysqli_query(\db2(), $sql) or die(mysqli_error(\db2()));

            $sql = "DELETE FROM envios_correos WHERE tipo_destinatario = 'proveedor' AND destinatario_id = '" . $data['ID_Proveedor'] . "' AND almacen = '" . $data['almacen'] . "'";
            $rs = mysqli_query(\db2(), $sql) or die(mysqli_error(\db2()));

            // Procesar cada correo individualmente
            foreach ($data['correos'] as $correo) {
                // Insertar en envios_correos
                $sql = "INSERT INTO envios_correos (tipo_destinatario, destinatario_id, correo,almacen) VALUES ('proveedor', '" . $data['ID_Proveedor'] . "', '" . $correo['correo'] . "', '" . $data['almacen'] . "')";
                $rs = mysqli_query(\db2(), $sql) or die(mysqli_error(\db2()));

                // Obtener el ID del correo recién insertado
                $envio_id = mysqli_insert_id(\db2());

                // Inicializar los valores de días como 0 (no seleccionados)
                $lunes = '0';
                $martes = '0';
                $miercoles = '0';
                $jueves = '0';
                $viernes = '0';
                $sabado = '0';
                $domingo = '0';

                // Si el día está en el arreglo de días, marcarlo como 1 (seleccionado)
                if (isset($correo['dias']) && is_array($correo['dias'])) {
                    if (in_array('lunes', $correo['dias'])) $lunes = '1';
                    if (in_array('martes', $correo['dias'])) $martes = '1';
                    if (in_array('miercoles', $correo['dias'])) $miercoles = '1';
                    if (in_array('jueves', $correo['dias'])) $jueves = '1';
                    if (in_array('viernes', $correo['dias'])) $viernes = '1';
                    if (in_array('sabado', $correo['dias'])) $sabado = '1';
                    if (in_array('domingo', $correo['dias'])) $domingo = '1';
                }

                // Insertar en la tabla periodicidad usando el ID obtenido
                $sql = "INSERT INTO periodicidad (envio_id, lunes, martes, miercoles, jueves, viernes, sabado, domingo, hora_envio) VALUES ('"
                    . $envio_id . "', '"
                    . $lunes . "', '"
                    . $martes . "', '"
                    . $miercoles . "', '"
                    . $jueves . "', '"
                    . $viernes . "', '"
                    . $sabado . "', '"
                    . $domingo . "', '"
                    . (isset($correo['horaEnvio']) ? $correo['horaEnvio'] : '08:00') . "')";

                $rs = mysqli_query(\db2(), $sql) or die(mysqli_error(\db2()));
                foreach ($correo['reportes'] as $reporte) {
                    $sql = "INSERT INTO envios_reportes (envio_id, reporte_id) VALUES ('" . $envio_id . "', '" . $reporte . "')";
                    $rs = mysqli_query(\db2(), $sql) or die(mysqli_error(\db2()));
                }
            }
        } else if ($data['envio_automatico'] == 'false') {
            // Si el envío automático está desactivado, eliminar los registros de envios_reportes
            $sql = "DELETE FROM envios_reportes WHERE envio_id IN (SELECT id FROM envios_correos WHERE tipo_destinatario = 'proveedor' AND destinatario_id = '" . $data['ID_Proveedor'] . "' AND almacen = '" . $data['almacen'] . "')";
            // Borrar correos de la tabla periodicidad (usando subconsulta para relacionar con los IDs de envios_correos)
            $sql = "DELETE FROM periodicidad WHERE envio_id IN (SELECT id FROM envios_correos WHERE tipo_destinatario = 'proveedor' AND destinatario_id = '" . $data['ID_Proveedor'] . "' AND almacen = '" . $data['almacen'] . "')";
            $rs = mysqli_query(\db2(), $sql) or die(mysqli_error(\db2()));

            // Borrar correos de la tabla envios_correos
            $sql = "DELETE FROM envios_correos WHERE tipo_destinatario = 'proveedor' AND destinatario_id = '" . $data['ID_Proveedor'] . "' AND almacen = '" . $data['almacen'] . "'";
            $rs = mysqli_query(\db2(), $sql) or die(mysqli_error(\db2()));
        }
    }

    function getCorreosProveedor($ID_Proveedor, $almacen)
    {
        //obtenemos primero los correos de la tabla envios_correos
        $sql = "SELECT * FROM envios_correos WHERE tipo_destinatario = 'proveedor' AND destinatario_id = '" . $ID_Proveedor . "' AND almacen = '" . $almacen . "'";
        $sth = \db()->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, '\Proveedores\Proveedores');
        $sth->execute(array($ID_Proveedor));
        $data = $sth->fetchAll(\PDO::FETCH_ASSOC);
        if (count($data) > 0) {
            foreach ($data as $key => $value) {
                $sql = "SELECT * FROM periodicidad WHERE envio_id = '" . $value['id'] . "'";
                $sth = \db()->prepare($sql);
                $sth->setFetchMode(\PDO::FETCH_CLASS, '\Proveedores\Proveedores');
                $sth->execute(array($ID_Proveedor));
                $periodicidad = $sth->fetchAll(\PDO::FETCH_ASSOC);
                if (count($periodicidad) > 0) {
                    $data[$key]['dias'] = $periodicidad;
                }
                $sql = "SELECT * FROM envios_reportes WHERE envio_id = '" . $value['id'] . "'";
                $sth = \db()->prepare($sql);
                $sth->setFetchMode(\PDO::FETCH_CLASS, '\Proveedores\Proveedores');
                $sth->execute(array($ID_Proveedor));
                $reportes = $sth->fetchAll(\PDO::FETCH_ASSOC);
                if (count($reportes) > 0) {
                    $data[$key]['reportes'] = $reportes;
                }
            }
        }
        return $data;
    }

    function borrarProveedor($data)
    {
        $sql = ' UPDATE ' . self::TABLE . ' SET Activo = 0 WHERE ID_Proveedor = ? ';
        $this->save = \db()->prepare($sql);
        $this->save->execute(array($data['ID_Proveedor']));
    }

    function recoveryProveedor($data)
    {
        $sql = "UPDATE " . self::TABLE . "SET Activo = 1 WHERE  ID_Proveedor='" . $data['ID_Proveedor'] . "';";
        $this->delete = \db()->prepare($sql);
        $this->delete->execute(array($data['ID_Proveedor']));
    }

    function exist($cve_proveedor)
    {
        $sql = sprintf(' SELECT * FROM ' . self::TABLE . ' WHERE cve_proveedor = ? ', self::TABLE);
        $sth = \db()->prepare($sql);

        $sth->setFetchMode(\PDO::FETCH_CLASS, '\Proveedores\Proveedores');
        $sth->execute(array($cve_proveedor));

        $this->data = $sth->fetch();

        if (!$this->data)
            return false;
        else
            return true;
    }

    function tieneOrden()
    {
        $sql = sprintf(' SELECT r.* FROM c_proveedores r, th_aduana cr WHERE cr.ID_Proveedor = r.ID_Proveedor  and r.ID_Proveedor = ? ', self::TABLE);

        $sth = \db()->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, '\Ruta\Ruta');
        $sth->execute(array($this->ID_Proveedor));
        $this->data = $sth->fetch();
    }

    public function inUse($data)
    {
        $sql = "SELECT ID_Proveedor from th_aduana where ID_Proveedor='" . $data['ID_Proveedor'] . "'";
        $sth = \db()->prepare($sql);
        $sth->execute();
        $data = $sth->fetch();

        if ($data['ID_Proveedor'])
            return true;
        else
            return false;
    }
}


use \Companias\Companias as Compania;
use \Usuarios\Usuarios as Usuario;


require_once __DIR__ . '/../../../app/db.php';
require_once __DIR__ . '/../../bi/_menu_global.php';
$TITLE = 'Administración de Manufactura';

$listaAlma = new \AlmacenP\AlmacenP();
$compania = new Compania();
$poblacion = $compania->getPoblacion();
$tiposCompania = $compania->getCompania();
$listaTC = new \TipoCliente\TipoCliente();
$listaZona = new \Zona\Zona();
$listaProvee = new \Proveedores\Proveedores();

$codDaneSql = \db()->prepare("SELECT *  FROM c_dane ");
$codDaneSql->execute();
$codDane = $codDaneSql->fetchAll(PDO::FETCH_ASSOC);
$usuario = new Usuario;
$usuarios = $usuario->getAll();

$id_almacen = $_SESSION['id_almacen'];

$sql = "SELECT a.cve_articulo, a.des_articulo, IFNULL(a.control_lotes, 'N') AS control_lotes, IFNULL(a.Caduca, 'N') AS Caduca, 
               IFNULL(a.peso, 0) AS peso, IFNULL(a.control_peso, 'N') AS control_granel, IF(um.mav_cveunimed='H87', 'S', 'N') AS es_pieza
        FROM c_articulo a
        INNER JOIN Rel_Articulo_Almacen al ON al.Cve_Articulo = a.cve_articulo AND al.Cve_Almac = $id_almacen
        LEFT JOIN c_unimed um ON um.id_umed = a.unidadMedida 
        WHERE a.Activo = 1";//WHERE Compuesto != 'S'
$res_art = mysqli_query(\db2(), $sql);

$articulos_no_compuestos = '<select id="articulos_no_compuestos" class="chosen-select form-control"><option value="">Seleccione Artículo</option>';

while ($a = mysqli_fetch_array($res_art)) {
    $articulos_no_compuestos .= '<option value="' . $a['cve_articulo'] . '" data-control_lotes="' . $a['control_lotes'] . '" data-caduca="' . $a['Caduca'] . '" data-granel="' . $a['control_granel'] . '" data-peso="' . $a['peso'] . '" data-espieza="' . $a['es_pieza'] . '"> ( ' . $a['cve_articulo'] . " ) " . utf8_encode($a['des_articulo']) . '</option>';
}
$articulos_no_compuestos .= '</select>';

$confSql = "SELECT IFNULL(Valor, '0') AS Valor FROM t_configuraciongeneral WHERE cve_conf = 'SAP' LIMIT 1";
$res_conf = mysqli_query(\db2(), $confSql);
$row_conf = mysqli_fetch_assoc($res_conf);
$ValorSAP = $row_conf['Valor'];

$confSql = "SELECT IFNULL(Valor, '') AS instancia FROM t_configuraciongeneral WHERE cve_conf = 'instancia' LIMIT 1";
$res_conf = mysqli_query(\db2(), $confSql);
$row_conf = mysqli_fetch_assoc($res_conf);
$instancia = $row_conf['instancia'];


$ejecutar_mostrar_SAP = 'M';//M = Mostrar, E = Ejecutar
$confSql = "SELECT Valor from t_configuraciongeneral WHERE cve_conf = 'mostrar_ejecutar_json'";
$res_conf = mysqli_query(\db2(), $confSql);
if (mysqli_num_rows($res_conf) > 0) {
    $row_conf = mysqli_fetch_assoc($res_conf);
    $ejecutar_mostrar_SAP = $row_conf['Valor'];
}

$confSql = "SELECT IFNULL(Valor, '0') AS Valor FROM t_configuraciongeneral WHERE cve_conf = 'OTSinSurtir' LIMIT 1";
$res_conf = mysqli_query(\db2(), $confSql);
$row_conf = mysqli_fetch_assoc($res_conf);
$mostrar_ot_sin_surtir = $row_conf['Valor'];

$ejecutar_mostrar_SAP = 'M';//M = Mostrar, E = Ejecutar
$confSql = "SELECT Valor from t_configuraciongeneral WHERE cve_conf = 'mostrar_ejecutar_json'";
$res_conf = mysqli_query(\db2(), $confSql);
if (mysqli_num_rows($res_conf) > 0) {
    $row_conf = mysqli_fetch_assoc($res_conf);
    $ejecutar_mostrar_SAP = $row_conf['Valor'];
}


$confSql = \db()->prepare("SELECT DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL -7 DAY), '%d-%m-%Y') AS fecha_semana FROM DUAL");
$confSql->execute();
$fecha_semana = $confSql->fetch()['fecha_semana'];

$confSql = \db()->prepare("SELECT CURDATE() AS fecha_actual FROM DUAL");
$confSql->execute();
$fecha_actual = $confSql->fetch()['fecha_actual'];

?>
<!DOCTYPE html>
<link href="/css/plugins/ladda/ladda-themeless.min.css" rel="stylesheet">
<link href="/css/plugins/datapicker/datepicker3.css" rel="stylesheet">
<link href="/css/plugins/chosen/chosen.css" rel="stylesheet">
<link href="/css/bootstrap-imageupload.min.css" rel="stylesheet">
<link href="/css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
<link href="/css/plugins/iCheck/custom.css" rel="stylesheet">


<style>

    .row_descargar:hover {
        background: #f2f2f2;
        cursor: pointer;
    }

    .row_descargar {
        border-top: 1px solid #ccc;
    }

    .row_descargar div, .row_descargar div i {
        margin: 5px 0 0 0 !important;
        font-size: 20px !important;
    }

    #FORM {
        width: 100%;
        /*height: 100%;*/
        position: absolute;
        left: 0px;
        z-index: 999;
    }

    #componentes_derivado_base_chosen, #articulos_no_compuestos_base_chosen {
        display: none;
    }

    #grid-table8 td {
        white-space: break-spaces;
    }
</style>

<div class="wrapper wrapper-content  animated" id="list">

    <h3>Administración de Ordenes de Trabajo</h3>

    <input type="hidden" id="terminar_produccion" value="0">
    <input type="hidden" id="tipo_embarque" value="0">
    <input type="hidden" id="art_caduca" value="N">
    <input type="hidden" id="existencia_OT" value="0">
    <input type="hidden" id="cambio_lote_realizado" value="0">
    <input type="hidden" id="instancia" value="<?php echo $instancia; ?>">
    <input type="hidden" id="fecha_actual" value="<?php echo $fecha_actual; ?>">

    <div class="row">
        <div class="col-lg-12">
            <div class="ibox ">
                <div class="ibox-title">

                    <div class="modal fade" id="fotos_oc_th" role="dialog">
                        <div class="vertical-alignment-helper">
                            <div class="modal-dialog vertical-align-center">
                                <!-- Modal content-->
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        <h4 class="modal-title">Artículo <span id="n_folio"></span></h4>
                                    </div>
                                    <div class="modal-body">
                                        <form id="form-subir-fotos-th" action="import" method="post"
                                              enctype="multipart/form-data">
                                            <div class="form-group">
                                                <label>Seleccione uno o más archivos para documentar (Límite 5) </label>
                                                <input type="hidden" name="cve_articulo_documento"
                                                       id="cve_articulo_documento" value="">

                                                <?php //accept=".png, .PNG, .jpg, .JPG, .JPEG, .jpeg"
                                                ?>
                                                <input type="file" name="image_file_th" id="file_th"
                                                       class="form-control" required>
                                                <br>
                                                <button id="btn-fotos-th" type="button" class="btn btn-primary">Subir
                                                    Archivo
                                                </button>
                                            </div>
                                        </form>
                                        <div class="col-md-12">
                                            <div class="progress" style="display:none">
                                                <div class="progress-bar progress-bar-info progress-bar-striped"
                                                     role="progressbar"
                                                     aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"
                                                     style="width:0%">
                                                    <div class="percent">0%</div>
                                                </div>
                                            </div>
                                        </div>
                                        <br>
                                        <div id="fotos_th">

                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <div style="text-align: right">
                                            <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group col-md-3">
                            <label>Seleccione un almacen</label>
                            <select class="form-control" id="almacen">
                                <option value="">Almacen</option>
                                <?php foreach ($listaAlma->getAll() as $a): ?>
                                    <option value="<?php echo $a->clave; ?>"><?php echo "($a->clave) $a->nombre"; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group col-md-3">
                            <label>Empresa | Proveedor</label>
                            <select class="chosen-select form-control" id="Proveedr">
                                <option value="">Seleccione</option>
                                <?php foreach ($listaProvee->getAll(" AND es_cliente = 1") as $a): ?>
                                    <option value="<?php echo $a->ID_Proveedor; ?>">[<?php echo $a->cve_proveedor; ?>]
                                        - <?php echo $a->Nombre; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group col-md-3">
                            <label>Status</label>
                            <select class="chosen-select form-control" id="statusOT">
                                <option value="P">Pendiente</option>
                                <option value="I">En Producción</option>
                                <option value="T">Terminado</option>
                                <!-- ESTOS 2 STATUS SON IGUALMENTE STATUS = T, SOLO QUE HAY QUE CLASIFICARLOS MÁS -->
                                <!--<option value="R">Envío Relacionado PV</option>
                                <option value="TR">Envío a Almacén</option>-->
                            </select>
                        </div>

                        <div class="form-group col-md-3">
                            <a href="/api/v2/otpendientes/exportar?almacen=<?php echo $_SESSION['cve_almacen']; ?>"
                               target="_blank" class="btn btn-primary" style="margin-left:15px; margin: 20px 0;"><span
                                        class="fa fa-upload"></span> Exportar OT Pendientes</a>
                        </div>

                    </div>

                    <div class="row">
                        <div class="col-lg-2">
                            <div class="form-group">
                                <label>Buscar</label>
                                <input id="criteriob" type="text" class="form-control">
                            </div>
                            <?php
                            /*
                            ?>
                             <div class="form-group">
                                <label for="folio_inicio">Folio Inicio</label>
                                <input type="text" class="form-control" name="folio_inicio" id="folio_inicio">
                            </div>
                            <?php
                            */
                            ?>
                        </div>
                        <div class="col-lg-2">
                            <div class="form-group">
                                <label>Buscar LP</label>
                                <input id="criteriobLP" type="text" class="form-control">
                            </div>
                        </div>
                        <?php
                        /*
                            ?>
                        <div class="col-lg-3">
                            <div class="form-group">
                                    <label for="email">En</label>
                                    <select name="filtro" id="filtro" class="chosen-select form-control">
                                        <option value="">Seleccione un filtro</option>
                                        <option value="c.Cve_Articulo">Clave de Producto</option>
                                        <option value="c.Cve_Usuario">Usuario</option>
                                        <option value="c.Cve_Lote">Lote</option>
                                    </select>

                            </div>

                             <div class="form-group">
                                <label for="folio_final">Folio Final</label>
                                <input type="text" class="form-control" name="folio_final" id="folio_final">
                            </div>

                        </div>
                            <?php
                            */
                        ?>

                        <div class="col-lg-2">
                            <div class="form-group">
                                <label>Fecha Inicio</label>
                                <div class="input-group date" id="data_1">
                                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span><input
                                            id="fechai" type="text" class="form-control"
                                            value="<?php echo $fecha_semana; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div class="form-group">
                                <label>Fecha Fin</label>
                                <div class="input-group date" id="data_2">
                                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span><input
                                            id="fechaf" type="text" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <br>
                            <div class="input-group-btn">
                                <a href="#" onclick="ReloadGrid()">
                                    <button type="submit" class="btn btn-sm btn-primary" id="buscarC"
                                            style="width: 219px;margin-top: 7px;">
                                        Buscar
                                    </button>
                                </a>
                            </div>
                        </div>
                    </div>

                    <?php
                    if ($_SERVER['HTTP_HOST'] == 'dicoisa.assistprowms.com' || $_SERVER['HTTP_HOST'] == 'www.dicoisa.assistprowms.com') {
                        ?>
                        <div class="row">
                            <div class="col-lg-3">
                                <br>
                                <div class="input-group-btn">
                                    <button type="button" class="btn btn-sm btn-primary" id="depurarOT"
                                            style="width: 219px;margin-top: 7px;">
                                        Depurar Todas las OT Pendientes
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>

                </div>
                <div class="ibox-content">

                    <div class="jqGrid_wrapper">
                        <table id="grid-table"></table>
                        <div id="grid-pager"></div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="coModal" role="dialog"><?php /* ?>style="overflow-x: scroll;"<?php */ ?>
    <div class="vertical-alignment-helper">
        <div class="modal-dialog vertical-align-center modal-lg"
             style="width: 80%!important;"><?php /* ?>style="width: 80%!important;"<?php */ ?>
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Detalle orden de trabajo</h4>
                    <br>
                    <div style="text-align: center;">
                        <span id="folio_ot"><b></b></span>
                        <br>
                        <span id="producto_compuesto"><b></b></span>
                    </div>

                </div>
                <div class="modal-body">
                    <div class="ibox-content" style="overflow-x: scroll;">
                        <div class="jqGrid_wrapper">
                            <table id="grid-table2" style="overflow-x: scroll;"></table>
                            <div id="grid-pager2"></div>
                        </div>
                    </div>

                    <?php
                    /*
                    ?>
                    <div id="a_asignar" class="col-md-3" style="display: none;">
                        <br><br><br>
                        <b>¿Cantidad a asignar?</b><br><br>
                        <!--<b>Máximo (<span></span>)</b>-->
                        <input type='number' id='cantidad_lote' class='form-control' value='0' min='0' max='0' data-id_orden='' data-folio='' data-clave='' data-cantidad='' data-existencia='' data-id_pedido='' />
                        <br>
                        <button type="button" id="boton_asignar" class="btn btn-primary">Asignar</button>
                        <button type="button" id="boton_cancelar" class="btn btn-default">Cancelar</button>
                    </div>
                    <?php
                    */
                    ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="historialsap" role="dialog"><?php /* ?>style="overflow-x: scroll;"<?php */ ?>
    <div class="vertical-alignment-helper">
        <div class="modal-dialog vertical-align-center modal-lg"
             style="width: 80%!important;"><?php /* ?>style="width: 80%!important;"<?php */ ?>
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Historial SAP de la ORDEN </h4>
                    <br>
                    <div style="text-align: center;">
                        <span id="folio_ot_sap"><b></b></span>
                        <br>
                    </div>

                </div>
                <div class="modal-body">
                    <div class="ibox-content" style="overflow-x: scroll;">
                        <div class="jqGrid_wrapper">
                            <table id="grid-table8" style="overflow-x: scroll;"></table>
                            <div id="grid-pager8"></div>
                        </div>
                    </div>

                    <?php
                    /*
                    ?>
                    <div id="a_asignar" class="col-md-3" style="display: none;">
                        <br><br><br>
                        <b>¿Cantidad a asignar?</b><br><br>
                        <!--<b>Máximo (<span></span>)</b>-->
                        <input type='number' id='cantidad_lote' class='form-control' value='0' min='0' max='0' data-id_orden='' data-folio='' data-clave='' data-cantidad='' data-existencia='' data-id_pedido='' />
                        <br>
                        <button type="button" id="boton_asignar" class="btn btn-primary">Asignar</button>
                        <button type="button" id="boton_cancelar" class="btn btn-default">Cancelar</button>
                    </div>
                    <?php
                    */
                    ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal_zonasembarque" role="dialog">
    <div class="vertical-alignment-helper">
        <div class="modal-dialog vertical-align-center modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4>Zonas de Embarque</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <label for="zonaembarque">Zonas Disponibles</label>
                            <select id="zonaembarque" class="chosen-select form-control">
                                <option value="">Seleccione la zona</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button>
                    <a href="#" onclick="asignarZonaEmbarque()">
                        <button id="asignarpedido" class="btn btn-primary pull-right" type="button">
                            <i class="fa fa-plus"></i>&nbsp;&nbsp;Asignar zona
                        </button>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="agregar_cliente" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body" style="text-align: left;">
                <h3 class="modal-title">Asignar Cliente a la Orden de Trabajo: <span id="nombre_lista_asignar"></span>
                </h3>
                <br><br>
                <div class="row">
                    <div class="form-group col-md-12">
                        <label>Cliente*</label>
                        <input class="form-control" name="cliente_buscar" id="cliente_buscar"
                               placeholder="Código del Cliente">
                        <input class="form-control" name="cliente" id="cliente" placeholder="Código del Cliente"
                               style="display: none;">
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-12">
                        <label>Clave y Nombre Cliente</label>
                        <select id="desc_cliente" name="desc_cliente" class="form-control">
                        </select>
                    </div>
                </div>

                <div class="row" style="display: none;">
                    <div class="form-group col-md-12">
                        <label>Destinatario *</label>
                        <select class="form-control chosen-select" name="destinatario" id="destinatario"
                                disabled></select>
                        <br><br>
                        <button type="button" disabled id="agregar_destinatario" class="btn btn-success"
                                data-toggle="modal" data-target="#modal_destinatario">
                            Asignar Destinatario
                        </button>
                        <br><br>
                        <textarea id="txt-direc" name="observacion" class="form-control" disabled></textarea>
                    </div>
                </div>
                <br><br>


            </div>
            <div class="modal-footer">
                <button class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button class="btn btn-primary" id="asignar_cliente">Asignar Cliente</button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="asignar_lote_modal" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" style="width: 300px;max-width: 300px;">
        <div class="modal-content">
            <div class="modal-header" id="headModal">
                <h2>Asignar Cantidad</h2>
            </div>
            <div class="modal-body">

                <b>¿Cantidad a asignar?</b><br><br>
                <!--<b>Máximo (<span></span>)</b>-->
                <input type='number' id='cantidad_lote' class='form-control' value='0' min='0' max='0' data-id_orden=''
                       data-folio='' data-clave='' data-cantidad='' data-existencia='' data-id_pedido='' dir="rtl"/>

            </div>
            <div class="modal-footer">
                <button type="button" id="boton_asignar" class="btn btn-primary">Asignar</button>
                <button type="button" id="boton_cancelar" class="btn btn-default" data-dismiss="modal">Cancelar</button>
            </div>

        </div>
    </div>
</div>


<div class="wrapper wrapper-content animated hidden" id="asignarUsuario">
    <div class="row">
        <div class="col-lg-12">
            <div class="ibox ">
                <div class="ibox-title">
                    <h3><b>Orden de producción: <span id="ordenprod"></span></b></h3>
                </div>
                <div class="ibox-content">
                    <div class="row">
                        <div class="col-lg-2">
                            <form action="/api/manufactura/ordentrabajo/usuarios.php" method="post" id="asignarForm">
                                <input type="hidden" name="folio" value="0">
                                <div class="form-group">
                                    <label> Usuario: </label>
                                    <?php if (isset($usuarios) && !empty($usuarios)): ?>
                                        <?php foreach ($usuarios as $user): ?>
                                            <?php
                                            if ($user->id_user == $_SESSION['id_user']) {
                                                echo $user->nombre_completo;
                                                ?>
                                                <input type="hidden" id="clave_usuario"
                                                       value="<?php echo $user->cve_usuario; ?>">
                                                <?php
                                            }
                                            ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                        <div class="col-lg-2">
                            <img src="../img/compania/noimage.jpg" height="100" id="imagen_producto_compuesto">
                        </div>
                        <div class="col-lg-4" style="text-align: center;
                             font-size: 30px;
                             font-weight: 600;
                             color: #F00;">
                            <div style="font-size: 16px;color: #000;">Fecha y Hora de Inicio</div>
                            <?php
                            /*
  $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

  $sql = "SELECT DATE_FORMAT(CURDATE(), '%d/%m/%Y') FECHA_ACTUAL, DATE_FORMAT(NOW(), '%H:%i:%s') HORA_ACTUAL";
  $query = mysqli_query($conn, $sql);
  $row_date = mysqli_fetch_assoc($query);
  $fecha = $row_date['FECHA_ACTUAL'];
  $hora_inicio = $row_date['HORA_ACTUAL'];

  echo $fecha."|".$hora_inicio;
*/
                            ?>

                            <input type="hidden" name="status_terminar_produccion" id="status_terminar_produccion"
                                   value="0">
                            <input type="hidden" name="mensaje_mostrar_produccion" id="mensaje_mostrar_produccion"
                                   value="1">
                            <span id="fecha_hora"></span>
                        </div>
                        <div class="col-lg-4" style="text-align: center;
                             font-size: 30px;
                             font-weight: 600;
                             color: #F00;">
                            <div style="font-size: 16px;color: #000;">Tiempo de Producción</div>
                            <span id="hour"></span>:<span id="minute"></span>:<span id="second"></span>
                        </div>

                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <label> Producto Compuesto </label>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                    <tr>
                                        <th style="text-align: center;">Clave</th>
                                        <th style="text-align: center;">Descripción</th>
                                        <th style="text-align: center;">BL Producción</th>
                                        <th style="text-align: center;">BL Destino</th>
                                        <th style="text-align: center;">Solicitada</th>
                                        <th style="text-align: center;">Producida</th>
                                        <th style="text-align: center;">Faltante</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td style="text-align: center;" id="compuesto_clave">0</td>
                                        <td style="text-align: center;" id="compuesto_descripcion">0</td>
                                        <td style="text-align: center;" id="compuesto_BL">

                                            <select class="form-control" id="select_BL" name="select_BL">
                                            </select>

                                        </td>
                                        <td style="text-align: center;" id="compuesto_BL_Destino">

                                            <select class="form-control" id="select_BL_Dest" name="select_BL_Dest">
                                            </select>

                                        </td>
                                        <td style="text-align: center;">
                                            <div contenteditable onchange="recalcularCantidadRequerida()"
                                                 id="compuesto_cantidad">0
                                            </div>
                                        </td>
                                        <td style="text-align: center;" id="producida"></td>
                                        <td style="text-align: center;" id="faltante"></td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <label id="clave_LP">Ingrese Clave de Producto a producir</label>
                            <div class="row" style="margin: 10px 0;">
                                <input type="hidden" id="id_orden_trabajo" value="">
                                <input type="hidden" id="tipo_orden_trabajo" value="">
                                <input type="hidden" id="cve_articulo_LP" value="">
                                <div class="col-lg-3" style="padding-left: 0;">
                                    <input type="text" class="form-control" name="cod_art_compuesto"
                                           id="cod_art_compuesto" placeholder="Código Artículo">
                                    <br>
                                    <div class="form-group">
                                        <input type="hidden" id="lote_usado" value="">
                                        <label>Lote</label>
                                        <input type="text" class="form-control" name="lote_compuesto"
                                               id="lote_compuesto" placeholder="Lote OT">
                                        <br>
                                        <div class="caducidad_compuesto">
                                            <label>Caducidad</label>
                                            <div class="input-group date" id="data_3">
                                                <span class="input-group-addon"><i
                                                            class="fa fa-calendar"></i></span><input
                                                        id="caducidad_compuesto" type="text" class="form-control">
                                            </div>
                                        </div>
                                    </div>

                                </div>
                                <div class="col-lg-3">
                                    <input type="number" class="form-control" name="cantidad_art_compuesto"
                                           id="cantidad_art_compuesto" placeholder="Cantidad" min="1"
                                           style="text-align: center" value="1">
                                    <br><br>
                                    <button type="button" id="cambiar_lote" class="btn btn-warning lote_compuesto"
                                            style="margin-top: 5px;">Cambiar Lote
                                    </button>
                                    <br><br><br>
                                    <button type="button" id="cambiar_caducidad"
                                            class="btn btn-warning caducidad_compuesto" style="margin-top: 5px;">Cambiar
                                        Caducidad
                                    </button>
                                    <button type="button" id="imprimir_etiqueta_btn" class="btn btn-primary"
                                            style="margin-top: 5px;display: none;">Imprimir Etiqueta
                                    </button>
                                </div>
                                <div class="col-lg-3">
                                    <button type="button" id="pasarArticuloCompuesto" class="btn btn-primary">Registrar
                                        Producción
                                    </button>
                                    <br><br>
                                    <button type="button" id="TerminarProduccion" class="btn btn-success">Terminar
                                        Producción
                                    </button>
                                </div>


                                <div class="col-lg-3" id="registrar_merma_derivado" style="display: none;">
                                    <button type="button" id="ProductoSobrante" class="btn btn-success" disabled>
                                        Registrar Producto Derivado | Merma
                                    </button>
                                    <div id="sobrante">
                                        <br><br>
                                        <div>
                                            <label for="derivado" id="label_derivado" style="cursor: pointer;">
                                                <input type="radio" name="derivado_merma" id="derivado" value="derivado"
                                                       checked>&nbsp;&nbsp;Derivado</label> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                            <label for="merma" id="label_merma" style="cursor: pointer;"><input
                                                        type="radio" name="derivado_merma" id="merma" value="merma">&nbsp;&nbsp;Merma</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                            <label for="pbase" style="cursor: pointer;"><input type="radio"
                                                                                               name="derivado_merma"
                                                                                               id="pbase" value="pbase">&nbsp;&nbsp;Derivar
                                                Productos Base</label>
                                            <label for="excedente" style="cursor: pointer;"><input type="radio"
                                                                                                   name="derivado_merma"
                                                                                                   id="excedente"
                                                                                                   value="excedente">&nbsp;&nbsp;Producto
                                                Excedente</label>
                                        </div>
                                        <br><br>
                                        <div id="producto_derivado">
                                            <div class="no_excedente">
                                                <label><b>Artículo Base</b></label>
                                                <select class="form-control chosen-select" id="componentes_derivado">
                                                    <option value="">Seleccione</option>
                                                </select>
                                                <select class="form-control chosen-select"
                                                        id="componentes_derivado_base" style="display: none;">
                                                    <option value="">Seleccione</option>
                                                </select>
                                                <input type="hidden" id="componente_derivado_select" value="">
                                                <br><br>
                                                <label><b>Stock Producto Base <span id="peso_comp"></span></b></label>
                                                <input type="number" class="form-control" id="cantidad_componente"
                                                       name="cantidad_componente" value="" min="0">
                                                <div style="color: red; font-weight: bolder;" id="text_max_comp"></div>
                                                <br><br>
                                            </div>
                                            <label><b><span class="no_excedente">Producto Derivado</span><span
                                                            class="si_excedente">Producto Excedente</span> <span
                                                            id="peso_derivado"></span></b></label>
                                            <?php echo $articulos_no_compuestos; ?>
                                            <select id="articulos_no_compuestos_base" class="chosen-select form-control"
                                                    style="display: none;">
                                                <option value="">Seleccione Artículo</option>
                                            </select>
                                            <br><br>
                                            <label><b>Cantidad</b></label>
                                            <input type="number" class="form-control" name="cantidad_sobrante"
                                                   id="cantidad_sobrante" min="0">
                                            <div style="color: red; font-weight: bolder;" id="text_max_derivado"></div>

                                            <div class="row" id="lote_caducidad_base">
                                                <div class="col-md-6" id="lote_derivado" style="display:none;">
                                                    <label>Lote</label>
                                                    <input type="text" class="form-control"
                                                           name="lote_compuesto_derivado" id="lote_compuesto_derivado"
                                                           placeholder="Lote OT" readonly>
                                                </div>
                                                <div class="col-md-6" id="caducidad_derivado" style="display:none;">
                                                    <label>Caducidad</label>
                                                    <div class="input-group date" id="data_3_derivado">
                                                        <span class="input-group-addon"><i
                                                                    class="fa fa-calendar"></i></span><input
                                                                id="caducidad_compuesto_derivado" type="text"
                                                                class="form-control" readonly>
                                                    </div>
                                                </div>
                                            </div>

                                            <br><br>
                                            <button type="button" id="RegistrarSobrante" class="btn btn-success">
                                                Registrar
                                            </button>
                                            <button type="button" id="CancelarSobrante" class="btn btn-danger">
                                                Cancelar
                                            </button>

                                        </div>

                                        <div id="producto_merma" style="display:none;">

                                            <label><b>Componente</b></label>
                                            <input type="text" class="form-control" name="articulo_componente"
                                                   id="articulo_componente" readonly>
                                            <input type="hidden" class="form-control" name="cve_articulo_componente"
                                                   id="cve_articulo_componente" readonly>
                                            <!--<input type="hidden" class="form-control" name="lote_componente_merma" id="lote_componente_merma" readonly>-->
                                            <br><br>

                                            <label><b>Cantidad</b></label>
                                            <input type="number" class="form-control" name="cantidad_sobrante_merma"
                                                   id="cantidad_sobrante_merma" min="0" readonly>
                                            <br><br>
                                            <div class="row" id="lote_caducidad_merma">
                                                <div class="col-md-6" id="lote_derivado_merma">
                                                    <label>Lote</label>
                                                    <input type="text" class="form-control"
                                                           name="lote_compuesto_derivado_merma"
                                                           id="lote_compuesto_derivado_merma" placeholder="Lote OT"
                                                           readonly>
                                                </div>
                                                <div class="col-md-6" id="caducidad_derivado_merma">
                                                    <label>Caducidad</label>
                                                    <div class="input-group date" id="data_3_derivado_merma">
                                                        <span class="input-group-addon"><i
                                                                    class="fa fa-calendar"></i></span><input
                                                                id="caducidad_compuesto_derivado_merma" type="text"
                                                                class="form-control" readonly>
                                                    </div>
                                                </div>
                                            </div>

                                            <br><br>
                                            <button type="button" id="RegistrarMerma" class="btn btn-success">Registrar
                                                Merma
                                            </button>
                                            <button type="button" id="CancelarMerma" class="btn btn-danger">Cancelar
                                            </button>

                                        </div>
                                    </div>
                                    <br><br>
                                    <?php /* ?>
                                    <button type="button" id="RegistrarMerma" class="btn btn-success" disabled>Registrar Merma</button>
<?php */ ?>
                                </div>
                            </div>
                            <label>Formulación | Componentes</label>
                            <div class="jqGrid_wrapper">
                                <table id="grid-table5"></table>
                                <div id="grid-pager5"></div>
                            </div>
                        </div>
                    </div>
                    <br><br>
                    <div class="row">
                        <div class="col-lg-12">
                            <label>Materia Prima | Producto Kitting</label>
                            <div class="jqGrid_wrapper">
                                <table id="grid-table3"></table>
                                <div id="grid-pager3"></div>
                            </div>
                        </div>
                    </div>

                    <?php
                    ?>
                    <br>
                    <div class="row">
                        <div class="col-lg-12">
                            <label>Producto Terminado | Ofertas | Kits</label>
                            <div class="jqGrid_wrapper">
                                <table id="grid-table4"></table>
                                <div id="grid-pager4"></div>
                            </div>
                        </div>
                    </div>

                    <br>
                    <div class="row">
                        <div class="col-lg-12">
                            <label>Producto Terminado | Derivados</label>
                            <div class="jqGrid_wrapper">
                                <table id="grid-table6"></table>
                                <div id="grid-pager6"></div>
                            </div>
                        </div>
                    </div>
                    <?php
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="selectOption" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" id="headModal">
                <h2>Imprimir etiquetas</h2>
            </div>
            <div class="modal-body">
                <form target="_blank" id="imprimir_etiqueta" method="post" action="/reportes/pdf/etiquetas">
                    <div class="form-group">
                        <label>Artículo</label>
                        <h3 style="font-weight: normal" id="producto_des"></h3><br>
                        <b>Existencia Disponible: <span id="existencia_producto_terminado"></span></b>
                    </div>
                    <div class="form-group">
                        <label>Tipo de etiqueta</label>
                        <select class="form-control" name="etiqueta" required>
                            <option value="caja">Empaque</option>
                            <?php
                            /*
                            ?>
                            <option value="">Seleccione</option>
                            <option value="articulo">Artículo</option>
                            <option value="pallet">Pallet</option>
                            <?php
                            */
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Lote</label>
                        <input type="text" class="form-control" name="lote" id="lotes_etiq" readonly required>
                        <br>
                        <label>Caducidad</label>
                        <input type="text" class="form-control" name="caducidad_etiq" id="caducidad_etiq" readonly
                               required>
                    </div>
                    <div class="form-group">
                        <label>Unidades por caja (Kg)</label>
                        <input type="number" name="unidades_caja" id="unidades_caja" class="form-control" value="1"
                               min="1" required>
                    </div>
                    <div class="form-group">
                        <label>Cantidad de etiquetas</label>
                        <input type="number" name="numero_impresiones" id="numero_impresiones" class="form-control"
                               value="1" min="1" required>
                    </div>
                    <input type="hidden" name="consultar" id="consultar" value="0">
                    <input type="hidden" name="cve_articulo" id="cve_articulo_etiq" value="0">
                    <input type="hidden" name="ordenp" id="ordenp_etiq" value="0">
                    <input type="hidden" name="des_articulo" id="des_articulo_etiq" value="0">
                    <input type="hidden" name="barras2" id="barras2_etiq" value="0">
                    <input type="hidden" name="barras3" id="barras3_etiq" value="0">
                    <input type="hidden" name="check_pallet" id="check_pallet_etiq" value="0">
                    <input type="hidden" name="nofooternoheader" value="true">
                    <div>
                        <div class="form-group" style="text-align: left;display: inline-block;float: left;">
                            <div class="checkbox">
                                <label for="palletizar_imprimir"
                                       style="font-weight: 600;font-size: 14px; -moz-user-select: none; -webkit-user-select: none;-ms-user-select: none; user-select: none;">
                                    <input type="checkbox" name="palletizar_imprimir" id="palletizar_imprimir"
                                           value="0">Palletizar | Imprimir LPs</label>
                            </div>
                        </div>

                        <div class="form-group" id="pallets" style="text-align: left;display: none;">
                            <br><br><br><br>
                            <label style="float: left;"><span class="fa fa-box-check"></span>Pallet | Contenedor</label>
                            <!--<input type="text" class="form-control" id="input-cajas" maxlength="5" required>-->
                            <select class="chosen-select form-control" name="select-pallets" id="select-pallets">
                                <!--<option value="">Seleccione Pallet | Contenedor</option>-->
                            </select>
                        </div>

                        <div style="text-align: right;">
                            <button type="button" class="btn btn-default" onclick="finalizar()">Cerrar</button>
                            <button type="button" class="btn btn-success" id="imprimir_etiquetas">
                                Imprimir
                                <i class="fa fa-print-o"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="select_pedidos" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" id="headModal">
                <h2>Relacionar Pedido a OT y Embarcar</h2>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Pedidos</label>
                    <select class="form-control" name="pedidos_rel" id="pedidos_rel">
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="obtener_zonas_embarque">Embarcar</button>
            </div>

        </div>
    </div>
</div>


<!-- Mainly scripts -->
<script src="/js/jquery-2.1.1.js"></script>
<script src="/js/bootstrap.min.js"></script>
<script src="/js/plugins/metisMenu/jquery.metisMenu.js"></script>
<script src="/js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="/js/plugins/sweetalert/sweetalert.min.js"></script>
<script src="https://momentjs.com/downloads/moment.js"></script>
<script src="https://momentjs.com/downloads/moment-with-locales.js"></script>

<!-- Peity -->
<script src="/js/plugins/peity/jquery.peity.min.js"></script>

<!-- jqGrid -->
<script src="/js/plugins/jqGrid/i18n/grid.locale-es.js"></script>
<script src="/js/plugins/jqGrid/jquery.jqGrid.min.js"></script>

<!-- Custom and plugin javascript -->
<script src="/js/inspinia.js"></script>
<script src="/js/plugins/pace/pace.min.js"></script>

<script src="/js/plugins/jquery-ui/jquery-ui.min.js"></script>

<script src="/js/plugins/ladda/spin.min.js"></script>
<script src="/js/plugins/ladda/ladda.min.js"></script>
<script src="/js/plugins/ladda/ladda.jquery.min.js"></script>
<script src="/js/plugins/iCheck/icheck.min.js"></script>

<!-- Select -->
<script src="/js/plugins/chosen/chosen.jquery.js"></script>
<script src="/js/bootstrap-imageupload.js"></script>
<script src="/js/plugins/datapicker/bootstrap-datepicker.js"></script>
<script type="text/javascript">


    //////////////////////////////////////////////////////////Aqui se contruye el Grid//////////////////////////////////////////////////////////
    $(function ($) {
        var grid_selector = "#grid-table";
        var pager_selector = "#grid-pager";

        //resize to fit page size
        $(window).on('resize.jqGrid', function () {
            $(grid_selector).jqGrid('setGridWidth', $(".page-content").width() - 60);
        })
        //resize on sidebar collapse/expand
        var parent_column = $(grid_selector).closest('[class*="col-"]');
        $(document).on('settings.ace.jqGrid', function (ev, event_name, collapsed) {
            if (event_name === 'sidebar_collapsed' || event_name === 'main_container_fixed') {
                //setTimeout is for webkit only to give time for DOM changes and then redraw!!!
                setTimeout(function () {
                    $(grid_selector).jqGrid('setGridWidth', parent_column.width());
                }, 0);
            }
        })

        $(grid_selector).jqGrid({
            url: '/api/adminordentrabajo/lista/index.php',
            datatype: "json",
            contentType: "application/json",
            shrinkToFit: false,
            height: 'auto',
            postData: {
                criterio: $("#criteriob").val(),
                criterioLP: $("#criteriobLP").val(),
                almacen: $("#almacen").val(),
                Proveedor: $("#Proveedr").val(),
                statusOT: $("#statusOT").val(),
                //filtro: $("#filtro").val(),
                fechaInicio: $("#fechai").val(),
                fechaFin: $("#fechaf").val(),
                //folioInicio: $("#folio_inicio").val(),
                //folioFin: $("#folio_final").val(),
                action: 'busqueda'
            },
            mtype: 'POST',
            colNames: ["Acciones", "Fecha OT", "Hora OT", 'Folio OT', "Pedido", 'Clave producto', "Nombre producto", "Lote | Serie", "Caducidad", "Cantidad solicitada", "Cantidad Producida", "Usuario", "Fecha Compromiso", "Status", "Fecha inicio", "Hora inicio", "Fecha fin", "Hora fin", "Empresa | Proveedor", "StatusOT", "Almacén", "Zona de Trabajo", "Traslado", "Area Producción", "Palletizado", "tiene_documentos", "TipoOT"],
            colModel: [
                //{name:'id',index:'id', width:60, sorttype:"int", editable: false},
                {
                    name: 'myac',
                    index: '',
                    width: 180,
                    fixed: true,
                    sortable: false,
                    resize: false,
                    formatter: imageFormat
                },
                {
                    name: 'fecha',
                    index: 'fecha',
                    width: 100,
                    editable: false,
                    sortable: false,
                    resizable: false,
                    align: 'center'
                },
                {
                    name: 'hora_ot',
                    index: 'hora_ot',
                    width: 100,
                    editable: false,
                    sortable: false,
                    resizable: false,
                    align: 'center'
                },
                {name: 'Folio_Pro', index: 'Folio_Pro', width: 120, editable: false, sortable: false},
                {name: 'pedido', index: 'pedido', width: 120, editable: false, sortable: false, resizable: false},
                {name: 'Cve_Articulo', index: 'Cve_Articulo', width: 140, editable: false, sortable: false},
                {name: 'descripcion', index: 'descripcion', width: 350, editable: false, sortable: false},
                {name: 'Cve_Lote', index: 'Cve_Lote', width: 100, editable: false, sortable: false, resizable: false},
                {
                    name: 'caducidad',
                    index: 'caducidad',
                    width: 100,
                    editable: false,
                    sortable: false,
                    resizable: false,
                    align: 'center'
                },
                {
                    name: 'Cantidad',
                    index: 'Cantidad',
                    align: 'right',
                    width: 150,
                    editable: false,
                    sortable: false,
                    resizable: false
                },
                {
                    name: 'Cant_Prod',
                    index: 'des_telef',
                    align: 'right',
                    editable: false,
                    sortable: false,
                    resizable: false
                },
                {name: 'usuario', index: 'usuario', width: 180, editable: false, sortable: false, resizable: false},
                {
                    name: 'fecha_compromiso',
                    index: 'fecha_compromiso',
                    width: 130,
                    editable: false,
                    sortable: false,
                    resizable: false,
                    align: 'center'
                },
                {name: 'status', index: 'status', width: 150, editable: false, sortable: false, resizable: false},
                {
                    name: 'Fecha_Ini',
                    index: 'Fecha_Ini',
                    width: 100,
                    editable: false,
                    sortable: false,
                    resizable: false,
                    align: 'center'
                },
                {
                    name: 'Hora_Ini',
                    index: 'Hora_Ini',
                    width: 100,
                    editable: false,
                    sortable: false,
                    resizable: false,
                    align: 'center'
                },
                {
                    name: 'Fecha_Fin',
                    index: 'Fecha_Fin',
                    width: 100,
                    editable: false,
                    sortable: false,
                    resizable: false,
                    align: 'center'
                },
                {
                    name: 'Hora_Fin',
                    index: 'Hora_Fin',
                    width: 100,
                    editable: false,
                    sortable: false,
                    resizable: false,
                    align: 'center'
                },
                {name: 'proveedor', index: 'proveedor', width: 180, editable: false, sortable: false, resizable: false},
                {
                    name: 'statusOT',
                    index: 'statusOT',
                    width: 160,
                    editable: false,
                    sortable: false,
                    resizable: false,
                    hidden: true
                },
                {name: 'almacen', index: 'almacen', width: 200, editable: false, sortable: false, resizable: false},
                {
                    name: 'zona',
                    index: 'zona',
                    width: 200,
                    editable: false,
                    sortable: false,
                    resizable: false,
                    hidden: true
                },
                {
                    name: 'traslado',
                    index: 'traslado',
                    width: 200,
                    editable: false,
                    sortable: false,
                    resizable: false,
                    hidden: true
                },
                {
                    name: 'ubicacion_produccion',
                    index: 'ubicacion_produccion',
                    width: 200,
                    editable: false,
                    sortable: false,
                    resizable: false,
                    hidden: true
                },
                {
                    name: 'palletizado',
                    index: 'palletizado',
                    width: 200,
                    editable: false,
                    sortable: false,
                    resizable: false,
                    hidden: true
                },
                {
                    name: 'tiene_documentos',
                    index: 'tiene_documentos',
                    width: 110,
                    editable: false,
                    sortable: false,
                    resizable: true,
                    hidden: true
                },
                {
                    name: 'TipoOT',
                    index: 'TipoOT',
                    width: 110,
                    editable: false,
                    sortable: false,
                    resizable: true,
                    hidden: true
                }
            ],
            rowNum: 30,
            rowList: [30, 40, 50],
            pager: pager_selector,
            sortname: 'Folio_Pro',
            viewrecords: true,
            sortorder: "desc",
            loadComplete: function (data) {
                console.log("SUCCESS grid-table: ", data);
            },
            loadError: function (data) {
                console.log("ERROR grid-table: ", data);
            }
        });

        // Setup buttons
        $("#grid-table").jqGrid('navGrid', '#grid-pager',
            {edit: false, add: false, del: false, search: false},
            {height: 200, reloadAfterSubmit: true}
        );


        $(window).triggerHandler('resize.jqGrid');//trigger window resize to make the grid get the correct size
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        //////////////////////////////////////////////////////////aqui se construye la funcion de cada item del grid//////////////////////////////////////////////////////////
        function imageFormat(cellvalue, options, rowObject) {
            var serie = rowObject[3];
            var status_nombre = rowObject[13];
            var correl = rowObject[8];
            var traslado = rowObject[22];
            var area_produccion = rowObject[23];
            var palletizado = rowObject[24];
            var tiene_documentos = rowObject[25];
            var TipoOT = rowObject[26];
            var cantidad_producida = rowObject[10];
            var art_comp = "(" + rowObject[4] + ") - " + rowObject[5];
            var statusOT = rowObject[19];
            var url = "x/?serie=" + serie + "&correl=" + correl;
            var url2 = "v/?serie=" + serie + "&correl=" + correl;
            $("#hiddencve_tipcia").val(serie);
            var html = '';
            html += '<a href="#" onclick="detalle(\'' + serie + '\', \'' + art_comp + '\')"><i class="fa fa-search" title="Ver artículos del compuesto"></i></a>&nbsp;&nbsp;';
            //console.log("statusOT = ",rowObject[19]);
            console.log("status = ", status_nombre);
            //console.log("folio = ", serie, " art_comp = ",art_comp, " traslado = ", traslado, " area_produccion = ", area_produccion, " palletizado = ", palletizado);
            //if(statusOT != 'P' ){
            if ($("#permiso_registrar").val() == 1 && $("#permiso_editar").val() == 1)
                html += '<a href="#" onclick="asignar(\'' + serie + '\', \'' + statusOT + '\')"><i class="fa fa-check" title="Iniciar Producción | Maquila"></i></a>&nbsp;&nbsp;';
            //}


            if (tiene_documentos == 'S')
                html += '<a href="#" onclick="descargar_documentos(\'' + rowObject[5] + '\')" title="Ver Documentos"><i class="fa fa-download" ></i></a>&nbsp;&nbsp;&nbsp;&nbsp;';


            if (statusOT == 'T' || statusOT == 'I') {
                var lotes_etiq = rowObject[7];
                var caducidad_etiq = rowObject[8];
                var cve_articulo_etiq = rowObject[5];
                var ordenp_etiq = rowObject[3];
                var des_articulo_etiq = rowObject[6];
                var Cantidad = rowObject[9];
                var Cantidad_Producida = rowObject[10];
                console.log("*************************************");
                Cantidad = (Cantidad).replace(',', ':');
                Cantidad = (Cantidad).replace('.', ',');
                Cantidad = (Cantidad).replace(':', '');
                //console.log("Cantidad = ", Cantidad);
                //console.log("Cantidad_Producida = ", parseFloat(Cantidad_Producida));
                //console.log("statusOT = ", statusOT);
                console.log("*************************************");

                if (parseFloat(Cantidad) > parseFloat(Cantidad_Producida) && statusOT == 'T' && $("#permiso_editar").val() == 1)
                    html += '<a href="#" onclick="cambiar_status(\'' + ordenp_etiq + '\')"><i class="fa fa-reply" title="Cambiar Status a Producción"></i></a>&nbsp;&nbsp;';
                //SI palletizado = 0 SIGNIFICA QUE NO SE HA HECHO NINGUNA PALLETIZACIÓN POR LO QUE NO ES NECESARIO REINICIAR
                //traslado = 1, CUENTA SI EL PRODUCTO AÚN SIGUE EN 1 SOLA UBICACIÓN DE AREA DE PRODUCCIÓN, SI TRASLADO > 1, SIGNIFICA QUE ESTÁ EN MÁS UBICACIONES POR LO QUE YA SE TRASLADÓ A OTRO BL.
                //CON area_produccion = 'S', aseguro que el traslado = 1, sea de un area de producción
                //if(palletizado > 0 /*&& traslado <= 1 /*&& (area_produccion == 'S' || area_produccion == '0')*/)
                //{
                var msj_preimprimir = 'Preimprimir';
                if (statusOT == 'T') msj_preimprimir = 'Imprimir';
                html += '<a href="#" onclick="imprimir_etiquetas_OT(\'' + lotes_etiq + '\', \'' + caducidad_etiq + '\', \'' + cve_articulo_etiq + '\', \'' + ordenp_etiq + '\', \'' + des_articulo_etiq + '\', 0)"><i class="fa fa-barcode" title="' + msj_preimprimir + ' Etiquetas"></i></a>&nbsp;&nbsp;';
                if (statusOT != 'P' && $("#permiso_editar").val() == 1)//status_nombre != 'Envio a Almac&eacute;n' &&
                    html += '<a href="#" onclick="reiniciar_etiquetado(\'' + ordenp_etiq + '\',\'' + cve_articulo_etiq + '\', \'' + lotes_etiq + '\', \'' + cantidad_producida + '\')"><i class="fa fa-refresh" title="Reiniciar Etiquetado"></i></a>&nbsp;&nbsp;';
                //}
            }

            html += '<a href="#" onclick="exportarPDF(\'' + serie + '\')"><i class="fa fa-file-pdf-o" title="Orden de Fabricación"></i></a>&nbsp;&nbsp;';

            html += '<a href="#" onclick="exportarExcel(\'' + serie + '\')"><i class="fa fa-file-excel-o" title="Orden de Fabricación"></i></a>&nbsp;&nbsp;';

            //if(statusOT == 'P' ){
            if ($("#permiso_registrar").val() == 1)
                html += '<a href="#" onclick="AsignarCliente(\'' + serie + '\')"><i class="fa fa-user-plus" title="Asignar Cliente"></i></a>&nbsp;&nbsp;';
            //}

            if (statusOT == 'T' && $("#permiso_registrar").val() == 1) {
                html += '<a href="#" onclick="embarcar_pedido_relacionado(\'' + rowObject[3] + '\', \'' + rowObject[5] + '\')"><i class="fa fa-truck" title="Relacionar Pedido a OT y Embarcar" aria-hidden="true"></i></a>&nbsp;&nbsp;';

                <?php

                if($ValorSAP == '1' && ($ejecutar_mostrar_SAP == 'M' || $ejecutar_mostrar_SAP == 'ME'))// && ($_SERVER['HTTP_HOST'] != 'dicoisa.assistprowms.com' || $_SERVER['HTTP_HOST'] != 'www.dicoisa.assistprowms.com')
                {
                ?>
                if ($("#permiso_registrar").val() == 1)
                    html += '<a href="#" onclick="OTSap(\'' + serie + '\')"><i class="fa fa-server" title="Enviar OT a SAP"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;';
                html += '<a href="#" onclick="HistorialSAP(\'' + serie + '\')"><i class="fa fa-list-alt" title="Ver Historial SAP"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;';
                <?php
                }

                ?>
            }

            if (statusOT == 'P') {
                if ($("#permiso_eliminar").val() == 1)
                    html += '<a href="#" onclick="eliminar(\'' + serie + '\')"><i class="fa fa-eraser" title="Eliminar Orden de Trabajo"></i></a>&nbsp;&nbsp;';

                <?php
                if($mostrar_ot_sin_surtir == '1')
                {
                ?>
                if ($("#permiso_editar").val() == 1)
                    html += '<a href="#" onclick="pasar_a_produccion_pregunta(\'' + serie + '\')"><i class="fa fa-share" title="Pasar la OT a Producción"></i></a>&nbsp;&nbsp;';
                <?php
                }
                ?>
            }

            if (statusOT == 'I' && TipoOT == 'IMP_LP' && $("#permiso_registrar").val() == 1) {
                var id_orden_trabajo = serie;
                var cantidad_art_compuesto = rowObject[9];
                var cod_art_compuesto = 'extraerLP';
                var tipo_orden_trabajo = TipoOT;
                var cve_articulo_LP = rowObject[5];
                html += '<a href="#" class="tarima_actual" onclick="asignarValoresProduccionLP(\'' + id_orden_trabajo + '\', \'' + cantidad_art_compuesto + '\', \'' + cod_art_compuesto + '\', \'' + tipo_orden_trabajo + '\', \'' + cve_articulo_LP + '\', \'' + cantidad_producida + '\')"><i class="fa fa-cogs" title="Producir Tarimas"></i></a>&nbsp;&nbsp;';


            }
            return html;

        }

        $(document).one('ajaxloadstart.page', function (e) {
            $(grid_selector).jqGrid('GridUnload');
            $('.ui-jqdialog').remove();
        });
    });


    //////////////////////////////////////////////////////////hace un reload del grid llamando al php en la carpera "c" para hacer la consulta//////////////////////////////////////////////////////////

    function almacenPrede() {
        $.ajax({
            type: "POST",
            dataType: "json",
            data: {
                idUser: '<?php echo $_SESSION["id_user"]?>',
                action: 'search_almacen_pre'
            },
            beforeSend: function (x) {
                if (x && x.overrideMimeType) {
                    x.overrideMimeType("application/json;charset=UTF-8");
                }
            },
            url: '/api/almacenPredeterminado/index.php',
            success: function (data) {
                if (data.success == true) {
                    document.getElementById('almacen').value = data.codigo.clave;
                    ReloadGrid();
                    //fillSelectZona(data.codigo.id);
                    $("#select-pallets").trigger("chosen:updated");
                }
            },
            error: function (res) {
                window.console.log(res);
            }
        });
    }

    almacenPrede();

    function ReloadGrid(cargargridtable0 = 1) {

        console.log("criterio: ", $("#criteriob").val());
        //console.log("filtro: ", $("#filtro").val());
        console.log("fechaInicio: ", $("#fechai").val());
        console.log("fechaFin: ", $("#fechaf").val());
        //console.log("folioInicio: ", $("#folio_inicio").val());
        //console.log("folioFin: ", $("#folio_final").val());

        if (cargargridtable0 == 1) {
            $('#grid-table').jqGrid('clearGridData')
                .jqGrid('setGridParam', {
                    postData: {
                        criterio: $("#criteriob").val(),
                        criterioLP: $("#criteriobLP").val(),
                        almacen: $("#almacen").val(),
                        Proveedor: $("#Proveedr").val(),
                        statusOT: $("#statusOT").val(),
                        //filtro: $("#filtro").val(),
                        fechaInicio: $("#fechai").val(),
                        fechaFin: $("#fechaf").val(),
                        //folioInicio: $("#folio_inicio").val(),
                        //folioFin: $("#folio_final").val(),
                    }, datatype: 'json', page: 1
                })
                .trigger('reloadGrid', [{current: true}]);
        } else {
            searchSelects($("#almacen").val());

            $('#grid-table4').jqGrid('clearGridData')
                .jqGrid('setGridParam', {
                    postData: {
                        action: 'lotes_kit',
                        //Folio_Pro: $("#id_orden_trabajo").val()
                        Folio_Pro: $("#ordenprod").text()
                    }, datatype: 'json', mtype: 'post',
                    loadComplete: function (data) {
                        //console.log("cargarLotes lotes_kit", $("#id_orden_trabajo").val());
                        //cargarLotes($("#id_orden_trabajo").val(), 1);
                        console.log("lotes_kit", data);
                        $("#lote_compuesto").val(data.lote_ot);
                        $("#lote_usado").val(data.lote_ot);
                        $("#caducidad_compuesto").val(data.caducidad);
                    }
                })
                .trigger('reloadGrid', [{current: true}]);


            console.log("Derivados KIT id_orden_trabajo = ", $("#ordenprod").text());
            $('#grid-table6').jqGrid('clearGridData')
                .jqGrid('setGridParam', {
                    postData: {
                        action: 'derivados_kit',
                        //Folio_Pro: $("#id_orden_trabajo").val()
                        Folio_Pro: $("#ordenprod").text()
                    }, datatype: 'json', mtype: 'post',
                    loadComplete: function (data) {
                        console.log("derivados_kit", data);
                        //if(data.realizo_fusion == true)
                        //    cargarLotes(_codigo, 1);
                    }
                })
                .trigger('reloadGrid', [{current: true}]);
        }


    }

    /*
    $("#criteriob").keyup(function(event){
    if(event.keyCode == 13){
        if($("#filtro").val()==""){
            swal({
                title: "¡Alerta!",
                text: "Debe indicar un filtro para su busqueda",
                type: "warning",
                showCancelButton: false,
            });
        }else{
            ReloadGrid();
      }
    }
});
*/

    $("#imprimir_etiquetas").click(function (e) {

        console.log("imprimir_etiquetas");
        console.log("cve_articulo");
        console.log("ordenp");
        console.log("des_articulo");
        console.log("lotes");

        if ($("#palletizar_imprimir").is(':checked')) {
            $("#select-pallets").trigger("chosen:updated");

            if (parseFloat($("#existencia_producto_terminado").text()) == 0) {
                $("#imprimir_etiquetas").prop("disabled", true);
            }

            if ($("#unidades_caja").val() == "" || $("#numero_impresiones").val() == "") {
                swal("Error", "Campo Vacío", "error");
                return;
            }

            if ((parseFloat($("#unidades_caja").val()) * parseFloat($("#numero_impresiones").val())) > parseFloat($("#existencia_producto_terminado").text())) {
                var hay = parseFloat($("#existencia_producto_terminado").text()),
                    se_mandaron = (parseFloat($("#unidades_caja").val()) * parseFloat($("#numero_impresiones").val()));
                swal("Error", "Solo hay " + hay + " unidad(es) de este producto y se mandaron a palletizar " + se_mandaron, "error");
                return;
            }
            console.log("Pallet SELECTED = ", $("#select-pallets").val());
            //return;
            //if($("#pallets").val() == "")
            if ($("#select-pallets").val() == "") {
                swal("Error", "Debe Seleccionar un Pallet", "error");
                return;
            }
            $("#selectOption").modal('hide');
            $("#imprimir_etiqueta").submit();
            ReloadGrid();
        } else {
            $("#selectOption").modal('hide');
            $("#imprimir_etiqueta").submit();
        }

    });

    $("#criteriob").keyup(function (event) {
        if (event.keyCode == 13) {
            ReloadGrid();
        }
    });

    /*
    $("#caducidad_compuesto").keydown(function(){
        return;
    });
*/

    function VerificarCaducidad(caducidad) {
        console.log("VerificarCaducidad = ", caducidad);

        var caducidad_formato = caducidad.split("-");
        var ok = 0;
        console.log("VerificarCaducidad F = ", caducidad_formato);
        console.log("fecha_actual = ", $("#fecha_actual").val());

        if (caducidad == '00-00-0000') {
            console.log("caducidad 00 = ", caducidad);
            ok = 1;
        } else if (Date.parse(caducidad_formato[2] + '-' + caducidad_formato[1] + '-' + caducidad_formato[0]) <= Date.parse($("#fecha_actual").val())) {
            console.log("caducidad pasada = ", Date.parse(caducidad_formato[2] + '-' + caducidad_formato[1] + '-' + caducidad_formato[0]));
            console.log("fecha_actual = ", Date.parse($("#fecha_actual").val()));
            ok = 2;
        }

        return ok;
    }

    function ImprimirFotos_th(folio) {
        console.log("ImprimirFotos_th", folio);
        $.ajax({
            type: "POST",
            //dataType: "json",
            data:
                {
                    folio: folio,
                    action: "cargarFotosTH"
                },/*
              beforeSend: function(x)
              {
                  if(x && x.overrideMimeType)
                  {
                    x.overrideMimeType("application/json;charset=UTF-8");
                  }
              },*/
            url: '/api/articulos/update/index.php',
            success: function (data) {

                $("#fotos_th").html(data);

            }, error: function (data) {
                console.log("ERROR Fotos Datos = ", data);
            }
        });
    }

    function DescargarDocumentos(folio) {
        console.log("ImprimirFotos_th", folio);
        $.ajax({
            type: "POST",
            //dataType: "json",
            data:
                {
                    folio: folio,
                    action: "DescargarDocumentos"
                },/*
              beforeSend: function(x)
              {
                  if(x && x.overrideMimeType)
                  {
                    x.overrideMimeType("application/json;charset=UTF-8");
                  }
              },*/
            url: '/api/articulos/update/index.php',
            success: function (data) {

                $("#fotos_th").html(data);

            }, error: function (data) {
                console.log("ERROR Fotos Datos = ", data);
            }
        });
    }

    function descargar_documentos(cve_articulo) {
        $("#folio_foto_th").val(cve_articulo);
        $("#n_folio").text(cve_articulo);
        $("#form-subir-fotos-th").hide();
        $modal0 = $("#fotos_oc_th");
        $modal0.modal('show');
        DescargarDocumentos(cve_articulo);
    }

    function ver_fotos() {
        if (!$("#codigo").val()) {
            swal("Error", "Es Necesario una clave de Artículo", "error");
            return;
        }
        $("#form-subir-fotos-th").show();
        $("#folio_foto_th").val($("#codigo").val());
        $("#n_folio").text($("#codigo").val());
        $modal0 = $("#fotos_oc_th");
        $modal0.modal('show');
        ImprimirFotos_th($("#codigo").val());
    }

    function exportarPDF(folio_ot) {
        console.log("folio_ot = ", folio_ot);

        var form = document.createElement('form'),
            input_nofooter = document.createElement('input'),
            input_folio_ot = document.createElement('input');

        form.setAttribute('method', 'post');
        form.setAttribute('action', '/ordentrabajo/pdf/exportar');
        form.setAttribute('target', '_blank');
        form.setAttribute('style', 'display:none');

        input_nofooter.setAttribute('name', 'nofooternoheader');
        input_nofooter.setAttribute('value', '1');

        input_folio_ot.setAttribute('name', 'folio_ot');
        input_folio_ot.setAttribute('value', folio_ot);


        form.appendChild(input_nofooter);
        form.appendChild(input_folio_ot);

        document.getElementsByTagName('body')[0].appendChild(form);
        form.submit();
    }

    function exportarExcel(folio_ot) {
        console.log("folio_ot = ", folio_ot);

        var form = document.createElement('form'),
            input_nofooter = document.createElement('input'),
            input_folio_ot = document.createElement('input');

        form.setAttribute('method', 'post');
        form.setAttribute('action', '/ordentrabajo/excel/exportar');
        form.setAttribute('target', '_blank');
        form.setAttribute('style', 'display:none');

        input_nofooter.setAttribute('name', 'nofooternoheader');
        input_nofooter.setAttribute('value', '1');

        input_folio_ot.setAttribute('name', 'folio_ot');
        input_folio_ot.setAttribute('value', folio_ot);


        form.appendChild(input_nofooter);
        form.appendChild(input_folio_ot);

        document.getElementsByTagName('body')[0].appendChild(form);
        form.submit();
    }

    function AsignarCliente(folio_OT) {
        $("#agregar_cliente #nombre_lista_asignar").text(folio_OT);
        $("#id_orden_trabajo").val(folio_OT);
        $("#agregar_cliente").modal("show");
        //loadDetalles(id);
    }

    function imprimir_etiquetas_OT(lotes, caducidad, cve_articulo, ordenp, des_articulo, boton) {
        if (boton == 0) {
            $("#lotes_etiq").val(lotes);
            $("#caducidad_etiq").val(caducidad);
            $("#cve_articulo_etiq").val(cve_articulo);
            $("#ordenp_etiq").val(ordenp);
            $("#des_articulo_etiq").val(des_articulo);

            if ($("#palletizar_imprimir").is(':checked'))
                $("#check_pallet_etiq").val(1);
            else
                $("#check_pallet_etiq").val(0);

            $("#consultar").val(1);
            console.log("consultar", $("#consultar").val());
            $("#imprimir_etiqueta").submit();
        } else {

            $("#lotes_etiq").val(lotes);
            $("#caducidad_etiq").val(caducidad);
            $("#cve_articulo_etiq").val(cve_articulo);
            $("#ordenp_etiq").val(ordenp);
            $("#des_articulo_etiq").val(des_articulo);

            if ($("#palletizar_imprimir").is(':checked'))
                $("#check_pallet_etiq").val(1);
            else
                $("#check_pallet_etiq").val(0);


            $("#consultar").val(0);
            console.log("consultar", $("#consultar").val());
            $("#selectOption").modal('show');
        }
    }

    function reiniciar_etiquetado(folio, cve_articulo, cve_lote, cantidad_producida) {

        console.log("folio:", folio);
        console.log("cve_articulo:", cve_articulo);
        console.log("cve_lote:", cve_lote);
        console.log("cantidad_producida:", cantidad_producida);
        console.log("almacen:", $("#almacen").val());
        swal({
                title: "Aviso",
                text: "Esta Seguro de proceder a Reiniciar el Etiquetado? Esto Borra todas las etiquetas ya generadas y restaura la existencia del producto para volver a iniciar el proceso",
                type: "warning",

                showCancelButton: true,
                cancelButtonText: "No",
                cancelButtonColor: "#55b9dd",

                confirmButtonColor: "#55b9dd",
                confirmButtonText: "Reiniciar",
                closeOnConfirm: true
            },
            function (e) {
                if (e == true) {
                    $.ajax({
                        url: '/api/adminordentrabajo/update/index.php',
                        data: {
                            folio: folio,
                            cve_articulo: cve_articulo,
                            cve_lote: cve_lote,
                            cantidad_producida: cantidad_producida,
                            almacen: $("#almacen").val(),
                            action: 'reiniciar_etiquetado'
                        },
                        datatype: 'json',
                        type: 'POST'
                    }).done(function (data) {
                        //var data = JSON.parse(data);
                        console.log("DATA Etiquetado = ", data);
                        swal('Éxito', 'Reinicio de Etiquetado Realizado', 'success');
                        ReloadGrid();
                    });
                }
            });

    }


    $("#depurarOT").click(function () {

        swal({
                title: "Aviso",
                text: "Esta Seguro de proceder a Depurar las OT? Esto Borra TODAS las OT PENDIENTES ya generadas de Ambos Almacenes pero se reintegrarán las activas en SAP",
                type: "warning",

                showCancelButton: true,
                cancelButtonText: "No",
                cancelButtonColor: "#55b9dd",

                confirmButtonColor: "#55b9dd",
                confirmButtonText: "Depurar",
                closeOnConfirm: true
            },
            function (e) {
                if (e == true) {
                    $.ajax({
                        url: '/api/adminordentrabajo/update/index.php',
                        data: {
                            action: 'depurar_ots'
                        },
                        datatype: 'json',
                        type: 'POST'
                    }).done(function (data) {
                        //var data = JSON.parse(data);
                        swal('Éxito', 'Todas las OT han sido depuradas', 'success');
                        ReloadGrid();
                    });
                }
            });

    });

    function cambiar_status(folio) {

        console.log("folio:", folio);

        swal({
                title: "Aviso",
                text: "La Orden de Trabajo: " + folio + " se reiniciará a Status En Producción Nuevamente",
                type: "warning",

                showCancelButton: true,
                cancelButtonText: "No",
                cancelButtonColor: "#55b9dd",

                confirmButtonColor: "#55b9dd",
                confirmButtonText: "Reiniciar",
                closeOnConfirm: true
            },
            function (e) {
                if (e == true) {
                    $.ajax({
                        url: '/api/adminordentrabajo/update/index.php',
                        data: {
                            folio: folio,
                            action: 'cambiar_status'
                        },
                        datatype: 'json',
                        type: 'POST'
                    }).done(function (data) {
                        //var data = JSON.parse(data);
                        console.log("DATA Cambiar Status = ", data);
                        swal('Éxito', 'Cambio de Status Realizado', 'success');
                        ReloadGrid();
                    });
                }
            });

    }

    function embarcar_pedido_relacionado(folio_ot, cve_articulo) {
        console.log("*************************");
        console.log("PEDIDOS RELACIONADOS");
        console.log("*************************");
        console.log("folio = ", folio_ot);
        console.log("cve_articulo = ", cve_articulo);
        console.log("almacen = ", $("#almacen").val());
        console.log("*************************");
        $("#id_orden_trabajo").val(folio_ot);
        $.ajax({
            url: '/api/adminordentrabajo/update/index.php',
            data: {
                folio: folio_ot,
                cve_articulo: cve_articulo,
                almacen: $("#almacen").val(),
                action: 'select_pedidos'
            },
            datatype: 'json',
            type: 'POST'
        }).done(function (data) {
            //var data = JSON.parse(data);
            console.log("DATA = ", data);
            $("#pedidos_rel").empty();
            $("#pedidos_rel").append(data);

        });
        $("#select_pedidos").modal('show');
    }

    $("#imprimir_etiqueta_btn").click(function () {
        //console.log("folio imprimir_etiqueta_btn = ", $("#id_orden_trabajo").val());

        $("#producto_des").text($("#cve_articulo_etiq").val() + ' - ' + $("#des_articulo_etiq").val());

        $.ajax({
            url: '/api/adminordentrabajo/update/index.php',
            data: {
                folio: $("#id_orden_trabajo").val(),
                action: 'existencia_producto_terminado'
            },
            datatype: 'json',
            type: 'POST'
        }).done(function (data) {
            //var data = JSON.parse(data);
            $("#existencia_producto_terminado").text(data);

        });


        //imprimir_etiquetas_OT('', '', '', '', '', 1);
        //imprimir_etiquetas_OT(lotes, caducidad, cve_articulo, ordenp, des_articulo, boton)
        imprimir_etiquetas_OT($("#lote_compuesto").val(), $("#caducidad_compuesto").val(), $("#compuesto_clave").text(), $("#id_orden_trabajo").val(), $("#compuesto_descripcion").text(), 1);
    });

    function detalle(_codigo, art_comp) {

        $('#folio_ot b').text(_codigo);
        $('#producto_compuesto b').text(art_comp);

        loadDetalle(_codigo);
        $modal0 = $("#coModal");
        $modal0.modal('show');
    }

    $('#data_1').datetimepicker({
        locale: 'es',
        format: 'DD-MM-YYYY',
        useCurrent: false
    });

    $('#data_2').datetimepicker({
        locale: 'es',
        format: 'DD-MM-YYYY',
        useCurrent: false
    });

    $('#data_3').datetimepicker({
        locale: 'es',
        format: 'DD-MM-YYYY',
        useCurrent: true
    });

    $('#data_3').data("DateTimePicker").minDate(new Date());

    $('#data_3_derivado').datetimepicker({
        locale: 'es',
        format: 'DD-MM-YYYY',
        useCurrent: true
    });

    //$('#data_3_derivado').data("DateTimePicker").minDate(new Date());

</script>

<script type="text/javascript">

    //////////////////////////////////////////////////////////Aqui se contruye el Grid//////////////////////////////////////////////////////////
    $(function ($) {
        var grid_selector2 = "#grid-table2";
        var pager_selector2 = "#grid-pager2";


        //resize to fit page size
        $(window).on('resize.jqGrid', function () {
            $(grid_selector2).jqGrid('setGridWidth', $(".page-content").width() - 60);
        })
        //resize on sidebar collapse/expand
        var parent_column = $(grid_selector2).closest('[class*="col-"]');
        $(document).on('settings.ace.jqGrid', function (ev, event_name, collapsed) {
            if (event_name === 'sidebar_collapsed' || event_name === 'main_container_fixed') {
                //setTimeout is for webkit only to give time for DOM changes and then redraw!!!
                setTimeout(function () {
                    $(grid_selector2).jqGrid('setGridWidth', parent_column.width());
                }, 0);
            }
        })
        /*
        //resize to fit page size
        $(window).on("resize", function () {
            var $grid = $("#grid-table2"),
                newWidth = $grid.closest(".ui-jqgrid").parent().width();
            $grid.jqGrid("setGridWidth", newWidth, true);
        });


        //resize on sidebar collapse/expand
        var parent_column = $(grid_selector2).closest('[class*="col-"]');
        $(document).on('settings.ace.jqGrid' , function(ev, event_name, collapsed) {
            if( event_name === 'sidebar_collapsed' || event_name === 'main_container_fixed' ) {
                //setTimeout is for webkit only to give time for DOM changes and then redraw!!!
                setTimeout(function() {
                    $(grid_selector2).jqGrid( 'setGridWidth', parent_column.width() );
                }, 0);
            }
        })
*/
        $(grid_selector2).jqGrid({
            url: '/api/adminordentrabajo/update/index.php',
            datatype: "local",
            shrinkToFit: false,
            //autowidth: true,
            //width: '1280',
            height: 250,
            postData: {
                criterio: $("#txtCriterio1").val()
            },
            mtype: 'POST',
            colNames: ['Folio', 'Proveedor', 'Clave de Producto', 'Nombre de Producto', "Lote", "Caducidad", "Fecha de Inicio", "Cantidad Requerida", "Existencia Pz", "Existencia Kg", "Usuario"],
            colModel: [
                {name: 'Folio_Pro', index: 'Folio_Pro', width: 80, editable: false, sortable: false},
                {name: 'proveedor', index: 'proveedor', width: 120, editable: false, sortable: false},
                {name: 'Cve_Articulo', index: 'Cve_Articulo', width: 120, editable: false, sortable: false},
                {name: 'descripcion', index: 'descripcion', width: 150, editable: false, sortable: false},
                {name: 'Cve_Lote', index: 'Cve_Lote', width: 200, editable: true, sortable: false},
                {name: 'Caducidad', index: 'Caducidad', width: 80, editable: false, sortable: false},
                {name: 'Fecha_Prod', index: 'Fecha_Prod', editable: false, sortable: false},
                {
                    name: 'Cantidad',
                    index: 'Cantidad',
                    align: 'right',
                    editable: false,
                    sortable: false,
                    resizable: false
                },
                {
                    name: 'existencia',
                    index: 'existencia',
                    width: 80,
                    align: 'right',
                    editable: false,
                    sortable: false,
                    resizable: false
                },
                {
                    name: 'existenciakg',
                    index: 'existenciakg',
                    width: 80,
                    align: 'right',
                    editable: false,
                    sortable: false,
                    resizable: false
                },
                {name: 'usuario', index: 'usuario', editable: false, width: 80, sortable: false, resizable: false},
            ],
            rowNum: 10,
            rowList: [10, 20, 30],
            pager: pager_selector2,
            sortname: 'Folio_Pro',
            viewrecords: true,
            sortorder: "desc",
            loadComplete: function (data) {
                console.log("SUCCESS", data);
            },
            loadError: function (data) {
                console.log("ERROR 5", data);
            }

        });


        // Setup buttons
        $("#grid-table2").jqGrid('navGrid', '#grid-pager2',
            {edit: false, add: false, del: false, search: false},
            {height: 200, reloadAfterSubmit: true}
        );


        $(window).triggerHandler('resize.jqGrid');//trigger window resize to make the grid get the correct size
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        $(document).one('ajaxloadstart.page', function (e) {
            $(grid_selector2).jqGrid('GridUnload');
            $('.ui-jqdialog').remove();
        });

        //$(".select_lotes").change(function()
        //{
        //    console.log("Lotes");
        //});


    });


    //////////////////////////////////////////////////////////Aqui se contruye el Grid//////////////////////////////////////////////////////////
    $(function ($) {
        var grid_selector2 = "#grid-table8";
        var pager_selector2 = "#grid-pager8";


        //resize to fit page size
        $(window).on('resize.jqGrid', function () {
            $(grid_selector2).jqGrid('setGridWidth', $(".page-content").width() - 60);
        })
        //resize on sidebar collapse/expand
        var parent_column = $(grid_selector2).closest('[class*="col-"]');
        $(document).on('settings.ace.jqGrid', function (ev, event_name, collapsed) {
            if (event_name === 'sidebar_collapsed' || event_name === 'main_container_fixed') {
                //setTimeout is for webkit only to give time for DOM changes and then redraw!!!
                setTimeout(function () {
                    $(grid_selector2).jqGrid('setGridWidth', parent_column.width());
                }, 0);
            }
        })
        /*
        //resize to fit page size
        $(window).on("resize", function () {
            var $grid = $("#grid-table8"),
                newWidth = $grid.closest(".ui-jqgrid").parent().width();
            $grid.jqGrid("setGridWidth", newWidth, true);
        });


        //resize on sidebar collapse/expand
        var parent_column = $(grid_selector2).closest('[class*="col-"]');
        $(document).on('settings.ace.jqGrid' , function(ev, event_name, collapsed) {
            if( event_name === 'sidebar_collapsed' || event_name === 'main_container_fixed' ) {
                //setTimeout is for webkit only to give time for DOM changes and then redraw!!!
                setTimeout(function() {
                    $(grid_selector2).jqGrid( 'setGridWidth', parent_column.width() );
                }, 0);
            }
        })
*/
        $(grid_selector2).jqGrid({
            url: '/api/adminordentrabajo/update/index.php',
            datatype: "local",
            shrinkToFit: false,
            //autowidth: true,
            //width: '1280',
            height: 250,
            postData: {
                criterio: $("#txtCriterio1").val()
            },
            mtype: 'POST',
            colNames: ['Fecha', 'Cadena', 'Respuesta', 'Modulo', "Funcion"],
            colModel: [
                {name: 'fecha', index: 'fecha', width: 150, editable: false, sortable: false},
                {name: 'cadena', index: 'cadena', width: 500, editable: false, sortable: false},
                {name: 'respuesta', index: 'respuesta', width: 400, editable: false, sortable: false},
                {name: 'modulo', index: 'modulo', width: 180, editable: false, sortable: false},
                {name: 'funcion', index: 'funcion', width: 150, editable: true, sortable: false},
            ],
            rowNum: 10,
            rowList: [10, 20, 30],
            pager: pager_selector2,
            sortname: 'Folio_Pro',
            viewrecords: true,
            sortorder: "desc",
            loadComplete: function (data) {
                console.log("SUCCESS", data);
            },
            loadError: function (data) {
                console.log("ERROR 5", data);
            }

        });


        // Setup buttons
        $("#grid-table8").jqGrid('navGrid', '#grid-pager8',
            {edit: false, add: false, del: false, search: false},
            {height: 200, reloadAfterSubmit: true}
        );


        $(window).triggerHandler('resize.jqGrid');//trigger window resize to make the grid get the correct size
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        $(document).one('ajaxloadstart.page', function (e) {
            $(grid_selector2).jqGrid('GridUnload');
            $('.ui-jqdialog').remove();
        });

        //$(".select_lotes").change(function()
        //{
        //    console.log("Lotes");
        //});


    });

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    $(function ($) {
        var grid_selector3 = "#grid-table3";
        var pager_selector3 = "#grid-pager3";

        //resize to fit page size
        // $(window).on("resize", function () {
        //     var $grid = $("#grid-table3"),
        //         newWidth = $grid.closest(".ui-jqgrid").parent().width();
        //     $grid.jqGrid("setGridWidth", newWidth, true);
        // });


        //resize on sidebar collapse/expand
        var parent_column = $(grid_selector3).closest('[class*="col-"]');
        $(document).on('settings.ace.jqGrid', function (ev, event_name, collapsed) {
            if (event_name === 'sidebar_collapsed' || event_name === 'main_container_fixed') {
                //setTimeout is for webkit only to give time for DOM changes and then redraw!!!
                setTimeout(function () {
                    $(grid_selector3).jqGrid('setGridWidth', parent_column.width());
                }, 0);
            }
        })

        $(grid_selector3).jqGrid({
            url: '/api/adminordentrabajo/lista/index.php',
            datatype: "local",
            height: "auto",
            cache: false,
            shrinkToFit: false,
            width: null,
            colNames: ['LP', 'BL', 'Clave', 'Descripcion', 'Lote', 'Caducidad', 'Folio', 'Stock'],
            colModel: [
                {name: 'LP', index: 'LP', editable: false, sortable: false, hidden: true},
                {name: 'BL', index: 'BL', editable: false, sortable: false},
                {name: 'clave', index: 'clave', editable: false, sortable: false},
                {name: 'descripcion', index: 'descripcion', width: 300, editable: false, sortable: false},
                {name: 'lote', index: 'lote', editable: false, sortable: false},
                {name: 'caducidad', index: 'caducidad', editable: false, sortable: false, align: 'center'},
                {name: 'folio', index: 'folio', editable: false, sortable: false, hidden: true},
                {name: 'cantidad', index: 'cantidad', editable: false, sortable: false, align: 'right'},
            ],
            rowNum: 10,
            rowList: [10, 20, 30],
            pager: pager_selector3,
            sortname: 'Folio_Pro',
            viewrecords: true,
            sortorder: "desc",
            loadComplete: function (data) {
                console.log("OK grid-table3");
                console.log("SUCCESS grid-table3: ", data);
            },
            loadError: function (data) {
                console.log("ERROR grid-table3: ", data);
            }
        });

        // Setup buttons
        $("#grid-table3").jqGrid('navGrid', '#grid-pager3',
            {edit: false, add: false, del: false, search: false},
            {height: 200, reloadAfterSubmit: true}
        );


        $(window).triggerHandler('resize.jqGrid');//trigger window resize to make the grid get the correct size
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        $(document).one('ajaxloadstart.page', function (e) {
            $(grid_selector3).jqGrid('GridUnload');
            $('.ui-jqdialog').remove();
        });
    });

    $(function ($) {
        var grid_selector4 = "#grid-table4";
        var pager_selector4 = "#grid-pager4";

        //resize to fit page size
        // $(window).on("resize", function () {
        //     var $grid = $("#grid-table4"),
        //         newWidth = $grid.closest(".ui-jqgrid").parent().width();
        //     $grid.jqGrid("setGridWidth", newWidth, true);
        // });


        //resize on sidebar collapse/expand
        var parent_column = $(grid_selector4).closest('[class*="col-"]');
        $(document).on('settings.ace.jqGrid', function (ev, event_name, collapsed) {
            if (event_name === 'sidebar_collapsed' || event_name === 'main_container_fixed') {
                //setTimeout is for webkit only to give time for DOM changes and then redraw!!!
                setTimeout(function () {
                    $(grid_selector4).jqGrid('setGridWidth', parent_column.width());
                }, 0);
            }
        })

        $(grid_selector4).jqGrid({
            url: '/api/adminordentrabajo/lista/index.php',
            datatype: "local",
            height: "auto",
            shrinkToFit: false,
            width: null,
            colNames: ['License Plate', 'BL', 'Clave', 'Descripcion', 'Lote', 'Caducidad | Fecha de Elaboración', 'Folio', 'Stock'],
            colModel: [
                {name: 'lp', index: 'lp', width: 120, editable: false, sortable: false},
                {name: 'bl', index: 'bl', width: 120, editable: false, sortable: false},
                {name: 'clave', index: 'clave', editable: false, sortable: false},
                {name: 'descripcion', index: 'descripcion', width: 300, editable: false, sortable: false},
                {name: 'lote', index: 'lote', editable: false, sortable: false},
                {name: 'caducidad', index: 'caducidad', editable: false, sortable: false, align: 'center', width: 220},
                {name: 'folio', index: 'folio', editable: false, sortable: false, hidden: true},
                {name: 'cantidad', index: 'cantidad', editable: false, sortable: false, align: 'right'},
            ],
            loadComplete: function (data) {
                //console.log("loadComplete", data);
                var lote = $("#grid-table4").jqGrid('getCell', 0, 'lote');
                var caducidad = $("#grid-table4").jqGrid('getCell', 0, 'caducidad');
                var clave = $("#grid-table4").jqGrid('getCell', 0, 'clave');
                var descripcion = $("#grid-table4").jqGrid('getCell', 0, 'descripcion');

                $("#lotes_etiq").val(lote);
                $("#caducidad_etiq").val(caducidad);
                $("#cve_articulo_etiq").val(clave);
                $("#ordenp_etiq").val(lote);
                $("#des_articulo_etiq").val(descripcion);

                if ($("#palletizar_imprimir").is(':checked'))
                    $("#check_pallet_etiq").val(1);
                else
                    $("#check_pallet_etiq").val(0);
            },
            rowNum: 10,
            rowList: [10, 20, 30],
            pager: pager_selector4,
            sortname: 'Folio_Pro',
            viewrecords: true,
            sortorder: "desc"
        });


        // Setup buttons
        $("#grid-table4").jqGrid('navGrid', '#grid-pager4',
            {edit: false, add: false, del: false, search: false},
            {height: 200, reloadAfterSubmit: true}
        );


        $(window).triggerHandler('resize.jqGrid');//trigger window resize to make the grid get the correct size
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        $(document).one('ajaxloadstart.page', function (e) {
            $(grid_selector6).jqGrid('GridUnload');
            $('.ui-jqdialog').remove();
        });
    });

    $(function ($) {
        var grid_selector6 = "#grid-table6";
        var pager_selector6 = "#grid-pager6";

        //resize to fit page size
        // $(window).on("resize", function () {
        //     var $grid = $("#grid-table6"),
        //         newWidth = $grid.closest(".ui-jqgrid").parent().width();
        //     $grid.jqGrid("setGridWidth", newWidth, true);
        // });


        //resize on sidebar collapse/expand
        var parent_column = $(grid_selector6).closest('[class*="col-"]');
        $(document).on('settings.ace.jqGrid', function (ev, event_name, collapsed) {
            if (event_name === 'sidebar_collapsed' || event_name === 'main_container_fixed') {
                //setTimeout is for webkit only to give time for DOM changes and then redraw!!!
                setTimeout(function () {
                    $(grid_selector6).jqGrid('setGridWidth', parent_column.width());
                }, 0);
            }
        })

        $(grid_selector6).jqGrid({
            url: '/api/adminordentrabajo/lista/index.php',
            datatype: "local",
            height: "auto",
            shrinkToFit: false,
            width: null,
            colNames: ['BL', 'Clave', 'Descripcion', 'Lote', 'Caducidad', 'Stock'],
            colModel: [
                {name: 'bl', index: 'bl', width: 120, editable: false, sortable: false},
                {name: 'clave', index: 'clave', editable: false, sortable: false},
                {name: 'descripcion', index: 'descripcion', width: 300, editable: false, sortable: false},
                {name: 'lote', index: 'lote', editable: false, sortable: false},
                {name: 'caducidad', index: 'caducidad', editable: false, sortable: false, align: 'center', width: 220},
                {name: 'cantidad', index: 'cantidad', editable: false, sortable: false, align: 'right'},
            ],
            loadComplete: function (data) {
                //console.log("loadComplete", data);
                //var lote = $("#grid-table6").jqGrid('getCell', 0, 'lote');
                //var caducidad = $("#grid-table6").jqGrid('getCell', 0, 'caducidad');
                //var clave = $("#grid-table6").jqGrid('getCell', 0, 'clave');
                //var descripcion = $("#grid-table6").jqGrid('getCell', 0, 'descripcion');
                /*
                $("#lotes_etiq").val(lote);
                $("#caducidad_etiq").val(caducidad);
                $("#cve_articulo_etiq").val(clave);
                $("#ordenp_etiq").val(lote);
                $("#des_articulo_etiq").val(descripcion);

                if($("#palletizar_imprimir").is(':checked'))
                   $("#check_pallet_etiq").val(1);
                else
                   $("#check_pallet_etiq").val(0);
*/
            },
            rowNum: 10,
            rowList: [10, 20, 30],
            pager: pager_selector6,
            sortname: 'Folio_Pro',
            viewrecords: true,
            sortorder: "desc"
        });


        // Setup buttons
        $("#grid-table6").jqGrid('navGrid', '#grid-pager6',
            {edit: false, add: false, del: false, search: false},
            {height: 200, reloadAfterSubmit: true}
        );


        $(window).triggerHandler('resize.jqGrid');//trigger window resize to make the grid get the correct size
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        $(document).one('ajaxloadstart.page', function (e) {
            $(grid_selector6).jqGrid('GridUnload');
            $('.ui-jqdialog').remove();
        });
    });


    $(function ($) {
        var grid_selector5 = "#grid-table5";
        var pager_selector5 = "#grid-pager5";

        //resize to fit page size
        // $(window).on("resize", function () {
        //     var $grid = $("#grid-table5"),
        //         newWidth = $grid.closest(".ui-jqgrid").parent().width();
        //     $grid.jqGrid("setGridWidth", newWidth, true);
        // });


        //resize on sidebar collapse/expand
        var parent_column = $(grid_selector5).closest('[class*="col-"]');
        $(document).on('settings.ace.jqGrid', function (ev, event_name, collapsed) {
            if (event_name === 'sidebar_collapsed' || event_name === 'main_container_fixed') {
                //setTimeout is for webkit only to give time for DOM changes and then redraw!!!
                setTimeout(function () {
                    $(grid_selector5).jqGrid('setGridWidth', parent_column.width());
                }, 0);
            }
        })

        $(grid_selector5).jqGrid({
            url: '/api/adminordentrabajo/lista/index.php',
            datatype: "local",
            height: "auto",
            cache: false,
            shrinkToFit: false,
            width: null,
            colNames: ['Clave', 'Producto', 'Unidad de Medida', 'Unidades por Producto', 'Cantidad Requerida', 'Producto Utilizado', 'Producto Faltante', 'Prod Sobrante', 'Merma'],
            colModel: [
                {
                    name: 'clave',
                    index: 'clave',
                    width: 100,
                    editable: false,
                    sortable: false,
                    key: false,
                    align: 'right'
                },
                {name: 'producto', index: 'producto', width: 200, editable: false, sortable: false, key: true},
                {name: 'unidad', index: 'unidad', width: 150, editable: false, sortable: false},
                {name: 'unidades_p', index: 'unidades_p', width: 150, editable: false, sortable: false, align: 'right'},
                {name: 'cantidad', index: 'cantidad', width: 150, editable: false, sortable: false, align: 'right'},
                {
                    name: 'Cantidad_Producida',
                    index: 'Cantidad_Producida',
                    editable: false,
                    sortable: false,
                    resizable: false,
                    align: 'right',
                    hidden: true
                },
                {
                    name: 'Cantidad_Faltante',
                    index: 'Cantidad_Faltante',
                    editable: false,
                    sortable: false,
                    resizable: false,
                    align: 'right',
                    hidden: true
                },
                {
                    name: 'prod_sobrante',
                    index: 'prod_sobrante',
                    editable: true,
                    sortable: false,
                    resizable: false,
                    align: 'right',
                    width: 100,
                    hidden: true
                },
                {
                    name: 'merma',
                    index: 'merma',
                    editable: false,
                    sortable: false,
                    resizable: false,
                    align: 'right',
                    width: 80,
                    hidden: true
                },
            ],
            rowNum: 10,
            rowList: [10, 20, 30],
            pager: pager_selector5,
            sortname: 'Folio_Pro',
            viewrecords: true,
            sortorder: "desc"
        });


        // Setup buttons
        $("#grid-table5").jqGrid('navGrid', '#grid-pager5',
            {edit: false, add: false, del: false, search: false},
            {height: 200, reloadAfterSubmit: true}
        );


        $(window).triggerHandler('resize.jqGrid');//trigger window resize to make the grid get the correct size
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        $(document).one('ajaxloadstart.page', function (e) {
            $(grid_selector5).jqGrid('GridUnload');
            $('.ui-jqdialog').remove();
        });
    });


    $("#palletizar_imprimir").click(function () {
        if ($("#palletizar_imprimir").is(':checked')) {
            $("#pallets").show();
            if (parseFloat($("#existencia_producto_terminado").text()) == 0) {
                $("#imprimir_etiquetas").prop("disabled", true);
            }
            $("#check_pallet_etiq").val(1);
        } else {
            $("#pallets").hide();
            $("#check_pallet_etiq").val(0);
            $("#imprimir_etiquetas").prop("disabled", false);
        }

        $("#select-pallets").trigger("chosen:updated");
    });

    function fillSelectPallets(node) {
        //searchSelects($("#almacen").val());
        var select_pallets = document.getElementById('select-pallets');

        var options = "";//"<option value = ''>Seleccione un Pallet | Contenedor</option>";

        if (node) {

            options = "";//"<option value = ''>Seleccione un Pallet | Contenedor ("+node.length+")</option>";

            for (var i = 0; i < node.length; i++) {
                options += "<option value = " + node[i].IDContenedor + ">" + ("( " + node[i].clave_contenedor + " ) " + node[i].descripcion) + "</option>";
                //options += "<option value = "+node[i].IdCont+">"+htmlEntities("( "+node[i].Cve_Cont+" ) "+node[i].DesCont)+"</option>";
            }
        }

        select_pallets.innerHTML = options;
        $(select_pallets).trigger("chosen:updated");
    }

    function searchSelects(almacen) {

        //var almacen = select_almacen.value;
        console.log("almacend = ", almacen);

        $.ajax({
            url: "/api/qaauditoria/index.php",
            type: "POST",
            data: {
                "action": "enter-view",
                ordentrabajo: true,
                alma: almacen
            },
            beforeSend: function (x) {
                if (x && x.overrideMimeType) x.overrideMimeType("application/json;charset=UTF-8");
            },
            success: function (res) {
                console.log("SUCCESS PALLETS = ", res);
                fillSelectPallets(res.pallets);

            },
            error: function (res) {
                console.log("ERROR PALLETS = ", res);
            }
        });
    }

    function change_lote(lote) {
        console.log("ID Lote = ", lote);

        var lotes = lote.split(";;;;");
        console.log("Lote Folio = ", lotes[0]);
        console.log("Lote Cve_Articulo = ", lotes[1]);
        console.log("Lote Value = ", lotes[2]);
        console.log("Lote id_orden = ", lotes[3]);
        console.log("Lote id_pedido = ", lotes[4]);

        $.ajax({
            url: '/api/adminordentrabajo/update/index.php',
            data: {
                folio: lotes[0],
                cve_articulo: lotes[1],
                val_lote: lotes[2],
                id_orden: lotes[3],
                id_pedido: lotes[4],
                action: 'asignar_lote'
            },
            datatype: 'json',
            method: 'POST'
        }).done(function (data) {
            console.log(data);
            loadDetalle(lotes[0]);
            //swal('Éxito', 'Producción Terminada', 'success');
            //window.location.reload();
        });
    }

    function cantidad_asignar(id_orden, folio, clave, cantidad, existencia, id_pedido) {

        //$("#a_asignar").show();
        var cantidad_asig = (parseFloat(cantidad) > parseFloat(existencia)) ? parseFloat(existencia) : parseFloat(cantidad);

        $("#asignar_lote_modal").modal('show');
        $("#cantidad_lote").val(cantidad_asig);
        $("#cantidad_lote").data("id_orden", id_orden);
        $("#cantidad_lote").data("folio", folio);
        $("#cantidad_lote").data("clave", clave);
        $("#cantidad_lote").data("cantidad", cantidad);
        $("#cantidad_lote").data("existencia", existencia);
        $("#cantidad_lote").data("id_pedido", id_pedido);
        $("#cantidad_lote").attr("max", cantidad_asig);
        //$("#a_asignar span").text(existencia);
    }

    //$("#boton_cancelar").click(function(){
    //    $("#a_asignar").hide();
    //});

    $("#boton_asignar").click(function () {

        console.log("Asignar = ", $("#cantidad_lote").val());
        console.log("id_orden = ", $("#cantidad_lote").data("id_orden"));
        console.log("folio = ", $("#cantidad_lote").data("folio"));
        console.log("clave = ", $("#cantidad_lote").data("clave"));
        console.log("cantidad = ", $("#cantidad_lote").data("cantidad"));
        console.log("existencia = ", $("#cantidad_lote").data("existencia"));
        console.log("id_pedido = ", $("#cantidad_lote").data("id_pedido"));
        console.log("cantidad_lote max = ", $("#cantidad_lote").attr("max"));

        if (parseFloat($("#cantidad_lote").val()) <= parseFloat($("#cantidad_lote").attr("max"))) {
            $("#asignar_lote_modal").modal('hide');
            agregar_lote($("#cantidad_lote").data("id_orden"), $("#cantidad_lote").data("folio"), $("#cantidad_lote").data("clave"), $("#cantidad_lote").val(), $("#cantidad_lote").data("existencia"), $("#cantidad_lote").data("id_pedido"), $("#cantidad_lote").data("cantidad"));
        } else
            swal('Error', 'No puede asignar una cantidad Mayor a la restante', 'error');

    });


    function agregar_lote(id_orden, folio, clave, cantidad, existencia, id_pedido, cantidad_req) {
        $.ajax({
            url: '/api/adminordentrabajo/update/index.php',
            data: {
                id_orden: id_orden,
                folio: folio,
                cve_articulo: clave,
                cantidad: cantidad,
                cantidad_req: cantidad_req,
                existencia: existencia,
                id_pedido: id_pedido,
                action: 'agregar_lote'
            },
            datatype: 'json',
            method: 'POST'
        }).done(function (data) {
            console.log(data);
            loadDetalle(folio);
            //swal('Éxito', 'Producción Terminada', 'success');
            //window.location.reload();
        });
    }

    function loadDetalle(_codigo) {
        $('#grid-table2').jqGrid('clearGridData')
            .jqGrid('setGridParam', {
                postData: {
                    action: 'detalle',
                    Folio_Pro: _codigo
                }, datatype: 'json'
            })
            .trigger('reloadGrid', [{current: true}]);
    }

    function HistorialSAP(_codigo) {

        $("#folio_ot_sap").text(_codigo);
        $modal0 = $("#historialsap");
        $modal0.modal('show');

        $('#grid-table8').jqGrid('clearGridData')
            .jqGrid('setGridParam', {
                postData: {
                    action: 'HistorialSAP',
                    Folio_Pro: _codigo
                }, datatype: 'json'
            })
            .trigger('reloadGrid', [{current: true}]);
    }

    var nlotes;

    function cargarLotes(_codigo, primera_produccion) {
        console.log("cargarLotes", _codigo);
        console.log("Tipo_OT", $("#tipo_orden_trabajo").val());
        if ($("#select_BL").val() == "") {
            $('#grid-table3').empty();
            return;
        }
        $('#grid-table3').jqGrid('clearGridData')
            .jqGrid('setGridParam', {
                postData: {
                    action: 'lotes',
                    Tipo_OT: $("#tipo_orden_trabajo").val(),
                    producido: $("#producida").text(),
                    Folio_Pro: $("#id_orden_trabajo").val()
                }, datatype: 'json', mtype: 'post',
                loadComplete: function (data) {
                    console.log("OK lotes grid-table3");
                    console.log("lotes", data);
                    nlotes = data.rows;

                    if ((data.records > 1 || data.records == 0 || data.rows[0].cell[7] == 0) && parseFloat($("#faltante").text()) <= 0) $("#label_merma").hide();
                    else {
                        console.log("Stock Componente = ", data.rows[0].cell[7], " cve_articulo_componente = ", data.rows[0].cell[7], "articulo_componente = ", data.rows[0].cell[7]);

                        $("#articulo_componente").val("[" + data.rows[0].cell[2] + "] - " + data.rows[0].cell[3]);
                        $("#cve_articulo_componente").val(data.rows[0].cell[2]);
                        $("#lote_compuesto_derivado_merma").val(data.rows[0].cell[4]);
                        $("#caducidad_compuesto_derivado_merma").val(data.rows[0].cell[5]);
                        //$("#lote_componente_merma").val(data.rows[0].cell[4]);
                        $("#cantidad_sobrante_merma").val(data.rows[0].cell[7]);
                    }
                    //console.log("N row lotes", nlotes.length);
                    //if(nlotes == 1) $("#registrar_merma_derivado").show();
                }, loadError: function (data) {
                    console.log("ERROR lotes", data);
                }
            })
            .trigger('reloadGrid', [{current: true}]);

        $('#grid-table4').jqGrid('clearGridData')
            .jqGrid('setGridParam', {
                postData: {
                    action: 'lotes_kit',
                    Folio_Pro: $("#id_orden_trabajo").val()
                }, datatype: 'json', mtype: 'post',
                loadComplete: function (data) {
                    console.log("lotes_kit 2", data);
                    $("#lote_usado").val(data.lote_ot);
                    if (primera_produccion > 0) {
                        $("#lote_compuesto, #lote_compuesto_derivado").val(data.lote_ot);
                        $("#caducidad_compuesto, #caducidad_compuesto_derivado").val(data.caducidad);
                    }
                    //if(data.realizo_fusion == true)
                    //    cargarLotes(_codigo, 1);
                }
            })
            .trigger('reloadGrid', [{current: true}]);

        console.log("Derivados KIT _codigo = ", _codigo);
        $('#grid-table6').jqGrid('clearGridData')
            .jqGrid('setGridParam', {
                postData: {
                    action: 'derivados_kit',
                    Folio_Pro: _codigo
                }, datatype: 'json', mtype: 'post',
                loadComplete: function (data) {
                    console.log("derivados_kit 2", data);
                }
            })
            .trigger('reloadGrid', [{current: true}]);

    }

    /*
    function cargarArticulosCompuestos(_codigo) {
        $('#grid-table4').jqGrid('clearGridData')
            .jqGrid('setGridParam', {postData: {
                action: 'articulos',
                Folio_Pro: _codigo
            }, datatype: 'json', mtype: 'post'})
            .trigger('reloadGrid',[{current:true}]);
    }
*/

    function cargarComponentes(_codigo) {
        console.log("cargarComponentes", _codigo);
        $("#id_orden_trabajo").val(_codigo);

        $('#grid-table5').jqGrid('clearGridData')
            .jqGrid('setGridParam', {
                postData: {
                    action: 'componentes',
                    Folio_Pro: _codigo
                }, datatype: 'json', mtype: 'post'
            })
            .trigger('reloadGrid', [{current: true}]);
    }


    $(".chosen-select").chosen();


    $("#guardar").on('click', function () {
        //guardarUsuarioLote();
    });

    $("#cerrar").on("click", function cerrar() {
        $("#asignarUsuario").removeClass('fadeIn animated');
        $("#asignarUsuario").addClass('hidden');
        $("#list").fadeIn();
    });


    function eliminarOT(ot) {
        $.ajax({
            url: '/api/adminordentrabajo/update/index.php',
            data: {
                folio: ot,
                action: 'eliminarOT'
            },
            datatype: 'json',
            method: 'POST'
        }).done(function (data) {
            console.log(data);
            ReloadGrid();
            swal('Éxito', 'Orden de Trabajo Eliminada Correctamente', 'success');
        });
    }

    function eliminar(ot) {
        swal({
                title: "Aviso",
                text: "Esta Seguro de proceder a Eliminar la Orden de Trabajo " + ot + "?",
                type: "warning",

                showCancelButton: true,
                cancelButtonText: "No",
                cancelButtonColor: "#55b9dd",

                confirmButtonColor: "#ff0000",
                confirmButtonText: "Eliminar",
                closeOnConfirm: true
            },
            function (e) {
                if (e == true) {
                    eliminarOT(ot);
                }
            });
    }

    function pasar_a_produccion(ot) {
        console.log("*************************************");
        console.log("EVALUAR PASAR PEDIDO A PRODUCCIÓN");
        console.log("*************************************");
        console.log("folio = ", ot);
        console.log("almacen = ", '<?php echo $_SESSION['id_almacen']; ?>');
        console.log("*************************************");

        $.ajax({
            type: "POST",
            dataType: "json",
            url: '/api/administradorpedidos/update/index.php',
            data:
                {
                    folio: ot,
                    ot_produccion: true,
                    almacen: '<?php echo $_SESSION['id_almacen']; ?>',
                    action: 'semaforo'
                },
            beforeSend: function (x) {
                if (x && x.overrideMimeType) {
                    x.overrideMimeType("application/json;charset=UTF-8");
                }
            },
            success: function (data) {
                console.log("data = ", data);
                //console.log("success = ", data.success);
                //console.log("num_productos_disponibles = ", data.num_productos_disponibles);
                //console.log("num_productos_pedido = ", data.num_productos_pedido);
                //console.log("sql_disp = ", data.sql_disp);
                console.log("color = ", data.color);
                if (data.color == 'verde' || data.num_productos_disponibles == data.num_productos_pedido) {
                    $.ajax({
                        type: "POST",
                        dataType: "json",
                        url: '/api/adminordentrabajo/update/index.php',
                        data:
                            {
                                folio: ot,
                                action: 'pasar_a_produccion'
                            },
                        beforeSend: function (x) {
                            if (x && x.overrideMimeType) {
                                x.overrideMimeType("application/json;charset=UTF-8");
                            }
                        },
                        success: function (data) {
                            console.log("data = ", data);
                            swal("Éxito", "Se cambió la ot " + ot + " a Producción", "success");
                            ReloadGrid();

                        }, error: function (data) {
                            console.log("ERROR semaforo", data);
                        }
                    });
                } else {
                    swal("Error", "No hay suficientes productos disponibles para pasar la OT a producción", "error");
                    return;
                }

            }, error: function (data) {
                console.log("ERROR semaforo", data);
            }
        });

    }

    function pasar_a_produccion_pregunta(ot) {
        swal({
                title: "Aviso",
                text: "Este proceso evalúa primero que los productos de la OT se encuentran disponibles para producir para así poder cambiar el status a producción. \n\n ¿Desea Continuar?",
                type: "warning",

                showCancelButton: true,
                cancelButtonText: "No",
                cancelButtonColor: "#55b9dd",

                confirmButtonColor: "#0066ff",
                confirmButtonText: "Si",
                closeOnConfirm: true
            },
            function (e) {
                if (e == true) {
                    pasar_a_produccion(ot);
                }
            });
    }

    function asignar(id, status) {
        $("#ordenprod").html(id);
        $("#ordenp_etiq").val(id);
        console.log("Asignar()", id);
        $("#id_orden_trabajo").val(id);


        if (status == 'P') {
            $("#TerminarProduccion").hide();
            $("#pasarArticuloCompuesto").hide();
        }
        //cargarLotes(id, 1);
        //cargarArticulosCompuestos(id);
        cargarComponentes(id);
        //cargarLotes(id, 1);

        $("#asignarForm input[name='folio']").val(id);
        $.ajax({
            url: '/api/manufactura/ordentrabajo/usuarios.php',
            data: {
                orden: id
            },
            async: false,
            cache: false,
            datatype: 'json',
            method: 'GET'
        }).done(function (data) {
            data = JSON.parse(data);
            console.log("SUCCESS INIT:", data);
            if (data.Tipo == 'IMP_LP') {
                $("#clave_LP").text("Ingrese LP Registrado en la OT");
                $("#cod_art_compuesto").attr("placeholder", "Código LP");
                $("#tipo_orden_trabajo").val(data.Tipo);
                $("#cve_articulo_LP").val(data.Cve_Articulo);
                $("#imprimir_etiqueta_btn").hide();
            } else {
                $("#cod_art_compuesto").val(data.compuesto.clave);
            }
            $("#asignarForm select[name='usuario']").val(data.usuario);
            $("#asignarForm select[name='usuario']").trigger("chosen:updated");
            $("#list").fadeOut();
            $("#asignarUsuario").removeClass('hidden');
            $("#asignarUsuario").addClass('fadeIn animated');
            $("#compuesto_clave").html(data.compuesto.clave);
            $("#compuesto_descripcion").html(data.compuesto.descripcion);
            $("#compuesto_cantidad").html(data.compuesto.cantidad);

            $("#select_BL").append(data.options_bl);
            $("#select_BL_Dest").append(data.options_bl_dest);

            $("#cve_articulo_etiq").val(data.compuesto.clave);
            $("#des_articulo_etiq").val(data.compuesto.descripcion);

            if (data.compuesto.control_peso == 'N') {
                $("#ProductoSobrante").hide();
                //$("#RegistrarMerma").hide();
            }

            $("#producida").text(data.compuesto.Cant_Prod);
            $("#faltante").text(($("#compuesto_cantidad").text() - data.compuesto.Cant_Prod).toFixed(4));

            if (parseFloat($("#faltante").text()) < 0) {
                console.log("Verificar Productos Sobrantes");
                $("#faltante").text('0');
                $("#producida").text($("#compuesto_cantidad").text());
            }

            /*if(nlotes == 1)*/ //$("#registrar_merma_derivado").show();
            if (parseFloat($("#faltante").text()) > 0) {
                $("#merma").show();
            } else //if(nlotes == 1)
                $("#label_derivado input").text("Sobrante");


            if (parseFloat($("#producida").text()) > 0 && data.Tipo != 'IMP_LP')
                $("#imprimir_etiqueta_btn").show();


            $("#lote_compuesto").val(data.compuesto.Lote);

            if (data.compuesto.Caduca != 'S') {
                $("#art_caduca").val("N");
                $("#caducidad_compuesto_derivado_merma").hide();
                $(".caducidad_compuesto").hide();
            } else if (data.compuesto.Lote != '' && data.compuesto.Caduca == 'S') {
                $("#art_caduca").val("S");
                $("#lote_usado").val(data.compuesto.Lote);
                $("#caducidad_compuesto").val(data.compuesto.Caducidad);
                $("#caducidad_compuesto_derivado_merma").show();
            } else
                $("#caducidad_compuesto").val('');

            if (data.compuesto.Caduca == 'S') {
                $("#art_caduca").val("S");
            }

            if (data.compuesto.imagen != '' && data.compuesto.imagen != 'noimage.jpg' && data.compuesto.imagen != 'undefined')
                $("#imagen_producto_compuesto").attr("src", '../img/articulo/' + data.compuesto.imagen);
            cargarLotes(id, 1);
            IniciarCronometro();

            //console.log("*********************************************");
            //console.log("producida = ", parseFloat($("#producida").text()));
            //console.log("*********************************************");
            if (parseFloat($("#producida").text()) == 0) {
                $("#TerminarProduccion").hide();
            }

        });

        $("#status_terminar_produccion").val(status);

        if (status == 'T')
            $("#TerminarProduccion").trigger('click');//$("#imprimir_etiqueta_btn").show();

        ReloadGrid(0);
    }


    function OTSap(folio_ot) {
        console.log("folio_ot = ", folio_ot);

//*************************************************************************************************
        $.ajax({
            type: "POST",
            dataType: "json",
            url: '/api/adminordentrabajo/update/index.php',
            data:
                {
                    action: 'ConectarSAP',
                    funcion: 'Login',
                    metodo: 'POST',
                    folio_ot: folio_ot
                },
            beforeSend: function (x) {
                if (x && x.overrideMimeType) {
                    x.overrideMimeType("application/json;charset=UTF-8");
                }
            },
            success: function (data) {
                console.log("data.SessionId = ", data.SessionId);
                $.ajax({
                    type: "POST",
                    dataType: "json",
                    url: '/api/adminordentrabajo/update/index.php',
                    data:
                        {
                            action: 'EjecutarOTSAP',
                            funcion: 'InventoryGenEntries',
                            funcion2: 'InventoryGenExits',
                            ejecutar: 'Entries',
                            //funcion: 'Drafts',
                            metodo: 'POST',
                            folio_ot: folio_ot,
                            //cantidad_art_compuesto: $("#cantidad_art_compuesto").val(),
                            sesion_id: data.SessionId
                        },
                    beforeSend: function (x) {
                        if (x && x.overrideMimeType) {
                            x.overrideMimeType("application/json;charset=UTF-8");
                        }
                    },
                    success: function (data) {
                        console.log("SUCCESS EjecutarOTSAP", data);
                        if (data.error) {
                            console.log("data.error", data.error);
                            swal("Error", data.error.message.value, "error");
                        } else
                            swal("Envío Correcto", "Actualización a OT Correcta", "success");
                    }, error: function (data) {
                        console.log("ERROR EjecutarOTSAP 1", data);
                    }
                });


            }, error: function (data) {
                console.log("NO SE EJECUTO SAP", data);
            }
        });
//*************************************************************************************************

        /*
        //return;
      $.ajax({
        type: "POST",
        dataType: "json",
        url: '/api/adminordentrabajo/update/index.php',
        data:
        {
          action: 'ConectarSAP',
          funcion: 'Login',
          metodo: 'POST',
          folio_ot: folio_ot
        },
        beforeSend: function(x){if(x && x.overrideMimeType) { x.overrideMimeType("application/json;charset=UTF-8"); }
        },
        success: function(data)
        {
            console.log("SUCCESS", data);
            //return;
            if(data.SessionId)
            {
                console.log("SessionId OK:", data.SessionId);
                    //return;
/*
                  $.ajax({
                    type: "POST",
                    dataType: "json",
                    url: '/api/adminordentrabajo/update/index.php',
                    data:
                    {
                      action: 'EjecutarOTSAP',
                      funcion: 'InventoryGenEntries',
                      funcion2: 'InventoryGenExits',
                      ejecutar: 'Exits',
                      //funcion: 'Drafts',
                      metodo: 'POST',
                      folio_ot: folio_ot,
                      sesion_id: data.SessionId
                    },
                    beforeSend: function(x){if(x && x.overrideMimeType) { x.overrideMimeType("application/json;charset=UTF-8"); }
                    },
                    success: function(data)
                    {
                      console.log("SUCCESS EjecutarOTSAP", data);
                      if(data.error)
                      {
                         swal("Error", data.error.message.value, "error");
                      }
                      else
                      {
*/

        /*
                          $.ajax({
                            type: "POST",
                            dataType: "json",
                            url: '/api/adminordentrabajo/update/index.php',
                            data:
                            {
                              action: 'ConectarSAP',
                              funcion: 'Login',
                              metodo: 'POST',
                              folio_ot: folio_ot
                            },
                            beforeSend: function(x){if(x && x.overrideMimeType) { x.overrideMimeType("application/json;charset=UTF-8"); }
                            },
                            success: function(data)
                            {

                              $.ajax({
                                type: "POST",
                                dataType: "json",
                                url: '/api/adminordentrabajo/update/index.php',
                                data:
                                {
                                  action: 'EjecutarOTSAP',
                                  funcion: 'InventoryGenEntries',
                                  funcion2: 'InventoryGenExits',
                                  ejecutar: 'Entries',
                                  //funcion: 'Drafts',
                                  metodo: 'POST',
                                  folio_ot: folio_ot,
                                  sesion_id: data.SessionId
                                },
                                beforeSend: function(x){if(x && x.overrideMimeType) { x.overrideMimeType("application/json;charset=UTF-8"); }
                                },
                                success: function(data)
                                {
                                  console.log("SUCCESS EjecutarOTSAP", data);
                                  if(data.error)
                                  {
                                     swal("Error", data.error.message.value, "error");
                                  }
                                  else
                                     swal("Envío Correcto", "Actualización a OT Correcta", "success");
                                }, error: function(data)
                                {
                                  console.log("ERROR EjecutarOTSAP", data);
                                }
                              });


                            }, error: function(data)
                            {
                              console.log("ERROR", data);
                            }
                          });
                    */
        /*
                      }
                    }, error: function(data)
                    {
                      console.log("ERROR EjecutarOTSAP", data);
                    }
                  });
*/
        /*
            }
            else
            {
                console.log("SessionId ERROR:", data);
                swal("Error", "No hay Conexión a SAP", "error");
            }


        }, error: function(data)
        {
          console.log("ERROR 1", data);
        }
      });
*/
    }

    function validar_produccion(ot) {

        $.ajax({
            type: "POST",
            dataType: "json",
            url: '/api/adminordentrabajo/update/index.php',
            data:
                {
                    folio: ot,
                    action: 'validar_produccion'
                },
            beforeSend: function (x) {
                if (x && x.overrideMimeType) {
                    x.overrideMimeType("application/json;charset=UTF-8");
                }
            },
            success: function (data) {
                if (data > 0)
                    return true;
                else
                    return false;
            }, error: function (data) {
                console.log("ERROR validar", data);
            }
        });

    }


    function asignarValoresProduccionLP(id_orden_trabajo, cantidad_art_compuesto, cod_art_compuesto, tipo_orden_trabajo, cve_articulo_LP, cantidad_producida) {
        //$(".tarima_actual").click(function(){
        //    $(this).hide();
        //});

        $("#id_orden_trabajo").val(id_orden_trabajo);
        $("#cantidad_art_compuesto").val(cantidad_art_compuesto);
        $("#cod_art_compuesto").val(cod_art_compuesto);
        $("#tipo_orden_trabajo").val(tipo_orden_trabajo);
        $("#cve_articulo_LP").val(cve_articulo_LP);
        $("#lote_compuesto").val(id_orden_trabajo);
        $("#art_caduca").val('N');
        $("#faltante").text(cantidad_art_compuesto.toFixed(4));

        console.log("id_orden_trabajo = ", id_orden_trabajo);
        console.log("cantidad_art_compuesto = ", cantidad_art_compuesto);
        console.log("cod_art_compuesto = ", cod_art_compuesto);
        console.log("tipo_orden_trabajo = ", tipo_orden_trabajo);
        console.log("cve_articulo_LP = ", cve_articulo_LP);
        console.log("cantidad_producida = ", cantidad_producida);

        if (cantidad_producida < cantidad_art_compuesto)
            pasarArticuloCompuesto('icono_engranaje');
        else {
            finalizar_orden_de_trabajo('icono_engranaje');
            swal("Éxito", "Producción Finalizada", "success");
        }
        setTimeout(function () {
            //if(validar_produccion(id_orden_trabajo))
            //{
            //finalizar_orden_de_trabajo('icono_engranaje');
            ReloadGrid();
            //}
            //else
            //    swal("Producción No Realizada", "Intente de Nuevo por favor", "error");
        }, 2000);

    }

    function pasarArticuloCompuesto(modo = "") {
        //console.log("compuesto_clave", $("#compuesto_clave").text());
        //console.log("cod_art_compuesto", $("#cod_art_compuesto").val());
        console.log("id_orden_trabajo = ", $("#id_orden_trabajo").val());
        console.log("cantidad_art_compuesto = ", $("#cantidad_art_compuesto").val());
        console.log("Tipo_OT = ", $("#tipo_orden_trabajo").val());
        console.log("cod_art_compuesto = ", $("#cod_art_compuesto").val());
        console.log("cve_articulo_LP = ", $("#cve_articulo_LP").val());
        console.log("faltante = ", $("#faltante").text());
        console.log("art_caduca = ", $("#art_caduca").val());
        var primera_produccion = $("#producida").text(),
            lote_primera_produccion = $("#lote_compuesto").val(),
            caducidad_primera_produccion = $("#caducidad_compuesto").val();


        if ($("#art_caduca").val() == 'S' && lote_primera_produccion == '' && caducidad_primera_produccion == '') {
            swal('Lote y Caducidad Vacíos', 'Debe Ingresar un Lote y una Caducidad', 'error');
        } else if ($("#art_caduca").val() == 'S' && caducidad_primera_produccion == '') {
            swal('Caducidad Vacía', 'Debe Ingresar una Caducidad', 'error');
        } else if (lote_primera_produccion == '') {
            swal('Lote Vacío', 'Debe Ingresar un Lote', 'error');
        } else if (parseFloat($("#cantidad_art_compuesto").val()) > parseFloat($("#faltante").text()) && $("#tipo_orden_trabajo").val() != 'IMP_LP') {
            swal('Error', 'La Cantidad a Producir es Mayor a la Cantidad Faltante', 'error');
        } else if (parseFloat($("#faltante").text()) == 0 && $("#tipo_orden_trabajo").val() != 'IMP_LP') {
            swal('Error', 'La Cantidad Faltante es Cero, debe Terminar la Producción', 'error');
        } else if ($("#select_BL").val() == "") {
            swal('Error', 'Debe Incluir un BL marcado como Área de Producción para poder producir', 'error');
        } else if ($("#compuesto_clave").text() == $("#cod_art_compuesto").val().toUpperCase() || $("#tipo_orden_trabajo").val() == 'IMP_LP' || $("#cod_art_compuesto").val().search('$') >= 0) {
            $("#pasarArticuloCompuesto").prop("disabled", true);
            $.ajax({
                url: '/api/adminordentrabajo/update/index.php',
                data: {
                    orden_id: $("#id_orden_trabajo").val(),
                    cantidad_art_compuesto: $("#cantidad_art_compuesto").val(),
                    cod_art_compuesto: $("#cod_art_compuesto").val(),
                    //lote_cambiar: $("#lote_compuesto").val(),
                    //caducidad: $("#caducidad_compuesto").val(),
                    Tipo_OT: $("#tipo_orden_trabajo").val(),
                    cve_articulo_LP: $("#cve_articulo_LP").val(),
                    almacen: $('#almacen').val(),
                    instancia: $('#instancia').val(),
                    //async: false,
                    action: 'actualizarComponentes'
                },
                datatype: 'json',
                method: 'POST'
            }).done(function (data) {
                console.log(data);
                if (data == "CantidadFaltanteError")
                    swal('Error', 'La Cantidad a Producir es Mayor a la Cantidad Faltante', 'error');
                else if (data == "cero")
                    swal('Error', 'Debe Ingresar un Código Válido', 'error');
                else if (data == "dos")
                    swal('Error', 'La Cantidad Ingresada no debe ser mayor a la Cantidad Faltante', 'error');
                else if (data == "tres")
                    swal('Error', 'No hay suficientes artículos en el inventario para realizar la producción', 'error');
                else if (data == "cuatro")
                    swal('Error', 'Los componentes del artículo compuesto no se encuentran en producción', 'error');
                else if (data == "error")
                    swal('Error', 'Los productos componentes no se encuentran todos en producción', 'error');
                else if (data == "NoPerteneceLP")
                    swal('Error', 'El LP No está asignado a esta orden de producción', 'error');
                else if (data == "LPYaDescontado")
                    swal('Error', 'El LP Ya se ha descontado de la producción', 'error');
                else if (data == "LecturaIncorrecta")
                    swal('Error', 'El código leído es incorrecto, Verifique el Formato del código', 'error');
                else if (data == "LecturaIncorrectaOT")
                    swal('Error', 'El código leído No pertenece a esta OT', 'error');
                else if (data == "LecturaIncorrectaArt")
                    swal('Error', 'El código leído No tiene el artículo que pertenece a esta OT', 'error');
                else if (data == "LecturaLPExistente")
                    swal('Error', 'EL LP del código Leído ya existe', 'error');
                else if (data == "T" || data == "Terminar") {
                    swal('Ya se ha generado esta orden', 'Esta Orden de Trabajo ya se ha producido anteriormente', 'error');
                    if (data == "Terminar") {
                        finalizar_orden_de_trabajo();
                        swal("Éxito", "Producción Finalizada", "success");
                    }
                    ReloadGrid();
                } else {
                    cargarComponentes($("#id_orden_trabajo").val());
                    //cargarLotes($("#id_orden_trabajo").val(), primera_produccion);
                    cargarLotes(lote_primera_produccion, primera_produccion);
                    $("#producida").text(data);
                    $("#faltante").text(($("#compuesto_cantidad").text() - data).toFixed(4));
                    $("#imprimir_etiqueta_btn").show();

                    if (parseFloat($("#faltante").text()) < 0) {
                        console.log("Verificar Productos Sobrantes");
                        $("#faltante").text('0');
                        $("#producida").text($("#compuesto_cantidad").text());
                    }
                    if ($("#cod_art_compuesto").val().search('$') >= 0)
                        $("#cod_art_compuesto").val("");

                    if ($('#instancia').val() != 'foam') {
//*************************************************************************************************
                        $.ajax({
                            type: "POST",
                            dataType: "json",
                            url: '/api/adminordentrabajo/update/index.php',
                            data:
                                {
                                    action: 'ConectarSAP',
                                    funcion: 'Login',
                                    metodo: 'POST',
                                    folio_ot: $("#id_orden_trabajo").val()
                                },
                            beforeSend: function (x) {
                                if (x && x.overrideMimeType) {
                                    x.overrideMimeType("application/json;charset=UTF-8");
                                }
                            },
                            success: function (data) {

                                $.ajax({
                                    type: "POST",
                                    dataType: "json",
                                    url: '/api/adminordentrabajo/update/index.php',
                                    data:
                                        {
                                            action: 'EjecutarOTSAP',
                                            funcion: 'InventoryGenEntries',
                                            funcion2: 'InventoryGenExits',
                                            ejecutar: 'Entries',
                                            //funcion: 'Drafts',
                                            metodo: 'POST',
                                            folio_ot: $("#id_orden_trabajo").val(),
                                            cantidad_art_compuesto: $("#cantidad_art_compuesto").val(),
                                            sesion_id: data.SessionId
                                        },
                                    beforeSend: function (x) {
                                        if (x && x.overrideMimeType) {
                                            x.overrideMimeType("application/json;charset=UTF-8");
                                        }
                                    },
                                    success: function (data) {
                                        console.log("SUCCESS EjecutarOTSAP", data);
                                        if (data.error) {
                                            swal("Error", data.error.message.value, "error");
                                        }
                                        //else
                                        //swal("Envío Correcto", "Actualización a OT Correcta", "success");
                                    }, error: function (data) {
                                        console.log("ERROR EjecutarOTSAP 2", data);
                                    }
                                });


                            }, error: function (data) {
                                console.log("NO SE EJECUTO SAP", data);
                            }
                        });
//*************************************************************************************************
                    }

                    if (primera_produccion == 0 && $("#lote_compuesto").val() != '') {
                        //$("#cambiar_lote").click();
                        //Cambiar_Lote_OT($("#id_orden_trabajo").val(), 0);
                        if ($("#tipo_orden_trabajo").val() != 'IMP_LP') Cambiar_Lote_OT("", 0);
                        else {
                            swal("Producción Realizada", "Se finalizó la producción", "success");
                            ReloadGrid();
                        }
                        //la primera producción tiene como default del lote el folio de la OT
                        console.log("++++++++++++++++++++++++++++++++++");
                        console.log("caducidad_compuesto = ", $("#caducidad_compuesto").val());
                        console.log("art_caduca = ", $("#art_caduca").val());
                        console.log("++++++++++++++++++++++++++++++++++");
                        if ($("#caducidad_compuesto").val() != '' && $("#art_caduca").val() == 'S' && $("#tipo_orden_trabajo").val() != 'IMP_LP') {
                            //$("#cambiar_caducidad").click();
                            Cambiar_Caducidad_OT($("#lote_compuesto").val(), 0);
                        }
                    }

                    console.log("*********************************************");
                    console.log("producida = ", parseFloat($("#producida").text()));
                    console.log("*********************************************");

                    if (parseFloat($("#producida").text()) > 0) {
                        $("#TerminarProduccion").show();
                    }
                    //Cambiar_Lote_OT($("#lote_compuesto").val(), 1);
                    //Cambiar_Caducidad_OT($("#lote_compuesto").val(), 1);
                    if (modo == 'icono_engranaje') {
                        finalizar_orden_de_trabajo('icono_engranaje');
                        swal("Éxito", "Producción Finalizada", "success");
                        ReloadGrid();
                    }

                }

            });
            setTimeout(function () {
                $("#pasarArticuloCompuesto").prop("disabled", false);
            }, 2000);
            //if(parseFloat($("#producida").text()) > 0)
            //   $("#imprimir_etiqueta_btn").show();

        } else {
            swal('Error', 'La Orden de Producción debe generarse con la clave del Producto Compuesto: ' + $("#compuesto_clave").text(), 'error');
        }
    }


    $("#statusOT").change(function (e) {
        ReloadGrid();
    });

    $("#pasarArticuloCompuesto").click(function () {
        pasarArticuloCompuesto();
    });

    $("#cod_art_compuesto, #cantidad_art_compuesto").bind('keyup', function (e) {
        //console.log("OK tecla");
        if (e.which === 13) {
            pasarArticuloCompuesto();
        }
    });

    $("#derivado, #merma, #pbase, #excedente").change(function (e) {

        if ($("input[name=derivado_merma]:checked").val() == 'derivado') {
            $("#lote_derivado, #caducidad_derivado").hide();
            $("#producto_derivado").show(100);
            $("#componentes_derivado_chosen, #articulos_no_compuestos_chosen").show();
            $("#componentes_derivado_base_chosen, #articulos_no_compuestos_base_chosen").hide();
            $("#cantidad_componente").val("");
            $("#cantidad_componente").removeAttr("readonly");
            $("#cantidad_sobrante").removeAttr("max");
            $("#producto_merma").hide(100);
        } else if ($("input[name=derivado_merma]:checked").val() == 'pbase' || $("input[name=derivado_merma]:checked").val() == 'excedente') {
            $("#producto_derivado").show(100);
            $("#componentes_derivado_chosen, #articulos_no_compuestos_chosen").hide();
            //$("#articulos_no_compuestos_chosen").hide();
            $("#componentes_derivado_base_chosen, #articulos_no_compuestos_base_chosen").show();

            $("#cantidad_sobrante").attr("max", $("#existencia_OT").val());
            $("#cantidad_componente").val($("#existencia_OT").val());
            $("#cantidad_componente").attr("readonly", "readonly");
            $("#producto_merma").hide(100);
            //$("#lote_caducidad_base").hide();
        } else {
            $("#producto_derivado").hide(100);
            $("#producto_merma").show(100);
            //$("#lote_compuesto_derivado_merma").val($("#lote_compuesto").val());
            //$("#caducidad_compuesto_derivado_merma").val($("#caducidad_compuesto").val());

            //$("#cantidad_sobrante_merma").val();
            //$("#lote_compuesto_derivado_merma").val($("#lote_compuesto").val());
            //$("#caducidad_compuesto_derivado_merma").val($("#caducidad_compuesto").val());
            //$("#peso_comp").text(" | Peso: "+$(this).find(':selected').data('peso'));


        }

        if ($("input[name=derivado_merma]:checked").val() == 'excedente') {
            $(".no_excedente").hide();
            $(".si_excedente").show();
        } else {
            $(".no_excedente").show();
            $(".si_excedente").hide();
        }


    });

    $("#articulos_no_compuestos").change(function () {
        var control_lotes_d = $(this).find(':selected').data('control_lotes');
        var Caduca_d = $(this).find(':selected').data('caduca');
        var granel_d = $(this).find(':selected').data('granel');
        var espieza_d = $(this).find(':selected').data('espieza');

        console.log("cve_articulo = ", $(this).val(), " ; control_lotes = ", control_lotes_d, " ; Caduca = ", Caduca_d, " ; granel = ", granel_d, " ; espieza = ", espieza_d);

        $("#lote_derivado, #caducidad_derivado").hide();
        if ($(this).val() == "") {
            $("#peso_derivado").text("");
        } else {
            $("#lote_derivado, #caducidad_derivado").hide();
            $("#lote_compuesto_derivado").val($("#lote_compuesto").val());
            $("#caducidad_compuesto_derivado").val($("#caducidad_compuesto").val());
            $("#peso_derivado").text(" | Peso: " + $(this).find(':selected').data('peso'));
            if ($(this).find(':selected').data('control_lotes') == 'S') $("#lote_derivado").show();
            if ($(this).find(':selected').data('caduca') == 'S') $("#caducidad_derivado").show();


            //if($("input[name=derivado_merma]:checked").val() == 'pbase')
            //{
            //    $("#lote_caducidad_base").hide();
            //}

        }
    });


    $("#componentes_derivado").change(function (e) {

        if ($(this).val() == "") {
            $("#cantidad_componente").val("");
            $("#peso_comp").text("");
        } else {
            var data = $(this).val();
            var cant_comp = data.split(";;:::::;;");
            $("#componente_derivado_select").val(cant_comp[0]);
            $("#cantidad_componente").val(cant_comp[1]);
            $("#cantidad_componente").attr("max", cant_comp[1]);
            //$("#peso_comp").text(" | Peso: "+$(this).find(':selected').data('peso'));
        }

    });


    $("#articulos_no_compuestos_base").change(function () {
        var control_lotes_d = $(this).find(':selected').data('control_lotes');
        var Caduca_d = $(this).find(':selected').data('caduca');
        var granel_d = $(this).find(':selected').data('granel');

        console.log("cve_articulo = ", $(this).val(), " ; control_lotes = ", control_lotes_d, " ; Caduca = ", Caduca_d, " ; granel = ", granel_d);

        $("#lote_derivado, #caducidad_derivado").hide();
        if ($(this).val() == "") {
            $("#peso_derivado").text("");
        } else {
            $("#lote_derivado, #caducidad_derivado").hide();
            $("#lote_compuesto_derivado").val($("#lote_compuesto").val());
            $("#caducidad_compuesto_derivado").val($("#caducidad_compuesto").val());
            $("#peso_derivado").text(" | Peso: " + $(this).find(':selected').data('peso'));
            if ($(this).find(':selected').data('control_lotes') == 'S') $("#lote_derivado").show();
            if ($(this).find(':selected').data('caduca') == 'S') $("#caducidad_derivado").show();
        }
    });


    $("#componentes_derivado_base").change(function (e) {

        if ($(this).val() == "") {
            $("#cantidad_componente").val("");
            $("#peso_comp_base").text("");
        } else {
            var data = $(this).val();
            var cant_comp = data.split(";;:::::;;");
            $("#componente_derivado_select").val(cant_comp[0]);
            $("#cantidad_componente").val(cant_comp[1]);
            $("#cantidad_componente").attr("max", cant_comp[1]);
            //$("#peso_comp").text(" | Peso: "+$(this).find(':selected').data('peso'));
        }

    });


    //cantidad_componente
    //cantidad_sobrante
    //text_max_comp
    //text_max_derivado

    $("#cantidad_componente").keyup(function (e) {
        $("#text_max_comp").text("");

        var valor = $(this).val();
        var arr_val = valor.split(".");
        var punto = "";
        console.log("arr_val_0 = ", arr_val[0]);
        if (arr_val[0] == '') {
            console.log("OK Keyup 1");
            punto = '0';
        }

        if (parseFloat($(this).val()) > parseFloat(punto + $("#cantidad_componente").attr("max"))) {
            $(this).val($("#cantidad_componente").attr("max"));
            $("#text_max_comp").text("Cantidad Mayor a Stock, se modificó la cantidad a la máxima permitida");
        }

        var peso = $("#articulos_no_compuestos").find(':selected').data('peso');
        if (peso == 0)
            $("#cantidad_sobrante").val(0);
        else {
            var control_granel = $("#articulos_no_compuestos").find(':selected').data('control_granel');
            if (control_granel == 'S')
                $("#cantidad_sobrante").val(((punto + $("#cantidad_componente").val()) / peso));
            else {
                console.log("GRANEL 1 = ", $("#articulos_no_compuestos").find(':selected').data('granel'));
                if ($("#articulos_no_compuestos").find(':selected').data('granel') == 'S' && $("#articulos_no_compuestos").find(':selected').data('espieza') == 'S')
                    $("#cantidad_sobrante").val(parseInt((punto + $("#cantidad_componente").val()) / peso));
                else
                    $("#cantidad_sobrante").val(parseFloat((punto + $("#cantidad_componente").val()) / peso));
            }
        }
    });


    $("#cantidad_sobrante").keyup(function (e) {
        if (($("input[name=derivado_merma]:checked").val() == 'derivado' && $("#articulos_no_compuestos").find(':selected').data('espieza') == 'S') || ($("input[name=derivado_merma]:checked").val() == 'pbase' && $("#articulos_no_compuestos_base").find(':selected').data('espieza') == 'S')) {
            $("#cantidad_componente").val($(this).val());
        } else if ($("input[name=derivado_merma]:checked").val() == 'derivado') {
            var valor = $(this).val();
            var arr_val = valor.split(".");
            var punto = "";
            console.log("arr_val_0 = ", arr_val[0]);
            if (arr_val[0] == '') {
                console.log("OK Keyup");
                punto = '0';
            }

            var peso = punto + $("#articulos_no_compuestos").find(':selected').data('peso');
            console.log("valor cantidad_sobrante = ", $(this).val(), " peso = ", peso);
            console.log("valor cantidad componente = ", parseFloat(punto + $("#cantidad_componente").val()));
            console.log("valor cantidad componente MAX = ", parseFloat(punto + $("#cantidad_componente").attr("max")));

            if (peso == 0)
                $("#cantidad_componente").val(0);
            else
                $("#cantidad_componente").val(parseFloat((punto + $(this).val()) * peso));


            $("#text_max_comp").text("");
            if (parseFloat(punto + $("#cantidad_componente").val()) > parseFloat(punto + $("#cantidad_componente").attr("max"))) {
                $("#cantidad_componente").val($("#cantidad_componente").attr("max"));
                $("#text_max_comp").text("Cantidad Mayor a Stock, se modificó la cantidad a la máxima permitida");

                var peso = $("#articulos_no_compuestos").find(':selected').data('peso');
                if (peso == 0)
                    $("#cantidad_sobrante").val(0);
                else {
                    console.log("GRANEL 2 = ", $("#articulos_no_compuestos").find(':selected').data('granel'));
                    if ($("#articulos_no_compuestos").find(':selected').data('granel') == 'S' && $("#articulos_no_compuestos").find(':selected').data('espieza') == 'S')
                        $("#cantidad_sobrante").val(parseInt((punto + $("#cantidad_componente").val()) / peso));
                    else
                        $("#cantidad_sobrante").val(parseFloat((punto + $("#cantidad_componente").val()) / peso));
                }
            }
        } else if ($("input[name=derivado_merma]:checked").val() == 'pbase') {
            var peso = $("#articulos_no_compuestos_base").find(':selected').data('peso');
            console.log("-----------------------------");
            console.log("peso = ", peso);
            console.log("keyup = ", parseFloat($(this).val()));
            console.log("max = ", parseFloat($(this).attr("max")));
            console.log("max/peso = ", (parseFloat($(this).attr("max") / peso)));
            console.log("-----------------------------");
            if (parseFloat($(this).val()) > (parseFloat($(this).attr("max") / peso))) {
                $(this).val(($(this).attr("max") / peso).toFixed(5));
            }
        }
    });


    $("#select_BL").change(function () {

        if ($(this).val() == '') return;

        $.ajax({
            url: '/api/adminordentrabajo/update/index.php',
            data: {
                orden_id: $("#id_orden_trabajo").val(),
                idy_ubica: $(this).val(),
                action: 'cambiarBL'
            },
            datatype: 'json',
            method: 'POST'
        }).done(function (data) {
            console.log(data);
            cargarComponentes($("#id_orden_trabajo").val());
            cargarLotes($("#id_orden_trabajo").val(), 1);
        });

    });


    $("#select_BL_Dest").change(function () {

        if ($(this).val() == '') return;

        $.ajax({
            url: '/api/adminordentrabajo/update/index.php',
            data: {
                orden_id: $("#id_orden_trabajo").val(),
                idy_ubica: $(this).val(),
                action: 'cambiarBLDest'
            },
            datatype: 'json',
            method: 'POST'
        }).done(function (data) {
            console.log(data);
            cargarComponentes($("#id_orden_trabajo").val());
            cargarLotes($("#id_orden_trabajo").val(), 1);
        });

    });

    function RegistrarMermaOK() {
        console.log("RegistrarMermaOK()");

        console.log("orden_id:", $("#id_orden_trabajo").val());
        console.log("idy_ubica:", $("#select_BL").val());
        console.log("cve_articulo_componente:", $("#cve_articulo_componente").val());
        console.log("cantidad_sobrante_merma:", $("#cantidad_sobrante_merma").val());
        console.log("lote_compuesto_derivado_merma:", $("#lote_compuesto_derivado_merma").val());
        console.log("caducidad_compuesto_derivado_merma:", $("#caducidad_compuesto_derivado_merma").val());
        console.log("lote_compuesto:", $("#lote_compuesto").val());
        console.log("art_caduca:", $("#art_caduca").val());

        //return;
        $.ajax({
            url: '/api/adminordentrabajo/update/index.php',
            data: {
                orden_id: $("#id_orden_trabajo").val(),
                idy_ubica: $("#select_BL").val(),
                cve_articulo_componente: $("#cve_articulo_componente").val(),
                cantidad_sobrante_merma: $("#cantidad_sobrante_merma").val(),
                lote_compuesto_derivado_merma: $("#lote_compuesto_derivado_merma").val(),
                caducidad_compuesto_derivado_merma: $("#caducidad_compuesto_derivado_merma").val(),
                lote_compuesto: $("#lote_compuesto").val(),
                art_caduca: $("#art_caduca").val(),
                action: 'RegistrarMermaOT'
            },
            datatype: 'json',
            method: 'POST'
        }).done(function (data) {
            console.log(data);
            cargarComponentes($("#id_orden_trabajo").val());
            cargarLotes($("#id_orden_trabajo").val(), 1);
            swal("Éxito", "Merma Registrada Correctamente", "success");
        });
    }

    $("#RegistrarMerma").click(function () {

        swal({
                title: "Está Seguro de proceder a registrar la Merma?",
                text: "Esto Eliminará todo el stock actual del componente y se registrará en Kardex como Merma",
                type: "warning",

                showCancelButton: true,
                cancelButtonText: "No",
                cancelButtonColor: "#14960a",

                confirmButtonColor: "#55b9dd",
                confirmButtonText: "Si",
                closeOnConfirm: true
            },
            function (e) {
                if (e == true) {
                    RegistrarMermaOK();
                }
            });

    });

    $("#RegistrarSobrante").click(function () {
        if ($("#cantidad_sobrante").val() == '' && ($("input[name=derivado_merma]:checked").val() == 'derivado' || $("input[name=derivado_merma]:checked").val() == 'pbase' || $("input[name=derivado_merma]:checked").val() == 'excedente')) {
            swal('Error', 'Debe Ingresar un Valor', 'error');
            return;
        }

        if ($("#producida").text() == '0') {
            swal('Error', 'No se ha iniciado la producción', 'error');
            return;
        }


        var cantidad_sobrante = $("#cantidad_sobrante").val();

        var cantidad_s = cantidad_sobrante.split("");

        if (cantidad_s[0] == '.')
            cantidad_sobrante = '0' + $("#cantidad_sobrante").val();


        if ($("input[name=derivado_merma]:checked").val() == 'derivado' || $("input[name=derivado_merma]:checked").val() == 'pbase')
            $("#producto_derivado").show(100);
        else
            $("#producto_derivado").hide(100);

        //var anc = $("#articulos_no_compuestos").val();
        //var anc_cve_art_arr = anc.split(':::::;;;;;:::::');
        //var anc_cve_art = anc_cve_art_arr[0];

//componentes_derivado
//componente_derivado_select
//cantidad_componente
//cantidad_sobrante
//lote_compuesto_derivado
//caducidad_compuesto_derivado

        var control_lotes_d = $("#articulos_no_compuestos").find(':selected').data('control_lotes');
        var Caduca_d = $("#articulos_no_compuestos").find(':selected').data('caduca');
        var granel_d = $("#articulos_no_compuestos").find(':selected').data('granel');
        var lote_componente = $("#componentes_derivado").find(':selected').data('lote');
        var peso_componente = $("#componentes_derivado").find(':selected').data('peso');
        var unidad_medida_comp = $("#componentes_derivado").find(':selected').data('unidad_medida');
        var control_peso_comp = $("#componentes_derivado").find(':selected').data('control_peso');
        var art_compuesto_val = $("#articulos_no_compuestos").val();
        var lote_compuesto_derivado = $("#lote_compuesto_derivado").val();

        var lote_componente_base = $("#componentes_derivado_base").find(':selected').data('lote');
        var peso_componente_base = $("#componentes_derivado_base").find(':selected').data('peso');
        var unidad_medida_comp_base = $("#componentes_derivado_base").find(':selected').data('unidad_medida');
        var control_peso_comp_base = $("#componentes_derivado_base").find(':selected').data('control_peso');

        var control_lotes_d_base = $("#articulos_no_compuestos_base").find(':selected').data('control_lotes');
        var Caduca_d_base = $("#articulos_no_compuestos_base").find(':selected').data('caduca');
        var granel_d_base = $("#articulos_no_compuestos_base").find(':selected').data('granel');
        var art_compuesto_val_base = $("#articulos_no_compuestos_base").val();
        var lote_compuesto_derivado_base = $("#lote_compuesto_derivado").val();


        console.log("id_orden_trabajo = ", $("#id_orden_trabajo").val());
        console.log("cod_art_compuesto = ", $("#compuesto_clave").text());
        console.log("lote_compuesto = ", $("#lote_compuesto").val());
        //console.log("articulos_no_compuestos split = ", anc_cve_art);
        console.log("derivado_merma = ", $("input[name=derivado_merma]:checked").val());
        console.log("*******************************************************");
        console.log("articulos_no_compuestos = ", $("#articulos_no_compuestos").val());
        console.log("control_lotes_d = ", control_lotes_d);
        console.log("Caduca_d = ", Caduca_d);
        console.log("granel_d = ", granel_d);
        console.log("componentes_derivado = ", $("#componentes_derivado").val());
        console.log("lote_componente = ", lote_componente);
        console.log("peso_componente = ", peso_componente);
        console.log("unidad_medida_comp = ", unidad_medida_comp);
        console.log("control_peso_comp = ", control_peso_comp);
        console.log("componente_derivado_select = ", $("#componente_derivado_select").val());
        console.log("cantidad_componente = ", $("#cantidad_componente").val());
        console.log("cantidad_sobrante = ", $("#cantidad_sobrante").val());
        console.log("lote_compuesto_derivado = ", $("#lote_compuesto_derivado").val());
        console.log("caducidad_compuesto_derivado = ", $("#caducidad_compuesto_derivado").val());
        console.log("*******************************************************");
        console.log("lote_componente_base = ", lote_componente_base);
        console.log("peso_componente_base = ", peso_componente_base);
        console.log("unidad_medida_comp_base = ", unidad_medida_comp_base);
        console.log("control_peso_comp_base = ", control_peso_comp_base);
        console.log("control_lotes_d_base = ", control_lotes_d_base);
        console.log("Caduca_d_base = ", Caduca_d_base);
        console.log("granel_d_base = ", granel_d_base);
        console.log("art_compuesto_val_base = ", art_compuesto_val_base);
        console.log("lote_compuesto_derivado_base = ", lote_compuesto_derivado_base);
        console.log("*******************************************************");

        if ($("input[name=derivado_merma]:checked").val() == 'derivado') {
            if ($("#articulos_no_compuestos").val() == "" || $("#componente_derivado_select").val() == "" || $("#cantidad_componente").val() == '' || $("#cantidad_componente").val() == '0' || $("#cantidad_sobrante").val() == '' || $("#cantidad_sobrante").val() == '0') {
                swal("Error", "Todos los datos son obligatorios para registrar derivado", "error");
                return;
            }

            if ($("#lote_compuesto_derivado").val() == '' && control_lotes_d == 'S') {
                swal("Error", "Debe Ingresar un Lote para registrar producto Derivado", "error");
                return;
            }

            if ($("#caducidad_compuesto_derivado").val() == '' && Caduca_d == 'S') {
                swal("Error", "Debe Ingresar una Caducidad para registrar este producto Derivado", "error");
                return;
            }

        }

        if ($("input[name=derivado_merma]:checked").val() == 'pbase' || $("input[name=derivado_merma]:checked").val() == 'excedente') {
            peso_componente = $("#articulos_no_compuestos_base").find(':selected').data('peso');
            console.log("vvvvvvvvvvvvvvvvvvvvvvvvvvvvv");
            console.log("articulos_no_compuestos_base = ", $("#articulos_no_compuestos_base").val());
            console.log("componente_derivado_select = ", $("#componente_derivado_select").val());
            console.log("cantidad_componente = ", $("#cantidad_componente").val());
            console.log("cantidad_sobrante = ", $("#cantidad_sobrante").val());
            console.log("peso_componente comp= ", peso_componente);
            console.log("vvvvvvvvvvvvvvvvvvvvvvvvvvvvv");
            if ($("#cantidad_componente").val() == '0' && $("input[name=derivado_merma]:checked").val() != 'excedente') {
                swal("Error", "No tiene Stock para Derivar", "error");
                return;
            }

            if ($("input[name=derivado_merma]:checked").val() != 'excedente' || $("#cantidad_sobrante").val() == '' || $("#cantidad_sobrante").val() == '0') {
                if ($("#articulos_no_compuestos_base").val() == "" /*|| $("#componente_derivado_select").val() == ""  */ || $("#cantidad_componente").val() == '' || $("#cantidad_componente").val() == '0' || $("#cantidad_sobrante").val() == '' || $("#cantidad_sobrante").val() == '0') {
                    swal("Error", "Todos los datos son obligatorios para registrar derivado", "error");
                    return;
                }
            }
            /*
                if($("#lote_compuesto_derivado").val() == '' && control_lotes_d_base == 'S')
                {
                    swal("Error", "Debe Ingresar un Lote para registrar producto Derivado", "error");
                    return;
                }

                if($("#caducidad_compuesto_derivado").val() == '' && Caduca_d_base == 'S')
                {
                    swal("Error", "Debe Ingresar una Caducidad para registrar este producto Derivado", "error");
                    return;
                }
*/
        }

        //if($("input[name=derivado_merma]:checked").val() == 'pbase') return; //MIENTRAS TERMINO EL PROCESO
        //return;

        $.ajax({
            url: '/api/adminordentrabajo/update/index.php',
            data: {
                idy_ubica: $("#select_BL").val(),
                orden_id: $("#id_orden_trabajo").val(),
                art_sobrante: $("#articulos_no_compuestos").val(),
                lote_compuesto: $("#lote_compuesto").val(),
                cod_art_compuesto: $("#compuesto_clave").text(),
                control_lotes_d: control_lotes_d,
                Caduca_d: Caduca_d,
                granel_d: granel_d,
                componentes_derivado: $("#componentes_derivado").val(),
                lote_componente: lote_componente,
                peso_componente: peso_componente,
                unidad_medida_comp: unidad_medida_comp,
                control_peso_comp: control_peso_comp,
                componente_derivado_select: $("#componente_derivado_select").val(),
                cantidad_componente: $("#cantidad_componente").val(),
                cantidad_sobrante: $("#cantidad_sobrante").val(),
                cve_usuario: '<?php echo $_SESSION['cve_usuario']; ?>',
                lote_compuesto_derivado: $("#lote_compuesto_derivado").val(),
                caducidad_compuesto_derivado: $("#caducidad_compuesto_derivado").val(),

                radio_select: $("input[name=derivado_merma]:checked").val(),
                existencia_PT: $("#existencia_OT").val(),
                lote_componente_base: lote_componente_base,
                peso_componente_base: peso_componente_base,
                unidad_medida_comp_base: unidad_medida_comp_base,
                control_peso_comp_base: control_peso_comp_base,
                control_lotes_d_base: control_lotes_d_base,
                Caduca_d_base: Caduca_d_base,
                granel_d_base: granel_d_base,
                art_compuesto_val_base: art_compuesto_val_base,
                lote_compuesto_derivado_base: lote_compuesto_derivado_base,
                caducidad_compuesto_derivado_base: $("#caducidad_compuesto").val(),
                /*
              cantidad_art_sobrante: cantidad_sobrante,
              art_sobrante: $("#articulos_no_compuestos").val(),
              derivado_merma: $("input[name=derivado_merma]:checked").val(),
              cve_usuario: '<?php echo $_SESSION['cve_usuario']; ?>',
              cantidad_faltante: parseFloat($("#faltante").text()),
              //action: 'productoSobrante'
*/
                action: 'productoDerivado'
            },
            datatype: 'json',
            method: 'POST'
        }).done(function (data) {
            console.log(data);

            if (data != 'NoStockNoSurtibles') {
                cargarComponentes($("#id_orden_trabajo").val());
                cargarLotes($("#id_orden_trabajo").val(), 1);

                if ($("input[name=derivado_merma]:checked").val() == 'excedente')
                    swal("Éxito", "Producto Excedente Registrado Correctamente", "success");
                else
                    swal("Éxito", "Se ha registado el Producto Derivado " + art_compuesto_val + " con Lote " + lote_compuesto_derivado + " y cantidad " + cantidad_sobrante + " \nPuede Verificarlo en Kardex", "success");

                $("#componentes_derivado, #componente_derivado_select, #cantidad_componente, #cantidad_sobrante, #articulos_no_compuestos, #lote_compuesto_derivado, #caducidad_compuesto_derivado").val("");
                $('#componentes_derivado, #componente_derivado_select, #cantidad_componente, #cantidad_sobrante, #articulos_no_compuestos, #lote_compuesto_derivado, #caducidad_compuesto_derivado').trigger("chosen:updated");

                $("#mensaje_mostrar_produccion").val('0');


                if ($("input[name=derivado_merma]:checked").val() == 'pbase') {
                    $("#cantidad_componente, #existencia_OT").val(data);
                    $("#cantidad_sobrante").attr("max", data);
                    $("#articulos_no_compuestos_chosen").hide();
                    //$("#componentes_derivado_chosen, #articulos_no_compuestos_chosen").hide();
                }


                /*

                    if(parseFloat($("#faltante").text()) < 0)
                    {
                        console.log("Verificar Productos Sobrantes");
                        $("#faltante").text('0');
                        $("#producida").text($("#compuesto_cantidad").text());
                    }
                    if($("input[name=derivado_merma]:checked").val() == 'derivado')
                    {
                        swal("Éxito", "Se ha registado el Producto Derivado, Puede Verificarlo en Kardex", "success");
                        $("#faltante").text('0');
                    }
                    else if($("input[name=derivado_merma]:checked").val() != 'merma')//if(parseFloat($("#faltante").text()) == 0)
                    {
                        swal("Éxito", "Se ha registado el Producto Sobrante, Puede Verificarlo en Kardex", "success");
                        $("#producto_derivado").hide(100);
                    }
                    else
                    {
                        swal("Éxito", "Se ha registado el Producto como Merma, Puede Verificarlo en Kardex", "success");
                        $("#producida").text($("#compuesto_cantidad").text());
                        $("#faltante").text('0');

                    }
                    $("#cantidad_sobrante").val("");
                    $("#articulos_no_compuestos").val("");
                    */
                $("#TerminarProduccion").trigger('click');
            } else
                swal("Error", "No Hay Stock en Los Componentes No Surtibles", "error");


        });

    });

    /*
    function guardarUsuarioLote(){
        var usuario = $("#clave_usuario").val();//$("#asignarForm select[name='usuario']").val();
        var folio = $("#asignarForm input[name='folio']").val();
        var articulos = $("#grid-table4").jqGrid('getCol','clave');
        var compuesto = $("#compuesto_clave").html();
        var loteProduccion = $("#compuesto_clave").html();
        if($.isEmptyObject($('#grid-table4').jqGrid ('getRowData', loteProduccion))){
            swal('Error', 'Debes generar el lote de producción antes de guardar', 'error');
        }else if(usuario === ''){
            swal('Error', 'No seleccionaste ningún usuario', 'error');
        }else{
            $.ajax({
                url: '/api/manufactura/ordentrabajo/usuarios.php',
                data: {
                    usuario: usuario,
                    folio: folio,
                    articulos: $('#grid-table4').jqGrid('getRowData')
                },
                type: 'post',
                datatype: 'json'
            }).done(function(data){
                data = JSON.parse(data);
                if(data.success === true){
                  swal({
                    title: 'Éxito',
                    text: 'Usuario y Lotes asignado correctamente. ¿Desea imprimir las etiquetas del producto?',
                    type: 'success',
                    showConfirmButton: true,
                    confirmButtonText: 'Sí',
                    showCancelButton: true,
                    cancelButtonText: 'No',
                    closeOnConfirm: true,
                    closeOnCancel: true
                  }, function(yes){
                      if(yes){
                          $.ajax({
                              url: '/api/adminordentrabajo/lista/index.php',
                              data: {
                                folio: folio,
                                action: 'dataParaImprimir'
                              },
                              datatype: 'json',
                              type: 'GET'
                          }).done(function(data){
                              var data = JSON.parse(data);
                              $("#producto_des").html(data.compuesto.articulo);
                              $("#lotes").val(data.compuesto.lote);
                              $("#imprimir_etiqueta input[name='unidades_caja']").val(data.compuesto.unidades_caja);
                              $("#imprimir_etiqueta input[name='cve_articulo']").val(data.compuesto.clave);
                              $("#imprimir_etiqueta input[name='ordenp']").val(data.compuesto.ordenp);
                              $("#imprimir_etiqueta input[name='des_articulo']").val(data.compuesto.articulo);
                              $("#imprimir_etiqueta input[name='barras2']").val(data.compuesto.barras2);
                              $("#imprimir_etiqueta input[name='barras3']").val(data.compuesto.barras3);
                              console.log("data para imprimir",data)
                              $("#selectOption").modal('show');
                          })
                      }else{
                          finalizar();
                      }
                  })
                }else{
                  swal("Error!", "Ocurrió un error al guardar la información", "error");
                }
            });
        }
    }
*/

    function finalizar_orden_de_trabajo(modo = "") {
        $("#componentes_derivado, #componentes_derivado_base, #articulos_no_compuestos_base").empty();
        $("#ProductoSobrante").prop("disabled", false);
        //$("#RegistrarMerma").prop("disabled", false);
        $("#ProductoSobrante").show();
        $("#imprimir_etiqueta_btn").show();
        //$("#RegistrarMerma").show();
        console.log("modo", modo);

        if (parseFloat($("#faltante").text()) == 0) {
            $("#TerminarProduccion").prop("disabled", true);
            $("#pasarArticuloCompuesto").prop("disabled", true);
        }

        $.ajax({
            url: '/api/adminordentrabajo/update/index.php',
            data: {
                folio: $("#id_orden_trabajo").val(),
                modo: modo,
                action: 'terminar_produccion'
            },
            datatype: 'json',
            method: 'POST'
        }).done(function (data) {
            console.log("TERMINAR PRODUCCION = ", data);
            //swal('Éxito', 'Producción Terminada', 'success');

            if (data == "Pedido_Sin_Surtir") {
                swal("Orden de Trabajo Sin Picking", "No se puede terminar una orden si no se han surtido los componentes", "error");
                return;
            }

            if ($("#status_terminar_produccion").val() != 'T' && $("#mensaje_mostrar_produccion").val() == '0') {
                <?php
                if($ValorSAP == 1 && ($_SERVER['HTTP_HOST'] == 'dicoisa.assistprowms.com' || $_SERVER['HTTP_HOST'] != 'www.dicoisa.assistprowms.com'))
                {
                ?>
                //OTSap($("#id_orden_trabajo").val());
                <?php
                }
                ?>
                swal({
                        title: "Producción Terminada",
                        text: "¿Que desea hacer?",
                        type: "success",

                        showCancelButton: true,
                        cancelButtonText: "Finalizar Orden de Trabajo",
                        cancelButtonColor: "#14960a",

                        confirmButtonColor: "#55b9dd",
                        confirmButtonText: "Enviar Orden de Trabajo a Auditoría",
                        closeOnConfirm: true
                    },
                    function (e) {
                        if (e == true) {

                            $.ajax({
                                url: '/api/adminordentrabajo/update/index.php',
                                data: {
                                    folio: $("#id_orden_trabajo").val(),
                                    action: 'enviar_ot_qa'
                                },
                                datatype: 'json',
                                type: 'POST'
                            }).done(function (data) {
                                swal('Éxito', 'Orden de Trabajo Enviada a Auditoría', 'success');
                            });
                        } else {
                            console.log("NO Enviar a QA");
                            $("#status_terminar_produccion").val('T');
                        }
                        $("#mensaje_mostrar_produccion").val('1');
                    });
                $("#mensaje_mostrar_produccion").val('1');
            }


            var lote_cad = data.split(":;;;;;;:");
            console.log("lote_cad = ", lote_cad);
            $("#lotes_etiq").val(lote_cad[0]);
            $("#caducidad_etiq").val(lote_cad[1]);
            $("#componentes_derivado").append(lote_cad[2]);
            $("#articulos_no_compuestos_base").append(lote_cad[3]);
            $("#componentes_derivado_base").append(lote_cad[4]);
            $("#existencia_OT").val(lote_cad[5]);
            $("#cve_articulo_etiq").val($("#compuesto_clave").text());
            $("#ordenp_etiq").val($("#id_orden_trabajo").val());
            $("#des_articulo_etiq").val($("#compuesto_descripcion").text());
            $("#terminar_produccion").val(1);

            if ($("input[name=derivado_merma]:checked").val() == 'pbase') {
                $("#cantidad_componente").val(lote_cad[5]);
                $("#cantidad_componente").attr("readonly", "readonly");
                $("#cantidad_sobrante").attr("max", lote_cad[5]);

                $("#componentes_derivado_base_chosen, #articulos_no_compuestos_base_chosen").show();
                $('#componentes_derivado, #componentes_derivado_base, #articulos_no_compuestos_base').trigger("chosen:updated");
            } else {
                $("#cantidad_componente").val("");
                $("#cantidad_componente").removeAttr("readonly");
                $("#cantidad_sobrante").removeAttr("max");
                $("#componentes_derivado_base_chosen, #articulos_no_compuestos_base_chosen").hide();
                $('#componentes_derivado, #componentes_derivado_base, #articulos_no_compuestos_base').trigger("chosen:updated");
            }


            /*if(nlotes == 1)*/
            $("#registrar_merma_derivado").show();
            if (parseFloat($("#faltante").text()) > 0) {
                $("#merma").show();
            } else //if(nlotes == 1)
            {
                $("#label_derivado input").text("Sobrante");
                //$("#registrar_merma_derivado").hide();
            }

            //cargarLotes($("#id_orden_trabajo").val(), 1);
            //window.location.reload();

        });
    }


    $("#TerminarProduccion").click(function () {

        var mensaje = "Seguro desea Terminar la Producción?";
        var boton = "Si";
        var mostrar_boton = true;

        if (parseFloat($("#producida").text()) == 0) {
            swal("Error", "No se ha iniciado la producción", "error");
            return;
        }


        if ($("#status_terminar_produccion").val() == 'T' && $("#mensaje_mostrar_produccion").val() == '1') {
            mensaje = "Producción ya Finalizada";
            boton = "OK";
            mostrar_boton = false;
            /*if(nlotes == 1) */
            $("#registrar_merma_derivado").show();
        }


        if ($("#mensaje_mostrar_produccion").val() == '1') {
            swal({
                    title: "Aviso",
                    text: mensaje,
                    type: "warning",

                    showCancelButton: mostrar_boton,
                    cancelButtonText: "No",
                    cancelButtonColor: "#14960a",

                    confirmButtonColor: "#55b9dd",
                    confirmButtonText: boton,
                    closeOnConfirm: true
                },
                function (e) {
                    if (e == true) {
                        finalizar_orden_de_trabajo();
                    }
                });
            $("#mensaje_mostrar_produccion").val('0');
        } else
            finalizar_orden_de_trabajo();

    });

    $("#obtener_zonas_embarque").click(function () {

        if ($("#pedidos_rel").val() != '') {
            console.log("folio_pedido: ", $('#pedidos_rel').val());
            console.log("folio_orden: ", $("#id_orden_trabajo").val());
            $.ajax({
                url: '/api/adminordentrabajo/update/index.php',
                data: {
                    folio_pedido: $('#pedidos_rel').val(),
                    folio_orden: $("#id_orden_trabajo").val(),
                    action: 'evaluar_cantidad_pedido'
                },
                type: 'POST',
                dataType: 'json'
            })
                .done(function (data) {
                    $('#tipo_embarque').val(data);
                    console.log("SUCCESS = ", data);
                    if (data == 0) {
                        console.log("obtenerZonasDisponibles()");
                        obtenerZonasDisponibles();
                    } else if (data == 1) {
                        console.log("Advertir Cantidad");
//*************************************************************
                        swal({
                                title: "Advertencia",
                                text: "Esta Orden de Trabajo tiene Menor Cantidad Producida que la del Pedido Seleccionado a Embarcar. Al Aceptar embarcar Este Pedido, éste se clonará en otro pedido con la cantidad restante para volver a embarcarlo en otra ocasión",
                                type: "warning",

                                showCancelButton: true,
                                cancelButtonText: "Cancelar",
                                cancelButtonColor: "#14960a",

                                confirmButtonColor: "#55b9dd",
                                confirmButtonText: "Embarcar",
                                closeOnConfirm: true
                            },
                            function (e) {
                                if (e == true) {
                                    console.log("obtenerZonasDisponibles()");
                                    obtenerZonasDisponibles();
                                } else
                                    console.log("NO");
                            });
//*************************************************************/
                    } else {
                        console.log("Cantidad Incorrecta para dividir pedido");
                        swal("No se Puede Embarcar", "El Pedido que desea embarcar tiene menor cantidad a la Cantidad Producida en esta Orden de Trabajo", "error");
                    }
                });
        } else {
            swal("Error", "Debe Seleccionar un Pedido", "error");
        }

    });

    function obtenerZonasDisponibles() {
        $('#select_pedidos').modal('hide');
        $.ajax({
            url: '/api/administradorpedidos/lista/index.php',
            data: {
                almacen: $('#almacen').val(),
                modulo_ot: true,
                action: 'obtenerZonasEmbarque'
            },
            type: 'POST',
            dataType: 'json'
        })
            .done(function (data) {
                if (data.success) {

                    options = '';
                    $.each(data.data, function (key, value) {
                        options += '<option value="' + value.id + '">' + value.nombre + '</option>';
                    });

                    $('#zonaembarque').html(options);
                    $('#zonaembarque').trigger("chosen:updated");
                    $('#modal_zonasembarque').modal('show');
                } else {

                }
            });
    }


    function asignarZonaEmbarque() {

        console.log("folio_pro: ", $("#id_orden_trabajo").val());
        console.log("folio: ", $("#pedidos_rel").val());
        console.log("almacen: ", $('#almacen').val());
        console.log("zonaembarque: ", $('#zonaembarque').val());
        console.log("tipo_embarque: ", $('#tipo_embarque').val());

        $.ajax({
            url: '/api/administradorpedidos/update/index.php',
            data: {
                modulo_ot: true,
                folio_pro: $("#id_orden_trabajo").val(),
                folio: $("#pedidos_rel").val(),
                almacen: $('#almacen').val(),
                tipo_embarque: $("#tipo_embarque").val(),
                status: 'C',
                sufijo: 1,
                zonaembarque: $('#zonaembarque').val(),
                action: 'asignarZonaEmbarque'
            },
            type: 'POST',
            dataType: 'json'
        })
            .done(function (data) {
                if (data.success) {
                    $('#modal_zonasembarque').modal('hide');
                    $('#modalItems').modal('hide');
                    //swal("Éxito", "Status de la orden cambiado exitosamente", "success");
                    swal("Éxito", "Pedido Enviado a Embarques exitosamente", "success");

                    ReloadGrid();
                    console.log("filtralo 2");
                    //filtralo();
                    //window.location.reload();
                } else {
                    //swal("Error", "Ocurrió un error al cambiar el status de la orden", "success");
                    swal("Error", "Ocurrió un error al enviar el pedido a Embarques", "error");
                }
            });
    }


    //******************************************************************************************************
    //******************************************************************************************************
    $("#cliente_buscar").on('keyup', function (e) {
        var cliente = e.target.value;
        //console.log("cliente.length = ", cliente.length);
        if (cliente.length > 2)
            BuscarCliente(cliente);
        else
            BuscarCliente("Borrar_La_Lista_de_Clientes");
    });

    function BuscarCliente(cliente) {
        console.log("Cliente = ", cliente);
        document.getElementById("agregar_destinatario").removeAttribute("disabled");
        $.ajax({
            url: '/api/nuevospedidos/lista/index.php',
            data: {
                action: 'getClientesSelect',
                listaD: 1,
                id_almacen: <?php echo $_SESSION['id_almacen']; ?>,
                cliente: cliente
            },
            dataType: 'json',
            method: 'GET',
            beforeSend: function (x) {
                if (x && x.overrideMimeType) {
                    x.overrideMimeType("application/json;charset=UTF-8");
                }
            },
            success: function (data) {
                console.log("SUCCESS: ", data);
                //var combo = data.combo;
                //if(combo.length > 36 || data.direccion.length > 2){
                var desc_cliente = document.getElementById("desc_cliente");
                if (data.combo !== '' && data.find == true) {
                    desc_cliente.innerHTML = data.combo;
                    var text = desc_cliente.options[desc_cliente.selectedIndex].text;
                    //document.getElementById("txt-direc").value = text;
                    desc_cliente.removeAttribute('disabled');
                    //$("#cliente").val(data.clave_cliente);
                    //$("#desc_cliente").val("["+data.clave_cliente+"] - "+data.nombre_cliente);
                    $("#cliente").val(data.firsTValue);
                    BuscarDestinatario(data.firsTValue);
                    console.log("#cliente = ", $("#cliente").val());
                } else {
                    $("#destinatario, #agregar_destinatario").prop("disabled", true);
                    $("#cliente").val("");
                    $("#desc_cliente").val("");
                    console.log("#cliente = ", $("#cliente").val());
                }

                $(".chosen-select").trigger("chosen:updated");
            }, error: function (data) {
                console.log("ERROR 3: ", data);
            }

        });
    }

    function BuscarDestinatario(cliente) {
        document.getElementById("agregar_destinatario").removeAttribute("disabled");
        $.ajax({
            url: '/api/nuevospedidos/lista/index.php',
            data: {
                action: 'getDestinatario',
                descuento: 1,
                cliente: cliente
            },
            dataType: 'json',
            method: 'GET',
            beforeSend: function (x) {
                if (x && x.overrideMimeType) {
                    x.overrideMimeType("application/json;charset=UTF-8");
                }
            },
            success: function (data) {
                console.log("SUCCESS: ", data);
                var destinatario = document.getElementById("destinatario");
                if (data.combo !== '' && data.find == true) {
                    destinatario.innerHTML = data.combo;
                    var text = destinatario.options[destinatario.selectedIndex].text;
                    document.getElementById("txt-direc").value = text;
                    destinatario.removeAttribute('disabled');
                } else {
                    $("#destinatario, #agregar_destinatario").prop("disabled", true);
                }

                $(".chosen-select").trigger("chosen:updated");
            }, error: function (data) {
                console.log("ERROR 4: ", data);
            }

        });
    }

    $("#desc_cliente").on("change", function (e) {
        var cliente = $(this).val();
        BuscarDestinatario(cliente);
        //console.log("Cliente = ", cliente);
    });

    $("#destinatario").on("change", function (e) {
        var destinatario = document.getElementById("destinatario");
        var text = destinatario.options[destinatario.selectedIndex].text;
        document.getElementById("txt-direc").value = text;
    });
    //******************************************************************************************************
    //******************************************************************************************************

    $("#asignar_cliente").click(function () {

        $.ajax({
            url: '/api/adminordentrabajo/update/index.php',
            data: {
                folio: $("#id_orden_trabajo").val(),
                clave_cliente: $("#desc_cliente").val(),
                action: 'asignar_cliente'
            },
            datatype: 'json',
            type: 'POST'
        }).done(function (data) {
            //var data = JSON.parse(data);
            if (data == 1) {
                swal('Éxito', 'Cliente asignado correctamente', 'success');
                $("#agregar_cliente").modal("hide");
            } else
                swal('Error', 'El Cliente no pudo ser asignado', 'error');
        });

    });


    function Cambiar_Lote_OT(lote_usado, primera_produccion) {
        console.log("Cambiar Lote");
        console.log("folio: ", $("#id_orden_trabajo").val());
        console.log("art_compuesto: ", $("#compuesto_clave").text());
        console.log("lote_usado: ", lote_usado);
        console.log("lote_cambiar: ", $("#lote_compuesto").val());
        //return;
        $.ajax({
            url: '/api/adminordentrabajo/update/index.php',
            data: {
                folio: $("#id_orden_trabajo").val(),
                art_compuesto: $("#compuesto_clave").text(),
                //lote_usado: $("#lote_usado").val(),
                lote_usado: lote_usado,
                lote_cambiar: $("#lote_compuesto").val(),
                action: 'modificar_lote'
            },
            datatype: 'json',
            type: 'POST'
        }).done(function (data) {
            //var data = JSON.parse(data);
            if (data == 1) {
                $("#lotes_etiq").val($("#lote_compuesto").val());
                cargarLotes($("#id_orden_trabajo").val(), primera_produccion);
                if (primera_produccion > 0)
                    swal('Éxito', 'Lote del Artículo Compuesto Modificado', 'success');
                else
                    swal('Éxito', 'El Lote del Artículo Compuesto se Modificará al Realizar la producción', 'success');
            } else
                swal('Error', 'El Lote ya Existe', 'error');
        });
    }

    $("#cambiar_lote").click(function () {

        swal({
                title: "Aviso",
                text: "Seguro desea Cambiar el Lote del Artículo Compuesto?",
                type: "warning",

                showCancelButton: true,
                cancelButtonText: "No",
                cancelButtonColor: "#14960a",

                confirmButtonColor: "#55b9dd",
                confirmButtonText: "Si",
                closeOnConfirm: true
            },
            function (e) {
                if (e == true) {
                    var primera_produccion = 1;
                    if ($("#producida").text() == '0') primera_produccion = 0;

                    Cambiar_Lote_OT($("#lote_usado").val(), primera_produccion);
                } else
                    console.log("NO Cambiar Lote");
            });


    });

    function Cambiar_Caducidad_OT(lote_usado, primera_produccion) {
        console.log("Cambiar Caducidad");

        console.log("folio: ", $("#id_orden_trabajo").val());
        console.log("caducidad: ", $("#caducidad_compuesto").val());
        console.log("art_compuesto: ", $("#compuesto_clave").text());
        //console.log("lote_usado: ", $("#lote_usado").val());
        console.log("lote_usado: ", lote_usado);

        setTimeout(function () {

            var vfecha = VerificarCaducidad($("#caducidad_compuesto").val());
            if (vfecha == 1) {
                //$("#caducidad_compuesto").change();
                console.log("vfecha =", vfecha);
                swal("Caducidad Inválida", "Debe registrar una caducidad válida mayor a la fecha actual", "error");
                //return;
            }
            if (vfecha == 2) {
                //$("#caducidad_compuesto").change();
                console.log("vfecha =", vfecha);
                swal("Fecha ya Caducada", "Debe registrar una caducidad válida mayor a la fecha actual", "error");
                //return;
            }

            if (vfecha == 0) {
                $.ajax({
                    url: '/api/adminordentrabajo/update/index.php',
                    data: {
                        folio: $("#id_orden_trabajo").val(),
                        caducidad: $("#caducidad_compuesto").val(),
                        art_compuesto: $("#compuesto_clave").text(),
                        //lote_usado: $("#lote_usado").val(),
                        lote_usado: lote_usado,
                        action: 'modificar_caducidad'
                    },
                    datatype: 'json',
                    type: 'POST'
                }).done(function (data) {
                    var data = JSON.parse(data);
                    $("#caducidad_etiq").val($("#caducidad_compuesto").val());
                    cargarLotes($("#id_orden_trabajo").val(), primera_produccion);
                    if (primera_produccion > 0)
                        swal('Éxito', 'Caducidad del Artículo Compuesto Modificada', 'success');
                    else swal('Éxito', 'La Caducidad del Artículo Compuesto será Modificada al iniciar la producción', 'success');
                });
            }

        }, 1000);
    }

    $("#cambiar_caducidad").click(function () {

        swal({
                title: "Aviso",
                text: "Seguro desea Cambiar la Caducidad del Artículo Compuesto?",
                type: "warning",

                showCancelButton: true,
                cancelButtonText: "No",
                cancelButtonColor: "#14960a",

                confirmButtonColor: "#55b9dd",
                confirmButtonText: "Si",
                closeOnConfirm: true
            },
            function (e) {
                if (e == true) {
                    Cambiar_Caducidad_OT($("#lote_usado").val(), 1);
                } else
                    console.log("NO Cambiar Caducidad");
            });


    });

    $("#ProductoSobrante").click(function () {
        $("#sobrante").show(500);
    });

    $("#CancelarSobrante, #CancelarMerma").click(function () {
        $("#sobrante").hide(500);
    });


    /*
    $("#generarLotes").on("click", function(){
      var loteProduccion = $("#compuesto_clave").html();

      if($.isEmptyObject($('#grid-table4').jqGrid ('getRowData', loteProduccion))){
        var articulos = $("#grid-table5").jqGrid('getCol','clave');
        var lotes = $("#grid-table3").jqGrid('getRowData');

        $.ajax({
            url: '/api/adminordentrabajo/lista/index.php',
            method: 'GET',
            datatype: 'json',
            data: {
              action: 'obtenerLoteProduccion',
              folio: $("#ordenprod").html()
            }
        }).done(function(res){
            var res = JSON.parse(res);
            var articulos = res.articulos;
            for(i = 0; i < articulos.length; i++){
              var articulo = articulos[i];
              var datoArticulo = jQuery('#grid-table4').jqGrid ('getRowData', articulo.clave);
              datoArticulo.lote = articulo.lote;
              datoArticulo.caducidad = articulo.caducidad;
              $('#grid-table4').jqGrid('setRowData', articulo.clave, datoArticulo);
            }
            var data = {
              clave: $("#compuesto_clave").html(),
              descripcion: $("#compuesto_descripcion").html(),
              cantidad: $("#compuesto_cantidad").html(),
              lote: res.produccion.lote,
              caducidad: res.produccion.caducidad
            };
            $("#grid-table4").jqGrid('addRowData', data.clave, data, "last");
            $(`tr#${data.clave}`).css({'background-color': 'yellow'});
          });
      }else{
          swal("Error", "Ya los lotes fueron generados", "error");
      }
    });
*/
    function finalizar() {
        $("#selectOption").modal('hide');
        /*
        $("#asignarUsuario").addClass('hidden');
        $("#asignarUsuario").removeClass('fadeIn animated');
        $("#list").fadeIn();
        $('#grid-table').jqGrid('clearGridData')
                        .jqGrid('setGridParam', {postData: {}, datatype: 'json', page : 1})
                        .trigger('reloadGrid',[{current:true}]);
*/
    }

    function recalcularCantidadRequerida() {
        console.log("Recalcular");
        /*
      $.ajax({
            url: '/api/adminordentrabajo/lista/index.php',
            method: 'GET',
            datatype: 'json',
            data: {
              action: 'obtenerLoteProduccion',
              folio: $("#ordenprod").html()
            }
        });
      */
    }

    //**************************************************************
    //                          CRONÓMETRO
    //**************************************************************
    function IniciarCronometro() {
        console.log("CRONÓMETRO");


        $.ajax({
            url: '/api/adminordentrabajo/update/index.php',
            data: {
                folio: $("#id_orden_trabajo").val(),
                action: 'verificar_cronometro'
            },
            datatype: 'json',
            type: 'POST'
        }).done(function (data) {
            //var data = JSON.parse(data);
            console.log("CRONÓMETRO", data);

            $("#fecha_hora").text(data);
        });

        var tiempo = {
            hora: 0,
            minuto: 0,
            segundo: 0
        };

        var tiempo_corriendo = null;

        $.ajax({
            url: '/api/adminordentrabajo/update/index.php',
            data: {
                folio: $("#id_orden_trabajo").val(),
                action: 'iniciar_cronometro'
            },
            datatype: 'json',
            type: 'POST'
        }).done(function (data) {
            var time = data.split(":");
            tiempo.hora = time[0];
            tiempo.minuto = time[1];
            tiempo.segundo = time[2];
            //var data = JSON.parse(data);
            console.log("Variables", data);
            console.log("CRONÓMETRO", $("#hour").text(), $("#minute").text(), $("#second").text());
            //$("#fecha_hora").text(data);
        });

        tiempo_corriendo = setInterval(function () {
            // Segundos
            tiempo.segundo++;
            if (tiempo.segundo >= 60) {
                tiempo.segundo = 0;
                tiempo.minuto++;
            }

            // Minutos
            if (tiempo.minuto >= 60) {
                tiempo.minuto = 0;
                tiempo.hora++;
            }

            $("#hour").text(tiempo.hora < 10 ? '0' + parseInt(tiempo.hora) : tiempo.hora);
            $("#minute").text(tiempo.minuto < 10 ? '0' + parseInt(tiempo.minuto) : tiempo.minuto);
            $("#second").text(tiempo.segundo < 10 ? '0' + parseInt(tiempo.segundo) : tiempo.segundo);

            $.ajax({
                url: '/api/adminordentrabajo/update/index.php',
                data: {
                    folio: $("#id_orden_trabajo").val(),
                    h: $("#hour").text(),
                    m: $("#minute").text(),
                    s: $("#second").text(),
                    action: 'seguir_cronometro'
                },
                datatype: 'json',
                type: 'POST'
            }).done(function (data) {
                var time = data.split(":");
                tiempo.hora = time[0];
                tiempo.minuto = time[1];
                tiempo.segundo = time[2];
                //var data = JSON.parse(data);
                //console.log("Variables", data);
                //console.log("CRONÓMETRO", $("#hour").text(), $("#minute").text(), $("#second").text());
                //$("#fecha_hora").text(data);
            });

            if ($("#terminar_produccion").val() == 1)
                clearInterval(tiempo_corriendo);

        }, 1000);
    }

    //**************************************************************

</script>
