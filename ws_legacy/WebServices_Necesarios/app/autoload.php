<?php

  /**

    This is inspired from the example set by a group of brilliant people.
    https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md

  **/

  function autoload($className) {
    $className = ltrim($className, '\\');

    $filename = __DIR__
              . DIRECTORY_SEPARATOR
              . 'class'
              . DIRECTORY_SEPARATOR
              . str_replace('\\', DIRECTORY_SEPARATOR, $className)
              . '.php'
              ;

    if(is_file($filename)) {
      require $filename;
    }
  }

  spl_autoload_register('autoload');