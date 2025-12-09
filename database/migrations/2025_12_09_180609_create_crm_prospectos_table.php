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
        if (!Schema::hasTable('crm_prospectos')) {
            Schema::create('crm_prospectos', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('empresa')->nullable();
                $table->string('email')->nullable();
                $table->string('telefono')->nullable();
                $table->text('comentarios')->nullable();
                $table->string('origen')->default('E-COMMERCE');
                $table->integer('cliente_id')->nullable();
                $table->timestamp('fecha_alta')->default('CURRENT_TIMESTAMP');
                $table->string('status')->default('NUEVO');
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `crm_prospectos` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_prospectos');
    }
};