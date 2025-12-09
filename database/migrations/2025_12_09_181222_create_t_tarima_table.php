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
        if (!Schema::hasTable('t_tarima')) {
            Schema::create('t_tarima', function (Blueprint $table) {
                $table->integer('Id');
                $table->integer('ntarima');
                $table->string('Fol_Folio')->nullable();
                $table->integer('Sufijo');
                $table->string('cve_articulo')->nullable();
                $table->string('lote')->nullable();
                $table->string('cantidad')->nullable();
                $table->string('Num_Empacados')->nullable();
                $table->integer('Caja_ref')->nullable();
                $table->string('Ban_Embarcado')->nullable();
                $table->boolean('Abierta')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_tarima` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_tarima');
    }
};