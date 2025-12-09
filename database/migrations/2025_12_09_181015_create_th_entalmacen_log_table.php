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
        if (!Schema::hasTable('th_entalmacen_log')) {
            Schema::create('th_entalmacen_log', function (Blueprint $table) {
                $table->string('Fol_Folio')->nullable();
                $table->timestamp('fecha_inicio')->nullable();
                $table->timestamp('fecha_fin')->nullable();
                $table->integer('id');
                $table->string('cve_usuario')->nullable();
                $table->string('quehizo')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `th_entalmacen_log` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('th_entalmacen_log');
    }
};