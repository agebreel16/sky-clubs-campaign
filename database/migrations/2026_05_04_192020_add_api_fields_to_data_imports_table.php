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
        Schema::table('data_imports', function (Blueprint $table) {
            $table->string('api_url')->nullable()->after('stored_filepath');
            $table->text('api_token')->nullable()->after('api_url');
        });
    }

    public function down(): void
    {
        Schema::table('data_imports', function (Blueprint $table) {
            $table->dropColumn(['api_url', 'api_token']);
        });
    }
};
