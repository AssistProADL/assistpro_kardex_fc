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
        if (!Schema::hasTable('user')) {
            Schema::create('user', function (Blueprint $table) {
                $table->integer('id_user');
                $table->string('name')->nullable();
                $table->string('email')->nullable();
                $table->string('password')->nullable();
                $table->string('identifier')->nullable();
                $table->string('country')->nullable();
                $table->integer('emails_allowed');
                $table->date('due_date')->nullable();
                $table->string('settings_company')->nullable();
                $table->text('settings_description')->nullable();
                $table->string('settings_press_contact')->nullable();
                $table->text('settings_logo')->nullable();
                $table->string('settings_primary_color')->nullable();
                $table->string('settings_secondary_color')->nullable();
                $table->integer('timestamp');
                $table->text('subdomain')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `user` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user');
    }
};