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
        if (!Schema::hasTable('t_entalmacentransporte')) {
            Schema::create('t_entalmacentransporte', function (Blueprint $table) {
                $table->integer('Id');
                $table->integer('Fol_Folio');
                $table->string('Operador')->nullable();
                $table->string('No_Unidad')->nullable();
                $table->string('Placas')->nullable();
                $table->string('Linea_Transportista')->nullable();
                $table->string('Observaciones')->nullable();
                $table->string('Sello')->nullable();
                $table->timestamp('Fec_Ingreso')->nullable();
                $table->timestamp('Fec_Salida')->nullable();
                $table->string('Id_Operador')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_entalmacentransporte` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_entalmacentransporte');
    }
};