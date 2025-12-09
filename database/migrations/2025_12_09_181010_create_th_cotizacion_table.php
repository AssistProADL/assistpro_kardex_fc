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
        if (!Schema::hasTable('th_cotizacion')) {
            Schema::create('th_cotizacion', function (Blueprint $table) {
                $table->id();
                $table->integer('empresa_id');
                $table->integer('almacen_id');
                $table->string('folio');
                $table->string('serie')->nullable();
                $table->bigInteger('cliente_id');
                $table->string('fuente')->default('WEB_PROPIA');
                $table->string('fuente_detalle')->nullable();
                $table->bigInteger('contacto_id')->nullable();
                $table->string('crm_oportunidad_id')->nullable();
                $table->timestamp('fecha_cotizacion');
                $table->date('fecha_vigencia')->nullable();
                $table->string('moneda')->default('MXN');
                $table->decimal('tipo_cambio')->nullable()->default('1.000000');
                $table->decimal('subtotal')->default('0.000000');
                $table->decimal('descuento_total')->default('0.000000');
                $table->decimal('impuestos_total')->default('0.000000');
                $table->decimal('total')->default('0.000000');
                $table->string('estatus')->default('BORRADOR');
                $table->text('observaciones')->nullable();
                $table->string('usuario_crea');
                $table->timestamp('fecha_crea')->default('CURRENT_TIMESTAMP');
                $table->string('usuario_mod')->nullable();
                $table->timestamp('fecha_mod')->nullable();
                $table->integer('id_opp')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
                $table->index('id_opp', 'idx_opp');
                $table->index('fecha_cotizacion', 'idx_th_cotizacion_fecha');
                $table->unique(['empresa_id', 'folio'], 'uk_th_cotizacion_folio');
                $table->index('estatus', 'idx_th_cotizacion_estatus');
                $table->index('crm_oportunidad_id', 'idx_th_cotizacion_crm');
                $table->index('cliente_id', 'idx_th_cotizacion_cliente');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `th_cotizacion` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('th_cotizacion');
    }
};