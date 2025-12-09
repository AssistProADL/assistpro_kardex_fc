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
        if (!Schema::hasTable('td_servicio_caso_log')) {
            Schema::create('td_servicio_caso_log', function (Blueprint $table) {
                $table->id();
                $table->integer('servicio_id');
                $table->timestamp('fecha')->default('CURRENT_TIMESTAMP');
                $table->string('usuario');
                $table->string('evento');
                $table->text('detalle')->nullable();
                $table->timestamp('created_at')->default('CURRENT_TIMESTAMP');
                $table->string('created_by');
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->index(['servicio_id', 'fecha'], 'ix_serv_log_servicio_fecha');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `td_servicio_caso_log` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_servicio_caso_log');
    }
};