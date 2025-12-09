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
        if (!Schema::hasTable('stg_cuotas')) {
            Schema::create('stg_cuotas', function (Blueprint $table) {
                $table->integer('Id');
                $table->string('Clave')->nullable();
                $table->string('Descripcion')->nullable();
                $table->string('UniMed')->nullable();
                $table->string('Cantidad')->nullable();
                $table->timestamp('FechaI')->nullable();
                $table->timestamp('FechaF')->nullable();
                $table->string('Producto')->nullable();
                $table->boolean('Tipo')->nullable();
                $table->boolean('Activa')->nullable();
                $table->boolean('NivelNum')->nullable();
                $table->string('IdEmpresa')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `stg_cuotas` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stg_cuotas');
    }
};