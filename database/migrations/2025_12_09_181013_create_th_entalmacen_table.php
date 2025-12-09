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
        if (!Schema::hasTable('th_entalmacen')) {
            Schema::create('th_entalmacen', function (Blueprint $table) {
                $table->integer('Fol_Folio');
                $table->string('Cve_Almac')->nullable();
                $table->timestamp('Fec_Entrada')->nullable();
                $table->string('Fol_OEP')->nullable();
                $table->string('Cve_Usuario')->nullable();
                $table->integer('Cve_Proveedor');
                $table->string('STATUS')->nullable();
                $table->string('Cve_Autorizado')->nullable();
                $table->string('tipo');
                $table->string('BanCrossD')->nullable();
                $table->integer('id_ocompra')->nullable();
                $table->string('placas')->nullable();
                $table->date('Fec_Factura_Prov')->nullable();
                $table->string('bufer')->nullable();
                $table->timestamp('HoraInicio')->nullable();
                $table->string('ID_Protocolo')->nullable();
                $table->integer('Consec_protocolo')->nullable();
                $table->string('cve_ubicacion')->nullable();
                $table->timestamp('HoraFin')->nullable();
                $table->string('Fact_Prov')->nullable();
                $table->decimal('TipoCambioSAP')->nullable()->default('1.00000');
                $table->integer('Id_moneda')->nullable();
                $table->string('Proveedor')->nullable();
                $table->string('Proyecto')->nullable();
                $table->string('Pedimento_Well')->nullable();
                $table->string('Referencia_Well')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
                $table->index(['Fol_Folio', 'Cve_Almac', 'Cve_Proveedor'], 'Idx_EntAlmacen');
                $table->index('Cve_Proveedor', 'Cve_Proveedor');
                $table->index('STATUS', 'status');
                $table->unique('Fol_Folio', 'Fol_Folio');
                $table->index('Id_moneda', 'PK_th_entalmacen_c_moneda');
                $table->index('Cve_Almac', 'Cve_Almac');
                $table->primary(['Fol_Folio', 'tipo']);
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `th_entalmacen` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('th_entalmacen');
    }
};