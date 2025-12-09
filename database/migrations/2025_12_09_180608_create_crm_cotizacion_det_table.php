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
        if (!Schema::hasTable('crm_cotizacion_det')) {
            Schema::create('crm_cotizacion_det', function (Blueprint $table) {
                $table->id();
                $table->integer('cotizacion_id');
                $table->string('cve_articulo');
                $table->string('descripcion')->nullable();
                $table->decimal('cantidad');
                $table->decimal('precio_unitario');
                $table->decimal('subtotal');
                $table->decimal('existencia')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
                $table->index('cotizacion_id', 'fk_crm_cot_det_cot');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `crm_cotizacion_det` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_cotizacion_det');
    }
};