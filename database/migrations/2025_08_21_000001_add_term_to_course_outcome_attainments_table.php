<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('course_outcome_attainments', function (Blueprint $table) {
            // Check if the column doesn't already exist before adding it
            if (!Schema::hasColumn('course_outcome_attainments', 'term')) {
                $table->string('term')->after('student_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('course_outcome_attainments', function (Blueprint $table) {
            // Only drop the column if it exists
            if (Schema::hasColumn('course_outcome_attainments', 'term')) {
                $table->dropColumn('term');
            }
        });
    }
};
