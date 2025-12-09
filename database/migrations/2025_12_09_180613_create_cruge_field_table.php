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
        if (!Schema::hasTable('cruge_field')) {
            Schema::create('cruge_field', function (Blueprint $table) {
                $table->integer('idfield');
                $table->string('fieldname')->nullable();
                $table->string('longname')->nullable();
                $table->integer('position')->nullable();
                $table->integer('required')->nullable();
                $table->integer('fieldtype')->nullable();
                $table->integer('fieldsize')->nullable();
                $table->integer('maxlength')->nullable();
                $table->integer('showinreports')->nullable();
                $table->text('useregexp')->nullable();
                $table->text('useregexpmsg')->nullable();
                $table->text('predetvalue')->nullable();
                $table->integer('Activo')->nullable();
                $table->timestamps(); // created_at y updated_at
            });
            
            // Set table engine, charset and collation (from database defaults)
            DB::statement("ALTER TABLE `cruge_field` ENGINE = InnoDB, CHARSET = utf8mb4, COLLATE = utf8mb4_unicode_ci");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cruge_field');
    }
};