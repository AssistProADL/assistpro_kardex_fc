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
        if (!Schema::hasTable('c_ws_conexion')) {
            Schema::create('c_ws_conexion', function (Blueprint $table) {
                $table->id();
                $table->string('sistema');
                $table->string('nombre_conexion');
                $table->string('url_base');
                $table->string('tipo_auth')->default('SERVICELAYER');
                $table->string('company_db')->nullable();
                $table->string('usuario_ws')->nullable();
                $table->string('password_ws')->nullable();
                $table->string('token_ws')->nullable();
                $table->string('observaciones')->nullable();
                $table->boolean('activo')->default('1');
                $table->timestamp('fecha_crea')->default('CURRENT_TIMESTAMP');
                $table->string('usuario_crea')->nullable();
                $table->timestamp('fecha_mod')->nullable();
                $table->string('usuario_mod')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_ws_conexion` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_ws_conexion');
    }
};