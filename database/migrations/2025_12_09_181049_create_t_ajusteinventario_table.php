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
        if (!Schema::hasTable('t_ajusteinventario')) {
            Schema::create('t_ajusteinventario', function (Blueprint $table) {
                $table->integer('Id');
                $table->integer('Id_Inventario');
                $table->integer('NConteo');
                $table->integer('Idy_Ubica');
                $table->string('Cve_Articulo')->nullable();
                $table->string('Cve_Lote')->nullable();
                $table->decimal('Cant_Ant')->nullable();
                $table->decimal('Cant_Ajuste')->nullable();
                $table->string('Cve_Usuario_Inv')->nullable();
                $table->string('Cve_Usuario_Ajuste')->nullable();
                $table->timestamp('Fecha')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_ajusteinventario` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_ajusteinventario');
    }
};