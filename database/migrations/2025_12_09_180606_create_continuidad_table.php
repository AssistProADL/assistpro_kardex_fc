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
        if (!Schema::hasTable('continuidad')) {
            Schema::create('continuidad', function (Blueprint $table) {
                $table->integer('RutaID');
                $table->integer('DiaO')->nullable();
                $table->integer('FolVta')->nullable();
                $table->integer('FolPed')->nullable();
                $table->integer('FolDevol')->nullable();
                $table->integer('FolCob')->nullable();
                $table->integer('UDiaO')->nullable();
                $table->string('CteNvo')->nullable();
                $table->string('IdEmpresa')->nullable();
                $table->integer('FolGto')->nullable();
                $table->integer('FolServicio')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `continuidad` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('continuidad');
    }
};