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
        if (!Schema::hasTable('_th_aduana_old')) {
            Schema::create('_th_aduana_old', function (Blueprint $table) {
                $table->integer('ID_Aduana');
                $table->string('num_pedimento')->nullable();
                $table->timestamp('fech_pedimento')->nullable();
                $table->string('aduana')->nullable();
                $table->string('factura')->nullable();
                $table->timestamp('fech_llegPed')->nullable();
                $table->string('status')->nullable();
                $table->integer('ID_Proveedor');
                $table->string('ID_Protocolo')->nullable();
                $table->integer('Consec_protocolo')->nullable();
                $table->string('cve_usuario')->nullable();
                $table->integer('Cve_Almac')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `_th_aduana_old` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_th_aduana_old');
    }
};