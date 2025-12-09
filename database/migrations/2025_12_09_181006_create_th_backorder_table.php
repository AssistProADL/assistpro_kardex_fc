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
        if (!Schema::hasTable('th_backorder')) {
            Schema::create('th_backorder', function (Blueprint $table) {
                $table->string('Folio_BackO');
                $table->string('Fol_Folio')->nullable();
                $table->string('Cve_Clte')->nullable();
                $table->timestamp('Fec_Pedido')->nullable();
                $table->timestamp('Fec_Entrega')->nullable();
                $table->timestamp('Fec_BO')->nullable();
                $table->string('Pick_num')->nullable();
                $table->string('Status')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `th_backorder` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('th_backorder');
    }
};