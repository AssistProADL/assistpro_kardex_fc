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
        if (!Schema::hasTable('ts_ubicxart')) {
            Schema::create('ts_ubicxart', function (Blueprint $table) {
                $table->string('cve_articulo');
                $table->integer('idy_ubica');
                $table->integer('CapacidadMinima')->nullable();
                $table->integer('CapacidadMaxima')->nullable();
                $table->integer('Activo')->nullable();
                $table->string('folio')->nullable();
                $table->string('caja_pieza')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `ts_ubicxart` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ts_ubicxart');
    }
};