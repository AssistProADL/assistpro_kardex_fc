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
        if (!Schema::hasTable('mvtosinvruta')) {
            Schema::create('mvtosinvruta', function (Blueprint $table) {
                $table->integer('IdMovStock');
                $table->string('IdEmpresa')->nullable();
                $table->integer('Id_Ruta')->nullable();
                $table->string('Articulo')->nullable();
                $table->string('Lote')->nullable();
                $table->string('Referencia')->nullable();
                $table->decimal('Cantidad')->nullable();
                $table->integer('Id_TipoMovimiento')->nullable();
                $table->timestamp('fecha')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `mvtosinvruta` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mvtosinvruta');
    }
};