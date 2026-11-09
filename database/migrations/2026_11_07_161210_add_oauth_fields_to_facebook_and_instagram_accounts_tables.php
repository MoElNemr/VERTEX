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
        Schema::table('facebook_accounts', function (Blueprint $table) {
            $table->string('meta_user_id')->nullable()->after('page_name');
            $table->enum('token_type', ['manual', 'oauth'])->default('oauth')->after('page_access_token');
            $table->timestamp('token_expires_at')->nullable()->after('token_type');
            $table->text('avatar_url')->nullable()->after('token_expires_at');
            $table->json('granted_scopes')->nullable()->after('avatar_url');
        });

        Schema::table('instagram_accounts', function (Blueprint $table) {
            $table->string('meta_user_id')->nullable()->after('username');
            $table->string('linked_facebook_page_id')->nullable()->after('meta_user_id');
            $table->enum('token_type', ['manual', 'oauth'])->default('oauth')->after('access_token');
            $table->timestamp('token_expires_at')->nullable()->after('token_type');
            $table->text('avatar_url')->nullable()->after('token_expires_at');
            $table->json('granted_scopes')->nullable()->after('avatar_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facebook_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'meta_user_id',
                'token_type',
                'token_expires_at',
                'avatar_url',
                'granted_scopes',
            ]);
        });

        Schema::table('instagram_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'meta_user_id',
                'linked_facebook_page_id',
                'token_type',
                'token_expires_at',
                'avatar_url',
                'granted_scopes',
            ]);
        });
    }
};
