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
        if (!Schema::hasTable('c_cliente')) {
            Schema::create('c_cliente', function (Blueprint $table) {
                $table->integer('id_cliente');
                $table->string('Cve_Clte')->nullable();
                $table->string('RazonSocial')->nullable();
                $table->string('RazonComercial')->nullable();
                $table->string('CalleNumero')->nullable();
                $table->string('Colonia')->nullable();
                $table->string('Ciudad')->nullable();
                $table->string('Estado')->nullable();
                $table->string('Pais')->nullable();
                $table->string('CodigoPostal')->nullable();
                $table->string('RFC')->nullable();
                $table->string('Telefono1')->nullable();
                $table->string('Telefono2')->nullable();
                $table->string('Telefono3')->nullable();
                $table->string('ClienteTipo')->nullable();
                $table->string('ClienteTipo2')->nullable();
                $table->string('ClienteGrupo')->nullable();
                $table->string('ClienteFamilia')->nullable();
                $table->string('CondicionPago')->nullable();
                $table->string('MedioEmbarque')->nullable();
                $table->string('ViaEmbarque')->nullable();
                $table->string('CondicionEmbarque')->nullable();
                $table->string('ZonaVenta')->nullable();
                $table->string('cve_ruta')->nullable();
                $table->integer('ID_Proveedor')->nullable();
                $table->string('Cve_CteProv')->nullable();
                $table->integer('Activo')->nullable();
                $table->integer('Cve_Almacenp')->nullable();
                $table->integer('Fol_Serie')->nullable();
                $table->string('Contacto')->nullable();
                $table->integer('id_destinatario')->nullable();
                $table->text('longitud')->nullable();
                $table->text('latitud')->nullable();
                $table->string('IdEmpresa')->nullable();
                $table->string('email_cliente')->nullable();
                $table->string('Cve_SAP')->nullable();
                $table->string('Encargado')->nullable();
                $table->string('Referencia')->nullable();
                $table->integer('credito')->nullable();
                $table->string('limite_credito')->nullable();
                $table->integer('dias_credito')->nullable();
                $table->string('credito_actual')->nullable();
                $table->string('saldo_inicial')->nullable();
                $table->string('saldo_actual')->nullable();
                $table->integer('validar_gps')->nullable();
                $table->integer('cliente_general')->nullable();
                $table->integer('Id_RegFis')->nullable();
                $table->integer('Id_CFDI')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_cliente` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_cliente');
    }
};