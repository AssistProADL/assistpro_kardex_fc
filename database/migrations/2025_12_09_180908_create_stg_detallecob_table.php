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
        if (!Schema::hasTable('stg_detallecob')) {
            Schema::create('stg_detallecob', function (Blueprint $table) {
                $table->integer('Id');
                $table->integer('IdCobranza')->nullable();
                $table->decimal('Abono')->nullable();
                $table->timestamp('Fecha')->nullable();
                $table->integer('RutaId')->nullable();
                $table->decimal('SaldoAnt')->nullable();
                $table->decimal('Saldo')->nullable();
                $table->integer('FormaP')->nullable();
                $table->integer('DiaO')->nullable();
                $table->string('Documento')->nullable();
                $table->string('Cliente')->nullable();
                $table->string('IdEmpresa')->nullable();
                $table->string('Cancelada')->nullable();
                $table->string('ClaveBco')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `stg_detallecob` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stg_detallecob');
    }
};