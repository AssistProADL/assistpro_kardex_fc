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
        if (!Schema::hasTable('t_tiposprioridad')) {
            Schema::create('t_tiposprioridad', function (Blueprint $table) {
                $table->integer('ID_Tipoprioridad');
                $table->string('Descripcion')->nullable();
                $table->integer('Prioridad')->nullable();
                $table->string('Status')->nullable();
                $table->integer('Activo')->nullable();
                $table->string('Clave')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_tiposprioridad` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_tiposprioridad');
    }
};