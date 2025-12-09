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
        if (!Schema::hasTable('s_dispositivos')) {
            Schema::create('s_dispositivos', function (Blueprint $table) {
                $table->id();
                $table->integer('id_almacen');
                $table->string('tipo');
                $table->string('marca')->nullable();
                $table->string('modelo')->nullable();
                $table->string('serie')->nullable();
                $table->string('imei')->nullable();
                $table->string('firmware_version')->nullable();
                $table->string('mac_wifi')->nullable();
                $table->string('mac_bt')->nullable();
                $table->string('ip')->nullable();
                $table->string('usuario_asignado')->nullable();
                $table->string('estatus')->nullable()->default('ACTIVO');
                $table->timestamp('fecha_alta')->nullable()->default('CURRENT_TIMESTAMP');
                $table->text('comentarios')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
                $table->index('id_almacen', 'id_almacen');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `s_dispositivos` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('s_dispositivos');
    }
};