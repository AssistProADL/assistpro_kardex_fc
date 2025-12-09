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
        if (!Schema::hasTable('c_almacenp')) {
            Schema::create('c_almacenp', function (Blueprint $table) {
                $table->id();
                $table->string('clave')->nullable();
                $table->string('nombre')->nullable();
                $table->string('rut')->nullable();
                $table->integer('codigopostal')->nullable();
                $table->string('direccion')->nullable();
                $table->string('telefono')->nullable();
                $table->string('contacto')->nullable();
                $table->string('correo')->nullable();
                $table->string('comentarios')->nullable();
                $table->integer('Activo')->nullable();
                $table->string('distrito')->nullable();
                $table->integer('cve_talmacen');
                $table->integer('No_Licencias')->nullable();
                $table->integer('cve_cia')->nullable();
                $table->string('BL')->nullable();
                $table->boolean('BL_Pasillo')->nullable();
                $table->boolean('BL_Rack')->nullable();
                $table->boolean('BL_Nivel')->nullable();
                $table->boolean('BL_Seccion')->nullable();
                $table->boolean('BL_Posicion')->nullable();
                $table->decimal('longitud')->nullable();
                $table->decimal('latitud')->nullable();
                $table->integer('interno')->nullable();
                $table->string('tipolp_traslado')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_almacenp` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_almacenp');
    }
};