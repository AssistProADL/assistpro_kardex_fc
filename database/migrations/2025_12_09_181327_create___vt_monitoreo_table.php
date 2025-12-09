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
        if (!Schema::hasTable('__vt_monitoreo')) {
            Schema::create('__vt_monitoreo', function (Blueprint $table) {
                $table->bigInteger('id');
                $table->string('id_pedido')->nullable();
                $table->string('fol_folio')->nullable();
                $table->timestamp('fec_pedido')->nullable();
                $table->string('status')->nullable();
                $table->string('descripcion')->nullable();
                $table->string('postal')->nullable();
                $table->string('direccion')->nullable();
                $table->string('colonia')->nullable();
                $table->string('ciudad')->nullable();
                $table->string('estado')->nullable();
                $table->string('guia')->nullable();
                $table->timestamp('fec_recoleccion')->nullable();
                $table->timestamp('fec_entrega')->nullable();
                $table->string('recibe')->nullable();
                $table->string('serv_status')->nullable();
                $table->timestamp('hora_inicio')->nullable();
                $table->timestamp('hir')->nullable();
                $table->timestamp('fi_emp')->nullable();
                $table->timestamp('hie')->nullable();
                $table->string('u_asig')->nullable();
                $table->string('u_empa')->nullable();
                $table->string('u_revi')->nullable();
                $table->string('guia_caja')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `__vt_monitoreo` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('__vt_monitoreo');
    }
};