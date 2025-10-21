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
        Schema::table('g_e_subject_requests', function (Blueprint $table) {
            // Modify the status ENUM to include 'revoked'
            $table->enum('status', ['pending', 'approved', 'rejected', 'revoked'])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('g_e_subject_requests', function (Blueprint $table) {
            // Revert back to original ENUM values
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->change();
        });
    }
};
