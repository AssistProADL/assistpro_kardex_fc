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
        if (!Schema::hasTable('venta')) {
            Schema::create('venta', function (Blueprint $table) {
                $table->integer('Id');
                $table->integer('RutaId')->nullable();
                $table->integer('VendedorId');
                $table->string('CodCliente')->nullable();
                $table->string('Documento')->nullable();
                $table->timestamp('Fecha')->nullable();
                $table->string('TipoVta')->nullable();
                $table->integer('DiasCred')->nullable();
                $table->decimal('CreditoDispo')->nullable();
                $table->decimal('Saldo')->nullable();
                $table->date('Fvence')->nullable();
                $table->decimal('SubTotal')->nullable();
                $table->decimal('IVA')->nullable();
                $table->decimal('IEPS')->nullable();
                $table->decimal('TOTAL')->nullable();
                $table->string('EnLetra')->nullable();
                $table->decimal('Items')->nullable();
                $table->integer('FormaPag')->nullable();
                $table->string('DocSalida')->nullable();
                $table->integer('DiaO')->nullable();
                $table->string('Cancelada')->nullable();
                $table->decimal('Kg')->nullable();
                $table->integer('ID_Ayudante1')->nullable();
                $table->integer('ID_Ayudante2')->nullable();
                $table->integer('VenAyunate')->nullable();
                $table->string('IdEmpresa')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `venta` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venta');
    }
};