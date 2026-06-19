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
        Schema::table('agent_import_logs', function (Blueprint $table) {
            $table->json('success_details')->nullable()->after('error_details');
        });
    }

    public function down(): void
    {
        Schema::table('agent_import_logs', function (Blueprint $table) {
            $table->dropColumn('success_details');
        });
    }
};
