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
        if (!Schema::hasTable('td_entalmacencaja')) {
            Schema::create('td_entalmacencaja', function (Blueprint $table) {
                $table->integer('Fol_Folio');
                $table->string('Cve_Almac');
                $table->string('Cve_Articulo');
                $table->string('Cve_Lote');
                $table->decimal('PzsXCaja')->nullable();
                $table->integer('Id_Caja')->nullable();
                $table->string('ClaveEtiqueta')->nullable();
                $table->string('Ubicada')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `td_entalmacencaja` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_entalmacencaja');
    }
};