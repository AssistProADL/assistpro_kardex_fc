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
        if (!Schema::hasTable('cobranza')) {
            Schema::create('cobranza', function (Blueprint $table) {
                $table->integer('id');
                $table->integer('Cliente')->nullable();
                $table->string('Documento')->nullable();
                $table->decimal('Saldo')->nullable();
                $table->integer('Status')->nullable();
                $table->integer('RutaId')->nullable();
                $table->string('UltPago')->nullable();
                $table->timestamp('FechaReg')->nullable();
                $table->timestamp('FechaVence')->nullable();
                $table->integer('FolioInterno')->nullable();
                $table->string('TipoDoc')->nullable();
                $table->integer('DiaO')->nullable();
                $table->string('IdEmpresa')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `cobranza` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cobranza');
    }
};