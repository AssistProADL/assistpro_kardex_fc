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
        if (!Schema::hasTable('listad')) {
            Schema::create('listad', function (Blueprint $table) {
                $table->integer('id');
                $table->string('Lista')->nullable();
                $table->string('Tipo')->nullable();
                $table->boolean('Caduca')->nullable();
                $table->date('FechaIni')->nullable();
                $table->date('FechaFin')->nullable();
                $table->integer('Cve_Almac');
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `listad` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listad');
    }
};