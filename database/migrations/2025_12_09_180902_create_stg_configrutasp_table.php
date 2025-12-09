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
        if (!Schema::hasTable('stg_configrutasp')) {
            Schema::create('stg_configrutasp', function (Blueprint $table) {
                $table->integer('Id');
                $table->integer('RutaId')->nullable();
                $table->string('ModelPrinter')->nullable();
                $table->string('VelCom')->nullable();
                $table->string('COM')->nullable();
                $table->string('Server')->nullable();
                $table->integer('Puerto')->nullable();
                $table->string('ServerGPS')->nullable();
                $table->string('GPS')->nullable();
                $table->string('PuertoG')->nullable();
                $table->string('PagoContado')->nullable();
                $table->string('CteNvo')->nullable();
                $table->string('CveCteNvo')->nullable();
                $table->string('IdEmpresa')->nullable();
                $table->string('SugerirCant')->nullable();
                $table->string('PromoEq')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `stg_configrutasp` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stg_configrutasp');
    }
};