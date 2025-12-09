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
        if (!Schema::hasTable('th_pedido_ptl')) {
            Schema::create('th_pedido_ptl', function (Blueprint $table) {
                $table->integer('Almacen');
                $table->string('Folio')->nullable();
                $table->timestamp('Fec_Req')->nullable();
                $table->timestamp('Fec_Carga')->nullable();
                $table->string('Status')->nullable();
                $table->integer('Secuencia');
                $table->integer('Prioridad')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `th_pedido_ptl` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('th_pedido_ptl');
    }
};