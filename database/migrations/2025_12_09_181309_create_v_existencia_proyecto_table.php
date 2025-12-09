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
        if (!Schema::hasTable('v_existencia_proyecto')) {
            Schema::create('v_existencia_proyecto', function (Blueprint $table) {
                $table->integer('empresa_id');
                $table->text('Cve_Almac')->nullable();
                $table->text('Idy_Ubica')->nullable();
                $table->text('nTarima')->nullable();
                $table->text('Cve_Articulo')->nullable();
                $table->text('Cve_Lote')->nullable();
                $table->text('Existencia')->nullable();
                $table->text('Proyecto')->nullable();
                $table->text('ID_Proveedor')->nullable();
                $table->text('Cuarentena')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
                $table->index('empresa_id', 'idx_empresa');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `v_existencia_proyecto` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('v_existencia_proyecto');
    }
};