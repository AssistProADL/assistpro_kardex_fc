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
        if (!Schema::hasTable('td_pedido')) {
            Schema::create('td_pedido', function (Blueprint $table) {
                $table->integer('id');
                $table->string('Fol_folio');
                $table->string('Cve_articulo');
                $table->string('Num_cantidad')->nullable();
                $table->integer('id_unimed')->nullable();
                $table->integer('Num_Meses')->nullable();
                $table->integer('SurtidoXCajas')->nullable();
                $table->integer('SurtidoXPiezas')->nullable();
                $table->string('status')->nullable();
                $table->string('Cve_Cot')->nullable();
                $table->string('factor')->nullable();
                $table->integer('itemPos')->nullable();
                $table->string('cve_lote')->nullable();
                $table->string('Num_revisadas');
                $table->string('Num_Empacados')->nullable();
                $table->integer('Activo')->nullable();
                $table->string('Auditado')->nullable();
                $table->string('Precio_unitario')->nullable();
                $table->string('Desc_Importe')->nullable();
                $table->string('IVA')->nullable();
                $table->integer('id_ot')->nullable();
                $table->string('Cve_Almac_Ori')->nullable();
                $table->date('Fec_Entrega')->nullable();
                $table->decimal('Valor_Expo')->nullable();
                $table->decimal('Valor_Comercial_MN')->nullable();
                $table->decimal('Valor_Aduana')->nullable();
                $table->decimal('Valor_Comercial_DLL')->nullable();
                $table->string('Proyecto')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `td_pedido` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_pedido');
    }
};