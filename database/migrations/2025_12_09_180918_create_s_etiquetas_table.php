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
        if (!Schema::hasTable('s_etiquetas')) {
            Schema::create('s_etiquetas', function (Blueprint $table) {
                $table->string('MODULO');
                $table->string('NOMBRE');
                $table->text('CADENA')->nullable();
                $table->string('TIPO_IMPRESORA')->nullable();
                $table->integer('Activo')->nullable();
                $table->integer('Num_Regs')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `s_etiquetas` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('s_etiquetas');
    }
};