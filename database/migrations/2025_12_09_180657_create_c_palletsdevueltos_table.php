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
        if (!Schema::hasTable('c_palletsdevueltos')) {
            Schema::create('c_palletsdevueltos', function (Blueprint $table) {
                $table->integer('ID');
                $table->string('cve_almac')->nullable();
                $table->string('descripcion')->nullable();
                $table->string('tipo')->nullable();
                $table->string('clave')->nullable();
                $table->string('ClaveLP')->nullable();
                $table->string('desc_almac')->nullable();
                $table->string('statu')->nullable();
                $table->string('pedido')->nullable();
                $table->string('razon')->nullable();
                $table->string('cliente')->nullable();
                $table->string('direccion')->nullable();
                $table->string('destino')->nullable();
                $table->string('fecha')->nullable();
                $table->timestamp('fechadev')->nullable();
                $table->string('dias')->nullable();
                $table->string('bl')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_palletsdevueltos` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_palletsdevueltos');
    }
};