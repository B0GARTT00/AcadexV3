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
        Schema::table('sessions', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable()->after('last_activity');
            $table->string('device_type')->nullable()->after('user_agent');
            $table->string('browser')->nullable()->after('device_type');
            $table->string('platform')->nullable()->after('browser');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn(['last_activity_at', 'device_type', 'browser', 'platform']);
        });
    }
};
