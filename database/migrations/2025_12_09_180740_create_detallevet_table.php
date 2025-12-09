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
        if (!Schema::hasTable('detallevet')) {
            Schema::create('detallevet', function (Blueprint $table) {
                $table->integer('ID');
                $table->string('Articulo')->nullable();
                $table->string('Descripcion')->nullable();
                $table->decimal('Precio')->nullable();
                $table->decimal('Pza')->nullable();
                $table->decimal('Kg')->nullable();
                $table->string('DescPorc')->nullable();
                $table->decimal('DescMon')->nullable();
                $table->integer('Tipo')->nullable();
                $table->string('Docto')->nullable();
                $table->decimal('Importe')->nullable();
                $table->decimal('IVA')->nullable();
                $table->decimal('IEPS')->nullable();
                $table->integer('RutaId')->nullable();
                $table->string('IdEmpresa')->nullable();
                $table->decimal('Comisiones')->nullable();
                $table->decimal('Utilidad')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `detallevet` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detallevet');
    }
};