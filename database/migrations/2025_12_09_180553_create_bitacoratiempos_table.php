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
        if (!Schema::hasTable('bitacoratiempos')) {
            Schema::create('bitacoratiempos', function (Blueprint $table) {
                $table->integer('Id');
                $table->string('Codigo')->nullable();
                $table->string('Descripcion')->nullable();
                $table->timestamp('HI')->nullable();
                $table->timestamp('HF')->nullable();
                $table->string('HT')->nullable();
                $table->string('TS')->nullable();
                $table->boolean('Visita')->nullable();
                $table->boolean('Programado')->nullable();
                $table->integer('DiaO')->nullable();
                $table->integer('RutaId')->nullable();
                $table->boolean('Cerrado')->nullable();
                $table->integer('IdV')->nullable();
                $table->string('Tip')->nullable();
                $table->string('latitude')->nullable();
                $table->string('longitude')->nullable();
                $table->boolean('pila')->nullable();
                $table->string('IdEmpresa')->nullable();
                $table->integer('IdVendedor')->nullable();
                $table->integer('Id_Ayudante1')->nullable();
                $table->integer('Id_Ayudante2')->nullable();
                $table->integer('IdVehiculo')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `bitacoratiempos` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitacoratiempos');
    }
};