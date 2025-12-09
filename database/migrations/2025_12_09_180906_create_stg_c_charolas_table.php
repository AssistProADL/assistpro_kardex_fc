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
        if (!Schema::hasTable('stg_c_charolas')) {
            Schema::create('stg_c_charolas', function (Blueprint $table) {
                $table->integer('empresa_id');
                $table->text('IDContenedor')->nullable();
                $table->text('cve_almac')->nullable();
                $table->text('Clave_Contenedor')->nullable();
                $table->text('descripcion')->nullable();
                $table->text('Permanente')->nullable();
                $table->text('Pedido')->nullable();
                $table->text('sufijo')->nullable();
                $table->text('tipo')->nullable();
                $table->text('Activo')->nullable();
                $table->text('alto')->nullable();
                $table->text('ancho')->nullable();
                $table->text('fondo')->nullable();
                $table->text('peso')->nullable();
                $table->text('pesomax')->nullable();
                $table->text('capavol')->nullable();
                $table->text('Costo')->nullable();
                $table->text('CveLP')->nullable();
                $table->text('TipoGen')->nullable();
                $table->timestamps(); // created_at y updated_at
                $table->index(['empresa_id', 'cve_almac'], 'ix_charolas_emp_alm');
                $table->index('empresa_id', 'idx_empresa');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `stg_c_charolas` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stg_c_charolas');
    }
};