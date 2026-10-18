<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->string('actor_type')->nullable(); // user, team_member, platform_admin, system
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->foreignId('business_id')->nullable()->constrained('businesses')->nullOnDelete();
            $table->string('action'); // e.g. login, impersonate, export, wallet_adjust, role_change
            $table->string('target_type')->nullable(); // e.g. User, Business, Campaign
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('request_id')->nullable();
            $table->timestamps();

            $table->index(['actor_type', 'actor_id', 'created_at'], 'ae_actor_idx');
            $table->index(['business_id', 'action', 'created_at'], 'ae_business_action_idx');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
