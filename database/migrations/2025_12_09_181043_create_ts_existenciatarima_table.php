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
        if (!Schema::hasTable('ts_existenciatarima')) {
            Schema::create('ts_existenciatarima', function (Blueprint $table) {
                $table->integer('cve_almac');
                $table->integer('idy_ubica');
                $table->string('cve_articulo');
                $table->string('lote');
                $table->integer('Fol_Folio');
                $table->integer('ntarima');
                $table->integer('capcidad');
                $table->decimal('existencia')->nullable();
                $table->integer('Activo')->nullable();
                $table->integer('ID_Proveedor');
                $table->boolean('Cuarentena')->nullable();
                $table->string('epc')->nullable();
                $table->string('code')->nullable();
                $table->timestamps(); // created_at y updated_at
                $table->index(['cve_almac', 'ntarima'], 'idx_ts_exi_almac_ntarima');
                $table->unique('epc', 'ux_ts_existenciatarima_epc');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `ts_existenciatarima` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ts_existenciatarima');
    }
};