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
        if (!Schema::hasTable('tipo_transporte')) {
            Schema::create('tipo_transporte', function (Blueprint $table) {
                $table->integer('id');
                $table->string('clave_ttransporte')->nullable();
                $table->string('alto')->nullable();
                $table->string('fondo')->nullable();
                $table->string('ancho')->nullable();
                $table->integer('capacidad_carga')->nullable();
                $table->string('desc_ttransporte')->nullable();
                $table->string('imagen')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `tipo_transporte` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_transporte');
    }
};