<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->foreignId('business_id')->nullable()->constrained('businesses')->nullOnDelete();
            $table->string('idempotency_key')->unique();
            $table->string('provider'); // whatsapp, telegram, facebook, instagram
            $table->string('provider_message_id')->nullable();
            $table->string('status')->default('queued'); // queued, submitted, delivered, read, failed, unknown
            $table->string('error_category')->nullable(); // rate_limit, auth, network, provider, unknown
            $table->text('error_message')->nullable();
            $table->decimal('cost', 10, 4)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['provider', 'provider_message_id'], 'ma_provider_idx');
            $table->index(['business_id', 'status', 'created_at'], 'ma_business_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_attempts');
    }
};
