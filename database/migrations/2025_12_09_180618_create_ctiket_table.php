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
        if (!Schema::hasTable('ctiket')) {
            Schema::create('ctiket', function (Blueprint $table) {
                $table->integer('ID');
                $table->string('Linea1')->nullable();
                $table->string('Linea2')->nullable();
                $table->string('Linea3')->nullable();
                $table->string('Linea4')->nullable();
                $table->string('Mensaje')->nullable();
                $table->string('Tdv')->nullable();
                $table->string('LOGO')->nullable();
                $table->string('MLiq')->nullable();
                $table->string('IdEmpresa')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `ctiket` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ctiket');
    }
};