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
        if (!Schema::hasTable('t_servicio_diagnostico')) {
            Schema::create('t_servicio_diagnostico', function (Blueprint $table) {
                $table->id();
                $table->integer('servicio_id');
                $table->text('diagnostico')->nullable();
                $table->text('causa_raiz')->nullable();
                $table->decimal('tiempo_estimado_horas')->nullable();
                $table->decimal('tiempo_real_horas')->nullable();
                $table->date('fecha_estim_entrega')->nullable();
                $table->date('fecha_real_entrega')->nullable();
                $table->decimal('costo_mano_obra')->nullable()->default('0.00');
                $table->decimal('costo_materiales')->nullable()->default('0.00');
                $table->decimal('costo_total')->nullable()->default('0.00');
                $table->timestamp('created_at');
                $table->string('created_by');
                $table->timestamp('updated_at')->nullable();
                $table->string('updated_by')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->index('servicio_id', 'idx_servicio');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_servicio_diagnostico` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_servicio_diagnostico');
    }
};