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
        if (!Schema::hasTable('td_entalmacencarac')) {
            Schema::create('td_entalmacencarac', function (Blueprint $table) {
                $table->integer('Id');
                $table->integer('Fol_Folio');
                $table->string('Cve_Articulo')->nullable();
                $table->string('Cve_Lote')->nullable();
                $table->integer('Id_Carac')->nullable();
                $table->decimal('Cant_Sol')->nullable();
                $table->decimal('Cant_Rec')->nullable();
                $table->decimal('Cant_Ubic')->nullable();
                $table->string('ClaveEtiqueta')->nullable();
                $table->string('Ubicada')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `td_entalmacencarac` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_entalmacencarac');
    }
};