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
        if (!Schema::hasTable('td_subpedido')) {
            Schema::create('td_subpedido', function (Blueprint $table) {
                $table->string('fol_folio');
                $table->integer('cve_almac');
                $table->integer('Sufijo');
                $table->string('Cve_articulo');
                $table->decimal('Num_Cantidad');
                $table->string('Nun_Surtida')->nullable();
                $table->string('ManejaCajas')->nullable();
                $table->string('Status')->nullable();
                $table->integer('Num_Revisda')->nullable();
                $table->integer('Num_Meses')->nullable();
                $table->string('Autorizado')->nullable();
                $table->string('ManejaPiezas')->nullable();
                $table->string('Cve_Lote');
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `td_subpedido` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_subpedido');
    }
};