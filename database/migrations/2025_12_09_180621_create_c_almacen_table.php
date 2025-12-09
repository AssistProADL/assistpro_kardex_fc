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
        if (!Schema::hasTable('c_almacen')) {
            Schema::create('c_almacen', function (Blueprint $table) {
                $table->integer('cve_almac');
                $table->string('clave_almacen')->nullable();
                $table->integer('cve_almacenp');
                $table->string('des_almac')->nullable();
                $table->string('des_direcc')->nullable();
                $table->integer('Activo')->nullable();
                $table->string('Cve_TipoZona')->nullable();
                $table->string('clasif_abc')->nullable();
                $table->integer('ID_Proveedor')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_almacen` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_almacen');
    }
};