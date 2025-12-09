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
        if (!Schema::hasTable('t_eda_sessions')) {
            Schema::create('t_eda_sessions', function (Blueprint $table) {
                $table->integer('IdSession');
                $table->string('Usuario')->nullable();
                $table->string('IMEI')->nullable();
                $table->string('Cve_Almac')->nullable();
                $table->timestamp('Fecha')->nullable();
                $table->integer('Expira')->nullable();
                $table->boolean('Activo')->nullable();
                $table->string('Proceso')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_eda_sessions` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_eda_sessions');
    }
};