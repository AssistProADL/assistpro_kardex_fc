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
        if (!Schema::hasTable('ts_existenciapiezas')) {
            Schema::create('ts_existenciapiezas', function (Blueprint $table) {
                $table->integer('cve_almac');
                $table->integer('idy_ubica');
                $table->integer('id');
                $table->string('cve_articulo');
                $table->string('cve_lote');
                $table->decimal('Existencia')->nullable();
                $table->string('ClaveEtiqueta')->nullable();
                $table->integer('ID_Proveedor');
                $table->boolean('Cuarentena')->nullable();
                $table->string('epc')->nullable();
                $table->string('code')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
                $table->unique('epc', 'ux_ts_existenciapiezas_epc');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `ts_existenciapiezas` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ts_existenciapiezas');
    }
};