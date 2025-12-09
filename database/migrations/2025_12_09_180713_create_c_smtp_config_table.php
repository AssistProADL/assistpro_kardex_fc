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
        if (!Schema::hasTable('c_smtp_config')) {
            Schema::create('c_smtp_config', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('host');
                $table->integer('puerto');
                $table->string('seguridad')->default('tls');
                $table->string('usuario');
                $table->string('password');
                $table->string('from_email');
                $table->string('from_name');
                $table->string('aliases_json')->nullable();
                $table->boolean('activo')->default('1');
                $table->timestamp('fecha_creacion')->default('CURRENT_TIMESTAMP');
                $table->timestamp('fecha_actualiza')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_smtp_config` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_smtp_config');
    }
};