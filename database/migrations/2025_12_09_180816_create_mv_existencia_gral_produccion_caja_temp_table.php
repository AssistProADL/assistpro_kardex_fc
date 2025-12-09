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
        if (!Schema::hasTable('mv_existencia_gral_produccion_caja_temp')) {
            Schema::create('mv_existencia_gral_produccion_caja_temp', function (Blueprint $table) {
                $table->integer('cve_almac');
                $table->string('cve_ubicacion')->nullable();
                $table->string('cve_articulo');
                $table->string('cve_lote');
                $table->decimal('Existencia')->nullable();
                $table->string('tipo');
                $table->string('Cve_Contenedor');
                $table->bigInteger('Cuarentena')->nullable();
                $table->integer('Id_Proveedor');
                $table->string('Lote_Alterno');
                $table->bigInteger('Id_Caja');
                $table->string('Cve_Caja');
                $table->decimal('Cantidad_Caja');
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `mv_existencia_gral_produccion_caja_temp` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mv_existencia_gral_produccion_caja_temp');
    }
};