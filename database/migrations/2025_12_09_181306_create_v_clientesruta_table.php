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
        if (!Schema::hasTable('v_clientesruta')) {
            Schema::create('v_clientesruta', function (Blueprint $table) {
                $table->string('Cve_Clte')->nullable();
                $table->string('RazonSocial')->nullable();
                $table->string('direccion')->nullable();
                $table->string('colonia')->nullable();
                $table->string('ciudad')->nullable();
                $table->string('estado')->nullable();
                $table->string('Pais')->nullable();
                $table->string('postal')->nullable();
                $table->string('RFC')->nullable();
                $table->string('telefono')->nullable();
                $table->string('Telefono2')->nullable();
                $table->string('CondicionPago')->nullable();
                $table->text('longitud')->nullable();
                $table->text('latitud')->nullable();
                $table->integer('credito')->nullable();
                $table->string('limite_credito')->nullable();
                $table->integer('dias_credito')->nullable();
                $table->string('credito_actual')->nullable();
                $table->string('saldo_inicial')->nullable();
                $table->string('saldo_actual')->nullable();
                $table->integer('validar_gps')->nullable();
                $table->string('Id_Fcm')->nullable();
                $table->string('Ruta')->nullable();
                $table->integer('IdRuta')->nullable();
                $table->string('IdEmpresa')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `v_clientesruta` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('v_clientesruta');
    }
};