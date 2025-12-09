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
        if (!Schema::hasTable('lc_diaso')) {
            Schema::create('lc_diaso', function (Blueprint $table) {
                $table->integer('empresa_id');
                $table->text('Id')->nullable();
                $table->text('DiaO')->nullable();
                $table->text('Fecha')->nullable();
                $table->text('RutaId')->nullable();
                $table->text('VProg')->nullable();
                $table->text('Ve')->nullable();
                $table->text('IdEmpresa')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
                $table->index('empresa_id', 'idx_empresa');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `lc_diaso` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lc_diaso');
    }
};