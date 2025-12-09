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
        if (!Schema::hasTable('cruge_user')) {
            Schema::create('cruge_user', function (Blueprint $table) {
                $table->integer('iduser');
                $table->bigInteger('regdate')->nullable();
                $table->bigInteger('actdate')->nullable();
                $table->bigInteger('logondate')->nullable();
                $table->string('username')->nullable();
                $table->string('email')->nullable();
                $table->string('password')->nullable();
                $table->string('authkey')->nullable();
                $table->integer('state')->nullable();
                $table->integer('totalsessioncounter')->nullable();
                $table->integer('currentsessioncounter')->nullable();
                $table->integer('id_departamento')->nullable();
                $table->string('image_profile')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `cruge_user` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cruge_user');
    }
};