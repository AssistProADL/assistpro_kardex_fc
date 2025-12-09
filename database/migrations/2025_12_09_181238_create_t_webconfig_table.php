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
        if (!Schema::hasTable('t_webconfig')) {
            Schema::create('t_webconfig', function (Blueprint $table) {
                $table->integer('id');
                $table->string('servidor')->nullable();
                $table->string('usuario')->nullable();
                $table->string('ContraseÃƒÆ’Ã‚Â±a')->nullable();
                $table->integer('puerto')->nullable();
                $table->string('Asunto')->nullable();
                $table->text('Mensaje')->nullable();
                $table->text('Destinatario')->nullable();
                $table->text('CC')->nullable();
                $table->text('BCC')->nullable();
                $table->integer('Id_Mail');
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `t_webconfig` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_webconfig');
    }
};