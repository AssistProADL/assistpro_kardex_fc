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
        if (!Schema::hasTable('t_indicadores')) {
            Schema::create('t_indicadores', function (Blueprint $table) {
                $table->integer('Anio');
                $table->integer('Mes');
                $table->integer('Dia');
                $table->integer('Facturas Procesadas');
                $table->integer('Notas de Entrega Procesadas');
                $table->decimal('Entregas Locales');
                $table->decimal('Entregas Edo Mex')->nullable();
                $table->decimal('Entregas Foraneas');
                $table->decimal('Pedidos en Transito');
                $table->decimal('Pedidos Atrasados');
                $table->decimal('Pedidos Entregados');
                $table->integer('Errores ESTAFETA')->nullable();
                $table->integer('Errores SCI')->nullable();
                $table->integer('Errores Picking')->nullable();
                $table->decimal('Efectividad Entrega');
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_indicadores` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_indicadores');
    }
};