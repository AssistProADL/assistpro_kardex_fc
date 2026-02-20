<?php

    if(!isset($_SESSION)) {
        session_start();
    }

    /**
    * Required files
    **/

    include_once dirname( __DIR__ ) . '/config.php';
    include_once 'vendor/autoload.php';
    include_once 'autoload.php';
    include_once 'db.php';

    date_default_timezone_set('America/Los_Angeles');



    /**
    * Database Connection
    **/

    $db = new PDO(
      sprintf( 'mysql:host=%s;dbname=%s;charset=utf8', DB_HOST, DB_NAME )
      , DB_USER
      , DB_PASSWORD
	  , array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8") 
    );
    $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    \db($db);

    $db2 = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    mysqli_select_db($db2, DB_NAME);
    \db2($db2);

    /**
    * Path
    **/

    define( 'PATH', dirname( __DIR__ ) . '/' );



    /**
    * User ID
    **/

    $p = explode( '/', $_SERVER['REQUEST_URI'] );

    $subdomain = "f";

    if (isset($_SESSION['id_user'])) {
        //$u = new \User\User();
        //$u->id_user = $_SESSION['id_user'];
        $subdomain = $_SESSION['identifier'];
        $_subdomain = $_SESSION['subdomain'];
        define( 'SUBDOMAIN_USER', $_subdomain );
        define( 'DYNAMIC_SUBDOMAIN', $_subdomain.'.presshunters.com' );
    }

    preg_match("/([a-zA-Z0-9])+/", $p[1], $m);

    if( isset( $_SESSION['id_user'] ) AND $_SESSION['id_user'] ) {
        define( 'ID_USER', $_SESSION['id_user'] );
    } elseif( !defined('NO_LOGIN') AND $p[0] != $subdomain) { //($m[0]=="login" || empty($m[0]))) {
        header( 'Location: /login' );
    exit();
    }


    /**
    * Static path
    **/

    define( 'ST', '/app/template/static/' );
