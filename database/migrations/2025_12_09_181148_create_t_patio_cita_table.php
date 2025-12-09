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
        if (!Schema::hasTable('t_patio_cita')) {
            Schema::create('t_patio_cita', function (Blueprint $table) {
                $table->integer('id_cita');
                $table->integer('id_transporte');
                $table->integer('empresa_id');
                $table->text('almacenp_id');
                $table->string('tipo_operacion');
                $table->timestamp('ventana_inicio');
                $table->timestamp('ventana_fin');
                $table->boolean('prioridad')->nullable()->default('3');
                $table->string('estatus')->nullable()->default('PROGRAMADA');
                $table->string('referencia_doc')->nullable();
                $table->integer('id_cliente')->nullable();
                $table->integer('id_proveedor')->nullable();
                $table->text('comentarios')->nullable();
                $table->string('usuario_crea');
                $table->timestamp('fecha_crea');
                $table->string('usuario_modifica')->nullable();
                $table->timestamp('fecha_modifica')->nullable();
                $table->string('usuario_confirma')->nullable();
                $table->timestamp('fecha_confirma')->nullable();
                $table->string('usuario_cancela')->nullable();
                $table->timestamp('fecha_cancela')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_patio_cita` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_patio_cita');
    }
};