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
        if (!Schema::hasTable('t_existenciacajas_prod')) {
            Schema::create('t_existenciacajas_prod', function (Blueprint $table) {
                $table->integer('Id');
                $table->integer('Idy_Ubica');
                $table->integer('Cve_Almac');
                $table->string('Cve_Articulo')->nullable();
                $table->string('Cve_Lote')->nullable();
                $table->integer('Cve_CajaMix')->nullable();
                $table->integer('nTarima')->nullable();
                $table->decimal('Cantidad')->nullable();
                $table->integer('Id_Proveedor')->nullable();
                $table->integer('Cuarentena')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_existenciacajas_prod` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_existenciacajas_prod');
    }
};