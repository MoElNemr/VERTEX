<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained('businesses')->nullOnDelete();
            $table->string('provider'); // meta, telegram, whatsapp, fawry
            $table->string('provider_event_id')->nullable(); // provider-assigned ID for deduplication
            $table->string('webhook_type')->nullable(); // e.g. messages, page_feed, comments
            $table->string('signature_result')->default('unknown'); // valid, invalid, missing, skipped
            $table->string('processing_status')->default('pending'); // pending, processed, failed, skipped
            $table->text('error_message')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['provider', 'provider_event_id'], 'wr_provider_event_idx');
            $table->index(['business_id', 'provider', 'received_at'], 'wr_business_provider_idx');
            $table->unique(['provider', 'provider_event_id'], 'wr_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_receipts');
    }
};
