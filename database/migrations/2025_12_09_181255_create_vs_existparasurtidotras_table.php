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
        if (!Schema::hasTable('vs_existparasurtidotras')) {
            Schema::create('vs_existparasurtidotras', function (Blueprint $table) {
                $table->integer('Cve_Almac');
                $table->string('Idy_Ubica')->nullable();
                $table->string('cve_articulo');
                $table->string('cve_lote');
                $table->string('Existencia')->nullable();
                $table->string('BanPTL');
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `vs_existparasurtidotras` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vs_existparasurtidotras');
    }
};