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
        if (!Schema::hasTable('c_tipocaja')) {
            Schema::create('c_tipocaja', function (Blueprint $table) {
                $table->integer('id_tipocaja');
                $table->string('clave')->nullable();
                $table->string('descripcion')->nullable();
                $table->decimal('largo')->nullable();
                $table->decimal('alto')->nullable();
                $table->decimal('ancho')->nullable();
                $table->integer('Activo')->nullable();
                $table->string('Packing');
                $table->string('peso');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_tipocaja` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_tipocaja');
    }
};