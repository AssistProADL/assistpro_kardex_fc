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
        if (!Schema::hasTable('t_historicoguias')) {
            Schema::create('t_historicoguias', function (Blueprint $table) {
                $table->string('Guia');
                $table->string('Cve_Evento');
                $table->integer('Nivel_Hist');
                $table->timestamp('Fecha')->nullable();
                $table->string('Des_Evento')->nullable();
                $table->string('Lugar_Evento')->nullable();
                $table->string('Cve_Excepcion')->nullable();
                $table->string('Des_Excepcion')->nullable();
                $table->string('Detalles_Excepcion')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_historicoguias` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_historicoguias');
    }
};