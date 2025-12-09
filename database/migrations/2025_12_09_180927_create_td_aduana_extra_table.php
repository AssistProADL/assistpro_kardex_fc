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
        if (!Schema::hasTable('td_aduana_extra')) {
            Schema::create('td_aduana_extra', function (Blueprint $table) {
                $table->integer('num_orden');
                $table->string('Cve_Articulo')->nullable();
                $table->string('ParteM3')->nullable();
                $table->string('Parte')->nullable();
                $table->integer('Fraccion');
                $table->integer('Nico')->nullable();
                $table->string('Des_AA')->nullable();
                $table->decimal('CantidadFactura')->nullable();
                $table->integer('UMComercializacion')->nullable();
                $table->decimal('CantidadTarifa')->nullable();
                $table->integer('UMTarifa')->nullable();
                $table->decimal('PrecioUnitario')->nullable();
                $table->decimal('ValorFactura')->nullable();
                $table->string('UMCOVE')->nullable();
                $table->decimal('CantidadCOVE')->nullable();
                $table->decimal('ValorMercanciaCOVE')->nullable();
                $table->decimal('PrecioUnitarioCOVE')->nullable();
                $table->string('DescripcionFacturaCOVE')->nullable();
                $table->string('PlacasTranposte')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `td_aduana_extra` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_aduana_extra');
    }
};