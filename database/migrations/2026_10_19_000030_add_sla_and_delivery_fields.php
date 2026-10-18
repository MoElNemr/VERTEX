<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add SLA / resolution tracking to conversations
        Schema::table('conversations', function (Blueprint $table) {
            $table->timestamp('first_response_at')->nullable()->after('last_message_at');
            $table->timestamp('resolved_at')->nullable()->after('first_response_at');
            $table->timestamp('sla_due_at')->nullable()->after('resolved_at');
            $table->boolean('sla_breached')->default(false)->after('sla_due_at');
            $table->string('channel_connection_id')->nullable()->after('sla_breached');
            $table->index(['business_id', 'status', 'sla_due_at'], 'conv_sla_idx');
        });

        // Add delivery tracking fields to messages
        Schema::table('messages', function (Blueprint $table) {
            $table->string('provider_message_id')->nullable()->after('status');
            $table->timestamp('delivered_at')->nullable()->after('provider_message_id');
            $table->timestamp('read_at')->nullable()->after('delivered_at');
            $table->string('failure_reason')->nullable()->after('read_at');
            $table->index(['conversation_id', 'provider_message_id'], 'msg_provider_idx');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['provider_message_id', 'delivered_at', 'read_at', 'failure_reason']);
        });
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['first_response_at', 'resolved_at', 'sla_due_at', 'sla_breached', 'channel_connection_id']);
        });
    }
};
