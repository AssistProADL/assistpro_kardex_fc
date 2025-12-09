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
        if (!Schema::hasTable('t_patio_visita')) {
            Schema::create('t_patio_visita', function (Blueprint $table) {
                $table->integer('id_visita');
                $table->integer('id_cita')->nullable();
                $table->integer('id_transporte');
                $table->integer('empresa_id');
                $table->text('almacenp_id');
                $table->integer('id_zona')->nullable();
                $table->integer('id_anden_actual')->nullable();
                $table->string('estatus')->nullable()->default('EN_PATIO');
                $table->timestamp('fecha_llegada');
                $table->timestamp('fecha_salida')->nullable();
                $table->text('observaciones')->nullable();
                $table->string('usuario_checkin');
                $table->string('usuario_checkout')->nullable();
                $table->string('usuario_asigna')->nullable();
                $table->timestamp('fecha_asigna')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_patio_visita` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_patio_visita');
    }
};