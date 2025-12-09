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
        if (!Schema::hasTable('c_sucursal')) {
            Schema::create('c_sucursal', function (Blueprint $table) {
                $table->integer('id');
                $table->integer('cve_cia');
                $table->string('distrito')->nullable();
                $table->string('des_cia')->nullable();
                $table->string('des_rfc')->nullable();
                $table->string('des_direcc')->nullable();
                $table->string('des_cp')->nullable();
                $table->string('des_telef')->nullable();
                $table->string('des_contacto')->nullable();
                $table->string('des_email')->nullable();
                $table->string('des_observ')->nullable();
                $table->integer('Activo')->nullable();
                $table->string('imagen')->nullable();
                $table->string('clave_sucursal')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_sucursal` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_sucursal');
    }
};