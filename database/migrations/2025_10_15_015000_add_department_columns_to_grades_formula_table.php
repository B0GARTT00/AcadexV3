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
        if (! Schema::hasColumn('grades_formula', 'department_id')) {
            Schema::table('grades_formula', function (Blueprint $table) {
                $table->foreignId('department_id')
                    ->nullable()
                    ->after('name')
                    ->constrained('departments')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('grades_formula', 'label')) {
            Schema::table('grades_formula', function (Blueprint $table) {
                $table->string('label')->nullable()->after('department_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('grades_formula', 'label')) {
            Schema::table('grades_formula', function (Blueprint $table) {
                $table->dropColumn('label');
            });
        }

        if (Schema::hasColumn('grades_formula', 'department_id')) {
            Schema::table('grades_formula', function (Blueprint $table) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            });
        }
    }
};
