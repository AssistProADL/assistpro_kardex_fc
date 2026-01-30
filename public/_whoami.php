<?php
echo "Loaded php.ini: <b>".php_ini_loaded_file()."</b><br>";
echo "cURL exists: <b>".(function_exists('curl_init')?'YES':'NO')."</b>";
