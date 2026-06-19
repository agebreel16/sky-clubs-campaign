<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE agents DROP CONSTRAINT chk_baseline_positive');
        DB::statement('ALTER TABLE agents ADD CONSTRAINT chk_baseline_non_negative CHECK (baseline_count >= 0)');

        DB::statement('ALTER TABLE agents DROP CONSTRAINT chk_current_total_positive');
        DB::statement('ALTER TABLE agents ADD CONSTRAINT chk_current_total_non_negative CHECK (current_total >= 0)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE agents DROP CONSTRAINT chk_baseline_non_negative');
        DB::statement('ALTER TABLE agents ADD CONSTRAINT chk_baseline_positive CHECK (baseline_count > 0)');

        DB::statement('ALTER TABLE agents DROP CONSTRAINT chk_current_total_non_negative');
        DB::statement('ALTER TABLE agents ADD CONSTRAINT chk_current_total_positive CHECK (current_total > 0)');
    }
};
