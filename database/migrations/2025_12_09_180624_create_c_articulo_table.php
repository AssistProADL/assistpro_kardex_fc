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
        if (!Schema::hasTable('c_articulo')) {
            Schema::create('c_articulo', function (Blueprint $table) {
                $table->string('cve_articulo')->nullable();
                $table->string('des_articulo')->nullable();
                $table->text('des_detallada')->nullable();
                $table->integer('cve_umed')->nullable();
                $table->integer('cve_ssgpo')->nullable();
                $table->timestamp('fec_altaart')->nullable();
                $table->decimal('imp_costo')->nullable();
                $table->string('des_tipo')->nullable();
                $table->integer('comp_cveumed')->nullable();
                $table->integer('empq_cveumed')->nullable();
                $table->string('num_multiplo')->nullable();
                $table->string('des_observ')->nullable();
                $table->string('mav_almacenable')->nullable();
                $table->integer('cve_moneda')->nullable();
                $table->integer('cve_almac');
                $table->string('mav_cveubica')->nullable();
                $table->string('mav_delinea')->nullable();
                $table->string('mav_obsoleto')->nullable();
                $table->decimal('mav_pctiva')->nullable();
                $table->decimal('IEPS')->nullable();
                $table->decimal('PrecioVenta')->nullable();
                $table->integer('cve_tipcaja')->nullable();
                $table->boolean('ban_condic')->nullable();
                $table->decimal('num_volxpal')->nullable();
                $table->string('cve_codprov')->nullable();
                $table->string('remplazo')->nullable();
                $table->integer('ID_Proveedor')->nullable();
                $table->string('peso')->nullable();
                $table->integer('num_multiploch')->nullable();
                $table->string('barras2')->nullable();
                $table->string('Caduca')->nullable();
                $table->string('Compuesto')->nullable();
                $table->integer('Max_Cajas')->nullable();
                $table->integer('Activo')->nullable();
                $table->integer('id');
                $table->string('barras3')->nullable();
                $table->integer('cajas_palet')->nullable();
                $table->string('control_lotes')->nullable();
                $table->string('control_numero_series')->nullable();
                $table->string('control_garantia')->nullable()->default('N');
                $table->string('tipo_garantia')->nullable()->default('MESES');
                $table->integer('valor_garantia')->nullable();
                $table->string('control_peso')->nullable();
                $table->string('control_volumen')->nullable();
                $table->string('req_refrigeracion')->nullable();
                $table->string('mat_peligroso')->nullable();
                $table->string('grupo')->nullable();
                $table->string('clasificacion')->nullable();
                $table->string('tipo')->nullable();
                $table->integer('tipo_caja')->nullable();
                $table->decimal('alto')->nullable();
                $table->decimal('fondo')->nullable();
                $table->decimal('ancho')->nullable();
                $table->decimal('costo')->nullable();
                $table->string('tipo_producto')->nullable();
                $table->integer('umas')->nullable();
                $table->integer('unidadMedida')->nullable();
                $table->string('costoPromedio')->nullable();
                $table->string('Cve_SAP')->nullable();
                $table->string('Ban_Envase')->nullable();
                $table->string('Usa_Envase')->nullable();
                $table->string('Tipo_Envase')->nullable();
                $table->string('control_abc')->nullable();
                $table->string('cve_alt')->nullable();
                $table->boolean('ecommerce_activo')->nullable()->default('0');
                $table->string('ecommerce_categoria')->nullable();
                $table->string('ecommerce_subcategoria')->nullable();
                $table->string('ecommerce_img_principal')->nullable();
                $table->text('ecommerce_img_galeria')->nullable();
                $table->string('ecommerce_tags')->nullable();
                $table->boolean('ecommerce_destacado')->nullable()->default('0');
                $table->timestamps(); // created_at y updated_at
                $table->index('control_garantia', 'ix_articulo_garantia');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_articulo` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_articulo');
    }
};