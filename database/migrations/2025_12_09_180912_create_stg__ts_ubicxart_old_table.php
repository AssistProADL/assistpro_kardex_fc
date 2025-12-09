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
        if (!Schema::hasTable('stg__ts_ubicxart_old')) {
            Schema::create('stg__ts_ubicxart_old', function (Blueprint $table) {
                $table->string('cve_articulo')->nullable();
                $table->integer('idy_ubica');
                $table->integer('CapacidadMinima')->nullable();
                $table->integer('CapacidadMaxima')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `stg__ts_ubicxart_old` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stg__ts_ubicxart_old');
    }
};