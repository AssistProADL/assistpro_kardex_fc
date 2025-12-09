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
        if (!Schema::hasTable('th_ordenembarque')) {
            Schema::create('th_ordenembarque', function (Blueprint $table) {
                $table->integer('ID_OEmbarque');
                $table->integer('Cve_Almac')->nullable();
                $table->integer('ID_Transporte')->nullable();
                $table->integer('Id_Ruta')->nullable();
                $table->string('cve_usuario')->nullable();
                $table->integer('t_ubicacionembarque_id')->nullable();
                $table->timestamp('fecha')->nullable();
                $table->timestamp('FechaEnvio')->nullable();
                $table->string('destino')->nullable();
                $table->string('status')->nullable();
                $table->string('comentarios')->nullable();
                $table->string('embarcador')->nullable();
                $table->string('Num_Guia')->nullable();
                $table->string('Tipo_Entrega')->nullable();
                $table->string('Ban_Libre')->nullable();
                $table->string('seguro')->nullable();
                $table->string('flete')->nullable();
                $table->text('origen')->nullable();
                $table->string('chofer')->nullable();
                $table->integer('Activo');
                $table->string('guia_transporte')->nullable();
                $table->string('contacto')->nullable();
                $table->string('id_chofer')->nullable();
                $table->string('num_unidad')->nullable();
                $table->string('cve_transportadora')->nullable();
                $table->string('placa')->nullable();
                $table->string('sello_precinto')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `th_ordenembarque` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('th_ordenembarque');
    }
};