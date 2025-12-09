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
        if (!Schema::hasTable('motivos_devolucion')) {
            Schema::create('motivos_devolucion', function (Blueprint $table) {
                $table->integer('MOT_ID');
                $table->string('MOT_DESC')->nullable();
                $table->integer('Activo')->nullable();
                $table->string('Clave_motivo')->nullable();
                $table->integer('id_almacen')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `motivos_devolucion` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('motivos_devolucion');
    }
};