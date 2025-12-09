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
        if (!Schema::hasTable('t_servicio_parte')) {
            Schema::create('t_servicio_parte', function (Blueprint $table) {
                $table->id();
                $table->integer('servicio_id');
                $table->string('cve_articulo');
                $table->decimal('cantidad')->default('0.0000');
                $table->string('tipo_mov')->default('REQUERIDA');
                $table->string('almacen_origen')->nullable();
                $table->string('status_surtido')->default('SOLICITADA');
                $table->string('nota')->nullable();
                $table->timestamp('created_at');
                $table->string('created_by');
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->index('servicio_id', 'idx_servicio');
                $table->index('cve_articulo', 'idx_articulo');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_servicio_parte` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_servicio_parte');
    }
};