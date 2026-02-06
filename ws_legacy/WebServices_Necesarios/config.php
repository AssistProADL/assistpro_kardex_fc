<?php

  /**
    * Error Reporting --- ignorar
  **/
  error_reporting( E_ALL & ~E_NOTICE );
  ini_set( 'display_errors', 'On' );

  /**
    * MySQL Details
  **/

define( 'DB_HOST', '10.0.0.38' );
define( 'DB_USER', 'assistpr_wmsdev' );
define( 'DB_NAME', 'assistpr_wmsdev' );
define( 'DB_PASSWORD', '0sGYvVuzeGX)' );

/*
define( 'DB_HOST', '10.0.0.38' );
define( 'DB_USER', 'assistpr_wms0154' );
define( 'DB_NAME', 'assistpr_wms0154' );
define( 'DB_PASSWORD', 'RVl{KT8aqQoT' );
*/
  /**
    * SQL Server Details
  **/
define( 'DB_REMOTE_HOST', 'vps176454.vps.ovh.ca,1434' );
define( 'DB_REMOTE_USER', 'sa' );
define( 'DB_REMOTE_NAME', 'lacentral' );
define( 'DB_REMOTE_PASSWORD', 'L4c3ntral 096253' );

  /****
    * General Details
  **/
  define( 'SITE_TITLE', 'AssistPro ADL WMS | 2020OC' );
  define( 'SITE_EMAIL', 'send@advanceware.com' );
  define( 'SITE_URL', 'http://wms.local.com/' );

  /**
    * SMTP Details
  ****/
  define( 'SMTP_HOSTNAME', 'mail.advanceware.com' );          // Semi-colon (;) seperated list for multiple entries
  define( 'SMTP_USERNAME', 'send@advanceware.com' );
  define( 'SMTP_PASSWORD', 'test2' );
  define( 'SMTP_PROTOCOL', 'tls' );
  define( 'SMTP_PORT', '25' );

  /**
    * Mercado Pago
  **/
  define( 'MERCADO_CLIENT', '7151238173629837' );
  define( 'MERCADO_SECRET', 'Vpq911rjWF0R9MoM44u9wytNyh5Lu2CT' );
