<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('provider'); // whatsapp, facebook, instagram, telegram
            $table->string('provider_account_id')->nullable(); // external account/page/bot ID
            $table->string('display_name')->nullable(); // human-readable label (page name, phone, bot username)
            $table->string('status')->default('disconnected'); // connected, disconnected, error, pending
            $table->json('capabilities')->nullable(); // array of supported features
            $table->json('credential_reference')->nullable(); // encrypted reference, never raw token
            $table->timestamp('last_health_check_at')->nullable();
            $table->string('health_status')->default('unknown'); // healthy, degraded, unknown, failed
            $table->text('health_message')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'provider', 'status'], 'cc_business_provider_idx');
            $table->unique(['business_id', 'provider', 'provider_account_id'], 'cc_unique_account');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_connections');
    }
};
