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
        if (!Schema::hasTable('c_pedido_fuente')) {
            Schema::create('c_pedido_fuente', function (Blueprint $table) {
                $table->id();
                $table->string('clave');
                $table->string('descripcion');
                $table->string('canal')->nullable();
                $table->integer('empresa_id')->nullable();
                $table->boolean('activo')->default('1');
                $table->timestamp('creado_en')->default('CURRENT_TIMESTAMP');
                $table->string('creado_por')->nullable();
                $table->timestamps(); // created_at y updated_at
                $table->unique('clave', 'uk_c_pedido_fuente_clave');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_pedido_fuente` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_pedido_fuente');
    }
};