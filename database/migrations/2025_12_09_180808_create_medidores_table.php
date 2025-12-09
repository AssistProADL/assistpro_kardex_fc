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
        if (!Schema::hasTable('medidores')) {
            Schema::create('medidores', function (Blueprint $table) {
                $table->integer('IdRow');
                $table->integer('IdRuta')->nullable();
                $table->integer('DiaO')->nullable();
                $table->decimal('OdometroInicial')->nullable();
                $table->decimal('OdometroFinal')->nullable();
                $table->decimal('TanqueInicial')->nullable();
                $table->decimal('TanqueFinal')->nullable();
                $table->decimal('LitrosCargados')->nullable();
                $table->decimal('GastoLitros')->nullable();
                $table->decimal('Rendimiento')->nullable();
                $table->decimal('KmR')->nullable();
                $table->string('IdEmpresa')->nullable();
                $table->integer('IdVehiculo')->nullable();
                $table->string('Fol_Ticket')->nullable();
                $table->string('Proveedor')->nullable();
                $table->string('Direccion')->nullable();
                $table->string('Combustible')->nullable();
                $table->decimal('Precio')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `medidores` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medidores');
    }
};