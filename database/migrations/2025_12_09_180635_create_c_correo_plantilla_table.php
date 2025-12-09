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
        if (!Schema::hasTable('c_correo_plantilla')) {
            Schema::create('c_correo_plantilla', function (Blueprint $table) {
                $table->id();
                $table->string('codigo');
                $table->string('descripcion');
                $table->string('asunto');
                $table->text('cuerpo_html');
                $table->text('cuerpo_texto')->nullable();
                $table->string('variables_json')->nullable();
                $table->boolean('activo')->default('1');
                $table->timestamp('fecha_creacion')->default('CURRENT_TIMESTAMP');
                $table->timestamp('fecha_actualiza')->nullable();
                $table->timestamps(); // created_at y updated_at
                $table->unique('codigo', 'codigo');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_correo_plantilla` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_correo_plantilla');
    }
};