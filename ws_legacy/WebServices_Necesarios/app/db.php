<?php

  /**
    * DB Connection
  **/

  function db($arg = null) {
    static $val;
    if ($arg) $val = $arg;
    return $val;
  }

  function db2($arg = null) {
    static $val;
    if ($arg) $val = $arg;
    return $val;
  } 