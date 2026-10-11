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
        Schema::table('conversations', function (Blueprint $table) {
            $table->index('business_id', 'conversations_business_id_perf_index');
            $table->index('status', 'conversations_status_perf_index');
            $table->index('last_message_at', 'conversations_last_message_at_perf_index');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->index('conversation_id', 'messages_conversation_id_perf_index');
            $table->index('created_at', 'messages_created_at_perf_index');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->index('business_id', 'contacts_business_id_perf_index');
            $table->index('platform_sender_id', 'contacts_platform_sender_id_perf_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('conversations_business_id_perf_index');
            $table->dropIndex('conversations_status_perf_index');
            $table->dropIndex('conversations_last_message_at_perf_index');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_conversation_id_perf_index');
            $table->dropIndex('messages_created_at_perf_index');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex('contacts_business_id_perf_index');
            $table->dropIndex('contacts_platform_sender_id_perf_index');
        });
    }
};
