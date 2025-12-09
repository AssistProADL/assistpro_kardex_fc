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
        if (!Schema::hasTable('t_crm_actividad')) {
            Schema::create('t_crm_actividad', function (Blueprint $table) {
                $table->integer('id_act');
                $table->integer('id_lead')->nullable();
                $table->integer('id_opp')->nullable();
                $table->string('tipo')->nullable();
                $table->text('descripcion')->nullable();
                $table->timestamp('fecha_programada')->nullable();
                $table->timestamp('fecha_realizada')->nullable();
                $table->string('usuario')->nullable();
                $table->string('estatus')->nullable()->default('Programada');
                $table->timestamp('fecha_crea')->nullable()->default('CURRENT_TIMESTAMP');
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
                $table->index('id_lead', 'id_lead');
                $table->index('id_opp', 'id_opp');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_crm_actividad` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_crm_actividad');
    }
};