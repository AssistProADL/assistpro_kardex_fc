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
        if (!Schema::hasTable('emp_paqueteria')) {
            Schema::create('emp_paqueteria', function (Blueprint $table) {
                $table->decimal('Id_Empresa')->nullable();
                $table->string('No_Cliente')->nullable();
                $table->string('Usuario')->nullable();
                $table->string('Password')->nullable();
                $table->string('No_Suscriptor')->nullable();
                $table->string('Contacto')->nullable();
                $table->string('Telefono')->nullable();
                $table->string('Tel_Celular')->nullable();
                $table->integer('serviceTypeId')->nullable();
                $table->integer('Id_EstatusGuias')->nullable();
                $table->string('Usuario_EstatusGuias')->nullable();
                $table->string('Pswd_EstatusGuias')->nullable();
                $table->integer('Tiempo_Actualizacion')->nullable();
                $table->string('Operador_Logistico')->nullable();
                $table->string('officeNum')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `emp_paqueteria` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emp_paqueteria');
    }
};