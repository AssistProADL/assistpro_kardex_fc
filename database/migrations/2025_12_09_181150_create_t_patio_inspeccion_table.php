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
        if (!Schema::hasTable('t_patio_inspeccion')) {
            Schema::create('t_patio_inspeccion', function (Blueprint $table) {
                $table->integer('id_inspeccion');
                $table->integer('id_visita');
                $table->string('tipo_inspeccion');
                $table->timestamp('fecha');
                $table->string('usuario_inspeccion');
                $table->string('usuario_autoriza')->nullable();
                $table->timestamp('fecha_autoriza')->nullable();
                $table->string('sello_origen')->nullable();
                $table->string('sello_llegada')->nullable();
                $table->string('sello_salida')->nullable();
                $table->string('coincide_sello')->nullable()->default('S');
                $table->string('estado_unidad')->nullable();
                $table->string('resultado_qa')->nullable()->default('APROBADO');
                $table->text('observaciones')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_patio_inspeccion` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_patio_inspeccion');
    }
};