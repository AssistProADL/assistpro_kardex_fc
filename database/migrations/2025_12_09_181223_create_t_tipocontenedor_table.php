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
        if (!Schema::hasTable('t_tipocontenedor')) {
            Schema::create('t_tipocontenedor', function (Blueprint $table) {
                $table->integer('Cve_TipoCont');
                $table->string('Des_TipoCont')->nullable();
                $table->integer('Ancho')->nullable();
                $table->integer('Largo')->nullable();
                $table->integer('Alto')->nullable();
                $table->string('Peso')->nullable();
                $table->string('CapVol')->nullable();
                $table->string('PesoMax')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_tipocontenedor` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_tipocontenedor');
    }
};