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
        if (!Schema::hasTable('ap_plantillas_filtros')) {
            Schema::create('ap_plantillas_filtros', function (Blueprint $table) {
                $table->id();
                $table->string('modulo');
                $table->string('nombre');
                $table->string('descripcion')->nullable();
                $table->string('vista_sql')->nullable();
                $table->string('filtros_json');
                $table->boolean('es_default')->default('0');
                $table->boolean('activo')->default('1');
                $table->integer('creado_por')->nullable();
                $table->timestamp('creado_en')->default('CURRENT_TIMESTAMP');
                $table->timestamp('actualizado_en')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `ap_plantillas_filtros` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ap_plantillas_filtros');
    }
};