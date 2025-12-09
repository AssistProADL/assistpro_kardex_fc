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
        if (!Schema::hasTable('td_cotizacion')) {
            Schema::create('td_cotizacion', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('th_id');
                $table->integer('renglon');
                $table->bigInteger('producto_id');
                $table->string('descripcion');
                $table->integer('unidad_id')->nullable();
                $table->string('unidad_clave')->nullable();
                $table->decimal('cantidad')->default('0.0000');
                $table->decimal('precio_unitario')->default('0.000000');
                $table->decimal('descuento_pct')->default('0.00');
                $table->decimal('descuento_imp')->default('0.000000');
                $table->decimal('subtotal')->default('0.000000');
                $table->decimal('impuesto_pct')->default('0.00');
                $table->decimal('impuesto_imp')->default('0.000000');
                $table->decimal('total_linea')->default('0.000000');
                $table->integer('almacen_id');
                $table->integer('zona_id')->nullable();
                $table->bigInteger('ubicacion_id')->nullable();
                $table->date('fecha_promesa')->nullable();
                $table->string('estatus_linea')->default('ABIERTA');
                $table->decimal('stock_disponible')->nullable();
                $table->decimal('stock_comprometido')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
                $table->index('th_id', 'idx_td_cotizacion_th');
                $table->index('producto_id', 'idx_td_cotizacion_prod');
                $table->index('almacen_id', 'idx_td_cotizacion_almacen');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `td_cotizacion` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_cotizacion');
    }
};