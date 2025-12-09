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
        if (!Schema::hasTable('devenvases')) {
            Schema::create('devenvases', function (Blueprint $table) {
                $table->integer('ID');
                $table->integer('RutaId')->nullable();
                $table->integer('DiaO')->nullable();
                $table->integer('CodCli')->nullable();
                $table->string('Docto');
                $table->string('Articulo')->nullable();
                $table->integer('Cantidad')->nullable();
                $table->integer('Devuelto')->nullable();
                $table->string('Tipo')->nullable();
                $table->string('Envase')->nullable();
                $table->string('Status')->nullable();
                $table->string('IdEmpresa')->nullable();
                $table->integer('SaldoActual')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `devenvases` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devenvases');
    }
};