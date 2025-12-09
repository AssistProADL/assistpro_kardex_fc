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
        if (!Schema::hasTable('t_movcuarentena')) {
            Schema::create('t_movcuarentena', function (Blueprint $table) {
                $table->integer('Id');
                $table->string('Fol_Folio')->nullable();
                $table->integer('Idy_Ubica');
                $table->integer('IdContenedor')->nullable();
                $table->string('Cve_Articulo')->nullable();
                $table->string('Cve_Lote')->nullable();
                $table->string('Cantidad')->nullable();
                $table->integer('PzsXCaja')->nullable();
                $table->timestamp('Fec_Ingreso')->nullable();
                $table->integer('Id_MotivoIng')->nullable();
                $table->string('Tipo_Cat_Ing')->nullable();
                $table->string('Usuario_Ing')->nullable();
                $table->timestamp('Fec_Libera')->nullable();
                $table->integer('Id_MotivoLib')->nullable();
                $table->string('Tipo_Cat_Lib')->nullable();
                $table->string('Usuario_Lib')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_movcuarentena` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_movcuarentena');
    }
};