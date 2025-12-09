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
        if (!Schema::hasTable('t_correo_queue')) {
            Schema::create('t_correo_queue', function (Blueprint $table) {
                $table->id();
                $table->integer('job_id')->nullable();
                $table->string('destino_tipo');
                $table->integer('destino_id')->nullable();
                $table->string('email_to');
                $table->string('asunto_resuelto');
                $table->text('cuerpo_resuelto_html')->nullable();
                $table->text('cuerpo_resuelto_texto')->nullable();
                $table->integer('intentos')->default('0');
                $table->timestamp('ultimo_intento')->nullable();
                $table->boolean('enviado')->default('0');
                $table->timestamp('fecha_enviado')->nullable();
                $table->text('error_msg')->nullable();
                $table->timestamp('fecha_creacion')->default('CURRENT_TIMESTAMP');
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
                $table->index(['enviado', 'intentos'], 'idx_enviado_intentos');
                $table->index('job_id', 'idx_job');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_correo_queue` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_correo_queue');
    }
};