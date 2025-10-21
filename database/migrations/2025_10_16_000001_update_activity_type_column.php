<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE activities MODIFY type VARCHAR(191) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE activities MODIFY type ENUM('quiz','ocr','exam') NOT NULL");
    }
};
