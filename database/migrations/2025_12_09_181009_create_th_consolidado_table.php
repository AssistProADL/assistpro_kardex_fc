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
        if (!Schema::hasTable('th_consolidado')) {
            Schema::create('th_consolidado', function (Blueprint $table) {
                $table->integer('id_consolidado');
                $table->string('CodB_Prov')->nullable();
                $table->string('NIT_Prov')->nullable();
                $table->string('Nom_Prov')->nullable();
                $table->string('Cve_CteCon')->nullable();
                $table->string('CodB_CteCon')->nullable();
                $table->string('Nom_CteCon')->nullable();
                $table->string('Dir_CteCon')->nullable();
                $table->string('Cd_CteCon')->nullable();
                $table->string('NIT_CteCon')->nullable();
                $table->string('Cod_CteCon')->nullable();
                $table->string('CodB_CteEnv')->nullable();
                $table->string('Nom_CteEnv')->nullable();
                $table->string('Dir_CteEnv')->nullable();
                $table->string('Cd_CteEnv')->nullable();
                $table->string('Tel_CteEnv')->nullable();
                $table->timestamp('Fec_Entrega');
                $table->integer('Tot_Cajas')->nullable();
                $table->integer('Tot_Pzs')->nullable();
                $table->string('Placa_Trans')->nullable();
                $table->string('Sellos')->nullable();
                $table->string('Fol_PedidoCon');
                $table->string('No_OrdComp');
                $table->string('Status')->nullable();
                $table->integer('Activo')->nullable();
                $table->string('Cve_Usuario')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `th_consolidado` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('th_consolidado');
    }
};