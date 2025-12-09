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
        if (!Schema::hasTable('colaprocesamiento')) {
            Schema::create('colaprocesamiento', function (Blueprint $table) {
                $table->integer('id');
                $table->integer('idVendedor')->nullable();
                $table->integer('DiaO')->nullable();
                $table->string('idEmpresa')->nullable();
                $table->string('nombreArchivo')->nullable();
                $table->timestamp('Fecha')->nullable();
                $table->string('Procesado')->nullable();
                $table->timestamp('FechaProcesado')->nullable();
                $table->integer('idRuta')->nullable();
                $table->string('rutaOpcional')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `colaprocesamiento` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colaprocesamiento');
    }
};