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
        Schema::create('course_outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('academic_period_id')->constrained('academic_periods')->onDelete('cascade');
            $table->string('co_code', 10); // CO1, CO2, CO3, etc.
            $table->string('co_identifier', 20); // IT 101.1, IT 101.2, etc.
            $table->text('description'); // Course outcome description
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->constrained('users')->onDelete('cascade');
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
            
            // Add unique constraint to prevent duplicate CO codes per subject
            $table->unique(['subject_id', 'co_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_outcomes');
    }
};
