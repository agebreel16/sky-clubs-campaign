<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distributors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 200);
            $table->string('phone', 20)->unique();
            $table->string('email', 255)->unique();
            $table->string('region', 100);
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active', 'idx_distributor_active');
            $table->index('region', 'idx_distributor_region');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distributors');
    }
};
