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
        if (!Schema::hasTable('detallelp')) {
            Schema::create('detallelp', function (Blueprint $table) {
                $table->id();
                $table->integer('ListaId')->nullable();
                $table->string('Cve_Articulo')->nullable();
                $table->string('PrecioMin')->nullable();
                $table->string('PrecioMax')->nullable();
                $table->integer('Cve_Almac')->nullable();
                $table->string('ComisionPor')->nullable();
                $table->string('ComisionMon')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
                $table->index('ListaId', 'idx_detallelp_lista');
                $table->index('Cve_Articulo', 'idx_detallelp_art');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `detallelp` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detallelp');
    }
};