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
        if (!Schema::hasTable('stg_td_entalmacen')) {
            Schema::create('stg_td_entalmacen', function (Blueprint $table) {
                $table->integer('fol_folio');
                $table->string('cve_articulo');
                $table->string('cve_lote');
                $table->decimal('CantidadPedida')->nullable();
                $table->decimal('CantidadRecibida')->nullable();
                $table->decimal('CantidadDisponible')->nullable();
                $table->decimal('CantidadUbicada')->nullable();
                $table->string('status')->nullable();
                $table->string('numero_serie')->nullable();
                $table->integer('id');
                $table->string('cve_usuario')->nullable();
                $table->string('cve_ubicacion')->nullable();
                $table->timestamp('fecha_inicio')->nullable();
                $table->timestamp('fecha_fin')->nullable();
                $table->integer('tipo_entrada')->nullable();
                $table->string('costoUnitario')->nullable();
                $table->integer('num_orden')->nullable();
                $table->decimal('IVA')->nullable();
                $table->string('num_pedimento')->nullable();
                $table->timestamp('fecha_pedimento')->nullable();
                $table->string('factura_articulo')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
                $table->index('fol_folio', 'fx_th_entalmacen_td_entalmacen_idx');
                $table->index(['fol_folio', 'cve_articulo', 'cve_lote'], 'fx_td_entalmacen_td_entalmacencar');
                $table->primary(['id', 'fol_folio', 'cve_articulo', 'cve_lote']);
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `stg_td_entalmacen` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stg_td_entalmacen');
    }
};