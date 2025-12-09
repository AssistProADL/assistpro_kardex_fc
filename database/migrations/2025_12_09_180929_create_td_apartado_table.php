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
        if (!Schema::hasTable('td_apartado')) {
            Schema::create('td_apartado', function (Blueprint $table) {
                $table->integer('ID_Apartado');
                $table->string('cve_articulo');
                $table->string('cve_lote');
                $table->integer('idy_ubica');
                $table->integer('cantidad')->nullable();
                $table->string('status')->nullable();
                $table->timestamp('fecha')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `td_apartado` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_apartado');
    }
};