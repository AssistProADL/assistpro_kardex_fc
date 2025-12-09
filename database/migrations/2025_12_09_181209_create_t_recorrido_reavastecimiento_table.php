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
        if (!Schema::hasTable('t_recorrido_reavastecimiento')) {
            Schema::create('t_recorrido_reavastecimiento', function (Blueprint $table) {
                $table->integer('cve_almac');
                $table->string('cve_articulo');
                $table->string('usuario');
                $table->integer('idy_ubica');
                $table->decimal('Reabastecer')->nullable();
                $table->string('Cve_Lote');
                $table->decimal('Surtidas')->nullable();
                $table->integer('Activo')->nullable();
                $table->decimal('Existencia')->nullable();
                $table->string('Folio')->nullable();
                $table->string('Status')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_recorrido_reavastecimiento` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_recorrido_reavastecimiento');
    }
};