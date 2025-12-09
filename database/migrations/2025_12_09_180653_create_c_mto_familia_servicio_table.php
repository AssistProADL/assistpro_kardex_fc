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
        if (!Schema::hasTable('c_mto_familia_servicio')) {
            Schema::create('c_mto_familia_servicio', function (Blueprint $table) {
                $table->id();
                $table->integer('cve_cia');
                $table->string('CVE_FAM_SERV');
                $table->string('descripcion');
                $table->boolean('activo')->default('1');
                $table->timestamps(); // created_at y updated_at
                $table->unique(['cve_cia', 'CVE_FAM_SERV'], 'ux_fam_serv_cia_codigo');
                $table->index(['cve_cia', 'activo'], 'ix_fam_serv_cia_activo');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_mto_familia_servicio` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_mto_familia_servicio');
    }
};