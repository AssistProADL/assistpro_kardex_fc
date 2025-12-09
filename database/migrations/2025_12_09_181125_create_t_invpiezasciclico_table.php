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
        if (!Schema::hasTable('t_invpiezasciclico')) {
            Schema::create('t_invpiezasciclico', function (Blueprint $table) {
                $table->integer('ID_PLAN');
                $table->integer('NConteo');
                $table->integer('idy_ubica');
                $table->string('cve_articulo');
                $table->string('cve_lote');
                $table->string('Cantidad')->nullable();
                $table->string('ExistenciaTeorica')->nullable();
                $table->string('cve_usuario')->nullable();
                $table->timestamp('fecha')->nullable();
                $table->string('ClaveEtiqueta')->nullable();
                $table->integer('Activo')->nullable();
                $table->integer('Id_Proveedor');
                $table->boolean('Cuarentena')->nullable();
                $table->timestamps(); // created_at y updated_at
                $table->index(['ID_PLAN', 'NConteo', 'idy_ubica'], 'idx_pzc_2');
                $table->index('ID_PLAN', 'idx_pzc_1');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_invpiezasciclico` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_invpiezasciclico');
    }
};