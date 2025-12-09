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
        if (!Schema::hasTable('td_incidencia')) {
            Schema::create('td_incidencia', function (Blueprint $table) {
                $table->integer('ID_Incidencia');
                $table->integer('ID_Detalle');
                $table->string('Cve_articulo')->nullable();
                $table->string('cve_lote')->nullable();
                $table->timestamp('Caducidad')->nullable();
                $table->string('Observaciones')->nullable();
                $table->string('Fol_folio')->nullable();
                $table->string('Cantidad')->nullable();
                $table->timestamp('fecha')->nullable();
                $table->integer('Activo')->nullable();
                $table->string('clave')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `td_incidencia` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_incidencia');
    }
};