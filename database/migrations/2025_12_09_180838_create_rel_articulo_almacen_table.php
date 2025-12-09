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
        if (!Schema::hasTable('rel_articulo_almacen')) {
            Schema::create('rel_articulo_almacen', function (Blueprint $table) {
                $table->integer('Id');
                $table->integer('Cve_Almac');
                $table->string('Cve_Articulo')->nullable();
                $table->integer('Grupo_ID')->nullable();
                $table->integer('Clasificacion_ID')->nullable();
                $table->integer('Tipo_Art_ID')->nullable();
                $table->decimal('StockMax')->nullable();
                $table->decimal('StockMin')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `rel_articulo_almacen` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rel_articulo_almacen');
    }
};