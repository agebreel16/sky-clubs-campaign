<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn('demotion_timer_start');
        });

        Schema::table('clubs', function (Blueprint $table) {
            $table->dropColumn('demotion_timer_days');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->timestamp('demotion_timer_start')->nullable();
        });

        Schema::table('clubs', function (Blueprint $table) {
            $table->unsignedInteger('demotion_timer_days')->default(7);
        });
    }
};
