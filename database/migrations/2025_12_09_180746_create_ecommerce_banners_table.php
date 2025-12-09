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
        if (!Schema::hasTable('ecommerce_banners')) {
            Schema::create('ecommerce_banners', function (Blueprint $table) {
                $table->id();
                $table->string('titulo')->nullable();
                $table->string('subtitulo')->nullable();
                $table->string('imagen');
                $table->string('url_destino')->nullable();
                $table->boolean('activo')->default('1');
                $table->integer('orden')->default('0');
                $table->timestamp('fecha_alta')->default('CURRENT_TIMESTAMP');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `ecommerce_banners` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_banners');
    }
};