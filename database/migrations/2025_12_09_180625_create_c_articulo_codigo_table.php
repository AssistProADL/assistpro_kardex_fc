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
        if (!Schema::hasTable('c_articulo_codigo')) {
            Schema::create('c_articulo_codigo', function (Blueprint $table) {
                $table->integer('Cve_Almacen');
                $table->string('Cve_Articulo');
                $table->string('Cve_Clte');
                $table->string('Codigo')->nullable();
                $table->string('Sku_R')->nullable();
                $table->string('Descripcion')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_articulo_codigo` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_articulo_codigo');
    }
};