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
        if (!Schema::hasTable('c_charolas')) {
            Schema::create('c_charolas', function (Blueprint $table) {
                $table->integer('IDContenedor');
                $table->integer('cve_almac');
                $table->string('Clave_Contenedor');
                $table->string('descripcion')->nullable();
                $table->boolean('Permanente')->nullable();
                $table->string('Pedido')->nullable();
                $table->integer('sufijo')->nullable();
                $table->string('tipo')->nullable();
                $table->integer('Activo')->nullable();
                $table->integer('alto')->nullable();
                $table->integer('ancho')->nullable();
                $table->integer('fondo')->nullable();
                $table->decimal('peso')->nullable();
                $table->decimal('pesomax')->nullable();
                $table->decimal('capavol')->nullable();
                $table->decimal('Costo')->nullable();
                $table->string('CveLP')->nullable();
                $table->integer('TipoGen')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_charolas` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_charolas');
    }
};