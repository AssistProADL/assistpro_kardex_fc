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
        if (!Schema::hasTable('v_administracioninventario')) {
            Schema::create('v_administracioninventario', function (Blueprint $table) {
                $table->integer('consecutivo');
                $table->string('fecha_inicio')->nullable();
                $table->string('fecha_final')->nullable();
                $table->string('almacen')->nullable();
                $table->string('zona');
                $table->string('usuario');
                $table->string('status')->nullable();
                $table->string('diferencia')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `v_administracioninventario` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('v_administracioninventario');
    }
};