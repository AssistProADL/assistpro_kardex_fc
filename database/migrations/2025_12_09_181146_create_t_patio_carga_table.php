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
        if (!Schema::hasTable('t_patio_carga')) {
            Schema::create('t_patio_carga', function (Blueprint $table) {
                $table->integer('id_carga');
                $table->integer('id_visita');
                $table->string('tipo_operacion');
                $table->string('sistema_origen')->default('ER');
                $table->string('tabla_doc_origen')->nullable();
                $table->integer('id_doc_origen')->nullable();
                $table->string('folio_doc_origen')->nullable();
                $table->timestamp('fecha_inicio')->nullable();
                $table->timestamp('fecha_fin')->nullable();
                $table->string('estatus')->nullable()->default('PENDIENTE');
                $table->text('comentario')->nullable();
                $table->string('usuario_inicia')->nullable();
                $table->string('usuario_cierra')->nullable();
                $table->string('usuario_cancela')->nullable();
                $table->timestamp('fecha_cancela')->nullable();
                $table->integer('id_doclink')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
                $table->index('id_doclink', 'ix_patio_carga_doclink');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_patio_carga` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_patio_carga');
    }
};