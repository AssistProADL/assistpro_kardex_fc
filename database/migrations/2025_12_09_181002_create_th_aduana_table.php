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
        if (!Schema::hasTable('th_aduana')) {
            Schema::create('th_aduana', function (Blueprint $table) {
                $table->integer('ID_Aduana');
                $table->integer('num_pedimento')->nullable();
                $table->timestamp('fech_pedimento')->nullable();
                $table->string('aduana')->nullable();
                $table->string('Factura')->nullable();
                $table->timestamp('fech_llegPed')->nullable();
                $table->string('status')->nullable();
                $table->integer('ID_Proveedor');
                $table->string('ID_Protocolo')->nullable();
                $table->integer('Consec_protocolo')->nullable();
                $table->string('cve_usuario')->nullable();
                $table->string('Cve_Almac')->nullable();
                $table->integer('Activo')->nullable()->default('1');
                $table->string('recurso')->nullable();
                $table->string('procedimiento')->nullable();
                $table->integer('AduanaDespacho')->nullable();
                $table->string('dictamen')->nullable();
                $table->string('presupuesto')->nullable();
                $table->string('condicionesDePago')->nullable();
                $table->string('lugarDeEntrega')->nullable();
                $table->timestamp('fechaDeFallo')->nullable();
                $table->string('plazoDeEntrega')->nullable();
                $table->string('Proyecto')->nullable();
                $table->string('areaSolicitante')->nullable();
                $table->string('numSuficiencia')->nullable();
                $table->timestamp('fechaSuficiencia')->nullable();
                $table->timestamp('fechaContrato')->nullable();
                $table->string('montoSuficiencia')->nullable();
                $table->string('numeroContrato')->nullable();
                $table->string('importeAlmacenado')->nullable();
                $table->string('Pedimento')->nullable();
                $table->string('BlMaster')->nullable();
                $table->string('BlHouse')->nullable();
                $table->decimal('Tipo_Cambio')->nullable()->default('1.00000');
                $table->integer('Id_moneda')->nullable();
                $table->timestamps(); // created_at y updated_at
                $table->index('Factura', 'factura');
                $table->unique('num_pedimento', 'num_pedimento');
                $table->index('Id_moneda', 'PK_th_aduana_c_moneda');
                $table->index('ID_Proveedor', 'ID_Proveedor');
                $table->index('status', 'idx_status');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `th_aduana` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('th_aduana');
    }
};