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
        if (!Schema::hasTable('v_pendientesacomodo')) {
            Schema::create('v_pendientesacomodo', function (Blueprint $table) {
                $table->string('cve_articulo');
                $table->string('Cve_Lote');
                $table->decimal('Cantidad')->nullable();
                $table->string('Cve_Ubicacion')->nullable();
                $table->integer('ID_Proveedor');
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `v_pendientesacomodo` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('v_pendientesacomodo');
    }
};