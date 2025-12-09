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
        if (!Schema::hasTable('cruge_session')) {
            Schema::create('cruge_session', function (Blueprint $table) {
                $table->integer('idsession');
                $table->integer('iduser');
                $table->bigInteger('created')->nullable();
                $table->bigInteger('expire')->nullable();
                $table->integer('status')->nullable();
                $table->string('ipaddress')->nullable();
                $table->integer('usagecount')->nullable();
                $table->bigInteger('lastusage')->nullable();
                $table->bigInteger('logoutdate')->nullable();
                $table->string('ipaddressout')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `cruge_session` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cruge_session');
    }
};