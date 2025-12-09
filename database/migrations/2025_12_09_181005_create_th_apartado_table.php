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
        if (!Schema::hasTable('th_apartado')) {
            Schema::create('th_apartado', function (Blueprint $table) {
                $table->integer('ID_Apartado');
                $table->string('Cve_Clte')->nullable();
                $table->string('titulo')->nullable();
                $table->string('comentarios')->nullable();
                $table->timestamp('fechaApartado')->nullable();
                $table->timestamp('fechaLiveracion')->nullable();
                $table->string('status')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `th_apartado` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('th_apartado');
    }
};