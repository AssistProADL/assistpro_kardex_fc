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
        if (!Schema::hasTable('th_incidencia')) {
            Schema::create('th_incidencia', function (Blueprint $table) {
                $table->integer('ID_Incidencia');
                $table->string('Fol_folio')->nullable();
                $table->string('ReportadoCas')->nullable();
                $table->text('Descripcion')->nullable();
                $table->text('Respuesta')->nullable();
                $table->string('status')->nullable();
                $table->timestamp('Fecha')->nullable();
                $table->integer('Activo')->nullable();
                $table->string('clave')->nullable();
                $table->string('centro_distribucion')->nullable();
                $table->string('cliente')->nullable();
                $table->string('reportador')->nullable();
                $table->string('cargo_reportador')->nullable();
                $table->string('responsable_recibo')->nullable();
                $table->string('responsable_caso')->nullable();
                $table->text('plan_accion')->nullable();
                $table->string('responsable_plan')->nullable();
                $table->timestamp('Fecha_accion')->nullable();
                $table->string('responsable_verificacion')->nullable();
                $table->string('tipo_reporte')->nullable();
                $table->integer('id_motivo_registro')->nullable();
                $table->text('desc_motivo_registro')->nullable();
                $table->integer('id_motivo_cierre')->nullable();
                $table->text('desc_motivo_cierre')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `th_incidencia` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('th_incidencia');
    }
};