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
        if (!Schema::hasTable('t_activo_fijo')) {
            Schema::create('t_activo_fijo', function (Blueprint $table) {
                $table->integer('id');
                $table->string('clave_activo')->nullable();
                $table->integer('id_articulo');
                $table->integer('id_orden_compra');
                $table->integer('id_pedido')->nullable();
                $table->integer('id_serie')->nullable();
                $table->string('nombre_empleado')->nullable();
                $table->string('clave_empleado')->nullable();
                $table->string('rfc_empleado')->nullable();
                $table->date('fecha_entrada');
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_activo_fijo` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_activo_fijo');
    }
};