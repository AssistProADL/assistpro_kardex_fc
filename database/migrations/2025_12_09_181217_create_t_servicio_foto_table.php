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
        if (!Schema::hasTable('t_servicio_foto')) {
            Schema::create('t_servicio_foto', function (Blueprint $table) {
                $table->id();
                $table->integer('servicio_id');
                $table->string('etapa');
                $table->string('ruta');
                $table->string('nota')->nullable();
                $table->timestamp('created_at')->default('CURRENT_TIMESTAMP');
                $table->string('created_by');
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->index(['servicio_id', 'etapa'], 'ix_foto_servicio');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_servicio_foto` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_servicio_foto');
    }
};