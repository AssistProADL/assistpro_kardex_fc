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
        if (!Schema::hasTable('td_aduana')) {
            Schema::create('td_aduana', function (Blueprint $table) {
                $table->integer('Id_DetAduana');
                $table->integer('ID_Aduana');
                $table->string('cve_articulo')->nullable();
                $table->string('cantidad')->nullable();
                $table->string('Cve_Lote')->nullable();
                $table->timestamp('caducidad')->nullable();
                $table->string('temperatura')->nullable();
                $table->integer('num_orden')->nullable();
                $table->integer('Ingresado')->nullable();
                $table->integer('Activo')->nullable()->default('1');
                $table->string('costo')->nullable()->default('0.00');
                $table->decimal('IVA')->nullable();
                $table->string('Item')->nullable();
                $table->integer('Id_UniMed')->nullable();
                $table->date('Fec_Entrega')->nullable();
                $table->string('Ref_Docto')->nullable();
                $table->decimal('Peso')->nullable();
                $table->string('MarcaNumTotBultos')->nullable();
                $table->string('Factura')->nullable();
                $table->date('Fec_Factura')->nullable();
                $table->string('Contenedores')->nullable();
                $table->timestamps(); // created_at y updated_at
                $table->index('num_orden', 'FK_td_aduana_th_aduana');
                $table->unique(['cve_articulo', 'Cve_Lote', 'num_orden'], 'Idx_td_aduana');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `td_aduana` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_aduana');
    }
};