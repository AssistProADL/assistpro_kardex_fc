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
        if (!Schema::hasTable('ts_existenciacd')) {
            Schema::create('ts_existenciacd', function (Blueprint $table) {
                $table->integer('Id');
                $table->string('Cve_Articulo')->nullable();
                $table->string('Cve_lote')->nullable();
                $table->decimal('Cantidad')->nullable();
                $table->string('cve_ubicacion')->nullable();
                $table->integer('ID_Proveedor')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `ts_existenciacd` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ts_existenciacd');
    }
};