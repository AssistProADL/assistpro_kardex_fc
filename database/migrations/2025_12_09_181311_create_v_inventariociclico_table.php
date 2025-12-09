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
        if (!Schema::hasTable('v_inventariociclico')) {
            Schema::create('v_inventariociclico', function (Blueprint $table) {
                $table->integer('ID_PLAN');
                $table->string('cve_articulo');
                $table->integer('ID_PERIODO')->nullable();
                $table->string('DESCRIPCION')->nullable();
                $table->timestamp('FECHA_INI')->nullable();
                $table->timestamp('FECHA_FIN')->nullable();
                $table->integer('id_almacen')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamp('FECHA_APLICA');
                $table->string('status')->nullable();
                $table->string('Cve_Usuario')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `v_inventariociclico` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('v_inventariociclico');
    }
};