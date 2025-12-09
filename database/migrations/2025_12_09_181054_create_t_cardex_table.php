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
        if (!Schema::hasTable('t_cardex')) {
            Schema::create('t_cardex', function (Blueprint $table) {
                $table->id();
                $table->string('cve_articulo')->nullable();
                $table->string('cve_lote')->nullable();
                $table->timestamp('fecha')->nullable();
                $table->string('origen')->nullable();
                $table->string('destino')->nullable();
                $table->decimal('cantidad')->nullable();
                $table->decimal('ajuste')->nullable();
                $table->decimal('stockinicial')->nullable();
                $table->integer('id_TipoMovimiento')->nullable();
                $table->string('cve_usuario')->nullable();
                $table->string('Cve_Almac')->nullable();
                $table->string('Cve_Almac_Origen')->nullable();
                $table->string('Cve_Almac_Destino')->nullable();
                $table->integer('Activo')->nullable();
                $table->date('Fec_Ingreso')->nullable();
                $table->integer('Id_Motivo')->nullable();
                $table->integer('ID_Proveedor_Dueno')->nullable();
                $table->string('Referencia')->nullable();
                $table->string('contenedor_clave')->nullable();
                $table->string('contenedor_lp')->nullable();
                $table->string('pallet_clave')->nullable();
                $table->string('pallet_lp')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_cardex` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_cardex');
    }
};