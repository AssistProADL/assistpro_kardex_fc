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
        if (!Schema::hasTable('c_mto_actividad')) {
            Schema::create('c_mto_actividad', function (Blueprint $table) {
                $table->id();
                $table->integer('cve_cia');
                $table->string('CVE_ACT');
                $table->string('descripcion');
                $table->integer('tipo_id');
                $table->integer('familia_id')->nullable();
                $table->integer('km_frecuencia')->nullable();
                $table->integer('dias_frecuencia')->nullable();
                $table->integer('horas_frecuencia')->nullable();
                $table->integer('tiempo_estimado_min')->nullable();
                $table->decimal('tarifa_mano_obra')->nullable();
                $table->decimal('tarifa_fija')->nullable();
                $table->boolean('activo')->default('1');
                $table->timestamps(); // created_at y updated_at
                $table->index('familia_id', 'ix_mto_act_familia');
                $table->unique(['cve_cia', 'CVE_ACT'], 'ux_mto_act_cia_codigo');
                $table->index('tipo_id', 'ix_mto_act_tipo');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_mto_actividad` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_mto_actividad');
    }
};