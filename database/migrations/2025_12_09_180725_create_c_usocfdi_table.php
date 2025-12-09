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
        if (!Schema::hasTable('c_usocfdi')) {
            Schema::create('c_usocfdi', function (Blueprint $table) {
                $table->integer('Id_CFDI');
                $table->string('Cve_CFDI');
                $table->string('Des_CFDI');
                $table->string('Persona_Fisica');
                $table->string('Persona_Moral');
                $table->integer('Activo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_usocfdi` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_usocfdi');
    }
};