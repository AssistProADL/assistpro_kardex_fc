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
        if (!Schema::hasTable('vh_pedido_ola')) {
            Schema::create('vh_pedido_ola', function (Blueprint $table) {
                $table->string('Fol_folio')->nullable();
                $table->date('Fec_Pedido')->nullable();
                $table->string('Cve_clte')->nullable();
                $table->string('status')->nullable();
                $table->date('Fec_Entrega')->nullable();
                $table->string('cve_Vendedor')->nullable();
                $table->integer('Num_Meses')->nullable();
                $table->text('Observaciones')->nullable();
                $table->integer('ID_Tipoprioridad')->nullable();
                $table->timestamp('Fec_Entrada')->nullable();
                $table->string('TipoPedido')->nullable();
                $table->boolean('bloqueado')->nullable();
                $table->string('cve_almac')->nullable();
                $table->string('Pick_Num')->nullable();
                $table->string('Cve_Usuario')->nullable();
                $table->string('Ship_Num')->nullable();
                $table->string('Cve_CteProv')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `vh_pedido_ola` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vh_pedido_ola');
    }
};