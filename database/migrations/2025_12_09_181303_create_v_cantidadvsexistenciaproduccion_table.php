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
        if (!Schema::hasTable('v_cantidadvsexistenciaproduccion')) {
            Schema::create('v_cantidadvsexistenciaproduccion', function (Blueprint $table) {
                $table->string('orden_id')->nullable();
                $table->string('cod_art_compuesto')->nullable();
                $table->string('clave')->nullable();
                $table->string('control_lotes');
                $table->string('Lote')->nullable();
                $table->string('LoteOT')->nullable();
                $table->string('Caduca');
                $table->string('Caducidad')->nullable();
                $table->string('control_peso');
                $table->string('Cantidad')->nullable();
                $table->bigInteger('Cant_OT')->nullable();
                $table->string('cantnecesaria')->nullable();
                $table->string('Cantidad_Producida')->nullable();
                $table->string('ubicacion')->nullable();
                $table->decimal('existencia');
                $table->string('um')->nullable();
                $table->string('mav_cveunimed')->nullable();
                $table->string('clave_almacen')->nullable();
                $table->string('Cve_Contenedor')->nullable();
                $table->string('CveLP');
                $table->bigInteger('Id_Contenedor');
                $table->integer('acepto');
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `v_cantidadvsexistenciaproduccion` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('v_cantidadvsexistenciaproduccion');
    }
};