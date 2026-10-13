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
        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('platform')->default('whatsapp'); // whatsapp, facebook, instagram, telegram
            $table->string('recipient_id'); // phone or PSID/chat_id
            $table->string('recipient_name')->nullable();
            $table->text('message_body')->nullable();
            $table->json('custom_data')->nullable();
            $table->string('status')->default('pending'); // pending, sent, failed, skipped
            $table->text('error_message')->nullable();
            $table->string('platform_message_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'status']);
            $table->index(['campaign_id', 'platform']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_recipients');
    }
};
