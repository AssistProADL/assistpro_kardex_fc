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
        if (!Schema::hasTable('users_bitacora')) {
            Schema::create('users_bitacora', function (Blueprint $table) {
                $table->integer('id');
                $table->string('cve_usuario')->nullable();
                $table->timestamp('fecha_inicio');
                $table->timestamp('fecha_cierre')->nullable();
                $table->string('IP_Address')->nullable();
                $table->string('cve_almacen')->nullable();
                $table->boolean('sesion_cerrada')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `users_bitacora` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_bitacora');
    }
};