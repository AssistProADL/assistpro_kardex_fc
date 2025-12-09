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
        if (!Schema::hasTable('t_mov_bajas')) {
            Schema::create('t_mov_bajas', function (Blueprint $table) {
                $table->integer('id');
                $table->integer('idy_ubica')->nullable();
                $table->string('cve_articulo')->nullable();
                $table->string('cve_lote')->nullable();
                $table->integer('PiezasXCaja')->nullable();
                $table->integer('cve_almac')->nullable();
                $table->integer('tomadas')->nullable();
                $table->timestamp('fecha')->nullable();
                $table->string('cve_usuario')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_mov_bajas` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_mov_bajas');
    }
};