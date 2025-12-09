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
        if (!Schema::hasTable('th_dest_pedido')) {
            Schema::create('th_dest_pedido', function (Blueprint $table) {
                $table->integer('id_dest_ped');
                $table->integer('id_pedido');
                $table->string('Cve_Clte')->nullable();
                $table->string('CalleNumero')->nullable();
                $table->string('Colonia')->nullable();
                $table->string('Ciudad')->nullable();
                $table->string('Estado')->nullable();
                $table->string('Pais')->nullable();
                $table->string('CodigoPostal')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `th_dest_pedido` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('th_dest_pedido');
    }
};