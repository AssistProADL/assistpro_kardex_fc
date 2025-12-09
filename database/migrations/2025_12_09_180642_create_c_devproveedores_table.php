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
        if (!Schema::hasTable('c_devproveedores')) {
            Schema::create('c_devproveedores', function (Blueprint $table) {
                $table->integer('id');
                $table->string('folio_dev')->nullable();
                $table->string('folio_entrada')->nullable();
                $table->string('cve_contenedor')->nullable();
                $table->string('cve_articulo')->nullable();
                $table->string('cve_lote')->nullable();
                $table->date('caducidad')->nullable();
                $table->string('devueltas')->nullable();
                $table->string('idy_ubica')->nullable();
                $table->integer('proveedor')->nullable();
                $table->string('factura')->nullable();
                $table->string('usuario')->nullable();
                $table->integer('defectuoso')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_devproveedores` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_devproveedores');
    }
};