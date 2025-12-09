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
        if (!Schema::hasTable('lc_rel_dest_ruta')) {
            Schema::create('lc_rel_dest_ruta', function (Blueprint $table) {
                $table->integer('empresa_id');
                $table->text('Id')->nullable();
                $table->text('Cve_Almac')->nullable();
                $table->text('Id_Destinatario')->nullable();
                $table->text('Id_Ruta')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
                $table->index('empresa_id', 'idx_empresa');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `lc_rel_dest_ruta` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lc_rel_dest_ruta');
    }
};