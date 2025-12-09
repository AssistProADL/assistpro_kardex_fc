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
        if (!Schema::hasTable('vs_lpmanufacturaot')) {
            Schema::create('vs_lpmanufacturaot', function (Blueprint $table) {
                $table->string('Folio_Pro');
                $table->string('Referencia')->nullable();
                $table->string('Cve_Almac')->nullable();
                $table->integer('Idy_Ubica');
                $table->integer('ID_Proveedor')->nullable();
                $table->integer('nTarima');
                $table->string('Cve_Articulo');
                $table->string('Lote');
                $table->decimal('Existencia')->nullable();
                $table->string('CveLP')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `vs_lpmanufacturaot` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vs_lpmanufacturaot');
    }
};