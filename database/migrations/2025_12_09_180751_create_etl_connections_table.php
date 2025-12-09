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
        if (!Schema::hasTable('etl_connections')) {
            Schema::create('etl_connections', function (Blueprint $table) {
                $table->id();
                $table->string('alias');
                $table->string('friendly_name');
                $table->string('tipo');
                $table->string('host')->nullable();
                $table->integer('puerto')->nullable();
                $table->string('db_name')->nullable();
                $table->string('username')->nullable();
                $table->string('password')->nullable();
                $table->string('opciones_json')->nullable();
                $table->string('file_path')->nullable();
                $table->boolean('activo')->default('1');
                $table->timestamp('created_at')->default('CURRENT_TIMESTAMP');
                $table->timestamp('actualizado_en')->nullable();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->unique('alias', 'alias');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `etl_connections` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etl_connections');
    }
};