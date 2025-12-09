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
        if (!Schema::hasTable('c_mto_taller')) {
            Schema::create('c_mto_taller', function (Blueprint $table) {
                $table->id();
                $table->integer('cve_cia');
                $table->string('CVE_TALLER');
                $table->string('nombre');
                $table->string('tipo')->default('INTERNO');
                $table->string('contacto')->nullable();
                $table->string('telefono')->nullable();
                $table->string('email')->nullable();
                $table->string('direccion')->nullable();
                $table->boolean('activo')->default('1');
                $table->timestamp('fecha_alta')->default('CURRENT_TIMESTAMP');
                $table->timestamps(); // created_at y updated_at
                $table->unique(['cve_cia', 'CVE_TALLER'], 'ux_mto_taller_cia_codigo');
                $table->index(['cve_cia', 'activo'], 'ix_mto_taller_cia_activo');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_mto_taller` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_mto_taller');
    }
};