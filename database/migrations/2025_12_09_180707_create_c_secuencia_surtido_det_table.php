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
        if (!Schema::hasTable('c_secuencia_surtido_det')) {
            Schema::create('c_secuencia_surtido_det', function (Blueprint $table) {
                $table->id();
                $table->integer('sec_id');
                $table->integer('ubicacion_id');
                $table->integer('orden');
                $table->boolean('activo')->default('1');
                $table->timestamps(); // created_at y updated_at
                $table->index('sec_id', 'idx_sec_det_sec');
                $table->index('ubicacion_id', 'idx_sec_det_ubi');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_secuencia_surtido_det` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_secuencia_surtido_det');
    }
};