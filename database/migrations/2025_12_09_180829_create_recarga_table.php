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
        if (!Schema::hasTable('recarga')) {
            Schema::create('recarga', function (Blueprint $table) {
                $table->integer('ID');
                $table->integer('IdRuta');
                $table->string('Articulo')->nullable();
                $table->decimal('Cantidad')->nullable();
                $table->timestamp('Fecha')->nullable();
                $table->integer('Diao')->nullable();
                $table->string('IdEmpresa')->nullable();
                $table->string('Folio')->nullable();
                $table->timestamp('Hora')->nullable();
                $table->decimal('Surtidas')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `recarga` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recarga');
    }
};