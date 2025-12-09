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
        if (!Schema::hasTable('c_servicio')) {
            Schema::create('c_servicio', function (Blueprint $table) {
                $table->id();
                $table->string('clave');
                $table->string('descripcion');
                $table->boolean('activo')->default('1');
                $table->boolean('requiere_refacciones')->default('1');
                $table->boolean('control_fotos')->default('1');
                $table->timestamp('created_at')->default('CURRENT_TIMESTAMP');
                $table->string('created_by');
                $table->timestamp('updated_at')->nullable();
                $table->string('updated_by')->nullable();
                $table->unique('clave', 'clave');
                $table->index('activo', 'ix_c_servicio_activo');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_servicio` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_servicio');
    }
};