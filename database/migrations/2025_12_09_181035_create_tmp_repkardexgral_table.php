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
        if (!Schema::hasTable('tmp_repkardexgral')) {
            Schema::create('tmp_repkardexgral', function (Blueprint $table) {
                $table->integer('id');
                $table->timestamp('FECHA')->nullable();
                $table->string('ARTICULO')->nullable();
                $table->string('NOMBRE')->nullable();
                $table->string('LOTE')->nullable();
                $table->string('caducidad')->nullable();
                $table->integer('CANTIDAD')->nullable();
                $table->string('TIPO DE MOVIMIENTO')->nullable();
                $table->string('ORIGEN')->nullable();
                $table->string('DESTINO')->nullable();
                $table->string('USUARIO')->nullable();
                $table->integer('MODO')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `tmp_repkardexgral` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tmp_repkardexgral');
    }
};