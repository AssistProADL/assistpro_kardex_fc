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
        if (!Schema::hasTable('t_log_operaciones')) {
            Schema::create('t_log_operaciones', function (Blueprint $table) {
                $table->integer('empresa_id');
                $table->text('modulo')->nullable();
                $table->text('usuario')->nullable();
                $table->text('fecha')->nullable();
                $table->text('operacion')->nullable();
                $table->text('dispositivo')->nullable();
                $table->text('observaciones')->nullable();
                $table->timestamp('fecha_dt')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
                $table->index('operacion', 'idx_operacion');
                $table->index('fecha_dt', 'idx_fecha_dt');
                $table->index('usuario', 'idx_usuario');
                $table->index('modulo', 'idx_modulo');
                $table->index('empresa_id', 'idx_empresa');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_log_operaciones` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_log_operaciones');
    }
};