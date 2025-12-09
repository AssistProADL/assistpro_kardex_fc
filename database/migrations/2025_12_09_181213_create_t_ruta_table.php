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
        if (!Schema::hasTable('t_ruta')) {
            Schema::create('t_ruta', function (Blueprint $table) {
                $table->integer('ID_Ruta');
                $table->string('cve_ruta');
                $table->string('descripcion');
                $table->string('status')->nullable();
                $table->integer('cve_almacenp');
                $table->integer('venta_preventa');
                $table->string('control_pallets_cont')->nullable();
                $table->integer('consig_pallets')->nullable();
                $table->integer('consig_cont')->nullable();
                $table->integer('ID_Proveedor')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_ruta` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_ruta');
    }
};