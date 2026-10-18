<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained('businesses')->nullOnDelete();
            $table->string('event_type'); // message.send, campaign.send, webhook.outbound, notification
            $table->string('aggregate_type')->nullable(); // Message, Campaign, etc.
            $table->unsignedBigInteger('aggregate_id')->nullable();
            $table->json('payload'); // committed event data
            $table->string('status')->default('pending'); // pending, processing, delivered, failed
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('max_attempts')->default(3);
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['status', 'next_attempt_at'], 'oe_dispatch_idx');
            $table->index(['aggregate_type', 'aggregate_id'], 'oe_aggregate_idx');
            $table->index(['business_id', 'event_type', 'created_at'], 'oe_business_event_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_events');
    }
};
