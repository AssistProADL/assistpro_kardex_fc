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
        if (!Schema::hasTable('t_mon_no_tomados')) {
            Schema::create('t_mon_no_tomados', function (Blueprint $table) {
                $table->integer('id');
                $table->integer('idy_ubica')->nullable();
                $table->integer('cve_almac')->nullable();
                $table->string('cve_pasillo')->nullable();
                $table->integer('cve_rack')->nullable();
                $table->string('Seccion')->nullable();
                $table->integer('cve_nivel')->nullable();
                $table->integer('Ubicacion')->nullable();
                $table->integer('orden_secuencia')->nullable();
                $table->string('fol_folio')->nullable();
                $table->string('Sufijo')->nullable();
                $table->string('Cve_articulo')->nullable();
                $table->string('cve_usuario')->nullable();
                $table->string('picking')->nullable();
                $table->string('cve_lote')->nullable();
                $table->string('MasBiejo')->nullable();
                $table->string('revisado')->nullable();
                $table->integer('surtir')->nullable();
                $table->timestamp('hora')->nullable();
                $table->text('msg')->nullable();
                $table->string('modulo')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_mon_no_tomados` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_mon_no_tomados');
    }
};