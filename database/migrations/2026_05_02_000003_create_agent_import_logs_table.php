<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_import_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('source_type', ['excel', 'api']);
            $table->string('stored_filepath')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('api_url')->nullable();
            $table->text('api_token')->nullable();
            $table->enum('status', ['pending', 'processing', 'success', 'failed'])->default('pending');
            $table->unsignedInteger('total_rows')->nullable();
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('rejected_count')->default(0);
            $table->unsignedInteger('errors_count')->default(0);
            $table->text('error_message')->nullable();
            $table->json('error_details')->nullable();
            $table->unsignedInteger('processing_duration_ms')->nullable();
            $table->foreignUuid('imported_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_import_logs');
    }
};
