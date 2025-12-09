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
        if (!Schema::hasTable('t_log_ws')) {
            Schema::create('t_log_ws', function (Blueprint $table) {
                $table->integer('Id');
                $table->timestamp('Fecha')->nullable();
                $table->string('Referencia')->nullable();
                $table->string('Mensaje')->nullable();
                $table->string('Respuesta')->nullable();
                $table->integer('Enviado');
                $table->string('Proceso')->nullable();
                $table->string('Dispositivo')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_log_ws` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_log_ws');
    }
};