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
        if (!Schema::hasTable('detalledevol')) {
            Schema::create('detalledevol', function (Blueprint $table) {
                $table->integer('Id');
                $table->string('SKU')->nullable();
                $table->decimal('Pza')->nullable();
                $table->decimal('KG')->nullable();
                $table->decimal('Precio')->nullable();
                $table->decimal('Importe')->nullable();
                $table->string('EDO')->nullable();
                $table->string('Motivo')->nullable();
                $table->decimal('IVA')->nullable();
                $table->decimal('IEPS')->nullable();
                $table->integer('Devol')->nullable();
                $table->integer('Docto')->nullable();
                $table->integer('RutaId')->nullable();
                $table->integer('Tipo')->nullable();
                $table->string('IdEmpresa')->nullable();
                $table->string('UrlImagen')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `detalledevol` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalledevol');
    }
};