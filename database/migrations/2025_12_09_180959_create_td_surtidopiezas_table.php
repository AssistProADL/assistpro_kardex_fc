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
        if (!Schema::hasTable('td_surtidopiezas')) {
            Schema::create('td_surtidopiezas', function (Blueprint $table) {
                $table->string('fol_folio');
                $table->integer('cve_almac');
                $table->integer('Sufijo');
                $table->string('Cve_articulo');
                $table->string('LOTE');
                $table->decimal('Cantidad');
                $table->string('revisadas')->nullable();
                $table->string('Num_Empacados')->nullable();
                $table->string('status')->nullable();
                $table->string('empacado')->nullable();
                $table->string('embarcado')->nullable();
                $table->integer('Id_Proveedor')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `td_surtidopiezas` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_surtidopiezas');
    }
};