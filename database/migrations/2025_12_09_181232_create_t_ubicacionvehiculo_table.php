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
        if (!Schema::hasTable('t_ubicacionvehiculo')) {
            Schema::create('t_ubicacionvehiculo', function (Blueprint $table) {
                $table->integer('Id');
                $table->integer('Id_Vendedor');
                $table->integer('ID_Vehiculo');
                $table->integer('IdRuta');
                $table->timestamp('Fecha')->nullable();
                $table->string('Longitud')->nullable();
                $table->string('Latitud')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_ubicacionvehiculo` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_ubicacionvehiculo');
    }
};