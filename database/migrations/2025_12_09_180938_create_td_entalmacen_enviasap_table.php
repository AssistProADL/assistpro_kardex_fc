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
        if (!Schema::hasTable('td_entalmacen_enviasap')) {
            Schema::create('td_entalmacen_enviasap', function (Blueprint $table) {
                $table->integer('Id');
                $table->integer('Fol_Folio');
                $table->string('Cve_Articulo')->nullable();
                $table->string('Cve_lote')->nullable();
                $table->decimal('Cant_Rec')->nullable();
                $table->string('Item')->nullable();
                $table->timestamp('Fec_Envio')->nullable();
                $table->boolean('Enviado')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `td_entalmacen_enviasap` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_entalmacen_enviasap');
    }
};