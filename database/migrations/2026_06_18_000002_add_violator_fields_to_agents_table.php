<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->boolean('is_violator')->default(false)->after('portal_token');
            $table->timestamp('violator_since')->nullable()->after('is_violator');
            $table->text('violator_reason')->nullable()->after('violator_since');

            $table->index('is_violator', 'idx_agents_is_violator');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropIndex('idx_agents_is_violator');
            $table->dropColumn(['is_violator', 'violator_since', 'violator_reason']);
        });
    }
};
