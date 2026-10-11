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
        Schema::table('team_members', function (Blueprint $table) {
            $table->string('role')->default('agent')->after('password'); // admin, supervisor, agent
            $table->boolean('is_active')->default(true)->after('role');
        });

        Schema::table('business_team_member', function (Blueprint $table) {
            $table->string('role')->nullable()->default('agent')->after('team_member_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_team_member', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('team_members', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active']);
        });
    }
};
