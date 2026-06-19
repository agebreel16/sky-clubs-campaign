<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->foreignUuid('distributor_id')
                ->nullable()
                ->after('current_club_id')
                ->constrained('distributors', 'id')
                ->nullOnDelete();

            $table->index('distributor_id', 'idx_agent_distributor');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropForeign(['distributor_id']);
            $table->dropIndex('idx_agent_distributor');
            $table->dropColumn('distributor_id');
        });
    }
};
