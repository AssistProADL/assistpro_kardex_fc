<?php
require_once __DIR__ . '/app/bootstrap.php';
use Illuminate\Database\Capsule\Manager as DB;

try {
    echo "--- Almacenes Padres (Distinct cve_almacenp) ---\n";
    $parents = DB::table('c_almacen')->select('cve_almacenp')->distinct()->get();
    foreach ($parents as $p) {
        echo "Parent ID: " . $p->cve_almacenp . "\n";
        if ($p->cve_almacenp) {
            $name = DB::table('c_almacen')->where('cve_almac', $p->cve_almacenp)->value('des_almac');
            echo "  Name: " . $name . "\n";
        }
    }

    echo "\n--- Top 5 Almacenes ---\n";
    $almacenes = DB::table('c_almacen')->limit(5)->get();
    foreach ($almacenes as $a) {
        echo "ID: {$a->cve_almac}, Parent: {$a->cve_almacenp}, Name: {$a->des_almac}\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
