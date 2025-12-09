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
        if (!Schema::hasTable('c_lotes')) {
            Schema::create('c_lotes', function (Blueprint $table) {
                $table->integer('id');
                $table->string('cve_articulo')->nullable();
                $table->string('Lote')->nullable();
                $table->date('Caducidad');
                $table->integer('Activo')->nullable();
                $table->date('Fec_Prod')->nullable();
                $table->string('Lote_Alterno')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_lotes` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_lotes');
    }
};