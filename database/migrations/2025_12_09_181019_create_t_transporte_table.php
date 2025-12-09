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
        if (!Schema::hasTable('t_transporte')) {
            Schema::create('t_transporte', function (Blueprint $table) {
                $table->id();
                $table->string('ID_Transporte')->nullable();
                $table->string('Nombre')->nullable();
                $table->integer('anio')->nullable();
                $table->string('Placas')->nullable();
                $table->integer('cve_cia')->nullable();
                $table->integer('Activo')->nullable();
                $table->string('estatus_uso')->nullable()->default('ACTIVO');
                $table->string('tipo_transporte')->nullable();
                $table->string('tipo_combustible')->nullable();
                $table->decimal('km_inicial')->nullable();
                $table->decimal('km_actual')->nullable();
                $table->decimal('horas_motor_actual')->nullable();
                $table->integer('id_almac')->nullable();
                $table->integer('operador_principal_id')->nullable();
                $table->timestamp('fecha_alta')->nullable();
                $table->string('num_ec')->nullable();
                $table->integer('transporte_externo')->nullable();
                $table->integer('es_transportista')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_transporte` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_transporte');
    }
};