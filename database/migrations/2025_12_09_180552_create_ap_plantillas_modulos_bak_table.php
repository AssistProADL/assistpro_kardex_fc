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
        if (!Schema::hasTable('ap_plantillas_modulos_bak')) {
            Schema::create('ap_plantillas_modulos_bak', function (Blueprint $table) {
                $table->bigInteger('id')->default('0');
                $table->string('clave_modulo');
                $table->string('nombre');
                $table->string('vista_sql')->nullable();
                $table->string('tipo_modulo')->nullable();
                $table->string('ruta_sugerida')->nullable();
                $table->string('descripcion')->nullable();
                $table->boolean('activo')->default('1');
                $table->timestamp('creado_en')->default('CURRENT_TIMESTAMP');
                $table->timestamp('actualizado_en')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `ap_plantillas_modulos_bak` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ap_plantillas_modulos_bak');
    }
};