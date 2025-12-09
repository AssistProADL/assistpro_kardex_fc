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
        if (!Schema::hasTable('s_impresoras')) {
            Schema::create('s_impresoras', function (Blueprint $table) {
                $table->id();
                $table->integer('id_almacen');
                $table->string('IP');
                $table->string('TIPO_IMPRESORA')->nullable();
                $table->string('NOMBRE')->nullable();
                $table->string('Marca')->nullable();
                $table->string('Modelo')->nullable();
                $table->integer('Densidad_Imp')->nullable();
                $table->string('TIPO_CONEXION')->nullable();
                $table->integer('PUERTO');
                $table->integer('TiempoEspera')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
                $table->unique(['id_almacen', 'IP'], 'IDX_Almac_IP');
                $table->index('id_almacen', 'IDX_Almacen');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `s_impresoras` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('s_impresoras');
    }
};