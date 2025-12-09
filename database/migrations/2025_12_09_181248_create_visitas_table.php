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
        if (!Schema::hasTable('visitas')) {
            Schema::create('visitas', function (Blueprint $table) {
                $table->integer('Id');
                $table->string('CodCliente')->nullable();
                $table->integer('DiaO')->nullable();
                $table->timestamp('FechaI')->nullable();
                $table->boolean('EnSecuencia')->nullable();
                $table->timestamp('FechaF')->nullable();
                $table->decimal('Venta')->nullable();
                $table->decimal('Pedido')->nullable();
                $table->decimal('Devolucion')->nullable();
                $table->decimal('Cobranza')->nullable();
                $table->decimal('IdCe')->nullable();
                $table->integer('Cve_Ruta')->nullable();
                $table->string('IdEmpresa')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `visitas` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitas');
    }
};