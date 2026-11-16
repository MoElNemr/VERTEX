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
        Schema::create('tiktok_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('open_id')->unique();
            $table->string('union_id')->nullable();
            $table->string('username')->nullable();
            $table->string('display_name')->nullable();
            $table->string('avatar_url')->nullable();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('refresh_expires_at')->nullable();
            $table->json('granted_scopes')->nullable();
            $table->enum('status', ['connected', 'disconnected'])->default('connected');
            $table->boolean('comment_reply_enabled')->default(false);
            $table->text('comment_reply_text')->nullable();
            $table->text('dm_message_text')->nullable();
            $table->json('comment_keywords')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiktok_accounts');
    }
};
