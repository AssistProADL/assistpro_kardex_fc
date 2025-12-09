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
        if (!Schema::hasTable('t_vendedores')) {
            Schema::create('t_vendedores', function (Blueprint $table) {
                $table->integer('Id_Vendedor');
                $table->string('Nombre')->nullable();
                $table->integer('Activo')->nullable();
                $table->string('CalleNumero')->nullable();
                $table->string('Colonia')->nullable();
                $table->string('Ciudad')->nullable();
                $table->string('Estado')->nullable();
                $table->string('Pais')->nullable();
                $table->string('CodigoPostal')->nullable();
                $table->string('Cve_Vendedor');
                $table->boolean('Ban_Ayudante');
                $table->string('Psswd_EDA')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_vendedores` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_vendedores');
    }
};