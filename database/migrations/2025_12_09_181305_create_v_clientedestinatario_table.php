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
        if (!Schema::hasTable('v_clientedestinatario')) {
            Schema::create('v_clientedestinatario', function (Blueprint $table) {
                $table->string('Cve_Clte')->nullable();
                $table->string('Cve_CteProv')->nullable();
                $table->integer('Id_Destinatario');
                $table->string('RazonSocial')->nullable();
                $table->string('Destinatario')->nullable();
                $table->string('Direccion')->nullable();
                $table->string('Colonia')->nullable();
                $table->string('CP')->nullable();
                $table->string('Ciudad')->nullable();
                $table->string('Estado')->nullable();
                $table->string('Pais')->nullable();
                $table->string('RFC')->nullable();
                $table->string('Telefono1')->nullable();
                $table->string('Limite_Credito');
                $table->bigInteger('Dias_Credito');
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `v_clientedestinatario` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('v_clientedestinatario');
    }
};