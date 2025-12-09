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
        if (!Schema::hasTable('t_correo_job')) {
            Schema::create('t_correo_job', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('tipo_destino');
                $table->string('filtro_json')->nullable();
                $table->integer('plantilla_id');
                $table->integer('smtp_config_id');
                $table->string('alias_smtp')->nullable();
                $table->string('tipo_frecuencia')->default('ON_DEMAND');
                $table->string('hora_envio')->nullable();
                $table->boolean('dia_semana')->nullable();
                $table->boolean('dia_mes')->nullable();
                $table->integer('intervalo_horas')->nullable();
                $table->timestamp('proxima_ejecucion')->nullable();
                $table->timestamp('ultima_ejecucion')->nullable();
                $table->boolean('activo')->default('1');
                $table->timestamp('fecha_creacion')->default('CURRENT_TIMESTAMP');
                $table->timestamp('fecha_actualiza')->nullable();
                $table->timestamps(); // created_at y updated_at
                $table->index('plantilla_id', 'fk_job_plantilla');
                $table->index('smtp_config_id', 'fk_job_smtp');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_correo_job` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_correo_job');
    }
};