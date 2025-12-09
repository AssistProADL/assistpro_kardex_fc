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
        if (!Schema::hasTable('_th_entalmacen')) {
            Schema::create('_th_entalmacen', function (Blueprint $table) {
                $table->integer('Fol_Folio');
                $table->integer('Cve_Almac');
                $table->timestamp('Fec_Entrada')->nullable();
                $table->string('fol_oep')->nullable();
                $table->string('Cve_Usuario')->nullable();
                $table->integer('Cve_Proveedor')->nullable();
                $table->string('STATUS')->nullable();
                $table->string('Cve_Autorizado')->nullable();
                $table->string('TieneOE')->nullable();
                $table->integer('statusaurora')->nullable();
                $table->integer('id_ocompra')->nullable();
                $table->string('placas')->nullable();
                $table->string('entarimado')->nullable();
                $table->string('bufer')->nullable();
                $table->timestamp('HoraInicio')->nullable();
                $table->string('ID_Protocolo')->nullable();
                $table->integer('Consec_protocolo')->nullable();
                $table->string('cve_ubicacion')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `_th_entalmacen` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_th_entalmacen');
    }
};