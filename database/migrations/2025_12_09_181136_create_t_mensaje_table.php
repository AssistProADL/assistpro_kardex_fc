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
        if (!Schema::hasTable('t_mensaje')) {
            Schema::create('t_mensaje', function (Blueprint $table) {
                $table->integer('id');
                $table->string('clave')->nullable();
                $table->string('descripcion')->nullable();
                $table->string('mensaje')->nullable();
                $table->timestamp('fecha_inicio');
                $table->timestamp('fecha_final');
                $table->string('activo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_mensaje` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_mensaje');
    }
};