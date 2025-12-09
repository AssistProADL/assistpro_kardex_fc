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
        if (!Schema::hasTable('etl_process_docs')) {
            Schema::create('etl_process_docs', function (Blueprint $table) {
                $table->id();
                $table->integer('process_id');
                $table->string('title');
                $table->string('url')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->default('CURRENT_TIMESTAMP');
                $table->string('file_name')->nullable();
                $table->string('file_path')->nullable();
                $table->string('mime_type')->nullable();
                $table->bigInteger('file_size')->nullable();
                $table->tinyInteger('Activo')->default(1)->comment('Estado activo/inactivo');
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->index('process_id', 'fk_proc_docs_process');
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `etl_process_docs` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etl_process_docs');
    }
};