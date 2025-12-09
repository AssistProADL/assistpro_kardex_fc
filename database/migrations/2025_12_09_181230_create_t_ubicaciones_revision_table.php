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
        if (!Schema::hasTable('t_ubicaciones_revision')) {
            Schema::create('t_ubicaciones_revision', function (Blueprint $table) {
                $table->integer('ID_URevision');
                $table->string('cve_almac');
                $table->string('cve_ubicacion');
                $table->string('fol_folio')->nullable();
                $table->integer('sufijo')->nullable();
                $table->string('Checado')->nullable();
                $table->integer('Activo')->nullable();
                $table->string('descripcion')->nullable();
                $table->string('AreaStagging')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_ubicaciones_revision` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_ubicaciones_revision');
    }
};