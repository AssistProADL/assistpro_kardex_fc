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
        if (!Schema::hasTable('_td_aduana_old')) {
            Schema::create('_td_aduana_old', function (Blueprint $table) {
                $table->integer('ID_Aduana');
                $table->string('cve_articulo');
                $table->integer('cantidad')->nullable();
                $table->string('cve_lote');
                $table->timestamp('caducidad')->nullable();
                $table->string('temperatura')->nullable();
                $table->string('num_orden')->nullable();
                $table->integer('Ingresado')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `_td_aduana_old` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_td_aduana_old');
    }
};