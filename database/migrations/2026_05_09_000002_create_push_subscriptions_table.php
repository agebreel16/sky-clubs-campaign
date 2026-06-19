<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('webpush.database_connection'))->create(config('webpush.table_name', 'push_subscriptions'), function (Blueprint $table) {
            $table->bigIncrements('id');
            // uuidMorphs instead of morphs — agent_id is UUID, not integer
            $table->uuidMorphs('subscribable', 'push_subscriptions_subscribable_morph_idx');
            $table->string('endpoint', 500)->unique();
            $table->string('public_key')->nullable();
            $table->string('auth_token')->nullable();
            $table->string('content_encoding')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection(config('webpush.database_connection'))->dropIfExists(config('webpush.table_name', 'push_subscriptions'));
    }
};
