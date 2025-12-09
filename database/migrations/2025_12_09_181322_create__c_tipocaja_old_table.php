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
        if (!Schema::hasTable('_c_tipocaja_old')) {
            Schema::create('_c_tipocaja_old', function (Blueprint $table) {
                $table->integer('cve_tipcaja');
                $table->string('des_tipcaja')->nullable();
                $table->decimal('num_largo')->nullable();
                $table->decimal('num_ancho')->nullable();
                $table->decimal('num_alto')->nullable();
                $table->string('Ban_Picking')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `_c_tipocaja_old` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_c_tipocaja_old');
    }
};