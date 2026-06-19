<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE history_logs MODIFY event_type ENUM('promotion','demotion','warning','achievement','data_import','rejection','violation') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE history_logs MODIFY event_type ENUM('promotion','demotion','warning','achievement','data_import') NOT NULL");
    }
};
