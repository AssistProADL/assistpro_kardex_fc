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
        if (!Schema::hasTable('tubicacionesretencion')) {
            Schema::create('tubicacionesretencion', function (Blueprint $table) {
                $table->integer('id');
                $table->string('cve_ubicacion');
                $table->integer('cve_almacp')->nullable();
                $table->integer('Activo')->nullable();
                $table->string('desc_ubicacion')->nullable();
                $table->string('B_Devolucion')->nullable();
                $table->string('AreaStagging')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `tubicacionesretencion` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tubicacionesretencion');
    }
};