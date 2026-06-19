<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // إضافة 'deals_api' إلى ENUM
        DB::statement("ALTER TABLE data_imports MODIFY COLUMN source_type ENUM('excel','api','deals_api') NOT NULL DEFAULT 'excel'");

        // تحويل UNIQUE الفردي على data_date إلى composite (data_date, source_type)
        Schema::table('data_imports', function (Blueprint $table) {
            $table->dropUnique(['data_date']);
            $table->unique(['data_date', 'source_type'], 'data_imports_date_source_unique');
        });
    }

    public function down(): void
    {
        Schema::table('data_imports', function (Blueprint $table) {
            $table->dropUnique('data_imports_date_source_unique');
            $table->unique('data_date');
        });

        DB::statement("ALTER TABLE data_imports MODIFY COLUMN source_type ENUM('excel','api') NOT NULL DEFAULT 'excel'");
    }
};
