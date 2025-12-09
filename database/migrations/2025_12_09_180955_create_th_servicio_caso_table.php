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
        if (!Schema::hasTable('th_servicio_caso')) {
            Schema::create('th_servicio_caso', function (Blueprint $table) {
                $table->id();
                $table->string('folio');
                $table->timestamp('fecha_alta')->default('CURRENT_TIMESTAMP');
                $table->string('origen_tipo');
                $table->integer('origen_almacen_id');
                $table->integer('destino_almacen_id')->nullable();
                $table->integer('cliente_id');
                $table->string('articulo');
                $table->string('serie');
                $table->string('motivo');
                $table->boolean('es_garantia')->default('0');
                $table->integer('servicio_id')->nullable();
                $table->integer('precio_lista_id')->nullable();
                $table->integer('cotizacion_id')->nullable();
                $table->integer('laboratorio_id')->nullable();
                $table->string('status')->default('RECIBIDO_DEPOT');
                $table->text('observacion_inicial')->nullable();
                $table->string('token_publico')->nullable();
                $table->timestamp('created_at')->default('CURRENT_TIMESTAMP');
                $table->string('created_by');
                $table->timestamp('updated_at')->nullable();
                $table->string('updated_by')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->index('cliente_id', 'ix_serv_caso_cliente');
                $table->unique('folio', 'folio');
                $table->index('origen_almacen_id', 'ix_serv_caso_almacen');
                $table->index('serie', 'ix_serv_caso_serie');
                $table->index('status', 'ix_serv_caso_status');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `th_servicio_caso` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('th_servicio_caso');
    }
};