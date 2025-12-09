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
        if (!Schema::hasTable('mv_existencia_gral')) {
            Schema::create('mv_existencia_gral', function (Blueprint $table) {
                $table->integer('cve_almac');
                $table->string('cve_ubicacion')->nullable();
                $table->string('cve_articulo');
                $table->string('cve_lote');
                $table->decimal('Existencia')->nullable();
                $table->integer('Id_Proveedor');
                $table->string('tipo');
                $table->bigInteger('Cuarentena')->nullable();
                $table->string('Cve_Contenedor');
                $table->string('Lote_Alterno');
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
                $table->index('cve_almac', 'idx_mvexist_almacen');
                $table->index(['cve_almac', 'cve_ubicacion', 'cve_articulo', 'cve_lote'], 'idx_mvexist_alm_ubi_art_lote');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `mv_existencia_gral` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mv_existencia_gral');
    }
};