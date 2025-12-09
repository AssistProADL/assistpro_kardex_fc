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
        if (!Schema::hasTable('t_ubicacionembarque')) {
            Schema::create('t_ubicacionembarque', function (Blueprint $table) {
                $table->integer('ID_Embarque');
                $table->string('cve_ubicacion');
                $table->integer('cve_almac');
                $table->string('status')->nullable();
                $table->integer('Activo')->nullable();
                $table->string('descripcion')->nullable();
                $table->string('AreaStagging')->nullable();
                $table->string('largo')->nullable();
                $table->string('ancho')->nullable();
                $table->string('alto')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_ubicacionembarque` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_ubicacionembarque');
    }
};