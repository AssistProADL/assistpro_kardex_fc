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
        if (!Schema::hasTable('td_recorrido_reavastecimiento')) {
            Schema::create('td_recorrido_reavastecimiento', function (Blueprint $table) {
                $table->integer('Id');
                $table->string('Folio')->nullable();
                $table->integer('Idy_Ubica');
                $table->integer('Secuencia')->nullable();
                $table->string('Cve_Articulo')->nullable();
                $table->string('Cve_Lote')->nullable();
                $table->timestamp('Fec_Caducidad')->nullable();
                $table->decimal('Tomadas')->nullable();
                $table->decimal('Colocadas')->nullable();
                $table->string('Status')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `td_recorrido_reavastecimiento` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_recorrido_reavastecimiento');
    }
};