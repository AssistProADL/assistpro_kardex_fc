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
        if (!Schema::hasTable('td_ordenprod')) {
            Schema::create('td_ordenprod', function (Blueprint $table) {
                $table->bigInteger('id_ord');
                $table->string('Folio_Pro');
                $table->string('Cve_Articulo')->nullable();
                $table->string('Cve_Lote')->nullable();
                $table->timestamp('Fecha_Prod')->nullable();
                $table->string('Cantidad')->nullable();
                $table->string('Usr_Armo')->nullable();
                $table->integer('Activo')->nullable();
                $table->integer('id_art_rel')->nullable();
                $table->string('Referencia')->nullable();
                $table->string('Cve_Almac_Ori')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `td_ordenprod` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_ordenprod');
    }
};