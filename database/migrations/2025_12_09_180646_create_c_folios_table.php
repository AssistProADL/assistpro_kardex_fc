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
        if (!Schema::hasTable('c_folios')) {
            Schema::create('c_folios', function (Blueprint $table) {
                $table->id();
                $table->integer('empresa_id');
                $table->string('modulo');
                $table->string('serie');
                $table->string('descripcion')->nullable();
                $table->string('prefijo')->nullable();
                $table->string('sufijo')->nullable();
                $table->bigInteger('folio_inicial')->default('1');
                $table->bigInteger('folio_actual')->default('0');
                $table->bigInteger('folio_final')->nullable();
                $table->boolean('longitud_num')->default('6');
                $table->boolean('rellenar_ceros')->default('1');
                $table->date('vigente_desde')->nullable();
                $table->date('vigente_hasta')->nullable();
                $table->boolean('activo')->default('1');
                $table->string('usuario_crea');
                $table->timestamp('fecha_crea')->default('CURRENT_TIMESTAMP');
                $table->string('usuario_mod')->nullable();
                $table->timestamp('fecha_mod')->nullable();
                $table->timestamps(); // created_at y updated_at
                $table->index('modulo', 'idx_c_folios_modulo');
                $table->unique(['empresa_id', 'modulo', 'serie'], 'uk_c_folios_emp_mod_serie');
                $table->index('activo', 'idx_c_folios_activo');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_folios` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_folios');
    }
};