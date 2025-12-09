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
        if (!Schema::hasTable('t_lista_diferencias')) {
            Schema::create('t_lista_diferencias', function (Blueprint $table) {
                $table->integer('id');
                $table->string('fol_folio')->nullable();
                $table->string('Sufijo')->nullable();
                $table->string('Cve_articulo')->nullable();
                $table->string('LOTE')->nullable();
                $table->integer('PiexasXCaja')->nullable();
                $table->integer('Cantidad')->nullable();
                $table->string('tipo')->nullable();
                $table->string('modo')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_lista_diferencias` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_lista_diferencias');
    }
};