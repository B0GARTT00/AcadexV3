<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->enum('term', ['prelim', 'midterm', 'prefinal', 'final']);
            $table->enum('type', ['quiz', 'ocr', 'exam']);
            $table->string('title');
            $table->unsignedBigInteger('course_outcome_id')->nullable();
            $table->foreign('course_outcome_id')->references('id')->on('course_outcomes')->onDelete('set null');
            $table->integer('number_of_items');
            $table->boolean('is_deleted')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};