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
        Schema::create('grades_formula', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('base_score', 5, 2);
            $table->decimal('scale_multiplier', 5, 2);
            $table->decimal('passing_grade', 5, 2)->default(75);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades_formula');
    }
};
