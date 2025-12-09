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
        if (!Schema::hasTable('t_rastreoguias')) {
            Schema::create('t_rastreoguias', function (Blueprint $table) {
                $table->string('Guia');
                $table->string('Fol_Folio')->nullable();
                $table->string('Serv_Status')->nullable();
                $table->timestamp('Fec_Recoleccion')->nullable();
                $table->timestamp('Fec_Programada')->nullable();
                $table->timestamp('Fec_Entrega')->nullable();
                $table->string('Recibe')->nullable();
                $table->string('Destino')->nullable();
                $table->string('Comentarios')->nullable();
                $table->string('Datos_SAP')->nullable();
                $table->timestamp('Fecha_Act')->nullable();
                $table->string('Actualizado')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_rastreoguias` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_rastreoguias');
    }
};