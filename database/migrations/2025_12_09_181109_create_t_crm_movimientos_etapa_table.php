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
        if (!Schema::hasTable('t_crm_movimientos_etapa')) {
            Schema::create('t_crm_movimientos_etapa', function (Blueprint $table) {
                $table->integer('id_mov');
                $table->integer('id_opp');
                $table->string('etapa_anterior')->nullable();
                $table->string('etapa_nueva')->nullable();
                $table->string('usuario')->nullable();
                $table->timestamp('fecha')->nullable()->default('CURRENT_TIMESTAMP');
                $table->string('comentario')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
                $table->index('id_opp', 'id_opp');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_crm_movimientos_etapa` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_crm_movimientos_etapa');
    }
};