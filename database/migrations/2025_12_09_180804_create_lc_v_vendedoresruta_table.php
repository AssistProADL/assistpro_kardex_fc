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
        if (!Schema::hasTable('lc_v_vendedoresruta')) {
            Schema::create('lc_v_vendedoresruta', function (Blueprint $table) {
                $table->integer('empresa_id');
                $table->text('Id_Vendedor')->nullable();
                $table->text('Cve_Vendedor')->nullable();
                $table->text('Nombre')->nullable();
                $table->text('Activo')->nullable();
                $table->text('CalleNumero')->nullable();
                $table->text('Colonia')->nullable();
                $table->text('Ciudad')->nullable();
                $table->text('CodigoPostal')->nullable();
                $table->text('Estado')->nullable();
                $table->text('Pais')->nullable();
                $table->text('Id_Fcm')->nullable();
                $table->text('Ruta')->nullable();
                $table->text('Cve_Ruta')->nullable();
                $table->text('IdEmpresa')->nullable();
                $table->timestamps(); // created_at y updated_at
                $table->index('empresa_id', 'idx_empresa');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `lc_v_vendedoresruta` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lc_v_vendedoresruta');
    }
};