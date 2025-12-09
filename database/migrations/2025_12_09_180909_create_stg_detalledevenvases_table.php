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
        if (!Schema::hasTable('stg_detalledevenvases')) {
            Schema::create('stg_detalledevenvases', function (Blueprint $table) {
                $table->integer('ID');
                $table->string('IdEmpresa');
                $table->integer('RutaId');
                $table->integer('DiaO');
                $table->timestamp('Fecha');
                $table->integer('CodCli');
                $table->string('DoctoRef');
                $table->string('FolioDev');
                $table->string('Envase');
                $table->integer('SaldoAnt')->nullable();
                $table->integer('CantDevuelta')->nullable();
                $table->integer('SaldoActual')->nullable();
                $table->string('Tipo')->nullable();
                $table->string('Status')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `stg_detalledevenvases` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stg_detalledevenvases');
    }
};