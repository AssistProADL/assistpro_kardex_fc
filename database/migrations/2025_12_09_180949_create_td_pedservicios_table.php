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
        if (!Schema::hasTable('td_pedservicios')) {
            Schema::create('td_pedservicios', function (Blueprint $table) {
                $table->integer('id');
                $table->string('Fol_Folio');
                $table->string('Cve_Almac')->nullable();
                $table->string('Cve_Servicio');
                $table->decimal('Num_cantidad')->nullable();
                $table->integer('id_unimed')->nullable();
                $table->string('status')->nullable();
                $table->integer('itemPos')->nullable();
                $table->integer('Activo')->nullable();
                $table->string('Precio_unitario')->nullable();
                $table->string('Desc_Importe')->nullable();
                $table->string('IVA')->nullable();
                $table->integer('Id_Moneda');
                $table->string('Referencia');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `td_pedservicios` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_pedservicios');
    }
};