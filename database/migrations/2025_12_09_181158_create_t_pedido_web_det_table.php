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
        if (!Schema::hasTable('t_pedido_web_det')) {
            Schema::create('t_pedido_web_det', function (Blueprint $table) {
                $table->id();
                $table->integer('pedido_id');
                $table->integer('articulo_id');
                $table->string('cve_articulo')->nullable();
                $table->string('des_articulo')->nullable();
                $table->decimal('cantidad')->default('0.0000');
                $table->decimal('precio_unit')->default('0.0000');
                $table->decimal('total_renglon')->default('0.0000');
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
                $table->index('pedido_id', 'idx_pedido');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_pedido_web_det` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_pedido_web_det');
    }
};