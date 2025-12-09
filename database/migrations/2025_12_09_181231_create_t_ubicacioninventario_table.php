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
        if (!Schema::hasTable('t_ubicacioninventario')) {
            Schema::create('t_ubicacioninventario', function (Blueprint $table) {
                $table->integer('id');
                $table->integer('ID_Inventario');
                $table->integer('NConteo');
                $table->string('Cve_Usuario')->nullable();
                $table->integer('idy_ubica')->nullable();
                $table->string('cve_ubicacion')->nullable();
                $table->string('status')->nullable();
                $table->string('Vacia')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
                $table->index(['ID_Inventario', 'idy_ubica'], 'ix_ui_inv_ubica');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_ubicacioninventario` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_ubicacioninventario');
    }
};