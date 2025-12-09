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
        if (!Schema::hasTable('t_movcharolas')) {
            Schema::create('t_movcharolas', function (Blueprint $table) {
                $table->id();
                $table->integer('id_kardex')->nullable();
                $table->string('Cve_Almac')->nullable();
                $table->integer('ID_Contenedor')->nullable();
                $table->timestamp('Fecha')->nullable();
                $table->string('Origen')->nullable();
                $table->string('Destino')->nullable();
                $table->integer('Id_TipoMovimiento')->nullable();
                $table->string('Cve_Usuario')->nullable();
                $table->string('Status')->nullable();
                $table->string('EsCaja')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
                $table->index(['id_kardex', 'Cve_Almac', 'ID_Contenedor', 'Destino', 'Id_TipoMovimiento'], 'Idx_MovCharolas_Destino');
                $table->index('Id_TipoMovimiento', 'Fk_T_TipoMov_T_MovCharolas');
                $table->index(['id_kardex', 'Cve_Almac', 'ID_Contenedor', 'Origen', 'Id_TipoMovimiento'], 'Idx_MovCharolas_Origen');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_movcharolas` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_movcharolas');
    }
};