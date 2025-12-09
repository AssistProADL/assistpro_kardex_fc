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
        if (!Schema::hasTable('detalleld')) {
            Schema::create('detalleld', function (Blueprint $table) {
                $table->integer('id');
                $table->integer('ListaId')->nullable();
                $table->string('Articulo')->nullable();
                $table->decimal('Factor')->nullable();
                $table->decimal('FactorMax')->nullable();
                $table->decimal('Minimo')->nullable();
                $table->decimal('Maximo')->nullable();
                $table->string('Tipo')->nullable();
                $table->integer('Cve_Almac');
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `detalleld` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalleld');
    }
};