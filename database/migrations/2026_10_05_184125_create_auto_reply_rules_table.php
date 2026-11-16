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
        Schema::create('auto_reply_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('platform', ['all', 'whatsapp', 'facebook', 'instagram', 'telegram', 'tiktok'])->default('all');
            $table->enum('trigger_type', ['keyword', 'welcome', 'fallback', 'comment'])->default('keyword');
            $table->enum('match_type', ['exact', 'contains', 'starts_with'])->default('contains');
            $table->json('keywords')->nullable();
            $table->text('reply_text');
            $table->text('private_dm_text')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('priority')->default(10);
            $table->timestamps();

            $table->index(['business_id', 'is_active', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_reply_rules');
    }
};
