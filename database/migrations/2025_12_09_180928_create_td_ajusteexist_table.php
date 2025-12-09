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
        if (!Schema::hasTable('td_ajusteexist')) {
            Schema::create('td_ajusteexist', function (Blueprint $table) {
                $table->string('fol_folio');
                $table->integer('cve_almac');
                $table->integer('Idy_ubica');
                $table->string('cve_articulo');
                $table->string('cve_lote');
                $table->string('num_cantant')->nullable();
                $table->string('num_cantnva')->nullable();
                $table->decimal('imp_cosprom')->nullable();
                $table->integer('Id_Motivo')->nullable();
                $table->string('Tipo_Cat')->nullable();
                $table->integer('ntarima')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `td_ajusteexist` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_ajusteexist');
    }
};