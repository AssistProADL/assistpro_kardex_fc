<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('rel_articulo_proveedor_respaldo_sc')) {
            Schema::create('rel_articulo_proveedor_respaldo_sc', function (Blueprint $table) {
                $table->integer('id')->nullable();
                $table->string('Cve_Articulo')->nullable();
                $table->integer('Id_Proveedor');
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `rel_articulo_proveedor_respaldo_sc` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rel_articulo_proveedor_respaldo_sc');
    }
};