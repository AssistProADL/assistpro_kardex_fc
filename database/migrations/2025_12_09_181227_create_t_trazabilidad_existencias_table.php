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
        if (!Schema::hasTable('t_trazabilidad_existencias')) {
            Schema::create('t_trazabilidad_existencias', function (Blueprint $table) {
                $table->integer('cve_almac');
                $table->integer('idy_ubica')->nullable();
                $table->string('cve_articulo')->nullable();
                $table->string('cve_lote')->nullable();
                $table->string('cantidad')->nullable();
                $table->integer('ntarima')->nullable();
                $table->integer('id_proveedor')->nullable();
                $table->integer('folio_entrada')->nullable();
                $table->integer('folio_oc')->nullable();
                $table->string('factura_ent')->nullable();
                $table->string('factura_oc')->nullable();
                $table->string('proyecto')->nullable();
                $table->integer('id_tipo_movimiento')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_trazabilidad_existencias` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_trazabilidad_existencias');
    }
};