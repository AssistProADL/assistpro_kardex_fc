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
        if (!Schema::hasTable('td_entalmacenxtarima')) {
            Schema::create('td_entalmacenxtarima', function (Blueprint $table) {
                $table->integer('fol_folio');
                $table->string('cve_articulo');
                $table->string('cve_lote');
                $table->string('ClaveEtiqueta');
                $table->string('Cantidad')->nullable();
                $table->string('Ubicada')->nullable();
                $table->integer('Activo')->nullable();
                $table->integer('PzsXCaja');
                $table->boolean('Abierto');
                $table->string('Observacion')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `td_entalmacenxtarima` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_entalmacenxtarima');
    }
};