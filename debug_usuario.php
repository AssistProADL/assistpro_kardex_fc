<?php
require_once __DIR__ . '/app/bootstrap.php';
use Illuminate\Database\Capsule\Manager as DB;

try {
    echo "--- c_usuario ---\n";
    $row = DB::table('c_usuario')->first();
    $columns = array_keys((array) $row);
    foreach ($columns as $col) {
        echo "- " . $col . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
