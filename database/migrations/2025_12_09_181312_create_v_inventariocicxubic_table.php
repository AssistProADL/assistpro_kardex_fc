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
        if (!Schema::hasTable('v_inventariocicxubic')) {
            Schema::create('v_inventariocicxubic', function (Blueprint $table) {
                $table->integer('ID_PLAN');
                $table->integer('NConteo');
                $table->integer('Idy_Ubica');
                $table->string('Cve_Articulo');
                $table->string('Cve_Lote');
                $table->string('Cantidad')->nullable();
                $table->string('Cve_Usuario')->nullable();
                $table->timestamp('Fecha')->nullable();
                $table->string('Status_Ubica')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `v_inventariocicxubic` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('v_inventariocicxubic');
    }
};