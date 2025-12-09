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
        if (!Schema::hasTable('rel_tipocaja_art')) {
            Schema::create('rel_tipocaja_art', function (Blueprint $table) {
                $table->integer('Id');
                $table->string('Cve_Articulo')->nullable();
                $table->integer('Id_TipoCaja')->nullable();
                $table->integer('Num_Multiplo')->nullable();
                $table->string('CB_Caja')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `rel_tipocaja_art` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rel_tipocaja_art');
    }
};