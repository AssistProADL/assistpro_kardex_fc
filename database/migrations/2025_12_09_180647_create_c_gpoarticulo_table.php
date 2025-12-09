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
        if (!Schema::hasTable('c_gpoarticulo')) {
            Schema::create('c_gpoarticulo', function (Blueprint $table) {
                $table->integer('id');
                $table->string('cve_gpoart')->nullable();
                $table->string('des_gpoart')->nullable();
                $table->string('por_depcont')->nullable();
                $table->string('por_depfical')->nullable();
                $table->integer('Activo')->nullable();
                $table->integer('id_almacen')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_gpoarticulo` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_gpoarticulo');
    }
};