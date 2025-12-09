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
        if (!Schema::hasTable('cat_estados')) {
            Schema::create('cat_estados', function (Blueprint $table) {
                $table->string('ESTADO');
                $table->string('DESCRIPCION')->nullable();
                $table->integer('DURACION')->nullable();
                $table->integer('ORDEN')->nullable();
                $table->integer('COLORR')->nullable();
                $table->integer('COLORG')->nullable();
                $table->integer('COLORB')->nullable();
                $table->string('STATUS')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `cat_estados` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cat_estados');
    }
};