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
        if (!Schema::hasTable('etl_object_meta')) {
            Schema::create('etl_object_meta', function (Blueprint $table) {
                $table->id();
                $table->string('alias');
                $table->string('remote_db');
                $table->string('object_name');
                $table->text('comment')->nullable();
                $table->text('procesos')->nullable();
                $table->timestamp('created_at')->default('CURRENT_TIMESTAMP');
                $table->timestamp('updated_at')->nullable();
                $table->timestamp('last_action_at')->nullable();
                $table->string('last_action_type')->nullable();
                $table->integer('last_action_rows')->nullable();
                $table->string('dest_table')->nullable();
                $table->boolean('auto_update')->default('0');
                $table->string('auto_update_cron')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->unique(['alias', 'remote_db', 'object_name'], 'uq_meta');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `etl_object_meta` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etl_object_meta');
    }
};