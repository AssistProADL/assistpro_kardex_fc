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
        if (!Schema::hasTable('c_usuario')) {
            Schema::create('c_usuario', function (Blueprint $table) {
                $table->integer('id_user');
                $table->string('cve_usuario')->nullable();
                $table->integer('cve_cia')->nullable();
                $table->string('nombre_completo')->nullable();
                $table->string('email')->nullable();
                $table->string('perfil')->nullable();
                $table->text('des_usuario');
                $table->timestamp('fec_ingreso')->nullable();
                $table->string('pwd_usuario')->nullable();
                $table->boolean('ban_usuario');
                $table->string('status')->nullable();
                $table->integer('Activo')->nullable();
                $table->integer('timestamp')->nullable();
                $table->string('identifier')->nullable();
                $table->string('image_url')->nullable();
                $table->integer('es_cliente')->nullable();
                $table->string('cve_almacen')->nullable();
                $table->string('cve_cliente')->nullable();
                $table->string('cve_proveedor')->nullable();
                $table->string('Id_Fcm')->nullable();
                $table->string('web_apk')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_usuario` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_usuario');
    }
};