<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_imports', function (Blueprint $table) {
            $table->dropUnique('data_imports_date_source_unique');
        });
    }

    public function down(): void
    {
        Schema::table('data_imports', function (Blueprint $table) {
            $table->unique(['data_date', 'source_type'], 'data_imports_date_source_unique');
        });
    }
};
