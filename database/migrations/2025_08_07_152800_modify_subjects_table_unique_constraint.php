<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the existing unique index on subject_code
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropUnique('subjects_subject_code_unique');
        });

        // Add a new composite unique index on subject_code and academic_period_id
        Schema::table('subjects', function (Blueprint $table) {
            $table->unique(['subject_code', 'academic_period_id'], 'subjects_subject_code_academic_period_id_unique');
        });
    }

    public function down(): void
    {
        // Drop the composite unique index
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropUnique('subjects_subject_code_academic_period_id_unique');
        });

        // Re-add the original unique index on subject_code
        Schema::table('subjects', function (Blueprint $table) {
            $table->unique('subject_code');
        });
    }
};
