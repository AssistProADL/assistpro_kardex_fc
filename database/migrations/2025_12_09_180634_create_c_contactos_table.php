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
        if (!Schema::hasTable('c_contactos')) {
            Schema::create('c_contactos', function (Blueprint $table) {
                $table->integer('id');
                $table->string('clave')->nullable();
                $table->string('nombre')->nullable();
                $table->string('apellido')->nullable();
                $table->string('correo')->nullable();
                $table->string('telefono1')->nullable();
                $table->string('telefono2')->nullable();
                $table->string('pais')->nullable();
                $table->string('estado')->nullable();
                $table->string('ciudad')->nullable();
                $table->string('direccion')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_contactos` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_contactos');
    }
};