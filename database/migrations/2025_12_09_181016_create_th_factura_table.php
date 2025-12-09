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
        if (!Schema::hasTable('th_factura')) {
            Schema::create('th_factura', function (Blueprint $table) {
                $table->integer('Id_Fac');
                $table->string('Folio_Fac')->nullable();
                $table->string('Tipo_Doc')->nullable();
                $table->date('FechaEmision');
                $table->string('Cve_Clte')->nullable();
                $table->string('Cve_Almacen')->nullable();
                $table->string('OrdenCompra')->nullable();
                $table->timestamp('Fecha_Modif');
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `th_factura` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('th_factura');
    }
};