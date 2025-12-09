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
        if (!Schema::hasTable('c_ubicacion')) {
            Schema::create('c_ubicacion', function (Blueprint $table) {
                $table->integer('idy_ubica');
                $table->integer('cve_almac');
                $table->string('cve_pasillo')->nullable();
                $table->string('cve_rack')->nullable();
                $table->string('cve_nivel')->nullable();
                $table->decimal('num_ancho')->nullable();
                $table->decimal('num_largo')->nullable();
                $table->decimal('num_alto')->nullable();
                $table->decimal('num_volumenDisp')->nullable();
                $table->string('Status')->nullable();
                $table->string('picking')->nullable();
                $table->string('Seccion')->nullable();
                $table->string('Ubicacion')->nullable();
                $table->integer('orden_secuencia')->nullable();
                $table->string('PesoMaximo')->nullable();
                $table->string('PesoOcupado')->nullable();
                $table->string('claverp')->nullable();
                $table->string('CodigoCSD')->nullable();
                $table->string('TECNOLOGIA')->nullable();
                $table->string('Maneja_Cajas')->nullable();
                $table->string('Maneja_Piezas')->nullable();
                $table->string('Reabasto')->nullable();
                $table->integer('Activo')->nullable();
                $table->string('Tipo')->nullable();
                $table->string('AcomodoMixto')->nullable();
                $table->string('AreaProduccion')->nullable();
                $table->string('AreaStagging')->nullable();
                $table->string('Ubicacion_CrossDocking')->nullable()->default('N');
                $table->string('Staging_Pedidos')->nullable()->default('N');
                $table->string('Ptl')->nullable();
                $table->integer('Maximo')->nullable();
                $table->integer('Minimo')->nullable();
                $table->string('clasif_abc')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `c_ubicacion` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_ubicacion');
    }
};