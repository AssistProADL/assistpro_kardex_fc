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
        if (!Schema::hasTable('t_invcajas')) {
            Schema::create('t_invcajas', function (Blueprint $table) {
                $table->integer('id_invcajas');
                $table->integer('ID_Inventario');
                $table->integer('NConteo');
                $table->integer('idy_ubica');
                $table->string('cve_articulo')->nullable();
                $table->string('cve_lote')->nullable();
                $table->integer('PiezasXCaja');
                $table->integer('Id_Caja')->nullable();
                $table->integer('nTarima')->nullable();
                $table->integer('Cantidad')->nullable();
                $table->string('epc')->nullable();
                $table->string('code')->nullable();
                $table->string('cve_usuario')->nullable();
                $table->timestamp('fecha')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_invcajas` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_invcajas');
    }
};