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
        if (!Schema::hasTable('c_proveedores')) {
            Schema::create('c_proveedores', function (Blueprint $table) {
                $table->integer('ID_Proveedor');
                $table->string('Empresa')->nullable();
                $table->string('Nombre')->nullable();
                $table->string('RUT')->nullable();
                $table->string('direccion')->nullable();
                $table->string('cve_dane')->nullable();
                $table->integer('ID_Externo')->nullable();
                $table->integer('Activo')->nullable();
                $table->string('cve_proveedor')->nullable();
                $table->string('colonia')->nullable();
                $table->string('ciudad')->nullable();
                $table->string('estado')->nullable();
                $table->string('pais')->nullable();
                $table->string('telefono1')->nullable();
                $table->string('telefono2')->nullable();
                $table->integer('es_cliente')->nullable();
                $table->string('longitud')->nullable();
                $table->string('latitud')->nullable();
                $table->integer('es_transportista')->nullable();
                $table->boolean('envio_correo_automatico')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_proveedores` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_proveedores');
    }
};