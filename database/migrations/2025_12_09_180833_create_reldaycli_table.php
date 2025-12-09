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
        if (!Schema::hasTable('reldaycli')) {
            Schema::create('reldaycli', function (Blueprint $table) {
                $table->integer('Id');
                $table->string('Cve_Ruta')->nullable();
                $table->string('Cve_Cliente')->nullable();
                $table->integer('Id_Destinatario');
                $table->string('Cve_Vendedor')->nullable();
                $table->integer('Lu')->nullable();
                $table->integer('Ma')->nullable();
                $table->integer('Mi')->nullable();
                $table->integer('Ju')->nullable();
                $table->integer('Vi')->nullable();
                $table->integer('Sa')->nullable();
                $table->integer('Do')->nullable();
                $table->string('Cve_Almac')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `reldaycli` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reldaycli');
    }
};