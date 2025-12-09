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
        if (!Schema::hasTable('t_ordenprod')) {
            Schema::create('t_ordenprod', function (Blueprint $table) {
                $table->integer('id');
                $table->string('Folio_Pro');
                $table->string('FolioImport')->nullable();
                $table->string('cve_almac')->nullable();
                $table->integer('ID_Proveedor')->nullable();
                $table->string('Cve_Articulo')->nullable();
                $table->string('Cve_Lote')->nullable();
                $table->string('Cantidad')->nullable();
                $table->integer('Cant_Prod')->nullable();
                $table->string('Cve_Usuario')->nullable();
                $table->timestamp('Fecha')->nullable();
                $table->timestamp('FechaReg')->nullable();
                $table->string('Usr_Armo')->nullable();
                $table->timestamp('Hora_Ini')->nullable();
                $table->timestamp('Hora_Fin')->nullable();
                $table->string('cronometro')->nullable();
                $table->integer('id_umed')->nullable();
                $table->string('Status')->nullable();
                $table->string('Referencia')->nullable();
                $table->string('Cve_Almac_Ori')->nullable();
                $table->string('Tipo')->nullable();
                $table->integer('id_zona_almac')->nullable();
                $table->integer('idy_ubica')->nullable();
                $table->integer('idy_ubica_dest')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
                $table->index('FolioImport', 'idx_t_ordenprod_folioimport');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_ordenprod` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_ordenprod');
    }
};