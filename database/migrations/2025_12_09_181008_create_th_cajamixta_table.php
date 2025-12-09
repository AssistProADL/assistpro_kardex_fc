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
        if (!Schema::hasTable('th_cajamixta')) {
            Schema::create('th_cajamixta', function (Blueprint $table) {
                $table->integer('Cve_CajaMix');
                $table->string('fol_folio')->nullable();
                $table->integer('Sufijo')->nullable();
                $table->integer('NCaja')->nullable();
                $table->string('abierta')->nullable();
                $table->string('embarcada')->nullable();
                $table->string('TipoCaja')->nullable();
                $table->integer('Activo')->nullable();
                $table->integer('cve_tipocaja')->nullable();
                $table->string('Guia')->nullable();
                $table->decimal('Peso')->nullable();
                $table->string('Subida')->nullable();
                $table->string('Status_Guia')->nullable();
                $table->string('tipo')->nullable();
                $table->string('etiqueta')->nullable();
                $table->string('CB_Guia')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `th_cajamixta` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('th_cajamixta');
    }
};