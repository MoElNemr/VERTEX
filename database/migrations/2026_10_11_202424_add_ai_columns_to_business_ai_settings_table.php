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
        Schema::table('business_ai_settings', function (Blueprint $table) {
            $table->boolean('ai_enabled')->default(false)->after('business_id');
            $table->string('ai_model')->default('gpt-4o-mini')->after('ai_enabled');
            $table->text('ai_personality')->nullable()->after('ai_model');
            $table->text('ai_instructions')->nullable()->after('ai_personality');
            $table->boolean('working_hours_only')->default(false)->after('ai_instructions');
            $table->unsignedTinyInteger('work_start_hour')->default(9)->after('working_hours_only');
            $table->unsignedTinyInteger('work_end_hour')->default(18)->after('work_start_hour');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_ai_settings', function (Blueprint $table) {
            $table->dropColumn([
                'ai_enabled',
                'ai_model',
                'ai_personality',
                'ai_instructions',
                'working_hours_only',
                'work_start_hour',
                'work_end_hour',
            ]);
        });
    }
};
