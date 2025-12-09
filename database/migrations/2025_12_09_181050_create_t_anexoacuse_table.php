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
        if (!Schema::hasTable('t_anexoacuse')) {
            Schema::create('t_anexoacuse', function (Blueprint $table) {
                $table->integer('id');
                $table->integer('ID_Acuse')->nullable();
                $table->timestamp('FechaEntrega')->nullable();
                $table->string('Fol_folio')->nullable();
                $table->string('RazonSocial')->nullable();
                $table->timestamp('FechaEnvio')->nullable();
                $table->string('Placas')->nullable();
                $table->timestamp('FechaEntrega1')->nullable();
                $table->string('Recivio')->nullable();
                $table->string('NFactura')->nullable();
                $table->integer('ID_Incidencia')->nullable();
                $table->integer('urgencia')->nullable();
                $table->integer('orden')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_anexoacuse` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_anexoacuse');
    }
};