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
        if (!Schema::hasTable('t_invtarimaciclico')) {
            Schema::create('t_invtarimaciclico', function (Blueprint $table) {
                $table->integer('ID_PLAN');
                $table->integer('NConteo');
                $table->integer('idy_ubica');
                $table->string('cve_Lote');
                $table->integer('ntarima');
                $table->string('cve_articulo');
                $table->decimal('Teorico')->nullable();
                $table->string('existencia')->nullable();
                $table->string('cve_usuario')->nullable();
                $table->timestamp('fecha')->nullable();
                $table->integer('Activo')->nullable();
                $table->integer('Id_Proveedor');
                $table->string('Abierto')->nullable();
                $table->boolean('Cuarentena')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_invtarimaciclico` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_invtarimaciclico');
    }
};