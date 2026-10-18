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
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('notification_token')->nullable()->after('tags');
            $table->string('token_frequency')->nullable()->after('notification_token');
            $table->timestamp('token_expires_at')->nullable()->after('token_frequency');
            $table->string('optin_topic')->nullable()->after('token_expires_at');

            $table->index(['business_id', 'platform', 'token_expires_at'], 'contacts_token_expiry_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex('contacts_token_expiry_idx');
            $table->dropColumn([
                'notification_token',
                'token_frequency',
                'token_expires_at',
                'optin_topic',
            ]);
        });
    }
};
