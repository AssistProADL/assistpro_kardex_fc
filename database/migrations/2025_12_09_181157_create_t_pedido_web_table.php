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
        if (!Schema::hasTable('t_pedido_web')) {
            Schema::create('t_pedido_web', function (Blueprint $table) {
                $table->id();
                $table->timestamp('fecha');
                $table->integer('cliente_id')->nullable();
                $table->string('usuario')->nullable();
                $table->decimal('total')->default('0.0000');
                $table->string('estatus')->default('CAPTURADO');
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_pedido_web` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_pedido_web');
    }
};