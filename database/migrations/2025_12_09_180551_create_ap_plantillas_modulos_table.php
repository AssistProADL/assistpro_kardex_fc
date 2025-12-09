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
        if (!Schema::hasTable('ap_plantillas_modulos')) {
            Schema::create('ap_plantillas_modulos', function (Blueprint $table) {
                $table->id();
                $table->string('clave_modulo');
                $table->string('nombre');
                $table->string('vista_sql')->nullable();
                $table->string('tipo_modulo')->nullable()->default('REPORTE');
                $table->string('ruta_sugerida')->nullable();
                $table->text('descripcion')->nullable();
                $table->text('filtros_json')->nullable();
                $table->boolean('activo')->nullable()->default('1');
                $table->timestamp('creado_en')->nullable()->default('CURRENT_TIMESTAMP');
                $table->timestamp('actualizado_en')->nullable();
                $table->timestamps(); // created_at y updated_at
                $table->unique('clave_modulo', 'clave_modulo');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `ap_plantillas_modulos` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ap_plantillas_modulos');
    }
};