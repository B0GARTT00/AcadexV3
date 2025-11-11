<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('structure_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_key')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->json('structure_config');
            $table->boolean('is_system_default')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('template_key');
            $table->index('is_deleted');
            $table->index('is_system_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('structure_templates');
    }
};
