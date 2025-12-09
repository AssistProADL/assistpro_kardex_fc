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
        if (!Schema::hasTable('t_crm_lead')) {
            Schema::create('t_crm_lead', function (Blueprint $table) {
                $table->integer('id_lead');
                $table->timestamp('fecha_alta')->default('CURRENT_TIMESTAMP');
                $table->string('nombre_contacto');
                $table->string('empresa')->nullable();
                $table->string('telefono')->nullable();
                $table->string('correo')->nullable();
                $table->string('origen')->nullable();
                $table->string('etapa')->nullable()->default('Nuevo');
                $table->string('prioridad')->nullable()->default('Normal');
                $table->text('notas')->nullable();
                $table->string('usuario_asignado')->nullable();
                $table->string('estatus')->nullable()->default('A');
                $table->timestamp('fecha_actualiza')->nullable()->default('CURRENT_TIMESTAMP');
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_crm_lead` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_crm_lead');
    }
};