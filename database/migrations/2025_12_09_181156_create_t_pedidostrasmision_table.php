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
        if (!Schema::hasTable('t_pedidostrasmision')) {
            Schema::create('t_pedidostrasmision', function (Blueprint $table) {
                $table->integer('id');
                $table->string('ARCHIVO')->nullable();
                $table->string('FOL_FOLIO')->nullable();
                $table->timestamp('fecha')->nullable();
                $table->string('Usuario')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_pedidostrasmision` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_pedidostrasmision');
    }
};