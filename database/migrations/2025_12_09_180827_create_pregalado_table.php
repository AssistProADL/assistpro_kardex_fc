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
        if (!Schema::hasTable('pregalado')) {
            Schema::create('pregalado', function (Blueprint $table) {
                $table->integer('Id');
                $table->string('SKU')->nullable();
                $table->integer('RutaId')->nullable();
                $table->decimal('Pz')->nullable();
                $table->decimal('Kg')->nullable();
                $table->integer('DiaO')->nullable();
                $table->string('Docto')->nullable();
                $table->integer('Cliente');
                $table->integer('Cant')->nullable();
                $table->string('Tipmed')->nullable();
                $table->string('IdEmpresa')->nullable();
                $table->string('SKU_Base')->nullable();
                $table->decimal('Multiplo_Base')->nullable();
                $table->string('UM_Base')->nullable();
                $table->decimal('Multiplo_Regalo')->nullable();
                $table->string('UM_Regalo')->nullable();
                $table->string('Tipo')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `pregalado` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pregalado');
    }
};