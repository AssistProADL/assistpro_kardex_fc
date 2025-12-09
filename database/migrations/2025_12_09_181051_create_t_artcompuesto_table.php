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
        if (!Schema::hasTable('t_artcompuesto')) {
            Schema::create('t_artcompuesto', function (Blueprint $table) {
                $table->string('Cve_Articulo');
                $table->string('Cve_ArtComponente');
                $table->string('Cantidad');
                $table->string('Cantidad_Producida')->nullable();
                $table->string('Status')->nullable();
                $table->integer('Activo')->nullable();
                $table->string('cve_umed')->nullable();
                $table->integer('Etapa')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_artcompuesto` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_artcompuesto');
    }
};