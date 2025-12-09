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
        if (!Schema::hasTable('th_mto_orden')) {
            Schema::create('th_mto_orden', function (Blueprint $table) {
                $table->id();
                $table->integer('cve_cia');
                $table->integer('transporte_id');
                $table->string('folio');
                $table->integer('tipo_id');
                $table->integer('taller_id')->nullable();
                $table->string('origen')->default('PROGRAMADO');
                $table->timestamp('fecha_programada')->nullable();
                $table->decimal('km_programados')->nullable();
                $table->string('estatus')->default('ABIERTA');
                $table->string('motivo_cancelacion')->nullable();
                $table->decimal('km_inicio')->nullable();
                $table->decimal('km_fin')->nullable();
                $table->decimal('horas_inicio')->nullable();
                $table->decimal('horas_fin')->nullable();
                $table->decimal('costo_mano_obra')->default('0.00');
                $table->decimal('costo_refacciones')->default('0.00');
                $table->string('usuario_crea');
                $table->timestamp('fecha_crea')->default('CURRENT_TIMESTAMP');
                $table->string('usuario_cierre')->nullable();
                $table->timestamp('fecha_cierre')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
                $table->index('taller_id', 'fk_mto_orden_taller');
                $table->index(['cve_cia', 'estatus'], 'ix_mto_orden_cia_estatus');
                $table->unique(['cve_cia', 'folio'], 'ux_mto_orden_cia_folio');
                $table->index('tipo_id', 'ix_mto_orden_tipo');
                $table->index('transporte_id', 'ix_mto_orden_transporte');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `th_mto_orden` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('th_mto_orden');
    }
};