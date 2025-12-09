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
        if (!Schema::hasTable('productoenvase')) {
            Schema::create('productoenvase', function (Blueprint $table) {
                $table->integer('Id');
                $table->string('Producto')->nullable();
                $table->string('Envase')->nullable();
                $table->integer('Cant_Base')->nullable();
                $table->integer('Cant_Eq')->nullable();
                $table->string('Status')->nullable();
                $table->string('IdEmpresa')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `productoenvase` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productoenvase');
    }
};