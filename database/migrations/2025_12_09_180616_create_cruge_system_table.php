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
        if (!Schema::hasTable('cruge_system')) {
            Schema::create('cruge_system', function (Blueprint $table) {
                $table->string('name')->nullable();
                $table->string('largename')->nullable();
                $table->integer('sessionmaxdurationmins')->nullable();
                $table->integer('sessionmaxsameipconnections')->nullable();
                $table->integer('sessionreusesessions')->nullable();
                $table->integer('sessionmaxsessionsperday')->nullable();
                $table->integer('sessionmaxsessionsperuser')->nullable();
                $table->integer('systemnonewsessions')->nullable();
                $table->integer('systemdown')->nullable();
                $table->integer('registerusingcaptcha')->nullable();
                $table->integer('registerusingterms')->nullable();
                $table->string('terms')->nullable();
                $table->integer('registerusingactivation')->nullable();
                $table->string('defaultroleforregistration')->nullable();
                $table->string('registerusingtermslabel')->nullable();
                $table->integer('registrationonlogin')->nullable();
                $table->integer('idsystem');
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `cruge_system` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cruge_system');
    }
};