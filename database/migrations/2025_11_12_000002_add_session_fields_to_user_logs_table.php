<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_logs', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('event_type');
            $table->text('user_agent')->nullable()->after('ip_address');
        });

        // Modify the event_type enum to include new session events
        DB::statement("ALTER TABLE `user_logs` MODIFY COLUMN `event_type` ENUM('login', 'logout', 'failed_login', 'session_revoked', 'all_sessions_revoked', 'bulk_sessions_revoked') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_logs', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'user_agent']);
        });

        // Revert event_type enum to original values
        DB::statement("ALTER TABLE `user_logs` MODIFY COLUMN `event_type` ENUM('login', 'logout', 'failed_login') NOT NULL");
    }
};
