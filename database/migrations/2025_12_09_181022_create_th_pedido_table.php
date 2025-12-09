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
        if (!Schema::hasTable('th_pedido')) {
            Schema::create('th_pedido', function (Blueprint $table) {
                $table->string('Fol_folio')->nullable();
                $table->date('Fec_Pedido')->nullable();
                $table->string('Cve_clte')->nullable();
                $table->string('status')->nullable();
                $table->date('Fec_Entrega')->nullable();
                $table->string('cve_Vendedor')->nullable();
                $table->integer('Num_Meses')->nullable();
                $table->integer('fuente_id')->nullable();
                $table->string('fuente_detalle')->nullable();
                $table->text('Observaciones')->nullable();
                $table->integer('statusaurora')->nullable();
                $table->integer('ID_Tipoprioridad')->nullable();
                $table->timestamp('Fec_Entrada')->nullable();
                $table->string('TipoPedido')->nullable();
                $table->string('ruta')->nullable();
                $table->boolean('bloqueado')->nullable();
                $table->integer('DiaO')->nullable();
                $table->string('TipoDoc')->nullable();
                $table->string('rango_hora')->nullable();
                $table->string('cve_almac')->nullable();
                $table->string('destinatario')->nullable();
                $table->integer('Id_Proveedor')->nullable();
                $table->string('cve_ubicacion')->nullable();
                $table->string('Pick_Num')->nullable();
                $table->string('Cve_Usuario')->nullable();
                $table->string('Ship_Num')->nullable();
                $table->string('BanEmpaque')->nullable();
                $table->string('Cve_CteProv')->nullable();
                $table->integer('id_pedido');
                $table->integer('Activo')->nullable();
                $table->string('foto1')->nullable();
                $table->string('foto2')->nullable();
                $table->string('foto3')->nullable();
                $table->string('foto4')->nullable();
                $table->integer('Forma_Pago')->nullable();
                $table->string('tipo_venta')->nullable();
                $table->string('tipo_negociacion')->nullable();
                $table->string('Almac_Ori')->nullable();
                $table->string('Docto_Ref')->nullable();
                $table->boolean('Enviado')->nullable();
                $table->string('Ref_Wel')->nullable();
                $table->string('Ref_Imp')->nullable();
                $table->string('Pedimento')->nullable();
                $table->string('Factura_Vta')->nullable();
                $table->string('Ped_Imp')->nullable();
                $table->timestamp('Fec_Aprobado')->nullable();
                $table->decimal('Tot_Factura')->nullable();
                $table->integer('orden_etapa')->nullable();
                $table->integer('contacto_id')->nullable();
                $table->string('tipo_asignacion')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `th_pedido` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('th_pedido');
    }
};