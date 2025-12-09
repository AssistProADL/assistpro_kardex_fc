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
        if (!Schema::hasTable('ubicacionesinventareadas')) {
            Schema::create('ubicacionesinventareadas', function (Blueprint $table) {
                $table->integer('ID_PLAN');
                $table->timestamp('FECHA_APLICA');
                $table->string('cve_articulo');
                $table->integer('almacen');
                $table->integer('idy_ubica');
                $table->decimal('Cantidad')->nullable();
                $table->string('status')->nullable();
                $table->integer('Activo')->nullable();
                $table->integer('NConteo');
                $table->string('Cve_Usuario')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `ubicacionesinventareadas` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ubicacionesinventareadas');
    }
};