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
        if (!Schema::hasTable('c_secuencia_surtido')) {
            Schema::create('c_secuencia_surtido', function (Blueprint $table) {
                $table->id();
                $table->string('clave_sec');
                $table->string('nombre');
                $table->string('tipo_sec');
                $table->string('proceso');
                $table->integer('almacen_id');
                $table->boolean('activo')->default('1');
                $table->timestamps(); // created_at y updated_at
                $table->unique('clave_sec', 'uk_clave_sec');
                $table->index('almacen_id', 'idx_sec_almacen');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_secuencia_surtido` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_secuencia_surtido');
    }
};