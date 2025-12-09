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
        if (!Schema::hasTable('c_destinatarios')) {
            Schema::create('c_destinatarios', function (Blueprint $table) {
                $table->integer('id_destinatario');
                $table->string('Cve_Clte')->nullable();
                $table->string('razonsocial')->nullable();
                $table->string('direccion')->nullable();
                $table->string('colonia')->nullable();
                $table->string('postal')->nullable();
                $table->string('ciudad')->nullable();
                $table->string('estado')->nullable();
                $table->string('contacto')->nullable();
                $table->string('telefono')->nullable();
                $table->string('Activo');
                $table->string('clave_destinatario')->nullable();
                $table->string('cve_vendedor')->nullable();
                $table->string('email_destinatario')->nullable();
                $table->text('latitud')->nullable();
                $table->text('longitud')->nullable();
                $table->integer('dir_principal')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_destinatarios` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_destinatarios');
    }
};