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
        if (!Schema::hasTable('th_subpedido')) {
            Schema::create('th_subpedido', function (Blueprint $table) {
                $table->string('fol_folio');
                $table->integer('cve_almac');
                $table->integer('Sufijo');
                $table->timestamp('Fec_Entrada')->nullable();
                $table->string('Cve_Usuario')->nullable();
                $table->timestamp('Hora_inicio')->nullable();
                $table->timestamp('Hora_Final')->nullable();
                $table->string('status')->nullable();
                $table->string('Reviso')->nullable();
                $table->integer('nivel')->nullable();
                $table->string('empaco')->nullable();
                $table->integer('cajaz_piezas')->nullable();
                $table->string('buffer')->nullable();
                $table->integer('UltimaUbic')->nullable();
                $table->string('TomarApartado')->nullable();
                $table->timestamp('HIR')->nullable();
                $table->timestamp('HFR')->nullable();
                $table->timestamp('HIE')->nullable();
                $table->timestamp('HFE')->nullable();
                $table->string('Placas_T')->nullable();
                $table->string('Embarco')->nullable();
                $table->string('Chofer')->nullable();
                $table->timestamp('FI_Emp')->nullable();
                $table->timestamp('FF_Emp')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `th_subpedido` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('th_subpedido');
    }
};