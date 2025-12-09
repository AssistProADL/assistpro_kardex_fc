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
        if (!Schema::hasTable('detallepromo')) {
            Schema::create('detallepromo', function (Blueprint $table) {
                $table->integer('Id');
                $table->string('Articulo')->nullable();
                $table->integer('PromoId')->nullable();
                $table->decimal('Cantidad')->nullable();
                $table->boolean('Tipo')->nullable();
                $table->string('TipoProm')->nullable();
                $table->decimal('Monto')->nullable();
                $table->decimal('Volumen')->nullable();
                $table->integer('UniMed')->nullable();
                $table->integer('Cve_Almac')->nullable();
                $table->integer('Nivel')->nullable();
                $table->string('Grupo_Art')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `detallepromo` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detallepromo');
    }
};