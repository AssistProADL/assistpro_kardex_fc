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
        if (!Schema::hasTable('t_patio_doclink')) {
            Schema::create('t_patio_doclink', function (Blueprint $table) {
                $table->integer('id_doclink');
                $table->integer('id_visita');
                $table->string('sistema_origen')->default('ER');
                $table->string('tipo_doc');
                $table->string('tabla_origen');
                $table->integer('id_origen');
                $table->string('folio_origen')->nullable();
                $table->integer('proveedor_id')->nullable();
                $table->decimal('monto_total')->nullable();
                $table->string('estado_sync')->nullable()->default('PENDIENTE');
                $table->string('usuario_vincula')->nullable();
                $table->timestamp('fecha_vincula')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
                $table->unique(['id_visita', 'tipo_doc', 'tabla_origen', 'id_origen'], 'id_visita');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_patio_doclink` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_patio_doclink');
    }
};