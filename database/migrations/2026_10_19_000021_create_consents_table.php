<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->string('channel'); // whatsapp, telegram, facebook, instagram
            $table->string('purpose'); // marketing, transactional, support
            $table->string('source')->nullable(); // opt_in_form, imported, comment_reply, api
            $table->text('proof')->nullable(); // description or reference of how consent was captured
            $table->timestamp('captured_at');
            $table->timestamp('withdrawn_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'contact_id', 'channel', 'purpose'], 'con_lookup_idx');
            $table->index(['business_id', 'is_active'], 'con_active_idx');
        });

        Schema::create('suppression_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('channel'); // whatsapp, telegram, facebook, instagram, email
            $table->string('identifier'); // phone number, chat ID, page-scoped PSID, email
            $table->string('reason')->nullable(); // unsubscribe, complaint, bounce, admin
            $table->string('scope')->default('marketing'); // marketing, all
            $table->timestamp('suppressed_at');
            $table->timestamps();

            $table->unique(['business_id', 'channel', 'identifier'], 'se_unique_suppression');
            $table->index(['business_id', 'channel'], 'se_business_channel_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppression_entries');
        Schema::dropIfExists('consents');
    }
};
