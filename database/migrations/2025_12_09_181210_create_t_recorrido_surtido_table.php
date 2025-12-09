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
        if (!Schema::hasTable('t_recorrido_surtido')) {
            Schema::create('t_recorrido_surtido', function (Blueprint $table) {
                $table->integer('idy_ubica');
                $table->integer('cve_almac')->nullable();
                $table->string('cve_pasillo')->nullable();
                $table->integer('cve_rack')->nullable();
                $table->string('Seccion')->nullable();
                $table->integer('cve_nivel')->nullable();
                $table->integer('Ubicacion')->nullable();
                $table->integer('orden_secuencia')->nullable();
                $table->string('fol_folio');
                $table->string('Sufijo');
                $table->string('Cve_articulo');
                $table->string('cve_usuario')->nullable();
                $table->string('picking')->nullable();
                $table->string('claverp')->nullable();
                $table->string('ClaveEtiqueta');
                $table->string('cve_lote');
                $table->string('Cantidad')->nullable();
                $table->string('SURTIBLE')->nullable();
                $table->string('OPCIONAL')->nullable();
                $table->integer('PiezasXCaja')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_recorrido_surtido` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_recorrido_surtido');
    }
};