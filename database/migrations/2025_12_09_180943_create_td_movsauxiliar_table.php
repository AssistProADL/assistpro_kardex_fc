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
        if (!Schema::hasTable('td_movsauxiliar')) {
            Schema::create('td_movsauxiliar', function (Blueprint $table) {
                $table->integer('cve_cia');
                $table->integer('cve_annio');
                $table->integer('cve_mes');
                $table->string('cve_tipmov');
                $table->timestamp('fec_movto');
                $table->string('fol_folmov');
                $table->integer('cve_almac');
                $table->string('cve_articulo');
                $table->decimal('Id_Reg');
                $table->string('num_cant')->nullable();
                $table->string('num_signo')->nullable();
                $table->decimal('imp_importe')->nullable();
                $table->decimal('imp_cosprom')->nullable();
                $table->string('num_existf')->nullable();
                $table->decimal('imp_importef')->nullable();
                $table->integer('cve_ucosto')->nullable();
                $table->string('num_ordprod')->nullable();
                $table->string('des_otros')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `td_movsauxiliar` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_movsauxiliar');
    }
};