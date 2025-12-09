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
        if (!Schema::hasTable('t_ubicacion_reabastecer')) {
            Schema::create('t_ubicacion_reabastecer', function (Blueprint $table) {
                $table->string('cve_articulo');
                $table->string('usuario');
                $table->integer('idy_ubica');
                $table->integer('faltantes')->nullable();
                $table->integer('cve_almac');
                $table->string('Cve_Lote');
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_ubicacion_reabastecer` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_ubicacion_reabastecer');
    }
};