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
        if (!Schema::hasTable('td_factura')) {
            Schema::create('td_factura', function (Blueprint $table) {
                $table->integer('Id_Fac');
                $table->string('Fol_Folio')->nullable();
                $table->string('Referencia')->nullable();
                $table->string('Cve_Articulo')->nullable();
                $table->string('Cve_Lote')->nullable();
                $table->integer('Item');
                $table->string('CPROWNUM')->nullable();
                $table->decimal('Cantidad');
                $table->decimal('Cantidad_Surtida');
                $table->timestamp('Fecha_Modif');
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `td_factura` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_factura');
    }
};