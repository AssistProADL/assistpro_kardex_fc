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
        if (!Schema::hasTable('th_inventario')) {
            Schema::create('th_inventario', function (Blueprint $table) {
                $table->integer('ID_Inventario');
                $table->timestamp('Fecha')->nullable();
                $table->string('Nombre')->nullable();
                $table->string('Status')->nullable();
                $table->integer('Activo')->nullable();
                $table->string('cve_almacen')->nullable();
                $table->string('cve_zona')->nullable();
                $table->integer('Inv_Inicial')->nullable();
                $table->timestamps(); // created_at y updated_at
                $table->index(['Activo', 'Fecha'], 'ix_thinv_activo_fecha');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `th_inventario` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('th_inventario');
    }
};