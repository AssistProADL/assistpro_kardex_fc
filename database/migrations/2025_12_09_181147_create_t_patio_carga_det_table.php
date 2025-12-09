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
        if (!Schema::hasTable('t_patio_carga_det')) {
            Schema::create('t_patio_carga_det', function (Blueprint $table) {
                $table->integer('id_carga');
                $table->integer('renglon');
                $table->string('cve_articulo');
                $table->string('descripcion')->nullable();
                $table->string('lote')->nullable();
                $table->decimal('cantidad');
                $table->string('uom')->nullable();
                $table->string('bl')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
                $table->primary(['id_carga', 'renglon']);
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_patio_carga_det` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_patio_carga_det');
    }
};