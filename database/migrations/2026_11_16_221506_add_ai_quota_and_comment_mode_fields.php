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
        // 1. Add AI quota to plans table
        Schema::table('plans', function (Blueprint $table) {
            if (! Schema::hasColumn('plans', 'monthly_ai_quota')) {
                $table->integer('monthly_ai_quota')->nullable()->after('monthly_message_quota');
            }
        });

        // 2. Add AI quota to subscriptions table
        Schema::table('subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('subscriptions', 'ai_messages_limit')) {
                $table->integer('ai_messages_limit')->nullable()->after('messages_limit');
            }
            if (! Schema::hasColumn('subscriptions', 'ai_messages_used')) {
                $table->integer('ai_messages_used')->default(0)->after('messages_used');
            }
        });

        // 3. Add comment reply mode (fixed vs ai) to Facebook accounts
        Schema::table('facebook_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('facebook_accounts', 'comment_reply_mode')) {
                $table->string('comment_reply_mode')->default('fixed')->after('comment_reply_enabled');
            }
        });

        // 4. Add comment reply mode (fixed vs ai) to Instagram accounts
        Schema::table('instagram_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('instagram_accounts', 'comment_reply_mode')) {
                $table->string('comment_reply_mode')->default('fixed')->after('comment_reply_enabled');
            }
        });

        // 5. Add comment reply mode (fixed vs ai) to TikTok accounts
        Schema::table('tiktok_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('tiktok_accounts', 'comment_reply_mode')) {
                $table->string('comment_reply_mode')->default('fixed')->after('comment_reply_enabled');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'monthly_ai_quota')) {
                $table->dropColumn('monthly_ai_quota');
            }
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('subscriptions', 'ai_messages_limit')) {
                $cols[] = 'ai_messages_limit';
            }
            if (Schema::hasColumn('subscriptions', 'ai_messages_used')) {
                $cols[] = 'ai_messages_used';
            }
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('facebook_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('facebook_accounts', 'comment_reply_mode')) {
                $table->dropColumn('comment_reply_mode');
            }
        });

        Schema::table('instagram_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('instagram_accounts', 'comment_reply_mode')) {
                $table->dropColumn('comment_reply_mode');
            }
        });

        Schema::table('tiktok_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('tiktok_accounts', 'comment_reply_mode')) {
                $table->dropColumn('comment_reply_mode');
            }
        });
    }
};
