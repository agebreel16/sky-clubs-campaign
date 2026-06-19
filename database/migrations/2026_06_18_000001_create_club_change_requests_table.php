<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_change_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('agent_id');
            $table->foreign('agent_id')
                ->references('agent_id')->on('agents')
                ->onDelete('cascade');

            $table->uuid('import_id')->nullable();
            $table->foreign('import_id')
                ->references('import_id')->on('data_imports')
                ->onDelete('set null');

            $table->uuid('from_club_id')->nullable();
            $table->foreign('from_club_id')
                ->references('club_id')->on('clubs')
                ->onDelete('set null');

            $table->uuid('to_club_id')->nullable();
            $table->foreign('to_club_id')
                ->references('club_id')->on('clubs')
                ->onDelete('set null');

            $table->enum('change_type', ['promotion', 'demotion']);

            $table->json('agent_stats_snapshot');

            $table->enum('status', ['pending', 'approved', 'rejected', 'auto_cancelled'])
                ->default('pending');

            $table->uuid('reviewed_by')->nullable();
            $table->foreign('reviewed_by')
                ->references('id')->on('users')
                ->onDelete('set null');

            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->index(['agent_id', 'status'], 'idx_ccr_agent_status');
            $table->index('status', 'idx_ccr_status');
            $table->index('created_at', 'idx_ccr_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_change_requests');
    }
};
