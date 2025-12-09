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
        if (!Schema::hasTable('mensajes')) {
            Schema::create('mensajes', function (Blueprint $table) {
                $table->integer('ID');
                $table->string('Clave')->nullable();
                $table->string('EnBaseA')->nullable();
                $table->string('Descripcion')->nullable();
                $table->string('Mensaje')->nullable();
                $table->timestamp('FechaInicio')->nullable();
                $table->timestamp('FechaFinal')->nullable();
                $table->boolean('Estado')->nullable();
                $table->string('IdEmpresa')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `mensajes` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mensajes');
    }
};