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
        if (!Schema::hasTable('t_crm_oportunidad')) {
            Schema::create('t_crm_oportunidad', function (Blueprint $table) {
                $table->integer('id_opp');
                $table->integer('id_lead')->nullable();
                $table->integer('id_cliente')->nullable();
                $table->string('titulo');
                $table->decimal('valor_estimado')->nullable()->default('0.00');
                $table->integer('probabilidad')->nullable()->default('10');
                $table->string('etapa')->nullable()->default('Prospección');
                $table->string('motivo_perdida')->nullable();
                $table->date('fecha_cierre_estimada')->nullable();
                $table->string('usuario_responsable')->nullable();
                $table->timestamp('fecha_crea')->nullable()->default('CURRENT_TIMESTAMP');
                $table->timestamp('fecha_modifica')->nullable()->default('CURRENT_TIMESTAMP');
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
                $table->index('id_lead', 'id_lead');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_crm_oportunidad` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_crm_oportunidad');
    }
};