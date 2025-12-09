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
        if (!Schema::hasTable('crm_cotizacion')) {
            Schema::create('crm_cotizacion', function (Blueprint $table) {
                $table->id();
                $table->string('folio_cotizacion');
                $table->timestamp('fecha');
                $table->integer('id_cliente')->nullable();
                $table->string('cve_clte')->nullable();
                $table->integer('fuente_id')->nullable();
                $table->string('fuente_detalle')->nullable();
                $table->decimal('total')->nullable();
                $table->string('estado')->default('BORRADOR');
                $table->timestamp('creado_en')->default('CURRENT_TIMESTAMP');
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `crm_cotizacion` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_cotizacion');
    }
};