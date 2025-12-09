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
        if (!Schema::hasTable('t_invpiezas')) {
            Schema::create('t_invpiezas', function (Blueprint $table) {
                $table->bigInteger('id');
                $table->integer('ID_Inventario');
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
                $table->boolean('Art_Cerrado');
                $table->timestamp('fecha_fin');
                $table->integer('ID_Proveedor')->nullable();
                $table->boolean('Cuarentena')->nullable();
                $table->timestamps(); // created_at y updated_at
                $table->index(['ID_Inventario', 'idy_ubica'], 'ix_invpiezas_inv_ubica');
                $table->index('ID_Inventario', 'ix_invpiezas_inv');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_invpiezas` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_invpiezas');
    }
};