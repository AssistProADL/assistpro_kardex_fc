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
        if (!Schema::hasTable('tmp_inventconteo')) {
            Schema::create('tmp_inventconteo', function (Blueprint $table) {
                $table->integer('Id_Inventario')->nullable();
                $table->integer('NConteo')->nullable();
                $table->integer('Idy_Ubica')->nullable();
                $table->string('Cve_Articulo')->nullable();
                $table->string('Cve_Lote')->nullable();
                $table->decimal('CantTeorica')->nullable();
                $table->decimal('Cant1')->nullable();
                $table->decimal('Cant2')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `tmp_inventconteo` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tmp_inventconteo');
    }
};