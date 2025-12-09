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
        if (!Schema::hasTable('t_menu__')) {
            Schema::create('t_menu__', function (Blueprint $table) {
                $table->bigInteger('id_menu');
                $table->string('modulo')->nullable();
                $table->string('icono')->nullable();
                $table->string('href')->nullable();
                $table->decimal('id_menu_padre')->nullable();
                $table->boolean('orden')->nullable();
                $table->integer('orden_screen')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_menu__` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_menu__');
    }
};